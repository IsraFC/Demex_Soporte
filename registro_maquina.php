<?php
/**
 * ARCHIVO: registro_maquina.php
 * DESCRIPCIÓN: Formulario de alta para equipos con validación asíncrona de duplicados.
 * Integra un buscador dinámico de clientes y cálculo automático de vigencia en backend.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.2
 */

// Define el contexto para el header (habilita botones Inicio/Maquinas)
$pagina_actual = 'otro'; 
require_once 'config/db.php';

include 'includes/header.php';
?>

<style>
    /**
     * ESTILOS DE VALIDACIÓN VISUAL
     * .required-alt: Agrega un asterisco rojo a etiquetas obligatorias.
     */
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
                    placeholder="Numero de serie..." required maxlength="15">
                <div id="status_serie" class="small mt-1 fw-bold" style="display:none;"></div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-person-circle me-1"></i> Cliente
                </label>
                <input list="listaClientes" name="nombre_cliente" class="form-control border-0 bg-light shadow-sm" 
                       placeholder="Escriba para buscar..." required>
                <datalist id="listaClientes">
                    <?php
                    // Carga nominal de clientes para el buscador predictivo
                    $clientes = $pdo->query("SELECT nombre_cliente FROM Clientes ORDER BY nombre_cliente ASC");
                    while ($c = $clientes->fetch()) {
                        echo "<option value='{$c['nombre_cliente']}'>";
                    }
                    ?>
                </datalist>
                <small class="text-muted" style="font-size: 0.65rem;">Seleccione de la lista o escriba uno nuevo.</small>
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

        <div class="row justify-content-center g-4 border-top pt-4">
            
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

<script>
/**
 * LÓGICA DE VALIDACIÓN ASÍNCRONA (AJAX)
 * Verifica la existencia del número de serie en la base de datos mientras el usuario escribe.
 */
$(document).ready(function() {
    var typingTimer;                // Identificador del temporizador
    var doneTypingInterval = 500;  // Tiempo de espera para evitar saturación de peticiones (Debounce)

    $('#no_serie').on('input', function() {
        clearTimeout(typingTimer);
        var serie = $(this).val();
        var input = $(this);
        var msg = $('#status_serie');

        // Limpieza de estados visuales previos
        input.css('border', 'none'); 
        msg.hide();

        if (serie.length >= 3) {
            // Se inicia el temporizador para procesar la búsqueda tras una pausa en la escritura
            typingTimer = setTimeout(function() {
                $.ajax({
                    url: 'actions/verificar_serie.php', // Endpoint de verificación
                    method: 'POST',
                    data: { no_serie: serie },
                    success: function(response) {
                        if (response === 'existe') {
                            // Estado: Error (Serie duplicada)
                            input.addClass('is-invalid').removeClass('is-valid');
                            input.css('border', '2px solid #dc3545');
                            msg.text('⚠️ Este número de serie ya existe').css('color', '#dc3545').show();
                            $('#btnGuardar').attr('disabled', true); // Bloquea envío
                        } else {
                            // Estado: Éxito (Serie disponible)
                            input.addClass('is-valid').removeClass('is-invalid');
                            input.css('border', '2px solid #198754');
                            msg.text('✅ Disponible').css('color', '#198754').show();
                            $('#btnGuardar').attr('disabled', false); // Habilita envío
                        }
                    }
                });
            }, doneTypingInterval);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>