<?php
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$anioActual = date('Y');
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Nuevo Informe de Rendimientos</h3>
        <p class="text-muted">Complete los datos para generar el comprobante de pago de rendimientos al socio.</p>
    </div>
    <a href="dashboard.php?modulo=reportes&action=pagos_socios" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Volver al Listado
    </a>
</div>

<form action="dashboard.php?modulo=reportes&action=guardar_pago_socio" method="POST" id="formRendimiento">
    <div class="row">
        <!-- Datos del Socio -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white fw-bold">1. Información del Socio y Periodo</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Socio / Inversionista</label>
                        <select name="socio_id" class="form-select" required>
                            <option value="">Seleccione un socio...</option>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['nombre_completo']) ?> (
                                    <?= $s['documento'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Mes</label>
                            <select name="mes" class="form-select" required>
                                <?php foreach ($meses as $m): ?>
                                    <option value="<?= $m ?>" <?= $m == $meses[date('n') - 1] ? 'selected' : '' ?>>
                                        <?= $m ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Año</label>
                            <input type="number" name="anio" class="form-control" value="<?= $anioActual ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fecha de Emisión</label>
                        <input type="date" name="fecha_emision" class="form-control" value="<?= date('Y-m-d') ?>"
                            required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle General -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white fw-bold">2. Detalle General del Portafolio</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Utilidades Generadas</label>
                            <input type="number" step="0.01" name="utilidades_generadas" class="form-control calc-field"
                                value="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Saldo a favor anterior</label>
                            <input type="number" step="0.01" name="saldo_favor_anterior" class="form-control calc-field"
                                value="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Rendimiento Mensual (%)</label>
                            <input type="number" step="0.01" name="rendimiento_mensual_porc" class="form-control"
                                value="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Deducciones</label>
                            <input type="number" step="0.01" name="deducciones" class="form-control calc-field"
                                value="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Impuestos</label>
                            <input type="number" step="0.01" name="impuestos" class="form-control calc-field"
                                value="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Ajustes</label>
                            <input type="number" step="0.01" name="ajustes" class="form-control calc-field"
                                value="0.00">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold text-primary">Saldo Final del Mes</label>
                        <input type="number" step="0.01" name="saldo_final_mes" id="saldo_final_mes"
                            class="form-control fw-bold bg-light" value="0.00" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle de Liquidación (Préstamos) -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-stars me-2"></i>3. Liquidación Detallada por Préstamo</span>
                    <button type="button" id="btnCalcularRendimientos" class="btn btn-warning btn-sm fw-bold">
                        <i class="bi bi-calculator me-1"></i> Consultar Rendimientos
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="tablaPrestamos">
                            <thead class="table-light">
                                <tr>
                                    <th>N° Préstamo</th>
                                    <th>Aporte</th>
                                    <th>Tasa %</th>
                                    <th>Rendimiento</th>
                                    <th>Cap. Devuelto</th>
                                    <th>Detalle</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Seleccione un socio y periodo
                                        para consultar...</td>
                                </tr>
                            </tbody>
                            <tfoot class="d-none">
                                <tr class="table-secondary fw-bold">
                                    <td colspan="3" class="text-end">TOTALES:</td>
                                    <td id="totalRendimiento">0.00</td>
                                    <td id="totalCapDevuelto">0.00</td>
                                    <td></td>
                                    <td class="text-end" id="grandTotal">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <input type="hidden" name="items_json" id="items_json">
                </div>
            </div>
        </div>

        <!-- Información del Pago -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">4. Información del Pago de Rendimientos</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de Pago</label>
                            <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Medio de Pago</label>
                            <select name="medio_pago" class="form-select">
                                <option value="Transferencia">Transferencia</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Consignación">Consignación</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-success">Valor Pagado</label>
                            <input type="number" step="0.01" name="valor_pagado" id="valor_pagado"
                                class="form-control fw-bold" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Responsable</label>
                            <input type="text" name="responsable" class="form-control" value="Felipe Vega">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"
                            placeholder="Notas adicionales sobre el pago..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end mb-5">
        <button type="submit" class="btn btn-primary btn-lg shadow">
            <i class="bi bi-save me-1"></i> Generar Informe y Guardar
        </button>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const form = document.getElementById('formRendimiento');
    const itemsJsonInput = document.getElementById('items_json');

    function actualizarSaldos() {
        const utilidades = parseFloat(document.getElementsByName('utilidades_generadas')[0].value) || 0;
        const saldoFavor = parseFloat(document.getElementsByName('saldo_favor_anterior')[0].value) || 0;
        const deducciones = parseFloat(document.getElementsByName('deducciones')[0].value) || 0;
        const impuestos = parseFloat(document.getElementsByName('impuestos')[0].value) || 0;
        const ajustes = parseFloat(document.getElementsByName('ajustes')[0].value) || 0;

        const saldoFinal = (utilidades + saldoFavor) - (deducciones + impuestos + ajustes);
        document.getElementById('saldo_final_mes').value = saldoFinal.toFixed(2);
        document.getElementById('valor_pagado').value = saldoFinal.toFixed(2);
    }

    document.querySelectorAll('.calc-field').forEach(input => {
        input.addEventListener('input', actualizarSaldos);
    });

    document.getElementById('btnCalcularRendimientos').onclick = function () {
        const socioId = document.getElementsByName('socio_id')[0].value;
        const mes = document.getElementsByName('mes')[0].value;
        const anio = document.getElementsByName('anio')[0].value;

        if (!socioId) {
            Swal.fire('Atención', 'Seleccione un socio primero', 'warning');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Calculando...';

        fetch(`dashboard.php?modulo=reportes&action=calcular_rendimientos_ajax&socio_id=${socioId}&mes=${mes}&anio=${anio}`)
            .then(r => {
                if (!r.ok) throw new Error('Error en el servidor');
                return r.text(); // First get as text to debug potential pollution
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON:', text);
                    throw new Error('Respuesta del servidor no es válida (JSON Error)');
                }
            })
            .then(data => {
                if (data.error) {
                    if (data.error === 'ALREADY_EXISTS') {
                        Swal.fire('Liquidación Existente', data.message, 'warning');
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                    const tbody = document.querySelector('#tablaPrestamos tbody');
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-warning py-4">${data.message || data.error}</td></tr>`;
                    return;
                }

                const tbody = document.querySelector('#tablaPrestamos tbody');
                const tfoot = document.querySelector('#tablaPrestamos tfoot');
                tbody.innerHTML = '';

                if (data.items && data.items.length > 0) {
                    let sumRendimiento = 0;
                    let sumCap = 0;
                    let sumTotal = 0;

                    data.items.forEach(it => {
                        sumRendimiento += parseFloat(it.rendimiento);
                        sumCap += parseFloat(it.capital_devuelto);
                        sumTotal += parseFloat(it.total);

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${it.prestamo_consecutivo}</td>
                            <td>$ ${it.aporte.toLocaleString()}</td>
                            <td>${it.tasa_socio}%</td>
                            <td class="text-primary fw-bold">$ ${it.rendimiento.toLocaleString()}</td>
                            <td>$ ${it.capital_devuelto.toLocaleString()}</td>
                            <td class="text-muted small">${it.detalle}</td>
                            <td class="text-end fw-bold">$ ${it.total.toLocaleString()}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Update totals in table
                    tfoot.classList.remove('d-none');
                    document.getElementById('totalRendimiento').innerText = `$ ${sumRendimiento.toLocaleString()}`;
                    document.getElementById('totalCapDevuelto').innerText = `$ ${sumCap.toLocaleString()}`;
                    document.getElementById('grandTotal').innerText = `$ ${sumTotal.toLocaleString()}`;

                    // Auto-fill form fields
                    document.getElementsByName('utilidades_generadas')[0].value = sumRendimiento.toFixed(2);
                    document.getElementsByName('ajustes')[0].value = (sumCap * -1).toFixed(2); // Devolución de capital como ajuste negativo? O mejor sumarlo.
                    // Ajuste: si el capital se devuelve, aumenta el pago. 
                    // Según fórmula (utilidades + saldoFavor) - (deducciones + impuestos + ajustes)
                    // Si pongo ajustes negativo, se suma.

                    actualizarSaldos();
                    itemsJsonInput.value = JSON.stringify(data.items);

                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">No se encontraron pagos compensables para este socio en el periodo seleccionado.</td></tr>';
                    tfoot.classList.add('d-none');
                    itemsJsonInput.value = '[]';
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'No se pudo realizar el cálculo: ' + err.message, 'error');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-calculator me-1"></i> Consultar Rendimientos';
            });
    };
</script>