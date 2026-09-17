<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Helpers\EmpresaAccessPolicy;

$passed = 0;
$failed = 0;
function test(string $name, callable $fn): void { global $passed, $failed; try { $fn(); echo "[PASS] {$name}\n"; $passed++; } catch (Throwable $e) { echo "[FAIL] {$name}: {$e->getMessage()}\n"; $failed++; } }
function assertPolicy(bool $expected, bool $actual): void { if ($expected !== $actual) throw new RuntimeException('Permiso inesperado'); }

echo "=== Empresa Access Policy Tests ===\n\n";

test('Admin administra cualquier empresa', function () {
    assertPolicy(true, EmpresaAccessPolicy::canManage('admin', 1, 999));
    assertPolicy(true, EmpresaAccessPolicy::canCreateOrDelete('admin'));
});

test('Cliente administra solo su empresa', function () {
    assertPolicy(true, EmpresaAccessPolicy::canManage('cliente', 8, 8));
    assertPolicy(false, EmpresaAccessPolicy::canManage('cliente', 8, 9));
    assertPolicy(false, EmpresaAccessPolicy::canManage('cliente', 8, null));
});

test('Cliente no crea ni elimina empresas', function () {
    assertPolicy(false, EmpresaAccessPolicy::canCreateOrDelete('cliente'));
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
