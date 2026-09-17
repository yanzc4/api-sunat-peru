<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Config\FacturacionConfig;

$config = FacturacionConfig::getInstance();
$relative = 'storage/private/facturacion/20000000001/2026/09/pdf/doc.pdf';
$resolved = str_replace('\\', '/', (string) $config->resolveProjectPath($relative));
$expected = $config->getProjectRoot() . '/' . $relative;

if ($resolved !== $expected) {
    echo "[FAIL] Ruta resuelta incorrectamente: {$resolved}\n";
    exit(1);
}

$legacy = $config->resolveProjectPath('api-sunat-peru/' . $relative);
if (str_replace('\\', '/', (string) $legacy) !== $expected) {
    echo "[FAIL] Ruta legacy resuelta incorrectamente\n";
    exit(1);
}

echo "[PASS] Las rutas relativas se resuelven desde la raíz del proyecto\n";
exit(0);
