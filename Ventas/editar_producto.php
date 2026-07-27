<?php
/**
 * ARCHIVO: Ventas/editar_producto.php
 * DESCRIPCIÓN: Interfaz reactiva para la edición de productos del catálogo.
 * Detecta el origen del producto para retornar a su lista correspondiente.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 2.1 (Retorno dinámico a listas especializadas)
 */

$page_title = "Editar Producto | CRM Ventas";
require_once '../config/db.php';

$id_producto = isset($_GET['id_producto']) ? intval($_GET['id_producto']) : 0;

if ($id_producto <= 0) {
    header("Location: catalogo_productos.php");
    exit();
}

// 1. Consultamos el producto y traemos el nombre de su categoría
$sql = "SELECT p.*, c.nombre_categoria, c.codigo_prefijo 
        FROM productos p
        INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria
        WHERE p.id_producto = :id_producto LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id_producto' => $id_producto]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header("Location: catalogo_productos.php?msg=error_no_encontrado");
    exit();
}

// Mapeo dinámico para el botón Cancelar y el redireccionamiento post-guardado
$url_retorno = 'catalogo_productos.php';
switch (intval($producto['id_categoria'])) {
    case 1: $url_retorno = 'lista_maquinas.php'; break;
    case 2: $url_retorno = 'lista_bases.php'; break;
    case 3: $url_retorno = 'lista_saborizantes.php'; break;
    case 4: $url_retorno = 'lista_refacciones.php'; break;
}

// 2. Decodificamos de forma segura el bloque JSON de atributos específicos
$attrs = json_decode($producto['atributos_especificos'], true) ?? [];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-12">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-pencil-square"></i> Editar Producto</h1>
        <p class="text-muted small">Modifica los precios, stock o especificaciones técnicas del registro seleccionado.</p>
    </div>
</div>

