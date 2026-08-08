<?php
/**
 * ARCHIVO: Almacen/editar_lote.php
 * DESCRIPCIÓN: Formulario para la modificación de datos generales, modelos y cantidades del lote.
 * @project Almacén Técnico DEMEX
 * @version 2.0 - Edición Completa de Modelos y Cantidades
 * @author Israel Fernández Carrera
 */

require_once '../config/db.php';

$id_lote = intval($_GET['id_lote'] ?? 0);
if ($id_lote <= 0) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM almacen_lotes WHERE id_lote = ?");
$stmt->execute([$id_lote]);
$lote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lote) {
    header("Location: index.php");
    exit();
}

// Cargar desglose actual de modelos y cantidades de este lote
$stmtModelos = $pdo->prepare("SELECT modelo, COUNT(*) as cantidad FROM almacen_inventario WHERE id_lote = ? GROUP BY modelo ORDER BY modelo ASC");
$stmtModelos->execute([$id_lote]);
$modelos_actuales = $stmtModelos->fetchAll(PDO::FETCH_ASSOC);

$modelos_oficiales = [
    'DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 
    'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'
];

$page_title = "Editar Lote - Almacén";
include '../includes/header.php';
?>

<style>
    .form-control-demex {
        height: 45px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        background-color: #f8f9fa !important;
        border: 1px solid #e9ecef !important;
        border-radius: 50rem !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04) !important;
    }

    .form-control-demex:focus, 
    .input-group-demex:focus-within {
        background-color: #ffffff !important;
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
    }

    .input-group-demex {
        height: 45px !important;
        background-color: #f8f9fa !important;
        border: 1px solid #e9ecef !important;
        border-radius: 50rem !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04) !important;
        overflow: hidden;
    }

    .input-group-demex .form-control {
        height: 100% !important;
        font-size: 14px !important;
        font-weight: 600 !important;
    }

    .input-group-demex .input-group-text {
        height: 100% !important;
        background: transparent !important;
        border: none !important;
    }

    .btn-action-demex {
        height: 45px !important;
        width: 45px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 50% !important;
    }
</style>

<div class="row mb-4 animate__animated animate__fadeIn">
    <div class="col-12">
        <h1 class="fw-bold text-danger mb-0 text-uppercase"><i class="bi bi-pencil-square me-2"></i>Modificar Lote de Importación</h1>
        <p class="text-muted small">Actualice el identificador, observaciones o ajusta el desglose de maquinaria por modelo.</p>
    </div>
</div>

