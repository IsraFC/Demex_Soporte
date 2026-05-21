<?php
/**
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Panel de Control Principal (Dashboard) con Server-side Processing.
 * Centraliza la visualización de tickets mediante un motor de filtrado remoto.
 * Mantiene lógica de indicadores (KPI) y sistema de alarmas de urgencia.
 * * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 2.0 (Optimizado)
 */

require_once 'config/db.php';

/**
 * CONSULTAS DE INDICADORES (KPIs):
 * Estas se mantienen porque alimentan las tarjetas superiores al cargar la página.
 */
$total = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte")->fetchColumn();
$pendientes = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte WHERE estatus = 'Abierto'")->fetchColumn();

$sql_cobro = "SELECT SUM(d.costo_total) FROM Detalles_Costos_Tiempos d
JOIN Tickets_Soporte t ON d.id_ticket = t.id_ticket
WHERE d.estatus_pago = 'Pendiente' AND t.estatus = 'Abierto'";
$por_cobrar = $pdo->query($sql_cobro)->fetchColumn() ?: 0;

$criticos = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte 
                         WHERE estatus = 'Abierto' 
                         AND DATEDIFF(CURDATE(), fecha_inicial) >= 14")->fetchColumn();

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-speedometer2"></i> Panel de Seguimiento</h1>
        <p class="text-muted small">Sistema de gestión y soporte técnico.</p>
    </div>
    <div class="col-md-7 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 90px;">
                <span class="d-block fw-bold fs-5"><?= $total ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">TICKETS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 90px;">
                <span id="kpi_pendientes" class="d-block fw-bold fs-5 text-warning"><?= $pendientes ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">ABIERTOS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 120px;">
                <span id="kpi_cobrar" class="d-block fw-bold fs-5 text-success">$<?= number_format($por_cobrar, 2) ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">POR COBRAR</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 90px;">
                <span id="kpi_criticos" class="d-block fw-bold fs-5 <?= ($criticos > 0) ? 'text-danger ms-1-animate' : 'text-muted' ?>">
                    <?= $criticos ?>
                </span>
                <small class="text-muted" style="font-size: 0.6rem;">CRÍTICOS</small>
            </div>
        </div>
    </div>
</div>

<?php if ($criticos > 0): ?>
    <div class="alert alert-danger shadow-sm border-0 border-start border-4 border-danger bg-white d-flex align-items-center justify-content-between animate__animated animate__headShake" role="alert" id="alertaCriticos">
        <div>
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
            <span class="fw-bold">Atención:</span> Hay <strong><?= $criticos ?></strong> tickets con más de 2 semanas abiertos.
        </div>
        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold" id="btnFiltrarCriticos">
            <i class="bi bi-funnel-fill me-1"></i> Ver Urgentes
        </button>
    </div>
