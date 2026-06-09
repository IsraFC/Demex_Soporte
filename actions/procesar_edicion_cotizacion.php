<?php
/**
 * ARCHIVO: actions/procesar_edicion_cotizacion.php
 * DESCRIPCIÓN: Procesador de Base de Datos para el UPDATE estructural completo de cotizaciones.
 * Recalcula los subtotales netos en base a los descuentos de porcentaje modificados.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 4.2
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Ventas/leads_crm.php");
    exit();
}

// Captura exhaustiva de las variables del formulario unificado
$id_cotizacion        = isset($_POST['id_cotizacion']) ? intval($_POST['id_cotizacion']) : 0;
$cliente_razon        = trim($_POST['cliente'] ?? 'Público General');
$rfc_receptor         = !empty($_POST['rfc_receptor']) ? strtoupper(trim($_POST['rfc_receptor'])) : 'XAXX010101000';
$sucursal             = !empty($_POST['sucursal']) ? trim($_POST['sucursal']) : 'Matriz';
$direccion_entrega    = trim($_POST['direccion_entrega'] ?? '');
$cantidad             = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
$unidad               = trim($_POST['unidad'] ?? 'Pieza');
$maquina_modelo       = trim($_POST['maquina'] ?? '');
$tipo_cliente         = trim($_POST['tipo_cliente'] ?? 'Publico General');
$precio_base_origen   = floatval($_POST['precio_base_origen'] ?? 0);
$descuento_porcentaje = isset($_POST['descuento_porcentaje']) ? intval($_POST['descuento_porcentaje']) : 0;
$costo_envio          = floatval($_POST['costo_envio'] ?? 0);
$especificacion       = trim($_POST['especificion_cotizada'] ?? '');
$notas                = trim($_POST['notas'] ?? '');

if ($id_cotizacion <= 0) {
    header("Location: ../Ventas/leads_crm.php?msg=error");
    exit();
}

try {
    // 1. Buscamos la llave foránea id_maquina correspondiente al modelo seleccionado en el combo
    $sql_maquina = "SELECT id_maquina FROM maquinaria WHERE modelo = ? LIMIT 1";
    $stmt_maq = $pdo->prepare($sql_maquina);
    $stmt_maq->execute([$maquina_modelo]);
    $id_maquina_real = $stmt_maq->fetchColumn();

    if (!$id_maquina_real) {
        header("Location: ../Ventas/leads_crm.php?msg=error");
        exit();
    }

    // 2. RECÁLCULO MATEMÁTICO COMERCIAL EN BACKEND (Estilo procesar_cotizacion.php)
    $monto_descuento_unitario = $precio_base_origen * ($descuento_porcentaje / 100);
    $precio_pactado_unitario  = $precio_base_origen - $monto_descuento_unitario;

    // 3. Sentencia SQL de actualización completa
    $sql_update = "UPDATE cotizacion 
                   SET id_maquina = :id_maquina,
                       razon_social = :razon_social,
                       rfc_receptor = :rfc_receptor,
                       direccion_entrega = :direccion_entrega,
                       sucursal = :sucursal,
                       cantidad = :cantidad,
                       unidad = :unidad,
                       tipo_cliente = :tipo_cliente,
                       precio_base_origen = :precio_base_origen,
                       precio_pactado = :precio_pactado,
                       especificacion_cotizada = :especificacion_cotizada,
                       costo_envio = :costo_envio,
                       notes = :notes
                   WHERE id_cotizacion = :id_cotizacion";

    $stmt = $pdo->prepare($sql_update);
    $stmt->execute([
        ':id_maquina'              => $id_maquina_real,
        ':razon_social'            => $cliente_razon,
        ':rfc_receptor'            => $rfc_receptor,
        ':direccion_entrega'       => $direccion_entrega,
        ':sucursal'                => $sucursal,
        ':cantidad'                => $cantidad,
        ':unidad'                  => $unidad,
        ':tipo_cliente'            => $tipo_cliente,
        ':precio_base_origen'      => $precio_base_origen,
        ':precio_pactado'          => $precio_pactado_unitario, // Precio neto unitario ya con descuento aplicado
        ':especificacion_cotizada' => $especificacion,
        ':costo_envio'             => $costo_envio,
        ':notes'                   => $notas,
        ':id_cotizacion'           => $id_cotizacion
    ]);

    // Redirección exitosa con bandera para SweetAlert de leads_crm
    header("Location: ../Ventas/leads_crm.php?msg=success");
    exit();

} catch (\Exception $e) {
    header("Location: ../Ventas/leads_crm.php?msg=error");
    exit();
}