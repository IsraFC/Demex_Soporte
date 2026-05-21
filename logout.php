<?php
/**
 * @file logout.php
 * @package Portal_Demex
 * @brief Destructor de sesión seguro.
 * * Limpia el arreglo $_SESSION, destruye la cookie en el navegador 
 * y redirige al usuario a la pantalla de control de acceso.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Limpiamos todas las variables de sesión
$_SESSION = array();

// 2. Si se desea destruir la sesión completamente, se borra también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruimos la sesión en el servidor
session_destroy();

// 4. Redirigimos al login con bandera de éxito al salir
header("Location: login.php?salida=exito");
exit();