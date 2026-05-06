<?php
/**
 * ARCHIVO: actions/obtener_estadisticas.php
 * DESCRIPCIÓN: API interna que centraliza el procesamiento de datos para el Dashboard.
 * Calcula métricas operativas (KPIs), desglose financiero estricto y promedios 
 * de tiempos de respuesta basados en la lógica de resolución por acción.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.6
 */
header('Content-Type: application/json');
require_once '../config/db.php';

try {
    // 1. Total Global de Tickets para base de auditoría
    $total = $pdo->query("SELECT COUNT(*) FROM Tickets_Soporte")->fetchColumn();

    // --- KPIs DE GRÁFICAS (Distribución de Frecuencias) ---
    // Procesamiento de Garantías, Estado de Máquinas, Estatus de Ticket, Fallas y Tipos de Llamada.
    $res_gar = $pdo->query("SELECT SUM(CASE WHEN garantia_valida = 'Válida' THEN 1 ELSE 0 END) as v, SUM(CASE WHEN garantia_valida = 'No válida' THEN 1 ELSE 0 END) as n, SUM(CASE WHEN garantia_valida = 'Pendiente' THEN 1 ELSE 0 END) as p FROM Tickets_Soporte")->fetch(PDO::FETCH_ASSOC);
    $res_func = $pdo->query("SELECT SUM(CASE WHEN maquina_func = 1 THEN 1 ELSE 0 END) as si, SUM(CASE WHEN maquina_func = 0 THEN 1 ELSE 0 END) as no FROM Tickets_Soporte")->fetch(PDO::FETCH_ASSOC);
    $res_est = $pdo->query("SELECT SUM(CASE WHEN estatus = 'Abierto' THEN 1 ELSE 0 END) as a, SUM(CASE WHEN estatus = 'Cerrado' THEN 1 ELSE 0 END) as c, SUM(CASE WHEN estatus = 'Cancelado' THEN 1 ELSE 0 END) as x FROM Tickets_Soporte")->fetch(PDO::FETCH_ASSOC);
    $res_fallas = $pdo->query("SELECT SUM(CASE WHEN tipo_falla = 'Mecánica' THEN 1 ELSE 0 END) as mec, SUM(CASE WHEN tipo_falla = 'Refrigeración' THEN 1 ELSE 0 END) as ref, SUM(CASE WHEN tipo_falla = 'Electrónica' THEN 1 ELSE 0 END) as ele, SUM(CASE WHEN tipo_falla = 'Regulador' THEN 1 ELSE 0 END) as reg, SUM(CASE WHEN tipo_falla = 'Materia prima' THEN 1 ELSE 0 END) as mp, SUM(CASE WHEN tipo_falla = 'Otra' THEN 1 ELSE 0 END) as otr FROM Tickets_Soporte")->fetch(PDO::FETCH_ASSOC);
    $res_tllamada = $pdo->query("SELECT SUM(CASE WHEN tipo_llamada = 'VENTA REFACCIONES' THEN 1 ELSE 0 END) as venta, SUM(CASE WHEN tipo_llamada = 'INFORMACIÓN' THEN 1 ELSE 0 END) as info_ll, SUM(CASE WHEN tipo_llamada = 'CAPACITACIONES' THEN 1 ELSE 0 END) as capa, SUM(CASE WHEN tipo_llamada = 'SOPORTE' THEN 1 ELSE 0 END) as sop FROM Tickets_Soporte")->fetch(PDO::FETCH_ASSOC);
    $res_acc = $pdo->query("SELECT SUM(CASE WHEN accion = 'Ninguna' THEN 1 ELSE 0 END) as ning, SUM(CASE WHEN accion = 'Envio técnico' THEN 1 ELSE 0 END) as e_tec, SUM(CASE WHEN accion = 'Envio refacciones' THEN 1 ELSE 0 END) as e_ref, SUM(CASE WHEN accion = 'Envio técnico y refacciones' THEN 1 ELSE 0 END) as e_amb, SUM(CASE WHEN accion = 'Envio base' THEN 1 ELSE 0 END) as e_bas, SUM(CASE WHEN accion = 'Reparación en taller' THEN 1 ELSE 0 END) as tall, SUM(CASE WHEN accion = 'Cambio de maquina' THEN 1 ELSE 0 END) as camb, SUM(CASE WHEN accion = 'Información' THEN 1 ELSE 0 END) as info FROM Detalles_Costos_Tiempos")->fetch(PDO::FETCH_ASSOC);

    // --- LÓGICA FINANCIERA (Detalles_Costos_Tiempos) ---
    // Se utiliza NULLIF para excluir ceros de los conteos y obtener promedios reales de cobro.
    $sql_fin = "SELECT 
        SUM(costo_refac_garantia) as sum_gar, COUNT(NULLIF(costo_refac_garantia, 0)) as count_gar,
        SUM(costo_refac_venta) as sum_venta, COUNT(NULLIF(costo_refac_venta, 0)) as count_venta,
        SUM(costo_base) as sum_base, COUNT(NULLIF(costo_base, 0)) as count_base,
        SUM(costo_tecnico) as sum_tec, COUNT(NULLIF(costo_tecnico, 0)) as count_tec,
        SUM(costo_envio) as sum_envio, COUNT(NULLIF(costo_envio, 0)) as count_envio,
        SUM(costo_total) as sum_total
        FROM Detalles_Costos_Tiempos";
    $res_fin = $pdo->query($sql_fin)->fetch(PDO::FETCH_ASSOC);

    // --- LÓGICA DE TIEMPOS ( Lead Time por Acción ) ---
    // Cálculos de promedios de días basados en el diferencial de fechas (DATEDIFF).
    $sql_tiempos = "SELECT 
        SUM(DATEDIFF(fecha_fin_acc, fecha_inicio_acc)) as total_dias_gen,
        COUNT(CASE WHEN fecha_fin_acc IS NOT NULL THEN 1 END) as count_gen,
        
        SUM(CASE WHEN accion = 'Envio técnico' THEN DATEDIFF(fecha_fin_acc, fecha_inicio_acc) ELSE 0 END) as sum_tec,
        COUNT(CASE WHEN accion = 'Envio técnico' AND fecha_fin_acc IS NOT NULL THEN 1 END) as count_tec,

        SUM(CASE WHEN accion = 'Envio refacciones' THEN DATEDIFF(fecha_fin_acc, fecha_inicio_acc) ELSE 0 END) as sum_ref,
        COUNT(CASE WHEN accion = 'Envio refacciones' AND fecha_fin_acc IS NOT NULL THEN 1 END) as count_ref,

        SUM(CASE WHEN accion = 'Envio técnico y refacciones' THEN DATEDIFF(fecha_fin_acc, fecha_inicio_acc) ELSE 0 END) as sum_mix,
        COUNT(CASE WHEN accion = 'Envio técnico y refacciones' AND fecha_fin_acc IS NOT NULL THEN 1 END) as count_mix,

        SUM(CASE WHEN accion = 'Envio base' THEN DATEDIFF(fecha_fin_acc, fecha_inicio_acc) ELSE 0 END) as sum_base,
        COUNT(CASE WHEN accion = 'Envio base' AND fecha_fin_acc IS NOT NULL THEN 1 END) as count_base,

        SUM(CASE WHEN accion = 'Reparación en taller' THEN DATEDIFF(fecha_fin_acc, fecha_inicio_acc) ELSE 0 END) as sum_tall,
        COUNT(CASE WHEN accion = 'Reparación en taller' AND fecha_fin_acc IS NOT NULL THEN 1 END) as count_tall,

        SUM(CASE WHEN accion = 'Cambio de maquina' THEN DATEDIFF(fecha_fin_acc, fecha_inicio_acc) ELSE 0 END) as sum_camb,
        COUNT(CASE WHEN accion = 'Cambio de maquina' AND fecha_fin_acc IS NOT NULL THEN 1 END) as count_camb

        FROM Detalles_Costos_Tiempos";
    
    $res_tiempos = $pdo->query($sql_tiempos)->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'garantias' => ['v' => (int)$res_gar['v'], 'n' => (int)$res_gar['n'], 'p' => (int)$res_gar['p'], 'suma' => (int)$res_gar['v'] + (int)$res_gar['n'] + (int)$res_gar['p']],
        'funcionamiento' => ['si' => (int)$res_func['si'], 'no' => (int)$res_func['no'], 'suma' => (int)$res_func['si'] + (int)$res_func['no']],
        'estatus' => ['a' => (int)$res_est['a'], 'c' => (int)$res_est['c'], 'x' => (int)$res_est['x'], 'suma' => (int)$res_est['a'] + (int)$res_est['c'] + (int)$res_est['x']],
        'fallas' => ['mec' => (int)$res_fallas['mec'], 'ref' => (int)$res_fallas['ref'], 'ele' => (int)$res_fallas['ele'], 'reg' => (int)$res_fallas['reg'], 'mp' => (int)$res_fallas['mp'], 'otr' => (int)$res_fallas['otr']],
        'acciones' => ['ning' => (int)$res_acc['ning'], 'e_tec' => (int)$res_acc['e_tec'], 'e_ref' => (int)$res_acc['e_ref'], 'e_amb' => (int)$res_acc['e_amb'], 'e_bas' => (int)$res_acc['e_bas'], 'tall' => (int)$res_acc['tall'], 'camb' => (int)$res_acc['camb'], 'info' => (int)$res_acc['info']],
        'tipo_llamada' => ['venta' => (int)$res_tllamada['venta'], 'info' => (int)$res_tllamada['info_ll'], 'capa' => (int)$res_tllamada['capa'], 'sop' => (int)$res_tllamada['sop']],
        'financiero' => [
            'gar_sum' => (float)$res_fin['sum_gar'], 'gar_count' => (int)$res_fin['count_gar'],
            'venta_sum' => (float)$res_fin['sum_venta'], 'venta_count' => (int)$res_fin['count_venta'],
            'base_sum' => (float)$res_fin['sum_base'], 'base_count' => (int)$res_fin['count_base'],
            'tec_sum' => (float)$res_fin['sum_tec'], 'tec_count' => (int)$res_fin['count_tec'],
            'envio_sum' => (float)$res_fin['sum_envio'], 'envio_count' => (int)$res_fin['count_envio'],
            'total_sum' => (float)$res_fin['sum_total']
        ],
        'tiempos' => [
            'sol_sum' => (float)$res_tiempos['total_dias_gen'], 'sol_count' => (int)$res_tiempos['count_gen'],
            'tec_sum' => (float)$res_tiempos['sum_tec'], 'tec_count' => (int)$res_tiempos['count_tec'],
            'ref_sum' => (float)$res_tiempos['sum_ref'], 'ref_count' => (int)$res_tiempos['count_ref'],
            'mix_sum' => (float)$res_tiempos['sum_mix'], 'mix_count' => (int)$res_tiempos['count_mix'],
            'base_sum' => (float)$res_tiempos['sum_base'], 'base_count' => (int)$res_tiempos['count_base'],
            'tall_sum' => (float)$res_tiempos['sum_tall'], 'tall_count' => (int)$res_tiempos['count_tall'],
            'camb_sum' => (float)$res_tiempos['sum_camb'], 'camb_count' => (int)$res_tiempos['count_camb']
        ]
    ]);
} catch (Exception $e) { 
    echo json_encode(['success' => false, 'error' => $e->getMessage()]); 
}