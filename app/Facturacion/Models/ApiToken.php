<?php

declare(strict_types=1);

namespace App\Facturacion\Models;

class ApiToken
{
    public ?int $id;
    public int $empresaId;
    public string $token;
    public bool $activo;
    public ?string $createdAt;

    public function __construct(
        ?int $id = null,
        int $empresaId = 0,
        string $token = '',
        bool $activo = true,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->empresaId = $empresaId;
        $this->token = $token;
        $this->activo = $activo;
        $this->createdAt = $createdAt;
    }
}
