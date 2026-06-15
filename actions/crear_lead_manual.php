<?php
/**
 * ARCHIVO: actions/crear_lead_manual.php
 * DESCRIPCIÓN: Inserta un nuevo registro manual en la tabla 'formulario' y genera
 * su respectivo expediente en la tabla 'prospectos' retornando el id_prospecto.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.1 (Soporte de Canal de Origen Manual)
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de petición no permitido.']);
    exit();
}

// Recibir y limpiar variables sanitizadas
$nombre       = trim($_POST['nombre'] ?? '');
$apellidos    = trim($_POST['apellidos'] ?? '');
$correo       = trim($_POST['correo'] ?? '');
$telefono     = trim($_POST['telefono'] ?? '');
$estado_region= trim($_POST['estado_region'] ?? 'Puebla');
$pais         = trim($_POST['pais'] ?? 'México');
$maquina      = trim($_POST['maquina_interes'] ?? 'Sin Especificar');
$canal_origen = trim($_POST['canal_origen'] ?? 'Registro Manual'); // <-- AGREGADO: Captura el canal seleccionado

if (empty($nombre) || empty($telefono)) {
    echo json_encode(['success' => false, 'message' => 'El nombre y el teléfono son obligatorios.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Insertar datos de contacto en la tabla madre 'formulario' (Corregido con :canal_origen)
    $sql_form = "INSERT INTO formulario (nombre, apellidos, correo, telefono, estado_region, pais, canal_origen, maquina_interes, fecha_registro) 
                 VALUES (:nombre, :apellidos, :correo, :telefono, :estado_region, :pais, :canal_origen, :maquina_interes, NOW())";
    
    $stmt1 = $pdo->prepare($sql_form);
    $stmt1->execute([
        ':nombre'          => $nombre,
        ':apellidos'       => $apellidos,
        ':correo'          => !empty($correo) ? $correo : 'sin_correo@demex.com',
        ':telefono'        => $telefono,
        ':estado_region'   => $estado_region,
        ':pais'            => $pais,
        ':canal_origen'    => $canal_origen, // <-- INYECTADO: Guarda el canal real (ej. WhatsApp, Teléfono)
        ':maquina_interes' => $maquina
    ]);

    $id_formulario = $pdo->lastInsertId();

    // 2. Insertar el puente comercial en la tabla 'prospectos'
    $sql_pros = "INSERT INTO prospectos (id_formulario, status_comercial, status_operativo, fecha_contacto, fecha_ultimo_contacto) 
                 VALUES (:id_formulario, 'Consultado', 'Consulta', NOW(), NOW())";
    
    $stmt2 = $pdo->prepare($sql_pros);
    $stmt2->execute([':id_formulario' => $id_formulario]);
    
    $id_prospecto = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'id_prospecto' => $id_prospecto,
        'message' => 'Lead manual registrado exitosamente.'
    ]);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error de Base de Datos: ' . $e->getMessage()]);
}