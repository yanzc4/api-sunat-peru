<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Config\FacturacionConfig;
use App\Facturacion\Exceptions\FacturacionException;

final class DocumentLookupService
{
    public function lookup(string $type, string $number): array
    {
        $type = strtolower(trim($type));
        if (!in_array($type, ['dni', 'ruc'], true)) {
            throw new FacturacionException('Tipo de documento no válido');
        }
        $length = $type === 'dni' ? 8 : 11;
        if (!preg_match('/^\d{' . $length . '}$/', $number)) {
            throw new FacturacionException(strtoupper($type) . " debe tener {$length} dígitos");
        }

        $config = FacturacionConfig::getInstance();
        $token = $config->getDocumentLookupToken();
        if ($token === '') {
            throw new \RuntimeException('Proveedor de documentos no configurado');
        }
        $url = rtrim($config->getDocumentLookupBaseUrl(), '/') . '/' . $type . '/' . $number
            . '?' . http_build_query(['token' => $token]);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false || $error !== '') {
            throw new \RuntimeException('El proveedor de documentos no respondió a tiempo');
        }
        $data = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            throw new FacturacionException('No se encontraron datos para el documento');
        }

        if ($type === 'dni') {
            $name = trim(implode(' ', array_filter([
                $data['nombres'] ?? '',
                $data['apellidoPaterno'] ?? '',
                $data['apellidoMaterno'] ?? '',
            ])));
            return ['tipo_documento' => '1', 'numero_documento' => $number, 'nombre' => $name, 'direccion' => ''];
        }

        return [
            'tipo_documento' => '6',
            'numero_documento' => $number,
            'nombre' => trim((string) ($data['razonSocial'] ?? '')),
            'direccion' => trim((string) ($data['direccion'] ?? '')),
            'estado' => $data['estado'] ?? null,
            'condicion' => $data['condicion'] ?? null,
        ];
    }
}
