<?php
// public/index.php

session_start();

// Autoloader robusto
spl_autoload_register(function ($class) {
    $map = [
        'Config\\'      => __DIR__ . '/../config/',
        'Controllers\\' => __DIR__ . '/../controllers/',
        'Models\\'      => __DIR__ . '/../models/'
    ];

    foreach ($map as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

use Controllers\AuthController;
use Controllers\CajaController;
use Controllers\VentaController;
use Controllers\CocinaController;
use Controllers\ProductoController;

$route = $_GET['route'] ?? 'dashboard';

$authController = new AuthController();

// Rutas públicas que no exigen sesión iniciada
if ($route === 'login' || $route === 'login/procesar') {
    if ($route === 'login') {
        $authController->mostrarLogin();
    } else {
        $authController->procesarLogin();
    }
    exit;
}

if ($route === 'logout') {
    $authController->logout();
    exit;
}

// Proteger todas las demás rutas: Si no hay sesión, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?route=login');
    exit;
}

// Enrutador de módulos protegidos
switch ($route) {
    case 'dashboard':
        require_once __DIR__ . '/../views/dashboard/index.php';
        break;

    case 'caja':
        $cajaController = new CajaController();
        $cajaController->index();
        break;
    case 'caja/abrir':
        $cajaController = new CajaController();
        $cajaController->abrir();
        break;
    case 'caja/cerrar':
        $cajaController = new CajaController();
        $cajaController->cerrar();
        break;

    case 'ventas':
        require_once __DIR__ . '/../views/ventas/pos.php';
        break;
    case 'ventas/buscar':
        $ventaController = new VentaController();
        $ventaController->buscarProducto();
        break;
    case 'ventas/comanda_mesa':
        $ventaController = new VentaController();
        $ventaController->obtenerComandaMesa();
        break;
    case 'ventas/procesar':
        $ventaController = new VentaController();
        $ventaController->procesarVenta();
        break;

    case 'cocina':
        require_once __DIR__ . '/../views/cocina/pantalla.php';
        break;
    case 'cocina/obtener_comandas':
        $cocinaController = new CocinaController();
        $cocinaController->obtenerComandasActivas();
        break;
    case 'cocina/cambiar_estado':
        $cocinaController = new CocinaController();
        $cocinaController->cambiarEstadoItem();
        break;

    // Módulo de Catálogo y Productos (Backoffice)
    case 'productos':
        $prodController = new ProductoController();
        $prodController->index();
        break;
    case 'productos/guardar':
        $prodController = new ProductoController();
        $prodController->guardar();
        break;

    default:
        http_response_code(404);
        echo "Página no encontrada";
        break;
}
