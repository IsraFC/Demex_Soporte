<?php
/**
 * ARCHIVO: Ventas/lista_bases.php
 * DESCRIPCIÓN: Listado especializado del catálogo de Bases para Helado DEMEX.
 * Decodifica dinámicamente el bloque JSON de atributos e integra vista de ficha técnica en Modal XL optimizado.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.5 (Rediseño visual de Ficha Técnica: Imagen flotante, Tabla de Especificaciones y Colores Neutros)
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

<!-- ================= MODAL DETALLE DE INSUMO OPTIMIZADO ================= -->
<div class="modal fade" id="modalDetalleBase" tabindex="-1" aria-labelledby="modalDetalleBaseLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-danger text-white py-3 px-4" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title fw-bold" id="modalDetalleBaseLabel"><i class="bi bi-moisture me-2"></i> Ficha Técnica de Materia Prima</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row g-4 align-items-center">
                    <!-- Sección Izquierda: Imagen Flotante Transparente -->
                    <div class="col-12 col-lg-4 text-center border-end border-light pe-lg-4 d-flex align-items-center justify-content-center" style="min-height: 320px;">
                        <div class="w-100 d-flex align-items-center justify-content-center" style="height: 280px;">
                            <img src="" id="modal_img_eq" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain; filter: drop-shadow(0px 8px 12px rgba(0,0,0,0.12));" alt="Fotografía del Insumo">
                        </div>
                    </div>

                    <!-- Sección Derecha: Información Estructurada -->
                    <div class="col-12 col-lg-8 ps-lg-4">
                        <!-- Header del Producto -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h2 class="fw-bold text-dark mb-0" id="modal_lbl_nombre">-</h2>
                            <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                                <span class="badge bg-dark px-3 py-2 fw-bold text-uppercase" id="modal_lbl_sku" style="font-size:0.8rem;">SKU: -</span>
                                <span class="badge bg-primary px-3 py-2 fw-bold" id="modal_lbl_stock" style="font-size:0.8rem;">Stock: -</span>
                            </div>
                        </div>

                        <!-- Caja Formateada y Scrollable para Descripción -->
                        <div class="mb-3">
                            <label class="small text-muted fw-bold text-uppercase mb-1"><i class="bi bi-file-earmark-text text-danger me-1"></i> Descripción Comercial / Especificaciones</label>
                            <div class="p-3 bg-light rounded-3 border border-light-subtle shadow-sm" style="max-height: 110px; overflow-y: auto; font-size: 0.85rem; line-height: 1.6; color: #334155;" id="modal_lbl_desc">
                                -
                            </div>
                        </div>
                        
                        <!-- Lista Estructurada de Especificaciones -->
                        <div class="card border-0 bg-light p-3 rounded-3 mb-3 shadow-sm">
                            <div class="row g-2 align-items-center border-bottom pb-2 mb-2">
                                <div class="col-4 text-muted small fw-bold text-uppercase"><i class="bi bi-droplet-fill text-danger me-1"></i> Sabor / Variante:</div>
                                <div class="col-8 fw-bold text-dark small" id="modal_lbl_sabor">-</div>
                            </div>
                            <div class="row g-2 align-items-center border-bottom pb-2 mb-2">
                                <div class="col-4 text-muted small fw-bold text-uppercase"><i class="bi bi-box-seam text-secondary me-1"></i> Presentación / Peso:</div>
                                <div class="col-8 fw-bold text-dark small" id="modal_lbl_peso">-</div>
                            </div>
                            <div class="row g-2 align-items-start">
                                <div class="col-4 text-muted small fw-bold text-uppercase"><i class="bi bi-speedometer2 text-primary me-1"></i> Rendimiento Sugerido:</div>
                                <div class="col-8 fw-semibold text-secondary small" id="modal_lbl_rendimiento" style="line-height: 1.4;">-</div>
                            </div>
                        </div>

                        <!-- Banner de Precios con Fondo Neutro -->
                        <div class="p-3 bg-light border rounded-3 d-flex align-items-center justify-content-around text-center shadow-sm">
                            <div>
                                <small class="text-muted d-block small text-uppercase fw-bold">Precio Público</small>
                                <span class="fs-4 fw-bold text-primary" id="modal_lbl_publico">-</span>
                            </div>
                            <div class="border-end border-secondary border-opacity-25" style="height: 35px;"></div>
                            <div>
                                <small class="text-muted d-block small text-uppercase fw-bold">Precio Distribuidor</small>
                                <span class="fs-4 fw-bold text-danger" id="modal_lbl_distribuidor">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-0">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal" style="border-radius:6px;">Cerrar Ficha</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function formatearTextoDescripcion(texto) {
    if (!texto || texto === 'Sin descripción comercial.') return 'Sin descripción comercial.';
    
    let formateado = texto
        .replace(/(Base oficial)/gi, '<strong>$1</strong>')
        .replace(/(Cada \d+)/g, '<br>• <strong>$1</strong>')
        .replace(/(Dilución:)/gi, '<br>• <strong>$1</strong>')
        .replace(/(Rendimiento:)/gi, '<br>• <strong>$1</strong>')
        .replace(/(Tiempo de caducidad:)/gi, '<br>• <strong>$1</strong>');
        
    return formateado;
}

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
        
        $('#modal_lbl_desc').html(formatearTextoDescripcion(desc));
        
        $('#modal_lbl_stock').text('Stock: ' + stock + ' Bultos').removeClass('bg-primary bg-danger');
        if (parseInt(stock) > 0) {
            $('#modal_lbl_stock').addClass('bg-primary');
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