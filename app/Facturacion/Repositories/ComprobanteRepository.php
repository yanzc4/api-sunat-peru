<?php

declare(strict_types=1);

namespace App\Facturacion\Repositories;

use App\Facturacion\Models\Comprobante;
use App\Facturacion\Models\DetalleComprobante;
use App\Facturacion\Exceptions\FacturacionException;
use PDO;

class ComprobanteRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Comprobante
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM comprobantes WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $comprobante = Comprobante::fromArray($row);
        $comprobante->detalles = $this->findDetalles($id);

        return $comprobante;
    }

    public function findByIdAndEmpresa(int $id, int $empresaId): ?Comprobante
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM comprobantes WHERE id = :id AND empresa_id = :empresa_id"
        );
        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $comprobante = Comprobante::fromArray($row);
        $comprobante->detalles = $this->findDetalles($id);

        return $comprobante;
    }

    public function findDetalles(int $comprobanteId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM comprobante_detalles WHERE comprobante_id = :id ORDER BY id ASC"
        );
        $stmt->execute([':id' => $comprobanteId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $row) => DetalleComprobante::fromArray($row),
            $rows
        );
    }

    public function findAll(array $filtros = []): array
    {
        $conditions = [];
        $params = [];

        if (isset($filtros['empresa_id'])) {
            $conditions[] = "c.empresa_id = :empresa_id";
            $params[':empresa_id'] = $filtros['empresa_id'];
        }

        if (isset($filtros['estado'])) {
            $conditions[] = "c.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (isset($filtros['tipo_comprobante'])) {
            $conditions[] = "c.tipo_comprobante = :tipo";
            $params[':tipo'] = $filtros['tipo_comprobante'];
        }

        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT c.* FROM comprobantes c {$where} ORDER BY c.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $row) => Comprobante::fromArray($row),
            $rows
        );
    }

    public function obtenerCorrelativo(
        int $empresaId,
        string $tipoComprobante,
        string $serie
    ): int {
        $stmt = $this->pdo->prepare("
            SELECT correlativo
            FROM comprobante_series
            WHERE empresa_id = :empresa_id
              AND tipo_comprobante = :tipo
              AND serie = :serie
            FOR UPDATE
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':tipo' => $tipoComprobante,
            ':serie' => $serie,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new FacturacionException(
                "Serie no configurada: {$tipoComprobante}-{$serie}"
            );
        }

        $nuevoCorrelativo = (int) $row['correlativo'] + 1;

        $update = $this->pdo->prepare("
            UPDATE comprobante_series
            SET correlativo = :correlativo
            WHERE empresa_id = :empresa_id
              AND tipo_comprobante = :tipo
              AND serie = :serie
        ");
        $update->execute([
            ':correlativo' => $nuevoCorrelativo,
            ':empresa_id' => $empresaId,
            ':tipo' => $tipoComprobante,
            ':serie' => $serie,
        ]);

        return $nuevoCorrelativo;
    }

    public function crearSerie(int $empresaId, string $tipoComprobante, string $serie, int $correlativoInicial = 0): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO comprobante_series (empresa_id, tipo_comprobante, serie, correlativo)
            VALUES (:empresa_id, :tipo_comprobante, :serie, :correlativo)
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':tipo_comprobante' => $tipoComprobante,
            ':serie' => $serie,
            ':correlativo' => $correlativoInicial,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function existeSerie(int $empresaId, string $tipoComprobante, string $serie): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM comprobante_series
            WHERE empresa_id = :empresa_id AND tipo_comprobante = :tipo AND serie = :serie
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':tipo' => $tipoComprobante,
            ':serie' => $serie,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function findSeriesByEmpresa(int $empresaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, empresa_id, tipo_comprobante, serie, correlativo
            FROM comprobante_series
            WHERE empresa_id = :empresa_id
            ORDER BY tipo_comprobante ASC, serie ASC
        ");
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(Comprobante $comprobante): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO comprobantes (
                empresa_id, tipo_comprobante, serie, correlativo,
                fecha_emision, hora_emision, moneda,
                cliente_tipo_documento, cliente_numero_documento,
                cliente_nombre, cliente_direccion,
                subtotal, igv, total,
                estado, created_at, updated_at
            ) VALUES (
                :empresa_id, :tipo_comprobante, :serie, :correlativo,
                :fecha_emision, :hora_emision, :moneda,
                :cliente_tipo_documento, :cliente_numero_documento,
                :cliente_nombre, :cliente_direccion,
                :subtotal, :igv, :total,
                :estado, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':empresa_id' => $comprobante->empresaId,
            ':tipo_comprobante' => $comprobante->tipoComprobante,
            ':serie' => $comprobante->serie,
            ':correlativo' => $comprobante->correlativo,
            ':fecha_emision' => $comprobante->fechaEmision,
            ':hora_emision' => $comprobante->horaEmision,
            ':moneda' => $comprobante->moneda,
            ':cliente_tipo_documento' => $comprobante->clienteTipoDocumento,
            ':cliente_numero_documento' => $comprobante->clienteNumeroDocumento,
            ':cliente_nombre' => $comprobante->clienteNombre,
            ':cliente_direccion' => $comprobante->clienteDireccion,
            ':subtotal' => $comprobante->subtotal,
            ':igv' => $comprobante->igv,
            ':total' => $comprobante->total,
            ':estado' => $comprobante->estado,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createDetalle(DetalleComprobante $detalle): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO comprobante_detalles (
                comprobante_id, codigo_producto, descripcion, unidad,
                cantidad, precio_unitario, valor_unitario,
                subtotal, igv, total, afectacion_igv
            ) VALUES (
                :comprobante_id, :codigo_producto, :descripcion, :unidad,
                :cantidad, :precio_unitario, :valor_unitario,
                :subtotal, :igv, :total, :afectacion_igv
            )
        ");

        $stmt->execute([
            ':comprobante_id' => $detalle->comprobanteId,
            ':codigo_producto' => $detalle->codigoProducto,
            ':descripcion' => $detalle->descripcion,
            ':unidad' => $detalle->unidad,
            ':cantidad' => $detalle->cantidad,
            ':precio_unitario' => $detalle->precioUnitario,
            ':valor_unitario' => $detalle->valorUnitario,
            ':subtotal' => $detalle->subtotal,
            ':igv' => $detalle->igv,
            ':total' => $detalle->total,
            ':afectacion_igv' => $detalle->afectacionIgv,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateEstado(
        int $id,
        string $estado,
        ?string $codigoRespuesta = null,
        ?string $mensajeRespuesta = null
    ): bool {
        $stmt = $this->pdo->prepare("
            UPDATE comprobantes
            SET estado = :estado,
                codigo_respuesta = :codigo,
                mensaje_respuesta = :mensaje,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':estado' => $estado,
            ':codigo' => $codigoRespuesta,
            ':mensaje' => $mensajeRespuesta,
        ]);
    }

    public function claimForProcessing(int $id, int $empresaId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE comprobantes
            SET estado = 'generando',
                codigo_respuesta = NULL,
                mensaje_respuesta = NULL,
                updated_at = NOW()
            WHERE id = :id
              AND empresa_id = :empresa_id
              AND estado IN ('pendiente', 'error')
        ");
        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId,
        ]);

        return $stmt->rowCount() === 1;
    }

    private function makeRelative(?string $path): ?string
    {
        if ($path === null) return null;
        $path = str_replace('\\', '/', $path);
        $pos = strpos($path, 'storage/');
        if ($pos !== false) {
            return substr($path, $pos);
        }
        return $path;
    }

    public function updateArchivos(
        int $id,
        ?string $xmlPath = null,
        ?string $pdfPath = null,
        ?string $cdrPath = null,
        ?string $hashCpe = null
    ): bool {
        $sets = ["updated_at = NOW()"];
        $params = [':id' => $id];

        if ($xmlPath !== null) {
            $sets[] = "xml_path = :xml_path";
            $params[':xml_path'] = $this->makeRelative($xmlPath);
        }
        if ($pdfPath !== null) {
            $sets[] = "pdf_path = :pdf_path";
            $params[':pdf_path'] = $this->makeRelative($pdfPath);
        }
        if ($cdrPath !== null) {
            $sets[] = "cdr_path = :cdr_path";
            $params[':cdr_path'] = $this->makeRelative($cdrPath);
        }
        if ($hashCpe !== null) {
            $sets[] = "hash_cpe = :hash_cpe";
            $params[':hash_cpe'] = $hashCpe;
        }

        $sql = "UPDATE comprobantes SET " .
               implode(', ', $sets) .
               " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function existeComprobante(
        int $empresaId,
        string $tipoComprobante,
        string $serie,
        int $correlativo
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM comprobantes
            WHERE empresa_id = :empresa_id
              AND tipo_comprobante = :tipo
              AND serie = :serie
              AND correlativo = :correlativo
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':tipo' => $tipoComprobante,
            ':serie' => $serie,
            ':correlativo' => $correlativo,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
