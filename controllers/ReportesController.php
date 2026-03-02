<?php
require_once __DIR__ . '/../models/PagoSocio.php';
require_once __DIR__ . '/../models/Socio.php';

class ReportesController
{
    private $pagoSocioModel;
    private $socioModel;

    public function __construct()
    {
        $this->pagoSocioModel = new PagoSocio();
        $this->socioModel = new Socio();
    }

    public function index()
    {
        if (($_SESSION['usuario']['rol'] ?? '') === 'socio') {
            header("Location: dashboard.php?modulo=reportes&action=pagos_socios");
            exit;
        }
        require_once __DIR__ . '/../views/reportes/index.php';
    }

    public function pagos_socios()
    {
        $socio_id = $this->getSocioIdLogueado();
        $pagos = $this->pagoSocioModel->obtenerTodos($socio_id);
        require_once __DIR__ . '/../views/reportes/pago_socio_lista.php';
    }

    private function getSocioIdLogueado()
    {
        if (($_SESSION['usuario']['rol'] ?? '') !== 'socio') {
            return null;
        }

        $email = $_SESSION['usuario']['email'] ?? '';
        $stmt = $this->socioModel->obtenerTodos(); // Reutilizamos el método para buscar el socio por email
        foreach ($stmt as $s) {
            if ($s['email'] === $email) {
                return $s['id'];
            }
        }
        return -1; // No encontrado
    }

    public function nuevo_pago_socio()
    {
        $socios = $this->socioModel->obtenerTodos();
        require_once __DIR__ . '/../views/reportes/pago_socio_crear.php';
    }

    public function guardar_pago_socio()
    {
        $data = $_POST;
        // Validación de duplicidad
        if ($this->pagoSocioModel->existeParaPeriodo($data['socio_id'], $data['mes'], $data['anio'])) {
            die("Error: Ya se ha generado una liquidación para este socio en el periodo seleccionado.");
        }

        // Recuperar items calculados (si vienen en el POST como JSON)
        $items = isset($_POST['items_json']) ? json_decode($_POST['items_json'], true) : [];

        try {
            $this->pagoSocioModel->guardar($data, $items);
            header("Location: dashboard.php?modulo=reportes&action=pagos_socios&mensaje=guardado");
        } catch (Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function calcular_rendimientos_ajax()
    {
        header('Content-Type: application/json');
        $socio_id = $_GET['socio_id'] ?? null;
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;

        if (!$socio_id || !$mes || !$anio) {
            echo json_encode(['error' => 'Faltan parámetros']);
            exit;
        }

        // Validación de duplicidad
        if ($this->pagoSocioModel->existeParaPeriodo($socio_id, $mes, $anio)) {
            echo json_encode(['error' => 'ALREADY_EXISTS', 'message' => "Ya se ha generado una liquidación para este socio en el periodo seleccionado ($mes $anio)."]);
            exit;
        }

        $res = $this->pagoSocioModel->obtenerRendimientosCalculados($socio_id, $mes, $anio);
        echo json_encode($res);
        exit;
    }

    public function portafolios_inversiones()
    {
        $socio_id = $this->getSocioIdLogueado();
        if ($socio_id) {
            $socio = $this->socioModel->obtenerPorId($socio_id);
            $socios = $socio ? [$socio] : [];
        } else {
            $socios = $this->socioModel->obtenerTodos();
        }
        require_once __DIR__ . '/../views/reportes/portafolios_socios.php';
    }

    public function reporte_general_prestamos()
    {
        require_once __DIR__ . '/../views/reportes/reporte_general_filtros.php';
    }

    public function reporte_general_pagos()
    {
        require_once __DIR__ . '/../views/reportes/reporte_pagos_filtros.php';
    }

    public function enviar_correo_pago_socio()
    {
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
            exit;
        }

        try {
            $pago = $this->pagoSocioModel->obtenerPorId($id);
            if (!$pago)
                throw new Exception("Registro no encontrado");

            $socio = $this->socioModel->obtenerPorId($pago['socio_id']);
            if (!$socio || empty($socio['email'])) {
                throw new Exception("El socio no tiene un correo electrónico registrado.");
            }

            // 1. Generar el PDF en memoria ($pdfContent)
            $_GET['id'] = $id;
            $returnPDF = true; // Variable que leerá pago_socio_pdf.php

            ob_start();
            require __DIR__ . '/../reportes/pago_socio_pdf.php';
            // Al terminar el require, $pdfContent tendrá los bytes del PDF (Output 'S')
            ob_end_clean();

            if (!isset($pdfContent) || empty($pdfContent)) {
                throw new Exception("Error al generar el PDF del informe.");
            }

            // 2. Usamos PHPMailer
            require_once __DIR__ . '/../vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Configuración SMTP Real (Copiada de EnviarReciboPago.php)
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'info@ldholdingsgroup.org';
            $mail->Password = 'Info9669**';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';

            // Destinatarios
            $mail->setFrom('info@ldholdingsgroup.org', 'Sistema LD Holdings');
            $mail->addAddress($socio['email'], $socio['nombre_completo']);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = "Informe de Rendimientos Mensuales - " . $pago['consecutivo'];
            $mail->Body = "Hola <b>{$socio['nombre_completo']}</b>,<br><br>Adjuntamos el informe de rendimientos correspondiente al periodo <b>{$pago['mes']} / {$pago['anio']}</b>.<br><br>Cordialmente,<br>Equipo LD Holdings";

            // Adjuntar PDF desde memoria
            $mail->addStringAttachment($pdfContent, "Informe_Rendimientos_{$pago['consecutivo']}.pdf", 'base64', 'application/pdf');

            // 3. Enviar
            $mail->send();

            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
