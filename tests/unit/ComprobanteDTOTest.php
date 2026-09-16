<?php

declare(strict_types=1);

/**
 * Test: ComprobanteDTO
 * Ejecutar: php tests/unit/ComprobanteDTOTest.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\DTO\ComprobanteDTO;
use App\Facturacion\Exceptions\FacturacionException;

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "[PASS] {$name}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "[FAIL] {$name}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

function assertThrows(callable $fn, string $msg = ''): void
{
    try {
        $fn();
        throw new \RuntimeException($msg ?: 'Se esperaba excepción');
    } catch (FacturacionException $e) {
        // OK
    }
}

function assertEquals($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        $msg = $msg ?: "Esperado: " . var_export($expected, true) . " | Obtenido: " . var_export($actual, true);
        throw new \RuntimeException($msg);
    }
}

echo "=== ComprobanteDTO Tests ===\n\n";

// Test data válida
$validData = [
    'empresa_id' => 1,
    'tipo_comprobante' => '01',
    'serie' => 'F001',
    'moneda' => 'PEN',
    'cliente' => [
        'tipo_documento' => '6',
        'numero_documento' => '20000000001',
        'nombre' => 'EMPRESA TEST SAC',
        'direccion' => 'Lima',
    ],
    'items' => [
        [
            'codigo' => 'PROD001',
            'descripcion' => 'Producto test',
            'unidad' => 'NIU',
            'cantidad' => 2,
            'precio_unitario' => 100.00,
            'afectacion_igv' => '10',
        ],
    ],
];

test('Valid invoice data passes validation', function () use ($validData) {
    $dto = ComprobanteDTO::fromArray($validData);
    $dto->validate();
    assertEquals(1, $dto->empresaId);
});

test('Invalid tipo_comprobante throws error', function () use ($validData) {
    $data = $validData;
    $data['tipo_comprobante'] = '99';
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'Tipo comprobante inválido debe fallar');
});

test('Invalid moneda throws error', function () use ($validData) {
    $data = $validData;
    $data['moneda'] = 'XYZ';
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'Moneda inválida debe fallar');
});

test('Empty cliente nombre throws error', function () use ($validData) {
    $data = $validData;
    $data['cliente']['nombre'] = '';
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'Nombre vacío debe fallar');
});

test('Invalid RUC length throws error', function () use ($validData) {
    $data = $validData;
    $data['cliente']['tipo_documento'] = '6';
    $data['cliente']['numero_documento'] = '123';
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'RUC corto debe fallar');
});

test('Invalid DNI length throws error', function () use ($validData) {
    $data = $validData;
    $data['cliente']['tipo_documento'] = '1';
    $data['cliente']['numero_documento'] = '123';
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'DNI corto debe fallar');
});

test('Empty items throws error', function () use ($validData) {
    $data = $validData;
    $data['items'] = [];
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'Items vacíos debe fallar');
});

test('Invalid unidad throws error', function () use ($validData) {
    $data = $validData;
    $data['items'][0]['unidad'] = 'ZZZ';
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'Unidad inválida debe fallar');
});

test('Invalid afectacion_igv throws error', function () use ($validData) {
    $data = $validData;
    $data['items'][0]['afectacion_igv'] = '99';
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'Afectación IGV inválida debe fallar');
});

test('Cantidad zero throws error', function () use ($validData) {
    $data = $validData;
    $data['items'][0]['cantidad'] = 0;
    $dto = ComprobanteDTO::fromArray($data);
    assertThrows(fn() => $dto->validate(), 'Cantidad 0 debe fallar');
});

test('Boleta (03) is valid', function () use ($validData) {
    $data = $validData;
    $data['tipo_comprobante'] = '03';
    $dto = ComprobanteDTO::fromArray($data);
    $dto->validate();
    assertEquals('03', $dto->tipoComprobante);
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

exit($failed > 0 ? 1 : 0);
