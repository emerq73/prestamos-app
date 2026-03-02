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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
</script>