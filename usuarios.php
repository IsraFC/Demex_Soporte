<?php
/**
 * @file usuarios.php
 * @package Portal_Demex
 * @version 1.4 - Gestión de Personal en Raíz del Sistema
 * @date 2026-05-25
 * @brief Interfaz centralizada para dar de alta técnicos y administradores globales.
 */
// Se adaptan las rutas de los layouts para consumir los componentes del módulo técnico de forma relativa
$modulo_actual = 'global';
require_once 'includes/header.php';

// Control de acceso estricto: Solo administradores manejan personal
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: 'No tienes privilegios de Administrador para gestionar el personal.',
            confirmButtonColor: '#C62828'
        }).then(() => { window.location.href = 'index.php'; });
    </script>";
    exit();
}

// Carga directa desde la nueva carpeta config de la raíz
require_once 'config/db.php';
?>

<div class="row mb-4 animate-fade-in">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Personal del Sistema</h2>
            <p class="text-muted small mb-0">Administra los accesos de los ingenieros de soporte y administradores de DEMEX.</p>
        </div>
        <button class="btn btn-dark btn-sm rounded-3 px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
            <i class="bi bi-person-plus-fill fs-5"></i> Registrar Personal
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 animate-fade-in">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaUsuarios" style="width:100%;">
                <thead class="table-light">
                    <tr>
                        <th>Nombre Completo</th>
                        <th>Correo Electrónico</th>
                        <th>Rol Asignado</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT id_usuario, nombre, apellidos, correo, rol, estatus FROM usuarios ORDER BY id_usuario DESC");
                    while ($u = $stmt->fetch()) {
                        $badgeColor = ($u['rol'] === 'administrador') ? 'bg-danger' : 'bg-warning text-dark';
                        
                        if ((int)$u['estatus'] === 1) {
                            $badgeEstatus = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                            $textoEstatus = 'Activo';
                        } else {
                            $badgeEstatus = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                            $textoEstatus = 'Pendiente';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 40px; height: 40px;">
                                        <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <span class="fw-semibold d-block text-dark"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?></span>
                                        <small class="text-muted" style="font-size: 11px;">ID: #<?= $u['id_usuario'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="fw-medium text-secondary"><?= htmlspecialchars($u['correo']) ?></td>
                            <td>
                                <span class="badge <?= $badgeColor ?> px-3 py-2 rounded-pill text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                    <?= htmlspecialchars($u['rol']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $badgeEstatus ?> px-2 py-1 rounded">
                                    <?= $textoEstatus ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-light btn-sm rounded-circle text-dark border btn-editar-usuario" 
                                        title="Editar Personal"
                                        data-id="<?= $u['id_usuario'] ?>"
                                        data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                                        data-apellidos="<?= htmlspecialchars($u['apellidos']) ?>"
                                        data-rol="<?= htmlspecialchars($u['rol']) ?>"
                                        data-estatus="<?= $u['estatus'] ?>">
                                    <i class="bi bi-pencil-square text-danger"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-fill-add me-2"></i>Alta de Personal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoUsuario" action="actions/procesar_usuario.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nombre(s)</label>
                            <input type="text" name="nombre" class="form-control bg-light border-0 py-2" required placeholder="Ej. Juan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control bg-light border-0 py-2" required placeholder="Ej. Pérez">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="correo" id="correo_usuario" class="form-control bg-light border-0 py-2" required placeholder="usuario@demex.com">
                                <div id="correo-feedback" class="invalid-feedback fw-semibold"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Rol del Sistema</label>
                            <select name="rol" class="form-select bg-light border-0 py-2" required>
                                <option value="soporte" selected>Soporte Técnico</option>
                                <option value="administrador">Administrador Global</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarUsuario" class="btn btn-danger rounded-pill px-4 fw-bold shadow">Guardar Acceso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Modificar Personal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarUsuario" action="actions/editar_usuario.php" method="POST">
                <input type="hidden" name="id_usuario" id="edit_id_usuario">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nombre(s)</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control bg-light border-0 py-2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Apellidos</label>
                            <input type="text" name="apellidos" id="edit_apellidos" class="form-control bg-light border-0 py-2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Rol del Sistema</label>
                            <select name="rol" id="edit_rol" class="form-select bg-light border-0 py-2" required>
                                <option value="soporte">Soporte Técnico</option>
                                <option value="administrador">Administrador Global</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Estatus de Acceso</label>
                            <select name="estatus" id="edit_estatus" class="form-select bg-light border-0 py-2" required>
                                <option value="1">Activo / Permitir Acceso</option>
                                <option value="0">Inactivo / Bloquear Acceso</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow">Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicialización de DataTable existente
    $('#tablaUsuarios').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 10,
        lengthChange: false,
        dom: 'rtip'
    });

    $('#modalNuevoUsuario').appendTo("body");
    // Asegurar que el modal de edición se mueva correctamente al body
    $('#modalEditarUsuario').appendTo("body");

    // Al cerrar el modal, limpiamos los formularios y los estilos de validación anteriores
    $('#modalNuevoUsuario').on('hidden.bs.modal', function () {
        $('#formNuevoUsuario')[0].reset();
        $('#correo_usuario').removeClass('is-invalid is-valid');
        $('#btnGuardarUsuario').prop('disabled', false);
    });

    // NUEVO: Validación de correo en tiempo real mediante AJAX
    $('#correo_usuario').on('input', function() {
        const correo = $(this).val().trim();
        const inputCorreo = $(this);
        const feedback = $('#correo-feedback');
        const btnGuardar = $('#btnGuardarUsuario');

        // Si el campo está vacío, reseteamos el estado visual y habilitamos el botón
        if (correo === '') {
            inputCorreo.removeClass('is-invalid is-valid');
            btnGuardar.prop('disabled', false);
            return;
        }

        // Petición asíncrona al verificador
        $.ajax({
            url: 'actions/verificar_correo_disponible.php',
            type: 'GET',
            data: { correo: correo },
            dataType: 'json',
            success: function(response) {
                if (response.disponible === false) {
                    // Si el correo ya existe en la BD
                    inputCorreo.addClass('is-invalid').removeClass('is-valid');
                    feedback.text('Este correo ya pertenece a un miembro del staff.');
                    btnGuardar.prop('disabled', true); // Bloqueamos el botón de guardar
                } else {
                    // Si el correo está libre
                    inputCorreo.addClass('is-valid').removeClass('is-invalid');
                    btnGuardar.prop('disabled', false); // Habilitamos el botón
                }
            },
            error: function() {
                console.log('Error al validar el correo electrónico.');
            }
        });
    });

    // NUEVO: Escuchar clics en los botones de editar para cargar datos en el modal
    $(document).on('click', '.btn-editar-usuario', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const apellidos = $(this).data('apellidos');
        const rol = $(this).data('rol');
        const estatus = $(this).data('estatus');

        // Insertar valores en los inputs del modal de edición
        $('#edit_id_usuario').val(id);
        $('#edit_nombre').val(nombre);
        $('#edit_apellidos').val(apellidos);
        $('#edit_rol').val(rol);
        $('#edit_estatus').val(estatus);

        // Desplegar el modal de edición de Bootstrap
        $('#modalEditarUsuario').modal('show');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>