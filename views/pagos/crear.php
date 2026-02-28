<?php
require_once __DIR__ . '/../../models/Prestamo.php';
require_once __DIR__ . '/../../models/PrestamoDetalle.php';

$prestamoId = intval($_GET['prestamo_id'] ?? 0);
$prestamoModel = new Prestamo();
$detalleModel = new PrestamoDetalle();

$prestamo = $prestamoModel->obtenerPorId($prestamoId);
$cuotas = $detalleModel->obtenerPorPrestamo($prestamoId);

if (!$prestamo) {
    echo '<div class="alert alert-danger">Préstamo no encontrado</div>';
    return;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Registrar Pago</h3>
    <a href="dashboard.php?modulo=pagos" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Volver a Pagos
    </a>
</div>
<form method="POST" action="dashboard.php?modulo=pagos&action=guardar">
    <!-- <form method="POST" action="controllers/PagosController.php?action=guardar"> -->

    <input type="hidden" name="prestamo_id" value="<?= $prestamoId ?>">

    <div class="card mb-3">
        <div class="card-body">
            <h5>Datos del Préstamo</h5>
            <p><b>Deudor:</b> <?= htmlspecialchars($prestamo['deudor_nombre']) ?></p>
            <p><b>Monto:</b> $<?= number_format($prestamo['monto'], 2) ?></p>
            <p><b>Estado:</b> <?= strtoupper($prestamo['estado'] ?? 'activo') ?></p>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Tipo de pago</label>
        <select name="tipo" class="form-select" required>
            <option value="cuota">Pago de cuota(s)</option>
            <option value="interes">Solo intereses</option>
            <option value="total">Pago total</option>
        </select>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th style="width:30px"></th>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Capital</th>
                    <th>Interés</th>
                    <th>Pagado Cap.</th>
                    <th>Pagado Int.</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cuotas as $c): ?>
                    <tr>
                        <td class="text-center">
                            <?php
                            $pend = ($c['capital'] + $c['interes']) - ($c['pagado_capital'] + $c['pagado_interes']);
                            if ($pend > 0.01 && $c['estado'] !== 'parcial'):
                                ?>
                                <input type="checkbox" name="cuotas[]" value="<?= $c['id'] ?>">
                            <?php endif; ?>
                        </td>
                        <td><?= $c['numero_cuota'] ?></td>
                        <td><?= $c['fecha_programada'] ?></td>
                        <td>$<?= number_format($c['capital'], 2) ?></td>
                        <td>$<?= number_format($c['interes'], 2) ?></td>
                        <td>$<?= number_format($c['pagado_capital'], 2) ?></td>
                        <td>$<?= number_format($c['pagado_interes'], 2) ?></td>
                        <td>$<?= number_format($c['saldo_restante'], 2) ?></td>
                        <td>
                            <?php
                            $badgeClass = match ($c['estado']) {
                                'pagado' => 'bg-success',
                                'solo_interes' => 'bg-info text-white',
                                'pendiente' => 'bg-warning text-dark',
                                'mora' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= strtoupper($c['estado']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="row mt-3">
        <div class="col-md-4">
            <label>Método de pago</label>
            <select name="metodo_pago" class="form-select">
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="tarjeta">Tarjeta</option>
            </select>
        </div>
        <div class="col-md-4">
            <label>Referencia</label>
            <input type="text" name="referencia" class="form-control">
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-success">Guardar Pago</button>
        <a href="dashboard.php?modulo=pagos&prestamo_id=<?= $prestamoId ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>