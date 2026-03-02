<?php
require_once __DIR__ . '/../../models/Deudor.php';
require_once __DIR__ . '/../../models/DeudorDocumento.php';

$baseURL = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/prestamos-app";
$deudorModel = new Deudor();
$docModel = new DeudorDocumento();

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='alert alert-danger'>ID no proporcionado.</div>";
    exit;
}

$deudor = $deudorModel->obtenerPorId($id);
$documentos = $docModel->obtenerPorDeudor($id);
?>

<div class="card shadow">
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-pencil-square me-2"></i> Editar acreedor
    </div>
    <div class="card-body">
        <form action="dashboard.php?modulo=deudores&action=editar" method="POST" enctype="multipart/form-data"
            autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?= $deudor['id'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" name="nombre_completo" class="form-control"
                        value="<?= $deudor['nombre_completo'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Documento</label>
                    <input type="text" name="documento" class="form-control" value="<?= $deudor['documento'] ?>"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= $deudor['telefono'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $deudor['email'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?= $deudor['direccion'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="activo" <?= $deudor['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= $deudor['estado'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control"
                        value="<?= $deudor['observaciones'] ?>">
                </div>

                <!-- 🔽 DOCUMENTOS ADJUNTOS -->
                <div class="col-md-12 mt-4">
                    <label class="form-label fw-bold">Documentos adjuntos</label>
                    <?php if (count($documentos) == 0): ?>
                        <div class="alert alert-warning">No hay documentos cargados.</div>
                    <?php else: ?>
                        <ul class="list-group mb-3">
                            <?php foreach ($documentos as $doc): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= basename($doc['archivo']) ?></span>
                                    <div class="btn-group">
                                        <a href="<?= $baseURL . '/' . $doc['archivo'] ?>" target="_blank"
                                            class="btn btn-sm btn-primary">
                                            Ver/Descargar
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger btn-eliminar-doc" data-doc="<?= $doc['id'] ?>"
                                            data-deudor="<?= $id ?>">
                                            Eliminar
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <label class="form-label">Subir nuevos documentos PDF</label>
                    <input type="file" name="documentos[]" accept="application/pdf" multiple class="form-control">
                </div>
                <!-- /DOCUMENTOS -->
            </div>

            <div class="d-flex justify-content-between">
                <a href="dashboard.php?modulo=deudores" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="btn-guardar" disabled>
                    <i class="bi bi-check-circle"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPT SweetAlert para eliminar documento -->
<script>
    const baseURL = "<?= $baseURL ?>";
    const form = document.querySelector('form');
    const btnGuardar = document.getElementById('btn-guardar');

    // Almacenar valores iniciales
    const initialValues = {};
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        if (input.type !== 'hidden' && input.type !== 'submit' && input.type !== 'file') {
            initialValues[input.name] = input.value;
        }
    });

    // Función para verificar cambios
    const checkChanges = () => {
        let hasChanges = false;

        // Verificar inputs normales
        inputs.forEach(input => {
            if (input.type !== 'hidden' && input.type !== 'submit' && input.type !== 'file') {
                if (input.value !== initialValues[input.name]) {
                    hasChanges = true;
                }
            }
            // Verificar input file (si tiene archivos seleccionados, es un cambio)
            if (input.type === 'file' && input.files.length > 0) {
                hasChanges = true;
            }
        });

        btnGuardar.disabled = !hasChanges;
    };

    // Listeners para detectar cambios
    inputs.forEach(input => {
        input.addEventListener('input', checkChanges);
        input.addEventListener('change', checkChanges);
    });

    // Lógica para eliminar documentos
    document.querySelectorAll('.btn-eliminar-doc').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const idDoc = this.dataset.doc;
            const idDeudor = this.dataset.deudor;

            Swal.fire({
                title: '¿Eliminar documento?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `${baseURL}/controllers/DocumentoDeudorController.php?eliminar=${idDoc}&deudor=${idDeudor}`;
                }
            });
        });
    });
</script>