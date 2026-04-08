<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../models/Pago.php';

// Validar ID
$pagoId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($pagoId <= 0) {
    die("ID de pago inválido");
}

$pagoModel = new Pago();
$pago = $pagoModel->obtenerDetallePago($pagoId);

if (!$pago) {
    die("Pago no encontrado");
}

// Configuración de media carta (Half Letter) vertical: 140mm x 216mm aprox
// O usamos formato custom: array(140, 216)
$pageLayout = array(140, 216); // Ancho, Alto en mm

class ReciboPDF extends TCPDF
{
    public function Header()
    {
        // Logo
        $logo = __DIR__ . '/../assets/logo.jpg';
        if (file_exists($logo)) {
            $this->Image($logo, 10, 5, 20);
        }
        $this->SetFont('helvetica', 'B', 12);
        $this->SetXY(40, 10);
        $this->Cell(0, 5, 'COMPROBANTE DE PAGO', 0, 1, 'L');
        $this->SetX(40);
        $this->SetFont('helvetica', '', 9);
        //$this->Cell(0, 5, 'RUC/NIT: 900.123.456-7', 0, 1, 'L');
    }
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Gracias por su pago', 0, 0, 'C');
    }
}

$pdf = new ReciboPDF('L', 'mm', $pageLayout, true, 'UTF-8', false);
$pdf->SetCreator('Sistema Préstamos');
$pdf->SetTitle('Recibo de Pago #' . $pago['id']);
$pdf->SetMargins(10, 30, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

// ESTILOS CSS SIMPLIFICADOS
$style = '
<style>
    table { width: 100%; border-collapse: collapse; font-family: helvetica; font-size: 10px; }
    th { background-color: #f0f0f0; font-weight: bold; border-bottom: 1px solid #000; padding: 4px; }
    td { border-bottom: 1px solid #ddd; padding: 4px; }
    .total { font-size: 11px; font-weight: bold; }
    .header-info { font-size: 10px; }
</style>';

// INFORMACIÓN CABECERA
$html = $style . '
<table class="header-info">
    <tr>
        <td width="60%">
            <b>Cliente:</b> ' . htmlspecialchars($pago['deudor_nombre']) . '<br>
            <b>Documento:</b> ' . htmlspecialchars($pago['deudor_documento']) . '
        </td>
        <td width="40%" align="right">
            <b>Recibo N°:</b> ' . ($pago['consecutivo'] ?? str_pad($pago['id'], 6, '0', STR_PAD_LEFT)) . '<br>
            <b>Fecha:</b> ' . date('d/m/Y H:i', strtotime($pago['fecha'])) . '
        </td>
    </tr>
</table>
<br><br>

<b>Detalle del Pago</b>
<table cellpadding="4">
    <thead>
        <tr>
            <th width="50%">Concepto</th>
            <th width="25%" align="right">Cuota N°</th>
            <th width="25%" align="right">Monto</th>
        </tr>
    </thead>
    <tbody>';

foreach ($pago['items'] as $item) {
    $concepto = 'Pago Cuota';
    if ($pago['tipo'] == 'interes')
        $concepto = 'Pago Intereses';

    $subtotal = $item['monto_capital'] + $item['monto_interes'];

    $html .= '
    <tr>
        <td>' . $concepto . '</td>
        <td align="right">' . $item['numero_cuota'] . '</td>
        <td align="right">$' . number_format($subtotal, 2) . '</td>
    </tr>';
}

$html .= '
    <tr>
        <td colspan="2" align="right" class="total">TOTAL PAGADO:</td>
        <td align="right" class="total">$' . number_format($pago['monto_total'], 2) . '</td>
    </tr>
</tbody>
</table>
<br>
<div>
    <b>Método de Pago:</b> ' . ucfirst($pago['metodo_pago']) . '<br>
    <b>Referencia:</b> ' . ($pago['referencia'] ?? '---') . '
</div>
<br><br>
<table border="0" cellpadding="0">
    <tr>
        <td width="60%"></td>
        <td width="40%" align="center">
            <br><br><br>
            __________________________<br>
            <b>Responsable</b><br>
            RAUL FELIPE VEGA
        </td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');

// Agregar la firma con el método Image (más fiable)
$firma = __DIR__ . '/../assets/firma_raul.png';
if (file_exists($firma)) {
    // Al ser alineado a la derecha, calculamos la posición
    $currentY = $pdf->GetY();
    // En media carta landscape (216mm ancho), la sección responsable está a la derecha.
    // X=148 centra la imagen sobre la línea en la celda del 40% derecho.
    // Movido un poco más arriba (-36)
    $pdf->Image($firma, 148, $currentY - 36, 35);
}

// SALIDA
if (isset($returnPDF) && $returnPDF) {
    $pdfContent = $pdf->Output('recibo_' . $pagoId . '.pdf', 'S');
} else {
    $pdf->Output('recibo_' . $pagoId . '.pdf', 'I');
    exit;
}
