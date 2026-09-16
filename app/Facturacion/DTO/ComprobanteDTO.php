<?php

declare(strict_types=1);

namespace App\Facturacion\DTO;

use App\Facturacion\Exceptions\FacturacionException;

class ComprobanteDTO
{
    private const TIPOS_COMPROBANTE_VALIDOS = ['01', '03', '07', '08'];
    private const MONEDAS_VALIDAS = ['PEN', 'USD'];
    private const TIPOS_DOCUMENTO_VALIDOS = ['1', '4', '6', '7', '11', 'A'];
    private const AFECCIONES_IGV_VALIDAS = ['10', '20', '30', '21'];
    private const UNIDADES_VALIDAS = [
        'NIU', 'ZZ', 'BX', 'CJ', 'CT', 'DZN',
        'GRM', 'KG', 'L', 'M', 'M2', 'M3',
        'ML', 'MT', 'PA', 'PR', 'RO', 'SET',
        'TN', 'UD', 'UN', 'YRD',
    ];

    public int $empresaId;
    public string $tipoComprobante;
    public string $serie;
    public string $moneda;

    public string $clienteTipoDocumento;
    public string $clienteNumeroDocumento;
    public string $clienteNombre;
    public ?string $clienteDireccion;

    public array $items;

    public ?string $fechaEmision;
    public ?string $horaEmision;

    public ?float $subtotalCalculado = null;
    public ?float $igvCalculado = null;
    public ?float $totalCalculado = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();

        $dto->empresaId = (int) ($data['empresa_id'] ?? 0);
        $dto->tipoComprobante = (string) ($data['tipo_comprobante'] ?? '');
        $dto->serie = (string) ($data['serie'] ?? '');
        $dto->moneda = (string) ($data['moneda'] ?? 'PEN');

        $cliente = $data['cliente'] ?? [];
        $dto->clienteTipoDocumento = (string) ($cliente['tipo_documento'] ?? '');
        $dto->clienteNumeroDocumento = (string) ($cliente['numero_documento'] ?? '');
        $dto->clienteNombre = (string) ($cliente['nombre'] ?? '');
        $dto->clienteDireccion = $cliente['direccion'] ?? null;

        $dto->items = array_map(
            fn(array $item) => ItemDTO::fromArray($item),
            $data['items'] ?? []
        );

        $dto->fechaEmision = $data['fecha_emision'] ?? null;
        $dto->horaEmision = $data['hora_emision'] ?? null;

