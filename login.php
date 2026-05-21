<?php
/**
 * @file login.php
 * @package Portal_Demex
 * @brief Interfaz gráfica con validación específica de errores y limpieza sintáctica de URL.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
// Una vez leído, lo eliminamos de la sesión para que no se quede estancado para siempre
unset($_SESSION['old_correo']);

// Capturamos el tipo de error
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal DEMEX | Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Soporte/css/estilos.css">
    <style>
        body { 
            background-color: #F8F9FA; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
            font-family: 'Poppins', sans-serif;
        }
        .login-card { 
            max-width: 450px; 
            width: 90%; 
            border-top: 5px solid #C62828; 
        }
    </style>
</head>
<body>

<div class="card-main login-card shadow-lg text-center">
    <div class="mb-4">
        <img src="Soporte/img/logo_demex.png" alt="Desarrollo Mexicano" width="220" class="filter-drop-shadow">
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
                    default:
                        echo "Ocurrió una anomalía de seguridad en el servidor.";
                        break;
                }
            ?>
        </div>
    <?php endif; ?>

    <form action="procesar_login.php" method="POST" class="text-start">
        <div class="mb-3">
            <label for="correo" class="form-label small fw-bold text-muted text-uppercase">Correo Electrónico</label>
            <input type="email" name="correo" id="correo" 
                   class="form-control <?php echo ($error === 'usuario_no_encontrado' || $error === 'correo_invalido') ? 'is-invalid' : ''; ?>" 
                   placeholder="Correo..." 
                   value="<?php echo htmlspecialchars($old_correo); ?>" 
                   required autocomplete="email">
        </div>

        <div class="mb-4">
            <label for="password" class="form-label small fw-bold text-muted text-uppercase">Contraseña</label>
            <input type="password" name="password" id="password" 
                   class="form-control <?php echo ($error === 'password_incorrecto') ? 'is-invalid' : ''; ?>" 
                   placeholder="Contraseña..." 
                   required>
        </div>

        <button type="submit" class="btn-demex w-100 py-3 rounded-pill shadow-sm">
            Entrar al Sistema
        </button>
    </form>
</div>

<script>
    if (typeof window.history.replaceState === 'function') {
        // Reemplaza la URL actual quitándole el "?error=..." de forma silenciosa
        const limpiaUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({ path: limpiaUrl }, '', limpiaUrl);
    }
</script>

</body>
</html>