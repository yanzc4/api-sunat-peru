# Módulo de Facturación Electrónica SUNAT para PHP

## Objetivo

Implementar un módulo de facturación electrónica para Perú integrado al sistema existente en **PHP + Flight PHP + Twig + MySQL/MariaDB + PDO**, utilizando **Greenter Lite** como motor de facturación.

El módulo debe ser:

* Multiempresa / multi-RUC.
* Compatible con SUNAT.
* Ligero para servidores VPS con aproximadamente 1 GB de RAM.
* Sin Laravel.
* Sin Node.js.
* Sin Docker.
* Integrable con la arquitectura existente.
* Capaz de generar XML firmado.
* Capaz de enviar comprobantes a SUNAT.
* Capaz de consultar/guardar el CDR.
* Capaz de generar representación PDF.
* Preparado para crecimiento futuro.

---

# 1. Dependencias

Utilizar:

* PHP 8.x
* Flight PHP
* MySQL/MariaDB
* PDO
* Composer
* Greenter Lite
* Greenter Report

Instalar:

```bash
composer require greenter/lite
composer require greenter/report
```

No instalar Laravel ni frameworks adicionales.

No instalar Redis.

No crear servicios adicionales innecesarios.

La implementación debe priorizar bajo consumo de RAM y CPU.

---

# 2. Arquitectura

Crear un módulo independiente:

```text
/app/
    Facturacion/
        Config/
        Services/
        Repositories/
        Models/
        Helpers/
        Exceptions/
        DTO/
```

Ejemplo:

```text
/app/Facturacion/
    Config/
        FacturacionConfig.php

    Services/
        FacturacionService.php
        SunatService.php
        XmlService.php
        PdfService.php
        CertificadoService.php

    Repositories/
        EmpresaFacturacionRepository.php
        ComprobanteRepository.php

    Models/
        EmpresaFacturacion.php
        Comprobante.php
        DetalleComprobante.php

    DTO/
        ComprobanteDTO.php

    Exceptions/
        FacturacionException.php
        SunatException.php
```

Adaptar los nombres a la estructura real del proyecto.

No modificar innecesariamente módulos existentes.

---

# 3. Multiempresa

El sistema debe permitir que una misma instalación gestione múltiples empresas/RUC.

Ejemplo:

```text
Empresa 1
RUC: 20111111111
Certificado: certificado_1.pfx
Usuario SOL: usuario1
Password SOL: ********

Empresa 2
RUC: 20222222222
Certificado: certificado_2.pfx
Usuario SOL: usuario2
Password SOL: ********
```

Cada comprobante debe estar asociado obligatoriamente a una empresa.

Nunca utilizar las credenciales de una empresa para emitir comprobantes de otra.

La empresa debe seleccionarse mediante su ID interno.

---

# 4. Tabla de empresas

Crear una tabla similar a:

```sql
CREATE TABLE empresas_facturacion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,

    ruc VARCHAR(11) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    nombre_comercial VARCHAR(255) NULL,

    direccion VARCHAR(500) NULL,
    ubigeo VARCHAR(6) NULL,
    departamento VARCHAR(100) NULL,
    provincia VARCHAR(100) NULL,
    distrito VARCHAR(100) NULL,

    sol_usuario VARCHAR(100) NOT NULL,
    sol_password TEXT NOT NULL,

    certificado_path VARCHAR(500) NOT NULL,
    certificado_password TEXT NOT NULL,

    entorno ENUM('beta','produccion') NOT NULL DEFAULT 'beta',

    activo TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    UNIQUE KEY uk_ruc (ruc),
    UNIQUE KEY uk_empresa (empresa_id)
);
```

Si el proyecto ya posee una tabla de empresas/negocios, reutilizarla en lugar de duplicar información.

---

# 5. Seguridad

Las siguientes credenciales nunca deben aparecer en:

* HTML.
* JavaScript.
* URLs.
* logs.
* respuestas JSON.
* mensajes de error.
* PDF.
* XML generado para descarga pública.

