<?php
/**
 * ARCHIVO: Soporte/registro_maquina.php
 * DESCRIPCIÓN: Formulario de alta para equipos con modal de cliente integrado y switch de garantía vencida.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.7.2 - Formato de Garantía Vencida 01/01/2000
 */

require_once '../config/db.php';
$page_title = "Registrar Nueva Máquina - Soporte";
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
        <p class="text-muted small">Complete la información técnica para activar o registrar el equipo en el sistema.</p>
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
                    placeholder="Número de serie..." required maxlength="25"
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
                    $clientes = $pdo->query("SELECT nombre_cliente FROM clientes ORDER BY nombre_cliente ASC");
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

        <div class="card bg-light border-0 rounded-4 p-4 my-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <span class="fw-bold text-secondary small text-uppercase">
                    <i class="bi bi-shield-check me-1"></i> Configuración de Póliza de Garantía
                </span>
                
                <div class="form-check form-switch bg-white py-2 pe-3 ps-5 rounded-pill border shadow-sm mb-0">
                    <input class="form-check-input ms-n4" type="checkbox" role="switch" id="switch_vencida" name="garantia_vencida" value="1" style="cursor: pointer;">
                    <label class="form-check-label fw-bold small text-danger ms-2" for="switch_vencida" style="cursor: pointer;">
                        ¿Equipo Antiguo / Garantía Vencida?
                    </label>
                </div>
            </div>

            <div id="wrapper_garantia_normal" class="row justify-content-center g-4 pt-2">
                <div class="col-md-4 text-center">
                    <label class="form-label fw-bold small text-muted required-alt">
                        <i class="bi bi-calendar-check me-1"></i> Inicio de Garantía
                    </label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control border-0 bg-white shadow-sm text-center" 
                           value="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-4 text-center">
                    <label class="form-label fw-bold small text-muted required-alt">
                        <i class="bi bi-hourglass-split me-1"></i> Tiempo de Vigencia
                    </label>
                    <div class="d-flex justify-content-center gap-4 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="vigencia" id="v1" value="1" checked>
                            <label class="form-check-label small fw-bold" for="v1">1 Año</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="vigencia" id="v2" value="2">
                            <label class="form-check-label small fw-bold" for="v2">2 Años</label>
                        </div>
                    </div>
                </div>
            </div>

            <div id="aviso_vencida" class="alert alert-warning border-0 rounded-3 text-center mb-0 mt-2 p-2 shadow-sm" style="display: none;">
                <i class="bi bi-exclamation-triangle-fill me-2 text-warning fs-5"></i>
                <span class="small fw-bold text-dark">
                    La garantía se registrará como <strong>VENCIDA AUTOMÁTICAMENTE (01/01/0000)</strong>.
                </span>
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
    $('#modalNuevoCliente').on('show.bs.modal', function () {
        $(this).appendTo("body");
    });

    // DINÁMICA DEL SWITCH DE GARANTÍA VENCIDA
    $('#switch_vencida').on('change', function() {
        if ($(this).is(':checked')) {
            $('#wrapper_garantia_normal').slideUp(200);
            $('#aviso_vencida').slideDown(200);
            $('#fecha_inicio').prop('required', false);
        } else {
            $('#wrapper_garantia_normal').slideDown(200);
            $('#aviso_vencida').slideUp(200);
            $('#fecha_inicio').prop('required', true);
        }
    });

    // 1. VALIDACIÓN DE SERIE
    var typingTimer;
    $('#no_serie').on('input', function() {
        clearTimeout(typingTimer);
        var serie = $(this).val().trim();
        var input = $(this);
        var msg = $('#status_serie');

        if (serie.length >= 3) {
            typingTimer = setTimeout(function() {
                $.ajax({
                    url: 'actions/verificar_serie.php',
                    method: 'POST',
                    data: { no_serie: serie },
                    success: function(response) {
                        if (response.trim() === 'existe') {
                            input.removeClass('is-valid').addClass('is-invalid').css('border', '2px solid #dc3545');
                            msg.text('⚠️ Existe').css('color', '#dc3545').show();
                            $('#btnGuardar').attr('disabled', true);
                        } else {
                            input.removeClass('is-invalid').addClass('is-valid').css('border', '2px solid #198754');
                            msg.text('✅ OK').css('color', '#198754').show();
                            $('#btnGuardar').attr('disabled', false);
                        }
                    }
                });
            }, 500);
        } else {
            input.removeClass('is-invalid is-valid').css('border', 'none');
            msg.hide();
            $('#btnGuardar').attr('disabled', false);
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

    // 3. GUARDAR CLIENTE DESDE MODAL (AJAX)
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
                es_ajax: true
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

    // 4. ENVIAR FORMULARIO PRINCIPAL
    $('#formRegistroMaquina').on('submit', function(e) {
        e.preventDefault();

        const btnGuardar = $('#btnGuardar');
        const textoOriginal = btnGuardar.html();
        
        btnGuardar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');

        const formulario = this;
        const datosFormulario = new FormData(formulario);

        fetch(formulario.action, {
            method: formulario.method,
            body: datosFormulario
        })
        .then(respuesta => {
            if (!respuesta.ok) throw new Error('Error en la red con el servidor.');
            return respuesta.json();
        })
        .then(data => {
            Swal.fire({
                icon: data.status,
                title: data.title,
                text: data.text,
                confirmButtonColor: data.status === 'success' ? '#198754' : '#C62828'
            }).then(() => {
                if (data.status === 'success') {
                    window.location.href = 'maquinas.php';
                } else {
                    btnGuardar.prop('disabled', false).html(textoOriginal);
                }
            });
        })
        .catch(error => {
            btnGuardar.prop('disabled', false).html(textoOriginal);
            Swal.fire({
                icon: 'error',
                title: 'Falla Operativa',
                text: error.message,
                confirmButtonColor: '#C62828'
            });
        });
    });

    if ($('#no_serie').val().length >= 3) {
        $('#no_serie').trigger('input');
    }
});
</script>

<?php include '../includes/footer.php'; ?>