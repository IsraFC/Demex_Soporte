<?php
/**
 * ARCHIVO: editar_ticket.php
 * DESCRIPCIÓN: Interfaz de edición unificada. Permite modificar datos técnicos y financieros.
 * Utiliza bordes primarios (azules) para diferenciar visualmente el modo edición del registro.
 * * * MEJORAS V1.6:
 * - Inteligencia de Series: Genera S/N-XXX automáticamente si se elige modelo en tickets sin serie.
 * - Sincronización AJAX: Valida garantías y modelos al vuelo durante la edición.
 * * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.6
 */
require_once '../config/db.php';

// Validación de existencia del folio para la carga de datos
$id_ticket = $_GET['id_ticket'] ?? null;
if (!$id_ticket) { header("Location: index.php"); exit(); }

/**
 * 1. CONSULTA DE RECUPERACIÓN:
 * Extrae la información actual del ticket cruzando datos de soporte, costos y cliente.
 */
$sql = "SELECT t.*, d.*, c.nombre_cliente, e.modelo 
        FROM Tickets_Soporte t 
        LEFT JOIN Detalles_Costos_Tiempos d ON t.id_ticket = d.id_ticket 
        JOIN Clientes c ON t.id_cliente = c.id_cliente
        LEFT JOIN Equipos_Garantia e ON t.no_serie = e.no_serie
        WHERE t.id_ticket = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ticket]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) { header("Location: index.php"); exit(); }

/**
 * LÓGICA DE BLOQUEO:
 * Si el ticket ya tiene un número de serie real asignado, se bloquean los campos de equipo
 * para mantener la integridad del historial. Si está vacío o es genérico, permite la captura.
 */
$tieneSerie = !empty($ticket['no_serie']) && strpos($ticket['no_serie'], 'S/N-') === false;

// 2. Consulta de equipos del cliente para el buscador dinámico (Datalist)
$stmt_eq = $pdo->prepare("SELECT no_serie, modelo FROM Equipos_Garantia WHERE id_cliente = ?");
$stmt_eq->execute([$ticket['id_cliente']]);
$equipos_cliente = $stmt_eq->fetchAll(PDO::FETCH_ASSOC);

// 3. Catálogo de modelos disponibles para selección manual
$stmt_mod = $pdo->query("SELECT DISTINCT modelo FROM Equipos_Garantia ORDER BY modelo ASC");
$todos_modelos = $stmt_mod->fetchAll(PDO::FETCH_COLUMN);

$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="fw-bold text-danger mb-0 text-uppercase">Modificar Ticket de Soporte</h1>
        <p class="text-muted"><i class="bi bi-person-badge me-1 text-danger"></i> Cliente: <strong><?= htmlspecialchars($ticket['nombre_cliente']) ?></strong> | Folio: <strong>#<?= $id_ticket ?></strong></p>
    </div>
</div>

