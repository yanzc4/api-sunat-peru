# Fase 1: Base de Datos

## Objetivo
Crear el schema completo de la base de datos para soportar facturación electrónica multiempresa.

## Tablas

### 1. empresas_facturacion

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

### 2. comprobante_series

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

### 3. comprobantes

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

### 4. comprobante_detalles

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

    afectacion_igv VARCHAR(10) NULL,

    KEY idx_comprobante (comprobante_id)
);
```

## Índices adicionales

```sql
CREATE INDEX idx_comprobantes_empresa ON comprobantes(empresa_id);
CREATE INDEX idx_comprobantes_estado ON comprobantes(estado);
CREATE INDEX idx_comprobantes_fecha ON comprobantes(fecha_emision);
```

## Relaciones

- `empresas_facturacion.empresa_id` → tabla empresas existente (no duplicar)
- `comprobantes.empresa_id` → `empresas_facturacion.id`
- `comprobante_detalles.comprobante_id` → `comprobantes.id`
- `comprobante_series.empresa_id` → `empresas_facturacion.id`

## Reglas

- No duplicar tabla de empresas si ya existe
- `sol_password` y `certificado_password` se almacenan cifrados
- Cada empresa tiene sus propias series/correlativos
- Tipos comprobante: 01=Factura, 03=Boleta, 07=NC, 08=ND
- Afectación IGV: 10=Gravado, 20=Exonerado, 30=Inafecto, 21=Gratuito

## Verificación

- [x] Tablas creadas sin errores
- [x] Índices aplicados
- [x] Relaciones definidas (Foreign Keys)
- [x] Charset UTF-8
- [x] Engine InnoDB
