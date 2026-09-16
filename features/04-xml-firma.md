# Fase 4: XML y Firma Digital

## Objetivo
Generar XML UBL válido y firmarlo con el certificado digital de la empresa usando Greenter Lite.

## Archivos a crear

| Archivo | Propósito |
|---------|-----------|
| `Services/XmlService.php` | Generación XML UBL |
| `Services/FirmaService.php` | Firma digital con certificado |

## Dependencia

```bash
composer require greenter/lite
```

Greenter Lite incluye:
- Generador de XML UBL 2.1
- Firma digital con certificados .pfx/.p12
- Validación de esquemas SUNAT

## Flujo XML

```
1. Recibir datos del comprobante (DTO)
   ↓
2. Mapear a estructura Greenter
   ↓
3. Configurar empresa (RUC, razón social, dirección)
   ↓
4. Agregar cliente
   ↓
5. Agregar items con IGV
   ↓
6. Calcular totales
   ↓
7. Generar XML UBL
   ↓
8. Firmar con certificado .pfx
   ↓
9. Generar hash SHA-256
   ↓
10. Retornar XML firmado + hash
```

## Ejemplo Greenter

```php
use Greenter\Builder\InvoiceBuilder;
use Greenter\Builder\NoteBuilder;
use Greenter\Secooter\XmlSecTool;

// Crear factura
$builder = new InvoiceBuilder();
$invoice = $builder
    ->setTipoDoc('01')
    ->setSerie('F001')
    ->setCorrelativo('00000001')
    ->setFechaEmision(new DateTime())
    ->setMoneda('PEN')
    ->setCliente($cliente)
    ->setItems($items)
    ->build();

// Generar XML
$xml = $builder->build($invoice);

// Firmar
$secTool = new XmlSecTool();
$xmlFirmado = $secTool->sign($xml, $certificatePath, $certificatePassword);

// Hash
$hash = hash('sha256', $xmlFirmado);
```

## Configuración empresa para Greenter

```php
$company = new \Greenter\Model\Company();
$company->setRuc($empresa->ruc);
$company->setRazonSocial($empresa->razonSocial);
$company->setNombreComercial($empresa->nombreComercial ?? $empresa->razonSocial);
$company->setAddress($address);
$company->setUserSol($empresa->solUsuario);
$company->setPasswordSol($empresa->solPassword); // descifrado
```

## Certificado

```php
// Ruta del certificado
$certPath = $empresa->certificadoPath;
// Ejemplo: /storage/private/certificados/20111111111/certificado.pfx

// Contraseña descifrada
$certPassword = descifrar($empresa->certificadoPassword);

// Validar certificado antes de firmar
if (!file_exists($certPath)) {
    throw new CertificadoException("Certificado no encontrado");
}
```

## XML UBL 2.1

Estructura generada:
```xml
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
    <ID>F001-00000001</ID>
    <IssueDate>2026-09-01</IssueDate>
    <InvoiceTypeCode>01</InvoiceTypeCode>
    <DocumentCurrencyCode>PEN</DocumentCurrencyCode>
    <AccountingCustomerParty>...</AccountingCustomerParty>
    <TaxTotal>...</TaxTotal>
    <LegalMonetaryTotal>...</LegalMonetaryTotal>
    <InvoiceLine>...</InvoiceLine>
</Invoice>
```

## Firma digital

Greenter usa `XMLSecurityDSig` para:
- Canonicalizar el XML
- Crear digest SHA-256
- Firmar con clave privada del certificado
- Insertar nodo Signature

## Errores comunes

| Error | Causa | Solución |
|-------|-------|----------|
| Certificado inválido | Archivo corrupto | Verificar .pfx |
| Password incorrecta | Cifrado/descifrado | Verificar encriptación |
| XML mal formado | Datos incompletos | Validar antes de generar |
| Firma falla | Extensión openssl | Instalar openssl |

## Verificación

- [x] XML UBL generado correctamente
- [x] XML firmado con certificado de prueba
- [x] Hash SHA-256 generado
- [x] Validación de esquema SUNAT pasa
- [x] Factura (01) genera XML válido
- [x] Boleta (03) genera XML válido
- [x] Nota de crédito (07) genera XML válido
- [x] Nota de débito (08) genera XML válido
