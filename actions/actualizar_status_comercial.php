<?php
/**
 * ARCHIVO: actions/actualizar_status_comercial.php
 * DESCRIPCIÓN: Procesador asíncrono para actualizar el estatus comercial y migrar el prospecto ganado a cartera de clientes.
 * MODIFICACIÓN: Compatible con cotizaciones unitarias y multipartida (Bases y Saborizantes en ventas_historial).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.0 (Migración Multipartida a Historial de Ventas)
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
$fecha_compra     = !empty($_POST['fecha_compra']) ? trim($_POST['fecha_compra']) : date('Y-m-d');
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

    // 1. Actualiza el estado comercial del prospecto
    $sql = "UPDATE prospectos SET status_comercial = :status_comercial, fecha_ultimo_contacto = NOW() WHERE id_prospecto = :id_prospecto";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':status_comercial' => $status_comercial,
        ':id_prospecto'     => $id_prospecto
    ]);

    // 2. Si es 'Venta Cerrada', ejecutamos la migración a la cartera de clientes
    if ($status_comercial === 'Venta Cerrada') {
        
        // Obtener la última cotización emitida de este prospecto
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
            $nombre_cliente = $datos_venta['nombre'];
            $telefono       = $datos_venta['telefono'];
            $correo         = $datos_venta['correo'];
            $ubicacion      = $datos_venta['estado_region'];
            $id_cotizacion  = intval($datos_venta['id_cotizacion']);
            $tipo_cliente   = !empty($datos_venta['tipo_cliente']) ? $datos_venta['tipo_cliente'] : 'Publico General';
            $costo_envio    = floatval($datos_venta['costo_envio'] ?? 0);

            // Sanitización de RFC
            $rfc_crudo    = strtoupper(trim($datos_venta['rfc_receptor'] ?? ''));
            $rfc_receptor = !empty($rfc_crudo) ? $rfc_crudo : 'XAXX010101000';

            // 3. Verificar si el cliente ya existe en el catálogo unificado
            $sql_check = "SELECT id_cliente FROM clientes WHERE nombre_cliente = ? LIMIT 1";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$nombre_cliente]);
            $id_cliente = $stmt_check->fetchColumn();

            if (!$id_cliente) {
                $sql_ins_cli = "INSERT INTO clientes (nombre_cliente, telefono, correo, rfc_receptor, ubicacion, id_prospecto_origen, tipo_cliente, fecha_registro) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt_ins = $pdo->prepare($sql_ins_cli);
                $stmt_ins->execute([$nombre_cliente, $telefono, !empty($correo) ? $correo : null, $rfc_receptor, $ubicacion, $id_prospecto, $tipo_cliente]);
                $id_cliente = $pdo->lastInsertId();
            }

            // Marcar la cotización como Liberada y vincularla al cliente creado
            $pdo->prepare("UPDATE cotizacion SET estatus_seguimiento = 'Liberada', id_cliente = ? WHERE id_cotizacion = ?")
                ->execute([$id_cliente, $id_cotizacion]);

            // 4. Detectar la columna exacta en ventas_historial (id_producto o id_maquina)
            $col_prod = 'id_producto';
            try {
                $checkCol = $pdo->query("SHOW COLUMNS FROM ventas_historial LIKE 'id_producto'")->fetch();
                if (!$checkCol) {
                    $col_prod = 'id_maquina';
                }
            } catch (\Exception $e) {
                $col_prod = 'id_producto';
            }

            // 5. Consultar partidas asociadas en cotizacion_detalle
            $sql_det = "SELECT * FROM cotizacion_detalle WHERE id_cotizacion = ? ORDER BY id_detalle ASC";
            $stmt_det = $pdo->prepare($sql_det);
            $stmt_det->execute([$id_cotizacion]);
            $partidas_detalle = $stmt_det->fetchAll(PDO::FETCH_ASSOC);

            $sql_historial = "INSERT INTO ventas_historial (
                                id_cliente, id_cotizacion_origen, {$col_prod}, 
                                cantidad, precio_pactado_neto, costo_envio, 
                                fecha_compra, observaciones_venta, fecha_registro_sistema
                              ) VALUES (
                                :id_cliente, :id_cotizacion, :id_producto, 
                                :cantidad, :precio_pactado_neto, :costo_envio, 
                                :fecha_compra, :observaciones, NOW()
                              )";
            $stmt_hist = $pdo->prepare($sql_historial);

            if (!empty($partidas_detalle)) {
                // Cotización Multipartida: inyecta cada insumo/partida individual
                $flete_asignado = false;
                foreach ($partidas_detalle as $partida) {
                    // El flete se carga a la primera partida para no duplicar el monto logístico
                    $flete_item = !$flete_asignado ? $costo_envio : 0.00;
                    $flete_asignado = true;

                    $stmt_hist->execute([
                        ':id_cliente'           => $id_cliente,
                        ':id_cotizacion'        => $id_cotizacion,
                        ':id_producto'          => $partida['id_producto'],
                        ':cantidad'             => $partida['cantidad'],
                        ':precio_pactado_neto'  => $partida['precio_pactado'],
                        ':costo_envio'          => $flete_item,
                        ':fecha_compra'         => $fecha_compra,
                        ':observaciones'        => $observaciones
                    ]);
                }
            } else {
                // Cotización Clásica de Maquinaria única
                $id_prod_unico = intval($datos_venta['id_producto'] ?? ($datos_venta['id_maquina'] ?? 0));
                if ($id_prod_unico > 0) {
                    $stmt_hist->execute([
                        ':id_cliente'           => $id_cliente,
                        ':id_cotizacion'        => $id_cotizacion,
                        ':id_producto'          => $id_prod_unico,
                        ':cantidad'             => intval($datos_venta['cantidad'] ?? 1),
                        ':precio_pactado_neto'  => floatval($datos_venta['precio_pactado'] ?? 0),
                        ':costo_envio'          => $costo_envio,
                        ':fecha_compra'         => $fecha_compra,
                        ':observaciones'        => $observaciones
                    ]);
                }
            }
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