<?php
require_once __DIR__ . '/models/Database.php';
try {
    $db = (new Database())->getConnection();
    $tables = ['prestamos', 'prestamos_detalle'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW COLUMNS FROM $table LIKE 'estado'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Table: $table, Column: estado, Type: " . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
