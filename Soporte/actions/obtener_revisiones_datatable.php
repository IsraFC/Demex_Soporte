<?php
/**
 * ARCHIVO: Soporte/actions/obtener_revisiones_datatable.php
 * DESCRIPCIÓN: Procesador Server-Side exclusivo para DataTables del Laboratorio Técnico.
 * FILTRO FIJO: Solo aísla maquinaria en tránsito o revisión de Soporte.
 * @project Soporte Técnico DEMEX
 * @version 1.0 - Motor Server-Side Independiente
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $rowperpage = isset($_POST['length']) ? intval($_POST['length']) : 13;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    $filterEstatus = isset($_POST['filterEstatus']) ? trim($_POST['filterEstatus']) : '';

    // FILTRADO LOGÍSTICO EXCLUSIVO: Solo cargamos el stock que requiere labor técnica
    $searchQuery = " WHERE 1=1 ";
    if (!empty($filterEstatus)) {
        $searchQuery .= " AND a.estatus = :filterEstatus ";
        $params[':filterEstatus'] = $filterEstatus;
    } else {
        $searchQuery .= " AND a.estatus IN ('DISPONIBLE PARA SOPORTE', 'EN REVISIÓN SOPORTE') ";
    }

    if (!empty($searchValue)) {
        $searchQuery .= " AND (a.no_serie LIKE :search_serie OR a.contenedor LIKE :search_contenedor OR a.modelo LIKE :search_modelo) ";
        $params[':search_serie'] = '%' . $searchValue . '%';
        $params[':search_contenedor'] = '%' . $searchValue . '%';
        $params[':search_modelo'] = '%' . $searchValue . '%';
    }

    $totalRecords = $pdo->query("SELECT COUNT(*) FROM almacen_inventario WHERE estatus IN ('DISPONIBLE PARA SOPORTE', 'EN REVISIÓN SOPORTE')")->fetchColumn();

    $sqlFiltered = "SELECT COUNT(*) FROM almacen_inventario a $searchQuery";
    $stmtFiltered = $pdo->prepare($sqlFiltered);
    if (!empty($params)) {
        foreach ($params as $key => $val) { $stmtFiltered->bindValue($key, $val, PDO::PARAM_STR); }
    }
    $stmtFiltered->execute();
    $totalRecordwithFilter = $stmtFiltered->fetchColumn();

    $sql = "SELECT a.id, a.contenedor, a.modelo, a.no_serie, a.tipo, a.estatus, a.fecha_ingreso_contenedor 
            FROM almacen_inventario a
            $searchQuery
            ORDER BY a.fecha_ingreso_contenedor DESC, a.id DESC 
            LIMIT :limit OFFSET :offset";

    $stmtData = $pdo->prepare($sql);
    if (!empty($params)) {
        foreach ($params as $key => $val) { $stmtData->bindValue($key, $val, PDO::PARAM_STR); }
    }
    $stmtData->bindValue(':limit', (int)$rowperpage, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', (int)$start, PDO::PARAM_INT);
    $stmtData->execute();
    $records = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($records as $row) {
        $data[] = [
            "id"                         => $row['id'],
            "contenedor"                 => htmlspecialchars($row['contenedor']),
            "modelo"                     => htmlspecialchars($row['modelo']),
            "no_serie"                   => htmlspecialchars($row['no_serie']),
            "tipo"                       => htmlspecialchars($row['tipo'] ?? 'ORIGINAL'),
            "estatus"                    => $row['estatus'],
            "fecha_ingreso_contenedor"   => date('d/m/Y', strtotime($row['fecha_ingreso_contenedor']))
        ];
    }

    echo json_encode([
        "draw"            => intval($draw),
        "recordsTotal"    => intval($totalRecords),
        "recordsFiltered" => intval($totalRecordwithFilter),
        "data"            => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    echo json_encode([
        "draw" => 1, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => $e->getMessage()
    ]);
    exit();
}