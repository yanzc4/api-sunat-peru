<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\Exceptions\CertificadoException;

class CertificadoService
{
    private FacturacionConfig $config;
    private EncryptionService $encryption;

    public function __construct()
    {
        $this->config = FacturacionConfig::getInstance();
        $this->encryption = new EncryptionService();
    }

    public function validate(string $ruc, string $certificadoPath, string $certificadoPasswordCifrado): void
    {
        $this->validarExistencia($certificadoPath);
        $this->validarExtension($certificadoPath);
        $this->validarPropiedad($certificadoPath, $ruc);

        $password = $this->encryption->decrypt($certificadoPasswordCifrado);
        $this->validarContraseña($certificadoPath, $password);
        $this->validarExpiracion($certificadoPath, $password);
    }

    public function getCertificadoInfo(string $certificadoPath, string $certificadoPasswordCifrado): array
    {
        $this->validarExistencia($certificadoPath);
        $this->validarExtension($certificadoPath);

        $password = $this->encryption->decrypt($certificadoPasswordCifrado);

        $certInfo = $this->obtenerInfoCertificado($certificadoPath, $password);

        return [
            'path' => $certificadoPath,
            'subject' => $certInfo['subject'] ?? null,
            'issuer' => $certInfo['issuer'] ?? null,
            'valid_from' => $certInfo['valid_from'] ?? null,
            'valid_to' => $certInfo['valid_to'] ?? null,
            'serial_number' => $certInfo['serial_number'] ?? null,
        ];
    }

    public function guardarCertificado(
        string $ruc,
        string $certificadoTmpPath,
        string $extension
    ): string {
        $directorioCert = $this->config->getCertificadoPath($ruc);

        if (!is_dir($directorioCert)) {
            mkdir($directorioCert, 0750, true);
        }

        $nombreArchivo = 'certificado.' . strtolower($extension);
        $destino = $directorioCert . '/' . $nombreArchivo;

        if (!move_uploaded_file($certificadoTmpPath, $destino)) {
            throw new CertificadoException(
                "Error al guardar el certificado"
            );
        }

        return $destino;
    }

    private function validarExistencia(string $path): void
    {
        $resolvedPath = $this->resolverRuta($path);

        if (!file_exists($resolvedPath)) {
            throw new CertificadoException('El certificado configurado no existe');
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

    private function validarExtension(string $path): void
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $extensionesValidas = ['pfx', 'p12'];

        if (!in_array($extension, $extensionesValidas, true)) {
            throw new CertificadoException(
                "Extensión de certificado no válida: {$extension}. " .
                "Extensiones permitidas: " . implode(', ', $extensionesValidas)
            );
        }
    }

    private function validarPropiedad(string $path, string $ruc): void
    {
        $directorioCert = $this->config->getCertificadoPath($ruc);

        $pathNormalizado = $this->normalizarRuta($path);
        $directorioNormalizado = $this->normalizarRuta($directorioCert);

        if (strpos($pathNormalizado, $directorioNormalizado) !== 0) {
            throw new CertificadoException(
                "El certificado no pertenece a la empresa con RUC: {$ruc}"
            );
        }
    }

    private function normalizarRuta(string $ruta): string
    {
        $ruta = str_replace('\\', '/', $ruta);

        if (!preg_match('/^[A-Z]:\//i', $ruta) && $ruta[0] !== '/') {
            $projectRoot = $this->normalizarRuta(dirname(__DIR__, 3));
            $ruta = $projectRoot . '/' . $ruta;
        }

        $ruta = rtrim($ruta, '/');
        return $ruta;
    }

    private function validarContraseña(string $path, string $password): void
    {
        $certInfo = $this->obtenerInfoCertificado($path, $password);

        if ($certInfo === null) {
            throw new CertificadoException(
                "La contraseña del certificado es incorrecta"
            );
        }
    }

    private function validarExpiracion(string $path, string $password): void
    {
        $certInfo = $this->obtenerInfoCertificado($path, $password);

        if ($certInfo === null) {
            return;
        }

        if (isset($certInfo['valid_to'])) {
            $fechaExpiracion = strtotime($certInfo['valid_to']);
            if ($fechaExpiracion !== false && $fechaExpiracion < time()) {
                throw new CertificadoException(
                    "El certificado ha expirado el " .
                    date('d/m/Y', $fechaExpiracion)
                );
            }
        }
    }

    private function obtenerInfoCertificado(string $path, string $password): ?array
    {
        $resolvedPath = $this->resolverRuta($path);
        $certContent = file_get_contents($resolvedPath);

        if ($certContent === false) {
            return null;
        }

        $cert = openssl_pkcs12_read($certContent, $certs, $password);

        if (!$cert || empty($certs)) {
            return null;
        }

        $certPem = $certs['cert'] ?? null;
        $pkeyPem = $certs['pkey'] ?? null;

        if ($certPem === null) {
            return null;
        }

        $certData = openssl_x509_parse($certPem);

        if ($certData === false) {
            return null;
        }

        return [
            'subject' => $certData['subject']['CN'] ??
                         $certData['subject']['commonName'] ??
                         null,
            'issuer' => $certData['issuer']['CN'] ??
                        $certData['issuer']['commonName'] ??
                        null,
            'valid_from' => isset($certData['validFrom_time_t'])
                ? date('Y-m-d H:i:s', $certData['validFrom_time_t'])
                : null,
            'valid_to' => isset($certData['validTo_time_t'])
                ? date('Y-m-d H:i:s', $certData['validTo_time_t'])
                : null,
            'serial_number' => $certData['serialNumberHex'] ?? null,
            'cert' => $certPem,
            'pkey' => $pkeyPem,
        ];
    }
}
