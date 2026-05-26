<?php
/**
 * @file procesar_login.php
 * @package Portal_Demex
 * @version 1.5 - Enrutador Global Adaptativo por Roles
 * @brief Controlador del Backend encargado de validar y procesar la autenticación en la raíz.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Se conecta a la nueva ruta centralizada de la base de datos
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$correo_crudo = $_POST['correo'] ?? '';
$password     = $_POST['password'] ?? '';

// Sanitización del lado del servidor
$correo = filter_var(trim($correo_crudo), FILTER_SANITIZE_EMAIL);

// Guardamos el correo en la sesión temporalmente por si hay error
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
            
    // 2. Se actualiza la variable a $pdo (la configurada en config/db.php)
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: login.php?error=usuario_no_encontrado");
        exit();
    }

    /**
     * Si el usuario existe, pasamos a evaluar la firma criptográfica.
     */
    if (password_verify($password, $user['password'])) {
        
        // Autenticación Exitosa: Limpiamos los inputs viejos
        unset($_SESSION['old_correo']);

        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nombre']     = $user['nombre'];
        $_SESSION['apellidos']  = $user['apellidos'];
        $_SESSION['correo']     = $user['correo'];
        $_SESSION['rol']        = $user['rol'];

        /**
         * 3. Ruteador Inteligente Estricto por Roles
         * Facilita la escalabilidad cuando se agreguen módulos como Ventas o Finanzas.
         */
        switch ($user['rol']) {
            case 'administrador':
                // El administrador global entra a la raíz operativa (Soporte o Dashboard General)
                header("Location: Soporte/index.php");
                exit();

            case 'soporte':
                // El ingeniero de soporte va directo a su módulo de tickets
                header("Location: Soporte/index.php");
                exit();

            case 'ventas':
                // Dejado listo para la siguiente fase del Portal Demex
                header("Location: Ventas/index.php");
                exit();

            default:
                // Si es un cliente o rol no catalogado
                header("Location: vista_cliente.php");
                exit();
        }

    } else {
        header("Location: login.php?error=password_incorrecto");
        exit();
    }

} catch (\Exception $e) {
    // Puedes descomentar la siguiente línea si necesitas debuguear errores de base de datos en desarrollo:
    // die("Error en login: " . $e->getMessage());
    header("Location: login.php?error=fatal");
    exit();
}