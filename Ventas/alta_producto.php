<?php
/**
 * ARCHIVO: Ventas/alta_producto.php
 * DESCRIPCIÓN: Formulario inteligente con precarga automática de Catálogos Oficiales (Precios y Fichas).
 * Sincroniza de forma reactiva selects condicionales para Máquinas, Insumos y Refacciones previas.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 2.1 (Corrección de desbloqueo estricto para sobreescritura de precios manuales)
 */

$page_title = "Registrar Nuevo Producto | CRM Ventas";
require_once '../config/db.php';

// 1. Jalamos las categorías base de la base de datos
$stmt_cat = $pdo->query("SELECT * FROM categorias_productos ORDER BY id_categoria ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 2. Jalamos las refacciones que YA se han registrado anteriormente para el selector dinámico de duplicados
$stmt_ref = $pdo->query("SELECT DISTINCT nombre FROM productos WHERE id_categoria = 4 ORDER BY nombre ASC");
$refacciones_existentes = $stmt_ref->fetchAll(PDO::FETCH_COLUMN);

// 3. Matriz Maestra de Precios Oficiales y Especificaciones de Maquinaria (.btn-danger standard)
$catalogo_maquinas = [
    'SPICE MT15'  => ['publico' => 45885.00,  'distribuidor' => 38900.00,  'linea' => 'Spice', 'tipo' => 'Suave', 'voltaje' => 'Monofásica 110V/60Hz', 'capacidad' => '25 Lts x Hora', 'desc' => 'LÍNEA SPICE - HELADO SUAVE (25 LTS x HR) cilindros de 1.8 LT x 2.'],
    'SPICE MV89'  => ['publico' => 49335.00,  'distribuidor' => 41800.00,  'linea' => 'Spice', 'tipo' => 'Suave', 'voltaje' => 'Monofásica 110V/60Hz', 'capacidad' => '25 Lts x Hora', 'desc' => 'LÍNEA SPICE - HELADO SUAVE DE PISO (25 LTS x HR).'],
    'DEMEX 313T'  => ['publico' => 50000.00,  'distribuidor' => 41500.00,  'linea' => 'Demex', 'tipo' => 'Suave', 'voltaje' => 'Monofásica 110V/60Hz', 'capacidad' => '33 Lts x Hora', 'desc' => 'HELADO SUAVE DE MESA DE ALTO RENDIMIENTO.'],
    'DEMEX 313'   => ['publico' => 66000.00,  'distribuidor' => 55000.00,  'linea' => 'Demex', 'tipo' => 'Suave', 'voltaje' => 'Monofásica 110V/60Hz', 'capacidad' => '35 Lts x Hora', 'desc' => 'HELADO SUAVE DE PISO CON COMPRESOR PANASONIC.'],
    'DEMEX 513'   => ['publico' => 78000.00,  'distribuidor' => 64000.00,  'linea' => 'Demex', 'tipo' => 'Suave', 'voltaje' => 'Monofásica 110V/60Hz', 'capacidad' => '35 Lts x Hora', 'desc' => 'HELADO SUAVE DE PISO CON TOLVAS AMPLIADAS DE 12 LTS.'],
    'DEMEX 613'   => ['publico' => 88000.00,  'distribuidor' => 74000.00,  'linea' => 'Demex', 'tipo' => 'Suave', 'voltaje' => 'Bifásica 220V/60Hz',    'capacidad' => '46-52 Lts x Hora','desc' => 'MAQUINARIA INDUSTRIAL BIFÁSICA PARA ALTA DEMANDA.'],
    'DEMEX 125'   => ['publico' => 98000.00,  'distribuidor' => 82000.00,  'linea' => 'Demex', 'tipo' => 'Duro',  'voltaje' => 'Monofásica 110V/60Hz', 'capacidad' => 'Producción cada 9-11 min', 'desc' => 'HELADO DURO / NIEVE DE GARRAFA TRADICIONAL.'],
    'DEMEX 1020'  => ['publico' => 150000.00, 'distribuidor' => 130000.00, 'linea' => 'Demex', 'tipo' => 'Duro',  'voltaje' => 'Bifásica 220V/60Hz',    'capacidad' => 'Producción cada 8-10 min', 'desc' => 'EQUIPO INDUSTRIAL PARA HELADO DURO Y GELATO.']
];

// 4. Matriz de Insumos Oficiales (Bases y Saborizantes extraídos del PDF de control)
$catalogo_bases = [
    'Base Helado Suave Vainilla'   => ['publico' => 850.00,  'distribuidor' => 720.00,  'sabor' => 'Vainilla Holandesa', 'peso' => 'Bulto 10 Kg', 'rendimiento' => 'Rinde para 40 Lts de mezcla'],
    'Base Helado Suave Chocolate'  => ['publico' => 890.00,  'distribuidor' => 750.00,  'sabor' => 'Chocolate Obscuro', 'peso' => 'Bulto 10 Kg', 'rendimiento' => 'Rinde para 40 Lts de mezcla'],
    'Base Helado Suave Fresa'      => ['publico' => 890.00,  'distribuidor' => 750.00,  'sabor' => 'Fresa Silvestre',    'peso' => 'Bulto 10 Kg', 'rendimiento' => 'Rinde para 40 Lts de mezcla'],
    'Base Helado Duro / Neutra'    => ['publico' => 950.00,  'distribuidor' => 800.00,  'sabor' => 'Neutro Base',        'peso' => 'Bulto 10 Kg', 'rendimiento' => 'Ideal para estabilizar helado duro']
];

$catalogo_saborizantes = [
    'Concentrado Fresa Premium'    => ['publico' => 320.00,  'distribuidor' => 250.00,  'sabor' => 'Fresa Concentrada', 'peso' => 'Porrón 1 Litro', 'rendimiento' => 'Dosificación al 5% por litro'],
    'Concentrado Mango Alfonso'    => ['publico' => 350.00,  'distribuidor' => 280.00,  'sabor' => 'Mango Natural',      'peso' => 'Porrón 1 Litro', 'rendimiento' => 'Dosificación al 5% por litro'],
    'Veteado Zarzamora Premium'    => ['publico' => 1200.00, 'distribuidor' => 980.00,  'sabor' => 'Zarzamora Frutos',   'peso' => 'Cubeta 5 Kg',    'rendimiento' => 'Veteado directo de helados'],
    'Veteado Chocolate Semi-Amargo'=> ['publico' => 1350.00, 'distribuidor' => 1100.00, 'sabor' => 'Chocolate Especial', 'peso' => 'Cubeta 5 Kg',    'rendimiento' => 'Veteado directo de helados']
];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-12">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-plus-circle"></i> Alta de Producto Automática</h1>
        <p class="text-muted small">Selecciona la categoría; el sistema cargará los modelos oficiales de DEMEX y sus costos base al instante.</p>
    </div>
</div>

<div class="card-main mb-4 py-4 px-4 shadow-sm border-top border-4 border-danger bg-white rounded animate__animated animate__fadeInUp">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-sliders text-danger me-2"></i> Configuración Inteligente del Catálogo</h5>
    
    <form action="../actions/procesar_alta_producto.php" method="POST" id="formAltaProducto">
        
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Categoría del Catálogo <span class="text-danger">*</span></label>
                <select class="form-select fw-bold border-danger" id="id_categoria" name="id_categoria" required>
                    <option value="" selected disabled>Selecciona una categoría...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>" data-prefijo="<?= $cat['codigo_prefijo'] ?>">
                            <?= htmlspecialchars($cat['nombre_categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- CONTENEDOR CONTROLLABLE: Cambia de Select a Input Abierto mediante JS -->
            <div class="col-12 col-md-5" id="wrapper_nombre_producto">
                <label class="form-label fw-semibold text-dark small">Nombre del Producto / Modelo <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="txt_nombre" name="nombre" placeholder="Selecciona primero la categoría..." disabled required>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Código SKU Único <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-uppercase fw-semibold" id="sku_codigo" name="sku_codigo" placeholder="SKU Automático" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Precio Público Base ($ MXN) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control fw-bold text-dark" id="precio_publico" name="precio_publico" step="0.01" min="0" required>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Precio Distribuidor Base ($ MXN) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control fw-bold text-danger" id="precio_distribuidor" name="precio_distribuidor" step="0.01" min="0" required>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Stock Inicial Disponible <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="stock" value="0" min="0" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12">
                <label class="form-label fw-semibold text-dark small">Ficha Técnica / Descripción Comercial Corta</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Se auto-rellenará con la información oficial..."></textarea>
            </div>
        </div>

        <!-- === BLOQUES DINÁMICOS DE ATRIBUTOS === -->
        
        <!-- Bloque Máquinas -->
        <div id="bloque_maquinas" class="bloque-dinamico border-top pt-3 mt-3" style="display: none;">
            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-cpu me-2"></i> Especificaciones Técnicas Opcionales</h6>
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-dark small">Línea del Equipo</label>
                    <input type="text" class="form-control" id="attr_linea" name="attr_linea">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-dark small">Tipo de Helado</label>
                    <input type="text" class="form-control" id="attr_tipo_helado" name="attr_tipo_helado">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-dark small">Corriente / Voltaje</label>
                    <input type="text" class="form-control" id="attr_voltaje" name="attr_voltaje">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-dark small">Capacidad de Producción</label>
                    <input type="text" class="form-control" id="attr_capacidad" name="attr_capacidad">
                </div>
            </div>
        </div>

        <!-- Bloque Insumos -->
        <div id="bloque_insumos" class="bloque-dinamico border-top pt-3 mt-3" style="display: none;">
            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-egg-fried me-2"></i> Detalles de Materia Prima</h6>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Sabor / Variante</label>
                    <input type="text" class="form-control" id="attr_sabor" name="attr_sabor">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Presentación / Peso por Unidad</label>
                    <input type="text" class="form-control" id="attr_peso" name="attr_peso">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Rendimiento Estimado</label>
                    <input type="text" class="form-control" id="attr_rendimiento" name="attr_rendimiento">
                </div>
            </div>
        </div>

        <!-- Bloque Refacciones -->
        <div id="bloque_refacciones" class="bloque-dinamico border-top pt-3 mt-3" style="display: none;">
            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-nut me-2"></i> Registro de Nueva Componente Técnico</h6>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Número de Parte</label>
                    <input type="text" class="form-control text-uppercase" name="attr_no_parte" placeholder="Ej. NAV-313-EXT">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold text-dark small">Modelos Compatibles</label>
                    <input type="text" class="form-control" name="attr_compatibilidad" placeholder="Ej. DEMEX 313, DEMEX 313T">
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-4 mt-4">
            <a href="catalogo_productos.php" class="btn btn-secondary py-2 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-x-circle me-1"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-danger py-2 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-file-earmark-check-fill me-2"></i> Registrar en Catálogo
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Guardamos los catálogos de PHP directos a objetos de JS para usarlos de forma síncrona
const maquinasObj = <?= json_encode($catalogo_maquinas) ?>;
const basesObj = <?= json_encode($catalogo_bases) ?>;
const saborizantesObj = <?= json_encode($catalogo_saborizantes) ?>;
const refaccionesExistentes = <?= json_encode($refacciones_existentes) ?>;

$(document).ready(function() {
    
    // === INTERCEPCIÓN ASÍNCRONA ESTILO RECOMPRAS ===
    $('#formAltaProducto').on('submit', function(e) {
        e.preventDefault(); 

        $.ajax({
            url: '../actions/procesar_alta_producto.php',
            method: 'POST',
            data: $(this).serialize(), 
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: '¡Venta Cerrada!', 
                        text: '¡Producto registrado e integrado al catálogo comercial con éxito!',
                        icon: 'success',
                        confirmButtonColor: '#198754',
                        confirmButtonText: 'Entendido'
                    }).then(() => {
                        window.location.href = 'catalogo_productos.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Error de Consistencia',
                        text: response.message,
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Revisar'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error de Red',
                    text: 'No se pudo conectar con el servidor central de ventas.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Entendido'
                });
            }
        });
    });

    // === MOTOR REACTIVO DE CATEGORÍAS ===
    $('#id_categoria').on('change', function() {
        const idCat = parseInt($(this).val());
        const prefijo = $(this).find('option:selected').data('prefijo');
        const wrapper = $('#wrapper_nombre_producto');
        
        // Garantizamos que los inputs siempre estén activos y limpios para sobreescritura manual
        $('#precio_publico, #precio_distribuidor, #sku_codigo').val('').prop('disabled', false).prop('readonly', false);
        $('#descripcion').val('');
        $('.bloque-dinamico').slideUp(200);

        let selectHtml = `<label class="form-label fw-semibold text-dark small">Nombre del Producto / Modelo <span class="text-danger">*</span></label>`;
        
        if (idCat === 1) { // MÁQUINAS
            selectHtml += `<select class="form-select border-danger" id="select_producto" name="nombre" required>
                            <option value="" selected disabled>Selecciona una Máquina...</option>
                            <option value="NUEVO_REGISTRO_MANUAL">-- REGISTRAR NUEVA MÁQUINA MANUAL --</option>`;
            Object.keys(maquinasObj).forEach(key => { selectHtml += `<option value="${key}">${key}</option>`; });
            selectHtml += `</select>`;
            wrapper.html(selectHtml);
            $('#bloque_maquinas').slideDown(300);

        } else if (idCat === 2) { // BASES
            selectHtml += `<select class="form-select border-danger" id="select_producto" name="nombre" required>
                            <option value="" selected disabled>Selecciona una Base...</option>
                            <option value="NUEVO_REGISTRO_MANUAL">-- REGISTRAR NUEVA BASE MANUAL --</option>`;
            Object.keys(basesObj).forEach(key => { selectHtml += `<option value="${key}">${key}</option>`; });
            selectHtml += `</select>`;
            wrapper.html(selectHtml);
            $('#bloque_insumos').slideDown(300);

        } else if (idCat === 3) { // SABORIZANTES
            selectHtml += `<select class="form-select border-danger" id="select_producto" name="nombre" required>
                            <option value="" selected disabled>Selecciona un Saborizante...</option>
                            <option value="NUEVO_REGISTRO_MANUAL">-- REGISTRAR NUEVO SABORIZANTE MANUAL --</option>`;
            Object.keys(saborizantesObj).forEach(key => { selectHtml += `<option value="${key}">${key}</option>`; });
            selectHtml += `</select>`;
            wrapper.html(selectHtml);
            $('#bloque_insumos').slideDown(300);

        } else if (idCat === 4) { // REFACCIONES (Híbrido)
            selectHtml += `<select class="form-select border-danger" id="select_producto" name="nombre" required>
                            <option value="" selected disabled>Selecciona o registra una refacción...</option>
                            <option value="NUEVO_REGISTRO_MANUAL">-- REGISTRAR NUEVA REFACCIÓN MANUAL --</option>`;
            refaccionesExistentes.forEach(ref => { selectHtml += `<option value="${ref}">${ref}</option>`; });
            selectHtml += `</select>`;
            wrapper.html(selectHtml);
            $('#bloque_refacciones').slideDown(300);
        }

        const randomNum = Math.floor(1000 + Math.random() * 9000);
        $('#sku_codigo').val(prefijo + '-' + randomNum);
    });

    // Escuchamos dinámicamente cuando seleccionan un producto o la opción manual
    $(document).on('change', '#select_producto', function() {
        const idCat = parseInt($('#id_categoria').val());
        const valor = $(this).val();

        // MODIFICADO: Transforma el select en un input abierto si se elige la opción manual
        if (valor === 'NUEVO_REGISTRO_MANUAL') {
            let placeholderText = "Escribe el nombre del producto...";
            if (idCat === 1) placeholderText = "Ej. DEMEX 413-BIFÁSICA";
            if (idCat === 2) placeholderText = "Ej. Base Helado Suave Queso Crema";
            if (idCat === 3) placeholderText = "Ej. Concentrado de Maracuyá Premium";
            if (idCat === 4) placeholderText = "Ej. Empaque de Silicón Grado Alimenticio";

            $('#wrapper_nombre_producto').html(`
                <label class="form-label fw-semibold text-dark small">Nombre del Nuevo Registro <span class="text-danger">*</span></label>
                <input type="text" class="form-control border-danger" name="nombre" placeholder="${placeholderText}" required>`
            );
            // Limpiamos los bloques de atributos para llenado completamente manual
            $('.bloque-dinamico input').val('');
            return;
        }

        let data = null;
        if (idCat === 1) {
            data = maquinasObj[valor];
            if(data) {
                $('#attr_linea').val(data.linea);
                $('#attr_tipo_helado').val(data.tipo);
                $('#attr_voltaje').val(data.voltaje);
                $('#attr_capacidad').val(data.capacidad);
                $('#descripcion').val(data.desc);
            }
        } else if (idCat === 2) {
            data = basesObj[valor];
            if(data) {
                $('#attr_sabor').val(data.sabor);
                $('#attr_peso').val(data.peso);
                $('#attr_rendimiento').val(data.rendimiento);
                $('#descripcion').val(`Base oficial en polvo para preparación de helado sabor ${data.sabor}.`);
            }
        } else if (idCat === 3) {
            data = saborizantesObj[valor];
            if(data) {
                $('#attr_sabor').val(data.sabor);
                $('#attr_peso').val(data.peso);
                $('#attr_rendimiento').val(data.rendimiento);
                $('#descripcion').val(`Materia prima saborizante concentrada para veteado o mezclado.`);
            }
        }

        if (data) {
            $('#precio_publico').val(data.publico).prop('disabled', false).prop('readonly', false);
            $('#precio_distribuidor').val(data.distribuidor).prop('disabled', false).prop('readonly', false);
        }
    });

});
</script>