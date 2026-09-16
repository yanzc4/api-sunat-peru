<?php

declare(strict_types=1);

namespace App\Facturacion\Controllers;

use App\Facturacion\Config\Database;
use App\Facturacion\DTO\ComprobanteDTO;
use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Exceptions\SunatException;
use App\Facturacion\Helpers\ResponseHelper;
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
            $filtros = [];

            $authEmpresaId = \Flight::get('auth_empresa_id');
            if ($authEmpresaId) {
                $filtros['empresa_id'] = $authEmpresaId;
            } elseif (isset($_GET['empresa_id'])) {
                $filtros['empresa_id'] = (int) $_GET['empresa_id'];
            }

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
            ResponseHelper::internalError('Error al listar comprobantes: ' . $e->getMessage());
        }
    }

    public function ver(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante((int) $id);

            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $data = $comprobante->toArray();
            $data['pdf_url'] = '/api/facturacion/comprobantes/' . $id . '/pdf';
            $data['xml_url'] = '/api/facturacion/comprobantes/' . $id . '/xml';
            $data['cdr_url'] = '/api/facturacion/comprobantes/' . $id . '/cdr';

            ResponseHelper::success($data);
        } catch (\Throwable $e) {
            ResponseHelper::internalError('Error al obtener comprobante: ' . $e->getMessage());
        }
    }

    public function emitir(): void
    {
        try {
            $data = $this->getJsonInput();
            $dto = ComprobanteDTO::fromArray($data);
            $dto->validate();

            $comprobante = $this->facturacionService->emitir($dto);

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
                'message' => 'Comprobante creado. Usar POST /procesar para enviar a SUNAT.',
            ];

            ResponseHelper::success($response, 201);

        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalError('Error al emitir comprobante: ' . $e->getMessage());
        }
    }

    private function getAbsolutePath(?string $path): ?string
    {
        if (empty($path)) return null;
        if (strpos($path, 'D:') === 0 || strpos($path, 'C:') === 0 || strpos($path, '/') === 0) {
            return $path;
        }
        $pos = strpos($path, 'storage/');
        if ($pos !== false) {
            $path = substr($path, $pos);
        }
        return dirname(__DIR__, 4) . '/' . $path;
    }

    public function descargarPdf(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante((int) $id);

            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $formato = \Flight::request()->query->formato ?? 'a4';
            $pdfPath = $this->getAbsolutePath($comprobante->pdfPath);

            if ($formato === 'ticket') {
                $pdfPath = str_replace('.pdf', '-ticket.pdf', $pdfPath);
            }

            if (empty($pdfPath) || !file_exists($pdfPath)) {
                ResponseHelper::notFound('PDF no disponible en formato ' . $formato);
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($pdfPath) . '"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            exit;

        } catch (\Throwable $e) {
            ResponseHelper::internalError('Error al descargar PDF: ' . $e->getMessage());
        }
    }

    public function descargarXml(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante((int) $id);

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
            ResponseHelper::internalError('Error al descargar XML: ' . $e->getMessage());
        }
    }

    public function descargarCdr(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante((int) $id);

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
            ResponseHelper::internalError('Error al descargar CDR: ' . $e->getMessage());
        }
    }

    public function procesar(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->procesar((int) $id);

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
                'hash' => $comprobante->hashCpe,
                'xml_path' => $cleanPath($comprobante->xmlPath),
                'pdf_path' => $cleanPath($comprobante->pdfPath),
                'cdr_path' => $cleanPath($comprobante->cdrPath),
                'mensaje' => $comprobante->mensajeRespuesta,
            ];

            ResponseHelper::success($response);

        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (SunatException $e) {
            ResponseHelper::sunatError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalError('Error al procesar comprobante: ' . $e->getMessage());
        }
    }

    public function consultarEstado(string $id): void
    {
        try {
            $comprobante = $this->facturacionService->obtenerComprobante((int) $id);

            if (!$comprobante) {
                ResponseHelper::notFound('Comprobante no encontrado');
            }

            $empresa = $this->facturacionService->obtenerEmpresa($comprobante->empresaId);

            if (!$empresa) {
                ResponseHelper::notFound('Empresa no encontrada');
            }

            $sunatService = new SunatService(Database::getConnection());
            $resultado = $sunatService->consultarEstado(
                $empresa,
                $empresa->ruc,
                $comprobante->tipoComprobante,
                $comprobante->serie,
                $comprobante->correlativo
            );

            ResponseHelper::success($resultado);

        } catch (SunatException $e) {
            ResponseHelper::sunatError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalError('Error al consultar estado: ' . $e->getMessage());
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            ResponseHelper::validationError('JSON inválido');
        }

        $authEmpresaId = \Flight::get('auth_empresa_id');
        if ($authEmpresaId) {
            $data['empresa_id'] = $authEmpresaId;
        }

        return $data;
    }

}
