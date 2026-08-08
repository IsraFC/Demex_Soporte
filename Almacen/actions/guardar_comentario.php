<?php
/**
 * ARCHIVO: Almacen/actions/guardar_comentario.php
 * DESCRIPCIÓN: Guarda comentario/nota vinculada al Lote.
 */
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

$id_lote = intval($_POST['id_lote'] ?? 0);
$id_usuario = intval($_SESSION['id_usuario'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

if ($id_lote <= 0 || $id_usuario <= 0 || empty($comentario)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO almacen_comentarios (id_lote, id_usuario, comentario) VALUES (?, ?, ?)");
    $stmt->execute([$id_lote, $id_usuario, $comentario]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}