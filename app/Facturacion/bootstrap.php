<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// =====================================================
// Bootstrap común de la aplicación.
// Tanto index.php (raíz) como public/index.php pasan por
// aquí, garantizando el mismo comportamiento en ambos.
// La sesión web se mantiene mediante JWT (cookie firmada
// HttpOnly); NO se usa session_start() ni sesiones de
// Apache/PHP.
// =====================================================

// Autoload Composer (__DIR__ = app/Facturacion → 2 niveles arriba = raíz del proyecto)
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

// Validar configuración JWT temprano (falla rápido si falta el secreto)
App\Facturacion\Config\FacturacionConfig::getInstance();

// Crear directorios de storage si no existen
App\Facturacion\Config\FacturacionConfig::getInstance()->ensureDirectories();

// Cargar rutas
require_once dirname(__DIR__, 2) . '/routes/facturacion.php';

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Flush output buffers al finalizar
register_shutdown_function(function () {
    while (ob_get_level()) {
        ob_end_flush();
    }
});
