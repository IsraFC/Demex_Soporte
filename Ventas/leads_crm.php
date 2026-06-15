<?php
/**
 * ARCHIVO: leads_crm.php
 * DESCRIPCIÓN: Panel de Control de Leads CRM con Motor de Búsqueda Asíncrono.
 * Integra animaciones intermitentes en semáforos, filtros avanzados de DataTables y visor modal blindado.
 * ORDENAMIENTO: Clasificación por Prioridad Estricta de Semáforo (Urgente > Atención > En Curso > Al día).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 6.0 (Filtro Adicional de Cotizaciones En Curso)
 */

$page_title = "Panel de Seguimiento | CRM Ventas";
require_once '../config/db.php';

/**
 * KPIs - INDICADORES CLAVE DE DESEMPEÑO (PHP Base)
 */
$total_leads = $pdo->query("SELECT COUNT(*) FROM prospectos")->fetchColumn();

$maquinas_reales = ['DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-funnel"></i> Control de Prospectos a Clientes</h1>
        <p class="text-muted small">Prospectos capturados desde el formulario público de la página web.</p>
    </div>
    <div class="col-md-7 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-secondary border-4 text-center" style="min-width: 105px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= $total_leads ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">TOTAL PROSP.</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-primary border-4 text-center" style="min-width: 105px;">
                <span id="kpi-encurso" class="d-block fw-bold fs-5 text-primary">0</span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">POR COTIZAR</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 105px;">
                <span id="kpi-pendientes" class="d-block fw-bold fs-5 text-warning">0</span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">PENDIENTES</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 105px;">
                <span id="kpi-urgentes" class="d-block fw-bold fs-5 text-danger">0</span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">URGENTES</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-0 align-items-center px-3 justify-content-between">
        <div class="col-auto" style="width: 30%;">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Buscar Prospecto o Correo...">
            </div>
        </div>
        <div class="col-auto">
            <select id="filterCanal" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 220px;">
                <option value="">Todos los Canales</option>
                <option value="Página Web">Página Web</option>
                <option value="Facebook">Facebook</option>
                <option value="YouTube">YouTube</option>
                <option value="WhatsApp">WhatsApp</option>
                <option value="Recomendación">Recomendación</option>
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
                <label class="form-check-label small fw-bold text-muted" style="cursor:pointer;" for="btnFiltrarPendientes">Solo Cotizaciones Pendientes</label>
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
        <table id="tablaLeads" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>Fecha Registro</th>
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
                $sql = "SELECT f.*, p.id_prospecto, p.status_comercial, p.fecha_ultimo_contacto,
                               c.id_cotizacion, c.status_cotizacion, c.fecha_emision AS cotizacion_fecha,
                               DATEDIFF(CURDATE(), c.fecha_emision) AS dias_transcurridos
                        FROM formulario f
                        INNER JOIN prospectos p ON f.id_formulario = p.id_formulario
                        LEFT JOIN cotizacion c ON p.id_prospecto = c.id_prospecto
                        ORDER BY 
                            CASE 
                                WHEN p.status_comercial = 'Consultado' AND DATEDIFF(CURDATE(), p.fecha_ultimo_contacto) > 5 THEN 1
                                WHEN p.status_comercial = 'Cotizado' AND (c.status_cotizacion = 'Vencida' OR DATEDIFF(CURDATE(), c.fecha_emision) > 30) THEN 1
                                WHEN p.status_comercial = 'Cotizado' AND DATEDIFF(CURDATE(), c.fecha_emision) <= 30 THEN 2
                                WHEN p.status_comercial = 'Cotizado' AND c.id_cotizacion IS NOT NULL AND c.fecha_emision IS NULL THEN 2
                                WHEN p.status_comercial = 'Consultado' AND DATEDIFF(CURDATE(), p.fecha_ultimo_contacto) <= 5 THEN 3
                                WHEN p.status_comercial = 'Venta Cerrada' THEN 4
                                ELSE 5
                            END ASC, 
                            f.fecha_registro DESC";
                
                $stmt = $pdo->query($sql);
                while ($lead = $stmt->fetch()):
                    $estatus_real_cotizacion = $lead['status_cotizacion'];
                    if (!empty($lead['cotizacion_fecha']) && intval($lead['dias_transcurridos']) > 30) {
                        $estatus_real_cotizacion = 'Vencida';
                    }
                ?>
                <tr class="row-lead-item" 
                    data-origen="<?= htmlspecialchars($lead['canal_origen']) ?>" 
                    data-equipo="<?= htmlspecialchars($lead['maquina_interes']) ?>" 
                    data-urgente="0"
                    data-atencion="0"
                    data-encurso="0"> <td class="small fw-semibold text-secondary"><?= date('d/m/Y g:i A', strtotime($lead['fecha_registro'])) ?></td>
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($lead['nombre'] . ' ' . $lead['apellidos']) ?></div>
                        <span class="badge mt-1 text-uppercase text-muted border bg-white" style="font-size: 0.65rem; letter-spacing: 0.5px; font-weight: 500; padding: 0.2rem 0.4rem; border-radius: 4px;"><?= htmlspecialchars($lead['canal_origen']) ?></span>
                    </td>
                    <td>
                        <div class="small text-dark"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($lead['correo']) ?></div>
                        <div class="small mt-1">
                            <a href="https://wa.me/52<?= $lead['telefono'] ?>" target="_blank" class="text-success text-decoration-none fw-semibold d-inline-flex align-items-center">
                                <i class="bi bi-whatsapp me-1 fs-6"></i><?= htmlspecialchars($lead['telefono']) ?>
                            </a>
                        </div>
                    </td>
                    <td class="small text-secondary">
                        <i class="bi bi-geo-alt-fill text-muted me-1"></i><?= htmlspecialchars($lead['estado_region'] . ', ' . $lead['pais']) ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border py-1.5 px-2.5 fw-semibold" style="border-radius: 6px; font-size: 0.75rem;">
                            <?= htmlspecialchars($lead['maquina_interes']) ?>
                        </span>
                    </td>
                    <td class="text-center col-status-badge"></td>
                    
                    <td class="text-center col-cotizacion-badge" 
                        data-tiene-cotizacion="<?= ($lead['id_cotizacion'] > 0) ? '1' : '0' ?>"
                        data-status-cotiz="<?= htmlspecialchars($estatus_real_cotizacion ?? '') ?>">
                    </td>

                    <td class="text-center col-semaforo" 
                        data-status-venta="<?= $lead['status_comercial'] ?>"
                        data-status-cotizacion="<?= htmlspecialchars($estatus_real_cotizacion ?? '') ?>"
                        data-fecha-consulta="<?= $lead['fecha_ultimo_contacto'] ?>"
                        data-fecha-cotizacion="<?= htmlspecialchars($lead['cotizacion_fecha'] ?? '') ?>">
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm col-acciones-comerciales" 
                             data-id-prospecto="<?= $lead['id_prospecto'] ?>" 
                             data-id-cotizacion="<?= htmlspecialchars($lead['id_cotizacion'] ?? '0') ?>"
                             data-status-venta="<?= $lead['status_comercial'] ?>">
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
    
    // --- Interceptor de Mensajes en URL para Alertas Animadas ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'success') {
        Swal.fire({
            title: '¡Cambios Guardados!',
            text: 'La cotización y el expediente del lead se actualizaron exitosamente.',
            icon: 'success',
            confirmButtonColor: '#198754',
            confirmButtonText: 'Entendido',
            showClass: { popup: 'animate__animated animate__fadeInDown' },
            hideClass: { popup: 'animate__animated animate__fadeOutUp' }
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    } else if (urlParams.get('msg') === 'error') {
        const descError = urlParams.get('desc') || 'No se pudo procesar la actualización.';
        Swal.fire({
            title: 'Error de Actualización',
            text: decodeURIComponent(descError),
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Revisar'
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }

    // --- 1. MOTOR DE REACTIVIDAD EN TIEMPO REAL ---
    function calcularSemaforosComerciales() {
        const ahora = Date.now();
        
        let countEnCurso = 0;
        let countAtencion = 0;
        let countUrgentes = 0;

        $('.col-semaforo').each(function() {
            const statusVenta = $(this).data('status-venta');
            const statusCotiz = $(this).data('status-cotizacion');
            const fechaConsultaStr = $(this).data('fecha-consulta');
            const fechaCotizStr = $(this).data('fecha-cotizacion');
            
            const fila = $(this).closest('tr');
            const contenedorAcciones = fila.find('.col-acciones-comerciales');
            const contenedorStatusBadge = fila.find('.col-status-badge');
            const contenedorCotizBadge = fila.find('.col-cotizacion-badge');
            
            const idProspecto = contenedorAcciones.data('id-prospecto');
            const idCotizacion = contenedorAcciones.data('id-cotizacion');

            // --- A. RENDEREADO DE LA INSIGNIA DE ESTATUS DE VENTA ---
            let statusBadgeHtml = '';
            if (statusVenta === 'Venta Cerrada') {
                statusBadgeHtml = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Venta Cerrada</span>';
            } else if (statusVenta === 'Cotizado') {
                statusBadgeHtml = '<span class="badge" style="background-color: #FFFDE7; color: #F57F17; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Cotizado</span>';
            } else {
                statusBadgeHtml = '<span class="badge" style="background-color: #E3F2FD; color: #0D47A1; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Consultado</span>';
            }
            if (contenedorStatusBadge.html() !== statusBadgeHtml) {
                contenedorStatusBadge.html(statusBadgeHtml);
            }

            // --- B. RENDEREADO DE LA INSIGNIA DE ESTATUS DE LA COTIZACIÓN ---
            const tieneCotizacion = contenedorCotizBadge.data('tiene-cotizacion');
            const valorStatusCotiz = contenedorCotizBadge.data('status-cotiz');
            let cotizBadgeHtml = '';

            if (tieneCotizacion === 1 || tieneCotizacion === '1') {
                if (valorStatusCotiz === 'Vencida' || valorStatusCotiz === 'vencida') {
                    cotizBadgeHtml = '<span class="badge bg-danger animate__animated animate__flash animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-calendar-x me-1"></i> Vencida</span>';
                } else {
                    cotizBadgeHtml = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-calendar-check me-1"></i> Vigente</span>';
                }
            } else {
                cotizBadgeHtml = '<span class="text-muted small"><em>Sin Emitir</em></span>';
            }

            if (contenedorCotizBadge.html() !== cotizBadgeHtml) {
                contenedorCotizBadge.html(cotizBadgeHtml);
            }

            // --- C. RENDEREADO DE LA BARRA DE ACCIONES AUTOMÁTICA ---
            let botonesHtml = '';
            if (statusVenta === 'Venta Cerrada') {
                botonesHtml = `<button type="button" onclick="verDetallesCotizacion(${idCotizacion})" class="btn btn-outline-info border-0" title="Visualizar Detalle Cotización"><i class="bi bi-eye-fill fs-5"></i></button>`;
            } else if (statusVenta === 'Cotizado') {
                botonesHtml = `<button type="button" onclick="verDetallesCotizacion(${idCotizacion})" class="btn btn-outline-info border-0" title="Visualizar Detalle Cotización"><i class="bi bi-eye-fill fs-5"></i></button>
                               <a href="editar_cotizacion.php?id_cotizacion=${idCotizacion}" class="btn btn-outline-warning border-0" title="Editar Cotización"><i class="bi bi-pencil-square fs-5"></i></a>
                               <button type="button" class="btn btn-outline-success border-0" onclick="cerrarOperacionComercial(${idProspecto})" title="Cerrar Venta"><i class="bi bi-check-circle-fill fs-5"></i></button>`;
            } else {
                botonesHtml = `<a href="cotizaciones.php?id_prospecto=${idProspecto}" class="btn btn-outline-danger border-0" title="Generar Cotización"><i class="bi bi-file-earmark-plus-fill fs-5"></i></a>`;
            }
            if (contenedorAcciones.html() !== botonesHtml) {
                contenedorAcciones.html(botonesHtml);
            }

            // --- D. LÓGICA DE TIEMPOS DEL SEMÁFORO Y MAPEO DE ANIMACIONES ---
            if (statusVenta === 'Venta Cerrada') {
                $(this).html('<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-check-circle-fill me-1"></i> Al día</span>');
                fila.removeClass('table-warning-sutil table-danger-sutil').attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '0');
                return;
            }

            if (statusVenta === 'Consultado') {
                const fechaConsulta = new Date(fechaConsultaStr);
                const diasInactivo = Math.floor((ahora - fechaConsulta.getTime()) / (1000 * 60 * 60 * 24));
                if (diasInactivo > 5) {
                    $(this).html('<span class="badge bg-danger animate__animated animate__headShake animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 3.5s !important;"><i class="bi bi-fire me-1"></i> Urgente</span>');
                    fila.removeClass('table-warning-sutil').addClass('table-danger-sutil').attr('data-urgente', '1').attr('data-atencion', '0').attr('data-encurso', '0');
                    countUrgentes++;
                } else {
                    $(this).html('<span class="badge" style="background-color: #E3F2FD; color: #0D47A1; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 3.5s !important;"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> En Curso</span>');
                    // MODIFICADO: Se setea data-encurso en 1 para que responda al nuevo switch de filtrado
                    fila.removeClass('table-warning-sutil table-danger-sutil').attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '1');
                    countEnCurso++;
                }
            }

            if (statusVenta === 'Cotizado') {
                if (!fechaCotizStr || idCotizacion === '0') {
                    $(this).html('<span class="badge bg-warning text-dark animate__animated animate__flash animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 4.5s !important;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Atención</span>');
                    fila.removeClass('table-danger-sutil').addClass('table-warning-sutil').attr('data-urgente', '0').attr('data-atencion', '1').attr('data-encurso', '0');
                    countAtencion++;
                    return;
                }
                const fechaCotizacion = new Date(fechaCotizStr);
                const diasCotizado = Math.floor((ahora - fechaCotizacion.getTime()) / (1000 * 60 * 60 * 24));
                
                if (diasCotizado <= 30 && statusCotiz !== 'Vencida') {
                    $(this).html('<span class="badge bg-warning text-dark animate__animated animate__flash animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 6.5s !important;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Atención</span>');
                    fila.removeClass('table-danger-sutil').addClass('table-warning-sutil').attr('data-urgente', '0').attr('data-atencion', '1').attr('data-encurso', '0');
                    countAtencion++;
                } else {
                    $(this).html('<span class="badge bg-danger animate__animated animate__headShake animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 6.5s !important;"><i class="bi bi-fire me-1"></i> Urgente</span>');
                    fila.removeClass('table-warning-sutil').addClass('table-danger-sutil').attr('data-urgente', '1').attr('data-atencion', '0').attr('data-encurso', '0');
                    countUrgentes++;
                }
            }
        });

        $('#kpi-encurso').text(countEnCurso);
        $('#kpi-pendientes').text(countAtencion);
        $('#kpi-urgentes').text(countUrgentes);
    }

    // --- 2. CONFIGURACIÓN DE DATATABLES ---
    var table = $('#tablaLeads').DataTable({
        "language": { "emptyTable": "No hay datos", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "0 registros", "infoFiltered": "(filtrado de _MAX_)", "zeroRecords": "Sin coincidencias", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtip', 
        "pageLength": 10, 
        "responsive": true,
        "ordering": false, 
        "drawCallback": function() { calcularSemaforosComerciales(); }
    });

    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    $('#filterCanal').on('change', function() { table.column(1).search(this.value).draw(); });
    $('#filterEquipo').on('change', function() { table.column(4).search(this.value).draw(); });
    
    // CORREGIDO: Filtro combinado de DataTables expandido a 3 estados simultáneos (Urgente, Atención, En Curso)
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var row = $(table.row(dataIndex).node());
        
        var cumpleUrgente = !$('#btnFiltrarCriticos').is(':checked') || row.attr('data-urgente') === '1';
        var cumplePendiente = !$('#btnFiltrarPendientes').is(':checked') || row.attr('data-atencion') === '1';
        var cumpleEnCurso = !$('#btnFiltrarEnCurso').is(':checked') || row.attr('data-encurso') === '1';
        
        return cumpleUrgente && cumplePendiente && cumpleEnCurso;
    });

    // Escuchadores de eventos para redibujar la tabla al cambiar cualquiera de los 3 switches
    $('#btnFiltrarCriticos').on('change', function() { table.draw(); });
    $('#btnFiltrarPendientes').on('change', function() { table.draw(); });
    $('#btnFiltrarEnCurso').on('change', function() { table.draw(); });

    // --- 3. LAZO DE TIEMPO REAL E INICIALIZACIÓN ---
    calcularSemaforosComerciales();
    setInterval(calcularSemaforosComerciales, 500); 
});

