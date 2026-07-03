<?php
/**
 * @file verificar.php
 * @package Portal_Demex
 * @brief Vista y controlador para el establecimiento de contraseña y activación de cuenta.
 */

require_once 'config/db.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    die("Token de verificación ausente o inválido.");
}

// 1. Validar si el token corresponde a un usuario pendiente
$stmt = $pdo->prepare("SELECT id_usuario, nombre, correo FROM usuarios WHERE token_verificacion = ? AND estatus = 0 LIMIT 1");
$stmt->execute([$token]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("El enlace de activación expiró, es inválido o la cuenta ya fue activada.");
}

$error_msg = "";

// 2. Si el usuario envía la contraseña elegida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if (empty($pass1) || empty($pass2)) {
        $error_msg = "Todos los campos de contraseña son obligatorios.";
    } elseif (strlen($pass1) < 8) {
        $error_msg = "La contraseña debe tener al menos 8 caracteres por seguridad.";
    } elseif ($pass1 !== $pass2) {
        $error_msg = "Las contraseñas ingresadas no coinciden.";
    } else {
        try {
            $pdo->beginTransaction();

            // Generamos el hash criptográfico robusto de la contraseña real del usuario
            $passwordHash = password_hash($pass1, PASSWORD_BCRYPT, ['cost' => 12]);

            // Activamos la cuenta, guardamos la contraseña y removemos el token
            $update = $pdo->prepare("UPDATE usuarios SET password = ?, estatus = 1, token_verificacion = NULL WHERE id_usuario = ?");
            $update->execute([$passwordHash, $usuario['id_usuario']]);

            $pdo->commit();

            // TRUCO DE SEGURIDAD: Destruimos cualquier rastro de la sesión del Admin en este navegador
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            // Renderizamos un aviso estético de éxito y redirigimos al login de forma limpia
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Cuenta Activada | DEMEX</title>
                <link rel="icon" type="image/png" href="img/demex_icon.png">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
                <style>body { font-family: 'Poppins', sans-serif; }</style>
            </head>
            <body class="bg-light">
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Cuenta Activada Exitosamente!',
                        text: 'Tu contraseña ha sido registrada. Por seguridad, ingresa tus nuevas credenciales para acceder.',
                        confirmButtonColor: '#d15b00'
                    }).then(() => { 
                        // Redirigimos al login informando el estatus para mostrar un banner verde opcional
                        window.location.href = 'login.php?status=cuenta_activada'; 
                    });
                </script>
            </body>
            </html>
            <?php
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Error al activar la cuenta: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/demex_icon.png">
    <title>Configurar Contraseña | DEMEX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { 
            background-color: #F8F9FA; 
            font-family: 'Poppins', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
        }
        .activation-card { 
            background: #ffffff; 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 8px 30px rgba(0,0,0,0.08); 
            padding: 3rem 2rem; 
            max-width: 480px; 
            width: 90%;
            border-top: 5px solid #d15b00; /* Unificado al color corporativo */
        }
        .btn-demex-act {
            background-color: #d15b00; 
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-demex-act:hover {
            background-color: #b85000;
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<div class="activation-card">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark mb-1">Configura tu Contraseña</h4>
        <p class="text-muted small">Hola <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>, asigna las credenciales de seguridad para tu correo corporativo: <br><span class="text-secondary"><?= htmlspecialchars($usuario['correo']) ?></span></p>
    </div>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger border-0 small py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <form action="verificar.php" method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label for="password" class="form-label small fw-bold text-muted text-uppercase">Nueva Contraseña</label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                <button class="input-group-text bg-white border-start-0 toggle-password" type="button" data-target="#password" style="border-color: #dee2e6;">
                    <i class="bi bi-eye text-muted"></i>
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label for="password_confirm" class="form-label small fw-bold text-muted text-uppercase">Confirmar Contraseña</label>
            <div class="input-group">
                <input type="password" name="password_confirm" id="password_confirm" class="form-control" placeholder="Repite tu contraseña" required>
                <button class="input-group-text bg-white border-start-0 toggle-password" type="button" data-target="#password_confirm" style="border-color: #dee2e6;">
                    <i class="bi bi-eye text-muted"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-demex-act w-100 py-3 rounded-pill fw-bold shadow-sm">
            Activar Perfil e Ir al Inicio
        </button>
    </form>
</div>

<script>
    // Limpieza sintáctica de URL
    if (typeof window.history.replaceState === 'function') {
        const limpiaUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({ path: limpiaUrl }, '', limpiaUrl);
    }

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