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

    private function subirDocumentos($deudorID, $files)
    {
        $dir = __DIR__ . "/../uploads/deudores/";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        foreach ($files['name'] as $key => $name) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $tmp = $files['tmp_name'][$key];
                $nuevoNombre = $deudorID . "-" . date('Y-m-d') . "-" . basename($name);
                if (move_uploaded_file($tmp, $dir . $nuevoNombre)) {
                    $this->docModel->crear([
                        'deudor_id' => $deudorID,
                        'tipo' => 'documento',
                        'archivo' => 'uploads/deudores/' . $nuevoNombre
                    ]);
                }
            }
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
                $this->subirDocumentos($id, $_FILES['documentos']);
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
                $this->subirDocumentos($_POST['id'], $_FILES['documentos']);
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
