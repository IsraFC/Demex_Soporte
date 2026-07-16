<?php
/**
 * ARCHIVO: clientes.php
 * DESCRIPCIÓN: Panel de control y directorio de clientes.
 * Integra DataTables para gestión de grandes volúmenes de datos, 
 * geolocalización asíncrona mediante un componente externo de mapas y comunicación directa por WhatsApp.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.6
 */
require_once '../config/db.php';
$page_title = "Clientes - Soporte";
$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-people me-2"></i> Directorio de Clientes</h1>
        <p class="text-muted small">Gestión de cuentas y ubicación de servicios.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="registro_cliente.php" class="btn btn-outline-danger shadow-sm px-5 rounded-pill fw-bold">
            <i class="bi bi-person-plus-fill me-2"></i> Nuevo Cliente
        </a>
    </div>
</div>
    
<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-0 align-items-center px-3 justify-content-between">
        <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-danger"></i></span>
            <input type="text" id="searchClientes" class="form-control bg-transparent border-0" placeholder="Buscar por nombre o ubicación...">
        </div>
    </div>
</div>
    
<div class="card-main shadow-lg p-4 bg-white rounded">
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
                        <div class="btn-group btm-group-sm">
                            <a href="editar_cliente.php?id=<?= $row['id_cliente'] ?>" class="btn btn-outline-primary border-0" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="registro_ticket.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-outline-danger border-0" title="Crear Ticket">
                                <i class="bi bi-ticket-perforated"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
        "dom": 'rtip', 
        "pageLength": 10
    });

    // 2. BUSCADOR EXTERNO: Vincula el input personalizado con la búsqueda de DataTables
    $('#searchClientes').on('keyup', function() {
        table.search(this.value).draw();
    });

    /**
     * 3. LÓGICA DE MAPAS (DELEGACIÓN DE EVENTOS)
     * Consume el modal centralizado delegando los clics para soportar paginación.
     */
    $(document).on('click', '.view-map', function(e) {
        e.preventDefault();
        
        var direccion = $(this).data('direccion');
        var nombre = $(this).data('nombre');

        if (!direccion || direccion === 'Sin dirección') {
            Swal.fire({
                title: 'Dirección no Registrada',
                text: 'Este cliente no tiene una dirección válida en el sistema.',
                icon: 'warning',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#C62828',
                buttonsStyling: true,
                customClass: {
                    popup: 'rounded-4 border-0 shadow-lg',
                    confirmButton: 'btn btn-demex rounded-pill px-4 fw-bold'
                }
            });
            return;
        }
        
        // Carga de metadatos en el modal común
        $('#mapTitle').text('Ubicación: ' + nombre);
        $('#mapAddressText').text(direccion);
        
        // Construcción de URLs dinámicas para Google Maps
        var embedUrl = "https://maps.google.com/maps?q=" + encodeURIComponent(direccion) + "&t=&z=16&ie=UTF8&iwloc=&output=embed";
        $('#mapIframe').attr('src', embedUrl);
        
        var gMapsUrl = "https://maps.google.com/?q=" + encodeURIComponent(direccion);
        $('#btnGoogleMaps').attr('href', gMapsUrl);
        
        // Desplazamos el modal a la raíz del body para romper el bloqueo de capas
        $('#mapModal').appendTo("body").modal('show');
    });

    /**
     * 4. LIMPIEZA DE RECURSOS
     */
    $(document).on('hidden.bs.modal', '#mapModal', function () {
        $('#mapIframe').attr('src', '');
    });
});
</script>

<?php 
// Inclusión del fragmento global reutilizable del mapa y cierre del layout maestro
include '../includes/modal_mapa.php'; 
include '../includes/footer.php'; 
?>