<?php
/**
 * ARCHIVO: actions/procesar_feedback.php
 * DESCRIPCIÓN: Controlador asíncrono transaccional que procesa e inserta en la base
 * de datos las incidencias y reportes de calidad del portal.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.0 (Server-side receptor)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forzamos que la respuesta del servidor sea interpretada estrictamente como JSON
header('Content-Type: application/json; charset=utf-8');

// Requerimos tu conexión relacional nativa
require_once '../config/db.php';

// 1. GUARDIÁN DE SEGURIDAD: Validamos que exista una sesión activa
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        "success" => false,
        "message" => "Sesión inválida. Debe autenticarse en la plataforma para reportar incidencias."
    ]);
    exit();
}

// 2. VERIFICACIÓN DE MÉTODO POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Método de transferencia no permitido por el core del sistema."
    ]);
    exit();
}

// 3. CAPTURA Y SANITIZACIÓN DE VARIABLES DEL FORMULARIO
$id_usuario    = $_SESSION['id_usuario'];
$tipo_feedback = trim($_POST['tipo_feedback'] ?? '');
$desc_feedback = trim($_POST['desc_feedback'] ?? '');
$url_pantalla  = trim($_POST['url_incidencia'] ?? 'Desconocida');

// Validamos en el servidor que la descripción no viaje vacía
if (empty($desc_feedback)) {
    echo json_encode([
        "success" => false,
        "message" => "La descripción de la incidencia es obligatoria."
    ]);
    exit();
}

try {
    // 4. INSERCIÓN BLINDADA CON SENTENCIAS PREPARADAS PDO
    $sql = "INSERT INTO reportes_feedback (id_usuario, tipo_reporte, descripcion, url_pantalla) 
            VALUES (:id_usuario, :tipo_reporte, :descripcion, :url_pantalla)";
            
    $stmt = $pdo->prepare($sql);
    
    $resultado = $stmt->execute([
        ':id_usuario'    => $id_usuario,
        ':tipo_reporte'  => $tipo_feedback,
        ':descripcion'   => $desc_feedback,
        ':url_pantalla'  => $url_pantalla
    ]);

    if ($resultado) {
        // Retorno de éxito absoluto consumido por el Fetch API del front
        echo json_encode([
            "success" => true,
            "message" => "La incidencia ha sido registrada en el sistema de control técnico para su revisión."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "El motor de la base de datos rechazó la inserción del reporte."
        ]);
    }

} catch (PDOException $e) {
    // Captura de fallas críticas de base de datos sin romper el flujo del frontend
    echo json_encode([
        "success" => false,
        "message" => "Error interno del servidor: " . $e->getMessage()
    ]);
}