<form action="actions/procesar_lote.php" method="POST" id="formEditarLote">
    <input type="hidden" name="id_lote" value="<?= $lote['id_lote'] ?>">

    <div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger mb-4 mx-auto" style="max-width: 850px;">
        <h5 class="fw-bold mb-4 text-secondary text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px;">
            <i class="bi bi-truck me-2 text-danger"></i>Datos Generales del Contenedor
        </h5>
       
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted mb-1">Identificador del Contenedor / Lote</label>
                <div class="input-group input-group-demex d-flex align-items-center px-2">
                    <span class="input-group-text text-danger fs-6"><i class="bi bi-box-seam"></i></span>
                    <input type="text" name="contenedor" id="contenedor" class="form-control border-0 bg-transparent text-uppercase text-dark" value="<?= htmlspecialchars($lote['contenedor']) ?>" required autocomplete="off">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Tipo de Stock</label>
                <select name="tipo" id="tipo" class="form-select form-control-demex px-3 text-dark" required>
                    <option value="ORIGINAL" <?= $lote['tipo'] === 'ORIGINAL' ? 'selected' : '' ?>>ORIGINAL</option>
                    <option value="DEMO" <?= $lote['tipo'] === 'DEMO' ? 'selected' : '' ?>>DEMO</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Fecha de Arribo</label>
                <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control form-control-demex px-3 text-muted" value="<?= $lote['fecha_ingreso'] ?>" required>
            </div>

            <div class="col-12 mt-3">
                <label class="form-label small fw-bold text-muted mb-1">Observaciones / Notas del Contenedor</label>
                <textarea name="observaciones" id="observaciones" class="form-control bg-light border-0 shadow-sm rounded-4 p-3 fw-semibold text-dark" rows="2" placeholder="Notas adicionales sobre este lote..."><?= htmlspecialchars($lote['observaciones'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h5 class="fw-bold mb-0 text-secondary text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px;">
                <i class="bi bi-cpu-fill me-2 text-danger"></i>Ajustar Desglose de Maquinaria
            </h5>
            <button type="button" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btnAgregarModelo" style="height: 38px;">
                <i class="bi bi-plus-circle me-1"></i> Agregar Modelo
            </button>
        </div>

        <div id="contenedorModelos">
            <?php foreach ($modelos_actuales as $idx => $m): ?>
                <div class="row g-2 align-items-center mb-3 fila-modelo">
                    <div class="col-md-7">
                        <select name="modelos[]" class="form-select form-control-demex px-3 text-dark" required>
                            <option value="" disabled>-- Seleccione Modelo --</option>
                            <?php foreach($modelos_oficiales as $mod): ?>
                                <option value="<?= $mod ?>" <?= $m['modelo'] === $mod ? 'selected' : '' ?>><?= $mod ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-demex d-flex align-items-center px-2">
                            <span class="input-group-text text-muted fs-6"><i class="bi bi-hash"></i></span>
                            <input type="number" name="cantidades[]" class="form-control border-0 bg-transparent text-dark text-center" value="<?= intval($m['cantidad']) ?>" placeholder="Cantidad" min="1" max="200" required>
                        </div>
                    </div>
                    <div class="col-md-1 text-center">
                        <button type="button" class="btn btn-outline-danger border-0 btn-action-demex btnEliminarFila" <?= count($modelos_actuales) <= 1 ? 'disabled' : '' ?> title="Eliminar fila">
                            <i class="bi bi-trash-fill fs-6"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="text-center mt-4 d-flex justify-content-center gap-3">
        <a href="index.php" class="btn btn-light border px-5 rounded-pill fw-bold text-dark shadow-sm" style="height: 45px; line-height: 30px;">Cancelar</a>
        <button type="submit" id="btnGuardarLote" class="btn btn-danger px-5 rounded-pill fw-bold shadow" style="background-color: #dc3545; height: 45px;">
            <span>Guardar Cambios</span> <i class="bi bi-check-circle ms-1"></i>
        </button>
    </div>
</form>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    const opcionesModelos = `<?php foreach($modelos_oficiales as $mod): ?><option value="<?= $mod ?>"><?= $mod ?></option><?php endforeach; ?>`;

    $('#btnAgregarModelo').on('click', function() {
        const nuevaFila = `
            <div class="row g-2 align-items-center mb-3 fila-modelo animate__animated animate__fadeIn">
                <div class="col-md-7">
                    <select name="modelos[]" class="form-select form-control-demex px-3 text-dark" required>
                        <option value="" disabled selected>-- Seleccione Modelo --</option>
                        ${opcionesModelos}
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-demex d-flex align-items-center px-2">
                        <span class="input-group-text text-muted fs-6"><i class="bi bi-hash"></i></span>
                        <input type="number" name="cantidades[]" class="form-control border-0 bg-transparent text-dark text-center" placeholder="Cantidad de Piezas" min="1" max="200" required>
                    </div>
                </div>
                <div class="col-md-1 text-center">
                    <button type="button" class="btn btn-outline-danger border-0 btn-action-demex btnEliminarFila" title="Eliminar fila">
                        <i class="bi bi-trash-fill fs-6"></i>
                    </button>
                </div>
            </div>`;
        
        $('#contenedorModelos').append(nuevaFila);
        actualizarBotonesEliminar();
    });

    $(document).on('click', '.btnEliminarFila', function() {
        if ($('.fila-modelo').length > 1) {
            $(this).closest('.fila-modelo').remove();
            actualizarBotonesEliminar();
        }
    });

    function actualizarBotonesEliminar() {
        if ($('.fila-modelo').length <= 1) {
            $('.btnEliminarFila').prop('disabled', true);
        } else {
            $('.btnEliminarFila').prop('disabled', false);
        }
    }

    $('#formEditarLote').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#btnGuardarLote');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');

        fetch(this.action, { method: this.method, body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Lote Actualizado!',
                    text: data.message,
                    confirmButtonColor: '#198754'
                }).then(() => { window.location.href = 'index.php'; });
            } else {
                btn.prop('disabled', false).html('Guardar Cambios <i class="bi bi-check-circle ms-1"></i>');
                Swal.fire({ icon: 'error', title: 'Atención', text: data.message, confirmButtonColor: '#dc3545' });
            }
        })
        .catch(error => {
            btn.prop('disabled', false).html('Guardar Cambios <i class="bi bi-check-circle ms-1"></i>');
            Swal.fire({ icon: 'error', title: 'Error de Red', text: error.message, confirmButtonColor: '#dc3545' });
        });
    });
});
</script>