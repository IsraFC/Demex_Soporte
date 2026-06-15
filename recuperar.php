<?php
/**
 * @file recuperar.php
 * @package Portal_Demex
 * @brief Interfaz gráfica para que el usuario solicite el restablecimiento de su contraseña.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya tiene sesión activa, lo redirigimos a su módulo correspondiente
if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte') {
        header("Location: Soporte/index.php");
        exit();
    } elseif ($_SESSION['rol'] === 'cliente') {
        header("Location: vista_cliente.php");
        exit();
    }
}

$error = $_GET['error'] ?? '';
$status = $_GET['status'] ?? '';
$old_correo = $_SESSION['old_correo'] ?? '';
unset($_SESSION['old_correo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal DEMEX | Recuperar Contraseña</title>
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
    
    <h4 class="fw-bold text-dark mb-1">¿Olvidaste tu contraseña?</h4>
    <p class="text-muted small mb-4">Introduce tu correo electrónico y te enviaremos un enlace seguro para restablecerla.</p>

    <!-- ALERTAS DE ERROR -->
    <?php if ($error): ?>
        <div class="alert alert-danger border-0 small py-2 text-start" role="alert">
            <?php 
                switch ($error) {
                    case 'usuario_no_encontrado':
                        echo "El correo electrónico ingresado no coincide con ninguna cuenta activa.";
                        break;
                    case 'correo_invalido':
                        echo "El formato del correo electrónico ingresado no es válido.";
                        break;
                    case 'envio_fallido':
                        echo "No se pudo enviar el correo de recuperación. Por favor, contacta a soporte técnico.";
                        break;
                    default:
                        echo "Ocurrió una anomalía en el servidor. Intenta más tarde.";
                        break;
                }
            ?>
        </div>
    <?php endif; ?>

    <!-- ALERTA DE ÉXITO -->
    <?php if ($status === 'enviado'): ?>
        <div class="alert alert-success border-0 small py-2 text-start" role="alert">
            <strong>Enlace enviado:</strong> Hemos mandado las instrucciones de recuperación a tu bandeja de entrada. Revisa tu correo.
        </div>
    <?php endif; ?>

    <form action="actions/procesar_recuperacion.php" method="POST" class="text-start">
        <div class="mb-4">
            <label for="correo" class="form-label small fw-bold text-muted text-uppercase">Correo Electrónico</label>
            <input type="email" name="correo" id="correo" 
                   class="form-control <?php echo ($error === 'usuario_no_encontrado' || $error === 'correo_invalido') ? 'is-invalid' : ''; ?>" 
                   placeholder="ejemplo@demex.com" 
                   value="<?php echo htmlspecialchars($old_correo); ?>" 
                   required autocomplete="email">
        </div>

        <button type="submit" class="btn-demex w-100 py-3 rounded-pill shadow-sm mb-3">
            Enviar Enlace de Recuperación
        </button>

        <div class="text-center">
            <a href="login.php" class="text-decoration-none small text-mutedfw-bold"><i class="bi bi-arrow-left me-1"></i> Regresar al Inicio de Sesión</a>
        </div>
    </form>
</div>

<script>
    // Limpieza sintáctica de URL para remover parámetros molestos al recargar
    if (typeof window.history.replaceState === 'function') {
        const limpiaUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({ path: limpiaUrl }, '', limpiaUrl);
    }
</script>

</body>
</html>