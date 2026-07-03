<?php
/**
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Panel de Control Principal de Almacén con Server-side Processing y Chat Flotante Fijo.
 * @project Almacén Técnico DEMEX
 * @version 5.7 - Adaptado para Carga de Fotos Binarias LONGBLOB (Base64)
 */

require_once '../config/db.php';
$page_title = "Panel de Control - Almacén";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_usuario_actual = intval($_SESSION['id_usuario'] ?? 0);

$total_equipos  = $pdo->query("SELECT COUNT(*) FROM almacen_inventario")->fetchColumn();
$sin_revisar    = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'SIN REVISAR'")->fetchColumn();
$kpi_en_almacen = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN ALMACÉN'")->fetchColumn();
$kpi_en_soporte = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN SOPORTE'")->fetchColumn();

include '../includes/header.php';
?>

<style>
    .widget-chat-flotante {
        position: fixed !important;
        bottom: 25px !important;
        right: 25px !important;
        width: 360px !important;
        height: 500px !important;
        background-color: #ffffff !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2) !important;
        z-index: 9999 !important;
        display: none; 
        border: 1px solid rgba(0,0,0,0.08) !important;
        overflow: hidden !important;
    }
    .widget-chat-header {
        background-color: #dc3545;
        color: #ffffff;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .widget-chat-body {
        height: calc(100% - 115px) !important;
        overflow-y: auto !important;
        background-color: #f8f9fa !important;
        padding: 12px !important;
    }
    .msg-bubble {
        max-width: 80%;
        padding: 8px 12px;
        border-radius: 14px;
        margin-bottom: 8px;
        font-size: 12.5px;
        line-height: 1.4;
        position: relative;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .msg-received {
        background-color: #ffffff;
        color: #212529;
        border-top-left-radius: 0px;
    }
    .msg-sent {
        background-color: #fff5f5;
        color: #212529;
        border-top-right-radius: 0px;
        border: 1px solid rgba(220, 53, 69, 0.1);
    }
    .chat-avatar {
        width: 28px;
        height: 28px;
        object-fit: cover;
        border-radius: 50%;
    }
</style>

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
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text border-0 bg-transparent"><i class="bi bi-funnel-fill text-danger"></i></span>
                <select id="filterEstatus" class="form-control bg-transparent border-0 small fw-bold text-muted shadow-none" style="cursor: pointer; font-size: 14px;">
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
        </div>
        <div class="col-md-2">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text border-0 bg-transparent"><i class="bi bi-tag text-danger"></i></span>
                <select id="filterTipo" class="form-control bg-transparent border-0 small fw-bold text-muted shadow-none" style="cursor: pointer; font-size: 14px;">
                    <option value="">Todos los Tipos</option>
                    <option value="ORIGINAL">ORIGINAL</option>
                    <option value="DEMO">DEMO</option>
                </select>
            </div>
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
                    <th class="text-center">Espera Caja</th>
                    <th class="text-center">Ajustes Alm.</th>
                    <th class="text-center">En Laboratorio</th>
                    <th class="text-center">Total Gral</th>
                    <th class="text-center">Comentarios</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody class="small fw-semibold text-dark"></tbody>
        </table>
    </div>
</div>

<div id="recuadroFlotanteChat" class="widget-chat-flotante animate__animated animate__fadeInUp">
    <div class="widget-chat-header shadow-sm">
        <div>
            <h6 class="fw-bold mb-0" style="font-size: 0.85rem;"><i class="bi bi-chat-left-text-fill me-1.5"></i> Notas de Lote</h6>
            <small id="chatSubtitulo" class="text-white-50 fw-bold" style="font-size: 10px;"></small>
        </div>
        <button type="button" class="btn-close btn-close-white" onclick="cerrarChatFlotante()"></button>
    </div>
    <div id="cuerpoChat" class="widget-chat-body"></div>
    <div class="p-2 bg-white border-top shadow-sm">
        <form id="formEnviarComentario" autocomplete="off">
            <input type="hidden" id="chatIdInventario">
            <div class="input-group border rounded-pill px-2.5 py-0.5 bg-light shadow-sm">
                <input type="text" id="inputMensaje" class="form-control bg-transparent border-0 small py-1" placeholder="Escribe un comentario..." required>
                <button class="btn bg-transparent border-0 text-danger p-0 px-2" type="submit"><i class="bi bi-send-fill fs-6"></i></button>
            </div>
        </form>
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
    var fuenteEventosChat = null; 
    var ultimoIdComentario = 0;
    var usuarioActualId = <?= $id_usuario_actual ?>;

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

    // CORRECCIÓN: Mapea directamente el Base64 generado en la propiedad 'foto_src'
    function renderizarGloboMensaje(msg) {
        const esPropio = (parseInt(msg.id_usuario, 10) === usuarioActualId);
        const claseMensaje = esPropio ? 'msg-sent ms-auto' : 'msg-received';
        
        return `
            <div class="d-flex align-items-start gap-1.5 mb-2.5 ${esPropio ? 'flex-row-reverse' : ''}">
                <img src="${msg.foto_src}" class="chat-avatar shadow-sm border" onerror="this.src='../../img/default-avatar.png'">
                <div class="msg-bubble ${claseMensaje}">
                    <small class="d-block fw-bold ${esPropio ? 'text-danger' : 'text-primary'}" style="font-size: 10px;">${msg.nombre_completo}</small>
                    <span class="d-block mt-0.5" style="word-break: break-word;">${msg.comentario}</span>
                    <small class="d-block text-end text-muted mt-0.5" style="font-size: 8px; font-weight: 500;">${msg.fecha_formateada}</small>
                </div>
            </div>`;
    }

    $(document).ready(function() {
        $('#recuadroFlotanteChat').appendTo("body");

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
                            return `<button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm fw-bold" onclick="abrirChatFlotante(${row.id}, '${row.no_serie}')" style="font-size: 11px;">
                                        <i class="bi bi-chat-dots-fill me-1"></i> Notas
                                    </button>`;
                        }
                    },
                    {
                        "data": null,
                        "orderable": false,
                        "className": "text-center",
                        "render": function(data, type, row) {
                            if (row.estatus === 'DISPONIBLE PARA SOPORTE' || row.estatus === 'EN REVISIÓN SOPORTE') {
                                return `<button type="button" class="btn btn-outline-secondary border-0 opacity-50" onclick="Swal.fire({icon:'warning', title:'Fase Bloqueada', text:'El equipo físico está bajo el resguardo y diagnóstico del laboratorio de Soporte.'})">
                                            <i class="bi bi-lock-fill fs-5 text-muted"></i>
                                        </button>`;
                            }
                            if (row.estatus === 'PAGADA / POR ENTREGAR' || row.estatus === 'CAMBIO') {
                                return `<button type="button" class="btn btn-success btn-xs rounded-pill px-2 fw-bold" onclick="abrirModalAsignacion(${row.id})" style="font-size: 11px;">
                                            <i class="bi bi-person-plus-fill me-1"></i> Entregar
                                        </button>`;
                            }
                            if (row.estatus === 'ENTREGADA') {
                                return `<button type="button" class="btn btn-outline-success border-0 opacity-75" disabled><i class="bi bi-check-all fs-5"></i></button>`;
                            }
                            return `<button type="button" class="btn btn-outline-danger border-0" onclick="abrirModalFase(${row.id})"><i class="bi bi-arrow-right-circle-fill fs-5"></i></button>`;
                        }
                    }
                ],
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "dom": 'rtip',
                "pageLength": 13,
                "order": [[5, "desc"]]
            });

            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
            $('#filterEstatus, #filterTipo, #fechaDesde, #fechaHasta').on('change', function() { table.draw(); });
        }

        $('#formEnviarComentario').on('submit', function(e) {
            e.preventDefault();
            const txt = $('#inputMensaje').val().trim();
            if(!txt) return;

            $.ajax({
                url: 'actions/guardar_comentario.php',
                method: 'POST',
                data: {
                    id_inventario: $('#chatIdInventario').val(),
                    comentario: txt
                },
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        $('#inputMensaje').val('');
                    }
                }
            });
        });
    });

    function abrirChatFlotante(id, serie) {
        if (fuenteEventosChat) { fuenteEventosChat.close(); fuenteEventosChat = null; }

        $('#chatIdInventario').val(id);
        $('#chatSubtitulo').text('Serie: ' + serie);
        $('#cuerpoChat').html('<div class="text-center p-4"><div class="spinner-border spinner-border-sm text-danger" role="status"></div></div>');
        ultimoIdComentario = 0;

        $('#recuadroFlotanteChat').show();

        // PASO 1: Descargar el historial síncronamente desde las acciones locales
        $.ajax({
            url: 'actions/obtener_historial_comentarios.php',
            method: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                $('#cuerpoChat').html('');
                
                if (res.success && res.comentarios.length > 0) {
                    res.comentarios.forEach(function(msg) {
                        $('#cuerpoChat').append(renderizarGloboMensaje(msg));
                    });
                    ultimoIdComentario = res.ultimo_id;
                } else if (!res.success) {
                    // Muestra el mensaje de error técnico exacto capturado en el catch de PHP si algo falla
                    $('#cuerpoChat').html('<div class="text-center text-muted small p-3">Error al cargar notas: ' + (res.error || '') + '</div>');
                } else {
                    $('#cuerpoChat').html('<div class="text-center text-muted small p-3" id="msgSinNotas">Sin notas en este lote.</div>');
                }

                $('#cuerpoChat').animate({ scrollTop: $('#cuerpoChat')[0].scrollHeight }, 10);

                // PASO 2: Abrir el stream SSE apuntando al último ID conocido
                fuenteEventosChat = new EventSource(`actions/stream_comentarios.php?id=${id}&last_id=${ultimoIdComentario}`);

                fuenteEventosChat.onmessage = function(event) {
                    const msg = JSON.parse(event.data);
                    
                    if($('#msgSinNotas').length) { $('#msgSinNotas').remove(); }

                    $('#cuerpoChat').append(renderizarGloboMensaje(msg));
                    $('#cuerpoChat').animate({ scrollTop: $('#cuerpoChat')[0].scrollHeight }, 150);
                    
                    ultimoIdComentario = msg.id_comentario;
                };
            },
            error: function() {
                $('#cuerpoChat').html('<div class="text-center text-muted small p-3">Error de red o comunicación.</div>');
            }
        });
    }

    function cerrarChatFlotante() {
        $('#recuadroFlotanteChat').hide();
        if (fuenteEventosChat) {
            fuenteEventosChat.close();
            fuenteEventosChat = null;
        }
    }

    function abrirModalFase(id) {
        var idLimpio = parseInt(id, 10);
        if (isNaN(idLimpio) || idLimpio <= 0) return;
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
        if (isNaN(idLimpio) || idLimpio <= 0) return;
        $('#modalAsignarCliente').appendTo("body").modal('show');
        $('#contenidoAsignacion').html('<div class="text-center p-5"><div class="spinner-border text-success" role="status"></div></div>');
        $.ajax({
            url: 'actions/abrir_modal_entrega.php?id=' + idLimpio,
            method: 'GET',
            success: function(html) { $('#contenidoAsignacion').html(html); }
        });
    }
</script>