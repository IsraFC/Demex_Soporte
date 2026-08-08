<?php
/**
 * ARCHIVO: Soporte/actions/obtener_revisiones_datatable.php
 * DESCRIPCIÓN: Procesa Lotes agrupados con unidades activas en el laboratorio de Soporte.
 * @project Soporte Técnico DEMEX
 * @version 3.0 - Agrupamiento por Lotes
 * @author Israel Fernández Carrera
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $draw        = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start       = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $rowperpage  = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

    $where = " WHERE i.estatus IN ('DISPONIBLE PARA SOPORTE', 'EN REVISIÓN SOPORTE') ";
    
    if (!empty($searchValue)) {
        $clean = str_replace("'", "", $searchValue);
        $where .= " AND (l.contenedor LIKE '%$clean%' OR l.tipo LIKE '%$clean%') ";
    }

    $sqlTotal = "SELECT COUNT(DISTINCT l.id_lote) 
                 FROM almacen_lotes l 
                 INNER JOIN almacen_inventario i ON l.id_lote = i.id_lote 
                 WHERE i.estatus IN ('DISPONIBLE PARA SOPORTE', 'EN REVISIÓN SOPORTE')";
    
    $totalRecords = $pdo->query($sqlTotal)->fetchColumn();

    $sql = "SELECT l.id_lote, l.contenedor, l.tipo, l.fecha_ingreso,
                   COUNT(i.id) AS total_taller,
                   SUM(CASE WHEN i.estatus = 'DISPONIBLE PARA SOPORTE' THEN 1 ELSE 0 END) AS por_recibir,
                   SUM(CASE WHEN i.estatus = 'EN REVISIÓN SOPORTE' THEN 1 ELSE 0 END) AS en_calibracion
            FROM almacen_lotes l
            INNER JOIN almacen_inventario i ON l.id_lote = i.id_lote
            $where
            GROUP BY l.id_lote
            ORDER BY l.fecha_ingreso DESC, l.id_lote DESC
            LIMIT " . intval($start) . ", " . intval($rowperpage);

    $stmt = $pdo->query($sql);
    $data = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $desglose = [];
        if ($row['por_recibir'] > 0)    $desglose[] = "<span class='badge bg-warning text-dark'>{$row['por_recibir']} Por Recibir</span>";
        if ($row['en_calibracion'] > 0) $desglose[] = "<span class='badge bg-primary'>{$row['en_calibracion']} En Calibración</span>";

        $data[] = [
            "id_lote"          => $row['id_lote'],
            "contenedor"       => htmlspecialchars($row['contenedor']),
            "tipo"             => htmlspecialchars($row['tipo']),
            "fecha_ingreso"    => date('d/m/Y', strtotime($row['fecha_ingreso'])),
            "total_taller"     => $row['total_taller'],
            "desglose_estatus" => implode(' ', $desglose)
        ];
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        "draw"            => intval($draw),
        "recordsTotal"    => intval($totalRecords),
        "recordsFiltered" => intval($totalRecords),
        "data"            => $data
    ]);
    exit();

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(["draw" => 1, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => $e->getMessage()]);
    exit();
}