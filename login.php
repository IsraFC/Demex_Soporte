<?php
/**
 * @file login.php
 * @package Portal_Demex
 * @brief Interfaz gráfica con validación específica de errores y mitigación precisa de fuerza bruta.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conectamos a la base de datos centralizada
require_once 'config/db.php';

if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte') {
        header("Location: Soporte/index.php");
        exit();
    } elseif ($_SESSION['rol'] === 'cliente') {
        header("Location: vista_cliente.php");
        exit();
    }
}

// Recuperamos el correo viejo si es que existió un error
$old_correo = $_SESSION['old_correo'] ?? '';
unset($_SESSION['old_correo']);

// Capturamos el tipo de error originalmente enviado
$error = $_GET['error'] ?? '';

$bloqueado = false;
$minutos_restantes = 0;

// NUEVO: Verificación estricta en Base de Datos por Cuenta
if (!empty($old_correo)) {
    try {
        $stmt = $pdo->prepare("SELECT bloqueado_hasta FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->execute([$old_correo]);
        $checkUser = $stmt->fetch();

        if ($checkUser && !empty($checkUser['bloqueado_hasta'])) {
            $tiempo_bloqueo = strtotime($checkUser['bloqueado_hasta']);
            if (time() < $tiempo_bloqueo) {
                $bloqueado = true;
                $minutos_restantes = ceil(($tiempo_bloqueo - time()) / 60);
                $error = 'cuenta_bloqueada'; 
            }
        }
    } catch (\Exception $e) {
        // Silencioso en desarrollo para evitar romper el renderizado visual
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal DEMEX | Control de Acceso</title>
    <link rel="icon" type="image/png" href="img/demex_icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/estilos.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="login-body">

<div class="card-main login-card shadow-lg text-center">
    <div class="mb-4 d-flex justify-content-center align-items-center">
        <div class="logo-login-container">
            <img src="img/logo_demex.png" alt="Desarrollo Mexicano" width="220" class="logo-highlight">
        </div>
    </div>
    
    <h4 class="fw-bold text-dark mb-1">Control de Acceso</h4>
    <p class="text-muted small mb-4">Ingresa tus credenciales para acceder al portal corporativo</p>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 small py-2 text-start" role="alert">
            <?php 
                switch ($error) {
                    case 'usuario_no_encontrado':
                        echo "El correo electrónico ingresado no está registrado en el sistema.";
                        break;
                    case 'password_incorrecto':
                        echo "La contraseña ingresada es incorrecta. Por favor, verifícala.";
                        break;
                    case 'correo_invalido':
                        echo "El formato del correo electrónico no es válido.";
                        break;
                    case 'no_autorizado':
                        echo "Acceso denegado. Se requieren credenciales activas.";
                        break;
                    case 'sesion_expirada':
                        echo "<strong>Sesión cerrada:</strong> Tu sesión ha expirado por inactividad para proteger los datos de la empresa. Por favor, vuelve a ingresar.";
                        break;
                    case 'cuenta_bloqueada':
                        echo "<strong>Acceso restringido:</strong> Demasiados intentos fallidos. Por seguridad, esta cuenta se ha bloqueado de forma temporal. Intenta de nuevo en <strong>{$minutos_restantes} minuto(s)</strong>.";
                        break;
                    default:
                        echo "Ocurrió una anomalía de seguridad en el servidor.";
                        break;
                }
            ?>
        </div>
    <?php endif; ?>

    <!-- ALERTA DE ÉXITO EXCLUSIVA PARA RECUPERACIÓN DE CLAVE -->
    <?php if (isset($_GET['status']) && $_GET['status'] === 'password_actualizada'): ?>
        <div class="alert alert-success border-0 small py-2 text-start" role="alert">
            <strong>¡Contraseña actualizada!</strong> Tu nueva contraseña ha sido registrada con éxito. Ya puedes ingresar al portal corporativo.
        </div>
    <?php endif; ?>

    <form action="actions/procesar_login.php" method="POST" class="text-start">
        <div class="mb-3">
            <label for="correo" class="form-label small fw-bold text-muted text-uppercase">Correo Electrónico</label>
            <input type="email" name="correo" id="correo" 
                   class="form-control <?php echo ($error === 'usuario_no_encontrado' || $error === 'correo_invalido' || $error === 'cuenta_bloqueada') ? 'is-invalid' : ''; ?>" 
                   placeholder="Correo..." 
                   value="<?php echo htmlspecialchars($old_correo); ?>" 
                   required autocomplete="email">
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label small fw-bold text-muted text-uppercase mb-0">Contraseña</label>
                <a href="recuperar.php" class="text-decoration-none small text-muted fw-semibold" style="font-size: 0.75rem; color: #d15b00 !important;">¿Olvidaste tu contraseña?</a>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" 
                    class="form-control <?php echo ($error === 'password_incorrecto') ? 'is-invalid' : ''; ?>" 
                    placeholder="Contraseña..." <?php echo $bloqueado ? 'disabled' : ''; ?> required>
                <button class="input-group-text bg-white border-start-0 toggle-password" tabindex="-1" type="button" data-target="#password" style="border-color: #dee2e6;">
                    <i class="bi bi-eye text-muted"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-demex w-100 py-3 rounded-pill shadow-sm" <?php echo $bloqueado ? 'disabled' : ''; ?>>
            Entrar al Sistema
        </button>
    </form>
</div>

<script>
$(document).ready(function() {
    // 1. Script existente para limpiar la URL de errores molestos al recargar
    if (typeof window.history.replaceState === 'function') {
        const limpiaUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({ path: limpiaUrl }, '', limpiaUrl);
    }

    // 2. Alternador de contraseñas (El ojito en JS Puro adaptado)
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

    // 3. NUEVO: Validación asíncrona por cuenta en tiempo real al cambiar de correo
    $('#correo').on('input', function() {
        const correo = $(this).val().trim();
        const inputPassword = $('#password');
        const btnSubmit = $('.btn-demex');
        
        // Expresión regular para validar formato de correo antes de molestar a la base de datos
        const regexCorreo = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;

        if (correo === '' || !regexCorreo.test(correo)) {
            // Si limpian el input o no es un correo válido aún, liberamos los campos por defecto
            inputPassword.prop('disabled', false);
            btnSubmit.prop('disabled', false);
            $('.alert-danger').hide(); 
            return;
        }

        $.ajax({
            url: 'actions/verificar_bloqueo_cuenta.php',
            type: 'GET',
            data: { correo: correo },
            dataType: 'json',
            success: function(response) {
                if (response.bloqueado === true) {
                    // Si el correo escrito está congelado en MySQL, bloqueamos la interacción al instante
                    inputPassword.prop('disabled', true).addClass('is-invalid');
                    btnSubmit.prop('disabled', true);
                    
                    // Si no existe el contenedor de alertas de Bootstrap en la pantalla, lo inyectamos dinámicamente
                    if ($('.alert-danger').length === 0) {
                        $('.card-main h4').after('<div class="alert alert-danger border-0 small py-2 text-start" role="alert"></div>');
                    }
                    
                    $('.alert-danger').html('<strong>Acceso restringido:</strong> Demasiados intentos fallidos. Por seguridad, esta cuenta se ha bloqueado de forma temporal. Intenta de nuevo en <strong>' + response.minutos + ' minuto(s)</strong>.').show();
                } else {
                    // Si la cuenta está limpia y libre, reactivamos todo inmediatamente
                    inputPassword.prop('disabled', false).removeClass('is-invalid');
                    btnSubmit.prop('disabled', false);
                    $('.alert-danger').hide();
                }
            },
            error: function() {
                console.log('Error al consultar el estado de bloqueo en el servidor.');
            }
        });
    });
});
</script>

</body>
</html>