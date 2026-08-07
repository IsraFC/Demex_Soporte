<?php
/**
 * ARCHIVO: Ventas/eliminar_cliente.php
 * DESCRIPCIÓN: Controlador asíncrono (AJAX) para eliminar a un cliente del catálogo general.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../config/db.php';

// Verificación de autenticación y método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['roles'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

$id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);

if (!$id_cliente) {
    echo json_encode(['success' => false, 'message' => 'ID de cliente no válido']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$id_cliente]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Cliente eliminado con éxito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'El cliente no existe o ya fue eliminado']);
    }
} catch (PDOException $e) {
    // Si la eliminación falla por integridad referencial (llaves foráneas en ventas)
    if ($e->getCode() == '23000') {
        echo json_encode([
            'success' => false, 
            'message' => 'No se puede eliminar el cliente porque tiene un historial de ventas o registros asociados.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al eliminar cliente: ' . $e->getMessage()
        ]);
    }
}