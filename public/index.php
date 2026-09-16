<?php

declare(strict_types=1);

// =====================================================
// Entry Point: API Facturación Electrónica SUNAT
// =====================================================

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_save_path(dirname(__DIR__) . '/storage/private/sessions');
session_start();

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Cargar rutas
require_once __DIR__ . '/../routes/facturacion.php';

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

// Iniciar Flight
Flight::start();
