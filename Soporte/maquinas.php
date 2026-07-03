<?php
/**
 * ARCHIVO: maquinas.php
 * DESCRIPCIÓN: Módulo de gestión de inventario de equipos y control de garantías reactivo.
 * @author Israel Fernández Carrera
 * @version 1.5 - Pop-up de detalles del cliente optimizado (Más amplio)
 * @project Soporte Desarrollo Mexicano (DEMEX)
 */
require_once '../config/db.php';
$page_title = "Máquinas - Soporte";

/** * KPIs - INDICADORES CLAVE DE DESEMPEÑO */
$totalMaquinas = $pdo->query("SELECT COUNT(*) FROM Equipos_Garantia")->fetchColumn();
$garantiasActivas = $pdo->query("SELECT COUNT(*) FROM Equipos_Garantia WHERE fecha_termino >= CURDATE()")->fetchColumn();

$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<style>
    .pop-cliente-flotante {
        position: fixed !important;
        bottom: 25px !important;
        right: 25px !important;
        width: 380px !important; /* Incrementado para mayor legibilidad */
        background-color: #ffffff !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2) !important;
        z-index: 9999 !important; 
        display: none; 
        border: 1px solid rgba(0,0,0,0.08) !important;
        overflow: hidden !important;
    }
    .pop-cliente-header {
        background-color: #dc3545;
        color: #ffffff;
        padding: 16px 20px; /* Más padding para que respire */
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .pop-cliente-body {
        background-color: #f8f9fa !important;
        padding: 20px !important; /* Más espaciado interno */
    }
</style>

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
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-cpu"></i> Máquinas y Garantía</h1>
        <p class="text-muted small">Inventario de activos y control de cobertura técnica.</p>
    </div>
    
    <div class="col-md-6 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 100px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= $totalMaquinas ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">EQUIPOS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 100px;">
                <span id="kpi-vigentes" class="d-block fw-bold fs-5 text-success"><?= $garantiasActivas ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">VIGENTES</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-0 align-items-center px-3 justify-content-between">
        <div class="col-md-4">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="searchMaquinas" class="form-control bg-transparent border-0" placeholder="Cliente o No. de Serie...">
            </div>
        </div>

        <div class="col-auto">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-funnel-fill text-danger"></i></span>
                <select id="filterModelo" class="form-control bg-transparent border-0 small fw-bold text-muted shadow-none py-1" style="min-width: 200px; cursor: pointer;">
                    <option value="">Seleccionar Modelo</option>
                    <?php
                    $modelos = ["DEMEX 313", "DEMEX 313T", "DEMEX 513", "DEMEX 613", "DEMEX 1020", "DEMEX 125", "SPICE MT15", "SPICE MV89"];
                    foreach($modelos as $m) echo "<option value='$m'>$m</option>";
                    ?>
                </select>
            </div>
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
                $sql = "SELECT e.*, c.nombre_cliente, c.telefono, c.ubicacion, c.id_cliente
                        FROM Equipos_Garantia e
                        JOIN Clientes c ON e.id_cliente = c.id_cliente
                        ORDER BY e.fecha_termino DESC";
                
                $stmt = $pdo->query($sql);
                $i = 1;

                while ($row = $stmt->fetch()):
                    $hoy_php = date('Y-m-d');
                    $estaVencida = ($row['fecha_termino'] < $hoy_php);
                ?>
                <tr>
                    <td class="fw-bold text-danger"><?= $i++ ?></td>
                    <td><code class="text-dark fw-bold"><?= $row['no_serie'] ?></code></td>
                    
                    <td>
                        <a href="#" class="text-decoration-none trigger-pop-cliente fw-bold text-danger"
                           data-id="CLI-<?= str_pad($row['id_cliente'], 3, "0", STR_PAD_LEFT) ?>" 
                           data-nombre="<?= htmlspecialchars($row['nombre_cliente']) ?>" 
                           data-tel="<?= htmlspecialchars($row['telefono'] ?: 'Sin Registro') ?>" 
                           data-dir="<?= htmlspecialchars($row['ubicacion'] ?: 'Sin Dirección') ?>">
                           <?= htmlspecialchars($row['nombre_cliente']) ?>
                        </a>
                    </td>
                    
                    <td class="small text-muted"><?= $row['modelo'] ?></td>
                    <td class="small"><?= date('d/m/y', strtotime($row['fecha_inicio'])) ?></td>
                    
                    <td class="small fw-bold col-fecha" data-termino="<?= $row['fecha_termino'] ?>">
                        <?= date('d/m/y', strtotime($row['fecha_termino'])) ?>
                    </td>
                    
                    <td class="text-center col-estado">
                        <span class="badge <?= $estaVencida ? 'bg-danger' : 'bg-success' ?>" style="font-size: 0.65rem;">
                            <?= $estaVencida ? 'Vencida' : 'Vigente' ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="text-center mt-4">
    <a href="registro_maquina.php" class="btn btn-outline-danger shadow-sm px-5 rounded-pill fw-bold">
        <i class="bi bi-plus-circle me-2"></i> Nueva Máquina
    </a>
</div>

<div id="popClienteFlotante" class="pop-cliente-flotante animate__animated animate__fadeInUp">
    <div class="pop-cliente-header shadow-sm">
        <div>
            <h6 class="fw-bold mb-0 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;"><i class="bi bi-person-badge-fill me-1.5"></i> Ficha del Cliente</h6>
            <small id="popCliId" class="text-white-50 fw-bold" style="font-size: 10px;"></small>
        </div>
        <button type="button" class="btn-close btn-close-white" onclick="cerrarPopCliente()"></button>
    </div>
    <div class="pop-cliente-body">
        <div class="mb-3">
            <span class="text-muted d-block fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">NOMBRE / EMPRESA</span>
            <div id="popCliNombre" class="fw-bold text-dark fs-6 mt-0.5"></div>
        </div>
        <hr class="opacity-10 my-2.5">
        <div class="mb-3">
            <span class="text-muted d-block fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">TELÉFONO DE CONTACTO</span>
            <div id="popCliTel" class="fw-semibold text-secondary mt-0.5" style="font-size: 13.5px;"></div>
        </div>
        <hr class="opacity-10 my-2.5">
        <div>
            <span class="text-muted d-block fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">UBICACIÓN MATRIZ</span>
            <div id="popCliDir" class="text-secondary mt-0.5 lh-base" style="font-size: 13px;"></div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#popClienteFlotante').appendTo("body");

    $(document).on('click', '.trigger-pop-cliente', function(e) {
        e.preventDefault();
        $('#popCliId').text($(this).data('id'));
        $('#popCliNombre').text($(this).data('nombre'));
        $('#popCliTel').html('<i class="bi bi-telephone text-muted me-1.5"></i>' + $(this).data('tel'));
        $('#popCliDir').html('<i class="bi bi-geo-alt text-danger me-1.5"></i>' + $(this).data('dir'));
        
        $('#popClienteFlotante').show();
    });

    window.cerrarPopCliente = function() {
        $('#popClienteFlotante').hide();
    }
    
    function actualizarGarantiasVivas() {
        const ahoraTimestamp = Date.now(); 
        const hoy = new Date(ahoraTimestamp);
        hoy.setHours(0, 0, 0, 0);

        let contadorVigentes = 0;

        $('.col-fecha').each(function() {
            const fechaStr = $(this).data('termino');
            if (!fechaStr) return;

            const fechaTermino = new Date(fechaStr + "T23:59:59");
            const celdaEstado = $(this).siblings('.col-estado');

            if (hoy > fechaTermino) {
                if (!$(this).hasClass('text-danger')) { 
                    $(this).removeClass('text-success').addClass('text-danger');
                    celdaEstado.html('<span class="badge bg-danger shadow-sm" style="font-size: 0.65rem;">Vencida</span>');
                }
            } else {
                if (!$(this).hasClass('text-success')) {
                    $(this).removeClass('text-danger').addClass('text-success');
                    celdaEstado.html('<span class="badge bg-success shadow-sm" style="font-size: 0.65rem;">Vigente</span>');
                }
                contadorVigentes++;
            }
        });

        $('#kpi-vigentes').text(contadorVigentes);
    }

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
            ],
            "drawCallback": function() {
                actualizarGarantiasVivas();
            }
        });

        $('#searchMaquinas').on('keyup', function() { table.search(this.value).draw(); });
        $('#filterModelo').on('change', function() { table.column(3).search(this.value).draw(); });
        $('#checkSoloVigentes').on('change', function() {
            table.column(6).search(this.checked ? 'Vigente' : '', true, false).draw();
        });
    }

    actualizarGarantiasVivas();
    setInterval(actualizarGarantiasVivas, 500);

    if (window.location.search.indexOf('msg=') > -1) {
        var clean_url = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: clean_url}, '', clean_url);

        setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function(){
                $(this).remove();
            });
        }, 4000);
    }
});
</script>

<?php include '../includes/footer.php'; ?>