<?php
/**
 * @file perfil.php
 * @package Portal_Demex
 * @version 1.8 - Mi Perfil con Visores de Contraseña e Interceptores Asíncronos
 * @date 2026-06-08
 * @brief Interfaz de autogestión de usuario con validación de correo en tiempo real, Canvas API y switches de visibilidad de claves.
 */

$page_title = "Mi Perfil";
require_once 'includes/header.php';
require_once 'config/db.php';

$id_usuario_sesion = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario_sesion) {
    echo "<div class='alert alert-danger m-4'>Error crítico: Sesión no válida.</div>";
    require_once 'includes/footer.php';
    exit();
}

try {
    $query = "SELECT nombre, apellidos, correo, estatus, foto_perfil FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id_usuario' => $id_usuario_sesion]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo "<div class='alert alert-danger m-4'>No se encontró la información del usuario.</div>";
        require_once 'includes/footer.php';
        exit();
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-4'>Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    require_once 'includes/footer.php';
    exit();
}
?>

<div class="row mb-4 animate__animated animate__fadeIn">
    <div class="col-12">
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-person-gear me-2 text-primary"></i>Mi Perfil</h2>
        <p class="text-muted small mb-0">Gestiona tu información personal y visualiza tus permisos asignados en el portal.</p>
    </div>
</div>

<div class="row g-4 animate__animated animate__fadeIn">
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white">
            <div class="card-body">
                
                <div class="position-relative mx-auto mb-2" style="width: 100px; height: 100px;">
                    <div onclick="document.getElementById('foto_perfil').click();" style="cursor: pointer;" title="Cambiar foto de perfil">
                        <?php if (!empty($usuario['foto_perfil'])): ?>
                            <img id="avatar-preview" src="data:image/jpeg;base64,<?= base64_encode($usuario['foto_perfil']) ?>" 
                                 alt="Foto de perfil" 
                                 class="rounded-circle shadow object-fit-cover" 
                                 style="width: 100px; height: 100px;">
                        <?php else: ?>
                            <div id="avatar-inicial" class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow" style="width: 100px; height: 100px; font-size: 38px;">
                                <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                            </div>
                            <img id="avatar-preview" src="" alt="Foto de perfil" class="rounded-circle shadow object-fit-cover d-none" style="width: 100px; height: 100px;">
                        <?php endif; ?>
                        
                        <div class="bg-dark text-white rounded-circle position-absolute d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; bottom: 0; right: 0; border: 2px solid #fff;">
                            <i class="bi bi-camera-fill" style="font-size: 14px;"></i>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" id="btn-borrar-foto" class="btn btn-link text-danger btn-sm p-0 text-decoration-none small <?= empty($usuario['foto_perfil']) ? 'd-none' : '' ?>" onclick="marcarEliminacionFoto()">
                        <i class="bi bi-trash3-fill me-1"></i> Eliminar foto actual
                    </button>
                </div>
                
                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?></h5>
                <p class="text-muted small mb-3"><?= htmlspecialchars($usuario['correo']) ?></p>
                
                <div class="mb-3">
                    <?php foreach ($_SESSION['roles'] as $rol): ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 text-uppercase px-2 py-1 small m-1">
                            <?= htmlspecialchars($rol) ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <hr class="opacity-25 my-3">

                <div class="text-start">
                    <small class="text-muted d-block mb-1">Estatus de cuenta:</small>
                    <?php if ((int)$usuario['estatus'] === 1): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-check-circle-fill me-1"></i> Cuenta Activa</span>
                    <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-x-circle-fill me-1"></i> Inactiva</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="row g-4">
            
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-dark mb-4">Información de la Cuenta</h5>
                        
                        <form id="formPerfilUsuario" method="POST" action="actions/procesar_perfil.php" enctype="multipart/form-data">
                            <input type="file" id="foto_perfil" class="d-none" accept="image/jpeg, image/png, image/webp, image/gif" onchange="procesarYComprimirFoto(this)">
                            <input type="hidden" name="foto_comprimida_base64" id="foto_comprimida_base64" value="">
                            <input type="hidden" name="eliminar_foto" id="eliminar_foto" value="0">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Nombre(s)</label>
                                    <input type="text" name="nombre" class="form-control rounded-3" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Apellidos</label>
                                    <input type="text" name="apellidos" class="form-control rounded-3" value="<?= htmlspecialchars($usuario['apellidos']) ?>" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-secondary">Correo Electrónico</label>
                                    <input type="email" name="correo" id="correo_perfil" class="form-control rounded-3" value="<?= htmlspecialchars($usuario['correo']) ?>" required>
                                    <div id="correo-feedback" class="invalid-feedback fw-semibold"></div>
                                    <div class="form-text text-muted mt-2" style="font-size: 11px;">
                                        <i class="bi bi-info-circle-fill me-1"></i> Las imágenes de alta resolución se optimizarán automáticamente antes de guardarse para asegurar la estabilidad del servidor.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted">Roles Asignados (Solo Lectura)</label>
                                    <input type="text" class="form-control bg-light rounded-3 text-muted small" value="<?= htmlspecialchars(implode(', ', $_SESSION['roles'])) ?>" disabled>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="<?= $base_path ?>index.php" class="btn btn-light rounded-3 px-4 small fw-semibold">Cancelar</a>
                                <button type="submit" class="btn btn-primary rounded-3 px-4 small fw-semibold shadow-sm">
                                    <i class="bi bi-save2-fill me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-dark mb-4">Seguridad de la Cuenta</h5>
                        
                        <form id="formPasswordUsuario" method="POST" action="actions/cambiar_password.php">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-secondary">Contraseña Actual</label>
                                    <div class="input-group">
                                        <input type="password" name="password_actual" id="password_actual" class="form-control rounded-start-3" placeholder="Ingresa tu clave vigente" required>
                                        <button class="input-group-text bg-light border text-secondary rounded-end-3" type="button" tabindex="-1" onclick="alternarVisibilidad('password_actual', this)">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Nueva Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" name="nueva_password" id="nueva_password" class="form-control rounded-start-3" placeholder="Mínimo 8 caracteres" required>
                                        <button class="input-group-text bg-light border text-secondary rounded-end-3" type="button" tabindex="-1" onclick="alternarVisibilidad('nueva_password', this)">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Confirmar Nueva Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" id="confirmar_password" class="form-control rounded-start-3" placeholder="Repite la nueva clave" required>
                                        <button class="input-group-text bg-light border text-secondary rounded-end-3" type="button" tabindex="-1" onclick="alternarVisibilidad('confirmar_password', this)">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        <div id="password-feedback" class="invalid-feedback fw-semibold">Las contraseñas nuevas no coinciden.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="submit" class="btn btn-danger rounded-3 px-4 small fw-semibold shadow-sm">
                                    <i class="bi bi-key-fill me-2"></i>Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
