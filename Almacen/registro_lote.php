<?php
/**
 * ARCHIVO: Almacen/registro_lote.php
 * DESCRIPCIÓN: Interfaz para el registro individual de maquinaria en Almacén.
 * Incluye un catálogo modal asíncrono para buscar y seleccionar números de serie cómodamente.
 * @project Almacén Técnico DEMEX
 * @version 4.2 - Mapeo Asíncrono Amarrado a ID de Ticket con Visualización en Modal
 */
require_once '../config/db.php';
$page_title = "Registrar Entrada de Equipo";

include '../includes/header.php';
?>

<style>
#modalCatalogoSeries .dataTables_filter input {
    background-color: transparent !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 50rem !important;
    padding: 0.25rem 1rem !important;
    box-shadow: none !important;
    outline: none !important;
}
</style>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="fw-bold text-danger mb-0 text-uppercase">Registrar Ingreso de Equipo</h1>
        <p class="text-muted">Introduce o selecciona el número de serie para validar y confirmar los datos del cliente y garantía.</p>
    </div>
</div>

<form action="actions/procesar_lote.php" method="POST" id="formRegistroLote">
    <input type="hidden" name="id_ticket" id="id_ticket_input">

    <div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger mb-4">
        <div class="row g-4">
            
            <div class="col-md-5">
                <h5 class="fw-bold mb-3"><i class="bi bi-box-seam-fill me-2 text-danger"></i>Datos de Entrada</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Número de Serie</label>
                    <div class="input-group shadow-sm rounded-pill overflow-hidden bg-light border px-2 py-1">
                        <input type="text" name="no_serie" id="no_serie_input" class="form-control border-0 bg-transparent fw-bold text-uppercase text-danger small" placeholder="Escriba o busque un numero de serie..." required autocomplete="off">
                        <button class="btn btn-outline-danger border-0 rounded-pill px-3 py-1 small fw-bold d-flex align-items-center gap-1" type="button" data-bs-toggle="modal" data-bs-target="#modalCatalogoSeries" style="font-size: 12px;">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                    
                    <div id="status_busqueda" class="mt-2 p-2 rounded small fw-bold" style="display:none; background-color: #f8f9fa; border-left: 4px solid #dee2e6;">
                        <span id="txt_status_busqueda">Esperando serie...</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Identificador del Contenedor / Lote</label>
                    <input type="text" name="contenedor" id="contenedor" class="form-control border-0 bg-light shadow-sm fw-bold text-uppercase text-dark" placeholder="Identificador lote..." required autocomplete="off">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Tipo de Stock</label>
                    <select name="tipo" id="tipo" class="form-select border-0 bg-light shadow-sm fw-bold text-dark" required>
                        <option value="ORIGINAL" selected>ORIGINAL</option>
                        <option value="DEMO">DEMO</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control border-0 bg-light shadow-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="col-md-7 border-start">
                <h5 class="fw-bold mb-3"><i class="bi bi-person-check-fill me-2 text-danger"></i>Datos de Confirmación (Garantía y Cliente)</h5>
                
                <div class="alert alert-light border-0 small text-secondary mb-3 p-3 d-flex align-items-start gap-2" style="border-radius: 12px; background-color: #f8f9fa;">
                    <i class="bi bi-info-circle-fill text-danger mt-0.5 fs-6"></i>
                    <span>Los campos inferiores se llenarán de forma automática al detectar una serie válida en el sistema. Verifícalos antes de guardar.</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Modelo de la Máquina</label>
                        <input type="text" id="confirm_modelo" class="form-control border-0 bg-light shadow-sm fw-bold text-dark text-uppercase" placeholder="---" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Vigencia Garantía</label>
                        <input type="text" id="confirm_garantia" class="form-control border-0 bg-light shadow-sm fw-bold text-dark" placeholder="---" readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Cliente Asignado</label>
                        <input type="text" id="confirm_cliente" class="form-control border-0 bg-light shadow-sm fw-bold text-dark" placeholder="---" readonly>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Teléfono de Contacto</label>
                        <input type="text" id="confirm_telefono" class="form-control border-0 bg-light shadow-sm text-dark" placeholder="---" readonly>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted">Ubicación / Dirección</label>
                        <textarea id="confirm_ubicacion" class="form-control border-0 bg-light shadow-sm text-dark small" rows="1" style="resize:none;" placeholder="---" readonly></textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="text-center mt-4 d-flex justify-content-center gap-3">
        <a href="index.php" class="btn btn-light border px-5 rounded-pill fw-bold text-dark">Cancelar</a>
        <button type="submit" id="btnGuardarLote" class="btn btn-success px-5 rounded-pill fw-bold shadow" disabled>
            <span id="btnText">Finalizar Entrada</span> <i class="bi bi-check-circle ms-1"></i>
        </button>
    </div>
</form>

