<?php
/**
 * ARCHIVO: Almacen/actions/buscar_equipo_garantia.php
 * DESCRIPCIÓN: Endpoint AJAX para buscar y validar la existencia de una serie con su cliente.
 */
require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$no_serie = isset($_POST['no_serie']) ? trim($_POST['no_serie']) : '';

if (empty($no_serie)) {
    echo json_encode(['exists' => false]);
    exit();
}

try {
    // Consulta con INNER JOIN para traer los datos del cliente y el equipo al mismo tiempo
    $sql = "SELECT eg.*, c.nombre_cliente, c.telefono, c.ubicacion 
            FROM equipos_garantia eg
            INNER JOIN clientes c ON eg.id_cliente = c.id_cliente
            WHERE eg.no_serie = ? LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$no_serie]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($equipo) {
        // Validamos dinámicamente si la fecha_termino es mayor o igual al día de hoy
        $hoy = date('Y-m-d');
        $garantia_status = ($equipo['fecha_termino'] >= $hoy) ? 'Válida (Vigente)' : 'Expirada';

        echo json_encode([
            'exists'          => true,
            'modelo'          => $equipo['modelo'],
            'nombre_cliente'  => $equipo['nombre_cliente'],
            'telefono'        => $equipo['telefono'],
            'ubicacion'       => $equipo['ubicacion'],
            'garantia_status' => $garantia_status
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit();

} catch (Exception $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
    exit();
}