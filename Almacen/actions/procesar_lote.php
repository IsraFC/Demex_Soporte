<?php
/**
 * ARCHIVO: Almacen/actions/procesar_lote.php
 * DESCRIPCIÓN: Guarda el registro logístico individual tras validar la serie.
 */
require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$no_serie     = isset($_POST['no_serie']) ? strtoupper(trim($_POST['no_serie'])) : '';
$contenedor   = isset($_POST['contenedor']) ? strtoupper(trim($_POST['contenedor'])) : '';
$tipo         = isset($_POST['tipo']) ? strtoupper(trim($_POST['tipo'])) : 'ORIGINAL';
$fecha_ingreso = isset($_POST['fecha_ingreso']) ? trim($_POST['fecha_ingreso']) : '';

if (empty($no_serie) || empty($contenedor) || empty($fecha_ingreso)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit();
}

try {
    // 1. Validar que la máquina no esté ya registrada en el almacén con un flujo abierto
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM almacen_inventario WHERE no_serie = ? AND estatus != 'ENTREGADA'");
    $stmtCheck->execute([$no_serie]);
    
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Este número de serie ya cuenta con un registro activo en Almacén y aún no ha sido entregado.']);
        exit();
    }

    // 2. Inserción limpia e individual
    $sqlInsert = "INSERT INTO almacen_inventario (contenedor, no_serie, tipo, estatus, fecha_ingreso_contenedor) 
                  VALUES (?, ?, ?, 'SIN REVISAR', ?)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([$contenedor, $no_serie, $tipo, $fecha_ingreso]);

    echo json_encode([
        'success' => true,
        'message' => "¡Excelente! La entrada del equipo con serie {$no_serie} fue registrada exitosamente."
    ]);
    exit();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    exit();
}