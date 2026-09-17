<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Facturacion\Models\Comprobante;
use App\Facturacion\Models\EmpresaFacturacion;
use App\Facturacion\Services\SunatService;

$pdo = new PDO('sqlite::memory:');
$empresa = new EmpresaFacturacion();
$empresa->entorno = 'beta';

$comprobante = new Comprobante();
$comprobante->estado = 'aceptado';
$comprobante->codigoRespuesta = '0';
$comprobante->mensajeRespuesta = 'Aceptado por SUNAT beta';
$comprobante->cdrPath = 'storage/private/cdr/respuesta.zip';

$result = (new SunatService($pdo))->consultarEstadoComprobante($empresa, $comprobante);

$checks = [
    $result['success'] === true,
    $result['source'] === 'local_cdr',
    $result['entorno'] === 'beta',
    $result['estado'] === 'aceptado',
    $result['code'] === '0',
    $result['cdr_disponible'] === true,
];

if (in_array(false, $checks, true)) {
    echo "[FAIL] Estado beta inesperado\n";
    exit(1);
}

echo "[PASS] Beta devuelve el estado y CDR almacenados sin consultar producción\n";
exit(0);
