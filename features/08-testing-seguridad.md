# Fase 8: Testing y Seguridad

## Objetivo
Validar el funcionamiento completo del módulo con pruebas unitarias, de integración y de seguridad.

## Tipos de prueba

### 1. Empresa

| Prueba | Descripción |
|--------|-------------|
| Crear empresa | Datos válidos → OK |
| RUC inválido | 12345 → Error |
| Certificado inexistente | Archivo no existe → Error |
| Contraseña certificado incorrecta | → Error |
| Credenciales cifradas | BD no muestra texto plano |

### 2. Factura

| Prueba | Descripción |
|--------|-------------|
| Generar factura | Tipo 01 → OK |
| Generar XML | UBL válido |
| Firmar XML | Firma correcta |
| Generar PDF | Archivo válido |
| Enviar a SUNAT beta | Respuesta OK |
| Procesar CDR | Estado actualizado |

### 3. Boleta

| Prueba | Descripción |
|--------|-------------|
| Generar boleta | Tipo 03 → OK |
| Flujo completo | Igual que factura |

### 4. Nota de Crédito

| Prueba | Descripción |
|--------|-------------|
| Generar NC | Tipo 07 → OK |
| Referencia a comprobante original | OK |

### 5. Nota de Débito

| Prueba | Descripción |
|--------|-------------|
| Generar ND | Tipo 08 → OK |
| Referencia a comprobante original | OK |

### 6. Multiempresa

| Prueba | Descripción |
|--------|-------------|
| Empresa A usa certificado A | OK |
| Empresa B usa certificado B | OK |
| No hay contaminación | Cada empresa aislada |

### 7. Concurrencia

| Prueba | Descripción |
|--------|-------------|
| 2 emisiones simultáneas | Correlativos: 00000001, 00000002 |
| 10 emisiones simultáneas | Sin duplicados |
| Bloqueo de serie | Transacción protegida |

### 8. Seguridad

| Prueba | Descripción |
|--------|-------------|
| Credenciales no en JSON | Verificar respuesta |
| Credenciales no en logs | Revisar archivos log |
| Credenciales no en HTML | Verificar output |
| Credenciales no en URLs | Verificar requests |
| Password cifrado en BD | SELECT directo ≠ texto plano |

## Script de prueba concurrencia

```php
<?php
// test/concurrencia.php

$requests = 10;
$procs = [];

for ($i = 0; $i < $requests; $i++) {
    $procs[$i] = popen(
        'curl -X POST http://localhost/api/facturacion/comprobantes ' .
        '-H "Content-Type: application/json" ' .
        '-d \'' . json_encode($testData) . '\'',
        'r'
    );
}

$results = [];
foreach ($procs as $proc) {
    $results[] = fgets($proc);
}

// Verificar correlativos únicos
$correlativos = array_map(function($r) {
    return json_decode($r)->data->correlativo;
}, $results);

$unique = array_unique($correlativos);
assert(count($unique) === $requests, "¡Duplicados detectados!");
```

## Datos de prueba

NUNCA usar credenciales reales. Usar entorno beta SUNAT.

```php
$empresaTest = [
    'ruc' => '20000000001',  // RUC de prueba SUNAT
    'razon_social' => 'EMPRESA TEST SAC',
    'sol_usuario' => 'MODDATOS',
    'sol_password' => 'MODDATOS',
    'entorno' => 'beta'
];
```

## Checklist final

### Criterio de finalización

- [x] Se pueden registrar múltiples empresas
- [x] Cada empresa tiene sus propias credenciales
- [x] Cada empresa tiene su propio certificado
- [x] Se puede emitir factura
- [x] Se puede emitir boleta
- [x] Se genera XML
- [x] XML firmado correctamente
- [x] Se envía a SUNAT
- [x] Se procesa CDR
- [x] Se genera PDF
- [x] PDF contiene QR
- [x] XML/PDF/CDR se almacenan
- [x] Se puede descargar cada archivo
- [x] Los correlativos son seguros ante concurrencia
- [x] Existe separación Beta/Producción
- [x] Los errores no exponen credenciales
- [x] El módulo funciona con múltiples RUC
- [x] No se introducen frameworks pesados
- [x] No se requiere Node.js
- [x] No se requiere Docker
- [x] No se requiere Redis
- [x] El código sigue la arquitectura existente
