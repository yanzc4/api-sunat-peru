<?php

declare(strict_types=1);

namespace App\Facturacion\Controllers;

use App\Facturacion\Config\Database;
use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Helpers\ResponseHelper;
use App\Facturacion\Models\EmpresaFacturacion;
use App\Facturacion\Repositories\EmpresaFacturacionRepository;
use App\Facturacion\Services\CertificadoService;
use App\Facturacion\Services\EncryptionService;
use PDO;

class EmpresaController
{
    private EmpresaFacturacionRepository $repo;
    private EncryptionService $encryption;
    private CertificadoService $certificadoService;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->repo = new EmpresaFacturacionRepository($pdo);
        $this->encryption = new EncryptionService();
        $this->certificadoService = new CertificadoService();
    }

    private function checkOwner(EmpresaFacturacion $empresa): void
    {
        if (!isset($_SESSION['usuario_id'])) return; // Es API, ya filtró token
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') return;
        if ($empresa->usuarioId !== $_SESSION['usuario_id']) {
            ResponseHelper::validationError('No tienes permisos sobre esta empresa.');
            exit;
        }
    }

    public function listar(): void
    {
        try {
            $empresas = $this->repo->findAll();
            $data = array_map(
                fn(EmpresaFacturacion $e) => $e->toArray(),
                $empresas
            );
            ResponseHelper::success($data);
        } catch (\Exception $e) {
            ResponseHelper::internalError('Error al listar empresas: ' . $e->getMessage());
        }
    }

    public function ver(string $id): void
    {
        try {
            $empresa = $this->repo->findById((int) $id);

            if (!$empresa) {
                ResponseHelper::notFound('Empresa no encontrada');
            }
            $this->checkOwner($empresa);

            ResponseHelper::success($empresa->toArray());
        } catch (\Exception $e) {
            ResponseHelper::internalError('Error al obtener empresa: ' . $e->getMessage());
        }
    }

    public function crear(): void
    {
        try {
            $data = $this->getJsonInput();

            $this->validateRequired($data, [
                'ruc', 'razon_social',
                'sol_usuario', 'sol_password'
            ]);

            $this->validateRuc($data['ruc']);

            if ($this->repo->existeRuc($data['ruc'])) {
                ResponseHelper::error(
                    'DUPLICATE_RUC',
                    "El RUC {$data['ruc']} ya está registrado",
                    422
                );
            }

            $empresa = new EmpresaFacturacion();
            $empresa->usuarioId = $_SESSION['usuario_id'] ?? null; // Asignar al usuario actual
            $empresa->ruc = $data['ruc'];
            $empresa->razonSocial = $data['razon_social'];
            $empresa->nombreComercial = $data['nombre_comercial'] ?? null;
            $empresa->direccion = $data['direccion'] ?? null;
            $empresa->ubigeo = $data['ubigeo'] ?? null;
            $empresa->departamento = $data['departamento'] ?? null;
            $empresa->provincia = $data['provincia'] ?? null;
            $empresa->distrito = $data['distrito'] ?? null;
            $empresa->solUsuario = $data['sol_usuario'];
            $empresa->solPassword = $this->encryption->encrypt($data['sol_password']);
            $empresa->certificadoPath = $data['certificado_path'] ?? '';
            
            $certPass = $data['certificado_password'] ?? '';
            $empresa->certificadoPassword = $certPass ? $this->encryption->encrypt($certPass) : '';
            
            $empresa->entorno = $data['entorno'] ?? 'beta';
            $empresa->activo = true;

            $id = $this->repo->create($empresa);

            $empresa->id = $id;
            ResponseHelper::success($empresa->toArray(), 201);

        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Exception $e) {
            ResponseHelper::internalError('Error al crear empresa: ' . $e->getMessage());
        }
    }

    public function editar(string $id): void
    {
        try {
            $empresa = $this->repo->findById((int) $id);

            if (!$empresa) {
                ResponseHelper::notFound('Empresa no encontrada');
            }
            $this->checkOwner($empresa);

            $data = $this->getJsonInput();

            $campos = [];

            if (isset($data['ruc'])) {
                $this->validateRuc($data['ruc']);
                $campos['ruc'] = $data['ruc'];
            }

            if (isset($data['razon_social'])) {
                $campos['razon_social'] = $data['razon_social'];
            }

            if (isset($data['nombre_comercial'])) {
                $campos['nombre_comercial'] = $data['nombre_comercial'];
            }

            if (isset($data['direccion'])) {
                $campos['direccion'] = $data['direccion'];
            }

            if (isset($data['ubigeo'])) {
                $campos['ubigeo'] = $data['ubigeo'];
            }

            if (isset($data['departamento'])) {
                $campos['departamento'] = $data['departamento'];
            }

            if (isset($data['provincia'])) {
                $campos['provincia'] = $data['provincia'];
            }

            if (isset($data['distrito'])) {
                $campos['distrito'] = $data['distrito'];
            }

            if (isset($data['sol_usuario'])) {
                $campos['sol_usuario'] = $data['sol_usuario'];
            }

            if (isset($data['sol_password'])) {
                $campos['sol_password'] = $this->encryption->encrypt($data['sol_password']);
            }

            if (isset($data['certificado_path'])) {
                $campos['certificado_path'] = $data['certificado_path'];
            }

            if (isset($data['certificado_password'])) {
                $campos['certificado_password'] = $this->encryption->encrypt($data['certificado_password']);
            }

            if (isset($data['entorno'])) {
                $campos['entorno'] = $data['entorno'];
            }

            if (isset($data['logo_path'])) {
                $campos['logo_path'] = $data['logo_path'];
            }

            if (isset($data['activo'])) {
                $campos['activo'] = $data['activo'] ? 1 : 0;
            }

            if (empty($campos)) {
                ResponseHelper::validationError('No hay campos para actualizar');
            }

            $this->repo->update((int) $id, $campos);

            $empresa = $this->repo->findById((int) $id);
            ResponseHelper::success($empresa->toArray());

        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Exception $e) {
            ResponseHelper::internalError('Error al actualizar empresa: ' . $e->getMessage());
        }
    }

    public function eliminar(string $id): void
    {
        try {
            $empresa = $this->repo->findById((int) $id);

            if (!$empresa) {
                ResponseHelper::notFound('Empresa no encontrada');
            }
            $this->checkOwner($empresa);

            $this->repo->delete((int) $id);

            ResponseHelper::success(['message' => 'Empresa eliminada correctamente']);

        } catch (\Exception $e) {
            ResponseHelper::internalError('Error al eliminar empresa: ' . $e->getMessage());
        }
    }

    public function subirCertificado(string $id): void
    {
        try {
            $empresa = $this->repo->findById((int) $id);

            if (!$empresa) {
                ResponseHelper::notFound('Empresa no encontrada');
            }
            $this->checkOwner($empresa);

            if (!isset($_FILES['certificado'])) {
                ResponseHelper::validationError('No se envió archivo de certificado');
            }

            $file = $_FILES['certificado'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                ResponseHelper::validationError('Error al subir archivo: ' . $file['error']);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

            $this->certificadoService->guardarCertificado(
                $empresa->ruc,
                $file['tmp_name'],
                $extension
            );

            $pathRelativo = 'storage/private/certificados/' . $empresa->ruc . '/certificado.' . strtolower($extension);

            $updateData = ['certificado_path' => $pathRelativo];
            
            if (isset($_POST['password']) && !empty($_POST['password'])) {
                $updateData['certificado_password'] = $this->encryption->encrypt($_POST['password']);
            }

            $this->repo->update((int) $id, $updateData);

            ResponseHelper::success([
                'message' => 'Certificado subido correctamente',
                'path' => $pathRelativo
            ]);

        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalError('Error al subir certificado: ' . $e->getMessage());
        }
    }

    public function subirLogo(string $id): void
    {
        try {
            $empresa = $this->repo->findById((int) $id);

            if (!$empresa) {
                ResponseHelper::notFound('Empresa no encontrada');
            }
            $this->checkOwner($empresa);

            if (!isset($_FILES['logo'])) {
                ResponseHelper::validationError('No se envió archivo de logo');
            }

            $file = $_FILES['logo'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                ResponseHelper::validationError('Error al subir archivo: ' . $file['error']);
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'svg'])) {
                ResponseHelper::validationError('El logo debe ser de tipo JPG, PNG o SVG');
            }

            $basePath = dirname(__DIR__, 4);
            $relDir = 'storage/public/empresas/' . $empresa->ruc;
            $absDir = $basePath . '/' . $relDir;

            if (!is_dir($absDir)) {
                mkdir($absDir, 0755, true);
            }

            $pathRelativo = $relDir . '/logo.' . $extension;
            $absPath = $absDir . '/logo.' . $extension;

            if (!move_uploaded_file($file['tmp_name'], $absPath)) {
                ResponseHelper::internalError('No se pudo guardar el archivo de logo');
            }

            $this->repo->update((int) $id, [
                'logo_path' => $pathRelativo,
            ]);

            ResponseHelper::success([
                'message' => 'Logo subido correctamente',
                'path' => $pathRelativo
            ]);

        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalError('Error al subir logo: ' . $e->getMessage());
        }
    }

    public function crearSerie(string $id): void
    {
        try {
            $empresa = $this->repo->findById((int) $id);

            if (!$empresa) {
                ResponseHelper::notFound('Empresa no encontrada');
            }
            $this->checkOwner($empresa);

            $data = $this->getJsonInput();

            $this->validateRequired($data, ['tipo_comprobante', 'serie']);

            $comprobanteRepo = new \App\Facturacion\Repositories\ComprobanteRepository(\App\Facturacion\Config\Database::getConnection());

            if ($comprobanteRepo->existeSerie($empresa->id, $data['tipo_comprobante'], $data['serie'])) {
                ResponseHelper::error(
                    'DUPLICATE_SERIE',
                    "La serie {$data['serie']} para el tipo de comprobante {$data['tipo_comprobante']} ya existe.",
                    422
                );
            }

            $correlativoInicial = isset($data['correlativo']) ? (int) $data['correlativo'] : 0;

            $serieId = $comprobanteRepo->crearSerie(
                $empresa->id,
                $data['tipo_comprobante'],
                $data['serie'],
                $correlativoInicial
            );

            ResponseHelper::success([
                'id' => $serieId,
                'empresa_id' => $empresa->id,
                'tipo_comprobante' => $data['tipo_comprobante'],
                'serie' => $data['serie'],
                'correlativo' => $correlativoInicial,
                'message' => 'Serie creada correctamente',
            ], 201);

        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (\Throwable $e) {
            ResponseHelper::internalError('Error al crear serie: ' . $e->getMessage());
        }
    }

    private function validateRequired(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new FacturacionException(
                    "El campo {$field} es obligatorio"
                );
            }
        }
    }

    private function validateRuc(string $ruc): void
    {
        if (!preg_match('/^\d{11}$/', $ruc)) {
            throw new FacturacionException(
                "El RUC debe tener exactamente 11 dígitos"
            );
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            ResponseHelper::validationError('JSON inválido');
        }

        return $data;
    }

}
