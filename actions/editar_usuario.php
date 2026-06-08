<?php
/**
 * @file editar_usuario.php
 * @package Portal_Demex
 * @version 2.1 - Edición Asíncrona con Respuesta JSON
 * @date 2026-06-08
 * @brief Controlador transaccional que procesa modificaciones de personal y retorna estados legibles en formato JSON.
 */

session_start();
// Declaramos el tipo de contenido como JSON antes de cualquier salida de datos
header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';

// Evaluación estricta de sesión y privilegios antes de procesar el POST
if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles']) || !in_array('Administrador', $_SESSION['roles'])) {
    echo json_encode(['status' => 'error', 'title' => 'Acceso denegado', 'text' => 'No cuentas con los privilegios requeridos.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
    $nombre     = trim($_POST['nombre'] ?? '');
    $apellidos  = trim($_POST['apellidos'] ?? '');
    $roles      = $_POST['roles'] ?? []; 
    $estatus    = isset($_POST['estatus']) ? intval($_POST['estatus']) : 1;

    if ($id_usuario === 0 || empty($nombre) || empty($apellidos) || empty($roles)) {
        echo json_encode(['status' => 'error', 'title' => 'Datos Incompletos', 'text' => 'Parámetros obligatorios incompletos, incluyendo al menos un rol.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Actualización de datos generales del usuario
        $sql = "UPDATE usuarios 
                SET nombre = ?, apellidos = ?, estatus = ? 
                WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellidos, $estatus, $id_usuario]);

        // 2. Limpieza de roles anteriores asignados
        $sql_delete = "DELETE FROM usuario_roles WHERE id_usuario = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([$id_usuario]);

        // 3. Inserción de los nuevos roles seleccionados
        $sql_insert = "INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);

        foreach ($roles as $id_rol) {
            $stmt_insert->execute([$id_usuario, $id_rol]);
        }

        $pdo->commit();

        // Retornamos el objeto de éxito correspondiente para ser procesado por el frontend
        echo json_encode([
            'status' => 'success',
            'title' => '¡Cambios Guardados!',
            'text' => 'La información del usuario y sus roles han sido actualizados con éxito.'
        ]);
        exit();

    } catch (\PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'title' => 'Error de Servidor', 'text' => $e->getMessage()]);
        exit();
    }
}