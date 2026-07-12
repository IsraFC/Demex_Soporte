<?php
/**
 * ARCHIVO: leads_crm.php
 * DESCRIPCIÓN: Panel de Control de Leads CRM con Motor de Búsqueda Asíncrono.
 * Integra animaciones intermitentes en semáforos, filtros avanzados de DataTables y visor modal blindado.
 * ORDENAMIENTO: Clasificación por Prioridad Estricta de Semáforo (Urgente > Atención > En Curso > Al día).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 7.1 (Corrección estricta de invocación a modal de cierre comercial)
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
                               c.fecha_vencimiento, c.fecha_recordatorio
                        FROM formulario f
                        INNER JOIN prospectos p ON f.id_formulario = p.id_formulario
                        LEFT JOIN cotizacion c ON p.id_prospecto = c.id_prospecto
                        ORDER BY 
                            CASE 
                                WHEN p.status_comercial = 'Venta Cerrada' THEN 4
                                WHEN p.status_comercial = 'Consultado' AND DATEDIFF(CURDATE(), p.fecha_ultimo_contacto) > 5 THEN 1
                                WHEN p.status_comercial = 'Cotizado' AND (c.status_cotizacion = 'Vencida' OR c.fecha_recordatorio < CURDATE()) THEN 1
                                WHEN p.status_comercial = 'Cotizado' AND c.fecha_recordatorio = CURDATE() THEN 2
                                WHEN p.status_comercial = 'Cotizado' AND c.fecha_recordatorio > CURDATE() THEN 3
                                WHEN p.status_comercial = 'Consultado' AND DATEDIFF(CURDATE(), p.fecha_ultimo_contacto) <= 5 THEN 3
                                ELSE 5
                            END ASC, 
                            f.fecha_registro DESC";
                
                $stmt = $pdo->query($sql);
                while ($lead = $stmt->fetch()):
                    $estatus_real_cotizacion = $lead['status_cotizacion'];
                ?>
                <tr class="row-lead-item" 
                    data-origen="<?= htmlspecialchars($lead['canal_origen']) ?>" 
                    data-equipo="<?= htmlspecialchars($lead['maquina_interes']) ?>" 
                    data-urgente="0"
                    data-atencion="0"
                    data-encurso="0"> 
                    <td class="small fw-semibold text-secondary">
                        <?= date('d/m/Y g:i A', strtotime($lead['fecha_registro'])) ?>
                        <?php if(!empty($lead['fecha_vencimiento'])): ?>
                            <br><small class="text-muted" style="font-size:0.7rem;">Vence: <?= date('d/m/Y', strtotime($lead['fecha_vencimiento'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($lead['nombre']) ?></div>
                        <span class="badge mt-1 text-uppercase text-muted border bg-white" style="font-size: 0.65rem; letter-spacing: 0.5px; font-weight: 500; padding: 0.2rem 0.4rem; border-radius: 4px;"><?= htmlspecialchars($lead['canal_origen']) ?></span>
                    </td>
                    <td>
                        <div class="small text-dark"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($lead['correo'] ?? 'Sin Correo') ?></div>
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
                        data-fecha-recordatorio="<?= htmlspecialchars($lead['fecha_recordatorio'] ?? '') ?>">
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

<div class="modal fade" id="modalLiberarVenta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Desgloce de cierre de Venta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formConfirmarVenta">
                <input type="hidden" id="liberar_id_prospecto" name="id_prospecto">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">El prospecto se convertirá en Cliente formal y se dará de alta su equipo en el historial cronológico de compras.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small">Fecha Exacta de Compra <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="liberar_fecha_compra" name="fecha_compra" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold text-dark small">Observaciones Especiales del Cierre</label>
                        <textarea class="form-control small text-muted" id="liberar_observaciones" name="observaciones_venta" rows="3" placeholder="Ej. Pago realizado en efectivo de liquidación, entrega programada..." style="font-size: 0.82rem; resize: none;"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary px-3 fw-bold small" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold small"><i class="bi bi-send-check me-1"></i> Liberar y Pasar a Clientes</button>
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
                    <div class="spinner-border text-danger" role="status"><span class="visually-hidden">Cargando...</span></div>
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

    // === MOTOR DE ALERTAS PROACTIVAS DE RECORDATORIOS (LEADS) ===
    const d = new Date();
    const hoyStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    let leadsPendientesHoy = [];

    $('.row-lead-item').each(function() {
        const fechaRec = $(this).find('.col-semaforo').data('fecha-recordatorio');
        const statusVenta = $(this).find('.col-semaforo').data('status-venta');
        
        if (fechaRec === hoyStr && statusVenta === 'Cotizado') {
            const nombreLead = $(this).find('.fw-bold.text-dark').text().trim();
            const equipo = $(this).attr('data-equipo');
            leadsPendientesHoy.push(`• <strong>${nombreLead}</strong> (${equipo})`);
        }
    });

    if (leadsPendientesHoy.length > 0) {
        Swal.fire({
            title: `<i class="bi bi-bell-fill text-danger animate__animated animate__swing animate__infinite" style="display:inline-block;"></i> Tienes ${leadsPendientesHoy.length} seguimiento(s) hoy`,
            html: `<div class="text-start mt-2 small text-muted">Debes dar seguimiento hoy a los siguientes prospectos a clientes:</div>
                   <div class="text-start mt-3 p-3 bg-light rounded border border-dark" style="max-height: 200px; overflow-y: auto; font-size: 0.9rem; line-height: 1.5;">
                     ${leadsPendientesHoy.join('<br>')}
                   </div>`,
            icon: 'info',
            confirmButtonColor: '#c72f3e',
            confirmButtonText: 'Continuar',
            backdrop: false,
            position: 'top-end',
            toast: false,
            showCloseButton: true,
            customClass: { popup: 'shadow-lg border-start border-4 border-danger' }
        });
    }

function calcularSemaforosComerciales() {
        let countEnCurso = 0, countAtencion = 0, countUrgentes = 0;
        const ahora = new Date().getTime(); // Corrección para la variable de tiempo

        $('.col-semaforo').each(function() {
            const statusVenta = $(this).data('status-venta');
            const statusCotiz = $(this).data('status-cotizacion');
            const fechaConsultaStr = $(this).data('fecha-consulta');
            const fechaRecordatorioStr = $(this).data('fecha-recordatorio');
            
            const fila = $(this).closest('tr');
            const contenedorAcciones = fila.find('.col-acciones-comerciales');
            const contenedorStatusBadge = fila.find('.col-status-badge');
            const contenedorCotizBadge = fila.find('.col-cotizacion-badge');
            
            const idProspecto = contenedorAcciones.data('id-prospecto');
            // BLINDAJE: Si no hay cotización, forzamos que sea 0 o vacío de forma segura
            const idCotizacion = contenedorAcciones.data('id-cotizacion') || '0';

            let statusBadgeHtml = '';
            if (statusVenta === 'Venta Cerrada') {
                statusBadgeHtml = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Venta Cerrada</span>';
            } else if (statusVenta === 'Cotizado') {
                statusBadgeHtml = '<span class="badge" style="background-color: #FFFDE7; color: #F57F17; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Cotizado</span>';
            } else {
                statusBadgeHtml = '<span class="badge" style="background-color: #E3F2FD; color: #0D47A1; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Consultado</span>';
            }
            if (contenedorStatusBadge.html() !== statusBadgeHtml) contenedorStatusBadge.html(statusBadgeHtml);

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
            if (contenedorCotizBadge.html() !== cotizBadgeHtml) contenedorCotizBadge.html(cotizBadgeHtml);

            // === BLINDAJE CRÍTICO AQUÍ: Manejo de botones sin romper el HTML ===
            let botonesHtml = '';
            if (statusVenta === 'Venta Cerrada') {
                botonesHtml = `<button type="button" onclick="verDetallesCotizacion(${idCotizacion})" class="btn btn-outline-info border-0" title="Visualizar Detalle Cotización"><i class="bi bi-eye-fill fs-5"></i></button>`;
            } else if (statusVenta === 'Cotizado' && idCotizacion !== '0' && idCotizacion !== 0) {
                botonesHtml = `<button type="button" onclick="verDetallesCotizacion(${idCotizacion})" class="btn btn-outline-info border-0" title="Visualizar Detalle Cotización"><i class="bi bi-eye-fill fs-5"></i></button>
                               <a href="editar_cotizacion.php?id_cotizacion=${idCotizacion}" class="btn btn-outline-warning border-0" title="Editar Cotización"><i class="bi bi-pencil-square fs-5"></i></a>
                               <button type="button" class="btn btn-outline-success border-0" onclick="cerrarOperationComercial(${idProspecto})" title="Cerrar Venta"><i class="bi bi-check-circle-fill fs-5"></i></button>`;
            } else {
                // Si está consultado o por alguna razón no tiene cotización, siempre le damos el botón de crear
                botonesHtml = `<a href="cotizaciones.php?id_prospecto=${idProspecto}" class="btn btn-outline-danger border-0" title="Generar Cotización"><i class="bi bi-file-earmark-plus-fill fs-5"></i></a>`;
            }
            if (contenedorAcciones.html() !== botonesHtml) contenedorAcciones.html(botonesHtml);

            if (statusVenta === 'Venta Cerrada') {
                $(this).html('<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-check-circle-fill me-1"></i> Al día</span>');
                fila.removeClass('table-warning-sutil table-danger-sutil').attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '0');
                return;
            }

            if (statusVenta === 'Consultado' || !tieneCotizacion || tieneCotizacion === '0') {
                const fechaConsulta = new Date(fechaConsultaStr);
                const diasInactivo = Math.floor((ahora - fechaConsulta.getTime()) / (1000 * 60 * 60 * 24));
                if (diasInactivo > 5) {
                    $(this).html('<span class="badge bg-danger text-white px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-fire me-1"></i> Urgente</span>');
                    fila.removeClass('table-warning-sutil').addClass('table-danger-sutil').attr('data-urgente', '1').attr('data-atencion', '0').attr('data-encurso', '0');
                    countUrgentes++;
                } else {
                    $(this).html('<span class="badge bg-primary text-white px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> En Curso</span>');
                    fila.removeClass('table-warning-sutil table-danger-sutil').attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '1');
                    countEnCurso++;
                }
            } else if (statusVenta === 'Cotizado') {
                const hoyStr = new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0');
                if (statusCotiz === 'Vencida' || (fechaRecordatorioStr && fechaRecordatorioStr < hoyStr)) {
                    $(this).html('<span class="badge bg-danger text-white px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-fire me-1"></i> Urgente</span>');
                    fila.removeClass('table-warning-sutil').addClass('table-danger-sutil').attr('data-urgente', '1').attr('data-atencion', '0').attr('data-encurso', '0');
                    countUrgentes++;
                } else if (fechaRecordatorioStr === hoyStr) {
                    $(this).html('<span class="badge bg-warning text-dark px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Atención</span>');
                    fila.removeClass('table-danger-sutil').addClass('table-warning-sutil').attr('data-urgente', '0').attr('data-atencion', '1').attr('data-encurso', '0');
                    countAtencion++;
                } else {
                    $(this).html('<span class="badge bg-primary text-white px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> En Curso</span>');
                    fila.removeClass('table-warning-sutil table-danger-sutil').attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '1');
                    countEnCurso++;
                }
            }
        });

        $('#kpi-encurso').text(countEnCurso);
        $('#kpi-pendientes').text(countAtencion);
        $('#kpi-urgentes').text(countUrgentes);
    }

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
    
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var row = $(table.row(dataIndex).node());
        var cumpleUrgente = !$('#btnFiltrarCriticos').is(':checked') || row.attr('data-urgente') === '1';
        var cumplePendiente = !$('#btnFiltrarPendientes').is(':checked') || row.attr('data-atencion') === '1';
        var cumpleEnCurso = !$('#btnFiltrarEnCurso').is(':checked') || row.attr('data-encurso') === '1';
        return cumpleUrgente && cumplePendiente && cumpleEnCurso;
    });

    $('#btnFiltrarCriticos, #btnFiltrarPendientes, #btnFiltrarEnCurso').on('change', function() { table.draw(); });

    calcularSemaforosComerciales();
    setInterval(calcularSemaforosComerciales, 1000); 

    $('#formConfirmarVenta').on('submit', function(e) {
        e.preventDefault();
        const idProspecto = $('#liberar_id_prospecto').val();
        const fechaCompra = $('#liberar_fecha_compra').val();
        
        // CORREGIDO: Cambiado a #liberar_observaciones para que haga match estricto con el id del textarea
        const observaciones = $('#liberar_observaciones').val();

        $.ajax({
            url: '../actions/actualizar_status_comercial.php',
            method: 'POST',
            data: { 
                id_prospecto: idProspecto, 
                status_comercial: 'Venta Cerrada',
                fecha_compra: fechaCompra,
                observaciones_venta: observaciones
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalLiberarVenta').modal('hide');
                    Swal.fire({ 
                        title: '¡Venta Liberada!', 
                        text: 'El prospecto ha pasado exitosamente a tu cartera de clientes activos.', 
                        icon: 'success', 
                        timer: 2000, 
                        showConfirmButton: false 
                    });
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
    });
});

function cerrarOperationComercial(idProspecto) {
    $('#formConfirmarVenta')[0].reset();
    $('#liberar_id_prospecto').val(idProspecto);
    $('#liberar_fecha_compra').val(new Date().toISOString().split('T')[0]);
    
    // CORREGIDO: Empujamos el modal al body antes de mostrarlo para que el fondo opaco no tape la pantalla
    $('#modalLiberarVenta').appendTo("body").modal('show');
}

function verDetallesCotizacion(idCotizacion) {
    $_cuerpo = $('#cuerpoModalCotizacion');
    $_cuerpo.html(`
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
        success: function(response) { $_cuerpo.html(response); },
        error: function() { $_cuerpo.html('<div class="alert alert-danger m-0"><i class="bi bi-exclamation-octagon-fill me-2"></i> Error de comunicación con el servidor central de ventas.</div>'); }
    });
}
</script>