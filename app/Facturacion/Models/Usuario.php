<?php

declare(strict_types=1);

namespace App\Facturacion\Models;

class Usuario
{
    public ?int $id;
    public string $nombre;
    public string $email;
    public string $password;
    public string $rol;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        string $nombre = '',
        string $email = '',
        string $password = '',
        string $rol = 'cliente',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password = $password;
        $this->rol = $rol;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}
