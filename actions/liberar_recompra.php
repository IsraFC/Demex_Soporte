<?php
/**
 * ARCHIVO: actions/liberar_recompra.php
 * DESCRIPCIÓN: Controlador asíncrono (AJAX JSON) para liberar una recompra comercial.
 * Registra exclusivamente la venta en el histórico mercantil de la empresa.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 3.0 (Procesador Exclusivo de Facturación Comercial)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de acceso no permitido.']);
    exit();
}

$id_cotizacion  = isset($_POST['id_cotizacion']) ? intval($_POST['id_cotizacion']) : 0;
$observaciones  = !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : 'Recompra liberada formalmente desde el Pipeline Comercial.';
$fecha_compra   = date('Y-m-d');

if ($id_cotizacion <= 0) {
    echo json_encode(['success' => false, 'message' => 'Identificador de cotización inválido.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Extraer los montos y datos de la cotización
    $sql_cot = "SELECT * FROM cotizacion WHERE id_cotizacion = :id_cotizacion LIMIT 1";
    $stmt_cot = $pdo->prepare($sql_cot);
    $stmt_cot->execute([':id_cotizacion' => $id_cotizacion]);
    $cot_data = $stmt_cot->fetch(PDO::FETCH_ASSOC);

    if (!$cot_data) {
        throw new Exception("La cotización objetivo no existe en el sistema.");
    }

    $id_cliente  = $cot_data['id_cliente'];
    $id_maquina  = $cot_data['id_maquina'];
    $cantidad    = intval($cot_data['cantidad']);
    $precio_neto = floatval($cot_data['precio_pactado']);
    $costo_envio = floatval($cot_data['costo_envio']);

    // 2. Cambiar el estatus comercial de la cotización
    $sql_up_cot = "UPDATE cotizacion SET estatus_seguimiento = 'Liberada' WHERE id_cotizacion = :id_cotizacion";
    $stmt_up_cot = $pdo->prepare($sql_up_cot);
    $stmt_up_cot->execute([':id_cotizacion' => $id_cotizacion]);

    // 3. Inyectar exclusivamente al histórico contable mercantil (Ventas)
    $sql_historial = "INSERT INTO ventas_historial (id_cliente, id_cotizacion_origen, id_maquina, cantidad, precio_pactado_neto, costo_envio, fecha_compra, observaciones_venta, fecha_registro_sistema) 
                      VALUES (:id_cliente, :id_cotizacion, :id_maquina, :cantidad, :precio_pactado_neto, :costo_envio, :fecha_compra, :observaciones, NOW())";
    
    $stmt_hist = $pdo->prepare($sql_historial);
    $stmt_hist->execute([
        ':id_cliente'           => $id_cliente,
        ':id_cotizacion'         => $id_cotizacion,
        ':id_maquina'           => $id_maquina,
        ':cantidad'             => $cantidad,
        ':precio_pactado_neto'  => $precio_neto,
        ':costo_envio'          => $costo_envio,
        ':fecha_compra'         => $fecha_compra,
        ':observaciones'        => $observaciones
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => '¡Recompra autorizada e integrada al historial de facturación comercial con éxito!']);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Fallo en consistencia: ' . $e->getMessage()]);
}
exit();