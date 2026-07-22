<?php
/**
 * ARCHIVO: Almacen/indicadores.php
 * DESCRIPCIÓN: Panel analítico avanzado que desglosa los tiempos promedio de permanencia por fase logística.
 * @project Almacén Técnico DEMEX
 * @version 1.1 (Vista Analítica de Rendimiento)
 */

require_once '../config/db.php';
$page_title = "Indicadores de Rendimiento - Almacén";

/**
 * CONSULTAS ANALÍTICAS (Métricas de Desfase Promedio):
 * Calculan el promedio de días reales utilizando las diferencias de fechas registradas.
 */
$promedio_espera  = $pdo->query("SELECT IFNULL(AVG(DATEDIFF(fecha_inicio_ajustes_almacen, fecha_ingreso_contenedor)), 0) FROM almacen_inventario WHERE fecha_inicio_ajustes_almacen IS NOT NULL")->fetchColumn();
$promedio_ajustes = $pdo->query("SELECT IFNULL(AVG(DATEDIFF(fecha_disponible_soporte, fecha_inicio_ajustes_almacen)), 0) FROM almacen_inventario WHERE fecha_disponible_soporte IS NOT NULL")->fetchColumn();
$promedio_soporte = $pdo->query("SELECT IFNULL(AVG(DATEDIFF(fecha_reingreso_almacen, fecha_entrega_soporte)), 0) FROM almacen_inventario WHERE fecha_reingreso_almacen IS NOT NULL AND fecha_entrega_soporte IS NOT NULL")->fetchColumn();
$promedio_total   = $pdo->query("SELECT IFNULL(AVG(DATEDIFF(fecha_entrega_cliente, fecha_ingreso_contenedor)), 0) FROM almacen_inventario WHERE fecha_entrega_cliente IS NOT NULL")->fetchColumn();

include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-12">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Tiempos de Permanencia</h1>
        <p class="text-muted small mb-0">Métricas analíticas basadas en los promedios reales de desfase logístico dentro de la empresa.</p>
    </div>
</div>

<div class="row g-3 mb-4 animate__animated animate__fadeInUp">
    <div class="col-md-3">
        <div class="p-3 bg-white shadow-sm rounded border-start border-warning border-4">
            <span class="d-block text-muted fw-bold small text-uppercase" style="font-size: 11px;">Espera en Caja</span>
            <div class="fs-3 fw-bold text-dark mt-1"><?= round($promedio_espera, 1) ?> <span class="fs-6 text-secondary fw-normal">días</span></div>
            <small class="text-secondary" style="font-size: 11px;">Desde ingreso hasta apertura.</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white shadow-sm rounded border-start border-primary border-4">
            <span class="d-block text-muted fw-bold small text-uppercase" style="font-size: 11px;">Ajustes Almacén</span>
            <div class="fs-3 fw-bold text-primary mt-1"><?= round($promedio_ajustes, 1) ?> <span class="fs-6 text-secondary fw-normal">días</span></div>
            <small class="text-secondary" style="font-size: 11px;">Tiempo de revisión interna.</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white shadow-sm rounded border-start border-info border-4">
            <span class="d-block text-muted fw-bold small text-uppercase" style="font-size: 11px;">Permanencia Soporte</span>
            <div class="fs-3 fw-bold text-info mt-1"><?= round($promedio_soporte, 1) ?> <span class="fs-6 text-secondary fw-normal">días</span></div>
            <small class="text-secondary" style="font-size: 11px;">Tiempo en laboratorio técnico.</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white shadow-sm rounded border-start border-danger border-4">
            <span class="d-block text-muted fw-bold small text-uppercase" style="font-size: 11px;">Ciclo Total Inventario</span>
            <div class="fs-3 fw-bold text-danger mt-1"><?= round($promedio_total, 1) ?> <span class="fs-6 text-secondary fw-normal">días</span></div>
            <small class="text-secondary" style="font-size: 11px;">Desde ingreso hasta la entrega.</small>
        </div>
    </div>
</div>

<div class="row g-4 animate__animated animate__fadeInUp">
    <div class="col-md-12">
        <div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger">
            <h5 class="fw-bold text-dark text-uppercase mb-4" style="font-size: 14px; letter-spacing: 0.5px;"><i class="bi bi-bar-chart-fill me-2 text-danger"></i>Gráfica Comparativa de Cuellos de Botella</h5>
            <div style="position: relative; height:300px; width:100%;">
                <canvas id="graficaTiempos"></canvas>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    const ctx = document.getElementById('graficaTiempos')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Espera en Caja', 'Ajustes Almacén', 'Permanencia Soporte', 'Ciclo Total Gral.'],
                datasets: [{
                    label: 'Promedio de Días Transcurridos',
                    data: [
                        <?= floatval($promedio_espera) ?>, 
                        <?= floatval($promedio_ajustes) ?>, 
                        <?= floatval($promedio_soporte) ?>, 
                        <?= floatval($promedio_total) ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.75)',  // Amarillo (Warning)
                        'rgba(13, 110, 253, 0.75)', // Azul (Primary)
                        'rgba(13, 202, 240, 0.75)', // Cyan (Info)
                        'rgba(220, 53, 69, 0.75)'   // Rojo (Danger)
                    ],
                    borderColor: [
                        '#ffc107',
                        '#0d6efd',
                        '#0dcaf0',
                        '#dc3545'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return value + ' días'; },
                            font: { weight: 'bold' }
                        }
                    },
                    x: {
                        ticks: { font: { weight: 'bold' } }
                    }
                }
            }
        });
    }
});
</script>