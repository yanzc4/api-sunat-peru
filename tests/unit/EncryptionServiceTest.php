<?php

declare(strict_types=1);

/**
 * Test: EncryptionService
 * Ejecutar: php tests/unit/EncryptionServiceTest.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Services\EncryptionService;

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

function assertEquals($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        $msg = $msg ?: "Esperado: " . var_export($expected, true) . " | Obtenido: " . var_export($actual, true);
        throw new \RuntimeException($msg);
    }
}

// Tests
echo "=== EncryptionService Tests ===\n\n";

$encryption = new EncryptionService();

test('Encrypt returns non-empty string', function () use ($encryption) {
    $encrypted = $encryption->encrypt('test-password');
    assertEquals(true, !empty($encrypted), 'Cifrado no debe estar vacío');
});

test('Decrypt returns original value', function () use ($encryption) {
    $original = 'mi-password-secreto-123';
    $encrypted = $encryption->encrypt($original);
    $decrypted = $encryption->decrypt($encrypted);
    assertEquals($original, $decrypted, 'Descifrado debe retornar valor original');
});

test('Different encryptions decrypt to same value', function () use ($encryption) {
    $original = 'password-repetible';
    $enc1 = $encryption->encrypt($original);
    $enc2 = $encryption->encrypt($original);
    assertEquals($encryption->decrypt($enc1), $encryption->decrypt($enc2));
});

test('Empty string can be encrypted', function () use ($encryption) {
    $encrypted = $encryption->encrypt('');
    $decrypted = $encryption->decrypt($encrypted);
    assertEquals('', $decrypted);
});

test('Special characters handled correctly', function () use ($encryption) {
    $original = 'p@$$w0rd!#%^&*()_+{}|:<>?';
    $encrypted = $encryption->encrypt($original);
    $decrypted = $encryption->decrypt($encrypted);
    assertEquals($original, $decrypted);
});

test('Long string handled correctly', function () use ($encryption) {
    $original = str_repeat('a', 1000);
    $encrypted = $encryption->encrypt($original);
    $decrypted = $encryption->decrypt($encrypted);
    assertEquals($original, $decrypted);
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

exit($failed > 0 ? 1 : 0);
