<?php
/**
 * ARCHIVO: actions/obtener_estadisticas.php
 * DESCRIPCIÓN: API con corrección en el divisor de promedios de tiempo (basado en acciones, no tickets).
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 2.3
 */
header('Content-Type: application/json');
require_once '../../config/db.php';

try {
    $fecha_inicio = !empty($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : null;
    $fecha_fin = !empty($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : null;
    
    $where_tickets = " WHERE 1=1 ";
    $where_detalles = " WHERE 1=1 ";

    if ($fecha_inicio && $fecha_fin) {
        if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
            throw new Exception("La fecha de fin no puede ser anterior a la fecha de inicio.");
        }
        $where_tickets .= " AND fecha_inicial BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59' ";
        $where_detalles .= " AND fecha_inicio_acc BETWEEN '$fecha_inicio' AND '$fecha_fin' ";
    }

    $total = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte $where_tickets")->fetchColumn();

    if ($total == 0) {
        echo json_encode(['success' => true, 'total' => 0]);
        exit;
    }

    // --- KPIs ---
    $res_gar = $pdo->query("SELECT SUM(CASE WHEN garantia_valida = 'Válida' THEN 1 ELSE 0 END) as v, SUM(CASE WHEN garantia_valida = 'No válida' THEN 1 ELSE 0 END) as n, SUM(CASE WHEN garantia_valida = 'Pendiente' THEN 1 ELSE 0 END) as p FROM Tickets_Soporte $where_tickets")->fetch(PDO::FETCH_ASSOC);
    $res_func = $pdo->query("SELECT SUM(CASE WHEN maquina_func = 1 THEN 1 ELSE 0 END) as si, SUM(CASE WHEN maquina_func = 0 THEN 1 ELSE 0 END) as no FROM Tickets_Soporte $where_tickets")->fetch(PDO::FETCH_ASSOC);
    $res_est = $pdo->query("SELECT SUM(CASE WHEN estatus = 'Abierto' THEN 1 ELSE 0 END) as a, SUM(CASE WHEN estatus = 'Cerrado' THEN 1 ELSE 0 END) as c, SUM(CASE WHEN estatus = 'Cancelado' THEN 1 ELSE 0 END) as x FROM Tickets_Soporte $where_tickets")->fetch(PDO::FETCH_ASSOC);
    $res_fallas = $pdo->query("SELECT SUM(CASE WHEN tipo_falla = 'Mecánica' THEN 1 ELSE 0 END) as mec, SUM(CASE WHEN tipo_falla = 'Refrigeración' THEN 1 ELSE 0 END) as ref, SUM(CASE WHEN tipo_falla = 'Electrónica' THEN 1 ELSE 0 END) as ele, SUM(CASE WHEN tipo_falla = 'Regulador' THEN 1 ELSE 0 END) as reg, SUM(CASE WHEN tipo_falla = 'Materia prima' THEN 1 ELSE 0 END) as mp, SUM(CASE WHEN tipo_falla = 'Otra' THEN 1 ELSE 0 END) as otr FROM Tickets_Soporte $where_tickets")->fetch(PDO::FETCH_ASSOC);
    $res_tllamada = $pdo->query("SELECT 
    SUM(CASE WHEN UPPER(TRIM(tipo_llamada)) LIKE 'VENTA%' THEN 1 ELSE 0 END) as venta, 
    SUM(CASE WHEN UPPER(TRIM(tipo_llamada)) LIKE 'INF%' THEN 1 ELSE 0 END) as info, 
    SUM(CASE WHEN UPPER(TRIM(tipo_llamada)) LIKE 'CAPA%' THEN 1 ELSE 0 END) as capa, 
    SUM(CASE WHEN UPPER(TRIM(tipo_llamada)) LIKE 'SOP%' THEN 1 ELSE 0 END) as sop 
    FROM Tickets_Soporte $where_tickets")->fetch(PDO::FETCH_ASSOC);

    // Conteo de Acciones individuales para usarlas como divisores
    $res_acc = $pdo->query("SELECT SUM(CASE WHEN accion = 'Ninguna' THEN 1 ELSE 0 END) as ning, SUM(CASE WHEN accion = 'Envio técnico' THEN 1 ELSE 0 END) as e_tec, SUM(CASE WHEN accion = 'Envio refacciones' THEN 1 ELSE 0 END) as e_ref, SUM(CASE WHEN accion = 'Envio técnico y refacciones' THEN 1 ELSE 0 END) as e_amb, SUM(CASE WHEN accion = 'Envio base' THEN 1 ELSE 0 END) as e_bas, SUM(CASE WHEN accion = 'Reparación en taller' THEN 1 ELSE 0 END) as tall, SUM(CASE WHEN accion = 'Cambio de maquina' THEN 1 ELSE 0 END) as camb, SUM(CASE WHEN accion = 'Información' THEN 1 ELSE 0 END) as info FROM Detalles_Costos_Tiempos $where_detalles")->fetch(PDO::FETCH_ASSOC);

    // --- FINANZAS ---
    $res_fin = $pdo->query("SELECT SUM(costo_refac_garantia) as sum_gar, COUNT(NULLIF(costo_refac_garantia, 0)) as count_gar, SUM(costo_refac_venta) as sum_venta, COUNT(NULLIF(costo_refac_venta, 0)) as count_venta, SUM(costo_base) as sum_base, COUNT(NULLIF(costo_base, 0)) as count_base, SUM(costo_tecnico) as sum_tec, COUNT(NULLIF(costo_tecnico, 0)) as count_tec, SUM(costo_envio) as sum_envio, COUNT(NULLIF(costo_envio, 0)) as count_envio, SUM(costo_total) as sum_total FROM Detalles_Costos_Tiempos $where_detalles")->fetch(PDO::FETCH_ASSOC);

    // --- TIEMPOS ---
    // Obtenemos el total de acciones para el promedio general
    $total_acciones = $pdo->query("SELECT COUNT(*) FROM Detalles_Costos_Tiempos $where_detalles")->fetchColumn();

    $res_tiempos = $pdo->query("SELECT 
        SUM(tiempo_accion) as total_dias_gen,
        SUM(CASE WHEN accion = 'Envio técnico' THEN tiempo_accion ELSE 0 END) as sum_tec,
        SUM(CASE WHEN accion = 'Envio refacciones' THEN tiempo_accion ELSE 0 END) as sum_ref,
        SUM(CASE WHEN accion = 'Envio técnico y refacciones' THEN tiempo_accion ELSE 0 END) as sum_mix,
        SUM(CASE WHEN accion = 'Envio base' THEN tiempo_accion ELSE 0 END) as sum_base,
        SUM(CASE WHEN accion = 'Reparación en taller' THEN tiempo_accion ELSE 0 END) as sum_tall,
        SUM(CASE WHEN accion = 'Cambio de maquina' THEN tiempo_accion ELSE 0 END) as sum_camb
        FROM Detalles_Costos_Tiempos $where_detalles")->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'garantias' => ['v' => (int)$res_gar['v'], 'n' => (int)$res_gar['n'], 'p' => (int)$res_gar['p'], 'suma' => (int)$res_gar['v'] + (int)$res_gar['n'] + (int)$res_gar['p']],
        'funcionamiento' => ['si' => (int)$res_func['si'], 'no' => (int)$res_func['no'], 'suma' => (int)$res_func['si'] + (int)$res_func['no']],
        'estatus' => ['a' => (int)$res_est['a'], 'c' => (int)$res_est['c'], 'x' => (int)$res_est['x'], 'suma' => (int)$res_est['a'] + (int)$res_est['c'] + (int)$res_est['x']],
        'fallas' => ['mec' => (int)$res_fallas['mec'], 'ref' => (int)$res_fallas['ref'], 'ele' => (int)$res_fallas['ele'], 'reg' => (int)$res_fallas['reg'], 'mp' => (int)$res_fallas['mp'], 'otr' => (int)$res_fallas['otr']],
        'acciones' => ['ning' => (int)$res_acc['ning'], 'e_tec' => (int)$res_acc['e_tec'], 'e_ref' => (int)$res_acc['e_ref'], 'e_amb' => (int)$res_acc['e_amb'], 'e_bas' => (int)$res_acc['e_bas'], 'tall' => (int)$res_acc['tall'], 'camb' => (int)$res_acc['camb'], 'info' => (int)$res_acc['info']],
        'tipo_llamada' => [
            'venta' => (int)($res_tllamada['venta'] ?? 0), 
            'info'  => (int)($res_tllamada['info'] ?? 0), 
            'capa'  => (int)($res_tllamada['capa'] ?? 0), 
            'sop'   => (int)($res_tllamada['sop'] ?? 0)
        ],
        'financiero' => [
            'gar_sum' => (float)$res_fin['sum_gar'], 'gar_count' => (int)$res_fin['count_gar'],
            'venta_sum' => (float)$res_fin['sum_venta'], 'venta_count' => (int)$res_fin['count_venta'],
            'base_sum' => (float)$res_fin['sum_base'], 'base_count' => (int)$res_fin['count_base'],
            'tec_sum' => (float)$res_fin['sum_tec'], 'tec_count' => (int)$res_fin['count_tec'],
            'envio_sum' => (float)$res_fin['sum_envio'], 'envio_count' => (int)$res_fin['count_envio'],
            'total_sum' => (float)$res_fin['sum_total']
        ],
        'tiempos' => [
            'sol_sum' => (float)$res_tiempos['total_dias_gen'], 
            'sol_count' => (int)$total_acciones, // CORRECCIÓN: Divisor basado en acciones
            'tec_sum' => (float)$res_tiempos['sum_tec'], 'tec_count' => (int)$res_acc['e_tec'],
            'ref_sum' => (float)$res_tiempos['sum_ref'], 'ref_count' => (int)$res_acc['e_ref'],
            'mix_sum' => (float)$res_tiempos['sum_mix'], 'mix_count' => (int)$res_acc['e_amb'],
            'base_sum' => (float)$res_tiempos['sum_base'], 'base_count' => (int)$res_acc['e_bas'],
            'tall_sum' => (float)$res_tiempos['sum_tall'], 'tall_count' => (int)$res_acc['tall'],
            'camb_sum' => (float)$res_tiempos['sum_camb'], 'camb_count' => (int)$res_acc['camb']
        ]
    ]);
} catch (Exception $e) { 
    echo json_encode(['success' => false, 'error' => $e->getMessage()]); 
}