<?php
/**
 * ARCHIVO: Soporte/actions/procesar_tecnico.php
 * DESCRIPCIÓN: Controlador asíncrono unificado para creación y edición transaccional de técnicos.
 * Evalúa dinámicamente el id_tecnico para ejecutar lógica de inserción o actualización (Upsert).
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 2.0
 * @date 2026-07-15
 */

session_start();
require_once '../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Si viene el ID, lo parseamos como entero (indica modo EDICIÓN)
    $id_tecnico = isset($_POST['id_tecnico']) && !empty($_POST['id_tecnico']) ? intval($_POST['id_tecnico']) : null;
    $nombre     = trim($_POST['nombre'] ?? '');
    $estado     = trim($_POST['estado'] ?? '');
    $zona       = trim($_POST['zona'] ?? '');
    $telefonos  = isset($_POST['telefonos']) ? $_POST['telefonos'] : [];

    if (empty($nombre) || empty($estado) || empty($zona)) {
        echo json_encode([
            'status' => 'error',
            'title'  => 'Estructura Incompleta',
            'text'   => 'Los campos de nombre, estado y zona operativa son estrictamente obligatorios.'
        ]);
        exit;
    }

    try {
        // Inicialización de contexto transaccional atomizado
        $pdo->beginTransaction();

        if ($id_tecnico) {
            // ==========================================
            // MODO MIGRACIÓN / EDICIÓN
            // ==========================================
            $sqlTecnico = "UPDATE tecnicos SET nombre = ?, estado = ?, zona = ? WHERE id_tecnico = ?";
            $stmt = $pdo->prepare($sqlTecnico);
            $stmt->execute([$nombre, $estado, $zona, $id_tecnico]);

            // Limpieza absoluta de teléfonos anteriores para este técnico para evitar huérfanos
            $sqlDelTel = "DELETE FROM tecnicos_telefonos WHERE id_tecnico = ?";
            $stmtDel = $pdo->prepare($sqlDelTel);
            $stmtDel->execute([$id_tecnico]);

            $titulo_js = '¡Registro Actualizado!';
            $texto_js  = 'Los cambios operativos del técnico se guardaron exitosamente en el directorio.';

        } else {
            // ==========================================
            // MODO CREACIÓN
            // ==========================================
            $sqlTecnico = "INSERT INTO tecnicos (nombre, estado, zona) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sqlTecnico);
            $stmt->execute([$nombre, $estado, $zona]);
            
            $id_tecnico = $pdo->lastInsertId();

            $titulo_js = '¡Registro Exitoso!';
            $texto_js  = 'El técnico ha sido incorporado correctamente al directorio del portal.';
        }

        // ==========================================
        // PERSISTENCIA DE TELÉFONOS (Común para ambos)
        // ==========================================
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
            'title'  => $titulo_js,
            'text'   => $texto_js
        ]);
        exit;

    } catch (PDOException $e) {
        // Reversión absoluta del estado en caso de error intermedio
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'status' => 'error',
            'title'  => 'Fallo Transaccional',
            'text'   => 'El motor SQL abortó la operación debido al siguiente error: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode([
        'status' => 'error',
        'title'  => 'Protocolo No Permitido',
        'text'   => 'El método de envío solicitado no es válido.'
    ]);
    exit;
}