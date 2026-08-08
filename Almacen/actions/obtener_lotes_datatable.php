<?php
/**
 * ARCHIVO: Almacen/actions/obtener_lotes_datatable.php
 * DESCRIPCIÓN: Servidor para DataTable de Lotes con desglose completo de los 10 estatus del ciclo.
 * @project Almacén Técnico DEMEX
 * @version 6.5 - Cobertura Total de Badges de Estatus
 * @author Israel Fernández Carrera
 */

require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $draw   = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start  = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

    $where = " WHERE 1=1 ";
    if (!empty($searchValue)) {
        $clean = str_replace("'", "", $searchValue);
        $where .= " AND (l.contenedor LIKE '%$clean%' OR l.tipo LIKE '%$clean%') ";
    }

    $totalRecords = $pdo->query("SELECT COUNT(*) FROM almacen_lotes")->fetchColumn();
    $totalRecordsWithFilter = $pdo->query("SELECT COUNT(*) FROM almacen_lotes l $where")->fetchColumn();

    // Sumatoria individual por CADA UNO de los 10 estatus
    $sql = "SELECT l.id_lote, l.contenedor, l.tipo, l.fecha_ingreso,
                   COUNT(i.id) AS total_unidades,
                   SUM(CASE WHEN i.estatus = 'SIN REVISAR' THEN 1 ELSE 0 END) AS sin_revisar,
                   SUM(CASE WHEN i.estatus = 'EN REVISIÓN ALMACÉN' THEN 1 ELSE 0 END) AS rev_almacen,
                   SUM(CASE WHEN i.estatus = 'DISPONIBLE PARA SOPORTE' THEN 1 ELSE 0 END) AS disp_soporte,
                   SUM(CASE WHEN i.estatus = 'EN REVISIÓN SOPORTE' THEN 1 ELSE 0 END) AS rev_soporte,
                   SUM(CASE WHEN i.estatus = 'REINGRESO A ALMACÉN' THEN 1 ELSE 0 END) AS reingreso_almacen,
                   SUM(CASE WHEN i.estatus = 'DISPONIBLE PARA VENTA' THEN 1 ELSE 0 END) AS disp_venta,
                   SUM(CASE WHEN i.estatus = 'COMODATO' THEN 1 ELSE 0 END) AS comodato,
                   SUM(CASE WHEN i.estatus = 'PAGADA / POR ENTREGAR' THEN 1 ELSE 0 END) AS pagada_por_entregar,
                   SUM(CASE WHEN i.estatus = 'CAMBIO' THEN 1 ELSE 0 END) AS cambio,
                   SUM(CASE WHEN i.estatus = 'ENTREGADA' THEN 1 ELSE 0 END) AS entregadas
            FROM almacen_lotes l
            LEFT JOIN almacen_inventario i ON l.id_lote = i.id_lote
            $where
            GROUP BY l.id_lote
            ORDER BY l.fecha_ingreso DESC, l.id_lote DESC
            LIMIT " . intval($start) . ", " . intval($length);

    $stmt = $pdo->query($sql);
    $data = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $desglose = [];

        // 1. SIN REVISAR
        if ($row['sin_revisar'] > 0) {
            $desglose[] = "<span class='badge bg-warning text-dark me-1 mb-1'>{$row['sin_revisar']} Sin Revisar</span>";
        }
        // 2. EN REVISIÓN ALMACÉN
        if ($row['rev_almacen'] > 0) {
            $desglose[] = "<span class='badge bg-primary text-white me-1 mb-1'>{$row['rev_almacen']} Rev. Almacén</span>";
        }
        // 3. DISPONIBLE PARA SOPORTE
        if ($row['disp_soporte'] > 0) {
            $desglose[] = "<span class='badge bg-info text-dark me-1 mb-1'>{$row['disp_soporte']} Disp. Soporte</span>";
        }
        // 4. EN REVISIÓN SOPORTE
        if ($row['rev_soporte'] > 0) {
            $desglose[] = "<span class='badge bg-primary text-white me-1 mb-1'>{$row['rev_soporte']} En Laboratorio</span>";
        }
        // 5. REINGRESO A ALMACÉN
        if ($row['reingreso_almacen'] > 0) {
            $desglose[] = "<span class='badge bg-secondary text-white me-1 mb-1'>{$row['reingreso_almacen']} Reingreso Alm.</span>";
        }
        // 6. DISPONIBLE PARA VENTA
        if ($row['disp_venta'] > 0) {
            $desglose[] = "<span class='badge bg-success text-white me-1 mb-1'>{$row['disp_venta']} Disp. Venta</span>";
        }
        // 7. COMODATO
        if ($row['comodato'] > 0) {
            $desglose[] = "<span class='badge bg-purple text-white me-1 mb-1' style='background-color: #6f42c1;'>{$row['comodato']} Comodato</span>";
        }
        // 8. PAGADA / POR ENTREGAR
        if ($row['pagada_por_entregar'] > 0) {
            $desglose[] = "<span class='badge bg-dark text-white me-1 mb-1'>{$row['pagada_por_entregar']} Pagada / Por Entregar</span>";
        }
        // 9. CAMBIO
        if ($row['cambio'] > 0) {
            $desglose[] = "<span class='badge bg-danger text-white me-1 mb-1'>{$row['cambio']} Cambio</span>";
        }
        // 10. ENTREGADA
        if ($row['entregadas'] > 0) {
            $desglose[] = "<span class='badge bg-outline-success text-success border border-success me-1 mb-1'>{$row['entregadas']} Entregadas</span>";
        }

        $desglose_html = !empty($desglose) ? implode('', $desglose) : "<span class='text-muted small fst-italic'>Sin unidades</span>";

        $data[] = [
            "id_lote"           => $row['id_lote'],
            "contenedor"        => htmlspecialchars($row['contenedor']),
            "tipo"              => htmlspecialchars($row['tipo']),
            "fecha_ingreso"     => date('d/m/Y', strtotime($row['fecha_ingreso'])),
            "total_unidades"    => $row['total_unidades'],
            "desglose_estatus"  => $desglose_html
        ];
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        "draw"            => intval($draw),
        "recordsTotal"    => intval($totalRecords),
        "recordsFiltered" => intval($totalRecordsWithFilter),
        "data"            => $data
    ]);
    exit();

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(["draw" => 1, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => $e->getMessage()]);
    exit();
}