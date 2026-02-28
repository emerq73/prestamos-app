<?php
require_once __DIR__ . '/Database.php';

class PrestamoSocioDetalle {
    private $db;

    // Se puede pasar la conexión PDO si ya existe
    public function __construct($db = null) {
        if ($db instanceof PDO) {
            $this->db = $db;
            return;
        }
        $this->db = (new Database())->getConnection();
    }

    /**
     * Crear un registro de distribución de cuota por socio
     * $data = [
     *   'prestamos_detalle_id' => int,
     *   'socio_id' => int,
     *   'aporte_capital' => float,
     *   'aporte_interes' => float
     * ]
     */
    public function crear($data) {
        $sql = "INSERT INTO prestamo_socios_detalle (prestamos_detalle_id, socio_id, aporte_capital, aporte_interes)
                VALUES (:prestamos_detalle_id, :socio_id, :aporte_capital, :aporte_interes)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':prestamos_detalle_id' => $data['prestamos_detalle_id'],
            ':socio_id'             => $data['socio_id'],
            ':aporte_capital'       => $data['aporte_capital'],
            ':aporte_interes'       => $data['aporte_interes']
        ]);
        return $this->db->lastInsertId();
    }

    public function obtenerPorDetalle($detalle_id) {
        $stmt = $this->db->prepare("SELECT * FROM prestamo_socio_detalle WHERE prestamos_detalle_id = ?");
        $stmt->execute([$detalle_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarPorPrestamo($prestamo_id) {
        // Si tu estructura guarda referencia al prestamo en la tabla detalle -> unir y borrar
        $sql = "DELETE sd FROM prestamo_socio_detalle sd
                JOIN prestamos_detalle pd ON pd.id = sd.prestamos_detalle_id
                WHERE pd.prestamo_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$prestamo_id]);
    }
}
