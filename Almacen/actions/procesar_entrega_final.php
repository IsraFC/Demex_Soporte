<?php
/**
 * ARCHIVO: Almacen/actions/procesar_entrega_final.php
 * DESCRIPCIÓN: Cambia estatus a ENTREGADA e inyecta la máquina con su serie real en la base instalada de garantías.
 * @project Almacén Técnico DEMEX
 * @version 6.4 - Registro de Garantía con Serie Grabada
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

if ($id_almacen <= 0 || empty($modelo) || empty($fecha_inicio) || empty($fecha_termino)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros logísticos incompletos.']);
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
    // 1. Obtener la serie real directamente desde el inventario si no vino en el POST
    if (empty($no_serie)) {
        $stmtGetSerie = $pdo->prepare("SELECT no_serie FROM almacen_inventario WHERE id = ?");
        $stmtGetSerie->execute([$id_almacen]);
        $no_serie = trim($stmtGetSerie->fetchColumn());
    }

    if (empty($no_serie) || $no_serie === 'SIN SERIE') {
        $pdo->rollBack();
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'La máquina seleccionada no tiene un número de serie válido asignado.']);
        exit();
    }

    // 2. Validar que la serie no esté usada en la base instalada de garantías
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM equipos_garantia WHERE no_serie = ?");
    $stmtCheck->execute([$no_serie]);
    if ($stmtCheck->fetchColumn() > 0) {
        $pdo->rollBack();
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => "La serie técnica {$no_serie} ya se encuentra registrada en la base instalada de garantías."]);
        exit();
    }

    // 3. Registro en caliente del cliente nuevo si aplica
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

    // 4. Actualizamos la unidad en almacén (estatus a ENTREGADA y fecha de entrega)
    $sqlAlmacen = "UPDATE almacen_inventario 
                   SET estatus = 'ENTREGADA', fecha_entrega_cliente = :fecha_entrega 
                   WHERE id = :id";
    $stmtAlmacen = $pdo->prepare($sqlAlmacen);
    $stmtAlmacen->execute([
        ':fecha_entrega' => $fecha_inicio,
        ':id'            => $id_almacen
    ]);

    // 5. Inyección en la tabla maestro de equipos_garantia
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

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "¡Despliegue exitoso! La máquina con serie {$no_serie} ha sido entregada y su póliza de garantía se encuentra activa."
    ]);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()]);
    exit();
}