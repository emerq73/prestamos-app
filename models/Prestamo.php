<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PrestamoDetalle.php';
require_once __DIR__ . '/PrestamoSocioDetalle.php';
require_once __DIR__ . '/Consecutivo.php';

class Prestamo
{
    private $conn;  // ← AHORA SÍ EXISTE

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection(); // ← YA NO MARCA ERROR
        date_default_timezone_set('America/Bogota');
    }

    // Crear préstamo y devolver id
    public function crear($data)
    {
        $consecutivoModel = new Consecutivo($this->conn);
        $consecutivoStr = $consecutivoModel->obtenerSiguiente('prestamos');

        $sql = "INSERT INTO prestamos (consecutivo, deudor_id, monto, tasa_interes, plazo, tipo_tasa, periodo_pago, fecha_inicio, observaciones)
                VALUES (:consecutivo, :deudor_id, :monto, :tasa_interes, :plazo, :tipo_tasa, :periodo_pago, :fecha_inicio, :observaciones)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':consecutivo' => $consecutivoStr,
            ':deudor_id' => $data['deudor_id'],
            ':monto' => $data['monto'],
            ':tasa_interes' => $data['tasa_interes'],
            ':plazo' => $data['plazo'],
            ':tipo_tasa' => $data['tipo_tasa'] ?? 'mensual',
            ':periodo_pago' => $data['periodo_pago'] ?? 'mensual',
            ':fecha_inicio' => $data['fecha_inicio'],
            ':observaciones' => $data['observaciones'] ?? null
        ]);
        return $this->conn->lastInsertId();
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT 
    p.*, 
    d.nombre_completo AS deudor_nombre, 
    d.documento AS deudor_documento
