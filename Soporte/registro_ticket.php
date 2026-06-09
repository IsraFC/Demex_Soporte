<?php
/**
 * ARCHIVO: registro_ticket.php
 * DESCRIPCIÓN: Panel de control unificado para la creación de folios de soporte técnico.
 * * CARACTERÍSTICAS TÉCNICAS:
 * 1. Integración Fase 1 (Técnica) y Fase 2 (Financiera) en tiempo real.
 * 2. Inteligencia de Modelos: Genera automáticamente series genéricas (S/N-XXX) si se elige un modelo manual.
 * 3. Validación de Garantía: Consulta vía AJAX si el equipo tiene cobertura vigente.
 * 4. Cálculos Automáticos: Sumatoria de costos y cálculo de días de servicio al vuelo.
 * * ACTUALIZACIÓN V1.8.7:
 * - Fix de Bloqueo Visual: Integración del método nativo .appendTo("body") emulando la lógica funcional de clientes.php.
 * - Saneo de Flujo: Interceptación basada en el evento submit nativo de Bootstrap.
 * - Unificación Semántica: Ajuste de minúsculas en consultas según esquema físico real de la BD.
 * * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 */
require_once '../config/db.php';
$page_title = "Registrar Ticket - Soporte";

// 1. VALIDACIÓN DE SEGURIDAD
$id_cliente = $_GET['id_cliente'] ?? null;
if (!$id_cliente) { header("Location: clientes.php"); exit(); }

// 2. RECUPERACIÓN DE DATOS DEL CLIENTE
$stmt = $pdo->prepare("SELECT nombre_cliente FROM clientes WHERE id_cliente = ?");
$stmt->execute([$id_cliente]);
$cliente = $stmt->fetch();

// 3. CARGA DE EQUIPOS DEL CLIENTE
$stmt_eq = $pdo->prepare("SELECT no_serie, modelo FROM equipos_garantia WHERE id_cliente = ?");
$stmt_eq->execute([$id_cliente]);
$equipos_cliente = $stmt_eq->fetchAll(PDO::FETCH_ASSOC);

// 4. CATÁLOGO DE MODELOS
$stmt_mod = $pdo->query("SELECT DISTINCT modelo FROM equipos_garantia ORDER BY modelo ASC");
$todos_modelos = $stmt_mod->fetchAll(PDO::FETCH_COLUMN);

$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="fw-bold text-danger mb-0 text-uppercase">Nuevo Ticket de Soporte</h1>
        <p class="text-muted">
            <i class="bi bi-person-badge me-1 text-danger"></i> 
            Cliente: <strong><?= htmlspecialchars($cliente['nombre_cliente']) ?></strong>
        </p>
    </div>
</div>

