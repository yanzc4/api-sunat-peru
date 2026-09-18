<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;

class EncryptionService
{
    private const PREFIX = 'v2:';
    private const AAD = 'facturacion:v2';
    private const GCM_IV_LENGTH = 12;
    private const GCM_TAG_LENGTH = 16;

    private string $key;
    private string $iv;
    private string $legacyMethod = 'aes-256-cbc';

    public function __construct()
    {
        $config = FacturacionConfig::getInstance();
        $this->key = $config->getEncryptionKey();
        $this->iv = substr($config->getEncryptionIv(), 0, 16);
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::GCM_IV_LENGTH);
        $tag = '';
        $encrypted = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            hash('sha256', $this->key, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::AAD,
            self::GCM_TAG_LENGTH
        );

        if ($encrypted === false || strlen($tag) !== self::GCM_TAG_LENGTH) {
            throw new \RuntimeException('Error al cifrar datos');
        }

        return self::PREFIX . base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt(string $ciphertext): string
    {
        if (str_starts_with($ciphertext, self::PREFIX)) {
            return $this->decryptAuthenticated(substr($ciphertext, strlen(self::PREFIX)));
        }

        return $this->decryptLegacy($ciphertext);
    }

    private function decryptAuthenticated(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) < self::GCM_IV_LENGTH + self::GCM_TAG_LENGTH) {
            throw new \RuntimeException('Datos cifrados inválidos');
        }

        $iv = substr($decoded, 0, self::GCM_IV_LENGTH);
        $tag = substr($decoded, self::GCM_IV_LENGTH, self::GCM_TAG_LENGTH);
        $encrypted = substr($decoded, self::GCM_IV_LENGTH + self::GCM_TAG_LENGTH);
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-gcm',
            hash('sha256', $this->key, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::AAD
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Los datos cifrados fueron alterados o la llave no es válida');
        }

        return $decrypted;
    }

    private function decryptLegacy(string $ciphertext): string
    {
        $decoded = base64_decode($ciphertext, true);

        if ($decoded === false) {
            throw new \RuntimeException('Datos cifrados inválidos');
        }

        $decrypted = openssl_decrypt(
            $decoded,
            $this->legacyMethod,
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
