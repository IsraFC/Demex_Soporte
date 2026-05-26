<?php
/**
 * ARCHIVO: registro_maquina.php
 * DESCRIPCIÓN: Formulario de alta para equipos con modal de cliente integrado.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.6
 */

require_once '../config/db.php';
$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<style>
    .required-alt::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
</style>

<div class="row mb-4">
    <div class="col-12 text-center">
        <h1 class="fw-bold text-danger mb-0">Registrar Nueva Máquina</h1>
        <p class="text-muted small">Complete la información técnica para activar la garantía en el sistema.</p>
    </div>
</div>

<div class="card-main shadow-lg p-5 bg-white rounded border-top border-4 border-danger">
    <form action="actions/procesar_maquina.php" method="POST" id="formRegistroMaquina">
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-upc-scan me-1"></i> Número de Serie
                </label>
                <input type="text" name="no_serie" id="no_serie" class="form-control border-0 bg-light shadow-sm" 
                    placeholder="Número de serie..." required maxlength="15"
                    value="<?= htmlspecialchars($_GET['no_serie'] ?? '') ?>">
                <div id="status_serie" class="small mt-1 fw-bold" style="display:none;"></div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-person-circle me-1"></i> Cliente
                </label>
                <div class="input-group">
                    <input list="listaClientes" name="nombre_cliente" id="input_cliente" class="form-control border-0 bg-light shadow-sm" 
                           placeholder="Buscar cliente..." required>
                    <button class="btn btn-danger shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente" title="Agregar nuevo cliente">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <datalist id="listaClientes">
                    <?php
                    $clientes = $pdo->query("SELECT nombre_cliente FROM Clientes ORDER BY nombre_cliente ASC");
                    while ($c = $clientes->fetch()) {
                        echo "<option value='".htmlspecialchars($c['nombre_cliente'])."'>";
                    }
                    ?>
                </datalist>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-gear-wide-connected me-1"></i> Modelo
                </label>
                <select name="modelo" class="form-select border-0 bg-light shadow-sm" required>
                    <option value="">Seleccionar Modelo...</option>
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

        <div class="row justify-content-center g-4 pt-4">
            <div class="col-md-4 text-center">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-calendar-check me-1"></i> Inicio de Garantía
                </label>
                <input type="date" name="fecha_inicio" class="form-control border-0 bg-light shadow-sm text-center" 
                       value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-4 text-center">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-hourglass-split me-1"></i> Tiempo de Vigencia
                </label>
                <div class="d-flex justify-content-center gap-4 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="vigencia" id="v1" value="1" checked>
                        <label class="form-check-label small" for="v1">1 Año</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="vigencia" id="v2" value="2">
                        <label class="form-check-label small" for="v2">2 Años</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center d-flex justify-content-center gap-3">
                <a href="maquinas.php" class="btn btn-light border px-5 rounded-pill fw-bold text-muted">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </a>
                <button type="submit" id="btnGuardar" class="btn btn-danger px-5 rounded-pill fw-bold shadow">
                    <i class="bi bi-check-circle me-1"></i> Guardar Equipo
                </button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Alta Rápida de Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formModalCliente">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Nombre del Cliente / Empresa</label>
                        <input type="text" id="m_nombre_cliente" class="form-control border-0 bg-light shadow-sm" placeholder="Nombre completo..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Teléfono</label>
                        <input type="tel" id="m_telefono" class="form-control border-0 bg-light shadow-sm" placeholder="222 123 4567" maxlength="12">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Ubicación</label>
                        <textarea id="m_ubicacion" class="form-control border-0 bg-light shadow-sm" rows="2" placeholder="Dirección..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="btnGuardarClienteModal" class="btn btn-danger rounded-pill px-4 fw-bold shadow">
                    <i class="bi bi-person-check me-1"></i> Guardar Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. VALIDACIÓN DE SERIE
    var typingTimer;
    $('#no_serie').on('input', function() {
        clearTimeout(typingTimer);
        var serie = $(this).val();
        var input = $(this);
        var msg = $('#status_serie');
        input.css('border', 'none'); msg.hide();

        if (serie.length >= 3) {
            typingTimer = setTimeout(function() {
                $.ajax({
                    url: 'actions/verificar_serie.php',
                    method: 'POST',
                    data: { no_serie: serie },
                    success: function(response) {
                        if (response === 'existe') {
                            input.addClass('is-invalid').css('border', '2px solid #dc3545');
                            msg.text('⚠️ Existe').css('color', '#dc3545').show();
                            $('#btnGuardar').attr('disabled', true);
                        } else {
                            input.addClass('is-valid').css('border', '2px solid #198754');
                            msg.text('✅ OK').css('color', '#198754').show();
                            $('#btnGuardar').attr('disabled', false);
                        }
                    }
                });
            }, 500);
        }
    });

    // 2. MÁSCARA TELÉFONO MODAL
    $('#m_telefono').on('input', function() {
        var val = $(this).val().replace(/\D/g, '');
        var res = '';
        if (val.length > 0) {
            res = val.substring(0, 3);
            if (val.length > 3) res += ' ' + val.substring(3, 6);
            if (val.length > 6) res += ' ' + val.substring(6, 10);
        }
        $(this).val(res);
    });

    // 3. GUARDAR CLIENTE (AJAX)
    $('#btnGuardarClienteModal').on('click', function() {
        const nombre = $('#m_nombre_cliente').val();
        if (nombre.length < 4) { Swal.fire('Atención', 'Nombre muy corto', 'warning'); return; }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'actions/procesar_cliente.php',
            method: 'POST',
            data: {
                nombre_cliente: nombre,
                telefono: $('#m_telefono').val(),
                ubicacion: $('#m_ubicacion').val(),
                es_ajax: true // BANDERA PARA EL PHP
            },
            success: function(response) {
                if(response.trim() === "ok") {
                    $('#listaClientes').append($('<option>').val(nombre));
                    $('#input_cliente').val(nombre);
                    $('#modalNuevoCliente').modal('hide');
                    $('#formModalCliente')[0].reset();
                    Swal.fire('¡Éxito!', 'Cliente seleccionado', 'success');
                } else {
                    Swal.fire('Error', 'No se pudo guardar', 'error');
                }
            },
            error: function() { Swal.fire('Error', 'Fallo de conexión', 'error'); },
            complete: function() { btn.prop('disabled', false).html('<i class="bi bi-person-check me-1"></i> Guardar Cliente'); }
        });
    });

    // Si el campo ya viene con texto, dispara la validación de una vez
    if ($('#no_serie').val().length >= 3) {
        $('#no_serie').trigger('input');
    }
});
</script>

<?php include '../includes/footer.php'; ?>