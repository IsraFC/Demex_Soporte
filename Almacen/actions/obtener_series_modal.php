<?php
/**
 * ARCHIVO: Almacen/actions/obtener_series_modal.php
 * DESCRIPCIÓN: Procesador Server-Side para el modal de selección de series.
 * FILTRO AVANZADO: Muestra tickets abiertos E ignora aquellos que ya tienen un proceso activo en Almacén.
 */

ini_set('display_errors', 0);
require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $draw   = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start  = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

    // CORRECCIÓN LOGÍSTICA: Añadimos una subconsulta para excluir los tickets que ya están registrados y activos en almacén
    $queryBase = "FROM Tickets_Soporte t 
                  JOIN equipos_garantia g ON t.no_serie = g.no_serie
                  JOIN clientes c ON g.id_cliente = c.id_cliente
                  WHERE t.estatus = 'Abierto'
                  AND t.id_ticket NOT IN (
                      SELECT DISTINCT id_ticket 
                      FROM almacen_inventario 
                      WHERE id_ticket IS NOT NULL AND estatus != 'ENTREGADA'
                  )";

    $where = "";
    $params = [];

    if (!empty($searchValue)) {
        // Mantenemos tu filtro de búsqueda global por texto o folio de ticket
        $where .= " AND (g.no_serie LIKE :search_serie OR c.nombre_cliente LIKE :search_cliente OR g.modelo LIKE :search_modelo OR t.id_ticket LIKE :search_ticket) ";
        $params[':search_serie'] = '%' . $searchValue . '%';
        $params[':search_cliente'] = '%' . $searchValue . '%';
        $params[':search_modelo'] = '%' . $searchValue . '%';
        $params[':search_ticket'] = '%' . $searchValue . '%';
    }

    // Conteo total de tickets abiertos reales disponibles para Almacén (aplicando la misma exclusión)
    $sqlTotal = "SELECT COUNT(*) FROM Tickets_Soporte t 
                 WHERE t.estatus = 'Abierto' 
                 AND t.id_ticket NOT IN (
                     SELECT DISTINCT id_ticket FROM almacen_inventario WHERE id_ticket IS NOT NULL AND estatus != 'ENTREGADA'
                 )";
    $totalRecords = $pdo->query($sqlTotal)->fetchColumn();
    
    $stmtFiltered = $pdo->prepare("SELECT COUNT(*) $queryBase $where");
    foreach ($params as $key => $val) {
        $stmtFiltered->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmtFiltered->execute();
    $totalRecordwithFilter = $stmtFiltered->fetchColumn();

    $sql = "SELECT g.no_serie, g.modelo, c.nombre_cliente, t.id_ticket 
            $queryBase 
            $where 
            ORDER BY t.fecha_inicial DESC 
            LIMIT :limit OFFSET :offset";

    $stmtData = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmtData->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmtData->bindValue(':limit', (int)$length, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', (int)$start, PDO::PARAM_INT);
    $stmtData->execute();
    $records = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($records as $row) {
        $data[] = [
            "id_ticket"      => '<span class="badge bg-dark bg-opacity-75 font-monospace px-2 py-1" style="font-size:11px;">#' . $row['id_ticket'] . '</span>',
            "nombre_cliente" => htmlspecialchars($row['nombre_cliente']),
            "no_serie"       => htmlspecialchars($row['no_serie']),
            "modelo"         => htmlspecialchars($row['modelo'] ?? 'S/M'),
            "accion"         => '<button type="button" class="btn btn-success btn-xs rounded-pill px-3 fw-bold btn-seleccionar-serie" data-serie="' . htmlspecialchars($row['no_serie']) . '" style="font-size: 11px;"><i class="bi bi-check2-circle me-1"></i> Seleccionar</button>'
        ];
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        "draw" => intval($draw),
        "recordsTotal" => intval($totalRecords),
        "recordsFiltered" => intval($totalRecordwithFilter),
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(["draw" => 1, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => $e->getMessage()]);
    exit();
}