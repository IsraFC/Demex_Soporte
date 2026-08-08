<?php
/**
 * ARCHIVO: Almacen/actions/actualizar_fase.php
 * DESCRIPCIÓN: Ejecuta la actualización física de estatus, guarda la serie técnica y marca la fecha de fase.
 * @project Almacén Técnico DEMEX
 * @version 6.3 - Registro de Serie con Validación Única
 * @author Israel Fernández Carrera
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no autorizado.']);
    exit();
}

$id            = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nuevo_estatus = isset($_POST['nuevo_estatus']) ? trim($_POST['nuevo_estatus']) : '';
$campo_fecha   = isset($_POST['campo_fecha']) ? trim($_POST['campo_fecha']) : '';
$fecha_fase    = isset($_POST['fecha_fase']) ? trim($_POST['fecha_fase']) : '';
$no_serie      = isset($_POST['no_serie']) ? strtoupper(trim($_POST['no_serie'])) : '';

if ($id <= 0 || empty($nuevo_estatus) || empty($campo_fecha) || empty($fecha_fase)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Parámetros incompletos o ID de equipo no válido para actualizar la fase.'
    ]);
    exit();
}

$columnas_permitidas = [
    'fecha_inicio_ajustes_almacen',
    'fecha_disponible_soporte',
    'fecha_entrega_soporte',
    'fecha_reingreso_almacen',
    'fecha_entrega_cliente'
];

if (!in_array($campo_fecha, $columnas_permitidas)) {
    echo json_encode(['success' => false, 'message' => 'Columna de auditoría no válida.']);
    exit();
}

try {
    // Si se capturó número de serie, validamos que no exista en otra máquina
    if (!empty($no_serie)) {
        $stmtCheckInv = $pdo->prepare("SELECT id FROM almacen_inventario WHERE no_serie = ? AND id != ?");
        $stmtCheckInv->execute([$no_serie, $id]);
        if ($stmtCheckInv->fetch()) {
            echo json_encode(['success' => false, 'message' => "El número de serie '{$no_serie}' ya pertenece a otra unidad en inventario."]);
            exit();
        }

        $stmtCheckGar = $pdo->prepare("SELECT no_serie FROM equipos_garantia WHERE no_serie = ?");
        $stmtCheckGar->execute([$no_serie]);
        if ($stmtCheckGar->fetch()) {
            echo json_encode(['success' => false, 'message' => "El número de serie '{$no_serie}' ya se encuentra en la base instalada de garantías."]);
            exit();
        }

        $sql = "UPDATE almacen_inventario 
                SET estatus = :nuevo_estatus, no_serie = :no_serie, $campo_fecha = :fecha_fase 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            ':nuevo_estatus' => $nuevo_estatus,
            ':no_serie'      => $no_serie,
            ':fecha_fase'    => $fecha_fase,
            ':id'            => $id
        ]);
    } else {
        $sql = "UPDATE almacen_inventario 
                SET estatus = :nuevo_estatus, $campo_fecha = :fecha_fase 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            ':nuevo_estatus' => $nuevo_estatus,
            ':fecha_fase'    => $fecha_fase,
            ':id'            => $id
        ]);
    }

    if ($resultado) {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true,
            'message' => "El equipo avanzó a la fase de {$nuevo_estatus}" . (!empty($no_serie) ? " con serie '{$no_serie}'." : ".")
        ]);
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios en el registro.']);
    }
    exit();

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error de MySQL: ' . $e->getMessage()]);
    exit();
}