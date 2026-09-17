<?php

declare(strict_types=1);

namespace App\Facturacion\Helpers;

final class EmpresaAccessPolicy
{
    public static function isAdmin(string $role): bool
    {
        return $role === 'admin';
    }

    public static function canManage(string $role, int $userId, ?int $ownerId): bool
    {
        return self::isAdmin($role) || ($ownerId !== null && $ownerId === $userId);
    }

    public static function canCreateOrDelete(string $role): bool
    {
        return self::isAdmin($role);
    }
}
