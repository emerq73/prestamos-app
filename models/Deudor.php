<?php
require_once 'Database.php';

class Deudor
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Crear deudor y devolver el ID insertado
    public function crear($data)
    {
        $sql = "INSERT INTO deudores (nombre_completo, documento, telefono, direccion, email, observaciones, estado)
                VALUES (:nombre_completo, :documento, :telefono, :direccion, :email, :observaciones, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre_completo' => $data['nombre_completo'],
            ':documento' => $data['documento'],
            ':telefono' => $data['telefono'] ?? null,
            ':direccion' => $data['direccion'] ?? null,
            ':email' => $data['email'] ?? null,
            ':observaciones' => $data['observaciones'] ?? null,
            ':estado' => $data['estado'] ?? 'activo'
        ]);
        return $this->db->lastInsertId();
    }

    // Obtener todos los deudores
    public function obtenerTodos()
    {
        $stmt = $this->db->query("SELECT * FROM deudores ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM deudores WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizar($id, $data)
    {
        $sql = "UPDATE deudores SET 
            nombre_completo = :nombre_completo, 
            documento       = :documento, 
            telefono        = :telefono, 
            direccion       = :direccion, 
            email           = :email,
            observaciones   = :observaciones,
            estado          = :estado 
        WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre_completo' => $data['nombre_completo'],
            ':documento' => $data['documento'],
            ':telefono' => $data['telefono'] ?? null,
            ':direccion' => $data['direccion'] ?? null,
            ':email' => $data['email'] ?? null,
            ':observaciones' => $data['observaciones'] ?? null,
            ':estado' => $data['estado'] ?? 'activo',
            ':id' => $id
        ]);
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM deudores WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
