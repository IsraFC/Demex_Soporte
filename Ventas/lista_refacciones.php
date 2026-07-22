<?php
/**
 * ARCHIVO: Ventas/lista_refacciones.php
 * DESCRIPCIÓN: Listado especializado del catálogo de Refacciones Técnicas DEMEX.
 * Decodifica dinámicamente el bloque JSON de atributos para componentes y empaques.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 (Maquetado simétrico para Refacciones Técnicas)
 */

$page_title = "Catálogo de Refacciones | CRM Ventas";
require_once '../config/db.php';

// Filtramos estrictamente por la categoría 4 que corresponde a 'Refacciones'
$sql = "SELECT p.*, c.nombre_categoria 
        FROM productos p
        INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria
        WHERE p.id_categoria = 4
        ORDER BY p.nombre ASC";
$stmt = $pdo->query($sql);
$refacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-nut"></i> Catálogo de Refacciones Técnicas</h1>
        <p class="text-muted small">Componentes críticos, empaques, navajas y refacciones compatibles con equipos oficiales.</p>
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
        <table id="tablaRefacciones" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>SKU / Código</th>
                    <th>Nombre de la Pieza</th>
                    <th>Número de Parte</th>
                    <th>Modelos de Máquinas Compatibles</th>
                    <th class="text-end">P. Público</th>
                    <th class="text-end">P. Distribuidor</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center" style="width: 100px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($refacciones as $ref): 
                    $attrs = json_decode($ref['atributos_especificos'], true) ?? [];
                ?>
                <tr>
                    <td class="fw-bold text-secondary small">
                        <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($ref['sku_codigo']) ?></span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($ref['nombre']) ?></div>
                        <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="<?= htmlspecialchars($ref['descripcion'] ?? '') ?>">
                            <?= htmlspecialchars($ref['descripcion'] ?? 'Sin descripción comercial.') ?>
                        </small>
                    </td>
                    <td class="small fw-bold text-secondary">
                        <?= htmlspecialchars($attrs['no_parte'] ?? 'N/A') ?>
                    </td>
                    <td class="small text-muted fw-medium">
                        <?= htmlspecialchars($attrs['compatibilidad'] ?? 'Compatible global') ?>
                    </td>
                    <td class="text-end fw-bold text-dark">
                        $<?= number_format($ref['precio_publico'], 2) ?>
                    </td>
                    <td class="text-end fw-bold text-danger">
                        $<?= number_format($ref['precio_distribuidor'], 2) ?>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold <?= ($ref['stock'] > 0) ? 'text-success' : 'text-danger' ?>">
                            <?= $ref['stock'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="editar_producto.php?id_producto=<?= $ref['id_producto'] ?>" class="btn btn-outline-warning border-0" title="Editar Precios y Stock">
                                <i class="bi bi-pencil-square fs-5"></i>
                            </a>
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
    $('#tablaRefacciones').DataTable({
        "searching": false,
        "lengthChange": false,
        "language": { 
            "emptyTable": "No hay refacciones registradas en el catálogo", 
            "info": "Mostrando _START_ a _END_ de _TOTAL_ refacciones", 
            "infoEmpty": "0 registros", 
            "infoFiltered": "(filtrado de _MAX_)", 
            "zeroRecords": "Sin coincidencias encontradas", 
            "paginate": { "next": "Sig.", "previous": "Ant." } 
        },
        "pageLength": 100,
        "responsive": true,
        "ordering": true
    });
});
</script>