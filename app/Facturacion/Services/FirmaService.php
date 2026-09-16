<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Exceptions\CertificadoException;
use App\Facturacion\Exceptions\FacturacionException;
use App\Facturacion\Models\EmpresaFacturacion;
use Greenter\XMLSecLibs\Sunat\SignedXml;

class FirmaService
{
    private EncryptionService $encryption;

    public function __construct()
    {
        $this->encryption = new EncryptionService();
    }

    public function firmar(string $xml, EmpresaFacturacion $empresa): string
    {
        $this->validarCertificado($empresa);

        $certContent = $this->obtenerCertificado($empresa);

        $signedXml = new SignedXml();
        $signedXml->setCertificate($certContent);

        try {
            $xmlFirmado = $signedXml->signXml($xml);
        } catch (\Exception $e) {
            throw new FacturacionException(
                "Error al firmar XML: " . $e->getMessage()
            );
        }

        if (empty($xmlFirmado)) {
            throw new FacturacionException(
                "El XML firmado está vacío"
            );
        }

        return $xmlFirmado;
    }

    public function generarHash(string $xmlFirmado): string
    {
        return hash('sha256', $xmlFirmado);
    }

    private function validarCertificado(EmpresaFacturacion $empresa): void
    {
        if (empty($empresa->certificadoPath)) {
            throw new CertificadoException(
                "La empresa no tiene certificado configurado"
            );
        }

        $resolvedPath = $this->resolverRuta($empresa->certificadoPath);

        if (!file_exists($resolvedPath)) {
            throw new CertificadoException(
                "El certificado no existe: {$resolvedPath}"
            );
        }

        $extension = strtolower(pathinfo(
            $resolvedPath,
            PATHINFO_EXTENSION
        ));

        if (!in_array($extension, ['pfx', 'p12'], true)) {
            throw new CertificadoException(
                "Extensión de certificado no válida: {$extension}"
            );
        }
    }

    private function obtenerCertificado(EmpresaFacturacion $empresa): string
    {
        $resolvedPath = $this->resolverRuta($empresa->certificadoPath);
        $certContent = file_get_contents($resolvedPath);

        if ($certContent === false) {
            throw new CertificadoException(
                "Error al leer el certificado"
            );
        }

        $password = $this->encryption->decrypt(
            $empresa->certificadoPassword
        );

        $certs = [];
        $result = openssl_pkcs12_read($certContent, $certs, $password);

        if (!$result || empty($certs)) {
            throw new CertificadoException(
                "La contraseña del certificado es incorrecta o el certificado está corrupto"
            );
        }

        $certPem = $certs['cert'] ?? null;
        $pkeyPem = $certs['pkey'] ?? null;

        if ($certPem === null || $pkeyPem === null) {
            throw new CertificadoException(
                "No se pudo extraer el certificado o la clave privada"
            );
        }

        $this->validarExpiracion($certPem);

        return $certPem . $pkeyPem;
    }

    private function validarExpiracion(string $certPem): void
    {
        $certData = openssl_x509_parse($certPem);

        if ($certData === false) {
            return;
        }

        if (isset($certData['validTo_time_t'])) {
            $fechaExpiracion = $certData['validTo_time_t'];
            if ($fechaExpiracion < time()) {
                throw new CertificadoException(
                    "El certificado ha expirado el " .
                    date('d/m/Y', $fechaExpiracion)
                );
            }
        }
    }

    private function resolverRuta(string $ruta): string
    {
        $ruta = str_replace('\\', '/', $ruta);

        if (!preg_match('/^[A-Z]:\//i', $ruta) && $ruta[0] !== '/') {
            $projectRoot = str_replace('\\', '/', dirname(__DIR__, 3));
            $ruta = $projectRoot . '/' . $ruta;
        }

        return $ruta;
    }
}
