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
