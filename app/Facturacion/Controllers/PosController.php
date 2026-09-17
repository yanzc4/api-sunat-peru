<?php

declare(strict_types=1);

namespace App\Facturacion\Controllers;

use App\Facturacion\Config\Database;
use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\DTO\ComprobanteDTO;
use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Exceptions\SunatException;
use App\Facturacion\Helpers\OwnedEmpresaScope;
use App\Facturacion\Helpers\ResponseHelper;
use App\Facturacion\Repositories\ComprobanteRepository;
use App\Facturacion\Services\DocumentLookupService;
use App\Facturacion\Services\FacturacionService;

final class PosController
{
    private \PDO $pdo;
    private FacturacionService $service;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->service = new FacturacionService($this->pdo);
    }

    public function series(): void
    {
        try {
            $empresaId = (int) (\Flight::request()->query->empresa_id ?? 0);
            OwnedEmpresaScope::require($this->pdo, $empresaId);
            $series = (new ComprobanteRepository($this->pdo))->findSeriesByEmpresa($empresaId);
            $series = array_values(array_filter(
                $series,
                static fn (array $serie): bool => in_array($serie['tipo_comprobante'], ['01', '03'], true)
            ));
            ResponseHelper::success($series);
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al listar series del POS');
        }
    }

    public function consultarDocumento(string $tipo, string $numero): void
    {
        try {
            ResponseHelper::success((new DocumentLookupService())->lookup($tipo, $numero));
        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error consultando documento');
        }
    }

    public function crearComprobante(): void
    {
        try {
            $data = $this->json();
            $empresaId = (int) ($data['empresa_id'] ?? 0);
            $empresa = OwnedEmpresaScope::require($this->pdo, $empresaId);
            $data['empresa_id'] = $empresa->id;
            if (!in_array((string) ($data['tipo_comprobante'] ?? ''), ['01', '03'], true)) {
                throw new FacturacionException('El facturador interno admite factura (01) y boleta (03).');
            }
            $dto = ComprobanteDTO::fromArray($data);
            $dto->validate();
            $comprobante = $this->service->emitir($dto);
            ResponseHelper::success([
                'id' => $comprobante->id,
                'tipo' => $comprobante->tipoComprobante,
                'serie' => $comprobante->serie,
                'numero' => $comprobante->getNumeroFormato(),
                'estado' => $comprobante->estado,
                'entorno' => $empresa->entorno,
            ], 201);
        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al crear venta interna');
        }
    }

    public function procesar(string $id): void
    {
        try {
            set_time_limit(120);
            $data = $this->json();
            $empresaId = (int) ($data['empresa_id'] ?? 0);
            $empresa = OwnedEmpresaScope::require($this->pdo, $empresaId);
            if (!$this->service->obtenerComprobante((int) $id, $empresaId)) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }
            $comprobante = $this->service->procesar((int) $id, $empresaId);
            ResponseHelper::success([
                'id' => $comprobante->id,
                'numero' => $comprobante->getNumeroFormato(),
                'estado' => $comprobante->estado,
                'entorno' => $empresa->entorno,
                'mensaje' => $comprobante->mensajeRespuesta,
                'pdf_disponible' => !empty($comprobante->pdfPath),
                'print_url' => '/api/facturacion/pos/comprobantes/' . $comprobante->id
                    . '/pdf?empresa_id=' . $empresaId,
            ]);
        } catch (SunatException $e) {
            ResponseHelper::sunatError($e->getMessage(), $e->getSunatCode());
        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al procesar venta interna');
        }
    }

    public function pdf(string $id): void
    {
        try {
            $empresaId = (int) (\Flight::request()->query->empresa_id ?? 0);
            OwnedEmpresaScope::require($this->pdo, $empresaId, false);
            $comprobante = $this->service->obtenerComprobante((int) $id, $empresaId);
            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }
            $format = strtolower(trim((string) (\Flight::request()->query->formato ?? 'ticket')));
            if (!in_array($format, ['a4', 'ticket'], true)) {
                ResponseHelper::validationError('Formato de impresión no válido');
            }
            $path = FacturacionConfig::getInstance()->resolveProjectPath($comprobante->pdfPath);
            if ($format === 'ticket' && $path !== null) {
                $path = preg_replace('/\.pdf$/i', '-ticket.pdf', $path) ?? $path;
            }
            if (!$path || !is_file($path)) {
                ResponseHelper::notFound('PDF no disponible');
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($path) . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al imprimir venta');
        }
    }

    public function venta(string $id): void
    {
        try {
            $empresaId = (int) (\Flight::request()->query->empresa_id ?? 0);
            OwnedEmpresaScope::require($this->pdo, $empresaId, false);
            $comprobante = $this->service->obtenerComprobante((int) $id, $empresaId);
            if (!$comprobante || !in_array($comprobante->tipoComprobante, ['01', '03'], true)) {
                ResponseHelper::notFound('Venta no encontrada');
            }
            $data = $comprobante->toArray();
            $data['detalles'] = array_map(
                static fn ($detalle): array => $detalle->toArray(),
                $comprobante->detalles
            );
            $data['pdf_disponible'] = !empty($comprobante->pdfPath);
            ResponseHelper::success($data);
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al consultar venta');
        }
    }

    private function json(): array
    {
        $data = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($data)) {
            ResponseHelper::validationError('JSON inválido');
        }
        return $data;
    }
}
