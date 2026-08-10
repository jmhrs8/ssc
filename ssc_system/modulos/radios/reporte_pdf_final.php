<?php
require('../../libs/fpdf/fpdf.php'); 
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
if (!$id) die("ID NO PROPORCIONADO");

// Consulta que une inventario con la tabla espejo
$stmt = $pdo->prepare("
    SELECT r.*, e.* 
    FROM inventario_radio r 
    INNER JOIN espejo_siniestros_radios e ON r.id = e.id_radio 
    WHERE r.id = ?
");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) die("NO SE ENCONTRARON DATOS");

class PDF extends FPDF {
    function Header() {
        // Encabezado institucional
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, utf8_decode('CIUDAD DE MÉXICO'), 0, 1, 'L');
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 4, utf8_decode('CAPITAL DE LA TRANSFORMACIÓN'), 0, 1, 'L');
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, utf8_decode('REPORTE DE SINIESTRO 2026'), 0, 1, 'C');
        $this->Ln(2);
    }

    function CampoFila($label, $valor) {
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(45, 6, utf8_decode($label), 1, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 6, ' | ' . utf8_decode(strtoupper($valor)), 1, 1, 'L');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetMargins(15, 10, 15);

// --- BLOQUE SUPERIOR (EXPEDIENTE Y CAMARA) ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(35, 8, 'EXPEDIENTE:', 1, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 8, utf8_decode($d['no_expediente']), 1, 0, 'C');

$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(50, 8, utf8_decode(' [ X ] CÁMARA PORTÁTIL'), 1, 0, 'C'); // Marcado fijo según tu ejemplo
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(0, 8, 'FECHA ELAB: ' . $d['fecha_elaboracion'], 1, 1, 'C');
$pdf->Ln(2);

// --- SECCIÓN: DATOS DEL RESGUARDANTE ---
$pdf->SetFillColor(230, 230, 230);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, 'DATOS DEL RESGUARDANTE DEL BIEN', 0, 1, 'L');

$pdf->CampoFila('NOMBRE:', $d['nombre_resguardante']);
$pdf->CampoFila('ADSCRIPCIÓN:', $d['adscripcion']);
$pdf->CampoFila('GRADO:', $d['grado']);
$pdf->CampoFila('NO. EMPLEADO:', $d['no_empleado']);
$pdf->CampoFila('TELÉFONO:', $d['telefono']);
$pdf->CampoFila('E-MAIL:', strtolower($d['email']));
$pdf->Ln(2);

// --- SECCIÓN: DATOS DEL SINIESTRO ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, 'DATOS DEL SINIESTRO', 0, 1, 'L');

$pdf->CampoFila('FECHA DE SINIESTRO:', $d['fecha_siniestro']);
$pdf->CampoFila('HORA DE OCURRENCIA:', $d['hora_siniestro']);
$pdf->CampoFila('TIPO DE SINIESTRO:', $d['tipo_siniestro']);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(45, 5, utf8_decode('NARRACIÓN SINIESTRO:'), 'LTR', 1);
$pdf->SetFont('Arial', '', 7);
$pdf->MultiCell(0, 4, utf8_decode(strtoupper($d['narracion'])), 'LRB', 'J');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(45, 5, 'LUGAR DEL SINIESTRO:', 'LTR', 1);
$pdf->SetFont('Arial', '', 7);
$pdf->MultiCell(0, 4, utf8_decode(strtoupper($d['lugar_siniestro'])), 'LRB', 'J');
$pdf->Ln(2);

// --- SECCIÓN: DESCRIPCIÓN DEL BIEN[cite: 4] ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, utf8_decode('DESCRIPCIÓN DEL BIEN:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 8);
$detalle_bien = "MARCA: {$d['marca']}, MODELO: {$d['modelo']}, SERIE: {$d['serie_matricula']}";
$pdf->Cell(0, 7, utf8_decode(strtoupper($detalle_bien)), 1, 1, 'L');
$pdf->Ln(2);

// --- SECCIÓN: ASEGURADORA[cite: 4] ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, 'DATOS REPORTE A LA ASEGURADORA', 0, 1, 'L');
$pdf->CampoFila('ASEGURADORA:', $d['aseguradora']);
$pdf->CampoFila('PÓLIZA:', $d['poliza']);
$pdf->CampoFila('FECHA REPORTE:', $d['fecha_reporte']);
$pdf->CampoFila('HORA REPORTE:', $d['hora_reporte']);
$pdf->CampoFila('RECIBE REPORTE:', $d['nombre_recibe']);
$pdf->CampoFila('NO. SINIESTRO:', $d['no_siniestro_seguro']);
$pdf->CampoFila('DESPACHO AJUSTADOR:', $d['despacho_ajustador']);
$pdf->Ln(10);

// --- FIRMAS[cite: 4] ---
$y = $pdf->GetY();
$pdf->SetFont('Arial', 'B', 7);
// Lado izquierdo: Quien reporta
$pdf->SetXY(15, $y);
$pdf->MultiCell(70, 4, "___________________________\nERIKA REYES ROCHA\nNOMBRE Y FIRMA DE QUIEN REPORTA", 0, 'C');

// Lado derecho: VoBo
$pdf->SetXY(125, $y);
$pdf->MultiCell(70, 4, "___________________________\nMARGARITA MAZA\nVoBo J.U.D DE ASEGURAMIENTO DE BIENES", 0, 'C');

// --- PIE DE PÁGINA[cite: 4] ---
$pdf->SetY(-20);
$pdf->SetFont('Arial', 'I', 6);
$pdf->Cell(0, 4, utf8_decode('2026 - AÑO DE MARGARITA MAZA - AÑO MUNDIALISTA'), 0, 1, 'C');

$pdf->Output('I', 'Reporte_Radio_' . $d['no_expediente'] . '.pdf');
