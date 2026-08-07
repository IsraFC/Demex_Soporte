<?php
/**
 * ARCHIVO: Ventas/eliminar_producto.php
 * DESCRIPCIÓN: Controlador Backend para la eliminación asíncrona de productos.
 * Ubicado dentro de la carpeta Ventas.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.1 (Ajuste de ruta relativa config/db.php)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración estricta de respuesta JSON
header('Content-Type: application/json; charset=utf-8');
// CORREGIDO: Subimos un nivel para encontrar la configuración de la base de datos
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de acceso no permitido.']);
    exit();
}

$id_producto = isset($_POST['id_producto']) ? intval($_POST['id_producto']) : 0;

if ($id_producto <= 0) {
    echo json_encode(['success' => false, 'message' => 'Identificador de producto inválido.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Verificamos primero si el producto existe
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE id_producto = ?");
    $stmt_check->execute([$id_producto]);
    
    if ($stmt_check->fetchColumn() == 0) {
        throw new Exception("El producto ya no existe en el catálogo.");
    }

    // Ejecutamos la eliminación
    $stmt_delete = $pdo->prepare("DELETE FROM productos WHERE id_producto = ?");
    $stmt_delete->execute([$id_producto]);

    $pdo->commit();

    echo json_encode(['success' => true]);
    exit();

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
    exit();
}