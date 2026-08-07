<?php
/**
 * ARCHIVO: actions/procesar_edicion_producto.php
 * DESCRIPCIÓN: Controlador Backend unificado para la actualización de productos.
 * Procesa datos generales y maneja el reemplazo dinámico de imágenes borrando los archivos físicos obsoletos.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.4 (Soporte dinámico multiplataforma para Maquinas, Bases y Saborizantes)
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

$id_producto         = isset($_POST['id_producto']) ? intval($_POST['id_producto']) : 0;
$nombre              = trim($_POST['nombre'] ?? ''); 
$precio_publico      = floatval($_POST['precio_publico'] ?? 0);
$precio_distribuidor = floatval($_POST['precio_distribuidor'] ?? 0);
$stock               = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$descripcion         = trim($_POST['descripcion'] ?? '');

if ($id_producto <= 0 || empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'Identificador o nombre de producto inválido.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. CONSULTAR EL PRODUCTO ACTUAL PARA SABER SU CATEGORÍA, SKU E IMAGEN PREVIA
    $stmt_prod = $pdo->prepare("SELECT id_categoria, sku_codigo, atributos_especificos FROM productos WHERE id_producto = ? LIMIT 1");
    $stmt_prod->execute([$id_producto]);
    $prod_actual = $stmt_prod->fetch(PDO::FETCH_ASSOC);

    if (!$prod_actual) {
        throw new Exception("El producto objetivo no existe en el sistema.");
    }

    $id_categoria = intval($prod_actual['id_categoria']);
    $sku_codigo   = $prod_actual['sku_codigo'];
    $attrs_viejos = json_decode($prod_actual['atributos_especificos'], true) ?? [];

    // Recuperamos el nombre de la imagen que ya tenía guardada en el JSON
    $nombre_imagen_final = !empty($attrs_viejos['imagen']) ? $attrs_viejos['imagen'] : null;

    // 2. DETECCIÓN DINÁMICA DE SUBIDA E IDENTIFICACIÓN DE CARPETAS DESTINO
    $input_file_name = null;
    $sub_carpeta = null;

    if ($id_categoria === 1) {
        $input_file_name = 'foto_producto_maq';
        $sub_carpeta = 'maquinas/';
    } else if ($id_categoria === 2) {
        $input_file_name = 'foto_producto_insumo';
        $sub_carpeta = 'bases/';
    } else if ($id_categoria === 3) {
        $input_file_name = 'foto_producto_insumo';
        $sub_carpeta = 'saborizantes/';
    }

    // Procesamos el reemplazo físico en disco solo si se envió un archivo binario válido
    if ($input_file_name !== null && isset($_FILES[$input_file_name]) && $_FILES[$input_file_name]['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES[$input_file_name]['tmp_name'];
        $file_name = $_FILES[$input_file_name]['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext !== 'png') {
            echo json_encode(['success' => false, 'message' => 'El formato de imagen debe ser estrictamente PNG.']);
            exit();
        }

        $directorio_destino = '../img/' . $sub_carpeta;
        if (!is_dir($directorio_destino)) {
            mkdir($directorio_destino, 0755, true);
        }

        // Si ya existía una imagen previa registrada en esa carpeta, la borramos físicamente para optimizar espacio
        if ($nombre_imagen_final !== null) {
            $ruta_imagen_vieja = $directorio_destino . $nombre_imagen_final;
            if (file_exists($ruta_imagen_vieja)) {
                unlink($ruta_imagen_vieja); 
            }
        }

        // Seteamos y renombramos el archivo final con el SKU único del registro
        $nombre_imagen_final = $sku_codigo . '.png';
        $ruta_final = $directorio_destino . $nombre_imagen_final;

        if (!move_uploaded_file($file_tmp, $ruta_final)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la nueva imagen comercial en el servidor.']);
            exit();
        }
    }

    // 3. RE-EMPAQUETADO DE ATRIBUTOS PRESERVANDO O INYECTANDO LA IMAGEN
    $atributos_armados = [];

    switch ($id_categoria) {
        case 1: // Máquinas
            $atributos_armados = [
                'linea'       => trim($_POST['attr_linea'] ?? 'Demex'),
                'tipo_helado' => trim($_POST['attr_tipo_helado'] ?? 'Suave'),
                'voltaje'     => trim($_POST['attr_voltaje'] ?? ''),
                'capacidad'   => trim($_POST['attr_capacidad'] ?? '')
            ];
            break;

        case 2: // Bases para Helado
        case 3: // Saborizantes
            $atributos_armados = [
                'sabor'       => trim($_POST['attr_sabor'] ?? ''),
                'peso'        => trim($_POST['attr_peso'] ?? ''),
                'rendimiento' => trim($_POST['attr_rendimiento'] ?? '')
            ];
            break;

        case 4: // Refacciones
            $atributos_armados = [
                'no_parte'       => strtoupper(trim($_POST['attr_no_parte'] ?? '')),
                'compatibilidad' => trim($_POST['attr_compatibilidad'] ?? '')
            ];
            break;
    }

    // Si hay una imagen registrada (previa o recién cargada), la mantenemos estructurada dentro del JSON de atributos
    if ($nombre_imagen_final !== null && $id_categoria !== 4) {
        $atributos_armados['imagen'] = $nombre_imagen_final;
    }

    $json_atributos = !empty($atributos_armados) ? json_encode($atributos_armados, JSON_UNESCAPED_UNICODE) : null;

    // 4. EJECUTAR EL UPDATE GENERAL EN LA BASE DE DATOS
    $sql_update = "UPDATE productos SET 
                        nombre = :nombre,
                        precio_publico = :precio_publico, 
                        precio_distribuidor = :precio_distribuidor, 
                        stock = :stock, 
                        descripcion = :descripcion,
                        atributos_especificos = :atributos_especificos
                   WHERE id_producto = :id_producto";

    $stmt_up = $pdo->prepare($sql_update);
    $stmt_up->execute([
        ':nombre'                => $nombre,
        ':precio_publico'        => $precio_publico,
        ':precio_distribuidor'   => $precio_distribuidor,
        ':stock'                 => $stock,
        ':descripcion'           => !empty($descripcion) ? $descripcion : null,
        ':atributos_especificos' => $json_atributos,
        ':id_producto'           => $id_producto
    ]);

    $pdo->commit();
    
    echo json_encode(['success' => true]);
    exit();

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Fallo en actualización: ' . $e->getMessage()]);
    exit();
}