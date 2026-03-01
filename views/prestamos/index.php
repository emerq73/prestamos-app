<?php
// $prestamos viene del controlador
$estadoFiltro = $_GET['estado'] ?? 'todos';
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">
            <i class="bi bi-cash-coin me-2"></i> Préstamos
        </h3>

        <div class="d-flex align-items-center">
            <label class="me-2 mb-0 small fw-bold">Filtrar:</label>
            <select class="form-select form-select-sm me-3" style="width: 150px"
                onchange="location.href='dashboard.php?modulo=prestamos&estado=' + this.value">
                <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activos</option>
                <option value="cancelado" <?= $estadoFiltro === 'cancelado' ? 'selected' : '' ?>>Cancelados</option>
            </select>

            <a href="dashboard.php?modulo=prestamos/crear" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Nuevo préstamo
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'creado'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'El préstamo fue registrado exitosamente.',
                    confirmButtonColor: '#0d6efd'
                });
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'tiene_pagos'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'warning',
                    title: 'No se puede eliminar',
                    text: '<?= isset($_GET['msg']) ? urldecode($_GET['msg']) : "El préstamo tiene pagos aplicados." ?>',
                    confirmButtonColor: '#d33'
                });
            });
        </script>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <?php if (empty($prestamos)): ?>
                <div class="alert alert-info">
                    No hay préstamos registrados aún.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Deudor</th>
                                <th>Monto</th>
                                <th>Interés</th>
                                <th>Plazo</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($prestamos as $p): ?>
                                <tr>
                                    <td><?= $p['id'] ?></td>
                                    <td><?= htmlspecialchars($p['deudor_nombre']) ?></td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            $ <?= number_format($p['monto'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td><?= $p['tasa_interes'] ?>%</td>
                                    <td><?= $p['plazo'] ?> meses</td>
                                    <td><?= $p['fecha_inicio'] ?></td>
                                    <td>
                                        <?php if ($p['estado'] == 'activo'): ?>
                                            <span class="badge bg-success px-3 py-2 shadow-sm">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-2">Cancelado</span>
                                        <?php endif; ?>
                                    </td>


                                    <td>
                                        <a href="dashboard.php?modulo=prestamos/ver&id=<?= $p['id'] ?>"
                                            class="btn btn-sm btn-info text-white">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $p['id'] ?>">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    // Eliminar préstamo con SweetAlert
    document.querySelectorAll('.btn-eliminar').forEach(btn => {

        btn.addEventListener('click', function () {
            let prestamoID = this.dataset.id;

            Swal.fire({
                title: "¿Eliminar préstamo?",
                text: "Esta acción no se puede deshacer.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href =
                        "dashboard.php?modulo=prestamos&action=eliminar&id=" + prestamoID;

                }
            });
        });
    });
</script>