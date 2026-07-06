<?php
/**
 * @file usuarios.php
 * @package Portal_Demex
 * @version 2.3 - Gestión de Personal Completa con Captura Síncrona y Asíncrona
 * @date 2026-06-08
 * @brief Interfaz de administración de personal con interceptores AJAX/Fetch instalados en la creación y edición.
 */

$modulo_actual = 'global';
$page_title = "Gestión de Personal";
require_once 'includes/header.php';

// Control de acceso estricto: Solo administradores manejan personal
if (!tieneAcceso(['Administrador'])) {
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

// Carga directa desde la carpeta config de la raíz
require_once 'config/db.php';

try {
    $sql_usuarios = "SELECT u.id_usuario, u.nombre, u.apellidos, u.correo, u.estatus, u.foto_perfil,
                            GROUP_CONCAT(r.nombre_rol SEPARATOR ', ') AS roles_asignados
                     FROM usuarios u
                     LEFT JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
                     LEFT JOIN roles r ON ur.id_rol = r.id_rol
                     GROUP BY u.id_usuario
                     ORDER BY u.id_usuario DESC";
    $stmt = $pdo->query($sql_usuarios);
    $usuarios = $stmt->fetchAll();

    $roles_catalogo = $pdo->query("SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol ASC")->fetchAll();

} catch (\Exception $e) {
    echo "<div class='alert alert-danger m-4'>Error en el servidor: " . htmlspecialchars($e->getMessage()) . "</div>";
    require_once 'includes/footer.php';
    exit();
}
?>

<div class="row mb-4 animate-fade-in">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Personal del Sistema</h2>
            <p class="text-muted small mb-0">Administra los accesos y los múltiples perfiles de seguridad de los ingenieros de soporte y administradores de DEMEX.</p>
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
                        <th>Roles Asignados</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): 
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
                                    <?php if (!empty($u['foto_perfil'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($u['foto_perfil']) ?>" 
                                             alt="Foto" 
                                             class="rounded-circle shadow-sm object-fit-cover me-3" 
                                             style="width: 40px; height: 40px;">
                                    <?php else: ?>
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 40px; height: 40px; min-width: 40px;">
                                            <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <span class="fw-semibold d-block text-dark"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?></span>
                                        <small class="text-muted" style="font-size: 11px;">ID: #<?= $u['id_usuario'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="fw-medium text-secondary"><?= htmlspecialchars($u['correo']) ?></td>
                            <td>
                                <?php if (!empty($u['roles_asignados'])): ?>
                                    <?php 
                                    $lista_roles = explode(', ', $u['roles_asignados']);
                                    foreach ($lista_roles as $rol_item): 
                                        $badgeColor = 'bg-secondary';
                                        if ($rol_item === 'Administrador') $badgeColor = 'bg-danger';
                                        if ($rol_item === 'Soporte') $badgeColor = 'bg-info text-dark';
                                        if ($rol_item === 'Ventas') $badgeColor = 'bg-warning text-dark';
                                        if( $rol_item === 'Almacen') $badgeColor = 'bg-primary';
                                        if ($rol_item === 'Cliente') $badgeColor = 'bg-success';
                                    ?>
                                        <span class="badge <?= $badgeColor ?> px-2 py-1 rounded-pill text-uppercase me-1" style="font-size: 9px; letter-spacing: 0.5px;">
                                            <?= htmlspecialchars($rol_item) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted px-2 py-1 rounded">Sin roles</span>
                                <?php endif; ?>
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
                                        data-roles="<?= htmlspecialchars($u['roles_asignados'] ?? '') ?>"
                                        data-estatus="<?= $u['estatus'] ?>">
                                    <i class="bi bi-pencil-square text-danger"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
                            <label class="form-label small fw-bold text-muted text-uppercase d-block mb-2">Roles del Sistema</label>
                            <div class="card bg-light border-0 py-2 px-3">
                                <?php foreach ($roles_catalogo as $rol): ?>
                                    <div class="form-check my-1">
                                        <input class="form-check-input check-rol-nuevo" type="checkbox" name="roles[]" value="<?= $rol['id_rol'] ?>" id="rol_nuevo_<?= $rol['id_rol'] ?>">
                                        <label class="form-check-label small fw-semibold text-dark" for="rol_nuevo_<?= $rol['id_rol'] ?>">
                                            <?= htmlspecialchars($rol['nombre_rol']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-danger small d-none" id="error-roles-nuevo">Debe seleccionar al menos un rol para continuar.</div>
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
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block mb-2">Roles del Sistema</label>
                            <div class="card bg-light border-0 py-2 px-3">
                                <?php foreach ($roles_catalogo as $rol): ?>
                                    <div class="form-check my-1">
                                        <input class="form-check-input check-rol-editar" type="checkbox" name="roles[]" value="<?= $rol['id_rol'] ?>" id="rol_edit_<?= $rol['id_rol'] ?>" data-nombre="<?= htmlspecialchars($rol['nombre_rol']) ?>">
                                        <label class="form-check-label small fw-semibold text-dark" for="rol_edit_<?= $rol['id_rol'] ?>">
                                            <?= htmlspecialchars($rol['nombre_rol']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-danger small d-none" id="error-roles-editar">Debe seleccionar al menos un rol para continuar.</div>
                        </div>
                        <div class="col-12">
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
                    <button type="submit" id="btnActualizarUsuario" class="btn btn-danger rounded-pill px-4 fw-bold shadow">Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tablaUsuarios').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 10,
        lengthChange: false,
        dom: 'rtip'
    });

    $('#modalNuevoUsuario').appendTo("body");
    $('#modalEditarUsuario').appendTo("body");

    $('#modalNuevoUsuario').on('hidden.bs.modal', function () {
        $('#formNuevoUsuario')[0].reset();
        $('.check-rol-nuevo').prop('checked', false);
        $('#correo_usuario').removeClass('is-invalid is-valid');
        $('#error-roles-nuevo').addClass('d-none');
        $('#btnGuardarUsuario').prop('disabled', false);
    });

    // INTERCEPTOR ASÍNCRONO PARA EL FORMULARIO DE ALTA (FETCH API)
    $('#formNuevoUsuario').on('submit', function(e) {
        e.preventDefault(); // Impedimos la redirección nativa del navegador

        if ($('.check-rol-nuevo:checked').length === 0) {
            $('#error-roles-nuevo').removeClass('d-none');
            return false;
        }
        $('#error-roles-nuevo').addClass('d-none');

        // Ponemos un estado visual de carga en el botón para prevenir clicks dobles mientras PHPMailer procesa
        const btnGuardar = $('#btnGuardarUsuario');
        const textoOriginal = btnGuardar.html();
        btnGuardar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enviando...');

        const formulario = this;
        const datosFormulario = new FormData(formulario);

        fetch(formulario.action, {
            method: formulario.method,
            body: datosFormulario
        })
        .then(respuesta => {
            if (!respuesta.ok) {
                throw new Error('Falla en la comunicación con el servidor de correos.');
            }
            return respuesta.json();
        })
        .then(data => {
            $('#modalNuevoUsuario').modal('hide');

            Swal.fire({
                icon: data.status,
                title: data.title,
                text: data.text,
                confirmButtonColor: data.status === 'success' ? '#d15b00' : '#C62828'
            }).then(() => {
                if (data.status === 'success') {
                    window.location.reload(); // Recargamos para ver al usuario en la tabla con estatus "Pendiente"
                } else {
                    btnGuardar.prop('disabled', false).html(textoOriginal);
                    $('#modalNuevoUsuario').modal('show'); 
                }
            });
        })
        .catch(error => {
            btnGuardar.prop('disabled', false).html(textoOriginal);
            Swal.fire({
                icon: 'error',
                title: 'Error de Despacho',
                text: error.message,
                confirmButtonColor: '#C62828'
            });
        });
    });

    // INTERCEPTOR ASÍNCRONO PARA EL FORMULARIO DE EDICIÓN (FETCH API)
    $('#formEditarUsuario').on('submit', function(e) {
        e.preventDefault(); 

        if ($('.check-rol-editar:checked').length === 0) {
            $('#error-roles-editar').removeClass('d-none');
            return false;
        }
        $('#error-roles-editar').addClass('d-none');

        const formulario = this;
        const datosFormulario = new FormData(formulario);

        fetch(formulario.action, {
            method: formulario.method,
            body: datosFormulario
        })
        .then(respuesta => {
            if (!respuesta.ok) {
                throw new Error('Falla en la comunicación con el servidor de bases de datos.');
            }
            return respuesta.json();
        })
        .then(data => {
            $('#modalEditarUsuario').modal('hide');

            Swal.fire({
                icon: data.status,
                title: data.title,
                text: data.text,
                confirmButtonColor: data.status === 'success' ? '#d15b00' : '#C62828'
            }).then(() => {
                if (data.status === 'success') {
                    window.location.reload(); 
                } else {
                    $('#modalEditarUsuario').modal('show'); 
                }
            });
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error de Procesamiento',
                text: error.message,
                confirmButtonColor: '#C62828'
            });
        });
    });

    $('#correo_usuario').on('input', function() {
        const correo = $(this).val().trim();
        const inputCorreo = $(this);
        const feedback = $('#correo-feedback');
        const btnGuardar = $('#btnGuardarUsuario');

        if (correo === '') {
            inputCorreo.removeClass('is-invalid is-valid');
            btnGuardar.prop('disabled', false);
            return;
        }

        $.ajax({
            url: 'actions/verificar_correo_disponible.php',
            type: 'GET',
            data: { correo: correo },
            dataType: 'json',
            success: function(response) {
                if (response.disponible === false) {
                    inputCorreo.addClass('is-invalid').removeClass('is-valid');
                    feedback.text('Este correo ya pertenece a un miembro del staff.');
                    btnGuardar.prop('disabled', true);
                } else {
                    inputCorreo.addClass('is-valid').removeClass('is-invalid');
                    btnGuardar.prop('disabled', false);
                }
            },
            error: function() {
                console.log('Error al validar el correo electrónico.');
            }
        });
    });

    $(document).on('click', '.btn-editar-usuario', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const apellidos = $(this).data('apellidos');
        const rolesString = $(this).data('roles');
        const estatus = $(this).data('estatus');

        $('#edit_id_usuario').val(id);
        $('#edit_nombre').val(nombre);
        $('#edit_apellidos').val(apellidos);
        $('#edit_estatus').val(estatus);
        $('#error_roles-editar').addClass('d-none');

        $('.check-rol-editar').prop('checked', false);

        if (rolesString) {
            const rolesArray = rolesString.split(', ');
            $('.check-rol-editar').each(function() {
                const nombreCheckbox = $(this).data('nombre');
                if (rolesArray.includes(nombreCheckbox)) {
                    $(this).prop('checked', true);
                }
            });
        }

        $('#modalEditarUsuario').modal('show');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>