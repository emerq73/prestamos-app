<?php
require_once __DIR__ . '/../../models/Socio.php';
$baseURL = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/prestamos-app";

$socioModel = new Socio();
$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<div class='alert alert-danger'>ID no proporcionado.</div>";
    exit;
}
$socio = $socioModel->obtenerPorId($id);
?>

<div class="card shadow">
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-pencil-square me-2"></i> Editar socio
    </div>
    <div class="card-body">
        <form action="dashboard.php?modulo=socios&action=editar" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?= $socio['id'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" name="nombre_completo" class="form-control"
                        value="<?= $socio['nombre_completo'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Documento</label>
                    <input type="text" name="documento" class="form-control" value="<?= $socio['documento'] ?>"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= $socio['telefono'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $socio['email'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?= $socio['direccion'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Banco</label>
                    <input type="text" name="banco" class="form-control" value="<?= $socio['banco'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo de cuenta</label>
                    <select name="tipo_cuenta" class="form-select">
                        <option value="ahorros" <?= $socio['tipo_cuenta'] == 'ahorros' ? 'selected' : '' ?>>Ahorros
                        </option>
                        <option value="corriente" <?= $socio['tipo_cuenta'] == 'corriente' ? 'selected' : '' ?>>Corriente
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">N° cuenta</label>
                    <input type="text" name="nro_cuenta" class="form-control" value="<?= $socio['nro_cuenta'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Aporte ($)</label>
                    <input type="text" name="aporte" id="aporte" class="form-control" value="<?= $socio['aporte'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">% Participación</label>
                    <input type="number" step="0.01" name="porcentaje_participacion" class="form-control"
                        value="<?= $socio['porcentaje_participacion'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="activo" <?= $socio['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= $socio['estado'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control"
                        rows="3"><?= $socio['observaciones'] ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="dashboard.php?modulo=socios" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="btn-guardar" disabled>
                    <i class="bi bi-check-circle"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    const inputAporte = document.getElementById('aporte');
    const form = document.querySelector('form');
    const btnGuardar = document.getElementById('btn-guardar');

    // Almacenar valores iniciales
    const initialValues = {};
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        if (input.type !== 'hidden' && input.type !== 'submit') {
            initialValues[input.name] = input.value;
        }
    });

    // Función para verificar cambios
    const checkChanges = () => {
        let hasChanges = false;
        inputs.forEach(input => {
            if (input.type !== 'hidden' && input.type !== 'submit') {
                if (input.value !== initialValues[input.name]) {
                    hasChanges = true;
                }
            }
        });
        btnGuardar.disabled = !hasChanges;
    };

    // Listeners para detectar cambios
    inputs.forEach(input => {
        input.addEventListener('input', checkChanges);
        input.addEventListener('change', checkChanges);
    });

    // Formateador para moneda colombiana (puedes cambiar COP por tu moneda)
    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    });

    // ✏️ — Formatear el valor inicial que viene de PHP
    if (inputAporte.value !== "") {
        let numero = parseInt(inputAporte.value);
        inputAporte.value = formatter.format(numero);
        // Actualizar valor inicial formateado para correcta comparación
        initialValues['aporte'] = inputAporte.value;
    }

    // 🟡 — Cada vez que escribe el usuario
    inputAporte.addEventListener('input', function (e) {
        let valor = e.target.value.replace(/\D/g, ''); // quitar todo lo que no sea número
        let numero = parseInt(valor) || 0;
        e.target.value = formatter.format(numero);
        checkChanges(); // Verificar cambios después del formateo
    });

    // ✅ — Antes de enviar, quitar formato para que llegue como número puro
    form.addEventListener('submit', function () {
        let v = inputAporte.value.replace(/\D/g, '') || '0';
        inputAporte.value = v;
    });
</script>