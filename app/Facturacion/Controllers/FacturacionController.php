<?php

declare(strict_types=1);

namespace App\Facturacion\Controllers;

use App\Facturacion\Config\Database;
use App\Facturacion\DTO\ComprobanteDTO;
use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Exceptions\SunatException;
use App\Facturacion\Helpers\ResponseHelper;
use App\Facturacion\Helpers\ApiAuthPolicy;
use App\Facturacion\Models\Comprobante;
use App\Facturacion\Repositories\ComprobanteRepository;
use App\Facturacion\Services\FacturacionService;
use App\Facturacion\Services\SunatService;
use PDO;

class FacturacionController
{
    private FacturacionService $facturacionService;
    private SunatService $sunatService;
    private ComprobanteRepository $comprobanteRepo;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->facturacionService = new FacturacionService($pdo);
        $this->sunatService = new SunatService($pdo);
        $this->comprobanteRepo = new ComprobanteRepository($pdo);
    }

    public function listar(): void
    {
        try {
            $filtros = ['empresa_id' => $this->authenticatedEmpresaId()];

            if (isset($_GET['estado'])) {
                $filtros['estado'] = $_GET['estado'];
            }

            if (isset($_GET['tipo_comprobante'])) {
                $filtros['tipo_comprobante'] = $_GET['tipo_comprobante'];
            }

            $comprobantes = $this->facturacionService->listarComprobantes($filtros);
            $data = array_map(
                fn(Comprobante $c) => $c->toArray(),
                $comprobantes
            );

            ResponseHelper::success($data);
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al listar comprobantes');
        }
    }

    public function ver(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante(
                (int) $id,
                $this->authenticatedEmpresaId()
            );

            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $data = $comprobante->toArray();
            $data['pdf_url'] = '/api/facturacion/comprobantes/' . $id . '/pdf';
            $data['xml_url'] = '/api/facturacion/comprobantes/' . $id . '/xml';
            $data['cdr_url'] = '/api/facturacion/comprobantes/' . $id . '/cdr';

            ResponseHelper::success($data);
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al obtener comprobante');
        }
    }

    public function emitir(): void
    {
        try {
            $data = $this->getJsonInput();
            $dto = ComprobanteDTO::fromArray($data);
            $dto->validate();

            $comprobante = $this->facturacionService->emitir($dto);
            $empresa = $this->facturacionService->obtenerEmpresa($dto->empresaId);

            $response = [
                'id' => $comprobante->id,
                'tipo' => $comprobante->tipoComprobante,
                'serie' => $comprobante->serie,
                'correlativo' => str_pad(
                    (string) $comprobante->correlativo,
                    8,
                    '0',
                    STR_PAD_LEFT
                ),
                'numero' => $comprobante->getNumeroFormato(),
                'estado' => $comprobante->estado,
                'entorno' => $empresa?->entorno,
                'message' => 'Comprobante creado. Usar POST /procesar para enviar a SUNAT.',
            ];

            ResponseHelper::success($response, 201);

        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al emitir comprobante');
        }
    }

    private function getAbsolutePath(?string $path): ?string
    {
        return \App\Facturacion\Config\FacturacionConfig::getInstance()
            ->resolveProjectPath($path);
    }

    public function descargarPdf(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante(
                (int) $id,
                $this->authenticatedEmpresaId()
            );

            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $formato = \Flight::request()->query->formato ?? 'a4';
            $disposicion = strtolower(trim((string) (
                \Flight::request()->query->disposicion ?? 'attachment'
            )));

            if (!in_array($disposicion, ['attachment', 'inline'], true)) {
                ResponseHelper::validationError(
                    'La disposición debe ser attachment o inline'
                );
            }

            $pdfPath = $this->getAbsolutePath($comprobante->pdfPath);

            if ($formato === 'ticket') {
                $pdfPath = str_replace('.pdf', '-ticket.pdf', $pdfPath);
            }

            if (empty($pdfPath) || !file_exists($pdfPath)) {
                ResponseHelper::notFound('PDF no disponible en formato ' . $formato);
            }

            header('Content-Type: application/pdf');
            header(
                'Content-Disposition: ' . $disposicion
                . '; filename="' . basename($pdfPath) . '"'
            );
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            exit;

        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al descargar PDF');
        }
    }

    public function descargarXml(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante(
                (int) $id,
                $this->authenticatedEmpresaId()
            );

            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $xmlPath = $this->getAbsolutePath($comprobante->xmlPath);

            if (empty($xmlPath) || !file_exists($xmlPath)) {
                ResponseHelper::notFound('XML no disponible');
            }

            header('Content-Type: application/xml');
            header('Content-Disposition: attachment; filename="' . basename($xmlPath) . '"');
            header('Content-Length: ' . filesize($xmlPath));
            readfile($xmlPath);
            exit;

        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al descargar XML');
        }
    }

    public function descargarCdr(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante(
                (int) $id,
                $this->authenticatedEmpresaId()
            );

            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $cdrPath = $this->getAbsolutePath($comprobante->cdrPath);

            if (empty($cdrPath) || !file_exists($cdrPath)) {
                ResponseHelper::notFound('CDR no disponible');
            }

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($cdrPath) . '"');
            header('Content-Length: ' . filesize($cdrPath));
            readfile($cdrPath);
            exit;

        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al descargar CDR');
        }
    }

    public function procesar(string $id): void
    {
        try {
            $empresaId = $this->authenticatedEmpresaId();
            if (!$this->facturacionService->obtenerComprobante((int) $id, $empresaId)) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $comprobante = $this->facturacionService->procesar(
                (int) $id,
                $empresaId
            );
            $empresa = $this->facturacionService->obtenerEmpresa($empresaId);

            $cleanPath = function($path) {
                if (empty($path)) return null;
                $pos = strpos($path, 'storage/');
                return $pos !== false ? substr($path, $pos) : $path;
            };

            $response = [
                'id' => $comprobante->id,
                'tipo' => $comprobante->tipoComprobante,
                'serie' => $comprobante->serie,
                'correlativo' => str_pad((string) $comprobante->correlativo, 8, '0', STR_PAD_LEFT),
                'numero' => $comprobante->getNumeroFormato(),
                'estado' => $comprobante->estado,
                'entorno' => $empresa?->entorno,
                'hash' => $comprobante->hashCpe,
                'xml_path' => $cleanPath($comprobante->xmlPath),
                'pdf_path' => $cleanPath($comprobante->pdfPath),
                'cdr_path' => $cleanPath($comprobante->cdrPath),
                'mensaje' => $comprobante->mensajeRespuesta,
            ];

            ResponseHelper::success($response);

        } catch (SunatException $e) {
            ResponseHelper::sunatError($e->getMessage(), $e->getSunatCode());
        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al procesar comprobante');
        }
    }

    public function consultarEstado(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante(
                (int) $id,
                $this->authenticatedEmpresaId()
            );

            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $empresa = $this->facturacionService->obtenerEmpresa($comprobante->empresaId);

            if (!$empresa) {
                ResponseHelper::notFound('Empresa no encontrada');
            }

            $sunatService = new SunatService(Database::getConnection());
            $resultado = $sunatService->consultarEstadoComprobante($empresa, $comprobante);

            ResponseHelper::success($resultado);

        } catch (SunatException $e) {
            ResponseHelper::sunatError($e->getMessage(), $e->getSunatCode());
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al consultar estado');
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            ResponseHelper::validationError('JSON inválido');
        }

        // La empresa siempre proviene del token; cualquier valor del cliente se sobrescribe.
        $data = ApiAuthPolicy::bindEmpresa($data, $this->authenticatedEmpresaId());

        return $data;
    }

    private function authenticatedEmpresaId(): int
    {
        $empresaId = (int) (\Flight::get('auth_empresa_id') ?? 0);
        if ($empresaId <= 0) {
            ResponseHelper::unauthorized('Token empresarial requerido');
        }

        return $empresaId;
    }

}
