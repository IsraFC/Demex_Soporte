<?php
/**
 * ARCHIVO: recompras_crm.php
 * DESCRIPCIÓN: Panel de Control de Recompras CRM con Motor de Búsqueda Asíncrono.
 * Integra animaciones intermitentes en semáforos, filtros avanzados de DataTables y visor modal blindado.
 * ORDENAMIENTO: Clasificación por Prioridad Estricta de Semáforo (Urgente > Atención > En Curso > Al día).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 6.1 (Estructura espejo homologada con Leads)
 */

$page_title = "Pipeline de Recompras | CRM Ventas";
require_once '../config/db.php';

/**
 * KPIs - INDICADORES CLAVE DE DESEMPEÑO (PHP Base)
 */
$total_recompras = $pdo->query("SELECT COUNT(*) FROM cotizacion WHERE id_prospecto IS NULL OR id_prospecto = 0")->fetchColumn();

$maquinas_reales = ['DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-success mb-0"><i class="bi bi-arrow-repeat"></i> CRM de Recompras</h1>
        <p class="text-muted small">Seguimiento de segundas compras y negociaciones con clientes frecuentes de la cartera.</p>
    </div>
    <div class="col-md-7 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-secondary border-4 text-center" style="min-width: 105px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= $total_recompras ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">TOTAL RECOMP.</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-primary border-4 text-center" style="min-width: 105px;">
                <span id="kpi-encurso" class="d-block fw-bold fs-5 text-primary">0</span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">EN CURSO</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 105px;">
                <span id="kpi-pendientes" class="d-block fw-bold fs-5 text-warning">0</span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">ATENCIÓN</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 105px;">
                <span id="kpi-urgentes" class="d-block fw-bold fs-5 text-danger">0</span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">URGENTES</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-success bg-white rounded">
    <div class="row g-0 align-items-center px-3 justify-content-between">
        <div class="col-auto" style="width: 30%;">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-success"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Buscar Cliente o Correo...">
            </div>
        </div>
        <div class="col-auto">
            <select id="filterCanal" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 220px;">
                <option value="">Todos los Canales</option>
                <option value="Cliente Frecuente">Cliente Frecuente</option>
            </select>
        </div>
        <div class="col-auto">
            <select id="filterEquipo" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 220px;">
                <option value="">Todos los Equipos</option>
                <?php foreach ($maquinas_reales as $maquina): ?>
                    <option value="<?= htmlspecialchars($maquina) ?>"><?= htmlspecialchars($maquina) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto d-flex flex-column gap-1">
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="btnFiltrarCriticos" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" style="cursor:pointer;" for="btnFiltrarCriticos">Solo Alertas Urgentes</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="btnFiltrarPendientes" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" style="cursor:pointer;" for="btnFiltrarPendientes">Solo Alertas Atención</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="btnFiltrarEnCurso" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" style="cursor:pointer;" for="btnFiltrarEnCurso">Solo Cotizaciones En Curso</label>
            </div>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaRecompras" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>Fecha Emisión</th>
                    <th>Cliente / Canal</th>
                    <th>Contacto Directo</th>
                    <th>Ubicación</th>
                    <th>Equipo de Interés</th>
                    <th class="text-center" style="width: 140px;">Estatus Venta</th>
                    <th class="text-center" style="width: 140px;">Estatus Cotiz.</th> 
                    <th class="text-center" style="width: 130px;">Semáforo</th>
                    <th class="text-center" style="width: 150px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                        $sql = "SELECT c.*, cl.nombre_cliente, cl.apellidos_cliente, cl.telefono, cl.correo, cl.ubicacion, m.nombre_maquina,
                    DATEDIFF(CURDATE(), c.fecha_emision) AS dias_transcurridos
                FROM cotizacion c
                INNER JOIN clientes cl ON (c.cliente = CONCAT(cl.nombre_cliente, ' ', IFNULL(cl.apellidos_cliente, '')) OR c.rfc_receptor = cl.rfc_receptor)
                INNER JOIN maquinas m ON c.id_maquina = m.id_maquina
                WHERE c.id_prospecto IS NULL OR c.id_prospecto = 0
                GROUP BY c.id_cotizacion
                ORDER BY 
                    CASE 
                        WHEN c.status_cotizacion = 'Vencida' OR DATEDIFF(CURDATE(), c.fecha_emision) > 30 THEN 1
                        WHEN c.status_cotizacion = 'Vigente' AND DATEDIFF(CURDATE(), c.fecha_emision) <= 30 THEN 2
                        WHEN c.status_cotizacion = 'Cerrada' THEN 4
                        ELSE 5
                    END ASC, 
                    c.fecha_emision DESC";
                
                $stmt = $pdo->query($sql);
                while ($recompra = $stmt->fetch()):
                    $estatus_real_cotizacion = $recompra['status_cotizacion'];
                    if (!empty($recompra['fecha_emision']) && intval($recompra['dias_transcurridos']) > 30 && $estatus_real_cotizacion !== 'Cerrada') {
                        $estatus_real_cotizacion = 'Vencida';
                    }
                ?>
                <tr class="row-lead-item" 
                    data-origen="Cliente Frecuente" 
                    data-equipo="<?= htmlspecialchars($recompra['nombre_maquina']) ?>" 
                    data-urgente="0"
                    data-atencion="0"
                    data-encurso="0"> 
                    <td class="small fw-semibold text-secondary"><?= date('d/m/Y g:i A', strtotime($recompra['fecha_emision'])) ?></td>
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($recompra['nombre_cliente'] . ' ' . $recompra['apellidos_cliente']) ?></div>
                        <span class="badge mt-1 text-uppercase text-success border bg-white" style="font-size: 0.65rem; letter-spacing: 0.5px; font-weight: 500; padding: 0.2rem 0.4rem; border-radius: 4px;">Cliente Frecuente</span>
                    </td>
                    <td>
                        <div class="small text-dark"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($recompra['correo']) ?></div>
                        <div class="small mt-1">
                            <a href="https://wa.me/52<?= $recompra['telefono'] ?>" target="_blank" class="text-success text-decoration-none fw-semibold d-inline-flex align-items-center">
                                <i class="bi bi-whatsapp me-1 fs-6"></i><?= htmlspecialchars($recompra['telefono']) ?>
                            </a>
                        </div>
                    </td>
                    <td class="small text-secondary">
                        <i class="bi bi-geo-alt-fill text-muted me-1"></i><?= htmlspecialchars($recompra['ubicacion'] ?? 'Puebla, México') ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border py-1.5 px-2.5 fw-semibold" style="border-radius: 6px; font-size: 0.75rem;">
                            <?= htmlspecialchars($recompra['nombre_maquina']) ?>
                        </span>
                    </td>
                    <td class="text-center col-status-badge"></td>
                    
                    <td class="text-center col-cotizacion-badge" 
                        data-tiene-cotizacion="1"
                        data-status-cotiz="<?= htmlspecialchars($estatus_real_cotizacion ?? '') ?>">
                    </td>

                    <td class="text-center col-semaforo" 
                        data-status-venta="<?= ($estatus_real_cotizacion === 'Cerrada') ? 'Venta Cerrada' : 'Cotizado' ?>"
                        data-status-cotizacion="<?= htmlspecialchars($estatus_real_cotizacion ?? '') ?>"
                        data-fecha-consulta="<?= $recompra['fecha_emision'] ?>"
                        data-fecha-cotizacion="<?= htmlspecialchars($recompra['fecha_emision'] ?? '') ?>">
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm col-acciones-comerciales" 
                             data-id-prospecto="0" 
                             data-id-cotizacion="<?= htmlspecialchars($recompra['id_cotizacion'] ?? '0') ?>"
                             data-status-venta="<?= ($estatus_real_cotizacion === 'Cerrada') ? 'Venta Cerrada' : 'Cotizado' ?>">
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalLiberarVenta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 16px;">
            <div class="modal-header bg-success text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Formulario de Cierre de Recompra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formConfirmarVenta">
                <input type="hidden" id="liberar_id_cotizacion" name="id_cotizacion">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">La cotización se guardará como 'Cerrada' y el nuevo equipo se sumará automáticamente al historial acumulado de este cliente.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small">Fecha Exacta de Compra <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="liberar_fecha_compra" name="fecha_compra" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold text-dark small">Observaciones Especiales del Cierre</label>
                        <textarea class="form-control small text-muted" id="liberar_observaciones" name="observaciones_venta" rows="3" placeholder="Ej. Segunda sucursal aperturada, entrega prioritaria..." style="font-size: 0.82rem; resize: none;"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary px-3 fw-bold small" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold small"><i class="bi bi-send-check me-1"></i> Liberar y Cargar a Historial</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetallesCotizacion" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 12px;">
            <div class="modal-header bg-danger text-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold">Desglose Técnico de Cotización</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="cuerpoModalCotizacion">
                <div class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'success') {
        Swal.fire({
            title: '¡Cambios Guardados!',
            text: 'La cotización de recompra se actualizó exitosamente.',
            icon: 'success',
            confirmButtonColor: '#198754',
            confirmButtonText: 'Entendido'
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }

    // --- 1. MOTOR DE REACTIVIDAD EN TIEMPO REAL HOMOLOGADO ---
    function calcularSemaforosComerciales() {
        const ahora = Date.now();
        
        let countEnCurso = 0;
        let countAtencion = 0;
        let countUrgentes = 0;

        $('.col-semaforo').each(function() {
            const statusVenta = $(this).data('status-venta');
            const statusCotiz = $(this).data('status-cotizacion');
            const fechaCotizStr = $(this).data('fecha-cotizacion');
            
            const fila = $(this).closest('tr');
            const contenedorAcciones = fila.find('.col-acciones-comerciales');
            const contenedorStatusBadge = fila.find('.col-status-badge');
            const contenedorCotizBadge = fila.find('.col-cotizacion-badge');
            
            const idCotizacion = contenedorAcciones.data('id-cotizacion');

            // --- A. RENDEREADO DE ESTATUS DE VENTA ---
            let statusBadgeHtml = '';
            if (statusVenta === 'Venta Cerrada' || statusCotiz === 'Cerrada') {
                statusBadgeHtml = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Venta Cerrada</span>';
            } else {
                statusBadgeHtml = '<span class="badge" style="background-color: #FFFDE7; color: #F57F17; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Cotizado</span>';
            }
            if (contenedorStatusBadge.html() !== statusBadgeHtml) {
                contenedorStatusBadge.html(statusBadgeHtml);
            }

            // --- B. RENDEREADO DE ESTATUS DE COTIZACIÓN ---
            let cotizBadgeHtml = '';
            if (statusCotiz === 'Cerrada') {
                cotizBadgeHtml = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-calendar-check me-1"></i> Cerrada</span>';
            } else if (statusCotiz === 'Vencida') {
                cotizBadgeHtml = '<span class="badge bg-danger animate__animated animate__flash animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-calendar-x me-1"></i> Vencida</span>';
            } else {
                cotizBadgeHtml = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-calendar-check me-1"></i> Vigente</span>';
            }
            if (contenedorCotizBadge.html() !== cotizBadgeHtml) {
                contenedorCotizBadge.html(cotizBadgeHtml);
            }

            // --- C. RENDEREADO DE LA BARRA DE ACCIONES AUTOMÁTICA ---
            let botonesHtml = '';
            if (statusCotiz === 'Cerrada') {
                botonesHtml = `<button type="button" onclick="verDetallesCotizacion(${idCotizacion})" class="btn btn-outline-info border-0" title="Visualizar Detalle Cotización"><i class="bi bi-eye-fill fs-5"></i></button>`;
            } else {
                botonesHtml = `<button type="button" onclick="verDetallesCotizacion(${idCotizacion})" class="btn btn-outline-info border-0" title="Visualizar Detalle Cotización"><i class="bi bi-eye-fill fs-5"></i></button>
                               <a href="editar_cotizacion.php?id_cotizacion=${idCotizacion}" class="btn btn-outline-warning border-0" title="Editar Cotización"><i class="bi bi-pencil-square fs-5"></i></a>
                               <button type="button" class="btn btn-outline-success border-0" onclick="cerrarOperacionComercial(${idCotizacion})" title="Cerrar Recompra"><i class="bi bi-check-circle-fill fs-5"></i></button>`;
            }
            if (contenedorAcciones.html() !== botonesHtml) {
                contenedorAcciones.html(botonesHtml);
            }

            // --- D. LÓGICA DE SEMÁFOROS ---
            if (statusCotiz === 'Cerrada') {
                $(this).html('<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-check-circle-fill me-1"></i> Al día</span>');
                fila.removeClass('table-warning-sutil table-danger-sutil').attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '0');
                return;
            }

            const fechaCotizacion = new Date(fechaCotizStr);
            const diasCotizado = Math.floor((ahora - fechaCotizacion.getTime()) / (1000 * 60 * 60 * 24));
            
            if (statusCotiz === 'Vencida' || diasCotizado > 30) {
                $(this).html('<span class="badge bg-danger animate__animated animate__headShake animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 3.5s !important;"><i class="bi bi-fire me-1"></i> Urgente</span>');
                fila.removeClass('table-warning-sutil').addClass('table-danger-sutil').attr('data-urgente', '1').attr('data-atencion', '0').attr('data-encurso', '0');
                countUrgentes++;
            } else if (diasCotizado > 15) {
                $(this).html('<span class="badge bg-warning text-dark animate__animated animate__flash animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 6.5s !important;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Atención</span>');
                fila.removeClass('table-danger-sutil').addClass('table-warning-sutil').attr('data-urgente', '0').attr('data-atencion', '1').attr('data-encurso', '0');
                countAtencion++;
            } else {
                $(this).html('<span class="badge" style="background-color: #E3F2FD; color: #0D47A1; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 3.5s !important;"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> En Curso</span>');
                fila.removeClass('table-warning-sutil table-danger-sutil').attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '1');
                countEnCurso++;
            }
        });

        $('#kpi-encurso').text(countEnCurso);
        $('#kpi-pendientes').text(countAtencion);
        $('#kpi-urgentes').text(countUrgentes);
    }

    // --- 2. CONFIGURACIÓN DE DATATABLES ---
    var table = $('#tablaRecompras').DataTable({
        "language": { "emptyTable": "No hay datos", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "0 registros", "infoFiltered": "(filtrado de _MAX_)", "zeroRecords": "Sin coincidencias", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtip', "pageLength": 10, "ordering": false, "responsive": true,
        "drawCallback": function() { calcularSemaforosComerciales(); }
    });

    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    $('#filterEquipo').on('change', function() { table.column(4).search(this.value).draw(); });
    
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var row = $(table.row(dataIndex).node());
        var cumpleUrgente = !$('#btnFiltrarCriticos').is(':checked') || row.attr('data-urgente') === '1';
        var cumplePendiente = !$('#btnFiltrarPendientes').is(':checked') || row.attr('data-atencion') === '1';
        var cumpleEnCurso = !$('#btnFiltrarEnCurso').is(':checked') || row.attr('data-encurso') === '1';
        return cumpleUrgente && cumplePendiente && cumpleEnCurso;
    });

    $('#btnFiltrarCriticos').on('change', function() { table.draw(); });
    $('#btnFiltrarPendientes').on('change', function() { table.draw(); });
    $('#btnFiltrarEnCurso').on('change', function() { table.draw(); });

    calcularSemaforosComerciales();
    setInterval(calcularSemaforosComerciales, 500); 

    // --- 3. ENVÍO DEL FORMULARIO DE RECOMPRA ---
    $('#formConfirmarVenta').on('submit', function(e) {
        e.preventDefault();
        
        const idCotizacion = $('#liberar_id_cotizacion').val();
        const fechaCompra = $('#liberar_fecha_compra').val();
        const observaciones = $('#liberar_observaciones').val();

        $.ajax({
            url: '../actions/actualizar_status_recompra.php',
            method: 'POST',
            data: { 
                id_cotizacion: idCotizacion, 
                fecha_compra: fechaCompra,
                observaciones_venta: observaciones
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalLiberarVenta').modal('hide');
                    Swal.fire({ title: '¡Recompra Cerrada!', text: 'El historial de la cartera del cliente se actualizó en tiempo real.', icon: 'success', timer: 2000, showConfirmButton: false });
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({ title: 'Error', text: response.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error de Red', text: 'No se pudo conectar con el servidor corporativo.', icon: 'error' });
            }
        });
    });
});

function cerrarOperacionComercial(idCotizacion) {
    $('#formConfirmarVenta')[0].reset();
    $('#liberar_id_cotizacion').val(idCotizacion);
    $('#liberar_fecha_compra').val(new Date().toISOString().split('T')[0]);
    $('#modalLiberarVenta').appendTo("body").modal('show');
}

function verDetallesCotizacion(idCotizacion) {
    $('#cuerpoModalCotizacion').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-danger" role="status"><span class="visually-hidden">Cargando...</span></div>
            <p class="text-muted small mt-2">Consultando servidor corporativo DEMEX central...</p>
        </div>
    `);
    $('#modalDetallesCotizacion').appendTo("body").modal('show');
    
    $.ajax({
        url: '../actions/obtener_detalles_cotizacion.php',
        method: 'GET',
        data: { id_cotizacion: idCotizacion },
        success: function(response) { $('#cuerpoModalCotizacion').html(response); }
    });
}
</script>