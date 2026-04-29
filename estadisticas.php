<?php
/**
 * ARCHIVO: estadisticas.php
 * DESCRIPCIÓN: Módulo de Business Intelligence (BI) y Reportes Técnicos.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.16
 */
$pagina_actual = 'estadisticas';
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="fw-bold text-danger mb-0"><i class="bi bi-graph-up"></i> Estadísticas de Soporte</h1>
            <p class="text-muted small">Análisis financiero y monitor de tiempos de respuesta por categoría.</p>
        </div>
        <!-- El KPI ahora tiene un ID para ocultarlo fácilmente en el PDF -->
        <div class="col-md-4 text-md-end" id="kpi_superior">
            <div class="p-3 bg-white shadow-sm rounded-4 border-start border-danger border-4 d-inline-block text-center" style="min-width: 150px;">
                <span class="d-block fw-bold fs-4" id="kpi_total">--</span>
                <small class="text-muted fw-bold small text-uppercase">Total Tickets</small>
            </div>
        </div>
    </div>

    <!-- FILA 1: GRÁFICOS CIRCULARES -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <div><h5 class="fw-bold mb-0">Garantías</h5><small id="audit_gar" class="fw-bold small text-muted">...</small></div>
                    </div>
                    <div style="height: 150px;"><canvas id="chartGar"></canvas></div>
                    <div id="leg_gar" class="mt-4 small border-top pt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <div><h5 class="fw-bold mb-0">Máquinas</h5><small id="audit_fun" class="fw-bold small text-muted">...</small></div>
                    </div>
                    <div style="height: 150px;"><canvas id="chartFun"></canvas></div>
                    <div id="leg_fun" class="mt-4 small border-top pt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <div><h5 class="fw-bold mb-0">Estatus Global</h5><small id="audit_est" class="fw-bold small text-muted">...</small></div>
                    </div>
                    <div style="height: 150px;"><canvas id="chartEst"></canvas></div>
                    <div id="leg_est" class="mt-4 small border-top pt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="html2pdf__page-break" style="page-break-before: always;"></div>

    <!-- FILA 2: GRÁFICOS DE BARRAS -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4"><div class="card h-100 shadow-sm border-0 rounded-4"><div class="card-body p-4"><h5>Fallas</h5><div style="height: 250px;"><canvas id="chartFal"></canvas></div></div></div></div>
        <div class="col-lg-4"><div class="card h-100 shadow-sm border-0 rounded-4"><div class="card-body p-4"><h5>Tipo Llamada</h5><div style="height: 250px;"><canvas id="chartLL"></canvas></div></div></div></div>
        <div class="col-lg-4"><div class="card h-100 shadow-sm border-0 rounded-4"><div class="card-body p-4"><h5>Acciones</h5><div style="height: 250px;"><canvas id="chartAcc"></canvas></div></div></div></div>
    </div>

    <div class="html2pdf__page-break" style="page-break-before: always;"></div>

    <!-- FILA 3: TABLAS -->
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-header bg-danger text-white p-3"><h5 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-2"></i> Análisis de Costos</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light small fw-bold"><tr><th class="text-start ps-4">Concepto</th><th>Monto Total</th><th>Eventos (>0)</th><th>Promedio</th></tr></thead>
                        <tbody id="tabla_financiera"></tbody>
                        <tfoot class="table-light fw-bold fs-5"><tr id="fila_total_costos"></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-header bg-dark text-white p-3"><h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-2"></i> Tiempo de Acción (Días)</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small fw-bold"><tr><th class="ps-4">Acción</th><th class="text-center">Promedio Días</th></tr></thead>
                        <tbody id="tabla_tiempos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECCIÓN DEL BOTÓN -->
