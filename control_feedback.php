<?php
/**
 * ARCHIVO: control_feedback.php
 * DESCRIPCIÓN: Panel Administrativo Globalizado para control de incidencias en la raíz.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.6 - Simetría Visual Perfecta en Filtros y AJAX Sincronizado
 */

require_once 'config/db.php'; 
$page_title = "Control de Calidad Global";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['roles']) || !in_array('Administrador', $_SESSION['roles'])) {
    header("Location: login.php?error=no_autorizado");
    exit();
}

$modulo_actual = 'global'; 
include 'includes/header.php'; 
?>

<div class="card border-0 shadow-lg animate__animated animate__fadeIn mb-4" style="border-radius: 24px; overflow: hidden;">
    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex align-items-center justify-content-between">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-shield-check text-danger me-2"></i> Bitácora de Incidencias y Feedback Global
            </h4>
            <p class="text-muted small mb-0">Monitoreo transaccional de reportes visuales, errores y optimizaciones del staff.</p>
        </div>
        <div class="shadow-sm d-flex align-items-center justify-content-center" 
             style="width: 50px; height: 50px; background-color: #fff5f5; border-radius: 16px;">
            <i class="bi bi-bug-fill text-danger fs-4"></i>
        </div>
    </div>

    <div class="card-body px-4 pb-4">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                    <span class="input-group-text border-0 bg-transparent"><i class="bi bi-search text-danger"></i></span>
                    <input type="text" id="buscarFeedback" class="form-control bg-transparent border-0 small" placeholder="Filtrar por staff o descripción...">
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                    <span class="input-group-text border-0 bg-transparent"><i class="bi bi-funnel-fill text-danger"></i></span>
                    <select id="filtroEstatus" class="form-control bg-transparent border-0 small fw-bold text-muted shadow-none" style="cursor: pointer;">
                        <option value="">Todos los Estados</option>
                        <option value="Pendiente">Solo Pendientes</option>
                        <option value="Resuelto">Solo Resueltos</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaControlFeedback" class="table table-hover align-middle w-100" style="border-radius: 15px; overflow: hidden;">
                <thead class="table-light text-uppercase text-muted small fw-bold">
                    <tr>
                        <th class="ps-3" style="width: 8%">ID</th>
                        <th style="width: 18%">Usuario Reporta</th>
                        <th style="width: 12%">Categoría</th>
                        <th style="width: 22%">Detalle de Incidencia</th>
                        <th style="width: 12%">Pantalla Origen</th>
                        <th style="width: 12%">Fecha Registro</th>
                        <th style="width: 8%">Estatus</th>
                        <th class="text-center pe-3" style="width: 12%">Acción</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLeerDetalleFeedback" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 bg-light py-3 px-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-text text-danger me-2"></i>Descripción del Reporte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 11px;">Operador Informa:</small>
                    <span id="txtFeedbackUsuario" class="fw-semibold text-dark small"></span>
                </div>
                <hr class="opacity-25">
                <div>
                    <small class="text-muted fw-bold text-uppercase d-block mb-2" style="font-size: 11px;">Detalle Técnico:</small>
                    <p id="txtFeedbackDetalle" class="text-secondary small lh-base p-3 bg-light" style="border-radius: 12px; white-space: pre-line;"></p>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light px-4 py-2">
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 

<script>
$(document).ready(function() {
    var tableFeedback = $('#tablaControlFeedback').DataTable({
        "processing": true,
        "ajax": {
            "url": "actions/obtener_feedback_datatable.php",
            "type": "POST",
            "data": function(d) {
                d.estatus = $('#filtroEstatus').val();
            }
        },
        "columns": [
            { "data": "id", "className": "ps-3 text-secondary" },
            { "data": "usuario" },
            { "data": "tipo" },
            { "data": "descripcion" },
            { "data": "pantalla" },
            { "data": "fecha" },
            { "data": "estatus" },
            { "data": "acciones", "className": "text-center pe-3", "orderable": false }
        ],
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "dom": 'rtip',
        "pageLength": 10,
        "responsive": true,
        "order": [[0, "desc"]]
    });

    // Filtro por entrada de texto (Buscador)
    $('#buscarFeedback').on('keyup', function() {
        tableFeedback.search(this.value).draw();
    });

    // Recargar la tabla asíncronamente al cambiar el select
    $('#filtroEstatus').on('change', function() {
        tableFeedback.ajax.reload();
    });

    window.verDetalleFeedback = function(textoDescripcion, usuarioNombre) {
        $('#txtFeedbackUsuario').html('<i class="bi bi-person-fill text-muted me-1"></i>' + usuarioNombre);
        $('#txtFeedbackDetalle').text(textoDescripcion);
        $('#modalLeerDetalleFeedback').appendTo('body').modal('show');
    };

    window.cambiarEstatusFeedback = function(id, nuevoEstatus) {
        let tituloStr = nuevoEstatus === 'Resuelto' ? '¿Marcar como corregido?' : '¿Reabrir incidencia?';
        let iconStr = nuevoEstatus === 'Resuelto' ? 'success' : 'info';
        
        Swal.fire({
            title: tituloStr,
            text: `La incidencia #${id} cambiará su estado a ${nuevoEstatus.toLowerCase()}.`,
            icon: iconStr,
            showCancelButton: true,
            confirmButtonColor: nuevoEstatus === 'Resuelto' ? '#198754' : '#6c757d',
            cancelButtonColor: '#adb5bd',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'actions/actualizar_estatus_feedback.php',
                    method: 'POST',
                    data: { id_feedback: id, estatus: nuevoEstatus },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Actualizado!',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            tableFeedback.ajax.reload(null, false); 
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo actualizar.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error Técnico', 'Error de red o comunicación con el servidor.', 'error');
                    }
                });
            }
        });
    };
});
</script>