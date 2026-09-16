# Fase 3: Motor de Facturación

## Objetivo
Implementar el motor central: numeración segura, modelos de comprobantes, validaciones y DTOs.

## Archivos a crear

| Archivo | Propósito |
|---------|-----------|
| `Models/Comprobante.php` | Modelo comprobante |
| `Models/DetalleComprobante.php` | Modelo detalle |
| `Repositories/ComprobanteRepository.php` | Acceso a datos |
| `DTO/ComprobanteDTO.php` | Transferencia de datos |
| `Services/FacturacionService.php` | Lógica de negocio |
| `Helpers/ResponseHelper.php` | Formato respuestas |

## Numeración segura

NUNCA usar `SELECT MAX(numero)`. Usar transacción con bloqueo:

```php
function obtenerCorrelativo(
    PDO $pdo,
    int $empresaId,
    string $tipoComprobante,
    string $serie
): int {
    $pdo->beginTransaction();

    try {
        // Bloqueo con SELECT ... FOR UPDATE
        $stmt = $pdo->prepare("
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
            ':serie' => $serie
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new FacturacionException(
                "Serie no configurada: {$tipoComprobante}-{$serie}"
            );
        }

        $nuevoCorrelativo = $row['correlativo'] + 1;

        // Actualizar correlativo
        $update = $pdo->prepare("
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
            ':serie' => $serie
        ]);

        $pdo->commit();
        return $nuevoCorrelativo;

    } catch (\Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

## Flujo de emisión

```
1. Validar empresa activa
   ↓
2. Validar certificado
   ↓
3. Validar datos cliente (doc, nombre)
   ↓
4. Validar items (cantidades, precios, IGV)
   ↓
5. Obtener correlativo (transacción)
   ↓
6. Guardar comprobante en BD (estado: pendiente)
   ↓
7. Construir objeto Greenter
   ↓
8. Generar XML UBL
   ↓
9. Firmar XML
   ↓
10. Generar hash
   ↓
11. Generar PDF
   ↓
12. Enviar a SUNAT
   ↓
13. Procesar CDR
   ↓
14. Guardar archivos (XML, PDF, CDR)
   ↓
15. Actualizar estado
```

## DTO ComprobanteDTO

```php
class ComprobanteDTO {
    public int $empresaId;
    public string $tipoComprobante;  // 01, 03, 07, 08
    public string $serie;
    public string $moneda;           // PEN, USD

    public array $cliente;           // tipo_doc, num_doc, nombre, direccion
    public array $items;             // codigo, desc, unidad, cant, precio, igv

    public ?string $fechaEmision;
    public ?string $horaEmision;
}
```

## Validaciones

### Comprobante
- Tipo comprobante válido (01, 03, 07, 08)
- Serie existe para la empresa
- Moneda válida (PEN, USD)
- Fecha emisión válida

### Cliente
- Tipo documento válido (6=RUC, 1=DNI, 4=CE, etc.)
- Número documento según tipo
- Nombre obligatorio

### Items
- Cantidad > 0
- Precio unitario >= 0
- Descripción obligatoria
- Unidad válida (NIU, ZZ, etc.)
- Afectación IGV válida (10, 20, 30, 21)

### Totales
- Suma items = subtotal
- IGV calculado correctamente (18%)
- Total = subtotal + IGV

## Tipos de comprobante

| Código | Tipo |
|--------|------|
| 01 | Factura |
| 03 | Boleta |
| 07 | Nota de Crédito |
| 08 | Nota de Débito |

## Estados

```
pendiente → generando → enviado → aceptado
                            ↓
                        rechazado
                            ↓
                          error
```

## Verificación

- [x] Correlativo secuencial por empresa/serie
- [x] Concurrencia: dos peticiones generan correlativos distintos
- [x] Validación de items completa
- [x] Cálculo de totales correcto
- [x] DTO recibe y valida datos
- [x] Errores descriptivos sin exponer internals
