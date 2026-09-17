<?php

declare(strict_types=1);

/**
 * Verificación de Seguridad
 * Ejecutar: php tests/security/SecurityCheck.php
 *
 * Valida que las credenciales no se expongan en las respuestas.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../support/TestProcess.php';

use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\Models\EmpresaFacturacion;
use App\Facturacion\Models\Comprobante;

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

function assertNotContains(string $haystack, string $needle, string $msg = ''): void
{
    if (strpos($haystack, $needle) !== false) {
        throw new \RuntimeException(
            $msg ?: "La cadena contiene texto prohibido: '{$needle}'"
        );
    }
}

echo "=== Security Verification Tests ===\n\n";

// Test 1: Empresa toArray no expone passwords
test('Empresa toArray no incluye sol_password', function () {
    $empresa = EmpresaFacturacion::fromArray([
        'id' => 1,
        'empresa_id' => 1,
        'ruc' => '20000000001',
        'razon_social' => 'TEST',
        'sol_usuario' => 'MODDATOS',
        'sol_password' => 'test-password-cifrado',
        'certificado_path' => '/path/to/cert.pfx',
        'certificado_password' => 'test-cert-password',
        'entorno' => 'beta',
        'activo' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $array = $empresa->toArray();
    $json = json_encode($array);

    assertNotContains($json, 'test-password-cifrado', 'toArray expone sol_password');
    assertNotContains($json, 'test-cert-password', 'toArray expone certificado_password');
});

test('Empresa toArray no incluye certificado_password', function () {
    $empresa = EmpresaFacturacion::fromArray([
        'id' => 1,
        'empresa_id' => 1,
        'ruc' => '20000000001',
        'razon_social' => 'TEST',
        'sol_usuario' => 'MODDATOS',
        'sol_password' => 'secret-sol',
        'certificado_path' => '/path/to/cert.pfx',
        'certificado_password' => 'secret-cert',
        'entorno' => 'beta',
        'activo' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $array = $empresa->toArray();

    assertEquals(false, isset($array['sol_password']), 'toArray no debe tener sol_password');
    assertEquals(true, isset($array['sol_usuario']), 'toArray debe tener sol_usuario');
});

test('Empresa toFullArray muestra [CIFRADO]', function () {
    $empresa = EmpresaFacturacion::fromArray([
        'id' => 1,
        'empresa_id' => 1,
        'ruc' => '20000000001',
        'razon_social' => 'TEST',
        'sol_usuario' => 'MODDATOS',
        'sol_password' => 'real-password',
        'certificado_path' => '/path/to/cert.pfx',
        'certificado_password' => 'real-cert',
        'entorno' => 'beta',
        'activo' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $array = $empresa->toFullArray();

    assertEquals('[CIFRADO]', $array['sol_password'], 'toFullArray debe mostrar [CIFRADO]');
    assertEquals('[CIFRADO]', $array['certificado_password'], 'toFullArray debe mostrar [CIFRADO]');
});

function assertEquals($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        $msg = $msg ?: "Esperado: " . var_export($expected, true) . " | Obtenido: " . var_export($actual, true);
        throw new \RuntimeException($msg);
    }
}

// Test 2: Config no expone paths sensibles
test('Storage path está fuera de /public/', function () {
    $config = FacturacionConfig::getInstance();
    $path = $config->getStoragePath();

    assertNotContains($path, '/public/', 'Storage no debe estar en /public/');
    assertNotContains($path, '/public_html/', 'Storage no debe estar en /public_html/');
});

// Test 3: Archivos .pfx no accesibles vía URL
test('Certificados están en directorio privado', function () {
    $config = FacturacionConfig::getInstance();
    $path = $config->getCertificadosPath();

    assertEquals(true, strpos($path, '/private/') !== false, 'Certificados en /private/');
    assertEquals(true, strpos($path, 'storage') !== false, 'Certificados en storage');
});

// Test 4: ResponseHelper no expone stack traces
test('ResponseHelper error no incluye stack trace', function () {
    $result = TestProcess::response('custom');
    $output = $result['output'];

    assertNotContains($output, 'Exception', 'No exponer Excepciones');
    assertNotContains($output, 'Stack trace', 'No exponer stack trace');
    assertNotContains($output, '/vendor/', 'No exponer paths de vendor');
});

test('Error interno oculta detalles técnicos', function () {
    $result = TestProcess::response('internal');
    $output = $result['output'];

    assertNotContains($output, 'super-secret', 'No exponer secretos');
    assertNotContains($output, 'PDO', 'No exponer detalles de base de datos');
    assertNotContains($output, '/vendor/', 'No exponer rutas internas');
    assertEquals(true, str_contains($output, 'Error interno del servidor'));
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

exit($failed > 0 ? 1 : 0);
