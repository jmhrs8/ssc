<?php
// controllers/ProductoController.php

namespace Controllers;

use Models\Producto;

class ProductoController
{
    private Producto $productoModel;

    public function __construct()
    {
        $this->productoModel = new Producto();
    }

    public function index(): void
    {
        $tipoModulo = $_GET['modulo'] ?? 'retail';
        $productos = $this->productoModel->listarPorModulo($tipoModulo);
        $categorias = $this->productoModel->listarCategorias();
        require_once __DIR__ . '/../views/productos/index.php';
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreImagen = 'default.jpg';

            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $nombreImagen = uniqid('prod_') . '.' . strtolower($ext);
                $destino = __DIR__ . '/../public/uploads/productos/' . $nombreImagen;
                @mkdir(dirname($destino), 0775, true);
                move_uploaded_file($_FILES['imagen']['tmp_name'], $destino);
            }

            $datos = [
                'codigo_barras' => trim($_POST['codigo_barras'] ?? ''),
                'nombre'        => trim($_POST['nombre']),
                'descripcion'   => trim($_POST['descripcion'] ?? ''),
                'categoria_id'  => $_POST['categoria_id'],
                'tipo_producto' => $_POST['tipo_producto'] ?? 'directo',
                'tipo_modulo'   => $_POST['tipo_modulo'] ?? 'ambos',
                'precio_venta'  => floatval($_POST['precio_venta']),
                'costo'         => floatval($_POST['costo'] ?? 0),
                'stock_actual'  => intval($_POST['stock_actual'] ?? 0),
                'imagen'        => $nombreImagen
            ];

            $this->productoModel->guardar($datos);
            header('Location: index.php?route=productos&msg=guardado');
            exit;
        }
    }
}
