<?php
/**
 * ARCHIVO: actions/obtener_tickets_datatable.php
 * DESCRIPCIÓN: Motor de procesamiento Server-side para DataTables en DEMEX.
 * Incorpora la relación hacia el catálogo maestro de técnicos asignados de forma limpia.
 * @project Soporte Técnico DEMEX
 * @version 2.1 - Motor Autónomo con Soporte de Técnicos
 */

require_once '../../config/db.php';

header('Content-Type: application/json');

/**
 * 1. CAPTURA DE PARÁMETROS DE DATATABLES
 */
$draw   = $_POST['draw'] ?? 1;
$start  = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 13;
$searchValue = $_POST['search']['value'] ?? '';

/**
 * 2. CONSTRUCCIÓN DE LA CONSULTA BASE PURA (JOIN A TÉCNICOS INYECTADO)
 */
$queryBase = "FROM tickets_soporte t 
              JOIN clientes c ON t.id_cliente = c.id_cliente
              LEFT JOIN equipos_garantia e ON t.no_serie = e.no_serie
              LEFT JOIN detalles_costos_tiempos d ON t.id_ticket = d.id_ticket
              LEFT JOIN tecnicos tec ON d.id_tecnico_asignado = tec.id_tecnico
              LEFT JOIN usuarios uc ON t.id_usuario_creador = uc.id_usuario
              LEFT JOIN usuarios ue ON t.id_usuario_editor = ue.id_usuario";

/**
 * 3. SISTEMA DE FILTRADO DINÁMICO (WHERE)
 */
$where = " WHERE 1=1 ";

if (!empty($searchValue)) {
    $searchClean = str_replace("'", "", $searchValue);
    $where .= " AND (c.nombre_cliente LIKE '%$searchClean%' OR t.no_serie LIKE '%$searchClean%' OR tec.nombre LIKE '%$searchClean%') ";
}

if (!empty($_POST['filterTipo']))   $where .= " AND t.tipo_llamada = '" . str_replace("'", "", $_POST['filterTipo']) . "' ";
if (!empty($_POST['filterFalla']))  $where .= " AND t.tipo_falla = '" . str_replace("'", "", $_POST['filterFalla']) . "' ";
if (!empty($_POST['filterAccion'])) $where .= " AND d.accion = '" . str_replace("'", "", $_POST['filterAccion']) . "' ";

if (($_POST['soloPendientes'] ?? 0) == 1) $where .= " AND t.estatus = 'Abierto' ";
if (($_POST['soloGarantia'] ?? 0) == 1)   $where .= " AND t.garantia_valida = 'Válida' ";
if (($_POST['soloDeuda'] ?? 0) == 1)      $where .= " AND d.estatus_pago = 'Pendiente' ";

if (!empty($_POST['fechaDesde'])) $where .= " AND t.fecha_inicial >= '" . $_POST['fechaDesde'] . " 00:00:00' ";
if (!empty($_POST['fechaHasta'])) $where .= " AND t.fecha_inicial <= '" . $_POST['fechaHasta'] . " 23:59:59' ";

if (($_POST['soloUrgentes'] ?? 0) == 1) {
    $where .= " AND t.estatus = 'Abierto' AND DATEDIFF(NOW(), t.fecha_inicial) >= 14 ";
}

/**
 * 4. CONTEO DE REGISTROS
 */
$totalRecords = $pdo->query("SELECT COUNT(*) FROM tickets_soporte")->fetchColumn();
$totalRecordsWithFilter = $pdo->query("SELECT COUNT(*) $queryBase $where")->fetchColumn();

/**
 * 5. EJECUCIÓN DE LA CONSULTA PAGINADA
 */
$sql = "SELECT t.id_ticket, c.nombre_cliente, e.modelo, t.no_serie, t.tipo_falla, 
               t.garantia_valida, t.estatus, t.fecha_inicial, d.estatus_pago, t.tipo_llamada, 
               d.accion, d.fecha_inicio_acc, d.fecha_fin_acc, d.costo_total, tec.nombre AS tecnico_nom,
               uc.nombre AS creador_nom, uc.apellidos AS creador_ape,
               ue.nombre AS editor_nom, ue.apellidos AS editor_ape
        $queryBase 
        $where 
        ORDER BY t.id_ticket DESC 
        LIMIT " . intval($start) . ", " . intval($length);

$stmt = $pdo->query($sql);
$data = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $diff_dias = floor((time() - strtotime($row['fecha_inicial'])) / 86400);
    $esUrgente = ($row['estatus'] === 'Abierto' && $diff_dias >= 14);

    $creadorCompleto = $row['creador_nom'] ? $row['creador_nom'] . ' ' . $row['creador_ape'] : 'Sistema';
    $editorCompleto  = $row['editor_nom'] ? $row['editor_nom'] . ' ' . $row['editor_ape'] : null;

    $data[] = [
        "id_ticket"        => $row['id_ticket'],
        "es_urgente"       => $esUrgente, 
        "diff_dias"        => $diff_dias, 
        "nombre_cliente"   => htmlspecialchars($row['nombre_cliente']),
        "modelo_serie"     => "<b>" . ($row['modelo'] ?: 'S/M') . "</b><br><code class='text-muted' style='font-size: 0.7rem;'>" . $row['no_serie'] . "</code>",
        "no_serie_plana"   => $row['no_serie'],
        "tipo_llamada"     => $row['tipo_llamada'],
        "tipo_falla"       => $row['tipo_falla'] ?: 'Soporte',
        "accion_realizada" => $row['accion'],
        "garantia_valida"  => $row['garantia_valida'],
        "estatus_pago"     => $row['estatus_pago'] ?: 'N/A',
        "estatus"          => $row['estatus'],
        "fecha_inicial"    => date('d/m/y', strtotime($row['fecha_inicial'])),
        "creador"          => $creadorCompleto, 
        "editor"           => $editorCompleto,  
        "f_ini_acc"        => $row['fecha_inicio_acc'],
        "f_fin_acc"        => $row['fecha_fin_acc']
    ];
}

echo json_encode([
    "draw"            => intval($draw),
    "recordsTotal"    => intval($totalRecords),
    "recordsFiltered" => intval($totalRecordsWithFilter),
    "data"            => $data
]);