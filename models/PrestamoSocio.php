<?php
require_once __DIR__ . '/Database.php';

class PrestamoSocio {
    private $db;
    public function __construct($db = null) {
        if ($db) { $this->db = $db; return; }
        $this->db = (new Database())->getConnection();
    }

    public function crear($data) {
        $sql = "INSERT INTO prestamo_socios (prestamo_id, socio_id, aporte, porcentaje) VALUES (:prestamo_id, :socio_id, :aporte, :porcentaje)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':prestamo_id' => $data['prestamo_id'],
            ':socio_id'    => $data['socio_id'],
            ':aporte'      => $data['aporte'],
            ':porcentaje'  => $data['porcentaje']
        ]);
        return $this->db->lastInsertId();
    }

    public function obtenerPorPrestamo($prestamo_id) {
        $stmt = $this->db->prepare("SELECT ps.*, s.nombre_completo FROM prestamo_socios ps JOIN socios s ON s.id = ps.socio_id WHERE ps.prestamo_id = ? ORDER BY ps.id");
        $stmt->execute([$prestamo_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarPorPrestamo($prestamo_id) {
        $stmt = $this->db->prepare("DELETE FROM prestamo_socios WHERE prestamo_id = ?");
        return $stmt->execute([$prestamo_id]);
    }
}