<?php endif; ?>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-3 align-items-center px-3 mb-3">
        <div class="col-md-3">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text border-0 bg-transparent"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Cliente o Serie...">
            </div>
        </div>
        <div class="col-md-3">
            <select id="filterTipo" class="form-select border-0 bg-light fw-bold text-muted shadow-sm">
                <option value="">Todas las Llamadas</option>
                <option value="Soporte">Soporte</option>
                <option value="Venta Refacciones">Venta Refacciones</option>
                <option value="Información">Información</option>
                <option value="Capacitaciones">Capacitaciones</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterFalla" class="form-select border-0 bg-light fw-bold text-muted shadow-sm">
                <option value="">Todas las Fallas</option>
                <option value="Mecánica">Mecánica</option>
                <option value="Refrigeración">Refrigeración</option>
                <option value="Electrónica">Electrónica</option>
                <option value="Regulador">Regulador</option>
                <option value="Materia prima">Materia prima</option>
                <option value="Otra">Otra</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterAccion" class="form-select border-0 bg-light fw-bold text-muted shadow-sm">
                <option value="">Todas las Acciones</option>
                <option value="Ninguna">Ninguna</option>
                <option value="Envio técnico">Envío técnico</option>
                <option value="Envio refacciones">Envío refacciones</option>
                <option value="Envio técnico y refacciones">Técnico + Refacc.</option>
                <option value="Envio base">Envío base</option>
                <option value="Reparación en taller">Reparación en taller</option>
                <option value="Cambio de maquina">Cambio de máquina</option>
            </select>
        </div>
    </div>

    <div class="row g-3 align-items-center px-3 pt-3">
        <div class="col-md-4 d-flex align-items-center gap-2">
            <span class="small fw-bold text-muted text-uppercase me-1">Rango:</span>
            <input type="date" id="fechaDesde" class="form-control form-control-sm border-0 bg-light shadow-sm text-muted">
            <input type="date" id="fechaHasta" class="form-control form-control-sm border-0 bg-light shadow-sm text-muted">
        </div>
        <div class="col-md-8 d-flex justify-content-end gap-4">
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkSoloPendientes">
                <label class="form-check-label small fw-bold text-muted" for="checkSoloPendientes">Solo Abiertos</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkGarantia">
                <label class="form-check-label small fw-bold text-muted" for="checkGarantia">Garantía Válida</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkSoloDeuda">
                <label class="form-check-label small fw-bold text-danger" for="checkSoloDeuda">Con Deuda</label>
            </div>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaTickets" class="table table-hover align-middle w-100">
            <thead class="table-light text-uppercase small fw-bold">
                <tr>
                    <th data-type="num">#</th> 
                    <th>Cliente</th>
                    <th>Equipo / Serie</th>
                    <th class="d-none">Tipo Llamada</th> 
                    <th>Falla</th>
                    <th class="d-none">Accion Realizada</th>
                    <th>Garantía</th> 
                    <th>Pago</th> 
                    <th>Estatus</th> 
                    <th>Inicio</th> 
                    <th class="text-center">Acción</th>
                    <th>Envios</th>
                </tr>
            </thead>
            <tbody>
                </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-3" style="gap: 10px;">
    <button type="button" class="btn btn-outline-success shadow-sm rounded-pill px-3" onclick="confirmarRespaldo()">
        <i class="bi bi-file-earmark-excel-fill me-1"></i> Respaldo y Limpieza
    </button>
</div>

