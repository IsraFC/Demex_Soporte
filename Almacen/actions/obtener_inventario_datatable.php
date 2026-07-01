<?php
/**
 * ARCHIVO: Almacen/actions/obtener_inventario_datatable.php
 * DESCRIPCIÓN: Procesador Server-Side para DataTables del inventario de almacén.
 * CORRECCIÓN: Lectura nativa de la columna 'modelo' desde almacen_inventario sin depender de equipos_garantia.
 * @project Almacén Técnico DEMEX
 * @version 5.2 - Procesamiento de Stock Nuevo e Importaciones Puras
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

try {
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $rowperpage = isset($_POST['length']) ? intval($_POST['length']) : 13;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

    $filterEstatus = isset($_POST['filterEstatus']) ? $_POST['filterEstatus'] : '';
    $filterTipo = isset($_POST['filterTipo']) ? $_POST['filterTipo'] : '';
    $fechaDesde = isset($_POST['fechaDesde']) ? $_POST['fechaDesde'] : '';
    $fechaHasta = isset($_POST['fechaHasta']) ? $_POST['fechaHasta'] : '';

    $searchQuery = " WHERE 1=1 ";
    $params = [];

    if (!empty($filterEstatus)) {
        $searchQuery .= " AND a.estatus = :filterEstatus ";
        $params[':filterEstatus'] = $filterEstatus;
    }
    if (!empty($filterTipo)) {
        $searchQuery .= " AND a.tipo = :filterTipo ";
        $params[':filterTipo'] = $filterTipo;
    }
    if (!empty($fechaDesde)) {
        $searchQuery .= " AND a.fecha_ingreso_contenedor >= :fechaDesde ";
        $params[':fechaDesde'] = $fechaDesde;
    }
    if (!empty($fechaHasta)) {
        $searchQuery .= " AND a.fecha_ingreso_contenedor <= :fechaHasta ";
        $params[':fechaHasta'] = $fechaHasta;
    }

    // CORRECCIÓN: Filtramos usando directamente el modelo local nativo de la tabla de stock
    if (!empty($searchValue)) {
        $searchQuery .= " AND (a.no_serie LIKE :search_serie OR a.contenedor LIKE :search_contenedor OR a.modelo LIKE :search_modelo) ";
        $params[':search_serie'] = '%' . $searchValue . '%';
        $params[':search_contenedor'] = '%' . $searchValue . '%';
        $params[':search_modelo'] = '%' . $searchValue . '%';
    }

    $totalRecords = $pdo->query("SELECT COUNT(*) FROM almacen_inventario")->fetchColumn();

    // Consulta de conteo filtrado limpia sin JOINs pesados innecesarios
    $sqlFiltered = "SELECT COUNT(*) FROM almacen_inventario a $searchQuery";
    $stmtFiltered = $pdo->prepare($sqlFiltered);
    
    foreach ($params as $key => $val) {
        $stmtFiltered->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmtFiltered->execute();
    $totalRecordwithFilter = $stmtFiltered->fetchColumn();

    // CORRECCIÓN METROLÓGICA: Selección directa de a.modelo (ENUM nativo de importaciones)
    $sql = "SELECT 
                a.*,
                IF(a.fecha_inicio_ajustes_almacen IS NOT NULL, DATEDIFF(a.fecha_inicio_ajustes_almacen, a.fecha_ingreso_contenedor), DATEDIFF(CURDATE(), a.fecha_ingreso_contenedor)) AS dias_espera_caja,
                IF(a.fecha_inicio_ajustes_almacen IS NOT NULL, 
                    IF(a.fecha_disponible_soporte IS NOT NULL, DATEDIFF(a.fecha_disponible_soporte, a.fecha_inicio_ajustes_almacen), DATEDIFF(CURDATE(), a.fecha_inicio_ajustes_almacen)), 
                    0
                ) AS dias_ajustes_almacen,
                IF(a.fecha_entrega_soporte IS NOT NULL, 
                    IF(a.fecha_reingreso_almacen IS NOT NULL, DATEDIFF(a.fecha_reingreso_almacen, a.fecha_entrega_soporte), DATEDIFF(CURDATE(), a.fecha_entrega_soporte)), 
                    0
                ) AS dias_soporte,
                IF(a.fecha_entrega_cliente IS NOT NULL, DATEDIFF(a.fecha_entrega_cliente, a.fecha_ingreso_contenedor), DATEDIFF(CURDATE(), a.fecha_ingreso_contenedor)) AS dias_inventario_total
            FROM almacen_inventario a
            $searchQuery
            ORDER BY a.fecha_ingreso_contenedor DESC, a.id DESC 
            LIMIT :limit OFFSET :offset";

    $stmtData = $pdo->prepare($sql);

    foreach ($params as $key => $val) {
        $stmtData->bindValue($key, $val, PDO::PARAM_STR);
    }

    $stmtData->bindValue(':limit', (int)$rowperpage, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', (int)$start, PDO::PARAM_INT);
    $stmtData->execute();
    $empRecords = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($empRecords as $row) {
        $data[] = [
            "id"                         => $row['id'],
            "contenedor"                 => htmlspecialchars($row['contenedor']),
            "modelo"                     => htmlspecialchars($row['modelo']), // Mapeo directo del ENUM
            "no_serie"                   => htmlspecialchars($row['no_serie']),
            "tipo"                       => htmlspecialchars($row['tipo'] ?? ''),
            "estatus"                    => $row['estatus'],
            "fecha_ingreso_contenedor"   => date('d/m/Y', strtotime($row['fecha_ingreso_contenedor'])),
            "dias_espera_caja"           => $row['dias_espera_caja'] . ' días',
            "dias_ajustes_almacen"       => $row['dias_ajustes_almacen'] . ' días',
            "dias_soporte"               => $row['dias_soporte'] . ' días',
            "dias_inventario_total"      => $row['dias_inventario_total'] . ' días'
        ];
    }

    // CORRECCIÓN: Nombres de claves estandarizados para DataTables modernos
    $response = [
        "draw"            => intval($draw),
        "recordsTotal"    => intval($totalRecords),
        "recordsFiltered" => intval($totalRecordwithFilter),
        "data"            => $data
    ];

    if (ob_get_length()) ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "draw"            => 1,
        "recordsTotal"    => 0,
        "recordsFiltered" => 0,
        "data"            => [],
        "error"           => $e->getMessage()
    ]);
    exit();
}