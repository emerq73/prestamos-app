<?php
// espera $detalles disponible
?>
<div class="card">
  <!-- <div class="card-header bg-light">Tabla de pagos</div> -->
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Capital</th>
            <th>Interés</th>
            <th>Total</th>
            <th>Saldo</th>
            <th>Estado</th>

          </tr>
        </thead>
        <tbody>
          <?php foreach ($detalles as $row): ?>
            <tr>
              <td><?= $row['numero_cuota'] ?></td>
              <td><?= $row['fecha_programada'] ?></td>
              <td><?= number_format($row['capital'], 2) ?></td>
              <td><?= number_format($row['interes'], 2) ?></td>
              <td><?= number_format($row['total_cuota'], 2) ?></td>
              <td><?= number_format($row['saldo_restante'], 2) ?></td>
              <td>
                <?php
                $badgeClass = match ($row['estado']) {
                  'pagado' => 'bg-success',
                  'pendiente' => 'bg-warning text-dark',
                  'mora' => 'bg-danger',
                  default => 'bg-secondary'
                };
                ?>
                <span class="badge <?= $badgeClass ?>"><?= ucfirst($row['estado']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal simple para pago -->
<div class="modal fade" id="pagoModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="pagoForm" action="<?= $baseURL ?>/controllers/PagoController.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="detalle_id" id="detalle_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Registrar pago</h5><button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Monto a pagar</label>
            <input type="number" step="0.01" name="monto_pagado" id="monto_pagado" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Confirmar pago</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  function openPagoModal(id, total) {
    document.getElementById('detalle_id').value = id;
    document.getElementById('monto_pagado').value = total;
    new bootstrap.Modal(document.getElementById('pagoModal')).show();
  }
</script>