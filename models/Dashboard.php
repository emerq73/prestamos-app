<?php
require_once __DIR__ . '/Database.php';

class Dashboard
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene las métricas globales para las tarjetas del dashboard
     */
    public function getMetrics()
    {
        $metrics = [
            'acreedores_activos' => 0,
            'prestamos_totales' => 0,
            'abonos_mes' => 0,
            'socios_activos' => 0
        ];

        // 1. Acreedores activos (Deudores con al menos un préstamo activo)
        $sql = "SELECT COUNT(DISTINCT deudor_id) FROM prestamos WHERE estado = 'activo'";
        $stmt = $this->db->query($sql);
        $metrics['acreedores_activos'] = $stmt->fetchColumn();

        // 2. Préstamos totales (Suma de montos de préstamos activos)
        $sql = "SELECT SUM(monto) FROM prestamos WHERE estado = 'activo'";
        $stmt = $this->db->query($sql);
        $metrics['prestamos_totales'] = (float) ($stmt->fetchColumn() ?? 0);

        // 3. Abonos del mes (Suma de pagos en el mes actual)
        $sql = "SELECT SUM(monto_total) FROM pagos WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())";
        $stmt = $this->db->query($sql);
        $metrics['abonos_mes'] = (float) ($stmt->fetchColumn() ?? 0);

        // 4. Socios activos
        $sql = "SELECT COUNT(*) FROM socios WHERE estado = 'activo'";
        $stmt = $this->db->query($sql);
        $metrics['socios_activos'] = $stmt->fetchColumn();

        return $metrics;
    }

    /**
     * Obtiene el reporte de rendimientos pagados para un mes y año específico
     */
    public function getYieldsReport($mes, $anio)
    {
        $sql = "SELECT ps.*, s.nombre_completo as socio_nombre 
                FROM pago_socios ps
                JOIN socios s ON ps.socio_id = s.id
                WHERE ps.mes = :mes AND ps.anio = :anio
                ORDER BY ps.fecha_pago DESC, ps.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
