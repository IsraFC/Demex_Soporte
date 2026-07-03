<?php
/**
 * @file restablecer.php
 * @package Portal_Demex
 * @brief Interfaz de cambio de clave que valida tokens asíncronos y procesa la actualización de credenciales.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';

$token = $_GET['token'] ?? '';
$error = $_GET['error'] ?? '';

$token_valido = false;
$id_usuario_valido = null;

// 1. VALIDACIÓN ESTRICTA DEL TOKEN DE RECUPERACIÓN
if (!empty($token)) {
    try {
        // Buscamos al usuario que tenga el token activo
        $stmt = $pdo->prepare("SELECT id_usuario, token_expira FROM usuarios WHERE token_recuperacion = ? AND estatus = 1 LIMIT 1");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $ahora = time();
            $expiracion = strtotime($usuario['token_expira']);

            // Validamos si la hora actual sigue dentro del rango permitido (1 hora)
            if ($ahora <= $expiracion) {
                $token_valido = true;
                $id_usuario_valido = $usuario['id_usuario'];
            } else {
                $error = 'token_expirado';
            }
        } else {
            $error = 'token_invalido';
        }
    } catch (\Exception $e) {
        $error = 'error_servidor';
    }
} else {
    $error = 'sin_token';
}

// 2. PROCESAR EL CAMBIO DE CONTRASEÑA (Cuando se envía el formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $nueva_password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    if (empty($nueva_password) || strlen($nueva_password) < 8) {
        header("Location: restablecer.php?token=" . $token . "&error=password_corta");
        exit();
    }

    if ($nueva_password !== $confirmar_password) {
        header("Location: restablecer.php?token=" . $token . "&error=no_coinciden");
        exit();
    }

    try {
        // Encriptamos la nueva contraseña de forma segura
        $password_encriptada = password_hash($nueva_password, PASSWORD_BCRYPT);

        // Actualizamos las credenciales y QUEMAMOS el token (poniéndolo en NULL) por seguridad
        $updateStmt = $pdo->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL, token_expira = NULL, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?");
        $updateStmt->execute([$password_encriptada, $id_usuario_valido]);

        // Redirigimos al login informando el éxito total
        header("Location: login.php?status=password_actualizada");
        exit();

    } catch (\Exception $e) {
        header("Location: restablecer.php?token=" . $token . "&error=error_actualizacion");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal DEMEX | Reestablecer Contraseña</title>
    <link rel="icon" type="image/png" href="img/demex_icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="login-body">

<div class="card-main login-card shadow-lg text-center">
    <div class="mb-4 d-flex justify-content-center align-items-center">
        <div class="logo-login-container">
            <img src="img/logo_demex.png" alt="Desarrollo Mexicano" width="220" class="logo-highlight">
        </div>
    </div>
    
    <h4 class="fw-bold text-dark mb-1">Actualizar Contraseña</h4>
    <p class="text-muted small mb-4">Ingresa tu nueva clave de acceso corporativo.</p>

    <!-- ALERTAS DE ERROR -->
    <?php if ($error): ?>
        <div class="alert alert-danger border-0 small py-2 text-start" role="alert">
            <?php 
                switch ($error) {
                    case 'token_expirado':
                        echo "<strong>Enlace vencido:</strong> El token de seguridad ha expirado porque superó el límite de 1 hora. Solicita uno nuevo.";
                        break;
                    case 'token_invalido':
                        echo "<strong>Acceso corrupto:</strong> El enlace de recuperación es inválido o ya fue utilizado previamente.";
                        break;
                    case 'password_corta':
                        echo "La contraseña debe tener una longitud mínima de <strong>8 caracteres</strong>.";
                        break;
                    case 'no_coinciden':
                        echo "Las contraseñas ingresadas no coinciden. Por favor, verifícalas.";
                        break;
                    case 'sin_token':
                        echo "Falta el token criptográfico para autorizar esta operación.";
                        break;
                    default:
                        echo "Ocurrió un problema en el servidor. Intenta de nuevo.";
                        break;
                }
            ?>
        </div>
        <?php if (!$token_valido): ?>
            <a href="recuperar.php" class="btn btn-outline-secondary rounded-pill w-100 py-2 mt-2 btn-sm">Solicitar nueva recuperación</a>
        <?php endif; ?>
    <?php endif; ?>

    <!-- FORMULARIO DE ACTUALIZACIÓN (Solo si el token es 100% legal) -->
    <?php if ($token_valido): ?>
        <form action="restablecer.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="text-start">
            
            <div class="mb-3">
                <label for="password" class="form-label small fw-bold text-muted text-uppercase">Nueva Contraseña</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Mínimo 8 caracteres..." required minlength="8">
                    <button class="input-group-text bg-white border-start-0 toggle-password" type="button" data-target="#password" style="border-color: #dee2e6;">
                        <i class="bi bi-eye text-muted"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirmar_password" class="form-label small fw-bold text-muted text-uppercase">Confirmar Nueva Contraseña</label>
                <div class="input-group">
                    <input type="password" name="confirmar_password" id="confirmar_password" class="form-control" placeholder="Repite la contraseña..." required minlength="8">
                    <button class="input-group-text bg-white border-start-0 toggle-password" type="button" data-target="#confirmar_password" style="border-color: #dee2e6;">
                        <i class="bi bi-eye text-muted"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-demex w-100 py-3 rounded-pill shadow-sm">
                Guardar Cambios e Iniciar Sesión
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
    // Alternador dinámico de contraseñas (Ojito)
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetSelector = this.getAttribute('data-target');
            const input = document.querySelector(targetSelector);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
</script>

</body>
</html>