# Fase 0: Setup del Proyecto

## Objetivo
Establecer la base del módulo de facturación electrónica dentro del proyecto existente.

## Dependencias

```bash
composer require greenter/lite
composer require greenter/report
```

No instalar Laravel, Node, Redis, ni frameworks adicionales.

## Estructura de directorios

```
/app/
    Facturacion/
        Config/
            FacturacionConfig.php
        Services/
            FacturacionService.php
            SunatService.php
            XmlService.php
            PdfService.php
            CertificadoService.php
        Repositories/
            EmpresaFacturacionRepository.php
            ComprobanteRepository.php
        Models/
            EmpresaFacturacion.php
            Comprobante.php
            DetalleComprobante.php
        DTO/
            ComprobanteDTO.php
        Exceptions/
            FacturacionException.php
            SunatException.php
            CertificadoException.php
            PdfException.php
        Helpers/
            ResponseHelper.php

/storage/
    private/
        certificados/
            {ruc}/
                certificado.pfx
        facturacion/
            {ruc}/
                {ano}/{mes}/
                    xml/
                    pdf/
                    cdr/

/sql/
    facturacion_schema.sql
```

## Archivos a crear

| Archivo | Propósito |
|---------|-----------|
| `composer.json` | Dependencias del módulo |
| `FacturacionConfig.php` | Configuración centralizada |
| `facturacion_schema.sql` | DDL de todas las tablas |
| `ResponseHelper.php` | Helper para respuestas API |

## Verificación

- [x] `composer install` ejecuta sin errores
- [x] Greenter Lite y Report instalados
- [x] PHP 8.x con extensiones: curl, openssl, soap, zlib, dom, xml, mbstring
- [x] Estructura de directorios creada
- [x] Archivos en `/storage/private/` fuera de alcance web

## Notas

- No ejecutar `composer update` global sin revisar `composer.lock`
- Preferir `composer require` por paquete
- Verificar versiones antes de instalar: `php -v`, `php -m`, `composer --version`
