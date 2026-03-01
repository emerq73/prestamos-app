<?php
require_once __DIR__ . '/models/Database.php';
try {
    $db = (new Database())->getConnection();
    $tables = ['prestamos', 'prestamos_detalle'];
    foreach ($tables as $table) {
        echo "Table: $table\n";
        $stmt = $db->query("DESCRIBE $table");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['Field'] == 'estado') {
                echo "  estado - " . $row['Type'] . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
