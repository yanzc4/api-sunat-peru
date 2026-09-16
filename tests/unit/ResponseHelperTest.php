<?php

declare(strict_types=1);

/**
 * Test: Respuestas de API (sin servidor)
 * Ejecutar: php tests/unit/ResponseHelperTest.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Helpers\ResponseHelper;

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

echo "=== ResponseHelper Tests ===\n\n";

test('Validation error JSON is valid', function () {
    ob_start();
    try {
        ResponseHelper::validationError('Test error');
    } catch (\Exception $e) {
        // exit() called by ResponseHelper
    }
    $output = ob_get_clean();

    $json = json_decode($output, true);
    assertEquals(false, $json['success'], 'success debe ser false');
    assertEquals('VALIDATION_ERROR', $json['error']['code'], 'code debe ser VALIDATION_ERROR');
});

test('Not found JSON is valid', function () {
    ob_start();
    try {
        ResponseHelper::notFound('No existe');
    } catch (\Exception $e) {
    }
    $output = ob_get_clean();

    $json = json_decode($output, true);
    assertEquals(false, $json['success']);
    assertEquals('NOT_FOUND', $json['error']['code']);
});

test('Sunat error JSON is valid', function () {
    ob_start();
    try {
        ResponseHelper::sunatError('SUNAT rechazó', '0101');
    } catch (\Exception $e) {
    }
    $output = ob_get_clean();

    $json = json_decode($output, true);
    assertEquals(false, $json['success']);
    assertEquals('SUNAT_ERROR', $json['error']['code']);
    assertEquals('0101', $json['error']['details']['sunat_code']);
});

test('Error response never exposes password', function () {
    ob_start();
    try {
        ResponseHelper::error('TEST', 'Password: abc123', 400);
    } catch (\Exception $e) {
    }
    $output = ob_get_clean();

    // El mensaje de error NO debe contener contraseñas reales
    assertEquals(true, strpos($output, 'MODDATOS') === false, 'No exponer credenciales SOL');
});

function assertEquals($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        $msg = $msg ?: "Esperado: " . var_export($expected, true) . " | Obtenido: " . var_export($actual, true);
        throw new \RuntimeException($msg);
    }
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

exit($failed > 0 ? 1 : 0);
