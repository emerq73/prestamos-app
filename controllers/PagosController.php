<?php
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/PrestamoDetalle.php';

class PagosController
{
    private $pagoModel;
    private $prestamoModel;
    private $detalleModel;

    public function __construct()
    {
        $this->pagoModel = new Pago();
        $this->prestamoModel = new Prestamo();
        $this->detalleModel = new PrestamoDetalle();
    }

    public function index()
    {
        require __DIR__ . '/../views/pagos/index.php';
    }

    /* =====================================================
       MOSTRAR FORMULARIO DE PAGO
    ===================================================== */
    public function crear()
    {
        $prestamoId = intval($_GET['prestamo_id'] ?? 0);

        if ($prestamoId <= 0) {
            echo "<div class='alert alert-danger'>Préstamo no especificado</div>";
            return;
        }

        require __DIR__ . '/../views/pagos/crear.php';
    }

    /* =====================================================
       GUARDAR PAGO
    ===================================================== */
    public function guardar()
    {
        try {
            $prestamoId = intval($_POST['prestamo_id']);
            $tipo = $_POST['tipo'] ?? null; // cuota | interes | total
            $metodo = $_POST['metodo_pago'] ?? null;
            $referencia = $_POST['referencia'] ?? null;
            $cuotasSel = $_POST['cuotas'] ?? [];

            if (!$prestamoId || !$tipo) {
                throw new Exception("Datos incompletos");
            }

            $items = [];
            $montoTotal = 0;

            /* =============================
               PAGO DE CUOTAS SELECCIONADAS
            ============================= */
            if ($tipo === 'cuota') {

                if (empty($cuotasSel)) {
                    throw new Exception("Seleccione al menos una cuota");
                }

                foreach ($cuotasSel as $detalleId) {
                    $detalle = $this->detalleModel->obtenerPorId($detalleId);

                    if ($detalle['estado'] === 'pagado')
                        continue;

                    $cap = $detalle['capital'] - $detalle['pagado_capital'];
                    $int = $detalle['interes'] - $detalle['pagado_interes'];

                    $items[] = [
                        'prestamos_detalle_id' => $detalleId,
                        'monto_capital' => $cap,
                        'monto_interes' => $int
                    ];

                    $montoTotal += ($cap + $int);
                }
            }

            /* =============================
               SOLO INTERESES
            ============================= */
            if ($tipo === 'interes') {

                if (count($cuotasSel) !== 1) {
                    throw new Exception("Seleccione una sola cuota para pago de intereses");
                }

                $detalleId = $cuotasSel[0];
                $detalle = $this->detalleModel->obtenerPorId($detalleId);

                $interesPendiente = $detalle['interes'] - $detalle['pagado_interes'];

                $items[] = [
                    'prestamos_detalle_id' => $detalleId,
                    'monto_capital' => 0,
                    'monto_interes' => $interesPendiente
                ];

                $montoTotal = $interesPendiente;
            }

            /* =============================
               PAGO TOTAL
            ============================= */
            /* =============================
               PAGO TOTAL (Liquidación)
            ============================= */
            if ($tipo === 'total') {
                $pagoId = $this->pagoModel->procesarPagoTotal($prestamoId, $metodo, $referencia);
                header("Location: /prestamos-app/views/dashboard.php?modulo=pagos&prestamo_id=$prestamoId&msg=pago_ok&tipo=total");
                exit;
            }

            // Registrar pago
            $this->pagoModel->crearPagoConItems(
                $prestamoId,
                $tipo,
                $montoTotal,
                $items,
                $metodo,
                $referencia
            );

            // verificar si préstamo queda cancelado
            $cuotas = $this->detalleModel->obtenerPorPrestamo($prestamoId);
            $pendientes = array_filter($cuotas, fn($c) => $c['estado'] !== 'pagado');

            if (count($pendientes) === 0) {
                $this->prestamoModel->actualizarEstado($prestamoId, 'cancelado');
            }

            header("Location: /prestamos-app/views/dashboard.php?modulo=pagos&prestamo_id=$prestamoId&msg=pago_ok&tipo=$tipo");
            exit;

        } catch (Exception $e) {
            $msg = urlencode($e->getMessage());
            header("Location: /prestamos-app/views/dashboard.php?modulo=pagos&action=crear&prestamo_id=$prestamoId&error=$msg");
            exit;
        }
    }
}
