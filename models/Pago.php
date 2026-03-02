<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Consecutivo.php';

class Pago
{
    private $db;
    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
            return;
        }
        $this->db = (new Database())->getConnection();
    }


    public function obtenerPagosPorPrestamo($prestamoId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM pagos 
         WHERE prestamo_id = ?
         ORDER BY fecha ASC"
        );
        $stmt->execute([$prestamoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearPagoConItems($prestamoId, $tipo, $montoTotal, $items, $metodo = null, $referencia = null)
    {
        // $items = array of ['prestamos_detalle_id'=>..., 'monto_capital'=>..., 'monto_interes'=>...]
        try {
            $this->db->beginTransaction();

            $consecutivoModel = new Consecutivo($this->db);
            $consecutivoStr = $consecutivoModel->obtenerSiguiente('pagos');

            $stmt = $this->db->prepare("INSERT INTO pagos (consecutivo, prestamo_id, fecha, monto_total, tipo, metodo_pago, referencia) VALUES (?, ?, NOW(), ?, ?, ?, ?)");
            $stmt->execute([$consecutivoStr, $prestamoId, $montoTotal, $tipo, $metodo, $referencia]);
            $pagoId = $this->db->lastInsertId();

            $pagoItemStmt = $this->db->prepare("INSERT INTO pago_items (pago_id, prestamos_detalle_id, monto_capital, monto_interes) VALUES (?, ?, ?, ?)");

            foreach ($items as $it) {
                // 1. Registrar el item del pago
                $pagoItemStmt->execute([$pagoId, $it['prestamos_detalle_id'], $it['monto_capital'], $it['monto_interes']]);

                // 2. Si es solo interés, aplicar la lógica de "patear" el capital antes de actualizar
                if ($tipo === 'interes') {
                    $this->crearCuotaExtraPorInteresManual($prestamoId, $it['prestamos_detalle_id']);
                }

                // 3. ACTUALIZAR montos pagados
                $upd = $this->db->prepare("UPDATE prestamos_detalle SET pagado_capital = pagado_capital + ?, pagado_interes = pagado_interes + ? WHERE id = ?");
                $upd->execute([$it['monto_capital'], $it['monto_interes'], $it['prestamos_detalle_id']]);

                // 4. Determinar estado (respetando 'solo_interes' si ya se aplicó)
                $check = $this->db->prepare("SELECT capital, interes, pagado_capital, pagado_interes, estado FROM prestamos_detalle WHERE id = ?");
                $check->execute([$it['prestamos_detalle_id']]);
                $row = $check->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    if ($row['estado'] === 'solo_interes') {
                        continue; // No sobreescribir si ya es solo_interes
                    }

                    $totalCap = (float) $row['capital'];
                    $totalInt = (float) $row['interes'];
                    $pc = (float) $row['pagado_capital'];
                    $pi = (float) $row['pagado_interes'];

                    $estado = ($pc >= $totalCap && $pi >= $totalInt) ? 'pagado' : 'parcial';
                    $this->db->prepare("UPDATE prestamos_detalle SET estado = ? WHERE id = ?")->execute([$estado, $it['prestamos_detalle_id']]);
                }
            }

            $this->db->commit();
            return $pagoId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    /**
     * LOTE DE LÓGICA PARA SOLO INTERESES (Debe ejecutarse dentro de una transacción)
     */
    private function crearCuotaExtraPorInteresManual($prestamoId, $detalleId)
    {
        // 1. Obtener datos básicos del préstamo y la cuota
        $stmtP = $this->db->prepare("SELECT periodo_pago FROM prestamos WHERE id = ?");
        $stmtP->execute([$prestamoId]);
        $prestamo = $stmtP->fetch(PDO::FETCH_ASSOC);
        $periodo = $prestamo['periodo_pago'] ?? 'mensual';

        $stmtC = $this->db->prepare("SELECT * FROM prestamos_detalle WHERE id = ?");
        $stmtC->execute([$detalleId]);
        $cuotaActual = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$cuotaActual)
            throw new Exception("Cuota no encontrada");

        // 2. Modificar cuota actual: Capital 0, Estado 'solo_interes'
        $stmtUpdate = $this->db->prepare("
            UPDATE prestamos_detalle 
            SET capital = 0, 
                total_cuota = interes,
                estado = 'solo_interes'
            WHERE id = ?
        ");
        $stmtUpdate->execute([$detalleId]);

        // 3. Obtener última cuota para calcular fecha y número
        $stmtLast = $this->db->prepare("
            SELECT numero_cuota, fecha_programada 
            FROM prestamos_detalle 
            WHERE prestamo_id = ? 
            ORDER BY numero_cuota DESC 
            LIMIT 1
        ");
        $stmtLast->execute([$prestamoId]);
        $ultima = $stmtLast->fetch(PDO::FETCH_ASSOC);

        $nuevaNumero = $ultima['numero_cuota'] + 1;

        // Calcular fecha según periodo REAL del préstamo
        try {
            $fechaObj = new DateTime($ultima['fecha_programada']);
            switch ($periodo) {
                case 'diario':
                    $fechaObj->modify('+1 day');
                    break;
                case 'semanal':
                    $fechaObj->modify('+7 days');
                    break;
                case 'quincenal':
                    $fechaObj->modify('+15 days');
                    break;
                case 'mensual':
                    $fechaObj->modify('+1 month');
                    break;
                case 'anual':
                    $fechaObj->modify('+1 year');
                    break;
                default:
                    $fechaObj->modify('+1 month');
                    break;
            }
            $nuevaFecha = $fechaObj->format('Y-m-d');
        } catch (Exception $e) {
            $nuevaFecha = date('Y-m-d', strtotime($ultima['fecha_programada'] . ' +1 month'));
        }

        // 4. Crear nueva cuota al final con el CAPITAL que se retiró
        $stmtInsert = $this->db->prepare("
            INSERT INTO prestamos_detalle
            (prestamo_id, numero_cuota, fecha_programada, capital, interes, mora, total_cuota, saldo_restante, estado)
            VALUES (?, ?, ?, ?, ?, 0, ?, 0, 'pendiente')
        ");

        $nuevoCap = floatval($cuotaActual['capital']);
        $nuevoInt = floatval($cuotaActual['interes']);
        $nuevoTotal = round($nuevoCap + $nuevoInt, 2);

        $stmtInsert->execute([$prestamoId, $nuevaNumero, $nuevaFecha, $nuevoCap, $nuevoInt, $nuevoTotal]);

        // 5. Recalcular saldos de todo el préstamo
        $this->recalcularSaldosPrestamo($prestamoId);
    }

    private function recalcularSaldosPrestamo($prestamoId)
    {
        // El saldo en este sistema trackea el CAPITAL pendiente.
        $stmtP = $this->db->prepare("SELECT monto FROM prestamos WHERE id = ?");
        $stmtP->execute([$prestamoId]);
        $prestamo = $stmtP->fetch(PDO::FETCH_ASSOC);
        $saldoActual = floatval($prestamo['monto'] ?? 0);

        $stmt = $this->db->prepare("SELECT * FROM prestamos_detalle WHERE prestamo_id = ? ORDER BY numero_cuota ASC");
        $stmt->execute([$prestamoId]);
        $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $upd = $this->db->prepare("UPDATE prestamos_detalle SET saldo_restante = ? WHERE id = ?");

        foreach ($cuotas as $c) {
            // Descontamos el capital de esta cuota al saldo acumulado
            $cap = floatval($c['capital']);
            $saldoActual = round(max(0, $saldoActual - $cap), 2);

            $upd->execute([$saldoActual, $c['id']]);
        }
    }

    public function crearCuotaExtraPorInteres($prestamoId, $detalleId)
    {
        // Redirigir por compatibilidad si algo lo llama externamente
        return true;
    }
    public function obtenerDetallePago($id)
    {
        // ... (rest of function unchanged)
        // 1. Obtener datos generales del pago
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   pr.monto as prestamo_monto, 
                   d.nombre_completo as deudor_nombre,
                   d.documento as deudor_documento
            FROM pagos p
            JOIN prestamos pr ON pr.id = p.prestamo_id
            JOIN deudores d ON d.id = pr.deudor_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $pago = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pago)
            return null;

        // 2. Obtener items del pago (qué cuotas pagó)
        $stmtItems = $this->db->prepare("
            SELECT pi.*, pd.numero_cuota
            FROM pago_items pi
            JOIN prestamos_detalle pd ON pd.id = pi.prestamos_detalle_id
            WHERE pi.pago_id = ?
        ");
        $stmtItems->execute([$id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $pago['items'] = $items;
        return $pago;
    }

    /**
     * LÓGICA PARA PAGO TOTAL
     */
    public function obtenerUltimaFechaActividad($prestamoId)
    {
        // Intentar obtener fecha del último pago realizado
        $stmt = $this->db->prepare("SELECT fecha FROM pagos WHERE prestamo_id = ? ORDER BY fecha DESC LIMIT 1");
        $stmt->execute([$prestamoId]);
        $ultimoPago = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ultimoPago) {
            return $ultimoPago['fecha'];
        }

        // Si no hay pagos, retornar fecha de inicio del préstamo
        $stmtP = $this->db->prepare("SELECT fecha_inicio FROM prestamos WHERE id = ?");
        $stmtP->execute([$prestamoId]);
        $prestamo = $stmtP->fetch(PDO::FETCH_ASSOC);
        return $prestamo['fecha_inicio'] ?? date('Y-m-d');
    }

    public function obtenerCapitalPendiente($prestamoId)
    {
        $stmt = $this->db->prepare("SELECT SUM(capital - pagado_capital) as pendiente FROM prestamos_detalle WHERE prestamo_id = ?");
        $stmt->execute([$prestamoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return floatval($row['pendiente'] ?? 0);
    }

    public function calcularLiquidacionTotal($prestamoId)
    {
        $fechaUltima = $this->obtenerUltimaFechaActividad($prestamoId);
        $fechaHoy = date('Y-m-d');

        $d1 = new DateTime($fechaUltima);
        $d2 = new DateTime($fechaHoy);
        $diff = $d1->diff($d2);
        $diasTranscurridos = $diff->days;

        $capitalPendiente = $this->obtenerCapitalPendiente($prestamoId);

        // Obtener tasa para calcular el interés del mes
        $stmt = $this->db->prepare("SELECT tasa_interes FROM prestamos WHERE id = ?");
        $stmt->execute([$prestamoId]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        $tasa = floatval($p['tasa_interes'] ?? 0);

        // Interés del mes = Capital Pendiente * Tasa (%)
        $interesMes = $capitalPendiente * ($tasa / 100);

        // Interés a la fecha = (Interés Mensual / 30) * Días
        $interesHoy = ($interesMes / 30) * $diasTranscurridos;

        return [
            'capital_pendiente' => round($capitalPendiente, 2),
            'interes_hoy' => round($interesHoy, 2),
            'dias' => $diasTranscurridos,
            'fecha_ultima' => $fechaUltima,
            'total' => round($capitalPendiente + $interesHoy, 2)
        ];
    }

    public function procesarPagoTotal($prestamoId, $metodo, $referencia)
    {
        $liq = $this->calcularLiquidacionTotal($prestamoId);

        try {
            $this->db->beginTransaction();

            $consecutivoModel = new Consecutivo($this->db);
            $consecutivoStr = $consecutivoModel->obtenerSiguiente('pagos');

            // 1. Crear el registro del pago global
            $stmt = $this->db->prepare("INSERT INTO pagos (consecutivo, prestamo_id, fecha, monto_total, tipo, metodo_pago, referencia) VALUES (?, ?, NOW(), ?, 'total', ?, ?)");
            $stmt->execute([$consecutivoStr, $prestamoId, $liq['total'], $metodo, $referencia]);
            $pagoId = $this->db->lastInsertId();

            // 2. Liquidar todas las cuotas pendientes
            $stmtCuotas = $this->db->prepare("SELECT id, capital, pagado_capital, interes, pagado_interes FROM prestamos_detalle WHERE prestamo_id = ? AND estado NOT IN ('pagado', 'solo_interes')");
            $stmtCuotas->execute([$prestamoId]);
            $cuotasPendientes = $stmtCuotas->fetchAll(PDO::FETCH_ASSOC);

            // Vamos a distribuir el capital y el interés de liquidación entre las cuotas
            // aunque sea algo simbólico para que la DB quede consistente.
            $capRestante = $liq['capital_pendiente'];
            $intRestante = $liq['interes_hoy'];

            foreach ($cuotasPendientes as $index => $c) {
                $pCap = $c['capital'] - $c['pagado_capital'];
                // La última cuota de la liquidación se lleva lo que sobre del interés calculado
                $pInt = ($index === count($cuotasPendientes) - 1) ? $intRestante : min($intRestante, $c['interes'] - $c['pagado_interes']);

                $intRestante -= $pInt;

                // Actualizar detalle
                $upd = $this->db->prepare("UPDATE prestamos_detalle SET pagado_capital = pagado_capital + ?, pagado_interes = pagado_interes + ?, estado = 'pagado', saldo_restante = 0 WHERE id = ?");
                $upd->execute([$pCap, $pInt, $c['id']]);

                // Registrar en pago_items
                $stmtItem = $this->db->prepare("INSERT INTO pago_items (pago_id, prestamos_detalle_id, monto_capital, monto_interes) VALUES (?, ?, ?, ?)");
                $stmtItem->execute([$pagoId, $c['id'], $pCap, $pInt]);
            }

            // 3. Finalizar préstamo
            $this->db->prepare("UPDATE prestamos SET estado = 'cancelado' WHERE id = ?")->execute([$prestamoId]);

            $this->db->commit();
            return $pagoId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
