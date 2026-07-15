<?php
/**
 * ARCHIVO: recompras_crm.php
 * DESCRIPCIÓN: Panel de Control de Recompras CRM con Vista Anidada Jerárquica.
 * Agrupa las cotizaciones por Cliente Único y despliega sub-tablas con transiciones fluidas.
 * ORDENAMIENTO: Clasificación por Prioridad de Alerta Master (Urgente > Pendiente > En Curso > Cerrado).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 8.5 (Transiciones Fluidas, Badges Master Clientes y Ordenamiento Inteligente)
 */

$page_title = "Pipeline de Recompras | CRM Ventas";
require_once '../config/db.php';

/**
 * KPIs - INDICADORES CLAVE DE DESEMPEÑO
 */
$total_recompras = $pdo->query("SELECT COUNT(*) FROM cotizacion WHERE id_cliente IS NOT NULL")->fetchColumn();

$maquinas_reales = ['DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<style>
    /* Estilos pulidos para el control de expansión con transiciones fluidas */
    td.details-control {
        text-align: center;
        cursor: pointer;
        color: #dc3545;
        font-size: 1.25rem;
    }
    td.details-control i {
        transition: color 0.25s ease, transform 0.25s ease;
        display: inline-block;
    }
    tr.shown td.details-control i {
        color: #6c757d;
    }
    .sub-table-wrapper {
        display: none; /* Se maneja la animación fluida por JS slideDown */
    }
    .sub-table-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        box-shadow: inset 0 3px 6px rgba(0,0,0,0.05);
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-arrow-repeat"></i> Panel de Recompras</h1>
        <p class="text-muted small">Seguimiento comercial de cotizaciones emitidas a clientes consolidados en cartera.</p>
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
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Buscar Cliente o Razón Social...">
            </div>
        </div>
        <div class="col-auto">
            <select id="filterEquipo" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 240px;">
                <option value="">Todos los Equipos Cotizados</option>
                <?php foreach ($maquinas_reales as $maquina): ?>
                    <option value="<?= htmlspecialchars($maquina) ?>"><?= htmlspecialchars($maquina) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto d-flex gap-3">
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="btnFiltrarCriticos" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" style="cursor:pointer;" for="btnFiltrarCriticos">Alertas Urgentes</label>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="btnFiltrarPendientes" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" style="cursor:pointer;" for="btnFiltrarPendientes">Cotizaciones Pendientes</label>
            </div>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaRecompras" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th style="width: 45px;"></th>
                    <th>Razon Social / Cliente</th>
                    <th>Canal Perfil</th>
                    <th>Contacto Directo</th>
                    <th>Ubicación</th>
                    <th class="text-center" style="width: 140px;">Status Venta</th>
                    <th class="text-center" style="width: 140px;">Cotizaciones Realizadas</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // MODIFICADO: Estructura SQL con cálculo del índice de prioridad para ordenación nativa del listado master
                $sql_clientes = "SELECT c.id_cliente, c.nombre_cliente, c.correo, c.telefono, c.ubicacion, c.tipo_cliente,
                                        COUNT(cot.id_cotizacion) as total_activas,
                                        MIN(CASE 
                                            WHEN cot.estatus_seguimiento = 'Liberada' THEN 4
                                            WHEN cot.estatus_seguimiento = 'Cancelada' THEN 5
                                            WHEN cot.status_cotizacion = 'Vencida' OR cot.fecha_recordatorio < CURDATE() THEN 1
                                            WHEN cot.fecha_recordatorio = CURDATE() THEN 2
                                            ELSE 3
                                        END) as orden_prioridad
                                 FROM clientes c
                                 INNER JOIN cotizacion cot ON c.id_cliente = cot.id_cliente
                                 GROUP BY c.id_cliente
                                 ORDER BY orden_prioridad ASC, c.nombre_cliente ASC";
                
                $stmt_cli = $pdo->query($sql_clientes);
                while ($cli = $stmt_cli->fetch(PDO::FETCH_ASSOC)):
                    $id_cliente = $cli['id_cliente'];

                    $sql_sub_cot = "SELECT cot.*, m.modelo AS maquina_nombre 
                                    FROM cotizacion cot
                                    INNER JOIN maquinaria m ON cot.id_maquina = m.id_maquina
                                    WHERE cot.id_cliente = :id_cliente
                                    ORDER BY 
                                        CASE 
                                            WHEN cot.estatus_seguimiento = 'Liberada' THEN 4
                                            WHEN cot.estatus_seguimiento = 'Cancelada' THEN 5
                                            WHEN cot.status_cotizacion = 'Vencida' OR cot.fecha_recordatorio < CURDATE() THEN 1
                                            WHEN cot.fecha_recordatorio = CURDATE() THEN 2
                                            ELSE 3
                                        END ASC, cot.fecha_emision DESC";
                    $stmt_sub = $pdo->prepare($sql_sub_cot);
                    $stmt_sub->execute([':id_cliente' => $id_cliente]);
                    $sub_cotizaciones = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <tr class="row-cliente-master" data-child-data="<?= htmlspecialchars(json_encode($sub_cotizaciones)) ?>" data-prioridad="<?= $cli['orden_prioridad'] ?>">
                    <td class="details-control fw-bold"><i class="bi bi-plus-circle-fill"></i></td>
                    <td>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($cli['nombre_cliente']) ?></div>
                    </td>
                    <td>
                        <span class="badge text-uppercase text-muted border bg-white px-2.5 py-1" style="font-size: 0.65rem; letter-spacing: 0.5px; font-weight: 600; border-radius: 4px;">
                            <?= htmlspecialchars($cli['tipo_cliente'] ?? 'Publico General') ?>
                        </span>
                    </td>
                    <td>
                        <?php if(!empty($cli['correo'])): ?>
                            <div class="small text-dark mb-1"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($cli['correo']) ?></div>
                        <?php endif; ?>
                        <div class="small">
                            <a href="https://wa.me/52<?= $cli['telefono'] ?>" target="_blank" class="text-success text-decoration-none fw-semibold d-inline-flex align-items-center">
                                <i class="bi bi-whatsapp me-1 fs-6"></i><?= htmlspecialchars($cli['telefono']) ?>
                            </a>
                        </div>
                    </td>
                    <td class="small text-secondary">
                        <i class="bi bi-geo-alt-fill text-muted me-1"></i><?= htmlspecialchars(!empty($cli['ubicacion']) ? $cli['ubicacion'] : 'Sin registrar') ?>
                    </td>
                    <!-- NUEVO: Celda Master de Identificación Comercial Inmediata al Entrar -->
                    <td class="text-center col-master-badge-alerta"></td>
                    <td class="text-center">
                        <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.8rem;"><?= $cli['total_activas'] ?> Docs</span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalLiberarRecompra" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i> Liberación Comercial de Recompra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formConfirmarRecompra">
                <input type="hidden" id="liberar_id_cotizacion" name="id_cotizacion">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">La recompra se autorizará de forma inmediata y se inyectará al historial de facturación del cliente en la cartera.</p>
                    <div class="bg-light p-3 rounded mb-3 border">
                        <div class="small"><strong>Cliente:</strong> <span id="lbl_lib_cliente"></span></div>
                        <div class="small"><strong>Maquinaria:</strong> <span id="lbl_lib_maquina"></span></div>
                        <div class="small"><strong>Cantidad Total:</strong> <span id="lbl_lib_cantidad"></span> pieza(s)</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold text-dark small">Observaciones Especiales del Cierre</label>
                        <textarea class="form-control small text-muted" id="liberar_observaciones" name="observaciones" rows="3" placeholder="Ej. Pago por transferencia bancaria aprobado, entrega inmediata..." style="font-size: 0.82rem; resize: none;"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary px-3 fw-bold small" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold small"><i class="bi bi-send-check me-1"></i> Autorizar y Cerrar Venta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetallesCotizacion" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 12px;">
            <div class="modal-header bg-danger text-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold">Desglose Técnico de Recompra</h5>
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
const formatoMXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

function formatChildRow(d) {
    let html = `<div class="sub-table-wrapper">
                  <div class="sub-table-container">
                    <table class="table table-sm table-bordered bg-white m-0 small align-middle">
                        <thead class="table-dark">
                            <tr style="font-size:0.75rem;">
                                <th>Fecha Emisión</th>
                                <th>Fecha Vencimiento</th>
                                <th>Equipo Cotizado</th>
                                <th class="text-center">Estatus Seg.</th>
                                <th class="text-center">Estatus Promoción</th>
                                <th class="text-center">Semáforo</th>
                                <th class="text-center" style="width:140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>`;
    
    d.forEach(function(cot) {
        let fEmision = cot.fecha_emision.split('-').reverse().join('/');
        let fVence = cot.fecha_vencimiento.split('-').reverse().join('/');
        
        let badgeSeg = `<span class="badge" style="background-color: #E3F2FD; color: #0D47A1; font-weight: 600;">${cot.estatus_seguimiento}</span>`;
        if (cot.estatus_seguimiento === 'Liberada') badgeSeg = `<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600;">Liberada</span>`;
        if (cot.estatus_seguimiento === 'Cancelada') badgeSeg = `<span class="badge" style="background-color: #FFE082; color: #E65100; font-weight: 600;">Cancelada</span>`;

        let badgeCot = cot.status_cotizacion === 'Vencida' ? 
            `<span class="badge bg-danger animate__animated animate__flash animate__infinite"><i class="bi bi-calendar-x"></i> Vencida</span>` : 
            `<span class="badge bg-success"><i class="bi bi-calendar-check"></i> Vigente</span>`;

        let btnAcciones = '';
        if (cot.estatus_seguimiento === 'Liberada' || cot.estatus_seguimiento === 'Cancelada') {
            btnAcciones = `<button type="button" onclick="verDetallesCotizacion(${cot.id_cotizacion})" class="btn btn-sm btn-outline-info border-0"><i class="bi bi-eye-fill fs-5"></i></button>`;
        } else {
            btnAcciones = `<div class="btn-group btn-group-sm">
                            <button type="button" onclick="verDetallesCotizacion(${cot.id_cotizacion})" class="btn btn-outline-info border-0" title="Ver Detalle"><i class="bi bi-eye-fill fs-5"></i></button>
                            <a href="editar_cotizacion.php?id_cotizacion=${cot.id_cotizacion}" class="btn btn-outline-warning border-0" title="Editar"><i class="bi bi-pencil-square fs-5"></i></a>
                            <button type="button" class="btn btn-outline-success border-0" onclick="cerrarOperacionRecompra(${cot.id_cotizacion}, '${escape(cot.cliente_nombre || '')}', '${escape(cot.maquina_nombre)}', ${cot.cantidad})" title="Liberar Venta"><i class="bi bi-check-circle-fill fs-5"></i></button>
                           </div>`;
        }

        html += `<tr class="sub-row-cot-item" data-recordatorio="${cot.fecha_recordatorio}" data-status-cotiz="${cot.status_cotizacion}" data-status-seg="${cot.estatus_seguimiento}" data-equipo="${cot.maquina_nombre}">
                    <td class="fw-semibold text-secondary">${fEmision}</td>
                    <td class="text-muted fw-semibold">${fVence}</td>
                    <td class="fw-bold text-dark">${cot.maquina_nombre} <span class="text-muted small">(${cot.cantidad} Pz)</span></td>
                    <td class="text-center">${badgeSeg}</td>
                    <td class="text-center">${badgeCot}</td>
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
        Swal.fire({ title: '¡Cambios Guardados!', text: 'La cotización de recompra y el expediente se actualizaron exitosamente.', icon: 'success', confirmButtonColor: '#198754' });
    }

    // === MODIFICADO: PROCESADOR CENTRAL DE ETIQUETAS MASTER, KPIs Y ALERTAS ===
function procesarKPIsYAlertas() {
        const d = new Date();
        const hoyStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        
        let countEnCurso = 0, countAtencion = 0, countUrgentes = 0;
        let recordatoriosHoyArray = [];

        $('.row-cliente-master').each(function() {
            const dataStr = $(this).attr('data-child-data');
            if(!dataStr) return;
            const subCots = JSON.parse(dataStr);
            const nombreCliente = $(this).find('.fw-bold.text-dark').text().trim();
            const contenedorBadgeMaster = $(this).find('.col-master-badge-alerta');

            let tieneActivas = false;

            subCots.forEach(function(cot) {
                if(cot.estatus_seguimiento === 'Liberada' || cot.estatus_seguimiento === 'Cancelada') return;
                tieneActivas = true;

                if (cot.status_cotizacion === 'Vencida' || cot.fecha_recordatorio < hoyStr) {
                    countUrgentes++;
                } else if (cot.fecha_recordatorio === hoyStr) {
                    countAtencion++;
                    recordatoriosHoyArray.push(`• <strong>${nombreCliente}</strong> (${cot.maquina_nombre})`);
                } else {
                    countEnCurso++;
                }
            });

            // MODIFICADO: Remoción de parpadeos. Etiqueta unificada azul con ícono de info para seguimientos activos
            if (!tieneActivas) {
                contenedorBadgeMaster.html('<span class="badge px-3 py-1.5" style="background-color: #E8F5E9; color: #2E7D32; font-weight:700;"><i class="bi bi-check-circle-fill me-1"></i> Cerrado</span>');
            } else {
                contenedorBadgeMaster.html('<span class="badge bg-primary text-white px-3 py-1.5" style="font-weight:700;">Pendiente</span>');
            }
        });

        $('#kpi-encurso').text(countEnCurso);
        $('#kpi-pendientes').text(countAtencion);
        $('#kpi-urgentes').text(countUrgentes);

        if (recordatoriosHoyArray.length > 0 && !window.alertLanzado) {
            window.alertLanzado = true;
            Swal.fire({
                title: `<i class="bi bi-bell-fill text-danger animate__animated animate__swing animate__infinite" style="display:inline-block;"></i> Tienes ${recordatoriosHoyArray.length} Seguimientos Hoy`,
                html: `<div class="text-start mt-2 small text-muted">Es momento de contactar a los siguientes clientes de recompras:</div>
                       <div class="text-start mt-3 p-3 bg-light rounded border border-dark" style="max-height: 200px; overflow-y: auto; font-size: 0.9rem; line-height: 1.5;">
                         ${recordatoriosHoyArray.join('<br>')}
                       </div>`,
                icon: 'info', confirmButtonColor: '#dc3545', confirmButtonText: 'Continuar',
                backdrop: false, position: 'top-end', showCloseButton: true,
                customClass: { popup: 'shadow-lg border-start border-4 border-danger' }
            });
        }
    }

    // Configuración nativa del DataTables ordenando rigurosamente por el data-attribute de prioridad
    var table = $('#tablaRecompras').DataTable({
        "language": { "emptyTable": "No hay datos", "info": "Mostrando _START_ a _END_ de _TOTAL_", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtip', 
        "pageLength": 10, 
        "responsive": true, 
        "ordering": true,
        "order": [[5, 'asc']] // Ordena automáticamente por la columna del badge de Alerta Prioritaria
    });

    procesarKPIsYAlertas();

    // MODIFICADO: Animación fluida slideDown/slideUp al presionar el acordeón
    $('#tablaRecompras tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            tr.next().find('.sub-table-wrapper').slideUp(200, function() {
                row.child.hide();
                tr.removeClass('shown');
            });
            $(this).html('<i class="bi bi-plus-circle-fill"></i>');
        } else {
            var childData = JSON.parse(tr.attr('data-child-data'));
            row.child(formatChildRow(childData)).show();
            tr.addClass('shown');
            $(this).html('<i class="bi bi-dash-circle-fill"></i>');
            
            // Animación suave de apertura
            tr.next().find('.sub-table-wrapper').slideDown(250);
            
            const d = new Date();
            const hoyStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            
            tr.next().find('.sub-row-cot-item').each(function() {
                const rec = $(this).data('recordatorio');
                const statC = $(this).data('status-cotiz');
                const statS = $(this).data('status-seg');
                const cellSem = $(this).find('.sub-col-semaforo');

                if (statS === 'Liberada' || statS === 'Cancelada') {
                    cellSem.html('<span class="badge bg-secondary">Cerrado</span>');
                } else if (statC === 'Vencida' || rec < hoyStr) {
                    cellSem.html('<span class="badge bg-danger animate__animated animate__headShake animate__infinite"><i class="bi bi-fire"></i> Urgente</span>');
                    $(this).addClass('table-danger-sutil');
                } else if (rec === hoyStr) {
                    cellSem.html('<span class="badge bg-warning text-dark animate__animated animate__flash animate__infinite"><i class="bi bi-exclamation-triangle-fill"></i> Pendiente</span>');
                    $(this).addClass('table-warning-sutil');
                } else {
                    cellSem.html('<span class="badge bg-primary text-white"><i class="bi bi-circle-fill" style="font-size:0.5rem;"></i> En Curso</span>');
                }
            });
        }
    });

    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var tr = $(table.row(dataIndex).node());
        var childDataStr = tr.attr('data-child-data');
        if(!childDataStr) return true;
        
        var subCots = JSON.parse(childDataStr);
        var filtroEquipo = $('#filterEquipo').val();
        var filtroUrgente = $('#btnFiltrarCriticos').is(':checked');
        var filtroPendiente = $('#btnFiltrarPendientes').is(':checked');

        const d = new Date();
        const hoyStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

        return subCots.some(function(cot) {
            var matchEquipo = !filtroEquipo || cot.maquina_nombre === filtroEquipo;
            var esUrgente = (cot.status_cotizacion === 'Vencida' || cot.fecha_recordatorio < hoyStr) && cot.estatus_seguimiento === 'En Seguimiento';
            var esPendiente = cot.fecha_recordatorio === hoyStr && cot.estatus_seguimiento === 'En Seguimiento';

            var matchUrgente = !filtroUrgente || esUrgente;
            var matchPendiente = !filtroPendiente || esPendiente;

            return matchEquipo && matchUrgente && matchPendiente;
        });
    });

    $('#filterEquipo, #btnFiltrarCriticos, #btnFiltrarPendientes').on('change', function() { table.draw(); });

    $('#formConfirmarRecompra').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../actions/liberar_recompra.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalLiberarRecompra').modal('hide');
                    Swal.fire({ title: '¡Venta Cerrada!', text: response.message, icon: 'success' }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ title: 'Error', text: response.message, icon: 'error' });
                }
            }
        });
    });
});

function cerrarOperacionRecompra(idCotizacion, clienteEscaped, maquinaEscaped, cantidad) {
    $('#liberar_id_cotizacion').val(idCotizacion);
    $('#lbl_lib_cliente').text(unescape(clienteEscaped));
    $('#lbl_lib_maquina').text(unescape(maquinaEscaped));
    $('#lbl_lib_cantidad').text(cantidad);
    $('#modalLiberarRecompra').appendTo("body").modal('show');
}

function verDetallesCotizacion(idCotizacion) {
    $_cuerpo = $('#cuerpoModalCotizacion');
    $_cuerpo.html(`<div class="text-center py-4"><div class="spinner-border text-danger" role="status"></div></div>`);
    $('#modalDetallesCotizacion').appendTo("body").modal('show');
    $.ajax({
        url: '../actions/obtener_detalles_cotizacion.php',
        method: 'GET',
        data: { id_cotizacion: idCotizacion },
        success: function(response) { $_cuerpo.html(response); }
    });
}
</script>