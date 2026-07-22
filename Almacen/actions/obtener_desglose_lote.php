<?php
/**
 * ARCHIVO: Almacen/actions/obtener_desglose_lote.php
 * DESCRIPCIÓN: Genera la subtabla HTML desplegable agrupando las máquinas por Modelo y Estatus.
 * @project Almacén Técnico DEMEX
 * @author Israel Fernández Carrera
 */

require_once '../../config/db.php';

$id_lote = intval($_GET['id_lote'] ?? 0);
if ($id_lote <= 0) {
    echo '<div class="text-danger small p-2">Lote no válido.</div>';
    exit();
}

// Consulta agrupada por Modelo y Estatus
$sql = "SELECT id, modelo, estatus, COUNT(*) AS cantidad, no_serie
        FROM almacen_inventario 
        WHERE id_lote = ? 
        GROUP BY modelo, estatus
        ORDER BY modelo ASC, estatus ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_lote]);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($grupos)) {
    echo '<div class="text-muted small p-2">No hay unidades registradas en este lote.</div>';
    exit();
}
?>

<div class="subtabla-lote my-2">
    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-danger border-opacity-25">
        <span class="fw-bold text-danger small text-uppercase"><i class="bi bi-cpu-fill me-1"></i> Desglose Agrupado de Maquinaria</span>
        <small class="text-muted fw-bold">Stock por Modelo y Estado Operativo</small>
    </div>

    <table class="table table-sm table-hover align-middle mb-0 bg-white rounded overflow-hidden shadow-sm" style="font-size: 12px;">
        <thead class="table-dark text-uppercase" style="font-size: 10px;">
            <tr>
                <th class="ps-3">Modelo</th>
                <th class="text-center">Estatus Logístico</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Acciones Rápidas</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grupos as $g): 
                $estatus = $g['estatus'];
                $badge = 'bg-secondary';
                if ($estatus === 'SIN REVISAR') $badge = 'bg-warning text-dark';
                else if ($estatus === 'EN REVISIÓN ALMACÉN' || $estatus === 'EN REVISIÓN SOPORTE') $badge = 'bg-primary text-white';
                else if ($estatus === 'DISPONIBLE PARA SOPORTE' || $estatus === 'DISPONIBLE PARA VENTA') $badge = 'bg-info text-dark';
                else if ($estatus === 'PAGADA / POR ENTREGAR') $badge = 'bg-dark text-white';
                else if ($estatus === 'ENTREGADA') $badge = 'bg-success text-white';
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
                        <?php if ($estatus === 'DISPONIBLE PARA SOPORTE' || $estatus === 'EN REVISIÓN SOPORTE'): ?>
                            <button type="button" class="btn btn-outline-secondary btn-xs border-0 opacity-50" onclick="Swal.fire({icon:'warning', title:'Fase Bloqueada', text:'Proceso administrado por Soporte.'})">
                                <i class="bi bi-lock-fill fs-6"></i>
                            </button>
                        <?php elseif ($estatus === 'PAGADA / POR ENTREGAR' || $estatus === 'CAMBIO'): ?>
                            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold py-0" onclick="abrirModalAsignacion(<?= $g['id'] ?>)" style="font-size: 10px;">
                                <i class="bi bi-person-plus-fill me-1"></i> Asignar y Entregar
                            </button>
                        <?php elseif ($estatus === 'ENTREGADA'): ?>
                            <span class="text-success fw-bold"><i class="bi bi-check-all fs-5"></i></span>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-0" onclick="abrirModalFase(<?= $g['id'] ?>)" style="font-size: 10px;">
                                Siguiente Fase <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>