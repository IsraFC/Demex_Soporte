<?php
/**
 * ARCHIVO: Almacen/registro_lote.php
 * DESCRIPCIÓN: Interfaz para el registro individual de maquinaria en Almacén.
 * Realiza la búsqueda y validación asíncrona del equipo y cliente para confirmación visual.
 * @author Israel Fernández Carrera
 * @project Almacén Técnico DEMEX
 * @version 3.0 - Validación Cruzada en Tiempo Real (AJAX)
 */
require_once '../config/db.php';
$page_title = "Registrar Entrada de Equipo";

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="fw-bold text-danger mb-0 text-uppercase">Registrar Ingreso de Equipo</h1>
        <p class="text-muted">Introduce el número de serie para validar y confirmar los datos del cliente y garantía.</p>
    </div>
</div>

<form action="actions/procesar_lote.php" method="POST" id="formRegistroLote">
    <div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger mb-4">
        <div class="row g-4">
            
            <div class="col-md-5">
                <h5 class="fw-bold mb-3"><i class="bi bi-box-seam-fill me-2 text-danger"></i>Datos de Entrada</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Número de Serie</label>
                    <input type="text" name="no_serie" id="no_serie_input" class="form-control border-0 bg-light shadow-sm fw-bold text-uppercase text-danger" placeholder="Escriba o escanee la serie..." required autocomplete="off">
                    
                    <div id="status_busqueda" class="mt-2 p-2 rounded small fw-bold" style="display:none; background-color: #f8f9fa; border-left: 4px solid #dee2e6;">
                        <span id="txt_status_busqueda">Esperando serie...</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Identificador del Contenedor / Lote</label>
                    <input type="text" name="contenedor" id="contenedor" class="form-control border-0 bg-light shadow-sm fw-bold text-uppercase" placeholder="Ej. 35DM25" required autocomplete="off">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Tipo de Stock</label>
                    <select name="tipo" id="tipo" class="form-select border-0 bg-light shadow-sm fw-bold text-dark" required>
                        <option value="ORIGINAL" selected>ORIGINAL</option>
                        <option value="DEMO">DEMO</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control border-0 bg-light shadow-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="col-md-7 border-start">
                <h5 class="fw-bold mb-3"><i class="bi bi-person-check-fill me-2 text-danger"></i>Datos de Confirmación (Garantía y Cliente)</h5>
                
                <div class="alert alert-light border-0 small text-secondary mb-3 p-3 d-flex align-items-start gap-2" style="border-radius: 12px; background-color: #f8f9fa;">
                    <i class="bi bi-info-circle-fill text-danger mt-0.5 fs-6"></i>
                    <span>Los campos inferiores se llenarán de forma automática al detectar una serie válida en el sistema. Verifícalos antes de guardar.</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Modelo de la Máquina</label>
                        <input type="text" id="confirm_modelo" class="form-control border-0 bg-light shadow-sm fw-bold text-dark text-uppercase" placeholder="---" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Vigencia Garantía</label>
                        <input type="text" id="confirm_garantia" class="form-control border-0 bg-light shadow-sm fw-bold text-dark" placeholder="---" readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Cliente Asignado</label>
                        <input type="text" id="confirm_cliente" class="form-control border-0 bg-light shadow-sm fw-bold text-dark" placeholder="---" readonly>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Teléfono de Contacto</label>
                        <input type="text" id="confirm_telefono" class="form-control border-0 bg-light shadow-sm text-dark" placeholder="---" readonly>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted">Ubicación / Dirección</label>
                        <textarea id="confirm_ubicacion" class="form-control border-0 bg-light shadow-sm text-dark small" rows="1" style="resize:none;" placeholder="---" readonly></textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="text-center mt-4 d-flex justify-content-center gap-3">
        <a href="index.php" class="btn btn-light border px-5 rounded-pill fw-bold text-dark">Cancelar</a>
        <button type="submit" id="btnGuardarLote" class="btn btn-success px-5 rounded-pill fw-bold shadow" disabled>
            <span id="btnText">Finalizar Entrada</span> <i class="bi bi-check-circle ms-1"></i>
        </button>
    </div>
</form>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    let serieValida = false;
    var typingTimer;

    // Evaluador asíncrono para buscar el equipo en caliente conforme escriben
    $('#no_serie_input').on('input', function() {
        clearTimeout(typingTimer);
        let val = $(this).val().trim();
        let msgDiv = $('#status_busqueda');
        let txtStatus = $('#txt_status_busqueda');
        let btnGuardar = $('#btnGuardarLote');

        // Limpiar campos de confirmación por defecto
        $('#confirm_modelo, #confirm_garantia, #confirm_cliente, #confirm_telefono, #confirm_ubicacion').val('');
        btnGuardar.prop('disabled', true);
        serieValida = false;

        if (val.length > 2) {
            msgDiv.show();
            txtStatus.text('🔍 Buscando serie...').css('color', '#6c757d');
            msgDiv.css('border-left', '4px solid #dee2e6');

            typingTimer = setTimeout(function() {
                $.ajax({
                    url: 'actions/buscar_equipo_garantia.php',
                    method: 'POST',
                    data: { no_serie: val },
                    dataType: 'json',
                    success: function(res) {
                        if (res.exists) {
                            txtStatus.text('✅ Equipo Localizado').css('color', '#198754');
                            msgDiv.css('border-left', '4px solid #198754');
                            
                            // Poblar los inputs de solo lectura
                            $('#confirm_modelo').val(res.modelo);
                            $('#confirm_garantia').val(res.garantia_status);
                            $('#confirm_cliente').val(res.nombre_cliente);
                            $('#confirm_telefono').val(res.telefono ? res.telefono : 'N/A');
                            $('#confirm_ubicacion').val(res.ubicacion ? res.ubicacion : 'N/A');
                            
                            btnGuardar.prop('disabled', false);
                            serieValida = true;
                        } else {
                            txtStatus.text('❌ Error: Esta serie no existe en Equipos Garantía').css('color', '#dc3545');
                            msgDiv.css('border-left', '4px solid #dc3545');
                        }
                    }
                });
            }, 500);
        } else {
            msgDiv.hide();
        }
    });

    // Envío seguro por Fetch API
    $('#formRegistroLote').on('submit', function(e) {
        e.preventDefault();
        if (!serieValida) return false;

        const btn = $('#btnGuardarLote');
        const txtBtn = $('#btnText');
        
        btn.prop('disabled', true);
        txtBtn.text('Procesando...');

        Swal.fire({
            title: 'Registrando entrada...',
            text: 'Guardando traza logística en la base de datos.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch(this.action, { method: this.method, body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? '¡Hecho!' : 'Falla',
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
            Swal.fire({ icon: 'error', title: 'Error de Red', text: error.message });
        });
    });
});
</script>