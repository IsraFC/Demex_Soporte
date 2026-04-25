<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_ticket'];
    $estatus = $_POST['estatus'];
    $fecha_cierre = ($estatus === 'Cerrado') ? date('Y-m-d H:i:s') : null;

    try {
        $stmt = $pdo->prepare("UPDATE Tickets_Soporte SET estatus = ?, fecha_cierre = ? WHERE id_ticket = ?");
        $stmt->execute([$estatus, $fecha_cierre, $id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}