<div class="modal fade" id="modalCatalogoSeries" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 shadow-sm">
                <h5 class="modal-title fw-bold text-uppercase mb-0" style="font-size: 0.9rem;"><i class="bi bi-journal-bookmark-fill me-2 text-danger"></i> Catálogo de Maquinaria Registrada</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Utiliza el cuadro de búsqueda para filtrar de forma inmediata por nombre del cliente, modelo o serie.</p>
                <div class="table-responsive">
                    <table id="tablaModalSeries" class="table table-hover align-middle w-100" style="font-size: 13px;">
                        <thead class="table-light text-uppercase fw-bold small" style="font-size: 11px;">
                            <tr>
                                <th>Ticket ID</th>
                                <th>Cliente Asignado</th>
                                <th>Nº de Serie</th>
                                <th>Modelo</th>
                                <th class="text-center">Selección</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-dark">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    let serieValida = false;
    var typingTimer;
    var tableModal;

    // Inicialización del DataTable interno del Catálogo Modal
    $('#modalCatalogoSeries').appendTo('body').on('shown.bs.modal', function () {
        if (!$.fn.DataTable.isDataTable('#tablaModalSeries')) {
            tableModal = $('#tablaModalSeries').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "actions/obtener_series_modal.php",
                    "type": "POST"
                },
                "columns": [
                    // CORRECCIÓN JS: Mapeamos la nueva propiedad de datos del folio al inicio
                    { "data": "id_ticket" },
                    { "data": "nombre_cliente" },
                    { "data": "no_serie", "render": function(data) { return `<code class="fw-bold text-danger">${data}</code>`; } },
                    { "data": "modelo" },
                    { "data": "accion", "orderable": false, "className": "text-center" }
                ],
                "language": {
                    "sProcessing":     "Buscando en base de datos...",
                    "sLengthMenu":     "Ver _MENU_",
                    "sZeroRecords":    "No se encontraron coincidencias",
                    "sInfo":           "Mostrando _START_ al _END_ de _TOTAL_",
                    "sInfoFiltered":   "",
                    "sSearch":         "Filtrar:",
                    "oPaginate": { "sNext": "Sig", "sPrevious": "Ant" }
                },
                "pageLength": 6,
                "dom": 'ftp',
                "initComplete": function() {
                    $('#modalCatalogoSeries .dataTables_filter input').addClass('bg-transparent border border-secondary border-opacity-20 shadow-none');
                }
            });
        } else {
            tableModal.ajax.reload();
        }
    });

    $(document).on('click', '.btn-seleccionar-serie', function() {
        const serie = $(this).data('serie');
        $('#no_serie_input').val(serie).trigger('input'); 
        $('#modalCatalogoSeries').modal('hide');
    });

    $('#no_serie_input').on('input', function() {
        clearTimeout(typingTimer);
        let val = $(this).val().trim();
        let msgDiv = $('#status_busqueda');
        let txtStatus = $('#txt_status_busqueda');
        let btnGuardar = $('#btnGuardarLote');

        $('#confirm_modelo, #confirm_garantia, #confirm_cliente, #confirm_telefono, #confirm_ubicacion, #id_ticket_input').val('');
        btnGuardar.prop('disabled', true);
        serieValida = false;

        if (val.length > 2) {
            msgDiv.show();
            txtStatus.text('🔍 Buscando serie...').css('color', '#6c757d');
            msgDiv.css('border-left', '4px solid #dee2e6');

            typingTimer = setTimeout(function() {
                $.ajax({
                    url: 'actions/buscar_equipo_garantia.php',
                    method: 'POST',
                    data: { no_serie: val },
                    dataType: 'json',
                    success: function(res) {
                        if (res.exists) {
                            txtStatus.text('✅ Equipo Localizado').css('color', '#198754');
                            msgDiv.css('border-left', '4px solid #198754');
                            
                            $('#confirm_modelo').val(res.modelo);
                            $('#confirm_garantia').val(res.garantia_status);
                            $('#confirm_cliente').val(res.nombre_cliente);
                            $('#confirm_telefono').val(res.telefono ? res.telefono : 'N/A');
                            $('#confirm_ubicacion').val(res.ubicacion ? res.ubicacion : 'N/A');
                            
                            $('#id_ticket_input').val(res.id_ticket);
                            
                            btnGuardar.prop('disabled', false);
                            serieValida = true;
                        } else {
                            txtStatus.text('❌ Error: Esta serie no existe en Equipos Garantía').css('color', '#dc3545');
                            msgDiv.css('border-left', '4px solid #dc3545');
                        }
                    }
                });
            }, 300); 
        } else {
            msgDiv.hide();
        }
    });

    $('#formRegistroLote').on('submit', function(e) {
        e.preventDefault();
        if (!serieValida) return false;

        const btn = $('#btnGuardarLote');
        const txtBtn = $('#btnText');
        
        btn.prop('disabled', true);
        txtBtn.text('Procesando...');

        Swal.fire({
            title: 'Registrando entrada...',
            text: 'Guardando traza logística en la base de datos.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch(this.action, { method: this.method, body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? '¡Hecho!' : 'Falla',
                text: data.message,
                confirmButtonColor: data.success ? '#198754' : '#dc3545'
            }).then(() => {
                if (data.success) window.location.href = 'index.php';
                else btn.prop('disabled', false).text('Finalizar Entrada');
            });
        })
        .catch(error => {
            Swal.close();
            btn.prop('disabled', false).text('Finalizar Entrada');
            Swal.fire({ icon: 'error', title: 'Error de Red', text: error.message });
        });
    });
});
</script>