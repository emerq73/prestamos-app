<?php
require_once __DIR__ . '/../../models/Prestamo.php';
require_once __DIR__ . '/../../models/PrestamoDetalle.php';

$prestamoModel = new Prestamo();
$detalleModel = new PrestamoDetalle();

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='alert alert-danger'>ID no proporcionado</div>";
    exit;
}

$prestamo = $prestamoModel->obtenerPorId($id);
$detalles = $detalleModel->obtenerPorPrestamo($id);
?>

<div class="mb-3 d-flex justify-content-between align-items-center">
    <h4 class="fw-bold">
        <i class="bi bi-cash-stack me-2"></i>
        Préstamo #<?= $prestamo['id'] ?>
    </h4>

    <div>
        <a class="btn btn-secondary" href="dashboard.php?modulo=prestamos">
            <i class="bi bi-arrow-left"></i> Volver
        </a>

        <a class="btn btn-primary" href="#" onclick="enviarReporte(<?= $prestamo['id'] ?>)">
            <i class="bi bi-envelope-paper-fill"></i> Enviar por correo
        </a>

        <a href="../reportes/prestamo_pdf.php?id=<?= $prestamo['id'] ?>" target="_blank" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf-fill"></i> Descargar PDF
        </a>
    </div>
</div>

<!-- Información del deudor -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Información del Deudor</h5>
    </div>

    <div class="card-body">
        <div class="row">

            <!-- Columna izquierda -->
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0"><strong>ID Deudor:</strong>
                        <?= htmlspecialchars($prestamo['deudor_id']) ?></li>
                    <li class="list-group-item px-0"><strong>Nombre Deudor:</strong>
                        <?= htmlspecialchars($prestamo['deudor_nombre']) ?></li>
                    <li class="list-group-item px-0"><strong>Documento:</strong>
                        <?= htmlspecialchars($prestamo['deudor_documento']) ?></li>
                    <li class="list-group-item px-0"><strong>Fecha Inicio:</strong> <?= $prestamo['fecha_inicio'] ?>
                    </li>
                </ul>
            </div>

            <!-- Columna derecha -->
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0"><strong>Monto:</strong> <?= number_format($prestamo['monto'], 2) ?>
                    </li>
                    <li class="list-group-item px-0"><strong>Tasa:</strong> <?= $prestamo['tasa_interes'] ?> %
                        (<?= $prestamo['tipo_tasa'] ?>)</li>
                    <li class="list-group-item px-0"><strong>Plazo:</strong> <?= $prestamo['plazo'] ?> cuotas</li>
                </ul>
            </div>

        </div>
    </div>
</div>


<!-- Tabla de amortización (DEBAJO) -->
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white fw-bold">
        Tabla de Pagos
    </div>

    <div class="card-body">
        <?php include __DIR__ . '/_amortizacion_table.php'; ?>
    </div>
</div>

<script>
    function enviarReporte(id) {
        Swal.fire({
            title: 'Enviar reporte',
            input: 'email',
            inputLabel: 'Correo del cliente',
            inputPlaceholder: 'cliente@email.com',
            showCancelButton: true,
            confirmButtonText: 'Enviar'
        }).then((result) => {

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Enviando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../reportes/EnviarReportePrestamo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${result.value}&nombre=Cliente&id=${id}`
            })
                .then(async res => {
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Raw Response:', text);
                        throw new Error('Servidor devolvió una respuesta no válida: ' + text.substring(0, 100));
                    }
                })
                .then(data => {
                    if (data.status === 'ok') {
                        Swal.fire('Éxito', data.msg, 'success');
                    } else {
                        Swal.fire('Error', data.msg, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', err.message || 'Hubo un problema al enviar el correo', 'error');
                    console.error(err);
                });

        });
    }
</script>