# API Facturación Electrónica SUNAT - Perú

## Stack

- PHP 8.1
- Flight PHP (micro-framework)
- MySQL/MariaDB
- Greenter Lite v4 (XML UBL + SUNAT SOAP)
- dompdf v3 (PDF)
- AES-256-CBC (credenciales cifradas)

## Configuración

### 1. Base de datos

```cmd
mysql -u root -p < sql\facturacion_schema.sql
mysql -u root -p < sql\facturacion_seed_beta.sql
```

### 2. Variables de entorno

Editar `.env`:

```env
FAC_DB_DSN=mysql:host=localhost;dbname=facturacion;charset=utf8mb4
FAC_DB_USER=root
FAC_DB_PASS=
FAC_ENCRYPTION_KEY=tu-key-32-bytes
FAC_ENCRYPTION_IV=tu-iv-16-bytes
FAC_SUNAT_ENV=beta
```

### 3. Certificado digital

Copiar archivo `.pfx` a:
```
storage/private/certificados/{RUC}/{RUC}.pfx
```

### 4. Apache (Laragon)

Acceder a: `http://api-sunat-peru.test`

---

## Endpoints

### Empresas

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/facturacion/empresas` | Listar todas las empresas |
| `GET` | `/api/facturacion/empresas/{id}` | Ver empresa por ID |
| `POST` | `/api/facturacion/empresas` | Crear empresa |
| `PUT` | `/api/facturacion/empresas/{id}` | Editar empresa |
| `DELETE` | `/api/facturacion/empresas/{id}` | Eliminar empresa |
| `POST` | `/api/facturacion/empresas/{id}/certificado` | Subir certificado .pfx |
| `POST` | `/api/facturacion/empresas/{id}/series` | Crear nueva serie |

### Comprobantes

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/facturacion/comprobantes` | Listar comprobantes |
| `GET` | `/api/facturacion/comprobantes/{id}` | Ver comprobante |
| `POST` | `/api/facturacion/comprobantes` | Emitir comprobante (crea + genera todo) |
| `POST` | `/api/facturacion/comprobantes/{id}/procesar` | Procesar comprobante pendiente |
| `GET` | `/api/facturacion/comprobantes/{id}/estado` | Consultar estado en SUNAT |
| `GET` | `/api/facturacion/comprobantes/{id}/pdf` | Descargar PDF |
| `GET` | `/api/facturacion/comprobantes/{id}/xml` | Descargar XML |
| `GET` | `/api/facturacion/comprobantes/{id}/cdr` | Descargar CDR |

---

## Flujo completo

```
1. Crear empresa
2. Subir certificado .pfx
3. Emitir comprobante → crea registro en BD (estado: pendiente)
4. Procesar comprobante:
   a. Generar XML UBL 2.1
   b. Firmar XML (PKCS12)
   c. Enviar a SUNAT
   d. Generar PDF
5. Verificar resultado (estado: aceptado/rechazado/error)
6. Descargar archivos (XML/PDF/CDR)
```

---

## Ejemplos con cURL

### 1. Crear empresa

```cmd
curl -X POST http://api-sunat-peru.test/api/facturacion/empresas ^
  -H "Content-Type: application/json" ^
  -d "{\"ruc\":\"20467534026\",\"razon_social\":\"AMERICA MOVIL PERU S.A.C.\",\"sol_usuario\":\"MODDATOS\",\"sol_password\":\"TU_PASSWORD_SOL\",\"certificado_password\":\"mireyra123\",\"entorno\":\"beta\"}"
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ruc": "20467534026",
    "razon_social": "AMERICA MOVIL PERU S.A.C.",
    "sol_usuario": "MODDATOS",
    "entorno": "beta",
    "activo": true
  }
}
```

### 2. Subir certificado

```cmd
curl -X POST http://api-sunat-peru.test/api/facturacion/empresas/1/certificado ^
  -F "certificado=@C:\ruta\certificado.pfx"
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "message": "Certificado subido correctamente",
    "path": "D:\\laragon\\www\\api-sunat-peru\\storage\\private\\certificados\\20467534026\\certificado.pfx"
  }
}
```

### 3. Emitir comprobante

```cmd
curl -X POST http://api-sunat-peru.test/api/facturacion/comprobantes ^
  -H "Content-Type: application/json" ^
  -d "{\"empresa_id\":1,\"tipo_comprobante\":\"03\",\"serie\":\"B001\",\"moneda\":\"PEN\",\"cliente\":{\"tipo_documento\":\"1\",\"numero_documento\":\"12345678\",\"nombre\":\"Cliente Test\",\"direccion\":\"Lima\"},\"items\":[{\"codigo\":\"PROD001\",\"descripcion\":\"Producto Test\",\"unidad\":\"NIU\",\"cantidad\":1,\"precio_unitario\":100,\"afectacion_igv\":\"10\"}]}"
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tipo": "03",
    "serie": "B001",
    "correlativo": "00000001",
    "numero": "B001-00000001",
    "estado": "pendiente"
  }
}
```

### 4. Procesar comprobante (XML → Firma → SUNAT → PDF)

