<?php
/**
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Panel de Control Principal (Dashboard). 
 * Centraliza la visualización de tickets mediante un motor de filtrado avanzado.
 * Implementa una lógica de indicadores de envío (KPI visual) basada en hitos 
 * temporales: Pendiente, Iniciado y Finalizado.
 * * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.7
 */

$pagina_actual = 'inicio';
require_once 'config/db.php';

/**
 * CONSULTAS DE INDICADORES (KPIs):
 * Cálculo de métricas globales para las tarjetas de resumen superior.
 */
$total = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte")->fetchColumn();
$pendientes = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte WHERE estatus = 'Abierto'")->fetchColumn();

// Sumatoria de deuda total (Tickets con estatus_pago = 'Pendiente' y estatus = ´Abierto´)
$sql_cobro = "SELECT SUM(d.costo_total) FROM Detalles_Costos_Tiempos d
JOIN Tickets_Soporte t ON d.id_ticket = t.id_ticket
WHERE d.estatus_pago = 'Pendiente' AND t.estatus = 'Abierto'";
$por_cobrar = $pdo->query($sql_cobro)->fetchColumn() ?: 0;

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-speedometer2"></i> Panel de Seguimiento</h1>
        <p class="text-muted small">Sistema de gestión y soporte técnico.</p>
    </div>
    <div class="col-md-7 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 90px;">
                <span class="d-block fw-bold fs-5"><?= $total ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">TICKETS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 90px;">
                <span class="d-block fw-bold fs-5 text-warning"><?= $pendientes ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">ABIERTOS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 120px;">
                <span class="d-block fw-bold fs-5 text-success">$<?= number_format($por_cobrar, 2) ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">POR COBRAR</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-0 align-items-center justify-content-between px-3"> 
        <div class="col-auto" style="width: 20%;">
            <div class="input-group input-group-sm border rounded shadow-sm">
                <span class="input-group-text bg-white border-0"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control border-0" placeholder="Cliente o Serie...">
            </div>
        </div>
        <div class="col-auto">
            <select id="filterTipo" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3">
                <option value="">Todo</option>
                <option value="Soporte">Soporte</option>
                <option value="Venta Refacciones">Venta Refacciones</option>
                <option value="Información">Información</option>
                <option value="Capacitaciones">Capacitaciones</option>
            </select>
        </div>
        <div class="col-auto d-flex gap-3 align-items-center">
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkSoloPendientes">
                <label class="form-check-label small fw-bold text-muted" for="checkSoloPendientes">Solo <br>Abiertos</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkGarantia">
                <label class="form-check-label small fw-bold text-muted" for="checkGarantia">Garantía</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkSoloDeuda">
                <label class="form-check-label small fw-bold text-danger" for="checkSoloDeuda">Con <br>Deuda</label>
            </div>
        </div>
        <div class="col-auto d-flex align-items-center gap-2">
            <input type="date" id="fechaDesde" class="form-control form-control-sm border-0 bg-light shadow-sm text-muted" style="width: 135px;">
            <input type="date" id="fechaHasta" class="form-control form-control-sm border-0 bg-light shadow-sm text-muted" style="width: 135px;">
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaTickets" class="table table-hover align-middle w-100">
            <thead class="table-light text-uppercase small fw-bold">
                <tr>
                    <th data-type="num">#</th> 
                    <th>Cliente</th>
                    <th>Equipo / Serie</th>
                    <th class="d-none">Tipo Llamada</th> <th>Falla</th>
                    <th>Garantía</th> 
                    <th>Pago</th> 
                    <th>Estatus</th> 
                    <th>Inicio</th> 
                    <th class="text-center">Acción</th>
                    <th>Envios</th>
                </tr>
            </thead>
            <tbody>
                <?php
                /**
                 * CARGA DINÁMICA: 
                 * Cruce de tablas Tickets_Soporte, Clientes, Equipos_Garantia y Detalles_Costos_Tiempos.
                 * Se recuperan las fechas de acción para procesar la lógica de estados de envío.
                 */
                $sql = "SELECT t.id_ticket, c.nombre_cliente, e.modelo, t.no_serie, t.tipo_falla, 
                               t.garantia_valida, t.estatus, t.fecha_inicial, d.estatus_pago, t.tipo_llamada, 
                               d.accion, d.fecha_inicio_acc, d.fecha_fin_acc
                        FROM Tickets_Soporte t 
                        JOIN Clientes c ON t.id_cliente = c.id_cliente
                        LEFT JOIN Equipos_Garantia e ON t.no_serie = e.no_serie
                        LEFT JOIN Detalles_Costos_Tiempos d ON t.id_ticket = d.id_ticket
                        ORDER BY t.id_ticket DESC";
                
                $stmt = $pdo->query($sql);
                while ($row = $stmt->fetch()):
                    // Formateo visual de columnas base
                    $colorGarantia = ($row['garantia_valida'] == 'Válida') ? 'text-success' : 'text-danger';
                    
                    $badgeEstatus = 'bg-secondary'; 
                    if ($row['estatus'] == 'Abierto') $badgeEstatus = 'bg-warning text-dark';
                    elseif ($row['estatus'] == 'Cerrado') $badgeEstatus = 'bg-success';

                    $pagoTexto = $row['estatus_pago'] ?: 'N/A';
                    $colorPago = ($pagoTexto == 'Pendiente') ? 'text-danger fw-bold' : 'text-success fw-bold';
                    $id = $row['id_ticket'];
                ?>
                <tr>
                    <td class="fw-bold text-danger"><?= $id ?></td>
                    <td><div class="fw-bold small"><?= htmlspecialchars($row['nombre_cliente']) ?></div></td>
                    <td>
                        <div class="small fw-bold text-dark"><?= $row['modelo'] ?: 'S/M' ?></div>
                        <code class="text-muted" style="font-size: 0.7rem;"><?= $row['no_serie'] ?></code>
                    </td>
                    <td class="d-none"><?= $row['tipo_llamada'] ?></td>
                    <td class="small text-muted"><?= $row['tipo_falla'] ?: 'Soporte' ?></td>
                    <td class="small fw-bold <?= $colorGarantia ?>"><?= $row['garantia_valida'] ?></td>
                    <td class="small <?= $colorPago ?>"><?= $pagoTexto ?></td>
                    <td><span class="badge <?= $badgeEstatus ?>" style="font-size: 0.65rem;"><?= $row['estatus'] ?></span></td>
                    <td class="small" data-order="<?= $row['fecha_inicial'] ?>">
                        <?= date('d/m/y', strtotime($row['fecha_inicial'])) ?>
                    </td>
                    
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-info border-0" onclick="abrirModalVisualizar(<?= $id ?>)" title="Ver detalles">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <?php if($row['estatus'] == 'Abierto'): ?>
                                <a href="editar_ticket.php?id_ticket=<?= $id ?>" class="btn btn-outline-warning border-0" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button" class="btn btn-outline-success border-0" onclick="cambiarEstatus(<?= $id ?>, 'Cerrado')" title="Cerrar">
                                    <i class="bi bi-lock-fill"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary border-0" onclick="cambiarEstatus(<?= $id ?>, 'Cancelado')" title="Cancelar">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="text-center col-envios" data-id="<?= $id ?>">
                        <div>
                            <?php 
                            /**
                             * LÓGICA DE INDICADORES (Versión 1.7):
                             * 1. Se define el ícono según la 'accion' registrada.
                             * 2. Se define el color/estado según la presencia de fechas:
                             * - Sin fecha inicio: PENDIENTE (Amarillo)
                             * - Con inicio, sin fin: INICIADO (Azul)
                             * - Con ambas fechas: FINALIZADO (Verde)
                             */
                            $iconos = [
                                'Envio base' => ['bi bi-truck', 'Envío de base'],
                                'Envio técnico' => ['bi bi-tools', 'Envío de técnico'],
                                'Envio refacciones' => ['bi bi-box-seam', 'Envío de refacciones'],
                                'Envio técnico y refacciones' => ['bi bi-tools', 'Técnico + Refacciones']
                            ];

                            $esAccionConocida = array_key_exists($row['accion'], $iconos);
                            $accionInfo = $esAccionConocida ? $iconos[$row['accion']] : ['bi bi-question-circle', 'No disponible'];
                            
                            $iconClass = $accionInfo[0];
                            $baseTitle = $accionInfo[1];

                            $bgClass = 'bg-secondary'; 
                            $suffix = '';

                            if ($esAccionConocida) {
                                if (empty($row['fecha_inicio_acc'])) {
                                    $bgClass = 'bg-warning text-dark';
                                    $suffix = ' (Pendiente)';
                                } 
                                elseif (!empty($row['fecha_inicio_acc']) && empty($row['fecha_fin_acc'])) {
                                    $bgClass = 'bg-primary text-white';
                                    $suffix = ' (Iniciado)';
                                }
                                elseif (!empty($row['fecha_inicio_acc']) && !empty($row['fecha_fin_acc'])) {
                                    $bgClass = 'bg-success text-white';
                                    $suffix = ' (Finalizado)';
                                }
                            } else {
                                $bgClass = 'bg-secondary text-white';
                            }
                            ?>
                            <span class="badge <?= $bgClass ?> rounded-pill px-3 py-2 badge-envio" 
                                  style="font-size: 0.65rem;" 
                                  title="<?= $baseTitle . $suffix ?>">
                                <i class="<?= $iconClass ?> <?= strpos($iconClass, 'bi-tools') !== false ? 'me-1' : '' ?>"></i>
                                <?php if ($row['accion'] == 'Envio técnico y refacciones'): ?>
                                    <i class="bi bi-box-seam me-1"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-3" style="gap: 10px;">
    <button type="button" class="btn btn-outline-success shadow-sm rounded-pill px-3" onclick="confirmarRespaldo()">
        <i class="bi bi-file-earmark-excel-fill me-1"></i> Respaldo y Limpieza
    </button>
