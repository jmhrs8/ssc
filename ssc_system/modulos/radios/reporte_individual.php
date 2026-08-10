<?php
require('../../vendor/fpdf/fpdf.php');
require_once('../../config/db.php');

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM radios WHERE id = ?");
$stmt->execute([$id]);
$reg = $stmt->fetch();

if (!$reg) die("No existe el registro");

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('FICHA TÉCNICA: RADIOS Y SEMOVIENTES'), 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 11);
$fields = [
    'Inventario' => $reg['inventario'],
    'Marca' => $reg['marca'],
    'Modelo' => $reg['modelo'],
    'Serie' => $reg['serie'],
    'No. Expediente' => $reg['no_expediente'],
    'Tipo de Bien' => $reg['tipo_bien'],
    'Aseguradora' => $reg['aseguradora'],
    'Estatus' => $reg['estatus'],
    'Folio DAAA' => $reg['folio_daaa']
];

foreach($fields as $label => $val) {
    $pdf->Cell(60, 10, utf8_decode($label . ':'), 1);
    $pdf->Cell(0, 10, utf8_decode($val), 1, 1);
}

$pdf->Output('I', 'Radio_'.$reg['inventario'].'.pdf');
