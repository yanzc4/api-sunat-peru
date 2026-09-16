<?php

declare(strict_types=1);

namespace App\Facturacion\Repositories;

use App\Facturacion\Models\ApiToken;
use PDO;

class ApiTokenRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createToken(int $empresaId): ApiToken
    {
        // 32 chars random string for token
        $tokenStr = bin2hex(random_bytes(16)); 

        $stmt = $this->pdo->prepare("
            INSERT INTO api_tokens (empresa_id, token, activo) 
            VALUES (:empresa_id, :token, 1)
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':token' => $tokenStr
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return new ApiToken($id, $empresaId, $tokenStr, true);
    }

    public function findByToken(string $tokenStr): ?ApiToken
    {
        $stmt = $this->pdo->prepare("SELECT * FROM api_tokens WHERE token = :token AND activo = 1 LIMIT 1");
        $stmt->execute([':token' => $tokenStr]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new ApiToken(
            (int)$row['id'],
            (int)$row['empresa_id'],
            $row['token'],
            (bool)$row['activo'],
            $row['created_at']
        );
    }

    public function findByEmpresa(int $empresaId): ?ApiToken
    {
        $stmt = $this->pdo->prepare("SELECT * FROM api_tokens WHERE empresa_id = :empresa_id AND activo = 1 LIMIT 1");
        $stmt->execute([':empresa_id' => $empresaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new ApiToken(
            (int)$row['id'],
            (int)$row['empresa_id'],
            $row['token'],
            (bool)$row['activo'],
            $row['created_at']
        );
    }
}
