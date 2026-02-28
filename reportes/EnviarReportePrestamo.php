<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

ob_start(); // Prevenir cualquier salida accidental (warnings, etc)

// 👉 Recibir ID y configurar entorno para PDF
$prestamoId = $_POST['id'] ?? null;
if (!$prestamoId) {
    echo json_encode(['status' => 'error', 'msg' => 'ID de préstamo no proporcionado']);
    exit;
}

// Shim para que prestamo_pdf.php funcione
$_GET['id'] = $prestamoId;
$returnPDF = true; // Bandera para que devuelva string en lugar de output

// 👉 Incluimos el reporte (genera $pdfContent)
require_once __DIR__ . '/prestamo_pdf.php'; // versión que devuelve $pdfContent

$correoDestino = $_POST['email'];
$nombreCliente = $_POST['nombre'];

try {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'info@ldholdingsgroup.org';
    $mail->Password = 'Info9669**';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('info@ldholdingsgroup.org', 'Sistema de Préstamos');
    $mail->addAddress($correoDestino, $nombreCliente);

    $mail->Subject = 'Detalle de su Préstamo';
    $mail->Body = "Adjuntamos el detalle de su préstamo.\n\nGracias por su preferencia.";

    // 📎 Adjuntar PDF
    if (isset($pdfContent)) {
        $mail->addStringAttachment(
            $pdfContent,
            "prestamo_$prestamoId.pdf",
            'base64',
            'application/pdf'
        );
    }

    $mail->send();

    ob_end_clean(); // Limpiar cualquier salida previa si todo salió bien

    echo json_encode([
        'status' => 'ok',
        'msg' => 'Reporte enviado correctamente'
    ]);

} catch (Throwable $e) {
    ob_end_clean(); // Limpiar para asegurar que el JSON sea válido
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error: ' . $e->getMessage() . (isset($mail) ? ' | Mailer: ' . $mail->ErrorInfo : '')
    ]);
}