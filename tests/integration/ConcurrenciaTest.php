<?php

declare(strict_types=1);

/**
 * Test: Concurrencia de correlativos
 * Ejecutar: php tests/integration/ConcurrenciaTest.php
 *
 * NOTA: Requiere base de datos configurada y corriendo.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

use App\Facturacion\Repositories\ComprobanteRepository;
use App\Facturacion\Repositories\EmpresaFacturacionRepository;
use App\Facturacion\Config\FacturacionConfig;

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

echo "=== Concurrencia Test ===\n";
echo "NOTA: Este test requiere BD configurada.\n\n";

function getPdo(): PDO
{
    $dsn = $_ENV['FAC_DB_DSN'] ?? 'mysql:host=localhost;dbname=facturacion;charset=utf8mb4';
    $user = $_ENV['FAC_DB_USER'] ?? 'root';
    $pass = $_ENV['FAC_DB_PASS'] ?? '';

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

try {
    $pdo = getPdo();
    $repo = new ComprobanteRepository($pdo);

    test('Correlativo secuencial funciona', function () use ($repo, $pdo) {
        $pdo->beginTransaction();
        try {
            // Asumir que existe empresa_id=1, tipo=01, serie=F001
            $c1 = $repo->obtenerCorrelativo(1, '01', 'F001');
            $c2 = $repo->obtenerCorrelativo(1, '01', 'F001');

            assertEquals(true, $c2 > $c1, 'Segundo correlativo debe ser mayor');
            assertEquals(1, $c2 - $c1, 'Correlativos deben ser consecutivos');

            $pdo->rollBack();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    });

    test('Múltiples correlativos sin duplicados', function () use ($repo, $pdo) {
        $correlativos = [];
        $numTests = 10;

        $pdo->beginTransaction();
        try {
            for ($i = 0; $i < $numTests; $i++) {
                $c = $repo->obtenerCorrelativo(1, '01', 'F001');
                $correlativos[] = $c;
            }
            $pdo->rollBack();
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        $unique = array_unique($correlativos);
        assertEquals($numTests, count($unique), "Todos los correlativos deben ser únicos. Obtenidos: " . implode(', ', $correlativos));
    });

    test('Serie inexistente lanza error', function () use ($repo, $pdo) {
        try {
            $repo->obtenerCorrelativo(1, '01', 'XXXX');
            throw new \RuntimeException('Debería haber lanzado excepción');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Serie no configurada') !== false) {
                return; // OK
            }
            throw $e;
        }
    });

} catch (\Exception $e) {
    echo "\n[SKIP] No se pudo conectar a la BD; se omiten tests de concurrencia.\n";
    exit(2);
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

exit($failed > 0 ? 1 : 0);
