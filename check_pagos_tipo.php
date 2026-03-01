<?php
require_once __DIR__ . '/models/Database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SHOW COLUMNS FROM pagos LIKE 'tipo'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Table: pagos, Column: tipo, Type: " . $row['Type'] . "\n";
