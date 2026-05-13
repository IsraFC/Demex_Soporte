<?php
/**
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Panel de Control Principal (Dashboard). 
 * Centraliza la visualización de tickets mediante un motor de filtrado avanzado.
 * Implementa una lógica de indicadores de envío (KPI visual) basada en hitos 
 * temporales: Pendiente, Iniciado y Finalizado.
 * * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.8
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

// Conteo de tickets críticos (Abiertos con más de 14 días)
$criticos = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte 
                         WHERE estatus = 'Abierto' 
                         AND DATEDIFF(CURDATE(), fecha_inicial) >= 14")->fetchColumn();

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-speedometer2"></i> Panel de Seguimiento</h1>
        <p class="text-muted small">Sistema de gestión y soporte técnico.</p>
    </div>
    <div class="col-md-7 text-md-end">
        <!-- Tarjetas de indicadores (KPIs) con lógica de colores y animaciones para resaltar información crítica. -->
        <div class="d-inline-flex gap-2">
            <!-- Tarjeta de Total: Resalta en rojo, con animación si hay más de 50 tickets. -->
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 90px;">
                <span class="d-block fw-bold fs-5"><?= $total ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">TICKETS</small>
            </div>

            <!-- Tarjeta de Pendientes: Resalta en amarillo, con animación si hay más de 10. -->
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 90px;">
                <span class="d-block fw-bold fs-5 text-warning"><?= $pendientes ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">ABIERTOS</small>
            </div>

            <!-- Tarjeta de Por Cobrar: Resalta en verde, con animación si la deuda supera los $5,000. -->
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 120px;">
                <span class="d-block fw-bold fs-5 text-success">$<?= number_format($por_cobrar, 2) ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">POR COBRAR</small>
            </div>

            <!-- Tarjeta de Críticos: Resalta en rojo, con animación si hay más de 0 tickets críticos. -->
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 90px;">
                <span class="d-block fw-bold fs-5 <?= ($criticos > 0) ? 'text-danger ms-1-animate' : 'text-muted' ?>">
                    <?= $criticos ?>
                </span>
                <small class="text-muted" style="font-size: 0.6rem;">CRÍTICOS</small>
            </div>
        </div>
    </div>
</div>

<?php if ($criticos > 0): ?>
    <div class="alert alert-danger shadow-sm border-0 border-start border-4 border-danger bg-white d-flex align-items-center justify-content-between animate__animated animate__headShake" role="alert" id="alertaCriticos">
        <div>
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
            <span class="fw-bold">Atención:</span> Hay <strong><?= $criticos ?></strong> tickets que llevan más de 2 semanas abiertos.
        </div>
        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold" id="btnFiltrarCriticos">
            <i class="bi bi-funnel-fill me-1"></i> Ver Urgentes
        </button>
    </div>
<?php endif; ?>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-3 align-items-center px-3 mb-3">
        <div class="col-md-3">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text border-0 bg-transparent"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Cliente o Serie...">
            </div>
        </div>
        <div class="col-md-3">
            <select id="filterTipo" class="form-select border-0 bg-light fw-bold text-muted shadow-sm">
                <option value="">Todas las Llamadas</option>
                <option value="Soporte">Soporte</option>
                <option value="Venta Refacciones">Venta Refacciones</option>
                <option value="Información">Información</option>
                <option value="Capacitaciones">Capacitaciones</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterFalla" class="form-select border-0 bg-light fw-bold text-muted shadow-sm">
                <option value="">Todas las Fallas</option>
                <option value="Mecánica">Mecánica</option>
                <option value="Refrigeración">Refrigeración</option>
                <option value="Electrónica">Electrónica</option>
                <option value="Regulador">Regulador</option>
                <option value="Materia prima">Materia prima</option>
                <option value="Otra">Otra</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterAccion" class="form-select border-0 bg-light fw-bold text-muted shadow-sm">
                <option value="">Todas las Acciones</option>
                <option value="Ninguna">Ninguna</option>
                <option value="Envio técnico">Envío técnico</option>
                <option value="Envio refacciones">Envío refacciones</option>
                <option value="Envio técnico y refacciones">Técnico + Refacc.</option>
                <option value="Envio base">Envío base</option>
                <option value="Reparación en taller">Reparación en taller</option>
                <option value="Cambio de maquina">Cambio de máquina</option>
            </select>
        </div>
    </div>

    <div class="row g-3 align-items-center px-3 border-top pt-3">
        <div class="col-md-4 d-flex align-items-center gap-2">
            <span class="small fw-bold text-muted text-uppercase me-1">Rango:</span>
            <input type="date" id="fechaDesde" class="form-control form-control-sm border-0 bg-light shadow-sm text-muted">
            <input type="date" id="fechaHasta" class="form-control form-control-sm border-0 bg-light shadow-sm text-muted">
        </div>
        
        <div class="col-md-8 d-flex justify-content-end gap-4">
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkSoloPendientes">
                <label class="form-check-label small fw-bold text-muted" for="checkSoloPendientes">Solo Abiertos</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkGarantia">
                <label class="form-check-label small fw-bold text-muted" for="checkGarantia">Garantía Válida</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkSoloDeuda">
                <label class="form-check-label small fw-bold text-danger" for="checkSoloDeuda">Con Deuda</label>
            </div>
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
                    <th class="d-none">Tipo Llamada</th> 
                    <th>Falla</th>
                    <th class="d-none">Accion Realizada</th>
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
                    // --- Formateo visual de columnas base ---
                    $colorGarantia = ($row['garantia_valida'] == 'Válida') ? 'text-success' : 'text-danger';

                    // --- Lógica de estatus (Abierto, Cerrado, Cancelado) con colores distintivos ---
                    $badgeEstatus = 'bg-secondary'; 
                    if ($row['estatus'] == 'Abierto') $badgeEstatus = 'bg-warning text-dark';
                    elseif ($row['estatus'] == 'Cerrado') $badgeEstatus = 'bg-success';

                    // --- Lógica de estatus de pago ---
                    $pagoTexto = $row['estatus_pago'] ?: 'N/A';
                    $colorPago = ($pagoTexto == 'Pendiente') ? 'text-danger fw-bold' : 'text-success fw-bold';
                    $id = $row['id_ticket'];

                    // --- Lógica de ticket crítico (Abierto por más de 14 días) ---
                    $fecha_ini = new DateTime($row['fecha_inicial']);
                    $hoy = new DateTime();
                    $diff = $hoy->diff($fecha_ini)->days; // Días transcurridos desde la apertura
                    $esCritico = ($row['estatus'] == 'Abierto' && $diff >= 14); // Condición: Abierto y >= 14 días
                ?>
                <tr>
                    <td class="fw-bold text-danger text-nowrap">
                        <?= $id ?>
                        <?php if ($esCritico): ?>
                            <i class="bi bi-exclamation-triangle-fill text-danger ms-1 ms-1-animate" 
                            title="Urgente: Este ticket lleva <?= $diff ?> días abierto" 
                            data-bs-toggle="tooltip"></i>
                        <?php endif; ?>
                    </td>
                    <td><div class="fw-bold small"><?= htmlspecialchars($row['nombre_cliente']) ?></div></td>
                    <td>
                        <div class="small fw-bold text-dark"><?= $row['modelo'] ?: 'S/M' ?></div>
                        <code class="text-muted" style="font-size: 0.7rem;"><?= $row['no_serie'] ?></code>
                    </td>
                    <td class="d-none"><?= $row['tipo_llamada'] ?></td>
                    <td class="small text-muted"><?= $row['tipo_falla'] ?: 'Soporte' ?></td>
                    <td class="d-none"><?= $row['accion'] ?></td>
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
     * CONTROL DE FLUJO: Actualiza el estatus del ticket.
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
                                text: `Ticket #${id} actualizado correctamente.`,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(() => { location.reload(); }, 1500);
                        }
                    }
                });
            }
        });
    }

    /**
     * FUNCIÓN DEL BOTÓN EXCEL (Respaldo y Limpieza)
     */
    function confirmarRespaldo() {
        Swal.fire({
            title: '¿Generar Respaldo y Limpiar?',
            text: "Se descargará un Excel con TODO el historial. Los tickets 'Cerrados' y 'Cancelados' se eliminarán de la base de datos.",
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
                setTimeout(() => { location.reload(); }, 3000);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Solo Descarga
                window.location.href = 'actions/respaldo_limpieza.php?download=true&clean=false';
            }
        });
    }


    /**
     * CONFIGURACIÓN DATATABLES Y FILTROS (Versión Blindada)
     */
    $(document).ready(function() {
        if ($('#tablaTickets').length) {
            var table = $('#tablaTickets').DataTable({
                "language": {
                    "sProcessing":     "Procesando...",
                    "sLengthMenu":     "Mostrar _MENU_ registros",
                    "sZeroRecords":    "No se encontraron resultados",
                    "sInfo":           "Mostrando _START_ al _END_ de _TOTAL_",
                    "sInfoEmpty":      "Mostrando 0 al 0 de 0",
                    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)", // <--- AGREGA ESTA LÍNEA
                    "sSearch":         "Buscar:",
                    "oPaginate": {
                        "sFirst": "Primero", 
                        "sLast": "Último", 
                        "sNext": "Sig", 
                        "sPrevious": "Ant"
                    }
                },
                "dom": 'rtip',
                "pageLength": 13,
                "order": [[0, "desc"]]
            });

            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

            $('#filterTipo').on('change', function() { table.column(3).search(this.value).draw(); }); // Col 3 (Oculta)
            $('#filterFalla').on('change', function() { table.column(4).search(this.value).draw(); }); // Col 4 (Visible)
            $('#filterAccion').on('change', function() { 
                table.column(5).search(this.value ? '^' + this.value + '$' : '', true, false).draw(); 
            });

            // 2. Lógica de los Switches (Interruptores)
            // Filtro: Solo Abiertos (Columna 7 del HTML / Índice 7 de DataTable)
            $('#checkSoloPendientes').on('change', function() {
                table.column(8).search(this.checked ? '^Abierto$' : '', true, false).draw();
            });

            // Filtro: Garantía Válida (Columna 5)
            $('#checkGarantia').on('change', function() {
                table.column(6).search(this.checked ? '^Válida$' : '', true, false).draw();
            });

            // Filtro: Con Deuda (Estatus de Pago 'Pendiente' en Columna 6)
            $('#checkSoloDeuda').on('change', function() {
                table.column(7).search(this.checked ? '^Pendiente$' : '', true, false).draw();
            });

            // --- INTEGRACIÓN DE SISTEMA DE ALARMAS ---

            // 1. Inicializar los Tooltips de Bootstrap (para que el mensaje del triángulo funcione)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // 2. Función para el botón "Ver Urgentes" del Banner
            $('#btnFiltrarCriticos').on('click', function() {
                const btn = $(this);

                // Si el botón tiene la clase de peligro, activa el filtro
                if (btn.hasClass('btn-danger')) {
                    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                        var estatus = data[8]; // Columna Estatus
                        var dateRaw = $(table.row(dataIndex).node()).find('td:nth-child(10)').attr('data-order');
                        
                        if (dateRaw && estatus === 'Abierto') {
                            // Calcula la diferencia de días entre hoy y la fecha de inicio
                            var diff = Math.floor((new Date() - new Date(dateRaw)) / (1000 * 60 * 60 * 24));
                            return diff >= 14;
                        }
                        return false;
                    });

                    // Cambia el texto y estilo para permitir quitar el filtro
                    btn.html('<i class="bi bi-arrow-counterclockwise me-1"></i> Quitar Filtro')
                    .addClass('btn-dark').removeClass('btn-danger');
                } 
                // Si no es rojo, quita la regla de filtrado
                else {
                    $.fn.dataTable.ext.search.pop(); // Elimina la última regla de búsqueda
                    
                    // Restaura el botón a su estado original
                    btn.html('<i class="bi bi-funnel-fill me-1"></i> Ver Urgentes')
                    .addClass('btn-danger').removeClass('btn-dark');
                }

                table.draw(); // Redibuja la tabla con los cambios aplicados
            });
            
            // --- BLOQUEO DE CALENDARIOS ---
            $('#fechaDesde').on('change', function() {
                var fechaMin = $(this).val();
                $('#fechaHasta').attr('min', fechaMin); // Bloquea días anteriores en el segundo input
                table.draw();
            });

            $('#fechaHasta').on('change', function() {
                var fechaMax = $(this).val();
                $('#fechaDesde').attr('max', fechaMax); // Bloquea días posteriores en el primer input
                table.draw();
            });

            // --- LÓGICA DE FILTRADO ---
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var min = $('#fechaDesde').val();
                var max = $('#fechaHasta').val();
                // nth-child(10) porque en el DOM el conteo empieza en 1
                var dateRaw = $(table.row(dataIndex).node()).find('td:nth-child(10)').attr('data-order');
                var date = dateRaw ? dateRaw.substring(0, 10) : ""; 

                if (min === "" && max === "") return true;
                if (min === "" && date <= max) return true;
                if (min <= date && max === "") return true;
                if (min <= date && date <= max) return true;
                return false;
            });
        }
    });
</script>