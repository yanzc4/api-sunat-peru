<?php

declare(strict_types=1);

namespace App\Facturacion\Models;

class Comprobante
{
    public int $id;
    public int $empresaId;
    public string $tipoComprobante;
    public string $serie;
    public int $correlativo;
    public string $fechaEmision;
    public ?string $horaEmision;
    public string $moneda;
    public ?string $clienteTipoDocumento;
    public ?string $clienteNumeroDocumento;
    public ?string $clienteNombre;
    public ?string $clienteDireccion;
    public float $subtotal;
    public float $igv;
    public float $total;
    public ?string $xmlPath;
    public ?string $pdfPath;
    public ?string $cdrPath;
    public ?string $hashCpe;
    public string $estado;
    public ?string $codigoRespuesta;
    public ?string $mensajeRespuesta;
    public string $createdAt;
    public string $updatedAt;

    /** @var DetalleComprobante[] */
    public array $detalles = [];

    public static function fromArray(array $row): self
    {
        $model = new self();
        $model->id = (int) $row['id'];
        $model->empresaId = (int) $row['empresa_id'];
        $model->tipoComprobante = $row['tipo_comprobante'];
        $model->serie = $row['serie'];
        $model->correlativo = (int) $row['correlativo'];
        $model->fechaEmision = $row['fecha_emision'];
        $model->horaEmision = $row['hora_emision'] ?? null;
        $model->moneda = $row['moneda'];
        $model->clienteTipoDocumento = $row['cliente_tipo_documento'] ?? null;
        $model->clienteNumeroDocumento = $row['cliente_numero_documento'] ?? null;
        $model->clienteNombre = $row['cliente_nombre'] ?? null;
        $model->clienteDireccion = $row['cliente_direccion'] ?? null;
        $model->subtotal = (float) $row['subtotal'];
        $model->igv = (float) $row['igv'];
        $model->total = (float) $row['total'];
        $model->xmlPath = $row['xml_path'] ?? null;
        $model->pdfPath = $row['pdf_path'] ?? null;
        $model->cdrPath = $row['cdr_path'] ?? null;
        $model->hashCpe = $row['hash_cpe'] ?? null;
        $model->estado = $row['estado'];
        $model->codigoRespuesta = $row['codigo_respuesta'] ?? null;
        $model->mensajeRespuesta = $row['mensaje_respuesta'] ?? null;
        $model->createdAt = $row['created_at'];
        $model->updatedAt = $row['updated_at'];
        return $model;
    }

    public function getNumeroFormato(): string
    {
        return $this->serie . '-' . str_pad((string) $this->correlativo, 8, '0', STR_PAD_LEFT);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'empresa_id' => $this->empresaId,
            'tipo' => $this->tipoComprobante,
            'serie' => $this->serie,
            'correlativo' => str_pad((string) $this->correlativo, 8, '0', STR_PAD_LEFT),
            'numero' => $this->getNumeroFormato(),
            'fecha_emision' => $this->fechaEmision,
            'moneda' => $this->moneda,
            'cliente' => [
                'tipo_documento' => $this->clienteTipoDocumento,
                'numero_documento' => $this->clienteNumeroDocumento,
                'nombre' => $this->clienteNombre,
                'direccion' => $this->clienteDireccion,
            ],
            'subtotal' => $this->subtotal,
            'igv' => $this->igv,
            'total' => $this->total,
            'estado' => $this->estado,
            'hash' => $this->hashCpe,
            'codigo_respuesta' => $this->codigoRespuesta,
            'mensaje_respuesta' => $this->mensajeRespuesta,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