FROM prestamos p
LEFT JOIN deudores d ON d.id = p.deudor_id
WHERE p.id = ?;
");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerTodos($estado = null)
    {
        $sql = "SELECT p.*, d.nombre_completo AS deudor_nombre, d.documento AS deudor_documento FROM prestamos p
                LEFT JOIN deudores d ON d.id = p.deudor_id";

        $params = [];
        if ($estado && $estado !== 'todos') {
            $sql .= " WHERE p.estado = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY p.created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstado($id, $estado)
    {
        $stmt = $this->conn->prepare("UPDATE prestamos SET estado = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }
    public function listar()
    {
        $sql = "SELECT p.*, d.nombre_completo AS deudor_nombre
            FROM prestamos p
            LEFT JOIN deudores d ON d.id = p.deudor_id
            ORDER BY p.id ASC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generarAmortizacion($prestamoId, $guardar = true)
    {
        require_once __DIR__ . '/PrestamoDetalle.php';
        $detalleModel = new PrestamoDetalle($this->conn);

        $prestamo = $this->obtenerPorId($prestamoId);
        if (!$prestamo)
            return false;

        // --------------------------
        //     DATOS BASE
        // --------------------------
        $monto = floatval($prestamo['monto']);
        $plazo = intval($prestamo['plazo']);
        $tasa = floatval($prestamo['tasa_interes']); // porcentaje
        $periodo = $prestamo['periodo_pago'];

        // --------------------------
        //     FÓRMULAS NUEVAS
        // --------------------------
        $capital_fijo = round($monto / $plazo, 2);
        $interes_fijo = round($monto * ($tasa / 100), 2);
        $total_cuota_fija = round($capital_fijo + $interes_fijo, 2);

        $saldo = $monto;
        $fecha = new DateTime($prestamo['fecha_inicio']);
        $cuotas = [];

        // --------------------------
        //     GENERAR CUOTAS
        // --------------------------
        for ($n = 1; $n <= $plazo; $n++) {

            // avanzar fecha según periodo antes de asignar a la cuota
            // La primera cuota debe ser un período DESPUÉS del desembolso
            switch ($periodo) {
                case 'diario':
                    $fecha->modify('+1 day');
                    break;
                case 'semanal':
                    $fecha->modify('+7 days');
                    break;
                case 'quincenal':
                    $fecha->modify('+15 days');
                    break;
                default:
                    $fecha->modify('+1 month');
                    break;
            }

            // última cuota ajusta capital para evitar errores centavos
            if ($n == $plazo) {
                $capital = $saldo;
                $total_cuota = round($capital + $interes_fijo, 2);
            } else {
                $capital = $capital_fijo;
                $total_cuota = $total_cuota_fija;
            }

            $interes = $interes_fijo;
            $saldo_restante = round(max(0, $saldo - $capital), 2);

            $cuotas[] = [
                'prestamo_id' => $prestamoId,
                'numero_cuota' => $n,
                'fecha_programada' => $fecha->format('Y-m-d'),
                'capital' => $capital,
                'interes' => $interes,
                'mora' => 0,
                'total_cuota' => $total_cuota,
                'saldo_restante' => $saldo_restante,
                'estado' => 'pendiente'
            ];

            $saldo = $saldo_restante;
        }

        // --------------------------
        //     GUARDAR SI ES NECESARIO
        // --------------------------
        if ($guardar) {

            // eliminar cuotas previas
            $detalleModel->eliminarPorPrestamo($prestamoId);

            // obtener socios que participan en el préstamo
            $prestamoSocioModel = new PrestamoSocio($this->conn);
            $prestamoSocios = $prestamoSocioModel->obtenerPorPrestamo($prestamoId);

            foreach ($cuotas as $c) {

                // guardar cuota principal
                $detalleId = $detalleModel->crear($c);

                // si hay socios, distribuir monto
                foreach ($prestamoSocios as $ps) {

                    $factor = floatval($ps['porcentaje']) / 100;

                    $aporte_capital = round($c['capital'] * $factor, 2);

                    // Lógica de Interés Específico
                    if (isset($ps['porcentaje_interes']) && $ps['porcentaje_interes'] > 0) {
                        // Calcular interés basado en el % del socio sobre su propio capital (aporte)
                        $tasaSocio = floatval($ps['porcentaje_interes']) / 100;
                        $aporte_interes = round(floatval($ps['aporte']) * $tasaSocio, 2);

                        // Si es mensual se asume que la tasa socio es mensual
                        // TODO: Si el periodo cambia, habría que ajustar esta lógica si el % interés socio es anual.
                        // Por simplicidad ahora asumo que el interés socio se aplica por cuota (como el interés del préstamo).
                    } else {
                        // Comportamiento anterior: Proporcional al interés cobrado al cliente
                        $aporte_interes = round($c['interes'] * $factor, 2);
                    }

                    $psdModel = new PrestamoSocioDetalle($this->conn);
                    $psdModel->crear([
                        'prestamos_detalle_id' => $detalleId,
                        'socio_id' => $ps['socio_id'],
                        'aporte_capital' => $aporte_capital,
                        'aporte_interes' => $aporte_interes
                    ]);
                }
            }
        }

        return $cuotas;
    }
    public function eliminar($prestamoId)
    {
        try {
            $this->conn->beginTransaction();

            // 1. eliminar pago_socios
            $this->conn->prepare("
            DELETE ps FROM pago_socios ps
            INNER JOIN pago_items pi ON pi.id = ps.pago_item_id
            INNER JOIN pagos p ON p.id = pi.pago_id
            WHERE p.prestamo_id = ?
        ")->execute([$prestamoId]);

            // 2. eliminar pago_items
            $this->conn->prepare("
            DELETE pi FROM pago_items pi
            INNER JOIN pagos p ON p.id = pi.pago_id
            WHERE p.prestamo_id = ?
        ")->execute([$prestamoId]);

            // 3. eliminar pagos
            $this->conn->prepare("DELETE FROM pagos WHERE prestamo_id = ?")
                ->execute([$prestamoId]);

            // 4. eliminar prestamo_socios_detalle
            $this->conn->prepare("
            DELETE psd FROM prestamo_socios_detalle psd
            INNER JOIN prestamos_detalle pd ON pd.id = psd.prestamos_detalle_id
            WHERE pd.prestamo_id = ?
        ")->execute([$prestamoId]);

            // 5. eliminar prestamo_socios
            $this->conn->prepare("DELETE FROM prestamo_socios WHERE prestamo_id = ?")
                ->execute([$prestamoId]);

            // 6. eliminar prestamos_detalle
            $this->conn->prepare("DELETE FROM prestamos_detalle WHERE prestamo_id = ?")
                ->execute([$prestamoId]);

            // 7. eliminar prestamo
            $this->conn->prepare("DELETE FROM prestamos WHERE id = ?")
                ->execute([$prestamoId]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    public function tienePrestamosPendientes($deudorId)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM prestamos WHERE deudor_id = ? AND estado = 'activo'");
        $stmt->execute([$deudorId]);
        return $stmt->fetchColumn() > 0;
    }
}