<div class="card-main mb-4 py-4 px-4 shadow-sm border-top border-4 border-danger bg-white rounded animate__animated animate__fadeInUp">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-sliders text-danger me-2"></i> Ficha de Modificación</h5>
    
    <form action="../actions/procesar_edicion_producto.php" method="POST" id="formEditarProducto">
        <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
        <input type="hidden" id="id_categoria_actual" value="<?= $producto['id_categoria'] ?>">
        
        <!-- === SECCIÓN 1: DATOS MAESTROS === -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Categoría Asignada</label>
                <input type="text" class="form-control bg-light fw-bold text-secondary" value="<?= htmlspecialchars($producto['nombre_categoria']) ?>" readonly>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold text-dark small">Nombre del Producto / Modelo <span class="text-danger">*</span></label>
                <!-- Ojo Mau: Si es registro manual o de catálogo, dejamos el nombre visible pero editable o lectura según prefieras -->
                <input type="text" class="form-control fw-bold" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Código SKU Único</label>
                <input type="text" class="form-control text-uppercase fw-semibold bg-light text-secondary" name="sku_codigo" value="<?= htmlspecialchars($producto['sku_codigo']) ?>" readonly>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Precio Público ($ MXN) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control fw-bold text-dark" name="precio_publico" step="0.01" min="0" value="<?= $producto['precio_publico'] ?>" required>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Precio Distribuidor ($ MXN) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control fw-bold text-danger" name="precio_distribuidor" step="0.01" min="0" value="<?= $producto['precio_distribuidor'] ?>" required>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Stock Actual Disponible <span class="text-danger">*</span></label>
                <input type="number" class="form-control fw-bold" name="stock" value="<?= $producto['stock'] ?>" min="0" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12">
                <label class="form-label fw-semibold text-dark small">Ficha Técnica / Descripción Comercial Corta</label>
                <textarea class="form-control" name="descripcion" rows="3"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- === SECCIÓN 2: CAMPOS DINÁMICOS === -->
        
        <!-- Bloque Máquinas (ID 1) -->
        <div id="bloque_maquinas" class="bloque-dinamico border-top pt-3 mt-3" style="display: none;">
            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-cpu me-2"></i> Especificaciones de Maquinaria</h6>
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-dark small">Línea del Equipo</label>
                    <input type="text" class="form-control" name="attr_linea" value="<?= htmlspecialchars($attrs['linea'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-dark small">Tipo de Helado</label>
                    <input type="text" class="form-control" name="attr_tipo_helado" value="<?= htmlspecialchars($attrs['tipo_helado'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-dark small">Corriente / Voltaje</label>
                    <input type="text" class="form-control" name="attr_voltaje" value="<?= htmlspecialchars($attrs['voltaje'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-dark small">Capacidad de Producción</label>
                    <input type="text" class="form-control" name="attr_capacidad" value="<?= htmlspecialchars($attrs['capacidad'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Bloque Insumos (Bases/Saborizantes - ID 2 y 3) -->
        <div id="bloque_insumos" class="bloque-dinamico border-top pt-3 mt-3" style="display: none;">
            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-egg-fried me-2"></i> Detalles de Materia Prima</h6>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Sabor / Variante</label>
                    <input type="text" class="form-control" name="attr_sabor" value="<?= htmlspecialchars($attrs['sabor'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Presentación / Peso por Unidad</label>
                    <input type="text" class="form-control" name="attr_peso" value="<?= htmlspecialchars($attrs['peso'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Rendimiento Estimado</label>
                    <input type="text" class="form-control" name="attr_rendimiento" value="<?= htmlspecialchars($attrs['rendimiento'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Bloque Refacciones (ID 4) -->
        <div id="bloque_refacciones" class="bloque-dinamico border-top pt-3 mt-3" style="display: none;">
            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-nut me-2"></i> Ficha de Refacción Técnica</h6>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Número de Parte</label>
                    <input type="text" class="form-control text-uppercase fw-bold" name="attr_no_parte" value="<?= htmlspecialchars($attrs['no_parte'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold text-dark small">Modelos Compatibles</label>
                    <input type="text" class="form-control" name="attr_compatibilidad" value="<?= htmlspecialchars($attrs['compatibilidad'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- === SECCIÓN 3: BOTONES DE ACCIÓN === -->
        <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-4 mt-4">
            <!-- CORREGIDO: Cancela y te regresa a la lista específica -->
            <a href="<?= $url_retorno ?>" class="btn btn-secondary py-2 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-x-circle me-1"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-danger py-2 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-file-earmark-check-fill me-2"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    
    // 1. Renderizado Automático del bloque correcto al abrir la página
    const idCat = parseInt($('#id_categoria_actual').val());
    if (idCat === 1) {
        $('#bloque_maquinas').show();
    } else if (idCat === 2 || idCat === 3) {
        $('#bloque_insumos').show();
    } else if (idCat === 4) {
        $('#bloque_refacciones').show();
    }

    // Variable para redirección dinámica leída desde PHP
    const urlRetorno = '<?= $url_retorno ?>';

    // 2. Intercepción asíncrona del envío para el SweetAlert de éxito estilo Recompras
    $('#formEditarProducto').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '../actions/procesar_edicion_producto.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: '¡Cambios Guardados!',
                        text: 'El producto ha sido modificado y actualizado con éxito en el catálogo.',
                        icon: 'success',
                        confirmButtonColor: '#198754',
                        confirmButtonText: 'Entendido'
                    }).then(() => {
                        // CORREGIDO: Redirecciona de vuelta a la lista origen (ej. lista_maquinas.php)
                        window.location.href = urlRetorno;
                    });
                } else {
                    Swal.fire({
                        title: 'Error en Actualización',
                        text: response.message,
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Revisar'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error de Servidor',
                    text: 'No se pudo conectar con el procesador central de catálogos.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Entendido'
                });
            }
        });
    });

});
</script>