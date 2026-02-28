<?php
require_once __DIR__ . '/../../models/Deudor.php';
require_once __DIR__ . '/../../models/Socio.php';

$deudorModel = new Deudor();
$socioModel = new Socio();

$deudores = $deudorModel->obtenerTodos();
$socios = $socioModel->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Crear Préstamo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .socio-row {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container py-4">
        <h2 class="mb-4">Registrar Nuevo Préstamo</h2>

        <!-- FORMULARIO PRINCIPAL -->
        <!-- IMPORTANTE: ESTA ES LA RUTA CORRECTA SEGÚN TU ROUTER -->
        <form action="/prestamos-app/views/dashboard.php?modulo=prestamos&action=guardar" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <!-- ======================= -->
            <!-- DATOS DEL DEUDOR -->
            <!-- ======================= -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Datos del Deudor</div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Seleccione Deudor</label>
                        <select name="deudor_id" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($deudores as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= $d['nombre_completo'] ?> - <?= $d['documento'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>

            <!-- ======================= -->
            <!-- DATOS DEL PRÉSTAMO -->
            <!-- ======================= -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">Datos del Préstamo</div>
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Monto del Préstamo</label>
                            <input
                                type="text"
                                id="monto"
                                class="form-control"
                                placeholder="$ 0,00"
                                inputmode="numeric"
                                autocomplete="off"
                                required>

                            <input type="hidden" name="monto" id="monto_real">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">% Interés</label>
                            <input
                                type="text"
                                id="tasa_interes"
                                inputmode="decimal"
                                name="tasa_interes"
                                class="form-control"
                                placeholder="Ej: 15.5"
                                required>
                            <small id="error-msg" class="text-danger" style="display: none; position: absolute;">
                                Solo se permiten números y un punto decimal.
                            </small>
                        </div>


                        <style>
                            /* Clase para resaltar el error */
                            .input-error {
                                border-color: #dc3545 !important;
                                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
                            }
                        </style>
                        <div class="col-md-4">
                            <label class="form-label">Plazo (cuotas)</label>
                            <input
                                type="text"
                                id="cantidad_entera"
                                inputmode="numeric"
                                name="plazo"
                                class="form-control"
                                placeholder="Ej: 500"
                                required>
                            <small id="error-msg-entero" class="text-danger" style="display: none; position: absolute;">
                                Solo se permiten números enteros.
                            </small>
                        </div>
                        <style>
                            .input-error-entero {
                                border-color: #dc3545 !important;
                                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
                            }
                        </style>

                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Tasa</label>
                            <select name="tipo_tasa" class="form-select">
                                <option value="mensual">Mensual</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Periodo de Pago</label>
                            <select name="periodo_pago" class="form-select">
                                <option value="mensual">Mensual</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="semanal">Semanal</option>
                                <option value="diario">Diario</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control"></textarea>
                    </div>

                </div>
            </div>

            <!-- ======================= -->
            <!-- SOCIOS APORTANTES -->
            <!-- ======================= -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span>Socios Aportantes</span>
                    <button type="button" class="btn btn-light btn-sm" id="btnAgregarSocio">+ Agregar Socio</button>
                </div>

                <div class="card-body" id="contenedor-socios">
                    <!-- Filas dinámicas -->
                </div>

                <div class="card-footer text-end">
                    <strong>Total Aportes: $ <span id="totalAportes">0</span></strong>
                </div>
            </div>

            <!-- ======================= -->
            <!-- BOTÓN GUARDAR -->
            <!-- ======================= -->
            <div class="text-end">
                <button class="btn btn-primary btn-lg">Guardar Préstamo</button>
            </div>

        </form>
    </div>

    <script>
        let socios = <?= json_encode($socios) ?>;
        let contenedor = document.getElementById('contenedor-socios');
        let totalAportesSpan = document.getElementById('totalAportes');
        let montoPrestamoInput = document.getElementById('monto');
        let montoRealInput = document.getElementById('monto_real');
        const formulario = document.querySelector('form');

        // --- FUNCIÓN PARA FORMATEAR MONEDA ---
        function formatCurrency(valor) {
            let cleanValue = valor.replace(/[^\d]/g, '');
            if (!cleanValue) return {
                display: '',
                real: 0
            };

            let numero = (parseInt(cleanValue, 10) / 100).toFixed(2);
            let display = '$ ' + numero.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return {
                display: display,
                real: parseFloat(numero)
            };
        }

        // --- MANEJO DE LOS INPUTS DINÁMICOS DE SOCIOS ---
        // --- FUNCIÓN PARA VALIDAR SOCIOS DUPLICADOS ---
        function validarSociosDuplicados() {
            let seleccionados = Array.from(document.querySelectorAll('select[name="socio_id[]"]'))
                .map(sel => sel.value)
                .filter(val => val !== "");

            document.querySelectorAll('select[name="socio_id[]"]').forEach(select => {
                let valorActual = select.value;
                Array.from(select.options).forEach(option => {
                    if (option.value !== "") {
                        // Deshabilitar si el socio ya está en otro select, a menos que sea el valor actual de este select
                        option.disabled = seleccionados.includes(option.value) && option.value !== valorActual;
                    }
                });
            });
        }

        // --- FUNCIÓN AGREGAR SOCIO AJUSTADA ---
        function agregarSocio() {
            // Verificar si aún hay socios disponibles para agregar
            let totalSociosDisponibles = socios.length;
            let sociosAgregados = document.querySelectorAll('.socio-row').length;

            if (sociosAgregados >= totalSociosDisponibles) {
                Swal.fire({
                    icon: 'info',
                    title: 'Límite alcanzado',
                    text: 'Ya has agregado a todos los socios disponibles.',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }

            let div = document.createElement('div');
            div.classList.add('socio-row');
            div.innerHTML = `
        <div class="row align-items-end">
            <div class="col-md-5">
                <label>Socio</label>
                <select name="socio_id[]" class="form-select select-socio" required>
                    <option value="">Seleccione...</option>
                    ${socios.map(s => `<option value="${s.id}">${s.nombre_completo} (${s.documento})</option>`).join('')}
                </select>
            </div>
            <div class="col-md-5">
                <label>Monto Aportado</label>
                <input type="text" class="form-control aporte-mask" placeholder="$ 0,00" required>
                <input type="hidden" name="socio_aporte[]" class="aporte-real">
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-danger btn-sm btnEliminar">X</button>
            </div>
        </div>`;

            contenedor.appendChild(div);

            const selectSocio = div.querySelector('.select-socio');
            const maskInput = div.querySelector('.aporte-mask');
            const realInput = div.querySelector('.aporte-real');

            // Al cambiar de socio, refrescamos la disponibilidad en los demás
            selectSocio.addEventListener('change', validarSociosDuplicados);

            maskInput.addEventListener('input', function() {
                let info = formatCurrency(this.value);
                this.value = info.display;
                realInput.value = info.real;
                actualizarTotal();
            });

            div.querySelector('.btnEliminar').addEventListener('click', () => {
                div.remove();
                validarSociosDuplicados(); // Liberar el socio para que pueda ser elegido de nuevo
                actualizarTotal();
            });

            // Ejecutar validación inicial para el nuevo select
            validarSociosDuplicados();
        }

        // --- ACTUALIZAR TOTALES ---
        function actualizarTotal() {
            let total = 0;
            document.querySelectorAll('.aporte-real').forEach(input => {
                total += parseFloat(input.value || 0);
            });

            let montoPrestamo = parseFloat(montoRealInput.value || 0);
            totalAportesSpan.innerText = total.toLocaleString('es-CO', {
                minimumFractionDigits: 2
            });

            // Cambio de color visual
            totalAportesSpan.style.color = (Math.abs(total - montoPrestamo) < 0.01) ? "green" : "red";
        }

        // --- VALIDACIÓN FINAL CON SWEETALERT ---
        formulario.addEventListener('submit', function(e) {
            let totalAportado = 0;
            document.querySelectorAll('.aporte-real').forEach(input => {
                totalAportado += parseFloat(input.value || 0);
            });

            let montoRequerido = parseFloat(montoRealInput.value || 0);

            // Usamos una pequeña tolerancia para evitar errores de precisión decimal
            if (Math.abs(totalAportado - montoRequerido) > 0.01) {
                e.preventDefault(); // Detener envío

                Swal.fire({
                    icon: 'error',
                    title: 'Los montos no coinciden',
                    text: `El total de aportes ($${totalAportado.toFixed(2)}) debe ser igual al monto del préstamo ($${montoRequerido.toFixed(2)}).`,
                    confirmButtonColor: '#0d6efd'
                });
            }
        });

        // Eventos iniciales
        document.getElementById('btnAgregarSocio').addEventListener('click', agregarSocio);
    </script>
    <script>
        const inputMonto = document.getElementById('monto');
        const inputReal = document.getElementById('monto_real');

        // ⛔ Bloquear letras y símbolos no permitidos al escribir
        inputMonto.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // 📋 Controlar pegado
        inputMonto.addEventListener('paste', function(e) {
            e.preventDefault();

            let texto = (e.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/[^\d]/g, '');

            if (texto === '') return;

            aplicarFormato(texto);
        });

        // 🔄 Formatear en tiempo real
        inputMonto.addEventListener('input', function() {
            let valor = this.value.replace(/[^\d]/g, '');
            aplicarFormato(valor);
        });

        // 🧠 Función central
        function aplicarFormato(valor) {

            if (!valor) {
                inputMonto.value = '';
                inputReal.value = '';
                return;
            }

            // Máximo 12 dígitos (ajusta si quieres)
            valor = valor.substring(0, 12);

            let numero = (parseInt(valor, 10) / 100).toFixed(2);

            // Guardar valor limpio para backend
            inputReal.value = numero;

            // Formato pesos
            inputMonto.value = '$ ' + numero
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
    </script>
    <script>
        const input = document.getElementById('tasa_interes');
        const errorMsg = document.getElementById('error-msg');

        input.addEventListener('input', function(e) {
            const startPos = this.selectionStart;
            const value = this.value;

            // Guardamos el valor limpio (solo números y un punto)
            const cleanValue = value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');

            if (value !== cleanValue) {
                // Mostrar error visual
                input.classList.add('input-error');
                errorMsg.style.display = 'block';

                // Aplicar la limpieza
                this.value = cleanValue;

                // Mantener la posición del cursor
                this.setSelectionRange(startPos - 1, startPos - 1);

                // Quitar el error después de 1.5 segundos
                setTimeout(() => {
                    input.classList.remove('input-error');
                    errorMsg.style.display = 'none';
                }, 1500);
            }
        });
    </script>
    <script>
        const inputEntero = document.getElementById('cantidad_entera');
        const errorMsgEntero = document.getElementById('error-msg-entero');

        inputEntero.addEventListener('input', function(e) {
            const value = this.value;

            // Filtro: Solo permite dígitos del 0 al 9
            const cleanValue = value.replace(/[^0-9]/g, '');

            if (value !== cleanValue) {
                // Activar feedback visual
                inputEntero.classList.add('input-error-entero');
                errorMsgEntero.style.display = 'block';

                // Aplicar limpieza inmediata
                this.value = cleanValue;

                // Ocultar mensaje tras un breve periodo
                setTimeout(() => {
                    inputEntero.classList.remove('input-error-entero');
                    errorMsgEntero.style.display = 'none';
                }, 1200);
            }
        });
    </script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!document.getElementById('monto_real').value) {
                e.preventDefault();
                alert('Debe ingresar un monto válido');
            }
        });
    </script>


</body>

</html>