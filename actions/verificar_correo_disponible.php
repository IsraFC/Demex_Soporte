<?php
/**
 * @file verificar_correo_disponible.php
 * @package Portal_Demex
 * @brief Validación asíncrona de duplicidad de correos para el staff.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Control de acceso estricto: Solo administradores
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

require_once '../config/db.php';

$correo = isset($_GET['correo']) ? trim($_GET['correo']) : '';

if (empty($correo)) {
    echo json_encode(['disponible' => true]);
    exit();
}

try {
    // Buscamos si ya existe el correo en la base de datos
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1");
    $stmt->execute([$correo]);
    
    if ($stmt->fetch()) {
        // Si existe, NO está disponible
        echo json_encode(['disponible' => false]);
    } else {
        // Si no existe, SÍ está disponible
        echo json_encode(['disponible' => true]);
    }
} catch (\PDOException $e) {
    echo json_encode(['error' => 'Error de base de datos']);
}
exit();