</div>

<div class="modal fade" id="modalVisualizar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold text-uppercase">Resumen Total del Ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoTicket">
                <div class="text-center p-5">
                    <div class="spinner-border text-danger" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    /**
     * AJAX: Recupera el desglose técnico y financiero del ticket.
     */
    function abrirModalVisualizar(id) {
        $('#modalVisualizar').modal('show');
        $('#contenidoTicket').html('<div class="text-center p-5"><div class="spinner-border text-danger"></div></div>');
        $.ajax({
            url: 'actions/obtener_detalles_ticket.php',
            method: 'GET',
            data: { id_ticket: id },
            success: function(html) {
                $('#contenidoTicket').html(html);
            }
        });
    }

    /**
     * CONTROL DE FLUJO: Actualiza el estatus del ticket y refresca la UI reactivamente.
     */
    function cambiarEstatus(id, nuevoEstatus) {
        const colorEstatus = {
            'Abierto': '#ffc107',
            'Cerrado': '#198754',
            'Cancelado': '#6c757d'
        };

        Swal.fire({
            title: `¿${nuevoEstatus === 'Cerrado' ? 'Cerrar' : 'Cancelar'} ticket #${id}?`,
            text: `El estatus cambiará a ${nuevoEstatus.toLowerCase()} de forma permanente.`,
            icon: nuevoEstatus === 'Cerrado' ? 'success' : 'warning',
            showCancelButton: true,
            confirmButtonColor: colorEstatus[nuevoEstatus],
            cancelButtonColor: '#adb5bd',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Regresar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'actions/actualizar_estatus.php',
                    method: 'POST',
                    data: { id_ticket: id, estatus: nuevoEstatus },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Hecho!',
                                text: `Ticket #${id} ${nuevoEstatus.toLowerCase()} correctamente.`,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            const fila = $(`button[onclick*="cambiarEstatus(${id},"]`).closest('tr');

                            // Actualización de Badge de Estatus principal
                            const badgeEstatus = fila.find('td:nth-child(8) .badge');
                            badgeEstatus.text(nuevoEstatus);
                            badgeEstatus.removeClass('bg-warning bg-success bg-secondary text-dark text-white');
                            
                            let badgeClass = (nuevoEstatus === 'Cerrado') ? 'bg-success' : 'bg-secondary';
                            badgeEstatus.addClass(badgeClass + ' text-white');

                            // Bloqueo de controles de edición
                            if (nuevoEstatus !== 'Abierto') {
                                fila.find('.btn-group').html(`
                                    <button type="button" class="btn btn-outline-info border-0" onclick="abrirModalVisualizar(${id})" title="Ver detalles">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                `);
                            }
                        } else {
                            Swal.fire('Error', 'No se pudo actualizar: ' + response.error, 'error');
                        }
                    }
                });
            }
        });
    }

    function confirmarRespaldo() {
        Swal.fire({
            title: '¿Generar Respaldo y Limpiar?',
            text: "Se descargará un Excel con TODO el historial. Los tickets 'Cerrados' y 'Cancelados' se eliminarán de la base de datos para depurar el sistema.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, respaldar y limpiar',
            cancelButtonText: 'Solo descargar Excel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Descarga + Limpieza
                window.location.href = 'actions/respaldo_limpieza.php?download=true&clean=true';
                // Recargar la página después de un momento para ver los cambios
                setTimeout(() => { location.reload(); }, 3000);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Solo Descarga (sin borrar nada)
                window.location.href = 'actions/respaldo_limpieza.php?download=true&clean=false';
            }
        });
    }

    /**
     * CONFIGURACIÓN DATATABLES: Inicializa filtros de búsqueda y rango de fechas.
     */
    $(document).ready(function() {
        if ($('#tablaTickets').length) {
            var table = $('#tablaTickets').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
                "dom": 'rtip',
                "pageLength": 13,
                "order": [[0, "desc"]]
            });

            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
            $('#filterTipo').on('change', function() { table.column(3).search(this.value).draw(); });
            $('#checkSoloPendientes').on('change', function() { table.column(7).search(this.checked ? '^Abierto$' : '', true, false).draw(); });
            $('#checkGarantia').on('change', function() { table.column(5).search(this.checked ? '^Válida$' : '', true, false).draw(); });
            $('#checkSoloDeuda').on('change', function() { table.column(6).search(this.checked ? '^Pendiente$' : '', true, false).draw(); });

            // Filtro de búsqueda por fechas personalizado
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var min = $('#fechaDesde').val();
                var max = $('#fechaHasta').val();
                var dateRaw = settings.aoData[dataIndex].anCells[8].getAttribute('data-order');
                var date = dateRaw ? dateRaw.split(' ')[0] : ""; 

                if ((min === "" && max === "") || (min === "" && date <= max) || (min <= date && max === "") || (min <= date && date <= max)) return true;
                return false;
            });

            $('#fechaDesde, #fechaHasta').on('change', function() { table.draw(); });
        }
    });
</script>