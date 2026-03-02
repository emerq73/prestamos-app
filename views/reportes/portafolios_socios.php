<div class="mb-4">
    <h3 class="fw-bold"><i class="bi bi-wallet2 me-2"></i>Portafolios de Inversión</h3>
    <p class="text-muted">Seleccione un socio para generar su reporte de portafolio de inversión detallado.</p>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Socio</th>
                        <th>Documento</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($socios)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No hay socios registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($socios as $s): ?>
                            <tr>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($s['nombre_completo']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['documento']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $s['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($s['estado']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="../reportes/portafolio_socio_pdf.php?id=<?= $s['id'] ?>" target="_blank"
                                        class="btn btn-primary btn-sm">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Generar Portafolio
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="dashboard.php?modulo=reportes" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Volver a Reportes
    </a>
</div>