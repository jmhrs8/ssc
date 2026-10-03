<?php
session_start();
include('config.php');

// 1. Verificación de sesión: Solo usuarios autenticados pueden descargar
if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado. Por favor, inicie sesión.");
}

/**
 * Función auxiliar para convertir texto a codificación compatible con Excel (.xls)
 * Esto evita problemas con acentos y caracteres especiales.
 */
function limpiar_texto($t) {
    return mb_convert_encoding($t ?? '', 'ISO-8859-1', 'UTF-8');
}

// 2. Configuración de cabeceras para forzar la descarga del archivo .xls
header("Content-Type: application/vnd.ms-excel; charset=iso-8859-1");
header("Content-Disposition: attachment; filename=Inventario_Revista_SSC_" . date('Y-m-d_H-i') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Consulta a la base de datos solicitada
// Obtenemos: ID, Inventario, Placa, Tipo, Fecha/Hora, Usuario y Nombre Real
$query = "SELECT 
            r.id, 
            r.n_inventario, 
            r.n_placa, 
            r.n_tipo, 
            r.fecha, 
            r.registrado_por AS usuario_operador, 
            u.nombre_completo 
          FROM reportes r 
          LEFT JOIN usuarios u ON r.registrado_por = u.usuario 
          ORDER BY r.id DESC";

$res = $conn->query($query);

// 4. Generación de la tabla que Excel interpretará
echo "<table border='1'>";
// Encabezados con estilo institucional
echo "<tr>
        <th style='background-color: #6b1e34; color: white;'>ID</th>
        <th style='background-color: #6b1e34; color: white;'>" . limpiar_texto('NO. INVENTARIO') . "</th>
        <th style='background-color: #6b1e34; color: white;'>" . limpiar_texto('NO. PLACA') . "</th>
        <th style='background-color: #6b1e34; color: white;'>" . limpiar_texto('TIPO DE UNIDAD') . "</th>
        <th style='background-color: #6b1e34; color: white;'>" . limpiar_texto('FECHA Y HORA REGISTRO') . "</th>
        <th style='background-color: #6b1e34; color: white;'>" . limpiar_texto('USUARIO OPERADOR') . "</th>
        <th style='background-color: #6b1e34; color: white;'>" . limpiar_texto('NOMBRE DEL RESPONSABLE') . "</th>
      </tr>";

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . strtoupper(limpiar_texto($row['n_inventario'])) . "</td>"; //
        echo "<td>" . strtoupper(limpiar_texto($row['n_placa'])) . "</td>";     //
        echo "<td>" . strtoupper(limpiar_texto($row['n_tipo'])) . "</td>";      //
        
        // Formateo de Fecha y Hora
        $fecha_formateada = date('d/m/Y H:i:s', strtotime($row['fecha']));
        echo "<td>" . $fecha_formateada . "</td>";
        
        echo "<td>" . strtoupper(limpiar_texto($row['usuario_operador'])) . "</td>"; //
        echo "<td>" . strtoupper(limpiar_texto($row['nombre_completo'])) . "</td>";  //
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7'>" . limpiar_texto('No se encontraron registros en la base de datos.') . "</td></tr>";
}

echo "</table>";
?>
