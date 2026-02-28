<?php
// $socios viene del controlador
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Socios</h4>
        <a href="dashboard.php?modulo=socios/crear" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuevo socio
        </a>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nombre completo</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Aporte</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($socios as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><?= $s['nombre_completo'] ?></td>
                    <td><?= $s['documento'] ?></td>
                    <td><?= $s['telefono'] ?></td>
                    <td>$ <?= number_format($s['aporte'], 0) ?></td>
                    <td><?= ucfirst($s['estado']) ?></td>
                    <td>
                        <a href="dashboard.php?modulo=socios/editar&id=<?= $s['id'] ?>"
                            class="btn btn-sm btn-warning">Editar</a>
                        <a href="#" class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $s['id'] ?>">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (isset($_GET['exito'])): ?>
    <script>
        let m = '';
        switch ('<?= $_GET['exito'] ?>') {
            case 'creado':
                m = 'Socio registrado correctamente';
                break;
            case 'editado':
                m = 'Socio actualizado con éxito';
                break;
            case 'eliminado':
                m = 'Socio eliminado correctamente';
                break;
        }
        if (m) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: m,
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
            const id = this.dataset.id;
            Swal.fire({
                title: '¿Eliminar socio?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = "dashboard.php?modulo=socios&action=eliminar&id=" + id;
                }
            });
        });
    });
</script>