// --- 4. CONTROL DE FLUJO COMERCIAL INTERACTIVO (SweetAlert2 & AJAX) ---
function cerrarOperacionComercial(idProspecto) {
    Swal.fire({
        title: `¿Cerrar venta del prospecto #${idProspecto}?`,
        text: "El estatus de la venta cambiará a 'Venta Cerrada' de forma definitiva.",
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#adb5bd',
        confirmButtonText: 'Sí, confirmar cierre',
        cancelButtonText: 'Regresar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../actions/actualizar_status_comercial.php',
                method: 'POST',
                data: { id_prospecto: idProspecto, status_comercial: 'Venta Cerrada' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({ title: '¡Venta Cerrada!', text: 'El estatus se ha actualizado correctamente.', icon: 'success', timer: 1500, showConfirmButton: false });
                        const celda = $(`.col-acciones-comerciales[data-id-prospecto='${idProspecto}']`).closest('tr').find('.col-semaforo');
                        celda.data('status-venta', 'Venta Cerrada').attr('data-status-venta', 'Venta Cerrada');
                    } else {
                        Swal.fire({ title: 'Error', text: response.message, icon: 'error' });
                    }
                },
                error: function() {
                    Swal.fire({ title: 'Error de Red', text: 'No se pudo conectar con el servidor corporativo.', icon: 'error' });
                }
            });
        }
    });
}

// --- 5. VISUALIZADOR ASÍNCRONO DE EXPEDIENTE COMERCIAL EN MODAL ---
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
        success: function(response) {
            $('#cuerpoModalCotizacion').html(response);
        },
        error: function() {
            $('#cuerpoModalCotizacion').html('<div class="alert alert-danger m-0"><i class="bi bi-exclamation-octagon-fill me-2"></i> Error de comunicación con el servidor central de ventas.</div>');
        }
    });
}
</script>