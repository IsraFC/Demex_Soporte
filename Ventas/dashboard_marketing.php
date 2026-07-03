<?php
/**
 * ARCHIVO: Ventas/dashboard_marketing.php
 * DESCRIPCIÓN: Dashboard Interactivo de Marketing y Analítica Comercial.
 * Integra métricas de conversión de leads, efectividad de canales de origen y top de inversión.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.1 (Corrección de Variables y Métricas en Caliente)
 */

$page_title = "Dashboard de Marketing y Analítica | CRM Ventas";
require_once '../config/db.php';

/**
 * 1. CONSULTAS PARA KPIs PRINCIPALES
 */
// Total de Prospectos que han llegado por el formulario público
$total_leads = $pdo->query("SELECT COUNT(*) FROM prospectos")->fetchColumn();

// Total de Clientes que han cerrado al menos una venta
$total_clientes = $pdo->query("SELECT COUNT(DISTINCT id_cliente) FROM ventas_historial")->fetchColumn();

// Inversión Comercial Acumulada (Mismo cálculo CLV neta de clientes)
$clv_total = $pdo->query("SELECT IFNULL(SUM(precio_pactado_neto * cantidad), 0) FROM ventas_historial")->fetchColumn();

/**
 * 2. CONSULTA DE DISTRIBUCIÓN POR CANAL DE ORIGEN (Para Gráfica/Tabla)
 */
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
$canales_data = $stmt_canales->fetchAll(PDO::FETCH_ASSOC);

