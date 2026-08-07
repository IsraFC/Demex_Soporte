<?php
/**
 * ARCHIVO: actions/procesar_alta_producto.php
 * DESCRIPCIÓN: Controlador Backend para el registro centralizado de productos.
 * Procesa la información técnica e incluye el enrutamiento dinámico de imágenes PNG por categorías.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.3 (Enrutamiento dinámico y creación física de carpetas para Maquinas, Bases y Saborizantes)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración estricta de cabecera para devolver JSON puro
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de acceso no permitido.']);
    exit();
}

// 1. CAPTURA Y SANITIZACIÓN DE DATOS GENERALES
$id_categoria       = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 0;
$nombre             = trim($_POST['nombre'] ?? '');
$sku_codigo         = strtoupper(trim($_POST['sku_codigo'] ?? ''));
$precio_publico     = floatval($_POST['precio_publico'] ?? 0);
$precio_distribuidor = floatval($_POST['precio_distribuidor'] ?? 0);
$stock              = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$descripcion        = trim($_POST['descripcion'] ?? '');

if ($id_categoria <= 0 || empty($nombre) || empty($sku_codigo)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, llena todos los campos obligatorios.']);
    exit();
}

// 2. DETECCIÓN DINÁMICA DE ENRUTAMIENTO DE ARCHIVO SEGÚN CATEGORÍA
$nombre_imagen_final = null;
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

// Procesamos el archivo binario solo si pertenece a una categoría válida con soporte de imágenes
if ($input_file_name !== null && isset($_FILES[$input_file_name]) && $_FILES[$input_file_name]['error'] === UPLOAD_ERR_OK) {
    $file_tmp  = $_FILES[$input_file_name]['tmp_name'];
    $file_name = $_FILES[$input_file_name]['name'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validamos estrictamente que sea extensión PNG
    if ($file_ext !== 'png') {
        echo json_encode(['success' => false, 'message' => 'El formato de imagen debe ser estrictamente PNG.']);
        exit();
    }

    // Definimos la ruta de destino dentro de la subcarpeta correspondiente
    $directorio_destino = '../img/' . $sub_carpeta;
    
    // Si la carpeta física no existe en el servidor, la creamos con los permisos correctos
    if (!is_dir($directorio_destino)) {
        mkdir($directorio_destino, 0755, true);
    }

    // Renombramos la imagen usando el SKU único
    $nombre_imagen_final = $sku_codigo . '.png';
    $ruta_final = $directorio_destino . $nombre_imagen_final;

    // Movemos el archivo temporal al directorio final
    if (!move_uploaded_file($file_tmp, $ruta_final)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo de imagen en el servidor.']);
        exit();
    }
}

// 3. CONSTRUCCIÓN DEL BLOQUE DE ATRIBUTOS ESPECÍFICOS (JSON DINÁMICO)
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

// Si se subió una imagen con éxito (en cualquier categoría admitida), la anexamos limpia al JSON
if ($nombre_imagen_final !== null) {
    $atributos_armados['imagen'] = $nombre_imagen_final;
}

$json_atributos = !empty($atributos_armados) ? json_encode($atributos_armados, JSON_UNESCAPED_UNICODE) : null;

try {
    $pdo->beginTransaction();

    // 4. VALIDACIÓN DE SKU DUPLICADO
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE sku_codigo = ?");
    $stmt_check->execute([$sku_codigo]);
    if ($stmt_check->fetchColumn() > 0) {
        // Limpieza física preventiva
        if ($nombre_imagen_final !== null && isset($ruta_final) && file_exists($ruta_final)) {
            unlink($ruta_final);
        }
        throw new Exception("El código SKU '{$sku_codigo}' ya se encuentra registrado en el catálogo.");
    }

    // 5. INSERCIÓN EN LA TABLA MAESTRA 'productos'
    $sql_insert = "INSERT INTO productos (
                        id_categoria, nombre, sku_codigo, descripcion, 
                        precio_publico, precio_distribuidor, stock, atributos_especificos, fecha_registro
                    ) VALUES (
                        :id_categoria, :nombre, :sku_codigo, :descripcion, 
                        :precio_publico, :precio_distribuidor, :stock, :atributos_especificos, NOW()
                    )";

    $stmt = $pdo->prepare($sql_insert);
    $stmt->execute([
        ':id_categoria'          => $id_categoria,
        ':nombre'                => $nombre,
        ':sku_codigo'            => $sku_codigo,
        ':descripcion'           => !empty($descripcion) ? $descripcion : null,
        ':precio_publico'        => $precio_publico,
        ':precio_distribuidor'   => $precio_distribuidor,
        ':stock'                 => $stock,
        ':atributos_especificos' => $json_atributos
    ]);

    $pdo->commit();

    echo json_encode(['success' => true]); 
    exit();

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Limpieza física del archivo en caso de fallo crítico en base de datos
    if ($nombre_imagen_final !== null && isset($ruta_final) && file_exists($ruta_final)) {
        unlink($ruta_final);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}