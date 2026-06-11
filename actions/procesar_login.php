<?php
/**
 * @file procesar_login.php
 * @package Portal_Demex
 * @version 2.1 - Adaptado a Múltiples Roles (Redirección a Raíz Homologada)
 * @brief Controlador encargado de validar, proteger la autenticación e inicializar los múltiples roles en la sesión.
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
    // 1. Buscamos al usuario (Removida la columna obsoleta 'rol')
    $sql = "SELECT id_usuario, nombre, apellidos, correo, password, estatus, intentos_fallidos, bloqueado_hasta 
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

        // 4. Consultar todos los roles que tiene asignados el usuario (con primera mayúscula)
        $sqlRoles = "SELECT r.nombre_rol 
                     FROM roles r
                     INNER JOIN usuario_roles ur ON r.id_rol = ur.id_rol
                     WHERE ur.id_usuario = ?";
        $stmtRoles = $pdo->prepare($sqlRoles);
        $stmtRoles->execute([$user['id_usuario']]);
        
        // fetchAll(PDO::FETCH_COLUMN) genera un array plano, ej: ['Soporte', 'Administrador']
        $mis_roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

        // Guardamos los datos de identidad en la sesión global
        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nombre']     = $user['nombre'];
        $_SESSION['apellidos']  = $user['apellidos'];
        $_SESSION['correo']     = $user['correo'];
        $_SESSION['roles']      = $mis_roles; // Guardamos el array completo de roles asignados

        // 5. Redirección por prioridades de roles asignados (Case-Sensitive)
        if (in_array('Administrador', $mis_roles) || in_array('Soporte', $mis_roles)) {
            header("Location: ../Soporte/index.php");
            exit();
        } elseif (in_array('Ventas', $mis_roles)) {
            header("Location: ../Ventas/leads_crm.php");
            exit();
        } else {
            // Corrección: Redirige al enrutador index.php de la raíz al no existir vista_cliente.php
            header("Location: ../index.php");
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