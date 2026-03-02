<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Consecutivo.php';

class PagoSocio
{
    private $db;

    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = (new Database())->getConnection();
        }
    }

    public function obtenerTodos($socio_id = null)
    {
        $sql = "SELECT ps.*, s.nombre_completo as socio_nombre, s.documento as socio_documento 
                FROM pago_socios ps 
                JOIN socios s ON ps.socio_id = s.id";

        $params = [];
        if ($socio_id) {
            $sql .= " WHERE ps.socio_id = ?";
            $params[] = $socio_id;
        }

        $sql .= " ORDER BY ps.fecha_emision DESC, ps.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT ps.*, s.nombre_completo as socio_nombre, s.documento as socio_documento, 
                       s.telefono as socio_telefono, s.direccion as socio_direccion
                FROM pago_socios ps 
                JOIN socios s ON ps.socio_id = s.id 
                WHERE ps.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardar($data, $items = [])
    {
        $consecutivoModel = new Consecutivo($this->db);
        $consecutivoStr = $consecutivoModel->obtenerSiguiente('pagos_socios');

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO pago_socios (consecutivo, socio_id, mes, anio, fecha_emision, utilidades_generadas, saldo_favor_anterior, rendimiento_mensual_porc, deducciones, impuestos, ajustes, saldo_final_mes, fecha_pago, medio_pago, valor_pagado, observaciones, responsable)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $consecutivoStr,
                $data['socio_id'],
                $data['mes'],
                $data['anio'],
                $data['fecha_emision'],
                $data['utilidades_generadas'],
                $data['saldo_favor_anterior'],
                $data['rendimiento_mensual_porc'],
                $data['deducciones'],
                $data['impuestos'],
                $data['ajustes'],
                $data['saldo_final_mes'],
                $data['fecha_pago'] ?? null,
                $data['medio_pago'] ?? null,
                $data['valor_pagado'],
                $data['observaciones'] ?? null,
                $data['responsable'] ?? null
            ]);
            $pagoSocioId = $this->db->lastInsertId();

            // Guardar detalles de los préstamos liquidados
            if (!empty($items)) {
                $stmtItem = $this->db->prepare("INSERT INTO pago_socio_items (pago_socio_id, prestamo_id, aporte, tasa_socio, rendimiento, capital_devuelto, detalle) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($items as $it) {
                    $stmtItem->execute([
                        $pagoSocioId,
                        $it['prestamo_id'],
                        $it['aporte'],
                        $it['tasa_socio'],
                        $it['rendimiento'],
                        $it['capital_devuelto'] ?? 0,
                        $it['detalle'] ?? ''
                    ]);
                }
            }

            $this->db->commit();
            return $pagoSocioId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function obtenerRendimientosCalculados($socio_id, $mes_nombre, $anio)
    {
        $mes_nombre = trim($mes_nombre);
        $meses_map = [
            'enero' => '01',
            'febrero' => '02',
            'marzo' => '03',
            'abril' => '04',
            'mayo' => '05',
            'junio' => '06',
            'julio' => '07',
            'agosto' => '08',
            'septiembre' => '09',
            'octubre' => '10',
            'noviembre' => '11',
            'diciembre' => '12'
        ];
        $mes_num = $meses_map[strtolower($mes_nombre)] ?? date('m');

        file_put_contents('yields_debug.log', "Calculando para Socio: $socio_id, Mes: $mes_nombre ($mes_num), Año: $anio\n", FILE_APPEND);

        // 1. Obtener todos los préstamos vinculados al socio
        $stmt = $this->db->prepare("
            SELECT ps.*, p.consecutivo as prestamo_consecutivo, p.fecha_inicio as prestamo_fecha_inicio, p.monto as prestamo_monto
            FROM prestamo_socios ps
            JOIN prestamos p ON p.id = ps.prestamo_id
            WHERE ps.socio_id = ? AND p.estado != 'cancelado_error'
        ");
        $stmt->execute([$socio_id]);
        $vinculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        file_put_contents('yields_debug.log', "Vínculos encontrados: " . count($vinculos) . "\n", FILE_APPEND);

        $resultados = [];
        $total_utilidades = 0;

        foreach ($vinculos as $v) {
            try {
                $prestamo_id = $v['prestamo_id'];
                $aporte = (float) $v['aporte'];
                $tasa_socio = (float) $v['porcentaje_interes'];

                // 2. Buscar si hubo pagos del cliente de este préstamo en el mes
                $stmtPagos = $this->db->prepare("
                    SELECT id, tipo, fecha, monto_total 
                    FROM pagos 
                    WHERE prestamo_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ?
                    ORDER BY fecha DESC
                ");
                $stmtPagos->execute([$prestamo_id, (int) $mes_num, (int) $anio]);
                $pagos_mes = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

                file_put_contents('yields_debug.log', "Iterando PR ID: $prestamo_id, Aporte: $aporte, Pagos count: " . count($pagos_mes) . "\n", FILE_APPEND);

                if (empty($pagos_mes)) {
                    continue;
                }

                $yield_item = [
                    'prestamo_id' => $prestamo_id,
                    'prestamo_consecutivo' => $v['prestamo_consecutivo'] ?? "N/A",
                    'aporte' => $aporte,
                    'tasa_socio' => $tasa_socio,
                    'rendimiento' => 0,
                    'capital_devuelto' => 0,
                    'detalle' => ''
                ];

                // 3. Evaluar tipo de pago
                $es_total = false;
                $pago_total_data = null;
                foreach ($pagos_mes as $p) {
                    if ($p['tipo'] === 'total') {
                        $es_total = true;
                        $pago_total_data = $p;
                        break;
                    }
                }

                if ($es_total) {
                    // Cálculo para pago TOTAL: Aporte + Intereses causados a la fecha
                    $stmtUlt = $this->db->prepare("
                        SELECT fecha FROM pagos 
                        WHERE prestamo_id = ? AND fecha < ? 
                        ORDER BY fecha DESC LIMIT 1
                    ");
                    $stmtUlt->execute([$prestamo_id, $pago_total_data['fecha']]);
                    $ultimo_pago = $stmtUlt->fetch(PDO::FETCH_ASSOC);

                    $fecha_desde = $ultimo_pago ? $ultimo_pago['fecha'] : $v['prestamo_fecha_inicio'];
                    $fecha_hasta = $pago_total_data['fecha'];

                    $d1 = new DateTime($fecha_desde);
                    $d2 = new DateTime($fecha_hasta);
                    $dias = $d1->diff($d2)->days;

                    $interes_mensual = $aporte * ($tasa_socio / 100);
                    $interes_proporcional = ($interes_mensual / 30) * $dias;

                    $yield_item['rendimiento'] = round($interes_proporcional, 2);
                    $yield_item['capital_devuelto'] = $aporte;
                    $yield_item['detalle'] = "Liquidación Total ($dias días)";
                } else {
                    // Pago regular
                    $yield_item['rendimiento'] = round($aporte * ($tasa_socio / 100), 2);
                    $yield_item['detalle'] = "Rendimiento Mensual";
                }

                $total_utilidades += $yield_item['rendimiento'];
                $yield_item['total'] = $yield_item['rendimiento'] + $yield_item['capital_devuelto'];

                $resultados[] = $yield_item;

            } catch (Exception $e) {
                // Loguear error pero continuar con otros préstamos si aplica
                error_log("Error calculando rendimientos PR $prestamo_id: " . $e->getMessage());
            }
        }

        return [
            'total_utilidades' => $total_utilidades,
            'items' => $resultados
        ];
    }

    public function obtenerItems($pago_socio_id)
    {
        $stmt = $this->db->prepare("
            SELECT psi.*, p.consecutivo as prestamo_consecutivo 
            FROM pago_socio_items psi
            JOIN prestamos p ON p.id = psi.prestamo_id
            WHERE psi.pago_socio_id = ?
            ORDER BY psi.id ASC
        ");
        $stmt->execute([$pago_socio_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeParaPeriodo($socio_id, $mes, $anio)
    {
        $sql = "SELECT id FROM pago_socios WHERE socio_id = ? AND mes = ? AND anio = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$socio_id, $mes, $anio]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
