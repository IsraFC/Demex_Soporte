<?php
/**
 * ARCHIVO: maquinas.php
 * DESCRIPCIÓN: Módulo de gestión de inventario de equipos y control de garantías.
 * Centraliza la visualización de activos, KPIs de cobertura y gestión de alertas temporales.
 * * @author Israel Fernández Carrera
 * @version 1.3
 * @project Soporte Desarrollo Mexicano (DEMEX)
 */
$pagina_actual = 'maquinas'; 
require_once 'config/db.php';

/** * KPIs - INDICADORES CLAVE DE DESEMPEÑO
 * Obtención de métricas en tiempo real mediante agregaciones SQL.
 */
// Total histórico de equipos registrados
$totalMaquinas = $pdo->query("SELECT COUNT(*) FROM Equipos_Garantia")->fetchColumn();

// Equipos con cobertura técnica activa (Fecha de término >= Hoy)
$garantiasActivas = $pdo->query("SELECT COUNT(*) FROM Equipos_Garantia WHERE fecha_termino >= CURDATE()")->fetchColumn();

include 'includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="container-fluid mb-4">
        <div class="alert alert-<?= ($_GET['msg'] == 'success') ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm border-0 border-start border-4 border-<?= ($_GET['msg'] == 'success') ? 'success' : 'danger' ?> bg-white" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi <?= ($_GET['msg'] == 'success') ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger' ?> fs-4 me-3"></i>
                <div>
                    <?php 
                        if ($_GET['msg'] == 'success') {
                            echo "<strong>¡Operación Exitosa!</strong> El registro se guardó correctamente.";
                        } else {
                            echo "<strong>Hubo un problema.</strong> No se pudo procesar la solicitud, verifique los datos.";
                        }
                    ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="fw-bold text-danger mb-0">Máquinas y Garantía</h1>
        <p class="text-muted small">Inventario de activos y control de cobertura técnica.</p>
    </div>
    
    <div class="col-md-6 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 100px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= $totalMaquinas ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">EQUIPOS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 100px;">
                <span class="d-block fw-bold fs-5 text-success"><?= $garantiasActivas ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">VIGENTES</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-0 align-items-center px-3 justify-content-between">
        <div class="col-auto" style="width: 30%;">
            <div class="input-group input-group-sm border rounded shadow-sm">
                <span class="input-group-text bg-white border-0"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="searchMaquinas" class="form-control border-0" placeholder="Cliente o No. de Serie...">
            </div>
        </div>

        <div class="col-auto">
            <select id="filterModelo" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 220px;">
                <option value="">Seleccionar Modelo</option>
                <?php
                $modelos = ["DEMEX 313", "DEMEX 313T", "DEMEX 513", "DEMEX 613", "DEMEX 1020", "DEMEX 125", "SPICE MT15", "SPICE MV89"];
                foreach($modelos as $m) echo "<option value='$m'>$m</option>";
                ?>
            </select>
        </div>

        <div class="col-auto">
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="checkSoloVigentes" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" for="checkSoloVigentes">Solo Garantía Vigente</label>
            </div>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaMaquinas" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th data-type="num">#</th>
                    <th>Número de Serie</th>
                    <th>Cliente</th>
                    <th>Modelo</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Término</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Consulta JOIN para asociar equipos con la información nominal del cliente
                $sql = "SELECT e.*, c.nombre_cliente 
                        FROM Equipos_Garantia e
                        JOIN Clientes c ON e.id_cliente = c.id_cliente
                        ORDER BY e.fecha_termino DESC";
                
                $stmt = $pdo->query($sql);
                $i = 1;
                $hoy = date('Y-m-d');

                while ($row = $stmt->fetch()):
                    $estaVencida = ($row['fecha_termino'] < $hoy);
                    $badge = $estaVencida ? 'bg-danger' : 'bg-success';
                    $textoBadge = $estaVencida ? 'Vencida' : 'Vigente';
                ?>
                <tr>
                    <td class="fw-bold text-danger"><?= $i++ ?></td>
                    <td><code class="text-dark fw-bold"><?= $row['no_serie'] ?></code></td>
                    <td><div class="fw-bold small"><?= htmlspecialchars($row['nombre_cliente']) ?></div></td>
                    <td class="small text-muted"><?= $row['modelo'] ?></td>
                    <td class="small"><?= date('d/m/y', strtotime($row['fecha_inicio'])) ?></td>
                    <td class="small fw-bold <?= $estaVencida ? 'text-danger' : 'text-success' ?>">
                        <?= date('d/m/y', strtotime($row['fecha_termino'])) ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $badge ?>" style="font-size: 0.65rem;"><?= $textoBadge ?></span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-center mt-4">
        <a href="registro_maquina.php" class="btn btn-white border shadow-sm px-5 rounded-pill fw-bold text-danger">
            <i class="bi bi-plus-circle me-2"></i> Nueva Máquina
        </a>
    </div>
</div>

<script>
/**
 * LÓGICA DE CONTROL DE INTERFAZ (DataTables & UX)
 */
$(document).ready(function() {
    
    // 1. Inicialización de DataTables
    if ($('#tablaMaquinas').length) {
        var table = $('#tablaMaquinas').DataTable({
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
            "order": [[0, "asc"]],
            "columnDefs": [
                { "type": "num", "targets": 0 }
            ]
        });

        // Filtros de búsqueda específicos
        $('#searchMaquinas').on('keyup', function() { table.search(this.value).draw(); });
        $('#filterModelo').on('change', function() { table.column(3).search(this.value).draw(); });
        $('#checkSoloVigentes').on('change', function() {
            table.column(6).search(this.checked ? '^Vigente$' : '', true, false).draw();
        });
    }

    /**
     * 2. GESTIÓN DE ALERTAS Y LIMPIEZA DE URL
     * Detecta notificaciones vía GET, limpia la barra de direcciones y aplica auto-cierre.
     */
    if (window.location.search.indexOf('msg=') > -1) {
        // Limpieza estética de la URL mediante History API (HTML5)
        var clean_url = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: clean_url}, '', clean_url);

        // Auto-cierre con colapso de espacio para evitar 'huecos' en el layout
        setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function(){
                $(this).remove(); // Eliminación física del nodo del DOM
            });
        }, 4000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>