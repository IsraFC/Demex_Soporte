<?php
/**
 * ARCHIVO: actions/actualizar_estatus_feedback.php
 * DESCRIPCIÓN: Cambia transaccionalmente el estatus de un reporte de error.
 * @author Israel Fernández Carrera
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

// Protección de privilegios
if (!isset($_SESSION['roles']) || !in_array('Administrador', $_SESSION['roles'])) {
    echo json_encode(["success" => false, "message" => "Acceso denegado."]);
    exit();
}

$id_feedback = $_POST['id_feedback'] ?? null;
$estatus     = $_POST['estatus'] ?? null;

if (!$id_feedback || !in_array($estatus, ['Pendiente', 'Resuelto'])) {
    echo json_encode(["success" => false, "message" => "Parámetros de ejecución inválidos."]);
    exit();
}

try {
    $sql = "UPDATE reportes_feedback SET estatus = ? WHERE id_feedback = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$estatus, $id_feedback]);

    if ($result) {
        echo json_encode([
            "success" => true, 
            "message" => "El reporte ha sido marcado como " . strtolower($estatus) . " con éxito."
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "No se detectaron cambios en el registro."]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error técnico: " . $e->getMessage()]);
}