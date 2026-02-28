<?php
$baseURL = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/prestamos-app";
?>
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-plus-circle me-2"></i> Registrar nuevo socio
    </div>
    <div class="card-body">
        <form action="dashboard.php?modulo=socios&action=crear" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" name="nombre_completo" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Documento</label>
                    <input type="text" name="documento" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Banco</label>
                    <input type="text" name="banco" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo de cuenta</label>
                    <select name="tipo_cuenta" class="form-select">
                        <option value="ahorros">Ahorros</option>
                        <option value="corriente">Corriente</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">N° cuenta</label>
                    <input type="text" name="nro_cuenta" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Aporte ($)</label>
                    <input type="text" name="aporte" id="aporte" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">% Participación</label>
                    <input type="number" step="0.01" name="porcentaje_participacion" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="dashboard.php?modulo=socios" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar socio
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    const inputAporte = document.getElementById('aporte');
    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    });

    inputAporte.addEventListener('input', function (e) {
        // eliminar caracteres que no sean números
        let valor = e.target.value.replace(/\D/g, '');
        // convertir a número
        let numero = parseInt(valor) || 0;
        // mostrar formateado
        e.target.value = formatter.format(numero);
    });

    // antes de enviar el formulario, le quitamos el formato para que llegue como número puro
    document.querySelector('form').addEventListener('submit', function () {
        let v = inputAporte.value.replace(/\D/g, '') || '0';
        inputAporte.value = v;
    });
</script>