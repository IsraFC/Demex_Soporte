<?php
/**
 * ARCHIVO: actions/procesar_importacion_clientes.php
 * DESCRIPCIÓN: Motor asíncrono de importación masiva para el catálogo e historial de clientes (CSV).
 * Sincroniza identidades compartidas con soporte y alimenta la tabla ventas_historial.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 - Arquitectura Comercial Asíncrona Blindada
 */

ini_set('max_execution_time', 600);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';

// ==========================================
// --- FUNCIONES AUXILIARES DE LIMPIEZA ---
// ==========================================

function limpiarDinero($valor) {
    return (float)preg_replace('/[^0-9.]/', '', $valor);
}

function convertirFecha($fecha) {
    if (empty($fecha) || strpos($fecha, '#') !== false) return null;
    $fecha = str_replace('-', '/', trim($fecha));
    $p = explode('/', $fecha);
    if (count($p) === 3) {
        // Asegurar formato de año de 4 dígitos
        $anio = (strlen($p[2]) == 2) ? '20' . $p[2] : $p[2];
        return "$anio-$p[1]-$p[0]";
    }
    return null;
}

function normalizarEnum($valor, $opciones, $default) {
    $valor_busqueda = mb_strtolower(trim($valor), 'UTF-8');
    foreach ($opciones as $opcion) {
        if (mb_strtolower($opcion, 'UTF-8') == $valor_busqueda) return $opcion;
    }
    return $default;
}

// ==========================================
// --- INICIO DE PROCESAMIENTO ---
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_csv'])) {
    $file = $_FILES['archivo_csv']['tmp_name'];
    
    if (empty($file) || !is_uploaded_file($file)) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Archivo no Válido',
            'text' => 'No se pudo leer el archivo cargado en el servidor.'
        ]);
        exit();
    }

    if (($handle = fopen($file, "r")) !== FALSE) {
        // Identificación automática de separador (Coma o Punto y Coma)
        $firstLine = fgets($handle);
        $separador = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle);

        try {
            $pdo->beginTransaction();
            $insertados = 0; 
            $fila = 0;

            // ORDEN REQUERIDO CSV: 
            // [0]Nombre, [1]Apellidos, [2]Teléfono, [3]Correo, [4]Ubicación, [5]Tipo Cliente, [6]Modelo Máquina, [7]Cantidad, [8]Precio Pactado, [9]Fecha Compra
            while (($data = fgetcsv($handle, 2000, $separador)) !== FALSE) {
                $fila++;
                if ($fila <= 2) continue; // Saltamos las líneas de encabezados tradicionales

                $nombre_cli = trim($data[0] ?? '');
                $telefono   = trim($data[2] ?? '');
                
                if (empty($nombre_cli)) continue; // Saltamos renglones vacíos

                $apellidos    = trim($data[1] ?? '');
                $correo       = trim($data[3] ?? '');
                $ubicacion    = trim($data[4] ?? '');
                $tipo_cli     = normalizarEnum($data[5] ?? '', ['Publico General', 'Distribuidor'], 'Publico General');
                $modelo_csv   = trim($data[6] ?? '');
                $cantidad     = isset($data[7]) ? intval($data[7]) : 1;
                $precio_neto  = limpiarDinero($data[8] ?? 0);
                $fecha_compra = convertirFecha($data[9] ?? '') ?: date('Y-m-d');

                // --- 1. BUSCAR ID REAL DE MAQUINARIA EN EL CATÁLOGO EN BD ---
                $id_maquina_real = 1; // Valor default de seguridad por si no se encuentra
                $stmt_maq = $pdo->prepare("SELECT id_maquina FROM maquinaria WHERE modelo = ? LIMIT 1");
                $stmt_maq->execute([$modelo_csv]);
                $maq_row = $stmt_maq->fetch();
                if ($maq_row) {
                    $id_maquina_real = $maq_row['id_maquina'];
                } else {
                    // Si no coincide exacto, intentamos una búsqueda parcial sutil
                    $stmt_maq_like = $pdo->prepare("SELECT id_maquina FROM maquinaria WHERE modelo LIKE ? LIMIT 1");
                    $stmt_maq_like->execute(['%' . $modelo_csv . '%']);
                    $maq_like_row = $stmt_maq_like->fetch();
                    if ($maq_like_row) {
                        $id_maquina_real = $maq_like_row['id_maquina'];
                    }
                }

                // --- 2. IDENTIFICACIÓN / INSERCIÓN UNIFICADA DE CLIENTE ---
                // Buscamos si ya existe por nombre completo para evitar duplicar catálogos compartidos
                $st = $pdo->prepare("SELECT id_cliente FROM clientes WHERE nombre_cliente = ? AND apellidos_cliente = ? LIMIT 1");
                $st->execute([$nombre_cli, $apellidos]);
                $id_cliente = $st->fetchColumn();

                if (!$id_cliente) {
                    // Insertamos el nuevo cliente expandido con la estructura que agregamos a MySQL
                    $sql_ins_cli = "INSERT INTO clientes (nombre_cliente, apellidos_cliente, telefono, correo, ubicacion, tipo_cliente, fecha_registro) 
                                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    $ins = $pdo->prepare($sql_ins_cli);
                    $ins->execute([$nombre_cli, $apellidos, $telefono, $correo, $ubicacion, $tipo_cli]);
                    $id_cliente = $pdo->lastInsertId();
                } else {
                    // Si el cliente ya existía (ej. de soporte), actualizamos sus datos comerciales por si acaso
                    $sql_upd_cli = "UPDATE clientes SET correo = IFNULL(correo, ?), ubicacion = IFNULL(ubicacion, ?) WHERE id_cliente = ?";
                    $pdo->prepare($sql_upd_cli)->execute([$correo, $ubicacion, $id_cliente]);
                }

                // --- 3. INSERCIÓN DEL HISTORIAL DE VENTAS GRANULAR ---
                // Alimentamos de forma directa la nueva tabla relacional que creamos
                $sql_historial = "INSERT INTO ventas_historial (id_cliente, id_cotizacion_origen, id_maquina, cantidad, precio_pactado_neto, costo_envio, fecha_compra, observaciones_venta, fecha_registro_sistema) 
                                  VALUES (:id_cliente, NULL, :id_maquina, :cantidad, :precio_pactado_neto, 0.00, :fecha_compra, 'Importación masiva histórica desde archivo de base.', NOW())";
                
                $stH = $pdo->prepare($sql_historial);
                $stH->execute([
                    ':id_cliente'          => $id_cliente,
                    ':id_maquina'          => $id_maquina_real,
                    ':cantidad'            => $cantidad,
                    ':precio_pactado_neto' => $precio_neto,
                    ':fecha_compra'        => $fecha_compra
                ]);

                $insertados++;
            }
            
            $pdo->commit();
            fclose($handle);

            echo json_encode([
                'status' => 'success',
                'title' => '¡Base de Clientes Migrada!',
                'text' => "Se procesaron exitosamente {$insertados} registros históricos con sus desgloses de compra al CRM."
            ]);
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            fclose($handle);
            echo json_encode([
                'status' => 'error',
                'title' => 'Falla en Fila ' . $fila,
                'text' => 'Error de consistencia transaccional en carga comercial: ' . $e->getMessage()
            ]);
            exit();
        }
    }
} else {
    echo json_encode(['status' => 'error', 'title' => 'Acceso Denegado', 'text' => 'Petición incorrecta.']);
    exit();
}