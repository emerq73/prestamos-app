<?php
// La variable $usuarios ya viene del Controlador
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Usuarios</h4>
        <!-- <a href="dashboard.php?modulo=usuarios/crear" class="btn btn-primary"> -->
            <a href="dashboard.php?modulo=usuarios&action=crear" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuevo usuario
        </a>
    </div>


    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= $u['nombre'] ?></td>
                    <td><?= $u['email'] ?></td>
                    <td><?= ucfirst($u['rol']) ?></td>
                    <td><?= ucfirst($u['estado']) ?></td>
                    <td>
                        <a href="dashboard.php?modulo=usuarios/editar&id=<?= $u['id'] ?>"
                            class="btn btn-sm btn-warning">Editar</a>
                        <a href="#" class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $u['id'] ?>">Eliminar</a>

                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (isset($_GET['exito'])): ?>
    <script>
        let tipo = 'success';
        let mensaje = '';

        switch ('<?= $_GET['exito'] ?>') {
            case 'creado':
                mensaje = 'Usuario creado correctamente';
                break;
            case 'editado':
                mensaje = 'Usuario actualizado con éxito';
                break;
            case 'eliminado':
                mensaje = 'Usuario eliminado correctamente';
                break;
        }

        if (mensaje) {
            Swal.fire({
                icon: tipo,
                title: '¡Éxito!',
                text: mensaje,
                timer: 2000,
                showConfirmButton: false
            });
        }
    </script>

<?php endif; ?>
<?php $baseURL = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/prestamos-app"; ?>

<script>
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');

            Swal.fire({
                title: '¿Eliminar usuario?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "dashboard.php?modulo=usuarios&action=eliminar&id=" + id;

                }
            });
        });
    });
</script>