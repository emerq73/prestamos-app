<?php
require_once __DIR__ . '/../../models/Usuario.php';
$baseURL = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/prestamos-app";

$usuarioModel = new Usuario();
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='alert alert-danger'>ID no proporcionado.</div>";
    exit;
}

$usuario = $usuarioModel->obtenerPorId($id);
?>

<div class="card shadow">
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-pencil-square me-2"></i> Editar usuario
    </div>
    <div class="card-body">
        <form action="dashboard.php?modulo=usuarios&action=editar" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" name="nombre" class="form-control" value="<?= $usuario['nombre'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" value="<?= $usuario['email'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cambiar contraseña</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control">
                        <span class="input-group-text">
                            <i class="bi bi-eye-slash toggle-password" style="cursor: pointer;"></i>
                        </span>
                    </div>
                    <small class="text-muted">Dejar en blanco para mantener la actual</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rol</label>
                    <select name="rol" class="form-select" required>
                        <option value="admin" <?= $usuario['rol'] == "admin" ? 'selected' : '' ?>>Administrador</option>
                        <option value="socio" <?= $usuario['rol'] == "socio" ? 'selected' : '' ?>>Socio</option>
                        <option value="operador" <?= $usuario['rol'] == "operador" ? 'selected' : '' ?>>Operador</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" required>
                        <option value="activo" <?= $usuario['estado'] == "activo" ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= $usuario['estado'] == "inactivo" ? 'selected' : '' ?>>Inactivo
                        </option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="dashboard.php?modulo=usuarios" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="btn-guardar" disabled>
                    <i class="bi bi-check-circle"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'email_duplicado'): ?>
            < script >
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Correo duplicado',
                    text: 'El correo electrónico ya existe asociado a otro usuario activo.',
                    confirmButtonColor: '#0d6efd'
                });
            });
    </script>
<?php endif; ?>
const form = document.querySelector('form');
const btnGuardar = document.getElementById('btn-guardar');

// Almacenar valores iniciales
const initialValues = {};
const inputs = form.querySelectorAll('input, select, textarea');

inputs.forEach(input => {
if (input.type !== 'hidden' && input.type !== 'submit') {
initialValues[input.name] = input.value;
}
});

// Función para verificar cambios
const checkChanges = () => {
let hasChanges = false;
inputs.forEach(input => {
if (input.type !== 'hidden' && input.type !== 'submit') {
if (input.value !== initialValues[input.name]) {
hasChanges = true;
}
}
});
btnGuardar.disabled = !hasChanges;
};

// Listeners para detectar cambios
inputs.forEach(input => {
input.addEventListener('input', checkChanges);
input.addEventListener('change', checkChanges);
});

document.querySelectorAll('.toggle-password').forEach(icon => {
icon.addEventListener('click', () => {
const input = icon.closest('.input-group').querySelector('input');
input.type = input.type === 'password' ? 'text' : 'password';
icon.classList.toggle('bi-eye');
icon.classList.toggle('bi-eye-slash');
});
});
</script>