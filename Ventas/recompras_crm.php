<?php
/**
 * ARCHIVO: recompras_crm.php
 * DESCRIPCIÓN: Panel de Control de Recompras CRM corregido.
 */

$page_title = "Pipeline de Recompras | CRM Ventas";
require_once '../config/db.php';

$total_recompras = $pdo->query("SELECT COUNT(*) FROM cotizacion WHERE id_prospecto IS NULL OR id_prospecto = 0")->fetchColumn();
$maquinas_reales = ['DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<!-- ... (Tu sección de KPIs se mantiene igual) ... -->

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-success bg-white rounded">
    <div class="row g-0 align-items-center px-3 justify-content-between">
        <div class="col-auto" style="width: 30%;">
            <label for="customSearch" class="visually-hidden">Buscar Cliente</label>
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-success"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Buscar Cliente o Correo...">
            </div>
        </div>
        <div class="col-auto">
            <label for="filterCanal" class="visually-hidden">Canal</label>
            <select id="filterCanal" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 220px;">
                <option value="">Todos los Canales</option>
                <option value="Cliente Frecuente">Cliente Frecuente</option>
            </select>
        </div>
        <div class="col-auto">
            <label for="filterEquipo" class="visually-hidden">Equipo</label>
            <select id="filterEquipo" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 220px;">
                <option value="">Todos los Equipos</option>
                <?php foreach ($maquinas_reales as $maquina): ?>
                    <option value="<?= htmlspecialchars($maquina) ?>"><?= htmlspecialchars($maquina) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- ... (Tus checkboxes se mantienen igual) ... -->
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaRecompras" class="table table-hover align-middle w-100">
            <!-- ... (Tu thead y estructura de tabla se mantienen igual) ... -->
        </table>
    </div>
</div>

<!-- ... (Tus modales se mantienen igual) ... -->

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    function calcularSemaforosComerciales() {
        const ahora = Date.now();
        let countEnCurso = 0, countAtencion = 0, countUrgentes = 0;

        $('.col-semaforo').each(function() {
            // ... (Tu lógica de semáforos se mantiene igual) ...
        });

        $('#kpi-encurso').text(countEnCurso);
        $('#kpi-pendientes').text(countAtencion);
        $('#kpi-urgentes').text(countUrgentes);
    }

    var table = $('#tablaRecompras').DataTable({
        "language": { "emptyTable": "No hay datos", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "0 registros", "infoFiltered": "(filtrado de _MAX_)", "zeroRecords": "Sin coincidencias", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtip', "pageLength": 10, "ordering": false, "responsive": true,
        "drawCallback": function() { calcularSemaforosComerciales(); }
    });

    // Eventos de filtro
    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    $('#filterCanal').on('change', function() { table.column(1).search(this.value).draw(); });
    $('#filterEquipo').on('change', function() { table.column(4).search(this.value).draw(); });
    
    // ... (Tu lógica de botones y modales se mantiene igual) ...

    calcularSemaforosComerciales();
    // Se elimina el setInterval para evitar el bucle infinito
});
</script>