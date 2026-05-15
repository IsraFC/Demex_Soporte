<?php
/**
 * ARCHIVO: estadisticas.php
 * DESCRIPCIÓN: Dashboard final con alineación de PDF corregida (sin recortes laterales).
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.41
 */
require_once 'config/db.php';
$pagina_actual = 'estadisticas';
include 'includes/header.php';

$fecha_inicio_val = $_GET['fecha_inicio'] ?? '';
$fecha_fin_val = $_GET['fecha_fin'] ?? '';
?>

<style>
    /* Estilo para el encabezado que SOLO aparecerá en el PDF */
    #pdf-header-extra { display: none; }
    
    @media print {
        .pdf-page-break { 
            page-break-before: always !important; 
            display: block; 
            height: 0; 
            margin: 0; 
            border: none;
        }
        #pdf-header-extra { display: block !important; }

        /* CORRECCIÓN DE ALINEACIÓN: Eliminamos márgenes negativos de Bootstrap que causan el recorte */
        .row { 
            margin-right: 0 !important; 
            margin-left: 0 !important; 
        }
        .container-fluid {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
    }
</style>

<div class="container-fluid py-4" id="area-reporte">
    
    <!-- ENCABEZADO EXCLUSIVO PARA PDF -->
    <div id="pdf-header-extra" class="mb-4">
        <div style="border-bottom: 3px solid #dc3545; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; font-family: Arial, sans-serif;">
            <div>
                <h1 style="color: #dc3545; margin: 0; font-size: 26px;">Reporte de Estadísticas de Soporte - DEMEX</h1>
                <p style="margin: 5px 0 0 0; color: #555; font-size: 14px;">
                    <strong>Periodo:</strong> <?php echo $fecha_inicio_val ? date("d/m/Y", strtotime($fecha_inicio_val)) : '--'; ?> al <?php echo $fecha_fin_val ? date("d/m/Y", strtotime($fecha_fin_val)) : '--'; ?>
                </p>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 32px; font-weight: bold; color: #dc3545; display: block;" id="kpi_pdf">--</span>
                <span style="font-size: 10px; font-weight: bold; color: #888; text-transform: uppercase;">Total Tickets</span>
            </div>
        </div>
    </div>

    <!-- ENCABEZADO WEB ORIGINAL -->
    <div class="row mb-4 align-items-center no-print" id="header-original">
        <div class="col-md-8">
            <h1 class="fw-bold text-danger mb-0"><i class="bi bi-graph-up"></i> Estadísticas de Soporte</h1>
            <p class="text-muted small">Análisis financiero y monitor de tiempos de respuesta por categoría.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="p-3 bg-white shadow-sm rounded-4 border-start border-danger border-4 d-inline-block text-center" style="min-width: 150px;">
                <span class="d-block fw-bold fs-4" id="kpi_total">--</span>
                <small class="text-muted fw-bold small text-uppercase">Total Tickets</small>
            </div>
        </div>
    </div>

    <!-- FILTROS  -->
    <div class="card shadow-sm border-0 rounded-4 mb-4 no-print">
        <div class="card-body p-4">
            <form method="GET" action="estadisticas.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control rounded-3" value="<?php echo $fecha_inicio_val; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Fecha Fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control rounded-3" value="<?php echo $fecha_fin_val; ?>">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger w-100 rounded-3 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Filtrar Datos</button>
                        <a href="estadisticas.php" class="btn btn-outline-secondary rounded-3"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="estado_vacio" class="text-center py-5" style="display: none;">
        <div class="p-5">
            <i class="bi bi-clipboard-x text-muted" style="font-size: 5rem;"></i>
            <h3 class="mt-3 fw-bold text-muted">No se encontraron registros</h3>
        </div>
    </div>

    <div id="dashboard_contenido">
        <!-- HOJA 1: CIRCULARES -->
        <div class="row g-4 mb-4" id="pdf_seccion_1">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 p-4 text-center">
                    <h5 class="fw-bold mb-3 text-start">Garantías</h5>
                    <div style="height: 150px;"><canvas id="chartGar"></canvas></div>
                    <div id="leg_gar" class="mt-4 small border-top pt-3 text-start"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 p-4 text-center">
                    <h5 class="fw-bold mb-3 text-start">Máquinas</h5>
                    <div style="height: 150px;"><canvas id="chartFun"></canvas></div>
                    <div id="leg_fun" class="mt-4 small border-top pt-3 text-start"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 p-4 text-center">
                    <h5 class="fw-bold mb-3 text-start">Estatus Global</h5>
                    <div style="height: 150px;"><canvas id="chartEst"></canvas></div>
                    <div id="leg_est" class="mt-4 small border-top pt-3 text-start"></div>
                </div>
            </div>
        </div>

        <div class="pdf-page-break"></div>

        <!-- HOJA 2: BARRAS -->
        <div class="row g-4 mb-4" id="pdf_seccion_2">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 p-4">
                    <h5>Fallas</h5>
                    <div style="height: 250px;"><canvas id="chartFal"></canvas></div>
                    <div id="leg_fal" class="mt-3 small border-top pt-2"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 p-4">
                    <h5>Tipo Llamada</h5>
                    <div style="height: 250px;"><canvas id="chartLL"></canvas></div>
                    <div id="leg_ll" class="mt-3 small border-top pt-2"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 p-4">
                    <h5>Acciones</h5>
                    <div style="height: 250px;"><canvas id="chartAcc"></canvas></div>
                    <div id="leg_acc" class="mt-3 small border-top pt-2"></div>
                </div>
            </div>
        </div>

        <div class="pdf-page-break"></div>

        <!-- HOJA 3: TABLAS COMPLETAS -->
        <div class="row g-4" id="pdf_seccion_3">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                    <div class="card-header bg-danger text-white p-3"><h5 class="mb-0 fw-bold">Análisis de Costos</h5></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light"><tr><th class="text-start ps-4">Concepto</th><th>Monto Total</th><th>Eventos</th><th>Promedio</th></tr></thead>
                            <tbody id="tabla_financiera"></tbody>
                            <tfoot class="table-light fs-5"><tr id="fila_total_costos"></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                    <div class="card-header bg-dark text-white p-3"><h5 class="mb-0 fw-bold">Tiempo de Acción (Días)</h5></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody id="tabla_tiempos"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center py-5 no-print" id="seccion_boton">
            <button id="btnPDF" class="btn btn-danger btn-lg shadow-lg rounded-pill px-5">
                <i class="bi bi-file-earmark-pdf-fill me-2"></i> Descargar Reporte en PDF
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // --- LÓGICA DE BLOQUEO DE CALENDARIOS ---
    $('#fecha_inicio').on('change', function() {
        var fechaMin = $(this).val();
        if (fechaMin) {
            // Establece la fecha seleccionada como el mínimo permitido para la fecha de fin
            $('#fecha_fin').attr('min', fechaMin);
        }
    });

    $('#fecha_fin').on('change', function() {
        var fechaMax = $(this).val();
        if (fechaMax) {
            // Establece la fecha seleccionada como el máximo permitido para la fecha de inicio
            $('#fecha_inicio').attr('max', fechaMax);
        }
    });

    const apiQuery = new URLSearchParams(window.location.search).toString();
    
    $.getJSON('actions/obtener_estadisticas.php?' + apiQuery, function(data) {
        if (!data.success) return;
        if (data.total === 0) { $('#dashboard_contenido').hide(); $('#estado_vacio').show(); return; }

        $('#kpi_total, #kpi_pdf').text(data.total);

        const setupChart = (ctx, type, labels, d, bg, donut = false) => {
            new Chart(document.getElementById(ctx), { 
                type: donut ? 'doughnut' : type, 
                data: { labels: labels, datasets: [{ data: d, backgroundColor: bg, borderWidth: 0 }] }, 
                options: { cutout: donut ? '75%' : 0, responsive: true, maintainAspectRatio: false, animation: { duration: 800 }, plugins: { legend: { display: false } } } 
            });
        };
        
        setupChart('chartGar', 'pie', ['Válidas', 'No válidas', 'Pendientes'], [data.garantias.v, data.garantias.n, data.garantias.p], ['#198754', '#dc3545', '#adb5bd'], true);
        setupChart('chartFun', 'pie', ['Funcionando', 'No funciona'], [data.funcionamiento.si, data.funcionamiento.no], ['#0d6efd', '#fd7e14']);
        setupChart('chartEst', 'pie', ['Abierto', 'Cerrado', 'Cancelado'], [data.estatus.a, data.estatus.c, data.estatus.x], ['#ffc107', '#198754', '#6c757d'], true);

        const barOpt = { 
            indexAxis: 'y', 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        // Forzamos a que el tooltip muestre el valor completo
                        label: function(context) {
                            return ' Total: ' + context.raw;
                        }
                    }
                }
            } 
        };

        new Chart(document.getElementById('chartFal'), { 
            type: 'bar', 
            data: { 
                labels: ['Mecánica', 'Refrigeración', 'Electrónica', 'Regulador', 'Materia Prima', 'Otra'], 
                datasets: [{ data: Object.values(data.fallas), backgroundColor: '#3b82f6' }] 
            }, 
            options: barOpt 
        });

        new Chart(document.getElementById('chartLL'), { 
            type: 'bar', 
            data: { 
                labels: ['Venta Refacciones', 'Información', 'Capacitaciones', 'Soporte'], 
                datasets: [{ 
                    // Accedemos a las propiedades una por una para que el orden sea SIEMPRE el mismo
                    data: [
                        data.tipo_llamada.venta, 
                        data.tipo_llamada.info, 
                        data.tipo_llamada.capa, 
                        data.tipo_llamada.sop
                    ], 
                    backgroundColor: '#10b981' 
                }] 
            }, 
            options: { 
                ...barOpt, 
                indexAxis: 'x',
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 } // Útil para que no salgan decimales si hay pocos datos
                    }
                }
            } 
        });

        new Chart(document.getElementById('chartAcc'), { 
            type: 'bar', 
            data: { 
                labels: ['Ninguna', 'Envío Técnico', 'Envío Refacciones', 'Técnico + Refacc.', 'Envío Base', 'Reparación Taller', 'Cambio Máquina', 'Información'], 
                datasets: [{ data: Object.values(data.acciones), backgroundColor: '#8b5cf6' }] 
            }, 
            options: barOpt 
        });

        // Función reutilizable para generar leyendas de barras
        const generarLegendaBarras = (idContenedor, etiquetas, valores, color) => {
            let html = '';
            etiquetas.forEach((label, i) => {
                if(valores[i] > 0) { // Solo muestra categorías que tengan datos
                    html += `
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="bi bi-square-fill me-1" style="color:${color}"></i> ${label}</span>
                            <span class="fw-bold">${valores[i]}</span>
                        </div>`;
                }
            });
            $(`#${idContenedor}`).html(html);
        };

        // Generar indicadores para Fallas
        generarLegendaBarras('leg_fal', 
            ['Mecánica', 'Refrigeración', 'Electrónica', 'Regulador', 'Materia Prima', 'Otra'], 
            Object.values(data.fallas), '#3b82f6'
        );

        // Generar indicadores para Tipo Llamada
        generarLegendaBarras('leg_ll', 
            ['Venta Refacciones', 'Información', 'Capacitaciones', 'Soporte'], 
            [data.tipo_llamada.venta, data.tipo_llamada.info, data.tipo_llamada.capa, data.tipo_llamada.sop], '#10b981'
        );

        // Generar indicadores para Acciones
        generarLegendaBarras('leg_acc', 
            ['Ninguna', 'Envío Técnico', 'Envío Refacciones', 'Técnico + Refacc.', 'Envío Base', 'Reparación Taller', 'Cambio Máquina', 'Información'], 
            Object.values(data.acciones), '#8b5cf6'
        );

        const fin = data.financiero;
        const fmt = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);
        $('#tabla_financiera').html(`
            <tr><td class="text-start ps-4 fw-bold small">Refacciones (Garantía)</td><td>${fmt(fin.gar_sum)}</td><td>${fin.gar_count}</td><td class="text-success fw-bold">${fmt(fin.gar_sum/(fin.gar_count||1))}</td></tr>
            <tr><td class="text-start ps-4 fw-bold small">Refacciones (Venta)</td><td>${fmt(fin.venta_sum)}</td><td>${fin.venta_count}</td><td class="text-success fw-bold">${fmt(fin.venta_sum/(fin.venta_count||1))}</td></tr>
            <tr><td class="text-start ps-4 fw-bold small">Costo de Base</td><td>${fmt(fin.base_sum)}</td><td>${fin.base_count}</td><td class="text-primary fw-bold">${fmt(fin.base_sum/(fin.base_count||1))}</td></tr>
            <tr><td class="text-start ps-4 fw-bold small">Costo de Técnico</td><td>${fmt(fin.tec_sum)}</td><td>${fin.tec_count}</td><td class="text-primary fw-bold">${fmt(fin.tec_sum/(fin.tec_count||1))}</td></tr>
            <tr><td class="text-start ps-4 fw-bold small">Costo de Envío</td><td>${fmt(fin.envio_sum)}</td><td>${fin.envio_count}</td><td class="text-primary fw-bold">${fmt(fin.envio_sum/(fin.envio_count||1))}</td></tr>
        `);
        $('#fila_total_costos').html(`<td class="text-start ps-4 small">TOTAL ACUMULADO</td><td class="text-danger">${fmt(fin.total_sum)}</td><td>---</td><td class="text-danger">${fmt(fin.total_sum/(data.total||1))}</td>`);

        const t = data.tiempos;
        const pT = (s, c) => (c > 0) ? (s / c).toFixed(2) : "0.00";
        $('#tabla_tiempos').html(`
            <tr class="table-primary"><td class="ps-4 fw-bold small">SOLUCIÓN (General)</td><td class="text-center fw-bold small">${pT(t.sol_sum, t.sol_count)} d</td></tr>
            <tr><td class="ps-4 text-uppercase small">Envio técnico</td><td class="text-center small">${pT(t.tec_sum, t.tec_count)} d</td></tr>
            <tr><td class="ps-4 text-uppercase small">Envio refacciones</td><td class="text-center small">${pT(t.ref_sum, t.ref_count)} d</td></tr>
            <tr><td class="ps-4 text-uppercase small">Envio técnico y refacciones</td><td class="text-center small">${pT(t.mix_sum, t.mix_count)} d</td></tr>
            <tr><td class="ps-4 text-uppercase small">Envio base</td><td class="text-center small">${pT(t.base_sum, t.base_count)} d</td></tr>
            <tr><td class="ps-4 text-uppercase small">Reparación en taller</td><td class="text-center small">${pT(t.tall_sum, t.tall_count)} d</td></tr>
            <tr><td class="ps-4 text-uppercase small">Cambio de maquina</td><td class="text-center small">${pT(t.camb_sum, t.camb_count)} d</td></tr>
        `);

        $('#leg_gar').html(`<div class="d-flex justify-content-between mb-1"><span>Válidas</span><span class="fw-bold text-success">${data.garantias.v}</span></div><div class="d-flex justify-content-between mb-1"><span>No válidas</span><span class="fw-bold text-danger">${data.garantias.n}</span></div><div class="d-flex justify-content-between"><span>Pendientes</span><span class="fw-bold text-warning">${data.garantias.p}</span></div>`);
        $('#leg_fun').html(`<div class="d-flex justify-content-between mb-1"><span>Funcionando</span><span class="fw-bold text-primary">${data.funcionamiento.si}</span></div><div class="d-flex justify-content-between mb-1"><span>No funciona</span><span class="fw-bold text-warning">${data.funcionamiento.no}</span></div>`);
        $('#leg_est').html(`<div class="d-flex justify-content-between mb-1"><span>Abiertos</span><span class="fw-bold text-warning">${data.estatus.a}</span></div><div class="d-flex justify-content-between mb-1"><span>Cerrados</span><span class="fw-bold text-success">${data.estatus.c}</span></div><div class="d-flex justify-content-between"><span>Cancelados</span><span class="fw-bold text-secondary">${data.estatus.x}</span></div>`);
    });

    $('#btnPDF').click(function() {
        const btn = $(this);
        const ahora = new Date();
        const fechaStr = ahora.toLocaleDateString('es-MX', {day:'2-digit',month:'2-digit',year:'numeric'}).replace(/\//g,'-');
        const horaStr = ahora.toLocaleTimeString('es-MX', {hour:'2-digit',minute:'2-digit',hour12:false}).replace(':','h');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        window.scrollTo(0, 0);

        const elemento = document.getElementById('area-reporte');
        $('nav, .no-print, #header-original').hide();
        $('#pdf-header-extra').show();

        const opciones = {
            margin: [10, 10, 10, 10], // Margen uniforme
            filename: `Reporte_DEMEX_${fechaStr}_${horaStr}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2, 
                useCORS: true, 
                scrollY: 0,
                windowWidth: 1450 // Aumentamos el visor para que nada se encime o se corte a la izquierda
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
            pagebreak: { mode: 'css', before: '.pdf-page-break' }
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            $('#pdf-header-extra').hide();
            $('nav, .no-print, #header-original').show();
            btn.prop('disabled', false).html('<i class="bi bi-file-earmark-pdf-fill me-2"></i> Descargar Reporte en PDF');
        });
    });
});
</script>
<?php include 'includes/footer.php'; ?>