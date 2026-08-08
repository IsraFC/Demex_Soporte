<?php
/**
 * ARCHIVO: Almacen/actions/obtener_unidades_lote.php
 * DESCRIPCIÓN: Renderiza el desglose de máquinas pertenecientes a un Lote.
 * Permite la captura/edición del Número de Serie y cambio de estatus individual.
 * @project Almacén Técnico DEMEX
 */

require_once '../../config/db.php';

$id_lote = intval($_GET['id_lote'] ?? 0);
if ($id_lote <= 0) { echo "<p class='text-danger text-center'>Lote inválido.</p>"; exit; }

$stmt = $pdo->prepare("SELECT * FROM almacen_inventario WHERE id_lote = ? ORDER BY modelo ASC, id ASC");
$stmt->execute([$id_lote]);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($unidades)) {
    echo "<p class='text-muted text-center py-4'>No hay máquinas asociadas a este lote.</p>";
    exit;
}
?>

<div class="table-responsive">
    <table class="table table-hover align-middle small">
        <thead class="table-light text-uppercase">
            <tr>
                <th>#</th>
                <th>Modelo</th>
                <th>Número de Serie Real</th>
                <th>Estatus Actual</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($unidades as $u): ?>
                <tr>
                    <td class="fw-bold text-muted"><?= $i++ ?></td>
                    <td class="fw-bold text-dark"><?= htmlspecialchars($u['modelo']) ?></td>
                    <td>
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <input type="text" id="serie_<?= $u['id'] ?>" class="form-control fw-bold text-danger text-uppercase" 
                                   value="<?= htmlspecialchars($u['no_serie'] ?? '') ?>" 
                                   placeholder="Ingresar S/N...">
                            <button class="btn btn-outline-danger" type="button" onclick="guardarSerieIndividual(<?= $u['id'] ?>)" title="Guardar Serie">
                                <i class="bi bi-save-fill"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-secondary"><?= htmlspecialchars($u['estatus']) ?></span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="abrirModalFase(<?= $u['id'] ?>)">
                            <i class="bi bi-arrow-right-circle me-1"></i> Fase
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function guardarSerieIndividual(id_inventario) {
    const serie = $('#serie_' + id_inventario).val().trim();
    if (!serie) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Escriba un número de serie válido.' });
        return;
    }

    $.ajax({
        url: 'actions/guardar_serie_individual.php',
        method: 'POST',
        data: { id: id_inventario, no_serie: serie },
        dataType: 'json',
        success: function(res) {
            Swal.fire({
                icon: res.success ? 'success' : 'error',
                title: res.success ? '¡Serie Registrada!' : 'Error',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            });
            if(res.success && typeof table !== 'undefined') table.ajax.reload(null, false);
        }
    });
}
</script>