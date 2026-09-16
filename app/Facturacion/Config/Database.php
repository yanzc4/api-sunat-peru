<?php

declare(strict_types=1);

namespace App\Facturacion\Config;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = $_ENV['FAC_DB_DSN'] ?? 'mysql:host=localhost;dbname=facturacion;charset=utf8mb4';
            $user = $_ENV['FAC_DB_USER'] ?? 'root';
            $pass = $_ENV['FAC_DB_PASS'] ?? '';

            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }
}
