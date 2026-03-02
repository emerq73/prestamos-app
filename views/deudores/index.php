<?php
// $deudores viene del controlador
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-people-fill me-2"></i> Acreedores</h4>
    <a href="dashboard.php?modulo=deudores/crear" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i> Nuevo acreedor
    </a>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nombre completo</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($deudores)): ?>
                <?php foreach ($deudores as $d): ?>
                    <tr>
                        <td><?= $d['id'] ?></td>
                        <td><?= htmlspecialchars($d['nombre_completo']) ?></td>
                        <td><?= $d['documento'] ?></td>
                        <td><?= $d['telefono'] ?></td>
                        <td>
                            <span class="badge <?= $d['estado'] === 'activo' ? 'bg-success' : 'bg-danger' ?>">
                                <?= ucfirst($d['estado']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="dashboard.php?modulo=deudores/editar&id=<?= $d['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $d['id'] ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No hay acreedores registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- SweetAlert mensajes -->
<?php if (isset($_GET['exito'])): ?>
    <script>
        let m = '';
        switch ('<?= $_GET['exito'] ?>') {
            case 'creado':
                m = 'Acreedor registrado correctamente';
                break;
            case 'editado':
                m = 'Acreedor actualizado con éxito';
                break;
            case 'eliminado':
                m = 'Acreedor eliminado correctamente';
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

<?php if (isset($_GET['error']) && $_GET['error'] === 'tiene_prestamos'): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'No se puede eliminar',
            text: 'Este acreedor tiene préstamos en ejecución',
            confirmButtonColor: '#d33'
        });
    </script>
<?php endif; ?>

<?php $baseURL = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/prestamos-app"; ?>

<!-- Confirmar eliminación -->
<script>
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            Swal.fire({
                title: '¿Eliminar acreedor?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = "dashboard.php?modulo=deudores&action=eliminar&id=" + id;
                }
            });
        });
    });
</script>