/**
 * Alterna el tipo de atributo del input de contraseña para ocultar o mostrar el texto plano
 */
function alternarVisibilidad(idInput, boton) {
    const input = document.getElementById(idInput);
    const icono = boton.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icono.classList.remove('bi-eye-fill');
        icono.classList.add('bi-eye-slash-fill');
    } else {
        input.type = 'password';
        icono.classList.remove('bi-eye-slash-fill');
        icono.classList.add('bi-eye-fill');
    }
}

/**
 * Intercepta la disponibilidad del correo electrónico ingresado en tiempo real
 */
document.getElementById('correo_perfil').addEventListener('input', function() {
    const correo = this.value.trim();
    const inputCorreo = this;
    const feedback = document.getElementById('correo-feedback');
    const btnGuardar = document.querySelector('#formPerfilUsuario button[type="submit"]');

    if (correo === '') {
        inputCorreo.classList.remove('is-invalid', 'is-valid');
        btnGuardar.disabled = false;
        return;
    }

    $.ajax({
        url: 'actions/verificar_correo_disponible.php',
        type: 'GET',
        data: { 
            correo: correo, 
            id_excluir: <?= (int)$id_usuario_sesion ?> 
        },
        dataType: 'json',
        success: function(response) {
            if (response.disponible === false) {
                inputCorreo.classList.add('is-invalid');
                inputCorreo.classList.remove('is-valid');
                feedback.innerText = 'Este correo electrónico ya pertenece a otro miembro de DEMEX.';
                btnGuardar.disabled = true;
            } else {
                inputCorreo.classList.remove('is-invalid');
                inputCorreo.classList.add('is-valid');
                btnGuardar.disabled = false;
            }
        },
        error: function() {
            console.log('Error al validar la disponibilidad del correo.');
        }
    });
});

/**
 * Captura el envío del formulario de información básica
 */
