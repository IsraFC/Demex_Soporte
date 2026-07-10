<?php
/**
 * ARCHIVO: actions/actualizar_status_comercial.php
 * DESCRIPCIÓN: Procesador asíncrono para actualizar el estatus comercial y migrar el prospecto ganado a cartera de clientes.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 3.1 (Unificación de Nombre/Razón Social y Eliminación de Apellidos en Clientes)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$id_prospecto     = isset($_POST['id_prospecto']) ? intval($_POST['id_prospecto']) : 0;
$status_comercial = isset($_POST['status_comercial']) ? trim($_POST['status_comercial']) : '';
$fecha_compra     = isset($_POST['fecha_compra']) ? trim($_POST['fecha_compra']) : date('Y-m-d');
$observaciones    = !empty($_POST['observaciones_venta']) ? trim($_POST['observaciones_venta']) : 'Cierre de venta y liberación automática desde el panel de Leads.';

if ($id_prospecto <= 0 || empty($status_comercial)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos.']);
    exit();
}

$estados_permitidos = ['Consultado', 'Cotizado', 'Venta Cerrada'];
if (!in_array($status_comercial, $estados_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Estatus comercial no válido.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Actualiza el estado comercial de la venta humana en prospectos
    $sql = "UPDATE prospectos SET status_comercial = :status_comercial WHERE id_prospecto = :id_prospecto";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':status_comercial' => $status_comercial,
        ':id_prospecto'     => $id_prospecto
    ]);

    // 2. Si es 'Venta Cerrada', ejecutamos de forma automatizada la mutación de lead a cliente
    if ($status_comercial === 'Venta Cerrada') {
        
        // Jalamos la última cotización activa de este prospecto para obtener los costos acordados y el modelo
        // Modificado: Se removió la columna apellidos de la consulta
        $sql_cot = "SELECT c.*, f.nombre, f.telefono, f.correo, f.estado_region
                    FROM cotizacion c
                    INNER JOIN prospectos p ON c.id_prospecto = p.id_prospecto
                    INNER JOIN formulario f ON p.id_formulario = f.id_formulario
                    WHERE p.id_prospecto = ? 
                    ORDER BY c.id_cotizacion DESC LIMIT 1";
        
        $stmt_cot = $pdo->prepare($sql_cot);
        $stmt_cot->execute([$id_prospecto]);
        $datos_venta = $stmt_cot->fetch(PDO::FETCH_ASSOC);

        if ($datos_venta) {
            $nombre_cliente    = $datos_venta['nombre']; // Ahora actúa como Nombre Completo / Razón Social
            $telefono          = $datos_venta['telefono'];
            $correo            = $datos_venta['correo'];
            $ubicacion         = $datos_venta['estado_region'];
            $id_maquina        = $datos_venta['id_maquina'];
            $cantidad          = $datos_venta['cantidad'];
            $precio_pactado    = $datos_venta['precio_pactado'];
            $costo_envio       = $datos_venta['costo_envio'];
            $id_cotizacion     = $datos_venta['id_cotizacion'];
            $tipo_cliente      = $datos_venta['tipo_cliente'];
            $rfc_receptor      = $datos_venta['rfc_receptor'];

            // 3. Verificamos si este cliente ya existe en el catálogo unificado usando solo la Razón Social / Nombre Único
            $sql_check = "SELECT id_cliente FROM clientes WHERE nombre_cliente = ? LIMIT 1";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$nombre_cliente]);
            $id_cliente = $stmt_check->fetchColumn();

            if (!$id_cliente) {
                // Inserción en catálogo unificado (Columna apellidos_cliente removida de la estructura)
                $sql_ins_cli = "INSERT INTO clientes (nombre_cliente, telefono, correo, rfc_receptor, ubicacion, id_prospecto_origen, tipo_cliente, fecha_registro) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt_ins = $pdo->prepare($sql_ins_cli);
                $stmt_ins->execute([$nombre_cliente, $telefono, !empty($correo) ? $correo : null, $rfc_receptor, $ubicacion, $id_prospecto, $tipo_cliente]);
                $id_cliente = $pdo->lastInsertId();
            }

            // 4. Inyectamos la compra en el historial granular de la cartera de clientes
            $sql_historial = "INSERT INTO ventas_historial (id_cliente, id_cotizacion_origen, id_maquina, cantidad, precio_pactado_neto, costo_envio, fecha_compra, observaciones_venta, fecha_registro_sistema) 
                              VALUES (:id_cliente, :id_cotizacion, :id_maquina, :cantidad, :precio_pactado_neto, :costo_envio, :fecha_compra, :observaciones, NOW())";
            
            $stmt_hist = $pdo->prepare($sql_historial);
            $stmt_hist->execute([
                ':id_cliente'          => $id_cliente,
                ':id_cotizacion'       => $id_cotizacion,
                ':id_maquina'          => $id_maquina,
                ':cantidad'            => $cantidad,
                ':precio_pactado_neto' => $precio_pactado,
                ':costo_envio'         => $costo_envio,
                ':fecha_compra'        => $fecha_compra,
                ':observaciones'       => $observaciones
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Operación comercial cerrada y migrada a cartera de clientes.']);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
exit();