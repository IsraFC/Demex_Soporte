<?php
/**
 * ARCHIVO: Almacen/actions/obtener_conteos_kpi.php
 * DESCRIPCIÓN: Devuelve los conteos en tiempo real para las tarjetas informativas superiores.
 */

require_once '../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $total_equipos  = $pdo->query("SELECT COUNT(*) FROM almacen_inventario")->fetchColumn();
    $sin_revisar    = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'SIN REVISAR'")->fetchColumn();
    $kpi_en_almacen = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN ALMACÉN'")->fetchColumn();
    $kpi_en_soporte = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN SOPORTE'")->fetchColumn();

    echo json_encode([
        'success' => true,
        'total' => intval($total_equipos),
        'sin_revisar' => intval($sin_revisar),
        'en_almacen' => intval($kpi_en_almacen),
        'en_soporte' => intval($kpi_en_soporte)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit();