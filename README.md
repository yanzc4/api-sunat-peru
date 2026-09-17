Dependencias instaladas:
- greenter/lite v4.3.1
- greenter/report v4.3.1
- greenter/ws v4.3.1
- greenter/xml v4.3.1
- greenter/xmldsig v5.0.3
Nota: Se habilitó extensión soap en php.ini (requiere reiniciar PHP/Apache en Laragon).

POST http://api-sunat-peru.test/api/facturacion/comprobantes/15/procesar
Body: {"token":"TOKEN_DE_LA_EMPRESA"}

PDF: GET http://api-sunat-peru.test/api/facturacion/comprobantes/6/pdf?token=TOKEN_DE_LA_EMPRESA
PDF para visualizar: GET http://api-sunat-peru.test/api/facturacion/comprobantes/6/pdf?disposicion=inline&token=TOKEN_DE_LA_EMPRESA
XML: GET http://api-sunat-peru.test/api/facturacion/comprobantes/6/xml?token=TOKEN_DE_LA_EMPRESA

El token determina la empresa. No enviar `empresa_id`. Las rutas públicas de
comprobantes no aceptan el JWT del dashboard. Documentación completa: `/doc`.

admin@admin.com
admin123

{
  "token": "d74c7cc66897826af0255dc6f0de74a7",
  "tipo_comprobante": "03",
  "serie": "B001",
  "moneda": "PEN",
  "cliente": {
    "tipo_documento": "1",
    "numero_documento": "12345678",
    "nombre": "Juan Perez",
    "direccion": "Av. Lima 123"
  },
  "items": [
    {
      "codigo": "PROD001",
      "descripcion": "Laptop de Prueba ejemplo de nombre de producto probando",
      "unidad": "NIU",
      "cantidad": 1,
      "precio_unitario": 1180.00,
      "afectacion_igv": "10"
    }
  ]
}

## Facturador interno

Las rutas `/productos`, `/vender` y `/ventas` usan la sesión JWT del panel. No exponen el
token empresarial de la API pública. Tanto administradores como clientes solo
pueden trabajar con empresas cuyo `usuario_id` coincide con el usuario actual.

Antes de usar el catálogo, ejecutar manualmente:

```sql
SOURCE sql/20260917_create_productos.sql;
```

### Consulta de DNI y RUC

La integración está encapsulada en
`app/Facturacion/Services/DocumentLookupService.php`. Para cambiar de proveedor
en el futuro, se debe reemplazar esa clase o adaptar su normalización, manteniendo
la respuesta interna con `tipo_documento`, `numero_documento`, `nombre` y
`direccion`.

Las credenciales se leen únicamente en el servidor desde `.env`:

```dotenv
FAC_DOCUMENT_LOOKUP_BASE_URL=https://apiperu.codemultiall.net.pe/api/v1
FAC_DOCUMENT_LOOKUP_TOKEN=token-privado-del-proveedor
```

El token no se incluye en HTML ni JavaScript. La consulta utiliza tiempos límite
de conexión y permite continuar llenando los datos del cliente manualmente si el
proveedor no responde.

La impresión y la opción de compartir descargan el PDF mediante una ruta interna
protegida por JWT. El navegador lo mantiene como `Blob`; para compartir se crea
un objeto `File` y se usa Web Share API, sin entregar el enlace del archivo.
