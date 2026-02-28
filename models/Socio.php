<?php
require_once 'Database.php'; // Ajusta el nombre si tu conexión se llama diferente

class Socio
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function obtenerTodos()
    {
        $stmt = $this->conn->prepare("SELECT * FROM socios ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM socios WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $sql = "INSERT INTO socios 
        (nombre_completo, documento, telefono, email, direccion, banco, tipo_cuenta, nro_cuenta, aporte, porcentaje_participacion, observaciones, estado) 
        VALUES
        (:nombre_completo, :documento, :telefono, :email, :direccion, :banco, :tipo_cuenta, :nro_cuenta, :aporte, :porcentaje_participacion, :observaciones, :estado)";

        $stmt = $this->conn->prepare($sql);

        $params = [
            ':nombre_completo' => $data['nombre_completo'],
            ':documento' => $data['documento'],
            ':telefono' => $data['telefono'] ?? null,
            ':email' => $data['email'] ?? null,
            ':direccion' => $data['direccion'] ?? null,
            ':banco' => $data['banco'] ?? null,
            ':tipo_cuenta' => $data['tipo_cuenta'] ?? 'ahorros',
            ':nro_cuenta' => $data['nro_cuenta'] ?? null,
            ':aporte' => $data['aporte'] ?? 0,
            ':porcentaje_participacion' => $data['porcentaje_participacion'] ?? 0,
            ':observaciones' => $data['observaciones'] ?? null,
            ':estado' => $data['estado'] ?? 'activo'
        ];

        return $stmt->execute($params);
    }


    public function actualizar($id, $data)
    {
        $sql = "UPDATE socios SET 
            nombre_completo = :nombre_completo,
            documento      = :documento,
            telefono       = :telefono,
            email          = :email,
            direccion      = :direccion,
            banco          = :banco,
            tipo_cuenta    = :tipo_cuenta,
            nro_cuenta     = :nro_cuenta,
            aporte         = :aporte,
            porcentaje_participacion = :porcentaje_participacion,
            observaciones  = :observaciones,
            estado         = :estado
        WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $params = [
            ':id' => $id,
            ':nombre_completo' => $data['nombre_completo'],
            ':documento' => $data['documento'],
            ':telefono' => $data['telefono'] ?? null,
            ':email' => $data['email'] ?? null,
            ':direccion' => $data['direccion'] ?? null,
            ':banco' => $data['banco'] ?? null,
            ':tipo_cuenta' => $data['tipo_cuenta'] ?? 'ahorros',
            ':nro_cuenta' => $data['nro_cuenta'] ?? null,
            ':aporte' => $data['aporte'] ?? 0,
            ':porcentaje_participacion' => $data['porcentaje_participacion'] ?? 0,
            ':observaciones' => $data['observaciones'] ?? null,
            ':estado' => $data['estado'] ?? 'activo'
        ];

        return $stmt->execute($params);
    }

    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM socios WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
