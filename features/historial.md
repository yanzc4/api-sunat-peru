# Historial de Cambios

Registro de todas las modificaciones realizadas en el módulo de facturación electrónica.

---

## [Unreleased]

### Fase 0 - Setup del Proyecto
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Habilitar extensión `soap` en php.ini
  - Instalar `greenter/lite` v4.3.1 y `greenter/report` v4.3.1
  - Crear estructura de directorios `/app/Facturacion/`
  - Crear `composer.json` con autoload PSR-4
  - Crear `FacturacionConfig.php` (configuración centralizada)
  - Crear `ResponseHelper.php` (respuestas API estandarizadas)
  - Crear `facturacion_schema.sql` (DDL completo 4 tablas)
  - Crear modelos: `EmpresaFacturacion`, `Comprobante`, `DetalleComprobante`
  - Crear excepciones: `FacturacionException`, `SunatException`, `CertificadoException`, `PdfException`
  - Crear directorios `storage/private/certificados` y `storage/private/facturacion`

---

### Fase 1 - Base de Datos
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Actualizar `sql/facturacion_schema.sql` con schema completo
  - 4 tablas: `empresas_facturacion`, `comprobante_series`, `comprobantes`, `comprobante_detalles`
  - Foreign keys con CASCADE
  - Índices para consultas frecuentes
  - Comentarios en columnas y tablas
  - `SET FOREIGN_KEY_CHECKS` para creación segura
  - Series iniciales comentadas como plantilla

---

### Fase 2 - Multi-Empresa
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Crear `Repositories/EmpresaFacturacionRepository.php` (CRUD completo)
  - Crear `Services/EncryptionService.php` (AES-256-CBC)
  - Crear `Services/CertificadoService.php` (validación .pfx/.p12)
  - Actualizar `Models/EmpresaFacturacion.php` (toArray excluye credenciales)
  - Validación de RUC (11 dígitos + duplicados)
  - Cifrado/descifrado de sol_password y certificado_password
  - Validación de certificados: existencia, extensión, propiedad, contraseña, expiración

---

### Fase 3 - Motor de Facturación
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Crear `DTO/ComprobanteDTO.php` con validaciones completas
  - Crear `DTO/ItemDTO.php` (clase interna para items)
  - Crear `Repositories/ComprobanteRepository.php` (CRUD + correlativo seguro FOR UPDATE)
  - Crear `Services/FacturacionService.php` (flujo emitir/procesar)
  - Validación de tipos comprobante (01, 03, 07, 08)
  - Validación de documentos cliente (RUC 11d, DNI 8d, CE, Pasaporte)
  - Cálculo automático de IGV (18%)
  - Numeración segura con transacción y bloqueo

---

### Fase 4 - XML y Firma
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Crear `Services/XmlService.php` (generación XML UBL 2.1)
  - Crear `Services/FirmaService.php` (firma digital con PKCS12)
  - Integración con Greenter Lite: InvoiceBuilder, NoteBuilder
  - Mapeo de modelos internos a modelos Greenter
  - Generador de letras para montos (PEN/USD)
  - Validación de certificado: existencia, extensión, expiración
  - Almacenamiento de XML en disco por RUC/año/mes

---

### Fase 5 - Integración SUNAT
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Crear `Services/SunatService.php` (envío, consulta estado, consulta CDR)
  - Integración con Greenter WS: BillSender, ConsultCdrService, SoapClient
  - Autenticación WSSE con credenciales SOL
  - Endpoints beta/producción configurables
  - Manejo de errores: SoapFault, timeouts, respuestas desconocidas
  - Almacenamiento de CDR en disco por RUC/año/mes
  - Estados: pendiente → generando → enviado → aceptado/rechazado/error
  - NO se reintentan rechazos automáticamente

---

### Fase 6 - PDF y QR
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Crear `Services/PdfService.php` (generación PDF con dompdf)
  - Instalar dependencia `dompdf/dompdf` v3.1.6
  - Integración con Greenter Report: HtmlReport, QrRender
  - Generación de HTML desde plantillas Twig
  - Conversión HTML → PDF con dompdf (A4, portrait)
  - Almacenamiento de PDF en disco por RUC/año/mes
  - Soporte para Factura, Boleta, NC, ND

---

### Fase 7 - API REST
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Crear `Controllers/EmpresaController.php` (CRUD empresas)
  - Crear `Controllers/FacturacionController.php` (emisión, consulta, descarga)
  - Crear `routes/facturacion.php` (rutas Flight PHP)
  - Endpoints: POST/GET/PUT empresas, POST/GET comprobantes
  - Descarga de archivos: PDF, XML, CDR
  - Respuestas JSON estandarizadas (success/error)
  - Manejo de errores sin exponer internals
  - Filtros de consulta: empresa_id, estado, tipo_comprobante

