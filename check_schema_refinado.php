<?php
require_once __DIR__ . '/models/Database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SHOW COLUMNS FROM prestamos_detalle LIKE 'estado'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "COLUMN: " . $row['Field'] . "\n";
echo "TYPE: " . $row['Type'] . "\n";
?>