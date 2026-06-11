<?php
/**
 * ARCHIVO: actions/procesar_cliente.php
 * DESCRIPCIÓN: Controlador híbrido asíncrono para Clientes. Maneja respuestas JSON y texto para AJAX.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 2.0 - Integración Multi-Respuesta JSON / Texto
 * @date 2026-06-08
 */

session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id     = $_POST['id_cliente'] ?? null; 
    $nombre = trim($_POST['nombre_cliente'] ?? '');
    $tel    = trim($_POST['telefono'] ?? '');
    $ubi    = trim($_POST['ubicacion'] ?? '');
    $esAjax = isset($_POST['es_ajax']); // Detecta si viene del modal rápido de máquinas

    // Si es una petición del formulario tradicional, enviamos cabecera JSON
    if (!$esAjax) {
        header('Content-Type: application/json; charset=utf-8');
    }

    if (empty($nombre)) {
        if ($esAjax) { echo "error_nombre"; exit; }
        
        echo json_encode([
            'status' => 'error',
            'title' => 'Campo Obligatorio',
            'text' => 'El nombre del cliente o empresa no puede estar vacío.'
        ]);
        exit;
    }

    try {
        if ($id) {
            // LÓGICA DE ACTUALIZACIÓN
            $sql = "UPDATE Clientes SET nombre_cliente = ?, telefono = ?, ubicacion = ? WHERE id_cliente = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $tel, $ubi, $id]);
            $titleText = "¡Cambios Guardados!";
            $msgText = "La información del cliente ha sido actualizada con éxito.";
        } else {
            // LÓGICA DE CREACIÓN
            $sql = "INSERT INTO Clientes (nombre_cliente, telefono, ubicacion) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $tel, $ubi]);
            $titleText = "¡Cliente Registrado!";
            $msgText = "El nuevo cliente ha sido incorporado al catálogo de DEMEX.";
        }

        // RESPUESTA SI VIENE DEL MODAL RÁPIDO (Se mantiene intacto tu flujo)
        if ($esAjax) {
            echo "ok"; 
            exit;
        }

        // RESPUESTA SI VIENE DEL FORMULARIO INDEPENDIENTE
        echo json_encode([
            'status' => 'success',
            'title' => $titleText,
            'text' => $msgText
        ]);
        exit;

    } catch (PDOException $e) {
        if ($esAjax) { echo "error_db"; exit; }
        
        echo json_encode([
            'status' => 'error',
            'title' => 'Error de Base de Datos',
            'text' => 'Ocurrió un fallo al guardar el registro: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    if (isset($_POST['es_ajax'])) {
        echo "error_peticion";
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'title' => 'Petición Inválida', 'text' => 'Acceso denegado.']);
    }
}
exit();