<?php

declare(strict_types=1);

namespace App\Facturacion\Config;

class FacturacionConfig
{
    private static ?self $instance = null;

    private string $projectRoot;
    private string $storagePath;
    private string $certificadosPath;
    private string $facturacionPath;
    private string $encryptionKey;
    private string $encryptionIv;
    private string $jwtSecret;
    private int $jwtTtl;

    private function __construct()
    {
        $root = dirname(__DIR__, 3);
        $this->projectRoot = str_replace('\\', '/', $root);

        $this->storagePath = $this->projectRoot . '/storage/private';
        $this->certificadosPath = $this->storagePath . '/certificados';
        $this->facturacionPath = $this->storagePath . '/facturacion';

        $this->encryptionKey = $_ENV['FAC_ENCRYPTION_KEY'] ?? 'default-key-change-me';
        $this->encryptionIv = $_ENV['FAC_ENCRYPTION_IV'] ?? 'default-iv--change';

        $this->jwtSecret = $_ENV['FAC_JWT_SECRET'] ?? '';

        $this->jwtTtl = (int) ($_ENV['FAC_JWT_TTL'] ?? 28800);
        if ($this->jwtTtl <= 0) {
            $this->jwtTtl = 28800;
        }
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

    public function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

    public function resolveProjectPath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($path));
        if (preg_match('/^[A-Z]:\//i', $normalized) || str_starts_with($normalized, '/')) {
            return $normalized;
        }

        // Compatibilidad con rutas antiguas que incluían prefijos del proyecto.
        $storagePosition = strpos($normalized, 'storage/');
        if ($storagePosition !== false) {
            $normalized = substr($normalized, $storagePosition);
        }

        return $this->projectRoot . '/' . ltrim($normalized, '/');
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

    public function getJwtSecret(): string
    {
        if ($this->jwtSecret === '' || strlen($this->jwtSecret) < 32) {
            throw new \RuntimeException(
                'FAC_JWT_SECRET no está definido o es demasiado corto. '
                . 'Genera uno con: php -r "echo bin2hex(random_bytes(32));"'
            );
        }

        return $this->jwtSecret;
    }

    public function getJwtTtl(): int
    {
        return $this->jwtTtl;
    }

    public function getSunatBetaUrl(): string
    {
        return \App\Facturacion\Services\SunatEnvironment::sendEndpoint('beta');
    }

    public function getSunatProduccionUrl(): string
    {
        return \App\Facturacion\Services\SunatEnvironment::sendEndpoint('produccion');
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
