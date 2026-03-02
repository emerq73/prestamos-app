<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/PrestamoDetalle.php';
require_once __DIR__ . '/../models/PrestamoSocio.php';
require_once __DIR__ . '/../models/Pago.php';

class PrestamosController
{
    private $prestamoModel;
    private $socioModel;
    private $detalleModel;
    private $prestamoSocioModel;

    public function __construct()
    {
        $this->prestamoModel = new Prestamo();
        $this->socioModel = new Socio();
        $this->detalleModel = new PrestamoDetalle();
        $this->prestamoSocioModel = new PrestamoSocio();
    }

    public function index()
    {
        $estado = $_GET['estado'] ?? 'todos';

        $prestamos = $this->prestamoModel->obtenerTodos($estado);

        require_once __DIR__ . '/../views/prestamos/index.php';
    }

    /**
     * Guardar un nuevo préstamo
     */
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "Método no permitido";
            return;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            die('CSRF Error');
        }

        try {

            // =======================
            // VALIDAR CAMPOS
            // =======================
            if (
                empty($_POST['deudor_id']) ||
                !isset($_POST['monto']) || $_POST['monto'] === '' ||
                empty($_POST['tasa_interes']) ||
                empty($_POST['plazo']) ||
                empty($_POST['fecha_inicio'])
            ) {
                throw new Exception("Todos los campos obligatorios deben ser completados.");
            }

            // =======================
            // PREPARAR DATA
            // =======================
            $data = [
                'deudor_id' => $_POST['deudor_id'],
                'monto' => floatval($_POST['monto']),
                'tasa_interes' => floatval($_POST['tasa_interes']),
                'plazo' => intval($_POST['plazo']),
                'tipo_tasa' => $_POST['tipo_tasa'] ?? 'mensual',
                'periodo_pago' => $_POST['periodo_pago'] ?? 'mensual',
                'fecha_inicio' => $_POST['fecha_inicio'],
                'observaciones' => $_POST['observaciones'] ?? null
            ];

            // =======================
            // 1️⃣ CREAR PRÉSTAMO
            // =======================
            $prestamoId = $this->prestamoModel->crear($data);

            if (!$prestamoId) {
                throw new Exception("No se pudo crear el préstamo. Verifica los datos.");
            }

            // =======================
            // 2️⃣ REGISTRAR APORTES (SOCIOS)
            // =======================
            if (!empty($_POST['socio_id']) && is_array($_POST['socio_id'])) {

                $totalAportes = 0;
                foreach ($_POST['socio_aporte'] as $monto) {
                    $totalAportes += floatval($monto);
                }

                if ($totalAportes != $data['monto']) {
                    throw new Exception(
                        "El total de aportes ($totalAportes) debe coincidir con el monto del préstamo ({$data['monto']})."
                    );
                }

                // Registrar cada socio con aporte y porcentaje
                for ($i = 0; $i < count($_POST['socio_id']); $i++) {

                    $aporte = floatval($_POST['socio_aporte'][$i]);
                    $porcentaje = ($aporte / $data['monto']) * 100;
                    $socio_interes = floatval($_POST['socio_interes'][$i] ?? 0);

                    $this->prestamoSocioModel->crear([
                        'prestamo_id' => $prestamoId,
                        'socio_id' => $_POST['socio_id'][$i],
                        'aporte' => $aporte,
                        'porcentaje' => $porcentaje,
                        'porcentaje_interes' => $socio_interes
                    ]);
                }
            }

            // =======================
            // 3️⃣ GENERAR AMORTIZACIÓN
            // =======================
            $this->prestamoModel->generarAmortizacion($prestamoId, true);

            // =======================
            // 4️⃣ REDIRECCIÓN CORRECTA
            // =======================
            header("Location: /prestamos-app/views/dashboard.php?modulo=prestamos&msg=creado");
            exit;
        } catch (Exception $e) {

            $msg = urlencode($e->getMessage());
            header("Location: /prestamos-app/views/dashboard.php?modulo=prestamos&error=$msg");
            exit;
        }
    }

    /**
     * Obtener todos los préstamos
     */
    public function listar()
    {
        return $this->prestamoModel->obtenerTodos();
    }

    /**
     * Ver detalle de préstamo
     */
    public function ver()
    {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            header("Location: dashboard.php?modulo=prestamos&error=ID inválido");
            exit;
        }

        $prestamo = $this->prestamoModel->obtenerPorId($id);
        $detalles = $this->detalleModel->obtenerPorPrestamo($id);
        $socios = $this->prestamoSocioModel->obtenerPorPrestamo($id);

        require_once __DIR__ . '/../views/prestamos/ver.php';
    }

    /**
     * Actualizar estado del préstamo
     */
    public function actualizarEstado()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "Método no permitido";
            return;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            die('CSRF Error');
        }

        $id = $_POST['prestamo_id'];
        $estado = $_POST['estado'];

        $this->prestamoModel->actualizarEstado($id, $estado);

        header("Location: /prestamos-app/views/dashboard.php?modulo=prestamos&action=ver&id=$id");
        exit;
    }

    public function eliminar()
    {
        if (!isset($_GET['id'])) {
            header("Location: dashboard.php?modulo=prestamos&error=ID no válido");
            exit;
        }

        try {
            // Verificar si tiene pagos
            $pagoModel = new Pago();
            $pagos = $pagoModel->obtenerPagosPorPrestamo($_GET['id']);

            if (!empty($pagos)) {
                $msg = "El préstamo está en ejecución y tiene pagos aplicados. No se puede eliminar.";
                header("Location: dashboard.php?modulo=prestamos&error=tiene_pagos&msg=" . urlencode($msg));
                exit;
            }

            $model = new Prestamo();
            $model->eliminar($_GET['id']);

            header("Location: dashboard.php?modulo=prestamos&msg=eliminado");
            exit;
        } catch (Exception $e) {
            header("Location: dashboard.php?modulo=prestamos&error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}
