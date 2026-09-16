Dependencias instaladas:
- greenter/lite v4.3.1
- greenter/report v4.3.1
- greenter/ws v4.3.1
- greenter/xml v4.3.1
- greenter/xmldsig v5.0.3
Nota: Se habilitó extensión soap en php.ini (requiere reiniciar PHP/Apache en Laragon).

http://api-sunat-peru.test/api/facturacion/comprobantes/15/procesar

PDF: GET http://api-sunat-peru.test/api/facturacion/comprobantes/6/pdf
XML: GET http://api-sunat-peru.test/api/facturacion/comprobantes/6/xml

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