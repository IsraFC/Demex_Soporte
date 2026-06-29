<?php
/**
 * ARCHIVO: Almacen/registro_lote.php
 * DESCRIPCIÓN: Interfaz para el registro individual de maquinaria nueva de importación en Almacén.
 * Optimizada para captura rápida mediante teclado y adaptada a los modelos oficiales DEMEX.
 * @project Almacén Técnico DEMEX
 * @version 5.3 - Catálogo Oficial de Modelos de Producción
 */
require_once '../config/db.php';
$page_title = "Registrar Entrada de Maquinaria Nueva";

include '../includes/header.php';
?>

<div class="row mb-4 animate__animated animate__fadeIn">
    <div class="col-12">
        <h1 class="fw-bold text-danger mb-0 text-uppercase"><i class="bi bi-box-arrow-in-down me-2"></i>Registrar Ingreso de Stock</h1>
        <p class="text-muted">Introduce el número de serie y especificaciones de la maquinaria nueva para darla de alta en el inventario global.</p>
    </div>
</div>

<form action="actions/procesar_lote.php" method="POST" id="formRegistroLote">
    <div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger mb-4 mx-auto" style="max-width: 700px;">
        <h5 class="fw-bold mb-4 text-secondary text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
            <i class="bi bi-file-earmark-medical-fill me-2 text-danger"></i>Especificaciones del Contenedor y Equipo
        </h5>
        
        <div class="row g-3">
            <!-- Número de Serie -->
            <div class="col-md-12">
                <label class="form-label small fw-bold text-muted">Número de Serie</label>
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-light border px-2 py-1">
                    <span class="input-group-text border-0 bg-transparent text-danger"><i class="bi bi-hash"></i></span>
                    <input type="text" name="no_serie" id="no_serie_input" class="form-control border-0 bg-transparent fw-bold text-uppercase text-danger" placeholder="Numero de serie..." required autocomplete="off" style="font-size: 15px;">
                </div>
                <div id="status_busqueda" class="mt-2 p-2 rounded small fw-bold" style="display:none; background-color: #f8f9fa; border-left: 4px solid #dee2e6;">
                    <span id="txt_status_busqueda">Validando formato de serie...</span>
                </div>
            </div>

            <!-- Modelo de la Máquina -->
            <div class="col-md-12">
                <label class="form-label small fw-bold text-muted">Modelo de la Maquinaria</label>
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-light border px-2 py-1">
                    <span class="input-group-text border-0 bg-transparent text-muted"><i class="bi bi-cpu-fill"></i></span>
                    <select name="modelo" id="modelo" class="form-control border-0 bg-transparent fw-bold text-dark" required style="font-size: 15px; background: transparent;">
                        <option value="" disabled selected>Seleccione el modelo correspondiente...</option>
                        <option value="DEMEX 313">DEMEX 313</option>
                        <option value="DEMEX 313T">DEMEX 313T</option>
                        <option value="DEMEX 513">DEMEX 513</option>
                        <option value="DEMEX 613">DEMEX 613</option>
                        <option value="DEMEX 1020">DEMEX 1020</option>
                        <option value="DEMEX 125">DEMEX 125</option>
                        <option value="SPICE MT15">SPICE MT15</option>
                        <option value="SPICE MV89">SPICE MV89</option>
                    </select>
                </div>
            </div>

            <!-- Identificador del Contenedor -->
            <div class="col-md-12">
                <label class="form-label small fw-bold text-muted">Identificador del Contenedor / Lote de Importación</label>
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-light border px-2 py-1">
                    <span class="input-group-text border-0 bg-transparent text-muted"><i class="bi bi-truck"></i></span>
                    <input type="text" name="contenedor" id="contenedor" class="form-control border-0 bg-transparent fw-bold text-uppercase text-dark" placeholder="Identificador del contenedor..." required autocomplete="off">
                </div>
            </div>

            <!-- Tipo de Stock -->
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Tipo de Stock</label>
                <select name="tipo" id="tipo" class="form-select border-5 rounded-pill bg-light shadow-sm fw-bold text-dark px-3" required>
                    <option value="ORIGINAL" selected>ORIGINAL</option>
                    <option value="DEMO">DEMO</option>
                </select>
            </div>

            <!-- Fecha de Arribo -->
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Fecha de Arribo a Almacén</label>
                <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control border-5 rounded-pill bg-light shadow-sm fw-semibold text-muted px-3" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>
    </div>

    <div class="text-center mt-4 d-flex justify-content-center gap-3">
        <a href="index.php" class="btn btn-light border px-5 rounded-pill fw-bold text-dark shadow-sm">Cancelar</a>
        <button type="submit" id="btnGuardarLote" class="btn btn-danger px-5 rounded-pill fw-bold shadow" style="background-color: #dc3545;">
            <span id="btnText">Finalizar Entrada</span> <i class="bi bi-check-circle ms-1"></i>
        </button>
    </div>
</form>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Validamos únicamente que el campo contenga caracteres antes de activar el botón
    $('#no_serie_input').on('input', function() {
        let val = $(this).val().trim();
        let msgDiv = $('#status_busqueda');
        let txtStatus = $('#txt_status_busqueda');
        let btnGuardar = $('#btnGuardarLote');

        if (val.length > 2) {
            msgDiv.show();
            txtStatus.text('Serie lista para registrar').css('color', '#0d6efd');
            msgDiv.css('border-left', '4px solid #0d6efd');
            btnGuardar.prop('disabled', false);
        } else {
            msgDiv.hide();
        }
    });

    // Envío asíncrono seguro por Fetch API hacia el procesador
    $('#formRegistroLote').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#btnGuardarLote');
        const txtBtn = $('#btnText');
        
        btn.prop('disabled', true);
        txtBtn.text('Guardando en Stock...');

        Swal.fire({
            title: 'Registrando maquinaria nueva...',
            text: 'Inyectando traza logística de importación en la base de datos.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch(this.action, { method: this.method, body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? '¡Ingreso Exitoso!' : 'Falla de Duplicidad',
                text: data.message,
                confirmButtonColor: data.success ? '#198754' : '#dc3545'
            }).then(() => {
                if (data.success) window.location.href = 'index.php';
                else btn.prop('disabled', false).text('Finalizar Entrada');
            });
        })
        .catch(error => {
            Swal.close();
            btn.prop('disabled', false).text('Finalizar Entrada');
            Swal.fire({ icon: 'error', title: 'Error de Comunicación', text: error.message });
        });
    });
});
</script>