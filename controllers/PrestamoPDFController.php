<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/PrestamoDetalle.php';

class PrestamoPDFController {

    public function generarPDF($prestamoId) {

        $prestamoModel = new Prestamo();
        $detalleModel = new PrestamoDetalle();

        $prestamo = $prestamoModel->obtenerPorId($prestamoId);
        $detalles = $detalleModel->obtenerPorPrestamo($prestamoId);

        if (!$prestamo) {
            die("Préstamo no encontrado.");
        }

        // -----------------------------
        // Inicializar TCPDF
        // -----------------------------
        $pdf = new TCPDF();
        $pdf->SetCreator('Sistema de Préstamos');
        $pdf->SetAuthor('Tu Cooperativa');
        $pdf->SetTitle('Detalle del Préstamo #' . $prestamoId);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        // -----------------------------
        // TÍTULO
        // ------------------------------
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'DETALLE DEL PRÉSTAMO', 0, 1, 'C');

        // -----------------------------
        // DATOS DEL DEUDOR
        // -----------------------------
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Información del Deudor', 0, 1);

        $pdf->SetFont('helvetica', '', 10);

        $html_deudor = '
        <table cellpadding="3">
            <tr>
                <td><b>Nombre:</b> ' . $prestamo['nombre_deudor'] . '</td>
                <td><b>Identificación:</b> ' . $prestamo['identificacion'] . '</td>
            </tr>
            <tr>
                <td><b>Teléfono:</b> ' . $prestamo['telefono'] . '</td>
                <td><b>Dirección:</b> ' . $prestamo['direccion'] . '</td>
            </tr>
        </table><br>';
        $pdf->writeHTML($html_deudor, false, false, false, false, '');

        // -----------------------------
        // DATOS DEL PRÉSTAMO
        // -----------------------------
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Información del Préstamo', 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        $html_prestamo = '
        <table cellpadding="3">
            <tr>
                <td><b>Capital:</b> $' . number_format($prestamo['capital'], 2) . '</td>
                <td><b>Interés (% mensual):</b> ' . $prestamo['interes'] . '%</td>
            </tr>
            <tr>
                <td><b>Plazo:</b> ' . $prestamo['plazo'] . ' meses</td>
                <td><b>Fecha Inicio:</b> ' . $prestamo['fecha_inicio'] . '</td>
            </tr>
        </table><br>';
        $pdf->writeHTML($html_prestamo, false, false, false, false, '');

        // -----------------------------
        // TABLA DEL PLAN DE PAGOS
        // -----------------------------
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Plan de Pagos', 0, 1);

        $pdf->SetFont('helvetica', '', 9);

        $tabla = '
        <table border="1" cellpadding="3">
            <thead>
                <tr style="background-color:#eaeaea;">
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Capital</th>
                    <th>Interés</th>
                    <th>Total Cuota</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($detalles as $d) {
            $tabla .= '
                <tr>
                    <td>' . $d['numero_cuota'] . '</td>
                    <td>' . $d['fecha_programada'] . '</td>
                    <td>$' . number_format($d['capital'], 2) . '</td>
                    <td>$' . number_format($d['interes'], 2) . '</td>
                    <td>$' . number_format($d['total_cuota'], 2) . '</td>
                    <td>$' . number_format($d['saldo_restante'], 2) . '</td>
                    <td>' . strtoupper($d['estado']) . '</td>
                </tr>';
        }

        $tabla .= '</tbody></table>';

        $pdf->writeHTML($tabla, true, false, true, false, '');

        // -----------------------------
        // Output
        // -----------------------------
        $pdf->Output("prestamo_$prestamoId.pdf", 'I'); // Mostrar en navegador
    }
}