<form action="actions/procesar_ticket.php" method="POST" id="formEditar">
    <input type="hidden" name="id_ticket" value="<?= $id_ticket ?>">
    <input type="hidden" name="id_cliente" value="<?= $ticket['id_cliente'] ?>">
    <input type="hidden" name="garantia_valida" id="garantia_valida_input" value="<?= $ticket['garantia_valida'] ?>">
    
    <input type="hidden" name="fecha_compra_nueva" id="fecha_compra_nueva" value="">

    <div class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-primary mb-4">
        <div class="row g-4">
            
            <div class="col-md-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-cpu-fill me-2 text-danger"></i>Equipo</h5>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">No. de Serie (Buscador)</label>
                    <input list="series_cliente" name="no_serie" id="no_serie_input" 
                           class="form-control border-0 bg-light shadow-sm fw-bold <?= $tieneSerie ? 'text-secondary' : 'text-dark' ?>" 
                           value="<?= $ticket['no_serie'] ?>" 
                           <?= $tieneSerie ? 'readonly' : '' ?> 
                           placeholder="Escriba o seleccione serie...">
                    
                    <datalist id="series_cliente">
                        <?php foreach($equipos_cliente as $eq): ?>
                            <option value="<?= htmlspecialchars($eq['no_serie']) ?>" data-model="<?= htmlspecialchars($eq['modelo']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    
                    <div id="status_garantia" class="mt-2 p-2 rounded small fw-bold" style="background-color: #f8f9fa; border-left: 4px solid #dee2e6; <?= !empty($ticket['no_serie']) ? '' : 'display:none;' ?>">
                        <span id="txt_status_garantia">Garantía: <?= $ticket['garantia_valida'] ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Modelo</label>
                    <select name="modelo" id="modelo_select" class="form-select border-0 bg-light shadow-sm" <?= $tieneSerie ? 'disabled' : '' ?>>
                        <option value="">-- Seleccionar Modelo --</option>
                        <?php foreach($todos_modelos as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= ($ticket['modelo'] == $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-md-4 border-start border-end">
                <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-danger"></i>Detalles</h5>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Tipo de Llamada</label>
                    <select name="tipo_llamada" class="form-select border-0 bg-light shadow-sm">
                        <?php foreach(['Soporte','Venta Refacciones','Información','Capacitaciones'] as $v): ?>
                            <option value="<?= $v ?>" <?= $ticket['tipo_llamada'] == $v ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Tipo de Falla</label>
                    <select name="tipo_falla" class="form-select border-0 bg-light shadow-sm">
                        <?php foreach(['Mecánica','Refrigeración','Electrónica','Regulador','Materia prima','Otra'] as $v): ?>
                            <option value="<?= $v ?>" <?= $ticket['tipo_falla'] == $v ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted d-block">¿Máquina Funcionando?</label>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="maquina_func" id="si" value="1" <?= $ticket['maquina_func'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="maquina_func" id="no" value="0" <?= !$ticket['maquina_func'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="no">No</label>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-journal-text me-2 text-danger"></i>Observaciones</h5>
                <textarea name="observaciones" class="form-control border-0 bg-light shadow-sm mb-3" rows="3"><?= htmlspecialchars($ticket['observaciones']) ?></textarea>
                <div class="row g-2">
                    <div class="col-4">
                        <label class="form-label small fw-bold text-muted">Llamadas</label>
                        <input type="number" min="1" name="no_llamadas" class="form-control border-0 bg-light shadow-sm text-center" value="<?= $ticket['no_llamadas'] ?>">
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-bold text-muted">Acción Principal</label>
                        <select name="accion" id="accion_select" class="form-select border-0 bg-light shadow-sm fw-bold text-dark" required>
                            <?php foreach(['Ninguna','Información','Envio técnico','Envio refacciones','Envio técnico y refacciones','Envio base','Reparación en taller','Cambio de maquina'] as $acc): ?>
                                <option value="<?= $acc ?>" <?= $ticket['accion'] == $acc ? 'selected' : '' ?>><?= $acc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="seccion_costos" class="card-main shadow-lg p-4 bg-white rounded border-top border-4 border-secondary mb-4" style="display:none;">
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <h5 class="fw-bold mb-2 text-danger border-bottom pb-2"><i class="bi bi-calendar-event me-2"></i>Logística y Costos</h5>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Fecha de Inicio</label>
                <input type="date" name="fecha_inicio_acc" id="fecha_inicio" class="form-control bg-light border-0 shadow-sm" value="<?= $ticket['fecha_inicio_acc'] ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Fecha de Finalización</label>
                <input type="date" name="fecha_fin_acc" id="fecha_fin" class="form-control bg-light border-0 shadow-sm" value="<?= $ticket['fecha_fin_acc'] ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Días de Acción</label>
                <input type="number" name="tiempo_accion" id="tiempo_accion" class="form-control bg-light border-0 shadow-sm text-center fw-bold" readonly value="<?= $ticket['tiempo_accion'] ?>">
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Refac. (Venta)</label>
                <input type="number" step="0.01" min="0" name="costo_refac_venta" class="form-control costo-input border-0 bg-light shadow-sm" 
                       value="<?= (float)$ticket['costo_refac_venta'] ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-success">Refac. (Gar)</label>
                <input type="number" step="0.01" min="0" name="costo_refac_garantia" class="form-control costo-input border-success bg-light shadow-sm" 
                       value="<?= (float)$ticket['costo_refac_garantia'] ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Base</label>
                <input type="number" step="0.01" min="0" name="costo_base" class="form-control costo-input border-0 bg-light shadow-sm" 
                       value="<?= (float)$ticket['costo_base'] ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Técnico</label>
                <input type="number" step="0.01" min="0" name="costo_tecnico" class="form-control costo-input border-0 bg-light shadow-sm" 
                       value="<?= (float)$ticket['costo_tecnico'] ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Envío</label>
                <input type="number" step="0.01" min="0" name="costo_envio" class="form-control costo-input border-0 bg-light shadow-sm" 
                       value="<?= (float)$ticket['costo_envio'] ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Cotización</label>
                <input type="text" name="no_cotizacion" class="form-control bg-light border-0 shadow-sm" 
                       value="<?= htmlspecialchars($ticket['no_cotizacion'] ?? '') ?>" placeholder="Folio...">
            </div>

            <div class="col-md-6 d-flex gap-4 align-items-center mt-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="estatus_pago" id="pago_switch" value="Pagado" <?= ($ticket['estatus_pago'] == 'Pagado') ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="pago_switch" id="label_pago">
                        Estatus: <span class="<?= ($ticket['estatus_pago'] == 'Pagado') ? 'text-success' : 'text-danger' ?>"><?= $ticket['estatus_pago'] ?: 'Pendiente' ?></span>
                    </label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="requiere_factura" id="factura" value="1" <?= $ticket['requiere_factura'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold text-muted" for="factura">Requiere Factura</label>
                </div>
            </div>

            <div class="col-md-6 text-end align-self-end">
                <div class="h3 fw-bold text-dark">Total: <span class="text-danger">$</span><span id="label_total"><?= number_format($ticket['costo_total'], 2) ?></span></div>
                <input type="hidden" name="costo_total" id="input_total" value="<?= $ticket['costo_total'] ?>">
            </div>
        </div>
    </div>

    <div class="text-center mt-4 d-flex justify-content-center gap-3">
        <a href="index.php" class="btn btn-light border px-5 rounded-pill fw-bold text-dark">Cancelar <i class="bi bi-x-lg ms-1"></i></a>
        <button type="submit" class="btn btn-primary text-white px-5 rounded-pill fw-bold shadow">
            Guardar Cambios <i class="bi bi-cloud-arrow-up ms-1"></i>
        </button>
    </div>
</form>

<div class="modal fade" id="modalSerieNueva" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-warning text-dark border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>¿Nueva Serie Detectada?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-center mb-3">Has ingresado la serie <strong id="txtSerieNueva" class="text-danger"></strong>, que no está registrada.</p>
                <div class="bg-light p-3 rounded-3 mb-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Fecha de Compra / Instalación</label>
                    <input type="date" id="modal_fecha_compra" class="form-control border-0 shadow-sm" value="<?= date('Y-m-d') ?>">
                    <p class="text-muted small mt-2 mb-0 italic">* Se usará para calcular la garantía del equipo.</p>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 justify-content-center">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Corregir</button>
                <button type="button" id="btnConfirmarRegistro" class="btn btn-warning rounded-pill px-4 fw-bold">Sí, Registrar Equipo</button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * LÓGICA FRONTEND (jQuery):
 * Controla validaciones AJAX, cálculos automáticos de costos y visualización dinámica.
 */
$(document).ready(function() {
    
    let serieExiste = true; // Por defecto asumimos que existe al cargar edición

    /**
     * NUEVA LÓGICA: GENERACIÓN DE SERIE GENÉRICA AL EDITAR
     */
    $('#modelo_select').on('change', function() {
        const modelo = $(this).val();
        const serieInput = $('#no_serie_input');

        if (!serieInput.attr('readonly') && serieInput.val().trim() === "" && modelo !== "") {
            const sufijo = modelo.replace('DEMEX ', '').replace('SPICE ', '').replace(' ', '');
            serieInput.val("S/N-" + sufijo);
            serieInput.trigger('input'); 
        }
    });

    $(document).on('keydown', 'input[type="number"]', function(e) {
        if (['e', 'E', '+', '-'].includes(e.key)) e.preventDefault();
    });
    $(document).on('focus', 'input, textarea', function() { $(this).select(); });

    // VALIDACIÓN DE SERIE
    var typingTimer;
    $('#no_serie_input').on('input', function() {
        if ($(this).attr('readonly')) return; 
        
        clearTimeout(typingTimer);
        var val = $(this).val().trim();
        var msgDiv = $('#status_garantia');
        var txtStatus = $('#txt_status_garantia');
        var option = $('#series_cliente option').filter(function() { 
            return this.value.toUpperCase() === val.toUpperCase(); 
        });

        if (val.length > 2) {
            msgDiv.show();
            txtStatus.text('🔍 Validando...').css('color', '#6c757d');
            
            if (option.length) $('#modelo_select').val(option.data('model'));

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
                        let color = (res.resultado === 'Válida') ? '#198754' : (res.resultado === 'Pendiente' ? '#6c757d' : '#dc3545');
                        txtStatus.css('color', color);
                        msgDiv.css('border-left', '4px solid ' + color);
                    }
                });
            }, 600);
        } else { msgDiv.hide(); serieExiste = false; }
    });

    // SEGURIDAD: INTERCEPTAR SUBMIT
    $('#formEditar').on('submit', function(e) {
        const serie = $('#no_serie_input').val().trim();
        if (!$('#no_serie_input').attr('readonly') && !serieExiste && serie !== "" && !serie.startsWith("S/N-")) {
            e.preventDefault();
            $('#txtSerieNueva').text(serie);
            $('#modalSerieNueva').modal('show');
        }
    });

    $('#btnConfirmarRegistro').on('click', function() {
        $('#fecha_compra_nueva').val($('#modal_fecha_compra').val());
        serieExiste = true; 
        $('#formEditar').submit();
    });

    function toggleCostos() {
        if (['Ninguna', 'Información'].includes($('#accion_select').val())) $('#seccion_costos').slideUp();
        else $('#seccion_costos').slideDown();
    }
    $('#accion_select').on('change', toggleCostos);

    // SUMATORIA + LÓGICA PAGO N/A
    $('.costo-input').on('input', function() {
        let total = 0;
        $('.costo-input').each(function() { total += parseFloat($(this).val()) || 0; });
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

    // LÓGICA DE FECHAS Y TIEMPOS (CON RESET INCLUIDO)
    $('#fecha_inicio, #fecha_fin').on('change', function() {
        const inicio = $('#fecha_inicio').val();
        const fin = $('#fecha_fin').val();
        
        if (inicio) {
            $('#fecha_fin').prop('disabled', false).attr('min', inicio);
            if (fin) {
                const diff = new Date(fin) - new Date(inicio);
                const dias = Math.floor(diff / (1000 * 60 * 60 * 24));
                $('#tiempo_accion').val(dias >= 0 ? dias : 0);
            } else {
                $('#tiempo_accion').val(''); // Reset días si se borra la fecha fin
            }
        } else {
            // Reset total si se borra la fecha de inicio
            $('#fecha_fin').val('').prop('disabled', true);
            $('#tiempo_accion').val('');
        }
    });

    $('#pago_switch').on('change', function() {
        const txt = $(this).is(':checked') ? '<span class="text-success">Pagado</span>' : '<span class="text-danger">Pendiente</span>';
        $('#label_pago').html('Estatus: ' + txt);
    });

    // --- INICIALIZACIÓN ---
    toggleCostos();
    
    /**
     * IMPORTANTE: Disparamos el input manualmente pero con un pequeño delay
     * para asegurar que los valores cargados por PHP ya estén disponibles.
     */
    setTimeout(function() {
        $('.costo-input').first().trigger('input'); 
    }, 100);

    const initG = $('#garantia_valida_input').val();
    let c = (initG === 'Válida') ? '#198754' : (initG === 'Pendiente' ? '#6c757d' : '#dc3545');
    $('#txt_status_garantia').css('color', c);
    $('#status_garantia').css('border-left', '4px solid ' + c);
});
</script>

<?php include '../includes/footer.php'; ?>