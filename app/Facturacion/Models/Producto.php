<?php

declare(strict_types=1);

namespace App\Facturacion\Models;

final class Producto
{
    public int $id;
    public int $empresaId;
    public string $codigo;
    public string $descripcion;
    public string $unidad;
    public float $precioUnitario;
    public string $afectacionIgv;
    public bool $activo;
    public string $createdAt;
    public string $updatedAt;

    public static function fromArray(array $row): self
    {
        $producto = new self();
        $producto->id = (int) $row['id'];
        $producto->empresaId = (int) $row['empresa_id'];
        $producto->codigo = (string) $row['codigo'];
        $producto->descripcion = (string) $row['descripcion'];
        $producto->unidad = (string) $row['unidad'];
        $producto->precioUnitario = (float) $row['precio_unitario'];
        $producto->afectacionIgv = (string) $row['afectacion_igv'];
        $producto->activo = (bool) $row['activo'];
        $producto->createdAt = (string) $row['created_at'];
        $producto->updatedAt = (string) $row['updated_at'];
        return $producto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'empresa_id' => $this->empresaId,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'unidad' => $this->unidad,
            'precio_unitario' => $this->precioUnitario,
            'afectacion_igv' => $this->afectacionIgv,
            'activo' => $this->activo,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
