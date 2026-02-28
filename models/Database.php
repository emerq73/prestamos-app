<?php
class Database
{
    private $host = "localhost";
    private $db_name = "prestamos_db"; // Ajusta según tu base de datos
    private $username = "root";
    private $password = "Admin123**";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name}", $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