<form action="actions/procesar_ticket.php" method="POST" id="formTicket">
    <input type="hidden" name="id_cliente" value="<?= $id_cliente ?>">
    <input type="hidden" name="estatus" value="Abierto">
    <input type="hidden" name="garantia_valida" id="garantia_valida_input" value="Pendiente">
    <input type="hidden" name="fecha_compra_nueva" id="fecha_compra_nueva" value="">
    <input type="hidden" name="vigencia_nueva" id="vigencia_nueva_input" value="1">

    <div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-danger mb-4">
        <div class="row g-4">
            
            <div class="col-md-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-cpu-fill me-2 text-danger"></i>Equipo</h5>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">No. de Serie (Buscador)</label>
                    <input list="series_cliente" name="no_serie" id="no_serie_input" class="form-control border-0 bg-light shadow-sm" placeholder="Escriba o seleccione serie..." autocomplete="off">
                    <datalist id="series_cliente">
                        <?php foreach($equipos_cliente as $eq): ?>
                            <option value="<?= htmlspecialchars($eq['no_serie']) ?>" data-model="<?= htmlspecialchars($eq['modelo']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    
                    <div id="status_garantia" class="mt-2 p-2 rounded small fw-bold" style="display:none; background-color: #f8f9fa; border-left: 4px solid #dee2e6;">
                        <span id="txt_status_garantia">Validando...</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Modelo</label>
                    <select name="modelo" id="modelo_select" class="form-select border-0 bg-light shadow-sm">
                        <option value="">-- Seleccionar Modelo --</option>
                        <?php foreach($todos_modelos as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-md-4 border-start border-end">
                <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-danger"></i>Detalles</h5>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Tipo de Llamada</label>
                    <select name="tipo_llamada" class="form-select border-0 bg-light shadow-sm">
                        <option value="Soporte">Soporte</option>
                        <option value="Venta Refacciones">Venta Refacciones</option>
                        <option value="Información">Información</option>
                        <option value="Capacitaciones">Capacitaciones</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Tipo de Falla</label>
                    <select name="tipo_falla" class="form-select border-0 bg-light shadow-sm">
                        <option value="Mecánica">Mecánica</option>
                        <option value="Refrigeración">Refrigeración</option>
                        <option value="Electrónica">Electrónica</option>
                        <option value="Regulador">Regulador</option>
                        <option value="Materia prima">Materia prima</option>
                        <option value="Otra">Otra</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted d-block">¿Máquina Funcionando?</label>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="maquina_func" id="si" value="1" checked>
                        <label class="form-check-label" for="si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="maquina_func" id="no" value="0">
                        <label class="form-check-label" for="no">No</label>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-journal-text me-2 text-danger"></i>Observaciones</h5>
                <textarea name="observaciones" class="form-control border-0 bg-light shadow-sm mb-3" rows="3" placeholder="Notas iniciales..."></textarea>
                <div class="row g-2">
                    <div class="col-4">
                        <label class="form-label small fw-bold text-muted">Llamadas</label>
                        <input type="number" name="no_llamadas" min="1" class="form-control border-0 bg-light shadow-sm text-center" value="1">
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-bold text-muted">Acción Principal</label>
                        <select name="accion" id="accion_select" class="form-select border-0 bg-light shadow-sm fw-bold text-dark" required>
                            <option value="Ninguna">Ninguna (Solo registrar)</option>
                            <option value="Información">Información / Consulta</option>
                            <option value="Envio técnico">Envío técnico</option>
                            <option value="Envio refacciones">Envío refacciones</option>
                            <option value="Envio técnico y refacciones">Envío técnico y refacciones</option>
                            <option value="Envio base">Envío base</option>
                            <option value="Reparación en taller">Reparación en taller</option>
                            <option value="Cambio de maquina">Cambio de máquina</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="seccion_costos" class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-dark mb-4" style="display:none;">
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <h5 class="fw-bold mb-2 text-danger border-bottom pb-2"><i class="bi bi-calendar-event me-2"></i>Logística y Costos</h5>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Fecha de Inicio</label>
                <input type="date" name="fecha_inicio_acc" id="fecha_inicio" class="form-control bg-light border-0 shadow-sm">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Fecha de Finalización</label>
                <input type="date" name="fecha_fin_acc" id="fecha_fin" class="form-control bg-light border-0 shadow-sm" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Días de Acción</label>
                <input type="number" name="tiempo_accion" id="tiempo_accion" class="form-control bg-light border-0 shadow-sm text-center fw-bold" readonly placeholder="---">
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Refac. (Venta)</label>
                <input type="number" step="0.01" min="0" name="costo_refac_venta" class="form-control costo-input border-0 bg-light shadow-sm" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-success">Refac. (Gar)</label>
                <input type="number" step="0.01" min="0" name="costo_refac_garantia" class="form-control costo-input border-success bg-light shadow-sm" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Base</label>
                <input type="number" step="0.01" min="0" name="costo_base" class="form-control costo-input border-0 bg-light shadow-sm" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Técnico</label>
                <input type="number" step="0.01" min="0" name="costo_tecnico" class="form-control costo-input border-0 bg-light shadow-sm" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Envío</label>
                <input type="number" step="0.01" min="0" name="costo_envio" class="form-control costo-input border-0 bg-light shadow-sm" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Cotización</label>
                <input type="text" name="no_cotizacion" class="form-control bg-light border-0 shadow-sm" placeholder="Folio...">
            </div>

            <div class="col-md-6 d-flex gap-4 align-items-center mt-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="estatus_pago" id="pago_switch" value="Pagado">
                    <label class="form-check-label fw-bold" for="pago_switch" id="label_pago">Estatus: <span class="text-danger">Pendiente</span></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="requiere_factura" id="factura" value="1">
                    <label class="form-check-label fw-bold text-muted" for="factura">Requiere Factura</label>
                </div>
            </div>

            <div class="col-md-6 text-end align-self-end">
                <div class="h3 fw-bold text-dark">Total: <span class="text-danger">$</span><span id="label_total">0.00</span></div>
                <input type="hidden" name="costo_total" id="input_total" value="0.00">
            </div>
        </div>
    </div>

    <div class="text-center mt-4 d-flex justify-content-center gap-3">
        <a href="clientes.php" class="btn btn-light border px-5 rounded-pill fw-bold text-dark">Cancelar <i class="bi bi-x-lg ms-1"></i></a>
        <button type="submit" class="btn btn-success px-5 rounded-pill fw-bold shadow btn-guardar">
            <span id="btnText">Finalizar Registro</span> <i class="bi bi-check-circle ms-1"></i>
        </button>
    </div>
</form>

<div class="modal fade" id="modalSerieNueva" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-warning text-dark border-0 py-3 shadow-sm">
                <h5 class="modal-title fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>¿Equipo Nuevo Detectado?</h5>
                <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-center mb-3 text-muted">La serie <strong id="txtSerieNueva" class="text-danger"></strong> no existe en el catálogo.</p>

                <div id="error_modelo_modal" class="alert alert-danger shadow-sm py-2 mb-3" style="display:none; border-radius: 12px;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i> 
                        <small class="fw-bold">Atención: Debes seleccionar un MODELO en el formulario para poder registrar el equipo.</small>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mb-2 border">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-muted text-uppercase">Fecha de Compra / Instalación</label>
                            <input type="date" id="modal_fecha_compra" class="form-control border-0 shadow-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Vigencia</label>
                            <div class="d-flex gap-2 pt-1">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="radio" name="modal_vigencia" id="mv1" value="1" checked>
                                    <label class="form-check-label small fw-bold text-dark" for="mv1">1 Año</label>
                                </div>
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="radio" name="modal_vigencia" id="mv2" value="2">
                                    <label class="form-check-label small" for="mv2">2 Años</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 justify-content-center bg-white" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted" data-bs-dismiss="modal">Corregir Serie</button>
                <button type="button" id="btnConfirmarRegistro" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">Sí, Registrar Equipo</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let serieExiste = false;

    // 1. CARGA INICIAL DESDE URL
    const urlParams = new URLSearchParams(window.location.search);
    const serieURL = urlParams.get('no_serie');
    const modeloURL = urlParams.get('modelo');
    if (serieURL) {
        $('#no_serie_input').val(serieURL);
        if (modeloURL) $('#modelo_select').val(modeloURL);
        setTimeout(function() { $('#no_serie_input').trigger('input'); }, 100);
    }

    // 2. GENERADOR DE SERIES GENÉRICAS
    $('#modelo_select').on('change', function() {
        const modelo = $(this).val();
        const serieInput = $('#no_serie_input');
        if (serieInput.val().trim() === "" && modelo !== "") {
            const sufijo = modelo.replace('DEMEX ', '').replace('SPICE ', '').replace(' ', '');
            serieInput.val("S/N-" + sufijo);
            serieInput.trigger('input'); 
        }
    });

    // 3. COMPORTAMIENTO UX EN FORMULARIOS
    $(document).on('keydown', 'input[type="number"]', function(e) {
        if (['e', 'E', '+', '-'].includes(e.key)) e.preventDefault();
    });
    $(document).on('focus', 'input, textarea', function() { $(this).select(); });

    // 4. CONTROL DE VISIBILIDAD DE SECCIÓN COSTOS
    function toggleSeccionCostos() {
        const accion = $('#accion_select').val();
        if (['Ninguna', 'Información'].includes(accion)) $('#seccion_costos').slideUp();
        else $('#seccion_costos').slideDown();
    }
    $('#accion_select').on('change', toggleSeccionCostos);

    // 5. EVALUADOR ASÍNCRONO DE GARANTÍA (AJAX) + SELECCIÓN AUTOMÁTICA DE MODELO
    var typingTimer;
    $('#no_serie_input').on('input', function() {
        clearTimeout(typingTimer);
        var val = $(this).val().trim();
        var msgDiv = $('#status_garantia');
        var txtStatus = $('#txt_status_garantia');

        // 🎯 LÓGICA DE AUTO-SELECCIÓN DE MODELO:
        // Buscamos si el valor ingresado coincide exactamente con una de las opciones del datalist
        var opcionSeleccionada = $('#series_cliente option').filter(function() {
            return $(this).val() === val;
        });

        if (opcionSeleccionada.length > 0) {
            // Extraemos el modelo mapeado en el data-model
            var modeloAsociado = opcionSeleccionada.data('model');
            if (modeloAsociado) {
                // Seteamos el valor en tu select y disparamos el trigger change por si tienes otra lógica acoplada
                $('#modelo_select').val(modeloAsociado).trigger('change');
            }
        }

        // Continúa tu validación normal de fechas por AJAX...
        if (val.length > 2) {
            msgDiv.show();
            txtStatus.text('🔍 Validando...').css('color', '#6c757d');
            typingTimer = setTimeout(function() {
                $.ajax({
                    url: 'actions/validar_garantia_fechas.php',
                    method: 'POST',
                    data: { no_serie: val },
                    dataType: 'json',
                    success: function(res) {
                        serieExiste = (res.resultado !== 'Pendiente');
                        txtStatus.text('Garantía: ' + res.resultado);
                        $('#garantia_valida_input').val(res.resultado);
                        let color = (res.resultado === 'Válida') ? '#198754' : '#dc3545';
                        txtStatus.css('color', color);
                        msgDiv.css('border-left', '4px solid ' + color);
                    }
                });
            }, 600);
        } else { msgDiv.hide(); serieExiste = false; }
    });

    // 6. INTERCEPTAR SUBMIT NATIVO USANDO TU TRUCO INFALIBLE CORREGIDO
    $('#formTicket').on('submit', function(e) {
        const serie = $('#no_serie_input').val().trim();
        const modelo = $('#modelo_select').val();

        if (!serieExiste && serie !== "" && !serie.startsWith("S/N-")) {
            e.preventDefault(); // Congelamos el envío
            $('#txtSerieNueva').text(serie);
            
            if (modelo === "") {
                $('#error_modelo_modal').show();
                $('#btnConfirmarRegistro').prop('disabled', true).addClass('opacity-50');
            } else {
                $('#error_modelo_modal').hide();
                $('#btnConfirmarRegistro').prop('disabled', false).removeClass('opacity-50');
            }
            
            // 🎯 AQUÍ APLICAMOS TU MAGIA: Desplazamos el modal a la raíz para romper el page-fade-wrapper
            $('#modalSerieNueva').appendTo("body").modal('show');
        }
    });

    // Escucha cambios en caliente del modelo
    $('#modelo_select').on('change', function() {
        if ($(this).val() !== "") {
            $('#error_modelo_modal').fadeOut();
            $('#btnConfirmarRegistro').prop('disabled', false).removeClass('opacity-50');
        } else {
            $('#error_modelo_modal').fadeIn();
            $('#btnConfirmarRegistro').prop('disabled', true).addClass('opacity-50');
        }
    });

    // Procesamiento definitivo desde el modal blanco relocalizado
    $('#btnConfirmarRegistro').on('click', function() {
        if ($('#modelo_select').val() === "") {
            return false; 
        }
        
        $('#fecha_compra_nueva').val($('#modal_fecha_compra').val());
        const valorVigencia = $('input[name="modal_vigencia"]:checked').val();
        $('#vigencia_nueva_input').val(valorVigencia);

        // Saltamos la bandera y hacemos submit
        serieExiste = true; 
        $('#formTicket').submit();
    });

    // 7. SUMATORIA DE COSTOS & INTERRUPTOR DE PAGO AUTOMÁTICO
    $('.costo-input').on('input', function() {
        let total = 0;
        $('.costo-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#label_total').text(total.toFixed(2));
        $('#input_total').val(total.toFixed(2));

        if (total === 0) {
            $('#pago_switch').prop('checked', false).prop('disabled', true);
            $('#label_pago').html('Estatus: <span class="text-muted">N/A</span>');
        } else {
            $('#pago_switch').prop('disabled', false);
            const statusTxt = $('#pago_switch').is(':checked') ? '<span class="text-success">Pagado</span>' : '<span class="text-danger">Pendiente</span>';
            $('#label_pago').html('Estatus: ' + statusTxt);
        }
    });

    // 8. CÓMPUTO DINÁMICO DE DÍAS LOGÍSTICOS
    $('#fecha_inicio, #fecha_fin').on('change', function() {
        const inicio = $('#fecha_inicio').val();
        const fin = $('#fecha_fin').val();
        if (inicio) {
            $('#fecha_fin').prop('disabled', false).attr('min', inicio);
            if (fin) {
                const diff = new Date(fin) - new Date(inicio);
                $('#tiempo_accion').val(Math.floor(diff / (1000 * 60 * 60 * 24)));
            }
        }
    });

    // 9. EVENTO MANUAL DEL SWITCH DE COBRO
    $('#pago_switch').on('change', function() {
        const txt = $(this).is(':checked') ? '<span class="text-success">Pagado</span>' : '<span class="text-danger">Pendiente</span>';
        $('#label_pago').html('Estatus: ' + txt);
    });
});
</script>

<?php include '../includes/footer.php'; ?>