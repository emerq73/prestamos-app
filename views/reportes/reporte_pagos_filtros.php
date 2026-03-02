<div class="mb-4">
    <h3 class="fw-bold"><i class="bi bi-cash-stack me-2"></i>Reporte General de Pagos</h3>
    <p class="text-muted">Seleccione el rango de fechas para generar el listado de recaudos/pagos.</p>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="../reportes/reporte_general_pagos_pdf.php" method="GET" target="_blank">
                    <div class="mb-3">
                        <label for="desde" class="form-label fw-bold">Fecha Desde</label>
                        <input type="date" class="form-control" id="desde" name="desde" required
                            value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="hasta" class="form-label fw-bold">Fecha Hasta</label>
                        <input type="date" class="form-control" id="hasta" name="hasta" required
                            value="<?= date('Y-m-t') ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Generar Reporte PDF
                        </button>
                        <a href="dashboard.php?modulo=reportes" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>