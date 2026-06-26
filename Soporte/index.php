<?php
/**
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Panel de Control Principal (Dashboard) con Server-side Processing.
 * Centraliza la visualización de tickets mediante un motor de filtrado remoto.
 * @project Soporte Técnico DEMEX
 * @version 2.4 (Alertas y filtros separados para control de urgencias y carga logística)
 */

require_once '../config/db.php';
$page_title = "Panel de Control - Soporte";

$total = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte")->fetchColumn();
$pendientes = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte WHERE estatus = 'Abierto'")->fetchColumn();

$sql_cobro = "SELECT SUM(d.costo_total) FROM Detalles_Costos_Tiempos d
JOIN Tickets_Soporte t ON d.id_ticket = t.id_ticket
WHERE d.estatus_pago = 'Pendiente' AND t.estatus = 'Abierto'";
$por_cobrar = $pdo->query($sql_cobro)->fetchColumn() ?: 0;

$criticos = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte WHERE estatus = 'Abierto' AND DATEDIFF(CURDATE(), fecha_inicial) >= 14")->fetchColumn();

// NUEVA CONSULTA SEPARADA: Cuenta los equipos activos asignados al área técnica en la tabla de Almacén
$sql_logistica = "SELECT COUNT(*) FROM almacen_inventario WHERE estatus IN ('DISPONIBLE PARA SOPORTE', 'EN REVISIÓN SOPORTE')";
$equipos_taller = $pdo->query($sql_logistica)->fetchColumn() ?: 0;

$modulo_actual = 'soporte';
include '../includes/header.php';
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

<?php if ($equipos_taller > 0): ?>
    <div class="alert alert-info shadow-sm border-0 border-start border-4 border-primary bg-white d-flex align-items-center justify-content-between animate__animated animate__fadeInUp" role="alert" id="alertaLogistica" style="margin-top: -5px;">
        <div>
            <i class="bi bi-boxes fs-4 me-3 text-primary"></i>
            <span class="fw-bold text-dark">Logística de Almacén:</span> Tienes <strong><?= $equipos_taller ?></strong> máquina(s) en tránsito o disponibles para revisión técnica en el taller.
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btnFiltrarLogistica">
            <i class="bi bi-boxes me-1"></i> Filtrar Equipos taller
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
                    <th>Registrado Por</th>
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

<?php include '../includes/footer.php'; ?>

