<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">
        <i class="bi bi-list-ul me-2"></i> Historial de Rendimientos Mensuales
    </h3>
    <?php if (($_SESSION['usuario']['rol'] ?? '') !== 'socio'): ?>
        <a href="dashboard.php?modulo=reportes&action=nuevo_pago_socio" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Informe
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'creado'): ?>
    <script>
        Swal.fire('¡Éxito!', 'El informe de rendimientos ha sido generado.', 'success');
    </script>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Consecutivo</th>
                        <th>Socio</th>
                        <th>Periodo</th>
                        <th>Fecha Emisión</th>
                        <th>Valor Pagado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagos)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay informes generados aún.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pagos as $p): ?>
                            <tr>
                                <td><span class="badge bg-info text-dark">
                                        <?= $p['consecutivo'] ?>
                                    </span></td>
                                <td>
                                    <?= htmlspecialchars($p['socio_nombre']) ?>
                                </td>
                                <td>
                                    <?= $p['mes'] ?> /
                                    <?= $p['anio'] ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($p['fecha_emision'])) ?>
                                </td>
                                <td class="fw-bold text-success">$
                                    <?= number_format($p['valor_pagado'], 2) ?>
                                </td>
                                <td>
                                    <a href="../reportes/pago_socio_pdf.php?id=<?= $p['id'] ?>" target="_blank"
                                        class="btn btn-sm btn-danger">
                                        <i class="bi bi-file-earmark-pdf"></i> PDF
                                    </a>
                                    <button onclick="enviarEmail(<?= $p['id'] ?>)" class="btn btn-sm btn-outline-primary ms-1">
                                        <i class="bi bi-envelope"></i> Email
                                    </button>
                                    
                                    <?php if (!empty($p['evidencia_pago'])): ?>
                                        <a href="https://drive.google.com/file/d/<?= $p['evidencia_pago'] ?>/view?usp=sharing" 
                                           target="_blank" class="btn btn-sm btn-success ms-1" title="Ver Evidencia">
                                            <i class="bi bi-google"></i>
                                        </a>
                                        <?php if (($_SESSION['usuario']['rol'] ?? '') !== 'socio'): ?>
                                            <button onclick="eliminarEvidencia(<?= $p['id'] ?>)" class="btn btn-sm btn-danger ms-1" title="Eliminar Evidencia">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif (($_SESSION['usuario']['rol'] ?? '') !== 'socio'): ?>
                                        <button onclick="prepararSubida(<?= $p['id'] ?>, '<?= $p['consecutivo'] ?>')" 
                                                class="btn btn-sm btn-warning ms-1" title="Adjuntar Evidencia">
                                            <i class="bi bi-paperclip"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para subir evidencia -->
<div class="modal fade" id="modalEvidencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjuntar Evidencia de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEvidencia">
                <div class="modal-body">
                    <p id="txtPagoAfecado" class="fw-bold"></p>
                    <input type="hidden" name="pago_socio_id" id="pago_socio_id_input">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar archivo (PDF o Imagen)</label>
                        <input type="file" name="evidencia" class="form-control" required accept="image/*,.pdf">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEvidencia">Subir a Drive</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (($_SESSION['usuario']['rol'] ?? '') !== 'socio'): ?>
    <div class="mt-3">
        <a href="dashboard.php?modulo=reportes" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Reportes
        </a>
    </div>
<?php endif; ?>

<script>
    let modalEvidenciaInstance = null;

    function prepararSubida(id, consecutivo) {
        if (!modalEvidenciaInstance) {
            modalEvidenciaInstance = new bootstrap.Modal(document.getElementById('modalEvidencia'));
        }
        document.getElementById('pago_socio_id_input').value = id;
        document.getElementById('txtPagoAfecado').innerText = "Recibo: " + consecutivo;
        modalEvidenciaInstance.show();
    }

    document.getElementById('formEvidencia').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btnGuardarEvidencia');
        btn.disabled = true;
        btn.innerText = "Subiendo...";

        const formData = new FormData(this);

        fetch('dashboard.php?modulo=reportes&action=subir_evidencia_pago', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire('¡Éxito!', 'La evidencia ha sido cargada correctamente.', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.error || 'No se pudo subir la evidencia.', 'error');
                btn.disabled = false;
                btn.innerText = "Subir a Drive";
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Error de conexión.', 'error');
            btn.disabled = false;
            btn.innerText = "Subir a Drive";
        });
    });

    function enviarEmail(id) {
        Swal.fire({
            title: '¿Enviar por correo?',
            text: "Se enviará el informe al correo registrado del socio.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Enviando...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`dashboard.php?modulo=reportes&action=enviar_correo_pago_socio&id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Enviado!', 'El correo ha sido enviado exitosamente al socio.', 'success');
                        } else {
                            Swal.fire('Error', data.error || 'No se pudo enviar el correo.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Ocurrió un error en la conexión.', 'error');
                    });
            }
        });
    }

    function eliminarEvidencia(id) {
        Swal.fire({
            title: '¿Eliminar evidencia?',
            text: "El archivo se borrará permanentemente de Google Drive.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Eliminando...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`dashboard.php?modulo=reportes&action=eliminar_evidencia_pago&id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Eliminado!', 'La evidencia ha sido borrada.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.error || 'No se pudo eliminar la evidencia.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Ocurrió un error en la conexión.', 'error');
                    });
            }
        });
    }
</script>