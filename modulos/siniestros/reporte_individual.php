<?php
require('../../vendor/fpdf/fpdf.php');
require_once('../../config/db.php');

$id = $_GET['id'];

// Obtener datos del siniestro
$stmt = $pdo->prepare("SELECT * FROM siniestros WHERE id = ?");
$stmt->execute([$id]);
$reg = $stmt->fetch();

if (!$reg) die("Registro no encontrado.");

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'FICHA TECNICA DE SINIESTRO - SSC', 0, 1, 'C');
        $this->Ln(5);
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Estructura de la ficha según tus campos [cite: 28]
$pdf->Cell(50, 10, 'Folio:', 1); $pdf->Cell(0, 10, $reg['folio'], 1, 1);
$pdf->Cell(50, 10, 'Fecha:', 1); $pdf->Cell(0, 10, $reg['fecha'], 1, 1);
$pdf->Cell(50, 10, 'Marca:', 1); $pdf->Cell(0, 10, $reg['marca'], 1, 1);
$pdf->Cell(50, 10, 'Modelo:', 1); $pdf->Cell(0, 10, $reg['modelo'], 1, 1);
$pdf->Cell(50, 10, 'Placas:', 1); $pdf->Cell(0, 10, $reg['economico_placas'], 1, 1);

// Mostrar foto si existe 
if ($reg['foto_unidad']) {
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Evidencia Fotografica:', 0, 1);
    $pdf->Image('../../uploads/fotos_siniestros/' . $reg['foto_unidad'], 10, $pdf->GetY(), 100);
}

$pdf->Output('D', 'Siniestro_' . $reg['folio'] . '.pdf');
