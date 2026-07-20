<?php
/**
 * ARCHIVO: Soporte/registro_tecnico.php
 * DESCRIPCIÓN: Interfaz de captura para el alta de técnicos de soporte.
 * Implementa listas de autocompletado nativas HTML5 (datalist) vinculadas por AJAX.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.5
 */

require_once '../config/db.php';
$page_title = "Registrar Técnico - Soporte";
$modulo_actual = 'soporte';
include '../includes/header.php';

// Consulta inicial para precargar los estados existentes en el volcado SQL oficial
$queryEstados = $pdo->query("SELECT estado FROM estados ORDER BY estado ASC");
$cat_estados = $queryEstados->fetchAll(PDO::FETCH_ASSOC);
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
        <h1 class="fw-bold text-danger mb-0">Alta de Nuevo Técnico</h1>
        <p class="text-muted small">Capture los datos base de la entidad operativa de soporte.</p>
    </div>
</div>

<div class="card-main shadow-lg p-5 bg-white rounded border-top border-4 border-danger">
    <form action="actions/procesar_tecnico.php" method="POST" id="formRegistroTecnico">
        
        <div class="row g-4">
            <div class="col-12">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-person me-1"></i> Nombre Completo / Razón Social del Servicio
                </label>
                <input type="text" name="nombre" id="nombre" class="form-control border-0 bg-light shadow-sm" placeholder="Ej: Juan Pérez o Soporte Telefónico Noreste" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-globe me-1"></i> Estado
                </label>
                <input type="text" name="estado" id="estado" list="listaEstados" class="form-control border-0 bg-light shadow-sm" placeholder="Escriba o seleccione un estado..." autocomplete="off" required>
                <datalist id="listaEstados">
                    <?php foreach ($cat_estados as $est): ?>
                        <option value="<?= htmlspecialchars($est['estado']) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-map me-1"></i> Zona / Municipio
                </label>
                <input type="text" name="zona" id="zona" list="listaMunicipios" class="form-control border-0 bg-light shadow-sm" placeholder="Escriba o seleccione una zona..." autocomplete="off" required>
                <datalist id="listaMunicipios">
                    </datalist>
            </div>

            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold small text-muted mb-0">
                        <i class="bi bi-telephone me-1"></i> Teléfonos de Línea Directa
                    </label>
                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold" id="btnAgregarTelefono">
                        <i class="bi bi-plus-lg me-1"></i> Añadir Otro
                    </button>
                </div>
                
                <div id="contenedorTelefonos">
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-telephone-plus"></i></span>
                        <input type="tel" name="telefonos[]" class="form-control border-0 bg-light shadow-sm class-telefono" placeholder="Ej: 222 123 4567" maxlength="12" required>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">Especifique las líneas directas del personal técnico (Máximo 10 dígitos).</small>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center d-flex justify-content-center gap-3">
                <a href="tecnicos.php" class="btn btn-light border px-5 rounded-pill fw-bold text-muted">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </a>
                <button type="submit" id="btnGuardar" class="btn btn-danger px-5 rounded-pill fw-bold shadow">
                    <i class="bi bi-person-check me-1"></i> Registrar Técnico
                </button>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    
    /**
     * 1. DETECTOR DE CAMBIO DE ESTADO (RELACIÓN DE MUNICIPIOS)
     * Escucha la selección del estado para gatillar la actualización asíncrona
     * del datalist vinculando las tres tablas físicas (estados, municipios y estados_municipios).
     */
    $('#estado').on('change', function() {
        var estadoSeleccionado = $(this).val().trim();
        var datalistMunicipios = $('#listaMunicipios');
        
        datalistMunicipios.empty();

        if (estadoSeleccionado !== '') {
            $.ajax({
                url: 'actions/buscar_geografia.php',
                type: 'POST',
                data: { 
                    accion: 'obtener_municipios_por_estado', 
                    estado: estadoSeleccionado 
                },
                success: function(opcionesHtml) {
                    datalistMunicipios.html(opcionesHtml);
                }
            });
        }
    });

    /**
     * 2. FORMATEO DINÁMICO DE TELÉFONOS (xxx xxx xxxx)
     * Garantiza el límite estricto de 10 dígitos y aplica los espacios de normalización.
     */
    $(document).on('input', '.class-telefono', function() {
        var input = $(this).val().replace(/\D/g, ''); 
        var formatted = '';

        if (input.length > 10) input = input.substring(0, 10);

        if (input.length > 0) {
            formatted = input.substring(0, 3);
            if (input.length > 3) formatted += ' ' + input.substring(3, 6);
            if (input.length > 6) formatted += ' ' + input.substring(6, 10);
        }
        $(this).val(formatted);
    });

    /**
     * 3. INSERCIÓN DINÁMICA DE ENTRADAS TELEFÓNICAS
     */
    $('#btnAgregarTelefono').on('click', function() {
        var inputFila = `
            <div class="input-group mb-2 animate__animated animate__fadeIn">
                <span class="input-group-text bg-light border-0"><i class="bi bi-telephone-plus"></i></span>
                <input type="tel" name="telefonos[]" class="form-control border-0 bg-light shadow-sm class-telefono" placeholder="Número adicional" maxlength="12" required>
                <button class="btn btn-outline-danger btnEliminarTelefono" type="button"><i class="bi bi-trash"></i></button>
            </div>`;
        $('#contenedorTelefonos').append(inputFila);
    });

    $(document).on('click', '.btnEliminarTelefono', function() {
        $(this).closest('.input-group').remove();
    });

    /**
     * 4. INTERCEPTOR ASÍNCRONO DEL FORMULARIO DE ALTA (FETCH API)
     * Procesa los datos y lee de forma transparente las respuestas JSON del controlador central.
     */
    $('#formRegistroTecnico').on('submit', function(e) {
        e.preventDefault();

        const btnGuardar = $('#btnGuardar');
        const textoOriginal = btnGuardar.html();
        btnGuardar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Procesando...');

        const formulario = this;
        const datosFormulario = new FormData(formulario);

        fetch(formulario.action, {
            method: formulario.method,
            body: datosFormulario
        })
        .then(respuesta => {
            if (!respuesta.ok) throw new Error('Error en la comunicación con el servidor.');
            return respuesta.json();
        })
        .then(data => {
            Swal.fire({
                icon: data.status,
                title: data.title,
                text: data.text,
                confirmButtonColor: data.status === 'success' ? '#d15b00' : '#C62828'
            }).then(() => {
                if (data.status === 'success') {
                    window.location.href = 'tecnicos.php';
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
});
</script>

<?php include '../includes/footer.php'; ?>