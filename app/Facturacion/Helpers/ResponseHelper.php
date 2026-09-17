<?php

declare(strict_types=1);

namespace App\Facturacion\Helpers;

class ResponseHelper
{
    public static function success(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $json = json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $json = json_encode([
                'success' => true,
                'data' => $data,
            ]);

            if ($json === false) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => ['code' => 'JSON_ERROR', 'message' => json_last_error_msg()],
                ]);
                exit;
            }
        }

        echo $json;
        exit;
    }

    public static function error(string $code, string $message, int $statusCode = 400, ?array $details = null): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function validationError(string $message, array $details = []): void
    {
        self::error('VALIDATION_ERROR', $message, 400, $details);
    }

    public static function notFound(string $message = 'Recurso no encontrado'): void
    {
        self::error('NOT_FOUND', $message, 404);
    }

    public static function unauthorized(string $message = 'No autorizado'): void
    {
        self::error('UNAUTHORIZED', $message, 401);
    }

    public static function forbidden(string $message = 'Acceso denegado'): void
    {
        self::error('FORBIDDEN', $message, 403);
    }

    public static function internalError(string $message = 'Error interno del servidor'): void
    {
        self::error('INTERNAL_ERROR', $message, 500);
    }

    public static function internalException(\Throwable $exception, string $context = 'Error interno'): void
    {
        error_log(sprintf(
            '[%s] %s: %s in %s:%d',
            date('c'),
            $context,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ));

        self::internalError('Error interno del servidor');
    }

    public static function sunatError(string $message, ?string $codigo = null): void
    {
        $details = $codigo !== null ? ['sunat_code' => $codigo] : null;
        self::error('SUNAT_ERROR', $message, 422, $details);
    }

    public static function sunatRejected(string $message, ?string $codigo = null, ?string $descripcion = null): void
    {
        $details = [];
        if ($codigo !== null) {
            $details['sunat_code'] = $codigo;
        }
        if ($descripcion !== null) {
            $details['sunat_description'] = $descripcion;
        }

        self::error('SUNAT_REJECTED', $message, 422, $details ?: null);
    }
}
