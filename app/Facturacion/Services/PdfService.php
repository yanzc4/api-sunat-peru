<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\Exceptions\PdfException;
use App\Facturacion\Models\Comprobante;
use App\Facturacion\Models\DetalleComprobante;
use App\Facturacion\Models\EmpresaFacturacion;
use Dompdf\Dompdf;
use Dompdf\Options;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Report\HtmlReport;
use Greenter\Report\Render\QrRender;
use Greenter\Report\XmlUtils;

class PdfService
{
    private FacturacionConfig $config;
    private XmlService $xmlService;

    public function __construct()
    {
        $this->config = FacturacionConfig::getInstance();
        $this->xmlService = new XmlService();
    }

    public function generarPdf(
        Comprobante $comprobante,
        EmpresaFacturacion $empresa,
        array $detalles,
        ?string $hash = null,
        string $formato = 'a4'
    ): string {
        try {
            if ($formato === 'ticket') {
                $html = $this->generateTicketHtml($comprobante, $empresa, $detalles, $hash);
            } else {
                $document = $this->buildDocument($comprobante, $empresa, $detalles);
                $html = $this->generateHtml($document, $empresa, $hash);
            }

            $pdf = $this->generatePdfFromHtml($html, $formato);

            $pdfPath = $this->guardarPdf($comprobante, $empresa, $pdf, $formato);

            return $pdfPath;

        } catch (\Exception $e) {
            throw new PdfException(
                "Error al generar PDF: " . $e->getMessage()
            );
        }
    }

    private function buildDocument(
        Comprobante $comprobante,
        EmpresaFacturacion $empresa,
        array $detalles
    ) {
        $company = $this->buildCompany($empresa);
        $client = $this->buildClient($comprobante);
        $items = $this->buildItems($detalles);

        if (in_array($comprobante->tipoComprobante, ['01', '03'])) {
            return $this->buildInvoice($comprobante, $company, $client, $items);
        }

        return $this->buildNote($comprobante, $company, $client, $items);
    }

    private function buildCompany(EmpresaFacturacion $empresa): Company
    {
        $company = new Company();
        $company->setRuc($empresa->ruc);
        $company->setRazonSocial($empresa->razonSocial);
        $company->setNombreComercial(
            $empresa->nombreComercial ?? $empresa->razonSocial
        );
        $company->setAddress((new Address())
            ->setDireccion($empresa->direccion ?? '')
        );

        return $company;
    }

    private function buildClient(Comprobante $comprobante): Client
    {
        $client = new Client();
        $client->setTipoDoc($comprobante->clienteTipoDocumento);
        $client->setNumDoc($comprobante->clienteNumeroDocumento);
        $client->setRznSocial($comprobante->clienteNombre);

        if ($comprobante->clienteDireccion) {
            $client->setAddress((new Address())
                ->setDireccion($comprobante->clienteDireccion)
            );
        }

        return $client;
    }

    private function buildItems(array $detalles): array
    {
        $items = [];

        foreach ($detalles as $detalle) {
            $detail = new SaleDetail();
            $detail->setUnidad($detalle->unidad);
            $detail->setCantidad($detalle->cantidad);
            $detail->setCodProducto($detalle->codigoProducto ?? '');
            $detail->setDescripcion($detalle->descripcion);
            $detail->setMtoValorUnitario($detalle->valorUnitario ?? $detalle->precioUnitario);
            $detail->setMtoPrecioUnitario($detalle->precioUnitario);
            $detail->setMtoValorVenta($detalle->total);

            $items[] = $detail;
        }

        return $items;
    }

    private function buildInvoice(
        Comprobante $comprobante,
        Company $company,
        Client $client,
        array $items
    ): Invoice {
        $invoice = new Invoice();
        $invoice->setUblVersion('2.1');
        $invoice->setTipoOperacion('0101'); // Venta Interna
        $invoice->setTipoDoc($comprobante->tipoComprobante);
        $invoice->setSerie($comprobante->serie);
        $invoice->setCorrelativo(
            str_pad((string) $comprobante->correlativo, 8, '0', STR_PAD_LEFT)
        );
        $invoice->setFechaEmision(
            new \DateTime($comprobante->fechaEmision)
        );
        $invoice->setTipoMoneda($comprobante->moneda);
        $invoice->setCompany($company);
        $invoice->setClient($client);
        $invoice->setDetails($items);
        $invoice->setMtoImpVenta($comprobante->total);
        $invoice->setValorVenta($comprobante->subtotal);
        $invoice->setMtoOperGravadas($comprobante->subtotal);
        $invoice->setMtoIGV($comprobante->igv);
        $invoice->setTotalImpuestos($comprobante->igv);
        $invoice->setLegends([
            (new Legend())
                ->setCode('1000')
                ->setValue('SON ' . $this->numeroALetras(
                    $comprobante->total,
                    $comprobante->moneda
                )),
        ]);

        return $invoice;
    }

