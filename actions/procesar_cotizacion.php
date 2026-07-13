<?php
/**
 * @file actions/procesar_cotizacion.php
 * @package Portal_Demex
 * @version 7.2 - Soporte para RFC Opcional Genérico Automatizado y Dirección Abierta
 * @brief Controlador encargado de registrar las cotizaciones y avanzar el estatus comercial del prospecto o cliente.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

// Validamos el método de acceso de forma estricta
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Ventas/cotizaciones.php");
    exit();
}

// Control e identificación del asesor logueado desde la sesión corporativa
$id_usuario = $_SESSION['id_usuario'] ?? 1;

// CAPTURA Y SANITIZACIÓN DE DATOS
$id_prospecto        = isset($_POST['id_prospecto']) ? intval($_POST['id_prospecto']) : 0;
$id_cliente_recompra = isset($_POST['id_cliente_recompra']) ? intval($_POST['id_cliente_recompra']) : 0;

// MODIFICADO: Si el RFC viene vacío del formulario, asignamos rigurosamente el genérico oficial por seguridad
$rfc_limpio          = strtoupper(trim($_POST['rfc_receptor'] ?? ''));
$rfc_receptor        = !empty($rfc_limpio) ? $rfc_limpio : 'XAXX010101000';

$direccion_entrega   = trim($_POST['direccion_entrega'] ?? '');
$sucursal            = !empty($_POST['sucursal']) ? trim($_POST['sucursal']) : 'Matriz';
$cantidad            = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
$unidad              = trim($_POST['unidad'] ?? 'Pieza');
$tipo_cliente        = trim($_POST['tipo_cliente'] ?? 'Publico General');

// Se recibe el precio base origen tal cual se manipuló en el cliente editable
$precio_base_origen  = floatval($_POST['precio_base_origen'] ?? 0);
$descuento_porcentaje= isset($_POST['descuento_porcentaje']) ? intval($_POST['descuento_porcentaje']) : 0;
$costo_envio         = floatval($_POST['costo_envio'] ?? 0);

$especificacion_cotizada = trim($_POST['especificion_cotizada'] ?? '');
$notes_original          = trim($_POST['notas'] ?? '');
$maquina_seleccionada    = trim($_POST['maquina'] ?? '');

// Captura del Toggle Button del IVA (1 = Incluye, 0 = Exento)
$incluye_iva_switch      = isset($_POST['incluye_iva']) ? intval($_POST['incluye_iva']) : 1;

// NUEVO: Captura de Fechas Manuales desde el Formulario
$fecha_emision        = date('Y-m-d');
$fecha_vencimiento    = !empty($_POST['fecha_vencimiento']) ? trim($_POST['fecha_vencimiento']) : date('Y-m-d', strtotime('+15 days'));
$fecha_recordatorio   = !empty($_POST['fecha_recordatorio']) ? trim($_POST['fecha_recordatorio']) : $fecha_emision;

// --- CAPTURA Y EMPAQUETADO DE BLOQUE BANCARIO EDITABLE ---
$condicion_comercial = trim($_POST['condicion_comercial_bancos'] ?? 'Precios de promoción para pagos por transferencia o efectivo.');
$banco_1_nombre      = trim($_POST['banco_1_nombre'] ?? 'BANORTE');
$banco_1_cuenta      = trim($_POST['banco_1_cuenta'] ?? '0434571284');
$banco_1_clabe       = trim($_POST['banco_1_clabe'] ?? '072 650 00434571284 8');
$banco_2_nombre      = trim($_POST['banco_2_nombre'] ?? 'BANAMEX');
$banco_2_cuenta      = trim($_POST['banco_2_cuenta'] ?? '7213722');
$banco_2_clabe       = trim($_POST['banco_2_clabe'] ?? '002 650 70107213722 1');
$banco_2_sucursal    = trim($_POST['banco_2_sucursal'] ?? '7010');

// Agregamos el parámetro 'incluye_iva' al paquete estructurado JSON para que persista en el PDF
$datos_bancos_empaquetados = base64_encode(json_encode([
    'condicion'   => $condicion_comercial,
    'b1_nom'      => $banco_1_nombre,
    'b1_cta'      => $banco_1_cuenta,
    'b1_clabe'    => $banco_1_clabe,
    'b2_nom'      => $banco_2_nombre,
    'b2_cta'      => $banco_2_cuenta,
    'b2_clabe'    => $banco_2_clabe,
    'b2_suc'      => $banco_2_sucursal,
    'incluye_iva' => $incluye_iva_switch
]));

// Unimos las notas de la vendedora con el bloque de bancos usando un divisor único (|||)
$notes_final = $notes_original . "|||" . $datos_bancos_empaquetados;

// RECÁLCULO MATEMÁTICO COMERCIAL DEL LADO DEL SERVIDOR (Respetando el precio base enviado)
$monto_descuento_unitario = $precio_base_origen * ($descuento_porcentaje / 100);
$precio_pactado_unitario  = $precio_base_origen - $monto_descuento_unitario;

// NUEVO: Validación dinámica de estatus en base a la fecha actual real
$status_cotizacion = ($fecha_vencimiento < $fecha_emision) ? 'Vencida' : 'Vigente';

try {
    // 1. Buscamos el ID de la maquinaria
    $sql_maquina = "SELECT id_maquina FROM maquinaria WHERE modelo = ? LIMIT 1";
    $stmt_maq = $pdo->prepare($sql_maquina);
    $stmt_maq->execute([$maquina_seleccionada]);
    $maquinaria_row = $stmt_maq->fetch();

    if (!$maquinaria_row) {
        header("Location: ../Ventas/cotizaciones.php?error=maquina_no_existente");
        exit();
    }

    $id_maquina_real = $maquinaria_row['id_maquina'];

    // 2. Insertamos la cotización incluyendo los nuevos campos relacionales de control de tiempos
    $sql_cotizacion = "INSERT INTO cotizacion (
        id_prospecto, id_cliente, id_maquina, id_usuario, rfc_receptor, 
        direccion_entrega, sucursal, cantidad, unidad, tipo_cliente, 
        precio_base_origen, precio_pactado, especificacion_cotizada, 
        costo_envio, notes, fecha_emision, fecha_vencimiento, status_cotizacion, fecha_recordatorio
    ) VALUES (
        :id_prospecto, :id_cliente, :id_maquina, :id_usuario, :rfc_receptor, 
        :direccion_entrega, :sucursal, :cantidad, :unidad, :tipo_cliente, 
        :precio_base_origen, :precio_pactado, :especificacion_cotizada, 
        :costo_envio, :notes, :fecha_emision, :fecha_vencimiento, :status_cotizacion, :fecha_recordatorio
    )";

    $stmt = $pdo->prepare($sql_cotizacion);

    $stmt->execute([
        ':id_prospecto'            => $id_prospecto > 0 ? $id_prospecto : null,
        ':id_cliente'              => $id_cliente_recompra > 0 ? $id_cliente_recompra : null,
        ':id_maquina'              => $id_maquina_real,
        ':id_usuario'              => $id_usuario,
        ':rfc_receptor'            => $rfc_receptor,
        ':direccion_entrega'       => $direccion_entrega,
        ':sucursal'                => $sucursal,
        ':cantidad'                => $cantidad,
        ':unidad'                  => $unidad,
        ':tipo_cliente'            => $tipo_cliente,
        ':precio_base_origen'      => $precio_base_origen,
        ':precio_pactado'          => $precio_pactado_unitario,
        ':especificacion_cotizada' => $especificacion_cotizada,
        ':costo_envio'             => $costo_envio,
        ':notes'                   => $notes_final,
        ':fecha_emision'           => $fecha_emision,
        ':fecha_vencimiento'       => $fecha_vencimiento,
        ':status_cotizacion'       => $status_cotizacion,
        ':fecha_recordatorio'      => $fecha_recordatorio
    ]);

    $id_cotizacion_generada = $pdo->lastInsertId();

    // 3. CONTROL DE FLUJO DE REDIRECCIÓN INTELIGENTE (Redirección unificada directa a visualizador PDF)
    if ($id_cliente_recompra > 0 && $id_prospecto <= 0) {
        header("Location: ../Ventas/generar_pdf_cotizacion.php?id_cotizacion=" . $id_cotizacion_generada . "&msg=success_recompra");
        exit();
    } else {
        $sql_update_lead = "UPDATE prospectos SET status_comercial = 'Cotizado', fecha_ultimo_contacto = NOW() WHERE id_prospecto = ?";
        $stmt_up = $pdo->prepare($sql_update_lead);
        $stmt_up->execute([$id_prospecto]);

        header("Location: ../Ventas/generar_pdf_cotizacion.php?id_cotizacion=" . $id_cotizacion_generada . "&msg=success");
        exit();
    }

} catch (PDOException $e) {
    header("Location: ../Ventas/cotizaciones.php?msg=error&desc=" . urlencode($e->getMessage()));
    exit();
}