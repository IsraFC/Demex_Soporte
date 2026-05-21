<?php
/**
 * @file login.php
 * @package Portal_Demex
 * @brief Interfaz gráfica y control de redirección previa para el inicio de sesión.
 * * Valida si el cliente/usuario ya posee un token de sesión activo (Cookie de Sesión) 
 * y lo redirige dinámicamente a su módulo correspondiente sin requerir reautenticación.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Evaluación de la persistencia de sesión (Pulsera VIP).
 * Si el rol está definido, el usuario ya se encuentra autenticado dentro del ecosistema.
 */
if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte') {
        header("Location: Soporte/index.php");
        exit();
    } elseif ($_SESSION['rol'] === 'cliente') {
        header("Location: vista_cliente.php");
        exit();
    }
}
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
            border-top: 5px solid #C62828; /* Línea de identidad corporativa DEMEX */
        }
    </style>
</head>
<body>

<div class="card-main login-card shadow-lg text-center">
    <div class="mb-4">
        <img src="Soporte/img/logo_demex.png" alt="Desarrollo Mexicano S.A. de C.V." width="220" class="filter-drop-shadow">
    </div>
    
    <h4 class="fw-bold text-dark mb-1">Control de Acceso</h4>
    <p class="text-muted small mb-4">Ingresa tus credenciales para acceder al portal corporativo</p>

    /**
     * Renderizado Condicional de Errores (Mensajes de retroalimentación basados en parámetros GET).
     */
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 small py-2 text-start" role="alert">
            <?php 
                switch ($_GET['error']) {
                    case 'datos_incorrectos':
                        echo "El correo electrónico o la contraseña ingresados no coinciden con nuestros registros.";
                        break;
                    case 'no_autorizado':
                        echo "Acceso denegado. Se requieren credenciales activas para visualizar este módulo.";
                        break;
                    default:
                        echo "Ocurrió una anomalía de seguridad en el servidor. Por favor, reintente.";
                        break;
                }
            ?>
        </div>
    <?php endif; ?>

    <form action="procesar_login.php" method="POST" class="text-start">
        <div class="mb-3">
            <label for="correo" class="form-label small fw-bold text-muted text-uppercase">Correo Electrónico</label>
            <input type="email" name="correo" id="correo" class="form-control" placeholder="ejemplo@demex.com" required autocomplete="email">
        </div>

        <div class="mb-4">
            <label for="password" class="form-label small fw-bold text-muted text-uppercase">Contraseña</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-demex w-100 py-3 rounded-pill shadow-sm">
            Entrar al Sistema
        </button>
    </form>
</div>

</body>
</html>