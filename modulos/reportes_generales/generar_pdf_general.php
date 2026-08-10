<?php
// Este script genera el PDF maestro consolidado capturando la vista web con Chart.js
$url_reporte = "http://10.13.14.16/modulos/armas/reportes_general.php";
$ruta_pdf = __DIR__ . '/Informe_Gerencial_Consolidado_SSC.pdf';

// Comando optimizado con wkhtmltopdf para renderizar correctamente Chart.js y gráficos web
$comando = "wkhtmltopdf --no-stop-slow-scripts --javascript-delay 3000 --enable-javascript --print-media-type " . escapeshellarg($url_reporte) . " " . escapeshellarg($ruta_pdf);
shell_exec($comando);

if (file_exists($ruta_pdf) && filesize($ruta_pdf) > 100) {
    echo "¡PDF maestro con gráficas exactas generado con éxito en: " . $ruta_pdf . "!";
} else {
    echo "Error al generar el PDF gráfico consolidado.";
}
