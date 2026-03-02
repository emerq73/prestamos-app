<?php
$baseURL = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/prestamos-app";
?>
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-person-plus me-2"></i> Registrar nuevo usuario
    </div>
    <div class="card-body">
        <form action="dashboard.php?modulo=usuarios&action=crear" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre completo</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" required autocomplete="off">
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" required autocomplete="off">
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required
                            autocomplete="new-password">
                        <span class="input-group-text">
                            <i class="bi bi-eye-slash toggle-password" style="cursor: pointer;"></i>
                        </span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="rol" class="form-label">Rol</label>
                    <select name="rol" id="rol" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="admin">Administrador</option>
                        <option value="socio">Socio</option>
                        <option value="operador">Operador</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select" required>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="dashboard.php?modulo=usuarios" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar usuario
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'email_duplicado'): ?>
    <script>
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

<script>
    // Mostrar/ocultar contraseña
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', () => {
            const input = icon.closest('.input-group').querySelector('input');
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    });
</script>