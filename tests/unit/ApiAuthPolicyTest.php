<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Helpers\ApiAuthPolicy;

$passed = 0;
$failed = 0;
function test(string $name, callable $fn): void { global $passed, $failed; try { $fn(); echo "[PASS] {$name}\n"; $passed++; } catch (Throwable $e) { echo "[FAIL] {$name}: {$e->getMessage()}\n"; $failed++; } }
function assertSameValue($expected, $actual): void { if ($expected !== $actual) throw new RuntimeException('Esperado ' . var_export($expected, true) . ', obtenido ' . var_export($actual, true)); }

echo "=== API Auth Policy Tests ===\n\n";

test('Todo el árbol de comprobantes exige api_token', function () {
    foreach ([
        '/api/facturacion/comprobantes',
        '/api/facturacion/comprobantes/10',
        '/api/facturacion/comprobantes/10/procesar',
        '/api/facturacion/comprobantes/10/pdf',
        '/api/facturacion/comprobantes/10/xml',
        '/api/facturacion/comprobantes/10/cdr',
        '/api/facturacion/comprobantes/10/estado',
    ] as $path) {
        assertSameValue(ApiAuthPolicy::API_TOKEN, ApiAuthPolicy::forPath($path));
    }
});

test('Empresas exige JWT y no token API', function () {
    assertSameValue(ApiAuthPolicy::JWT, ApiAuthPolicy::forPath('/api/facturacion/empresas'));
    assertSameValue(ApiAuthPolicy::JWT, ApiAuthPolicy::forPath('/api/facturacion/empresas/2/logo'));
});

test('GET solo extrae token del query', function () {
    assertSameValue('query-token', ApiAuthPolicy::extractApiToken('GET', ['token' => 'query-token'], '{"token":"body-token"}'));
});

test('POST solo extrae token del JSON', function () {
    assertSameValue('body-token', ApiAuthPolicy::extractApiToken('POST', ['token' => 'query-token'], '{"token":"body-token"}'));
});

test('La empresa del token sobrescribe empresa_id del cliente', function () {
    $data = ApiAuthPolicy::bindEmpresa(['empresa_id' => 999, 'serie' => 'B001'], 15);
    assertSameValue(15, $data['empresa_id']);
});

test('Rutas parecidas no heredan accidentalmente la política', function () {
    assertSameValue(null, ApiAuthPolicy::forPath('/api/facturacion/comprobantes-falsos'));
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
