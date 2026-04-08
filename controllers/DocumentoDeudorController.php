<?php
require_once '../models/DeudorDocumento.php';
$docModel = new DeudorDocumento();

$idDoc   = $_GET['eliminar'] ?? null;
$deudor  = $_GET['deudor']   ?? null;

if ($idDoc) {
    // Obtener info del archivo antes de borrar de la BD
    $doc = $docModel->obtenerPorId($idDoc);
    if ($doc) {
        if ($doc['tipo'] === 'documento_drive') {
            // Eliminar de Google Drive
            require_once '../includes/GoogleDrive.php';
            try {
                $drive = new GoogleDrive();
                $drive->eliminarArchivo($doc['archivo']);
            } catch (Exception $e) {
                error_log("Error eliminando de Google Drive: " . $e->getMessage());
            }
        } else {
            // Eliminar archivo físico local
            $ruta = '../' . $doc['archivo'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
        $docModel->eliminar($idDoc);
    }
}

header("Location: ../views/dashboard.php?modulo=deudores/editar&id=$deudor&exito=doc_eliminado");
exit;
