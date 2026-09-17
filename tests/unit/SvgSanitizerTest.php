<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Services\SvgSanitizer;

$passed = 0;
$failed = 0;
function test(string $name, callable $fn): void { global $passed, $failed; try { $fn(); echo "[PASS] {$name}\n"; $passed++; } catch (Throwable $e) { echo "[FAIL] {$name}: {$e->getMessage()}\n"; $failed++; } }
function assertSafe(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }

$sanitizer = new SvgSanitizer();
echo "=== SVG Sanitizer Tests ===\n\n";

test('Conserva un SVG válido', function () use ($sanitizer) {
    $result = $sanitizer->sanitize('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M0 0 L10 10" fill="#000"/></svg>');
    assertSafe(str_contains($result, '<path'), 'Debe conservar path');
    assertSafe(str_contains($result, 'viewBox'), 'Debe conservar viewBox');
});

test('Elimina scripts, eventos y foreignObject', function () use ($sanitizer) {
    $result = $sanitizer->sanitize('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script><foreignObject><p>html</p></foreignObject><rect width="10" height="10" onclick="x()"/></svg>');
    assertSafe(!preg_match('/script|foreignObject|onload|onclick/i', $result), 'Persistió contenido ejecutable');
});

test('Elimina referencias externas y javascript', function () use ($sanitizer) {
    $result = $sanitizer->sanitize('<svg xmlns="http://www.w3.org/2000/svg"><image href="https://example.com/a.png"/><use href="javascript:alert(1)"/><rect fill="url(https://example.com/x)"/></svg>');
    assertSafe(!str_contains($result, 'https://'), 'Persistió URL externa');
    assertSafe(!str_contains(strtolower($result), 'javascript:'), 'Persistió JavaScript');
});

test('Rechaza DTD y entidades', function () use ($sanitizer) {
    try {
        $sanitizer->sanitize('<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg">&xxe;</svg>');
        throw new RuntimeException('Debió rechazar el documento');
    } catch (FacturacionException $e) {
        // esperado
    }
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
