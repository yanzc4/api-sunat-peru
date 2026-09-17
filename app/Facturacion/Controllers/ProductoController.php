<?php

declare(strict_types=1);

namespace App\Facturacion\Controllers;

use App\Facturacion\Config\Database;
use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Helpers\OwnedEmpresaScope;
use App\Facturacion\Helpers\ResponseHelper;
use App\Facturacion\Repositories\ProductoRepository;
use PDOException;

final class ProductoController
{
    private const UNIDADES = ['NIU', 'ZZ', 'BX', 'CJ', 'CT', 'DZN', 'GRM', 'KG', 'L', 'M', 'M2', 'M3', 'ML', 'MT', 'PA', 'PR', 'RO', 'SET', 'TN', 'UD', 'UN', 'YRD'];
    private const AFECTACIONES = ['10', '20', '30', '21'];

    private \PDO $pdo;
    private ProductoRepository $repo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->repo = new ProductoRepository($this->pdo);
    }

    public function crear(): void
    {
        try {
            $data = $this->json();
            $empresaId = (int) ($data['empresa_id'] ?? 0);
            OwnedEmpresaScope::require($this->pdo, $empresaId);
            $clean = $this->validate($data);
            $id = $this->repo->create($empresaId, $clean);
            $producto = $this->repo->findByIdAndEmpresa($id, $empresaId);
            ResponseHelper::success($producto?->toArray() ?? ['id' => $id], 201);
        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                ResponseHelper::error('DUPLICATE_CODE', 'Ya existe un producto con ese código.', 422);
            }
            ResponseHelper::internalException($e, 'Error al crear producto');
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al crear producto');
        }
    }

    public function editar(string $id): void
    {
        try {
            $data = $this->json();
            $empresaId = (int) ($data['empresa_id'] ?? 0);
            OwnedEmpresaScope::require($this->pdo, $empresaId);
            if (!$this->repo->findByIdAndEmpresa((int) $id, $empresaId)) {
                ResponseHelper::notFound('Producto no encontrado');
            }
            $this->repo->update((int) $id, $empresaId, $this->validate($data));
            ResponseHelper::success($this->repo->findByIdAndEmpresa((int) $id, $empresaId)?->toArray() ?? []);
        } catch (FacturacionException $e) {
            ResponseHelper::validationError($e->getMessage());
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                ResponseHelper::error('DUPLICATE_CODE', 'Ya existe un producto con ese código.', 422);
            }
            ResponseHelper::internalException($e, 'Error al editar producto');
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al editar producto');
        }
    }

    public function eliminar(string $id): void
    {
        try {
            $data = $this->json();
            $empresaId = (int) ($data['empresa_id'] ?? 0);
            OwnedEmpresaScope::require($this->pdo, $empresaId);
            if (!$this->repo->delete((int) $id, $empresaId)) {
                ResponseHelper::notFound('Producto no encontrado');
            }
            ResponseHelper::success(['message' => 'Producto eliminado']);
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al eliminar producto');
        }
    }

    public function buscar(): void
    {
        try {
            $empresaId = (int) (\Flight::request()->query->empresa_id ?? 0);
            OwnedEmpresaScope::require($this->pdo, $empresaId);
            $search = trim((string) (\Flight::request()->query->q ?? ''));
            $page = max(1, (int) (\Flight::request()->query->page ?? 1));
            $result = $this->repo->searchActive($empresaId, $search, $page, 10);
            ResponseHelper::success([
                'results' => array_map(static fn ($product): array => [
                    'id' => $product->id,
                    'text' => $product->codigo . ' · ' . $product->descripcion,
                    'codigo' => $product->codigo,
                    'descripcion' => $product->descripcion,
                    'unidad' => $product->unidad,
                    'precio_unitario' => $product->precioUnitario,
                    'afectacion_igv' => $product->afectacionIgv,
                ], $result['items']),
                'pagination' => ['more' => $result['more']],
                'total' => $result['total'],
            ]);
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al buscar productos');
        }
    }

    private function validate(array $data): array
    {
        $codigo = strtoupper(trim((string) ($data['codigo'] ?? '')));
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        $unidad = strtoupper(trim((string) ($data['unidad'] ?? 'NIU')));
        $afectacion = trim((string) ($data['afectacion_igv'] ?? '10'));
        $precio = filter_var($data['precio_unitario'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($codigo === '' || mb_strlen($codigo) > 50) {
            throw new FacturacionException('El código es obligatorio y admite hasta 50 caracteres.');
        }
        if ($descripcion === '' || mb_strlen($descripcion) > 255) {
            throw new FacturacionException('La descripción es obligatoria y admite hasta 255 caracteres.');
        }
        if (!in_array($unidad, self::UNIDADES, true)) {
            throw new FacturacionException('Unidad de medida no válida.');
        }
        if (!in_array($afectacion, self::AFECTACIONES, true)) {
            throw new FacturacionException('Afectación IGV no válida.');
        }
        if ($precio === false || $precio < 0) {
            throw new FacturacionException('El precio unitario debe ser un número mayor o igual a cero.');
        }
        return [
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'unidad' => $unidad,
            'precio_unitario' => round((float) $precio, 2),
            'afectacion_igv' => $afectacion,
            'activo' => filter_var($data['activo'] ?? true, FILTER_VALIDATE_BOOL),
        ];
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
