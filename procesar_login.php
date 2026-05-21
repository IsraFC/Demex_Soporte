<?php
/**
 * @file procesar_login.php
 * @package Portal_Demex
 * @brief Controlador del Backend encargado de validar y procesar la autenticación.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$correo_crudo = $_POST['correo'] ?? '';
$password     = $_POST['password'] ?? '';

// Sanitización del lado del servidor
$correo = filter_var(trim($correo_crudo), FILTER_SANITIZE_EMAIL);

// Guardamos el correo en la sesión temporalmente por si hay error y queremos mantenerlo en el input
$_SESSION['old_correo'] = $correo_crudo;

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: login.php?error=correo_invalido");
    exit();
}

try {
    /**
     * Buscamos al usuario ÚNICAMENTE por correo para determinar si existe en el padrón activo.
     */
    $sql = "SELECT id_usuario, nombre, apellidos, correo, password, rol, estatus 
            FROM usuarios 
            WHERE correo = ? AND estatus = 1 
            LIMIT 1";
            
    $stmt = $pdo_portal->prepare($sql);
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    if (!$user) {
        // ERROR: El correo electrónico no existe en la base de datos
        header("Location: login.php?error=usuario_no_encontrado");
        exit();
    }

    /**
     * Si el usuario existe, pasamos a evaluar de forma aislada la firma criptográfica.
     */
    if (password_verify($password, $user['password'])) {
        
        // Autenticación Exitosa: Limpiamos la papelera de inputs viejos
        unset($_SESSION['old_correo']);

        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nombre']     = $user['nombre'];
        $_SESSION['apellidos']  = $user['apellidos'];
        $_SESSION['correo']     = $user['correo'];
        $_SESSION['rol']        = $user['rol'];

        if ($user['rol'] === 'administrador' || $user['rol'] === 'soporte') {
            header("Location: Soporte/index.php");
            exit();
        } else {
            header("Location: vista_cliente.php");
            exit();
        }

    } else {
        // ERROR: El usuario existe pero la contraseña no hizo match matemático
        header("Location: login.php?error=password_incorrecto");
        exit();
    }

} catch (\Exception $e) {
    header("Location: login.php?error=fatal");
    exit();
}