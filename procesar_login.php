<?php
/**
 * @file procesar_login.php
 * @package Portal_Demex
 * @brief Controlador del Backend encargado de validar y procesar la autenticación.
 * * Recibe los datos del formulario, aplica reglas de sanitización de datos,
 * consulta el repositorio relacional de MySQL y evalúa los hashes criptográficos BCRYPT.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inyección obligatoria de la capa de persistencia de datos
require_once 'conexion.php';

/**
 * Restricción de Método: Bloquea cualquier petición que no provenga de un flujo POST legítimo.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// Recopilación de variables y remoción de espacios colaterales
$correo_crudo = $_POST['correo'] ?? '';
$password     = $_POST['password'] ?? '';

// Capa Backend de Seguridad: Sanitización y validación formal de tipos de datos
$correo = filter_var(trim($correo_crudo), FILTER_SANITIZE_EMAIL);

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: login.php?error=datos_incorrectos");
    exit();
}

try {
    /**
     * Query Preparado (Evita de raíz la Inyección SQL):
     * Filtra únicamente usuarios cuyo correo coincida y que tengan estatus = 1 (Cuentas activas/verificadas).
     */
    $sql = "SELECT id_usuario, nombre, apellidos, correo, password, rol, estatus 
            FROM usuarios 
            WHERE correo = ? AND estatus = 1 
            LIMIT 1";
            
    $stmt = $pdo_portal->prepare($sql);
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    /**
     * Evaluación del Hash: password_verify() lee las instrucciones del hash guardado en DB
     * e identifica si el password en texto plano genera la misma firma matemática.
     */
    if ($user && password_verify($password, $user['password'])) {
        
        // Autenticación Exitosa: Se inicializan las variables del alcance Global $_SESSION
        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nombre']     = $user['nombre'];
        $_SESSION['apellidos']  = $user['apellidos'];
        $_SESSION['correo']     = $user['correo'];
        $_SESSION['rol']        = $user['rol'];

        /**
         * Enrutamiento Basado en Roles (RBAC - Role-Based Access Control):
         * Direcciona al usuario de acuerdo a sus privilegios corporativos.
         */
        if ($user['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte') {
            header("Location: Soporte/index.php");
            exit();
        } else {
            // Reservado para la futura implementación del módulo autónomo de clientes
            header("Location: vista_cliente.php");
            exit();
        }

    } else {
        // Credenciales incorrectas o usuario inexistente en el padrón activo
        header("Location: login.php?error=datos_incorrectos");
        exit();
    }

} catch (\Exception $e) {
    // Falla inesperada en tiempo de ejecución del motor de Base de Datos
    header("Location: login.php?error=fatal");
    exit();
}