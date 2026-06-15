<?php
/**
 * ARCHIVO: editar_cliente.php
 * DESCRIPCIÓN: Interfaz de modificación de datos de clientes de forma asíncrona.
 * Implementa recuperación de datos vía PDO y validación asíncrona (AJAX) 
 * que discrimina entre el nombre propio del registro y duplicados externos.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.5 - Integración de Interceptor Fetch API para Alertas
 * @date 2026-06-08
 */
require_once '../config/db.php';
$page_title = "Editar Cliente - Soporte";

/**
 * 1. FASE DE RECUPERACIÓN Y SEGURIDAD
 * Se valida la existencia del ID en la URL y su integridad en la base de datos.
 */
$id_cliente = $_GET['id'] ?? null;
if (!$id_cliente) {
    header("Location: clientes.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM Clientes WHERE id_cliente = ?");
$stmt->execute([$id_cliente]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header("Location: clientes.php?msg=not_found");
    exit();
}

$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<style>
    /* Estilo local para campos obligatorios */
    .required-alt::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
</style>

<div class="row mb-4">
    <div class="col-12 text-center">
        <h1 class="fw-bold text-danger mb-0">Editar Cliente</h1>
        <p class="text-muted small">ID de Registro: <strong>CLI-<?= str_pad($cliente['id_cliente'], 3, "0", STR_PAD_LEFT) ?></strong></p>
    </div>
</div>

<div class="card-main shadow-lg p-5 bg-white rounded border-top border-4 border-primary">
    <form action="actions/procesar_cliente.php" method="POST" id="formEditarCliente">
        
        <input type="hidden" name="id_cliente" id="id_cliente" value="<?= $cliente['id_cliente'] ?>">

        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-building me-1"></i> Nombre del Cliente / Empresa
                </label>
                <input type="text" name="nombre_cliente" id="nombre_cliente" 
                    class="form-control border-0 bg-light shadow-sm" 
                    value="<?= htmlspecialchars($cliente['nombre_cliente']) ?>" required>
                <div id="status_cliente" class="small mt-1 fw-bold" style="display:none;"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">
                    <i class="bi bi-telephone me-1"></i> Teléfono
                </label>
                <input type="tel" name="telefono" id="telefono" 
                    class="form-control border-0 bg-light shadow-sm" 
                    value="<?= htmlspecialchars($cliente['telefono']) ?>" maxlength="12">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small text-muted">
                    <i class="bi bi-geo-alt me-1"></i> Ubicación
                </label>
                <textarea name="ubicacion" class="form-control border-0 bg-light shadow-sm" rows="2"><?= htmlspecialchars($cliente['ubicacion']) ?></textarea>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center d-flex justify-content-center gap-3">
                <a href="clientes.php" class="btn btn-light border px-5 rounded-pill fw-bold text-muted">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </a>
                <button type="submit" id="btnGuardar" class="btn btn-primary px-5 rounded-pill fw-bold shadow">
                    <i class="bi bi-save me-1"></i> Actualizar Datos
                </button>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    
    /**
     * 1. FORMATEADOR DE TELÉFONO
     * Limpia caracteres no numéricos y aplica formato visual 000 000 0000.
     */
    $('#telefono').on('input', function() {
        var input = $(this).val().replace(/\D/g, '');
        var formatted = '';
        if (input.length > 0) {
            formatted = input.substring(0, 3);
            if (input.length > 3) formatted += ' ' + input.substring(3, 6);
            if (input.length > 6) formatted += ' ' + input.substring(6, 10);
        }
        $(this).val(formatted);
    });

    /**
     * 2. VALIDACIÓN AJAX INTELIGENTE
     * Envía el ID actual del cliente para que el servidor ignore su propio nombre
     * al buscar duplicados, permitiendo actualizaciones parciales (ej. solo teléfono).
     */
    var typingTimer;
    var doneTypingInterval = 500;

    $('#nombre_cliente').on('input', function() {
        clearTimeout(typingTimer);
        var nombre = $(this).val();
        var input = $(this);
        var msg = $('#status_cliente');
        var idActual = $('#id_cliente').val();

        input.css('border', 'none'); 
        msg.hide();

        if (nombre.length >= 4) {
            typingTimer = setTimeout(function() {
                $.ajax({
                    url: 'actions/verificar_cliente.php',
                    method: 'POST',
                    data: { 
                        nombre: nombre,
                        id_cliente: idActual 
                    },
                    success: function(response) {
                        if (response.trim() === 'existe') {
                            input.addClass('is-invalid').css('border', '2px solid #dc3545');
                            msg.text('⚠️ Este nombre ya lo ocupa otro cliente').css('color', '#dc3545').show();
                            $('#btnGuardar').attr('disabled', true);
                        } else {
                            input.addClass('is-valid').removeClass('is-invalid').css('border', '2px solid #198754');
                            msg.text('✅ Nombre válido').css('color', '#198754').show();
                            $('#btnGuardar').attr('disabled', false);
                        }
                    }
                });
            }, doneTypingInterval);
        }
    });

    // 3. INTERCEPTOR ASÍNCRONO DEL FORMULARIO DE EDICIÓN (NUEVO)
    $('#formEditarCliente').on('submit', function(e) {
        e.preventDefault(); // Detiene la redirección o recarga tradicional del navegador

        const btnGuardar = $('#btnGuardar');
        const textoOriginal = btnGuardar.html();

        // Estado visual de carga y deshabilitado para evitar clicks repetidos
        btnGuardar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Actualizando...');

        const formulario = this;
        const datosFormulario = new FormData(formulario);

        fetch(formulario.action, {
            method: formulario.method,
            body: datosFormulario
        })
        .then(respuesta => {
            if (!respuesta.ok) {
                throw new Error('Falla en la comunicación de red con el servidor.');
            }
            return respuesta.json(); // Parsea la respuesta JSON estructurada de tu backend híbrido
        })
        .then(data => {
            Swal.fire({
                icon: data.status,
                title: data.title,
                text: data.text,
                confirmButtonColor: data.status === 'success' ? '#d15b00' : '#C62828'
            }).then(() => {
                if (data.status === 'success') {
                    window.location.href = 'clientes.php'; // Redirección limpia al catálogo
                } else {
                    btnGuardar.prop('disabled', false).html(textoOriginal); // Reactiva si fue advertencia o fallo controlado
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
});
</script>

<?php include '../includes/footer.php'; ?>