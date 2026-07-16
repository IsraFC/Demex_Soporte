<?php
/**
 * ARCHIVO: Soporte/editar_tecnico.php
 * DESCRIPCIÓN: Interfaz de edición para la actualización de datos de técnicos.
 * Implementa datalists nativos e inyección dinámica de múltiples líneas telefónicas.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.0
 */

require_once '../config/db.php';

$id_tecnico = $_GET['id'] ?? null;
if (!$id_tecnico) {
    header("Location: tecnicos.php");
    exit();
}

// 1. OBTENER DATOS BASE DEL TÉCNICO
$stmt = $pdo->prepare("SELECT * FROM tecnicos WHERE id_tecnico = ?");
$stmt->execute([$id_tecnico]);
$tecnico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tecnico) {
    header("Location: tecnicos.php");
    exit();
}

// 2. OBTENER SUS TELÉFONOS REGISTRADOS
$stmt_tel = $pdo->prepare("SELECT telefono FROM tecnicos_telefonos WHERE id_tecnico = ? ORDER BY id_telefono ASC");
$stmt_tel->execute([$id_tecnico]);
$telefonos = $stmt_tel->fetchAll(PDO::FETCH_COLUMN);

// 3. PRECARGA DEL CATÁLOGO DE ESTADOS
$queryEstados = $pdo->query("SELECT estado FROM estados ORDER BY estado ASC");
$cat_estados = $queryEstados->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Editar Técnico - Soporte";
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
        <h1 class="fw-bold text-danger mb-0">Modificar Registro de Técnico</h1>
        <p class="text-muted small">Actualice el alcance operativo y los canales de comunicación de la entidad.</p>
    </div>
</div>

<div class="card-main shadow-lg p-5 bg-white rounded border-top border-4 border-danger">
    <form action="actions/procesar_tecnico.php" method="POST" id="formEditarTecnico">
        <input type="hidden" name="id_tecnico" value="<?= $tecnico['id_tecnico'] ?>">
        
        <div class="row g-4">
            <div class="col-12">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-person me-1"></i> Nombre Completo / Razón Social del Servicio
                </label>
                <input type="text" name="nombre" id="nombre" class="form-control border-0 bg-light shadow-sm" value="<?= htmlspecialchars($tecnico['nombre']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted required-alt">
                    <i class="bi bi-globe me-1"></i> Estado
                </label>
                <input type="text" name="estado" id="estado" list="listaEstados" class="form-control border-0 bg-light shadow-sm" value="<?= htmlspecialchars($tecnico['estado']) ?>" autocomplete="off" required>
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
                <input type="text" name="zona" id="zona" list="listaMunicipios" class="form-control border-0 bg-light shadow-sm" value="<?= htmlspecialchars($tecnico['zona']) ?>" autocomplete="off" required>
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
                    <?php if (!empty($telefonos)): ?>
                        <?php foreach ($telefonos as $index => $tel): ?>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-telephone-plus"></i></span>
                                <input type="tel" name="telefonos[]" class="form-control border-0 bg-light shadow-sm class-telefono" value="<?= htmlspecialchars($tel) ?>" placeholder="Ej: 222 123 4567" maxlength="12" required>
                                <?php if ($index > 0): ?>
                                    <button class="btn btn-outline-danger btnEliminarTelefono" type="button"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-telephone-plus"></i></span>
                            <input type="tel" name="telefonos[]" class="form-control border-0 bg-light shadow-sm class-telefono" placeholder="Ej: 222 123 4567" maxlength="12" required>
                        </div>
                    <?php endif; ?>
                </div>
                <small class="text-muted d-block mt-1">Modifique o agregue las líneas directas necesarias (Máximo 10 dígitos).</small>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center d-flex justify-content-center gap-3">
                <a href="tecnicos.php" class="btn btn-light border px-5 rounded-pill fw-bold text-muted">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </a>
                <button type="submit" id="btnGuardar" class="btn btn-danger px-5 rounded-pill fw-bold shadow">
                    <i class="bi bi-person-check me-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    
    // Carga inicial de municipios basados en el estado actual precargado
    if ($('#estado').val().trim() !== '') {
        cargarMunicipios($('#estado').val().trim());
    }

    $('#estado').on('change', function() {
        cargarMunicipios($(this).val().trim());
    });

    function cargarMunicipios(estadoSeleccionado) {
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
    }

    // Formateo dinámico de entradas telefónicas
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

    // Inserción dinámica de campos
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

    // Despacho asíncrono vía Fetch API + SweetAlert2
    $('#formEditarTecnico').on('submit', function(e) {
        e.preventDefault();
        const btnGuardar = $('#btnGuardar');
        const textoOriginal = btnGuardar.html();
        btnGuardar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Actualizando...');

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