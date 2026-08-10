<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// Consultas de los 3 módulos
$total_personal = $pdo->query("SELECT COUNT(*) FROM personal")->fetchColumn() ?: 0;
$personal_base = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'BASE'")->fetchColumn() ?: 0;
$personal_eventual = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'EVENTUAL'")->fetchColumn() ?: 0;
$top_areas = $pdo->query("SELECT area_adscripcion, COUNT(*) as total FROM personal GROUP BY area_adscripcion ORDER BY total DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

$total_siniestros = $pdo->query("SELECT COUNT(*) FROM siniestros")->fetchColumn() ?: 0;
$top_aseguradoras = $pdo->query("SELECT aseguradora, COUNT(*) as total FROM siniestros WHERE aseguradora IS NOT NULL AND aseguradora != '' GROUP BY aseguradora ORDER BY total DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

$tot_armas = $pdo->query("SELECT SUM(CAST(armas AS UNSIGNED)) FROM inventario_armas")->fetchColumn() ?: 0;
$monto_convenio = $pdo->query("SELECT SUM(importe_convenio) FROM inventario_armas")->fetchColumn() ?: 0;
$datos_armas = $pdo->query("SELECT tipo_bien, COUNT(*) as total FROM inventario_armas WHERE tipo_bien != '' GROUP BY tipo_bien ORDER BY total DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$status_armas = $pdo->query("SELECT status_seguro, COUNT(*) as total FROM inventario_armas WHERE status_seguro != '' GROUP BY status_seguro ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

class PDF_Reporte_SSC extends FPDF {
    function Header() {
        $this->SetFillColor(26, 26, 26);
        $this->Rect(0, 0, 210, 20, 'F');
        $this->SetFillColor(139, 0, 0);
        $this->Rect(0, 20, 210, 2.5, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->SetY(5);
        $this->Cell(0, 6, utf8_decode('SECRETARÍA DE SEGURIDAD CIUDADANA'), 0, 1, 'C');
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 4, utf8_decode('INFORME GERENCIAL CONSOLIDADO - MÓDULOS DEL SISTEMA'), 0, 1, 'C');
        $this->Ln(8);
    }
    function Footer() {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF_Reporte_SSC();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 10);

$pdf->SetFillColor(245, 245, 245);
$pdf->SetTextColor(26, 26, 26);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(48, 7, utf8_decode('PERSONAL: ' . number_format($total_personal)), 1, 0, 'C', true);
$pdf->Cell(48, 7, utf8_decode('SINIESTROS: ' . number_format($total_siniestros)), 1, 0, 'C', true);
$pdf->Cell(48, 7, utf8_decode('ARMAS: ' . number_format($tot_armas)), 1, 0, 'C', true);
$pdf->Cell(45, 7, utf8_decode('CONVENIO: $' . number_format($monto_convenio, 0)), 1, 1, 'C', true);
$pdf->Ln(3);

function dibujarBarraGrafica($pdf, $etiqueta, $valor, $max_valor, $color_rgb) {
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->SetTextColor(50, 50, 50);
    $label = utf8_decode(substr($etiqueta, 0, 32));
    $pdf->Cell(70, 4, $label, 0, 0, 'L');
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $ancho_max = 100;
    $alto = 3;
    $ancho_barra = ($max_valor > 0) ? ($valor / $max_valor) * $ancho_max : 0;
    if ($ancho_barra < 2) $ancho_barra = 2;
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Rect($x, $y + 0.5, $ancho_max, $alto, 'F');
    $pdf->SetFillColor($color_rgb[0], $color_rgb[1], $color_rgb[2]);
    $pdf->Rect($x, $y + 0.5, $ancho_barra, $alto, 'F');
    $pdf->SetXY($x + $ancho_max + 2, $y);
    $pdf->SetFont('Arial', 'B', 6.5);
    $pdf->Cell(15, 4, number_format($valor), 0, 1, 'R');
}

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(139, 0, 0);
$pdf->Cell(0, 4, utf8_decode('1. MÓDULO: PADRÓN DE PERSONAL (CONTRATACIÓN Y TOP ÁREAS)'), 0, 1, 'L');
$pdf->Ln(1);
dibujarBarraGrafica($pdf, 'PERSONAL BASE', $personal_base, max(1, $total_personal), [52, 58, 64]);
dibujarBarraGrafica($pdf, 'PERSONAL EVENTUAL', $personal_eventual, max(1, $total_personal), [108, 117, 125]);
$max_p = !empty($top_areas) ? $top_areas[0]['total'] : 1;
foreach ($top_areas as $row) {
    dibujarBarraGrafica($pdf, $row['area_adscripcion'], $row['total'], $max_p, [33, 37, 41]);
}
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(139, 0, 0);
$pdf->Cell(0, 4, utf8_decode('2. MÓDULO: VEHÍCULOS SINIESTRADOS (POR ASEGURADORA)'), 0, 1, 'L');
$pdf->Ln(1);
$max_s = !empty($top_aseguradoras) ? $top_aseguradoras[0]['total'] : 1;
foreach ($top_aseguradoras as $row) {
    dibujarBarraGrafica($pdf, $row['aseguradora'] ?: 'SIN DATO', $row['total'], $max_s, [13, 202, 240]);
}
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(139, 0, 0);
$pdf->Cell(0, 4, utf8_decode('3. MÓDULO: ARMAMENTO, TIPO DE BIEN Y ESTATUS'), 0, 1, 'L');
$pdf->Ln(1);
$max_a = !empty($datos_armas) ? $datos_armas[0]['total'] : 1;
foreach ($datos_armas as $row) {
    dibujarBarraGrafica($pdf, $row['tipo_bien'], $row['total'], $max_a, [25, 135, 84]);
}
foreach ($status_armas as $row) {
    dibujarBarraGrafica($pdf, 'ESTATUS: ' . $row['status_seguro'], $row['total'], max(1, $tot_armas), [220, 53, 69]);
}

$pdf->Output('I', 'Informe_Gerencial_SSC.pdf');