$maquinas_reales = ['DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<!-- ENCABEZADO Y CONTADORES KPI -->
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-bar-chart-line-fill"></i> Inteligencia y Métricas de Marketing</h1>
        <p class="text-muted small">Análisis de rendimiento de canales de atracción, conversión de embudo y analítica CLV.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-secondary border-4 text-center" style="min-width: 110px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= $total_leads ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">LEADS TOTALES</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-primary border-4 text-center" style="min-width: 110px;">
                <span class="d-block fw-bold fs-5 text-primary"><?= $total_clientes ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">CONVERTIDOS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 110px;">
                <span class="d-block fw-bold fs-5 text-warning"><?= number_format(($total_leads > 0 ? ($total_clientes / $total_leads) * 100 : 0), 1) ?>%</span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">% CONVERSIÓN</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 140px;">
                <span class="d-block fw-bold fs-5 text-success">$<?= number_format($clv_total, 0, '.', ',') ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">INGRESOS TOTALES</small>
            </div>
        </div>
    </div>
</div>

<!-- SECCIÓN GRÁFICAS Y DESGLOSE -->
<div class="row mb-4">
    <!-- Distribución por Canales de Origen -->
    <div class="col-md-7">
        <div class="card-main h-100 shadow-lg p-4 bg-white rounded border-top border-4 border-danger">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart-fill me-2 text-danger"></i>Rendimiento por Canal de Atracción</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm" style="font-size: 0.88rem;">
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
                        foreach($canales_data as $c): 
                            $efectividad = ($c['cantidad_leads'] > 0) ? ($c['ventas_cerradas'] / $c['cantidad_leads']) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-dark"><i class="bi bi-arrow-right-short text-danger"></i> <?= htmlspecialchars($c['canal_origen']) ?></span>
                            </td>
                            <td class="text-center fw-semibold text-secondary"><?= $c['cantidad_leads'] ?></td>
                            <td class="text-center text-muted"><?= $c['cotizaciones_emitidas'] ?></td>
                            <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success fw-bold" style="border-radius:6px;"><?= $c['ventas_cerradas'] ?></span></td>
                            <td class="text-end fw-bold text-danger"><?= number_format($efectividad, 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Clientes de Mayor Valor (VIP) -->
    <div class="col-md-5">
        <div class="card-main h-100 shadow-lg p-4 bg-white rounded border-top border-4 border-danger">
            <h5 class="fw-bold text-dark mb-3">Clientes de Mayor Inversión</h5>
            <ul class="list-group list-group-flush">
                <?php
                $sql_top_clientes = "SELECT c.nombre_cliente, c.apellidos_cliente, 
                                            SUM(vh.precio_pactado_neto * vh.cantidad) AS inversion_total,
                                            COUNT(vh.id_venta) AS maquinas_compradas
                                     FROM clientes c
                                     INNER JOIN ventas_historial vh ON c.id_cliente = vh.id_cliente
                                     GROUP BY c.id_cliente
                                     ORDER BY inversion_total DESC
                                     LIMIT 5";
                $stmt_top = $pdo->query($sql_top_clientes);
                $contador = 1;
                while($top = $stmt_top->fetch(PDO::FETCH_ASSOC)):
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size:0.75rem; font-weight:700;"><?= $contador++ ?></span>
                        <div>
                            <div class="fw-bold text-dark small lh-sm"><?= htmlspecialchars($top['nombre_cliente'] . ' ' . ($top['apellidos_cliente'] ?? '')) ?></div>
                            <small class="text-muted" style="font-size: 0.7rem; font-weight: 500;"><i class="bi bi-box-seam me-1"></i>Flota: <?= $top['maquinas_compradas'] ?> u.</small>
                        </div>
                    </div>
                    <span class="fw-bold text-success" style="font-size: 0.9rem;">$<?= number_format($top['inversion_total'], 2, '.', ',') ?></span>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<!-- SECCIÓN TABLA: DESEMPEÑO POR MODELO DE MAQUINARIA -->
<div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger">
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-cpu-fill me-2 text-danger"></i>Volumen de Ventas e Ingresos por Modelo Maquinaria</h5>
    <div class="table-responsive">
        <table id="tablaModelos" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>Modelo de Maquinaria</th>
                    <th class="text-center">Unidades Vendidas</th>
                    <th class="text-end">Precio Promedio Pactado</th>
                    <th class="text-end">Ingresos Totales Generados</th>
                    <th class="text-center" style="width: 150px;">Popularidad</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_modelos = "SELECT m.modelo, 
                                       SUM(vh.cantidad) AS unidades_vendidas,
                                       AVG(vh.precio_pactado_neto) AS precio_promedio,
                                       SUM(vh.precio_pactado_neto * vh.cantidad) AS ingresos_modelo
                                FROM ventas_historial vh
                                INNER JOIN maquinaria m ON vh.id_maquina = m.id_maquina
                                GROUP BY m.id_maquina
                                ORDER BY unidades_vendidas DESC";
                $stmt_modelos = $pdo->query($sql_modelos);
                
                // Sacamos el máximo de unidades vendidas para calcular el porcentaje de las barras de progreso
                $max_unidades = $pdo->query("SELECT MAX(unidades) FROM (SELECT SUM(cantidad) AS unidades FROM ventas_historial GROUP BY id_maquina) AS tot")->fetchColumn() ?: 1;

                while($m = $stmt_modelos->fetch(PDO::FETCH_ASSOC)):
                    $porcentaje_barra = ($m['unidades_vendidas'] / $max_unidades) * 100;
                ?>
                <tr>
                    <td class="fw-bold text-dark"><?= htmlspecialchars($m['modelo']) ?></td>
                    <td class="text-center fw-bold text-secondary"><?= $m['unidades_vendidas'] ?> u.</td>
                    <td class="text-end text-muted">$<?= number_format($m['precio_promedio'], 2, '.', ',') ?></td>
                    <td class="text-end fw-bold text-success">$<?= number_format($m['ingresos_modelo'], 2, '.', ',') ?></td>
                    <td>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $porcentaje_barra ?>%; border-radius: 4px;" aria-valuenow="<?= $porcentaje_barra ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#tablaModelos').DataTable({
        "language": { "emptyTable": "No hay registros", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "0 registros", "zeroRecords": "Sin coincidencias", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtp',
        "pageLength": 5,
        "responsive": true,
        "ordering": false
    });
});
</script>