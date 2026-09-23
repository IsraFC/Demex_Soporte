<?php
/**
 * ARCHIVO: cotizaciones.php
 * DESCRIPCIÓN: Formulario Universal de Configuración Comercial de Cotizaciones.
 * Soporta dos modalidades:
 * 1. Modo Maquinaria: Selección unitaria con especificaciones técnicas completas.
 * 2. Modo Materia Prima: Tabla dinámica para múltiples partidas (Bases + Saborizantes).
 * MODIFICACIÓN: Ajuste de proporciones de columnas y espaciado armónico en UI.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.1 (Diseño Simétrico y Espaciados Corregidos)
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

// Separamos en dos catálogos: Maquinaria y Materia Prima (Insumos)
$productos_agrupados = [];
$productos_insumos = [];
$productos_maquinas = [];
$productos_js_map = [];

foreach ($todos_los_productos as $item) {
    $cat_nombre = $item['nombre_categoria'];
    $productos_agrupados[$cat_nombre][] = $item;
    
    $atributos_decoded = json_decode($item['atributos_especificos'] ?? '[]', true) ?: [];
    $cat_id = (int)$item['id_categoria'];
    $nombre_cat_lower = strtolower($cat_nombre);
    
    $unidad_medida = 'Pieza';
    $es_maquina = false;

    if ($cat_id === 3 || strpos($nombre_cat_lower, 'saborizante') !== false) {
        $unidad_medida = 'Kilo';
    } elseif ($cat_id === 2 || strpos($nombre_cat_lower, 'base') !== false) {
        $unidad_medida = 'Costal';
    } elseif ($cat_id === 1 || strpos($nombre_cat_lower, 'maquina') !== false) {
        $unidad_medida = 'Pieza';
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
        'es_maquina'          => $es_maquina,
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
        $producto_interes_nombre = trim($lead_data['maquina_interes'] ?? '');
    }
} elseif ($id_cliente_recompra > 0) {
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

// Modo inicial por defecto: si el prospecto decía 'Materia Prima' se abre en multipartida
$modo_inicial = (strcasecmp($producto_interes_nombre, 'Materia Prima') === 0) ? 'materia_prima' : 'maquinaria';

$fecha_hoy = date('Y-m-d');
$fecha_vencimiento_sugerida = date('Y-m-d', strtotime('+15 days'));

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-file-earmark-pdf"></i> Generador de Cotizaciones</h1>
        <p class="text-muted small">Configuración comercial de máquinas individuales o lotes de materias primas.</p>
    </div>
    <div class="col-md-5 text-md-end mt-2 mt-md-0">
        <a href="leads_crm.php" class="btn btn-secondary py-2 px-3 fw-bold shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-arrow-left-short fs-5"></i> Regresar a Prospectos
        </a>
    </div>
</div>

<div class="card-main mb-4 py-4 px-4 shadow-sm border-top border-4 border-danger bg-white rounded" id="cardSeccionCotizacion">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calculator text-danger me-2"></i> Configuración Comercial de la Cotización</h5>
        
        <!-- SELECTOR DE MODALIDAD COMERCIAL -->
        <div class="btn-group p-1 bg-light rounded border shadow-sm" role="group">
            <input type="radio" class="btn-check" name="modo_cotizacion_switch" id="modo_maq" value="maquinaria" <?= ($modo_inicial === 'maquinaria') ? 'checked' : '' ?>>
            <label class="btn btn-sm btn-outline-danger fw-bold px-3 border-0" for="modo_maq">
                <i class="bi bi-gear-wide-connected me-1"></i> Maquinaria
            </label>

            <input type="radio" class="btn-check" name="modo_cotizacion_switch" id="modo_mp" value="materia_prima" <?= ($modo_inicial === 'materia_prima') ? 'checked' : '' ?>>
            <label class="btn btn-sm btn-outline-danger fw-bold px-3 border-0" for="modo_mp">
                <i class="bi bi-boxes me-1"></i> Materia Prima (Múltiples Partidas)
            </label>
        </div>
    </div>
    
    <form action="../actions/procesar_cotizacion.php" method="POST" id="formCotizacion">
        <input type="hidden" name="id_prospecto" id="sec_id_prospecto" value="<?= $id_prospecto ?>">
        <input type="hidden" name="id_cliente_recompra" value="<?= $id_cliente_recompra ?>">
        <input type="hidden" name="tipo_cotizacion" id="tipo_cotizacion" value="<?= $modo_inicial ?>">
        <input type="hidden" name="incluye_iva" id="incluye_iva" value="1">

        <!-- DATOS FISCALES Y RECEPTOR -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Cliente / Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control fw-bold bg-light" id="txt_cliente_fiscal" name="cliente" value="<?= htmlspecialchars($cliente_nombre) ?>" readonly required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">RFC Receptor</label>
                <input type="text" class="form-control text-uppercase" name="rfc_receptor" placeholder="XAXX010101000" maxlength="13" value="<?= htmlspecialchars($cliente_rfc ?: 'XAXX010101000') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Tipo de Cliente Comercial <span class="text-danger">*</span></label>
                <select class="form-select" id="tipo_cliente" name="tipo_cliente" required>
                    <option value="Publico General" selected>Público General</option>
                    <option value="Distribuidor">Distribuidor</option>
                </select>
            </div>
        </div>

        <!-- DIRECCIÓN Y LOGÍSTICA COMPACTA Y SIMÉTRICA -->
        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Dirección de Entrega</label>
                <textarea class="form-control" name="direccion_entrega" rows="2" placeholder="Dirección completa de entrega (Opcional)" style="height: 38px; resize: none;"></textarea>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Sucursal</label>
                <input type="text" class="form-control" name="sucursal" value="Matriz" placeholder="Ej. Matriz Puebla">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Costo de Envío / Flete ($ MXN)</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control" id="costo_envio" name="costo_envio" min="0" step="0.01" value="0.00">
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODALIDAD 1: MAQUINARIA (EQUIPO ÚNICO)     -->
        <!-- ========================================== -->
        <div id="seccion_maquinaria" class="border-top pt-3 mb-4" style="<?= ($modo_inicial === 'maquinaria') ? '' : 'display:none;' ?>">
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-dark small">Modelo de Máquina <span class="text-danger">*</span></label>
                    <select class="form-select" id="producto_select_maq" name="id_producto_maq">
                        <option value="" selected disabled>Selecciona el equipo a cotizar...</option>
                        <?php foreach ($productos_maquinas as $maq): 
                            $seleccionado = (trim($producto_interes_nombre) !== '' && stripos($maq['nombre'], trim($producto_interes_nombre)) !== false);
                        ?>
                            <option value="<?= $maq['id_producto'] ?>" <?= $seleccionado ? 'selected' : '' ?>>
                                <?= htmlspecialchars($maq['nombre']) ?> (SKU: <?= htmlspecialchars($maq['sku_codigo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label fw-semibold text-dark small">Cantidad</label>
                    <input type="number" class="form-control" id="cantidad_maq" name="cantidad_maq" value="1" min="1">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Precio Base de Lista ($ MXN)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted">$</span>
                        <input type="number" class="form-control fw-bold text-dark" id="precio_base_maq" name="precio_base_maq" step="0.01">
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-dark small">Descuento Especial (%)</label>
                    <div class="input-group mb-2">
                        <input type="number" class="form-control" id="descuento_porcentaje_maq" name="descuento_porcentaje_maq" min="0" max="100" step="1" value="0">
                        <span class="input-group-text bg-light fw-bold">%</span>
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">Aplica directamente sobre el precio pactado del equipo.</small>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold text-dark small">Especificaciones Técnicas Incluidas</label>
                    <textarea class="form-control small text-muted" id="especificacion_maq" name="especificacion_maq" style="background-color: #f8f9fa; height: 110px; resize: none;" placeholder="Se auto-rellenará con la ficha técnica del equipo..."></textarea>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODALIDAD 2: MATERIA PRIMA (TABLA DINÁMICA DE PARTIDAS)  -->
        <!-- ======================================================== -->
        <div id="seccion_materia_prima" class="border-top pt-3 mb-4" style="<?= ($modo_inicial === 'materia_prima') ? '' : 'display:none;' ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-danger mb-0"><i class="bi bi-list-check me-1"></i> Partidas de Materia Prima (Bases y Saborizantes)</h6>
                    <small class="text-muted">Agrega todas las partidas necesarias. Los cálculos se actualizan al instante.</small>
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
                        <!-- Filas dinámicas -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIGENCIA Y RECORDATORIO CON ESPACIADO VISUAL LIMPIO -->
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

        <!-- DATOS BANCARIOS -->
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

        <!-- NOTAS Y CUADRO DE RESUMEN FINANCIERO -->
        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Notas / Observaciones Comerciales</label>
                <textarea class="form-control" name="notes" rows="4" placeholder="Garantías, tiempos de embarque o condiciones acordadas..." style="height: 180px; resize: none;"></textarea>
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
                        <span>Descuento Comercial Acumulado:</span>
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
const catalogoCompleto = <?= json_encode($productos_js_map) ?>;
const catalogoInsumos = <?= json_encode($productos_insumos) ?>;
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

function agregarFilaPartida(idProdPreseleccionado = null, cantidadInicial = 1) {
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
                <input type="text" name="partidas[${indice}][unidad]" class="form-control form-control-sm text-center fw-semibold txt-unidad-partida" readonly style="background-color: #f8f9fa;" value="Pieza">
            </td>
            <td>
                <input type="number" name="partidas[${indice}][cantidad]" class="form-control form-control-sm text-center input-cantidad-partida" value="${cantidadInicial}" min="1" required>
            </td>
            <td>
                <input type="number" name="partidas[${indice}][precio_lista]" class="form-control form-control-sm text-end input-precio-partida" step="0.01" value="0.00" required>
            </td>
            <td>
                <input type="number" name="partidas[${indice}][descuento]" class="form-control form-control-sm text-center input-desc-partida" value="0" min="0" max="100" step="1">
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

    if (idProdPreseleccionado) {
        $(`#fila_mp_${indice} .select-producto-partida`).val(idProdPreseleccionado).trigger('change');
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
            $('#especificacion_maq').val(fichaTexto);
        } else {
            $('#especificacion_maq').val('');
        }
    } else {
        let totalFilas = 0;
        $('.fila-partida-mp').each(function() {
            totalFilas++;
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

        if (totalFilas === 0) {
            articulosTotales = 0;
        }
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

$(document).ready(function() {$('input[name="modo_cotizacion_switch"]').on('change', function() {
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

    if ($('input[name="modo_cotizacion_switch"]:checked').val() === 'materia_prima' && $('.fila-partida-mp').length === 0) {
        agregarFilaPartida();
    } else {
        if ($('#producto_select_maq').val()) {
            $('#producto_select_maq').trigger('change');
        }
    }

    recalcularTodo();
});
</script>