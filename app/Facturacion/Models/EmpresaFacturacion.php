<?php

declare(strict_types=1);

namespace App\Facturacion\Models;

class EmpresaFacturacion
{
    public int $id;
    public ?int $usuarioId;
    public string $ruc;
    public string $razonSocial;
    public ?string $nombreComercial;
    public ?string $direccion;
    public ?string $ubigeo;
    public ?string $departamento;
    public ?string $provincia;
    public ?string $distrito;
    public string $solUsuario;
    public string $solPassword;
    public string $certificadoPath;
    public string $certificadoPassword;
    public ?string $logoPath;
    public string $entorno;
    public bool $activo;
    public string $createdAt;
    public string $updatedAt;

    public static function fromArray(array $row): self
    {
        $model = new self();
        $model->id = (int) $row['id'];
        $model->usuarioId = isset($row['usuario_id']) ? (int) $row['usuario_id'] : null;
        $model->ruc = $row['ruc'];
        $model->razonSocial = $row['razon_social'];
        $model->nombreComercial = $row['nombre_comercial'] ?? null;
        $model->direccion = $row['direccion'] ?? null;
        $model->ubigeo = $row['ubigeo'] ?? null;
        $model->departamento = $row['departamento'] ?? null;
        $model->provincia = $row['provincia'] ?? null;
        $model->distrito = $row['distrito'] ?? null;
        $model->solUsuario = $row['sol_usuario'];
        $model->solPassword = $row['sol_password'];
        $model->certificadoPath = $row['certificado_path'];
        $model->certificadoPassword = $row['certificado_password'];
        $model->logoPath = $row['logo_path'] ?? null;
        $model->entorno = $row['entorno'];
        $model->activo = (bool) $row['activo'];
        $model->createdAt = $row['created_at'];
        $model->updatedAt = $row['updated_at'];
        return $model;
    }

    /**
     * Representación segura para respuestas JSON.
     * EXCLUYE credenciales y datos sensibles.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ruc' => $this->ruc,
            'razon_social' => $this->razonSocial,
            'nombre_comercial' => $this->nombreComercial,
            'direccion' => $this->direccion,
            'ubigeo' => $this->ubigeo,
            'departamento' => $this->departamento,
            'provincia' => $this->provincia,
            'distrito' => $this->distrito,
            'sol_usuario' => $this->solUsuario,
            'logo_path' => $this->logoPath,
            'entorno' => $this->entorno,
            'activo' => $this->activo,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Datos para debug/interno (NO usar en API).
     */
    public function toFullArray(): array
    {
        return [
            'id' => $this->id,
            'ruc' => $this->ruc,
            'razon_social' => $this->razonSocial,
            'nombre_comercial' => $this->nombreComercial,
            'direccion' => $this->direccion,
            'ubigeo' => $this->ubigeo,
            'departamento' => $this->departamento,
            'provincia' => $this->provincia,
            'distrito' => $this->distrito,
            'sol_usuario' => $this->solUsuario,
            'sol_password' => '[CIFRADO]',
            'certificado_path' => $this->certificadoPath,
            'certificado_password' => '[CIFRADO]',
            'logo_path' => $this->logoPath,
            'entorno' => $this->entorno,
            'activo' => $this->activo,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