    private function buildNote(
        Comprobante $comprobante,
        Company $company,
        Client $client,
        array $items
    ): Note {
        $note = new Note();
        $note->setUblVersion('2.1');
        $note->setTipoDoc($comprobante->tipoComprobante);
        $note->setSerie($comprobante->serie);
        $note->setCorrelativo(
            str_pad((string) $comprobante->correlativo, 8, '0', STR_PAD_LEFT)
        );
        $note->setFechaEmision(
            new \DateTime($comprobante->fechaEmision)
        );
        $note->setTipoMoneda($comprobante->moneda);
        $note->setCompany($company);
        $note->setClient($client);
        $note->setDetails($items);
        $note->setMtoImpVenta($comprobante->total);
        $note->setValorVenta($comprobante->subtotal);
        $note->setMtoOperGravadas($comprobante->subtotal);
        $note->setMtoIGV($comprobante->igv);
        $note->setTotalImpuestos($comprobante->igv);
        $note->setCodMotivo('01');
        $note->setDesMotivo('Anulación de operación');
        $note->setTipDocAfectado('01');
        $note->setNumDocfectado('0001-00000001');
        $note->setLegends([
            (new Legend())
                ->setCode('1000')
                ->setValue('SON ' . $this->numeroALetras(
                    $comprobante->total,
                    $comprobante->moneda
                )),
        ]);

        return $note;
    }

    private function generateHtml($document, EmpresaFacturacion $empresa, ?string $hash): string
    {
        $templatesDir = dirname(__DIR__, 2) . '/Templates';

        if (!is_dir($templatesDir)) {
            mkdir($templatesDir, 0750, true);
        }

        $htmlReport = new HtmlReport($templatesDir);

        $logoData = '';
        if (!empty($empresa->logoPath)) {
            $absPath = dirname(__DIR__, 4) . '/' . ltrim($empresa->logoPath, '/\\');
            if (file_exists($absPath)) {
                $logoData = file_get_contents($absPath);
            }
        }

        $parameters = [
            'system' => [
                'logo' => $logoData,
                'hash' => $hash ?? '',
            ],
            'user' => [
                'header' => '',
                'footer' => 'Representación Impresa - Facturación Electrónica SUNAT',
                'numIGV' => '18',
            ],
        ];

        return $htmlReport->render($document, $parameters);
    }

    private function generatePdfFromHtml(string $html, string $formato): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($options);

        // Adjust HTML for ticket size
        if ($formato === 'ticket') {
            $ticketStyles = '<style>body { font-size: 10px; margin: 0; padding: 0; } .container { width: 100%; }</style>';
            $html = str_replace('</head>', $ticketStyles . '</head>', $html);
        }

        $dompdf->loadHtml($html);

        if ($formato === 'ticket') {
            $dompdf->setPaper([0, 0, 226.77, 1000], 'portrait');
        } else {
            $dompdf->setPaper('A4', 'portrait');
        }

        $dompdf->render();

