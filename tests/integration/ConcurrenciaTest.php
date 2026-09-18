<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

use App\Facturacion\Repositories\ComprobanteRepository;

function getPdo(): PDO
{
    return new PDO(
        $_ENV['FAC_DB_DSN'] ?? 'mysql:host=localhost;dbname=facturacion;charset=utf8mb4',
        $_ENV['FAC_DB_USER'] ?? 'root',
        $_ENV['FAC_DB_PASS'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
}

if (($argv[1] ?? '') === 'correlativo-worker') {
    $empresaId = (int) ($argv[2] ?? 0);
    $serie = (string) ($argv[3] ?? '');
    $startAt = (float) ($argv[4] ?? microtime(true));
    while (microtime(true) < $startAt) usleep(1000);
    $pdo = getPdo();
    try {
        $pdo->beginTransaction();
        $value = (new ComprobanteRepository($pdo))->obtenerCorrelativo($empresaId, '01', $serie);
        usleep(30000);
        $pdo->commit();
        echo (string) $value;
        exit(0);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        fwrite(STDERR, $exception->getMessage());
        exit(1);
    }
}

if (($argv[1] ?? '') === 'claim-worker') {
    $id = (int) ($argv[2] ?? 0);
    $empresaId = (int) ($argv[3] ?? 0);
    $startAt = (float) ($argv[4] ?? microtime(true));
    while (microtime(true) < $startAt) usleep(1000);
    echo (new ComprobanteRepository(getPdo()))->claimForProcessing($id, $empresaId) ? '1' : '0';
    exit(0);
}

$passed = 0;
$failed = 0;

function test(string $name, callable $callback): void
{
    global $passed, $failed;
    try { $callback(); echo "[PASS] {$name}\n"; $passed++; }
    catch (Throwable $exception) { echo "[FAIL] {$name}: {$exception->getMessage()}\n"; $failed++; }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Esperado: ' . var_export($expected, true) . '; obtenido: ' . var_export($actual, true));
    }
}

/** @return array<int, string> */
function runWorkers(string $mode, array $arguments, int $count): array
{
    $startAt = microtime(true) + 0.6;
    $processes = [];
    for ($index = 0; $index < $count; $index++) {
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, __FILE__, $mode, ...array_map('strval', $arguments), (string) $startAt],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname(__DIR__, 2),
            null,
            ['bypass_shell' => true]
        );
        if (!is_resource($process)) throw new RuntimeException('No se pudo iniciar un proceso concurrente');
        $processes[] = [$process, $pipes];
    }

    $outputs = [];
    foreach ($processes as [$process, $pipes]) {
        $stdout = trim((string) stream_get_contents($pipes[1]));
        $stderr = trim((string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0) throw new RuntimeException($stderr !== '' ? $stderr : 'Worker terminó con código ' . $exitCode);
        $outputs[] = $stdout;
    }
    return $outputs;
}

echo "=== Concurrencia real ===\n\n";

try {
    $pdo = getPdo();
    $empresaId = (int) $pdo->query('SELECT id FROM empresas_facturacion ORDER BY id ASC LIMIT 1')->fetchColumn();
    if ($empresaId < 1) { echo "[SKIP] No existe una empresa para crear datos temporales de prueba.\n"; exit(2); }
} catch (Throwable $exception) {
    echo "[SKIP] No se pudo conectar a la base de datos.\n";
    exit(2);
}

$serie = 'Z' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 3));
$comprobanteId = null;

try {
    $insertSerie = $pdo->prepare("INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo) VALUES (:empresa, '01', :serie, 0)");
    $insertSerie->execute([':empresa' => $empresaId, ':serie' => $serie]);

    test('Doce procesos reservan correlativos únicos y consecutivos', function () use ($empresaId, $serie): void {
        $values = array_map('intval', runWorkers('correlativo-worker', [$empresaId, $serie], 12));
        sort($values);
        assertSameValue(range(1, 12), $values, 'Los correlativos concurrentes no fueron consecutivos.');
    });

    test('Serie inexistente lanza error', function () use ($empresaId): void {
        try {
            (new ComprobanteRepository(getPdo()))->obtenerCorrelativo($empresaId, '01', 'XXXX');
            throw new RuntimeException('La serie inexistente fue aceptada');
        } catch (Throwable $exception) {
            if (!str_contains($exception->getMessage(), 'Serie no configurada')) throw $exception;
        }
    });

    $insertComprobante = $pdo->prepare("INSERT INTO comprobantes (empresa_id,tipo_comprobante,serie,correlativo,fecha_emision,hora_emision,moneda,cliente_tipo_documento,cliente_numero_documento,cliente_nombre,subtotal,igv,total,estado,created_at,updated_at) VALUES (:empresa,'01',:serie,100,CURDATE(),CURTIME(),'PEN','6','20000000001','PRUEBA CONCURRENCIA',100,18,118,'pendiente',NOW(),NOW())");
    $insertComprobante->execute([':empresa' => $empresaId, ':serie' => $serie]);
    $comprobanteId = (int) $pdo->lastInsertId();

    test('Solo un proceso puede reclamar un comprobante', function () use ($comprobanteId, $empresaId): void {
        $outputs = runWorkers('claim-worker', [$comprobanteId, $empresaId], 8);
        $claimed = count(array_filter($outputs, static fn (string $value): bool => $value === '1'));
        assertSameValue(1, $claimed, 'Más de un proceso reclamó el mismo comprobante.');
    });
} finally {
    if ($comprobanteId !== null) {
        $deleteComprobante = $pdo->prepare('DELETE FROM comprobantes WHERE id = :id');
        $deleteComprobante->execute([':id' => $comprobanteId]);
    }
    $deleteSerie = $pdo->prepare("DELETE FROM comprobante_series WHERE empresa_id = :empresa AND tipo_comprobante = '01' AND serie = :serie");
    $deleteSerie->execute([':empresa' => $empresaId, ':serie' => $serie]);
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
