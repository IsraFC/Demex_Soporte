<?php
/**
 * ARCHIVO: registro_cliente.php
 * DESCRIPCIÓN: Interfaz de captura para nuevos clientes.
 * Incluye validación de duplicados en tiempo real mediante AJAX y 
 * una máscara de entrada dinámica para estandarizar números telefónicos.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.4
 */
$pagina_actual = 'otro'; 
require_once 'config/db.php';

include 'includes/header.php';
?>

<style>
    /**
     * ESTILOS DE VALIDACIÓN VISUAL
     * .required-alt: Genera el indicador visual de campo obligatorio.
     */
    .required-alt::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
</style>

<div class="row mb-4">
    <div class="col-12 text-center">
        <h1 class="fw-bold text-danger mb-0">Alta de Nuevo Cliente</h1>
        <p class="text-muted small">Ingrese los datos fiscales o comerciales para generar la cuenta.</p>
    </div>
</div>

<div class="card-main shadow-lg p-5 bg-white rounded border-top border-4 border-danger">
    <form action="actions/procesar_cliente.php" method="POST" id="formRegistroCliente">
        
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-building me-1"></i> Nombre del Cliente / Empresa
                </label>
                <input type="text" name="nombre_cliente" id="nombre_cliente" 
                    class="form-control border-0 bg-light shadow-sm" 
                    placeholder="Ej: Maderería El Pino S.A."
                    value="<?= htmlspecialchars($_GET['nombre'] ?? '') ?>" required>
                <div id="status_cliente" class="small mt-1 fw-bold" style="display:none;"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">
                    <i class="bi bi-telephone me-1"></i> Teléfono de Contacto
                </label>
                <input type="tel" name="telefono" id="telefono" 
                    class="form-control border-0 bg-light shadow-sm" 
                    placeholder="Ej: 222 123 4567" maxlength="12">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small text-muted">
                    <i class="bi bi-geo-alt me-1"></i> Ubicación / Dirección Completa
                </label>
                <textarea name="ubicacion" class="form-control border-0 bg-light shadow-sm" rows="2" 
                    placeholder="Calle, Número, Colonia y Municipio..."></textarea>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center d-flex justify-content-center gap-3">
                <a href="clientes.php" class="btn btn-light border px-5 rounded-pill fw-bold text-muted">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </a>
                <button type="submit" id="btnGuardar" class="btn btn-danger px-5 rounded-pill fw-bold shadow">
                    <i class="bi bi-person-check me-1"></i> Registrar Cliente
                </button>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    
    /**
     * 1. FORMATEO DINÁMICO DE TELÉFONO
     * Transforma la entrada '2221234567' en '222 123 4567' en tiempo real.
     */
    $('#telefono').on('input', function() {
        var input = $(this).val().replace(/\D/g, ''); // Remueve no-numéricos
        var formatted = '';

        if (input.length > 0) {
            formatted = input.substring(0, 3);
            if (input.length > 3) formatted += ' ' + input.substring(3, 6);
            if (input.length > 6) formatted += ' ' + input.substring(6, 10);
        }
        $(this).val(formatted);
    });

    /**
     * 2. VALIDACIÓN ASÍNCRONA DE DUPLICADOS (AJAX)
     * Utiliza un temporizador (debounce) de 500ms para evitar peticiones excesivas al servidor.
     */
    var typingTimer;
    var doneTypingInterval = 500;

    $('#nombre_cliente').on('input', function() {
        clearTimeout(typingTimer);
        var nombre = $(this).val();
        var input = $(this);
        var msg = $('#status_cliente');

        input.css('border', 'none'); 
        msg.hide();

        if (nombre.length >= 4) {
            typingTimer = setTimeout(function() {
                $.ajax({
                    url: 'actions/verificar_cliente.php',
                    method: 'POST',
                    data: { nombre: nombre },
                    success: function(response) {
                        if (response === 'existe') {
                            input.addClass('is-invalid').css('border', '2px solid #dc3545');
                            msg.text('⚠️ Este cliente ya está registrado').css('color', '#dc3545').show();
                            $('#btnGuardar').attr('disabled', true);
                        } else {
                            input.addClass('is-valid').css('border', '2px solid #198754');
                            msg.text('✅ Nombre disponible').css('color', '#198754').show();
                            $('#btnGuardar').attr('disabled', false);
                        }
                    }
                });
            }, doneTypingInterval);
        }
    });

    // Si ya viene un nombre en la URL, valida si existe de una vez
    if ($('#nombre_cliente').val().length >= 4) {
        $('#nombre_cliente').trigger('input');
    }
});
</script>

<?php include 'includes/footer.php'; ?>