Especial cuidado con:

```text
sol_password
certificado_password
```

No registrar contraseñas en logs.

Si el proyecto dispone de un sistema de cifrado de credenciales, utilizarlo.

Si no existe, implementar cifrado utilizando las capacidades nativas de PHP antes que introducir dependencias pesadas.

---

# 6. Certificados digitales

El sistema debe soportar certificados utilizados para SUNAT, normalmente:

```text
.pfx
.p12
```

El certificado debe almacenarse fuera del directorio público.

Ejemplo:

```text
/storage/private/certificados/
    empresa_1.pfx
    empresa_2.pfx
```

Nunca guardar certificados en:

```text
/public/
```

Nunca permitir acceso directo mediante URL.

Validar:

* existencia;
* extensión;
* contraseña;
* fecha de expiración;
* empresa propietaria.

---

# 7. Comprobantes iniciales

Implementar inicialmente:

### Factura

```text
Tipo SUNAT: 01
```

### Boleta

```text
Tipo SUNAT: 03
```

### Nota de crédito

```text
Tipo SUNAT: 07
```

### Nota de débito

```text
Tipo SUNAT: 08
```

La arquitectura debe permitir añadir posteriormente:

* Guía de remisión.
* Factura de exportación.
* Percepciones.
* Retenciones.
* Otros documentos SUNAT.

No implementar funcionalidades futuras si no son necesarias para el MVP.

---

# 8. Numeración

Cada empresa debe tener sus propias series y correlativos.

Ejemplo:

```text
Empresa A
F001-00000001
F001-00000002

Empresa B
F001-00000001
F001-00000002
```

El correlativo debe ser independiente por empresa y serie.

No generar números mediante:

```php
SELECT MAX(numero)
```

porque puede provocar duplicados bajo concurrencia.

Utilizar una tabla de series/correlativos con transacción y bloqueo.

Ejemplo:

```sql
CREATE TABLE comprobante_series (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    tipo_comprobante VARCHAR(2) NOT NULL,
    serie VARCHAR(4) NOT NULL,
    correlativo BIGINT UNSIGNED NOT NULL DEFAULT 0,

    UNIQUE KEY uk_serie (
        empresa_id,
        tipo_comprobante,
        serie
    )
);
```

---

# 9. Tabla de comprobantes

Crear una tabla similar a:

```sql
CREATE TABLE comprobantes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    empresa_id BIGINT UNSIGNED NOT NULL,

    tipo_comprobante VARCHAR(2) NOT NULL,
    serie VARCHAR(4) NOT NULL,
    correlativo BIGINT UNSIGNED NOT NULL,

    fecha_emision DATE NOT NULL,
    hora_emision TIME NULL,

    moneda VARCHAR(3) NOT NULL DEFAULT 'PEN',

    cliente_tipo_documento VARCHAR(2) NULL,
    cliente_numero_documento VARCHAR(20) NULL,
    cliente_nombre VARCHAR(255) NULL,
    cliente_direccion VARCHAR(500) NULL,

    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    igv DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,

    xml_path VARCHAR(500) NULL,
    pdf_path VARCHAR(500) NULL,
    cdr_path VARCHAR(500) NULL,

    hash_cpe VARCHAR(255) NULL,

    estado ENUM(
        'pendiente',
        'generando',
        'enviado',
        'aceptado',
        'rechazado',
        'error'
    ) NOT NULL DEFAULT 'pendiente',

    codigo_respuesta VARCHAR(20) NULL,
    mensaje_respuesta TEXT NULL,

    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    UNIQUE KEY uk_comprobante (
        empresa_id,
        tipo_comprobante,
        serie,
        correlativo
    )
);
```

Adaptar tipos y nombres a la base de datos existente.

---

# 10. Detalle del comprobante

Crear:

