<?php
/**
 * @file index.php
 * @package Portal_Demex
 * @version 1.0 - Enrutador Principal de la Raíz
 * @brief Punto de entrada que redirige de forma inteligente según el rol activo.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay una sesión de roles iniciada, mandamos al usuario a loguearse
if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles'])) {
    header("Location: login.php");
    exit();
}

$mis_roles = $_SESSION['roles'];

// Ruteo dinámico según la jerarquía de los perfiles cargados
if (in_array('Administrador', $mis_roles) || in_array('Soporte', $mis_roles)) {
    header("Location: Soporte/index.php");
    exit();
} elseif (in_array('Ventas', $mis_roles)) {
    header("Location: Ventas/index.php");
    exit();
} else {
    // Si en el futuro creas una vista específica para clientes, aquí cambiarás la ruta
    echo "<h3>Bienvenido al Portal DEMEX</h3>";
    echo "<p>Próximamente estará lista tu interfaz de usuario.</p>";
    echo "<a href='logout.php'>Cerrar Sesión</a>";
    exit();
}