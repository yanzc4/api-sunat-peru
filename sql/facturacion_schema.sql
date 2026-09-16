-- =====================================================
-- Schema: Facturación Electrónica SUNAT - Perú
-- Versión: 1.0.0
-- Fecha: 2026-09-10
-- Motor: InnoDB | Charset: utf8mb4
-- =====================================================
--
-- Ejecutar este archivo en MySQL/MariaDB:
--   mysql -u usuario -p nombre_basedatos < facturacion_schema.sql
--
-- Notas:
--   - Si ya existe una tabla de empresas, no duplicar.
--     Adaptar empresa_id para apuntar a ella.
--   - Las contraseñas (sol_password, certificado_password)
--     se almacenan CIFRADAS. Nunca guardar texto plano.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. empresas_facturacion
-- =====================================================
-- Cada empresa/RUC tiene sus propias credenciales SOL,
-- certificado digital y configuración de entorno.
-- empresa_id apunta a la tabla de empresas existente.

DROP TABLE IF EXISTS comprobante_detalles;
DROP TABLE IF EXISTS comprobantes;
DROP TABLE IF EXISTS comprobante_series;
DROP TABLE IF EXISTS empresas_facturacion;

CREATE TABLE empresas_facturacion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Datos tributarios
    ruc VARCHAR(11) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    nombre_comercial VARCHAR(255) NULL,

    -- Ubicación
    direccion VARCHAR(500) NULL,
    ubigeo VARCHAR(6) NULL,
    departamento VARCHAR(100) NULL,
    provincia VARCHAR(100) NULL,
    distrito VARCHAR(100) NULL,

    -- Credenciales SUNAT SOL (CIFRADAS)
    sol_usuario VARCHAR(100) NOT NULL,
    sol_password TEXT NOT NULL,

    -- Certificado digital (ruta + contraseña cifrada)
    certificado_path VARCHAR(500) NOT NULL,
    certificado_password TEXT NOT NULL,

    -- Entorno: beta (pruebas) o produccion
    entorno ENUM('beta','produccion') NOT NULL DEFAULT 'beta',

    -- Estado de la configuración
    activo TINYINT(1) NOT NULL DEFAULT 1,

    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Único por RUC
    UNIQUE KEY uk_ruc (ruc)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuración de facturación electrónica por empresa/RUC';

-- =====================================================
-- 2. comprobante_series
-- =====================================================
-- Cada empresa tiene sus propias series y correlativos.
-- El correlativo se incrementa de forma segura con
-- transacción y bloqueo FOR UPDATE.
--
-- Tipos de comprobante SUNAT:
--   01 = Factura
--   03 = Boleta
--   07 = Nota de Crédito
--   08 = Nota de Débito
--
-- Series típicas:
--   F001 = Factura
--   B001 = Boleta
--   FC01 = NC de Factura
--   BC01 = NC de Boleta
--   FD01 = ND de Factura
--   BD01 = ND de Boleta

