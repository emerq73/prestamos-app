<?php
require_once __DIR__ . '/Database.php';

class Consecutivo
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

    public function obtenerSiguiente($tipo)
    {
        try {
            // Usamos un bloqueo para evitar colisiones en concurrencia si fuera necesario, 
            // aunque en un entorno pequeño con transacciones suele bastar.
            $stmt = $this->db->prepare("SELECT prefijo, siguiente_numero, longitud FROM consecutivos WHERE tipo_documento = ? FOR UPDATE");
            $stmt->execute([$tipo]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$res) {
                return null;
            }

            $prefijo = $res['prefijo'];
            $numero = $res['siguiente_numero'];
            $longitud = $res['longitud'];

            // Formatear el consecutivo (ej: PR-00001)
            $consecutivoStr = $prefijo . str_pad($numero, $longitud, '0', STR_PAD_LEFT);

            // Incrementar para el siguiente
            $update = $this->db->prepare("UPDATE consecutivos SET siguiente_numero = siguiente_numero + 1 WHERE tipo_documento = ?");
            $update->execute([$tipo]);

            return $consecutivoStr;

        } catch (Exception $e) {
            error_log("Error al obtener consecutivo: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerTodos()
    {
        $stmt = $this->db->query("SELECT * FROM consecutivos ORDER BY tipo_documento ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarConfiguracion($tipo, $prefijo, $siguiente, $longitud)
    {
        $stmt = $this->db->prepare("UPDATE consecutivos SET prefijo = ?, siguiente_numero = ?, longitud = ? WHERE tipo_documento = ?");
        return $stmt->execute([$prefijo, $siguiente, $longitud, $tipo]);
    }
}
