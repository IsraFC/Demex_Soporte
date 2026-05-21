<?php
/**
 * ARCHIVO: actions/actualizar_estatus.php
 * DESCRIPCIÓN: Cambia el estado de un ticket (Cerrado/Cancelado/Abierto) vía AJAX.
 * Si el ticket se marca como 'Cerrado', registra automáticamente la fecha actual.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.5
 */

require_once '../config/db.php';

// Definimos que la respuesta siempre será un objeto JSON para que el JS del Index lo entienda
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura de datos enviados por la petición AJAX
    $id = $_POST['id_ticket'] ?? null;
    $estatus = $_POST['estatus'] ?? null;

    /**
     * LÓGICA DE CIERRE:
     * Si el estatus recibido es 'Cerrado', generamos la marca de tiempo (Timestamp).
     * Si no, la fecha de cierre se mantiene o vuelve a ser NULL.
     */
    $fecha_cierre = ($estatus === 'Cerrado') ? date('Y-m-d H:i:s') : null;

    try {
        // Preparamos la consulta para actualizar el estatus y la fecha de finalización
        $stmt = $pdo->prepare("UPDATE Tickets_Soporte SET estatus = ?, fecha_cierre = ? WHERE id_ticket = ?");
        $stmt->execute([$estatus, $fecha_cierre, $id]);

        // Retornamos éxito al cliente
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // En caso de error (ej. pérdida de conexión), enviamos el mensaje al log de consola
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
} else {
    // Si intentan entrar directo al archivo sin POST
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}