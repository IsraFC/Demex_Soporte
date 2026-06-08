<?php
/**
 * @file perfil.php
 * @package Portal_Demex
 * @version 1.5 - Mi Perfil con Compresión Perimetral en Cliente
 * @date 2026-06-08
 * @brief Interfaz de autogestión de usuario con pre-procesamiento y reducción de imágenes antes de su envío.
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
                            <input type="email" name="correo" class="form-control rounded-3" value="<?= htmlspecialchars($usuario['correo']) ?>" required>
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
</div>

<script>
/**
 * Captura el envío del formulario de forma asíncrona para procesar datos 
 * en segundo plano y renderizar alertas sin redirección de interfaz.
 */
document.getElementById('formPerfilUsuario').addEventListener('submit', function(e) {
    e.preventDefault(); // Detiene el viaje físico del navegador a /actions/procesar_perfil.php

    const formulario = this;
    const datosFormulario = new FormData(formulario);

    // Despachamos la petición asíncrona mediante Fetch API
    fetch(formulario.action, {
        method: formulario.method,
        body: datosFormulario
    })
    .then(respuesta => {
        if (!respuesta.ok) {
            throw new Error('Error en la comunicación de red con el servidor.');
        }
        return respuesta.json(); // Convierte la respuesta JSON pura del backend a objeto JS
    })
    .then(data => {
        // Dispara la alerta correspondiente basada en la respuesta del backend
        Swal.fire({
            icon: data.status, // Recibe 'success', 'warning' o 'error'
            title: data.title,
            text: data.text,
            confirmButtonColor: data.status === 'success' ? '#d15b00' : '#C62828'
        }).then(() => {
            if (data.status === 'success') {
                // Al dar clic en OK, recargamos la página actual para refrescar el layout y la navbar
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