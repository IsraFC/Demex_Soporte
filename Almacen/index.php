<?php
/**
 * ARCHIVO: Almacen/index.php
 * DESCRIPCIÓN: Panel de Control de Almacén con Despliegue de Filas Hijas (Child Rows).
 * Agrupa por Lote y despliega subtabla resumida por Modelo y Estatus sin salir de la vista.
 * @project Almacén Técnico DEMEX
 * @version 6.5 - Filas Hijas Agrupadas por Modelo
 * @author Israel Fernández Carrera
 */

require_once '../config/db.php';
$page_title = "Panel de Control - Almacén";

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$id_usuario_actual = intval($_SESSION['id_usuario'] ?? 0);

// KPIs Generales
$total_lotes   = $pdo->query("SELECT COUNT(*) FROM almacen_lotes")->fetchColumn();
$total_equipos = $pdo->query("SELECT COUNT(*) FROM almacen_inventario")->fetchColumn();
$sin_revisar   = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'SIN REVISAR'")->fetchColumn();
$disponibles   = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'DISPONIBLE PARA VENTA'")->fetchColumn();

include '../includes/header.php';
?>

<style>
    /* Estilos para el desplegable Child Rows */
    td.details-control {
        cursor: pointer;
        text-align: center;
    }
    .subtabla-lote {
        background-color: #f8f9fa !important;
        border-radius: 12px;
        padding: 15px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }
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
    }
    .msg-sent { background-color: #fff5f5; border: 1px solid rgba(220, 53, 69, 0.1); }
    .msg-received { background-color: #ffffff; color: #212529; }
    .chat-avatar { width: 28px; height: 28px; object-fit: cover; border-radius: 50%; }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-boxes me-2"></i>Gestión de Lotes</h1>
        <p class="text-muted small mb-2">Control de embarques de importación y desglose de maquinaria por estatus.</p>
        <a href="registro_lote.php" class="btn btn-danger btn-sm rounded-pill px-4 shadow-sm fw-bold" style="background-color: #dc3545; font-size: 12px;">
            <i class="bi bi-plus-lg me-1"></i> NUEVO LOTE
        </a>
    </div>
    <div class="col-md-7 text-md-end mt-3 mt-md-0">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 100px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= intval($total_lotes) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">LOTES</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-secondary border-4 text-center" style="min-width: 100px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= intval($total_equipos) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">TOTAL UNIDADES</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 100px;">
                <span class="d-block fw-bold fs-5 text-warning"><?= intval($sin_revisar) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">SIN REVISAR</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 100px;">
                <span class="d-block fw-bold fs-5 text-success"><?= intval($disponibles) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">DISPONIBLES VENTA</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaLotes" class="table table-hover align-middle w-100">
            <thead class="table-light text-uppercase small fw-bold" style="font-size: 11px;">
                <tr>
                    <th width="30" class="text-center"></th>
                    <th>Contenedor / Lote</th>
                    <th>Tipo</th>
                    <th>Fecha Arribo</th>
                    <th class="text-center">Total Unidades</th>
                    <th>Resumen de Estatus</th>
                    <th class="text-center">Notas</th>
                </tr>
            </thead>
            <tbody class="small fw-semibold text-dark"></tbody>
        </table>
    </div>
</div>

<!-- Modal para Asignar Cliente y Póliza (Al vender) -->
<div class="modal fade" id="modalAsignarCliente" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-success text-white border-0 py-3 shadow-sm">
                <h5 class="modal-title fw-bold text-uppercase mb-0" style="font-size: 0.95rem;"><i class="bi bi-person-plus-fill me-2"></i> Asignar Cliente y Activar Garantía</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="contenidoAsignacion">
                <div class="text-center p-5"><div class="spinner-border text-success" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Cambiar Fase -->
<div class="modal fade" id="modalActualizarFase" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-danger text-white border-0 py-3 shadow-sm">
                <h5 class="modal-title fw-bold text-uppercase mb-0" style="font-size: 0.95rem;"><i class="bi bi-calendar-check-fill me-2"></i> Cambiar Fase Logística</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="contenidoFase">
                <div class="text-center p-5"><div class="spinner-border text-danger" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Chat Flotante para Lotes -->
<div id="recuadroFlotanteChat" class="widget-chat-flotante animate__animated animate__fadeInUp">
    <div class="widget-chat-header shadow-sm">
        <div>
            <h6 class="fw-bold mb-0" style="font-size: 0.85rem;"><i class="bi bi-chat-left-text-fill me-1.5"></i> Chat del Lote</h6>
            <small id="chatSubtitulo" class="text-white-50 fw-bold" style="font-size: 10px;"></small>
        </div>
        <button type="button" class="btn-close btn-close-white" onclick="cerrarChatFlotante()"></button>
    </div>
    <div id="cuerpoChat" class="widget-chat-body"></div>
    <div class="p-2 bg-white border-top shadow-sm">
        <form id="formEnviarComentario" autocomplete="off">
            <input type="hidden" id="chatIdLote">
            <div class="input-group border rounded-pill px-2.5 py-0.5 bg-light shadow-sm">
                <input type="text" id="inputMensaje" class="form-control bg-transparent border-0 small py-1" placeholder="Comentario para este lote..." required>
                <button class="btn bg-transparent border-0 text-danger p-0 px-2" type="submit"><i class="bi bi-send-fill fs-6"></i></button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    var table;
    var fuenteEventosChat = null;
    var ultimoIdComentario = 0;
    var usuarioActualId = <?= $id_usuario_actual ?>;

    $(document).ready(function() {
        $('#recuadroFlotanteChat').appendTo("body");

        table = $('#tablaLotes').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "actions/obtener_lotes_datatable.php",
                "type": "POST"
            },
            "columns": [
                {
                    "className": 'details-control',
                    "orderable": false,
                    "data": null,
                    "defaultContent": '<button class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-plus-circle-fill fs-5"></i></button>'
                },
                { "data": "contenedor", "className": "fw-bold text-danger fs-6" },
                { "data": "tipo", "render": function(d) { return `<span class="badge bg-light text-dark border">${d}</span>`; } },
                { "data": "fecha_ingreso" },
                { "data": "total_unidades", "className": "text-center fw-bold fs-6" },
                { "data": "desglose_estatus" },
                {
                    "data": null,
                    "orderable": false,
                    "className": "text-center",
                    "render": function(data, type, row) {
                        return `<button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm fw-bold" onclick="abrirChatFlotante(${row.id_lote}, '${row.contenedor}')" style="font-size: 11px;">
                                    <i class="bi bi-chat-dots-fill me-1"></i> Notas
                                </button>`;
                    }
                }
            ],
            "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            "dom": 'rtip',
            "pageLength": 10,
            "order": [[3, "desc"]]
        });

        // EVENTO DESPLEGABLE CHILD ROWS (Al dar clic en el botón +)
        $('#tablaLotes tbody').on('click', 'td.details-control', function () {
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var btn = $(this).find('button i');

            if (row.child.isShown()) {
                // Cerrar subtabla
                row.child.hide();
                tr.removeClass('shown');
                btn.removeClass('bi-dash-circle-fill').addClass('bi-plus-circle-fill');
            } else {
                // Abrir subtabla con la consulta agrupada
                btn.removeClass('bi-plus-circle-fill').addClass('bi-dash-circle-fill');
                row.child('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-danger" role="status"></div> Cargando desglose...</div>').show();
                tr.addClass('shown');

                $.ajax({
                    url: 'actions/obtener_desglose_lote.php',
                    method: 'GET',
                    data: { id_lote: row.data().id_lote },
                    success: function (html) {
                        row.child(html).show();
                    }
                });
            }
        });

        $('#formEnviarComentario').on('submit', function(e) {
            e.preventDefault();
            const txt = $('#inputMensaje').val().trim();
            if(!txt) return;

            $.ajax({
                url: 'actions/guardar_comentario.php',
                method: 'POST',
                data: { id_lote: $('#chatIdLote').val(), comentario: txt },
                dataType: 'json',
                success: function(res) {
                    if(res.success) { $('#inputMensaje').val(''); }
                }
            });
        });
    });

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

    function abrirChatFlotante(id_lote, contenedor) {
        if (fuenteEventosChat) { fuenteEventosChat.close(); fuenteEventosChat = null; }

        $('#chatIdLote').val(id_lote);
        $('#chatSubtitulo').text('Contenedor: ' + contenedor);
        $('#cuerpoChat').html('<div class="text-center p-4"><div class="spinner-border spinner-border-sm text-danger" role="status"></div></div>');
        ultimoIdComentario = 0;

        $('#recuadroFlotanteChat').show();

        $.ajax({
            url: 'actions/obtener_historial_comentarios.php',
            method: 'GET',
            data: { id_lote: id_lote },
            dataType: 'json',
            success: function(res) {
                $('#cuerpoChat').html('');
                if (res.success && res.comentarios.length > 0) {
                    res.comentarios.forEach(function(msg) {
                        $('#cuerpoChat').append(renderizarGloboMensaje(msg));
                    });
                    ultimoIdComentario = res.ultimo_id;
                } else {
                    $('#cuerpoChat').html('<div class="text-center text-muted small p-3" id="msgSinNotas">Sin notas en este lote.</div>');
                }

                $('#cuerpoChat').animate({ scrollTop: $('#cuerpoChat')[0].scrollHeight }, 10);
                fuenteEventosChat = new EventSource(`actions/stream_comentarios.php?id_lote=${id_lote}&last_id=${ultimoIdComentario}`);

                fuenteEventosChat.onmessage = function(event) {
                    const msg = JSON.parse(event.data);
                    if($('#msgSinNotas').length) { $('#msgSinNotas').remove(); }
                    $('#cuerpoChat').append(renderizarGloboMensaje(msg));
                    $('#cuerpoChat').animate({ scrollTop: $('#cuerpoChat')[0].scrollHeight }, 150);
                    ultimoIdComentario = msg.id_comentario;
                };
            }
        });
    }

    function cerrarChatFlotante() {
        $('#recuadroFlotanteChat').hide();
        if (fuenteEventosChat) { fuenteEventosChat.close(); fuenteEventosChat = null; }
    }
</script>