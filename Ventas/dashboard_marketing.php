<?php
/**
 * ARCHIVO: Ventas/dashboard_marketing.php
 * DESCRIPCIÓN: Dashboard Interactivo de Inteligencia Comercial DEMEX.
 * Corregido para usar únicamente 'nombre_cliente' de acuerdo con la estructura simplificada de la BD.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 2.2 (Estructura de Base de Datos Homologada)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Dashboard de Marketing y Analítica | CRM Ventas";
require_once '../config/db.php';

// Inicializamos variables en 0 por seguridad
$total_leads = 0;
$total_clientes = 0;
$total_ventas_cerradas = 0;
$clv_total = 0;
$canales_data = [];
$top_results = [];
$modelos_data = [];

$chart_labels = [];
$chart_leads = [];

try {
    // 1. Total de Prospectos / Leads
    $total_leads = $pdo->query("SELECT COUNT(*) FROM prospectos")->fetchColumn() ?: 0;

    // 2. Total de Clientes con compras
    $total_clientes = $pdo->query("SELECT COUNT(DISTINCT id_cliente) FROM ventas_historial")->fetchColumn() ?: 0;

    // 3. Total de Ventas
    $total_ventas_cerradas = $pdo->query("SELECT COUNT(*) FROM ventas_historial")->fetchColumn() ?: 0;

    // 4. Ingresos Totales
    $clv_total = $pdo->query("SELECT IFNULL(SUM(precio_pactado_neto * cantidad), 0) FROM ventas_historial")->fetchColumn() ?: 0;

    // 5. Canales de Origen
    $sql_canales = "SELECT f.canal_origen, 
                           COUNT(p.id_prospecto) AS cantidad_leads,
                           SUM(CASE WHEN p.status_comercial = 'Venta Cerrada' THEN 1 ELSE 0 END) AS ventas_cerradas,
                           COUNT(c.id_cotizacion) AS cotizaciones_emitidas
                    FROM formulario f
                    INNER JOIN prospectos p ON f.id_formulario = p.id_formulario
                    LEFT JOIN cotizacion c ON p.id_prospecto = c.id_prospecto
                    GROUP BY f.canal_origen
                    ORDER BY cantidad_leads DESC";
    $stmt_canales = $pdo->query($sql_canales);
    if ($stmt_canales) {
        $canales_data = $stmt_canales->fetchAll(PDO::FETCH_ASSOC);
        foreach ($canales_data as $canal) {
            $chart_labels[] = $canal['canal_origen'];
            $chart_leads[]  = (int)$canal['cantidad_leads'];
        }
    }

    // 6. Top Clientes (CORREGIDO: Se quitó 'c.apellidos_cliente')
    $sql_top = "SELECT c.nombre_cliente, 
                       SUM(vh.precio_pactado_neto * vh.cantidad) AS inversion_total,
                       COUNT(vh.id_venta) AS maquinas_compradas
                FROM clientes c
                INNER JOIN ventas_historial vh ON c.id_cliente = vh.id_cliente
                GROUP BY c.id_cliente
                ORDER BY inversion_total DESC
                LIMIT 5";
    $stmt_top = $pdo->query($sql_top);
    if ($stmt_top) {
        $top_results = $stmt_top->fetchAll(PDO::FETCH_ASSOC);
    }

    // 7. Modelos de Maquinaria
    $sql_modelos = "SELECT m.modelo, 
                           SUM(vh.cantidad) AS unidades_vendidas,
                           AVG(vh.precio_pactado_neto) AS precio_promedio,
                           SUM(vh.precio_pactado_neto * vh.cantidad) AS ingresos_modelo
                    FROM ventas_historial vh
                    INNER JOIN maquinaria m ON vh.id_maquina = m.id_maquina
                    GROUP BY m.id_maquina
                    ORDER BY unidades_vendidas DESC";
    $stmt_modelos = $pdo->query($sql_modelos);
    if ($stmt_modelos) {
        $modelos_data = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Manejo silencioso de excepciones en producción
}

// Cálculos de KPIs seguros
$ticket_promedio = ($total_ventas_cerradas > 0) ? ($clv_total / $total_ventas_cerradas) : 0;
$tasa_conversion = ($total_leads > 0) ? ($total_clientes / $total_leads) * 100 : 0;

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<!-- ENCABEZADO Y CONTADORES KPI EJECUTIVOS -->
<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0">
            <i class="bi bi-bar-chart-line-fill"></i> Métricas de Ventas
        </h1>
        <p class="text-muted small mb-0">Análisis de rendimiento de canales de atracción e ingresos.</p>
    </div>
    <div class="col-md-7 text-md-end mt-3 mt-md-0">
        <div class="d-inline-flex gap-2 flex-wrap justify-content-md-end">
            <div class="p-2 bg-white shadow-sm rounded border-start border-secondary border-4 text-center" style="min-width: 95px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= $total_leads ?></span>
                <small class="text-muted d-block fw-bold" style="font-size: 0.6rem;">LEADS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-primary border-4 text-center" style="min-width: 95px;">
                <span class="d-block fw-bold fs-5 text-primary"><?= $total_clientes ?></span>
                <small class="text-muted d-block fw-bold" style="font-size: 0.6rem;">CLIENTES</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 100px;">
                <span class="d-block fw-bold fs-5 text-warning"><?= number_format($tasa_conversion, 1) ?>%</span>
                <small class="text-muted d-block fw-bold" style="font-size: 0.6rem;">% CONVERSIÓN</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-info border-4 text-center" style="min-width: 120px;">
                <span class="d-block fw-bold fs-5 text-info">$<?= number_format($ticket_promedio, 0, '.', ',') ?></span>
                <small class="text-muted d-block fw-bold" style="font-size: 0.6rem;">TICKET PROMEDIO</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 130px;">
                <span class="d-block fw-bold fs-5 text-success">$<?= number_format($clv_total, 0, '.', ',') ?></span>
                <small class="text-muted d-block fw-bold" style="font-size: 0.6rem;">INGRESOS TOTALES</small>
            </div>
        </div>
    </div>
</div>

<!-- SECCIÓN TABLA Y GRÁFICO -->
<div class="row g-4 mb-4">
    
    <div class="col-12 col-xl-4">
        <div class="card-main h-100 shadow-sm p-4 bg-white rounded border-top border-4 border-danger">
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-pie-chart-fill me-2 text-danger"></i>Proporción por Canal
            </h5>
            <p class="text-muted small mb-3">Distribución de prospección por origen.</p>
            <div class="d-flex align-items-center justify-content-center" style="position: relative; height: 220px;">
                <canvas id="chartCanales"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card-main h-100 shadow-sm p-4 bg-white rounded border-top border-4 border-danger">
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-funnel-fill me-2 text-danger"></i>Rendimiento por Canal de Atracción
            </h5>
            <p class="text-muted small mb-3">Efectividad de cierre por canal de contacto.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light">
                        <tr class="text-uppercase small fw-bold text-muted">
                            <th>Canal de Origen</th>
                            <th class="text-center">Total Leads</th>
                            <th class="text-center">Cotizaciones</th>
                            <th class="text-center">Ventas Exitosas</th>
                            <th class="text-end">Efectividad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (count($canales_data) > 0):
                            foreach($canales_data as $c): 
                                $efectividad = ($c['cantidad_leads'] > 0) ? ($c['ventas_cerradas'] / $c['cantidad_leads']) * 100 : 0;
                        ?>
                        <tr>
                            <td><span class="fw-bold text-dark"><i class="bi bi-arrow-right-short text-danger fs-5"></i> <?= htmlspecialchars($c['canal_origen']) ?></span></td>
                            <td class="text-center fw-semibold text-secondary"><?= $c['cantidad_leads'] ?></td>
                            <td class="text-center text-muted"><?= $c['cotizaciones_emitidas'] ?></td>
                            <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1"><?= $c['ventas_cerradas'] ?></span></td>
                            <td class="text-end fw-bold text-danger"><?= number_format($efectividad, 1) ?>%</td>
                        </tr>
                        <?php 
                            endforeach; 
                        else:
                        ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Sin datos de canales disponibles.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- SECCIÓN RANKING Y MODELOS -->
<div class="row g-4 mb-4">
    
    <div class="col-12 col-lg-5">
        <div class="card-main h-100 shadow-sm p-4 bg-white rounded border-top border-4 border-danger">
            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-trophy-fill me-2 text-warning"></i>Clientes VIP</h5>
            <p class="text-muted small mb-3">Top 5 clientes de mayor facturación.</p>
            <ul class="list-group list-group-flush">
                <?php
                $contador = 1;
                if (count($top_results) > 0):
                    foreach($top_results as $top):
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 24px; height: 24px; font-size:0.75rem; font-weight:700;"><?= $contador++ ?></span>
                        <div>
                            <div class="fw-bold text-dark small lh-sm"><?= htmlspecialchars($top['nombre_cliente']) ?></div>
                            <small class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-box-seam me-1"></i>Flota: <?= $top['maquinas_compradas'] ?> u.</small>
                        </div>
                    </div>
                    <span class="fw-bold text-success" style="font-size: 0.92rem;">$<?= number_format($top['inversion_total'], 2, '.', ',') ?></span>
                </li>
                <?php 
                    endforeach;
                else:
                ?>
                <li class="list-group-item text-center text-muted py-3">Sin clientes con historial de compra.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card-main h-100 shadow-sm p-4 bg-white rounded border-top border-4 border-danger">
            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-cpu-fill me-2 text-danger"></i>Ventas por Modelo</h5>
            <p class="text-muted small mb-3">Modelos de maquinaria comercializados.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100 mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase small fw-bold text-muted">
                            <th>Modelo</th>
                            <th class="text-center">Unidades</th>
                            <th class="text-end">Precio Promedio</th>
                            <th class="text-end">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($modelos_data) > 0):
                            foreach($modelos_data as $m):
                        ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($m['modelo']) ?></td>
                            <td class="text-center fw-bold text-secondary"><?= $m['unidades_vendidas'] ?> u.</td>
                            <td class="text-end text-muted">$<?= number_format($m['precio_promedio'], 2, '.', ',') ?></td>
                            <td class="text-end fw-bold text-success">$<?= number_format($m['ingresos_modelo'], 2, '.', ',') ?></td>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sin modelos vendidos aún.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    const ctxCanales = document.getElementById('chartCanales');
    if (ctxCanales && typeof Chart !== 'undefined') {
        new Chart(ctxCanales, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_leads) ?>,
                    backgroundColor: ['#dc3545', '#0d6efd', '#ffc107', '#198754', '#0dcaf0', '#6c757d'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
                cutout: '65%'
            }
        });
    }
});
</script>