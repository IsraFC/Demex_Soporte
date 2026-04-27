<?php
/**
 * ARCHIVO: registro_ticket.php
 * DESCRIPCIÓN: Interfaz unificada para la creación de nuevos folios de soporte.
 * Integra la captura de datos técnicos (Fase 1) y logística financiera (Fase 2)
 * en una sola pantalla con validaciones AJAX en tiempo real.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.5
 */
$pagina_actual = 'otro'; 
require_once 'config/db.php';

// 1. VALIDACIÓN DE CONTEXTO: Se requiere un cliente destino para iniciar el registro.
$id_cliente = $_GET['id_cliente'] ?? null;
if (!$id_cliente) { header("Location: clientes.php"); exit(); }

// 2. RECUPERACIÓN DE DATOS DEL CLIENTE
$stmt = $pdo->prepare("SELECT nombre_cliente FROM Clientes WHERE id_cliente = ?");
$stmt->execute([$id_cliente]);
$cliente = $stmt->fetch();

// 3. CARGA DE EQUIPOS VINCULADOS: Alimenta el buscador de series (Datalist) del cliente actual.
$stmt_eq = $pdo->prepare("SELECT no_serie, modelo FROM Equipos_Garantia WHERE id_cliente = ?");
$stmt_eq->execute([$id_cliente]);
$equipos_cliente = $stmt_eq->fetchAll(PDO::FETCH_ASSOC);

// 4. CATÁLOGO GLOBAL: Modelos registrados para selección manual en caso de equipos nuevos.
$stmt_mod = $pdo->query("SELECT DISTINCT modelo FROM Equipos_Garantia ORDER BY modelo ASC");
$todos_modelos = $stmt_mod->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="fw-bold text-danger mb-0 text-uppercase">Nuevo Ticket de Soporte</h1>
        <p class="text-muted"><i class="bi bi-person-badge me-1 text-danger"></i> Cliente: <strong><?= htmlspecialchars($cliente['nombre_cliente']) ?></strong></p>
    </div>
</div>

<form action="actions/procesar_ticket.php" method="POST" id="formTicket">
    <input type="hidden" name="id_cliente" value="<?= $id_cliente ?>">
    <input type="hidden" name="estatus" value="Abierto">
    <input type="hidden" name="garantia_valida" id="garantia_valida_input" value="Pendiente">

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

<script>
/**
 * LÓGICA FRONTEND (jQuery):
 * Controla la interactividad del registro, validaciones AJAX y cálculos en caliente.
 */
$(document).ready(function() {
    // 1. MEJORAS DE UX: Evita notación científica en números y permite selección total al enfocar.
    $(document).on('keydown', 'input[type="number"]', function(e) {
        if (['e', 'E', '+', '-'].includes(e.key)) e.preventDefault();
    });
    $(document).on('focus', 'input, textarea', function() { $(this).select(); });

    // 2. PANEL DE COSTOS DINÁMICO: Oculta la sección financiera si la acción es puramente informativa.
    function toggleSeccionCostos() {
        const accion = $('#accion_select').val();
        const btn = $('.btn-guardar');
        if (['Ninguna', 'Información'].includes(accion)) {
            $('#seccion_costos').slideUp();
            $('#btnText').text('Finalizar Registro');
            btn.removeClass('btn-danger').addClass('btn-success');
        } else {
            $('#seccion_costos').slideDown();
            $('#btnText').text('Guardar y Finalizar');
            btn.removeClass('btn-success').addClass('btn-danger');
        }
    }
    $('#accion_select').on('change', toggleSeccionCostos);

    // 3. VALIDACIÓN DE GARANTÍA AJAX: Consulta la DB al escribir la serie para determinar cobertura.
    var typingTimer;
    $('#no_serie_input').on('input', function() {
        clearTimeout(typingTimer);
        var val = $(this).val();
        var msgDiv = $('#status_garantia');
        var txtStatus = $('#txt_status_garantia');
        var option = $('#series_cliente option').filter(function() { return this.value === val; });

        if (val.length > 2) {
            msgDiv.show();
            txtStatus.text('🔍 Validando...').css('color', '#6c757d');
            
            // Auto-selección del modelo si la serie coincide con el datalist
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

    // 4. CÁLCULO DE TOTALES: Suma todos los conceptos financieros al vuelo.
    $('.costo-input').on('input', function() {
        let total = 0;
        $('.costo-input').each(function() {
            let val = parseFloat($(this).val()) || 0;
            total += val;
        });
        $('#label_total').text(total.toFixed(2));
        $('#input_total').val(total.toFixed(2));
    });

    // 5. CÁLCULO DE TIEMPO: Calcula la diferencia en días entre las fechas de logística.
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

    // 6. ESTATUS DE PAGO: Cambio visual del badge según el switch.
    $('#pago_switch').on('change', function() {
        const txt = $(this).is(':checked') ? '<span class="text-success">Pagado</span>' : '<span class="text-danger">Pendiente</span>';
        $('#label_pago').find('span').remove();
        $('#label_pago').append(txt);
    });
});
</script>

<?php include 'includes/footer.php'; ?>