<?php

declare(strict_types=1);

require_once __DIR__ . '/../support/TestProcess.php';

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "[PASS] {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "[FAIL] {$name}: {$e->getMessage()}\n";
        $failed++;
    }
}

function assertEquals($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message ?: 'Valores diferentes');
    }
}

function responseJson(string $scenario): array
{
    $result = TestProcess::response($scenario);
    assertEquals(0, $result['exit_code'], "El subproceso terminó con {$result['exit_code']}");
    $json = json_decode($result['output'], true);
    if (!is_array($json)) {
        throw new RuntimeException('La respuesta no es JSON válido: ' . $result['output']);
    }
    return $json;
}

echo "=== ResponseHelper Tests ===\n\n";

test('Validation error JSON is valid', function () {
    $json = responseJson('validation');
    assertEquals(false, $json['success']);
    assertEquals('VALIDATION_ERROR', $json['error']['code']);
});

test('Not found JSON is valid', function () {
    $json = responseJson('not_found');
    assertEquals(false, $json['success']);
    assertEquals('NOT_FOUND', $json['error']['code']);
});

test('Sunat error includes its code', function () {
    $json = responseJson('sunat');
    assertEquals('SUNAT_ERROR', $json['error']['code']);
    assertEquals('0101', $json['error']['details']['sunat_code']);
});

test('Internal error hides technical details', function () {
    $result = TestProcess::response('internal');
    $json = json_decode($result['output'], true);
    assertEquals('INTERNAL_ERROR', $json['error']['code']);
    assertEquals('Error interno del servidor', $json['error']['message']);
    assertEquals(false, str_contains($result['output'], 'super-secret'));
    assertEquals(false, str_contains($result['output'], '/vendor/'));
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
