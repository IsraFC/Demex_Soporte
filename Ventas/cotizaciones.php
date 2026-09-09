<?php
/**
 * ARCHIVO: cotizaciones.php
 * DESCRIPCIÓN: Formulario Universal de Configuración Comercial de Cotizaciones.
 * Carga directa desde la tabla 'productos' agrupada por categorías comerciales.
 * Reactividad automática entre tipo de cliente (Público/Distribuidor), 'precio_base_origen'
 * y asignación dinámica de Unidad de Medida (Pieza, Kilo, Costal).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 8.6 (Unidad de medida reactiva por categoría)
 */

$page_title = "Generador de Cotizaciones | CRM Ventas";
require_once '../config/db.php';

// 1. Consulta dinámica de todos los productos activos agrupados por categoría
$sql_productos = "SELECT p.id_producto, p.nombre, p.sku_codigo, p.descripcion, 
                         p.precio_publico, p.precio_distribuidor, p.stock, 
                         p.atributos_especificos, c.nombre_categoria, c.id_categoria
                  FROM productos p
                  INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria
                  ORDER BY c.id_categoria ASC, p.nombre ASC";

$stmt_prod = $pdo->query($sql_productos);
$todos_los_productos = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

// Agrupación para los optgroup en HTML y mapa JS
$productos_agrupados = [];
$productos_js_map = [];

foreach ($todos_los_productos as $item) {
    $cat_nombre = $item['nombre_categoria'];
    $productos_agrupados[$cat_nombre][] = $item;
    
    $atributos_decoded = json_decode($item['atributos_especificos'] ?? '[]', true) ?: [];

    // Mapeo automático de unidad según el id o nombre de la categoría
    $cat_id = (int)$item['id_categoria'];
    $nombre_cat_lower = strtolower($cat_nombre);
    
    $unidad_medida = 'Pieza';
    if ($cat_id === 3 || strpos($nombre_cat_lower, 'saborizante') !== false) {
        $unidad_medida = 'Kilo';
    } elseif ($cat_id === 2 || strpos($nombre_cat_lower, 'base') !== false) {
        $unidad_medida = 'Costal';
    } elseif ($cat_id === 1 || $cat_id === 4 || strpos($nombre_cat_lower, 'maquina') !== false || strpos($nombre_cat_lower, 'refaccion') !== false) {
        $unidad_medida = 'Pieza';
    }
    
    $productos_js_map[$item['id_producto']] = [
        'id_producto'         => (int)$item['id_producto'],
        'nombre'              => $item['nombre'],
        'unidad'              => $unidad_medida,
        'precio_publico'      => (float)$item['precio_publico'],
        'precio_distribuidor' => (float)$item['precio_distribuidor'],
        'descripcion'         => $item['descripcion'] ?? '',
        'atributos'           => $atributos_decoded
    ];
}

// 2. Detección de Prospecto o Cliente Recurrente
$id_prospecto = isset($_GET['id_prospecto']) ? intval($_GET['id_prospecto']) : 0;
$id_cliente_recompra = isset($_GET['cliente_recompra']) ? intval($_GET['cliente_recompra']) : 0;

$cliente_nombre = "";
$producto_interes_nombre = "";
$cliente_rfc = ""; 

if ($id_prospecto > 0) {
    $sql_lead = "SELECT f.nombre, f.maquina_interes 
                 FROM prospectos p 
                 INNER JOIN formulario f ON p.id_formulario = f.id_formulario 
                 WHERE p.id_prospecto = :id_prospecto LIMIT 1";
    $stmt_lead = $pdo->prepare($sql_lead);
    $stmt_lead->execute([':id_prospecto' => $id_prospecto]);
    $lead_data = $stmt_lead->fetch();
    
    if ($lead_data) {
        $cliente_nombre = $lead_data['nombre'];
        $producto_interes_nombre = $lead_data['maquina_interes'];
    }
} 
elseif ($id_cliente_recompra > 0) {
    $sql_rec = "SELECT nombre_cliente, rfc_receptor FROM clientes WHERE id_cliente = :id_cliente LIMIT 1";
    $stmt_rec = $pdo->prepare($sql_rec);
    $stmt_rec->execute([':id_cliente' => $id_cliente_recompra]);
    $client_data = $stmt_rec->fetch(PDO::FETCH_ASSOC);

    if ($client_data) {
        $cliente_nombre = $client_data['nombre_cliente'];
        $cliente_rfc = $client_data['rfc_receptor'] ?? '';
    }
}

if (empty($cliente_nombre)) {
    header("Location: leads_crm.php");
    exit();
}

$fecha_hoy = date('Y-m-d');
$fecha_vencimiento_sugerida = date('Y-m-d', strtotime('+15 days'));

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-file-earmark-pdf"></i> Generador de Cotizaciones</h1>
        <p class="text-muted small">Configuración comercial de máquinas, insumos, saborizantes y refacciones.</p>
    </div>
    <div class="col-md-5 text-md-end mt-2 mt-md-0">
        <a href="leads_crm.php" class="btn btn-secondary py-2 px-3 fw-bold shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-arrow-left-short fs-5"></i> Regresar a Prospectos
        </a>
    </div>
