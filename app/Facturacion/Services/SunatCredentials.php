<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Exceptions\FacturacionException;

final class SunatCredentials
{
    public static function soapUsername(string $ruc, string $solUser): string
    {
        $ruc = trim($ruc);
        $solUser = trim($solUser);

        if (!preg_match('/^\d{11}$/', $ruc) || $solUser === '') {
            throw new FacturacionException('Las credenciales SOL de la empresa no son válidas');
        }

        // Compatibilidad con registros que ya guardaron RUC + usuario completo.
        return str_starts_with($solUser, $ruc) ? $solUser : $ruc . $solUser;
    }
}
