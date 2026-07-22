<?php
/**
 * ARCHIVO: Almacen/actions/obtener_conteos_kpi.php
 * DESCRIPCIÓN: Devuelve los conteos en tiempo real para las tarjetas informativas superiores de Lotes.
 * @project Almacén Técnico DEMEX
 */

require_once '../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $total_lotes   = $pdo->query("SELECT COUNT(*) FROM almacen_lotes")->fetchColumn();
    $total_equipos = $pdo->query("SELECT COUNT(*) FROM almacen_inventario")->fetchColumn();
    $sin_revisar   = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'SIN REVISAR'")->fetchColumn();
    $disponibles   = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'DISPONIBLE PARA VENTA'")->fetchColumn();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success'     => true,
        'total_lotes' => intval($total_lotes),
        'total'       => intval($total_equipos),
        'sin_revisar' => intval($sin_revisar),
        'disponibles' => intval($disponibles)
    ]);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit();