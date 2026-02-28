<?php
require_once '../models/DeudorDocumento.php';
$docModel = new DeudorDocumento();

$idDoc   = $_GET['eliminar'] ?? null;
$deudor  = $_GET['deudor']   ?? null;

if ($idDoc) {
    // Obtener info del archivo antes de borrar de la BD
    $doc = $docModel->obtenerPorId($idDoc);
    if ($doc) {
        $ruta = '../' . $doc['archivo'];
        if (file_exists($ruta)) {
            unlink($ruta); // eliminar archivo físico
        }
        $docModel->eliminar($idDoc);
    }
}

header("Location: ../views/dashboard.php?modulo=deudores/editar&id=$deudor&exito=doc_eliminado");
exit;
