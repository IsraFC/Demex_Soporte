<?php
/**
 * ARCHIVO: Ventas/lista_maquinas.php
 * DESCRIPCIÓN: Listado especializado del catálogo de Maquinaria DEMEX.
 * Decodifica dinámicamente el bloque JSON de atributos para rendimiento en DataTables.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.1 (Cabecera limpia sin buscador DataTables ni alta duplicada)
 */

$page_title = "Catálogo de Maquinaria | CRM Ventas";
require_once '../config/db.php';

// Filtramos estrictamente por la categoría 1 que corresponde a 'Máquinas'
$sql = "SELECT p.*, c.nombre_categoria 
        FROM productos p
        INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria
        WHERE p.id_categoria = 1
        ORDER BY p.nombre ASC";
$stmt = $pdo->query($sql);
$maquinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-cpu"></i> Inventario de Maquinaria</h1>
        <p class="text-muted small">Catálogo oficial de líneas Demex y Spice para helado suave y duro.</p>
    </div>
    <div class="col-md-5 text-md-end">
        <!-- CORREGIDO: Se quitó el botón de agregar máquina para centralizarlo todo en el panel principal -->
        <a href="catalogo_productos.php" class="btn btn-secondary py-2 px-3 fw-bold shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-arrow-left-short fs-5"></i> Regresar al Catálogo
        </a>
    </div>
</div>

<!-- Contenedor Tabla Master -->
<div class="card-main shadow-lg p-4 bg-white rounded animate__animated animate__fadeInUp">
    <div class="table-responsive">
        <table id="tablaMaquinas" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>SKU / Código</th>
                    <th>Modelo / Nombre</th>
                    <th>Línea</th>
                    <th>Tipo Helado</th>
                    <th>Voltaje / Corriente</th>
                    <th>Capacidad</th>
                    <th class="text-end">P. Público</th>
                    <th class="text-end">P. Distribuidor</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center" style="width: 100px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($maquinas as $maq): 
                    $attrs = json_decode($maq['atributos_especificos'], true) ?? [];
                ?>
                <tr>
                    <td class="fw-bold text-secondary small">
                        <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($maq['sku_codigo']) ?></span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($maq['nombre']) ?></div>
                        <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($maq['descripcion'] ?? '') ?>">
                            <?= htmlspecialchars($maq['descripcion'] ?? 'Sin descripción comercial.') ?>
                        </small>
                    </td>
                    <td class="small fw-semibold text-dark">
                        <?= htmlspecialchars($attrs['linea'] ?? 'Demex') ?>
                    </td>
                    <td>
                        <span class="badge <?= ($attrs['tipo_helado'] == 'Suave') ? 'bg-primary bg-opacity-10 text-primary' : 'bg-info bg-opacity-10 text-info' ?> fw-bold px-2 py-1" style="border-radius:6px;">
                            <?= htmlspecialchars($attrs['tipo_helado'] ?? 'Suave') ?>
                        </span>
                    </td>
                    <td class="small text-muted">
                        <?= htmlspecialchars($attrs['voltaje'] ?? 'N/A') ?>
                    </td>
                    <td class="small text-muted fw-medium">
                        <?= htmlspecialchars($attrs['capacidad'] ?? 'N/A') ?>
                    </td>
                    <td class="text-end fw-bold text-dark">
                        $<?= number_format($maq['precio_publico'], 2) ?>
                    </td>
                    <td class="text-end fw-bold text-danger">
                        $<?= number_format($maq['precio_distribuidor'], 2) ?>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold <?= ($maq['stock'] > 0) ? 'text-success' : 'text-danger' ?>">
                            <?= $maq['stock'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="editar_producto.php?id_producto=<?= $maq['id_producto'] ?>" class="btn btn-outline-warning border-0" title="Editar Especificaciones y Precios">
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
    // CORREGIDO: Configuración blindada para ocultar buscador ("Search") y paginación forzada de registros
    $('#tablaMaquinas').DataTable({
        "searching": false,      // Elimina por completo el input de buscador de arriba a la derecha
        "lengthChange": false,   // Elimina el dropdown "Show entries" que se bugeaba visualmente
        "language": { 
            "emptyTable": "No hay máquinas registradas en el catálogo", 
            "info": "Mostrando _START_ a _END_ de _TOTAL_ equipos", 
            "infoEmpty": "0 registros", 
            "infoFiltered": "(filtrado de _MAX_)", 
            "zeroRecords": "Sin coincidencias encontradas", 
            "paginate": { "next": "Sig.", "previous": "Ant." } 
        },
        "pageLength": 100, // Lo dejamos predeterminado alto para que liste todo limpiamente sin romper cortes
        "responsive": true,
        "ordering": true
    });
});
</script>