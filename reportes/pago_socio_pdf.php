<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../models/PagoSocio.php';

if (!isset($_GET['id'])) {
    die("ID no proporcionado");
}

$pagoSocioModel = new PagoSocio();
$pago = $pagoSocioModel->obtenerPorId($_GET['id']);

if (!$pago) {
    die("Registro no encontrado");
}

class MYPDF extends TCPDF
{
    public function Header()
    {
        // 1. Draw Background Rect first
        $this->SetFillColor(20, 50, 100);
        $this->Rect(0, 0, 216, 25, 'F');

        // 2. Draw Logo on top of background
        $logo = __DIR__ . '/../assets/logo.jpg';
        if (!file_exists($logo)) {
            $logo = __DIR__ . '/../assets/logo.png'; // Try fallback
        }

        if (file_exists($logo)) {
            $this->Image($logo, 10, 5, 15); // Slightly smaller and to the left
        }

        // 3. Draw Text
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(0, 5);
        $this->Cell(0, 10, 'LD Holdings', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Informe de Rendimientos Mensuales', 0, 1, 'C');
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->SetCreator('LdHoldings');
$pdf->SetAuthor('LD Holdings');
$pdf->SetTitle('Informe de Rendimientos - ' . $pago['consecutivo']);

$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

$pdf->SetFillColor(250, 235, 215); // Beige claro
$pdf->SetFont('helvetica', '', 10);

// 4. Obtener detalle de préstamos
$items = $pagoSocioModel->obtenerItems($pago['id']);
$itemsHtml = '';
if (!empty($items)) {
    $itemsHtml = '
    <div style="background-color:#143264; color:white; font-weight:bold; padding:5px;">Detalle de Préstamos Liquidados</div>
    <table border="0.1" cellpadding="5">
        <tr style="background-color:#6495ED; color:white; font-weight:bold;">
            <th width="20%">N° Préstamo</th>
            <th width="20%">Aporte</th>
            <th width="15%">Tasa %</th>
            <th width="20%">Rendimiento</th>
            <th width="25%">Detalle</th>
        </tr>';
    foreach ($items as $it) {
        $itemsHtml .= '
        <tr>
            <td>' . $it['prestamo_consecutivo'] . '</td>
            <td>$' . number_format($it['aporte'], 0) . '</td>
            <td>' . number_format($it['tasa_socio'], 2) . '%</td>
            <td align="right">$' . number_format($it['rendimiento'], 2) . '</td>
            <td style="font-size:8pt;">' . htmlspecialchars($it['detalle']) . '</td>
        </tr>';
    }
    $itemsHtml .= '</table><br><br>';
}

$tbl = '
<table border="0.1" cellpadding="5" style="border-collapse: collapse;">
    <tr>
        <td width="40%" style="background-color:#FDF5E6;"><b>Nombre del Inversionista:</b></td>
        <td width="60%" style="background-color:#FFFFFF;"> ' . htmlspecialchars($pago['socio_nombre']) . '</td>
    </tr>
    <tr>
        <td style="background-color:#FDF5E6;"><b>ID del Inversionista:</b></td>
        <td style="background-color:#FFFFFF;"> ' . htmlspecialchars($pago['socio_documento']) . '</td>
    </tr>
    <tr>
        <td style="background-color:#FDF5E6;"><b>Mes / Año:</b></td>
        <td style="background-color:#FFFFFF;"> ' . $pago['mes'] . ' ' . $pago['anio'] . '</td>
    </tr>
    <tr>
        <td style="background-color:#FDF5E6;"><b>Fecha de Emisión:</b></td>
        <td style="background-color:#FFFFFF;"> ' . date('d/m/Y', strtotime($pago['fecha_emision'])) . '</td>
    </tr>
</table>
<br><br>
' . $itemsHtml . '
<div style="background-color:#143264; color:white; font-weight:bold; padding:5px;">Resumen del Portafolio</div>
<table border="0.1" cellpadding="5">
    <tr style="background-color:#6495ED; color:white; font-weight:bold;">
        <td width="70%">Concepto</td>
        <td width="30%" align="right">Valor COP</td>
    </tr>
    <tr>
        <td style="background-color:#FDF5E6;">Utilidades generadas</td>
        <td align="right" style="background-color:#FDF5E6;">$' . number_format($pago['utilidades_generadas'], 2) . '</td>
    </tr>
    <tr>
        <td style="background-color:#FFFFFF;">Saldo a favor anterior</td>
        <td align="right" style="background-color:#FFFFFF;">$' . number_format($pago['saldo_favor_anterior'], 2) . '</td>
    </tr>
    <tr>
        <td style="background-color:#FDF5E6;">Rendimiento mensual (%)</td>
        <td align="right" style="background-color:#FDF5E6;">' . number_format($pago['rendimiento_mensual_porc'], 2) . '%</td>
    </tr>
    <tr>
        <td style="background-color:#FFFFFF;">Deducciones</td>
        <td align="right" style="background-color:#FFFFFF;">$' . number_format($pago['deducciones'], 2) . '</td>
    </tr>
    <tr>
        <td style="background-color:#FDF5E6;">Impuestos</td>
        <td align="right" style="background-color:#FDF5E6;">$' . number_format($pago['impuestos'], 2) . '</td>
    </tr>
    <tr>
        <td style="background-color:#FFFFFF;">Ajustes</td>
        <td align="right" style="background-color:#FFFFFF;">$' . number_format($pago['ajustes'], 2) . '</td>
    </tr>
    <tr style="background-color:#143264; color:white; font-weight:bold;">
        <td>Saldo final del mes</td>
        <td align="right">$' . number_format($pago['saldo_final_mes'], 2) . '</td>
    </tr>
</table>
<br><br>
<div style="background-color:#143264; color:white; font-weight:bold; padding:5px;">Detalle del Pago de Rendimientos</div>
<table border="0.1" cellpadding="5">
    <tr style="background-color:#6495ED; color:white; font-weight:bold;">
        <td width="25%">Fecha de pago</td>
        <td width="25%">Medio de pago</td>
        <td width="25%" align="right">Valor pagado COP</td>
        <td width="25%">Observaciones</td>
    </tr>
    <tr>
        <td style="background-color:#FDF5E6;">' . ($pago['fecha_pago'] ? date('d/m/Y', strtotime($pago['fecha_pago'])) : '---') . '</td>
        <td style="background-color:#FDF5E6;">' . ($pago['medio_pago'] ?? '---') . '</td>
        <td align="right" style="background-color:#FDF5E6;">$' . number_format($pago['valor_pagado'], 2) . '</td>
        <td style="background-color:#FDF5E6;">' . htmlspecialchars($pago['observaciones'] ?? '') . '</td>
    </tr>
</table>
<br><br>
<table border="0" cellpadding="5">
    <tr>
        <td width="50%">
            <b>Resumen:</b><br>
            Se reciben los intereses generados en el periodo indicado por concepto de los aportes realizados a los préstamos listados anteriormente.
        </td>
    </tr>
    <br><br>
    <tr>
        <td width="50%">
            <br><br>
            __________________________<br>
            <b>Nombre del responsable:</b><br>
            ' . htmlspecialchars($pago['responsable'] ?? 'Felipe Vega') . '
        </td>
    </tr>
</table>
';

$pdf->writeHTML($tbl, true, false, false, false, '');

if (isset($returnPDF) && $returnPDF) {
    $pdfContent = $pdf->Output('Informe_Rendimientos_' . $pago['consecutivo'] . '.pdf', 'S');
} else {
    $pdf->Output('Informe_Rendimientos_' . $pago['consecutivo'] . '.pdf', 'I');
    exit;
}
