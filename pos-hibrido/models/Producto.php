<?php
// models/Producto.php

namespace Models;

use Config\Database;
use PDO;

class Producto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listarPorModulo(string $modulo = 'retail'): array
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre 
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.activo = 1";

        if ($modulo === 'cafeteria') {
            $sql .= " AND p.tipo_modulo IN ('cafeteria', 'ambos')";
        } else {
            $sql .= " AND p.tipo_modulo IN ('retail', 'ambos')";
        }

        $sql .= " ORDER BY c.nombre ASC, p.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarCategorias(): array
    {
        $stmt = $this->db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }

    public function guardar(array $datos): bool
    {
        $sql = "INSERT INTO productos (codigo_barras, nombre, descripcion, categoria_id, tipo_producto, tipo_modulo, precio_venta, costo, stock_actual, imagen, activo)
                VALUES (:codigo_barras, :nombre, :descripcion, :categoria_id, :tipo_producto, :tipo_modulo, :precio_venta, :costo, :stock_actual, :imagen, 1)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':codigo_barras' => $datos['codigo_barras'] ?? null,
            ':nombre'        => $datos['nombre'],
            ':descripcion'   => $datos['descripcion'] ?? '',
            ':categoria_id'  => $datos['categoria_id'],
            ':tipo_producto' => $datos['tipo_producto'] ?? 'directo',
            ':tipo_modulo'   => $datos['tipo_modulo'] ?? 'ambos',
            ':precio_venta'  => $datos['precio_venta'],
            ':costo'         => $datos['costo'] ?? 0.00,
            ':stock_actual'  => $datos['stock_actual'] ?? 0,
            ':imagen'        => $datos['imagen'] ?? 'default.jpg'
        ]);
    }
}
