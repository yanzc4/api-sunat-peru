<?php

declare(strict_types=1);

namespace App\Facturacion\Repositories;

use App\Facturacion\Models\Usuario;
use PDO;

class UsuarioRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new Usuario(
            (int)$row['id'],
            $row['nombre'],
            $row['email'],
            $row['password'],
            $row['rol'],
            $row['created_at'],
            $row['updated_at']
        );
    }

    public function findById(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    /** @return array<int, Usuario> */
    public function findClientes(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM usuarios WHERE rol = 'cliente' ORDER BY nombre ASC, id ASC");
        return array_map(fn(array $row) => $this->hydrate($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllWithEmpresas(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                u.id,
                u.nombre,
                u.email,
                u.rol,
                u.created_at,
                GROUP_CONCAT(
                    COALESCE(NULLIF(ef.nombre_comercial, ''), ef.razon_social)
                    ORDER BY COALESCE(NULLIF(ef.nombre_comercial, ''), ef.razon_social)
                    SEPARATOR ', '
                ) AS empresas
            FROM usuarios u
            LEFT JOIN empresas_facturacion ef ON ef.usuario_id = u.id
            GROUP BY u.id, u.nombre, u.email, u.rol, u.created_at
            ORDER BY u.id DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $nombre, string $email, string $passwordHash, string $rol = 'cliente'): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (nombre, email, password, rol, created_at, updated_at)
            VALUES (:nombre, :email, :password, :rol, NOW(), NOW())
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':password' => $passwordHash,
            ':rol' => $rol,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $nombre, string $email, string $rol, ?string $passwordHash = null): bool
    {
        $fields = [
            'nombre = :nombre',
            'email = :email',
            'rol = :rol',
            'updated_at = NOW()',
        ];
        $params = [
            ':id' => $id,
            ':nombre' => $nombre,
            ':email' => $email,
            ':rol' => $rol,
        ];

        if ($passwordHash !== null) {
            $fields[] = 'password = :password';
            $params[':password'] = $passwordHash;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );

        return $stmt->execute($params);
    }

    private function hydrate(array $row): Usuario
    {
        return new Usuario(
            (int) $row['id'],
            (string) $row['nombre'],
            (string) $row['email'],
            (string) $row['password'],
            (string) $row['rol'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
