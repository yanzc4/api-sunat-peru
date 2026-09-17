-- =====================================================
-- Consolidación SaaS: usuarios, empresas, tokens y logos
-- Compatible con MySQL 8 / MariaDB. No elimina datos.
-- Puede ejecutarse más de una vez.
-- =====================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conserva las credenciales administrativas existentes del proyecto.
INSERT IGNORE INTO usuarios (nombre, email, password, rol)
VALUES (
    'Administrador',
    'admin@admin.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = 'email'
          AND NON_UNIQUE = 0
    ),
    'SELECT 1',
    'ALTER TABLE usuarios ADD UNIQUE KEY uk_usuarios_email (email)'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'empresas_facturacion'
          AND COLUMN_NAME = 'usuario_id'
    ),
    'SELECT 1',
    'ALTER TABLE empresas_facturacion ADD COLUMN usuario_id BIGINT UNSIGNED NULL AFTER id'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'empresas_facturacion'
          AND COLUMN_NAME = 'logo_path'
    ),
    'SELECT 1',
    'ALTER TABLE empresas_facturacion ADD COLUMN logo_path VARCHAR(255) NULL AFTER certificado_password'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @admin_id = (
    SELECT id FROM usuarios WHERE email = 'admin@admin.com' ORDER BY id LIMIT 1
);
UPDATE empresas_facturacion
SET usuario_id = @admin_id
WHERE usuario_id IS NULL AND @admin_id IS NOT NULL;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'empresas_facturacion'
          AND COLUMN_NAME = 'usuario_id'
          AND REFERENCED_TABLE_NAME = 'usuarios'
          AND REFERENCED_COLUMN_NAME = 'id'
    ),
    'SELECT 1',
    'ALTER TABLE empresas_facturacion ADD CONSTRAINT fk_empresa_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estos índices fallarán sin modificar datos si existen duplicados que deben
-- ser resueltos manualmente antes de continuar.
SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'api_tokens'
          AND COLUMN_NAME = 'empresa_id'
          AND NON_UNIQUE = 0
    ),
    'SELECT 1',
    'ALTER TABLE api_tokens ADD UNIQUE KEY uk_api_tokens_empresa (empresa_id)'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'api_tokens'
          AND COLUMN_NAME = 'token'
          AND NON_UNIQUE = 0
    ),
    'SELECT 1',
    'ALTER TABLE api_tokens ADD UNIQUE KEY uk_api_tokens_token (token)'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'api_tokens'
          AND COLUMN_NAME = 'empresa_id'
          AND REFERENCED_TABLE_NAME = 'empresas_facturacion'
          AND REFERENCED_COLUMN_NAME = 'id'
    ),
    'SELECT 1',
    'ALTER TABLE api_tokens ADD CONSTRAINT fk_token_empresa FOREIGN KEY (empresa_id) REFERENCES empresas_facturacion(id) ON DELETE CASCADE ON UPDATE CASCADE'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

-- Verificación final (solo lectura).
SELECT
    (SELECT COUNT(*) FROM usuarios) AS usuarios,
    (SELECT COUNT(*) FROM empresas_facturacion WHERE usuario_id IS NULL) AS empresas_sin_propietario,
    (SELECT COUNT(*) FROM api_tokens) AS tokens_api,
    (SELECT COUNT(*) FROM empresas_facturacion ef
        LEFT JOIN api_tokens at ON at.empresa_id = ef.id
        WHERE at.id IS NULL) AS empresas_sin_token;
