# Fase 9: Configuración Completa

## Objetivo
Configurar la API para funcionar de forma completa con variables de entorno, rutas faltantes y fixes necesarios.

## Pasos

### 1. Instalar vlucas/phpdotenv
- `composer require vlucas/phpdotenv`
- Permite cargar variables desde `.env`

### 2. Crear `.env.example`
Template con todas las variables:
```env
# Database
FAC_DB_DSN=mysql:host=localhost;dbname=facturacion;charset=utf8mb4
FAC_DB_USER=root
FAC_DB_PASS=

# Encryption (32 y 16 bytes aleatorios)
FAC_ENCRYPTION_KEY=cambiar-32-bytes-aleatorios-aqui!!!
FAC_ENCRYPTION_IV=cambiar-16-bytes!

# SUNAT
FAC_SUNAT_ENV=beta
```

### 3. Crear `.env`
Valores reales (no commitear).

### 4. Cargar `.env` en entry point
`public/index.php` carga Dotenv antes de todo.

### 5. Crear Config/Database.php
Clase estática `Database::getConnection()` que retorna PDO.

### 6. Actualizar FacturacionConfig.php
Leer variables de `.env` en vez de `getenv()`.

### 7. Crear public/.htaccess
Reescritura Apache para Flight PHP.

### 8. Agregar ruta procesar
`POST /api/facturacion/comprobantes/{id}/procesar`

### 9. Métodos en FacturacionController
- `procesar($id)` - llama a FacturacionService::procesar()
- `consultarEstado($id)` - consulta estado en SUNAT

### 10. Métodos en EmpresaController
- `subirCertificado($id)` - sube .pfx vía multipart
- `eliminar($id)` - elimina empresa

### 11. Fix ComprobanteDTO
Declarar propiedades: `subtotalCalculado`, `igvCalculado`, `totalCalculado`

### 12. Rutas faltantes
- `POST /empresas/{id}/certificado`
- `DELETE /empresas/{id}`
- `GET /comprobantes/{id}/estado`
- `GET /comprobantes/{id}/cdr`

### 13. Crear .gitignore
Excluir `.env`, `vendor/`, `storage/private/`

## Verificación
- [ ] `.env` carga correctamente
- [ ] DB se conecta
- [ ] Todas las rutas responden
- [ ] Flight funciona en Apache
- [ ] Certificado se puede subir
- [ ] Procesar genera XML → firma → SUNAT → PDF
