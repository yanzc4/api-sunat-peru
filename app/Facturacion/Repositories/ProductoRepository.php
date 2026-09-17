<?php

declare(strict_types=1);

namespace App\Facturacion\Repositories;

use App\Facturacion\Models\Producto;
use PDO;

final class ProductoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Producto> */
    public function findByEmpresa(int $empresaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM productos_facturacion WHERE empresa_id = :empresa_id ORDER BY id DESC'
        );
        $stmt->execute([':empresa_id' => $empresaId]);
        return array_map(
            static fn (array $row): Producto => Producto::fromArray($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function findByIdAndEmpresa(int $id, int $empresaId): ?Producto
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM productos_facturacion WHERE id = :id AND empresa_id = :empresa_id LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':empresa_id' => $empresaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Producto::fromArray($row) : null;
    }

    /** @return array{items: array<int, Producto>, total: int, more: bool} */
    public function searchActive(int $empresaId, string $search, int $page, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $conditions = ['empresa_id = :empresa_id', 'activo = 1'];
        $params = [':empresa_id' => $empresaId];
        if ($search !== '') {
            $conditions[] = '(codigo LIKE :codigo OR descripcion LIKE :descripcion)';
            $params[':codigo'] = '%' . $search . '%';
            $params[':descripcion'] = '%' . $search . '%';
        }
        $where = implode(' AND ', $conditions);
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM productos_facturacion WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM productos_facturacion WHERE ' . $where .
            ' ORDER BY descripcion ASC, codigo ASC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':empresa_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(
                static fn (array $row): Producto => Producto::fromArray($row),
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            ),
            'total' => $total,
            'more' => $offset + $perPage < $total,
        ];
    }

    public function create(int $empresaId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO productos_facturacion
                (empresa_id, codigo, descripcion, unidad, precio_unitario, afectacion_igv, activo, created_at, updated_at)
             VALUES
                (:empresa_id, :codigo, :descripcion, :unidad, :precio, :afectacion, :activo, NOW(), NOW())'
        );
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':codigo' => $data['codigo'],
            ':descripcion' => $data['descripcion'],
            ':unidad' => $data['unidad'],
            ':precio' => $data['precio_unitario'],
            ':afectacion' => $data['afectacion_igv'],
            ':activo' => $data['activo'] ? 1 : 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $empresaId, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE productos_facturacion SET
                codigo = :codigo, descripcion = :descripcion, unidad = :unidad,
                precio_unitario = :precio, afectacion_igv = :afectacion,
                activo = :activo, updated_at = NOW()
             WHERE id = :id AND empresa_id = :empresa_id'
        );
        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId,
            ':codigo' => $data['codigo'],
            ':descripcion' => $data['descripcion'],
            ':unidad' => $data['unidad'],
            ':precio' => $data['precio_unitario'],
            ':afectacion' => $data['afectacion_igv'],
            ':activo' => $data['activo'] ? 1 : 0,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $empresaId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM productos_facturacion WHERE id = :id AND empresa_id = :empresa_id'
        );
        $stmt->execute([':id' => $id, ':empresa_id' => $empresaId]);
        return $stmt->rowCount() > 0;
    }
}
