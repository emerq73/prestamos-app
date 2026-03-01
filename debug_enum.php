<?php
require_once __DIR__ . '/models/Database.php';
$db = (new Database())->getConnection();
foreach (['prestamos', 'prestamos_detalle'] as $t) {
    $s = $db->query("SHOW COLUMNS FROM $t LIKE 'estado'");
    $r = $s->fetch(PDO::FETCH_ASSOC);
    echo $t . ': ' . $r['Type'] . "\n";
}
