<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Facturacion\Helpers\ResponseHelper;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', sys_get_temp_dir() . '/api-sunat-response-test.log');

$scenario = $argv[1] ?? '';

switch ($scenario) {
    case 'validation':
        ResponseHelper::validationError('Test error');
        break;
    case 'not_found':
        ResponseHelper::notFound('No existe');
        break;
    case 'sunat':
        ResponseHelper::sunatError('SUNAT rechazó', '0101');
        break;
    case 'custom':
        ResponseHelper::error('TEST', 'Error controlado', 400);
        break;
    case 'internal':
        ResponseHelper::internalException(
            new RuntimeException('PDO password=super-secret /vendor/internal.php'),
            'Prueba de error interno'
        );
        break;
    default:
        fwrite(STDERR, "Escenario desconocido\n");
        exit(1);
}
