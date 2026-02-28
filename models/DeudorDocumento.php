<?php
require_once 'Database.php';

class DeudorDocumento
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function crear($data)
    {
        $sql = "INSERT INTO deudor_documentos (deudor_id, tipo, archivo) 
                VALUES (:deudor_id, :tipo, :archivo)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':deudor_id' => $data['deudor_id'],
            ':tipo'      => $data['tipo'] ?? null,
            ':archivo'   => $data['archivo']
        ]);
    }
    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM deudor_documentos WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPorDeudor($deudor_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM deudor_documentos WHERE deudor_id = :deudor_id");
        $stmt->bindParam(':deudor_id', $deudor_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM deudor_documentos WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
