<?php
/**
 * ARCHIVO: actions/obtener_historial_compras_cliente.php
 * DESCRIPCIÓN: Componente backend para consultar y renderizar el historial de adquisiciones comerciales.
 * Agrupa las compras por Orden / Cotización Origen con tarjetas y desglose de partidas.
 * MODIFICACIÓN: Migrado al catálogo unificado 'productos' y diseño modular por pedido.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.0 (Agrupación Jerárquica por Pedido Comercial)
 */

require_once '../config/db.php';

$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0;

if ($id_cliente <= 0) {
    echo '<div class="alert alert-warning m-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> ID de cliente no válido.</div>';
    exit();
}

try {
    // 1. Detección de columna de producto en ventas_historial
    $col_prod = 'id_producto';
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM ventas_historial LIKE 'id_producto'")->fetch();
        if (!$checkCol) {
            $col_prod = 'id_maquina';
        }
    } catch (\Exception $e) {
        $col_prod = 'id_producto';
    }

    // 2. Consulta unificada enlazando con la tabla central 'productos' y categorías
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

    if (empty($compras_raw)) {
        echo '
        <div class="text-center py-4 text-muted">
            <i class="bi bi-folder-x fs-1 text-secondary d-block mb-2"></i>
            <span class="small">Este cliente no cuenta con registros de compras o adquisiciones históricas en el CRM.</span>
        </div>';
        exit();
    }

    // 3. Agrupación por Orden / Folio de Cotización
    $pedidos = [];
    $total_invertido_global = 0;

    foreach ($compras_raw as $c) {
        $folio_key = !empty($c['id_cotizacion_origen']) ? 'COT-' . $c['id_cotizacion_origen'] : 'HIST-' . $c['fecha_compra'] . '-' . $c['id_venta'];
        
        if (!isset($pedidos[$folio_key])) {
            $pedidos[$folio_key] = [
                'id_cotizacion'   => $c['id_cotizacion_origen'] ?? 0,
                'fecha_compra'    => $c['fecha_compra'],
                'costo_envio'     => floatval($c['costo_envio'] ?? 0),
                'observaciones'   => $c['observaciones_venta'] ?? '',
                'partidas'        => [],
                'total_orden'     => 0
            ];
        }

        $subtotal_partida = floatval($c['precio_pactado_neto']) * intval($c['cantidad']);
        $pedidos[$folio_key]['total_orden'] += $subtotal_partida;
        $total_invertido_global += $subtotal_partida;

        $pedidos[$folio_key]['partidas'][] = [
            'nombre'       => $c['producto_nombre'] ?? 'Producto no especificado',
            'sku'          => $c['sku_codigo'] ?? '',
            'categoria'    => $c['nombre_categoria'] ?? 'General',
            'cantidad'     => intval($c['cantidad']),
            'precio_neto'  => floatval($c['precio_pactado_neto']),
            'subtotal'     => $subtotal_partida
        ];
    }
?>

    <div class="historial-pedidos-wrapper p-1">
        <?php foreach ($pedidos as $folio => $orden): 
            $gran_total_pedido = $orden['total_orden'] + $orden['costo_envio'];
        ?>
            <div class="card mb-3 border shadow-sm rounded-3 overflow-hidden bg-white">
                <!-- CABECERA DEL PEDIDO / ORDEN -->
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.75rem;">
                            <i class="bi bi-bag-check-fill me-1"></i>
                            <?= ($orden['id_cotizacion'] > 0) ? 'Cotización #' . $orden['id_cotizacion'] : 'Venta Registrada' ?>
                        </span>
                        <span class="text-secondary small fw-semibold">
                            <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($orden['fecha_compra'])) ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Total Operación:</span>
                        <span class="fw-bold text-success fs-6">$<?= number_format($gran_total_pedido, 2, '.', ',') ?></span>
                        
                        <?php if ($orden['id_cotizacion'] > 0): ?>
                            <a href="generar_pdf_cotizacion.php?id_cotizacion=<?= $orden['id_cotizacion'] ?>" target="_blank" class="btn btn-sm btn-outline-danger border-0 py-0.5 px-2 ms-1" title="Ver Documento PDF">
                                <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TABLA DE PARTIDAS INCLUIDAS EN EL PEDIDO -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.83rem;">
                            <thead class="bg-white text-muted text-uppercase" style="font-size: 0.7rem; border-bottom: 1px solid #eee;">
                                <tr>
                                    <th class="ps-3 py-2">Producto Adquirido</th>
                                    <th>Categoría</th>
                                    <th class="text-center" style="width: 80px;">Cant.</th>
                                    <th class="text-end" style="width: 130px;">P. Unitario</th>
                                    <th class="text-end pe-3" style="width: 140px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orden['partidas'] as $item): ?>
                                    <tr>
                                        <td class="ps-3 py-2">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($item['nombre']) ?></span>
                                            <?php if (!empty($item['sku'])): ?>
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size: 0.68rem;">
                                                <?= htmlspecialchars($item['categoria']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold"><?= $item['cantidad'] ?></td>
                                        <td class="text-end text-muted">$<?= number_format($item['precio_neto'], 2, '.', ',') ?></td>
                                        <td class="text-end pe-3 fw-bold text-dark">$<?= number_format($item['subtotal'], 2, '.', ',') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PIE DEL PEDIDO: OBSERVACIONES Y FLETE -->
                <?php if (!empty($orden['observaciones']) || $orden['costo_envio'] > 0): ?>
                    <div class="card-footer bg-light bg-opacity-50 py-2 px-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 0.78rem;">
                        <div class="text-muted">
                            <?php if (!empty($orden['observaciones'])): ?>
                                <i class="bi bi-chat-left-text me-1 text-secondary"></i>
                                <span class="fst-italic"><?= htmlspecialchars($orden['observaciones']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($orden['costo_envio'] > 0): ?>
                            <div class="text-secondary">
                                <span>Flete / Envío:</span>
                                <strong class="text-dark">$<?= number_format($orden['costo_envio'], 2, '.', ',') ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- RESUMEN GENERAL AL FINAL DEL HISTORIAL -->
        <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-white border border-2 border-danger-subtle shadow-sm mt-3">
            <span class="fw-bold text-dark"><i class="bi bi-wallet2 text-danger me-2"></i>Inversión Histórica Total del Cliente:</span>
            <span class="fs-5 fw-bold text-danger">$<?= number_format($total_invertido_global, 2, '.', ',') ?> MXN</span>
        </div>
    </div>

<?php
} catch (\Exception $e) {
    echo '<div class="alert alert-danger m-0">Fallo técnico al procesar el expediente: ' . htmlspecialchars($e->getMessage()) . '</div>';
}