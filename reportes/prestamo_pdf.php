<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/PrestamoDetalle.php';
require_once __DIR__ . '/../models/Deudor.php';

if (!isset($_GET['id'])) {
    die("Falta el ID del préstamo");
}

$prestamoId = intval($_GET['id']);
$usuarioGenera = 'Administrador'; // ← AJUSTA según sesión
$ciudad = 'Bogotá';

// --------------------------------
// CLASE PERSONALIZADA TCPDF
// --------------------------------
class MYPDF extends TCPDF
{
    public $consecutivo = '';

    public function Header()
    {

        $logo = __DIR__ . '/../assets/logo.jpg';

        if (file_exists($logo)) {
            $this->Image($logo, 15, 5, 20);
        }

        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 51, 102); // Azul oscuro
        $this->SetXY(45, 12);

        $consecText = $this->consecutivo ? ' - ' . $this->consecutivo : '';
        $this->Cell(0, 10, 'DETALLE DE PRÉSTAMO' . $consecText, 0, false, 'C');

        $this->SetDrawColor(0, 51, 102); // Azul oscuro para la línea
        $this->Line(15, 28, 200, 28);
        $this->SetTextColor(0, 0, 0);   // Reset a negro
        $this->SetDrawColor(0, 0, 0);   // Reset a negro
    }

    public function Footer()
    {

        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(
            0,
            10,
            'Página ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(),
            0,
            0,
            'C'
        );
    }
}

// --------------------------------
// MODELOS
// --------------------------------
$prestamoModel = new Prestamo();
$detalleModel = new PrestamoDetalle();
$deudorModel = new Deudor();

$prestamo = $prestamoModel->obtenerPorId($prestamoId);
$cuotas = $detalleModel->obtenerPorPrestamo($prestamoId);
$deudor = $deudorModel->obtenerPorId($prestamo['deudor_id']);

if (!$prestamo) {
    die("Préstamo no encontrado");
}

// --------------------------------
// CREAR PDF
// --------------------------------
$pdf = new MYPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->SetCreator('Sistema de Préstamos');
$pdf->SetAuthor($usuarioGenera);
$pdf->consecutivo = $prestamo['consecutivo'] ?? '';
$pdf->SetTitle('Detalle del Préstamo ' . $pdf->consecutivo);

$pdf->SetMargins(15, 35, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();


// --------------------------------
// ESTILOS
// --------------------------------
$html = '
<style>
.subtitulo {
    font-size:13px;
    font-weight:bold;
    background-color:#003366;
    color:#ffffff;
    padding:5px;
    margin-top:10px;
}
table {
    width:100%;
    border-collapse: collapse;
}
th {
    background-color:#f2f2f2;
    font-weight:bold;
    border:1px solid #bbb;
    padding:4px;
    font-size:11px;
}
td {
    border:1px solid #ccc;
    padding:4px;
    font-size:9px;
}
</style>
';

// --------------------------------
// DATOS DEUDOR
// --------------------------------
$html .= '
<div class="subtitulo">Datos del Acreedor</div>
<table>
<tr><td><b>Nombre</b></td><td>' . $deudor['nombre_completo'] . '</td></tr>
<tr><td><b>Documento</b></td><td>' . $deudor['documento'] . '</td></tr>
<tr><td><b>Teléfono</b></td><td>' . $deudor['telefono'] . '</td></tr>
<tr><td><b>Dirección</b></td><td>' . $deudor['direccion'] . '</td></tr>
</table>
';

// --------------------------------
// DATOS PRÉSTAMO
// --------------------------------
$html .= '
<div class="subtitulo">Datos del Préstamo</div>
<table>
<tr><td><b>Consecutivo</b></td><td>' . ($prestamo['consecutivo'] ?? 'N/A') . '</td></tr>
<tr><td><b>Monto</b></td><td>$' . number_format($prestamo['monto'], 2) . '</td></tr>
<tr><td><b>Interés</b></td><td>' . $prestamo['tasa_interes'] . '%</td></tr>
<tr><td><b>Plazo</b></td><td>' . $prestamo['plazo'] . '</td></tr>
<tr><td><b>Inicio</b></td><td>' . $prestamo['fecha_inicio'] . '</td></tr>
</table>
';

// --------------------------------
// PLAN DE PAGOS
// --------------------------------
$html .= '
<div class="subtitulo">Plan de Pagos</div>
<table>
<tr>
<th>#</th>
<th>Fecha</th>
<th>Capital</th>
<th>Interés</th>
<th>Total</th>
<th>Saldo</th>
<th>Estado</th>
</tr>';

foreach ($cuotas as $c) {
    $html .= '
    <tr>
        <td>' . $c['numero_cuota'] . '</td>
        <td>' . $c['fecha_programada'] . '</td>
        <td>$' . number_format($c['capital'], 2) . '</td>
        <td>$' . number_format($c['interes'], 2) . '</td>
        <td>$' . number_format($c['total_cuota'], 2) . '</td>
        <td>$' . number_format($c['saldo_restante'], 2) . '</td>
        <td>' . strtoupper($c['estado']) . '</td>
    </tr>';
}

$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');

// --------------------------------
// FIRMAS
// --------------------------------
$pdf->Ln(15);
$pdf->SetFont('helvetica', '', 10);

// Firma Responsable (Imagen si existe)
$firmaResp = __DIR__ . '/../assets/firma.png';
if (file_exists($firmaResp)) {
    // Posicionar imagen encima de la línea
    // Con PNG transparente, podemos bajarla un poco para que "pise" la línea profesionalmente
    $pdf->Image($firmaResp, 35, $pdf->GetY() - 10, 40, 0, 'PNG');
}

$pdf->Cell(80, 10, '_____________________________', 0, 0, 'C');
$pdf->Cell(30);
$pdf->Cell(80, 10, '_____________________________', 0, 1, 'C');

$pdf->Cell(80, 6, 'Firma Responsable', 0, 0, 'C');
$pdf->Cell(30);
$pdf->Cell(80, 6, 'Firma Cliente', 0, 1, 'C');

/* $pdf->Ln(10);
$pdf->Cell(0, 6, $ciudad.', '.date('d/m/Y'), 0, 1, 'C');
$pdf->Cell(0, 6, 'Documento generado por: '.$usuarioGenera, 0, 1, 'C');
 */
// --------------------------------
// SALIDA
// --------------------------------
if (isset($returnPDF) && $returnPDF) {
    $pdfContent = $pdf->Output("prestamo_$prestamoId.pdf", "S");
} else {
    $pdf->Output("prestamo_$prestamoId.pdf", "I");
    exit;
}
