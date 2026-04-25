<?php
/**
 * ARCHIVO: editar_ticket.php
 * DESCRIPCIÓN: Edición unificada. Bordes azules para diferenciar, títulos rojos para identidad.
 * @author Israel Fernández Carrera
 */
$pagina_actual = 'soporte';
require_once 'config/db.php';

$id_ticket = $_GET['id_ticket'] ?? null;
if (!$id_ticket) { header("Location: index.php"); exit(); }

// 1. Consulta completa
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

// Lógica de bloqueo: Si tiene serie, se bloquea. Si no, queda libre.
$tieneSerie = !empty($ticket['no_serie']);

// 2. Equipos del cliente para el Datalist
$stmt_eq = $pdo->prepare("SELECT no_serie, modelo FROM Equipos_Garantia WHERE id_cliente = ?");
$stmt_eq->execute([$ticket['id_cliente']]);
$equipos_cliente = $stmt_eq->fetchAll(PDO::FETCH_ASSOC);

// 3. Modelos para el select
$stmt_mod = $pdo->query("SELECT DISTINCT modelo FROM Equipos_Garantia ORDER BY modelo ASC");
$todos_modelos = $stmt_mod->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
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
                    
                    <div id="status_garantia" class="mt-2 p-2 rounded small fw-bold" style="background-color: #f8f9fa; border-left: 4px solid #dee2e6; <?= $tieneSerie ? '' : 'display:none;' ?>">
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
                        <input type="number" name="no_llamadas" class="form-control border-0 bg-light shadow-sm text-center" value="<?= $ticket['no_llamadas'] ?>">
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
                <input type="number" step="0.01" name="costo_refac_venta" class="form-control costo-input border-0 bg-light shadow-sm" value="<?= $ticket['costo_refac_venta'] > 0 ? $ticket['costo_refac_venta'] : '' ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-success">Refac. (Gar)</label>
                <input type="number" step="0.01" name="costo_refac_garantia" class="form-control costo-input border-success bg-light shadow-sm" value="<?= $ticket['costo_refac_garantia'] > 0 ? $ticket['costo_refac_garantia'] : '' ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Base</label>
                <input type="number" step="0.01" name="costo_base" class="form-control costo-input border-0 bg-light shadow-sm" value="<?= $ticket['costo_base'] > 0 ? $ticket['costo_base'] : '' ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Técnico</label>
                <input type="number" step="0.01" name="costo_tecnico" class="form-control costo-input border-0 bg-light shadow-sm" value="<?= $ticket['costo_tecnico'] > 0 ? $ticket['costo_tecnico'] : '' ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Envío</label>
                <input type="number" step="0.01" name="costo_envio" class="form-control costo-input border-0 bg-light shadow-sm" value="<?= $ticket['costo_envio'] > 0 ? $ticket['costo_envio'] : '' ?>" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Cotización</label>
                <input type="text" name="no_cotizacion" class="form-control bg-light border-0 shadow-sm" value="<?= $ticket['no_cotizacion'] ?>" placeholder="Folio...">
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

<script>
$(document).ready(function() {
    // 1. BLOQUEOS E INTERFAZ
    $(document).on('keydown', 'input[type="number"]', function(e) {
        if (['e', 'E', '+', '-'].includes(e.key)) e.preventDefault();
    });
    $(document).on('focus', 'input, textarea', function() { $(this).select(); });

    // 2. VALIDACIÓN DE SERIE (Solo si está vacío al inicio)
    var typingTimer;
    $('#no_serie_input').on('input', function() {
        if ($(this).attr('readonly')) return; 
        
        clearTimeout(typingTimer);
        var val = $(this).val();
        var msgDiv = $('#status_garantia');
        var txtStatus = $('#txt_status_garantia');
        var option = $('#series_cliente option').filter(function() { return this.value === val; });

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
                        txtStatus.text('Garantía: ' + res.resultado);
                        $('#garantia_valida_input').val(res.resultado);
                        let color = (res.resultado === 'Válida') ? '#198754' : '#dc3545';
                        txtStatus.css('color', color);
                        msgDiv.css('border-left', '4px solid ' + color);
                    }
                });
            }, 600);
        } else { msgDiv.hide(); }
    });

    // 3. DINÁMICA DE COSTOS
    function toggleCostos() {
        if (['Ninguna', 'Información'].includes($('#accion_select').val())) $('#seccion_costos').slideUp();
        else $('#seccion_costos').slideDown();
    }
    $('#accion_select').on('change', toggleCostos);

    // 4. CÁLCULOS
    $('.costo-input').on('input', function() {
        let total = 0;
        $('.costo-input').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#label_total').text(total.toFixed(2));
        $('#input_total').val(total.toFixed(2));
    });

    $('#fecha_inicio, #fecha_fin').on('change', function() {
        const inicio = $('#fecha_inicio').val();
        const fin = $('#fecha_fin').val();
        if (inicio) {
            $('#fecha_fin').prop('disabled', false).attr('min', inicio);
            if (fin) {
                const diff = new Date(fin) - new Date(inicio);
                const dias = Math.floor(diff / (1000 * 60 * 60 * 24));
                $('#tiempo_accion').val(dias >= 0 ? dias : 0);
            }
        }
    });

    $('#pago_switch').on('change', function() {
        const color = $(this).is(':checked') ? 'text-success' : 'text-danger';
        const txt = $(this).is(':checked') ? 'Pagado' : 'Pendiente';
        $('#label_pago').find('span').attr('class', color).text(txt);
    });

    toggleCostos();
    // Color garantía inicial
    const initG = $('#garantia_valida_input').val();
    let c = (initG === 'Válida') ? '#198754' : (initG === 'Pendiente' ? '#6c757d' : '#dc3545');
    $('#txt_status_garantia').css('color', c);
    $('#status_garantia').css('border-left', '4px solid ' + c);
});
</script>

<?php include 'includes/footer.php'; ?>