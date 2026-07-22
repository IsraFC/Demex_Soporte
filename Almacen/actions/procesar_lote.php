<?php
/**
 * ARCHIVO: Almacen/actions/procesar_lote.php
 * DESCRIPCIÓN: Procesador transaccional para alta de lotes y generación automática de unidades.
 * @project Almacén Técnico DEMEX
 * @version 6.0
 * @author Israel Fernández Carrera
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
    exit();
}

$contenedor    = trim($_POST['contenedor'] ?? '');
$tipo          = trim($_POST['tipo'] ?? 'ORIGINAL');
$fecha_ingreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');
$modelos       = $_POST['modelos'] ?? [];
$cantidades    = $_POST['cantidades'] ?? [];

if (empty($contenedor) || empty($modelos) || empty($cantidades)) {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos obligatorios del lote.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Validar que el contenedor no exista
    $stmtCheck = $pdo->prepare("SELECT id_lote FROM almacen_lotes WHERE contenedor = ?");
    $stmtCheck->execute([$contenedor]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => "El identificador de contenedor '{$contenedor}' ya fue registrado previamente."]);
        $pdo->rollBack();
        exit();
    }

    // 2. Insertar Lote Maestro
    $sqlLote = "INSERT INTO almacen_lotes (contenedor, tipo, fecha_ingreso) VALUES (?, ?, ?)";
    $stmtLote = $pdo->prepare($sqlLote);
    $stmtLote->execute([$contenedor, $tipo, $fecha_ingreso]);
    $id_lote = $pdo->lastInsertId();

    // 3. Generar unidades individuales para cada modelo según la cantidad capturada
    $sqlUnidad = "INSERT INTO almacen_inventario (id_lote, contenedor, modelo, tipo, estatus, fecha_ingreso_contenedor) 
                  VALUES (?, ?, ?, ?, 'SIN REVISAR', ?)";
    $stmtUnidad = $pdo->prepare($sqlUnidad);

    $total_maquinas_registradas = 0;

    for ($i = 0; $i < count($modelos); $i++) {
        $modelo_actual = trim($modelos[$i]);
        $cant_actual   = intval($cantidades[$i]);

        if (!empty($modelo_actual) && $cant_actual > 0) {
            for ($k = 0; $k < $cant_actual; $k++) {
                $stmtUnidad->execute([$id_lote, $contenedor, $modelo_actual, $tipo, $fecha_ingreso]);
                $total_maquinas_registradas++;
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Se ha generado con éxito el lote '{$contenedor}' con un total de {$total_maquinas_registradas} unidades en inventario."
    ]);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    exit();
}