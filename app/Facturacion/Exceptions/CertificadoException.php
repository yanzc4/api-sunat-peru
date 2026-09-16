<?php

declare(strict_types=1);

namespace App\Facturacion\Exceptions;

class CertificadoException extends FacturacionException
{
    protected string $errorCode = 'CERTIFICADO_ERROR';
}
