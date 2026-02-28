<?php
require_once __DIR__ . '/models/Database.php';
try {
    $db = (new Database())->getConnection();
    // Agregamos 'solo_interes' al ENUM de estado
    $sql = "ALTER TABLE prestamos_detalle MODIFY COLUMN estado ENUM('pendiente', 'pagado', 'atrasado', 'parcial', 'solo_interes') DEFAULT 'pendiente'";
    $db->exec($sql);
    echo "SUCCESS: Column 'estado' updated.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>