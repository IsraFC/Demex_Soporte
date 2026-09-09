<?php
/**
 * ARCHIVO: Ventas/editar_cotizacion.php
 * DESCRIPCIÓN: Formulario de Modificación y Re-configuración Comercial de Cotizaciones.
 * Soporta editar tanto prospectos del embudo como recompras del catálogo de clientes.
 * MODIFICACIÓN: Migrado al catálogo unificado 'productos' con 'id_producto' y unidades dinámicas.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 8.0 (Catálogo Universal y Corrección de Relaciones SQL)
 */

$page_title = "Editar Cotización | CRM Ventas";
require_once '../config/db.php';

$id_cotizacion = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;

if ($id_cotizacion === 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Error: ID de cotización no válido para edición.</div></div>";
    exit();
}

// 1. CONSULTA DE RECUPERACIÓN UNIFICADA: Enlace con 'productos' mediante 'c.id_producto'
$sql = "SELECT c.*, p.nombre AS producto_nombre, p.id_categoria,
               f.nombre AS lead_cliente_nombre,
               cl.nombre_cliente AS cartera_cliente_nombre
        FROM cotizacion c
        INNER JOIN productos p ON c.id_producto = p.id_producto
        LEFT JOIN prospectos pr ON c.id_prospecto = pr.id_prospecto
        LEFT JOIN formulario f ON pr.id_formulario = f.id_formulario
        LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
        WHERE c.id_cotizacion = :id_cotizacion LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id_cotizacion' => $id_cotizacion]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Error: La cotización seleccionada no existe en el sistema.</div></div>";
    exit();
}

// Determinamos de forma limpia el nombre a renderizar y el archivo de retorno
$es_recompra = !empty($cotizacion['id_cliente']);
$nombre_cliente_final = $es_recompra ? $cotizacion['cartera_cliente_nombre'] : $cotizacion['lead_cliente_nombre'];
$retorno_exitoso_view = $es_recompra ? "recompras_crm.php" : "leads_crm.php";

// PROCESADOR DE DESEMPAQUETADO BANCARIO EN EDICIÓN
$notas_limpias = $cotizacion['notes'];
$bancos = [
    'condicion' => "Precios de promoción para pagos por transferencia o efectivo.\nNo incluyen el envío.",
    'b1_nom'    => "BANORTE", 'b1_cta' => "0434571284", 'b1_clabe' => "072 650 00434571284 8",
    'b2_nom'    => "BANAMEX", 'b2_cta' => "7213722", 'b2_clabe' => "002 650 70107213722 1", 'b2_suc' => "7010"
];
$estado_iva_guardado = 1;

if (strpos($cotizacion['notes'], '|||') !== false) {
    $partes_notas = explode('|||', $cotizacion['notes']);
    $notas_limpias = trim($partes_notas[0]);
    $json_desencriptado = json_decode(base64_decode($partes_notas[1]), true);
    if ($json_desencriptado) {
        $bancos = $json_desencriptado;
        if (isset($json_desencriptado['incluye_iva'])) {
            $estado_iva_guardado = intval($json_desencriptado['incluye_iva']);
        }
    }
}

// 2. CONSULTA DINÁMICA DE PRODUCTOS AGRUPADOS POR CATEGORÍA
$sql_productos = "SELECT p.id_producto, p.nombre, p.sku_codigo, p.descripcion, 
                         p.precio_publico, p.precio_distribuidor, p.atributos_especificos,
                         c.nombre_categoria, c.id_categoria
                  FROM productos p
                  INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria
                  ORDER BY c.id_categoria ASC, p.nombre ASC";

$stmt_prod = $pdo->query($sql_productos);
$todos_los_productos = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

$productos_agrupados = [];
$productos_js_map = [];

foreach ($todos_los_productos as $item) {
    $cat_nombre = $item['nombre_categoria'];
    $productos_agrupados[$cat_nombre][] = $item;

    $cat_id = (int)$item['id_categoria'];
    $nombre_cat_lower = strtolower($cat_nombre);
    
    $unidad_medida = 'Pieza';
    if ($cat_id === 3 || strpos($nombre_cat_lower, 'saborizante') !== false) {
        $unidad_medida = 'Kilo';
    } elseif ($cat_id === 2 || strpos($nombre_cat_lower, 'base') !== false) {
        $unidad_medida = 'Costal';
    }

    $productos_js_map[$item['id_producto']] = [
        'id_producto'         => (int)$item['id_producto'],
        'nombre'              => $item['nombre'],
        'unidad'              => $unidad_medida,
        'precio_publico'      => (float)$item['precio_publico'],
        'precio_distribuidor' => (float)$item['precio_distribuidor'],
        'descripcion'         => $item['descripcion'] ?? '',
        'atributos'           => json_decode($item['atributos_especificos'] ?? '[]', true) ?: []
    ];
}

