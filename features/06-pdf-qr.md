# Fase 6: PDF y QR

## Objetivo
Generar representación impresa del comprobante con QR SUNAT usando Greenter Report.

## Archivos a crear

| Archivo | Propósito |
|---------|-----------|
| `Services/PdfService.php` | Generación PDF |

## Dependencia

```bash
composer require greenter/report
```

Greenter Report genera PDF con:
- Datos de empresa
- Detalle de comprobante
- QR con información SUNAT
- Hash de validación

## Configuración

```php
use Greenter\Report\Renderer\PdfReportRenderer;
use Greenter\Report\Builder\ReportBuilder;

$renderer = new PdfReportRenderer();
$renderer->setLogoPath($empresa->logoPath);
$renderer->setTemplatePath(__DIR__ . '/templates/factura.html');
```

## Contenido mínimo del PDF

- Logo de empresa
- Razón social
- RUC
- Dirección
- Tipo de comprobante
- Serie y correlativo
- Fecha de emisión
- Cliente (nombre, tipo doc, número)
- Detalle (items, cantidades, precios)
- Subtotal
- IGV
- Total
- Moneda
- QR SUNAT
- Hash de validación

## QR SUNAT

Formato del QR:
```
https://allen face/gob.pe/veraCpe?fe={fecha}&fe={tipo}&fe={serie}-{correlativo}&fe={ruc}&fe={hash}
```

Greenter Report genera automáticamente el QR. No implementar generador adicional.

## Almacenamiento

```php
$pdfPath = "/storage/private/facturacion/{$ruc}/{$ano}/{$mes}/pdf/{$fileName}.pdf";
```

## Plantilla PDF

Usar HTML/CSS para la plantilla. Mantener separada para facilitar cambios futuros.

```html
<!-- templates/factura.html -->
<div class="header">
    <img src="{{ logo }}" alt="Logo">
    <div class="empresa">
        <h1>{{ razon_social }}</h1>
        <p>RUC: {{ ruc }}</p>
        <p>{{ direccion }}</p>
    </div>
</div>

<div class="comprobante">
    <h2>FACTURA</h2>
    <p>{{ serie }}-{{ correlativo }}</p>
    <p>Fecha: {{ fecha }}</p>
</div>

<div class="cliente">
    <h3>Cliente</h3>
    <p>{{ cliente_nombre }}</p>
    <p>{{ cliente_documento }}</p>
</div>

<table class="items">
    <thead>
        <tr>
            <th>Cantidad</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        {% for item in items %}
        <tr>
            <td>{{ item.cantidad }}</td>
            <td>{{ item.descripcion }}</td>
            <td>{{ item.precio }}</td>
            <td>{{ item.total }}</td>
        </tr>
        {% endfor %}
    </tbody>
</table>

<div class="totales">
    <p>Subtotal: {{ subtotal }}</p>
    <p>IGV (18%): {{ igv }}</p>
    <p><strong>Total: {{ total }}</strong></p>
</div>

<div class="qr">
    {{ qr_code }}
</div>

<div class="hash">
    Hash: {{ hash }}
</div>
```

## Sin navegador

NO usar:
- Chrome headless
- Puppeteer
- Playwright
- Node.js

Greenter Report usa motor propio de PDF. Si requiere binario externo, documentar:
- Paquete necesario
- Instalación
- Ubicación
- Consumo de RAM

## Verificación

- [x] PDF generado correctamente
- [x] Logo incluido
- [x] Datos empresa correctos
- [x] Datos cliente correctos
- [x] Items listados
- [x] Totales correctos
- [x] QR SUNAT presente
- [x] Hash incluido
- [x] Plantilla profesional y limpia
- [x] PDF almacenable en disco
