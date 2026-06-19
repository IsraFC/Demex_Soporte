<?php
/**
 * ARCHIVO: actions/obtener_historial_compras_cliente.php
 * DESCRIPCIÓN: Componente backend encargado de consultar y renderizar el historial de adquisiciones de un cliente.
 * Diseñado para cargarse de forma asíncrona dentro del modal del dashboard.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 (Visor de Historial por AJAX)
 */

require_once '../config/db.php';

$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0;

if ($id_cliente <= 0) {
    echo '<div class="alert alert-warning m-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> ID de cliente no válido.</div>';
    exit();
}

try {
    // Jalamos el historial uniendo con la tabla de maquinaria para traer el nombre del modelo
    $sql = "SELECT vh.*, m.modelo AS maquina_nombre 
            FROM ventas_historial vh
            INNER JOIN maquinaria m ON vh.id_maquina = m.id_maquina
            WHERE vh.id_cliente = :id_cliente
            ORDER BY vh.fecha_compra DESC, vh.id_venta DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_cliente' => $id_cliente]);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($compras)) {
        echo '
        <div class="text-center py-4 text-muted">
            <i class="bi bi-folder-x fs-1 text-secondary d-block mb-2"></i>
            <span class="small">Este cliente no cuenta con registros de compras o adquisiciones históricas en el CRM.</span>
        </div>';
        exit();
    }
?>
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-dark" style="font-size: 0.75rem;">
                <tr class="text-uppercase fw-bold">
                    <th>Fecha Compra</th>
                    <th>Equipo Adquirido</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-end">Precio Pactado</th>
                    <th class="text-end">Total Neto</th>
                    <th>Detalles / Origen</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_invertido_cliente = 0;
                foreach ($compras as $c): 
                    $subtotal_renglon = $c['precio_pactado_neto'] * $c['cantidad'];
                    $total_invertido_cliente += $subtotal_renglon;
                ?>
                <tr>
                    <td class="fw-semibold text-secondary"><?= date('d/m/Y', strtotime($c['fecha_compra'])) ?></td>
                    <td>
                        <span class="badge bg-light text-dark border border-secondary px-2 py-1 fw-bold">
                            <i class="bi bi-cpu text-danger me-1"></i><?= htmlspecialchars($c['maquina_nombre']) ?>
                        </span>
                    </td>
                    <td class="text-center fw-bold"><?= $c['cantidad'] ?></td>
                    <td class="text-end text-muted">$<?= number_format($c['precio_pactado_neto'], 2, '.', ',') ?></td>
                    <td class="text-end fw-bold text-dark">$<?= number_format($subtotal_renglon, 2, '.', ',') ?></td>
                    <td class="small text-muted" style="max-width: 220px; white-space: normal; font-size: 0.75rem;">
                        <?php if(!empty($c['id_cotizacion_origen'])): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger mb-1"><i class="bi bi-file-earmark-check me-1"></i>Cotización #<?= $c['id_cotizacion_origen'] ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary mb-1"><i class="bi bi-box-seam me-1"></i>Base Histórica</span>
                        <?php endif; ?>
                        <div class="lh-sm"><?= htmlspecialchars($c['observaciones_venta']) ?></div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light border-top border-2">
                <tr class="fw-bold fs-6">
                    <td colspan="4" class="text-end text-dark pt-2">Inversión Acumulada de Flota:</td>
                    <td class="text-end text-danger pt-2">$<?= number_format($total_invertido_cliente, 2, '.', ',') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php
} catch (\Exception $e) {
    echo '<div class="alert alert-danger m-0"><i class="bi bi-exclamation-octagon-fill me-2"></i> Fallo técnico al procesar el expediente: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>