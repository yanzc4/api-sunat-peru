<?php

declare(strict_types=1);

namespace App\Facturacion\Repositories;

use App\Facturacion\Models\EmpresaFacturacion;
use App\Facturacion\Exceptions\FacturacionException;
use PDO;

class EmpresaFacturacionRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?EmpresaFacturacion
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM empresas_facturacion WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return EmpresaFacturacion::fromArray($row);
    }

    public function findByRuc(string $ruc): ?EmpresaFacturacion
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM empresas_facturacion WHERE ruc = :ruc"
        );
        $stmt->execute([':ruc' => $ruc]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return EmpresaFacturacion::fromArray($row);
    }

    public function findByUsuarioId(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM empresas_facturacion WHERE usuario_id = :usuario_id");
        $stmt->execute([':usuario_id' => $usuarioId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $empresas = [];
        foreach ($rows as $row) {
            $empresas[] = EmpresaFacturacion::fromArray($row);
        }
        return $empresas;
    }

    public function findAll(bool $soloActivos = true): array
    {
        $sql = "SELECT * FROM empresas_facturacion";
        if ($soloActivos) {
            $sql .= " WHERE activo = 1";
        }
        $sql .= " ORDER BY razon_social ASC";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $row) => EmpresaFacturacion::fromArray($row),
            $rows
        );
    }

    /**
     * Devuelve empresas paginadas para el dashboard. Cuando se recibe un
     * usuario, el ámbito queda limitado a las empresas que le pertenecen.
     *
     * @return array{items: array<int, EmpresaFacturacion>, total: int, page: int, per_page: int, total_pages: int}
     */
    public function searchPaginated(?int $usuarioId, string $search, int $page, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $search = trim($search);
        $conditions = [];
        $params = [];

        if ($usuarioId !== null) {
            $conditions[] = 'usuario_id = :usuario_id';
            $params[':usuario_id'] = $usuarioId;
        }

        if ($search !== '') {
            // MySQL con prepares nativos no permite reutilizar el mismo
            // marcador nombrado más de una vez dentro de una sentencia.
            $conditions[] = '(nombre_comercial LIKE :search_nombre OR ruc LIKE :search_ruc)';
            $params[':search_nombre'] = '%' . $search . '%';
            $params[':search_ruc'] = '%' . $search . '%';
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM empresas_facturacion' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM empresas_facturacion' . $where .
            ' ORDER BY COALESCE(NULLIF(nombre_comercial, \'\'), razon_social) ASC, id ASC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':usuario_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(
                fn(array $row) => EmpresaFacturacion::fromArray($row),
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            ),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public function create(EmpresaFacturacion $empresa): int
    {
        $this->validarRuc($empresa->ruc);

        if ($this->existeRuc($empresa->ruc)) {
            throw new FacturacionException(
                "El RUC {$empresa->ruc} ya está registrado"
            );
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO empresas_facturacion (
                usuario_id, ruc, razon_social, nombre_comercial,
                direccion, ubigeo, departamento, provincia, distrito,
                sol_usuario, sol_password,
                certificado_path, certificado_password, logo_path,
                entorno, activo, created_at, updated_at
            ) VALUES (
                :usuario_id, :ruc, :razon_social, :nombre_comercial,
                :direccion, :ubigeo, :departamento, :provincia, :distrito,
                :sol_usuario, :sol_password,
                :certificado_path, :certificado_password, :logo_path,
                :entorno, :activo, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':usuario_id' => $empresa->usuarioId,
            ':ruc' => $empresa->ruc,
            ':razon_social' => $empresa->razonSocial,
            ':nombre_comercial' => $empresa->nombreComercial,
            ':direccion' => $empresa->direccion,
            ':ubigeo' => $empresa->ubigeo,
            ':departamento' => $empresa->departamento,
            ':provincia' => $empresa->provincia,
            ':distrito' => $empresa->distrito,
            ':sol_usuario' => $empresa->solUsuario,
            ':sol_password' => $empresa->solPassword,
            ':certificado_path' => $empresa->certificadoPath,
            ':certificado_password' => $empresa->certificadoPassword,
            ':logo_path' => $empresa->logoPath ?? null,
            ':entorno' => $empresa->entorno,
            ':activo' => $empresa->activo ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $datos): bool
    {
        if (isset($datos['ruc'])) {
            $this->validarRuc($datos['ruc']);

            $existente = $this->findByRuc($datos['ruc']);
            if ($existente && $existente->id !== $id) {
                throw new FacturacionException(
                    "El RUC {$datos['ruc']} ya está registrado"
                );
            }
        }

        $camposPermitidos = [
            'ruc', 'razon_social', 'nombre_comercial', 'direccion',
            'ubigeo', 'departamento', 'provincia', 'distrito',
            'sol_usuario', 'sol_password',
            'certificado_path', 'certificado_password',
            'logo_path', 'entorno', 'activo',
        ];

        $sets = [];
        $params = [':id' => $id];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $sets[] = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $datos[$campo];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sets[] = "updated_at = NOW()";

        $sql = "UPDATE empresas_facturacion SET " .
               implode(', ', $sets) .
               " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM empresas_facturacion WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    public function existeRuc(string $ruc): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM empresas_facturacion WHERE ruc = :ruc"
        );
        $stmt->execute([':ruc' => $ruc]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function validarRuc(string $ruc): void
    {
        if (!preg_match('/^\d{11}$/', $ruc)) {
            throw new FacturacionException(
                "El RUC debe tener exactamente 11 dígitos. Recibido: {$ruc}"
            );
        }
    }
}
