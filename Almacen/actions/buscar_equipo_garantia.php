<?php
require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$no_serie = isset($_POST['no_serie']) ? trim($_POST['no_serie']) : '';

if (empty($no_serie)) {
    echo json_encode(['exists' => false]);
    exit();
}

try {
    // 1. Buscamos el equipo y sus datos de cliente
    $sql = "SELECT eg.*, c.nombre_cliente, c.telefono, c.ubicacion 
            FROM equipos_garantia eg
            INNER JOIN clientes c ON eg.id_cliente = c.id_cliente
            WHERE eg.no_serie = ? LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$no_serie]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($equipo) {
        // 2. Buscamos si existe un ticket abierto para esta serie
        $sqlTicket = "SELECT id_ticket FROM Tickets_Soporte WHERE no_serie = ? AND estatus = 'Abierto' LIMIT 1";
        $stmtTicket = $pdo->prepare($sqlTicket);
        $stmtTicket->execute([$no_serie]);
        $ticket = $stmtTicket->fetch(PDO::FETCH_ASSOC);

        $hoy = date('Y-m-d');
        $garantia_status = ($equipo['fecha_termino'] >= $hoy) ? 'Válida (Vigente)' : 'Expirada';

        echo json_encode([
            'exists'          => true,
            'modelo'          => $equipo['modelo'],
            'nombre_cliente'  => $equipo['nombre_cliente'],
            'telefono'        => $equipo['telefono'],
            'ubicacion'       => $equipo['ubicacion'],
            'garantia_status' => $garantia_status,
            'id_ticket'       => $ticket ? $ticket['id_ticket'] : null // Vinculamos el ticket si existe
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}