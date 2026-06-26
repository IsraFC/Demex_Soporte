<?php
/**
 * ARCHIVO: Almacen/actions/actualizar_fase.php
 * DESCRIPCIÓN: Ejecuta la actualización física del estatus y guarda la fecha de la etapa en la base de datos.
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
    $sql = "UPDATE almacen_inventario 
            SET estatus = :nuevo_estatus, $campo_fecha = :fecha_fase 
            WHERE id = :id";
            
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([
        ':nuevo_estatus' => $nuevo_estatus,
        ':fecha_fase'    => $fecha_fase,
        ':id'            => $id
    ]);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => "El equipo avanzó correctamente a la fase de " . $nuevo_estatus . "."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios en el registro.']);
    }
    exit();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno de MySQL: ' . $e->getMessage()]);
    exit();
}