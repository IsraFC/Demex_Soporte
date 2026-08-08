<?php
/**
 * ARCHIVO: Soporte/actions/obtener_desglose_soporte.php
 * DESCRIPCIÓN: Subtabla desplegable de máquinas en taller por Lote, mostrando Modelo, Serie y Acciones.
 * @project Soporte Técnico DEMEX
 * @version 4.0 - Desglose Individual con Número de Serie
 * @author Israel Fernández Carrera
 */

require_once '../../config/db.php';

$id_lote = intval($_GET['id_lote'] ?? 0);
if ($id_lote <= 0) {
    echo '<div class="text-danger small p-2">Lote no válido.</div>';
    exit();
}

$sql = "SELECT id, modelo, IFNULL(no_serie, 'S/N PENDIENTE') AS no_serie, estatus
        FROM almacen_inventario 
        WHERE id_lote = ? AND estatus IN ('DISPONIBLE PARA SOPORTE', 'EN REVISIÓN SOPORTE')
        ORDER BY modelo ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_lote]);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($unidades)) {
    echo '<div class="text-muted small p-2 text-center">No hay unidades pendientes en taller para este lote.</div>';
    exit();
}
?>

<div class="subtabla-lote my-2 p-3 bg-light rounded-4 border">
    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-danger border-opacity-25">
        <span class="fw-bold text-danger small text-uppercase"><i class="bi bi-tools me-1"></i> Maquinaria en Laboratorio de Calibración</span>
        <small class="text-muted fw-bold">Seguimiento Técnico por Número de Serie</small>
    </div>

    <table class="table table-sm table-hover align-middle mb-0 bg-white rounded overflow-hidden shadow-sm" style="font-size: 12px;">
        <thead class="table-dark text-uppercase" style="font-size: 10px;">
            <tr>
                <th class="ps-3">Modelo</th>
                <th>Número de Serie Real</th>
                <th class="text-center">Estatus Técnico</th>
                <th class="text-center">Firma de Fase</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($unidades as $u): 
                $estatus = $u['estatus'];
                $badge = ($estatus === 'DISPONIBLE PARA SOPORTE') ? 'bg-warning text-dark' : 'bg-primary text-white';
            ?>
                <tr>
                    <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($u['modelo']) ?></td>
                    <td><code class="fw-bold text-danger fs-6"><?= htmlspecialchars($u['no_serie']) ?></code></td>
                    <td class="text-center">
                        <span class="badge <?= $badge ?> rounded-pill px-3 py-1"><?= htmlspecialchars($estatus) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($estatus === 'DISPONIBLE PARA SOPORTE'): ?>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold py-0" style="font-size: 10px;" onclick="ejecutarCambioFase(<?= $u['id'] ?>, 'EN REVISIÓN SOPORTE', 'fecha_entrega_soporte')">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Recibir en Taller
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold py-0" style="font-size: 10px;" onclick="ejecutarCambioFase(<?= $u['id'] ?>, 'REINGRESO A ALMACÉN', 'fecha_reingreso_almacen')">
                                <i class="bi bi-send-check me-1"></i> Liberar a Almacén
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>