---

### Fase 8 - Testing y Seguridad
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Crear tests unitarios: StructureTest, EncryptionServiceTest, ComprobanteDTOTest, ResponseHelperTest
  - Crear test de concurrencia (ConcurrenciaTest)
  - Crear verificación de seguridad (SecurityCheck)
  - Crear script ejecutor tests/run-tests.bat
  - Validación: Estructura directorios, cifrado, DTO validaciones, respuestas API
  - Security: credenciales no exponen en toArray, paths privados, sin stack traces

---

### Fase 9 - Preparación para Pruebas Beta
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Instalar dependencia `flightphp/core` v3.19.3
  - Crear `public/index.php` entry point (autoload + Flight + CORS)
  - Completar `FacturacionService::procesar()` con flujo real:
    - XmlService::generarXml() → XML UBL 2.1
    - XmlService::guardarXml() → guardar en disco
    - FirmaService::firmar() → firma digital PKCS12
    - FirmaService::generarHash() → SHA-256
    - SunatService::enviar() → envío SOAP a SUNAT beta
    - PdfService::generarPdf() → PDF con dompdf
  - Crear `sql/facturacion_seed_beta.sql` (empresa RUC 20467534026 + series)

---

### Fase 10 - Configuración Completa
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Instalar `vlucas/phpdotenv` v5.7.0
  - Crear `.env.example` (template variables)
  - Crear `.env` (valores reales)
  - Actualizar `public/index.php` para cargar `.env`
  - Crear `Config/Database.php` (conexión PDO centralizada)
  - Actualizar `FacturacionConfig.php` (leer de `$_ENV`)
  - Crear `public/.htaccess` (reescritura Apache para Flight)
  - Agregar rutas: `procesar`, `consultarEstado`, `subirCertificado`, `DELETE`
  - Métodos en `FacturacionController`: `procesar()`, `consultarEstado()`
  - Métodos en `EmpresaController`: `subirCertificado()`, `eliminar()`
  - Declarar propiedades faltantes en `ComprobanteDTO`
  - Crear `.gitignore` (excluir `.env`, `vendor/`, `storage/private/`)
  - Fix: property references `pdfPath/xmlPath/cdrPath` en controllers

### Fix - Eliminar empresa_id redundante
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Eliminar columna `empresa_id` y UNIQUE KEY `uk_empresa` de tabla `empresas_facturacion` en `sql/facturacion_schema.sql`
  - Eliminar `empresa_id` del INSERT en `sql/facturacion_seed_beta.sql`
  - Eliminar propiedad `$empresaId` de modelo `EmpresaFacturacion`
  - Eliminar métodos `findByEmpresaId()` y `existeEmpresaId()` de `EmpresaFacturacionRepository`
  - Eliminar campo `empresa_id` de INSERT y UPDATE en repository
  - `FacturacionService::validarEmpresa()` ahora busca solo por PK `id`
  - `EmpresaController::crear()` ya no requiere campo `empresa_id`
  - El campo `empresa_id` en el body de emitir comprobante ahora se busca directamente por PK `id`
  - Actualizar `documentacion.md` con cambios

### Fix - Transacciones anidadas y separar flujo emitir/procesar
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Eliminar `beginTransaction()` de `ComprobanteRepository::obtenerCorrelativo()` — ya corre dentro de la transacción del caller
  - Cambiar `obtenerEmpresa()` de `findByEmpresaId()` a `findById()` en `FacturacionService`
  - Simplificar `FacturacionController::emitir()` — solo crea comprobante en DB (sin XML/firma/PDF/SUNAT)
  - El flujo completo ahora es: `POST /comprobantes` (crear) → `POST /comprobantes/{id}/procesar` (XML→firma→SUNAT→PDF)
  - Limpiar imports y propiedades no usadas en `FacturacionController`
---

