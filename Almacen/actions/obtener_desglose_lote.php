<?php
/**
 * ARCHIVO: Almacen/actions/obtener_desglose_lote.php
 * DESCRIPCIÓN: Subtabla desplegable dividida en Unidades Sin Serie (Agrupadas) y Unidades Con Serie (Seguimiento Individual).
 * @project Almacén Técnico DEMEX
 * @version 7.0 - Seguimiento Híbrido por Serie Única
 * @author Israel Fernández Carrera
 */

require_once '../../config/db.php';

$id_lote = intval($_GET['id_lote'] ?? 0);
if ($id_lote <= 0) {
    echo '<div class="text-danger small p-2">Lote no válido.</div>';
    exit();
}

// 1. Consultar información del Lote
$stmtLote = $pdo->prepare("SELECT contenedor, observaciones FROM almacen_lotes WHERE id_lote = ?");
$stmtLote->execute([$id_lote]);
$infoLote = $stmtLote->fetch(PDO::FETCH_ASSOC);

// 2. Consultar unidades SIN SERIE (Aún en 'SIN REVISAR')
$sqlSinSerie = "SELECT id, modelo, estatus, COUNT(*) AS cantidad
                FROM almacen_inventario 
                WHERE id_lote = ? AND (no_serie IS NULL OR no_serie = '' OR estatus = 'SIN REVISAR')
                GROUP BY modelo";
$stmtSin = $pdo->prepare($sqlSinSerie);
$stmtSin->execute([$id_lote]);
$sinSerie = $stmtSin->fetchAll(PDO::FETCH_ASSOC);

// 3. Consultar unidades CON SERIE (En proceso, disponibles para venta, etc.)
$sqlConSerie = "SELECT id, modelo, no_serie, estatus
                FROM almacen_inventario 
                WHERE id_lote = ? AND no_serie IS NOT NULL AND no_serie != '' AND estatus != 'SIN REVISAR'
                ORDER BY modelo ASC, id ASC";
$stmtCon = $pdo->prepare($sqlConSerie);
$stmtCon->execute([$id_lote]);
$conSerie = $stmtCon->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="subtabla-lote my-2 p-3 bg-light rounded-4 border">
    
    <?php if (!empty($infoLote['observaciones'])): ?>
        <div class="alert alert-white border shadow-sm rounded-3 p-2 mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-chat-left-text-fill text-danger fs-5"></i>
            <div>
                <small class="fw-bold text-secondary text-uppercase d-block" style="font-size: 10px;">Observaciones del Contenedor:</small>
                <span class="small fw-semibold text-dark"><?= htmlspecialchars($infoLote['observaciones']) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($sinSerie)): ?>
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-warning">
                <span class="fw-bold text-warning text-dark small text-uppercase">
                    <i class="bi bi-box-seam me-1"></i> Maquinaria en Caja (Sin Asignar Serie)
                </span>
                <span class="badge bg-warning text-dark">Pendientes de Apertura</span>
            </div>

            <table class="table table-sm table-hover align-middle mb-0 bg-white rounded overflow-hidden shadow-sm" style="font-size: 12px;">
                <thead class="table-light text-uppercase" style="font-size: 10px;">
                    <tr>
                        <th class="ps-3">Modelo</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Cantidad en Caja</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sinSerie as $s): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($s['modelo']) ?></td>
                            <td class="text-center"><span class="badge bg-warning text-dark">SIN REVISAR</span></td>
                            <td class="text-center fw-bold fs-6 text-dark"><?= $s['cantidad'] ?> pzs</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 py-0 fw-bold" onclick="abrirModalFase(<?= $s['id'] ?>)" style="font-size: 11px;">
                                    <i class="bi bi-barcode me-1"></i> Asignar Serie / Abrir Caja
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div>
        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-danger">
            <span class="fw-bold text-danger small text-uppercase">
                <i class="bi bi-upc-scan me-1"></i> Seguimiento Individual por Número de Serie
            </span>
            <small class="text-muted fw-bold">Unidades identificadas con placa física</small>
        </div>

        <?php if (!empty($conSerie)): ?>
            <table class="table table-sm table-hover align-middle mb-0 bg-white rounded overflow-hidden shadow-sm" style="font-size: 12px;">
                <thead class="table-dark text-uppercase" style="font-size: 10px;">
                    <tr>
                        <th class="ps-3">Modelo</th>
                        <th>Número de Serie Real</th>
                        <th class="text-center">Estatus Actual</th>
                        <th class="text-center">Acción Técnica / Comercial</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conSerie as $c): 
                        $estatus = $c['estatus'];
                        $badge = 'bg-secondary';
                        if ($estatus === 'EN REVISIÓN ALMACÉN' || $estatus === 'EN REVISIÓN SOPORTE') $badge = 'bg-primary text-white';
                        else if ($estatus === 'DISPONIBLE PARA SOPORTE' || $estatus === 'DISPONIBLE PARA VENTA') $badge = 'bg-info text-dark';
                        else if ($estatus === 'PAGADA / POR ENTREGAR') $badge = 'bg-dark text-white';
                        else if ($estatus === 'ENTREGADA') $badge = 'bg-success text-white';
                        else if ($estatus === 'COMODATO') $badge = 'bg-purple text-white';
                    ?>
                        <tr>
                            <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($c['modelo']) ?></td>
                            <td><code class="fw-bold text-danger fs-6"><?= htmlspecialchars($c['no_serie']) ?></code></td>
                            <td class="text-center">
                                <span class="badge <?= $badge ?> rounded-pill px-3 py-1"><?= htmlspecialchars($estatus) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($estatus === 'DISPONIBLE PARA SOPORTE' || $estatus === 'EN REVISIÓN SOPORTE'): ?>
                                    <button type="button" class="btn btn-outline-secondary btn-xs border-0 opacity-50" onclick="Swal.fire({icon:'warning', title:'Fase Bloqueada', text:'Proceso administrado por el Laboratorio de Soporte.'})">
                                        <i class="bi bi-lock-fill fs-6"></i> En Soporte
                                    </button>
                                <?php elseif ($estatus === 'PAGADA / POR ENTREGAR' || $estatus === 'CAMBIO'): ?>
                                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold py-0" onclick="abrirModalAsignacion(<?= $c['id'] ?>)" style="font-size: 11px;">
                                        <i class="bi bi-person-plus-fill me-1"></i> Asignar Cliente y Entregar
                                    </button>
                                <?php elseif ($estatus === 'ENTREGADA'): ?>
                                    <span class="text-success fw-bold"><i class="bi bi-check-all fs-5 me-1"></i> Entregada</span>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-0 fw-bold" onclick="abrirModalFase(<?= $c['id'] ?>)" style="font-size: 11px;">
                                        Avanzar Fase <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center text-muted small py-3 bg-white rounded border">
                Aún no hay unidades con número de serie registrado en este lote. Asigne serie a una unidad en caja para iniciar seguimiento.
            </div>
        <?php endif; ?>
    </div>

</div>