<?php
/**
 * ARCHIVO: Soporte/tecnicos.php
 * DESCRIPCIÓN: Panel de control y directorio de técnicos de soporte.
 * Integra DataTables para la gestión de contactos, separación modular de teléfonos, mapas y edición.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.6
 */

require_once '../config/db.php';
$page_title = "Directorio de Técnicos - Soporte";
$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-person-lines-fill me-2"></i> Directorio de Técnicos</h1>
        <p class="text-muted small">Gestión interna de contactos, zonas operativas y números de atención.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="registro_tecnico.php" class="btn btn-outline-danger shadow-sm px-5 rounded-pill fw-bold">
            <i class="bi bi-person-plus-fill me-2"></i> Nuevo Técnico
        </a>
    </div>
</div>
    
<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-0 align-items-center px-3 justify-content-between">
        <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-danger"></i></span>
            <input type="text" id="searchTecnicos" class="form-control bg-transparent border-0" placeholder="Buscar técnico por nombre o ubicación...">
        </div>
    </div>
</div>
    
<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaTecnicos" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th width="50">#</th>
                    <th>Nombre / Entidad</th>
                    <th>Ubicación Operativa</th>
                    <th>Teléfono(s) de Contacto</th>
                    <th width="80" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT t.*, GROUP_CONCAT(tel.telefono SEPARATOR ',') as telefonos 
                        FROM tecnicos t 
                        LEFT JOIN tecnicos_telefonos tel ON t.id_tecnico = tel.id_tecnico 
                        GROUP BY t.id_tecnico 
                        ORDER BY t.nombre ASC";
                
                $stmt = $pdo->query($sql);
                $i = 1;

                while ($row = $stmt->fetch()):
                    $direccion_completa = $row['zona'] . ', ' . $row['estado'] . ', México';
                ?>
                <tr>
                    <td class="text-muted fw-bold"><?= $i++ ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nombre']) ?></div>
                        <small class="text-muted">ID: TEC-<?= str_pad($row['id_tecnico'], 3, "0", STR_PAD_LEFT) ?></small>
                    </td>
                    <td>
                        <a href="#" class="text-decoration-none view-map" 
                           data-direccion="<?= htmlspecialchars($direccion_completa) ?>" 
                           data-nombre="<?= htmlspecialchars($row['nombre']) ?>">
                            <span class="small text-muted fw-semibold">
                                <i class="bi bi-geo-alt me-1 text-danger"></i> 
                                <?= htmlspecialchars($row['zona'] . ', ' . $row['estado']) ?>
                            </span>
                        </a>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <?php 
                            if (!empty($row['telefonos'])): 
                                $lista_telefonos = explode(',', $row['telefonos']);
                                foreach ($lista_telefonos as $tel):
                            ?>
                                    <span class="badge bg-light text-danger border border-danger rounded-pill p-2 small fw-normal">
                                        <i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($tel) ?>
                                    </span>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <span class="small text-muted"><i class="bi bi-telephone me-1"></i> Sin líneas registradas</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="text-center">
                        <a href="editar_tecnico.php?id=<?= $row['id_tecnico'] ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar Técnico">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#tablaTecnicos').DataTable({
        "language": {
            "emptyTable": "No hay técnicos registrados en el directorio",
            "info": "Mostrando _START_ a _END_ de _TOTAL_",
            "infoEmpty": "0 registros",
            "infoFiltered": "(filtrado de _MAX_)",
            "zeroRecords": "Sin coincidencias encontradas",
            "paginate": { "next": "Sig.", "previous": "Ant." }
        },
        "dom": 'rtip',
        "pageLength": 10
    });

    $('#searchTecnicos').on('keyup', function() {
        table.search(this.value).draw();
    });

    $(document).on('click', '.view-map', function(e) {
        e.preventDefault();
        var direccion = $(this).data('direccion');
        var nombre = $(this).data('nombre');
        if (!direccion) return;
        
        $('#mapTitle').text('Ubicación Operativa: ' + nombre);
        $('#mapAddressText').text(direccion);
        
        var embedUrl = "https://maps.google.com/maps?q=" + encodeURIComponent(direccion) + "&t=&z=14&ie=UTF8&iwloc=&output=embed";
        $('#mapIframe').attr('src', embedUrl);
        
        var gMapsUrl = "https://maps.google.com/?q=" + encodeURIComponent(direccion);
        $('#btnGoogleMaps').attr('href', gMapsUrl);
        
        $('#mapModal').appendTo("body").modal('show');
    });

    $(document).on('hidden.bs.modal', '#mapModal', function () {
        $('#mapIframe').attr('src', '');
    });
});
</script>

<?php 
include '../includes/modal_mapa.php';
include '../includes/footer.php'; 
?>