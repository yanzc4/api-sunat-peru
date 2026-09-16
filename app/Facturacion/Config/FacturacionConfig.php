<?php

declare(strict_types=1);

namespace App\Facturacion\Config;

class FacturacionConfig
{
    private static ?self $instance = null;

    private string $storagePath;
    private string $certificadosPath;
    private string $facturacionPath;
    private string $encryptionKey;
    private string $encryptionIv;

    private function __construct()
    {
        $root = dirname(__DIR__, 3);

        $this->storagePath = $root . '/storage/private';
        $this->certificadosPath = $this->storagePath . '/certificados';
        $this->facturacionPath = $this->storagePath . '/facturacion';

        $this->encryptionKey = $_ENV['FAC_ENCRYPTION_KEY'] ?? 'default-key-change-me';
        $this->encryptionIv = $_ENV['FAC_ENCRYPTION_IV'] ?? 'default-iv--change';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function getCertificadosPath(): string
    {
        return $this->certificadosPath;
    }

    public function getFacturacionPath(): string
    {
        return $this->facturacionPath;
    }

    public function getCertificadoPath(string $ruc): string
    {
        return $this->certificadosPath . '/' . $ruc;
    }

    public function getComprobantePath(string $ruc, string $ano, string $mes): string
    {
        return $this->facturacionPath . '/' . $ruc . '/' . $ano . '/' . str_pad($mes, 2, '0', STR_PAD_LEFT);
    }

    public function getXmlPath(string $ruc, string $ano, string $mes, string $fileName): string
    {
        return $this->getComprobantePath($ruc, $ano, $mes) . '/xml/' . $fileName . '.xml';
    }

    public function getPdfPath(string $ruc, string $ano, string $mes, string $fileName): string
    {
        return $this->getComprobantePath($ruc, $ano, $mes) . '/pdf/' . $fileName . '.pdf';
    }

    public function getCdrPath(string $ruc, string $ano, string $mes, string $fileName): string
    {
        return $this->getComprobantePath($ruc, $ano, $mes) . '/cdr/' . $fileName . '.zip';
    }

    public function getEncryptionKey(): string
    {
        return $this->encryptionKey;
    }

    public function getEncryptionIv(): string
    {
        return $this->encryptionIv;
    }

    public function getSunatBetaUrl(): string
    {
        return 'https://e-beta.sunat.gob.pe/pe/tncityservice/v1/cdr';
    }

    public function getSunatProduccionUrl(): string
    {
        return 'https://api.sunat.gob.pe/v1/contribuyente/gem/comprobantes';
    }

    public function ensureDirectories(): void
    {
        $dirs = [
            $this->storagePath,
            $this->certificadosPath,
            $this->facturacionPath,
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
        }
    }
}
