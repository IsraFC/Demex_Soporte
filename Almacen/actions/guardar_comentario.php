<?php
/**
 * ARCHIVO: Almacen/actions/guardar_comentario.php
 * DESCRIPCIÓN: Guarda las interacciones de texto del chat en la bitácora transaccional.
 * @author Sistemas Demex
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

// Subimos dos niveles para conectar la base de datos
require_once '../../config/db.php';

$id_inventario = intval($_POST['id_inventario'] ?? 0);
$id_usuario    = intval($_SESSION['id_usuario'] ?? 0);
$comentario    = trim($_POST['comentario'] ?? '');

if ($id_inventario <= 0 || $id_usuario <= 0 || empty($comentario)) {
    echo json_encode(["success" => false, "message" => "Datos de mensaje insuficientes o sesión expirada."]);
    exit;
}

try {
    $sql = "INSERT INTO almacen_comentarios (id_inventario, id_usuario, comentario) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$id_inventario, $id_usuario, $comentario]);

    echo json_encode(["success" => $success]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}