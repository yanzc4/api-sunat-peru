<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\DTO\ComprobanteDTO;
use App\Facturacion\DTO\ItemDTO;
use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Models\Comprobante;
use App\Facturacion\Models\EmpresaFacturacion;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Xml\Builder\InvoiceBuilder;
use Greenter\Xml\Builder\NoteBuilder;

class XmlService
{
    private FacturacionConfig $config;
    private EncryptionService $encryption;

    public function __construct()
    {
        $this->config = FacturacionConfig::getInstance();
        $this->encryption = new EncryptionService();
    }

    public function generarXml(
        Comprobante $comprobante,
        EmpresaFacturacion $empresa,
        array $detalles
    ): string {
        $company = $this->buildCompany($empresa);
        $client = $this->buildClient($comprobante);
        $items = $this->buildItems($detalles);

        if (in_array($comprobante->tipoComprobante, ['01', '03'])) {
            $document = $this->buildInvoice(
                $comprobante,
                $company,
                $client,
                $items
            );
            $builder = new InvoiceBuilder();
        } else {
            $document = $this->buildNote(
                $comprobante,
                $company,
                $client,
                $items
            );
            $builder = new NoteBuilder();
        }

        $xml = $builder->build($document);

        if ($xml === null || $xml === '') {
            throw new FacturacionException(
                "Error al generar XML UBL"
            );
        }

        return $xml;
    }

    private function buildCompany(EmpresaFacturacion $empresa): Company
    {
        $company = new Company();
        $company->setRuc($empresa->ruc);
        $company->setRazonSocial($empresa->razonSocial);
        $company->setNombreComercial(
            $empresa->nombreComercial ?? $empresa->razonSocial
        );
        $company->setAddress($this->buildAddress(
            $empresa->ubigeo,
            $empresa->departamento,
            $empresa->provincia,
            $empresa->distrito,
            $empresa->direccion
        ));

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

    private function buildAddress(
        ?string $ubigeo,
        ?string $departamento,
        ?string $provincia,
        ?string $distrito,
        ?string $direccion
    ): Address {
        $address = new Address();
        $address->setUbigueo($ubigeo ?? '000000');
        $address->setDepartamento($departamento ?? '');
        $address->setProvincia($provincia ?? '');
        $address->setDistrito($distrito ?? '');
        $address->setDireccion($direccion ?? '');

        return $address;
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
            $detail->setMtoBaseIgv($detalle->subtotal);
            $detail->setPorcentajeIgv(18.0);
            $detail->setIgv($detalle->igv);
            $detail->setTipAfeIgv($detalle->afectacionIgv ?? '10');
            $detail->setTotalImpuestos($detalle->igv);

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

    public function guardarXml(string $xml, string $ruc, string $fechaEmision, string $numero): string
    {
        $ano = date('Y', strtotime($fechaEmision));
        $mes = date('m', strtotime($fechaEmision));

        $xmlPath = $this->config->getXmlPath($ruc, $ano, $mes, $numero);

        $directorio = dirname($xmlPath);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0750, true);
        }

        file_put_contents($xmlPath, $xml);

        return $xmlPath;
    }
}
