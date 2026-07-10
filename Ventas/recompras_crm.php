<?php
/**
 * ARCHIVO: recompras_crm.php
 * DESCRIPCIÓN: Panel de Control de Recompras CRM con Motor de Búsqueda Asíncrono.
 * Integra animaciones intermitentes en semáforos, filtros avanzados de DataTables y visor modal blindado.
 * ORDENAMIENTO: Clasificación por Prioridad Estricta de Semáforo (Urgente > Atención > En Curso).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 6.6 (Renderizado Dinámico de Perfil Comercial en la celda Cliente/Canal)
 */

$page_title = "Pipeline de Recompras | CRM Ventas";
require_once '../config/db.php';

/**
 * KPIs - INDICADORES CLAVE DE DESEMPEÑO (PHP Base)
 */
$total_recompras = $pdo->query("SELECT COUNT(*) FROM cotizacion WHERE id_cliente IS NOT NULL")->fetchColumn();

$maquinas_reales = ['DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

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
        <table id="tablaRecompras" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>Fecha Emisión</th>
                    <th>Cliente / Tipo</th>
                    <th>Contacto Directo</th>
                    <th>Ubicación</th>
                    <th>Equipo Cotizado</th>
                    <th class="text-center" style="width: 140px;">Estatus Seguimiento</th>
                    <th class="text-center" style="width: 140px;">Estatus Cotiz.</th> 
                    <th class="text-center" style="width: 130px;">Semáforo</th>
                    <th class="text-center" style="width: 150px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // MODIFICADO: Se añade 'c.tipo_cliente' a la selección para dinamizar el catálogo
                $sql = "SELECT cot.*, c.nombre_cliente, c.correo, c.telefono, c.ubicacion, c.tipo_cliente, m.modelo AS maquina_nombre,
                               DATEDIFF(CURDATE(), cot.fecha_emision) AS dias_transcurridos
                        FROM cotizacion cot
                        INNER JOIN clientes c ON cot.id_cliente = c.id_cliente
                        INNER JOIN maquinaria m ON cot.id_maquina = m.id_maquina
                        ORDER BY 
                            CASE 
                                WHEN cot.estatus_seguimiento = 'En Seguimiento' AND (cot.status_cotizacion = 'Vencida' OR DATEDIFF(CURDATE(), cot.fecha_emision) > 30) THEN 1
                                WHEN cot.estatus_seguimiento = 'En Seguimiento' AND DATEDIFF(CURDATE(), cot.fecha_emision) > 7 THEN 1
                                WHEN cot.estatus_seguimiento = 'En Seguimiento' AND DATEDIFF(CURDATE(), cot.fecha_emision) > 3 THEN 2
                                WHEN cot.estatus_seguimiento = 'En Seguimiento' AND DATEDIFF(CURDATE(), cot.fecha_emision) <= 3 THEN 3
                                WHEN cot.estatus_seguimiento = 'Liberada' THEN 4
                                ELSE 5
                            END ASC, 
                            cot.fecha_emision DESC";
                
                $stmt = $pdo->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $nombre_completo = $row['nombre_cliente'];
                    $estatus_real_cotizacion = $row['status_cotizacion'];
                    if ($row['estatus_seguimiento'] === 'En Seguimiento' && intval($row['dias_transcurridos']) > 30) {
                        $estatus_real_cotizacion = 'Vencida';
                    }
                ?>
                <tr class="row-recompra-item" 
                    data-equipo="<?= htmlspecialchars($row['maquina_nombre']) ?>" 
                    data-urgente="0"
                    data-atencion="0"
                    data-encurso="0"> 
                    <td class="small fw-semibold text-secondary"><?= date('d/m/Y', strtotime($row['fecha_emision'])) ?></td>
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($nombre_completo) ?></div>
                        <!-- MODIFICADO: Ahora imprime de forma dinámica el tipo_cliente de la base de datos (Distribuidor o Público General) -->
                        <span class="badge mt-1 text-uppercase text-muted border bg-white" style="font-size: 0.65rem; letter-spacing: 0.5px; font-weight: 500; padding: 0.2rem 0.4rem; border-radius: 4px;"><?= htmlspecialchars($row['tipo_cliente'] ?? 'Publico General') ?></span>
                    </td>
                    <td>
                        <?php if(!empty($row['correo'])): ?>
                            <div class="small text-dark mb-1"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($row['correo']) ?></div>
                        <?php endif; ?>
                        <div class="small mt-1">
                            <a href="https://wa.me/52<?= $row['telefono'] ?>" target="_blank" class="text-success text-decoration-none fw-semibold d-inline-flex align-items-center">
                                <i class="bi bi-whatsapp me-1 fs-6"></i><?= htmlspecialchars($row['telefono']) ?>
                            </a>
                        </div>
                    </td>
                    <td class="small text-secondary">
                        <i class="bi bi-geo-alt-fill text-muted me-1"></i><?= htmlspecialchars(!empty($row['ubicacion']) ? $row['ubicacion'] : 'Sin registrar') ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border py-1.5 px-2.5 fw-semibold" style="border-radius: 6px; font-size: 0.75rem;">
                            <?= htmlspecialchars($row['maquina_nombre']) ?> (<?= $row['cantidad'] ?> Pz)
                        </span>
                    </td>
                    <td class="text-center col-status-badge"></td>
                    
                    <td class="text-center col-cotizacion-badge" 
                        data-status-cotiz="<?= htmlspecialchars($estatus_real_cotizacion ?? '') ?>">
                    </td>

                    <td class="text-center col-semaforo" 
                        data-status-seg="<?= $row['estatus_seguimiento'] ?>"
                        data-status-cotizacion="<?= htmlspecialchars($estatus_real_cotizacion ?? '') ?>"
                        data-fecha-cotizacion="<?= htmlspecialchars($row['fecha_emision'] ?? '') ?>">
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm col-acciones-comerciales" 
                             data-id-cotizacion="<?= $row['id_cotizacion'] ?>"
                             data-cantidad="<?= $row['cantidad'] ?>"
                             data-cliente="<?= htmlspecialchars($nombre_completo) ?>"
                             data-maquina="<?= htmlspecialchars($row['maquina_nombre']) ?>"
                             data-status-seg="<?= $row['estatus_seguimiento'] ?>">
                        </div>
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
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'success') {
        Swal.fire({
            title: '¡Cambios Guardados!',
            text: 'La cotización de recompra y el expediente del cliente se actualizaron exitosamente.',
            icon: 'success',
            confirmButtonColor: '#198754',
            confirmButtonText: 'Entendido',
            showClass: { popup: 'animate__animated animate__fadeInDown' },
            hideClass: { popup: 'animate__animated animate__fadeOutUp' }
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    } else if (urlParams.get('msg') === 'error') {
        const descError = urlParams.get('desc') || 'No se pudo procesar la actualización del documento.';
        Swal.fire({
            title: 'Error de Edición',
            text: decodeURIComponent(descError),
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Revisar'
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }

    function calcularSemaforosComerciales() {
        const ahora = Date.now();
        let countEnCurso = 0, countAtencion = 0, countUrgentes = 0;

        $('.col-semaforo').each(function() {
            const statusSeg = $(this).data('status-seg');
            const statusCotiz = $(this).data('status-cotizacion');
            const fechaCotizStr = $(this).data('fecha-cotizacion');
            
            const fila = $(this).closest('tr');
            const contenedorAcciones = fila.find('.col-acciones-comerciales');
            const contenedorStatusBadge = fila.find('.col-status-badge');
            const contenedorCotizBadge = fila.find('.col-cotizacion-badge');
            
            const idCotizacion = contenedorAcciones.data('id-cotizacion');
            const cantidad = contenedorAcciones.data('cantidad');
            const cliente = contenedorAcciones.attr('data-cliente');
            const maquina = contenedorAcciones.attr('data-maquina');

            let statusBadgeHtml = '';
            if (statusSeg === 'Liberada') {
                statusBadgeHtml = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Liberada</span>';
            } else if (statusSeg === 'Cancelada') {
                statusBadgeHtml = '<span class="badge" style="background-color: #FFE082; color: #E65100; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">Cancelada</span>';
            } else {
                statusBadgeHtml = '<span class="badge" style="background-color: #E3F2FD; color: #0D47A1; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;">En Seguimiento</span>';
            }
            if (contenedorStatusBadge.html() !== statusBadgeHtml) contenedorStatusBadge.html(statusBadgeHtml);

            let cotizBadgeHtml = '';
            if (statusCotiz === 'Vencida') {
                cotizBadgeHtml = '<span class="badge bg-danger animate__animated animate__flash animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-calendar-x me-1"></i> Vencida</span>';
            } else {
                cotizBadgeHtml = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-calendar-check me-1"></i> Vigente</span>';
            }
            if (contenedorCotizBadge.html() !== cotizBadgeHtml) contenedorCotizBadge.html(cotizBadgeHtml);

            let botonesHtml = '';
            if (statusSeg === 'Liberada' || statusSeg === 'Cancelada') {
                botonesHtml = `<button type="button" onclick="verDetallesCotizacion(${idCotizacion})" class="btn btn-outline-info border-0" title="Visualizar Detalle Cotización"><i class="bi bi-eye-fill fs-5"></i></button>`;
            } else {
                botonesHtml = `<button type="button" onclick="verDetallesCotizacion(${idCotizacion})" class="btn btn-outline-info border-0" title="Visualizar Detalle Cotización"><i class="bi bi-eye-fill fs-5"></i></button>
                               <a href="editar_cotizacion.php?id_cotizacion=${idCotizacion}" class="btn btn-outline-warning border-0" title="Editar Cotización"><i class="bi bi-pencil-square fs-5"></i></a>
                               <button type="button" class="btn btn-outline-success border-0" onclick="cerrarOperacionRecompra(${idCotizacion}, '${escape(cliente)}', '${escape(maquina)}', ${cantidad})" title="Liberar Compra"><i class="bi bi-check-circle-fill fs-5"></i></button>`;
            }
            if (contenedorAcciones.html() !== botonesHtml) contenedorAcciones.html(botonesHtml);

            if (statusSeg === 'Liberada' || statusSeg === 'Cancelada') {
                $(this).html('<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-check-circle-fill me-1"></i> Cerrado</span>');
                fila.removeClass('table-warning-sutil table-danger-sutil').attr('data-urgente', '0').attr('data-atencion', '0').attr('data-encurso', '0');
                return;
            }

            const fechaCotizacion = new Date(fechaCotizStr);
            const diasCotizado = Math.floor((ahora - fechaCotizacion.getTime()) / (1000 * 60 * 60 * 24));
            
            if (statusCotiz === 'Vencida' || diasCotizado > 7) {
                $(this).html('<span class="badge bg-danger animate__animated animate__headShake animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 3.5s !important;"><i class="bi bi-fire me-1"></i> Urgente</span>');
                fila.removeClass('table-warning-sutil').addClass('table-danger-sutil').attr('data-urgente', '1').attr('data-atencion', '0').attr('data-encurso', '0');
                countUrgentes++;
            } else if (diasCotizado > 3) {
                $(this).html('<span class="badge bg-warning text-dark animate__animated animate__flash animate__infinite" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem; animation-duration: 4.5s !important;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Pendiente</span>');
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

    var table = $('#tablaRecompras').DataTable({
        "language": { "emptyTable": "No hay datos", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "0 registros", "infoFiltered": "(filtrado de _MAX_)", "zeroRecords": "Sin coincidencias", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtip', "pageLength": 10, "responsive": true, "ordering": false,
        "drawCallback": function() { calcularSemaforosComerciales(); }
    });

    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    $('#filterEquipo').on('change', function() { table.column(4).search(this.value).draw(); });
    
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var row = $(table.row(dataIndex).node());
        var cumpleUrgente = !$('#btnFiltrarCriticos').is(':checked') || row.attr('data-urgente') === '1';
        var cumplePending = !$('#btnFiltrarPendientes').is(':checked') || row.attr('data-atencion') === '1';
        var cumpleCurso = !$('#btnFiltrarEnCurso').is(':checked') || row.attr('data-encurso') === '1';
        return cumpleUrgente && cumplePending && cumpleCurso;
    });

    $('#btnFiltrarCriticos, #btnFiltrarPendientes, #btnFiltrarEnCurso').on('change', function() { table.draw(); });

    calcularSemaforosComerciales();
    setInterval(calcularSemaforosComerciales, 1000); 

    $('#formConfirmarRecompra').on('submit', function(e) {
        e.preventDefault();
        const idCotizacion = $('#liberar_id_cotizacion').val();
        const formularioDatos = $(this).serialize();

        $.ajax({
            url: '../actions/liberar_recompra.php',
            method: 'POST',
            data: formularioDatos,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalLiberarRecompra').modal('hide');
                    Swal.fire({ title: '¡Venta Cerrada!', text: response.message, icon: 'success', timer: 2000, showConfirmButton: false });
                    const celdaSemaforo = $(`.col-acciones-comerciales[data-id-cotizacion='${idCotizacion}']`).closest('tr').find('.col-semaforo');
                    celdaSemaforo.data('status-seg', 'Liberada').attr('data-status-seg', 'Liberada');
                    calcularSemaforosComerciales();
                } else {
                    Swal.fire({ title: 'Error de Validación', text: response.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error Técnico', text: 'No se recibió respuesta JSON del procesador de recompras.', icon: 'error' });
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
    $_cuerpo.html(`<div class="text-center py-4"><div class="spinner-border text-danger" role="status"></div><p class="text-muted small mt-2">Consultando expediente...</p></div>`);
    $('#modalDetallesCotizacion').appendTo("body").modal('show');
    $.ajax({
        url: '../actions/obtener_detalles_cotizacion.php',
        method: 'GET',
        data: { id_cotizacion: idCotizacion },
        success: function(response) { $_cuerpo.html(response); },
        error: function() { $_cuerpo.html('<div class="alert alert-danger m-0">Error al conectar con el servidor comercial.</div>'); }
    });
}
</script>