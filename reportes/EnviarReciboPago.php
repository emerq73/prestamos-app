<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

ob_start();

// 1. Recibir ID y Email
$pagoId = $_POST['pago_id'] ?? null;
$email = $_POST['email'] ?? null;

if (!$pagoId || !$email) {
    echo json_encode(['status' => 'error', 'msg' => 'Faltan datos (ID o Email)']);
    exit;
}

// 2. Configurar entorno para generar PDF en memoria
$_GET['id'] = $pagoId;
$returnPDF = true; // Para que el script PDF no haga echo, sino retorne string

ob_start(); // Prevenir salidas indeseadas
require __DIR__ . '/recibo_pago_pdf.php';
ob_end_clean();

// verifica si se generó content
if (!isset($pdfContent)) {
    echo json_encode(['status' => 'error', 'msg' => 'Error al generar el PDF del recibo']);
    exit;
}

// 3. Enviar Correo
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
    $mail->addAddress($email);

    $mail->Subject = 'Comprobante de Pago #' . str_pad($pagoId, 6, '0', STR_PAD_LEFT);
    $mail->Body = "Adjuntamos su comprobante de pago.\n\nGracias por su puntualidad.";

    // Adjuntar PDF desde memoria
    $mail->addStringAttachment($pdfContent, "recibo_$pagoId.pdf", 'base64', 'application/pdf');

    ob_end_clean();
    echo json_encode(['status' => 'ok', 'msg' => 'Recibo enviado correctamente.']);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error: ' . $e->getMessage() . (isset($mail) ? ' | Mailer: ' .
            $mail->ErrorInfo : '')
    ]);
}