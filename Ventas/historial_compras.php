<?php
/**
 * ARCHIVO: Ventas/historial_cliente.php
 * DESCRIPCIÓN: Expediente comercial corporativo de compras y cotizaciones del cliente.
 * Agrupa las adquisiciones por Orden / Cotización Origen en tarjetas modulares con sub-partidas.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.5 (Agrupación Jerárquica por Pedido Comercial)
 */

$page_title = "Historial de Cliente | CRM Ventas";
require_once '../config/db.php';

$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0;

if ($id_cliente <= 0) {
    header("Location: clientes_crm.php");
    exit();
}

// 1. Consulta de datos generales del cliente
$stmt_cli = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1");
$stmt_cli->execute([$id_cliente]);
$cliente = $stmt_cli->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    header("Location: clientes_crm.php");
    exit();
}

// 2. Detección de columna de producto en ventas_historial
$col_prod = 'id_producto';
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM ventas_historial LIKE 'id_producto'")->fetch();
    if (!$checkCol) {
        $col_prod = 'id_maquina';
    }
} catch (\Exception $e) {
    $col_prod = 'id_producto';
}

// 3. Consulta de adquisiciones enlazadas al catálogo de productos
$sql = "SELECT vh.*, 
               p.nombre AS producto_nombre, 
               p.sku_codigo,
               c.nombre_categoria
        FROM ventas_historial vh
        LEFT JOIN productos p ON vh.{$col_prod} = p.id_producto
        LEFT JOIN categorias_productos c ON p.id_categoria = c.id_categoria
        WHERE vh.id_cliente = :id_cliente
        ORDER BY vh.fecha_compra DESC, vh.id_cotizacion_origen DESC, vh.id_venta DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id_cliente' => $id_cliente]);
$compras_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Agrupación estructurada por Pedido / Cotización Origen
$pedidos = [];
$total_articulos_adquiridos = 0;
$total_inversion_cliente = 0;

foreach ($compras_raw as $item) {
    $id_cotiz = intval($item['id_cotizacion_origen'] ?? 0);
    $clave_pedido = ($id_cotiz > 0) ? 'COT_' . $id_cotiz : 'VENTA_' . $item['fecha_compra'] . '_' . $item['id_venta'];

    $cant = intval($item['cantidad']);
    $precio_unit = floatval($item['precio_pactado_neto']);
    $subtotal_partida = $precio_unit * $cant;

    $total_articulos_adquiridos += $cant;
    $total_inversion_cliente += $subtotal_partida;

    if (!isset($pedidos[$clave_pedido])) {
        $pedidos[$clave_pedido] = [
            'id_cotizacion' => $id_cotiz,
            'fecha_compra'  => $item['fecha_compra'],
            'costo_envio'   => floatval($item['costo_envio'] ?? 0),
            'observaciones' => trim($item['observaciones_venta'] ?? ''),
            'partidas'      => [],
            'subtotal_orden'=> 0
        ];
    }

    $pedidos[$clave_pedido]['subtotal_orden'] += $subtotal_partida;
    $pedidos[$clave_pedido]['partidas'][] = [
        'nombre'      => $item['producto_nombre'] ?? 'Producto no especificado',
        'sku'         => $item['sku_codigo'] ?? '',
        'categoria'   => $item['nombre_categoria'] ?? 'General',
        'cantidad'    => $cant,
        'precio_unit' => $precio_unit,
        'subtotal'    => $subtotal_partida
    ];
}

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-clock-history"></i> Historial de Cliente</h1>
        <p class="text-muted small mb-1">Expediente comercial corporativo de compras y cotizaciones.</p>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($cliente['nombre_cliente']) ?></h3>
            <span class="badge text-uppercase bg-light text-muted border px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                <?= htmlspecialchars($cliente['tipo_cliente'] ?? 'Publico General') ?>
            </span>
        </div>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <div class="d-inline-flex gap-2 align-items-center">
            <div class="p-2.5 bg-white shadow-sm rounded border-start border-danger border-4 text-center px-3">
                <span class="d-block fw-bold fs-5 text-danger"><?= $total_articulos_adquiridos ?></span>
                <small class="text-muted" style="font-size: 0.65rem; font-weight: 700;">ARTÍCULOS ADQUIRIDOS</small>
            </div>
            <div class="p-2.5 bg-white shadow-sm rounded border-start border-success border-4 text-center px-3">
                <span class="d-block fw-bold fs-5 text-success">$<?= number_format($total_inversion_cliente, 2, '.', ',') ?></span>
                <small class="text-muted" style="font-size: 0.65rem; font-weight: 700;">INVERSIÓN TOTAL</small>
            </div>
            <a href="clientes.php" class="btn btn-secondary py-2.5 px-3 fw-semibold shadow-sm d-inline-flex align-items-center" style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Regresar a Clientes
            </a>
        </div>
    </div>
</div>

<!-- TARJETA DE DATOS DEL CLIENTE -->
<div class="card shadow-sm border-0 bg-white rounded-3 p-3 mb-4">
    <div class="row g-3 small text-muted align-items-center">
        <div class="col-12 col-md-3">
            <i class="bi bi-telephone-fill text-secondary me-1"></i> <strong>Teléfono:</strong>
            <span class="d-block text-dark fw-semibold mt-0.5"><?= htmlspecialchars($cliente['telefono'] ?: 'Sin registrar') ?></span>
        </div>
        <div class="col-12 col-md-3">
            <i class="bi bi-envelope-fill text-secondary me-1"></i> <strong>Correo Electrónico:</strong>
            <span class="d-block text-dark fw-semibold mt-0.5"><?= htmlspecialchars($cliente['correo'] ?: 'Sin registrar') ?></span>
        </div>
        <div class="col-12 col-md-3">
            <i class="bi bi-card-text text-secondary me-1"></i> <strong>RFC Receptor:</strong>
            <span class="d-block text-danger fw-bold mt-0.5"><?= htmlspecialchars($cliente['rfc_receptor'] ?: 'XAXX010101000') ?></span>
        </div>
        <div class="col-12 col-md-3">
            <i class="bi bi-geo-alt-fill text-secondary me-1"></i> <strong>Ubicación Fija:</strong>
            <span class="d-block text-dark fw-semibold mt-0.5"><?= htmlspecialchars($cliente['ubicacion'] ?: 'Puebla, México') ?></span>
        </div>
    </div>
