<?php
/**
 * ARCHIVO: Ventas/editar_cotizacion.php
 * DESCRIPCIÓN: Formulario de Modificación y Re-configuración Comercial de Cotizaciones.
 * Soporta editar cotizaciones unitarias de Maquinaria y multipartida de Materia Prima.
 * Precarga y sincroniza las partidas desde 'cotizacion_detalle' con la tabla dinámica.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.0 (Edición Multipartida y Catálogo Universal)
 */

$page_title = "Editar Cotización | CRM Ventas";
require_once '../config/db.php';

$id_cotizacion = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;

if ($id_cotizacion === 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Error: ID de cotización no válido para edición.</div></div>";
    exit();
}

// 1. Consulta con LEFT JOIN a productos (id_producto es NULL en cotizaciones multipartida)
$sql = "SELECT c.*, p.nombre AS producto_nombre, p.id_categoria,
               f.nombre AS lead_cliente_nombre,
               cl.nombre_cliente AS cartera_cliente_nombre
        FROM cotizacion c
        LEFT JOIN productos p ON c.id_producto = p.id_producto
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

// 2. Consulta de partidas asociadas en cotizacion_detalle
$sql_partidas = "SELECT cd.*, prod.nombre AS producto_nombre, prod.sku_codigo
                 FROM cotizacion_detalle cd
                 INNER JOIN productos prod ON cd.id_producto = prod.id_producto
                 WHERE cd.id_cotizacion = ?
                 ORDER BY cd.id_detalle ASC";
$stmt_partidas = $pdo->prepare($sql_partidas);
$stmt_partidas->execute([$id_cotizacion]);
$partidas_guardadas = $stmt_partidas->fetchAll(PDO::FETCH_ASSOC);

// Detección de modalidad guardada
$es_modo_mp = (empty($cotizacion['id_producto']) || count($partidas_guardadas) > 1);
$modo_actual = $es_modo_mp ? 'materia_prima' : 'maquinaria';

$es_recompra = !empty($cotizacion['id_cliente']);
$nombre_cliente_final = $es_recompra ? $cotizacion['cartera_cliente_nombre'] : $cotizacion['lead_cliente_nombre'];
$retorno_exitoso_view = $es_recompra ? "recompras_crm.php" : "leads_crm.php";

// Desempaquetado bancario
$notas_limpias = $cotizacion['notes'] ?? '';
$bancos = [
    'condicion' => "Precios de promoción para pagos por transferencia o efectivo.\nNo incluyen el envío.",
    'b1_nom'    => "BANORTE", 'b1_cta' => "0434571284", 'b1_clabe' => "072 650 00434571284 8",
    'b2_nom'    => "BANAMEX", 'b2_cta' => "7213722", 'b2_clabe' => "002 650 70107213722 1", 'b2_suc' => "7010"
];
$estado_iva_guardado = 1;

