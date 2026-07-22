<?php
/**
 * ARCHIVO: Almacen/registro_lote.php
 * DESCRIPCIÓN: Interfaz para el registro masivo de lotes/contenedores de importación.
 * Controles con dimensiones y estilos 100% estandarizados en altura y peso visual.
 * @project Almacén Técnico DEMEX
 * @version 6.2 - Estilos Uniformes de Alta Precisión
 * @author Israel Fernández Carrera
 */
require_once '../config/db.php';
$page_title = "Registrar Lote de Importación";

$modelos_oficiales = [
    'DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 
    'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'
];

include '../includes/header.php';
?>

<style>
    /* 1. Forzar altura, tipografía y estilo uniforme para TODOS los controles */
    .form-control-demex {
        height: 45px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        background-color: #f8f9fa !important;
        border: 1px solid #e9ecef !important;
        border-radius: 50rem !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04) !important;
    }

    /* Enfoque homogéneo en color rojo DEMEX */
    .form-control-demex:focus, 
    .input-group-demex:focus-within {
        background-color: #ffffff !important;
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
    }

    /* Contenedor tipo Input-Group estandarizado a 45px */
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

    /* Botón de acción proporcional de 45px */
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
        <h1 class="fw-bold text-danger mb-0 text-uppercase"><i class="bi bi-boxes me-2"></i>Nuevo Lote de Importación</h1>
        <p class="text-muted small">Registra el contenedor de llegada y el desglose de maquinaria por modelo.</p>
    </div>
</div>

<form action="actions/procesar_lote.php" method="POST" id="formRegistroLote">
    <div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger mb-4 mx-auto" style="max-width: 850px;">
        
        <!-- CABECERA DE DATOS DEL EMBARQUE -->
        <h5 class="fw-bold mb-3 text-secondary text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px;">
            <i class="bi bi-truck me-2 text-danger"></i>Datos del Embarque / Contenedor
        </h5>
       
        <div class="row g-3 mb-4">
            <!-- Identificador del Contenedor -->
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted mb-1">Identificador del Contenedor / Lote</label>
                <div class="input-group input-group-demex d-flex align-items-center px-2">
                    <span class="input-group-text text-danger fs-6"><i class="bi bi-box-seam"></i></span>
                    <input type="text" name="contenedor" id="contenedor" class="form-control border-0 bg-transparent text-uppercase text-dark" placeholder="EJ: CONT-2026-07" required autocomplete="off">
                </div>
            </div>

            <!-- Tipo de Stock -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Tipo de Stock</label>
                <select name="tipo" id="tipo" class="form-select form-control-demex px-3 text-dark" required>
                    <option value="ORIGINAL" selected>ORIGINAL</option>
                    <option value="DEMO">DEMO</option>
                </select>
            </div>

            <!-- Fecha de Arribo -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Fecha de Arribo</label>
                <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control form-control-demex px-3 text-muted" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <!-- SECCIÓN DINÁMICA DE MAQUINARIA -->
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h5 class="fw-bold mb-0 text-secondary text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px;">
                <i class="bi bi-cpu-fill me-2 text-danger"></i>Desglose de Maquinaria
            </h5>
            <button type="button" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btnAgregarModelo" style="height: 38px;">
                <i class="bi bi-plus-circle me-1"></i> Agregar Modelo
            </button>
        </div>

        <div id="contenedorModelos">
            <div class="row g-2 align-items-center mb-3 fila-modelo">
                <!-- Select Modelo (Misma Altura) -->
                <div class="col-md-7">
                    <select name="modelos[]" class="form-select form-control-demex px-3 text-dark" required>
                        <option value="" disabled selected>-- Seleccione Modelo --</option>
                        <?php foreach($modelos_oficiales as $mod): ?>
                            <option value="<?= $mod ?>"><?= $mod ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Input Cantidad (Misma Altura) -->
                <div class="col-md-4">
                    <div class="input-group input-group-demex d-flex align-items-center px-2">
                        <span class="input-group-text text-muted fs-6"><i class="bi bi-hash"></i></span>
                        <input type="number" name="cantidades[]" class="form-control border-0 bg-transparent text-dark text-center" placeholder="Cantidad de Piezas" min="1" max="200" required>
                    </div>
                </div>
                <!-- Botón Eliminar Proporcional -->
                <div class="col-md-1 text-center">
                    <button type="button" class="btn btn-outline-danger border-0 btn-action-demex btnEliminarFila" disabled title="Eliminar fila">
                        <i class="bi bi-trash-fill fs-6"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div class="text-center mt-4 d-flex justify-content-center gap-3">
        <a href="index.php" class="btn btn-light border px-5 rounded-pill fw-bold text-dark shadow-sm" style="height: 45px; line-height: 30px;">Cancelar</a>
        <button type="submit" id="btnGuardarLote" class="btn btn-danger px-5 rounded-pill fw-bold shadow" style="background-color: #dc3545; height: 45px;">
            <span id="btnText">Generar Lote en Stock</span> <i class="bi bi-check-circle ms-1"></i>
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

    $('#formRegistroLote').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#btnGuardarLote');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Procesando Lote...');

        fetch(this.action, { method: this.method, body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Lote Creado!',
                    text: data.message,
                    confirmButtonColor: '#198754'
                }).then(() => { window.location.href = 'index.php'; });
            } else {
                btn.prop('disabled', false).html('Generar Lote en Stock <i class="bi bi-check-circle ms-1"></i>');
                Swal.fire({ icon: 'error', title: 'Atención', text: data.message, confirmButtonColor: '#dc3545' });
            }
        })
        .catch(error => {
            btn.prop('disabled', false).html('Generar Lote en Stock <i class="bi bi-check-circle ms-1"></i>');
            Swal.fire({ icon: 'error', title: 'Error de Red', text: error.message, confirmButtonColor: '#dc3545' });
        });
    });
});
</script>