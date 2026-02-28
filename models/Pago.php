<?php
require_once __DIR__ . '/Database.php';

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

            $stmt = $this->db->prepare("INSERT INTO pagos (prestamo_id, fecha, monto_total, tipo, metodo_pago, referencia) VALUES (?, NOW(), ?, ?, ?, ?)");
            $stmt->execute([$prestamoId, $montoTotal, $tipo, $metodo, $referencia]);
            $pagoId = $this->db->lastInsertId();

            $pagoItemStmt = $this->db->prepare("INSERT INTO pago_items (pago_id, prestamos_detalle_id, monto_capital, monto_interes) VALUES (?, ?, ?, ?)");

            foreach ($items as $it) {
                $pagoItemStmt->execute([$pagoId, $it['prestamos_detalle_id'], $it['monto_capital'], $it['monto_interes']]);

                // ACTUALIZAR saldo en prestamos_detalle: incrementar pagado_capital/pagado_interes
                $upd = $this->db->prepare("UPDATE prestamos_detalle SET pagado_capital = pagado_capital + ?, pagado_interes = pagado_interes + ? WHERE id = ?");
                $upd->execute([$it['monto_capital'], $it['monto_interes'], $it['prestamos_detalle_id']]);

                // opcional: actualizar estado (parcial/pagada)
                $check = $this->db->prepare("SELECT capital, interes, pagado_capital, pagado_interes FROM prestamos_detalle WHERE id = ?");
                $check->execute([$it['prestamos_detalle_id']]);
                $row = $check->fetch(PDO::FETCH_ASSOC);
                if ($row) {
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
    public function crearCuotaExtraPorInteres($prestamoId, $detalleId)
    {
        // 1. Obtener cuota actual
        $stmt = $this->db->prepare("SELECT * FROM prestamos_detalle WHERE id = ?");
        $stmt->execute([$detalleId]);
        $cuotaActual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cuotaActual)
            throw new Exception("Cuota no encontrada");

        // 2. Marcar cuota actual como PARCIAL. 
        $stmtUpdate = $this->db->prepare("
            UPDATE prestamos_detalle 
            SET estado = 'parcial'
            WHERE id = ?
        ");
        $stmtUpdate->execute([$detalleId]);

        // 3. Obtener última cuota para calcular fecha y numero
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
        $nuevaFecha = date('Y-m-d', strtotime($ultima['fecha_programada'] . ' +1 month')); // Asumimos mensual por defecto, idealmente leer periodo

        // 4. Crear nueva cuota al final con el CAPITAL que no se pagó hoy
        $stmtInsert = $this->db->prepare("
            INSERT INTO prestamos_detalle
            (prestamo_id, numero_cuota, fecha_programada, capital, interes, mora, total_cuota, saldo_restante, estado)
            VALUES (?, ?, ?, ?, ?, 0, ?, 0, 'pendiente')
        ");

        $nuevoCapital = $cuotaActual['capital'];
        $nuevoInteres = $cuotaActual['interes']; // Se mantiene el interés proyectado
        $nuevaCuotaTotal = $nuevoCapital + $nuevoInteres;

        $stmtInsert->execute([
            $prestamoId,
            $nuevaNumero,
            $nuevaFecha,
            $nuevoCapital,
            $nuevoInteres,
            $nuevaCuotaTotal
        ]);

        // 5. RECALCULAR SALDOS (En base a Deuda Total)
        // User: "cuando se paga solo interes se resta... solo el valor del interes. las cuotas subsiguientes se resta la total_cuota"

        $stmtAll = $this->db->prepare("SELECT * FROM prestamos_detalle WHERE prestamo_id = ? ORDER BY numero_cuota ASC");
        $stmtAll->execute([$prestamoId]);
        $todas = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

        // Saldo Inicial = Suma de todo el Capital + Interés programado de todas las cuotas
        $totalOriginalConIntereses = 0;
        foreach ($todas as $t) {
            $totalOriginalConIntereses += ($t['capital'] + $t['interes']);
        }

        $saldoActual = $totalOriginalConIntereses;

        foreach ($todas as $c) {
            // Regla de descuento de saldo:
            if ($c['estado'] === 'parcial') {
                // Si es parcial (solo interés), el saldo baja solo por el interés de esta cuota
                $descuento = (float) $c['interes'];
            } else {
                // Si es pagado o pendiente, el saldo baja por la cuota completa
                $descuento = (float) $c['capital'] + (float) $c['interes'];
            }

            $nuevoSaldoRestante = round(max(0, $saldoActual - $descuento), 2);

            $this->db->prepare("UPDATE prestamos_detalle SET saldo_restante = ? WHERE id = ?")
                ->execute([$nuevoSaldoRestante, $c['id']]);

            $saldoActual = $nuevoSaldoRestante;
        }

        return $this->db->lastInsertId();
    }
    public function obtenerDetallePago($id)
    {
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
}