```sql
CREATE TABLE comprobante_detalles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    comprobante_id BIGINT UNSIGNED NOT NULL,

    codigo_producto VARCHAR(100) NULL,
    descripcion TEXT NOT NULL,

    unidad VARCHAR(10) NOT NULL DEFAULT 'NIU',

    cantidad DECIMAL(15,4) NOT NULL,
    precio_unitario DECIMAL(15,6) NOT NULL,

    valor_unitario DECIMAL(15,6) NULL,

    subtotal DECIMAL(15,2) NOT NULL,
    igv DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,

    afectacion_igv VARCHAR(10) NULL
);
```

No asumir que todos los productos tienen IGV.

Preparar el modelo para:

* Gravado.
* Exonerado.
* Inafecto.
* Gratuito.

---

# 11. Flujo de emisión

El flujo debe ser:

```text
1. Validar empresa
        ↓
2. Validar certificado
        ↓
3. Validar datos del cliente
        ↓
4. Validar productos
        ↓
5. Obtener correlativo
        ↓
6. Construir comprobante Greenter
        ↓
7. Generar XML UBL
        ↓
8. Firmar XML
        ↓
9. Generar hash
        ↓
10. Generar PDF
        ↓
11. Enviar a SUNAT
        ↓
12. Obtener CDR
        ↓
13. Guardar XML
        ↓
14. Guardar PDF
        ↓
15. Guardar CDR
        ↓
16. Actualizar estado
```

---

# 12. Estados

Utilizar estados claros:

```text
pendiente
generando
enviado
aceptado
rechazado
error
```

Si SUNAT acepta:

```text
aceptado
```

Si SUNAT rechaza:

```text
rechazado
```

Guardar siempre:

* código;
* descripción;
* mensaje;
* CDR;
* fecha de respuesta.

---

# 13. Archivos

Organizar los archivos por RUC y año/mes:

```text
/storage/private/facturacion/

    20111111111/
        2026/
            09/

                xml/
                    F001-00000001.xml

                pdf/
                    F001-00000001.pdf

                cdr/
                    R-20111111111-01-F001-00000001.zip
```

Nunca guardar archivos de una empresa mezclados con otra.

---

# 14. PDF

Utilizar:

```text
greenter/report
```

para generar la representación impresa.

El PDF debe incluir como mínimo:

* Logo de empresa.
* Razón social.
* RUC.
* Dirección.
* Tipo de comprobante.
* Serie.
* Correlativo.
* Fecha.
* Cliente.
* Documento del cliente.
* Detalle.
* Cantidad.
* Precio.
* IGV.
* Total.
* Moneda.
* QR.
* Hash cuando corresponda.

El diseño debe ser limpio y profesional.

Preparar el código para poder cambiar posteriormente la plantilla PDF.

---

# 15. No utilizar navegador para PDF

No implementar una solución basada en:

```text
Chrome headless
Puppeteer
Playwright
Node.js
```

porque el objetivo es mantener bajo consumo de recursos.

Preferir el mecanismo de PDF compatible con Greenter Report que sea viable en el servidor.

Si una dependencia del PDF requiere un binario externo, documentar claramente:

* paquete necesario;
* instalación;
* ubicación;
* consumo aproximado;
* alternativa disponible.

---

# 16. API interna

Crear endpoints compatibles con Flight PHP.

Ejemplo:

```text
POST /api/facturacion/comprobantes
GET  /api/facturacion/comprobantes/{id}
GET  /api/facturacion/comprobantes/{id}/pdf
GET  /api/facturacion/comprobantes/{id}/xml
GET  /api/facturacion/comprobantes/{id}/cdr

POST /api/facturacion/empresas
PUT  /api/facturacion/empresas/{id}
GET  /api/facturacion/empresas/{id}
```

No exponer credenciales SUNAT mediante API.

---

# 17. Endpoint de emisión

Ejemplo de JSON:

