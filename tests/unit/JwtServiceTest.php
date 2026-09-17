<?php

declare(strict_types=1);

/**
 * Test: Servicio JWT (emisión y validación de tokens de sesión)
 * Ejecutar: php tests/unit/JwtServiceTest.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Models\Usuario;
use App\Facturacion\Services\JwtService;

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

echo "=== JwtService Tests ===\n\n";

// Usar secreto de prueba (evita depender del .env del entorno)
$secret = str_repeat('a', 64);
$service = new JwtService($secret, 3600);

test('Token generado y decodificado conserva los claims', function () use ($service) {
    $usuario = new Usuario(7, 'Admin Test', 'admin@test.com', 'x', 'admin');

    $result = $service->generate($usuario);
    assertEquals(true, is_string($result['token']) && $result['token'] !== '', 'token no vacío');
    assertEquals(true, $result['expires_at'] > time(), 'expires_at en el futuro');

    $payload = $service->decode($result['token']);
    assertEquals(true, is_array($payload), 'decode devuelve array');
    assertEquals(7, $payload['sub']);
    assertEquals('Admin Test', $payload['nombre']);
    assertEquals('admin@test.com', $payload['email']);
    assertEquals('admin', $payload['rol']);
});

test('Token con firma alterada es inválido', function () use ($service) {
    $usuario = new Usuario(1, 'User', 'u@test.com', 'x', 'cliente');
    $token = $service->generate($usuario)['token'];

    // Alterar la firma (última parte)
    $parts = explode('.', $token);
    $parts[2] = rtrim(strtr(base64_encode('firma-falsa'), '+/', '-_'), '=');
    $tampered = implode('.', $parts);

    assertEquals(null, $service->decode($tampered), 'decode debe devolver null');
});

test('Token con payload alterado es inválido', function () use ($service) {
    $usuario = new Usuario(1, 'User', 'u@test.com', 'x', 'cliente');
    $token = $service->generate($usuario)['token'];

    // Firmar un payload distinto "elevando" el rol con otra clave
    $forged = \Firebase\JWT\JWT::encode(
        ['sub' => 1, 'rol' => 'admin', 'exp' => time() + 3600],
        str_repeat('b', 64),
        'HS256'
    );
    assertEquals(true, $forged !== $token);
    assertEquals(null, $service->decode($forged), 'token firmado con otra clave debe ser rechazado');

    // Y el token original sigue siendo el del rol cliente, no admin
    $payload = $service->decode($token);
    assertEquals('cliente', $payload['rol']);
});

test('Token expirado es inválido', function () use ($secret, $service) {
    $expired = new JwtService($secret, -10); // TTL negativo → ya expiró
    $usuario = new Usuario(2, 'User', 'u@test.com', 'x', 'cliente');

    $token = $expired->generate($usuario)['token'];
    assertEquals(null, $service->decode($token), 'token expirado debe devolver null');
});

test('Basura no es un token válido', function () use ($service) {
    assertEquals(null, $service->decode('no-es-un-jwt'));
    assertEquals(null, $service->decode(''));
});

test('extractBearerToken lee el header Authorization', function () {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc.def.ghi';
    assertEquals('abc.def.ghi', JwtService::extractBearerToken());

    unset($_SERVER['HTTP_AUTHORIZATION']);
    $_SERVER['HTTP_AUTHORIZATION'] = 'SinBearer abc';
    assertEquals(null, JwtService::extractBearerToken());
    unset($_SERVER['HTTP_AUTHORIZATION']);
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

exit($failed > 0 ? 1 : 0);
