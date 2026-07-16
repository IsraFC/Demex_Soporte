<?php
/**
 * ARCHIVO: Soporte/actions/procesar_tecnico.php
 * DESCRIPCIÓN: Controlador asíncrono para la persistencia transaccional de técnicos.
 * Devuelve respuestas estructuradas bajo formato JSON para interceptores Fetch API.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.0
 * @date 2026-07-15
 */

session_start();
require_once '../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nombre    = trim($_POST['nombre'] ?? '');
    $estado    = trim($_POST['estado'] ?? '');
    $zona      = trim($_POST['zona'] ?? '');
    $telefonos = isset($_POST['telefonos']) ? $_POST['telefonos'] : [];

    if (empty($nombre) || empty($estado) || empty($zona)) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Estructura Incompleta',
            'text' => 'Los campos de nombre, estado y zona operativa son estrictamente obligatorios.'
        ]);
        exit;
    }

    try {
        // Inicialización de contexto transaccional atomizado
        $pdo->beginTransaction();

        // Operación 1: Persistencia del elemento maestro (Técnico)
        $sqlTecnico = "INSERT INTO tecnicos (nombre, estado, zona) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sqlTecnico);
        $stmt->execute([$nombre, $estado, $zona]);
        
        $id_tecnico = $pdo->lastInsertId();

        // Operación 2: Inserción iterativa de sub-nodos relacionados (Telefonos)
        if (!empty($telefonos) && is_array($telefonos)) {
            $sqlTelefono = "INSERT INTO tecnicos_telefonos (id_tecnico, telefono) VALUES (?, ?)";
            $stmtTel = $pdo->prepare($sqlTelefono);
            
            foreach ($telefonos as $tel) {
                $telLimpio = trim($tel);
                if ($telLimpio !== '') {
                    $stmtTel->execute([$id_tecnico, $telLimpio]);
                }
            }
        }

        // Consolidación atómica de cambios en base de datos
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'title' => '¡Registro Exitoso!',
            'text' => 'El técnico ha sido incorporado correctamente al directorio del portal.'
        ]);
        exit;

    } catch (PDOException $e) {
        // Reversión absoluta del estado en caso de error intermedio
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'status' => 'error',
            'title' => 'Fallo Transaccional',
            'text' => 'El motor SQL abortó la operación debido al siguiente error: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode([
        'status' => 'error',
        'title' => 'Protocolo No Permitido',
        'text' => 'El método de envío solicitado no es válido.'
    ]);
    exit;
}