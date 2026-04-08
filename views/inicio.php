<div class="mb-4">
    <h4 class="mb-0">Bienvenido, <?= $usuario ?> 👋</h4>
</div>

<?php if (($usuarioRol ?? '') === 'admin'): ?>
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Acreedores activos</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?= number_format($metrics['acreedores_activos'], 0, ',', '.') ?></h3>
                        <i class="bi bi-people-fill text-primary fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Préstamos totales</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">$<?= number_format($metrics['prestamos_totales'], 0, ',', '.') ?></h3>
                        <i class="bi bi-cash-coin text-success fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Abonos del mes</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">$<?= number_format($metrics['abonos_mes'], 0, ',', '.') ?></h3>
                        <i class="bi bi-graph-up text-info fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Socios activos</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?= number_format($metrics['socios_activos'], 0, ',', '.') ?></h3>
                        <i class="bi bi-person-vcard text-warning fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FILTRO DE RENDIMIENTOS -->
    <div class="d-flex justify-content-end mb-3">
        <form method="GET" class="d-flex gap-2 align-items-center p-3 bg-body-tertiary rounded shadow-sm border">
            <input type="hidden" name="modulo" value="inicio">
            <span class="text-muted small fw-bold me-2"><i class="bi bi-funnel-fill"></i> FILTRAR REPORTE:</span>
            
            <select name="filtro_mes" class="form-select form-select-sm" style="width: 140px;">
                <?php
                $meses_display = [
                    'enero' => 'Enero', 'febrero' => 'Febrero', 'marzo' => 'Marzo', 'abril' => 'Abril',
                    'mayo' => 'Mayo', 'junio' => 'Junio', 'julio' => 'Julio', 'agosto' => 'Agosto',
                    'septiembre' => 'Septiembre', 'octubre' => 'Octubre', 'noviembre' => 'Noviembre', 'diciembre' => 'Diciembre'
                ];
                foreach ($meses_display as $val => $nom): 
                    $sel = ($filtro_mes === $val) ? 'selected' : '';
                    echo "<option value='$val' $sel>$nom</option>";
                endforeach;
                ?>
            </select>

            <select name="filtro_anio" class="form-select form-select-sm" style="width: 100px;">
                <?php
                $anio_actual = date('Y');
                for ($a = $anio_actual; $a >= 2024; $a--): 
                    $sel = ($filtro_anio == $a) ? 'selected' : '';
                    echo "<option value='$a' $sel>$a</option>";
                endfor;
                ?>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i> Consultar
            </button>
        </form>
    </div>

    <!-- REPORTE DE RENDIMIENTOS -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Rendimientos Pagados - <span class="text-primary text-capitalize"><?= $filtro_mes ?> <?= $filtro_anio ?></span></h5>
            <i class="bi bi-journal-text text-muted fs-4"></i>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Socio</th>
                            <th>Concepto / Observaciones</th>
                            <th>Fecha Pago</th>
                            <th class="text-end">Valor Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_rendimientos = 0;
                        if (empty($rendimientos)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-info-circle me-2"></i>No hay rendimientos pagados para este periodo.
                                </td>
                            </tr>
                        <?php else: 
                            foreach ($rendimientos as $r): 
                                $total_rendimientos += $r['valor_pagado'];
                        ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($r['socio_nombre']) ?></td>
                                <td>
                                    <small class="text-muted d-block"><?= htmlspecialchars($r['consecutivo']) ?></small>
                                    <?= htmlspecialchars($r['observaciones'] ?: 'Distribución de rendimientos') ?>
                                </td>
                                <td><?= $r['fecha_pago'] ? date('d/m/Y', strtotime($r['fecha_pago'])) : 'Pendiente' ?></td>
                                <td class="text-end fw-bold text-success h6">$<?= number_format($r['valor_pagado'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if (!empty($rendimientos)): ?>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end py-3">TOTAL RECAUDADO EN EL PERIODO:</th>
                            <th class="text-end text-primary h5 mb-0 py-3">$<?= number_format($total_rendimientos, 0, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>