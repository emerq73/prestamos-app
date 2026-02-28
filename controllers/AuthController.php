<?php

class AuthController
{
    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // require_once __DIR__ . '/../config/database.php';
        $pdo = require __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../includes/Csrf.php';

        


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../views/login.php');
            exit;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Error CSRF';
            header('Location: ../views/login.php');
            exit;
        }

        

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['usuario'] = $user;
            header('Location: ../views/dashboard.php');
            exit;
        }

        $_SESSION['error'] = 'Credenciales incorrectas';
        header('Location: ../views/login.php');
        header('Location: ../views/login.php');
        exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

