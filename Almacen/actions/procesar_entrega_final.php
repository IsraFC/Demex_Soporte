<?php
/**
 * ARCHIVO: Almacen/actions/procesar_entrega_final.php
 * DESCRIPCIÓN: Procesa el cierre de flujo logístico e inyecta el equipo en la base instalada de garantías.
 * @project Almacén Técnico DEMEX
 * @version 2.2 - Sincronizado con Procesamiento de Vigencias Dinámicas
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no autorizado.']);
    exit();
}

$id_almacen    = isset($_POST['id_almacen']) ? intval($_POST['id_almacen']) : 0;
$no_serie      = isset($_POST['no_serie']) ? strtoupper(trim($_POST['no_serie'])) : '';
$modelo        = isset($_POST['modelo']) ? trim($_POST['modelo']) : '';
$fecha_inicio  = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
$fecha_termino = isset($_POST['fecha_termino']) ? trim($_POST['fecha_termino']) : '';

$es_cliente_nuevo = isset($_POST['es_cliente_nuevo']) && $_POST['es_cliente_nuevo'] == '1';
$id_cliente       = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;

$nuevo_nombre    = isset($_POST['nuevo_nombre']) ? trim($_POST['nuevo_nombre']) : '';
$nuevo_telefono  = isset($_POST['nuevo_telefono']) ? trim($_POST['nuevo_telefono']) : '';
$nueva_ubicacion = isset($_POST['nueva_ubicacion']) ? trim($_POST['nueva_ubicacion']) : '';

if ($id_almacen <= 0 || empty($no_serie) || empty($modelo) || empty($fecha_inicio) || empty($fecha_termino)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros logísticos o de cálculo de vigencia incompletos.']);
    exit();
}

if (!$es_cliente_nuevo && $id_cliente <= 0) {
    echo json_encode(['success' => false, 'message' => 'Debes seleccionar un cliente válido de la lista desplegable.']);
    exit();
}

if ($es_cliente_nuevo && (empty($nuevo_nombre) || empty($nueva_ubicacion))) {
    echo json_encode(['success' => false, 'message' => 'El nombre y la ubicación del nuevo cliente son obligatorios.']);
    exit();
}

$pdo->beginTransaction();

try {
    // 1. Control de duplicados en garantías
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM equipos_garantia WHERE no_serie = ?");
    $stmtCheck->execute([$no_serie]);
    if ($stmtCheck->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => "La serie técnica {$no_serie} ya se encuentra registrada en el catálogo maestro de garantías."]);
        exit();
    }

    // 2. Registro en caliente del nuevo cliente si aplica
    if ($es_cliente_nuevo) {
        $stmtClientCheck = $pdo->prepare("SELECT id_cliente FROM clientes WHERE nombre_cliente = ? LIMIT 1");
        $stmtClientCheck->execute([$nuevo_nombre]);
        $cliente_existente_id = $stmtClientCheck->fetchColumn();

        if ($cliente_existente_id) {
            $id_cliente = intval($cliente_existente_id);
        } else {
            $sqlCliente = "INSERT INTO clientes (nombre_cliente, telefono, ubicacion) VALUES (:nombre, :telefono, :ubicacion)";
            $stmtCliente = $pdo->prepare($sqlCliente);
            $stmtCliente->execute([
                ':nombre'    => $nuevo_nombre,
                ':telefono'  => !empty($nuevo_telefono) ? $nuevo_telefono : null,
                ':ubicacion' => $nueva_ubicacion
            ]);
            $id_cliente = intval($pdo->lastInsertId());
        }
    }

    // 3. Cerramos el estatus logístico en el almacén
    $sqlAlmacen = "UPDATE almacen_inventario 
                   SET estatus = 'ENTREGADA', fecha_entrega_cliente = :fecha_entrega 
                   WHERE id = :id";
    $stmtAlmacen = $pdo->prepare($sqlAlmacen);
    $stmtAlmacen->execute([
        ':fecha_entrega' => $fecha_inicio,
        ':id'            => $id_almacen
    ]);

    // 4. Inyección en la tabla maestro de base instalada con las fechas procesadas
    $sqlGarantia = "INSERT INTO equipos_garantia (no_serie, id_cliente, modelo, fecha_inicio, fecha_termino) 
                    VALUES (:no_serie, :id_cliente, :modelo, :fecha_inicio, :fecha_termino)";
    $stmtGarantia = $pdo->prepare($sqlGarantia);
    $stmtGarantia->execute([
        ':no_serie'      => $no_serie,
        ':id_cliente'    => $id_cliente,
        ':modelo'        => $modelo,
        ':fecha_inicio'  => $fecha_inicio,
        ':fecha_termino' => $fecha_termino
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "¡Operación autorizada! El equipo con serie {$no_serie} fue guardado correctamente en la base instalada con su vigencia de póliza calculada."
    ]);
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error de consistencia SQL: ' . $e->getMessage()]);
    exit();
}