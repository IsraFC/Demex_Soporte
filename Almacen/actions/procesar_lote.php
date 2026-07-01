<?php
/**
 * ARCHIVO: Almacen/actions/procesar_lote.php
 * DESCRIPCIÓN: Guarda de forma individual la maquinaria nueva de importación incluyendo su modelo.
 * @project Almacén Técnico DEMEX
 * @version 5.1 - Inserción de Lote con Mapeo de Modelo
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$no_serie      = isset($_POST['no_serie']) ? strtoupper(trim($_POST['no_serie'])) : '';
$modelo        = isset($_POST['modelo']) ? trim($_POST['modelo']) : '';
$contenedor    = isset($_POST['contenedor']) ? strtoupper(trim($_POST['contenedor'])) : '';
$tipo          = isset($_POST['tipo']) ? strtoupper(trim($_POST['tipo'])) : 'ORIGINAL';
$fecha_ingreso = isset($_POST['fecha_ingreso']) ? trim($_POST['fecha_ingreso']) : '';

// Validamos estrictamente que los nuevos campos requeridos no vengan vacíos
if (empty($no_serie) || empty($modelo) || empty($contenedor) || empty($fecha_ingreso)) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Todos los campos (Serie, Modelo, Contenedor y Fecha) son completamente obligatorios.']);
    exit();
}

try {
    // 1. Validar que la serie de la máquina de importación no exista ya activa en el almacén
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM almacen_inventario WHERE no_serie = ? AND estatus != 'ENTREGADA'");
    $stmtCheck->execute([$no_serie]);
    
    if ($stmtCheck->fetchColumn() > 0) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => "Error: El número de serie {$no_serie} ya tiene un registro de stock activo en las bodegas del almacén."]);
        exit();
    }

    // 2. Inserción limpia incluyendo el modelo seleccionado
    $sqlInsert = "INSERT INTO almacen_inventario (contenedor, no_serie, modelo, tipo, estatus, fecha_ingreso_contenedor) 
                  VALUES (?, ?, ?, ?, 'SIN REVISAR', ?)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([$contenedor, $no_serie, $modelo, $tipo, $fecha_ingreso]);

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "¡Excelente! La maquinaria nueva modelo {$modelo} con serie {$no_serie} fue dada de alta en el lote logístico {$contenedor}."
    ]);
    exit();

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Falla interna en base de datos: ' . $e->getMessage()]);
    exit();
}