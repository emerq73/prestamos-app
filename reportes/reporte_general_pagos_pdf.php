<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../models/Database.php';

if (!isset($_GET['desde']) || !isset($_GET['hasta'])) {
    die("Faltan parámetros de fecha.");
}

$desde = $_GET['desde'];
$hasta = $_GET['hasta'];
$db = (new Database())->getConnection();

// 1. Obtener listado de pagos en el rango
$sql = "SELECT p.*, pr.consecutivo as prestamo_consecutivo, d.nombre_completo as deudor_nombre
        FROM pagos p
        JOIN prestamos pr ON pr.id = p.prestamo_id
        JOIN deudores d ON d.id = pr.deudor_id
        WHERE p.fecha BETWEEN ? AND ?
        ORDER BY p.fecha ASC, p.id ASC";
$stmt = $db->prepare($sql);
$stmt->execute([$desde, $hasta]);
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener desgloses (capital, interes, etc) si existen en pago_items
// Por ahora usaremos los totales de la tabla pagos, pero si quisiéramos detalle item por item:
foreach ($pagos as &$pago) {
    $stmtItems = $db->prepare("SELECT SUM(monto_capital) as t_cap, SUM(monto_interes) as t_int FROM pago_items WHERE pago_id = ?");
    $stmtItems->execute([$pago['id']]);
    $totales = $stmtItems->fetch(PDO::FETCH_ASSOC);
    $pago['desglose'] = $totales;
}

class PagosReportePDF extends TCPDF
{
    public $desde;
    public $hasta;

    public function Header()
    {
        $this->SetFillColor(20, 50, 100);
        $this->Rect(0, 0, 279, 25, 'F');

        $logo = __DIR__ . '/../assets/logo.jpg';
        if (!file_exists($logo))
            $logo = __DIR__ . '/../assets/logo.png';
        if (file_exists($logo)) {
            $this->Image($logo, 10, 5, 15);
        }

        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(0, 5);
        $this->Cell(0, 10, 'LD Holdings', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Reporte General de Pagos Recaudados (' . date('d/m/Y', strtotime($this->desde)) . ' al ' . date('d/m/Y', strtotime($this->hasta)) . ')', 0, 1, 'C');
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Generado el ' . date('d/m/Y H:i') . ' - Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
    }
}

$pdf = new PagosReportePDF('L', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->desde = $desde;
$pdf->hasta = $hasta;
$pdf->SetCreator('LdHoldings');
$pdf->SetTitle('Reporte General de Pagos');
$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

$html = '
<table border="0.1" cellpadding="4">
    <thead>
        <tr style="background-color:#143264; color:white; font-weight:bold;">
            <th width="10%">Fecha</th>
            <th width="10%" align="center">Préstamo</th>
            <th width="22%">Deudor</th>
            <th width="10%" align="center">Tipo</th>
            <th width="12%" align="right">Capital</th>
            <th width="12%" align="right">Interés</th>
            <th width="10%" align="right">Otros</th>
            <th width="14%" align="right">TOTAL</th>
        </tr>
    </thead>
    <tbody>';

$tCap = 0;
$tInt = 0;
$tOtr = 0;
$tTotal = 0;

foreach ($pagos as $p) {
    $cap = $p['desglose']['t_cap'] ?? 0;
    $int = $p['desglose']['t_int'] ?? 0;
    $otr = 0; // Mora/Seguros no desglosados en pago_items en esta versión
    $total = $p['monto_total'];

    $tCap += $cap;
    $tInt += $int;
    $tOtr += $otr;
    $tTotal += $total;

    $html .= '
    <tr>
        <td>' . date('d/m/Y', strtotime($p['fecha'])) . '</td>
        <td align="center">' . $p['prestamo_consecutivo'] . '</td>
        <td>' . htmlspecialchars($p['deudor_nombre']) . '</td>
        <td align="center">' . ucfirst($p['tipo']) . '</td>
        <td align="right">$ ' . number_format($cap, 2) . '</td>
        <td align="right">$ ' . number_format($int, 2) . '</td>
        <td align="right">$ ' . number_format($otr, 2) . '</td>
        <td align="right" style="font-weight:bold;">$ ' . number_format($total, 2) . '</td>
    </tr>';
}

if (empty($pagos)) {
    $html .= '<tr><td colspan="8" align="center">No se encontraron pagos en el rango seleccionado.</td></tr>';
}

$html .= '
    </tbody>
    <tfoot>
        <tr style="background-color:#F5F5F5; font-weight:bold;">
            <td colspan="4" align="right">TOTALES:</td>
            <td align="right">$ ' . number_format($tCap, 2) . '</td>
            <td align="right">$ ' . number_format($tInt, 2) . '</td>
            <td align="right">$ ' . number_format($tOtr, 2) . '</td>
            <td align="right">$ ' . number_format($tTotal, 2) . '</td>
        </tr>
    </tfoot>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Reporte_General_Pagos_' . $desde . '_al_' . $hasta . '.pdf', 'I');
