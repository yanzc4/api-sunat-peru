<?php

declare(strict_types=1);

namespace App\Facturacion\Helpers;

use Flight;

/**
 * Contexto de autenticación del request actual.
 *
 * El middleware de rutas decodifica el JWT (cookie o Bearer) y guarda el
 * payload en Flight::set('auth_user', ...). Esta clase expone una lectura
 * cómoda y reemplaza a las antiguas variables $_SESSION.
 */
final class AuthContext
{
    /**
     * @return array<string, mixed>|null
     */
    private static function user(): ?array
    {
        $user = Flight::get('auth_user');
        return is_array($user) ? $user : null;
    }

    public static function userId(): ?int
    {
        $user = self::user();
        return isset($user['sub']) ? (int) $user['sub'] : null;
    }

    public static function nombre(): string
    {
        return (string) (self::user()['nombre'] ?? '');
    }

    public static function rol(): string
    {
        return (string) (self::user()['rol'] ?? '');
    }

    public static function isAdmin(): bool
    {
        return self::rol() === 'admin';
    }

    public static function isAuthenticated(): bool
    {
        return self::userId() !== null;
    }
}
