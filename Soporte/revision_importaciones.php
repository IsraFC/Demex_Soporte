<?php
/**
 * ARCHIVO: Soporte/revision_importaciones.php
 * DESCRIPCIÓN: Laboratorio Técnico Agrupado por Lotes con Desplegable de Unidades.
 * @project Soporte Técnico DEMEX
 * @version 3.0 - Vista por Lotes con Subtabla Child Rows
 * @author Israel Fernández Carrera
 */

require_once '../config/db.php';
$page_title = "Laboratorio de Lotes";

// Carga inicial antes del DOM (se mantendrán actualizados por AJAX)
$pendientes_taller = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'DISPONIBLE PARA SOPORTE'")->fetchColumn();
$en_diagnostico    = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN SOPORTE'")->fetchColumn();

$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<style>
    td.details-control { cursor: pointer; text-align: center; }
    .subtabla-lote {
        background-color: #f8f9fa !important;
        border-radius: 12px;
        padding: 15px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }
</style>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-6">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-boxes me-2"></i>Laboratorio de Lotes</h1>
        <p class="text-muted small mb-0">Revisión técnica, calibración mecánica e inspección de maquinaria por contenedor.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 120px;">
                <span id="kpi_pendientes" class="d-block fw-bold fs-5 text-warning"><?= intval($pendientes_taller) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">POR RECIBIR</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-primary border-4 text-center" style="min-width: 120px;">
                <span id="kpi_diagnostico" class="d-block fw-bold fs-5 text-primary"><?= intval($en_diagnostico) ?></span>
                <small class="text-muted fw-bold" style="font-size: 0.6rem;">EN CALIBRACIÓN</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded animate__animated animate__fadeIn">
    <div class="row g-3 align-items-center px-3">
        <div class="col-md-4">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text border-0 bg-transparent"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0 small" placeholder="Buscar Contenedor o Tipo...">
            </div>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded animate__animated animate__fadeInUp">
    <div class="table-responsive">
        <table id="tablaRevisiones" class="table table-hover align-middle w-100">
            <thead class="table-light text-uppercase small fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                <tr>
                    <th width="30" class="text-center"></th>
                    <th>Contenedor / Lote</th>
                    <th>Tipo Destino</th>
                    <th>Arribo Bodega</th>
                    <th class="text-center">Total en Taller</th>
                    <th>Estatus en Laboratorio</th>
                </tr>
            </thead>
            <tbody class="small fw-semibold text-dark"></tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    var table;

    function recargarKPILaboratorio() {
        $.ajax({
            url: 'actions/obtener_conteos_laboratorio.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#kpi_pendientes').text(response.pendientes);
                    $('#kpi_diagnostico').text(response.diagnostico);
                }
            }
        });
    }

    $(document).ready(function() {
        if ($('#tablaRevisiones').length) {
            table = $('#tablaRevisiones').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "actions/obtener_revisiones_datatable.php",
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
                    { "data": "total_taller", "className": "text-center fw-bold fs-6" },
                    { "data": "desglose_estatus" }
                ],
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "dom": 'rtip',
                "pageLength": 10,
                "order": [[3, "desc"]]
            });

            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

            // DESPLEGABLE CHILD ROWS
            $('#tablaRevisiones tbody').on('click', 'td.details-control', function () {
                var tr = $(this).closest('tr');
                var row = table.row(tr);
                var btn = $(this).find('button i');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    btn.removeClass('bi-dash-circle-fill').addClass('bi-plus-circle-fill');
                } else {
                    btn.removeClass('bi-plus-circle-fill').addClass('bi-dash-circle-fill');
                    row.child('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-danger" role="status"></div> Cargando modelos en taller...</div>').show();
                    tr.addClass('shown');

                    $.ajax({
                        url: 'actions/obtener_desglose_soporte.php',
                        method: 'GET',
                        data: { id_lote: row.data().id_lote },
                        success: function (html) {
                            row.child(html).show();
                        }
                    });
                }
            });
        }
    });

    function ejecutarCambioFase(id, nuevoEstatus, campoFecha) {
        let textoAlerta = (nuevoEstatus === 'EN REVISIÓN SOPORTE') ? 
            'Se iniciarán las pruebas de laboratorio y calibración para 1 unidad.' : 
            'Se certificará el control de calidad regresando 1 unidad a Almacén.';

        Swal.fire({
            title: '¿Confirmar actualización?',
            text: textoAlerta,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: (nuevoEstatus === 'EN REVISIÓN SOPORTE') ? '#0d6efd' : '#198754',
            cancelButtonColor: '#adb5bd',
            confirmButtonText: 'Sí, firmar fase',
            cancelButtonText: 'Regresar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Actualizando registro...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                
                const fd = new FormData();
                fd.append('id', id);
                fd.append('nuevo_estatus', nuevoEstatus);
                fd.append('campo_fecha', campoFecha);
                fd.append('fecha_fase', new Date().toISOString().split('T')[0]);

                fetch('../Almacen/actions/actualizar_fase.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: '¡Fase Actualizada!', text: data.message, timer: 1500, showConfirmButton: false });
                        
                        // Refrescar tabla y subtabla
                        table.ajax.reload(null, false);
                        recargarKPILaboratorio();

                        $('#tablaRevisiones tr.shown').each(function() {
                            var row = table.row(this);
                            if (row.child.isShown()) {
                                $.ajax({
                                    url: 'actions/obtener_desglose_soporte.php',
                                    method: 'GET',
                                    data: { id_lote: row.data().id_lote },
                                    success: function (html) { row.child(html).show(); }
                                });
                            }
                        });

                    } else {
                        Swal.fire({ icon: 'error', title: 'Falla', text: data.message, confirmButtonColor: '#dc3545' });
                    }
                })
                .catch(() => {
                    Swal.close();
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.', confirmButtonColor: '#dc3545' });
                });
            }
        });
    }
</script>