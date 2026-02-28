<?php
require_once __DIR__ . '/Database.php';

class PrestamoDetalle
{
    private $conn;

    public function __construct($db = null)
    {
        if ($db) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    /** Crear una cuota */
    public function crear($data)
    {
        $sql = "INSERT INTO prestamos_detalle
            (prestamo_id, numero_cuota, fecha_programada, capital, interes, mora,pagado_capital, pagado_interes, total_cuota, saldo_restante, estado)
            VALUES (:prestamo_id, :numero_cuota, :fecha_programada, :capital, :interes, :mora, 0,0,:total_cuota, :saldo_restante, :estado)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':prestamo_id'     => $data['prestamo_id'],
            ':numero_cuota'    => $data['numero_cuota'],
            ':fecha_programada' => $data['fecha_programada'],
            ':capital'         => $data['capital'],
            ':interes'         => $data['interes'],
            ':mora'            => $data['mora'],
            ':total_cuota'     => $data['total_cuota'],
            ':saldo_restante'  => $data['saldo_restante'],
            ':estado'          => $data['estado'],
        ]);
        return $this->conn->lastInsertId();
    }


    /** Obtener cuotas por préstamo */
    public function obtenerPorPrestamo($prestamo_id)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM prestamos_detalle 
            WHERE prestamo_id = ? 
            ORDER BY fecha_programada ASC
        ");
        $stmt->execute([$prestamo_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Obtener cuota por ID */
    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM prestamos_detalle WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function eliminarPorPrestamo($prestamo_id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM prestamos_detalle WHERE prestamo_id = ?"
        );
        return $stmt->execute([$prestamo_id]);
    }


    /** Actualizar cualquier campo */
    public function actualizar($id, $data)
    {
        $set = [];
        $params = [':id' => $id];

        foreach ($data as $k => $v) {
            $set[] = "$k = :$k";
            $params[":$k"] = $v;
        }

        $sql = "UPDATE prestamos_detalle SET " . implode(", ", $set) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /** Marcar como pagado o parcial (compatibilidad) */
    public function marcarPagadoParcial($id)
    {
        $cuota = $this->obtenerPorId($id);
        if (!$cuota) return false;

        $estado = (
            $cuota['pagado_capital'] >= $cuota['capital'] &&
            $cuota['pagado_interes'] >= $cuota['interes']
        ) ? 'pagada' : 'parcial';

        return $this->actualizar($id, [
            'estado' => $estado
        ]);
    }

    /** Crear nueva cuota extra (usado en pagos solo interes) */
    public function crearCuotaExtra($prestamoId, $capital, $interes, $fechaBase)
    {
        $nuevaFecha = date('Y-m-d', strtotime($fechaBase . " +30 days"));

        $sql = "INSERT INTO prestamos_detalle
                (prestamo_id, fecha, capital, interes, pagado_capital, pagado_interes, estado)
                VALUES (?, ?, ?, ?, 0, 0, 'pendiente')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$prestamoId, $nuevaFecha, $capital, $interes]);

        return $this->conn->lastInsertId();
    }

    /** Obtener última cuota del préstamo */
    public function obtenerUltimaCuota($prestamoId)
    {
        $sql = "SELECT * FROM prestamos_detalle WHERE prestamo_id = ? ORDER BY fecha DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$prestamoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
