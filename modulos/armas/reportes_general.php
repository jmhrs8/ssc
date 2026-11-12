<?php
require_once "../../config/conexion.php";

try {
    // 1. Estadísticas por Tipo de Bien (General)
    $tipoBien = $pdo->query("SELECT tipo_bien, COUNT(*) as total FROM inventario_armas WHERE tipo_bien != '' GROUP BY tipo_bien ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Estadísticas de Semovientes (Caninos y Equinos)
    $statsSemovientes = $pdo->query("SELECT tipo_bien, COUNT(*) as total FROM inventario_armas WHERE tipo_bien LIKE '%CANINO%' OR tipo_bien LIKE '%EQUINO%' GROUP BY tipo_bien")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Estadísticas por Aseguradora
    $statsAseguradora = $pdo->query("SELECT aseguradora, COUNT(*) as total FROM inventario_armas WHERE aseguradora != '' GROUP BY aseguradora ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Estadísticas por Despacho
    $statsDespacho = $pdo->query("SELECT despacho, COUNT(*) as total FROM inventario_armas WHERE despacho != '' GROUP BY despacho ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // 5. Estatus de Seguro
    $statusSeguro = $pdo->query("SELECT status_seguro, COUNT(*) as total FROM inventario_armas WHERE status_seguro != '' GROUP BY status_seguro ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

    // 6. DESGLOSE ORDENADO POR MES Y TIPO DE BIEN, SUMANDO CORRECTAMENTE ACCESORIOS Y CARTUCHOS
    $bienesPorAnioMes = $pdo->query("SELECT 
        COALESCE(NULLIF(YEAR(fecha_siniestro_1), ''), 'SIN AÑO') as anio,
        COALESCE(NULLIF(tipo_bien, ''), 'SIN ESPECIFICAR') as tipo_bien,
        CASE 
            WHEN UPPER(mes_reclamacion) LIKE '%ENERO%' OR MONTH(fecha_siniestro_1) = 1 THEN 'ENERO'
            WHEN UPPER(mes_reclamacion) LIKE '%FEBRERO%' OR MONTH(fecha_siniestro_1) = 2 THEN 'FEBRERO'
            WHEN UPPER(mes_reclamacion) LIKE '%MARZO%' OR MONTH(fecha_siniestro_1) = 3 THEN 'MARZO'
            WHEN UPPER(mes_reclamacion) LIKE '%ABRIL%' OR MONTH(fecha_siniestro_1) = 4 THEN 'ABRIL'
            WHEN UPPER(mes_reclamacion) LIKE '%MAYO%' OR MONTH(fecha_siniestro_1) = 5 THEN 'MAYO'
            WHEN UPPER(mes_reclamacion) LIKE '%JUNIO%' OR MONTH(fecha_siniestro_1) = 6 THEN 'JUNIO'
            WHEN UPPER(mes_reclamacion) LIKE '%JULIO%' OR MONTH(fecha_siniestro_1) = 7 THEN 'JULIO'
            WHEN UPPER(mes_reclamacion) LIKE '%AGOSTO%' OR MONTH(fecha_siniestro_1) = 8 THEN 'AGOSTO'
            WHEN UPPER(mes_reclamacion) LIKE '%SEPTIEMBRE%' OR MONTH(fecha_siniestro_1) = 9 THEN 'SEPTIEMBRE'
            WHEN UPPER(mes_reclamacion) LIKE '%OCTUBRE%' OR MONTH(fecha_siniestro_1) = 10 THEN 'OCTUBRE'
            WHEN UPPER(mes_reclamacion) LIKE '%NOVIEMBRE%' OR MONTH(fecha_siniestro_1) = 11 THEN 'NOVIEMBRE'
            WHEN UPPER(mes_reclamacion) LIKE '%DICIEMBRE%' OR MONTH(fecha_siniestro_1) = 12 THEN 'DICIEMBRE'
            ELSE 'SIN MES'
        END as mes_nombre,
        CASE 
            WHEN UPPER(mes_reclamacion) LIKE '%ENERO%' OR MONTH(fecha_siniestro_1) = 1 THEN 1
            WHEN UPPER(mes_reclamacion) LIKE '%FEBRERO%' OR MONTH(fecha_siniestro_1) = 2 THEN 2
            WHEN UPPER(mes_reclamacion) LIKE '%MARZO%' OR MONTH(fecha_siniestro_1) = 3 THEN 3
            WHEN UPPER(mes_reclamacion) LIKE '%ABRIL%' OR MONTH(fecha_siniestro_1) = 4 THEN 4
            WHEN UPPER(mes_reclamacion) LIKE '%MAYO%' OR MONTH(fecha_siniestro_1) = 5 THEN 5
            WHEN UPPER(mes_reclamacion) LIKE '%JUNIO%' OR MONTH(fecha_siniestro_1) = 6 THEN 6
            WHEN UPPER(mes_reclamacion) LIKE '%JULIO%' OR MONTH(fecha_siniestro_1) = 7 THEN 7
            WHEN UPPER(mes_reclamacion) LIKE '%AGOSTO%' OR MONTH(fecha_siniestro_1) = 8 THEN 8
            WHEN UPPER(mes_reclamacion) LIKE '%SEPTIEMBRE%' OR MONTH(fecha_siniestro_1) = 9 THEN 9
            WHEN UPPER(mes_reclamacion) LIKE '%OCTUBRE%' OR MONTH(fecha_siniestro_1) = 10 THEN 10
            WHEN UPPER(mes_reclamacion) LIKE '%NOVIEMBRE%' OR MONTH(fecha_siniestro_1) = 11 THEN 11
            WHEN UPPER(mes_reclamacion) LIKE '%DICIEMBRE%' OR MONTH(fecha_siniestro_1) = 12 THEN 12
            ELSE 13
        END as nro_mes,
        COUNT(*) as total_siniestros,
        SUM(CAST(COALESCE(candado_manos, 0) AS UNSIGNED)) as sum_candados,
        SUM(CAST(COALESCE(armas, 0) AS UNSIGNED)) as sum_armas,
        SUM(CAST(COALESCE(cargador, 0) AS UNSIGNED)) as sum_cargadores,
        SUM(CAST(COALESCE(cartuchos, 0) AS UNSIGNED)) as sum_cartuchos,
        SUM(CAST(COALESCE(cascos, 0) AS UNSIGNED)) as sum_cascos,
        SUM(CAST(COALESCE(escudos, 0) AS UNSIGNED)) as sum_escudos,
        SUM(CAST(COALESCE(chalecos, 0) AS UNSIGNED)) as sum_chalecos
        FROM inventario_armas 
        GROUP BY anio, mes_nombre, nro_mes, tipo_bien 
        ORDER BY anio DESC, nro_mes ASC, tipo_bien ASC")->fetchAll(PDO::FETCH_ASSOC);

    $aniosDisponibles = array_unique(array_column($bienesPorAnioMes, 'anio'));
    rsort($aniosDisponibles);

    // 7. Totales Globales: EMPATADO EXACTAMENTE CON LA GRÁFICA DE ESTATUS DE SEGURO (174 + 22 = 196)
    $totales = $pdo->query("SELECT
        SUM(CAST(armas AS UNSIGNED)) as total_armas,
        SUM(CAST(cartuchos AS UNSIGNED)) as total_cartuchos,
        SUM(importe_convenio) as gran_total_dinero,
        (SELECT COUNT(*) FROM inventario_armas WHERE tipo_bien LIKE '%CANINO%') as total_caninos,
        (SELECT COUNT(*) FROM inventario_armas WHERE tipo_bien LIKE '%EQUINO%') as total_equinos,
        (SELECT COUNT(id) FROM inventario_armas WHERE status_seguro LIKE '%EN ESPERA DE RESOLUCION%' OR status_seguro LIKE '%PENDIENTE%') as total_pendientes_seguro
        FROM inventario_armas")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes Globales | SSC SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        body { background-color: #f8f9fa; font-size: 12px; text-transform: uppercase; font-family: 'Segoe UI', sans-serif; }
        .header-report { background: #1a1a1a; color: white; padding: 20px; border-bottom: 4px solid #0dcaf0; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-header { background: #fff !important; border-bottom: 1px solid #f0f0f0 !important; font-weight: bold; color: #333; padding: 15px; }
        .stat-card { border-top: 4px solid #0dcaf0; text-align: center; }
        .stat-value { font-size: 24px; font-weight: 800; display: block; }
        .bg-money { background: linear-gradient(45deg, #198754, #2ecc71); color: white; }
        .bg-purple { background: linear-gradient(45deg, #6610f2, #a55eea); color: white; }
        .bg-warning-custom { background: linear-gradient(45deg, #ffc107, #ff9800); color: #212529; border-top-color: #ffc107; }
        .chart-container { position: relative; height: 500px; width: 100%; padding: 10px; }

        @media print {
            .header-report .btn, .btn, .no-print { display: none !important; }
            body { background: white !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body>

<div class="header-report mb-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-info"></i> PANEL INTEGRAL DE CONTROL</h4>
            <span class="text-info opacity-75">Armamento, Caninos y Equinos</span>
        </div>
        <div>
            <a href="exportar_excel_aseguradora.php" class="btn btn-warning btn-sm me-2 fw-bold"><i class="fas fa-file-excel me-1"></i> EXCEL POR ASEGURADORA</a>
            <a href="reporte_dinamico.php" class="btn btn-success btn-sm me-2"><i class="fas fa-file-excel me-1"></i> FILTROS Y EXPORTAR</a>
            <button onclick="window.print()" class="btn btn-info btn-sm me-2 text-white"><i class="fas fa-print me-1"></i> IMPRIMIR</button>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-undo me-1"></i> REGRESAR</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <div class="row g-3 mb-4 text-center">
        <div class="col-md-2">
            <div class="card stat-card p-3">
                <small class="text-muted">ARMAS</small>
                <span class="stat-value text-dark"><?= number_format($totales['total_armas'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 bg-purple">
                <small class="text-white-50">CANINOS</small>
                <span class="stat-value text-white"><?= number_format($totales['total_caninos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 bg-purple">
                <small class="text-white-50">EQUINOS</small>
                <span class="stat-value text-white"><?= number_format($totales['total_equinos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3" style="border-top-color: #fd7e14;">
                <small class="text-muted">CARTUCHOS</small>
                <span class="stat-value text-warning"><?= number_format($totales['total_cartuchos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 bg-warning-custom shadow">
                <small class="fw-bold text-dark">PENDIENTES ASEGURADORAS</small>
                <span class="stat-value text-dark"><?= number_format($totales['total_pendientes_seguro'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-money p-3 shadow text-center">
                <small class="fw-bold">MONTO TOTAL CONVENIO</small>
                <div class="stat-value" style="font-size: 16px;">$ <?= number_format($totales['gran_total_dinero'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-primary"><i class="fas fa-paw me-2"></i> DETALLE DE SEMOVIENTES</div>
                <div class="chart-container">
                    <canvas id="chartSemovientes"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-success"><i class="fas fa-tasks me-2"></i> ESTATUS DE SEGURO</div>
                <div class="chart-container">
                    <canvas id="chartSeguro"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><i class="fas fa-boxes me-2"></i> DISTRIBUCIÓN GENERAL POR TIPO DE BIEN</div>
                <div class="chart-container" style="height: 500px;">
                    <canvas id="chartBienes"></canvas>
                </div>
            </div>
        </div>

        <!-- GRÁFICA HORIZONTAL COMPACTA CON FILTRO Y EXCEL -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <span class="text-warning"><i class="fas fa-calendar-alt me-2"></i> TIPO DE BIEN Y ACCESORIOS POR MES (ORDEN CRONOLÓGICO)</span>
                    <div class="no-print mt-2 mt-md-0 d-flex align-items-center">
                        <label for="filtroAnio" class="fw-bold me-2 text-dark">AÑO:</label>
                        <select id="filtroAnio" class="form-select form-select-sm d-inline-block w-auto fw-bold text-dark bg-light me-3" onchange="actualizarGraficaAnio()">
                            <?php foreach($aniosDisponibles as $anio): ?>
                                <option value="<?= $anio ?>"><?= $anio ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="descargarExcelNumeralia()" class="btn btn-success btn-sm fw-bold">
                            <i class="fas fa-file-excel me-1"></i> EXCEL NUMERALIA
                        </button>
                    </div>
                </div>
                <div id="contenedorGraficaAnio" class="chart-container" style="height: 650px;">
                    <canvas id="chartBienesPorAnioHorizontal"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-danger"><i class="fas fa-building me-2"></i> TOP 10 ASEGURADORAS</div>
                <div class="chart-container">
                    <canvas id="chartAseguradoras"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-info"><i class="fas fa-briefcase me-2"></i> TOP 10 DESPACHOS</div>
                <div class="chart-container">
                    <canvas id="chartDespachos"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    Chart.register(ChartDataLabels);

    const verticalOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 30, right: 15, left: 15, bottom: 10 } },
        plugins: {
            legend: { display: false },
            datalabels: {
                anchor: 'end',
                align: 'top',
                offset: 6,
                font: { weight: 'bold', size: 11 },
                formatter: (value) => value.toLocaleString()
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grace: '20%',
                grid: { color: '#f0f0f0' }
            },
            x: { grid: { display: false } }
        }
    };

    const datosGlobales = <?= json_encode($bienesPorAnioMes); ?>;
    let chartAnioHorizontal = null;

    function actualizarGraficaAnio() {
        const anioSeleccionado = document.getElementById('filtroAnio').value;
        const datosFiltrados = datosGlobales.filter(item => item.anio === anioSeleccionado);

        let labels = [];
        let valores = [];

        datosFiltrados.forEach(item => {
            const mes = item.mes_nombre;
            const bien = item.tipo_bien;

            labels.push(`[${mes}] - ${bien} (Siniestros)`);
            valores.push(parseInt(item.total_siniestros));

            if (parseInt(item.sum_cartuchos) > 0) {
                labels.push(`[${mes}] - CARTUCHOS`);
                valores.push(parseInt(item.sum_cartuchos));
            }
            if (parseInt(item.sum_cargadores) > 0) {
                labels.push(`[${mes}] - CARGADORES`);
                valores.push(parseInt(item.sum_cargadores));
            }
            if (parseInt(item.sum_chalecos) > 0) {
                labels.push(`[${mes}] - CHALECOS`);
                valores.push(parseInt(item.sum_chalecos));
            }
            if (parseInt(item.sum_cascos) > 0) {
                labels.push(`[${mes}] - CASCOS`);
                valores.push(parseInt(item.sum_cascos));
            }
            if (parseInt(item.sum_escudos) > 0) {
                labels.push(`[${mes}] - ESCUDOS`);
                valores.push(parseInt(item.sum_escudos));
            }
        });

        const alturaDinamica = Math.max(500, labels.length * 24);
        document.getElementById('contenedorGraficaAnio').style.height = alturaDinamica + 'px';

        if (chartAnioHorizontal) {
            chartAnioHorizontal.destroy();
        }

        const ctx = document.getElementById('chartBienesPorAnioHorizontal').getContext('2d');
        chartAnioHorizontal = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: valores,
                    backgroundColor: '#f6c23e',
                    borderRadius: 4,
                    barPercentage: 0.95,
                    categoryPercentage: 0.95
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                layout: { padding: { right: 70, top: 5, bottom: 5 } },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'right',
                        font: { weight: 'bold', size: 11 },
                        formatter: (value) => value.toLocaleString()
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grace: '20%',
                        grid: { color: '#f0f0f0' },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });
    }

    // EXCEL "NUMERALIA DEL MES POR TIPO"
    function descargarExcelNumeralia() {
        const anioSeleccionado = document.getElementById('filtroAnio').value;
        const datosFiltrados = datosGlobales.filter(item => item.anio === anioSeleccionado);

        if (datosFiltrados.length === 0) {
            alert('No hay datos para exportar en el año seleccionado.');
            return;
        }

        let csvContent = "\ufeffNUMERALIA DEL MES POR TIPO Y ACCESORIOS - AÑO " + anioSeleccionado + "\n\n";
        csvContent += "AÑO,MES,TIPO DE BIEN,TOTAL SINIESTROS,CANDADOS,ARMAS,CARGADORES,CARTUCHOS,CASCOS,ESCUDOS,CHALECOS\n";

        datosFiltrados.forEach(row => {
            csvContent += `"${row.anio}","${row.mes_nombre}","${row.tipo_bien}",${row.total_siniestros},${row.sum_candados},${row.sum_armas},${row.sum_cargadores},${row.sum_cartuchos},${row.sum_cascos},${row.sum_escudos},${row.sum_chalecos}\n`;
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `Numeralia_del_mes_por_tipo_${anioSeleccionado}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    window.addEventListener('DOMContentLoaded', () => {
        actualizarGraficaAnio();
    });

    // 1. SEMOVIENTES (VERTICAL)
    new Chart(document.getElementById('chartSemovientes'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsSemovientes as $s) echo "'".$s['tipo_bien']."',"; ?>],
            datasets: [{
                data: [<?php foreach($statsSemovientes as $s) echo $s['total'].","; ?>],
                backgroundColor: '#6610f2',
                borderRadius: 8,
                barThickness: 50
            }]
        },
        options: verticalOptions
    });

    // 2. ESTATUS (VERTICAL)
    new Chart(document.getElementById('chartSeguro'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statusSeguro as $s) echo "'".($s['status_seguro']?:'S/D')."',"; ?>],
            datasets: [{
                data: [<?php foreach($statusSeguro as $s) echo $s['total'].","; ?>],
                backgroundColor: '#1cc88a',
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: verticalOptions
    });

    // 3. BIENES GENERAL (VERTICAL)
    new Chart(document.getElementById('chartBienes'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($tipoBien as $b) echo "'".$b['tipo_bien']."',"; ?>],
            datasets: [{
                data: [<?php foreach($tipoBien as $b) echo $b['total'].","; ?>],
                backgroundColor: '#4e73df',
                borderRadius: 5
            }]
        },
        options: verticalOptions
    });

    // 4. ASEGURADORAS (HORIZONTAL)
    new Chart(document.getElementById('chartAseguradoras'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsAseguradora as $a) echo "'".$a['aseguradora']."',"; ?>],
            datasets: [{
                data: [<?php foreach($statsAseguradora as $a) echo $a['total'].","; ?>],
                backgroundColor: '#e74a3b',
                borderRadius: 5
            }]
        },
        options: {
            ...verticalOptions,
            indexAxis: 'y',
            layout: { padding: { right: 60, top: 10, bottom: 10 } },
            plugins: {
                ...verticalOptions.plugins,
                datalabels: { ...verticalOptions.plugins.datalabels, align: 'right', anchor: 'end' }
            },
            scales: { x: { grace: '15%' }, y: { grid: { display: false } } }
        }
    });

    // 5. DESPACHOS (HORIZONTAL)
    new Chart(document.getElementById('chartDespachos'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsDespacho as $d) echo "'".$d['despacho']."',"; ?>],
            datasets: [{
                data: [<?php foreach($statsDespacho as $d) echo $d['total'].","; ?>],
                backgroundColor: '#36b9cc',
                borderRadius: 5
            }]
        },
        options: {
            ...verticalOptions,
            indexAxis: 'y',
            layout: { padding: { right: 60, top: 10, bottom: 10 } },
            plugins: {
                ...verticalOptions.plugins,
                datalabels: { ...verticalOptions.plugins.datalabels, align: 'right', anchor: 'end' }
            },
            scales: { x: { grace: '15%' }, y: { grid: { display: false } } }
        }
    });
</script>
</body>
</html>
