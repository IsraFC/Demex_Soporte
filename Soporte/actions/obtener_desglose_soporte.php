<?php
/**
 * ARCHIVO: Soporte/actions/obtener_desglose_soporte.php
 * DESCRIPCIÓN: Subtabla agrupada de máquinas en taller por Modelo y Estatus para Soporte.
 * @project Soporte Técnico DEMEX
 * @author Israel Fernández Carrera
 */

require_once '../../config/db.php';

$id_lote = intval($_GET['id_lote'] ?? 0);
if ($id_lote <= 0) {
    echo '<div class="text-danger small p-2">Lote no válido.</div>';
    exit();
}

$sql = "SELECT id, modelo, estatus, COUNT(*) AS cantidad, no_serie
        FROM almacen_inventario 
        WHERE id_lote = ? AND estatus IN ('DISPONIBLE PARA SOPORTE', 'EN REVISIÓN SOPORTE')
        GROUP BY modelo, estatus
        ORDER BY modelo ASC, estatus ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_lote]);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($grupos)) {
    echo '<div class="text-muted small p-2">No hay unidades pendientes en Soporte para este lote.</div>';
    exit();
}
?>

<div class="subtabla-lote my-2">
    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-danger border-opacity-25">
        <span class="fw-bold text-danger small text-uppercase"><i class="bi bi-cpu-fill me-1"></i> Modelos en Taller Técnico</span>
        <small class="text-muted fw-bold">Gestión de Calibración e Inspección</small>
    </div>

    <table class="table table-sm table-hover align-middle mb-0 bg-white rounded overflow-hidden shadow-sm" style="font-size: 12px;">
        <thead class="table-dark text-uppercase" style="font-size: 10px;">
            <tr>
                <th class="ps-3">Modelo</th>
                <th class="text-center">Estatus Técnico</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Firma de Fase</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grupos as $g): 
                $estatus = $g['estatus'];
                $badge = ($estatus === 'DISPONIBLE PARA SOPORTE') ? 'bg-warning text-dark' : 'bg-primary text-white';
            ?>
                <tr>
                    <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($g['modelo']) ?></td>
                    <td class="text-center">
                        <span class="badge <?= $badge ?> rounded-pill px-3 py-1"><?= htmlspecialchars($estatus) ?></span>
                    </td>
                    <td class="text-center fw-bold fs-6 text-danger">
                        <?= intval($g['cantidad']) ?> <small class="text-muted fs-6" style="font-size: 10px !important;">pzs</small>
                    </td>
                    <td class="text-center">
                        <?php if ($estatus === 'DISPONIBLE PARA SOPORTE'): ?>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold py-0" style="font-size: 10px;" onclick="ejecutarCambioFase(<?= $g['id'] ?>, 'EN REVISIÓN SOPORTE', 'fecha_entrega_soporte')">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Recibir
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold py-0" style="font-size: 10px;" onclick="ejecutarCambioFase(<?= $g['id'] ?>, 'REINGRESO A ALMACÉN', 'fecha_reingreso_almacen')">
                                <i class="bi bi-send-check me-1"></i> Liberar
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>