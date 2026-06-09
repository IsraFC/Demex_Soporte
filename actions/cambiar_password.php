<?php
/**
 * @file cambiar_password.php
 * @package Portal_Demex
 * @version 1.1 - Procesador Asíncrono de Cambio de Contraseña Proporcional
 * @date 2026-06-08
 * @brief Valida la identidad del usuario mediante hash, restringe contraseñas idénticas y actualiza la BD de forma segura.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';

// 1. CONTROL DE ACCESO: Validar sesión activa
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'title' => 'Acceso denegado', 'text' => 'Sesión no válida.']);
    exit();
}

$id_usuario = (int)$_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual   = $_POST['password_actual'] ?? '';
    $nueva_password    = $_POST['nueva_password'] ?? '';

    // Validar campos obligatorios vacíos
    if (empty($password_actual) || empty($nueva_password)) {
        echo json_encode(['status' => 'error', 'title' => 'Campos vacíos', 'text' => 'Todos los campos de contraseña son obligatorios.']);
        exit();
    }

    // Validar longitud mínima en el servidor (Seguridad perimetral doble)
    if (strlen($nueva_password) < 8) {
        echo json_encode(['status' => 'warning', 'title' => 'Contraseña Débil', 'text' => 'La nueva contraseña debe contener al menos 8 caracteres.']);
        exit();
    }

    // Validar que la nueva contraseña no sea idéntica a la contraseña actual en texto plano
    if ($password_actual === $nueva_password) {
        echo json_encode([
            'status' => 'warning',
            'title' => 'Clave Idéntica',
            'text' => 'La nueva contraseña no puede ser igual a tu contraseña actual. Elige una combinación distinta.'
        ]);
        exit();
    }

    try {
        // 2. CONSULTAR EL HASH ACTUAL DEL USUARIO
        $query = "SELECT password FROM usuarios WHERE id_usuario = ? LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo json_encode(['status' => 'error', 'title' => 'Error de cuenta', 'text' => 'No se encontró la información del usuario en el sistema.']);
            exit();
        }

        // 3. VALIDAR QUE LA CONTRASEÑA ACTUAL SEA CORRECTA
        if (!password_verify($password_actual, $usuario['password'])) {
            echo json_encode(['status' => 'error', 'title' => 'Validación Fallida', 'text' => 'La contraseña actual ingresada es incorrecta.']);
            exit();
        }

        // 4. GENERAR EL NUEVO HASH SEGURO
        $nuevo_hash = password_hash($nueva_password, PASSWORD_BCRYPT);

        // 5. ACTUALIZAR EN LA BASE DE DATOS
        $update = "UPDATE usuarios SET password = ? WHERE id_usuario = ?";
        $stmt_update = $pdo->prepare($update);
        $stmt_update->execute([$nuevo_hash, $id_usuario]);

        // Retornar éxito estructurado en JSON
        echo json_encode([
            'status' => 'success',
            'title' => '¡Contraseña Actualizada!',
            'text' => 'Tu nueva clave de acceso ha sido registrada con éxito.'
        ]);
        exit();

    } catch (\PDOException $e) {
        echo json_encode(['status' => 'error', 'title' => 'Error de Servidor', 'text' => 'Ocurrió un fallo en la base de datos: ' . $e->getMessage()]);
        exit();
    }
}