<?php
/**
 * ARCHIVO: leads_crm.php
 * DESCRIPCIÓN: Panel de Control de Leads CRM con Vista Anidada Jerárquica.
 * Agrupa las cotizaciones por Prospecto Único y despliega sub-tablas con transiciones fluidas.
 * ORDENAMIENTO: Clasificación por Prioridad de Alerta Master (Urgente > Pendiente > En Curso > Venta Cerrada).
 * MODIFICACIÓN: Soporte para Maquinaria y Lotes de Materia Prima en subtablas y catálogo universal.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.5 (Vista Anidada Jerárquica Master-Detail en Prospectos)
 */

$page_title = "Panel de Seguimiento | CRM Ventas";
require_once '../config/db.php';

$total_leads = $pdo->query("SELECT COUNT(*) FROM prospectos")->fetchColumn() ?: 0;

$sql_filtro_prod = "SELECT DISTINCT p.nombre 
                    FROM productos p 
                    INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria 
                    WHERE c.id_categoria != 4 
                      AND c.nombre_categoria NOT LIKE '%refaccion%'
                    ORDER BY c.id_categoria ASC, p.nombre ASC";
$productos_filtro = $pdo->query($sql_filtro_prod)->fetchAll(PDO::FETCH_COLUMN) ?: [];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<style>
    td.details-control {
        text-align: center;
        cursor: pointer;
        color: #dc3545;
        font-size: 1.2rem;
    }
    td.details-control i {
        transition: color 0.25s ease, transform 0.25s ease;
        display: inline-block;
    }
    tr.shown td.details-control i {
        color: #6c757d;
    }
    .sub-table-wrapper {
        display: none;
    }
    .sub-table-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        box-shadow: inset 0 3px 6px rgba(0,0,0,0.04);
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-funnel"></i> Control de Prospectos a Clientes</h1>
        <p class="text-muted small">Panel comercial jerárquico de prospectos y cotizaciones emitidas.</p>
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
        <div class="col-auto" style="width: 22%;">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Buscar Prospecto...">
            </div>
        </div>
        <div class="col-auto">
            <select id="filterCanal" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 180px;">
                <option value="">Todos los Canales</option>
                <option value="Página Web">Página Web</option>
                <option value="WhatsApp">WhatsApp</option>
                <option value="Facebook">Facebook</option>
                <option value="YouTube">YouTube</option>
                <option value="Recomendación">Recomendación</option>
            </select>
        </div>
        <div class="col-auto">
            <select id="filterEquipo" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 220px;">
                <option value="">Todos los Intereses / Productos</option>
                <optgroup label="Interés Inicial">
                    <option value="Maquinaria">Maquinaria</option>
                    <option value="Materia Prima">Materia Prima</option>
                </optgroup>
                <optgroup label="Catálogo Oficial">
                    <?php foreach ($productos_filtro as $prod_nom): ?>
                        <option value="<?= htmlspecialchars($prod_nom) ?>"><?= htmlspecialchars($prod_nom) ?></option>
                    <?php endforeach; ?>
                </optgroup>
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

        <div class="col-auto">
            <a href="registrar_prospecto.php" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm py-2">
                <i class="bi bi-person-plus-fill me-1"></i> Registrar Prospecto
            </a>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaLeads" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th style="width: 40px;"></th>
                    <th>Fecha Registro</th>
                    <th>Prospecto / Canal</th>
                    <th>Contacto Directo</th>
                    <th>Ubicación</th>
                    <th>Interés Registrado</th>
                    <th class="text-center" style="width: 130px;">Estatus Venta</th>
                    <th class="text-center" style="width: 120px;">Historial</th>
                    <th class="text-center" style="width: 130px;">Semáforo</th>
                    <th class="text-center" style="width: 110px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Consulta agrupada por prospecto único
                $sql_prospectos = "SELECT f.*, p.id_prospecto, p.status_comercial, p.fecha_ultimo_contacto,
                                          COUNT(c.id_cotizacion) AS total_cotizaciones
                                   FROM prospectos p
                                   INNER JOIN formulario f ON p.id_formulario = f.id_formulario
                                   LEFT JOIN cotizacion c ON p.id_prospecto = c.id_prospecto
                                   GROUP BY p.id_prospecto
                                   ORDER BY f.fecha_registro DESC";
                
                $stmt_p = $pdo->query($sql_prospectos);
                while ($lead = $stmt_p->fetch(PDO::FETCH_ASSOC)):
                    $id_prospecto = (int)$lead['id_prospecto'];

                    // Consulta de todas las cotizaciones de este prospecto
                    $sql_sub = "SELECT c.*, 
                                       prod.nombre AS producto_nombre,
                                       (SELECT COUNT(*) FROM cotizacion_detalle cd WHERE cd.id_cotizacion = c.id_cotizacion) AS total_partidas
                                FROM cotizacion c
                                LEFT JOIN productos prod ON c.id_producto = prod.id_producto
                                WHERE c.id_prospecto = :id_prospecto
                                ORDER BY c.fecha_emision DESC, c.id_cotizacion DESC";
                    $stmt_sub = $pdo->prepare($sql_sub);
                    $stmt_sub->execute([':id_prospecto' => $id_prospecto]);
                    $sub_cotizaciones = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

                    // Formatear nombres legibles de cada cotización en subtabla
                    foreach ($sub_cotizaciones as &$sc) {
                        if (!empty($sc['producto_nombre'])) {
                            $sc['item_descripcion'] = $sc['producto_nombre'];
                        } elseif ((int)$sc['total_partidas'] > 0) {
                            $sc['item_descripcion'] = 'Lote Materia Prima (' . $sc['total_partidas'] . ' Partidas)';
                        } else {
                            $sc['item_descripcion'] = 'Cotización General DEMEX';
                        }
                    }
                    unset($sc);

                    $interes_crudo = trim($lead['maquina_interes'] ?? '');
                    $badge_interes = '';
                    if ($interes_crudo === 'Maquinaria') {
                        $badge_interes = '<span class="badge py-1.5 px-2.5 fw-semibold" style="background-color: #F8F9FA; color: #495057; border: 1px solid #DEE2E6; border-radius: 6px; font-size: 0.75rem;"><i class="bi bi-gear-wide-connected me-1 text-danger"></i>Maquinaria</span>';
                    } elseif ($interes_crudo === 'Materia Prima') {
                        $badge_interes = '<span class="badge py-1.5 px-2.5 fw-semibold" style="background-color: #E3F2FD; color: #0D47A1; border: 1px solid #BBDEFB; border-radius: 6px; font-size: 0.75rem;"><i class="bi bi-box-seam me-1"></i>Materia Prima</span>';
                    } elseif (!empty($interes_crudo)) {
                        $badge_interes = '<span class="badge bg-light text-dark border py-1.5 px-2.5 fw-semibold" style="border-radius: 6px; font-size: 0.75rem;">' . htmlspecialchars($interes_crudo) . '</span>';
                    } else {
                        $badge_interes = '<span class="badge text-muted border bg-light py-1.5 px-2.5 fw-normal" style="border-radius: 6px; font-size: 0.75rem;"><em>Sin Definir</em></span>';
                    }
                ?>
                <tr class="row-lead-master" 
                    data-id-prospecto="<?= $id_prospecto ?>"
                    data-origen="<?= htmlspecialchars($lead['canal_origen']) ?>" 
                    data-equipo="<?= htmlspecialchars(!empty($interes_crudo) ? $interes_crudo : 'Sin Definir') ?>" 
                    data-child-data="<?= htmlspecialchars(json_encode($sub_cotizaciones)) ?>"
                    data-status-venta="<?= htmlspecialchars($lead['status_comercial']) ?>"
                    data-fecha-consulta="<?= htmlspecialchars($lead['fecha_ultimo_contacto']) ?>"
                    data-urgente="0"
                    data-atencion="0"
                    data-encurso="0"> 
                    
                    <td class="details-control fw-bold">
                        <?php if (count($sub_cotizaciones) > 0): ?>
                            <i class="bi bi-plus-circle-fill"></i>
                        <?php else: ?>
                            <i class="bi bi-circle text-muted" style="opacity: 0.3; font-size: 0.9rem;" title="Sin cotizaciones"></i>
                        <?php endif; ?>
                    </td>
                    <td class="small fw-semibold text-secondary">
                        <?= date('d/m/Y g:i A', strtotime($lead['fecha_registro'])) ?>
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
                        <?= $badge_interes ?>
                    </td>
                    <td class="text-center col-status-badge">
                        <?php if ($lead['status_comercial'] === 'Venta Cerrada'): ?>
                            <span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Venta Cerrada</span>
                        <?php elseif ($lead['status_comercial'] === 'Cotizado'): ?>
                            <span class="badge" style="background-color: #FFFDE7; color: #F57F17; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Cotizado</span>
                        <?php else: ?>
                            <span class="badge" style="background-color: #E3F2FD; color: #0D47A1; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Consultado</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="text-center">
                        <?php if (count($sub_cotizaciones) > 0): ?>
                            <span class="badge bg-danger rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.75rem;"><?= count($sub_cotizaciones) ?> Doc(s)</span>
                        <?php else: ?>
                            <span class="text-muted small"><em>0 Docs</em></span>
                        <?php endif; ?>
                    </td>

                    <td class="text-center col-semaforo-master"></td>

                    <td class="text-center">
                        <a href="cotizaciones.php?id_prospecto=<?= $id_prospecto ?>" class="btn btn-sm btn-outline-danger border-0" title="Nueva Cotización">
                            <i class="bi bi-file-earmark-plus-fill fs-5"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CERRAR VENTA -->
