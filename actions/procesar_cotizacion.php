<?php
/**
 * @file procesar_cotizacion.php
 * @package Portal_Demex
 * @version 4.7 - Gestión Comercial Inteligente con Almacenamiento de Datos Bancarios
 * @brief Controlador encargado de registrar las cotizaciones y avanzar el estatus comercial del prospecto.
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
$rfc_receptor        = !empty($_POST['rfc_receptor']) ? strtoupper(trim($_POST['rfc_receptor'])) : 'XAXX010101000';
$direccion_entrega   = trim($_POST['direccion_entrega'] ?? '');
$sucursal            = !empty($_POST['sucursal']) ? trim($_POST['sucursal']) : 'Matriz';
$cantidad            = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
$unidad              = trim($_POST['unidad'] ?? 'Pieza');
$tipo_cliente        = trim($_POST['tipo_cliente'] ?? 'Publico General');

$precio_base_origen  = floatval($_POST['precio_base_origen'] ?? 0);
$descuento_porcentaje= isset($_POST['descuento_porcentaje']) ? intval($_POST['descuento_porcentaje']) : 0;
$costo_envio         = floatval($_POST['costo_envio'] ?? 0);

$especificacion_cotizada = trim($_POST['especificion_cotizada'] ?? '');
$notes_original          = trim($_POST['notas'] ?? '');
$maquina_seleccionada    = trim($_POST['maquina'] ?? '');

// --- NUEVO: CAPTURA Y EMPAQUETADO DE BLOQUE BANCARIO EDITABLE ---
$condicion_comercial = trim($_POST['condicion_comercial_bancos'] ?? 'Precios de promoción para pagos por transferencia o efectivo.');
$banco_1_nombre      = trim($_POST['banco_1_nombre'] ?? 'BANORTE');
$banco_1_cuenta      = trim($_POST['banco_1_cuenta'] ?? '0434571284');
$banco_1_clabe       = trim($_POST['banco_1_clabe'] ?? '072 650 00434571284 8');
$banco_2_nombre      = trim($_POST['banco_2_nombre'] ?? 'BANAMEX');
$banco_2_cuenta      = trim($_POST['banco_2_cuenta'] ?? '7213722');
$banco_2_clabe       = trim($_POST['banco_2_clabe'] ?? '002 650 70107213722 1');
$banco_2_sucursal    = trim($_POST['banco_2_sucursal'] ?? '7010');

// Creamos un array ordenado y lo pasamos a JSON base64 para guardarlo sin romper acentos ni comillas
$datos_bancos_empaquetados = base64_encode(json_encode([
    'condicion' => $condicion_comercial,
    'b1_nom'    => $banco_1_nombre,
    'b1_cta'    => $banco_1_cuenta,
    'b1_clabe'  => $banco_1_clabe,
    'b2_nom'    => $banco_2_nombre,
    'b2_cta'    => $banco_2_cuenta,
    'b2_clabe'  => $banco_2_clabe,
    'b2_suc'    => $banco_2_sucursal
]));

// Unimos las notas de la vendedora con el bloque de bancos usando un divisor único (|||)
$notes_final = $notes_original . "|||" . $datos_bancos_empaquetados;

// RECÁLCULO MATEMÁTICO COMERCIAL DEL LADO DEL SERVIDOR
$monto_descuento_unitario = $precio_base_origen * ($descuento_porcentaje / 100);
$precio_pactado_unitario  = $precio_base_origen - $monto_descuento_unitario;

// Parámetros de control de vigencias comerciales del documento PDF (Vigente / Vencida)
$fecha_emision     = date('Y-m-d');
$fecha_vencimiento = date('Y-m-d', strtotime('+15 days'));
$status_cotizacion = 'Vigente';

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

    // 2. Insertamos la cotización en la base de datos
    $sql_cotizacion = "INSERT INTO cotizacion (
        id_prospecto, id_maquina, id_usuario, rfc_receptor, direccion_entrega, 
        sucursal, cantidad, unidad, tipo_cliente, precio_base_origen, 
        precio_pactado, especificacion_cotizada, costo_envio, notes, 
        fecha_emision, fecha_vencimiento, status_cotizacion
    ) VALUES (
        :id_prospecto, :id_maquina, :id_usuario, :rfc_receptor, :direccion_entrega, 
        :sucursal, :cantidad, :unidad, :tipo_cliente, :precio_base_origen, 
        :precio_pactado, :especificacion_cotizada, :costo_envio, :notes, 
        :fecha_emision, :fecha_vencimiento, :status_cotizacion
    )";

    $stmt = $pdo->prepare($sql_cotizacion);
    $stmt->execute([
        ':id_prospecto'            => $id_prospecto > 0 ? $id_prospecto : null,
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
        ':notes'                   => $notes_final, // <-- INYECTADO: Guarda notas + JSON de bancos
        ':fecha_emision'           => $fecha_emision,
        ':fecha_vencimiento'       => $fecha_vencimiento,
        ':status_cotizacion'       => $status_cotizacion
    ]);

    $id_cotizacion_generada = $pdo->lastInsertId();

    // 3. Avanzamos de forma automática el embudo comercial del prospecto si está enlazado
    if ($id_prospecto > 0) {
        $sql_prospecto = "UPDATE prospectos SET status_comercial = 'Cotizado', fecha_ultimo_contacto = NOW() WHERE id_prospecto = ?";
        $stmt_pros = $pdo->prepare($sql_prospecto);
        $stmt_pros->execute([$id_prospecto]);
    }

    // Redirigimos directo al renderizador del documento impresible pasándole el ID
    header("Location: ../Ventas/generar_pdf_cotizacion.php?id_cotizacion=" . $id_cotizacion_generada . "&msg=success");
    exit();

} catch (\Exception $e) {
    header("Location: ../Ventas/cotizaciones.php?id_prospecto=" . $id_prospecto . "&msg=error&desc=" . urlencode($e->getMessage()));
    exit();
}