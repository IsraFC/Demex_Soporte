<?php
/**
 * ARCHIVO: actions/buscar_cliente_por_serie.php
 * DESCRIPCIÓN: Busca el propietario de un equipo mediante su serie.
 */
header('Content-Type: application/json');
require_once '../../config/db.php';

if (isset($_POST['no_serie'])) {
    $serie = trim($_POST['no_serie']);

    // Buscamos en Equipos_Garantia para obtener el id_cliente
    $sql = "SELECT e.id_cliente, c.nombre_cliente, e.modelo 
            FROM Equipos_Garantia e 
            JOIN Clientes c ON e.id_cliente = c.id_cliente 
            WHERE e.no_serie = ? 
            LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$serie]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        echo json_encode([
            'encontrado' => true,
            'id_cliente' => $resultado['id_cliente'],
            'nombre_cliente' => $resultado['nombre_cliente'],
            'modelo' => $resultado['modelo']
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
}