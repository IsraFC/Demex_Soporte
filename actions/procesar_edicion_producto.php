<?php
/**
 * ARCHIVO: actions/procesar_edicion_producto.php
 * DESCRIPCIÓN: Controlador Backend unificado para la actualización de productos.
 * Procesa datos financieros generales y re-empaqueta los campos específicos en JSON.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 (Procesador Único Global de Edición)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de respuesta estricta JSON para interactuar con tu CRM
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de acceso no permitido.']);
    exit();
}

// 1. CAPTURA DE DATOS MAESTROS
$id_producto         = isset($_POST['id_producto']) ? intval($_POST['id_producto']) : 0;
$precio_publico      = floatval($_POST['precio_publico'] ?? 0);
$precio_distribuidor = floatval($_POST['precio_distribuidor'] ?? 0);
$stock               = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$descripcion         = trim($_POST['descripcion'] ?? '');

if ($id_producto <= 0) {
    echo json_encode(['success' => false, 'message' => 'Identificador de producto inválido.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 2. CONSULTAR EL PRODUCTO ACTUAL PARA SABER SU CATEGORÍA
    $stmt_prod = $pdo->prepare("SELECT id_categoria FROM productos WHERE id_producto = ? LIMIT 1");
    $stmt_prod->execute([$id_producto]);
    $id_categoria = $stmt_prod->fetchColumn();

    if (!$id_categoria) {
        throw new Exception("El producto objetivo no existe en el sistema.");
    }

    // 3. RE-EMPAQUETADO DE ATRIBUTOS SEGÚN SU CATEGORÍA COINCIDENTE
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

    // 4. EJECUTAR EL UPDATE GENERAL EN LA BASE DE DATOS
    $sql_update = "UPDATE productos SET 
                        precio_publico = :precio_publico, 
                        precio_distribuidor = :precio_distribuidor, 
                        stock = :stock, 
                        descripcion = :descripcion,
                        atributos_especificos = :atributos_especificos
                   WHERE id_producto = :id_producto";

    $stmt_up = $pdo->prepare($sql_update);
    $stmt_up->execute([
        ':precio_publico'        => $precio_publico,
        ':precio_distribuidor'   => $precio_distribuidor,
        ':stock'                 => $stock,
        ':descripcion'           => !empty($descripcion) ? $descripcion : null,
        ':atributos_especificos' => $json_atributos,
        ':id_producto'           => $id_producto
    ]);

    $pdo->commit();
    
    // Mandamos luz verde al AJAX de tu vista
    echo json_encode(['success' => true]);
    exit();

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Fallo en actualización: ' . $e->getMessage()]);
    exit();
}