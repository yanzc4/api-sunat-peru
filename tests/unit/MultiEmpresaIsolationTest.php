<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Repositories\ComprobanteRepository;

$passed = 0;
$failed = 0;
function test(string $name, callable $fn): void { global $passed, $failed; try { $fn(); echo "[PASS] {$name}\n"; $passed++; } catch (Throwable $e) { echo "[FAIL] {$name}: {$e->getMessage()}\n"; $failed++; } }
function assertSameValue($expected, $actual): void { if ($expected !== $actual) throw new RuntimeException('Esperado ' . var_export($expected, true) . ', obtenido ' . var_export($actual, true)); }

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE comprobantes (
    id INTEGER PRIMARY KEY, empresa_id INTEGER NOT NULL, tipo_comprobante TEXT NOT NULL,
    serie TEXT NOT NULL, correlativo INTEGER NOT NULL, fecha_emision TEXT NOT NULL,
    hora_emision TEXT, moneda TEXT NOT NULL, cliente_tipo_documento TEXT,
    cliente_numero_documento TEXT, cliente_nombre TEXT, cliente_direccion TEXT,
    subtotal REAL NOT NULL, igv REAL NOT NULL, total REAL NOT NULL,
    xml_path TEXT, pdf_path TEXT, cdr_path TEXT, hash_cpe TEXT, estado TEXT NOT NULL,
    codigo_respuesta TEXT, mensaje_respuesta TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
)');
$pdo->exec('CREATE TABLE comprobante_detalles (
    id INTEGER PRIMARY KEY, comprobante_id INTEGER NOT NULL, codigo_producto TEXT,
    descripcion TEXT NOT NULL, unidad TEXT NOT NULL, cantidad REAL NOT NULL,
    precio_unitario REAL NOT NULL, valor_unitario REAL, subtotal REAL NOT NULL,
    igv REAL NOT NULL, total REAL NOT NULL, afectacion_igv TEXT
)');
$insert = $pdo->prepare('INSERT INTO comprobantes (
    id, empresa_id, tipo_comprobante, serie, correlativo, fecha_emision, moneda,
    subtotal, igv, total, estado, created_at, updated_at
) VALUES (?, ?, "03", "B001", ?, "2026-09-16", "PEN", 100, 18, 118, "pendiente", "2026-09-16", "2026-09-16")');
$insert->execute([1, 101, 1]);
$insert->execute([2, 202, 1]);
$repo = new ComprobanteRepository($pdo);

echo "=== Multiempresa Isolation Tests ===\n\n";

test('Empresa solo lista sus comprobantes', function () use ($repo) {
    $empresa101 = $repo->findAll(['empresa_id' => 101]);
    $empresa202 = $repo->findAll(['empresa_id' => 202]);
    assertSameValue([1], array_map(fn($c) => $c->id, $empresa101));
    assertSameValue([2], array_map(fn($c) => $c->id, $empresa202));
});

test('Consulta directa exige id y empresa correctos', function () use ($repo) {
    assertSameValue(1, $repo->findByIdAndEmpresa(1, 101)?->id);
    assertSameValue(null, $repo->findByIdAndEmpresa(1, 202));
    assertSameValue(null, $repo->findByIdAndEmpresa(2, 101));
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
