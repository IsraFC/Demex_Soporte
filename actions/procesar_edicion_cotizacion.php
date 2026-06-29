<?php
/**
 * ARCHIVO: actions/procesar_edicion_cotizacion.php
 * DESCRIPCIÓN: Procesador de Base de Datos para el UPDATE unificado.
 * Actualiza la cotización, empaqueta los datos bancarios editados y sincroniza
 * los cambios de datos ya sea en la tabla 'formulario' o en la redirección dinámica.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 6.4 (Soporte Unificado para Edición de Leads y Recompras)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Ventas/leads_crm.php");
    exit();
}

// 1. Captura de variables desde editar_cotizacion.php
$id_cotizacion        = isset($_POST['id_cotizacion']) ? intval($_POST['id_cotizacion']) : 0;
$id_maquina           = isset($_POST['id_maquina']) ? intval($_POST['id_maquina']) : 0;
$cliente_razon        = trim($_POST['cliente'] ?? ''); 
$rfc_receptor         = !empty($_POST['rfc_receptor']) ? strtoupper(trim($_POST['rfc_receptor'])) : 'XAXX010101000';
$sucursal             = !empty($_POST['sucursal']) ? trim($_POST['sucursal']) : 'Matriz';
$direccion_entrega    = trim($_POST['direccion_entrega'] ?? '');
$cantidad             = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
$unidad               = trim($_POST['unidad'] ?? 'Pieza');
$tipo_cliente         = trim($_POST['tipo_cliente'] ?? 'Publico General');
$precio_base_origen   = floatval($_POST['precio_base_origen'] ?? 0);
$descuento_porcentaje = isset($_POST['descuento_porcentaje']) ? intval($_POST['descuento_porcentaje']) : 0;
$costo_envio          = floatval($_POST['costo_envio'] ?? 0);
$especificacion       = trim($_POST['especificion_cotizada'] ?? '');
$notes_original       = trim($_POST['notas'] ?? '');

// --- CAPTURA DE BLOQUE BANCARIO MODIFICADO DESDE LA EDICIÓN ---
$condicion_comercial = trim($_POST['condicion_comercial_bancos'] ?? 'Precios de promoción para pagos por transferencia o efectivo.');
$banco_1_nombre      = trim($_POST['banco_1_nombre'] ?? 'BANORTE');
$banco_1_cuenta      = trim($_POST['banco_1_cuenta'] ?? '0434571284');
$banco_1_clabe       = trim($_POST['banco_1_clabe'] ?? '072 650 00434571284 8');
$banco_2_nombre      = trim($_POST['banco_2_nombre'] ?? 'BANAMEX');
$banco_2_cuenta      = trim($_POST['banco_2_cuenta'] ?? '7213722');
$banco_2_clabe       = trim($_POST['banco_2_clabe'] ?? '002 650 70107213722 1');
$banco_2_sucursal    = trim($_POST['banco_2_sucursal'] ?? '7010');

// Empaquetamos los datos bancarios en un arreglo JSON codificado en base64 para proteger acentos y caracteres especiales
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

// Unimos las observaciones originales con el bloque cifrado bancario usando nuestro separador único (|||)
$notes_final = $notes_original . "|||" . $datos_bancos_empaquetados;

if ($id_cotizacion <= 0 || $id_maquina <= 0 || empty($cliente_razon)) {
    header("Location: ../Ventas/leads_crm.php?msg=error_datos");
    exit();
}

try {
    // Iniciamos la transacción para asegurar la consistencia transaccional
    $pdo->beginTransaction();

    // 2. Recálculo financiero en Backend
    $monto_descuento_unitario = $precio_base_origen * ($descuento_porcentaje / 100);
    $precio_pactado_unitario  = $precio_base_origen - $monto_descuento_unitario;

    // 3. OBTENER LOS IDs RELACIONADOS DE ORIGEN (id_prospecto e id_cliente)
    $stmt_ids = $pdo->prepare("SELECT id_prospecto, id_cliente FROM cotizacion WHERE id_cotizacion = ? LIMIT 1");
    $stmt_ids->execute([$id_cotizacion]);
    $cot_actual = $stmt_ids->fetch(PDO::FETCH_ASSOC);

    if (!$cot_actual) {
        throw new Exception("Cotización no encontrada");
    }
    
    $id_prospecto = $cot_actual['id_prospecto'];
    $id_cliente   = $cot_actual['id_cliente'];

    // Determinamos dinámicamente el destino final de la redirección
    $view_destino = (!empty($id_cliente)) ? "recompras_crm.php" : "leads_crm.php";

    // Obtener el nombre de la máquina basado en el id_maquina seleccionado
    $stmt_maq_name = $pdo->prepare("SELECT modelo FROM maquinaria WHERE id_maquina = ? LIMIT 1");
    $stmt_maq_name->execute([$id_maquina]);
    $maquina_info = $stmt_maq_name->fetch(PDO::FETCH_ASSOC);
    $modelo_texto = $maquina_info ? $maquina_info['modelo'] : '';

    // 4. PASO A: Actualizar la tabla madre 'cotizacion'
    $sql_update_cot = "UPDATE cotizacion 
                       SET id_maquina = :id_maquina,
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

    $stmt1 = $pdo->prepare($sql_update_cot);
    $stmt1->execute([
        ':id_maquina'              => $id_maquina,
        ':rfc_receptor'            => $rfc_receptor,
        ':direccion_entrega'       => $direccion_entrega,
        ':sucursal'                => $sucursal,
        ':cantidad'                => $cantidad,
        ':unidad'                  => $unidad,
        ':tipo_cliente'            => $tipo_cliente,
        ':precio_base_origen'      => $precio_base_origen,
        ':precio_pactado'          => $precio_pactado_unitario, 
        ':especificacion_cotizada' => $especificacion,
        ':costo_envio'             => $costo_envio,
        ':notes'                   => $notes_final,
        ':id_cotizacion'           => $id_cotizacion
    ]);

    // 5. PASO B: Sincronización del Lead (Solo si existe un id_prospecto válido)
    if (!empty($id_prospecto) && $id_prospecto > 0) {
        
        // Obtenemos el id_formulario desde el prospecto
        $stmt_form = $pdo->prepare("SELECT id_formulario FROM prospectos WHERE id_prospecto = ? LIMIT 1");
        $stmt_form->execute([$id_prospecto]);
        $prospecto_actual = $stmt_form->fetch(PDO::FETCH_ASSOC);
        $id_formulario = $prospecto_actual ? $prospecto_actual['id_formulario'] : null;

        if ($id_formulario) {
            $partes_nombre = explode(' ', $cliente_razon);
            $total_palabras = count($partes_nombre);

            $nuevo_nombre = '';
            $nuevos_apellidos = '';

            if ($total_palabras == 1) {
                $nuevo_nombre = $partes_nombre[0];
                $nuevos_apellidos = '';
            } elseif ($total_palabras == 2) {
                $nuevo_nombre = $partes_nombre[0];
                $nuevos_apellidos = $partes_nombre[1];
            } elseif ($total_palabras == 3) {
                $nuevo_nombre = $partes_nombre[0];
                $nuevos_apellidos = $partes_nombre[1] . ' ' . $partes_nombre[2];
            } else {
                $nuevo_nombre = $partes_nombre[0] . ' ' . $partes_nombre[1];
                $nuevos_apellidos = implode(' ', array_slice($partes_nombre, 2));
            }

            $sql_update_form = "UPDATE formulario 
                                SET nombre = :nuevo_nombre, 
                                    apellidos = :nuevos_apellidos, 
                                    maquina_interes = :maquina_interes
                                WHERE id_formulario = :id_formulario";
            
            $stmt2 = $pdo->prepare($sql_update_form);
            $stmt2->execute([
                ':nuevo_nombre'   => $nuevo_nombre,
                ':nuevos_apellidos'=> $nuevos_apellidos,
                ':maquina_interes'=> $modelo_texto,
                ':id_formulario'  => $id_formulario
            ]);
        }

        // 6. PASO C: Sincronizar el Semáforo y Tiempos de Prospección tradicionales
        $sql_update_pros = "UPDATE prospectos 
                            SET status_comercial = 'Cotizado', 
                                status_operativo = 'Cotizado',
                                fecha_ultimo_contacto = NOW()
                            WHERE id_prospecto = ?";
        $pdo->prepare($sql_update_pros)->execute([$id_prospecto]);
    }

    // Cerramos la transacción con éxito
    $pdo->commit();

    // Redirección exitosa adaptada al contexto comercial real
    header("Location: ../Ventas/" . $view_destino . "?msg=success");
    exit();

} catch (\Exception $e) {
    // Cancelamos cualquier cambio incompleto en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Si ocurre un error, determinamos a dónde regresar basándonos en los datos rescatados
    $view_error = (isset($view_destino)) ? $view_destino : "leads_crm.php";
    header("Location: ../Ventas/" . $view_error . "?msg=error&desc=" . urlencode($e->getMessage()));
    exit();
}