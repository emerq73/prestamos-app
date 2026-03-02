<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../models/Database.php';

if (!isset($_GET['desde']) || !isset($_GET['hasta'])) {
    die("Faltan parámetros de fecha.");
}

$desde = $_GET['desde'];
$hasta = $_GET['hasta'];
$db = (new Database())->getConnection();

// 1. Obtener listado de préstamos en el rango
$sql = "SELECT p.*, d.nombre_completo as deudor_nombre, d.documento as deudor_documento
        FROM prestamos p
        JOIN deudores d ON d.id = p.deudor_id
        WHERE p.fecha_inicio BETWEEN ? AND ?
        ORDER BY p.fecha_inicio ASC, p.consecutivo ASC";
$stmt = $db->prepare($sql);
$stmt->execute([$desde, $hasta]);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

class GeneralReportePDF extends TCPDF
{
    public $desde;
    public $hasta;

    public function Header()
    {
        // Fondo azul
        $this->SetFillColor(20, 50, 100);
        $this->Rect(0, 0, 279, 25, 'F');

        // Logo
        $logo = __DIR__ . '/../assets/logo.jpg';
        if (!file_exists($logo))
            $logo = __DIR__ . '/../assets/logo.png';
        if (file_exists($logo)) {
            $this->Image($logo, 10, 5, 15);
        }

        // Texto
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(0, 5);
        $this->Cell(0, 10, 'LD Holdings', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Reporte General de Préstamos (' . date('d/m/Y', strtotime($this->desde)) . ' al ' . date('d/m/Y', strtotime($this->hasta)) . ')', 0, 1, 'C');
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Generado el ' . date('d/m/Y H:i') . ' - Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
    }
}

$pdf = new GeneralReportePDF('L', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->desde = $desde;
$pdf->hasta = $hasta;
$pdf->SetCreator('LdHoldings');
$pdf->SetTitle('Reporte General de Préstamos');
$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

$html = '
<table border="0.1" cellpadding="4">
    <thead>
        <tr style="background-color:#143264; color:white; font-weight:bold;">
            <th width="10%">N°</th>
            <th width="25%">Deudor</th>
            <th width="15%">Fecha Inicio</th>
            <th width="15%">Monto</th>
            <th width="10%">Tasa</th>
            <th width="10%">Plazo</th>
            <th width="15%">Estado</th>
        </tr>
    </thead>
    <tbody>';

$totalMonto = 0;
foreach ($prestamos as $p) {
    $totalMonto += $p['monto'];
    $html .= '
    <tr>
        <td>' . ($p['consecutivo'] ?? '---') . '</td>
        <td>' . htmlspecialchars($p['deudor_nombre']) . '</td>
        <td>' . date('d/m/Y', strtotime($p['fecha_inicio'])) . '</td>
        <td align="right">$ ' . number_format($p['monto'], 2) . '</td>
        <td align="center">' . $p['tasa_interes'] . '%</td>
        <td align="center">' . $p['plazo'] . '</td>
        <td align="center">' . ucfirst($p['estado']) . '</td>
    </tr>';
}

if (empty($prestamos)) {
    $html .= '<tr><td colspan="7" align="center">No se encontraron préstamos en el rango seleccionado.</td></tr>';
}

$html .= '
    </tbody>
    <tfoot>
        <tr style="background-color:#F5F5F5; font-weight:bold;">
            <td colspan="3" align="right">TOTALES (' . count($prestamos) . ' préstamos):</td>
            <td align="right">$ ' . number_format($totalMonto, 2) . '</td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Reporte_General_Prestamos_' . $desde . '_al_' . $hasta . '.pdf', 'I');
