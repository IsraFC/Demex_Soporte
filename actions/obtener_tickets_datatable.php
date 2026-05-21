<?php
/**
 * ARCHIVO: actions/obtener_tickets_datatable.php
 * DESCRIPCIÓN: Motor de procesamiento Server-side para DataTables en DEMEX.
 * Administra la paginación, búsqueda global y filtros dinámicos directamente en MySQL.
 * * ACTUALIZACIÓN V1.2:
 * 1. Desglose de Urgencia: Envía banderas separadas para que el JS renderice la alerta en el ID.
 * 2. Integridad de Envíos: Incluye fechas de logística para la lógica visual de iconos (Truck/Tools/Box).
 * 3. Sanitización: Mantiene el estilo de documentación y seguridad de JOINS.
 * * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.2
 */

require_once '../config/db.php';

// Define la respuesta como JSON para que DataTables la interprete correctamente
header('Content-Type: application/json');

/**
 * 1. CAPTURA DE PARÁMETROS DE DATATABLES
 */
$draw   = $_POST['draw'] ?? 1;
$start  = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 13;
$searchValue = $_POST['search']['value'] ?? '';

/**
 * 2. CONSTRUCCIÓN DE LA CONSULTA BASE
 */
$queryBase = "FROM Tickets_Soporte t 
              JOIN Clientes c ON t.id_cliente = c.id_cliente
              LEFT JOIN Equipos_Garantia e ON t.no_serie = e.no_serie
              LEFT JOIN Detalles_Costos_Tiempos d ON t.id_ticket = d.id_ticket";

/**
 * 3. SISTEMA DE FILTRADO DINÁMICO (WHERE)
 */
$where = " WHERE 1=1 ";

// Búsqueda global (Cliente o No. de Serie)
if (!empty($searchValue)) {
    $where .= " AND (c.nombre_cliente LIKE '%$searchValue%' OR t.no_serie LIKE '%$searchValue%') ";
}

// Filtros de Selects (Tipo de Llamada, Falla y Acción)
if (!empty($_POST['filterTipo']))   $where .= " AND t.tipo_llamada = '" . $_POST['filterTipo'] . "' ";
if (!empty($_POST['filterFalla']))  $where .= " AND t.tipo_falla = '" . $_POST['filterFalla'] . "' ";
if (!empty($_POST['filterAccion'])) $where .= " AND d.accion = '" . $_POST['filterAccion'] . "' ";

// Filtros de Switches (1 = Activo)
if (($_POST['soloPendientes'] ?? 0) == 1) $where .= " AND t.estatus = 'Abierto' ";
if (($_POST['soloGarantia'] ?? 0) == 1)   $where .= " AND t.garantia_valida = 'Válida' ";
if (($_POST['soloDeuda'] ?? 0) == 1)      $where .= " AND d.estatus_pago = 'Pendiente' ";

// Filtros de Rango de Fechas
if (!empty($_POST['fechaDesde'])) $where .= " AND t.fecha_inicial >= '" . $_POST['fechaDesde'] . " 00:00:00' ";
if (!empty($_POST['fechaHasta'])) $where .= " AND t.fecha_inicial <= '" . $_POST['fechaHasta'] . " 23:59:59' ";

// Lógica del Botón "Ver Urgentes" (Más de 14 días abiertos)
if (($_POST['soloUrgentes'] ?? 0) == 1) {
    $where .= " AND t.estatus = 'Abierto' AND DATEDIFF(NOW(), t.fecha_inicial) >= 14 ";
}

/**
 * 4. CONTEO DE REGISTROS (Para paginación)
 */
$totalRecords = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte")->fetchColumn();
$totalRecordsWithFilter = $pdo->query("SELECT COUNT(*) $queryBase $where")->fetchColumn();

/**
 * 5. EJECUCIÓN DE LA CONSULTA PAGINADA
 */
$sql = "SELECT t.id_ticket, c.nombre_cliente, e.modelo, t.no_serie, t.tipo_falla, 
               t.garantia_valida, t.estatus, t.fecha_inicial, d.estatus_pago, t.tipo_llamada, 
               d.accion, d.fecha_inicio_acc, d.fecha_fin_acc, d.costo_total
        $queryBase 
        $where 
        ORDER BY t.id_ticket DESC 
        LIMIT $start, $length";

$stmt = $pdo->query($sql);
$data = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Calculamos la urgencia AQUÍ (Si está abierto y pasaron 14 días)
    $diff_dias = floor((time() - strtotime($row['fecha_inicial'])) / 86400);
    $esUrgente = ($row['estatus'] === 'Abierto' && $diff_dias >= 14);

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
        // Datos para la lógica visual de envíos
        "f_ini_acc"        => $row['fecha_inicio_acc'],
        "f_fin_acc"        => $row['fecha_fin_acc']
    ];
}

/**
 * 6. RETORNO DE RESULTADOS
 */
echo json_encode([
    "draw"            => intval($draw),
    "recordsTotal"    => intval($totalRecords),
    "recordsFiltered" => intval($totalRecordsWithFilter),
    "data"            => $data
]);