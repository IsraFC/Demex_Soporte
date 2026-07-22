<?php
/**
 * ARCHIVO: actions/procesar_alta_producto.php
 * DESCRIPCIÓN: Controlador Backend para el registro centralizado de productos.
 * Valida la información del catálogo y empaqueta los atributos dinámicos en formato JSON.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.1 (Adaptado estrictamente para respuestas síncronas AJAX JSON)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORREGIDO: Configuración estricta de cabecera para devolver JSON puro
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

// CORREGIDO: Validación responde con JSON estructurado para interceptarse en la vista
if ($id_categoria <= 0 || empty($nombre) || empty($sku_codigo)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, llena todos los campos obligatorios.']);
    exit();
}

// 2. CONSTRUCCIÓN DEL BLOQUE DE ATRIBUTOS ESPECÍFICOS (JSON DINÁMICO)
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

$json_atributos = !empty($atributos_armados) ? json_encode($atributos_armados, JSON_UNESCAPED_UNICODE) : null;

try {
    $pdo->beginTransaction();

    // 3. VALIDACIÓN DE SKU DUPLICADO
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE sku_codigo = ?");
    $stmt_check->execute([$sku_codigo]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("El código SKU '{$sku_codigo}' ya se encuentra registrado en el catálogo.");
    }

    // 4. INSERCIÓN EN LA TABLA MAESTRA 'productos'
    $sql_insert = "INSERT INTO productos (
                        id_categoria, nombre, sku_codigo, descripcion, 
                        precio_publico, precio_distribuidor, stock, atributos_especificos, fecha_registro
                    ) VALUES (
                        :id_categoria, :nombre, :sku_codigo, :descripcion, 
                        :precio_publico, :precio_distribuidor, :stock, :atributos_especificos, NOW()
                    )";

    $stmt = $pdo->prepare($sql_insert);
    $stmt->execute([
        ':id_categoria'        => $id_categoria,
        ':nombre'              => $nombre,
        ':sku_codigo'          => $sku_codigo,
        ':descripcion'         => !empty($descripcion) ? $descripcion : null,
        ':precio_publico'      => $precio_publico,
        ':precio_distribuidor' => $precio_distribuidor,
        ':stock'               => $stock,
        ':atributos_especificos' => $json_atributos
    ]);

    $pdo->commit();

    // Respuesta de éxito limpia leída por el AJAX de SweetAlert
    echo json_encode(['success' => true]); 
    exit();

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // CORREGIDO: El catch ahora también devuelve un objeto JSON en lugar de una redirección header
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}