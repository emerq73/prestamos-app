<?php
require_once __DIR__ . '/../../models/Pago.php';
require_once __DIR__ . '/../../models/Prestamo.php';

$prestamoModel = new Prestamo();
$pagoModel = new Pago();

$prestamoId = isset($_GET['prestamo_id']) ? intval($_GET['prestamo_id']) : 0;
$estadoFiltro = $_GET['estado'] ?? 'todos';
?>

<script>
    function enviarRecibo(pagoId) {
        Swal.fire({
            title: 'Enviar Comprobante',
            input: 'email',
            inputLabel: 'Correo del cliente',
            inputPlaceholder: 'cliente@email.com',
            showCancelButton: true,
            confirmButtonText: 'Enviar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Enviando...', didOpen: () => Swal.showLoading() });

                fetch('../reportes/EnviarReciboPago.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `pago_id=${pagoId}&email=${result.value}`
                })
                    .then(res => res.json())
                    .then(data => {
                        Swal.fire(data.status === 'ok' ? 'Éxito' : 'Error', data.msg, data.status === 'ok' ? 'success' : 'error');
                    })
                    .catch(err => Swal.fire('Error', 'Fallo de conexión', 'error'));
            }
        });
    }

    function confirmarVerPagos(prestamoId) {
        Swal.fire({
            title: 'Préstamo Cancelado',
            text: 'Este préstamo ya está cancelado. ¿Desea ver el historial de pagos?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, ver historial',
            cancelButtonText: 'No, volver'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `dashboard.php?modulo=pagos&prestamo_id=${prestamoId}`;
            }
        });
    }
</script>

<!-- SweetAlert para Pago Exitoso -->
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'pago_ok'): ?>
    <script>     document.addEventListener('DOMContentLoaded', function () {         let tipo = '<?= $_GET['tipo'] ?? 'pago' ?>';         let texto = '';         if (tipo === 'cuota') texto = 'Pago de cuota registrado exitosamente.';         else if (tipo === 'interes') texto = 'Pago de intereses registrado. Se ha generado una nueva cuota al final.';         else if (tipo === 'total') texto = 'Pago total registrado. ¡Préstamo cancelado!';         else texto = 'Pago registrado exitosamente.';
             Swal.fire({             icon: 'success',             title: '¡Pago Exitoso!',             text: texto,             confirmButtonColor: '#198754'         });     });
    </script>
<?php endif; ?>

<?php
/*
|--------------------------------------------------------------------------
| CASO 1: NO hay préstamo seleccionado → listar préstamos
|--------------------------------------------------------------------------
*/
if ($prestamoId <= 0) {

    $prestamos = $prestamoModel->obtenerTodos($estadoFiltro);
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-credit-card"></i> Módulo de Pagos</h3>
        <div class="d-flex align-items-center">
            <label class="me-2 mb-0 small">Filtrar:</label>
            <select class="form-select form-select-sm" style="width: 130px"
                onchange="location.href='dashboard.php?modulo=pagos&estado=' + this.value">
                <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activos</option>
                <option value="cancelado" <?= $estadoFiltro === 'cancelado' ? 'selected' : '' ?>>Cancelados</option>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-bold">
            Selecciona un préstamo
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Deudor</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prestamos)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                No hay préstamos registrados
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($prestamos as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['deudor_nombre']) ?></td>
                            <td>$<?= number_format($p['monto'], 2) ?></td>
                            <td>
                                <span
                                    class="badge bg-<?= ($p['estado'] ?? 'activo') === 'cancelado' ? 'success' : 'secondary' ?>">
                                    <?= strtoupper($p['estado'] ?? 'ACTIVO') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if (($p['estado'] ?? 'activo') === 'cancelado'): ?>
                                    <button onclick="confirmarVerPagos(<?= $p['id'] ?>)" class="btn btn-sm btn-primary">
                                        Ver pagos
                                    </button>
                                <?php else: ?>
                                    <a href="dashboard.php?modulo=pagos&prestamo_id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">
                                        Ver pagos
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return;
}

/*
|--------------------------------------------------------------------------
| CASO 2: HAY préstamo seleccionado → mostrar pagos
|--------------------------------------------------------------------------
*/
$prestamo = $prestamoModel->obtenerPorId($prestamoId);

if (!$prestamo) {
    echo '<div class="alert alert-danger">Préstamo no encontrado</div>';
    return;
}

$pagos = $pagoModel->obtenerPagosPorPrestamo($prestamoId);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Pagos del Préstamo #<?= $prestamoId ?> - Historial</h3>
    <div>
        <a href="dashboard.php?modulo=pagos" class="btn btn-secondary me-2">
            <i class="bi bi-arrow-left"></i> Volver a Pagos
        </a>
        <a href="dashboard.php?modulo=pagos&action=crear&prestamo_id=<?= $prestamoId ?>" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nuevo Pago
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><b>Deudor:</b> <?= htmlspecialchars($prestamo['deudor_nombre']) ?></div>
            <div class="col-md-4"><b>Monto:</b> $<?= number_format($prestamo['monto'], 2) ?></div>
            <div class="col-md-4">
                <b>Estado:</b>
                <span
                    class="badge bg-<?= ($prestamo['estado'] ?? 'activo') === 'cancelado' ? 'success' : 'secondary' ?>">
                    <?= strtoupper($prestamo['estado'] ?? 'ACTIVO') ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th>Método</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pagos)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        No hay pagos registrados
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($p['fecha'])) ?></td>
                    <td>
                        <span class="badge bg-secondary">
                            <?= strtoupper($p['tipo']) ?>
                        </span>
                    </td>
                    <td>$<?= number_format($p['monto_total'], 2) ?></td>
                    <td><?= ucfirst($p['metodo_pago'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['referencia'] ?? '-') ?></td>
                    <td>
                        <a href="../reportes/recibo_pago_pdf.php?id=<?= $p['id'] ?>" target="_blank"
                            class="btn btn-sm btn-danger" title="Descargar PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <button class="btn btn-sm btn-primary" onclick="enviarRecibo(<?= $p['id'] ?>)"
                            title="Enviar por correo">
                            <i class="bi bi-envelope"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<a href="dashboard.php?modulo=pagos" class="btn btn-secondary mt-3">
    ← Volver
</a>