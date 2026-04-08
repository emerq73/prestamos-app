<?php
require_once __DIR__ . '/../models/Deudor.php';
require_once __DIR__ . '/../models/DeudorDocumento.php';
require_once __DIR__ . '/../models/Prestamo.php';
date_default_timezone_set('America/Bogota');

class DeudorController
{
    private $deudorModel;
    private $docModel;

    public function __construct()
    {
        $this->deudorModel = new Deudor();
        $this->docModel = new DeudorDocumento();
    }

    public function index()
    {
        $deudores = $this->deudorModel->obtenerTodos();
        require_once __DIR__ . '/../views/deudores/index.php';
    }

    private function subirDocumentos($deudorID, $identificacion, $files)
    {
        require_once __DIR__ . '/../includes/GoogleDrive.php';
        try {
            $drive = new GoogleDrive();
            
            foreach ($files['name'] as $key => $name) {
                if ($files['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp = $files['tmp_name'][$key];
                    $prefix = !empty($identificacion) ? $identificacion : $deudorID;
                    $nuevoNombre = $prefix . "-" . date('Y-m-d') . "-" . basename($name);
                    
                    error_log("Subiendo archivo: $name as $nuevoNombre para deudor $deudorID");
                    
                    // Subir a Google Drive
                    $fileId = $drive->subirArchivo($tmp, $nuevoNombre);
                    
                    if ($fileId) {
                        error_log("Archivo subido a Drive con ID: $fileId");
                        $res = $this->docModel->crear([
                            'deudor_id' => $deudorID,
                            'tipo' => 'documento_drive',
                            'archivo' => $fileId
                        ]);
                        error_log("Resultado inserción BD: " . ($res ? 'éxito' : 'fallo'));
                    } else {
                        error_log("Fallo al obtener fileId de Drive");
                    }
                } else {
                    error_log("Error de upload PHP para $name: " . $files['error'][$key]);
                }
            }
        } catch (Exception $e) {
            error_log("Error subiendo a Google Drive: " . $e->getMessage());
            error_log($e->getTraceAsString());
        }
    }

    public function crear()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
                die('CSRF Error');
            }
            $id = $this->deudorModel->crear($_POST);
            if (!empty($_FILES['documentos']['name'][0])) {
                $this->subirDocumentos($id, $_POST['documento'] ?? '', $_FILES['documentos']);
            }
            header("Location: dashboard.php?modulo=deudores&exito=creado");
            exit;
        }
    }

    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
                die('CSRF Error');
            }
            $this->deudorModel->actualizar($_POST['id'], $_POST);
            if (!empty($_FILES['documentos']['name'][0])) {
                $this->subirDocumentos($_POST['id'], $_POST['documento'] ?? '', $_FILES['documentos']);
            }
            header("Location: dashboard.php?modulo=deudores&exito=editado");
            exit;
        }
    }

    public function eliminar()
    {
        if (isset($_GET['id'])) {
            $prestamoModel = new Prestamo();
            if ($prestamoModel->tienePrestamosPendientes($_GET['id'])) {
                header("Location: dashboard.php?modulo=deudores&error=tiene_prestamos");
                exit;
            }

            $this->deudorModel->eliminar($_GET['id']);
            header("Location: dashboard.php?modulo=deudores&exito=eliminado");
            exit;
        }
    }
}