document.getElementById('formPerfilUsuario').addEventListener('submit', function(e) {
    e.preventDefault(); 

    const formulario = this;
    const datosFormulario = new FormData(formulario);

    fetch(formulario.action, {
        method: formulario.method,
        body: datosFormulario
    })
    .then(respuesta => {
        if (!respuesta.ok) {
            throw new Error('Error en la comunicación de red con el servidor.');
        }
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
                window.location.reload();
            }
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Falla Operativa',
            text: error.message,
            confirmButtonColor: '#C62828'
        });
    });
});

/**
 * INTERCEPTOR ASÍNCRONO PARA EL CAMBIO DE CONTRASEÑA (FETCH API)
 */
document.getElementById('formPasswordUsuario').addEventListener('submit', function(e) {
    e.preventDefault();

    const formulario = this;
    const nuevaPassword = document.getElementById('nueva_password').value;
    const confirmarPassword = document.getElementById('confirmar_password').value;
    const inputConfirmar = document.getElementById('confirmar_password');

    // Validación intermedia de coincidencia del lado del cliente
    if (nuevaPassword !== confirmarPassword) {
        inputConfirmar.classList.add('is-invalid');
        return false;
    }
    inputConfirmar.classList.remove('is-invalid');

    // Validación intermedia de longitud mínima
    if (nuevaPassword.length < 8) {
        Swal.fire({
            icon: 'warning',
            title: 'Contraseña Débil',
            text: 'La nueva contraseña debe contener al menos 8 caracteres para asegurar tu cuenta.',
            confirmButtonColor: '#C62828'
        });
        return false;
    }

    const datosFormulario = new FormData(formulario);

    fetch(formulario.action, {
        method: formulario.method,
        body: datosFormulario
    })
    .then(respuesta => {
        if (!respuesta.ok) {
            throw new Error('Error al procesar el cambio de seguridad en el servidor.');
        }
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
                formulario.reset(); 
            }
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Falla Operativa',
            text: error.message,
            confirmButtonColor: '#C62828'
        });
    });
});

// Limpia el estado de error de coincidencia mientras el usuario vuelve a escribir
document.getElementById('confirmar_password').addEventListener('input', function() {
    this.classList.remove('is-invalid');
});

function procesarYComprimirFoto(input) {
    if (input.files && input.files[0]) {
        const archivo = input.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const img = new Image();
            img.src = e.target.result;

            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                const MAX_ANCHO = 500;
                const MAX_ALTO = 500;
                let ancho = img.width;
                let alto = img.height;

                if (ancho > alto) {
                    if (ancho > MAX_ANCHO) {
                        alto *= MAX_ANCHO / ancho;
                        ancho = MAX_ANCHO;
                    }
                } else {
                    if (alto > MAX_ALTO) {
                        ancho *= MAX_ALTO / alto;
                        alto = MAX_ALTO;
                    }
                }

                canvas.width = ancho;
                canvas.height = alto;
                ctx.drawImage(img, 0, 0, ancho, alto);

                const dataURL = canvas.toDataURL('image/jpeg', 0.85);

                const preview = document.getElementById('avatar-preview');
                const inicial = document.getElementById('avatar-inicial');
                const btnBorrar = document.getElementById('btn-borrar-foto');

                preview.src = dataURL;
                preview.classList.remove('d-none');
                if (inicial) inicial.classList.add('d-none');
                btnBorrar.classList.remove('d-none');

                document.getElementById('foto_comprimida_base64').value = dataURL.replace(/^data:image\/(png|jpeg|jpg);base64,/, "");
                document.getElementById('eliminar_foto').value = "0";
            };
        };
        reader.readAsDataURL(archivo);
    }
}

function marcarEliminacionFoto() {
    const preview = document.getElementById('avatar-preview');
    const inicial = document.getElementById('avatar-inicial');
    const btnBorrar = document.getElementById('btn-borrar-foto');
    const inputFile = document.getElementById('foto_perfil');
    
    inputFile.value = "";
    document.getElementById('foto_comprimida_base64').value = "";
    document.getElementById('eliminar_foto').value = "1";
    
    if (preview) preview.classList.add('d-none');
    
    if (inicial) {
        inicial.classList.remove('d-none');
    } else {
        const contenedor = preview.parentNode;
        const divInicial = document.createElement('div');
        divInicial.id = 'avatar-inicial';
        divInicial.className = 'bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow';
        divInicial.style.width = '100px';
        divInicial.style.height = '100px';
        divInicial.style.fontSize = '38px';
        divInicial.innerText = '<?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>';
        contenedor.insertBefore(divInicial, preview);
    }
    btnBorrar.classList.add('d-none');
}
</script>

<?php require_once 'includes/footer.php'; ?>