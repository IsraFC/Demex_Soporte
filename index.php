<?php
/**
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Panel de Control Principal (Dashboard). 
 * Centraliza los Indicadores Clave de Desempeño (KPIs) y el seguimiento de tickets.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 2.0
 */

$pagina_actual = 'inicio';
require_once 'config/db.php';

/**
 * CONSULTAS DE INDICADORES (KPIs)
 * Se ejecutan de forma síncrona para mostrar el estado actual del departamento.
 */

// 1. Volumen Histórico: Conteo total de registros en la tabla maestra.
$total = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte")->fetchColumn();

// 2. Carga de Trabajo: Tickets con estatus 'Abierto' que requieren atención inmediata.
$pendientes = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte WHERE estatus = 'Abierto'")->fetchColumn();

// 3. Estado Financiero: Sumatoria de costos totales de servicios no liquidados.
$sql_cobro = "SELECT SUM(costo_total) FROM Detalles_Costos_Tiempos WHERE estatus_pago = 'Pendiente'";
$por_cobrar = $pdo->query($sql_cobro)->fetchColumn() ?: 0;

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0">Panel de Seguimiento</h1>
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
                <input class="form-check-input" type="checkbox" id="checkSoloPendientes" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" for="checkSoloPendientes">Solo <br>Abiertos</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkGarantia" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" for="checkGarantia">Garantía</label>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkSoloDeuda" style="cursor:pointer;">
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
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold">
                    <th data-type="num">#</th> <th>Cliente</th>
                    <th>Equipo / Serie</th>
                    <th class="d-none">Tipo Llamada</th> <th>Falla</th>
                    <th>Garantía</th> 
                    <th>Pago</th> 
                    <th>Estatus</th> 
                    <th>Inicio</th> 
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                /**
                 * EXTRACCIÓN DE DATOS INTEGRADA (JOIN)
                 * Se vinculan tickets, clientes, modelos de equipo y estatus de pago.
                 */
                $sql = "SELECT t.id_ticket, c.nombre_cliente, e.modelo, t.no_serie, t.tipo_falla, 
                               t.garantia_valida, t.estatus, t.fecha_inicial, d.estatus_pago, t.tipo_llamada
                        FROM Tickets_Soporte t 
                        JOIN Clientes c ON t.id_cliente = c.id_cliente
                        LEFT JOIN Equipos_Garantia e ON t.no_serie = e.no_serie
                        LEFT JOIN Detalles_Costos_Tiempos d ON t.id_ticket = d.id_ticket
                        ORDER BY t.id_ticket DESC";
                
                $stmt = $pdo->query($sql);
                while ($row = $stmt->fetch()):
                    // Definición visual de estados
                    $colorGarantia = ($row['garantia_valida'] == 'Válida') ? 'text-success' : 'text-danger';
                    $badgeEstatus = ($row['estatus'] == 'Abierto') ? 'bg-warning text-dark' : 'bg-success';
                    $pagoTexto = $row['estatus_pago'] ?: 'N/A';
                    $colorPago = ($pagoTexto == 'Pendiente') ? 'text-danger fw-bold' : 'text-success fw-bold';
                ?>
                <tr>
                    <td class="fw-bold text-danger"><?= $row['id_ticket'] ?></td>
                    <td>
                        <div class="fw-bold small"><?= htmlspecialchars($row['nombre_cliente']) ?></div>
                    </td>
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
                        <a href="gestion_ticket.php?id=<?= $row['id_ticket'] ?>" class="btn btn-sm btn-outline-danger border-0">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-center mt-4">
        <a href="registro_ticket.php" class="btn btn-white border shadow-sm px-5 rounded-pill fw-bold text-danger">
            <i class="bi bi-plus-circle me-2"></i> Nuevo Ticket
        </a>
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($('#tablaTickets').length) {
            
            /**
             * 1. CONFIGURACIÓN DE DATATABLES
             * Se definen comportamientos de ordenamiento y visualización.
             */
            var table = $('#tablaTickets').DataTable({
                "language": {
                    "emptyTable": "No hay datos",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_",
                    "infoEmpty": "0 registros",
                    "infoFiltered": "(filtrado de _MAX_)",
                    "zeroRecords": "Sin coincidencias",
                    "paginate": { "next": "Sig.", "previous": "Ant." }
                },
                "dom": 'rtip',
                "pageLength": 13,
                "order": [[0, "desc"]], 
                "columnDefs": [
                    { "type": "num", "targets": 0 } // Forzado de orden numérico para el Folio
                ]
            });

            /**
             * 2. BINDING DE FILTROS PERSONALIZADOS
             * Vinculación de elementos UI con las columnas de la tabla.
             */
            
            // Búsqueda global
            $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

            // Filtro por Tipo de Llamada (Columna 3 - Oculta)
            $('#filterTipo').on('change', function() { table.column(3).search(this.value).draw(); });

            // Filtro de Estatus 'Abierto' (Columna 7)
            $('#checkSoloPendientes').on('change', function() {
                table.column(7).search(this.checked ? '^Abierto$' : '', true, false).draw();
            });

            // Filtro de Garantía (Columna 5)
            $('#checkGarantia').on('change', function() {
                table.column(5).search(this.checked ? '^Válida$' : '', true, false).draw();
            });

            // Filtro de Deuda (Columna 6)
            $('#checkSoloDeuda').on('change', function() {
                table.column(6).search(this.checked ? '^Pendiente$' : '', true, false).draw();
            });

            /**
             * 3. CONTROL Y VALIDACIÓN DE RANGOS DE FECHA
             * Evita la selección de fechas inconsistentes (Hasta < Desde).
             */
            $('#fechaDesde').on('change', function() {
                var fechaInicio = $(this).val();
                $('#fechaHasta').attr('min', fechaInicio);
                
                if ($('#fechaHasta').val() !== "" && $('#fechaHasta').val() < fechaInicio) {
                    $('#fechaHasta').val(fechaInicio);
                }
                table.draw();
            });

            $('#fechaHasta').on('change', function() {
                var fechaFin = $(this).val();
                $('#fechaDesde').attr('max', fechaFin);
                table.draw();
            });
        }
    });

    /**
     * EXTENSIÓN DE BÚSQUEDA: Rango de Fechas
     * Se inyecta lógica personalizada en el motor de búsqueda de DataTables 
     * para procesar la columna de 'Inicio' (Índice 8).
     */
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var min = $('#fechaDesde').val();
        var max = $('#fechaHasta').val();
        
        // Se extrae la fecha cruda del atributo data-order para evitar errores de formato local
        var dateRaw = settings.aoData[dataIndex].anCells[8].getAttribute('data-order');
        var date = dateRaw ? dateRaw.split(' ')[0] : ""; 

        // Lógica de inclusión del registro basado en el rango seleccionado
        if ((min === "" && max === "") || 
            (min === "" && date <= max) || 
            (min <= date && max === "") || 
            (min <= date && date <= max)) {
            return true;
        }
        return false;
    });
</script>

<?php include 'includes/footer.php'; ?>