<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\DTO\ComprobanteDTO;
use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Models\Comprobante;
use App\Facturacion\Models\DetalleComprobante;
use App\Facturacion\Models\EmpresaFacturacion;
use App\Facturacion\Repositories\ComprobanteRepository;
use App\Facturacion\Repositories\EmpresaFacturacionRepository;
use PDO;

class FacturacionService
{
    private PDO $pdo;
    private EmpresaFacturacionRepository $empresaRepo;
    private ComprobanteRepository $comprobanteRepo;
    private EncryptionService $encryption;
    private CertificadoService $certificadoService;
    private FacturacionConfig $config;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->empresaRepo = new EmpresaFacturacionRepository($pdo);
        $this->comprobanteRepo = new ComprobanteRepository($pdo);
        $this->encryption = new EncryptionService();
        $this->certificadoService = new CertificadoService();
        $this->config = FacturacionConfig::getInstance();
    }

    public function emitir(ComprobanteDTO $dto): Comprobante
    {
        $dto->validate();

        $empresa = $this->validarEmpresa($dto->empresaId);
        $this->validarCertificado($empresa);

        $comprobante = $this->crearComprobante($dto, $empresa);

        try {
            $this->pdo->beginTransaction();

            $correlativo = $this->comprobanteRepo->obtenerCorrelativo(
                $empresa->id,
                $dto->tipoComprobante,
                $dto->serie
            );

            $comprobante->correlativo = $correlativo;
            $comprobante->estado = 'pendiente';

            $comprobante->id = $this->comprobanteRepo->create($comprobante);

            $this->guardarDetalles($comprobante->id, $dto);

            $this->pdo->commit();

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $comprobante;
    }

    public function procesar(int $comprobanteId, int $empresaId): Comprobante
    {
        $comprobante = $this->comprobanteRepo->findByIdAndEmpresa($comprobanteId, $empresaId);

        if (!$comprobante) {
            throw new FacturacionException(
                "Comprobante no encontrado: {$comprobanteId}"
            );
        }

        if (!in_array($comprobante->estado, ['pendiente', 'generando', 'error'])) {
            throw new FacturacionException(
                "El comprobante no puede ser procesado. Estado actual: {$comprobante->estado}"
            );
        }

        $empresa = $this->validarEmpresa($comprobante->empresaId);
        $detalles = $comprobante->detalles;

        if (empty($detalles)) {
            throw new FacturacionException(
                "El comprobante no tiene detalles/items"
            );
        }

        // 1. Generar XML UBL 2.1
        $this->comprobanteRepo->updateEstado($comprobanteId, 'generando');

        $xmlService = new XmlService();
        try {
            $xml = $xmlService->generarXml($comprobante, $empresa, $detalles);
            $nombreArchivo = sprintf(
                '%s-%s-%s',
                $empresa->ruc,
                $comprobante->tipoComprobante,
                $comprobante->getNumeroFormato()
            );

            $xmlPath = $xmlService->guardarXml(
                $xml,
                $empresa->ruc,
                $comprobante->fechaEmision,
                $nombreArchivo
            );
        } catch (\Throwable $e) {
            $this->comprobanteRepo->updateEstado(
                $comprobanteId,
                'error',
                null,
                'Error al generar XML: ' . $e->getMessage()
            );
            throw $e;
        }

        // 2. Firmar XML
        $firmaService = new FirmaService();
        try {
            $xmlFirmado = $firmaService->firmar($xml, $empresa);
            $hash = $firmaService->generarHash($xmlFirmado);

            $this->comprobanteRepo->updateArchivos(
                $comprobanteId,
                $xmlPath,
                null,
                null,
                $hash
            );
        } catch (\Throwable $e) {
            $this->comprobanteRepo->updateEstado(
                $comprobanteId,
                'error',
                null,
                'Error al firmar XML: ' . $e->getMessage()
            );
            throw $e;
        }

        // 3. Enviar a SUNAT
        $sunatService = new SunatService($this->pdo);
        try {
            $resultado = $sunatService->enviar(
                $comprobante,
                $empresa,
                $xmlFirmado,
                $hash
            );
        } catch (\Throwable $e) {
            $this->comprobanteRepo->updateEstado(
                $comprobanteId,
                'error',
                null,
                'Error al enviar a SUNAT: ' . $e->getMessage()
            );
            throw $e;
        }

        // 4. Generar PDF (Ambos formatos: A4 y ticket)
        $pdfService = new PdfService();
        try {
            // Se genera el A4, que es la ruta que se guardará en la base de datos
            $pdfPath = $pdfService->generarPdf(
                $comprobante,
                $empresa,
                $detalles,
                $hash,
                'a4'
            );

            // También generamos silenciosamente el ticket en el mismo directorio (mismo path + '-ticket.pdf')
            $pdfService->generarPdf(
                $comprobante,
                $empresa,
                $detalles,
                $hash,
                'ticket'
            );

            $this->comprobanteRepo->updateArchivos(
                $comprobanteId,
                null,
                $pdfPath,
                null,
                null
            );
        } catch (\Throwable $e) {
            // PDF falla no es crítico, el comprobante ya fue aceptado
            error_log("Error generando PDF: " . $e->getMessage());
        }

        $actualizado = $this->comprobanteRepo->findByIdAndEmpresa($comprobanteId, $empresaId);
        if ($actualizado === null) {
            throw new \RuntimeException('El comprobante procesado ya no está disponible');
        }

        return $actualizado;
    }

    private function validarEmpresa(int $empresaId): EmpresaFacturacion
    {
        $empresa = $this->empresaRepo->findById($empresaId);

        if (!$empresa) {
            throw new FacturacionException(
                "Empresa de facturación no encontrada para empresa_id: {$empresaId}"
            );
        }

        if (!$empresa->activo) {
            throw new FacturacionException(
                "La empresa de facturación no está activa"
            );
        }

        SunatEnvironment::assertSupported($empresa->entorno);

        return $empresa;
    }

    private function validarCertificado(EmpresaFacturacion $empresa): void
    {
        try {
            $this->certificadoService->validate(
                $empresa->ruc,
                $empresa->certificadoPath,
                $empresa->certificadoPassword
            );
        } catch (FacturacionException $e) {
            throw new FacturacionException(
                "Error validando certificado: " . $e->getMessage()
            );
        }
    }

    private function crearComprobante(
        ComprobanteDTO $dto,
        EmpresaFacturacion $empresa
    ): Comprobante {
        $comprobante = new Comprobante();
        $comprobante->empresaId = $empresa->id;
        $comprobante->tipoComprobante = $dto->tipoComprobante;
        $comprobante->serie = $dto->serie;
        $comprobante->correlativo = 0;
        $comprobante->fechaEmision = $dto->getFechaEmision();
        $comprobante->horaEmision = $dto->getHoraEmision();
        $comprobante->moneda = $dto->moneda;
        $comprobante->clienteTipoDocumento = $dto->clienteTipoDocumento;
        $comprobante->clienteNumeroDocumento = $dto->clienteNumeroDocumento;
        $comprobante->clienteNombre = $dto->clienteNombre;
        $comprobante->clienteDireccion = $dto->clienteDireccion;
        $comprobante->subtotal = $dto->subtotalCalculado ?? 0;
        $comprobante->igv = $dto->igvCalculado ?? 0;
        $comprobante->total = $dto->totalCalculado ?? 0;
        $comprobante->estado = 'pendiente';

        return $comprobante;
    }

    private function guardarDetalles(int $comprobanteId, ComprobanteDTO $dto): void
    {
        foreach ($dto->items as $item) {
            $detalle = new DetalleComprobante();
            $detalle->comprobanteId = $comprobanteId;
            $detalle->codigoProducto = $item->codigo;
            $detalle->descripcion = $item->descripcion;
            $detalle->unidad = $item->unidad;
            $detalle->cantidad = $item->cantidad;
            $detalle->precioUnitario = $item->precioUnitario;
            $detalle->valorUnitario = $item->valorUnitario;
            $detalle->subtotal = $item->subtotal;
            $detalle->igv = $item->igv;
            $detalle->total = $item->total;
            $detalle->afectacionIgv = $item->afectacionIgv;

            $this->comprobanteRepo->createDetalle($detalle);
        }
    }

    public function obtenerComprobante(int $id, int $empresaId): ?Comprobante
    {
        return $this->comprobanteRepo->findByIdAndEmpresa($id, $empresaId);
    }

    public function listarComprobantes(array $filtros = []): array
    {
        return $this->comprobanteRepo->findAll($filtros);
    }

    public function obtenerEmpresa(int $empresaId): ?EmpresaFacturacion
    {
        return $this->empresaRepo->findById($empresaId);
    }
}
