<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Database.php';

if (!isset($_GET['id'])) {
    die("ID de socio no proporcionado");
}

$socioId = intval($_GET['id']);
$db = (new Database())->getConnection();

// 1. Obtener datos del socio
$socioModel = new Socio($db);
$socio = $socioModel->obtenerPorId($socioId);

if (!$socio) {
    die("Socio no encontrado");
}

// 2. Obtener préstamos activos con aportes
$sql = "SELECT p.consecutivo, p.monto as monto_prestamo, p.fecha_inicio, p.estado, 
               ps.aporte, ps.porcentaje_interes
        FROM prestamo_socios ps
        JOIN prestamos p ON p.id = ps.prestamo_id
        WHERE ps.socio_id = ? AND p.estado != 'cancelado_error'
        ORDER BY p.fecha_inicio DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$socioId]);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

class PortafolioPDF extends TCPDF
{
    public function Header()
    {
        // Fondo azul (Ajustado a ancho horizontal: 279mm)
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
        $this->Cell(0, 8, 'Resumen de Portafolio de Inversión', 0, 1, 'C');
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Generado el ' . date('d/m/Y H:i') . ' - Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
    }
}

$pdf = new PortafolioPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->SetCreator('LdHoldings');
$pdf->SetTitle('Portafolio - ' . $socio['nombre_completo']);
$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

$html = '
<h3 style="color:#143264; border-bottom: 2px solid #143264;">Información del Inversionista</h3>
<table cellpadding="5">
    <tr>
        <td width="30%"><b>Nombre:</b></td>
        <td width="70%">' . htmlspecialchars($socio['nombre_completo']) . '</td>
    </tr>
    <tr>
        <td><b>Documento:</b></td>
        <td>' . htmlspecialchars($socio['documento']) . '</td>
    </tr>
    <tr>
        <td><b>Email:</b></td>
        <td>' . htmlspecialchars($socio['email'] ?? 'N/A') . '</td>
    </tr>
</table>
<br><br>

<h3 style="color:#143264; border-bottom: 2px solid #143264;">Detalle de Inversiones Activas</h3>
<table border="0.1" cellpadding="5">
    <thead>
        <tr style="background-color:#143264; color:white; font-weight:bold;">
            <th width="15%">N° Préstamo</th>
            <th width="20%">Fecha Inicio</th>
            <th width="20%">Monto Aportado</th>
            <th width="10%">Tasa %</th>
            <th width="20%">Estado</th>
            <th width="15%">Rend. Est.</th>
        </tr>
    </thead>
    <tbody>';

$totalAportado = 0;
if (empty($prestamos)) {
    $html .= '<tr><td colspan="6" align="center">No se encontraron inversiones activas.</td></tr>';
} else {
    foreach ($prestamos as $p) {
        $totalAportado += $p['aporte'];
        $rendimientoEst = $p['aporte'] * ($p['porcentaje_interes'] / 100);
        $html .= '
        <tr>
            <td>' . ($p['consecutivo'] ?? '---') . '</td>
            <td>' . date('d/m/Y', strtotime($p['fecha_inicio'])) . '</td>
            <td align="right">$ ' . number_format($p['aporte'], 2) . '</td>
            <td align="center">' . number_format($p['porcentaje_interes'], 2) . '%</td>
            <td align="center">' . ucfirst($p['estado']) . '</td>
            <td align="right">$ ' . number_format($rendimientoEst, 2) . '</td>
        </tr>';
    }
}

$html .= '
    </tbody>
    <tfoot>
        <tr style="background-color:#F5F5F5; font-weight:bold;">
            <td colspan="2" align="right">TOTAL INVERTIDO:</td>
            <td align="right">$ ' . number_format($totalAportado, 2) . '</td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
</table>
<br><br>

<p style="font-size:10pt; text-align:justify;">
    Este documento relaciona los aportes de capital realizados por el socio a la fecha en los proyectos de préstamo gestionados por LD Holdings. 
    Los rendimientos se liquidan de manera mensual según lo estipulado en el acuerdo de inversión.
</p>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Portafolio_Inversion_' . str_replace(' ', '_', $socio['nombre_completo']) . '.pdf', 'I');
