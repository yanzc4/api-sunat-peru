<?php

declare(strict_types=1);

/**
 * Test: Estructura del proyecto
 * Ejecutar: php tests/unit/StructureTest.php
 */

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

function assertFileExists(string $path): void
{
    if (!file_exists($path)) {
        throw new \RuntimeException("Archivo no existe: {$path}");
    }
}

function assertDirExists(string $path): void
{
    if (!is_dir($path)) {
        throw new \RuntimeException("Directorio no existe: {$path}");
    }
}

$root = dirname(__DIR__, 2);

echo "=== Structure Tests ===\n\n";

// Directorios
test('Directorio app/Facturacion existe', fn() => assertDirExists("{$root}/app/Facturacion"));
test('Directorio Config existe', fn() => assertDirExists("{$root}/app/Facturacion/Config"));
test('Directorio Services existe', fn() => assertDirExists("{$root}/app/Facturacion/Services"));
test('Directorio Repositories existe', fn() => assertDirExists("{$root}/app/Facturacion/Repositories"));
test('Directorio Models existe', fn() => assertDirExists("{$root}/app/Facturacion/Models"));
test('Directorio DTO existe', fn() => assertDirExists("{$root}/app/Facturacion/DTO"));
test('Directorio Exceptions existe', fn() => assertDirExists("{$root}/app/Facturacion/Exceptions"));
test('Directorio Helpers existe', fn() => assertDirExists("{$root}/app/Facturacion/Helpers"));
test('Directorio Controllers existe', fn() => assertDirExists("{$root}/app/Facturacion/Controllers"));
test('Directorio routes existe', fn() => assertDirExists("{$root}/routes"));
test('Directorio sql existe', fn() => assertDirExists("{$root}/sql"));
test('Directorio storage/private existe', fn() => assertDirExists("{$root}/storage/private"));
test('Directorio tests existe', fn() => assertDirExists("{$root}/tests"));

// Archivos de configuración
test('composer.json existe', fn() => assertFileExists("{$root}/composer.json"));
test('composer.lock existe', fn() => assertFileExists("{$root}/composer.lock"));

// Archivos principales
test('FacturacionConfig.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Config/FacturacionConfig.php"));
test('ResponseHelper.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Helpers/ResponseHelper.php"));
test('RateLimiter.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Helpers/RateLimiter.php"));
test('ApiAuthPolicy.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Helpers/ApiAuthPolicy.php"));
test('EmpresaAccessPolicy.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Helpers/EmpresaAccessPolicy.php"));

// Services
test('FacturacionService.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/FacturacionService.php"));
test('XmlService.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/XmlService.php"));
test('FirmaService.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/FirmaService.php"));
test('SunatService.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/SunatService.php"));
test('PdfService.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/PdfService.php"));
test('EncryptionService.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/EncryptionService.php"));
test('CertificadoService.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/CertificadoService.php"));
test('SvgSanitizer.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/SvgSanitizer.php"));
test('SunatEnvironment.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/SunatEnvironment.php"));
test('SunatCredentials.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Services/SunatCredentials.php"));

// Models
test('EmpresaFacturacion.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Models/EmpresaFacturacion.php"));
test('Comprobante.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Models/Comprobante.php"));
test('DetalleComprobante.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Models/DetalleComprobante.php"));

// Repositories
test('EmpresaFacturacionRepository.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Repositories/EmpresaFacturacionRepository.php"));
test('ComprobanteRepository.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Repositories/ComprobanteRepository.php"));

// Controllers
test('EmpresaController.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Controllers/EmpresaController.php"));
test('FacturacionController.php existe', fn() => assertFileExists("{$root}/app/Facturacion/Controllers/FacturacionController.php"));

// Routes
test('routes/facturacion.php existe', fn() => assertFileExists("{$root}/routes/facturacion.php"));

// SQL
test('sql/facturacion_schema.sql existe', fn() => assertFileExists("{$root}/sql/facturacion_schema.sql"));
test('Migración consolidada SaaS existe', fn() => assertFileExists("{$root}/sql/20260916_consolidar_saas.sql"));

// Features
test('features/00-setup-proyecto.md existe', fn() => assertFileExists("{$root}/features/00-setup-proyecto.md"));
test('features/historial.md existe', fn() => assertFileExists("{$root}/features/historial.md"));

// Vendor
test('vendor/autoload.php existe', fn() => assertFileExists("{$root}/vendor/autoload.php"));
test('greenter/lite instalado', fn() => assertFileExists("{$root}/vendor/greenter/lite"));
test('greenter/report instalado', fn() => assertFileExists("{$root}/vendor/greenter/report"));
test('dompdf instalado', fn() => assertFileExists("{$root}/vendor/dompdf/dompdf"));

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

exit($failed > 0 ? 1 : 0);
