<?php
/**
 * ARCHIVO: Ventas/lista_saborizantes.php
 * DESCRIPCIÓN: Listado especializado del catálogo de Saborizantes Premium DEMEX.
 * Decodifica dinámicamente el bloque JSON de atributos para concentrados y veteados.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.1 (Integración de borrado asíncrono con SweetAlert2)
 */

$page_title = "Catálogo de Saborizantes | CRM Ventas";
require_once '../config/db.php';

// Filtramos estrictamente por la categoría 3 que corresponde a 'Saborizantes'
$sql = "SELECT p.*, c.nombre_categoria 
        FROM productos p
        INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria
        WHERE p.id_categoria = 3
        ORDER BY p.nombre ASC";
$stmt = $pdo->query($sql);
$saborizantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-funnel-fill"></i> Catálogo de Saborizantes Premium</h1>
        <p class="text-muted small">Concentrados de fruta y veteados comerciales para la producción de helados.</p>
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
        <table id="tablaSaborizantes" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>SKU / Código</th>
                    <th>Descripción Comercial</th>
                    <th>Sabor / Concentrado</th>
                    <th>Presentación / Empaque</th>
                    <th>Rendimiento</th>
                    <th class="text-end">P. Público</th>
                    <th class="text-end">P. Distribuidor</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center" style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($saborizantes as $sab): 
                    $attrs = json_decode($sab['atributos_especificos'], true) ?? [];
                ?>
                <tr id="fila-producto-<?= $sab['id_producto'] ?>">
                    <td class="fw-bold text-secondary small">
                        <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($sab['sku_codigo']) ?></span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($sab['nombre']) ?></div>
                        <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="<?= htmlspecialchars($sab['descripcion'] ?? '') ?>">
                            <?= htmlspecialchars($sab['descripcion'] ?? 'Sin descripción comercial.') ?>
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
                        $<?= number_format($sab['precio_publico'], 2) ?>
                    </td>
                    <td class="text-end fw-bold text-danger">
                        $<?= number_format($sab['precio_distribuidor'], 2) ?>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold <?= ($sab['stock'] > 0) ? 'text-success' : 'text-danger' ?>">
                            <?= $sab['stock'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="editar_producto.php?id_producto=<?= $sab['id_producto'] ?>" class="btn btn-outline-warning border-0" title="Editar Precios y Stock">
                                <i class="bi bi-pencil-square fs-5"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger border-0 btn-eliminar-producto" data-id="<?= $sab['id_producto'] ?>" data-nombre="<?= htmlspecialchars($sab['nombre']) ?>" title="Eliminar Producto del Catálogo">
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

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // 1. Inicialización limpia de la tabla
    const table = $('#tablaSaborizantes').DataTable({
        "searching": false,
        "lengthChange": false,
        "language": { 
            "emptyTable": "No hay saborizantes registrados en el catálogo", 
            "info": "Mostrando _START_ a _END_ de _TOTAL_ saborizantes", 
            "infoEmpty": "0 registros", 
            "infoFiltered": "(filtrado de _MAX_)", 
            "zeroRecords": "Sin coincidencias encontradas", 
            "paginate": { "next": "Sig.", "previous": "Ant." } 
        },
        "pageLength": 100,
        "responsive": true,
        "ordering": true
    });

    // 2. Intercepción asíncrona del evento clic para eliminación
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
                                text: 'El saborizante ha sido retirado del sistema con éxito.',
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