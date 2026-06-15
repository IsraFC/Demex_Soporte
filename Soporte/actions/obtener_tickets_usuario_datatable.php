<?php
/**
 * ARCHIVO: Soporte/actions/obtener_tickets_usuario_datatable.php
 * DESCRIPCIÓN: Motor Server-side para DataTables exclusivo para folios del usuario logueado.
 * Mantiene la auditoría doble de usuarios, trazas logísticas e integridad estructural.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.4 (Filtro Personalizado de Soporte)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';

// Define la respuesta como JSON para que DataTables la interprete correctamente
header('Content-Type: application/json');

// GUARDIA DE SEGURIDAD INTERNO DEL PROCESADOR
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['roles']) || 
    (!in_array('Administrador', $_SESSION['roles']) && !in_array('Soporte', $_SESSION['roles']))) {
    echo json_encode(["draw" => 0, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []]);
    exit();
}

$id_usuario_activo = $_SESSION['id_usuario'];

/**
 * 1. CAPTURA DE PARÁMETROS DE DATATABLES
 */
$draw   = $_POST['draw'] ?? 1;
$start  = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 13;
$searchValue = $_POST['search']['value'] ?? '';

/**
 * 2. CONSTRUCCIÓN DE LA CONSULTA BASE (CON DOBLE JOIN A USUARIOS)
 */
$queryBase = "FROM tickets_soporte t 
              JOIN clientes c ON t.id_cliente = c.id_cliente
              LEFT JOIN equipos_garantia e ON t.no_serie = e.no_serie
              LEFT JOIN detalles_costos_tiempos d ON t.id_ticket = d.id_ticket
              LEFT JOIN usuarios uc ON t.id_usuario_creador = uc.id_usuario
              LEFT JOIN usuarios ue ON t.id_usuario_editor = ue.id_usuario";

/**
 * 3. SISTEMA DE FILTRADO DINÁMICO (WHERE CON CANDADO DE SEGURIDAD)
 */
$where = " WHERE 1=1 AND t.id_usuario_creador = :id_usuario_activo ";
$params = [':id_usuario_activo' => $id_usuario_activo];

// Búsqueda global (Cliente o No. de Serie)
if (!empty($searchValue)) {
    $where .= " AND (c.nombre_cliente LIKE :search OR t.no_serie LIKE :search) ";
    $params[':search'] = "%$searchValue%";
}

// Filtros de Selects (Tipo de Llamada, Falla y Acción)
if (!empty($_POST['filterTipo'])) {
    $where .= " AND t.tipo_llamada = :filterTipo ";
    $params[':filterTipo'] = $_POST['filterTipo'];
}
if (!empty($_POST['filterFalla'])) {
    $where .= " AND t.tipo_falla = :filterFalla ";
    $params[':filterFalla'] = $_POST['filterFalla'];
}
if (!empty($_POST['filterAccion'])) {
    $where .= " AND d.accion = :filterAccion ";
    $params[':filterAccion'] = $_POST['filterAccion'];
}

// Filtros de Switches (1 = Activo)
if (($_POST['soloPendientes'] ?? 0) == 1) $where .= " AND t.estatus = 'Abierto' ";
if (($_POST['soloGarantia'] ?? 0) == 1)   $where .= " AND t.garantia_valida = 'Válida' ";
if (($_POST['soloDeuda'] ?? 0) == 1)      $where .= " AND d.estatus_pago = 'Pendiente' ";

// Filtros de Rango de Fechas
if (!empty($_POST['fechaDesde'])) {
    $where .= " AND t.fecha_inicial >= :fechaDesde ";
    $params[':fechaDesde'] = $_POST['fechaDesde'] . " 00:00:00";
}
if (!empty($_POST['fechaHasta'])) {
    $where .= " AND t.fecha_inicial <= :fechaHasta ";
    $params[':fechaHasta'] = $_POST['fechaHasta'] . " 23:59:59";
}

// Lógica del Botón "Ver Urgentes" (Más de 14 días abiertos)
if (($_POST['soloUrgentes'] ?? 0) == 1) {
    $where .= " AND t.estatus = 'Abierto' AND DATEDIFF(NOW(), t.fecha_inicial) >= 14 ";
}

/**
 * 4. CONTEO DE REGISTROS (Para paginación transaccional)
 */
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM tickets_soporte WHERE id_usuario_creador = ?");
$stmtTotal->execute([$id_usuario_activo]);
$totalRecords = $stmtTotal->fetchColumn();

$stmtFilter = $pdo->prepare("SELECT COUNT(*) $queryBase $where");
foreach ($params as $key => $val) {
    $stmtFilter->bindValue($key, $val);
}
$stmtFilter->execute();
$totalRecordsWithFilter = $stmtFilter->fetchColumn();

/**
 * 5. EJECUCIÓN DE LA CONSULTA PAGINADA
 */
$sql = "SELECT t.id_ticket, c.nombre_cliente, e.modelo, t.no_serie, t.tipo_falla, 
               t.garantia_valida, t.estatus, t.fecha_inicial, d.estatus_pago, t.tipo_llamada, 
               d.accion, d.fecha_inicio_acc, d.fecha_fin_acc, d.costo_total,
               uc.nombre AS creador_nom, uc.apellidos AS creador_ape,
               ue.nombre AS editor_nom, ue.apellidos AS editor_ape
        $queryBase 
        $where 
        ORDER BY t.id_ticket DESC 
        LIMIT :start, :length";

$stmtData = $pdo->prepare($sql);

$stmtData->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmtData->bindValue(':length', (int)$length, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmtData->bindValue($key, $val);
}
$stmtData->execute();

$data = [];
while ($row = $stmtData->fetch(PDO::FETCH_ASSOC)) {
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