### Fix - TypeErrors en str_pad y Endpoint Series
- **Fecha**: 2026-09-10
- **Estado**: Completado
- **Cambios**:
  - Corregir `TypeError` al pasar enteros a `str_pad` en `Comprobante.php`, `FacturacionController.php`, `PdfService.php`, `XmlService.php` y `SunatService.php` (guardarCdr) bajo `strict_types=1`. (Casteo explícito a `string`).
  - Corregir función `convertirNumero()` en `PdfService.php` y `XmlService.php` que fallaba con `Undefined array key` para montos mayores a 999. Ahora soporta miles y millones.
  - Corregir métodos deprecados/renombrados de Greenter en `PdfService.php` y `XmlService.php` (`setLegend` a `setLegends` y `setValor` a `setValue`).
  - Asignar `$invoice->setValorVenta()` y `$note->setValorVenta()` en `XmlService.php` y `PdfService.php` para evitar `number_format` null error en plantilla Twig de UBL 2.1.
  - Asignar `$invoice->setTipoOperacion('0101')` en `XmlService.php` y `PdfService.php` para corregir el error SUNAT 3205 de validación UBL 2.1.
  - Corregir formato de nombre del XML y ZIP al enviarse a SUNAT (`RUC-TIPO-SERIE-NUMERO.zip/xml`) que producía error de "nombre archivo no es cpe valido" en `SunatService`.
  - Refactorizar `PdfService` para generar dos versiones del PDF concurrentemente (A4 y ticket térmico de 80mm).
  - Rediseñar completamente el formato Ticket (HTML/CSS) para coincidir con diseño y normativa, añadir logo dinámico (SVG/PNG/JPG), arreglar renderizado del QR en base64 y ajustar padding a 10px.
  - Modificar leyenda de montos a "CON 00/100 SOLES" en lugar de "EXACTOS SOLES" para comprobantes A4 generados por Greenter.
  - Corregir inyección de logo en plantillas A4 de Greenter, pasando la data binaria en crudo para que el filtro `image_b64` de Twig lo procese correctamente.
  - Corregir error de redondeo de céntimos en cálculos de IGV inverso (ej: 100 -> 100.01) en `ComprobanteDTO`.
  - Solucionar persistencia de rutas sucias (`api-sunat-peru/storage/...`) truncando directamente todo texto previo a `storage/` en repositorio y respuestas de API.
  - Añadir soporte de `logo_path` a la tabla de `empresas_facturacion`, al repositorio de Base de datos y al endpoint `PUT /api/facturacion/empresas/@id`.
  - Transformación a SaaS (Módulo 1): Crear tablas de `usuarios` y `api_tokens` (Migración `20260910_saas_update.sql`), vincular `empresas_facturacion` con `usuario_id` e inyectar `rol` (admin/cliente).
  - Transformación a SaaS (Módulo 2): Crear sistema de vistas web (`/login`, `/logout`, `/dashboard`) usando sesiones PHP y restricción de botones según Rol de usuario.
  - Transformación a SaaS (Módulo 3): Añadir formulario visual en Landing Page que envía notificaciones silenciosas a WhatsApp vía CallMeBot.
  - Transformación a SaaS (Módulo 4): Filtrar endpoints de la API (`/api/facturacion/*`) exigiendo un Token (`?token=` por GET o `{"token":""}` por POST), inyectando dinámicamente el `empresa_id` interceptado para enmascarar su origen.
  - Crear página web dinámica de documentación de la API accesible en la ruta raíz `GET /doc` (PHP/HTML interactivo con listado de endpoints, diccionario de errores y sección de Ejemplos de código usando la URL base autocalculada).
  - Crear nuevo endpoint `POST /api/facturacion/empresas/@id/logo` (multipart/form-data) para subir el archivo de logo.
  - Asegurar formato con dos decimales en todas las cantidades (`[1.00]`) y precios en el PDF ticket e incrementar sutilmente su fuente general (+1px).
  - Crear archivo de migración manual en `sql/20260910_add_logo_path.sql`.
  - Añadir parámetro query opcional `?formato=ticket` a la descarga de PDF en `FacturacionController`.
  - Cambiar `catch (\Exception $e)` a `catch (\Throwable $e)` en `FacturacionController.php`, `EmpresaController.php` y `FacturacionService.php` para atrapar TypeErrors.
  - Modificar `FacturacionService::procesar()` para permitir reintentar en caso de estado `generando` o `error`.
  - Crear métodos `crearSerie()` y `existeSerie()` en `ComprobanteRepository.php`.
  - Crear endpoint `POST /api/facturacion/empresas/@id/series` en `routes/facturacion.php` y `EmpresaController.php` para configurar las series iniciales.
  - Actualizar `documentacion.md` con el nuevo endpoint de series.

---

## Formato de registro

Cada cambio debe incluir:
- **Fecha**: YYYY-MM-DD
- **Fase**: Número de fase
- **Archivo**: Archivo modificado/creado
- **Descripción**: Qué se hizo
- **Autor**: Quién lo hizo
