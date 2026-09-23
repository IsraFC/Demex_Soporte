<?php
/**
 * ARCHIVO: actions/procesar_cotizacion.php
 * DESCRIPCIÓN: Controlador para registrar cotizaciones en 'cotizacion' y 'cotizacion_detalle'.
 * Soporta modalidad unitaria (Maquinaria) y multipartida dinámica (Materia Prima).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.0 (Soporte Multipartida en Cotización Detalle)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Ventas/cotizaciones.php");
    exit();
}

$id_usuario          = $_SESSION['id_usuario'] ?? 1;
$id_prospecto        = isset($_POST['id_prospecto']) ? intval($_POST['id_prospecto']) : 0;
$id_cliente_recompra = isset($_POST['id_cliente_recompra']) ? intval($_POST['id_cliente_recompra']) : 0;
$tipo_cotizacion     = trim($_POST['tipo_cotizacion'] ?? 'maquinaria');

// Datos del receptor y logísticos
$rfc_limpio        = strtoupper(trim($_POST['rfc_receptor'] ?? ''));
$rfc_receptor      = !empty($rfc_limpio) ? $rfc_limpio : 'XAXX010101000';
$direccion_entrega = trim($_POST['direccion_entrega'] ?? '');
$sucursal          = !empty($_POST['sucursal']) ? trim($_POST['sucursal']) : 'Matriz';
$tipo_cliente      = trim($_POST['tipo_cliente'] ?? 'Publico General');
$costo_envio       = floatval($_POST['costo_envio'] ?? 0);
$notes_original    = trim($_POST['notes'] ?? ($_POST['notas'] ?? ''));

// Toggle IVA
$incluye_iva_switch = isset($_POST['incluye_iva']) ? intval($_POST['incluye_iva']) : 1;

// Fechas comerciales
$fecha_emision      = date('Y-m-d');
$fecha_vencimiento  = !empty($_POST['fecha_vencimiento']) ? trim($_POST['fecha_vencimiento']) : date('Y-m-d', strtotime('+15 days'));
$fecha_recordatorio = !empty($_POST['fecha_recordatorio']) ? trim($_POST['fecha_recordatorio']) : $fecha_emision;
$status_cotizacion  = ($fecha_vencimiento < $fecha_emision) ? 'Vencida' : 'Vigente';

// Empaquetado de datos bancarios
$condicion_comercial = trim($_POST['condicion_comercial_bancos'] ?? 'Precios de promoción para pagos por transferencia o efectivo.');
$banco_1_nombre      = trim($_POST['banco_1_nombre'] ?? 'BANORTE');
$banco_1_cuenta      = trim($_POST['banco_1_cuenta'] ?? '0434571284');
$banco_1_clabe       = trim($_POST['banco_1_clabe'] ?? '072 650 00434571284 8');
$banco_2_nombre      = trim($_POST['banco_2_nombre'] ?? 'BANAMEX');
$banco_2_cuenta      = trim($_POST['banco_2_cuenta'] ?? '7213722');
$banco_2_clabe       = trim($_POST['banco_2_clabe'] ?? '002 650 70107213722 1');
$banco_2_sucursal    = trim($_POST['banco_2_sucursal'] ?? '7010');

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

$notes_final = $notes_original . "|||" . $datos_bancos_empaquetados;

try {
    $pdo->beginTransaction();

    $id_producto_cabecera = null;
    $cantidad_cabecera = 1;
    $unidad_cabecera = 'Pieza';
    $precio_base_cabecera = 0.00;
    $precio_pactado_cabecera = 0.00;
    $especificacion_cabecera = '';
    $nombre_producto_actualizar_lead = '';

    $partidas_a_insertar = [];

    // ===============================================
    // CASO A: COTIZACIÓN DE MAQUINARIA (UNITARIA)
    // ===============================================
    if ($tipo_cotizacion === 'maquinaria') {
        $id_producto_maq = isset($_POST['id_producto_maq']) ? intval($_POST['id_producto_maq']) : 0;
        if ($id_producto_maq <= 0) {
            throw new Exception("Debes seleccionar una máquina válida.");
        }

        $stmt_prod = $pdo->prepare("SELECT nombre FROM productos WHERE id_producto = ? LIMIT 1");
        $stmt_prod->execute([$id_producto_maq]);
        $prod_info = $stmt_prod->fetch(PDO::FETCH_ASSOC);

        if (!$prod_info) {
            throw new Exception("La máquina seleccionada no existe en el catálogo.");
        }

        $id_producto_cabecera   = $id_producto_maq;
        $cantidad_cabecera      = max(1, intval($_POST['cantidad_maq'] ?? 1));
        $unidad_cabecera        = 'Pieza';
        $precio_base_cabecera   = floatval($_POST['precio_base_maq'] ?? 0);
        $desc_pct               = max(0, min(100, intval($_POST['descuento_porcentaje_maq'] ?? 0)));
        $especificacion_cabecera= trim($_POST['especificacion_maq'] ?? '');
        $nombre_producto_actualizar_lead = $prod_info['nombre'];

        $descuento_unitario     = $precio_base_cabecera * ($desc_pct / 100);
        $precio_pactado_unitario= $precio_base_cabecera - $descuento_unitario;
        $precio_pactado_cabecera= $precio_pactado_unitario * $cantidad_cabecera;

        $partidas_a_insertar[] = [
            'id_producto'          => $id_producto_cabecera,
            'cantidad'             => $cantidad_cabecera,
            'unidad'               => $unidad_cabecera,
            'precio_base_origen'   => $precio_base_cabecera,
            'descuento_porcentaje' => $desc_pct,
            'precio_pactado'       => $precio_pactado_unitario,
            'subtotal'             => $precio_pactado_cabecera
        ];

    // ===============================================
    // CASO B: COTIZACIÓN DE MATERIA PRIMA (MÚLTIPLE)
    // ===============================================
    } else {
        $partidas_post = $_POST['partidas'] ?? [];
        if (empty($partidas_post) || !is_array($partidas_post)) {
            throw new Exception("Debes agregar al menos un producto a la cotización de materia prima.");
        }

        $suma_base_lista = 0;
        $suma_pactado = 0;
        $total_articulos = 0;

        foreach ($partidas_post as $p) {
            $id_p = intval($p['id_producto'] ?? 0);
            $cant = intval($p['cantidad'] ?? 0);
            $p_lista = floatval($p['precio_lista'] ?? 0);
            $desc = max(0, min(100, intval($p['descuento'] ?? 0)));
            $unid = trim($p['unidad'] ?? 'Pieza');

            if ($id_p > 0 && $cant > 0 && $p_lista > 0) {
                $desc_unitario = $p_lista * ($desc / 100);
                $p_pactado_unit = $p_lista - $desc_unitario;
                $subtotal_fila = $p_pactado_unit * $cant;

                $suma_base_lista += ($p_lista * $cant);
                $suma_pactado += $subtotal_fila;
                $total_articulos += $cant;

                $partidas_a_insertar[] = [
                    'id_producto'          => $id_p,
                    'cantidad'             => $cant,
                    'unidad'               => $unid,
                    'precio_base_origen'   => $p_lista,
                    'descuento_porcentaje' => $desc,
                    'precio_pactado'       => $p_pactado_unit,
                    'subtotal'             => $subtotal_fila
                ];
            }
        }

        if (empty($partidas_a_insertar)) {
            throw new Exception("No se registraron partidas válidas con precio y cantidad mayor a cero.");
        }

        $id_producto_cabecera    = null; // NULL en cabecera para identificar cotización multipartida
        $cantidad_cabecera       = $total_articulos;
        $unidad_cabecera         = 'Artículos';
        $precio_base_cabecera    = $suma_base_lista;
        $precio_pactado_cabecera = $suma_pactado;
        $especificacion_cabecera = "Cotización de Materia Prima e Insumos (" . count($partidas_a_insertar) . " partidas).";
        $nombre_producto_actualizar_lead = "Materia Prima";
    }

    // 1. Inserción en la tabla madre 'cotizacion'
    $sql_cotizacion = "INSERT INTO cotizacion (
        id_prospecto, id_cliente, id_producto, id_usuario, rfc_receptor, 
        direccion_entrega, sucursal, cantidad, unidad, tipo_cliente, 
        precio_base_origen, precio_pactado, especificacion_cotizada, 
        costo_envio, notes, fecha_emision, fecha_vencimiento, status_cotizacion, fecha_recordatorio
    ) VALUES (
        :id_prospecto, :id_cliente, :id_producto, :id_usuario, :rfc_receptor, 
        :direccion_entrega, :sucursal, :cantidad, :unidad, :tipo_cliente, 
        :precio_base_origen, :precio_pactado, :especificacion_cotizada, 
        :costo_envio, :notes, :fecha_emision, :fecha_vencimiento, :status_cotizacion, :fecha_recordatorio
    )";

    $stmt_cot = $pdo->prepare($sql_cotizacion);
    $stmt_cot->execute([
        ':id_prospecto'            => $id_prospecto > 0 ? $id_prospecto : null,
        ':id_cliente'              => $id_cliente_recompra > 0 ? $id_cliente_recompra : null,
        ':id_producto'             => $id_producto_cabecera,
        ':id_usuario'              => $id_usuario,
        ':rfc_receptor'            => $rfc_receptor,
        ':direccion_entrega'       => $direccion_entrega,
        ':sucursal'                => $sucursal,
        ':cantidad'                => $cantidad_cabecera,
        ':unidad'                  => $unidad_cabecera,
        ':tipo_cliente'            => $tipo_cliente,
        ':precio_base_origen'      => $precio_base_cabecera,
        ':precio_pactado'          => $precio_pactado_cabecera,
        ':especificacion_cotizada' => $especificacion_cabecera,
        ':costo_envio'             => $costo_envio,
        ':notes'                   => $notes_final,
        ':fecha_emision'           => $fecha_emision,
        ':fecha_vencimiento'       => $fecha_vencimiento,
        ':status_cotizacion'       => $status_cotizacion,
        ':fecha_recordatorio'      => $fecha_recordatorio
    ]);

    $id_cotizacion_generada = $pdo->lastInsertId();

    // 2. Inserción de las partidas en 'cotizacion_detalle'
    $sql_det = "INSERT INTO cotizacion_detalle (
        id_cotizacion, id_producto, cantidad, unidad, 
        precio_base_origen, descuento_porcentaje, precio_pactado, subtotal
    ) VALUES (
        :id_cotizacion, :id_producto, :cantidad, :unidad, 
        :precio_base_origen, :descuento_porcentaje, :precio_pactado, :subtotal
    )";
    $stmt_det = $pdo->prepare($sql_det);

    foreach ($partidas_a_insertar as $item) {
        $stmt_det->execute([
            ':id_cotizacion'        => $id_cotizacion_generada,
            ':id_producto'          => $item['id_producto'],
            ':cantidad'             => $item['cantidad'],
            ':unidad'               => $item['unidad'],
            ':precio_base_origen'   => $item['precio_base_origen'],
            ':descuento_porcentaje' => $item['descuento_porcentaje'],
            ':precio_pactado'       => $item['precio_pactado'],
            ':subtotal'             => $item['subtotal']
        ]);
    }

    // 3. Sincronización del Prospecto (si aplica)
    if ($id_prospecto > 0) {
        $stmt_form = $pdo->prepare("SELECT id_formulario FROM prospectos WHERE id_prospecto = ? LIMIT 1");
        $stmt_form->execute([$id_prospecto]);
        $prospecto_row = $stmt_form->fetch(PDO::FETCH_ASSOC);

        if ($prospecto_row && !empty($nombre_producto_actualizar_lead)) {
            $pdo->prepare("UPDATE formulario SET maquina_interes = ? WHERE id_formulario = ?")
                ->execute([$nombre_producto_actualizar_lead, $prospecto_row['id_formulario']]);
        }

        $pdo->prepare("UPDATE prospectos SET status_comercial = 'Cotizado', fecha_ultimo_contacto = NOW() WHERE id_prospecto = ?")
            ->execute([$id_prospecto]);
    }

    $pdo->commit();

    $param_msg = ($id_cliente_recompra > 0) ? "success_recompra" : "success";
    header("Location: ../Ventas/generar_pdf_cotizacion.php?id_cotizacion=" . $id_cotizacion_generada . "&msg=" . $param_msg);
    exit();

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../Ventas/cotizaciones.php?id_prospecto=" . $id_prospecto . "&cliente_recompra=" . $id_cliente_recompra . "&error=" . urlencode($e->getMessage()));
    exit();
}