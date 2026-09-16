# Fase 5: Integración SUNAT

## Objetivo
Enviar comprobantes a SUNAT, recibir CDR y gestionar respuestas con manejo de errores robusto.

## Archivos a crear

| Archivo | Propósito |
|---------|-----------|
| `Services/SunatService.php` | Comunicación con SUNAT |

## Endpoints SUNAT

### Beta (pruebas)
```
https://e-beta.sunat.gob.pe/pe/tncityservice/v1/cdr
```

### Producción
```
https://api.sunat.gob.pe/v1/contribuyente/gem/comprobantes
```

## Flujo envío

```
1. Obtener XML firmado
   ↓
2. Obtener CDR (hash del comprobante)
   ↓
3. Preparar envío SOAP
   ↓
4. Enviar a SUNAT
   ↓
5. Recibir respuesta
   ↓
6. Si respuesta = 200 OK → CDR recibido
   ↓
7. Procesar CDR
   ↓
8. Actualizar estado comprobante
```

## Ejemplo envío

```php
use Greenter\Api SunatApi;

$api = new SunatApi(
    $empresa->solUsuario,
    $empresa->solPassword, // descifrado
    $empresa->entorno === 'produccion'
);

// Enviar comprobante
$response = $api->send(
    $tipoComprobante,  // 01
    $xmlFirmado,
    $hash
);

// Procesar respuesta
if ($response->isSuccess()) {
    // Aceptado
    $comprobante->estado = 'aceptado';
    $comprobante->codigo_respuesta = $response->getCode();
    $comprobante->mensaje_respuesta = $response->getMessage();
    $comprobante->cdr_path = $response->getCdrPath();
} else {
    // Rechazado
    $comprobante->estado = 'rechazado';
    $comprobante->codigo_respuesta = $response->getCode();
    $comprobante->mensaje_respuesta = $response->getMessage();
}
```

## CDR (Constancia de Recepción)

El CDR contiene:
- Código de respuesta
- Mensaje de SUNAT
- Observaciones
- Fecha de respuesta

Almacenar en:
```
/storage/private/facturacion/{ruc}/{ano}/{mes}/cdr/
    R-{ruc}-{tipo}-{serie}-{correlativo}.zip
```

## Manejo de errores

### Tipos de respuesta

| Código | Significado | Acción |
|--------|-------------|--------|
| 0 | Aceptado | Guardar CDR |
| 201 | Emisión exitosa | Guardar CDR |
| 202-299 | Rechazado | Guardar error |
| 100-199 | Observaciones | Guardar con obs |
| 400+ | Error servidor | Reintentar manual |

### Errores de conexión

```php
try {
    $response = $api->send(...);
} catch (ConnectException $e) {
    // Timeout o sin conexión
    $comprobante->estado = 'error';
    $comprobante->mensaje_respuesta = 'Error de conexión con SUNAT';
    // NO reintentar automáticamente
} catch (SoapException $e) {
    // Error SOAP
    $comprobante->estado = 'error';
    $comprobante->mensaje_respuesta = 'Error SOAP: ' . $e->getMessage();
}
```

### Reglas de reintentos

- **Rechazo confirmado**: NO reintentar. Corregir datos y emitir nuevo comprobante.
- **Timeout/Conexión**: NO reintentar automáticamente. Consultar estado primero.
- **Respuesta desconocida**: NO reintentar. Revisar manualmente.

NUNCA reutilizar correlativos de comprobantes que pudieron enviarse.

## Almacenamiento

```php
// XML
$xmlPath = "/storage/private/facturacion/{$ruc}/{$ano}/{$mes}/xml/{$fileName}.xml";
file_put_contents($xmlPath, $xmlFirmado);

// CDR
$cdrPath = "/storage/private/facturacion/{$ruc}/{$ano}/{$mes}/cdr/{$cdrFileName}.zip";
file_put_contents($cdrPath, $cdrData);
```

## Verificación

- [x] Envío a SUNAT beta funciona
- [x] CDR procesado correctamente
- [x] Estado actualizado: aceptado/rechazado
- [x] Errores de conexión manejados
- [x] No se reintentan rechazos automáticamente
- [x] CDR almacenado en disco
- [x] XML almacenado en disco
- [x] Timeout no causa duplicados
