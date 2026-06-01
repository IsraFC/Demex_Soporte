<?php
/**
 * @file verificar_bloqueo_cuenta.php
 * @package Portal_Demex
 * @brief Validación asíncrona para comprobar si una cuenta está bloqueada temporalmente.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

$correo = isset($_GET['correo']) ? trim($_GET['correo']) : '';

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['bloqueado' => false]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT bloqueado_hasta FROM usuarios WHERE correo = ? AND estatus = 1 LIMIT 1");
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    if ($user && !empty($user['bloqueado_hasta'])) {
        $tiempo_bloqueo = strtotime($user['bloqueado_hasta']);
        if (time() < $tiempo_bloqueo) {
            $minutos_restantes = ceil(($tiempo_bloqueo - time()) / 60);
            echo json_encode([
                'bloqueado' => true,
                'minutos' => $minutos_restantes
            ]);
            exit();
        }
    }
    
    echo json_encode(['bloqueado' => false]);
} catch (\Exception $e) {
    echo json_encode(['bloqueado' => false, 'error' => true]);
}
exit();