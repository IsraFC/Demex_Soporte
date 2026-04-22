<?php
/**
 * ARCHIVO: clientes.php
 * DESCRIPCIÓN: Panel de control y directorio de clientes.
 * Integra DataTables para gestión de grandes volúmenes de datos, 
 * geolocalización vía Google Maps Embed y comunicación directa por WhatsApp.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.5
 */
$pagina_actual = 'clientes'; 
require_once 'config/db.php';

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="fw-bold text-danger mb-0">Directorio de Clientes</h1>
        <p class="text-muted small">Gestión de cuentas y ubicación de servicios.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="registro_cliente.php" class="btn btn-danger rounded-pill px-4 shadow-sm">
            <i class="bi bi-person-plus-fill me-2"></i> Nuevo Cliente
        </a>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger">
    
    <div class="mb-4">
        <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-danger"></i></span>
            <input type="text" id="searchClientes" class="form-control bg-transparent border-0" placeholder="Buscar por nombre o ubicación...">
        </div>
    </div>

    <div class="table-responsive">
        <table id="tablaClientes" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th width="50">#</th>
                    <th>Nombre / Empresa</th>
                    <th>Teléfono</th>
                    <th>Ubicación</th>
                    <th class="text-center">Equipos</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                /**
                 * CONSULTA: Se incluye un subquery para contar los equipos asociados
                 * a cada cliente en tiempo real.
                 */
                $sql = "SELECT c.*, 
                        (SELECT COUNT(*) FROM Equipos_Garantia WHERE id_cliente = c.id_cliente) as total_equipos 
                        FROM Clientes c 
                        ORDER BY c.nombre_cliente ASC";
                
                $stmt = $pdo->query($sql);
                $i = 1;

                while ($row = $stmt->fetch()):
                ?>
                <tr>
                    <td class="text-muted fw-bold"><?= $i++ ?></td>
                    
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nombre_cliente']) ?></div>
                        <small class="text-muted">ID: CLI-<?= str_pad($row['id_cliente'], 3, "0", STR_PAD_LEFT) ?></small>
                    </td>
                    
                    <td>
                        <?php if ($row['telefono']): 
                            $tel_limpio = str_replace(' ', '', $row['telefono']); 
                            $wa_link = "https://wa.me/52" . $tel_limpio;
                        ?>
                            <a href="<?= $wa_link ?>" target="_blank" class="text-decoration-none shadow-sm badge rounded-pill bg-light text-success border border-success p-2">
                                <i class="bi bi-whatsapp me-1"></i> <?= htmlspecialchars($row['telefono']) ?>
                            </a>
                        <?php else: ?>
                            <span class="small text-muted"><i class="bi bi-telephone me-1"></i> Sin teléfono</span>
                        <?php endif; ?>
                    </td>
                    
                    <td>
                        <a href="#" class="text-decoration-none view-map" 
                           data-direccion="<?= htmlspecialchars($row['ubicacion']) ?>" 
                           data-nombre="<?= htmlspecialchars($row['nombre_cliente']) ?>">
                            <span class="small text-muted">
                                <i class="bi bi-geo-alt me-1 text-danger"></i> 
                                <?= $row['ubicacion'] ?: 'Sin dirección' ?>
                            </span>
                        </a>
                    </td>
                    
                    <td class="text-center">
                        <span class="badge bg-light text-danger border border-danger rounded-pill">
                            <?= $row['total_equipos'] ?> Eq.
                        </span>
                    </td>
                    
                    <td class="text-center">
                        <div class="btn-group shadow-sm rounded">
                            <a href="editar_cliente.php?id=<?= $row['id_cliente'] ?>" class="btn btn-sm btn-white border-end" title="Editar">
                                <i class="bi bi-pencil text-primary"></i>
                            </a>
                            <a href="registro_ticket.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-sm btn-white" title="Crear Ticket">
                                <i class="bi bi-plus-circle text-danger"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold" id="mapTitle">Ubicación del Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light" style="height: 450px;">
                <iframe id="mapIframe" width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
            <div class="modal-footer bg-white border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                    <span id="mapAddressText"></span>
                </div>
                <a href="#" id="btnGoogleMaps" target="_blank" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-google me-2"></i>Ver en Google Maps
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    /**
     * 1. CONFIGURACIÓN DE DATATABLES
     * Se traduce el plugin al español y se define la estructura DOM personalizada.
     */
    var table = $('#tablaClientes').DataTable({
        "language": {
            "emptyTable": "No hay clientes",
            "info": "Mostrando _START_ a _END_ de _TOTAL_",
            "infoEmpty": "0 registros",
            "infoFiltered": "(filtrado de _MAX_)",
            "zeroRecords": "Sin coincidencias",
            "paginate": { "next": "Sig.", "previous": "Ant." }
        },
        "dom": 'rtip', // Solo muestra Tabla, Información y Paginación (Buscador oculto para usar el personalizado)
        "pageLength": 10
    });

    // 2. BUSCADOR EXTERNO: Vincula el input personalizado con la búsqueda de DataTables
    $('#searchClientes').on('keyup', function() {
        table.search(this.value).draw();
    });

    /**
     * 3. LÓGICA DE MAPAS (DELEGACIÓN DE EVENTOS)
     * Se usa delegación (.on click) para asegurar que funcione tras filtrar o paginar.
     */
    $(document).on('click', '.view-map', function(e) {
        e.preventDefault();
        
        var direccion = $(this).data('direccion');
        var nombre = $(this).data('nombre');

        if (!direccion || direccion === 'Sin dirección') {
            alert('Este cliente no tiene una dirección válida registrada.');
            return;
        }
        
        // Carga de metadatos en el modal
        $('#mapTitle').text('Ubicación: ' + nombre);
        $('#mapAddressText').text(direccion);
        
        // Construcción de URLs dinámicas para Google Maps
        var embedUrl = "https://maps.google.com/maps?q=" + encodeURIComponent(direccion) + "&t=&z=16&ie=UTF8&iwloc=&output=embed";
        $('#mapIframe').attr('src', embedUrl);
        
        var gMapsUrl = "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(direccion);
        $('#btnGoogleMaps').attr('href', gMapsUrl);
        
        $('#mapModal').modal('show');
    });

    /**
     * 4. LIMPIEZA DE RECURSOS
     * Evita que el Iframe siga cargado en segundo plano al cerrar el modal.
     */
    $('#mapModal').on('hidden.bs.modal', function () {
        $('#mapIframe').attr('src', '');
    });
});
</script>

<?php include 'includes/footer.php'; ?>