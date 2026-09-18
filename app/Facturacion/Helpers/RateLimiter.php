<?php

declare(strict_types=1);

namespace App\Facturacion\Helpers;

use App\Facturacion\Config\FacturacionConfig;

final class RateLimiter
{
    public function __construct(private ?string $directory = null)
    {
        $this->directory ??= FacturacionConfig::getInstance()->getStoragePath() . '/rate_limits';
        if (!is_dir($this->directory) && !mkdir($this->directory, 0750, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('No se pudo preparar el limitador de solicitudes');
        }
    }

    /** @return array{allowed: bool, retry_after: int, remaining: int} */
    public function consume(string $scope, string $identifier, int $limit, int $windowSeconds): array
    {
        if ($limit < 1 || $windowSeconds < 1) {
            throw new \InvalidArgumentException('Configuración de límite inválida');
        }

        $now = time();
        $handle = $this->open($scope, $identifier);
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('No se pudo bloquear el limitador de solicitudes');
            }
            $attempts = $this->read($handle);
            $attempts = array_values(array_filter(
                $attempts,
                static fn (int $timestamp): bool => $timestamp > $now - $windowSeconds
            ));

            if (count($attempts) >= $limit) {
                $retryAfter = max(1, ($attempts[0] + $windowSeconds) - $now);
                $this->write($handle, $attempts);
                return ['allowed' => false, 'retry_after' => $retryAfter, 'remaining' => 0];
            }

            $attempts[] = $now;
            $this->write($handle, $attempts);
            return [
                'allowed' => true,
                'retry_after' => 0,
                'remaining' => max(0, $limit - count($attempts)),
            ];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function clear(string $scope, string $identifier): void
    {
        $handle = $this->open($scope, $identifier);
        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }
            $this->write($handle, []);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @return resource */
    private function open(string $scope, string $identifier)
    {
        $safeScope = preg_replace('/[^a-z0-9_-]/i', '_', $scope) ?: 'global';
        $path = rtrim((string) $this->directory, '/\\') . '/' . $safeScope . '-' . hash('sha256', $identifier) . '.json';
        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el limitador de solicitudes');
        }
        return $handle;
    }

    /** @param resource $handle
     *  @return array<int, int>
     */
    private function read($handle): array
    {
        rewind($handle);
        $decoded = json_decode((string) stream_get_contents($handle), true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_filter(array_map('intval', $decoded), static fn (int $value): bool => $value > 0));
    }

    /** @param resource $handle
     *  @param array<int, int> $attempts
     */
    private function write($handle, array $attempts): void
    {
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, (string) json_encode($attempts));
        fflush($handle);
    }
}