```json
{
    "empresa_id": 1,
    "tipo_comprobante": "01",
    "serie": "F001",

    "cliente": {
        "tipo_documento": "6",
        "numero_documento": "20123456789",
        "nombre": "EMPRESA DEMO SAC",
        "direccion": "Lima"
    },

    "moneda": "PEN",

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

La API debe validar todos los datos antes de intentar enviar a SUNAT.

---

# 18. Respuesta de emisión

Respuesta exitosa:

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
        "hash": "...",
        "pdf": "...",
        "xml": "...",
        "cdr": "..."
    }
}
```

Respuesta de error:

```json
{
    "success": false,
    "error": {
        "code": "SUNAT_REJECTED",
        "message": "Comprobante rechazado por SUNAT"
    }
}
```

Nunca devolver excepciones PHP completas al cliente.

---

# 19. Transacciones

La generación del correlativo debe estar protegida.

Evitar:

```text
dos solicitudes
       ↓
ambas leen correlativo 10
       ↓
ambas generan 11
       ↓
DUPLICADO
```

Utilizar transacciones SQL.

El comprobante y sus detalles deben guardarse de forma atómica.

Si falla antes del envío, dejar el comprobante correctamente identificado como error/pending según corresponda.

No reutilizar automáticamente un correlativo que haya podido ser enviado a SUNAT.

---

# 20. SUNAT Beta y Producción

Debe existir configuración:

```text
beta
produccion
```

Nunca mezclar credenciales de ambos entornos.

La empresa debe indicar explícitamente el entorno.

Ejemplo:

```php
$empresa->entorno === 'beta'
```

utiliza endpoint beta.

```php
$empresa->entorno === 'produccion'
```

utiliza endpoint producción.

Antes de cambiar a producción mostrar una confirmación administrativa.

---

# 21. Manejo de errores

Capturar errores de:

* validación;
* certificado;
* firma;
* XML;
* conexión;
* timeout;
* SUNAT;
* CDR;
* PDF;
* base de datos.

Crear excepciones específicas:

```php
FacturacionException
SunatException
CertificadoException
PdfException
```

Registrar errores técnicos en logs internos.

No mostrar:

```text
password
certificate password
SOL credentials
stack trace
```

al usuario.

---

# 22. Reintentos

No reenviar automáticamente un comprobante cuando no se conoce si SUNAT recibió el documento.

Diferenciar:

```text
rechazo confirmado
```

de:

```text
error de conexión
timeout
respuesta desconocida
```

Para un timeout, primero implementar mecanismo de consulta/revisión antes de generar otro comprobante.

Evitar duplicados.

---

# 23. PDF y XML descargables

Las rutas públicas no deben permitir acceso directo a:

```text
/storage/private/
```

Crear endpoints protegidos:

```text
GET /api/facturacion/comprobantes/{id}/pdf
GET /api/facturacion/comprobantes/{id}/xml
```

Validar que el usuario tenga acceso a la empresa propietaria del comprobante.

---

# 24. QR

El PDF debe incluir QR con la información requerida por SUNAT.

Utilizar la funcionalidad proporcionada por Greenter Report siempre que sea posible.

No implementar un generador QR adicional si Greenter ya proporciona la funcionalidad necesaria.

---

# 25. Rendimiento

El servidor objetivo puede tener:

```text
1 GB RAM
PHP-FPM
MySQL/MariaDB
HestiaCP
```

Por lo tanto:

* No usar Laravel.
* No usar Docker.
* No usar Node.
* No usar Redis.
* No levantar workers permanentes.
* No crear servicios adicionales.
* Liberar objetos grandes después de generar el PDF.
* Evitar cargar archivos innecesarios en memoria.
* Guardar archivos directamente en disco.
* Utilizar PDO.
* Utilizar índices adecuados.

El proceso de facturación debe ejecutarse bajo demanda.

---

# 26. Composer

El proyecto ya utiliza Composer.

No ejecutar:

```bash
composer update
```

sobre todo el proyecto sin analizar previamente el `composer.lock`.

Preferir:

```bash
composer require greenter/lite
composer require greenter/report
```

y conservar:

```text
composer.lock
```

No actualizar dependencias no relacionadas con facturación.

---

