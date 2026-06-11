<?php
/**
 * @file verificar_correo_disponible.php
 * @package Portal_Demex
 * @version 2.0 - Verificador de Correos Unificado (Staff y Perfil)
 * @date 2026-06-08
 * @brief Validación asíncrona de duplicidad de correos permitiendo exclusión por ID de usuario.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Control de acceso: Validar que al menos esté logueado en el sistema
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

require_once '../config/db.php';

$correo = isset($_GET['correo']) ? trim($_GET['correo']) : '';
// Si mandamos un ID por la URL, lo usamos para ignorarlo en la búsqueda (sirve para el perfil)
$id_excluir = isset($_GET['id_excluir']) ? intval($_GET['id_excluir']) : 0;

if (empty($correo)) {
    echo json_encode(['disponible' => true]);
    exit();
}

try {
    if ($id_excluir > 0) {
        // Modo Perfil: Busca si el correo lo tiene alguien que NO sea el usuario actual
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ? LIMIT 1");
        $stmt->execute([$correo, $id_excluir]);
    } else {
        // Modo Alta de Personal: Busca el correo de forma global en todo el sistema
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->execute([$correo]);
    }
    
    if ($stmt->fetch()) {
        echo json_encode(['disponible' => false]);
    } else {
        echo json_encode(['disponible' => true]);
    }
} catch (\PDOException $e) {
    echo json_encode(['error' => 'Error de base de datos']);
}
exit();