```cmd
curl -X POST http://api-sunat-peru.test/api/facturacion/comprobantes/1/procesar
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tipo": "03",
    "serie": "B001",
    "correlativo": "00000001",
    "numero": "B001-00000001",
    "estado": "aceptado",
    "hash": "abc123...",
    "xml_path": "storage/private/facturacion/20467534026/2026/09/xml/B001-00000001.xml",
    "pdf_path": "storage/private/facturacion/20467534026/2026/09/pdf/B001-00000001.pdf",
    "cdr_path": "storage/private/facturacion/20467534026/2026/09/cdr/R-20467534026-03-B001-00000001.zip"
  }
}
```

### 5. Consultar estado

```cmd
curl http://api-sunat-peru.test/api/facturacion/comprobantes/1/estado
```

### 6. Descargar archivos

```cmd
curl -O http://api-sunat-peru.test/api/facturacion/comprobantes/1/xml
curl -O http://api-sunat-peru.test/api/facturacion/comprobantes/1/pdf
curl -O http://api-sunat-peru.test/api/facturacion/comprobantes/1/cdr
```

---

## Estados del comprobante

```
pendiente → generando → enviado → aceptado
                         ↓
                      rechazado
                         ↓
                        error
```

| Estado | Descripción |
|--------|-------------|
| `pendiente` | Creado, listo para procesar |
| `generando` | Generando XML |
| `enviado` | Enviado a SUNAT, esperando respuesta |
| `aceptado` | SUNAT aceptó el comprobante |
| `rechazado` | SUNAT rechazó el comprobante |
| `error` | Error en el proceso |

---

## Tipos de comprobante

| Código | Tipo |
|--------|------|
| `01` | Factura electrónica |
| `03` | Boleta de venta electrónica |
| `07` | Nota de crédito electrónica |
| `08` | Nota de débito electrónica |

---

## Monedas

| Código | Moneda |
|--------|--------|
| `PEN` | Soles |
| `USD` | Dólares |

---

## Tipos de documento cliente

| Código | Tipo |
|--------|------|
| `1` | DNI (8 dígitos) |
| `6` | RUC (11 dígitos) |
| `4` | Carné de extranjería |
| `7` | Pasaporte |
| `11` | Doc. tributario |
| `A` | Doc. identidad |

---

## Afectaciones IGV

| Código | Descripción |
|--------|-------------|
| `10` | Gravado - Operación onerosa |
| `20` | Exonerado - Operación onerosa |
| `30` | Inafecto - Operación onerosa |
| `21` | Exonerado - Transferencia gratuita |

---

## Seguridad

- Credenciales SOL y contraseña del certificado se cifran con **AES-256-CBC** en la BD
- El endpoint `GET /empresas` **NO** retorna contraseñas
- El endpoint `GET /empresas/{id}` incluye campo `sol_usuario` pero **NO** `sol_password`
- Para debug, `toFullArray()` muestra `[CIFRADO]` en vez del valor real

---

## Estructura de archivos

```
api-sunat-peru/
├── index.php                          ← Entry point (Apache)
├── .htaccess                          ← Reescritura URL
├── .env                               ← Variables de entorno
├── composer.json
├── app/Facturacion/
│   ├── Config/
│   │   ├── FacturacionConfig.php      ← Configuración centralizada
│   │   └── Database.php               ← Conexión PDO
│   ├── Controllers/
│   │   ├── EmpresaController.php      ← CRUD empresas
│   │   └── FacturacionController.php  ← Comprobantes
│   ├── DTO/
│   │   └── ComprobanteDTO.php         ← Validación de entrada
│   ├── Exceptions/
│   │   ├── FacturacionException.php
│   │   ├── SunatException.php
│   │   ├── CertificadoException.php
│   │   └── PdfException.php
│   ├── Helpers/
│   │   └── ResponseHelper.php         ← Respuestas JSON
│   ├── Models/
│   │   ├── EmpresaFacturacion.php
│   │   ├── Comprobante.php
│   │   └── DetalleComprobante.php
│   ├── Repositories/
│   │   ├── EmpresaFacturacionRepository.php
│   │   └── ComprobanteRepository.php
│   └── Services/
│       ├── CertificadoService.php     ← Validación .pfx
│       ├── EncryptionService.php      ← AES-256-CBC
│       ├── FacturacionService.php     ← Lógica principal
│       ├── FirmaService.php           ← Firma digital
│       ├── PdfService.php             ← Generación PDF
│       ├── SunatService.php           ← SOAP SUNAT
│       └── XmlService.php             ← XML UBL 2.1
├── routes/
│   └── facturacion.php                ← Rutas Flight
├── sql/
│   ├── facturacion_schema.sql         ← DDL (tablas)
│   └── facturacion_seed_beta.sql      ← Datos prueba
├── storage/private/
│   ├── certificados/{RUC}/            ← Certificados .pfx
│   └── facturacion/{RUC}/{año}/{mes}/ ← XML/PDF/CDR generados
└── tests/
    ├── unit/
    ├── integration/
    └── security/
```

---

## Cambiar a producción

1. Editar `.env`:
```env
FAC_SUNAT_ENV=produccion
```

2. Cambiar entorno de la empresa en BD:
```sql
UPDATE empresas_facturacion SET entorno = 'produccion' WHERE ruc = '20467534026';
```

3. Usar credenciales SOL y certificado de producción.