<div class="modal fade" id="modalLiberarVenta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Desglose de Cierre de Venta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formConfirmarVenta">
                <input type="hidden" id="liberar_id_prospecto" name="id_prospecto">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">El prospecto se convertirá en Cliente formal y se transferirá al módulo de cartera y recompras.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small">Fecha Exacta de Compra <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="liberar_fecha_compra" name="fecha_compra" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold text-dark small">Observaciones Especiales del Cierre</label>
                        <textarea class="form-control small text-muted" id="liberar_observaciones" name="observaciones_venta" rows="3" placeholder="Ej. Anticipo liquidado por transferencia, entrega en mostrador..." style="font-size: 0.82rem; resize: none;"></textarea>
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

<!-- MODAL VISUALIZAR DETALLES -->
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
                    <p class="text-muted small mt-2">Consultando servidor corporativo DEMEX central...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
const formatoMXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

// Renderizado de la Subtabla Anidada de Cotizaciones
function formatChildRow(d, idProspecto) {
    if (!d || d.length === 0) {
        return `<div class="sub-table-wrapper p-3 bg-light text-center small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Este prospecto aún no tiene cotizaciones generadas.
                </div>`;
    }

    let html = `<div class="sub-table-wrapper">
                  <div class="sub-table-container">
                    <table class="table table-sm table-bordered bg-white m-0 small align-middle">
                        <thead class="table-dark">
                            <tr style="font-size:0.75rem;">
                                <th>Folio / Emisión</th>
                                <th>Vencimiento</th>
                                <th>Concepto Cotizado</th>
                                <th class="text-center">Vigencia</th>
                                <th class="text-end">Importe Total</th>
                                <th class="text-center">Semáforo</th>
                                <th class="text-center" style="width:140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>`;
    
    d.forEach(function(cot) {
        let fEmision = cot.fecha_emision ? cot.fecha_emision.split('-').reverse().join('/') : 'N/D';
        let fVence = cot.fecha_vencimiento ? cot.fecha_vencimiento.split('-').reverse().join('/') : 'N/D';

        let badgeCot = (cot.status_cotizacion === 'Vencida') ? 
            `<span class="badge bg-danger animate__animated animate__flash animate__infinite" style="font-size:0.7rem;"><i class="bi bi-calendar-x"></i> Vencida</span>` : 
            `<span class="badge bg-success" style="font-size:0.7rem;"><i class="bi bi-calendar-check"></i> Vigente</span>`;

        let btnAcciones = `
            <div class="btn-group btn-group-sm">
                <button type="button" onclick="verDetallesCotizacion(${cot.id_cotizacion})" class="btn btn-outline-info border-0" title="Ver Detalle"><i class="bi bi-eye-fill fs-5"></i></button>
                <a href="editar_cotizacion.php?id_cotizacion=${cot.id_cotizacion}" class="btn btn-outline-warning border-0" title="Editar"><i class="bi bi-pencil-square fs-5"></i></a>
                <button type="button" class="btn btn-outline-success border-0" onclick="cerrarOperationComercial(${idProspecto})" title="Cerrar Venta"><i class="bi bi-check-circle-fill fs-5"></i></button>
            </div>
        `;

        html += `<tr class="sub-row-cot-item" data-recordatorio="${cot.fecha_recordatorio}" data-status-cotiz="${cot.status_cotizacion}">
                    <td class="fw-bold text-danger">#${cot.id_cotizacion} <small class="text-secondary fw-normal d-block" style="font-size:0.72rem;">${fEmision}</small></td>
                    <td class="text-muted fw-semibold">${fVence}</td>
                    <td class="fw-bold text-dark">${cot.item_descripcion}</td>
                    <td class="text-center">${badgeCot}</td>
                    <td class="text-end fw-bold text-dark">${formatoMXN.format(parseFloat(cot.precio_pactado) + parseFloat(cot.costo_envio || 0))}</td>
                    <td class="text-center sub-col-semaforo"></td>
                    <td class="text-center">${btnAcciones}</td>
                 </tr>`;
    });

    html += `   </tbody>
            </table>
          </div>
         </div>`;
    return html;
}

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'success') {
        Swal.fire({
            title: '¡Operación Exitosa!',
            text: 'El prospecto y su cotización se sincronizaron correctamente.',
            icon: 'success',
            confirmButtonColor: '#198754'
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }

    function procesarKPIsYSemaforos() {
        const d = new Date();
        const hoyStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const ahora = d.getTime();

        let countEnCurso = 0, countAtencion = 0, countUrgentes = 0;
        let recordatoriosHoy = [];

        $('.row-lead-master').each(function() {
            const $fila =$(this);
            const statusVenta = $fila.attr('data-status-venta');
            const fechaConsultaStr = $fila.attr('data-fecha-consulta');
            const childDataStr = $fila.attr('data-child-data');
            const subCots = childDataStr ? JSON.parse(childDataStr) : [];
            const celdaSemaforo = $fila.find('.col-semaforo-master');

            if (statusVenta === 'Venta Cerrada') {
                celdaSemaforo.html('<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-check-circle-fill me-1"></i> Al día</span>');
                $fila.attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '0');
                return;
            }

            if (subCots.length === 0) {
                // Sin cotización: evalúa inactividad desde primer contacto
                const fConsulta = new Date(fechaConsultaStr);
                const diasInactivo = Math.floor((ahora - fConsulta.getTime()) / (1000 * 60 * 60 * 24));
                if (diasInactivo > 5) {
                    celdaSemaforo.html('<span class="badge bg-danger text-white px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-fire me-1"></i> Urgente</span>');
                    $fila.attr('data-urgente', '1').attr('data-atencion', '0').attr('data-encurso', '0');
                    countUrgentes++;
                } else {
                    celdaSemaforo.html('<span class="badge bg-primary text-white px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> En Curso</span>');
                    $fila.attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '1');
                    countEnCurso++;
                }
            } else {
                // Con cotizaciones: toma la alerta de mayor severidad
                let esUrgente = false;
                let esAtencion = false;

                subCots.forEach(function(cot) {
                    if (cot.status_cotizacion === 'Vencida' || (cot.fecha_recordatorio && cot.fecha_recordatorio < hoyStr)) {
                        esUrgente = true;
                    } else if (cot.fecha_recordatorio === hoyStr) {
                        esAtencion = true;
                        const nom = $fila.find('.fw-bold.text-dark').text().trim();
                        recordatoriosHoy.push(`• <strong>${nom}</strong> (Cotización #${cot.id_cotizacion})`);
                    }
                });

                if (esUrgente) {
                    celdaSemaforo.html('<span class="badge bg-danger text-white px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-fire me-1"></i> Urgente</span>');
                    $fila.attr('data-urgente', '1').attr('data-atencion', '0').attr('data-encurso', '0');
                    countUrgentes++;
                } else if (esAtencion) {
                    celdaSemaforo.html('<span class="badge bg-warning text-dark px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Atención</span>');
                    $fila.attr('data-urgente', '0').attr('data-atencion', '1').attr('data-encurso', '0');
                    countAtencion++;
                } else {
                    celdaSemaforo.html('<span class="badge bg-primary text-white px-3 py-1.5" style="font-weight: 600; border-radius: 8px;"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> En Curso</span>');
                    $fila.attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '1');
                    countEnCurso++;
                }
            }
        });

        $('#kpi-encurso').text(countEnCurso);
        $('#kpi-pendientes').text(countAtencion);
        $('#kpi-urgentes').text(countUrgentes);

        if (recordatoriosHoy.length > 0 && !window.alertaMostrada) {
            window.alertaMostrada = true;
            Swal.fire({
                title: `<i class="bi bi-bell-fill text-danger animate__animated animate__swing animate__infinite" style="display:inline-block;"></i> Tienes ${recordatoriosHoy.length} seguimiento(s) hoy`,
                html: `<div class="text-start mt-2 small text-muted">Debes dar seguimiento hoy a los siguientes prospectos:</div>
                       <div class="text-start mt-3 p-3 bg-light rounded border border-dark" style="max-height: 200px; overflow-y: auto; font-size: 0.9rem; line-height: 1.5;">
                           ${recordatoriosHoy.join('<br>')}
                       </div>`,
                icon: 'info',
                confirmButtonColor: '#c72f3e',
                confirmButtonText: 'Continuar',
                backdrop: false,
                position: 'top-end',
                showCloseButton: true,
                customClass: { popup: 'shadow-lg border-start border-4 border-danger' }
            });
        }
    }

    var table = $('#tablaLeads').DataTable({
        "language": { "emptyTable": "No hay datos", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "0 registros", "infoFiltered": "(filtrado de _MAX_)", "zeroRecords": "Sin coincidencias", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtip', 
        "pageLength": 10, 
        "responsive": true, 
        "ordering": false, 
        "drawCallback": function() { procesarKPIsYSemaforos(); }
    });

    // Control del botón [+] para desplegar subtabla
    $('#tablaLeads tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            tr.next().find('.sub-table-wrapper').slideUp(200, function() {
                row.child.hide();
                tr.removeClass('shown');
            });
            $(this).html('<i class="bi bi-plus-circle-fill"></i>');
        } else {
            var childDataStr = tr.attr('data-child-data');
            var idProspecto = tr.attr('data-id-prospecto');
            var childData = childDataStr ? JSON.parse(childDataStr) : [];

            row.child(formatChildRow(childData, idProspecto)).show();
            tr.addClass('shown');
            $(this).html('<i class="bi bi-dash-circle-fill"></i>');
            
            tr.next().find('.sub-table-wrapper').slideDown(250);
            
            const hoyStr = new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0');
            
            tr.next().find('.sub-row-cot-item').each(function() {
                const rec = $(this).data('recordatorio');
                const statC = $(this).data('status-cotiz');
                const cellSem = $(this).find('.sub-col-semaforo');

                if (statC === 'Vencida' || (rec && rec < hoyStr)) {
                    cellSem.html('<span class="badge bg-danger animate__animated animate__headShake animate__infinite"><i class="bi bi-fire"></i> Urgente</span>');
                } else if (rec === hoyStr) {
                    cellSem.html('<span class="badge bg-warning text-dark animate__animated animate__flash animate__infinite"><i class="bi bi-exclamation-triangle-fill"></i> Atención</span>');
                } else {
                    cellSem.html('<span class="badge bg-primary text-white"><i class="bi bi-circle-fill" style="font-size:0.5rem;"></i> En Curso</span>');
                }
            });
        }
    });

    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    $('#filterCanal').on('change', function() { table.column(2).search(this.value).draw(); });
    $('#filterEquipo').on('change', function() { table.column(5).search(this.value).draw(); });
    
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var row = $(table.row(dataIndex).node());
        var cumpleUrgente = !$('#btnFiltrarCriticos').is(':checked') || row.attr('data-urgente') === '1';
        var cumplePendiente = !$('#btnFiltrarPendientes').is(':checked') || row.attr('data-atencion') === '1';
        var cumpleEnCurso = !$('#btnFiltrarEnCurso').is(':checked') || row.attr('data-encurso') === '1';
        return cumpleUrgente && cumplePendiente && cumpleEnCurso;
    });

    $('#btnFiltrarCriticos, #btnFiltrarPendientes, #btnFiltrarEnCurso').on('change', function() { table.draw(); });

    procesarKPIsYSemaforos();

    // Confirmación y Cierre de Venta
    $('#formConfirmarVenta').on('submit', function(e) {
        e.preventDefault();
        const idProspecto = $('#liberar_id_prospecto').val();
        const fechaCompra = $('#liberar_fecha_compra').val();
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
                    }).then(() => {
                        window.location.reload();
                    });
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
    $('#modalLiberarVenta').appendTo("body").modal('show');
}

function verDetallesCotizacion(idCotizacion) {
    if (!idCotizacion || idCotizacion === '0' || idCotizacion === 0) {
        Swal.fire({
            title: 'Sin Cotización',
            text: 'Este prospecto aún no cuenta con una cotización registrada.',
            icon: 'info',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    $_cuerpo =$('#cuerpoModalCotizacion');
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