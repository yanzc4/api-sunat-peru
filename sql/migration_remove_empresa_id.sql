-- =====================================================
-- Migración: Eliminar columna empresa_id
-- Fecha: 2026-09-10
-- Descripción: Elimina la columna redundante empresa_id y su UNIQUE KEY
-- Ejecutar en phpMyAdmin o MySQL CLI
-- =====================================================

-- 1. Eliminar la constraint UNIQUE si existe
ALTER TABLE empresas_facturacion DROP INDEX uk_empresa;

-- 2. Eliminar la columna empresa_id
ALTER TABLE empresas_facturacion DROP COLUMN empresa_id;
