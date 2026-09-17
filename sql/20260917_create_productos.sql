-- Catálogo interno del facturador/POS.
-- Idempotente: puede ejecutarse más de una vez.
-- No administra inventario ni movimientos de stock.

CREATE TABLE IF NOT EXISTS productos_facturacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    unidad VARCHAR(10) NOT NULL DEFAULT 'NIU',
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    afectacion_igv VARCHAR(2) NOT NULL DEFAULT '10',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_producto_empresa_codigo (empresa_id, codigo),
    KEY idx_producto_empresa_descripcion (empresa_id, descripcion),
    KEY idx_producto_empresa_activo (empresa_id, activo),
    CONSTRAINT fk_producto_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas_facturacion(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
