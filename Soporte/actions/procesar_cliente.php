<?php
/**
 * ARCHIVO: actions/procesar_cliente.php
 * DESCRIPCIÓN: Controlador híbrido para Clientes. Maneja peticiones normales y AJAX.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.5
 */

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id     = $_POST['id_cliente'] ?? null; 
    $nombre = trim($_POST['nombre_cliente'] ?? '');
    $tel    = trim($_POST['telefono'] ?? '');
    $ubi    = trim($_POST['ubicacion'] ?? '');
    $esAjax = isset($_POST['es_ajax']); // Detecta si viene del modal de máquinas

    if (empty($nombre)) {
        if ($esAjax) { echo "error_nombre"; exit; }
        header("Location: ../clientes.php?msg=error");
        exit;
    }

    try {
        if ($id) {
            // LÓGICA DE ACTUALIZACIÓN
            $sql = "UPDATE Clientes SET nombre_cliente = ?, telefono = ?, ubicacion = ? WHERE id_cliente = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $tel, $ubi, $id]);
            $msg = "edit_success";
        } else {
            // LÓGICA DE CREACIÓN
            $sql = "INSERT INTO Clientes (nombre_cliente, telefono, ubicacion) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $tel, $ubi]);
            $msg = "success";
        }

        // RESPUESTA HÍBRIDA
        if ($esAjax) {
            echo "ok"; // Respuesta simple para el JavaScript del modal
            exit;
        }

        header("Location: ../clientes.php?msg=" . $msg);

    } catch (PDOException $e) {
        if ($esAjax) { echo "error_db"; exit; }
        header("Location: ../clientes.php?msg=error");
    }
} else {
    header("Location: ../clientes.php");
}
exit();