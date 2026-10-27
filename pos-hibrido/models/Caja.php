<?php
// models/Caja.php

namespace Models;

use Config\Database;
use PDO;
use Exception;

class Caja 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener la caja activa de un usuario en una sucursal dada.
     */
    public function obtenerCajaAbierta(int $sucursalId, int $usuarioId): ?array 
    {
        $sql = "SELECT * FROM cajas 
                WHERE sucursal_id = :sucursal_id 
                  AND usuario_id = :usuario_id 
                  AND estado = 'abierta' 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sucursal_id' => $sucursalId,
            ':usuario_id'  => $usuarioId
        ]);

        $caja = $stmt->fetch();
        return $caja ?: null;
    }

    /**
     * Abrir un nuevo turno/arqueo de caja.
     */
    public function abrirCaja(int $sucursalId, int $usuarioId, float $montoApertura): bool 
    {
        if ($this->obtenerCajaAbierta($sucursalId, $usuarioId)) {
            throw new Exception("Ya existe una caja abierta para este usuario en esta sucursal.");
        }

        $sql = "INSERT INTO cajas (sucursal_id, usuario_id, monto_apertura, estado, fecha_apertura) 
                VALUES (:sucursal_id, :usuario_id, :monto_apertura, 'abierta', NOW())";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':sucursal_id'   => $sucursalId,
            ':usuario_id'    => $usuarioId,
            ':monto_apertura' => $montoApertura
        ]);
    }

    /**
     * Calcular el flujo total de ventas y el desglose por método de pago para una caja abierta.
     */
    public function calcularFlujoEfectivo(int $cajaId): array 
    {
        // Totales desglosados por método de pago
        $sql = "SELECT 
                    pv.metodo_pago,
                    COALESCE(SUM(pv.monto), 0) AS total_metodo
                FROM ventas v
                INNER JOIN pagos_venta pv ON v.id = pv.venta_id
                WHERE v.caja_id = :caja_id 
                  AND v.estado = 'completada'
                GROUP BY pv.metodo_pago";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $cajaId]);
        $pagos = $stmt->fetchAll();

        $desglose = [
            'efectivo'      => 0.00,
            'tarjeta'       => 0.00,
            'transferencia' => 0.00,
            'total_ventas'  => 0.00
        ];

        foreach ($pagos as $pago) {
            $metodo = $pago['metodo_pago'];
            $monto  = (float) $pago['total_metodo'];

            if (array_key_exists($metodo, $desglose)) {
                $desglose[$metodo] = $monto;
            }
            $desglose['total_ventas'] += $monto;
        }

        // Obtener monto con el que abrió la caja
        $sqlCaja = "SELECT monto_apertura FROM cajas WHERE id = :caja_id";
        $stmtCaja = $this->db->prepare($sqlCaja);
        $stmtCaja->execute([':caja_id' => $cajaId]);
        $caja = $stmtCaja->fetch();

        $desglose['monto_apertura'] = $caja ? (float) $caja['monto_apertura'] : 0.00;
        // El dinero en efectivo teórico esperado es: Apertura + Ventas en Efectivo
        $desglose['efectivo_teorico_esperado'] = $desglose['monto_apertura'] + $desglose['efectivo'];

        return $desglose;
    }

    /**
     * Cerrar caja y calcular las diferencias físicas vs teóricas (Arqueo).
     */
    public function cerrarCaja(int $cajaId, float $montoCierreReal, ?string $notas = null): bool 
    {
        $flujo = $this->calcularFlujoEfectivo($cajaId);

        $montoTeorico = $flujo['efectivo_teorico_esperado'];
        $diferencia = $montoCierreReal - $montoTeorico; // Negativo = Faltante, Positivo = Sobrante

        $sql = "UPDATE cajas SET 
                    monto_cierre_teorico = :monto_teorico,
                    monto_cierre_real    = :monto_real,
                    diferencia           = :diferencia,
                    estado               = 'cerrada',
                    fecha_cierre         = NOW(),
                    notas                = :notas
                WHERE id = :caja_id AND estado = 'abierta'";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':monto_teorico' => $montoTeorico,
            ':monto_real'    => $montoCierreReal,
            ':diferencia'    => $diferencia,
            ':notas'         => $notas,
            ':caja_id'       => $cajaId
        ]);
    }
}
