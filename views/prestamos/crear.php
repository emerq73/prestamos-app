<?php
require_once __DIR__ . '/../../models/Deudor.php';
require_once __DIR__ . '/../../models/Socio.php';

$deudorModel = new Deudor();
$socioModel = new Socio();

$deudores = $deudorModel->obtenerTodos();
$socios = $socioModel->obtenerTodos();
?>

<style>
    .socio-row {
        background: var(--bs-tertiary-bg);
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 12px;
        border: 1px solid var(--bs-border-color);
        transition: all 0.2s ease;
    }
    .socio-row:hover {
        border-color: var(--bs-primary);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    /* Clases para errores de validación */
    .input-error, .input-error-entero {
        border-color: var(--bs-danger) !important;
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-danger-rgb), 0.25) !important;
    }
</style>

<div class="container-fluid py-2">
    <div class="d-flex align-items-center mb-4">
        <a href="dashboard.php?modulo=prestamos" class="btn btn-outline-secondary btn-sm me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h3 class="mb-0 fw-bold">Registrar Nuevo Préstamo</h3>
    </div>

    <!-- FORMULARIO PRINCIPAL -->
    <!-- Se mantiene la ruta según el diseño de tu router en dashboard.php -->
    <form action="/prestamos-app/views/dashboard.php?modulo=prestamos&action=guardar" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <div class="row g-4">
            <!-- Columna Izquierda: Datos del Acreedor y Préstamo -->
            <div class="col-lg-8">
                <!-- ======================= -->
                <!-- DATOS DEL DEUDOR (ACREEDOR) -->
                <!-- ======================= -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Datos del Acreedor</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Seleccione Acreedor</label>
                            <select name="deudor_id" class="form-select form-select-lg" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($deudores as $d): ?>
                                    <option value="<?= $d['id'] ?>">
                                        <?= htmlspecialchars($d['nombre_completo']) ?> - <?= htmlspecialchars($d['documento']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ======================= -->
                <!-- DATOS ECONÓMICOS DEL PRÉSTAMO -->
                <!-- ======================= -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white py-3">
                        <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Condiciones Económicas</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Monto del Préstamo</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">$</span>
                                    <input type="text" id="monto" class="form-control" placeholder="0,00" inputmode="numeric" autocomplete="off" required>
                                </div>
                                <input type="hidden" name="monto" id="monto_real">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">% Interés</label>
                                <div class="input-group input-group-lg">
                                    <input type="text" id="tasa_interes" inputmode="decimal" name="tasa_interes" class="form-control text-center" placeholder="0.0" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small id="error-msg" class="text-danger d-none mt-1">Solo números y un punto decimal.</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Plazo (cuotas)</label>
                                <input type="text" id="cantidad_entera" inputmode="numeric" name="plazo" class="form-control form-control-lg text-center" placeholder="Cant." required>
                                <small id="error-msg-entero" class="text-danger d-none mt-1">Solo números enteros.</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Tipo de Tasa</label>
                                <select name="tipo_tasa" class="form-select">
                                    <option value="mensual">Mensual</option>
                                    <option value="anual">Anual</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Periodo de Pago</label>
                                <select name="periodo_pago" class="form-select">
                                    <option value="mensual">Mensual</option>
                                    <option value="quincenal">Quincenal</option>
                                    <option value="semanal">Semanal</option>
                                    <option value="diario">Diario</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Fecha de Inicio</label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                <div class="mt-2">
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" id="nota-corte">
                                        <i class="bi bi-calendar-check me-1"></i> Corte: día <?= date('d') ?> de cada mes
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-bold small">Observaciones adicionales</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas internas..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Socios y Resumen -->
            <div class="col-lg-4">
                <!-- ======================= -->
                <!-- SOCIOS APORTANTES -->
                <!-- ======================= -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-people me-2"></i>Aportes de Socios</h6>
                        <button type="button" class="btn btn-light btn-sm fw-bold" id="btnAgregarSocio">
                            <i class="bi bi-plus-lg"></i> Agregar
                        </button>
                    </div>

                    <div class="card-body p-3" id="contenedor-socios" style="max-height: 500px; overflow-y: auto;">
                        <div class="alert alert-light text-center py-4 border dashed-border" id="msg-sin-socios">
                            <i class="bi bi-info-circle fs-3 text-muted d-block mb-2"></i>
                            <span class="text-muted small">Haz clic en '+ Agregar' para asignar socios aportantes.</span>
                        </div>
                        <!-- Filas dinámicas -->
                    </div>

                    <div class="card-footer bg-body-tertiary border-top mt-auto py-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total Aportado:</span>
                            <strong>$ <span id="totalAportes">0,00</span></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Diferencia:</span>
                            <strong id="labelDiferencia" class="text-danger">$ <span id="valorDiferencia">0,00</span></strong>
                        </div>
                    </div>
                </div>

                <!-- ACCIÓN FINAL -->
                <div class="card border-0 shadow mb-4 bg-primary text-white">
                    <div class="card-body p-4 text-center">
                        <p class="mb-3 small opacity-75">Asegúrate de que los aportes coincidan con el monto solicitado.</p>
                        <button class="btn btn-light btn-lg w-100 fw-bold py-3 shadow-sm border-0">
                            <i class="bi bi-check-circle-fill me-2"></i>GUARDAR PRÉSTAMO
                        </button>
                    </div>
                </div>
                
                <div class="alert alert-warning border-0 shadow-sm small py-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Al guardar se generará automáticamente el plan de amortización según el periodo seleccionado.
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .dashed-border { border-style: dashed !important; border-width: 2px !important; }
</style>

<script>
    // Variables de socios provenientes de PHP
    const listaSocios = <?= json_encode($socios) ?>;
    const contenedor = document.getElementById('contenedor-socios');
    const msgSinSocios = document.getElementById('msg-sin-socios');
    const totalAportesSpan = document.getElementById('totalAportes');
    const valorDiferenciaSpan = document.getElementById('valorDiferencia');
    const labelDiferencia = document.getElementById('labelDiferencia');
    const montoPrestamoInput = document.getElementById('monto');
    const montoRealInput = document.getElementById('monto_real');
    const formulario = document.querySelector('form');

    // --- FUNCIÓN PARA FORMATEAR MONEDA ---
    function formatCurrency(valor) {
        let cleanValue = valor.replace(/[^\d]/g, '');
        if (!cleanValue) return { display: '', real: 0 };
        let numero = (parseInt(cleanValue, 10) / 100).toFixed(2);
        let display = numero.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return { display: display, real: parseFloat(numero) };
    }

    // --- MANEJO DE LOS INPUTS DINÁMICOS DE SOCIOS ---
    function validarSociosDuplicados() {
        let seleccionados = Array.from(document.querySelectorAll('select[name="socio_id[]"]'))
            .map(sel => sel.value)
            .filter(val => val !== "");

        document.querySelectorAll('select[name="socio_id[]"]').forEach(select => {
            let valorActual = select.value;
            Array.from(select.options).forEach(option => {
                if (option.value !== "") {
                    option.disabled = seleccionados.includes(option.value) && option.value !== valorActual;
                }
            });
        });
    }

    function agregarSocio() {
        let totalSociosDisponibles = listaSocios.length;
        let sociosAgregados = document.querySelectorAll('.socio-row').length;

        if (sociosAgregados >= totalSociosDisponibles) {
            Swal.fire({
                icon: 'info',
                title: 'Límite alcanzado',
                text: 'Ya has agregado a todos los socios disponibles.',
                confirmButtonColor: '#0d6efd',
                background: getThemeColor('bg'),
                color: getThemeColor('text')
            });
            return;
        }

        // Ocultar mensaje de vacío
        if (msgSinSocios) msgSinSocios.classList.add('d-none');

        let div = document.createElement('div');
        div.classList.add('socio-row', 'animate__animated', 'animate__fadeIn');
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-body-secondary text-body fw-bold">#{INDEX}</span>
                <button type="button" class="btn btn-link text-danger p-0 btnEliminar" title="Eliminar socio">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
            <div class="row g-2">
                <div class="col-12 mb-2">
                    <label class="small fw-bold mb-1">Socio</label>
                    <select name="socio_id[]" class="form-select form-select-sm select-socio" required>
                        <option value="">Seleccione...</option>
                        ${listaSocios.map(s => `<option value="${s.id}">${s.nombre_completo}</option>`).join('')}
                    </select>
                </div>
                <div class="col-6">
                    <label class="small fw-bold mb-1">Aporte ($)</label>
                    <input type="text" class="form-control form-control-sm aporte-mask" placeholder="0,00" required>
                    <input type="hidden" name="socio_aporte[]" class="aporte-real">
                </div>
                <div class="col-6">
                    <label class="small fw-bold mb-1">% Interés</label>
                    <input type="number" step="0.01" name="socio_interes[]" class="form-control form-control-sm" placeholder="Ej: 5" required>
                </div>
            </div>`;

        // Corregir índice visual
        div.innerHTML = div.innerHTML.replace('#{INDEX}', sociosAgregados + 1);
        contenedor.appendChild(div);

        const selectSocio = div.querySelector('.select-socio');
        const maskInput = div.querySelector('.aporte-mask');
        const realInput = div.querySelector('.aporte-real');

        selectSocio.addEventListener('change', validarSociosDuplicados);

        maskInput.addEventListener('input', function () {
            let info = formatCurrency(this.value);
            this.value = info.display;
            realInput.value = info.real;
            actualizarTotal();
        });

        div.querySelector('.btnEliminar').addEventListener('click', () => {
            div.remove();
            validarSociosDuplicados();
            actualizarTotal();
            if (document.querySelectorAll('.socio-row').length === 0) {
                msgSinSocios.classList.remove('d-none');
            }
        });

        validarSociosDuplicados();
    }

    function actualizarTotal() {
        let total = 0;
        document.querySelectorAll('.aporte-real').forEach(input => {
            total += parseFloat(input.value || 0);
        });

        let montoPrestamo = parseFloat(montoRealInput.value || 0);
        let diferencia = montoPrestamo - total;

        totalAportesSpan.innerText = total.toLocaleString('es-CO', { minimumFractionDigits: 2 });
        valorDiferenciaSpan.innerText = Math.abs(diferencia).toLocaleString('es-CO', { minimumFractionDigits: 2 });

        if (Math.abs(diferencia) < 0.01) {
            labelDiferencia.classList.replace('text-danger', 'text-success');
            labelDiferencia.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Cuadra perfectamente';
        } else {
            labelDiferencia.classList.replace('text-success', 'text-danger');
            labelDiferencia.innerHTML = '$ <span id="valorDiferencia">' + Math.abs(diferencia).toLocaleString('es-CO', { minimumFractionDigits: 2 }) + '</span>';
        }
    }

    function getThemeColor(type) {
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        if (type === 'bg') return isDark ? '#1e293b' : '#fff';
        return isDark ? '#f8fafc' : '#1e293b';
    }

    // --- EVENTOS INICIALES ---
    document.getElementById('btnAgregarSocio').addEventListener('click', agregarSocio);

    // Formateo Monto Préstamo
    montoPrestamoInput.addEventListener('input', function () {
        let info = formatCurrency(this.value);
        this.value = info.display;
        montoRealInput.value = info.real;
        actualizarTotal();
    });

    // Validaciones de tipos de input
    document.getElementById('tasa_interes').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
    });

    document.getElementById('cantidad_entera').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Validación Final
    formulario.addEventListener('submit', function (e) {
        let total = 0;
        document.querySelectorAll('.aporte-real').forEach(input => {
            total += parseFloat(input.value || 0);
        });
        let montoReq = parseFloat(montoRealInput.value || 0);

        if (Math.abs(total - montoReq) > 0.01) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Desajuste de montos',
                text: 'El total de aportes debe ser exactamente igual al monto del préstamo.',
                confirmButtonColor: '#0d6efd',
                background: getThemeColor('bg'),
                color: getThemeColor('text')
            });
        }
    });

    // Lógica para Nota de Corte Dinámica
    const inputFecha = document.getElementById('fecha_inicio');
    const notaCorte = document.getElementById('nota-corte');
    const obsTextarea = document.querySelector('textarea[name="observaciones"]');

    function actualizarNotaCorte(fechaVal) {
        if (fechaVal) {
            const fecha = new Date(fechaVal + 'T00:00:00'); // Forzar hora local
            const dia = fecha.getDate();
            const noteText = `Corte: día ${dia} de cada mes`;
            
            // Actualizar el badge visual
            notaCorte.innerHTML = `<i class="bi bi-calendar-check me-1"></i> ${noteText}`;

            // Actualizar el campo de observaciones
            let currentObs = obsTextarea.value;
            const regex = /Corte: día \d+ de cada mes(\n)?/g;
            
            if (regex.test(currentObs)) {
                // Si ya existe la nota, la reemplazamos manteniendo el resto del texto
                obsTextarea.value = currentObs.replace(regex, noteText + "$1");
            } else {
                // Si no existe, la añadimos al inicio
                obsTextarea.value = noteText + (currentObs ? "\n" + currentObs : "");
            }
        }
    }

    inputFecha.addEventListener('change', function() {
        actualizarNotaCorte(this.value);
    });

    // Inicializar con la fecha de hoy
    const defaultDate = new Date();
    inputFecha.valueAsDate = defaultDate;
    actualizarNotaCorte(inputFecha.value);
</script>