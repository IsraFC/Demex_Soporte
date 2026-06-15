<?php
/**
 * ARCHIVO: actions/actualizar_status_comercial.php
 * DESCRIPCIÓN: Procesador asíncrono para actualizar únicamente el estatus comercial de la venta.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 2.0 (Pure Commercial Scope)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$id_prospecto     = isset($_POST['id_prospecto']) ? intval($_POST['id_prospecto']) : 0;
$status_comercial = isset($_POST['status_comercial']) ? trim($_POST['status_comercial']) : '';

if ($id_prospecto <= 0 || empty($status_comercial)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos.']);
    exit();
}

$estados_permitidos = ['Consultado', 'Cotizado', 'Venta Cerrada'];
if (!in_array($status_comercial, $estados_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Estatus comercial no válido.']);
    exit();
}

try {
    // Actualiza EXCLUSIVAMENTE el estado comercial de la venta humana
    $sql = "UPDATE prospectos SET status_comercial = :status_comercial WHERE id_prospecto = :id_prospecto";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':status_comercial' => $status_comercial,
        ':id_prospecto'     => $id_prospecto
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Estatus comercial actualizado con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se detectaron cambios en la base de datos.']);
    }
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno en el servidor de BD.']);
}
exit();