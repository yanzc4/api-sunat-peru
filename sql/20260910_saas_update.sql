-- =====================================================
-- Migración: Transformación a SaaS (Usuarios y Tokens)
-- =====================================================

-- 1. Crear tabla de usuarios (Administradores/Clientes)
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar un usuario administrador por defecto
-- Contraseña por defecto: admin123 (Se recomienda cambiar)
INSERT IGNORE INTO `usuarios` (`nombre`, `email`, `password`, `rol`) 
VALUES ('Administrador', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 2. Modificar empresas_facturacion para relacionarla al usuario
ALTER TABLE `empresas_facturacion` 
ADD COLUMN `usuario_id` BIGINT UNSIGNED NULL AFTER `id`;

-- Opcional: Asignar las empresas existentes al usuario 1 (admin)
UPDATE `empresas_facturacion` SET `usuario_id` = 1 WHERE `usuario_id` IS NULL;

-- 3. Crear foreign key en empresas_facturacion
ALTER TABLE `empresas_facturacion`
ADD CONSTRAINT `fk_empresa_usuario`
FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. Crear tabla de tokens de API
CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `empresa_id` BIGINT UNSIGNED NOT NULL UNIQUE COMMENT '1 token por empresa',
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_token_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_facturacion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