</div>

<div class="card-main mb-4 py-4 px-4 shadow-sm border-top border-4 border-danger bg-white rounded" id="cardSeccionCotizacion">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-calculator text-danger me-2"></i> Configuración Comercial de la Cotización</h5>
    
    <form action="../actions/procesar_cotizacion.php" method="POST" id="formCotizacion">
        <input type="hidden" name="id_prospecto" id="sec_id_prospecto" value="<?= $id_prospecto ?>">
        <input type="hidden" name="id_cliente_recompra" value="<?= $id_cliente_recompra ?>">
        <input type="hidden" name="incluye_iva" id="incluye_iva" value="1">

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Cliente / Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control fw-bold bg-light" id="txt_cliente_fiscal" name="cliente" value="<?= htmlspecialchars($cliente_nombre) ?>" readonly required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">RFC Receptor</label>
                <input type="text" class="form-control text-uppercase" name="rfc_receptor" placeholder="XAXX010101000" maxlength="13" value="<?= htmlspecialchars($cliente_rfc) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Sucursal</label>
                <input type="text" class="form-control" name="sucursal" value="Matriz" placeholder="Ej. Matriz Puebla">
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Dirección de Entrega</label>
                <textarea class="form-control" name="direccion_entrega" rows="2" placeholder="Dirección completa de entrega (Opcional)"></textarea>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Cantidad</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" value="1" min="1" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Unidad de Medida</label>
                <input type="text" class="form-control fw-semibold" name="unidad" id="unidad" value="Pieza" readonly style="background-color: #f8f9fa;">
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Selección del Producto (Catálogo DEMEX) <span class="text-danger">*</span></label>
                <select class="form-select" id="producto_select" name="id_producto" required>
                    <option value="" selected disabled>Selecciona un producto del catálogo...</option>
                    <?php foreach ($productos_agrupados as $categoria => $items): ?>
                        <optgroup label="<?= htmlspecialchars($categoria) ?>">
                            <?php foreach ($items as $prod): 
                                $seleccionado = (trim($producto_interes_nombre) !== '' && stripos($prod['nombre'], trim($producto_interes_nombre)) !== false);
                            ?>
                                <option value="<?= $prod['id_producto'] ?>" <?= $seleccionado ? 'selected' : '' ?>>
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
                    <option value="Publico General" selected>Público General</option>
                    <option value="Distribuidor">Distribuidor</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Precio Base de Lista ($ MXN) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control fw-bold text-dark" id="precio_base_origen" name="precio_base_origen" step="0.01" required>
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">Jalado de la BD según tipo de cliente. Editable.</small>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Descuento Especial</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="descuento_porcentaje" name="descuento_porcentaje" min="0" max="100" step="1" value="0">
                    <span class="input-group-text bg-light fw-bold">%</span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Costo de Envío</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control" id="costo_envio" name="costo_envio" min="0" step="0.01" value="0.00">
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3 bg-light p-2 rounded border">
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold text-dark small" for="fecha_vencimiento"><i class="bi bi-calendar-x text-danger me-1"></i> Fecha de Vencimiento de Promoción <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" value="<?= $fecha_vencimiento_sugerida ?>" min="<?= $fecha_hoy ?>" required>
                <small class="text-muted" style="font-size: 0.75rem;">Determina el límite de vigencia comercial del documento PDF.</small>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold text-dark small" for="fecha_recordatorio"><i class="bi bi-bell-fill text-warning me-1"></i> Planificar Recordatorio (Semáforo) <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_recordatorio" name="fecha_recordatorio" value="<?= $fecha_hoy ?>" min="<?= $fecha_hoy ?>" required>
                <small class="text-muted" style="font-size: 0.75rem;">Fecha en la que el sistema activará las alertas comerciales en el panel.</small>
            </div>
        </div>

        <!-- ESPECIFICACIONES TÉCNICAS / DESCRIPCIÓN -->
        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12">
                <label class="form-label fw-semibold text-dark small">Especificaciones Técnicas / Descripción Incluida</label>
                <textarea class="form-control small text-muted" id="especificion_cotizada" name="especificion_cotizada" style="background-color: #f8f9fa; height: 180px; resize: none;" placeholder="Se auto-rellenará con la descripción del producto seleccionado..." required></textarea>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12">
                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-bank me-2"></i> Datos Bancarios y Fiscales</h6>
                <p class="text-muted small mb-3">Condiciones de pago y cuentas oficiales corporativas que se imprimirán en la cotización.</p>
            </div>
            
            <div class="col-12 col-md-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Condiciones Comerciales Base</label>
                    <textarea class="form-control small text-muted" name="condicion_comercial_bancos" rows="2" style="background-color: #f8f9fa; height: 74px; resize: none;" required>Precios de promoción para pagos por transferencia o efectivo.&#10;No incluyen el envío.</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold text-dark small">Razón Social / Beneficiario</label>
                    <input type="text" class="form-control bg-light fw-semibold text-dark" name="banco_beneficiario" value="DEMEXTOR SA DE CV" readonly style="height: 38px;" required>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Banco Opción 1</label>
                    <input type="text" class="form-control text-uppercase" name="banco_1_nombre" value="BANORTE" style="height: 38px;" required>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Cuenta Banorte</label>
                    <input type="text" class="form-control fw-bold text-secondary" name="banco_1_cuenta" value="0434571284" style="height: 38px;" required>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark small">Clabe Interbancaria Banorte</label>
                    <input type="text" class="form-control fw-bold text-danger" name="banco_1_clabe" value="072 650 00434571284 8" style="height: 38px;" required>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Banco Opción 2</label>
                    <input type="text" class="form-control text-uppercase" name="banco_2_nombre" value="BANAMEX" style="height: 38px;" required>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label class="form-label fw-semibold text-dark small">Cuenta Banamex</label>
                        <input type="text" class="form-control fw-bold text-secondary" name="banco_2_cuenta" value="7213722" style="height: 38px;" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-semibold text-dark small">Sucursal</label>
                        <input type="text" class="form-control fw-bold text-secondary" name="banco_2_sucursal" value="7010" style="height: 38px;" required>
                    </div>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark small">Clabe Interbancaria Banamex</label>
                    <input type="text" class="form-control fw-bold text-danger" name="banco_2_clabe" value="002 650 70107213722 1" style="height: 38px;" required>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Notas / Observaciones</label>
                <textarea class="form-control" name="notes" rows="4" placeholder="Garantías, plazos de entrega o condiciones de pago..." style="height: 180px; resize: none;"></textarea>
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
                            <input class="form-check-input ms-0" type="checkbox" id="toggle_iva" checked style="cursor:pointer;">
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
            <button type="submit" class="btn btn-danger py-2.5 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-file-earmark-check-fill me-2"></i> Procesar y Guardar Cotización
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

    if (!idProd || !catalogoProductos[idProd]) {
        $('#precio_base_origen').val('');
        $('#especificion_cotizada').val('');
        $('#unidad').val('Pieza');
        $('#lbl_base_unitario, #lbl_descuento_monto, #lbl_flete_monto, #lbl_subtotal, #lbl_iva, #lbl_total').text('$0.00');
        $('#lbl_cantidad_desglose').text('0 Piezas');
        return;
    }

    const prodInfo = catalogoProductos[idProd];
    let precioBaseOriginal = parseFloat($('#precio_base_origen').val());

    // Actualizar la unidad de medida según la categoría del producto
    const unidadMedida = prodInfo.unidad || 'Pieza';
    $('#unidad').val(unidadMedida);

    // Asigna el precio correspondiente de la BD si cambió el producto o tipo de cliente
    if (!triggeredByManualInput || isNaN(precioBaseOriginal) || precioBaseOriginal <= 0) {
        precioBaseOriginal = (tipoCliente === 'Publico General') ? prodInfo.precio_publico : prodInfo.precio_distribuidor;
        $('#precio_base_origen').val(precioBaseOriginal.toFixed(2));
    }

    // Carga de ficha técnica o descripción
    let fichaTexto = prodInfo.descripcion || "";
    if (prodInfo.atributos && typeof prodInfo.atributos === 'object') {
        const specs = Object.entries(prodInfo.atributos)
            .filter(([k]) => k !== 'imagen' && k !== 'foto')
            .map(([k, v]) => `• ${k.charAt(0).toUpperCase() + k.slice(1)}: ${v}`)
            .join('\n');
        if (specs) {
            fichaTexto += (fichaTexto ? "\n\n" : "") + specs;
        }
    }
    $('#especificion_cotizada').val(fichaTexto);

    // Operaciones matemáticas
    const montoDescuentoUnitario = precioBaseOriginal * (pctDesc / 100);
    const precioPactadoUnitario = precioBaseOriginal - montoDescuentoUnitario;
    const subtotalPactadoAcumulado = (precioPactadoUnitario * cantidad) + flete;
    
    const ivaCalculado = conIva ? (subtotalPactadoAcumulado * 0.16) : 0;
    const totalNeto = subtotalPactadoAcumulado + ivaCalculado;

    $('#incluye_iva').val(conIva ? "1" : "0");

    const formatoMXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    $('#lbl_base_unitario').text(formatoMXN.format(precioBaseOriginal));
    $('#lbl_cantidad_desglose').text(`${cantidad} ${unidadMedida}(s)`);
    $('#lbl_descuento_monto').text('-' + formatoMXN.format(montoDescuentoUnitario * cantidad));
    $('#lbl_flete_monto').text(formatoMXN.format(flete));
    $('#lbl_subtotal').text(formatoMXN.format(subtotalPactadoAcumulado));
    $('#lbl_iva').text(formatoMXN.format(ivaCalculado));
    $('#lbl_total').text(formatoMXN.format(totalNeto));
}

$(document).ready(function() {
    $('#producto_select, #tipo_cliente').on('change', function() {
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

    if ($('#producto_select').val()) {
        calcularFlujoComercial(false);
    }
});
</script>