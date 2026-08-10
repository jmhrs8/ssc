<?php
session_start();
require_once "../../config/conexion.php";
// Importante: Debes tener la librería FPDF en tu servidor. 
// Si no la tienes, descárgala en: http://www.fpdf.org/
require_once "../../libs/fpdf/fpdf.php"; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    // 1. CAPTURA DE DATOS DEL FORMULARIO
    $no_expediente = $_POST['no_expediente'];
    $atendio = $_POST['atendio'];
    $fecha_siniestro = $_POST['fecha_siniestro_1'];
    $tipo_siniestro = $_POST['tipo_siniestro'];
    $siniestro_detalle = $_POST['siniestro_detalle'];
    $lugar_siniestro = $_POST['lugar_siniestro']; // Dato manual para el PDF
    $aseguradora = $_POST['aseguradora'];
    $poliza = $_POST['poliza'];
    $no_siniestro = $_POST['no_siniestro'];

    try {
        // 2. ACTUALIZAR LA BASE DE DATOS (Mantenemos la integridad de tu tabla)
        $sql_update = "UPDATE inventario_armas SET 
                        no_expediente = ?, 
                        atendio = ?, 
                        fecha_siniestro_1 = ?, 
                        tipo_siniestro = ?, 
                        siniestro_detalle = ?, 
                        aseguradora = ?, 
                        poliza = ?, 
                        no_siniestro = ? 
                       WHERE id = ?";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            $no_expediente, $atendio, $fecha_siniestro, $tipo_siniestro, 
            $siniestro_detalle, $aseguradora, $poliza, $no_siniestro, $id
        ]);

        // 3. GENERACIÓN DEL PDF INSTITUCIONAL
        class PDF extends FPDF {
            function Header() {
                // Aquí se dibujan los logotipos de la CDMX y títulos
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(0, 10, utf8_decode('CIUDAD DE MÉXICO - CAPITAL DE LA TRANSFORMACIÓN'), 0, 1, 'C');
                $this->SetFillColor(33, 37, 41);
                $this->SetTextColor(255);
                $this->Cell(0, 8, 'REPORTE DE SINIESTRO 2026', 0, 1, 'C', true);
                $this->Ln(5);
            }
        }

        $pdf = new PDF();
        $pdf->AddPage();
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', 'B', 8);

        // FILA: EXPEDIENTE Y TIPO DE BIEN
        $pdf->Cell(40, 7, 'EXPEDIENTE:', 1);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 7, utf8_decode($no_expediente), 1);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(40, 7, 'FECHA ELABORACION:', 1);
        $pdf->Cell(0, 7, date('d/m/Y'), 1, 1);

        // DATOS DEL RESGUARDANTE (Mapeado de 'atendio')[cite: 1, 2]
        $pdf->SetFillColor(230);
        $pdf->Cell(0, 7, 'DATOS DEL RESGUARDANTE DEL BIEN', 1, 1, 'L', true);
        $pdf->Cell(40, 7, 'NOMBRE:', 1);
        $pdf->Cell(0, 7, utf8_decode($atendio), 1, 1);

        // DESCRIPCIÓN DEL BIEN
        // Recuperamos marca y modelo para el texto del PDF
        $stmt_bien = $pdo->prepare("SELECT marca, modelo, serie_matricula_1 FROM inventario_armas WHERE id = ?");
        $stmt_bien->execute([$id]);
        $b = $stmt_bien->fetch();
        
        $pdf->SetFillColor(230);
        $pdf->Cell(0, 7, 'DESCRIPCION DEL BIEN', 1, 1, 'L', true);
        $pdf->MultiCell(0, 7, utf8_decode("ARMA ".$b['marca']." MODELO ".$b['modelo']." SERIE ".$b['serie_matricula_1']), 1);

        // DATOS DEL SINIESTRO
        $pdf->SetFillColor(230);
        $pdf->Cell(0, 7, 'DATOS DEL SINIESTRO', 1, 1, 'L', true);
        $pdf->Cell(40, 7, 'TIPO SINIESTRO:', 1);
        $pdf->Cell(50, 7, utf8_decode($tipo_siniestro), 1);
        $pdf->Cell(40, 7, 'FECHA OCURRENCIA:', 1);
        $pdf->Cell(0, 7, utf8_decode($fecha_siniestro), 1, 1);
        
        $pdf->Cell(40, 7, 'LUGAR:', 1);
        $pdf->Cell(0, 7, utf8_decode($lugar_siniestro), 1, 1);

        $pdf->Cell(0, 7, 'NARRACION DEL SINIESTRO:', 1, 1, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->MultiCell(0, 5, utf8_decode($siniestro_detalle), 1);

        // SECCIÓN DE FIRMAS[cite: 2]
        $pdf->Ln(20);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 7, 'NOMBRE Y FIRMA DE QUIEN REPORTA', 0, 0, 'C');
        $pdf->Cell(95, 7, 'VoBo J.U.D DE ASEGURAMIENTO', 0, 1, 'C');
        $pdf->Line(30, $pdf->GetY()+10, 80, $pdf->GetY()+10);
        $pdf->Line(130, $pdf->GetY()+10, 180, $pdf->GetY()+10);

        $pdf->Output('I', 'Reporte_Siniestro_'.$id.'.pdf');

    } catch (PDOException $e) {
        echo "Error al procesar: " . $e->getMessage();
    }
}
