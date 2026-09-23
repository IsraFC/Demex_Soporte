<?php
/**
 * ARCHIVO: actions/procesar_edicion_cotizacion.php
 * DESCRIPCIÓN: Procesador de Base de Datos para el UPDATE unificado.
 * Actualiza la cabecera 'cotizacion' y sincroniza las partidas en 'cotizacion_detalle'.
 * Soporta edición unitaria de Maquinaria y multipartida de Materia Prima.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.0 (Sincronización Transaccional con Cotización Detalle)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Ventas/leads_crm.php");
    exit();
}

// 1. Captura de identificadores base
$id_cotizacion        = isset($_POST['id_cotizacion']) ? intval($_POST['id_cotizacion']) : 0;
$tipo_cotizacion      = trim($_POST['tipo_cotizacion'] ?? 'maquinaria');
$cliente_razon        = trim($_POST['cliente'] ?? ''); 

// Sanitización de RFC
$rfc_limpio           = strtoupper(trim($_POST['rfc_receptor'] ?? ''));
$rfc_receptor         = !empty($rfc_limpio) ? $rfc_limpio : 'XAXX010101000';

$sucursal             = !empty($_POST['sucursal']) ? trim($_POST['sucursal']) : 'Matriz';
$direccion_entrega    = trim($_POST['direccion_entrega'] ?? '');
$tipo_cliente         = trim($_POST['tipo_cliente'] ?? 'Publico General');
$costo_envio          = floatval($_POST['costo_envio'] ?? 0);
$notes_original       = trim($_POST['notas'] ?? ($_POST['notes'] ?? ''));

// Fechas y vigencia
$fecha_hoy            = date('Y-m-d');
$fecha_vencimiento    = !empty($_POST['fecha_vencimiento']) ? trim($_POST['fecha_vencimiento']) : date('Y-m-d', strtotime('+15 days'));
$fecha_recordatorio   = !empty($_POST['fecha_recordatorio']) ? trim($_POST['fecha_recordatorio']) : $fecha_hoy;
$status_cotizacion    = ($fecha_vencimiento < $fecha_hoy) ? 'Vencida' : 'Vigente';

// Toggle del IVA
$incluye_iva_switch   = isset($_POST['incluye_iva']) ? intval($_POST['incluye_iva']) : 1;

// Datos Bancarios
$condicion_comercial  = trim($_POST['condicion_comercial_bancos'] ?? 'Precios de promoción para pagos por transferencia o efectivo.');
$banco_1_nombre       = trim($_POST['banco_1_nombre'] ?? 'BANORTE');
$banco_1_cuenta       = trim($_POST['banco_1_cuenta'] ?? '0434571284');
$banco_1_clabe        = trim($_POST['banco_1_clabe'] ?? '072 650 00434571284 8');
$banco_2_nombre       = trim($_POST['banco_2_nombre'] ?? 'BANAMEX');
$banco_2_cuenta       = trim($_POST['banco_2_cuenta'] ?? '7213722');
$banco_2_clabe        = trim($_POST['banco_2_clabe'] ?? '002 650 70107213722 1');
$banco_2_sucursal     = trim($_POST['banco_2_sucursal'] ?? '7010');

$datos_bancos_empaquetados = base64_encode(json_encode([
    'condicion'   => $condicion_comercial,
    'b1_nom'      => $banco_1_nombre,
    'b1_cta'      => $banco_1_cuenta,
    'b1_clabe'    => $banco_1_clabe,
    'b2_nom'      => $banco_2_nombre,
    'b2_cta'      => $banco_2_cuenta,
    'b2_clabe'      => $banco_2_clabe,
    'b2_suc'      => $banco_2_sucursal,
    'incluye_iva' => $incluye_iva_switch
]));

$notes_final = $notes_original . "|||" . $datos_bancos_empaquetados;

if ($id_cotizacion <= 0 || empty($cliente_razon)) {
    header("Location: ../Ventas/leads_crm.php?msg=error_datos");
    exit();
}

try {
    $pdo->beginTransaction();

    // 2. Verificar existencia y origen de la cotización
    $stmt_ids = $pdo->prepare("SELECT id_prospecto, id_cliente FROM cotizacion WHERE id_cotizacion = ? LIMIT 1");
    $stmt_ids->execute([$id_cotizacion]);
    $cot_actual = $stmt_ids->fetch(PDO::FETCH_ASSOC);

    if (!$cot_actual) {
        throw new Exception("Cotización no encontrada en el sistema.");
    }
    
    $id_prospecto = $cot_actual['id_prospecto'];
    $id_cliente   = $cot_actual['id_cliente'];
    $view_destino = (!empty($id_cliente) && intval($id_cliente) > 0) ? "recompras_crm.php" : "leads_crm.php";

    $id_producto_cabecera    = null;
    $cantidad_cabecera       = 1;
    $unidad_cabecera         = 'Pieza';
    $precio_base_cabecera    = 0.00;
    $precio_pactado_cabecera = 0.00;
    $especificacion_cabecera = '';
    $producto_texto_lead     = '';

    $partidas_a_sincronizar  = [];

    // ===============================================
    // CASO A: EDICIÓN EN MODO MAQUINARIA (UNITARIA)
    // ===============================================
    if ($tipo_cotizacion === 'maquinaria') {
        $id_producto_maq = isset($_POST['id_producto_maq']) ? intval($_POST['id_producto_maq']) : (isset($_POST['id_producto']) ? intval($_POST['id_producto']) : 0);
        
        if ($id_producto_maq <= 0) {
            throw new Exception("Debes seleccionar una máquina válida.");
        }

        $stmt_prod_name = $pdo->prepare("SELECT nombre FROM productos WHERE id_producto = ? LIMIT 1");
        $stmt_prod_name->execute([$id_producto_maq]);
        $prod_info = $stmt_prod_name->fetch(PDO::FETCH_ASSOC);

        if (!$prod_info) {
            throw new Exception("El producto seleccionado no existe en el catálogo.");
        }

        $cantidad_cabecera       = max(1, intval($_POST['cantidad_maq'] ?? ($_POST['cantidad'] ?? 1)));
        $unidad_cabecera         = trim($_POST['unidad'] ?? 'Pieza');
        $precio_base_cabecera    = floatval($_POST['precio_base_maq'] ?? ($_POST['precio_base_origen'] ?? 0));
        $desc_pct                = max(0, min(100, intval($_POST['descuento_porcentaje_maq'] ?? ($_POST['descuento_porcentaje'] ?? 0))));
        $especificacion_cabecera = trim($_POST['especificacion_maq'] ?? ($_POST['especificion_cotizada'] ?? ''));
        $id_producto_cabecera    = $id_producto_maq;
        $producto_texto_lead     = $prod_info['nombre'];

        $descuento_unitario      = $precio_base_cabecera * ($desc_pct / 100);
        $precio_pactado_unitario = $precio_base_cabecera - $descuento_unitario;
        $precio_pactado_cabecera = $precio_pactado_unitario * $cantidad_cabecera;

        $partidas_a_sincronizar[] = [
            'id_producto'          => $id_producto_cabecera,
            'cantidad'             => $cantidad_cabecera,
            'unidad'               => $unidad_cabecera,
            'precio_base_origen'   => $precio_base_cabecera,
            'descuento_porcentaje' => $desc_pct,
            'precio_pactado'       => $precio_pactado_unitario,
            'subtotal'             => $precio_pactado_cabecera
        ];

    // ====================================================
    // CASO B: EDICIÓN EN MODO MATERIA PRIMA (MULTIPARTIDA)
    // ====================================================
    } else {
        $partidas_post = $_POST['partidas'] ?? [];
        if (empty($partidas_post) || !is_array($partidas_post)) {
            throw new Exception("Debes registrar al menos una partida de materia prima.");
        }

        $suma_base_lista = 0;
        $suma_pactado    = 0;
        $total_articulos = 0;

        foreach ($partidas_post as $p) {
            $id_p    = intval($p['id_producto'] ?? 0);
            $cant    = intval($p['cantidad'] ?? 0);
            $p_lista = floatval($p['precio_lista'] ?? 0);
            $desc    = max(0, min(100, intval($p['descuento'] ?? 0)));
            $unid    = trim($p['unidad'] ?? 'Pieza');

            if ($id_p > 0 && $cant > 0 && $p_lista > 0) {
                $desc_unitario  = $p_lista * ($desc / 100);
                $p_pactado_unit = $p_lista - $desc_unitario;
                $subtotal_fila  = $p_pactado_unit * $cant;

                $suma_base_lista += ($p_lista * $cant);
                $suma_pactado    += $subtotal_fila;
                $total_articulos += $cant;

                $partidas_a_sincronizar[] = [
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

        if (empty($partidas_a_sincronizar)) {
            throw new Exception("No hay partidas válidas de insumos registradas.");
        }

        $id_producto_cabecera    = null; // NULL para cotizaciones multipartida
        $cantidad_cabecera       = $total_articulos;
        $unidad_cabecera         = 'Artículos';
        $precio_base_cabecera    = $suma_base_lista;
        $precio_pactado_cabecera = $suma_pactado;
        $especificacion_cabecera = "Cotización de Materia Prima e Insumos (" . count($partidas_a_sincronizar) . " partidas).";
        $producto_texto_lead     = "Materia Prima";
    }

    // 3. Actualizar la cabecera en 'cotizacion'
    $sql_update_cot = "UPDATE cotizacion 
                       SET id_producto = :id_producto,
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
                           notes = :notes,
                           fecha_vencimiento = :fecha_vencimiento,
                           status_cotizacion = :status_cotizacion,
                           fecha_recordatorio = :fecha_recordatorio
                       WHERE id_cotizacion = :id_cotizacion";

    $stmt1 = $pdo->prepare($sql_update_cot);
    $stmt1->execute([
        ':id_producto'             => $id_producto_cabecera,
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
        ':fecha_vencimiento'       => $fecha_vencimiento,
        ':status_cotizacion'       => $status_cotizacion,
        ':fecha_recordatorio'      => $fecha_recordatorio,
        ':id_cotizacion'           => $id_cotizacion
    ]);

    // 4. Sincronizar partidas en 'cotizacion_detalle' (borrado y reinserción atómica)
    $stmt_del = $pdo->prepare("DELETE FROM cotizacion_detalle WHERE id_cotizacion = ?");
    $stmt_del->execute([$id_cotizacion]);

    $sql_ins_det = "INSERT INTO cotizacion_detalle (
                        id_cotizacion, id_producto, cantidad, unidad, 
                        precio_base_origen, descuento_porcentaje, precio_pactado, subtotal
                    ) VALUES (
                        :id_cotizacion, :id_producto, :cantidad, :unidad, 
                        :precio_base_origen, :descuento_porcentaje, :precio_pactado, :subtotal
                    )";
    $stmt_ins_det = $pdo->prepare($sql_ins_det);

    foreach ($partidas_a_sincronizar as $item) {
        $stmt_ins_det->execute([
            ':id_cotizacion'        => $id_cotizacion,
            ':id_producto'          => $item['id_producto'],
            ':cantidad'             => $item['cantidad'],
            ':unidad'               => $item['unidad'],
            ':precio_base_origen'   => $item['precio_base_origen'],
            ':descuento_porcentaje' => $item['descuento_porcentaje'],
            ':precio_pactado'       => $item['precio_pactado'],
            ':subtotal'             => $item['subtotal']
        ]);
    }

    // 5. Sincronización del Lead (si proviene de un prospecto)
    if (!empty($id_prospecto) && $id_prospecto > 0) {
        $stmt_form = $pdo->prepare("SELECT id_formulario FROM prospectos WHERE id_prospecto = ? LIMIT 1");
        $stmt_form->execute([$id_prospecto]);
        $prospecto_actual = $stmt_form->fetch(PDO::FETCH_ASSOC);
        $id_formulario = $prospecto_actual ? $prospecto_actual['id_formulario'] : null;

        if ($id_formulario) {
            $sql_update_form = "UPDATE formulario 
                                SET nombre = :nuevo_nombre, 
                                    maquina_interes = :maquina_interes
                                WHERE id_formulario = :id_formulario";
            
            $stmt2 = $pdo->prepare($sql_update_form);
            $stmt2->execute([
                ':nuevo_nombre'    => $cliente_razon,
                ':maquina_interes' => $producto_texto_lead,
                ':id_formulario'   => $id_formulario
            ]);
        }

        // Sincronizar el Semáforo
        $sql_update_pros = "UPDATE prospectos 
                            SET status_comercial = 'Cotizado', 
                                status_operativo = 'Cotizado',
                                fecha_ultimo_contacto = NOW()
                            WHERE id_prospecto = ?";
        $pdo->prepare($sql_update_pros)->execute([$id_prospecto]);
    }

    $pdo->commit();

    header("Location: ../Ventas/" . $view_destino . "?msg=success");
    exit();

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $view_error = isset($view_destino) ? $view_destino : "leads_crm.php";
    header("Location: ../Ventas/" . $view_error . "?msg=error&desc=" . urlencode($e->getMessage()));
    exit();
}