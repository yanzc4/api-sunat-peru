<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Exceptions\SunatException;
use App\Facturacion\Services\SunatCredentials;
use App\Facturacion\Services\SunatEnvironment;
use Greenter\Ws\Services\SunatEndpoints;

$passed = 0;
$failed = 0;
function test(string $name, callable $fn): void { global $passed, $failed; try { $fn(); echo "[PASS] {$name}\n"; $passed++; } catch (Throwable $e) { echo "[FAIL] {$name}: {$e->getMessage()}\n"; $failed++; } }
function assertSameValue($expected, $actual): void { if ($expected !== $actual) throw new RuntimeException('Esperado ' . var_export($expected, true) . ', obtenido ' . var_export($actual, true)); }

echo "=== SUNAT Environment Tests ===\n\n";

test('Beta usa exclusivamente FE_BETA para envíos', function () {
    assertSameValue(SunatEndpoints::FE_BETA, SunatEnvironment::sendEndpoint('beta'));
});

test('Producción usa exclusivamente FE_PRODUCCION para envíos', function () {
    assertSameValue(SunatEndpoints::FE_PRODUCCION, SunatEnvironment::sendEndpoint('produccion'));
});

test('Consulta remota solo existe para producción', function () {
    assertSameValue(SunatEndpoints::FE_CONSULTA_CDR, SunatEnvironment::consultEndpoint('produccion'));
    try {
        SunatEnvironment::consultEndpoint('beta');
        throw new RuntimeException('Beta no debe usar billConsultService de producción');
    } catch (SunatException $e) {
        // esperado
    }
});

test('Entorno desconocido no cae silenciosamente en beta', function () {
    try {
        SunatEnvironment::sendEndpoint('staging');
        throw new RuntimeException('Debió rechazar staging');
    } catch (FacturacionException $e) {
        // esperado
    }
});

test('Usuario SOAP concatena RUC y usuario SOL', function () {
    assertSameValue(
        '20467534026MODDATOS',
        SunatCredentials::soapUsername('20467534026', 'MODDATOS')
    );
});

test('Usuario SOAP ya completo no duplica el RUC', function () {
    assertSameValue(
        '20467534026MODDATOS',
        SunatCredentials::soapUsername('20467534026', '20467534026MODDATOS')
    );
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
