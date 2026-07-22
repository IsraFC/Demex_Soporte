<?php
/**
 * ARCHIVO: Almacen/actions/procesar_entrega_final.php
 * DESCRIPCIÓN: Guarda el número de serie, cambia estatus a ENTREGADA e inyecta en la base instalada.
 * @project Almacén Técnico DEMEX
 * @version 6.1 - Sincronizado con Asignación de Serie en Caliente
 * @author Israel Fernández Carrera
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
    echo json_encode(['success' => false, 'message' => 'Parámetros logísticos o número de serie incompletos.']);
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
    // 1. Validar que la serie no esté usada en garantías ni por otra unidad activa
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM equipos_garantia WHERE no_serie = ?");
    $stmtCheck->execute([$no_serie]);
    if ($stmtCheck->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => "La serie técnica {$no_serie} ya existe registrada en la base instalada de garantías."]);
        exit();
    }

    // 2. Registro en caliente del cliente nuevo si aplica
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

    // 3. Actualizamos la unidad en almacén (S/N, estatus a ENTREGADA y fecha)
    $sqlAlmacen = "UPDATE almacen_inventario 
                   SET no_serie = :no_serie, estatus = 'ENTREGADA', fecha_entrega_cliente = :fecha_entrega 
                   WHERE id = :id";
    $stmtAlmacen = $pdo->prepare($sqlAlmacen);
    $stmtAlmacen->execute([
        ':no_serie'      => $no_serie,
        ':fecha_entrega' => $fecha_inicio,
        ':id'            => $id_almacen
    ]);

    // 4. Inyección en la tabla maestro de equipos_garantia
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
        'message' => "¡Despliegue exitoso! La máquina con serie {$no_serie} ha sido entregada y su póliza se encuentra activa."
    ]);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()]);
    exit();
}