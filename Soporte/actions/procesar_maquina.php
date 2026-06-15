<?php
/**
 * ARCHIVO: actions/procesar_maquina.php
 * DESCRIPCIÓN: Controlador lógico asíncrono para el alta de equipos.
 * Realiza la traducción de nombre de cliente a ID, calcula fechas de expiración
 * y gestiona la integridad de la base de datos mediante bloques try-catch.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 2.0 - Integración Fetch API y Respuestas JSON
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $no_serie       = trim($_POST['no_serie'] ?? '');
    $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
    $modelo         = trim($_POST['modelo'] ?? '');
    $fecha_inicio   = trim($_POST['fecha_inicio'] ?? '');
    $vigencia_anios = isset($_POST['vigencia']) ? intval($_POST['vigencia']) : 0;

    if (empty($no_serie) || empty($nombre_cliente) || empty($modelo) || empty($fecha_inicio) || $vigencia_anios === 0) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Datos Incompletos',
            'text' => 'Todos los campos del formulario son obligatorios para registrar la máquina.'
        ]);
        exit();
    }

    // 1. TRADUCCIÓN NOMINAL (Nombre -> ID)
    $stmt_cli = $pdo->prepare("SELECT id_cliente FROM Clientes WHERE nombre_cliente = ? LIMIT 1");
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

    // 2. CÁLCULO DE VIGENCIA TEMPORAL
    $fecha_termino = date('Y-m-d', strtotime($fecha_inicio . " + $vigencia_anios year"));

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO Equipos_Garantia (no_serie, id_cliente, modelo, fecha_inicio, fecha_termino) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$no_serie, $id_cliente, $modelo, $fecha_inicio, $fecha_termino]);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'title' => '¡Máquina Registrada!',
            'text' => 'El equipo y su cobertura de garantía técnica se guardaron correctamente.'
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