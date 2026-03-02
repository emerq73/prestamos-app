<?php
require_once __DIR__ . '/../../models/Consecutivo.php';

$consecutivoModel = new Consecutivo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar') {
    $tipo = $_POST['tipo_documento'];
    $prefijo = $_POST['prefijo'];
    $siguiente = intval($_POST['siguiente_numero']);
    $longitud = intval($_POST['longitud']);

    if ($consecutivoModel->actualizarConfiguracion($tipo, $prefijo, $siguiente, $longitud)) {
        echo "<script>Swal.fire('Éxito', 'Configuración actualizada', 'success');</script>";
    } else {
        echo "<script>Swal.fire('Error', 'No se pudo actualizar', 'error');</script>";
    }
}

$consecutivos = $consecutivoModel->obtenerTodos();
?>

<div class="mb-3">
    <h4 class="fw-bold"><i class="bi bi-hash me-2"></i>Administración de Consecutivos</h4>
    <p class="text-muted">Configura los prefijos y la numeración para los diferentes documentos del sistema.</p>
</div>

<div class="row">
    <?php foreach ($consecutivos as $c): ?>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white fw-bold">
                    <?= strtoupper($c['tipo_documento']) ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="actualizar">
                        <input type="hidden" name="tipo_documento" value="<?= $c['tipo_documento'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Prefijo</label>
                            <input type="text" name="prefijo" class="form-control"
                                value="<?= htmlspecialchars($c['prefijo']) ?>" placeholder="Ej: PR-">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Siguiente Número</label>
                                <input type="number" name="siguiente_numero" class="form-control"
                                    value="<?= $c['siguiente_numero'] ?>" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Longitud (Ceros)</label>
                                <input type="number" name="longitud" class="form-control" value="<?= $c['longitud'] ?>"
                                    min="1" max="10">
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="text-muted small">Vista previa ej:</label>
                            <span class="badge bg-info text-dark">
                                <?= $c['prefijo'] . str_pad($c['siguiente_numero'], $c['longitud'], '0', STR_PAD_LEFT) ?>
                            </span>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>