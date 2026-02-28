<?php
require_once __DIR__ . '/models/Database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE prestamos_detalle");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>