<?php
/**
 * ARCHIVO: Soporte/actions/obtener_conteos_laboratorio.php
 * DESCRIPCIÓN: Devuelve los conteos asíncronos en vivo (KPIs) exclusivamente para el área de laboratorio técnico.
 * @project Soporte Técnico DEMEX
 * @version 1.0
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pendientes_taller = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'DISPONIBLE PARA SOPORTE'")->fetchColumn();
    $en_diagnostico    = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus = 'EN REVISIÓN SOPORTE'")->fetchColumn();

    echo json_encode([
        'success' => true,
        'pendientes' => intval($pendientes_taller),
        'diagnostico' => intval($en_diagnostico)
    ]);
    exit();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}