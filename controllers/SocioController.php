<?php
require_once __DIR__ . '/../models/Socio.php';

class SocioController
{
    private $socioModel;

    public function __construct()
    {
        $this->socioModel = new Socio();
    }

    public function index()
    {
        $socios = $this->socioModel->obtenerTodos();
        require_once __DIR__ . '/../views/socios/index.php';
    }

    public function crear()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
                die('CSRF Error');
            }
            $this->socioModel->crear($_POST);
            header("Location: dashboard.php?modulo=socios&exito=creado");
            exit;
        }
    }

    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
                die('CSRF Error');
            }
            $this->socioModel->actualizar($_POST['id'], $_POST);
            header("Location: dashboard.php?modulo=socios&exito=editado");
            exit;
        }
    }

    public function eliminar()
    {
        if (isset($_GET['id'])) {
            $this->socioModel->eliminar($_GET['id']);
            header("Location: dashboard.php?modulo=socios&exito=eliminado");
            exit;
        }
    }
}
