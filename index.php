<?php

declare(strict_types=1);

// =====================================================
// Entry Point: API Facturación Electrónica SUNAT
// =====================================================

// Autoload Composer
require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Cargar rutas
require_once __DIR__ . '/routes/facturacion.php';

// Crear directorios de storage si no existen
App\Facturacion\Config\FacturacionConfig::getInstance()->ensureDirectories();

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

// Iniciar Flight
Flight::start();
