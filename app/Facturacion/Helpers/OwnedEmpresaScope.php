<?php

declare(strict_types=1);

namespace App\Facturacion\Helpers;

use App\Facturacion\Models\EmpresaFacturacion;
use App\Facturacion\Repositories\EmpresaFacturacionRepository;
use PDO;

final class OwnedEmpresaScope
{
    public static function require(PDO $pdo, int $empresaId, bool $requireActive = true): EmpresaFacturacion
    {
        $usuarioId = AuthContext::userId();
        if ($usuarioId === null) {
            ResponseHelper::unauthorized('Sesión de dashboard requerida');
        }

        $empresa = (new EmpresaFacturacionRepository($pdo))->findById($empresaId);
        if (!$empresa || $empresa->usuarioId !== $usuarioId) {
            ResponseHelper::notFound('Empresa no encontrada');
        }
        if ($requireActive && !$empresa->activo) {
            ResponseHelper::error(
                'ACCOUNT_SUSPENDED',
                'La cuenta de la empresa está suspendida. Contacta al administrador.',
                403
            );
        }
        return $empresa;
    }
}
