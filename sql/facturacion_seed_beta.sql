-- =====================================================
-- Seed: Datos de prueba beta - Facturación Electrónica
-- Fecha: 2026-09-10
-- =====================================================
--
-- Empresa: América Móvil Perú S.A.C. (Claro Perú)
-- RUC: 20467534026
-- Entorno: Beta SUNAT
--
-- INSTRUCCIONES:
-- 1. Ejecutar primero: facturacion_schema.sql
-- 2. Insertar empresa vía API POST /api/facturacion/empresas
--    (las credenciales SOL se cifran automáticamente)
-- 3. Copiar el certificado .pfx a:
--    storage/private/certificados/20467534026/20467534026.pfx
-- 4. Ejecutar este archivo para insertar series
-- =====================================================

SET NAMES utf8mb4;

-- =====================================================
-- 1. Empresa de facturación
-- =====================================================
-- NOTA: sol_password y certificado_password deben estar
-- CIFRADOS con AES-256-CBC. Para insertar directamente,
-- usar el script PHP o la API.

-- Para insertar vía SQL (valores CIFRADOS generados con):
-- php -r "echo openssl_encrypt('MODDATOS','aes-256-cbc','key123...',0,'iv123...');"
--
-- Insertar vía API es más seguro. Este INSERT usa valores
-- de ejemplo que DEBEN ser reemplazados.

INSERT INTO empresas_facturacion (
    ruc,
    razon_social,
    nombre_comercial,
    direccion,
    ubigeo,
    departamento,
    provincia,
    distrito,
    sol_usuario,
    sol_password,
    certificado_path,
    certificado_password,
    entorno,
    activo
) VALUES (
    '20467534026',
    'AMÉRICA MÓVIL PERÚ S.A.C.',
    'Claro Perú',
    'Av. Cantuaria 180, San Isidro',
    '150123',
    'LIMA',
    'LIMA',
    'SAN ISIDRO',
    'MODDATOS',
    'PLACEHOLDER_PASSWORD_SOL_CIFRADO',
    'storage/private/certificados/20467534026/certificado.pfx',
    'PLACEHOLDER_PASSWORD_CERT_CIFRADO',
    'beta',
    1
);

-- =====================================================
-- 2. Series por defecto
-- =====================================================
-- Ajustar empresa_id al ID real de la empresa insertada

-- Factura electrónica (tipo 01)
INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo)
VALUES (1, '01', 'F001', 0);

-- Boleta de venta (tipo 03)
INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo)
VALUES (1, '03', 'B001', 0);

-- Nota de crédito electrónica (tipo 07)
INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo)
VALUES (1, '07', 'FC01', 0);

-- Nota de débito electrónica (tipo 08)
INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo)
VALUES (1, '08', 'FD01', 0);

-- =====================================================
-- 3. Verificación
-- =====================================================
SELECT
    ef.id,
    ef.ruc,
    ef.razon_social,
    ef.nombre_comercial,
    ef.entorno,
    ef.activo,
    cs.tipo_comprobante,
    cs.serie,
    cs.correlativo
FROM empresas_facturacion ef
JOIN comprobante_series cs ON cs.empresa_id = ef.id
WHERE ef.ruc = '20467534026';