CREATE TABLE comprobante_series (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    empresa_id BIGINT UNSIGNED NOT NULL,
    tipo_comprobante VARCHAR(2) NOT NULL COMMENT '01,03,07,08',
    serie VARCHAR(4) NOT NULL COMMENT 'F001,B001,FC01,etc',
    correlativo BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Último número emitido',

    -- Una serie es única por empresa + tipo + serie
    UNIQUE KEY uk_serie (empresa_id, tipo_comprobante, serie),

    -- Índice para búsquedas por empresa
    KEY idx_series_empresa (empresa_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Series y correlativos por empresa y tipo de comprobante';

-- =====================================================
-- 3. comprobantes
-- =====================================================
-- Registro principal de cada comprobante emitido.
-- Estados del flujo:
--   pendiente  → Se creó registro, aún no se procesa
--   generando  → En proceso de generación XML/firma
--   enviado    → Enviado a SUNAT, esperando respuesta
--   aceptado   → SUNAT aceptó el comprobante
--   rechazado  → SUNAT rechazó el comprobante
--   error      → Error de conexión/tiempo de espera

CREATE TABLE comprobantes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Empresa propietaria
    empresa_id BIGINT UNSIGNED NOT NULL,

    -- Identificación del comprobante
    tipo_comprobante VARCHAR(2) NOT NULL COMMENT '01,03,07,08',
    serie VARCHAR(4) NOT NULL,
    correlativo BIGINT UNSIGNED NOT NULL,

    -- Fecha y hora de emisión
    fecha_emision DATE NOT NULL,
    hora_emision TIME NULL,

    -- Moneda (PEN, USD)
    moneda VARCHAR(3) NOT NULL DEFAULT 'PEN',

    -- Datos del cliente/receptor
    cliente_tipo_documento VARCHAR(2) NULL COMMENT '6=RUC,1=DNI,4=CE,etc',
    cliente_numero_documento VARCHAR(20) NULL,
    cliente_nombre VARCHAR(255) NULL,
    cliente_direccion VARCHAR(500) NULL,

    -- Totales
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    igv DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,

    -- Rutas de archivos generados
    xml_path VARCHAR(500) NULL,
    pdf_path VARCHAR(500) NULL,
    cdr_path VARCHAR(500) NULL,

    -- Hash de validación (SHA-256 del XML)
    hash_cpe VARCHAR(255) NULL,

    -- Estado del comprobante
    estado ENUM(
        'pendiente',
        'generando',
        'enviado',
        'aceptado',
        'rechazado',
        'error'
    ) NOT NULL DEFAULT 'pendiente',

    -- Respuesta de SUNAT
    codigo_respuesta VARCHAR(20) NULL,
    mensaje_respuesta TEXT NULL,

    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Único por empresa + tipo + serie + correlativo
    UNIQUE KEY uk_comprobante (empresa_id, tipo_comprobante, serie, correlativo),

    -- Índices para consultas frecuentes
    INDEX idx_comprobantes_empresa (empresa_id),
    INDEX idx_comprobantes_estado (estado),
    INDEX idx_comprobantes_fecha (fecha_emision),
    INDEX idx_comprobantes_tipo_serie (tipo_comprobante, serie)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Comprobantes electrónicos emitidos';

-- =====================================================
-- 4. comprobante_detalles
-- =====================================================
-- Líneas/items de cada comprobante.
-- Soporta: Gravado (10), Exonerado (20), Inafecto (30),
--          Gratuito (21)

CREATE TABLE comprobante_detalles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Comprobante padre
    comprobante_id BIGINT UNSIGNED NOT NULL,

    -- Datos del item
    codigo_producto VARCHAR(100) NULL,
    descripcion TEXT NOT NULL,

    -- Unidad de medida (NIU=Unidad, ZZ=Servicio, etc)
    unidad VARCHAR(10) NOT NULL DEFAULT 'NIU',

    -- Cantidades y precios
    cantidad DECIMAL(15,4) NOT NULL,
    precio_unitario DECIMAL(15,6) NOT NULL,
    valor_unitario DECIMAL(15,6) NULL COMMENT 'Precio sin IGV',

    -- Subtotales del item
    subtotal DECIMAL(15,2) NOT NULL,
    igv DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,

    -- Afectación IGV SUNAT:
    --   10 = Gravado
    --   20 = Exonerado
    --   30 = Inafecto
    --   21 = Gratuito (Gravado)
    afectacion_igv VARCHAR(10) NULL,

    -- Índice para búsquedas por comprobante
    KEY idx_detalles_comprobante (comprobante_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Detalle/items de comprobantes electrónicos';

-- =====================================================
-- Foreign Keys (después de crear todas las tablas)
-- =====================================================

ALTER TABLE comprobante_series
    ADD CONSTRAINT fk_series_empresa
    FOREIGN KEY (empresa_id) REFERENCES empresas_facturacion(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE comprobantes
    ADD CONSTRAINT fk_comprobantes_empresa
    FOREIGN KEY (empresa_id) REFERENCES empresas_facturacion(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE comprobante_detalles
    ADD CONSTRAINT fk_detalles_comprobante
    FOREIGN KEY (comprobante_id) REFERENCES comprobantes(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Datos iniciales: Series por defecto (OPCIONAL)
-- =====================================================
-- Descomentar y ajustar empresa_id según corresponda.
-- El correlativo inicia en 0 (primer comprobante será 1).

-- Factura (01)
-- INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo) VALUES
-- (1, '01', 'F001', 0);

-- Boleta (03)
-- INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo) VALUES
-- (1, '03', 'B001', 0);

-- Nota de Crédito - Factura (07)
-- INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo) VALUES
-- (1, '07', 'FC01', 0);

-- Nota de Crédito - Boleta (07)
-- INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo) VALUES
-- (1, '07', 'BC01', 0);

-- Nota de Débito - Factura (08)
-- INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo) VALUES
-- (1, '08', 'FD01', 0);

-- Nota de Débito - Boleta (08)
-- INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo) VALUES
-- (1, '08', 'BD01', 0);
