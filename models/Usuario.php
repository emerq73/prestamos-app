<?php
require_once 'Database.php';

class Usuario
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function obtenerTodos()
    {
        $stmt = $this->db->query("SELECT * FROM usuarios ORDER BY id asc");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['nombre'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['rol'],
            $data['estado']
        ]);
    }

    public function actualizar($id, $data)
    {
        $sql = "UPDATE usuarios SET nombre=?, email=?, rol=?, estado=?";
        $params = [$data['nombre'], $data['email'], $data['rol'], $data['estado']];

        if (!empty($data['password'])) {
            $sql .= ", password=?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id=?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
