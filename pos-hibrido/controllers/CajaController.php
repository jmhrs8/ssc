<?php
// controllers/CajaController.php

namespace Controllers;

use Models\Caja;
use Exception;

class CajaController 
{
    private Caja $cajaModel;

    public function __construct() 
    {
        $this->cajaModel = new Caja();
    }

    /**
     * Muestra la vista de estado o apertura de caja.
     */
    public function index(): void 
    {
        // En un sistema real, se leen de la sesión del usuario
        $sucursalId = $_SESSION['sucursal_id'] ?? 1;
        $usuarioId  = $_SESSION['usuario_id']  ?? 1;

        $caja = $this->cajaModel->obtenerCajaAbierta($sucursalId, $usuarioId);

        if (!$caja) {
            require_once __DIR__ . '/../views/caja/apertura.php';
            return;
        }

        $flujo = $this->cajaModel->calcularFlujoEfectivo($caja['id']);
        require_once __DIR__ . '/../views/caja/estado.php';
    }

    /**
     * Procesa la apertura de caja desde el formulario.
     */
    public function abrir(): void 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=caja');
            exit;
        }

        try {
            $sucursalId    = (int) ($_SESSION['sucursal_id'] ?? 1);
            $usuarioId     = (int) ($_SESSION['usuario_id']  ?? 1);
            
            // Sanitización y validación del campo de entrada
            $montoApertura = filter_input(INPUT_POST, 'monto_apertura', FILTER_VALIDATE_FLOAT);

            if ($montoApertura === false || $montoApertura < 0) {
                throw new Exception("El monto de apertura debe ser un número mayor o igual a cero.");
            }

            $this->cajaModel->abrirCaja($sucursalId, $usuarioId, (float) $montoApertura);

            header('Location: index.php?route=caja&msg=caja_abierta');
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
            require_once __DIR__ . '/../views/caja/apertura.php';
        }
    }

    /**
     * Procesa el cierre y arqueo de dinero en caja.
     */
    public function cerrar(): void 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=caja');
            exit;
        }

        try {
            $cajaId          = filter_input(INPUT_POST, 'caja_id', FILTER_VALIDATE_INT);
            $montoCierreReal = filter_input(INPUT_POST, 'monto_cierre_real', FILTER_VALIDATE_FLOAT);
            $notas           = filter_input(INPUT_POST, 'notas', FILTER_SANITIZE_SPECIAL_CHARS);

            if (!$cajaId || $montoCierreReal === false || $montoCierreReal < 0) {
                throw new Exception("El monto ingresado para el cierre no es válido.");
            }

            $this->cajaModel->cerrarCaja($cajaId, (float) $montoCierreReal, $notas);

            header('Location: index.php?route=caja&msg=caja_cerrada');
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->index();
        }
    }
}
