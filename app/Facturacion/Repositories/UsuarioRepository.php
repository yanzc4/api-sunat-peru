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
}
