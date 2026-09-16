<?php

declare(strict_types=1);

namespace App\Facturacion\Models;

class DetalleComprobante
{
    public int $id;
    public int $comprobanteId;
    public ?string $codigoProducto;
    public string $descripcion;
    public string $unidad;
    public float $cantidad;
    public float $precioUnitario;
    public ?float $valorUnitario;
    public float $subtotal;
    public float $igv;
    public float $total;
    public ?string $afectacionIgv;

    public static function fromArray(array $row): self
    {
        $model = new self();
        $model->id = (int) $row['id'];
        $model->comprobanteId = (int) $row['comprobante_id'];
        $model->codigoProducto = $row['codigo_producto'] ?? null;
        $model->descripcion = $row['descripcion'];
        $model->unidad = $row['unidad'];
        $model->cantidad = (float) $row['cantidad'];
        $model->precioUnitario = (float) $row['precio_unitario'];
        $model->valorUnitario = isset($row['valor_unitario']) ? (float) $row['valor_unitario'] : null;
        $model->subtotal = (float) $row['subtotal'];
        $model->igv = (float) $row['igv'];
        $model->total = (float) $row['total'];
        $model->afectacionIgv = $row['afectacion_igv'] ?? null;
        return $model;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigoProducto,
            'descripcion' => $this->descripcion,
            'unidad' => $this->unidad,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precioUnitario,
            'subtotal' => $this->subtotal,
            'igv' => $this->igv,
            'total' => $this->total,
            'afectacion_igv' => $this->afectacionIgv,
        ];
    }
}
