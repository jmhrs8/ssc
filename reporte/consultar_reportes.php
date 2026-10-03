<?php
include('config.php');

// Función para obtener conteos
function obtenerConteo($intervalo, $conn) {
    $sql = "";
    switch($intervalo) {
        case 'dia':
            $sql = "SELECT COUNT(*) as total FROM reportes WHERE DATE(fecha) = CURDATE()";
            break;
        case 'semana':
            $sql = "SELECT COUNT(*) as total FROM reportes WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'mes':
            $sql = "SELECT COUNT(*) as total FROM reportes WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())";
            break;
    }
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    return $row['total'] ?? 0;
}

$hoy = obtenerConteo('dia', $conn);
$semana = obtenerConteo('semana', $conn);
$mes = obtenerConteo('mes', $conn);
?>
