<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../includes/Csrf.php';
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Iniciar sesión</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css"
    rel="stylesheet">

  <script>
    (() => {
      const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: 'Inter', 'Segoe UI', sans-serif;
      transition: background 0.3s;
    }

    [data-bs-theme="dark"] body {
      background: linear-gradient(135deg, #020617 0%, #0f172a 100%);
    }

    .login-card {
      width: 100%;
      max-width: 440px;
      background-color: var(--bs-body-bg);
      border-radius: 20px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      padding: 3rem 2.5rem;
      border: 1px solid var(--bs-border-color-translucent);
    }

    .form-label {
      font-weight: 500;
      font-size: 0.9rem;
      color: var(--bs-secondary-color);
      margin-bottom: 0.5rem;
    }

    .btn-primary {
      font-weight: 600;
      padding: 0.8rem;
      border-radius: 10px;
      background-color: #0d6efd;
      border: none;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .btn-primary:hover {
      background-color: #0b5ed7;
      transform: translateY(-1px);
    }

    .input-group-text {
      background-color: var(--bs-tertiary-bg);
      border-right: 0;
      color: var(--bs-secondary-color);
      border-radius: 10px 0 0 10px;
    }

    .form-control {
      border-left: 0;
      background-color: var(--bs-body-bg);
      color: var(--bs-body-color);
      border-radius: 0 10px 10px 0;
      padding: 0.75rem 1rem;
    }

    .form-control:focus {
      background-color: var(--bs-body-bg);
      color: var(--bs-body-color);
      box-shadow: none;
      border-color: #0d6efd;
    }

    .login-title {
      font-weight: 700;
      color: var(--bs-body-color);
      letter-spacing: -0.025em;
    }

    .login-subtitle {
      color: var(--bs-secondary-color);
      font-weight: 400;
    }

    /* 🌀 LOADING OVERLAY */
    #loadingOverlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(8px);
      display: none;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      color: #fff;
      transition: all 0.3s ease;
    }

    /* 🌀 CUSTOM DOTS SPINNER (Matches image) */
    .spinner-container {
      position: relative;
      width: 80px;
      height: 80px;
      margin: 0 auto;
    }

    .spinner-dot {
      position: absolute;
      width: 100%;
      height: 100%;
      animation: spinner-rotate 4s infinite linear;
      /* Significantly slower */
    }

    .spinner-dot::before {
      content: '';
      display: block;
      margin: 0 auto;
      width: 15%;
      height: 15%;
      background-color: #fff;
      border-radius: 100%;
      animation: spinner-dot-fade 4s infinite ease-in-out both;
      /* Significantly slower */
    }

    /* Create 8 dots with varying sizes and delays */
    .spinner-dot:nth-child(1) {
      transform: rotate(0deg);
    }

    .spinner-dot:nth-child(2) {
      transform: rotate(45deg);
    }

    .spinner-dot:nth-child(3) {
      transform: rotate(90deg);
    }

    .spinner-dot:nth-child(4) {
      transform: rotate(135deg);
    }

    .spinner-dot:nth-child(5) {
      transform: rotate(180deg);
    }

    .spinner-dot:nth-child(6) {
      transform: rotate(225deg);
    }

    .spinner-dot:nth-child(7) {
      transform: rotate(270deg);
    }

    .spinner-dot:nth-child(8) {
      transform: rotate(315deg);
    }

    .spinner-dot:nth-child(1)::before {
      animation-delay: -2.18s;
      width: 22%;
      height: 22%;
    }

    .spinner-dot:nth-child(2)::before {
      animation-delay: -1.9s;
      width: 19%;
      height: 19%;
    }

    .spinner-dot:nth-child(3)::before {
      animation-delay: -1.63s;
      width: 16%;
      height: 16%;
    }

    .spinner-dot:nth-child(4)::before {
      animation-delay: -1.36s;
      width: 13%;
      height: 13%;
    }

    .spinner-dot:nth-child(5)::before {
      animation-delay: -1.09s;
      width: 10%;
      height: 10%;
    }

    .spinner-dot:nth-child(6)::before {
      animation-delay: -0.81s;
      width: 8%;
      height: 8%;
    }

    .spinner-dot:nth-child(7)::before {
      animation-delay: -0.54s;
      width: 7%;
      height: 7%;
    }

    .spinner-dot:nth-child(8)::before {
      animation-delay: -0.27s;
      width: 6%;
      height: 6%;
    }

    @keyframes spinner-rotate {
      100% {
        transform: rotate(360deg);
      }
    }

    @keyframes spinner-dot-fade {

      0%,
      39%,
      100% {
        opacity: 0.3;
        transform: scale(0.6);
      }
    }

    .loading-text {
      margin-top: 2rem;
      font-size: 1.2rem;
      font-weight: 500;
      letter-spacing: 0.05em;
      color: rgba(255, 255, 255, 0.9);
      animation: pulse 4s infinite;
      /* Synchronized with spinner */
    }

    @keyframes pulse {
      0% {
        opacity: 0.4;
      }

      50% {
        opacity: 1;
      }

      100% {
        opacity: 0.4;
      }
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

  <!-- 🌀 Overlay de Carga -->
  <div id="loadingOverlay">
    <div class="loader-content">
      <div class="spinner-container">
        <div class="spinner-dot"></div>
        <div class="spinner-dot"></div>
        <div class="spinner-dot"></div>
        <div class="spinner-dot"></div>
        <div class="spinner-dot"></div>
        <div class="spinner-dot"></div>
        <div class="spinner-dot"></div>
        <div class="spinner-dot"></div>
      </div>
      <div class="loading-text">Ingresando... por favor espere</div>
    </div>
  </div>

  <div class="login-card">
    <div class="text-center mb-4">
      <img src="../assets/logo.png" class="img-fluid"
        style="max-width: 140px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
    </div>
    <h3 class="text-center login-title mb-1">Bienvenido</h3>
    <p class="text-center login-subtitle mb-4">Ingresa tus credenciales para continuar</p>

    <?php if (isset($_SESSION['error'])): ?>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          Swal.fire({
            icon: 'error',
            title: 'Error de Acceso',
            text: '<?= $_SESSION['error']; ?>',
            confirmButtonColor: '#0d6efd',
            background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#fff',
            color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#f8fafc' : '#1e293b'
          });
        });
      </script>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="dashboard.php?modulo=auth&action=login" method="POST" autocomplete="off">
      <!-- <form action="../controllers/AuthController.php" method="POST"> -->

      <!-- Dummy fields to trick browser autocomplete -->
      <input type="text" name="dummy_user" style="display:none;" aria-hidden="true" autocomplete="off">
      <input type="password" name="dummy_pass" style="display:none;" aria-hidden="true" autocomplete="off">

      <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">

      <!-- Correo -->
      <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
          <input type="email" id="email" name="email" class="form-control" placeholder="ej: admin@demo.com" required
            autocomplete="off">
        </div>
      </div>

      <!-- Contraseña -->
      <div class="mb-4">
        <label for="password" class="form-label">Contraseña</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
          <input type="password" id="password" name="password" class="form-control" placeholder="********" required
            autocomplete="new-password">
          <span class="input-group-text toggle-password" onclick="togglePassword()" style="cursor: pointer;">
            <i class="bi bi-eye-slash-fill" id="eyeIcon"></i>
          </span>
        </div>
      </div>

      <!-- Botón -->
      <div class="d-grid">
        <button class="btn btn-primary btn-lg" type="submit" id="btnIngresar">
          <i class="bi bi-box-arrow-in-right me-2"></i> Ingresar
        </button>
      </div>
    </form>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById("password");
      const icon = document.getElementById("eyeIcon");
      const type = input.getAttribute("type");

      if (type === "password") {
        input.setAttribute("type", "text");
        icon.classList.remove("bi-eye-slash-fill");
        icon.classList.add("bi-eye-fill");
      } else {
        input.setAttribute("type", "password");
        icon.classList.remove("bi-eye-fill");
        icon.classList.add("bi-eye-slash-fill");
      }
    }

    // 🚀 Mostrar Spinner al enviar formulario con delay artificial para visibilidad
    const loginForm = document.querySelector('form');
    loginForm.addEventListener('submit', function (e) {
      e.preventDefault(); // Detener envío inmediato

      const overlay = document.getElementById('loadingOverlay');
      overlay.style.display = 'flex';

      // Deshabilitar botón para evitar múltiples clics
      const btn = document.getElementById('btnIngresar');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Procesando...';

      // Delay de 5 segundos para que sea totalmente visible y majestuoso
      setTimeout(() => {
        loginForm.submit();
      }, 5000);
    });
  </script>

</body>

</html>