<script>
    function abrirModalVisualizar(id) {
        $('#modalVisualizar').appendTo("body").modal('show');
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
                            table.ajax.reload(null, false); 
                        }
                    }
                });
            }
        });
    }

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

    function recibirEquipoTaller(almacenId) {
        Swal.fire({
            title: '¿Recibir equipo en taller?',
            text: 'Se registrará el inicio del proceso en Soporte y se notificará a Almacén.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Sí, recibir equipo',
            cancelButtonText: 'Regresar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Firmando fase...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                
                const fd = new FormData();
                fd.append('id', almacenId);
                fd.append('nuevo_estatus', 'EN REVISIÓN SOPORTE');
                fd.append('campo_fecha', 'fecha_entrega_soporte');
                fd.append('fecha_fase', new Date().toISOString().split('T')[0]);

                fetch('../Almacen/actions/actualizar_fase.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: '¡Equipo Recibido!', text: 'La traza logística se actualizó.', timer: 1500, showConfirmButton: false });
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Falla', text: data.message });
                    }
                })
                .catch(() => {
                    Swal.close();
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un colapso de red.' });
                });
            }
        });
    }

    function devolverEquipoAlmacen(almacenId) {
        Swal.fire({
            title: '¿Devolver equipo a Almacén?',
            text: 'Se confirmará que Soporte terminó los diagnósticos y regresa el equipo para su embalaje/entrega.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, enviar a Almacén',
            cancelButtonText: 'Regresar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Procesando devolución...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                
                const fd = new FormData();
                fd.append('id', almacenId);
                fd.append('nuevo_estatus', 'REINGRESO A ALMACÉN');
                fd.append('campo_fecha', 'fecha_reingreso_almacen');
                fd.append('fecha_fase', new Date().toISOString().split('T')[0]);

                fetch('../Almacen/actions/actualizar_fase.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: '¡Equipo Devuelto!', text: 'La estafeta regresó al área de Almacén.', timer: 1500, showConfirmButton: false });
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Falla', text: data.message });
                    }
                })
                .catch(() => {
                    Swal.close();
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un colapso de red.' });
                });
            }
        });
    }

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
                        d.filterTipo   = $('#filterTipo').val();
                        d.filterFalla  = $('#filterFalla').val();
                        d.filterAccion = $('#filterAccion').val();
                        d.fechaDesde   = $('#fechaDesde').val();
                        d.fechaHasta   = $('#fechaHasta').val();
                        d.soloPendientes = $('#checkSoloPendientes').is(':checked') ? 1 : 0;
                        d.soloGarantia   = $('#checkGarantia').is(':checked') ? 1 : 0;
                        d.soloDeuda      = $('#checkSoloDeuda').is(':checked') ? 1 : 0;
                        
                        d.soloUrgentes = $('#btnFiltrarCriticos').length && $('#btnFiltrarCriticos').hasClass('btn-dark') ? 1 : 0;
                        d.soloFaseSoporte = $('#btnFiltrarLogistica').length && $('#btnFiltrarLogistica').hasClass('btn-dark') ? 1 : 0;
                    }
                },
                "columns": [
                    { 
                        "data": "id_ticket",
                        "render": function(data, type, row) {
                            let alerta = row.es_urgente ? 
                                `<i class="bi bi-exclamation-triangle-fill text-danger ms-1-animate me-1" title="Urgente: ${row.diff_dias} días abierto" data-bs-toggle="tooltip"></i>` : '';
                            return `<span class="fw-bold text-danger text-nowrap">${alerta}${data}</span>`;
                        }
                    },
                    { "data": "nombre_cliente" },
                    { 
                        "data": "modelo_serie",
                        "render": function(data, type, row) {
                            let htmlLogistica = '';
                            
                            if (row.almacen_estatus === 'DISPONIBLE PARA SOPORTE') {
                                htmlLogistica = `<br><div class="mt-1 d-grid"><button type="button" class="btn btn-primary btn-xs rounded-pill fw-bold py-1 animate__animated animate__pulse animate__infinite shadow-sm" style="font-size: 10px;" onclick="recibirEquipoTaller(${row.almacen_id})"><i class="bi bi-box-arrow-in-right me-1"></i> Recibir en Taller</button></div>`;
                            } else if (row.almacen_estatus === 'EN REVISIÓN SOPORTE') {
                                htmlLogistica = `<br><span class="badge bg-info text-dark mt-1 d-block py-1 mb-1" style="font-size: 9px; font-weight:700; letter-spacing:0.3px;"><i class="bi bi-tools me-1"></i> En Diagnóstico Soporte</span>
                                                 <div class="d-grid"><button type="button" class="btn btn-success btn-xs rounded-pill fw-bold py-1 shadow-sm" style="font-size: 10px;" onclick="devolverEquipoAlmacen(${row.almacen_id})"><i class="bi bi-send-check me-1"></i> Enviar a Almacén</button></div>`;
                            } else if (row.almacen_estatus === 'SIN REVISAR' || row.almacen_estatus === 'EN REVISIÓN ALMACÉN') {
                                htmlLogistica = `<br><span class="badge bg-warning text-dark mt-1" style="font-size: 9px; font-weight:700;"><i class="bi bi-hourglass-split me-1"></i> Retenido en Almacén</span>`;
                            } else if (row.almacen_estatus === 'REINGRESO A ALMACÉN' || row.almacen_estatus === 'DISPONIBLE PARA VENTA') {
                                htmlLogistica = `<br><span class="badge bg-success text-white mt-1" style="font-size: 9px; font-weight:700;"><i class="bi bi-check-circle-fill me-1"></i> Devuelto a Almacén</span>`;
                            }

                            return `${data}${htmlLogistica}`;
                        }
                    },
                    { "data": "tipo_llamada", "visible": false },
                    { "data": "tipo_falla" },
                    { "data": "accion_realizada", "visible": false },
                    { 
                        "data": "garantia_valida",
                        "render": function(data) {
                            let color = data === 'Válida' ? 'text-success' : 'text-danger';
                            return `<span class="small fw-bold ${color}">${data}</span>`;
                        }
                    },
                    { 
                        "data": "estatus_pago",
                        "render": function(data) {
                            let color = 'text-success fw-bold';
                            if (data === 'Pendiente') { color = 'text-danger fw-bold'; } 
                            else if (data === 'NO APLICA' || data === 'N/A') { color = 'text-muted fw-normal'; }
                            return `<span class="small ${color}">${data}</span>`;
                        }
                    },
                    { 
                        "data": "estatus",
                        "render": function(data) {
                            let badge = data === 'Abierto' ? 'bg-warning text-dark' : (data === 'Cerrado' ? 'bg-success' : 'bg-secondary text-white');
                            return `<span class="badge ${badge}" style="font-size: 0.65rem;">${data}</span>`;
                        }
                    },
                    { "data": "fecha_inicial" },
                    { 
                        "data": null,
                        "render": function(data, type, row) {
                            let html = `<span class="badge bg-dark bg-opacity-10 text-dark border border-secondary border-opacity-25" style="font-size: 0.7rem; font-weight: 500; padding: 0.35rem 0.6rem; border-radius: 6px;"><i class="bi bi-person-fill text-muted me-1"></i>${row.creador}</span>`;
                            if (row.editor && row.editor !== row.creador) {
                                html += `<br><small class="text-muted d-block" style="font-size: 0.6rem; margin-top: 4px;"><i class="bi bi-pencil-square text-secondary me-1"></i>Edición: ${row.editor}</small>`;
                            }
                            return html;
                        }
                    },
                    {
                        "data": null,
                        "orderable": false,
                        "className": "text-center",
                        "render": function(data, type, row) {
                            let btns = `<button type="button" class="btn btn-outline-info border-0" onclick="abrirModalVisualizar(${row.id_ticket})" title="Ver detalles"><i class="bi bi-eye-fill"></i></button>`;
                            if (row.estatus === 'Abierto') {
                                if (row.almacen_estatus === 'SIN REVISAR' || row.almacen_estatus === 'EN REVISIÓN ALMACÉN') {
                                    btns += `<button type="button" class="btn btn-outline-secondary border-0 opacity-50" title="Retenido por Almacén" onclick="Swal.fire({icon:'warning', title:'Equipo Retenido', text:'Almacén Técnico aún no libera ni abre la caja de esta maquinaria.'})"><i class="bi bi-slash-circle"></i></button>`;
                                } else {
                                    btns += `<a href="editar_ticket.php?id_ticket=${row.id_ticket}" class="btn btn-outline-warning border-0" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                             <button type="button" class="btn btn-outline-success border-0" onclick="cambiarEstatus(${row.id_ticket}, 'Cerrado')" title="Cerrar"><i class="bi bi-lock-fill"></i></button>
                                             <button type="button" class="btn btn-outline-secondary border-0" onclick="cambiarEstatus(${row.id_ticket}, 'Cancelado')" title="Cancelar"><i class="bi bi-x-circle-fill"></i></button>`;
                                }
                            }
                            return `<div class="btn-group btn-group-sm">${btns}</div>`;
                        }
                    },
                    { 
                        "data": null,
                        "className": "text-center",
                        "render": function(data, type, row) {
                            const iconMap = { 'Envio base': 'bi bi-truck', 'Envio técnico': 'bi bi-tools', 'Envio refacciones': 'bi bi-box-seam', 'Envio técnico y refacciones': 'bi bi-tools' };
                            let iconClass = iconMap[row.accion_realizada] || 'bi bi-question-circle';
                            let bgClass = 'bg-secondary text-white'; let suffix = '';
                            if (iconMap[row.accion_realizada]) {
                                if (!row.f_ini_acc) { bgClass = 'bg-warning text-dark'; suffix = ' (Pendiente)'; } 
                                else if (row.f_ini_acc && !row.f_fin_acc) { bgClass = 'bg-primary text-white'; suffix = ' (Iniciado)'; } 
                                else { bgClass = 'bg-success text-white'; suffix = ' (Finalizado)'; }
                            }
                            let extraIcon = (row.accion_realizada === 'Envio técnico y refacciones') ? '<i class="bi bi-box-seam ms-1"></i>' : '';
                            return `<span class="badge ${bgClass} rounded-pill px-3 py-2 badge-envio" style="font-size: 0.65rem;" title="${row.accion_realizada}${suffix}"><i class="${iconClass}"></i>${extraIcon}</span>`;
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
                "order": [[0, "desc"]],
                "createdRow": function(row, data, dataIndex) {
                    if (data.estatus_pago === 'NO APLICA' || data.estatus_pago === 'N/A') {
                        $(row).addClass('bg-light text-muted opacity-75 text-decoration-none');
                        $(row).css('background-color', '#f8f9fa');
                    }
                }
            });

            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
            $('#filterTipo, #filterFalla, #filterAccion, #fechaDesde, #fechaHasta').on('change', function() { table.draw(); });
            $('#checkSoloPendientes, #checkGarantia, #checkSoloDeuda').on('change', function() { table.draw(); });

            $('#btnFiltrarCriticos').on('click', function() {
                const btn = $(this);
                btn.toggleClass('btn-danger btn-dark');
                const activo = btn.hasClass('btn-dark');
                btn.html(activo ? '<i class="bi bi-arrow-counterclockwise me-1"></i> Quitar Filtro' : '<i class="bi bi-funnel-fill me-1"></i> Ver Urgentes');
                table.draw();
            });

            $('#btnFiltrarLogistica').on('click', function() {
                const btn = $(this);
                btn.toggleClass('btn-outline-primary btn-dark');
                const activo = btn.hasClass('btn-dark');
                btn.html(activo ? '<i class="bi bi-arrow-counterclockwise me-1"></i> Quitar Filtro Taller' : '<i class="bi bi-boxes me-1"></i> Filtrar Equipos taller');
                table.draw();
            });

            $('#fechaDesde').on('change', function() { $('#fechaHasta').attr('min', $(this).val()); });
            $('#fechaHasta').on('change', function() { $('#fechaDesde').attr('max', $(this).val()); });

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
        }
    });
</script>