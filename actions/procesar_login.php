<?php
/**
 * @file procesar_login.php
 * @package Portal_Demex
 * @version 1.7 - Protección de Fuerza Bruta por Cuenta (Base de Datos)
 * @brief Controlador encargado de validar y proteger la autenticación de forma estricta por usuario.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$correo_crudo = $_POST['correo'] ?? '';
$password     = $_POST['password'] ?? '';

// Sanitización del lado del servidor
$correo = filter_var(trim($correo_crudo), FILTER_SANITIZE_EMAIL);
$_SESSION['old_correo'] = $correo_crudo;

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../login.php?error=correo_invalido");
    exit();
}

try {
    // 1. Buscamos al usuario (Traemos también las nuevas columnas de control)
    $sql = "SELECT id_usuario, nombre, apellidos, correo, password, rol, estatus, intentos_fallidos, bloqueado_hasta 
            FROM usuarios 
            WHERE correo = ? AND estatus = 1 
            LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    if (!$user) {
        // Si el correo no existe, no hacemos nada en la BD para evitar enumeración, solo rebotamos
        header("Location: ../login.php?error=usuario_no_encontrado");
        exit();
    }

    // 2. Verificar si la CUENTA está bloqueada en la Base de Datos
    if (!empty($user['bloqueado_hasta'])) {
        $tiempo_bloqueo = strtotime($user['bloqueado_hasta']);
        if (time() < $tiempo_bloqueo) {
            // Guardamos el timestamp en la sesión SOLO para que login.php sepa calcular los minutos restantes en pantalla
            $_SESSION['bloqueo_hasta'] = $tiempo_bloqueo;
            header("Location: ../login.php?error=cuenta_bloqueada");
            exit();
        }
    }

    // 3. Evaluar la contraseña criptográfica
    if (password_verify($password, $user['password'])) {
        
        // LOGIN EXITOSO: Reseteamos los intentos fallidos y el bloqueo en la Base de Datos
        $reset_sql = "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?";
        $reset_stmt = $pdo->prepare($reset_sql);
        $reset_stmt->execute([$user['id_usuario']]);

        // Limpiamos variables de sesión temporales
        unset($_SESSION['old_correo']);
        unset($_SESSION['bloqueo_hasta']);

        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nombre']     = $user['nombre'];
        $_SESSION['apellidos']  = $user['apellidos'];
        $_SESSION['correo']     = $user['correo'];
        $_SESSION['rol']        = $user['rol'];

        // Redirección por roles
        switch ($user['rol']) {
            case 'administrador':
            case 'soporte':
                header("Location: ../Soporte/index.php");
                exit();
            case 'ventas':
                header("Location: ../Ventas/index.php");
                exit();
            default:
                header("Location: ../vista_cliente.php");
                exit();
        }

    } else {
        // CONTRASEÑA INCORRECTA: Aumentamos el contador en la Base de Datos
        $nuevos_intentos = $user['intentos_fallidos'] + 1;
        $bloqueo_fecha = null;

        if ($nuevos_intentos >= 5) {
            // Generamos la fecha/hora actual mas 15 minutos en formato MySQL
            $bloqueo_fecha = date('Y-m-d H:i:s', time() + (15 * 60));
            $_SESSION['bloqueo_hasta'] = time() + (15 * 60); // Para la visualización inmediata del frontend
        }

        $update_sql = "UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id_usuario = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$nuevos_intentos, $bloqueo_fecha, $user['id_usuario']]);

        header("Location: ../login.php?error=password_incorrecto");
        exit();
    }

} catch (\Exception $e) {
    header("Location: ../login.php?error=fatal");
    exit();
}