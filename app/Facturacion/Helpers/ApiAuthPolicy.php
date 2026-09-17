<?php

declare(strict_types=1);

namespace App\Facturacion\Helpers;

final class ApiAuthPolicy
{
    public const API_TOKEN = 'api_token';
    public const JWT = 'jwt';

    public static function forPath(string $path): ?string
    {
        if (self::matchesPrefix($path, '/api/facturacion/comprobantes')) {
            return self::API_TOKEN;
        }

        if (self::matchesPrefix($path, '/api/facturacion/empresas')) {
            return self::JWT;
        }

        if (self::matchesPrefix($path, '/api/facturacion/usuarios')) {
            return self::JWT;
        }

        return null;
    }

    /** @param array<string, mixed> $query */
    public static function extractApiToken(string $method, array $query, string $rawBody): string
    {
        $method = strtoupper($method);
        if ($method === 'GET') {
            return trim((string) ($query['token'] ?? ''));
        }

        if ($method === 'POST') {
            $data = json_decode($rawBody, true);
            return is_array($data) ? trim((string) ($data['token'] ?? '')) : '';
        }

        return '';
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    public static function bindEmpresa(array $data, int $empresaId): array
    {
        $data['empresa_id'] = $empresaId;
        return $data;
    }

    private static function matchesPrefix(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
}
