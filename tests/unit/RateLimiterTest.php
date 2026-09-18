<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Helpers\RateLimiter;

$passed = 0;
$failed = 0;
$directory = sys_get_temp_dir() . '/facturacion-rate-test-' . bin2hex(random_bytes(5));
$limiter = new RateLimiter($directory);

function rateTest(string $name, callable $callback): void
{
    global $passed, $failed;
    try {
        $callback();
        echo "[PASS] {$name}\n";
        $passed++;
    } catch (Throwable $exception) {
        echo "[FAIL] {$name}: {$exception->getMessage()}\n";
        $failed++;
    }
}

function rateAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

echo "=== Rate Limiter Tests ===\n\n";

try {
    rateTest('Permite intentos dentro del límite', function () use ($limiter): void {
        rateAssert($limiter->consume('login', 'ip-a', 2, 60)['allowed'], 'Primer intento rechazado');
        rateAssert($limiter->consume('login', 'ip-a', 2, 60)['allowed'], 'Segundo intento rechazado');
    });

    rateTest('Rechaza al superar el límite', function () use ($limiter): void {
        $result = $limiter->consume('login', 'ip-a', 2, 60);
        rateAssert(!$result['allowed'], 'El tercer intento debió bloquearse');
        rateAssert($result['retry_after'] > 0, 'Debe indicar tiempo de reintento');
    });

    rateTest('Separa ámbitos e identificadores', function () use ($limiter): void {
        rateAssert($limiter->consume('contact', 'ip-a', 1, 60)['allowed'], 'Otro ámbito quedó bloqueado');
        rateAssert($limiter->consume('login', 'ip-b', 1, 60)['allowed'], 'Otro identificador quedó bloqueado');
    });

    rateTest('Clear restablece el contador', function () use ($limiter): void {
        $limiter->clear('login', 'ip-a');
        rateAssert($limiter->consume('login', 'ip-a', 1, 60)['allowed'], 'El contador no se restableció');
    });
} finally {
    foreach (glob($directory . '/*.json') ?: [] as $file) {
        unlink($file);
    }
    if (is_dir($directory)) {
        rmdir($directory);
    }
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
