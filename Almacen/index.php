<?php
/**
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Panel de Control Principal de Almacén con Server-side Processing.
 * Realiza el seguimiento completo del ciclo de vida de la maquinaria nueva de importación.
 * @project Almacén Técnico DEMEX
 * @version 5.1 (Validación de Fases y Disparador de Entrega Comercial Inteligente)
 */

require_once '../config/db.php';
$page_title = "Panel de Control - Almacén";

/**
 * CONSULTAS DE INDICADORES (KPIs):
 * Carga inicial de tarjetas informativas superiores basadas en el inventario de stock.
 */
$total_equipos  = $pdo->query("SELECT COUNT(*) FROM almacen_inventario")->fetchColumn();
$sin_revisar    = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'SIN REVISAR'")->fetchColumn();
$kpi_en_almacen = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN ALMACÉN'")->fetchColumn();
$kpi_en_soporte = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN SOPORTE'")->fetchColumn();

include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-boxes me-2"></i>Inventario de Lotes</h1>
        <p class="text-muted small mb-2">Control de flujo de importación, preparación de maquinaria nueva y tiempos de stock.</p>
        <a href="registro_lote.php" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm fw-bold" style="background-color: #dc3545; font-size: 12px;">
            REGISTRAR INGRESO
        </a>
    </div>
    <div class="col-md-7 text-md-end mt-3 mt-md-0">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 100px;">
                <span id="kpi_total" class="d-block fw-bold fs-5 text-dark"><?= intval($total_equipos) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">TOTAL STOCK</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 100px;">
                <span id="kpi_sin_revisar" class="d-block fw-bold fs-5 text-warning"><?= intval($sin_revisar) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">SIN REVISAR</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-primary border-4 text-center" style="min-width: 100px;">
                <span id="kpi_almacen" class="d-block fw-bold fs-5 text-primary"><?= intval($kpi_en_almacen) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">REVISIÓN ALM.</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-info border-4 text-center" style="min-width: 100px;">
                <span id="kpi_soporte" class="d-block fw-bold fs-5 text-info"><?= intval($kpi_en_soporte) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">EN LABORATORIO</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-3 align-items-center px-3">
        <div class="col-md-3">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text border-0 bg-transparent"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0 small" placeholder="Serie o Contenedor...">
            </div>
        </div>
        <div class="col-md-3">
            <select id="filterEstatus" class="form-select border-0 bg-light fw-bold text-muted shadow-sm" style="font-size: 14px;">
                <option value="">Todos los Estatus</option>
                <option value="SIN REVISAR">SIN REVISAR</option>
                <option value="EN REVISIÓN ALMACÉN">EN REVISIÓN ALMACÉN</option>
                <option value="DISPONIBLE PARA SOPORTE">DISPONIBLE PARA SOPORTE</option>
                <option value="EN REVISIÓN SOPORTE">EN REVISIÓN SOPORTE</option>
                <option value="REINGRESO A ALMACÉN">REINGRESO A ALMACÉN</option>
                <option value="DISPONIBLE PARA VENTA">DISPONIBLE PARA VENTA</option>
                <option value="COMODATO">COMODATO</option>
                <option value="PAGADA / POR ENTREGAR">PAGADA / POR ENTREGAR</option>
                <option value="CAMBIO">CAMBIO</option>
                <option value="ENTREGADA">ENTREGADA</option>
            </select>
        </div>
        <div class="col-md-2">
            <select id="filterTipo" class="form-select border-0 bg-light fw-bold text-muted shadow-sm" style="font-size: 14px;">
                <option value="">Todos los Tipos</option>
                <option value="ORIGINAL">ORIGINAL</option>
                <option value="DEMO">DEMO</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-center gap-2">
            <span class="small fw-bold text-muted text-uppercase style-range" style="font-size: 11px;">Rango:</span>
            <input type="date" id="fechaDesde" class="form-control form-control-sm border-0 bg-light shadow-sm text-muted fw-semibold">
            <input type="date" id="fechaHasta" class="form-control form-control-sm border-0 bg-light shadow-sm text-muted fw-semibold">
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaAlmacen" class="table table-hover align-middle w-100">
            <thead class="table-light text-uppercase small fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                <tr>
                    <th>Contenedor</th>
                    <th>Modelo</th>
                    <th>Nº Serie</th>
                    <th>Tipo</th>
                    <th>Estatus</th>
                    <th>Ingreso</th>
                    <th title="Días antes de que el almacén empiece ajustes" data-bs-toggle="tooltip" class="text-center">Espera Caja</th>
                    <th title="Días que le tomó al almacén la revisión inicial" data-bs-toggle="tooltip" class="text-center">Ajustes Alm.</th>
                    <th title="Días en soporte técnico" data-bs-toggle="tooltip" class="text-center">En Laboratorio</th>
                    <th title="Días totales en inventario" data-bs-toggle="tooltip" class="text-center">Total Gral</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody class="small fw-semibold text-dark">
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalActualizarFase" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-danger text-white border-0 py-3 shadow-sm">
                <h5 class="modal-title fw-bold text-uppercase mb-0" style="font-size: 0.95rem;"><i class="bi bi-calendar-check-fill me-2"></i> Cambiar Fase Logística</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="contenidoFase">
                <div class="text-center p-5"><div class="spinner-border text-danger" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAsignarCliente" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-success text-white border-0 py-3 shadow-sm">
                <h5 class="modal-title fw-bold text-uppercase mb-0" style="font-size: 0.95rem;"><i class="bi bi-person-plus-fill me-2"></i> Asignar Cliente y Activar Garantía</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="contenidoAsignacion">
                <div class="text-center p-5"><div class="spinner-border text-success" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    var table;

    function actualizarKPIs() {
        $.ajax({
            url: 'actions/obtener_conteos_kpi.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#kpi_total').text(response.total);
                    $('#kpi_sin_revisar').text(response.sin_revisar);
                    $('#kpi_almacen').text(response.en_almacen);
                    $('#kpi_soporte').text(response.en_soporte);
                }
            }
        });
    }

    $(document).ready(function() {
        if ($('#tablaAlmacen').length) {
            table = $('#tablaAlmacen').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "actions/obtener_inventario_datatable.php",
                    "type": "POST",
                    "data": function(d) {
                        d.filterEstatus = $('#filterEstatus').val();
                        d.filterTipo    = $('#filterTipo').val();
                        d.fechaDesde    = $('#fechaDesde').val();
                        d.fechaHasta    = $('#fechaHasta').val();
                    }
                },
                "columns": [
                    { "data": "contenedor", "className": "fw-bold text-secondary" },
                    { "data": "modelo", "className": "fw-bold text-dark" },
                    { "data": "no_serie", "render": function(data) { return `<span class="fw-bold text-danger text-nowrap">${data}</span>`; } },
                    { "data": "tipo", "render": function(data) { return data ? data : 'N/A'; } },
                    { 
                        "data": "estatus",
                        "render": function(data) {
                            let badge = 'bg-secondary';
                            if (data === 'SIN REVISAR') badge = 'bg-warning text-dark';
                            else if (data === 'EN REVISIÓN ALMACÉN' || data === 'EN REVISIÓN SOPORTE') badge = 'bg-primary text-white';
                            else if (data === 'DISPONIBLE PARA SOPORTE' || data === 'DISPONIBLE PARA VENTA') badge = 'bg-info text-dark';
                            else if (data === 'PAGADA / POR ENTREGAR') badge = 'bg-dark text-white';
                            else if (data === 'ENTREGADA') badge = 'bg-success text-white';
                            
                            return `<span class="badge ${badge}" style="font-size: 0.65rem; padding: 0.35rem 0.5rem;">${data}</span>`;
                        }
                    },
                    { "data": "fecha_ingreso_contenedor" },
                    { "data": "dias_espera_caja", "className": "text-center text-muted fw-bold" },
                    { "data": "dias_ajustes_almacen", "className": "text-center text-muted fw-bold" },
                    { "data": "dias_soporte", "className": "text-center text-muted fw-bold" },
                    { "data": "dias_inventario_total", "className": "text-center text-danger fw-bold" },
                    {
                        "data": null,
                        "orderable": false,
                        "className": "text-center",
                        "render": function(data, type, row) {
                            if (row.estatus === 'DISPONIBLE PARA SOPORTE' || row.estatus === 'EN REVISIÓN SOPORTE') {
                                return `<button type="button" class="btn btn-outline-secondary border-0 opacity-50" title="Retenido por área de Soporte Técnico" onclick="Swal.fire({icon:'warning', title:'Fase Bloqueada', text:'El equipo físico está bajo el resguardo y diagnóstico del laboratorio de Soporte.'})">
                                            <i class="bi bi-lock-fill fs-5 text-muted"></i>
                                        </button>`;
                            }

                            // CORRECCIÓN COMERCIAL: El botón de asignación obligatoria se activa para Ventas consolidadas o Cambios por defecto de fábrica
                            if (row.estatus === 'PAGADA / POR ENTREGAR' || row.estatus === 'CAMBIO') {
                                return `<button type="button" class="btn btn-success btn-xs rounded-pill px-2 fw-bold" onclick="abrirModalAsignacion(${row.id})" style="font-size: 11px;">
                                            <i class="bi bi-person-plus-fill me-1"></i> Entregar
                                        </button>`;
                            }
                            
                            if (row.estatus === 'ENTREGADA') {
                                return `<button type="button" class="btn btn-outline-success border-0 opacity-75" title="Flujo de Stock Finalizado" disabled>
                                            <i class="bi bi-check-all fs-5"></i>
                                        </button>`;
                            }
                            
                            return `<button type="button" class="btn btn-outline-danger border-0" onclick="abrirModalFase(${row.id})" title="Avanzar de Fase u Obtener Bitácora">
                                        <i class="bi bi-arrow-right-circle-fill fs-5"></i>
                                    </button>`;
                        }
                    }
                ],
                "language": {
                    "sProcessing":     "Procesando...",
                    "sLengthMenu":     "Mostrar _MENU_ registros",
                    "sZeroRecords":    "No se encontraron resultados",
                    "sInfo":           "Mostrando _START_ al _END_ de _TOTAL_",
                    "sInfoEmpty":      "Mostrando 0 al 0 de 0",
                    "sInfoFiltered":   "(filtrado de _MAX_ registros)",
                    "sSearch":         "Buscar:",
                    "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Sig", "sPrevious": "Ant" }
                },
                "dom": 'rtip',
                "pageLength": 13,
                "order": [[5, "desc"]]
            });

            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
            $('#filterEstatus, #filterTipo, #fechaDesde, #fechaHasta').on('change', function() { table.draw(); });
            
            $('#fechaDesde').on('change', function() { $('#fechaHasta').attr('min', $(this).val()); });
            $('#fechaHasta').on('change', function() { $('#fechaDesde').attr('max', $(this).val()); });
            
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
        }
    });

    function abrirModalFase(id) {
        var idLimpio = parseInt(id, 10);
        if (isNaN(idLimpio) || idLimpio <= 0) {
            return;
        }
        $('#modalActualizarFase').appendTo("body").modal('show');
        $('#contenidoFase').html('<div class="text-center p-5"><div class="spinner-border text-danger" role="status"></div></div>');
        
        $.ajax({
            url: 'actions/abrir_modal_fase.php?id=' + idLimpio,
            method: 'GET',
            success: function(html) { $('#contenidoFase').html(html); }
        });
    }

    function abrirModalAsignacion(id) {
        var idLimpio = parseInt(id, 10);
        if (isNaN(idLimpio) || idLimpio <= 0) {
            return;
        }
        $('#modalAsignarCliente').appendTo("body").modal('show');
        $('#contenidoAsignacion').html('<div class="text-center p-5"><div class="spinner-border text-success" role="status"></div></div>');
        
        $.ajax({
            url: 'actions/abrir_modal_entrega.php?id=' + idLimpio,
            method: 'GET',
            success: function(html) { $('#contenidoAsignacion').html(html); }
        });
    }
</script>