<div class="row mt-5 mb-4 justify-content-center" id="seccion_boton">
    <div class="col-auto">
        <button id="btnPDF" class="btn btn-danger btn-lg shadow-lg rounded-pill px-5">
            <i class="bi bi-file-earmark-pdf-fill me-2"></i> Descargar Reporte en PDF
        </button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Carga de datos original
    $.getJSON('actions/obtener_estadisticas.php', function(data) {
        if (!data.success) return;
        $('#kpi_total').text(data.total);

        const setupCircle = (ctx, labels, d, bg, donut = false) => {
            new Chart(document.getElementById(ctx), { type: donut ? 'doughnut' : 'pie', data: { labels: labels, datasets: [{ data: d, backgroundColor: bg, borderWidth: 0 }] }, options: { cutout: donut ? '75%' : 0, responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        };
        setupCircle('chartGar', ['Válidas', 'No válidas', 'Pendientes'], [data.garantias.v, data.garantias.n, data.garantias.p], ['#198754', '#dc3545', '#adb5bd'], true);
        setupCircle('chartFun', ['Funcionando', 'No funciona'], [data.funcionamiento.si, data.funcionamiento.no], ['#0d6efd', '#fd7e14']);
        setupCircle('chartEst', ['Abierto', 'Cerrado', 'Cancelado'], [data.estatus.a, data.estatus.c, data.estatus.x], ['#ffc107', '#198754', '#6c757d'], true);
        
        const mapFallas = { 'Mec': 'Mecánica', 'Ref': 'Refrigeración', 'Ele': 'Electrónica', 'Reg': 'Regulador', 'MP': 'Materia Prima', 'Otr': 'Otra' };
        const mapLlamada = { 'Venta': 'Venta Refacciones', 'Info': 'Información', 'Capa': 'Capacitaciones', 'Sop': 'Soporte' };
        const mapAcciones = { 'Ning': 'Ninguna', 'Tec': 'Envío Técnico', 'Ref': 'Envío Refacciones', 'Mix': 'Técnico + Refacciones', 'Base': 'Envío Base', 'Tall': 'Reparación Taller', 'Camb': 'Cambio Máquina', 'Info': 'Información' };

        new Chart(document.getElementById('chartFal'), { type: 'bar', data: { labels: Object.keys(mapFallas), datasets: [{ data: [data.fallas.mec, data.fallas.ref, data.fallas.ele, data.fallas.reg, data.fallas.mp, data.fallas.otr], backgroundColor: '#3b82f6', borderRadius: 5 }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        new Chart(document.getElementById('chartLL'), { type: 'bar', data: { labels: Object.keys(mapLlamada), datasets: [{ data: [data.tipo_llamada.venta, data.tipo_llamada.info, data.tipo_llamada.capa, data.tipo_llamada.sop], backgroundColor: '#10b981', borderRadius: 5 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        new Chart(document.getElementById('chartAcc'), { type: 'bar', data: { labels: Object.keys(mapAcciones), datasets: [{ data: [data.acciones.ning, data.acciones.e_tec, data.acciones.e_ref, data.acciones.e_amb, data.acciones.e_bas, data.acciones.tall, data.acciones.camb, data.acciones.info], backgroundColor: '#8b5cf6', borderRadius: 5 }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });

        const fin = data.financiero;
        const fmt = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);
        $('#tabla_financiera').html(`
            <tr><td class="text-start ps-4 fw-bold">Refacciones (Garantía)</td><td>${fmt(fin.gar_sum)}</td><td>${fin.gar_count}</td><td class="text-success fw-bold">${fmt(fin.gar_sum / (fin.gar_count || 1))}</td></tr>
            <tr><td class="text-start ps-4 fw-bold">Refacciones (Venta)</td><td>${fmt(fin.venta_sum)}</td><td>${fin.venta_count}</td><td class="text-success fw-bold">${fmt(fin.venta_sum / (fin.venta_count || 1))}</td></tr>
            <tr><td class="text-start ps-4 fw-bold">Costo de Base</td><td>${fmt(fin.base_sum)}</td><td>${fin.base_count}</td><td class="text-primary fw-bold">${fmt(fin.base_sum / (fin.base_count || 1))}</td></tr>
            <tr><td class="text-start ps-4 fw-bold">Costo de Técnico</td><td>${fmt(fin.tec_sum)}</td><td>${fin.tec_count}</td><td class="text-primary fw-bold">${fmt(fin.tec_sum / (fin.tec_count || 1))}</td></tr>
            <tr><td class="text-start ps-4 fw-bold">Costo de Envío</td><td>${fmt(fin.envio_sum)}</td><td>${fin.envio_count}</td><td class="text-primary fw-bold">${fmt(fin.envio_sum / (fin.envio_count || 1))}</td></tr>
        `);
        $('#fila_total_costos').html(`<td class="text-start ps-4">TOTAL ACUMULADO</td><td class="text-danger">${fmt(fin.total_sum)}</td><td>---</td><td class="text-danger">${fmt(fin.total_sum / data.total)}</td>`);
        
        const t = data.tiempos;
        const pT = (s, c) => (c > 0) ? (s / c).toFixed(2) : "0.00";
        $('#tabla_tiempos').html(`
            <tr class="table-primary"><td class="ps-4 fw-bold">SOLUCIÓN (General)</td><td class="text-center fw-bold">${pT(t.sol_sum, t.sol_count)} d</td></tr>
            <tr><td class="ps-4">Envío Técnico</td><td class="text-center">${pT(t.tec_sum, t.tec_count)} d</td></tr>
            <tr><td class="ps-4">Envío Refacciones</td><td class="text-center">${pT(t.ref_sum, t.ref_count)} d</td></tr>
            <tr><td class="ps-4">Técnico + Refacciones</td><td class="text-center">${pT(t.mix_sum, t.mix_count)} d</td></tr>
            <tr><td class="ps-4">Envío Base</td><td class="text-center">${pT(t.base_sum, t.base_count)} d</td></tr>
            <tr><td class="ps-4">Reparación en Taller</td><td class="text-center">${pT(t.tall_sum, t.tall_count)} d</td></tr>
            <tr><td class="ps-4">Cambio de Máquina</td><td class="text-center">${pT(t.camb_sum, t.camb_count)} d</td></tr>
        `);

        $('#leg_gar').html(`<div class="d-flex justify-content-between mb-1"><span>Válidas</span><span class="fw-bold text-success">${data.garantias.v}</span></div><div class="d-flex justify-content-between mb-1"><span>Vencidas</span><span class="fw-bold text-danger">${data.garantias.n}</span></div><div class="d-flex justify-content-between"><span>Pendientes</span><span class="fw-bold text-warning">${data.garantias.p}</span></div>`);
        $('#leg_fun').html(`<div class="d-flex justify-content-between mb-1"><span>Funcionando</span><span class="fw-bold text-primary">${data.funcionamiento.si}</span></div><div class="d-flex justify-content-between"><span>No funciona</span><span class="fw-bold text-warning">${data.funcionamiento.no}</span></div>`);
        $('#leg_est').html(`<div class="d-flex justify-content-between mb-1"><span>Abiertos</span><span class="fw-bold text-warning">${data.estatus.a}</span></div><div class="d-flex justify-content-between mb-1"><span>Cerrados</span><span class="fw-bold text-success">${data.estatus.c}</span></div><div class="d-flex justify-content-between"><span>Cancelados</span><span class="fw-bold text-secondary">${data.estatus.x}</span></div>`);
        
        $('#audit_gar').text(`Suma: ${data.garantias.suma} / ${data.total}`);
        $('#audit_fun').text(`Suma: ${data.funcionamiento.suma} / ${data.total}`);
        $('#audit_est').text(`Suma: ${data.estatus.suma} / ${data.total}`);
    });

    // LÓGICA DE PDF REFINADA (Versión 1.16)
    $('#btnPDF').click(function() {
        const ahora = new Date();
        const fechaStr = ahora.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        const horaStr = ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false }).replace(':', 'h');
        
        const elemento = document.querySelector('.container-fluid'); 
        
        const opciones = {
            margin: [10, 10, 10, 10],
            filename: `Reporte_Estadisticas_${fechaStr}_${horaStr}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 3, 
                useCORS: true, 
                letterRendering: true,
                scrollY: 0,
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
            pagebreak: { mode: ['css', 'legacy'] } 
        };

        const boton = $(this);
        boton.html('<span class="spinner-border spinner-border-sm me-2"></span> Generando...');
        boton.prop('disabled', true);

        // OCULTADO TEMPORAL PARA REPORTE LIMPIO
        $('nav').hide(); // Oculta menú
        $('#kpi_superior').hide(); // Oculta el recuadro que no se ve
        $('#seccion_boton').hide(); // Oculta el propio botón

        html2pdf().set(opciones).from(elemento).save().then(() => {
            boton.html('<i class="bi bi-file-earmark-pdf-fill me-2"></i> Descargar Reporte en PDF');
            boton.prop('disabled', false);
            // Restauramos todo
            $('nav').show();
            $('#kpi_superior').show();
            $('#seccion_boton').show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>