# 27. Compatibilidad

Antes de instalar comprobar:

```bash
php -v
php -m
composer --version
```

Verificar especialmente:

```text
curl
openssl
soap
zlib
dom
xml
mbstring
```

Si alguna dependencia adicional es requerida, documentarla antes de modificar el servidor.

---

# 28. Pruebas

Implementar pruebas mínimas:

### Empresa

* Crear empresa.
* Editar empresa.
* Validar RUC.
* Validar certificado.

### Factura

* Generar factura.
* Generar XML.
* Firmar XML.
* Generar PDF.
* Enviar a SUNAT.
* Procesar CDR.

### Boleta

Repetir flujo.

### Multiempresa

Verificar:

```text
Empresa A → certificado A
Empresa B → certificado B
```

Nunca debe existir contaminación de credenciales.

### Concurrencia

Simular dos emisiones simultáneas y verificar que los correlativos sean:

```text
00000001
00000002
```

y nunca:

```text
00000001
00000001
```

---

# 29. Datos de prueba

No utilizar credenciales reales durante desarrollo.

Utilizar el entorno beta de SUNAT y credenciales de prueba.

Separar completamente:

```text
desarrollo
beta
producción
```

---

# 30. Reglas importantes para el agente

Antes de escribir código:

1. Inspeccionar la estructura actual del proyecto.
2. Identificar cómo se manejan empresas/negocios.
3. Identificar cómo se manejan usuarios y permisos.
4. Identificar el sistema actual de almacenamiento.
5. Identificar cómo funciona Composer actualmente.
6. Revisar versión PHP.
7. Revisar extensiones instaladas.
8. Revisar rutas Flight existentes.
9. Revisar sistema de autenticación.
10. Reutilizar componentes existentes cuando sea posible.

No crear una arquitectura paralela innecesaria.

No modificar archivos existentes sin justificarlo.

No reemplazar el sistema actual de autenticación.

No crear tablas duplicadas si ya existe información equivalente.

---

# 31. Resultado esperado

Al finalizar debe ser posible:

```text
Administrador
     ↓
Selecciona empresa
     ↓
Ingresa datos del comprobante
     ↓
Emitir
     ↓
Greenter
     ↓
XML firmado
     ↓
SUNAT
     ↓
CDR
     ↓
PDF
     ↓
Guardar todo
```

Y posteriormente:

```text
Cliente
   ↓
Factura
   ├── Ver PDF
   ├── Descargar PDF
   ├── Descargar XML
   └── Descargar CDR
```

---

# 32. Criterio de finalización

El módulo se considera terminado cuando:

* [ ] Se pueden registrar múltiples empresas.
* [ ] Cada empresa tiene sus propias credenciales.
* [ ] Cada empresa tiene su propio certificado.
* [ ] Se puede emitir factura.
* [ ] Se puede emitir boleta.
* [ ] Se genera XML.
* [ ] XML firmado correctamente.
* [ ] Se envía a SUNAT.
* [ ] Se procesa CDR.
* [ ] Se genera PDF.
* [ ] PDF contiene QR.
* [ ] XML/PDF/CDR se almacenan.
* [ ] Se puede descargar cada archivo.
* [ ] Los correlativos son seguros ante concurrencia.
* [ ] Existe separación Beta/Producción.
* [ ] Los errores no exponen credenciales.
* [ ] El módulo funciona con múltiples RUC.
* [ ] No se introducen frameworks pesados.
* [ ] No se requiere Node.js.
* [ ] No se requiere Docker.
* [ ] No se requiere Redis.
* [ ] El código sigue la arquitectura existente.

# Regla final

**No implementar una solución genérica de facturación. Implementar específicamente facturación electrónica peruana mediante Greenter, integrada al proyecto existente y optimizada para bajo consumo de recursos.**

Primero analizar el proyecto existente y después implementar incrementalmente.

No asumir nombres de tablas, rutas, modelos o servicios existentes: inspeccionarlos antes de crear nuevos.
