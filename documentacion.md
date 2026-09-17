# API pública de facturación electrónica

La API pública usa exclusivamente el token activo asignado a una empresa en `api_tokens`. El token determina la empresa automáticamente: no se debe enviar `empresa_id` y un token nunca puede consultar comprobantes de otra empresa.

Las sesiones JWT sirven solamente para el dashboard y no autentican estas rutas.

El destino SUNAT se obtiene siempre de `empresas_facturacion.entorno`: `beta` utiliza el servicio de envío de pruebas y el estado/CDR almacenado; `produccion` utiliza los servicios productivos de envío y consulta. No existe una variable global que sobrescriba el ambiente de la empresa.

## Autenticación

- GET: agregar `?token=TU_TOKEN` a la URL.
- POST: agregar `"token": "TU_TOKEN"` al JSON.
- Token ausente, inválido o inactivo: HTTP 401 `UNAUTHORIZED`.
- ID inexistente o perteneciente a otra empresa: HTTP 404 `NOT_FOUND`.

## Flujo de emisión

1. `POST /api/facturacion/comprobantes` crea el comprobante en estado `pendiente` y reserva el correlativo.
2. `POST /api/facturacion/comprobantes/{id}/procesar` genera el XML, firma, envía a SUNAT y genera los PDF.

## Endpoints

### GET `/api/facturacion/comprobantes`

Lista los comprobantes de la empresa autenticada. Filtros opcionales: `estado` y `tipo_comprobante`.

```http
GET /api/facturacion/comprobantes?token=TU_TOKEN&estado=aceptado&tipo_comprobante=03
```

Respuesta HTTP 200: `{"success":true,"data":[...]}`.

### GET `/api/facturacion/comprobantes/{id}`

Devuelve un comprobante propio, sus detalles y URLs de descarga.

```http
GET /api/facturacion/comprobantes/12?token=TU_TOKEN
```

Respuesta HTTP 200. Devuelve 404 si el ID no existe o pertenece a otra empresa.

### POST `/api/facturacion/comprobantes`

```json
{
  "token": "TU_TOKEN",
  "tipo_comprobante": "03",
  "serie": "B001",
  "moneda": "PEN",
  "fecha_emision": "2026-09-16",
  "cliente": {
    "tipo_documento": "1",
    "numero_documento": "12345678",
    "nombre": "Juan Perez",
    "direccion": "Av. Lima 123"
  },
  "items": [{
    "codigo": "P001",
    "descripcion": "Producto de prueba",
    "unidad": "NIU",
    "cantidad": 1,
    "precio_unitario": 118.00,
    "afectacion_igv": "10"
  }]
}
```

Respuesta HTTP 201:

```json
{
  "success": true,
  "data": {
    "id": 12,
    "tipo": "03",
    "serie": "B001",
    "correlativo": "00000012",
    "numero": "B001-00000012",
    "estado": "pendiente",
    "entorno": "beta",
    "message": "Comprobante creado. Usar POST /procesar para enviar a SUNAT."
  }
}
```

Tipos: `01`, `03`, `07`, `08`. Monedas: `PEN`, `USD`. Afectaciones IGV: `10`, `20`, `30`, `21`.

### POST `/api/facturacion/comprobantes/{id}/procesar`

```json
{ "token": "TU_TOKEN" }
```

Procesa comprobantes en estado `pendiente`, `generando` o `error`. Responde HTTP 200 con estado, entorno utilizado, hash, rutas y mensaje SUNAT. Un estado no procesable devuelve 400.

### GET `/api/facturacion/comprobantes/{id}/estado`

```http
GET /api/facturacion/comprobantes/12/estado?token=TU_TOKEN
```

En producción consulta el servicio de estado de SUNAT. En beta devuelve el estado confirmado durante el envío y el CDR almacenado, ya que SUNAT no ofrece un servicio beta para consultar CDR. La respuesta beta incluye `source: "local_cdr"`, `estado` y `cdr_disponible`. Responde 200 o 422 `SUNAT_ERROR` en producción.

### GET `/api/facturacion/comprobantes/{id}/pdf`

```http
GET /api/facturacion/comprobantes/12/pdf?token=TU_TOKEN
GET /api/facturacion/comprobantes/12/pdf?token=TU_TOKEN&formato=ticket
GET /api/facturacion/comprobantes/12/pdf?token=TU_TOKEN&formato=ticket&disposicion=inline
```

Devuelve el comprobante como `application/pdf`. El formato predeterminado es A4;
`formato=ticket` usa el formato térmico de 80 mm.

`disposicion` controla cómo se entrega el archivo:

- `attachment` (predeterminado): solicita al navegador descargar el PDF.
- `inline`: solicita al navegador mostrar el PDF en una pestaña, visor o `iframe`.

Responde HTTP 200 con el contenido binario del PDF, HTTP 400 si `disposicion`
no es `attachment` ni `inline`, y HTTP 404 si el comprobante o el formato no
están disponibles. El token siempre debe corresponder a la empresa propietaria
del comprobante.

### GET `/api/facturacion/comprobantes/{id}/xml`

```http
GET /api/facturacion/comprobantes/12/xml?token=TU_TOKEN
```

Descarga `application/xml`.

### GET `/api/facturacion/comprobantes/{id}/cdr`

```http
GET /api/facturacion/comprobantes/12/cdr?token=TU_TOKEN
```

Descarga `application/zip` con la constancia de recepción.

## Errores

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Descripción segura del error"
  }
}
```

| HTTP | Código | Uso |
|---:|---|---|
| 400 | `VALIDATION_ERROR` | JSON, datos o estado inválidos |
| 401 | `UNAUTHORIZED` | Token ausente, inválido o inactivo |
| 404 | `NOT_FOUND` | Comprobante ajeno/inexistente o archivo no disponible |
| 422 | `SUNAT_ERROR` | Error o rechazo SUNAT |
| 500 | `INTERNAL_ERROR` | Error interno sin información técnica |

Las rutas del dashboard, autenticación web y administración de empresas son internas y no se documentan aquí.