</div>

<!-- REGISTRO HISTÓRICO DE COMPRAS (OPCIÓN 1: AGRUPACIÓN POR PEDIDO) -->
<div class="card-main shadow-sm p-4 bg-white rounded-3 border-top border-4 border-danger">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bag-check text-danger me-2"></i> Registro Histórico de Compras</h5>
            <small class="text-muted">Órdenes y pedidos cerrados con sus partidas desglosadas.</small>
        </div>
        <span class="badge bg-light text-dark border px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">
            Total: <?= count($pedidos) ?> Pedido(s) Registrado(s)
        </span>
    </div>

    <?php if (empty($pedidos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-folder-x fs-1 text-secondary d-block mb-2"></i>
            <h6 class="fw-bold text-secondary">Sin compras registradas</h6>
            <span class="small">Este cliente aún no cuenta con cierres comerciales registrados en el sistema.</span>
        </div>
    <?php else: ?>
        <div class="pedidos-container">
            <?php foreach ($pedidos as $clave => $orden): 
                $gran_total_orden = $orden['subtotal_orden'] + $orden['costo_envio'];
            ?>
                <div class="card mb-4 border shadow-sm rounded-3 overflow-hidden bg-white">
                    <!-- CABECERA DEL PEDIDO / COTIZACIÓN -->
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 px-4 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <?php if ($orden['id_cotizacion'] > 0): ?>
                                <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">
                                    <i class="bi bi-file-earmark-check-fill me-1"></i> Cotización #<?= $orden['id_cotizacion'] ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">
                                    <i class="bi bi-bag-fill me-1"></i> Venta Directa
                                </span>
                            <?php endif; ?>

                            <span class="text-secondary small fw-semibold">
                                <i class="bi bi-calendar3 text-danger me-1"></i> <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($orden['fecha_compra'])) ?>
                            </span>

                            <?php if (!empty($orden['observaciones'])): ?>
                                <span class="text-muted small border-start ps-3 d-none d-md-inline">
                                    <i class="bi bi-chat-left-quote text-secondary me-1"></i> "<?= htmlspecialchars($orden['observaciones']) ?>"
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <small class="text-muted d-block" style="font-size: 0.7rem; font-weight: 700;">TOTAL DE LA ORDEN</small>
                                <span class="fw-bold text-success fs-5">$<?= number_format($gran_total_orden, 2, '.', ',') ?> MXN</span>
                            </div>

                            <?php if ($orden['id_cotizacion'] > 0): ?>
                                <a href="generar_pdf_cotizacion.php?id_cotizacion=<?= $orden['id_cotizacion'] ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm fw-semibold d-inline-flex align-items-center gap-1 px-3 py-1.5" style="border-radius: 6px;" title="Descargar o Imprimir PDF oficial">
                                    <i class="bi bi-file-earmark-pdf-fill fs-6"></i> Ver PDF
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- DESGLOSE DE PARTIDAS DE LA ORDEN -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="bg-white text-muted text-uppercase" style="font-size: 0.72rem; border-bottom: 2px solid #f1f1f1;">
                                    <tr>
                                        <th class="ps-4 py-2.5">Producto / Insumo</th>
                                        <th>Categoría</th>
                                        <th class="text-center" style="width: 100px;">Cant.</th>
                                        <th class="text-end" style="width: 150px;">P. Unitario Neto</th>
                                        <th class="text-end pe-4" style="width: 160px;">Importe Neto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orden['partidas'] as $partida): ?>
                                        <tr>
                                            <td class="ps-4 py-2.5">
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($partida['nombre']) ?></span>
                                                <?php if (!empty($partida['sku'])): ?>
                                                    <small class="text-muted d-block" style="font-size: 0.72rem;">SKU: <?= htmlspecialchars($partida['sku']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-muted border px-2.5 py-1" style="font-size: 0.7rem;">
                                                    <?= htmlspecialchars($partida['categoria']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold fs-6 text-dark"><?= $partida['cantidad'] ?></td>
                                            <td class="text-end text-muted">$<?= number_format($partida['precio_unit'], 2, '.', ',') ?></td>
                                            <td class="text-end pe-4 fw-bold text-dark">$<?= number_format($partida['subtotal'], 2, '.', ',') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- PIE CON OBSERVACIONES MÓVIL Y FLETE -->
                    <?php if ($orden['costo_envio'] > 0 || !empty($orden['observaciones'])): ?>
                        <div class="card-footer bg-light bg-opacity-50 py-2 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 0.78rem;">
                            <div class="text-muted">
                                <?php if (!empty($orden['observaciones'])): ?>
                                    <span class="d-md-none"><i class="bi bi-chat-left-text me-1 text-secondary"></i>"<?= htmlspecialchars($orden['observaciones']) ?>"</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($orden['costo_envio'] > 0): ?>
                                <div class="text-secondary ms-auto">
                                    <span>Gasto de Envío / Flete:</span>
                                    <strong class="text-dark">$<?= number_format($orden['costo_envio'], 2, '.', ',') ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>