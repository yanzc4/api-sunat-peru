<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;

class EncryptionService
{
    private string $key;
    private string $iv;
    private string $method = 'aes-256-cbc';

    public function __construct()
    {
        $config = FacturacionConfig::getInstance();
        $this->key = $config->getEncryptionKey();
        $this->iv = substr($config->getEncryptionIv(), 0, 16);
    }

    public function encrypt(string $plaintext): string
    {
        $encrypted = openssl_encrypt(
            $plaintext,
            $this->method,
            $this->key,
            0,
            $this->iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Error al cifrar datos');
        }

        return base64_encode($encrypted);
    }

    public function decrypt(string $ciphertext): string
    {
        $decoded = base64_decode($ciphertext, true);

        if ($decoded === false) {
            throw new \RuntimeException('Datos cifrados inválidos');
        }

        $decrypted = openssl_decrypt(
            $decoded,
            $this->method,
            $this->key,
            0,
            $this->iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Error al descifrar datos');
        }

        return $decrypted;
    }
}
