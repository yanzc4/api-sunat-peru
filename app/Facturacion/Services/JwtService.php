<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

/**
 * Servicio de emisión y validación de tokens JWT (HS256)
 * para la sesión web (login → dashboard).
 */
class JwtService
{
    private string $secret;
    private int $ttl;
    private string $issuer;

    public function __construct(?string $secret = null, ?int $ttl = null)
    {
        $this->issuer = 'api-sunat-peru';

        // Solo consultar la configuración global si falta algún parámetro
        // (permite instanciarlo en tests sin cargar el .env)
        if ($secret !== null && $ttl !== null) {
            $this->secret = $secret;
            $this->ttl = $ttl;
            return;
        }

        $config = FacturacionConfig::getInstance();
        $this->secret = $secret ?? $config->getJwtSecret();
        $this->ttl = $ttl ?? $config->getJwtTtl();
    }

    /**
     * Emite un JWT firmado para el usuario autenticado.
     *
     * @return array{token: string, expires_at: int}
     */
    public function generate(\App\Facturacion\Models\Usuario $usuario): array
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->ttl;

        $payload = [
            'iss' => $this->issuer,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expire,
            'sub' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
        ];

        return [
            'token' => JWT::encode($payload, $this->secret, 'HS256'),
            'expires_at' => $expire,
        ];
    }

    /**
     * Decodifica y valida un JWT.
     * Devuelve el payload como array asociativo o null si es inválido/expirado.
     *
     * @return array<string, mixed>|null
     */
    public function decode(string $jwt): ?array
    {
        try {
            $decoded = JWT::decode($jwt, new Key($this->secret, 'HS256'));
        } catch (ExpiredException | SignatureInvalidException | \UnexpectedValueException $e) {
            return null;
        }

        $payload = json_decode(json_encode($decoded), true);

        if (!is_array($payload) || !isset($payload['sub'])) {
            return null;
        }

        return $payload;
    }

    /**
     * Extrae el Bearer token del header Authorization.
     */
    public static function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return $m[1];
        }

        return null;
    }
}
