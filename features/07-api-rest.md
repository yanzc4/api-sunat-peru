# Fase 7: API REST

## Objetivo
Exponer endpoints REST con Flight PHP para gestionar comprobantes y empresas.

## Archivos a crear

| Archivo | Propósito |
|---------|-----------|
| `routes/facturacion.php` | Rutas Flight PHP |
| `Controllers/FacturacionController.php` | Controlador comprobantes |
| `Controllers/EmpresaController.php` | Controlador empresas |

## Endpoints

### Comprobantes

```
POST   /api/facturacion/comprobantes              → Emitir comprobante
GET    /api/facturacion/comprobantes/{id}          → Ver comprobante
GET    /api/facturacion/comprobantes/{id}/pdf      → Descargar PDF
GET    /api/facturacion/comprobantes/{id}/xml      → Descargar XML
GET    /api/facturacion/comprobantes/{id}/cdr      → Descargar CDR
GET    /api/facturacion/comprobantes               → Listar comprobantes
```

### Empresas

```
POST   /api/facturacion/empresas                  → Crear empresa
PUT    /api/facturacion/empresas/{id}              → Editar empresa
GET    /api/facturacion/empresas/{id}              → Ver empresa
GET    /api/facturacion/empresas                   → Listar empresas
```

## Registro de rutas

```php
// routes/facturacion.php

Flight::route('POST /api/facturacion/comprobantes', function() {
    $controller = new FacturacionController();
    $controller->emitir();
});

Flight::route('GET /api/facturacion/comprobantes/@id', function($id) {
    $controller = new FacturacionController();
    $controller->ver($id);
});

Flight::route('GET /api/facturacion/comprobantes/@id/pdf', function($id) {
    $controller = new FacturacionController();
    $controller->descargarPdf($id);
});

Flight::route('GET /api/facturacion/comprobantes/@id/xml', function($id) {
    $controller = new FacturacionController();
    $controller->descargarXml($id);
});

Flight::route('GET /api/facturacion/comprobantes/@id/cdr', function($id) {
    $controller = new FacturacionController();
    $controller->descargarCdr($id);
});

Flight::route('POST /api/facturacion/empresas', function() {
    $controller = new EmpresaController();
    $controller->crear();
});

Flight::route('PUT /api/facturacion/empresas/@id', function($id) {
    $controller = new EmpresaController();
    $controller->editar($id);
});

Flight::route('GET /api/facturacion/empresas/@id', function($id) {
    $controller = new EmpresaController();
    $controller->ver($id);
});
```

## Request de emisión

```json
{
    "empresa_id": 1,
    "tipo_comprobante": "01",
    "serie": "F001",
    "moneda": "PEN",

    "cliente": {
        "tipo_documento": "6",
        "numero_documento": "20123456789",
        "nombre": "EMPRESA DEMO SAC",
        "direccion": "Av. Lima 123"
    },

    "items": [
        {
            "codigo": "PROD001",
            "descripcion": "Producto de prueba",
            "unidad": "NIU",
            "cantidad": 2,
            "precio_unitario": 118.00,
            "afectacion_igv": "10"
        }
    ]
}
```

## Respuesta exitosa

```json
{
    "success": true,
    "data": {
        "id": 123,
        "tipo": "01",
        "serie": "F001",
        "correlativo": "00000001",
        "numero": "F001-00000001",
        "estado": "aceptado",
        "hash": "abc123...",
        "pdf": "/api/facturacion/comprobantes/123/pdf",
        "xml": "/api/facturacion/comprobantes/123/xml",
        "cdr": "/api/facturacion/comprobantes/123/cdr"
    }
}
```

## Respuesta error

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "El RUC del cliente no es válido",
        "details": {
            "field": "cliente.numero_documento",
            "value": "12345"
        }
    }
}
```

## Códigos de error

| Código | HTTP | Significado |
|--------|------|-------------|
| VALIDATION_ERROR | 400 | Datos inválidos |
| EMPRESA_NOT_FOUND | 404 | Empresa no existe |
| CERTIFICADO_ERROR | 422 | Certificado inválido |
| SUNAT_REJECTED | 422 | SUNAT rechazó |
| SUNAT_TIMEOUT | 504 | Timeout SUNAT |
| INTERNAL_ERROR | 500 | Error interno |

## Seguridad

- No exponer credenciales SUNAT
- No exponer paths internos de archivos
- Validar acceso a empresa propietaria
- No mostrar stack traces

## Descarga de archivos

```php
Flight::route('GET /api/facturacion/comprobantes/@id/pdf', function($id) {
    $comprobante = $repo->find($id);
    
    if (!$comprobante || !file_exists($comprobante->pdf_path)) {
        Flight::json(['success' => false, 'error' => 'Archivo no encontrado'], 404);
        return;
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($comprobante->pdf_path) . '"');
    readfile($comprobante->pdf_path);
    exit;
});
```

## Verificación

- [x] POST comprobante crea y emite
- [x] GET comprobante retorna datos
- [x] GET pdf descarga archivo
- [x] GET xml descarga archivo
- [x] GET cdr descarga archivo
- [x] POST empresa crea registro
- [x] PUT empresa actualiza registro
- [x] Errores retornan JSON válido
- [x] No se exponen credenciales
- [x] Autenticación funciona (si aplica)
