<?php

declare(strict_types=1);

namespace App\Facturacion\Exceptions;

class PdfException extends FacturacionException
{
    protected string $errorCode = 'PDF_ERROR';
}
