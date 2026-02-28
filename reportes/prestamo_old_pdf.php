<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/PrestamoDetalle.php';
require_once __DIR__ . '/../models/Deudor.php';

if (!isset($_GET['id'])) {
    die("Falta el ID del préstamo");
}

$prestamoId = intval($_GET['id']);

$prestamoModel = new Prestamo();
$detalleModel  = new PrestamoDetalle();
$deudorModel  = new Deudor();

$prestamo = $prestamoModel->obtenerPorId($prestamoId);
$cuotas   = $detalleModel->obtenerPorPrestamo($prestamoId);
$deudor  = $deudorModel->obtenerPorId($prestamo['deudor_id']);

if (!$prestamo) { die("Préstamo no encontrado"); }

// ---------------------
// CREAR PDF
// ---------------------
$pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);

// TÍTULO Y CONFIG
$pdf->SetCreator('Sistema de Préstamos');
$pdf->SetAuthor('Sistema');
$pdf->SetTitle('Detalle del Préstamo');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// ---------------------
// ESTILOS
// ---------------------
$estiloTitulo = '
    <style>
    .titulo {
        font-size:18px;
        font-weight:bold;
        text-align:center;
        margin-bottom:15px;
    }
    .subtitulo {
        font-size:14px;
        font-weight:bold;
        background-color:#eeeeee;
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
        font-size:8px;
    }
    </style>
';

$html = $estiloTitulo;

// ---------------------
// TITULO
// ---------------------
$html .= '<div class="titulo">DETALLE DE PRÉSTAMO</div>';

// ---------------------
// DATOS DEL DEUDOR
// ---------------------
$html .= '
<div class="subtitulo">Datos del Deudor</div>
<table>
<tr><td><b>Nombre:</b></td><td>'. $deudor['nombre_completo'] .'</td></tr>
<tr><td><b>Doc. Identidad:</b></td><td>'. $deudor['documento'] .'</td></tr>
<tr><td><b>Teléfono:</b></td><td>'. $deudor['telefono'] .'</td></tr>
<tr><td><b>Dirección:</b></td><td>'. $deudor['direccion'] .'</td></tr>
</table>
';

// ---------------------
// DATOS DEL PRÉSTAMO
// ---------------------
$html .= '
<div class="subtitulo">Datos del Préstamo</div>
<table>
<tr><td><b>ID Préstamo:</b></td><td>'. $prestamo['id'] .'</td></tr>
<tr><td><b>Monto:</b></td><td>$'. number_format($prestamo['monto'],2) .'</td></tr>
<tr><td><b>Interés %:</b></td><td>'. $prestamo['tasa_interes'] .'%</td></tr>
<tr><td><b>Plazo (meses):</b></td><td>'. $prestamo['plazo'] .'</td></tr>
<tr><td><b>Fecha de inicio:</b></td><td>'. $prestamo['fecha_inicio'] .'</td></tr>
</table>
';

// ---------------------
// TABLA PLAN DE PAGOS
// ---------------------
$html .= '
<div class="subtitulo">Plan de Pagos</div>
<table>
<colgroup>
    <col width="5%">
    <col width="15%">
    <col width="13%">
    <col width="13%">
    <col width="14%">
    <col width="13%">   
    <col width="14%">
</colgroup>
<tr>
    <th>#</th>
    <th>Fecha</th>
    <th>Capital</th>
    <th>Interés</th>
    <th>Total</th>
    <th>Saldo</th>    
    <th>Estado</th>
</tr>
';

foreach ($cuotas as $c) {
    $html .= '
    <tr>
        <td>'. $c['numero_cuota'] .'</td>
        <td>'. $c['fecha_programada'] .'</td>
        <td>$'. number_format($c['capital'],2) .'</td>
        <td>$'. number_format($c['interes'],2) .'</td>
        <td>$'. number_format($c['total_cuota'],2) .'</td>
        <td>$'. number_format($c['saldo_restante'],2) .'</td>        
        <td>'. strtoupper($c['estado']) .'</td>
    </tr>';
}

$html .= '</table>';

// Renderizar HTML
$pdf->writeHTML($html, true, false, true, false, '');

// Descargar
$pdf->Output("prestamo_$prestamoId.pdf", "I");
exit;

