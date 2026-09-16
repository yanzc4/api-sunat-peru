<?php

declare(strict_types=1);

namespace App\Facturacion\Exceptions;

class FacturacionException extends \Exception
{
    protected string $errorCode = 'FACTURACION_ERROR';

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
        ];
    }
}