<div class="modal fade" id="modalVisualizar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold text-uppercase">Resumen Total del Ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoTicket">
                <div class="text-center p-5"><div class="spinner-border text-danger" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    /**
     * AJAX: Recupera el desglose técnico y financiero del ticket.
     */
    function abrirModalVisualizar(id) {
        $('#modalVisualizar').modal('show');
        $('#contenidoTicket').html('<div class="text-center p-5"><div class="spinner-border text-danger"></div></div>');
        $.ajax({
            url: 'actions/obtener_detalles_ticket.php',
            method: 'GET',
            data: { id_ticket: id },
            success: function(html) {
                $('#contenidoTicket').html(html);
            }
        });
    }

    /**
     * CONTROL DE FLUJO: Actualiza el estatus del ticket con recarga AJAX.
     */
    function cambiarEstatus(id, nuevoEstatus) {
        const colorEstatus = { 'Abierto': '#ffc107', 'Cerrado': '#198754', 'Cancelado': '#6c757d' };

        Swal.fire({
            title: `¿${nuevoEstatus === 'Cerrado' ? 'Cerrar' : 'Cancelar'} ticket #${id}?`,
            text: `El estatus cambiará a ${nuevoEstatus.toLowerCase()} de forma permanente.`,
            icon: nuevoEstatus === 'Cerrado' ? 'success' : 'warning',
            showCancelButton: true,
            confirmButtonColor: colorEstatus[nuevoEstatus],
            cancelButtonColor: '#adb5bd',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Regresar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'actions/actualizar_estatus.php',
                    method: 'POST',
                    data: { id_ticket: id, estatus: nuevoEstatus },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({ title: '¡Hecho!', icon: 'success', timer: 1000, showConfirmButton: false });
                            // Al ser Server-side, simplemente recargamos los datos del servidor
                            // 'false' mantiene la posición de la página actual
                            table.ajax.reload(null, false); 
                        }
                    }
                });
            }
        });
    }

    /**
     * FUNCIÓN DEL BOTÓN EXCEL (Respaldo y Limpieza)
     */
    function confirmarRespaldo() {
        Swal.fire({
            title: '¿Generar Respaldo y Limpiar?',
            text: "Se descargará un Excel con TODO el historial. Los tickets 'Cerrados' y 'Cancelados' se limpiarán.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, respaldar y limpiar',
            cancelButtonText: 'Solo descargar Excel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'actions/respaldo_limpieza.php?download=true&clean=true';
                setTimeout(() => { table.ajax.reload(); }, 3000);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                window.location.href = 'actions/respaldo_limpieza.php?download=true&clean=false';
            }
        });
    }

    /**
     * CONFIGURACIÓN DATATABLES SERVER-SIDE (VERSIÓN 2.0 BLINDADA)
     * @author Israel Fernández Carrera
     */
    var table;
    $(document).ready(function() {
        if ($('#tablaTickets').length) {
            table = $('#tablaTickets').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "actions/obtener_tickets_datatable.php",
                    "type": "POST",
                    "data": function(d) {
                        // INTEGRAMOS TODOS TUS FILTROS AL ENVÍO DEL SERVIDOR
                        d.filterTipo   = $('#filterTipo').val();
                        d.filterFalla  = $('#filterFalla').val();
                        d.filterAccion = $('#filterAccion').val();
                        d.fechaDesde   = $('#fechaDesde').val();
                        d.fechaHasta   = $('#fechaHasta').val();
                        
                        // Captura de Switches (Boleanos enviados como 1 o 0)
                        d.soloPendientes = $('#checkSoloPendientes').is(':checked') ? 1 : 0;
                        d.soloGarantia   = $('#checkGarantia').is(':checked') ? 1 : 0;
                        d.soloDeuda      = $('#checkSoloDeuda').is(':checked') ? 1 : 0;
                        
                        // Captura de Alarma de Urgentes (Basado en la clase del botón)
                        d.soloUrgentes   = $('#btnFiltrarCriticos').hasClass('btn-dark') ? 1 : 0;
                    }
                },
                "columns": [
                    { 
                        "data": "id_ticket", // 1. #
                        "render": function(data, type, row) {
                            let alerta = row.es_urgente ? 
                                `<i class="bi bi-exclamation-triangle-fill text-danger ms-1-animate me-1" title="Urgente: ${row.diff_dias} días abierto" data-bs-toggle="tooltip"></i>` : '';
                            // El triángulo va ANTES del data (ID) y todo en color rojo negrita
                            return `<span class="fw-bold text-danger text-nowrap">${alerta}${data}</span>`;
                        }
                    },
                    { "data": "nombre_cliente" }, // 2. Cliente
                    { "data": "modelo_serie" },   // 3. Equipo / Serie
                    { "data": "tipo_llamada", "visible": false }, // 4. Oculta
                    { "data": "tipo_falla" },     // 5. Falla
                    { "data": "accion_realizada", "visible": false }, // 6. Oculta
                    { 
                        "data": "garantia_valida", // 7. Garantía
                        "render": function(data) {
                            let color = data === 'Válida' ? 'text-success' : 'text-danger';
                            return `<span class="small fw-bold ${color}">${data}</span>`;
                        }
                    },
                    { 
                        "data": "estatus_pago", // 8. Pago
                        "render": function(data) {
                            let color = data === 'Pendiente' ? 'text-danger fw-bold' : 'text-success fw-bold';
                            return `<span class="small ${color}">${data}</span>`;
                        }
                    },
                    { 
                        "data": "estatus", // 9. Estatus
                        "render": function(data) {
                            let badge = data === 'Abierto' ? 'bg-warning text-dark' : (data === 'Cerrado' ? 'bg-success' : 'bg-secondary text-white');
                            return `<span class="badge ${badge}" style="font-size: 0.65rem;">${data}</span>`;
                        }
                    },
                    { "data": "fecha_inicial" }, // 10. Inicio
                    { 
                        "data": null, // 11. Botones de Acción
                        "orderable": false,
                        "className": "text-center",
                        "render": function(data, type, row) {
                            let btns = `<button type="button" class="btn btn-outline-info border-0" onclick="abrirModalVisualizar(${row.id_ticket})" title="Ver detalles"><i class="bi bi-eye-fill"></i></button>`;
                            if (row.estatus === 'Abierto') {
                                btns += `<a href="editar_ticket.php?id_ticket=${row.id_ticket}" class="btn btn-outline-warning border-0" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        <button type="button" class="btn btn-outline-success border-0" onclick="cambiarEstatus(${row.id_ticket}, 'Cerrado')" title="Cerrar"><i class="bi bi-lock-fill"></i></button>
                                        <button type="button" class="btn btn-outline-secondary border-0" onclick="cambiarEstatus(${row.id_ticket}, 'Cancelado')" title="Cancelar"><i class="bi bi-x-circle-fill"></i></button>`;
                            }
                            return `<div class="btn-group btn-group-sm">${btns}</div>`;
                        }
                    },
                    { 
                        "data": null, // 12. Envios (Lógica 1.7 de iconos)
                        "className": "text-center",
                        "render": function(data, type, row) {
                            const iconMap = {
                                'Envio base': 'bi bi-truck',
                                'Envio técnico': 'bi bi-tools',
                                'Envio refacciones': 'bi bi-box-seam',
                                'Envio técnico y refacciones': 'bi bi-tools'
                            };
                            
                            let iconClass = iconMap[row.accion_realizada] || 'bi bi-question-circle';
                            let bgClass = 'bg-secondary text-white'; // Default: N/A o Acción desconocida
                            let suffix = '';

                            if (iconMap[row.accion_realizada]) {
                                if (!row.f_ini_acc) { 
                                    bgClass = 'bg-warning text-dark'; suffix = ' (Pendiente)'; 
                                } else if (row.f_ini_acc && !row.f_fin_acc) { 
                                    bgClass = 'bg-primary text-white'; suffix = ' (Iniciado)'; 
                                } else { 
                                    bgClass = 'bg-success text-white'; suffix = ' (Finalizado)'; 
                                }
                            }

                            let extraIcon = (row.accion_realizada === 'Envio técnico y refacciones') ? '<i class="bi bi-box-seam ms-1"></i>' : '';

                            return `<span class="badge ${bgClass} rounded-pill px-3 py-2 badge-envio" style="font-size: 0.65rem;" title="${row.accion_realizada}${suffix}">
                                    <i class="${iconClass}"></i>${extraIcon}
                                    </span>`;
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
                "order": [[0, "desc"]]
            });

            // 1. EVENTOS DE RECARGA (REDAW) AL CAMBIAR FILTROS
            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

            $('#filterTipo, #filterFalla, #filterAccion, #fechaDesde, #fechaHasta').on('change', function() {
                table.draw(); 
            });

            $('#checkSoloPendientes, #checkGarantia, #checkSoloDeuda').on('change', function() {
                table.draw();
            });

            // 2. LÓGICA DE BOTÓN URGENTES
            $('#btnFiltrarCriticos').on('click', function() {
                const btn = $(this);
                btn.toggleClass('btn-danger btn-dark');
                const activo = btn.hasClass('btn-dark');
                btn.html(activo ? '<i class="bi bi-arrow-counterclockwise me-1"></i> Quitar Filtro' : '<i class="bi bi-funnel-fill me-1"></i> Ver Urgentes');
                table.draw(); // Esto dispara el AJAX enviando soloUrgentes = 1
            });

            // 3. BLOQUEO DE CALENDARIOS (UI)
            $('#fechaDesde').on('change', function() { $('#fechaHasta').attr('min', $(this).val()); });
            $('#fechaHasta').on('change', function() { $('#fechaDesde').attr('max', $(this).val()); });

            // 4. TOOLTIPS
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
        }
    });
</script>