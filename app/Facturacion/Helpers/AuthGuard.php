<?php

declare(strict_types=1);

namespace App\Facturacion\Helpers;

use App\Facturacion\Services\JwtService;
use Flight;

/**
 * Guardián de autenticación web basado en JWT.
 *
 * El token viaja en la cookie firmada `jwt_token` (HttpOnly, SameSite=Lax)
 * o en el header `Authorization: Bearer ...` para clientes API.
 * Reemplaza a las antiguas sesiones de PHP/Apache ($_SESSION).
 */
final class AuthGuard
{
    public const COOKIE_NAME = 'jwt_token';

    /**
     * Resuelve el usuario del request actual decodificando el JWT
     * (cookie o Bearer). Devuelve el payload o null si no hay sesión válida.
     *
     * @return array<string, mixed>|null
     */
    public static function resolveUser(): ?array
    {
        $existing = Flight::get('auth_user');
        if (is_array($existing)) {
            return $existing;
        }

        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($token) || $token === '') {
            $token = JwtService::extractBearerToken();
        }

        if (!is_string($token) || $token === '') {
            return null;
        }

        $payload = (new JwtService())->decode($token);
        if ($payload === null) {
            return null;
        }

        Flight::set('auth_user', $payload);
        return $payload;
    }

    /**
     * Exige sesión web válida; redirige a /login si no la hay.
     *
     * @return array<string, mixed>
     */
    public static function requireWebUser(): array
    {
        $user = self::resolveUser();

        if ($user === null) {
            header('Location: /login');
            exit;
        }

        return $user;
    }

    /**
     * Exige sesión web válida con rol admin.
     */
    public static function requireAdmin(): void
    {
        $user = self::requireWebUser();

        if (($user['rol'] ?? '') !== 'admin') {
            ResponseHelper::forbidden('Solo el administrador puede realizar esta acción.');
        }
    }

    /**
     * Emite la cookie firmada de sesión (HttpOnly).
     */
    public static function setAuthCookie(string $token, int $expiresAt): void
    {
        setcookie(self::COOKIE_NAME, $token, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Elimina la cookie de sesión (logout).
     */
    public static function clearAuthCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::COOKIE_NAME]);
        Flight::set('auth_user', null);
    }

    private static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