// Porcentaje de descuento guardado
$precio_base_guardado = floatval($cotizacion['precio_base_origen']);
$precio_pactado_guardado = floatval($cotizacion['precio_pactado']);
$descuento_porcentaje_inicial = 0;
if ($precio_base_guardado > 0 && $precio_pactado_guardado > 0) {
    $descuento_porcentaje_inicial = round((($precio_base_guardado - $precio_pactado_guardado) / $precio_base_guardado) * 100);
    if ($descuento_porcentaje_inicial < 0) $descuento_porcentaje_inicial = 0;
}

$fecha_hoy = date('Y-m-d');

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-pencil-square"></i> Modificar Cotización #<?= $cotizacion['id_cotizacion'] ?></h1>
        <p class="text-muted small">Ajuste de precios oficiales, especificaciones y condiciones comerciales del documento.</p>
    </div>
    <div class="col-md-5 text-md-end mt-2 mt-md-0">
        <a href="<?= $retorno_exitoso_view ?>" class="btn btn-secondary py-2 px-3 fw-bold shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-arrow-left-short fs-5"></i> Regresar al Panel
        </a>
    </div>
</div>

<div class="card-main mb-4 py-4 px-4 shadow-sm border-top border-4 border-danger bg-white rounded">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-calculator text-danger me-2"></i> Datos de la Cotización</h5>
    
    <form action="../actions/procesar_edicion_cotizacion.php" method="POST" id="formCotizacion">
        <input type="hidden" name="id_cotizacion" value="<?= $cotizacion['id_cotizacion'] ?>">
        <input type="hidden" name="incluye_iva" id="incluye_iva" value="<?= $estado_iva_guardado ?>">

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Cliente / Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control fw-bold bg-light" name="cliente" value="<?= htmlspecialchars($nombre_cliente_final) ?>" readonly required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">RFC Receptor</label>
                <input type="text" class="form-control text-uppercase" name="rfc_receptor" placeholder="XAXX010101000" maxlength="13" value="<?= htmlspecialchars($cotizacion['rfc_receptor'] ?: 'XAXX010101000') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Sucursal</label>
                <input type="text" class="form-control" name="sucursal" value="<?= htmlspecialchars($cotizacion['sucursal']) ?>" placeholder="Ej. Matriz Puebla">
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Dirección de Entrega</label>
                <textarea class="form-control" name="direccion_entrega" rows="2" placeholder="Dirección completa de entrega (Opcional)"><?= htmlspecialchars($cotizacion['direccion_entrega']) ?></textarea>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Cantidad</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" value="<?= $cotizacion['cantidad'] ?>" min="1" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Unidad de Medida</label>
                <input type="text" class="form-control fw-semibold" name="unidad" id="unidad" value="<?= htmlspecialchars($cotizacion['unidad'] ?: 'Pieza') ?>" readonly style="background-color: #f8f9fa;">
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Selección del Producto (Catálogo DEMEX) <span class="text-danger">*</span></label>
                <select class="form-select" id="producto_select" name="id_producto" required>
                    <?php foreach ($productos_agrupados as $categoria => $items): ?>
                        <optgroup label="<?= htmlspecialchars($categoria) ?>">
                            <?php foreach ($items as $prod): ?>
                                <option value="<?= $prod['id_producto'] ?>" <?= ($cotizacion['id_producto'] == $prod['id_producto']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prod['nombre']) ?> (SKU: <?= htmlspecialchars($prod['sku_codigo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Tipo de Cliente Comercial <span class="text-danger">*</span></label>
                <select class="form-select" id="tipo_cliente" name="tipo_cliente" required>
                    <option value="Publico General" <?= ($cotizacion['tipo_cliente'] === 'Publico General') ? 'selected' : '' ?>>Público General</option>
                    <option value="Distribuidor" <?= ($cotizacion['tipo_cliente'] === 'Distribuidor') ? 'selected' : '' ?>>Distribuidor</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Precio Base de Lista ($ MXN) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control fw-bold text-dark" id="precio_base_origen" name="precio_base_origen" step="0.01" required value="<?= $precio_base_guardado ?>">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Descuento Especial</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="descuento_porcentaje" name="descuento_porcentaje" min="0" max="100" step="1" value="<?= $descuento_porcentaje_inicial ?>">
                    <span class="input-group-text bg-light fw-bold">%</span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Costo de Envío</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control" id="costo_envio" name="costo_envio" min="0" step="0.01" value="<?= $cotizacion['costo_envio'] ?>">
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3 bg-light p-2 rounded border">
            <div class="col-12 col-md-6">
                <label for="fecha_vencimiento" class="form-label fw-bold text-dark small"><i class="bi bi-calendar-x text-danger me-1"></i> Fecha de Vencimiento de Promoción <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" value="<?= htmlspecialchars($cotizacion['fecha_vencimiento']) ?>" min="<?= $fecha_hoy ?>" required>
                <small class="text-muted" style="font-size: 0.75rem;">Modifica el límite de vigencia comercial del documento PDF.</small>
            </div>
            <div class="col-12 col-md-6">
                <label for="fecha_recordatorio" class="form-label fw-bold text-dark small"><i class="bi bi-bell-fill text-warning me-1"></i> Modificar Recordatorio (Semáforo) <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_recordatorio" name="fecha_recordatorio" value="<?= htmlspecialchars($cotizacion['fecha_recordatorio'] ?? $fecha_hoy) ?>" min="<?= $fecha_hoy ?>" required>
                <small class="text-muted" style="font-size: 0.75rem;">Fecha en la que el sistema activará las alertas comerciales en el panel.</small>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12">
                <label class="form-label fw-semibold text-dark small">Especificaciones Técnicas / Descripción Incluida</label>
                <textarea class="form-control small text-muted" id="especificion_cotizada" name="especificion_cotizada" style="background-color: #f8f9fa; height: 180px; resize: none;" placeholder="Se auto-rellenarán con la descripción del producto seleccionado..."><?= htmlspecialchars($cotizacion['especificacion_cotizada']) ?></textarea>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12">
                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-bank me-2"></i> Datos Bancarios y Fiscales Oficiales</h6>
                <p class="text-muted small mb-3">Condiciones de pago y cuentas oficiales corporativas que se imprimirán en la cotización.</p>
            </div>
            
            <div class="col-12 col-md-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Condiciones Comerciales Base</label>
                    <textarea class="form-control small text-muted" name="condicion_comercial_bancos" rows="2" style="background-color: #f8f9fa; height: 74px; resize: none;" required><?= htmlspecialchars($bancos['condicion']) ?></textarea>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark small">Razón Social / Beneficiario</label>
                    <input type="text" class="form-control bg-light fw-semibold text-dark" name="banco_beneficiario" value="DEMEXTOR SA DE CV" readonly style="height: 38px;" required>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Banco Opción 1</label>
                    <input type="text" class="form-control text-uppercase" name="banco_1_nombre" value="<?= htmlspecialchars($bancos['b1_nom']) ?>" style="height: 38px;" required>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Cuenta Banorte</label>
                    <input type="text" class="form-control fw-bold text-secondary" name="banco_1_cuenta" value="<?= htmlspecialchars($bancos['b1_cta']) ?>" style="height: 38px;" required>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark small">Clabe Interbancaria Banorte</label>
                    <input type="text" class="form-control fw-bold text-danger" name="banco_1_clabe" value="<?= htmlspecialchars($bancos['b1_clabe']) ?>" style="height: 38px;" required>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Banco Opción 2</label>
                    <input type="text" class="form-control text-uppercase" name="banco_2_nombre" value="<?= htmlspecialchars($bancos['b2_nom']) ?>" style="height: 38px;" required>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label class="form-label fw-semibold text-dark small">Cuenta Banamex</label>
                        <input type="text" class="form-control fw-bold text-secondary" name="banco_2_cuenta" value="<?= htmlspecialchars($bancos['b2_cta']) ?>" style="height: 38px;" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-semibold text-dark small">Sucursal</label>
                        <input type="text" class="form-control fw-bold text-secondary" name="banco_2_sucursal" value="<?= htmlspecialchars($bancos['b2_suc']) ?>" style="height: 38px;" required>
                    </div>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark small">Clabe Interbancaria Banamex</label>
                    <input type="text" class="form-control fw-bold text-danger" name="banco_2_clabe" value="<?= htmlspecialchars($bancos['b2_clabe']) ?>" style="height: 38px;" required>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Notas / Observaciones</label>
                <textarea class="form-control" name="notas" rows="4" placeholder="Garantías, plazos de entrega o condiciones de pago..." style="height: 180px; resize: none;"><?= htmlspecialchars($notas_limpias) ?></textarea>
            </div>

            <div class="col-12 col-md-6 ms-auto mt-3">
                <div class="p-3 rounded shadow-sm bg-light" style="border-left: 5px solid var(--primary-color);">
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Precio Unitario Base:</span>
                        <span id="lbl_base_unitario">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Unidades a Cotizar:</span>
                        <span id="lbl_cantidad_desglose" class="fw-bold text-dark">1 Pieza(s)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-danger fw-semibold">
                        <span>Descuento Otorgado:</span>
                        <span id="lbl_descuento_monto">-$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Gastos Logísticos (Envío):</span>
                        <span id="lbl_flete_monto">$0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2 fw-semibold text-dark">
                        <span>Precio Pactado Subtotal:</span>
                        <span id="lbl_subtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-muted small align-items-center">
                        <div class="form-check form-switch p-0 m-0 d-flex align-items-center gap-2">
                            <input class="form-check-input ms-0" type="checkbox" id="toggle_iva" <?= ($estado_iva_guardado === 1) ? 'checked' : '' ?> style="cursor:pointer;">
                            <label class="form-check-label text-muted" for="toggle_iva" style="cursor:pointer; font-size: 0.85rem;">IVA Traslado (16%):</label>
                        </div>
                        <span id="lbl_iva">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-success border-top pt-2">
                        <span>Gran Total Neto:</span>
                        <span id="lbl_total">$0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-3">
            <a href="<?= $retorno_exitoso_view ?>" class="btn btn-secondary py-2.5 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-x-circle me-1"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-danger py-2.5 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-file-earmark-check-fill me-2"></i> Actualizar y Guardar Cambios
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script>
const catalogoProductos = <?= json_encode($productos_js_map) ?>;

function calcularFlujoComercial(triggeredByManualInput = false) {
    const idProd = $('#producto_select').val();
    const tipoCliente = $('#tipo_cliente').val();
    const pctDesc = parseFloat($('#descuento_porcentaje').val()) || 0;
    const flete = parseFloat($('#costo_envio').val()) || 0;
    const cantidad = parseInt($('#cantidad').val()) || 1;
    const conIva = $('#toggle_iva').is(':checked');

    if (!idProd || !catalogoProductos[idProd]) return;

    const prodInfo = catalogoProductos[idProd];
    let precioBaseOriginal = parseFloat($('#precio_base_origen').val());

    // Actualizar unidad de medida
    const unidadMedida = prodInfo.unidad || 'Pieza';
    $('#unidad').val(unidadMedida);

    // Si cambió el producto o tipo de cliente manualmente
    if (!triggeredByManualInput || isNaN(precioBaseOriginal) || precioBaseOriginal <= 0) {
        precioBaseOriginal = (tipoCliente === 'Publico General') ? prodInfo.precio_publico : prodInfo.precio_distribuidor;
        $('#precio_base_origen').val(precioBaseOriginal.toFixed(2));
    }

    const montoDescuentoUnitario = precioBaseOriginal * (pctDesc / 100);
    const precioPactadoUnitario = precioBaseOriginal - montoDescuentoUnitario;
    
    const subtotalPartidaBruta = precioPactadoUnitario * cantidad;
    const baseConFlete = subtotalPartidaBruta + flete;
    
    const ivaCalculado = conIva ? (baseConFlete * 0.16) : 0;
    const totalNeto = baseConFlete + ivaCalculado;

    $('#incluye_iva').val(conIva ? "1" : "0");

    const formatoMXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    $('#lbl_base_unitario').text(formatoMXN.format(precioBaseOriginal));
    $('#lbl_cantidad_desglose').text(`${cantidad} ${unidadMedida}(s)`);
    $('#lbl_descuento_monto').text('-' + formatoMXN.format(montoDescuentoUnitario * cantidad));
    $('#lbl_flete_monto').text(formatoMXN.format(flete));
    $('#lbl_subtotal').text(formatoMXN.format(baseConFlete));
    $('#lbl_iva').text(formatoMXN.format(ivaCalculado));
    $('#lbl_total').text(formatoMXN.format(totalNeto));
}

$(document).ready(function() {
    const precioInicialBD = parseFloat("<?= $precio_base_guardado ?>") || 0;

    $('#producto_select').on('change', function() {
        const idProd = $(this).val();
        if(catalogoProductos[idProd]) {
            const prod = catalogoProductos[idProd];
            let fichaTexto = prod.descripcion || "";
            if (prod.atributos && typeof prod.atributos === 'object') {
                const specs = Object.entries(prod.atributos)
                    .filter(([k]) => k !== 'imagen' && k !== 'foto')
                    .map(([k, v]) => `• ${k.charAt(0).toUpperCase() + k.slice(1)}: ${v}`)
                    .join('\n');
                if (specs) fichaTexto += (fichaTexto ? "\n\n" : "") + specs;
            }
            $('#especificion_cotizada').val(fichaTexto);
        }
        calcularFlujoComercial(false);
    });

    $('#tipo_cliente').on('change', function() {
        calcularFlujoComercial(false);
    });
    
    $('#descuento_porcentaje, #costo_envio, #cantidad').on('input', function() {
        calcularFlujoComercial(true);
    });

    $('#precio_base_origen').on('input', function() {
        calcularFlujoComercial(true);
    });

    $('#toggle_iva').on('change', function() {
        calcularFlujoComercial(true);
    });

    $('#fecha_vencimiento').on('change', function() {
        $('#fecha_recordatorio').attr('max', this.value);
    });
    
    setTimeout(function() {
        calcularFlujoComercial(precioInicialBD > 0);
    }, 150);
});
</script>