        return $dompdf->output();
    }

    private function generateTicketHtml(
        Comprobante $comprobante,
        EmpresaFacturacion $empresa,
        array $detalles,
        ?string $hash
    ): string {
        $nombreComprobante = $comprobante->tipoComprobante === '01' ? 'FACTURA ELECTRÓNICA' :
            ($comprobante->tipoComprobante === '03' ? 'BOLETA ELECTRÓNICA' : 'NOTA ELECTRÓNICA');
        $moneda = $comprobante->moneda === 'PEN' ? 'S/' : '$';

        $htmlDetalles = '';
        foreach ($detalles as $d) {
            $cantidadFormateada = number_format((float) $d->cantidad, 2, '.', '');
            $precioFormateado = number_format((float) ($d->precioUnitario ?? $d->valorUnitario), 2, '.', '');
            $totalFormateado = number_format((float) $d->total, 2, '.', '');

            $htmlDetalles .= "<tr>
                <td style='padding: 2px 0; vertical-align: top;'>[ {$cantidadFormateada} ]</td>
                <td style='padding: 2px 2px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word;'>{$d->descripcion}</td>
                <td align='right' style='padding: 2px 0; vertical-align: top;'>{$precioFormateado}</td>
                <td align='right' style='padding: 2px 0; vertical-align: top;'>{$totalFormateado}</td>
            </tr>";
        }

        $subtotalFormateado = number_format((float) $comprobante->subtotal, 2, '.', '');
        $igvFormateado = number_format((float) $comprobante->igv, 2, '.', '');
        $totalComprobanteFormateado = number_format((float) $comprobante->total, 2, '.', '');

        $leyenda = 'SON: ' . $this->numeroALetras((float)$comprobante->total, $comprobante->moneda);

        $qrHtml = '';
        if ($hash) {
            $qrData = implode('|', [
                $empresa->ruc,
                $comprobante->tipoComprobante,
                $comprobante->serie,
                $comprobante->correlativo,
                $comprobante->igv,
                $comprobante->total,
                date('Y-m-d', strtotime($comprobante->fechaEmision)),
                $comprobante->clienteTipoDocumento,
                $comprobante->clienteNumeroDocumento,
                $hash
            ]) . '|';

            try {
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(110, 0),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                );
                $writer = new \BaconQrCode\Writer($renderer);
                $svg = $writer->writeString($qrData, 'UTF-8', \BaconQrCode\Common\ErrorCorrectionLevel::L());
                // Base64 encode the SVG to avoid DOMDocument errors in older dompdf
                $svgBase64 = base64_encode($svg);
                $qrHtml = "<div class='center' style='margin: 10px 0;'><img src='data:image/svg+xml;base64,{$svgBase64}' width='110' /></div>";
            } catch (\Exception $e) {
                error_log('QR Ticket Error: ' . $e->getMessage());
                $qrHtml = "";
            }
        }

        $logoHtml = '';
        if (!empty($empresa->logoPath)) {
            $absPath = dirname(__DIR__, 4) . '/' . ltrim($empresa->logoPath, '/\\');
            if (file_exists($absPath)) {
                $type = pathinfo($absPath, PATHINFO_EXTENSION);
                $type = ($type === 'svg') ? 'svg+xml' : $type;
                $data = file_get_contents($absPath);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                $logoHtml = '<div class="center" style="margin-bottom: 5px;"><img src="' . $base64 . '" width="80" /></div>';
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 12px; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; margin: 0; padding: 0; color: #000; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .divider { border-bottom: 1px solid #000; margin: 8px 0; }
    .divider-dashed { border-bottom: 1px dashed #000; margin: 8px 0; }
    table { width: 100%; border-collapse: collapse; font-size: 11px; table-layout: fixed; }
    .mb-5 { margin-bottom: 5px; }
    .wrap-text { word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; }
    .title { font-size: 14px; font-weight: bold; }
</style>
</head>
<body>
    {$logoHtml}
    <div class="center title wrap-text">{$empresa->razonSocial}</div>
    <div class="center wrap-text" style="font-size: 11px; margin-top:3px;">
        {$empresa->direccion}<br>
    </div>
    
    <div class="divider"></div>
    
    <div class="center bold" style="font-size: 13px;">RUC: {$empresa->ruc}</div>
    <div class="center bold" style="font-size: 13px;">{$nombreComprobante}</div>
    <div class="center bold" style="font-size: 13px;">{$comprobante->getNumeroFormato()}</div>
    
    <div class="divider"></div>
    
    <div style="font-size: 11px;">
        <div class="mb-5"><span class="bold">DOCUMENTO:</span> {$comprobante->clienteNumeroDocumento}</div>
        <div class="mb-5"><span class="bold">CLIENTE:</span> <span class="wrap-text">{$comprobante->clienteNombre}</span></div>
        <div class="mb-5"><span class="bold">DIRECCIÓN:</span> <span class="wrap-text">{$comprobante->clienteDireccion}</span></div>
        <div class="mb-5"><span class="bold">MEDIO PAGO:</span> CONTADO</div>
    </div>
    
    <div class="center mb-5" style="font-size: 11px; margin-top:8px;">
        <span class="bold">FECHA:</span> {$comprobante->fechaEmision} - <span class="bold">HORA:</span> {$comprobante->horaEmision}<br>
        <span class="bold">MONEDA:</span> {$comprobante->moneda}
    </div>
    
    <div class="divider" style="margin-top: 8px;"></div>
    
    <table>
        <thead>
            <tr>
                <th align="left" style="width: 22%; border-bottom: 1px solid #000;">[ CANT. ]</th>
                <th align="left" style="width: 38%; border-bottom: 1px solid #000;">DESCRIPCIÓN</th>
                <th align="right" style="width: 20%; border-bottom: 1px solid #000; padding-right: 2px;">PRECIO</th>
                <th align="right" style="width: 20%; border-bottom: 1px solid #000;">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            {$htmlDetalles}
        </tbody>
    </table>
    
    <div class="divider" style="margin-top: 2px;"></div>
    
    <table style="width: 100%; font-size: 11px;">
        <tr><td align="right">GRAVADA {$moneda}:</td><td align="right" width="60">{$subtotalFormateado}</td></tr>
        <tr><td align="right">INAFECTA {$moneda}:</td><td align="right">0.00</td></tr>
        <tr><td align="right">EXONERADA {$moneda}:</td><td align="right">0.00</td></tr>
        <tr><td align="right">IGV (18%) {$moneda}:</td><td align="right">{$igvFormateado}</td></tr>
        <tr><td align="right" class="bold">IMPORTE TOTAL {$moneda}:</td><td align="right" class="bold">{$totalComprobanteFormateado}</td></tr>
    </table>
    
    <div style="font-size: 11px; margin-top: 8px;" class="wrap-text bold">{$leyenda}</div>
    
    {$qrHtml}
    
    <div class="center wrap-text" style="margin-top: 10px; font-size: 11px;">
        Autorizado mediante Resolución N°<br>
        <span class="bold">034-005-0005315/SUNAT</span><br>
        Representación impresa del Comprobante Electrónico<br>
        Puede consultar su documento en nuestro portal web.<br>
        - GRACIAS POR SU COMPRA -
    </div>
</body>
</html>
HTML;
    }

    private function guardarPdf(
        Comprobante $comprobante,
        EmpresaFacturacion $empresa,
        string $pdfContent,
        string $formato
    ): string {
        $ano = date('Y', strtotime($comprobante->fechaEmision));
        $mes = date('m', strtotime($comprobante->fechaEmision));

        $fileName = sprintf(
            '%s-%s-%s',
            $empresa->ruc,
            $comprobante->tipoComprobante,
            $comprobante->getNumeroFormato()
        );

        if ($formato === 'ticket') {
            $fileName .= '-ticket';
        }

        $pdfPath = $this->config->getPdfPath(
            $empresa->ruc,
            $ano,
            $mes,
            $fileName
        );

        $directorio = dirname($pdfPath);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0750, true);
        }

        file_put_contents($pdfPath, $pdfContent);

        return $pdfPath;
    }

    private function numeroALetras(float $numero, string $moneda): string
    {
        $entero = (int) $numero;
        $decimalStr = str_pad((string) round(($numero - $entero) * 100), 2, '0', STR_PAD_LEFT);
        $letras = trim($this->convertirNumero((int) $entero));

        $monedaText = $moneda === 'PEN' ? 'SOLES' : 'DÓLARES';

        return $letras . ' CON ' . $decimalStr . '/100 ' . $monedaText;
    }

    private function convertirNumero(int $numero): string
    {
        if ($numero === 0) {
            return 'CERO';
        }

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $resultado = '';

        if ($numero >= 1000000) {
            $millones = (int) ($numero / 1000000);
            $resultado .= ($millones === 1 ? 'UN MILLÓN' : $this->convertirNumero($millones) . ' MILLONES') . ' ';
            $numero %= 1000000;
        }

        if ($numero >= 1000) {
            $miles = (int) ($numero / 1000);
            $resultado .= ($miles === 1 ? 'MIL' : $this->convertirNumero($miles) . ' MIL') . ' ';
            $numero %= 1000;
        }

        if ($numero === 100) {
            $resultado .= 'CIEN ';
            $numero = 0;
        } elseif ($numero > 100) {
            $resultado .= $centenas[(int) ($numero / 100)] . ' ';
            $numero %= 100;
        }

        if ($numero >= 20) {
            if ($numero > 20 && $numero < 30) {
                $unidadesVeinte = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
                $resultado .= 'VEINTI' . $unidadesVeinte[$numero % 10] . ' ';
                $numero = 0;
            } else {
                $resultado .= $decenas[(int) ($numero / 10)];
                $numero %= 10;
                if ($numero > 0) {
                    $resultado .= ' Y ';
                } else {
                    $resultado .= ' ';
                }
            }
        } elseif ($numero >= 10) {
            $resultado .= $especiales[$numero - 10] . ' ';
            $numero = 0;
        }

        if ($numero > 0) {
            $resultado .= $unidades[$numero] . ' ';
        }

        return trim(str_replace('  ', ' ', $resultado));
    }
}
