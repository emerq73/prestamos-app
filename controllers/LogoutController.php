<?php
session_start();

// eliminar todas las variables de sesión
session_unset();

// destruir la sesión
session_destroy();

// redirigir al login del sistema
header("Location: ../views/login.php");
exit;
