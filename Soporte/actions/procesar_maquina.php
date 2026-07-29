<?php
/**
 * ARCHIVO: Soporte/actions/procesar_maquina.php
 * DESCRIPCIÓN: Controlador para el alta de equipos en la base instalada.
 * Setea fechas en 2000-01-01 si se activa el switch de garantía vencida.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 2.3 - Fix de Sintaxis $stmt->execute
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $no_serie         = trim($_POST['no_serie'] ?? '');
    $nombre_cliente   = trim($_POST['nombre_cliente'] ?? '');
    $modelo           = trim($_POST['modelo'] ?? '');
    $garantia_vencida = isset($_POST['garantia_vencida']) && $_POST['garantia_vencida'] == '1';

    $fecha_inicio   = trim($_POST['fecha_inicio'] ?? date('Y-m-d'));
    $vigencia_anios = isset($_POST['vigencia']) ? intval($_POST['vigencia']) : 1;

    // Validación de campos obligatorios base
    if (empty($no_serie) || empty($nombre_cliente) || empty($modelo)) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Datos Incompletos',
            'text' => 'El número de serie, cliente y modelo son obligatorios.'
        ]);
        exit();
    }

    // 1. TRADUCCIÓN NOMINAL DE CLIENTE (Nombre -> ID)
    $stmt_cli = $pdo->prepare("SELECT id_cliente FROM clientes WHERE nombre_cliente = ? LIMIT 1");
    $stmt_cli->execute([$nombre_cliente]);
    $cliente = $stmt_cli->fetch();

    if (!$cliente) {
        echo json_encode([
            'status' => 'warning',
            'title' => 'Cliente no Registrado',
            'text' => 'El cliente ingresado no existe en el catálogo. Por favor, regístralo primero.'
        ]);
        exit();
    }

    $id_cliente = $cliente['id_cliente'];

    // 2. CÁLCULO DE VIGENCIA DE GARANTÍA
    if ($garantia_vencida) {
        // Si el switch está activo: Seteamos la fecha al 2000-01-01 para mostrar '01/01/00'
        $fecha_inicio  = '2000-01-01';
        $fecha_termino = '2000-01-01';
    } else {
        // Cálculo de vigencia normal (1 o 2 años a partir de la fecha seleccionada)
        if (empty($fecha_inicio)) { $fecha_inicio = date('Y-m-d'); }
        $fecha_termino = date('Y-m-d', strtotime($fecha_inicio . " + $vigencia_anios year"));
    }

    try {
        $pdo->beginTransaction();

        // Control de duplicados en la base instalada
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM equipos_garantia WHERE no_serie = ?");
        $stmtCheck->execute([$no_serie]);
        if ($stmtCheck->fetchColumn() > 0) {
            $pdo->rollBack();
            echo json_encode([
                'status' => 'error',
                'title' => 'Serie Duplicada',
                'text' => "El número de serie {$no_serie} ya se encuentra registrado en el sistema."
            ]);
            exit();
        }

        $sql = "INSERT INTO equipos_garantia (no_serie, id_cliente, modelo, fecha_inicio, fecha_termino) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$no_serie, $id_cliente, $modelo, $fecha_inicio, $fecha_termino]);

        $pdo->commit();

        $mensajeFinal = $garantia_vencida 
            ? 'El equipo se guardó correctamente marcando la póliza de garantía como VENCIDA (01/01/00).' 
            : 'El equipo y su cobertura de garantía técnica se guardaron correctamente.';

        echo json_encode([
            'status' => 'success',
            'title' => '¡Máquina Registrada!',
            'text' => $mensajeFinal
        ]);
        exit();
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'status' => 'error',
            'title' => 'Error de Base de Datos',
            'text' => 'No se pudo guardar el registro: ' . $e->getMessage()
        ]);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'title' => 'Petición Inválida', 'text' => 'Acceso denegado.']);
    exit();
}