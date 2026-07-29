<?php
/**
 * ARCHIVO: Ventas/lista_bases.php
 * DESCRIPCIÓN: Listado especializado del catálogo de Bases para Helado DEMEX.
 * Decodifica dinámicamente el bloque JSON de atributos e integra vista de ficha técnica en Modal.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.3 (Ruta corregida estrictamente hacia la carpeta img/bases/)
 */

$page_title = "Catálogo de Bases para Helado | CRM Ventas";
require_once '../config/db.php';

// Filtramos estrictamente por la categoría 2 que corresponde a 'Bases para Helado'
$sql = "SELECT p.*, c.nombre_categoria 
        FROM productos p
        INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria
        WHERE p.id_categoria = 2
        ORDER BY p.nombre ASC";
$stmt = $pdo->query($sql);
$bases = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-moisture"></i> Catálogo de Bases para Helado</h1>
        <p class="text-muted small">Listado de insumos base en bulto y fórmulas oficiales de la empresa.</p>
    </div>
    <div class="col-md-5 text-md-end">
        <a href="catalogo_productos.php" class="btn btn-secondary py-2 px-3 fw-bold shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-arrow-left-short fs-5"></i> Regresar al Catálogo
        </a>
    </div>
</div>

<!-- Contenedor Tabla Master -->
<div class="card-main shadow-lg p-4 bg-white rounded animate__animated animate__fadeInUp">
    <div class="table-responsive">
        <table id="tablaBases" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>SKU / Código</th>
                    <th>Descripción del Insumo</th>
                    <th>Sabor / Variante</th>
                    <th>Presentación / Peso</th>
                    <th>Rendimiento Sugerido</th>
                    <th class="text-end">P. Público</th>
                    <th class="text-end">P. Distribuidor</th>
                    <th class="text-center">Stock (Bultos)</th>
                    <th class="text-center" style="width: 140px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($bases as $base): 
                    $attrs = json_decode($base['atributos_especificos'], true) ?? [];
                    $img_name = !empty($attrs['imagen']) ? $attrs['imagen'] : '';
                ?>
                <tr id="fila-producto-<?= $base['id_producto'] ?>">
                    <td class="fw-bold text-secondary small">
                        <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($base['sku_codigo']) ?></span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($base['nombre']) ?></div>
                        <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="<?= htmlspecialchars($base['descripcion'] ?? '') ?>">
                            <?= htmlspecialchars($base['descripcion'] ?? 'Sin descripción comercial.') ?>
                        </small>
                    </td>
                    <td class="small fw-semibold text-dark">
                        <?= htmlspecialchars($attrs['sabor'] ?? 'N/A') ?>
                    </td>
                    <td class="small text-muted fw-medium">
                        <?= htmlspecialchars($attrs['peso'] ?? 'N/A') ?>
                    </td>
                    <td class="small text-muted">
                        <?= htmlspecialchars($attrs['rendimiento'] ?? 'N/A') ?>
                    </td>
                    <td class="text-end fw-bold text-dark">
                        $<?= number_format($base['precio_publico'], 2) ?>
                    </td>
                    <td class="text-end fw-bold text-danger">
                        $<?= number_format($base['precio_distribuidor'], 2) ?>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold <?= ($base['stock'] > 0) ? 'text-success' : 'text-danger' ?>">
                            <?= $base['stock'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-info border-0 btn-ver-detalle" 
                                    data-nombre="<?= htmlspecialchars($base['nombre']) ?>"
                                    data-sku="<?= htmlspecialchars($base['sku_codigo']) ?>"
                                    data-sabor="<?= htmlspecialchars($attrs['sabor'] ?? 'N/A') ?>"
                                    data-peso="<?= htmlspecialchars($attrs['peso'] ?? 'N/A') ?>"
                                    data-rendimiento="<?= htmlspecialchars($attrs['rendimiento'] ?? 'N/A') ?>"
                                    data-publico="$<?= number_format($base['precio_publico'], 2) ?>"
                                    data-distribuidor="$<?= number_format($base['precio_distribuidor'], 2) ?>"
                                    data-stock="<?= $base['stock'] ?>"
                                    data-desc="<?= htmlspecialchars($base['descripcion'] ?? 'Sin descripción comercial.') ?>"
                                    data-imagen="<?= $img_name ?>"
                                    title="Ver Detalles Completos e Imagen">
                                <i class="bi bi-eye fs-5"></i>
                            </button>
                            <a href="editar_producto.php?id_producto=<?= $base['id_producto'] ?>" class="btn btn-outline-warning border-0" title="Editar Precios y Stock">
                                <i class="bi bi-pencil-square fs-5"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger border-0 btn-eliminar-producto" data-id="<?= $base['id_producto'] ?>" data-nombre="<?= htmlspecialchars($base['nombre']) ?>" title="Eliminar Producto del Catálogo">
                                <i class="bi bi-trash fs-5"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= MODAL DETALLE DE INSUMO ================= -->
<div class="modal fade" id="modalDetalleBase" tabindex="-1" aria-labelledby="modalDetalleBaseLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-danger text-white py-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold" id="modalDetalleBaseLabel"><i class="bi bi-moisture me-2"></i> Ficha Técnica de Materia Prima</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row g-4">
                    <!-- Sección Izquierda: Imagen del Insumo -->
                    <div class="col-12 col-md-5 text-center d-flex flex-column align-items-center justify-content-center border-end border-light">
                        <div class="p-2 border rounded bg-light shadow-sm w-100 d-flex align-items-center justify-content-center" style="height: 250px; overflow: hidden;">
                            <img src="" id="modal_img_eq" class="img-fluid rounded" style="max-height: 100%; object-fit: contain;" alt="Fotografía del Insumo">
                        </div>
                        <span class="badge bg-secondary px-3 py-2 mt-3 fw-bold text-uppercase w-100" id="modal_lbl_sku" style="font-size:0.85rem;">SKU: -</span>
                    </div>
                    <!-- Sección Derecha: Información Estructurada -->
                    <div class="col-12 col-md-7">
                        <h3 class="fw-bold text-danger mb-1" id="modal_lbl_nombre">-</h3>
                        <p class="text-muted small mb-3 border-bottom pb-2" id="modal_lbl_desc">-</p>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <div class="p-2 bg-light rounded border-start border-3 border-danger">
                                    <small class="text-muted d-block small text-uppercase fw-semibold">Sabor / Variante</small>
                                    <span class="fw-bold text-dark" id="modal_lbl_sabor">-</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded border-start border-3 border-danger">
                                    <small class="text-muted d-block small text-uppercase fw-semibold">Presentación / Peso</small>
                                    <span class="fw-semibold text-secondary small" id="modal_lbl_peso">-</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded border-start border-3 border-danger">
                                    <small class="text-muted d-block small text-uppercase fw-semibold">Rendimiento Sugerido</small>
                                    <span class="fw-semibold text-secondary small" id="modal_lbl_rendimiento">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-4">
                                <small class="text-muted d-block small text-uppercase fw-semibold">P. Público</small>
                                <span class="fs-5 fw-bold text-dark" id="modal_lbl_publico">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block small text-uppercase fw-semibold">P. Distribuidor</small>
                                <span class="fs-5 fw-bold text-danger" id="modal_lbl_distribuidor">-</span>
                            </div>
                            <div class="col-4 text-center">
                                <small class="text-muted d-block small text-uppercase fw-semibold">Bultos Stock</small>
                                <span class="badge bg-success px-3 py-2 fw-bold mt-1" id="modal_lbl_stock" style="font-size:0.9rem;">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal" style="border-radius:6px;">Cerrar Ficha</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    const table = $('#tablaBases').DataTable({
        "searching": false,
        "lengthChange": false,
        "language": { 
            "emptyTable": "No hay bases para helado registradas en el catálogo", 
            "info": "Mostrando _START_ a _END_ de _TOTAL_ insumos", 
            "infoEmpty": "0 registros", 
            "infoFiltered": "(filtrado de _MAX_)", 
            "zeroRecords": "Sin coincidencias encontradas", 
            "paginate": { "next": "Sig.", "previous": "Ant." } 
        },
        "pageLength": 100,
        "responsive": true,
        "ordering": true
    });

    // === LÓGICA REACTIVA PARA CARGAR EL MODAL DE DETALLES ===
    $(document).on('click', '.btn-ver-detalle', function() {
        const nombre = $(this).data('nombre');
        const sku = $(this).data('sku');
        const sabor = $(this).data('sabor');
        const peso = $(this).data('peso');
        const rendimiento = $(this).data('rendimiento');
        const publico = $(this).data('publico');
        const distribuidor = $(this).data('distribuidor');
        const stock = $(this).data('stock');
        const desc = $(this).data('desc');
        const imagen = $(this).data('imagen');

        // CORREGIDO: Apunta de forma estricta al directorio de bases
        if (imagen !== '') {
            $('#modal_img_eq').attr('src', '../img/bases/' + imagen);
        } else {
            $('#modal_img_eq').attr('src', 'https://placehold.co/400x400/f8f9fa/6c757d?text=Sin+Imagen+Oficial');
        }

        $('#modal_lbl_nombre').text(nombre);
        $('#modal_lbl_sku').text('SKU: ' + sku);
        $('#modal_lbl_sabor').text(sabor);
        $('#modal_lbl_peso').text(peso);
        $('#modal_lbl_rendimiento').text(rendimiento);
        $('#modal_lbl_publico').text(publico);
        $('#modal_lbl_distribuidor').text(distribuidor);
        $('#modal_lbl_desc').text(desc);
        
        $('#modal_lbl_stock').text(stock).removeClass('bg-success bg-danger');
        if (parseInt(stock) > 0) {
            $('#modal_lbl_stock').addClass('bg-success');
        } else {
            $('#modal_lbl_stock').addClass('bg-danger');
        }

        $('#modalDetalleBase').appendTo("body").modal('show');
    });

    // === EVENTO ASÍNCRONO DE ELIMINACIÓN ===
    $(document).on('click', '.btn-eliminar-producto', function() {
        const idProducto = $(this).data('id');
        const nombreProducto = $(this).data('nombre');

        Swal.fire({
            title: '¿Eliminar del Catálogo?',
            text: `¿Estás seguro de quitar "${nombreProducto}"? Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'eliminar_producto.php', 
                    method: 'POST',
                    data: { id_producto: idProducto },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: 'El insumo ha sido retirado del sistema con éxito.',
                                icon: 'success',
                                confirmButtonColor: '#198754',
                                confirmButtonText: 'Entendido'
                            });
                            table.row(`#fila-producto-${idProducto}`).remove().draw(false);
                        } else {
                            Swal.fire({
                                title: 'Error al eliminar',
                                text: response.message,
                                icon: 'error',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error de Red',
                            text: 'No se pudo conectar con el servidor central.',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    });
});
</script>