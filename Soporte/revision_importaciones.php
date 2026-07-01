<?php
/**
 * ARCHIVO: Soporte/revision_importaciones.php
 * DESCRIPCIÓN: Panel de Control de Laboratorio Técnico para revisión de stock nuevo de importación.
 * @project Soporte Técnico DEMEX
 * @version 1.1 (KPIs Asíncronos sin Recarga de Página)
 */

require_once '../config/db.php';
$page_title = "Laboratorio de Lotes";

// Carga inicial antes del DOM (se mantendrán actualizados por AJAX)
$pendientes_taller = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'DISPONIBLE PARA SOPORTE'")->fetchColumn();
$en_diagnostico    = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN SOPORTE'")->fetchColumn();

$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-6">
        <h1 class="fw-bold text-danger mb-0 text-uppercase"><i class="bi bi-boxes me-2"></i>Laboratorio de Lotes</h1>
        <p class="text-muted small mb-0">Revisión técnica, calibración mecánica e inspección de maquinaria nueva de importación.</p>
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
                <input type="text" id="customSearch" class="form-control bg-transparent border-0 small" placeholder="Serie, Contenedor o Modelo...">
            </div>
        </div>
        <div class="col-md-4">
            <select id="filterEstatus" class="form-select border-0 bg-light fw-bold text-muted shadow-sm" style="font-size: 14px;">
                <option value="">Filtrar Estatus Técnico</option>
                <option value="DISPONIBLE PARA SOPORTE">DISPONIBLE PARA SOPORTE (POR RECIBIR)</option>
                <option value="EN REVISIÓN SOPORTE">EN REVISIÓN SOPORTE (EN PROCESO)</option>
            </select>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded animate__animated animate__fadeInUp">
    <div class="table-responsive">
        <table id="tablaRevisiones" class="table table-hover align-middle w-100">
            <thead class="table-light text-uppercase small fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                <tr>
                    <th>Contenedor</th>
                    <th>Modelo Oficial</th>
                    <th>Nº Serie Único</th>
                    <th>Tipo Destino</th>
                    <th>Estatus Logístico</th>
                    <th>Arribo Bodega</th>
                    <th class="text-center" style="width: 150px;">Firma de Fase</th>
                </tr>
            </thead>
            <tbody class="small fw-semibold text-dark">
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    var table;

    // CORRECCIÓN FLUIDA: Consulta los nuevos números por AJAX sin recargar la pantalla
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
                    "type": "POST",
                    "data": function(d) {
                        d.filterEstatus = $('#filterEstatus').val();
                    }
                },
                "columns": [
                    { "data": "contenedor", "className": "fw-bold text-secondary" },
                    { "data": "modelo", "className": "fw-bold text-dark" },
                    { "data": "no_serie", "render": function(data) { return `<span class="fw-bold text-danger text-nowrap">${data}</span>`; } },
                    { "data": "tipo" },
                    { 
                        "data": "estatus",
                        "render": function(data) {
                            let badge = (data === 'DISPONIBLE PARA SOPORTE') ? 'bg-warning text-dark' : 'bg-primary text-white';
                            return `<span class="badge ${badge}" style="font-size: 0.65rem; padding: 0.35rem 0.5rem;">${data}</span>`;
                        }
                    },
                    { "data": "fecha_ingreso_contenedor" },
                    {
                        "data": null,
                        "orderable": false,
                        "className": "text-center",
                        "render": function(data, type, row) {
                            if (row.estatus === 'DISPONIBLE PARA SOPORTE') {
                                return `<button type="button" class="btn btn-primary btn-xs rounded-pill px-3 fw-bold shadow-sm" style="font-size: 11px;" onclick="ejecutarCambioFase(${row.id}, 'EN REVISIÓN SOPORTE', 'fecha_entrega_soporte')">
                                            <i class="bi bi-box-arrow-in-right me-1"></i> Recibir
                                        </button>`;
                            } else {
                                return `<button type="button" class="btn btn-success btn-xs rounded-pill px-3 fw-bold shadow-sm" style="font-size: 11px;" onclick="ejecutarCambioFase(${row.id}, 'REINGRESO A ALMACÉN', 'fecha_reingreso_almacen')">
                                            <i class="bi bi-send-check me-1"></i> Liberar
                                        </button>`;
                            }
                        }
                    }
                ],
                "language": {
                    "sProcessing":     "Buscando en inventario...",
                    "sLengthMenu":     "Mostrar _MENU_ registros",
                    "sZeroRecords":    "No hay maquinaria en tránsito en este momento",
                    "sInfo":           "Mostrando _START_ al _END_ de _TOTAL_",
                    "sInfoEmpty":      "Mostrando 0 al 0 de 0",
                    "sSearch":         "Buscar:",
                    "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Sig", "sPrevious": "Ant" }
                },
                "dom": 'rtip',
                "pageLength": 13,
                "order": [[5, "desc"]]
            });

            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
            $('#filterEstatus').on('change', function() { table.draw(); });
        }
    });

    function ejecutarCambioFase(id, nuevoEstatus, campoFecha) {
        let textoAlerta = (nuevoEstatus === 'EN REVISIÓN SOPORTE') ? 
            'Se estampará el inicio de pruebas de laboratorio en taller.' : 
            'Se certificará el control de calidad regresando la maquinaria a Almacén.';

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
                        Swal.fire({ icon: 'success', title: '¡Hecho!', text: data.message, timer: 1500, showConfirmButton: false });
                        
                        // CORRECCIÓN: Actualiza la tabla y los contadores de forma asíncrona y fluida
                        table.ajax.reload(null, false);
                        recargarKPILaboratorio();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Falla', text: data.message, confirmButtonColor: '#dc3545' });
                    }
                })
                .catch(() => {
                    Swal.close();
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Colapso de red en el servidor local.', confirmButtonColor: '#dc3545' });
                });
            }
        });
    }
</script>