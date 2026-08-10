<?php
// Desactivar salida de errores para no corromper el PDF
error_reporting(0);
ini_set('display_errors', 0);

require('../../vendor/autoload.php'); // Asegúrate de que esta ruta sea correcta para FPDF
require_once('../../config/conexion.php');

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no proporcionado.");
}

// 1. Obtener datos con la conexión correcta ($pdo)
try {
    $stmt = $pdo->prepare("SELECT * FROM inventario_armas WHERE id = ?");
    $stmt->execute([$id]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error en DB: " . $e->getMessage());
}

if (!$reg) {
    die("Error: El registro con ID $id no existe en la base de datos.");
}

// 2. Generar PDF
// Nota: Si usas la versión de vendor, la ruta suele ser FPDF
if (!class_exists('FPDF')) {
    include('../../vendor/fpdf/fpdf.php');
}

class PDF extends FPDF {
    function Header() {
        $this->SetFillColor(26, 26, 26);
        $this->Rect(0, 0, 210, 30, 'F');
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, 'FICHA TECNICA DE ARMAMENTO - SSC', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Función auxiliar para filas del reporte
function agregarFila($label, $valor, $pdf) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 8, utf8_decode($label . ':'), 1, 0, 'L', false);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 8, utf8_decode($valor), 1, 1, 'L', false);
}

// Datos del Reporte
agregarFila('POLIZA', $reg['poliza'], $pdf);
agregarFila('TIPO DE BIEN', $reg['tipo_bien'], $pdf);
agregarFila('SERIE / MATRICULA', $reg['serie_matricula_1'], $pdf);
agregarFila('NO. EXPEDIENTE', $reg['no_expediente'], $pdf);
agregarFila('MARCA', $reg['marca'], $pdf);
agregarFila('MODELO', $reg['modelo'], $pdf);
agregarFila('FECHA RECLAMACION', $reg['fecha_reclamacion'], $pdf);
agregarFila('STATUS SEGURO', $reg['status_seguro'], $pdf);
agregarFila('STATUS INTERNO', $reg['status_interno'], $pdf);
$pdf->Ln(5);

// Cantidades
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 10, 'RESUMEN DE PIEZAS', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 8, 'Armas: ' . $reg['armas'], 1);
$pdf->Cell(45, 8, 'Cargadores: ' . $reg['cargador'], 1);
$pdf->Cell(45, 8, 'Cartuchos: ' . $reg['cartuchos'], 1);
$pdf->Cell(45, 8, 'Chalecos: ' . $reg['chalecos'], 1);

$pdf->Output('I', 'Ficha_Arma_' . $reg['serie_matricula_1'] . '.pdf');
