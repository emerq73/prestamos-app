<?php
$baseURL = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/prestamos-app";
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-person-plus me-2"></i> Registrar nuevo acreedor
    </div>

    <div class="card-body">
        <form action="dashboard.php?modulo=deudores&action=crear" method="POST" enctype="multipart/form-data"
            autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3">
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
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label">Adjuntar documentos (PDF opcional)</label>
                    <input type="file" name="documentos[]" multiple accept="application/pdf" class="form-control">
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="dashboard.php?modulo=deudores" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Guardar acreedor
                </button>
            </div>
        </form>
    </div>
</div>