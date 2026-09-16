<?php

declare(strict_types=1);

namespace App\Facturacion\Exceptions;

class SunatException extends FacturacionException
{
    protected string $errorCode = 'SUNAT_ERROR';

    private ?string $sunatCode;
    private ?string $sunatMessage;

    public function __construct(
        string $message,
        ?string $sunatCode = null,
        ?string $sunatMessage = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->sunatCode = $sunatCode;
        $this->sunatMessage = $sunatMessage;
    }

    public function getSunatCode(): ?string
    {
        return $this->sunatCode;
    }

    public function getSunatMessage(): ?string
    {
        return $this->sunatMessage;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['sunat_code'] = $this->sunatCode;
        $data['sunat_message'] = $this->sunatMessage;
        return $data;
    }
}
