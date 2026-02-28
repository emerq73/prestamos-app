<?php
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    private $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function index()
    {
        $usuarios = $this->usuarioModel->obtenerTodos();
        require_once __DIR__ . '/../views/usuarios/index.php';
    }

    /* public function crear()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
                die('CSRF Error');
            }
            $this->usuarioModel->crear($_POST);
            header("Location: dashboard.php?modulo=usuarios&exito=creado");
            exit;
        }
    } */

        public function crear()
{
    // 👉 SI ES POST: guardar
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            die('CSRF Error');
        }

        $this->usuarioModel->crear($_POST);
        header("Location: dashboard.php?modulo=usuarios&exito=creado");
        exit;
    }

    // 👉 SI ES GET: mostrar formulario
    require_once __DIR__ . '/../views/usuarios/crear.php';
}


    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
                die('CSRF Error');
            }
            $this->usuarioModel->actualizar($_POST['id'], $_POST);
            header("Location: dashboard.php?modulo=usuarios&exito=editado");
            exit;
        }
    }

    public function eliminar()
    {
        if (isset($_GET['id'])) {
            $this->usuarioModel->eliminar($_GET['id']);
            header("Location: dashboard.php?modulo=usuarios&exito=eliminado");
            exit;
        }
    }
}