if (strpos($cotizacion['notes'] ?? '', '|||') !== false) {
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

// 3. Catálogo dinámico
$sql_productos = "SELECT p.id_producto, p.nombre, p.sku_codigo, p.descripcion, 
                         p.precio_publico, p.precio_distribuidor, p.atributos_especificos,
                         c.nombre_categoria, c.id_categoria
                  FROM productos p
                  INNER JOIN categorias_productos c ON p.id_categoria = c.id_categoria
                  ORDER BY c.id_categoria ASC, p.nombre ASC";

$stmt_prod = $pdo->query($sql_productos);
$todos_los_productos = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

$productos_maquinas = [];
$productos_insumos = [];
$productos_js_map = [];

foreach ($todos_los_productos as $item) {
    $cat_nombre = $item['nombre_categoria'];
    $cat_id = (int)$item['id_categoria'];
    $nombre_cat_lower = strtolower($cat_nombre);
    
    $unidad_medida = 'Pieza';
    $es_maquina = false;

    if ($cat_id === 3 || strpos($nombre_cat_lower, 'saborizante') !== false) {
        $unidad_medida = 'Kilo';
    } elseif ($cat_id === 2 || strpos($nombre_cat_lower, 'base') !== false) {
        $unidad_medida = 'Costal';
    } elseif ($cat_id === 1 || strpos($nombre_cat_lower, 'maquina') !== false) {
        $es_maquina = true;
    }

    if ($es_maquina) {
        $productos_maquinas[] = $item;
    } else {
        $productos_insumos[$cat_nombre][] = $item;
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

// Descuento inicial para modo maquinaria
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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calculator text-danger me-2"></i> Datos de la Cotización</h5>
        
        <div class="btn-group p-1 bg-light rounded border shadow-sm" role="group">
            <input type="radio" class="btn-check" name="modo_cotizacion_switch" id="modo_maq" value="maquinaria" <?= ($modo_actual === 'maquinaria') ? 'checked' : '' ?>>
            <label class="btn btn-sm btn-outline-danger fw-bold px-3 border-0" for="modo_maq">
                <i class="bi bi-gear-wide-connected me-1"></i> Maquinaria
            </label>

            <input type="radio" class="btn-check" name="modo_cotizacion_switch" id="modo_mp" value="materia_prima" <?= ($modo_actual === 'materia_prima') ? 'checked' : '' ?>>
            <label class="btn btn-sm btn-outline-danger fw-bold px-3 border-0" for="modo_mp">
                <i class="bi bi-boxes me-1"></i> Materia Prima (Múltiples Partidas)
            </label>
        </div>
    </div>
    
    <form action="../actions/procesar_edicion_cotizacion.php" method="POST" id="formCotizacion">
        <input type="hidden" name="id_cotizacion" value="<?= $cotizacion['id_cotizacion'] ?>">
        <input type="hidden" name="tipo_cotizacion" id="tipo_cotizacion" value="<?= $modo_actual ?>">
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
                <label class="form-label fw-semibold text-dark small">Tipo de Cliente Comercial <span class="text-danger">*</span></label>
                <select class="form-select" id="tipo_cliente" name="tipo_cliente" required>
                    <option value="Publico General" <?= ($cotizacion['tipo_cliente'] === 'Publico General') ? 'selected' : '' ?>>Público General</option>
                    <option value="Distribuidor" <?= ($cotizacion['tipo_cliente'] === 'Distribuidor') ? 'selected' : '' ?>>Distribuidor</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Dirección de Entrega</label>
                <textarea class="form-control" name="direccion_entrega" rows="2" placeholder="Dirección completa de entrega (Opcional)" style="height: 38px; resize: none;"><?= htmlspecialchars($cotizacion['direccion_entrega']) ?></textarea>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Sucursal</label>
                <input type="text" class="form-control" name="sucursal" value="<?= htmlspecialchars($cotizacion['sucursal']) ?>" placeholder="Ej. Matriz Puebla">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Costo de Envío / Flete ($ MXN)</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control" id="costo_envio" name="costo_envio" min="0" step="0.01" value="<?= $cotizacion['costo_envio'] ?>">
                </div>
            </div>
        </div>

        <!-- SECCIÓN MAQUINARIA -->
        <div id="seccion_maquinaria" class="border-top pt-3 mb-4" style="<?= ($modo_actual === 'maquinaria') ? '' : 'display:none;' ?>">
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-dark small">Modelo de Máquina <span class="text-danger">*</span></label>
                    <select class="form-select" id="producto_select_maq" name="id_producto_maq">
                        <option value="" selected disabled>Selecciona el equipo...</option>
                        <?php foreach ($productos_maquinas as $maq): ?>
                            <option value="<?= $maq['id_producto'] ?>" <?= ($cotizacion['id_producto'] == $maq['id_producto']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($maq['nombre']) ?> (SKU: <?= htmlspecialchars($maq['sku_codigo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label fw-semibold text-dark small">Cantidad</label>
                    <input type="number" class="form-control" id="cantidad_maq" name="cantidad_maq" value="<?= $cotizacion['cantidad'] ?: 1 ?>" min="1">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Precio Base de Lista ($ MXN)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted">$</span>
                        <input type="number" class="form-control fw-bold text-dark" id="precio_base_maq" name="precio_base_maq" step="0.01" value="<?= $precio_base_guardado ?>">
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Descuento Especial (%)</label>
                    <div class="input-group mb-2">
                        <input type="number" class="form-control" id="descuento_porcentaje_maq" name="descuento_porcentaje_maq" min="0" max="100" step="1" value="<?= $descuento_porcentaje_inicial ?>">
                        <span class="input-group-text bg-light fw-bold">%</span>
                    </div>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold text-dark small">Especificaciones Técnicas Incluidas</label>
                    <textarea class="form-control small text-muted" id="especificacion_maq" name="especificacion_maq" style="background-color: #f8f9fa; height: 110px; resize: none;" placeholder="Ficha técnica del equipo..."><?= htmlspecialchars($cotizacion['especificacion_cotizada']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- SECCIÓN MATERIA PRIMA (TABLA DINÁMICA) -->
        <div id="seccion_materia_prima" class="border-top pt-3 mb-4" style="<?= ($modo_actual === 'materia_prima') ? '' : 'display:none;' ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-danger mb-0"><i class="bi bi-list-check me-1"></i> Partidas de Materia Prima (Bases y Saborizantes)</h6>
                    <small class="text-muted">Modifica o añade partidas según la negociación actual.</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 shadow-sm rounded-pill" id="btnAgregarPartida">
                    <i class="bi bi-plus-circle me-1"></i> Agregar Producto
                </button>
            </div>

            <div class="table-responsive mb-0">
                <table class="table table-bordered table-hover align-middle bg-white small mb-0" id="tablaPartidasMP">
                    <thead class="table-light">
                        <tr class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">
                            <th style="min-width: 250px;">Producto / Insumo</th>
                            <th class="text-center" style="width: 100px;">Unidad</th>
                            <th class="text-center" style="width: 100px;">Cantidad</th>
                            <th class="text-end" style="width: 140px;">P. Lista ($)</th>
                            <th class="text-center" style="width: 110px;">Desc. (%)</th>
                            <th class="text-end" style="width: 140px;">P. Pactado</th>
                            <th class="text-end" style="width: 150px;">Subtotal</th>
                            <th class="text-center" style="width: 50px;"><i class="bi bi-trash"></i></th>
                        </tr>
                    </thead>
                    <tbody id="contenedorPartidas">
                        <!-- Se puebla dinámicamente con JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3 bg-light p-2 rounded border">
            <div class="col-12 col-md-6">
                <label for="fecha_vencimiento" class="form-label fw-bold text-dark small"><i class="bi bi-calendar-x text-danger me-1"></i> Fecha de Vencimiento de Promoción <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" value="<?= htmlspecialchars($cotizacion['fecha_vencimiento']) ?>" min="<?= $fecha_hoy ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label for="fecha_recordatorio" class="form-label fw-bold text-dark small"><i class="bi bi-bell-fill text-warning me-1"></i> Modificar Recordatorio (Semáforo) <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_recordatorio" name="fecha_recordatorio" value="<?= htmlspecialchars($cotizacion['fecha_recordatorio'] ?? $fecha_hoy) ?>" min="<?= $fecha_hoy ?>" required>
            </div>
        </div>

        <!-- DATOS BANCARIOS -->
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

        <!-- NOTAS Y RESUMEN FINANCIERO -->
        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Notas / Observaciones</label>
                <textarea class="form-control" name="notas" rows="4" placeholder="Garantías, plazos de entrega o condiciones de pago..." style="height: 180px; resize: none;"><?= htmlspecialchars($notas_limpias) ?></textarea>
            </div>

            <div class="col-12 col-md-6 ms-auto mt-3">
                <div class="p-3 rounded shadow-sm bg-light" style="border-left: 5px solid var(--primary-color);">
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Subtotal de Productos (Lista):</span>
                        <span id="lbl_base_acumulado">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Total Partidas / Artículos:</span>
                        <span id="lbl_cantidad_desglose" class="fw-bold text-dark">0 Producto(s)</span>
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
const catalogoCompleto = <?= json_encode($productos_js_map) ?>;
const catalogoInsumos = <?= json_encode($productos_insumos) ?>;
const partidasPrecargadas = <?= json_encode($partidas_guardadas) ?>;
const formatoMXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

let contadorFilasMP = 0;

function obtenerOpcionesInsumos() {
    let html = '<option value="" selected disabled>Selecciona base o saborizante...</option>';
    Object.entries(catalogoInsumos).forEach(([categoria, productos]) => {
        html += `<optgroup label="${categoria}">`;
        productos.forEach(p => {
            html += `<option value="${p.id_producto}">${p.nombre} (SKU: ${p.sku_codigo})</option>`;
        });
        html += `</optgroup>`;
    });
    return html;
}

function agregarFilaPartida(idProd = null, cantidad = 1, precioLista = 0, descuento = 0, unidad = 'Pieza') {
    contadorFilasMP++;
    const indice = contadorFilasMP;

    const filaHtml = `
        <tr id="fila_mp_${indice}" class="fila-partida-mp">
            <td>
                <select name="partidas[${indice}][id_producto]" class="form-select form-select-sm select-producto-partida" data-row="${indice}" required>
                    ${obtenerOpcionesInsumos()}
                </select>
            </td>
            <td class="text-center">
                <input type="text" name="partidas[${indice}][unidad]" class="form-control form-control-sm text-center fw-semibold txt-unidad-partida" readonly style="background-color: #f8f9fa;" value="${unidad}">
            </td>
            <td>
                <input type="number" name="partidas[${indice}][cantidad]" class="form-control form-control-sm text-center input-cantidad-partida" value="${cantidad}" min="1" required>
            </td>
            <td>
                <input type="number" name="partidas[${indice}][precio_lista]" class="form-control form-control-sm text-end input-precio-partida" step="0.01" value="${precioLista}" required>
            </td>
            <td>
                <input type="number" name="partidas[${indice}][descuento]" class="form-control form-control-sm text-center input-desc-partida" value="${descuento}" min="0" max="100" step="1">
            </td>
            <td class="text-end fw-semibold celda-pactado-partida">$0.00</td>
            <td class="text-end fw-bold text-dark celda-subtotal-partida">$0.00</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-eliminar-fila" data-row="${indice}" title="Eliminar fila">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </td>
        </tr>
    `;

    $('#contenedorPartidas').append(filaHtml);

    if (idProd) {
        $(`#fila_mp_${indice} .select-producto-partida`).val(idProd);
    }
}

function recalcularTodo() {
    const modo = $('input[name="modo_cotizacion_switch"]:checked').val();
    const tipoCliente = $('#tipo_cliente').val();
    const flete = parseFloat($('#costo_envio').val()) || 0;
    const conIva = $('#toggle_iva').is(':checked');

    let totalBaseLista = 0;
    let totalDescuentoDinero = 0;
    let subtotalPactadoAcumulado = 0;
    let articulosTotales = 0;

    if (modo === 'maquinaria') {
        const idProd = $('#producto_select_maq').val();
        const cantidad = parseInt($('#cantidad_maq').val()) || 1;
        const pctDesc = parseFloat($('#descuento_porcentaje_maq').val()) || 0;

        if (idProd && catalogoCompleto[idProd]) {
            const prod = catalogoCompleto[idProd];
            let precioBase = parseFloat($('#precio_base_maq').val());

            if (isNaN(precioBase) || precioBase <= 0) {
                precioBase = (tipoCliente === 'Publico General') ? prod.precio_publico : prod.precio_distribuidor;
                $('#precio_base_maq').val(precioBase.toFixed(2));
            }

            const descuentoUnitario = precioBase * (pctDesc / 100);
            const precioPactadoUnitario = precioBase - descuentoUnitario;

            totalBaseLista = precioBase * cantidad;
            totalDescuentoDinero = descuentoUnitario * cantidad;
            subtotalPactadoAcumulado = precioPactadoUnitario * cantidad;
            articulosTotales = cantidad;

            let fichaTexto = prod.descripcion || "";
            if (prod.atributos && typeof prod.atributos === 'object') {
                const specs = Object.entries(prod.atributos)
                    .filter(([k]) => k !== 'imagen' && k !== 'foto')
                    .map(([k, v]) => `• ${k.charAt(0).toUpperCase() + k.slice(1)}: ${v}`)
                    .join('\n');
                if (specs) fichaTexto += (fichaTexto ? "\n\n" : "") + specs;
            }
            if (!$('#especificacion_maq').val()) {
                $('#especificacion_maq').val(fichaTexto);
            }
        }
    } else {
        $('.fila-partida-mp').each(function() {
            const $fila =$(this);
            const idProd = $fila.find('.select-producto-partida').val();
            const cantidad = parseInt($fila.find('.input-cantidad-partida').val()) || 0;
            const pctDesc = parseFloat($fila.find('.input-desc-partida').val()) || 0;
            let pLista = parseFloat($fila.find('.input-precio-partida').val()) || 0;

            if (idProd && catalogoCompleto[idProd]) {
                const descUnitario = pLista * (pctDesc / 100);
                const pPactado = pLista - descUnitario;
                const subtotalFila = pPactado * cantidad;

                totalBaseLista += (pLista * cantidad);
                totalDescuentoDinero += (descUnitario * cantidad);
                subtotalPactadoAcumulado += subtotalFila;
                articulosTotales += cantidad;

                $fila.find('.celda-pactado-partida').text(formatoMXN.format(pPactado));$fila.find('.celda-subtotal-partida').text(formatoMXN.format(subtotalFila));
            }
        });
    }

    const baseConFlete = subtotalPactadoAcumulado + flete;
    const ivaCalculado = conIva ? (baseConFlete * 0.16) : 0;
    const totalNeto = baseConFlete + ivaCalculado;

    $('#incluye_iva').val(conIva ? "1" : "0");

    $('#lbl_base_acumulado').text(formatoMXN.format(totalBaseLista));
    $('#lbl_cantidad_desglose').text(`${articulosTotales} Artículo(s)`);
    $('#lbl_descuento_monto').text('-' + formatoMXN.format(totalDescuentoDinero));
    $('#lbl_flete_monto').text(formatoMXN.format(flete));
    $('#lbl_subtotal').text(formatoMXN.format(baseConFlete));
    $('#lbl_iva').text(formatoMXN.format(ivaCalculado));
    $('#lbl_total').text(formatoMXN.format(totalNeto));
}

$(document).ready(function() {
    // Precarga de partidas existentes si es modo materia prima
    if (partidasPrecargadas && partidasPrecargadas.length > 0) {
        partidasPrecargadas.forEach(p => {
            agregarFilaPartida(p.id_producto, p.cantidad, p.precio_base_origen, p.descuento_porcentaje, p.unidad);
        });
    } else if ($('#tipo_cotizacion').val() === 'materia_prima') {
        agregarFilaPartida();
    }

    $('input[name="modo_cotizacion_switch"]').on('change', function() {
        const modo = $(this).val();$('#tipo_cotizacion').val(modo);

        if (modo === 'maquinaria') {
            $('#seccion_maquinaria').slideDown(200);
            $('#seccion_materia_prima').slideUp(200);
            $('#producto_select_maq').prop('required', true);
        } else {
            $('#seccion_maquinaria').slideUp(200);
            $('#seccion_materia_prima').slideDown(200);
            $('#producto_select_maq').prop('required', false);

            if ($('.fila-partida-mp').length === 0) {
                agregarFilaPartida();
            }
        }
        recalcularTodo();
    });

    $('#btnAgregarPartida').on('click', function() {
        agregarFilaPartida();
    });

    $(document).on('click', '.btn-eliminar-fila', function() {
        const rowId = $(this).data('row');$(`#fila_mp_${rowId}`).remove();
        recalcularTodo();
    });

    $(document).on('change', '.select-producto-partida', function() {
        const idProd = $(this).val();
        const $fila =$(this).closest('tr');
        const tipoCliente = $('#tipo_cliente').val();

        if (idProd && catalogoCompleto[idProd]) {
            const prod = catalogoCompleto[idProd];
            const precioLista = (tipoCliente === 'Publico General') ? prod.precio_publico : prod.precio_distribuidor;
            
            $fila.find('.txt-unidad-partida').val(prod.unidad);$fila.find('.input-precio-partida').val(precioLista.toFixed(2));
        }
        recalcularTodo();
    });

    $('#producto_select_maq').on('change', function() {
        const idProd = $(this).val();
        const tipoCliente = $('#tipo_cliente').val();
        if (idProd && catalogoCompleto[idProd]) {
            const prod = catalogoCompleto[idProd];
            const precio = (tipoCliente === 'Publico General') ? prod.precio_publico : prod.precio_distribuidor;
            $('#precio_base_maq').val(precio.toFixed(2));
        }
        recalcularTodo();
    });

    $('#tipo_cliente').on('change', function() {
        const tipoCliente = $(this).val();
        const idMaq = $('#producto_select_maq').val();
        if (idMaq && catalogoCompleto[idMaq]) {
            const precio = (tipoCliente === 'Publico General') ? catalogoCompleto[idMaq].precio_publico : catalogoCompleto[idMaq].precio_distribuidor;
            $('#precio_base_maq').val(precio.toFixed(2));
        }

        $('.fila-partida-mp').each(function() {
            const idProd = $(this).find('.select-producto-partida').val();
            if (idProd && catalogoCompleto[idProd]) {
                const precio = (tipoCliente === 'Publico General') ? catalogoCompleto[idProd].precio_publico : catalogoCompleto[idProd].precio_distribuidor;
                $(this).find('.input-precio-partida').val(precio.toFixed(2));
            }
        });

        recalcularTodo();
    });

    $(document).on('input', '.input-cantidad-partida, .input-precio-partida, .input-desc-partida, #cantidad_maq, #precio_base_maq, #descuento_porcentaje_maq, #costo_envio', function() {
        recalcularTodo();
    });

    $('#toggle_iva').on('change', function() {
        recalcularTodo();
    });

    $('#fecha_vencimiento').on('change', function() {
        $('#fecha_recordatorio').attr('max', this.value);
    });

    recalcularTodo();
});
</script>