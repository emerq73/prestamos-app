<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Csrf.php';
require_once __DIR__ . '/../includes/utils.php';

// ---------------------------------------------------------
// ROUTER
// ---------------------------------------------------------
$modulo = $_GET['modulo'] ?? 'inicio';
$action = $_GET['action'] ?? null;

if (!preg_match('#^[a-zA-Z0-9_/-]+$#', $modulo))
    $modulo = 'inicio';
if ($action !== null && !preg_match('#^[a-zA-Z0-9_/-]+$#', $action))
    $action = null;

// 🔐 PROTECCIÓN DE SESIÓN
if ($modulo !== 'auth' && !isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// ---------------------------------------------------------
// DATOS DE USUARIO
// ---------------------------------------------------------
$usuarioId = $_SESSION['usuario']['id'] ?? 0;
$usuarioNombre = $_SESSION['usuario']['nombre'] ?? '';
$usuario = $_SESSION['usuario']['nombre'] ?? '';
$usuarioRol = $_SESSION['usuario']['rol'] ?? 'Usuario';

$csrf_token = Csrf::getToken();

$avatarColor = match ($usuarioRol) {
    'Administrador' => 'dc3545',
    'Socio' => '198754',
    'Operador' => '0dcaf0',
    default => '0d6efd'
};

$avatarUrl = "https://ui-avatars.com/api/?" . http_build_query([
    'name' => $usuarioNombre,
    'background' => $avatarColor,
    'color' => 'ffffff',
    'size' => 64,
    'rounded' => true,
    'bold' => true
]);

// ---------------------------------------------------------
// CONTROLADOR
// ---------------------------------------------------------
$moduloCarpeta = strtolower($modulo);

$controllerBase = $modulo;
$pluralExceptions = ['prestamos', 'pagos', 'reportes', 'auth'];

if (!in_array($modulo, $pluralExceptions)) {
    $controllerBase = ($modulo === 'deudores') ? 'deudor' : rtrim($modulo, 's');
}

if ($modulo === 'reportes') {
    $controllerName = 'ReportesController'; // Forzado a plural con 's'
    $controllerFile = __DIR__ . "/../controllers/$controllerName.php";
} else {
    $controllerName = ucfirst($controllerBase) . 'Controller';
    $controllerFile = __DIR__ . "/../controllers/$controllerName.php";
}
// ---------------------------------------------------------
// DISPATCH & OUTPUT BUFFERING
// ---------------------------------------------------------
ob_start();

if ($modulo === 'inicio') {
    include __DIR__ . '/inicio.php';
} elseif ($modulo === 'auth' && $action === 'logout') {
    require_once $controllerFile;
    if (class_exists($controllerName)) {
        (new $controllerName())->logout();
    }
} elseif ($modulo === 'auth' && $action === 'login') {
    require_once $controllerFile;
    if (class_exists($controllerName)) {
        (new $controllerName())->login();
    }
} else {
    // 1. Intentar cargar controlador
    if (file_exists($controllerFile)) {
        require_once $controllerFile;

        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            $method = $action ?? 'index';

            if (method_exists($controller, $method)) {
                $controller->$method();
            } else {
                echo "<div class='alert alert-danger'>Método '$method' no existe en $controllerName</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Clase $controllerName no encontrada</div>";
        }
    } else {
        // 2. Si no hay controlador, intentar cargar vista directa
        $rutaIndex = __DIR__ . "/$moduloCarpeta/index.php";
        $rutaPlano = __DIR__ . "/$moduloCarpeta.php";

        if (file_exists($rutaIndex)) {
            include $rutaIndex;
        } elseif (file_exists($rutaPlano)) {
            include $rutaPlano;
        } else {
            echo "<div class='alert alert-danger'>Módulo no encontrado: " . htmlspecialchars($modulo) . "</div>";
        }
    }
}

$content = ob_get_clean();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Control - LdHoldings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Theme Detection Script (Prevent flashing) -->
    <script>
        (() => {
            const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
        }

        .sidebar {
            width: 260px;
            background: #141b2d;
            /* Fixed Darker Sidebar */
            color: #fff;
            transition: .3s;
            z-index: 1000;
        }

        /* Override sidebar colors for both themes to maintain branding */
        [data-bs-theme="light"] .sidebar {
            background: #0d6efd;
        }

        .sidebar-collapsed {
            width: 80px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
        }

        .sidebar a i {
            min-width: 22px;
        }

        .sidebar-collapsed span {
            display: none;
        }

        .sidebar-collapsed img {
            max-width: 40px !important;
        }

        .sidebar-collapsed .fw-bold {
            display: none;
        }

        .sidebar a:hover,
        .sidebar .active {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-weight: bold;
        }

        [data-bs-theme="light"] .sidebar a:hover,
        [data-bs-theme="light"] .sidebar .active {
            background: #0b5ed7;
        }

        /* 🔹 SUBMENU STYLES */
        .sidebar-submenu {
            background: rgba(0, 0, 0, 0.2);
            padding-left: 15px;
            display: none;
        }

        .sidebar-submenu.show {
            display: block;
        }

        .sidebar-submenu a {
            padding: 8px 20px;
            font-size: 0.9rem;
        }

        [data-bs-theme="light"] .sidebar-submenu {
            background: rgba(0, 0, 0, 0.05);
        }

        .sidebar a[data-bs-toggle="collapse"]::after {
            content: "\F282";
            font-family: "bootstrap-icons";
            margin-left: auto;
            transition: 0.3s;
        }

        .sidebar a[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }

        .sidebar-collapsed .sidebar-submenu,
        .sidebar-collapsed a[data-bs-toggle="collapse"]::after {
            display: none !important;
        }

        .topbar {
            background: var(--bs-body-bg);
            padding: 10px 20px;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .content {
            padding: 20px;
        }

        .theme-toggle-btn {
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: 0.3s;
        }

        .theme-toggle-btn:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        [data-bs-theme="dark"] .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>
    <div class="d-flex" style="min-height: 100vh;">

        <!-- SIDEBAR -->
        <div class="sidebar p-3 d-flex flex-column" id="sidebar">

            <div class="text-center mb-4">
                <img src="../assets/logo.png" class="img-fluid mb-2" style="max-width:120px;">
                <div class="fw-bold"><?= htmlspecialchars($usuarioNombre) ?></div>
                <div class="small opacity-75"><?= ucfirst(htmlspecialchars($usuarioRol)) ?></div>
            </div>

            <!-- 🔹 OPCIONES -->
            <div class="flex-grow-1">
                <?php if ($usuarioRol === 'socio'): ?>
                    <!-- Opciones para Socio: Directas y al Inicio -->
                    <a href="dashboard.php?modulo=reportes&action=pagos_socios"
                        class="<?= (($modulo === 'reportes' || $modulo === 'reportes_pagos_socios') && $action === 'pagos_socios') ? 'active' : '' ?>">
                        <i class="bi bi-list-ul"></i><span class="ms-2">Rendimientos pagados</span>
                    </a>
                    <a href="dashboard.php?modulo=reportes&action=portafolios_inversiones"
                        class="<?= (($modulo === 'reportes' || $modulo === 'reportes_portafolios_inversiones') && $action === 'portafolios_inversiones') ? 'active' : '' ?>">
                        <i class="bi bi-wallet2"></i><span class="ms-2">Portafolios de Inversión</span>
                    </a>
                <?php elseif ($usuarioRol === 'operador'): ?>
                        <!-- Opciones para Operador: Solo Préstamos y Pagos -->
                        <a href="dashboard.php?modulo=prestamos" class="<?= $modulo === 'prestamos' ? 'active' : '' ?>">
                            <i class="bi bi-cash-coin"></i><span class="ms-2">Préstamos</span>
                        </a>
                        <a href="dashboard.php?modulo=pagos" class="<?= $modulo === 'pagos' ? 'active' : '' ?>">
                            <i class="bi bi-credit-card"></i><span class="ms-2">Pagos</span>
                        </a>
                <?php else: ?>
                    <!-- Menú estándar para Admin/Operador -->
                    <a href="dashboard.php" class="<?= $modulo === 'inicio' ? 'active' : '' ?>">
                        <i class="bi bi-house-door"></i><span class="ms-2">Inicio</span>
                    </a>
                    <a href="dashboard.php?modulo=consecutivos" class="<?= $modulo === 'consecutivos' ? 'active' : '' ?>">
                        <i class="bi bi-hash"></i><span class="ms-2">Consecutivos</span>
                    </a>
                    <a href="dashboard.php?modulo=usuarios" class="<?= $modulo === 'usuarios' ? 'active' : '' ?>">
                        <i class="bi bi-person-lines-fill"></i><span class="ms-2">Usuarios</span>
                    </a>
                    <a href="dashboard.php?modulo=socios" class="<?= $modulo === 'socios' ? 'active' : '' ?>">
                        <i class="bi bi-piggy-bank"></i><span class="ms-2">Socios</span>
                    </a>
                    <a href="dashboard.php?modulo=deudores" class="<?= $modulo === 'deudores' ? 'active' : '' ?>">
                        <i class="bi bi-people-fill"></i><span class="ms-2">Acreedores</span>
                    </a>
                    <a href="dashboard.php?modulo=prestamos" class="<?= $modulo === 'prestamos' ? 'active' : '' ?>">
                        <i class="bi bi-cash-coin"></i><span class="ms-2">Préstamos</span>
                    </a>
                    <a href="dashboard.php?modulo=pagos" class="<?= $modulo === 'pagos' ? 'active' : '' ?>">
                        <i class="bi bi-credit-card"></i><span class="ms-2">Pagos</span>
                    </a>

                    <a href="dashboard.php?modulo=reportes" class="<?= $modulo === 'reportes' ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-bar-graph"></i><span class="ms-2">Reportes</span>
                    </a>
                    <div class="collapse <?= $modulo === 'reportes' ? 'show' : '' ?> sidebar-submenu" id="submenuReportes">
                        <a href="dashboard.php?modulo=reportes&action=nuevo_pago_socio"
                            class="<?= ($modulo === 'reportes' && ($action === 'nuevo_pago_socio')) ? 'fw-bold text-white' : '' ?>">
                            <i class="bi bi-plus-circle"></i><span class="ms-2">Liquidar Rendimientos</span>
                        </a>
                        <a href="dashboard.php?modulo=reportes&action=pagos_socios"
                            class="<?= ($modulo === 'reportes' && ($action === 'pagos_socios')) ? 'fw-bold text-white' : '' ?>">
                            <i class="bi bi-list-ul"></i><span class="ms-2">Rendimientos pagados</span>
                        </a>
                        <a href="dashboard.php?modulo=reportes&action=portafolios_inversiones"
                            class="<?= ($modulo === 'reportes' && ($action === 'portafolios_inversiones')) ? 'fw-bold text-white' : '' ?>">
                            <i class="bi bi-wallet2"></i><span class="ms-2">Portafolios de Inversión</span>
                        </a>
                        <a href="dashboard.php?modulo=reportes&action=reporte_general_prestamos"
                            class="<?= ($modulo === 'reportes' && ($action === 'reporte_general_prestamos')) ? 'fw-bold text-white' : '' ?>">
                            <i class="bi bi-file-earmark-bar-graph"></i><span class="ms-2">Reporte Gral Préstamos</span>
                        </a>
                        <a href="dashboard.php?modulo=reportes&action=reporte_general_pagos"
                            class="<?= ($modulo === 'reportes' && ($action === 'reporte_general_pagos')) ? 'fw-bold text-white' : '' ?>">
                            <i class="bi bi-cash-stack"></i><span class="ms-2">Reporte Gral Pagos</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <hr>
            <a h ref="#" class="btn-logout">
                <i class="bi bi-box-arrow-left"></i><span class="ms-2">Cerrar sesión</span>
            </a>
        </div>

        <!-- MAIN -->
        <div class="flex-grow-1 d-flex flex-column">

            <!-- TOPBAR -->
            <div class="topbar d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">
                    <!-- ⭐ BOTÓN COLAPSAR -->
                    <but ton class="btn btn-outline-secondary btn-sm me-3" id="toggleSidebar">
                        <i class="bi bi-list"></i>
                        </button>

                        <!-- ⭐ THEME TOGGLE -->
                        <div class="theme-toggle-btn btn border-0" id="themeToggle" title="Cambiar tema">
                            <i class="bi bi-sun-fill d-none" id="theme-icon-light"></i>
                            <i class="bi bi-moon-stars-fill" id="theme-icon-dark"></i>
                        </div>
                </div>

                <!-- ⭐ DROPDOWN PERFIL -->
                <div class="dropdown">
                    <a h ref="#" data-bs-toggle="dropdown" class="d-flex align-items-center text-decoration-none">
                        <img src="<?= $avatarUrl ?>" width="40" height="40" class="rounded-circle me-2">
                        <div class="d-none d-sm-flex flex-column text-start">
                            <strong class="text-body lh-1"><?= htmlspecialchars($usuarioNombre) ?></strong>
                            <sma ll class="text-muted" style="font-size: 0.75rem;">
                                <?= ucfirst(htmlspecialchars($usuarioRol)) ?></small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">

                        <li class="dropdown-header text-uppercase small fw-bold"><?= htmlspecialchars($usuarioRol) ?>
                        </li>
                        <li>

                            <a class="dropdown-item" href="dashboard.php?modulo=usuarios/editar&id=<?= $usuarioId ?>">
                                <i class="bi bi-person-gear me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item btn-logout text-danger" href="#">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- CONTENT -->
            <div class="content flex-grow-1">
                <?php
                if (isset($content)) {
                    echo $content;
                }
                ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ⭐ THEME LOGIC
        const themeToggle = document.getElementById('themeToggle');
        const lightIcon = document.getElementById('theme-icon-light');
        const darkIcon = document.getElementById('theme-icon-dark');

        const updateIcons = (theme) => {
            if (theme === 'dark') {
                lightIcon.classList.remove('d-none');
                darkIcon.classList.add('d-none');
            } else {
                lightIcon.classList.add('d-none');
                darkIcon.classList.remove('d-none');
            }
        };

        // Initialize icons
        updateIcons(document.documentElement.getAttribute('data-bs-theme'));

        themeToggle.onclick = () => {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcons(newTheme);
        };

        // ⭐ COLAPSAR SIDEBAR
        document.getElementById('toggleSidebar').onclick = () => {
            document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
        };

        // LOGOUT
        document.querySelectorAll('.btn-logout').forEach(btn => {
            btn.onclick = e => {
                e.preventDefault();
                Swal.fire({
                    title: '¿Cerrar sesión?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, salir',
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#fff',
                    color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#f8fafc' : '#1e293b'
                }).then(r => {
                    if (r.isConfirmed) {
                        location.href = 'dashboard.php?modulo=auth&action=logout';
                    }
                });
            };
        });
    </script>
</body>

</html>