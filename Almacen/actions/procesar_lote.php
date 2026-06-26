<?php
/**
 * ARCHIVO: Almacen/actions/procesar_lote.php
 * DESCRIPCIÓN: Guarda el registro logístico individual tras validar la serie, vinculando el id_ticket.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$no_serie      = isset($_POST['no_serie']) ? strtoupper(trim($_POST['no_serie'])) : '';
$contenedor    = isset($_POST['contenedor']) ? strtoupper(trim($_POST['contenedor'])) : '';
$tipo          = isset($_POST['tipo']) ? strtoupper(trim($_POST['tipo'])) : 'ORIGINAL';
$fecha_ingreso = isset($_POST['fecha_ingreso']) ? trim($_POST['fecha_ingreso']) : '';

// CORRECCIÓN: Capturamos el ID del ticket enviado desde el input oculto; si está vacío, se fuerza como NULL
$id_ticket     = !empty($_POST['id_ticket']) ? intval($_POST['id_ticket']) : null;

if (empty($no_serie) || empty($contenedor) || empty($fecha_ingreso)) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit();
}

try {
    // 1. Validar que la máquina no esté ya registrada en el almacén con un flujo abierto
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM almacen_inventario WHERE no_serie = ? AND estatus != 'ENTREGADA'");
    $stmtCheck->execute([$no_serie]);
    
    if ($stmtCheck->fetchColumn() > 0) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Este número de serie ya cuenta con un registro activo en Almacén y aún no ha sido entregado.']);
        exit();
    }

    // 2. Inserción limpia incluyendo la columna id_ticket para respetar la llave foránea
    $sqlInsert = "INSERT INTO almacen_inventario (contenedor, no_serie, tipo, estatus, fecha_ingreso_contenedor, id_ticket) 
                  VALUES (?, ?, ?, 'SIN REVISAR', ?, ?)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    
    // Pasamos las variables ordenadas al arreglo ejecutor
    $stmtInsert->execute([$contenedor, $no_serie, $tipo, $fecha_ingreso, $id_ticket]);

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "¡Excelente! La entrada del equipo con serie {$no_serie} fue registrada exitosamente amarrada al ticket del laboratorio."
    ]);
    exit();

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    exit();
}