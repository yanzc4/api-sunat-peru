# Fase 2: Multi-Empresa

## Objetivo
Gestionar múltiples empresas con sus credenciales SUNAT y certificados digitales de forma segura.

## Archivos a crear

| Archivo | Propósito |
|---------|-----------|
| `Models/EmpresaFacturacion.php` | Modelo de empresa |
| `Repositories/EmpresaFacturacionRepository.php` | Acceso a datos |
| `Services/CertificadoService.php` | Gestión de certificados .pfx |
| `Config/FacturacionConfig.php` | Configuración centralizada |

## Funcionalidades

### CRUD Empresas

```
POST   /api/facturacion/empresas        → Crear empresa
PUT    /api/facturacion/empresas/{id}    → Editar empresa
GET    /api/facturacion/empresas/{id}    → Ver empresa
GET    /api/facturacion/empresas         → Listar empresas
```

### Validaciones

- RUC válido (11 dígitos, algoritmo SUNAT)
- Razón social obligatoria
- Certificado .pfx/.p12 existe y es válido
- Contraseña del certificado correcta
- Usuario SOL obligatorio
- No duplicar RUC

### Seguridad de credenciales

```php
// Cifrado antes de guardar
$solPasswordCifrado = openssl_encrypt(
    $solPassword,
    'AES-256-CBC',
    $key,
    0,
    $iv
);

// Descifrado al usar
$solPassword = openssl_decrypt(
    $empresa->sol_password,
    'AES-256-CBC',
    $key,
    0,
    $iv
);
```

Nunca exponer en:
- HTML/JS
- URLs
- Logs
- Respuestas JSON
- Errores

### Certificados digitales

Ubicación:
```
/storage/private/certificados/
    20111111111/
        certificado.pfx
    20222222222/
        certificado.pfx
```

Validaciones:
- [ ] Archivo existe en disco
- [ ] Extensión .pfx o .p12
- [ ] Contraseña correcta
- [ ] No expirado
- [ ] Pertenece a la empresa correcta

### Entorno

```
beta       → Endpoint pruebas SUNAT
produccion → Endpoint real SUNAT
```

Cada empresa indica su entorno. Nunca mezclar credenciales.

## Modelo EmpresaFacturacion

```php
class EmpresaFacturacion {
    public int $id;
    public int $empresaId;
    public string $ruc;
    public string $razonSocial;
    public ?string $nombreComercial;
    public ?string $direccion;
    public ?string $ubigeo;
    public ?string $departamento;
    public ?string $provincia;
    public ?string $distrito;
    public string $solUsuario;
    public string $solPassword;       // cifrado
    public string $certificadoPath;
    public string $certificadoPassword; // cifrado
    public string $entorno;           // beta|produccion
    public bool $activo;
    public DateTime $createdAt;
    public DateTime $updatedAt;
}
```

## Verificación

- [x] Crear empresa con datos válidos
- [x] Editar empresa
- [x] Validar RUC inválido → error
- [x] Certificado inválido → error
- [x] Credenciales cifradas en BD
- [x] Credenciales no aparecen en JSON respuesta
- [x] Multi-RUC funciona correctamente
