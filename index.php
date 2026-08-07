<?php
/**
 * @file index.php
 * @package Portal_Demex
 * @version 2.0 - Enrutador Principal de la Raíz
 * @brief Punto de entrada que redirige a la pantalla de Inicio unificada tras iniciar sesión.
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

// Redirección unificada: Sea cual sea el rol activo, lo mandamos a la nueva pantalla de bienvenida inicio.php
header("Location: inicio.php");
exit();