        return $dto;
    }

    public function validate(): void
    {
        if ($this->empresaId <= 0) {
            throw new FacturacionException('empresa_id es obligatorio');
        }

        if (!in_array($this->tipoComprobante, self::TIPOS_COMPROBANTE_VALIDOS, true)) {
            throw new FacturacionException(
                "Tipo de comprobante no válido: {$this->tipoComprobante}. " .
                "Válidos: " . implode(', ', self::TIPOS_COMPROBANTE_VALIDOS)
            );
        }

        if (empty($this->serie) || strlen($this->serie) !== 4) {
            throw new FacturacionException(
                "La serie debe tener exactamente 4 caracteres. Recibido: '{$this->serie}'"
            );
        }

        if (!in_array($this->moneda, self::MONEDAS_VALIDAS, true)) {
            throw new FacturacionException(
                "Moneda no válida: {$this->moneda}. Válidas: " .
                implode(', ', self::MONEDAS_VALIDAS)
            );
        }

        $this->validateCliente();
        $this->validateItems();
        $this->validateTotales();
    }

    private function validateCliente(): void
    {
        if (empty($this->clienteNombre)) {
            throw new FacturacionException('El nombre del cliente es obligatorio');
        }

        if (empty($this->clienteTipoDocumento)) {
            throw new FacturacionException('El tipo de documento del cliente es obligatorio');
        }

        if (!in_array($this->clienteTipoDocumento, self::TIPOS_DOCUMENTO_VALIDOS, true)) {
            throw new FacturacionException(
                "Tipo de documento del cliente no válido: {$this->clienteTipoDocumento}"
            );
        }

        if (empty($this->clienteNumeroDocumento)) {
            throw new FacturacionException('El número de documento del cliente es obligatorio');
        }

        $this->validarNumeroDocumento(
            $this->clienteTipoDocumento,
            $this->clienteNumeroDocumento
        );
    }

    private function validarNumeroDocumento(string $tipo, string $numero): void
    {
        switch ($tipo) {
            case '6': // RUC
                if (!preg_match('/^\d{11}$/', $numero)) {
                    throw new FacturacionException(
                        "El RUC del cliente debe tener 11 dígitos. Recibido: {$numero}"
                    );
                }
                break;

            case '1': // DNI
                if (!preg_match('/^\d{8}$/', $numero)) {
                    throw new FacturacionException(
                        "El DNI del cliente debe tener 8 dígitos. Recibido: {$numero}"
                    );
                }
                break;

            case '4': // CE
                if (strlen($numero) < 3 || strlen($numero) > 12) {
                    throw new FacturacionException(
                        "El CE del cliente debe tener entre 3 y 12 caracteres. Recibido: {$numero}"
                    );
                }
                break;

            case '7': // Pasaporte
                if (strlen($numero) < 3 || strlen($numero) > 12) {
                    throw new FacturacionException(
                        "El pasaporte debe tener entre 3 y 12 caracteres. Recibido: {$numero}"
                    );
                }
                break;
        }
    }

    private function validateItems(): void
    {
        if (empty($this->items)) {
            throw new FacturacionException('El comprobante debe tener al menos un item');
        }

        foreach ($this->items as $index => $item) {
            $num = $index + 1;

            if (empty($item->descripcion)) {
                throw new FacturacionException("Item #{$num}: la descripción es obligatoria");
            }

            if ($item->cantidad <= 0) {
                throw new FacturacionException(
                    "Item #{$num}: la cantidad debe ser mayor a 0"
                );
            }

            if ($item->precioUnitario < 0) {
                throw new FacturacionException(
                    "Item #{$num}: el precio unitario no puede ser negativo"
                );
            }

            if (!in_array($item->unidad, self::UNIDADES_VALIDAS, true)) {
                throw new FacturacionException(
                    "Item #{$num}: unidad no válida: '{$item->unidad}'"
                );
            }

            if ($item->afectacionIgv !== null &&
                !in_array($item->afectacionIgv, self::AFECCIONES_IGV_VALIDAS, true)) {
                throw new FacturacionException(
                    "Item #{$num}: afectación IGV no válida: '{$item->afectacionIgv}'"
                );
            }
        }
    }

    private function validateTotales(): void
    {
        $subtotalCalculado = 0;
        $igvCalculado = 0;
        $totalCalculado = 0;

        foreach ($this->items as $item) {
            if ($item->afectacionIgv === '10' || $item->afectacionIgv === '21') {
                $totalItem = round($item->precioUnitario * $item->cantidad, 2);
                $subtotalItem = round($totalItem / 1.18, 2);
                $igvItem = round($totalItem - $subtotalItem, 2);
                $valorUnitario = round($item->precioUnitario / 1.18, 6);
            } else {
                $valorUnitario = $item->precioUnitario;
                $subtotalItem = round($valorUnitario * $item->cantidad, 2);
                $igvItem = 0;
                $totalItem = $subtotalItem;
            }

            $item->valorUnitario = $valorUnitario;
            $item->subtotal = $subtotalItem;
            $item->igv = $igvItem;
            $item->total = $totalItem;

            $subtotalCalculado += $subtotalItem;
            $igvCalculado += $igvItem;
            $totalCalculado += $totalItem;
        }

        $this->subtotalCalculado = round($subtotalCalculado, 2);
        $this->igvCalculado = round($igvCalculado, 2);
        $this->totalCalculado = round($totalCalculado, 2);
    }

    public function getTiposComprobante(): array
    {
        return self::TIPOS_COMPROBANTE_VALIDOS;
    }

    public function getFechaEmision(): string
    {
        return $this->fechaEmision ?? date('Y-m-d');
    }

    public function getHoraEmision(): string
    {
        return $this->horaEmision ?? date('H:i:s');
    }
}

class ItemDTO
{
    public ?string $codigo;
    public string $descripcion;
    public string $unidad;
    public float $cantidad;
    public float $precioUnitario;
    public ?string $afectacionIgv;

    public ?float $valorUnitario;
    public ?float $subtotal;
    public ?float $igv;
    public ?float $total;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->codigo = $data['codigo'] ?? null;
        $dto->descripcion = (string) ($data['descripcion'] ?? '');
        $dto->unidad = (string) ($data['unidad'] ?? 'NIU');
        $dto->cantidad = (float) ($data['cantidad'] ?? 0);
        $dto->precioUnitario = (float) ($data['precio_unitario'] ?? 0);
        $dto->afectacionIgv = $data['afectacion_igv'] ?? null;
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'unidad' => $this->unidad,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precioUnitario,
            'afectacion_igv' => $this->afectacionIgv,
            'valor_unitario' => $this->valorUnitario,
            'subtotal' => $this->subtotal,
            'igv' => $this->igv,
            'total' => $this->total,
        ];
    }
}
