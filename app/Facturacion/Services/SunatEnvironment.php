<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Exceptions\SunatException;
use Greenter\Ws\Services\SunatEndpoints;

final class SunatEnvironment
{
    public const BETA = 'beta';
    public const PRODUCCION = 'produccion';

    public static function normalize(string $environment): string
    {
        return strtolower(trim($environment));
    }

    public static function assertSupported(string $environment): void
    {
        if (!in_array(self::normalize($environment), [self::BETA, self::PRODUCCION], true)) {
            throw new FacturacionException(
                "Entorno SUNAT no válido: {$environment}. Use beta o produccion"
            );
        }
    }

    public static function sendEndpoint(string $environment): string
    {
        $environment = self::normalize($environment);
        self::assertSupported($environment);

        return $environment === self::PRODUCCION
            ? SunatEndpoints::FE_PRODUCCION
            : SunatEndpoints::FE_BETA;
    }

    public static function consultEndpoint(string $environment): string
    {
        $environment = self::normalize($environment);
        self::assertSupported($environment);

        if ($environment === self::BETA) {
            throw new SunatException(
                'SUNAT no ofrece consulta remota de estado o CDR en el entorno beta'
            );
        }

        return SunatEndpoints::FE_CONSULTA_CDR;
    }

    public static function isBeta(string $environment): bool
    {
        self::assertSupported($environment);
        return self::normalize($environment) === self::BETA;
    }
}
