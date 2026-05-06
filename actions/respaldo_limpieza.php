<?php
/**
 * ARCHIVO: actions/respaldo_limpieza.php
 * DESCRIPCIÓN: Módulo de Gestión de Datos de DEMEX. 
 * Realiza la exportación de un historial unificado (Tickets + Clientes + Equipos + Costos)
 * a formato Excel y permite la depuración controlada de registros finalizados.
 * 
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.1
 */

require_once '../config/db.php';

/**
 * 1. EXTRACCIÓN DE DATOS MEDIANTE INTEGRACIÓN MULTI-TABLA (JOIN)
 * Se construye una fila única por ticket que consolida información de 4 tablas.
 */
$sql = "SELECT 
            t.id_ticket AS 'ID TICKET', 
            t.no_serie AS 'NÚMERO DE SERIE', 
            t.tipo_llamada AS 'TIPO DE LLAMADA', 
            t.tipo_falla AS 'FALLA REPORTADA', 
            t.estatus AS 'ESTATUS ACTUAL', 
            t.fecha_inicial AS 'FECHA APERTURA', 
            t.fecha_cierre AS 'FECHA CIERRE', 
            t.observaciones AS 'OBSERVACIONES TÉCNICAS',
            c.nombre_cliente AS 'CLIENTE', 
            c.telefono AS 'TELÉFONO', 
            c.ubicacion AS 'UBICACIÓN',
            e.modelo AS 'MODELO EQUIPO', 
            e.fecha_inicio AS 'GARANTÍA INICIO', 
            e.fecha_termino AS 'GARANTÍA VENCIMIENTO',
            d.accion AS 'ACCIÓN TOMADA', 
            d.fecha_inicio_acc AS 'INICIO ACCIÓN', 
            d.fecha_fin_acc AS 'FIN ACCIÓN', 
            d.costo_total AS 'MONTO TOTAL', 
            d.estatus_pago AS 'ESTADO DE PAGO', 
            d.no_cotizacion AS 'COTIZACIÓN'
        FROM Tickets_Soporte t
        JOIN Clientes c ON t.id_cliente = c.id_cliente
        LEFT JOIN Equipos_Garantia e ON t.no_serie = e.no_serie
        LEFT JOIN Detalles_Costos_Tiempos d ON t.id_ticket = d.id_ticket
        ORDER BY t.id_ticket ASC";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * 2. PROCESO DE GENERACIÓN Y DESCARGA DEL ARCHIVO (EXCEL/HTML)
 * Se activa mediante el parámetro GET 'download'.
 */
if (isset($_GET['download'])) {
    
    // Generación de nombre dinámico: Historial_Soporte_YYYY-MM-DD_HHhMM.xls
    $filename = "Historial_Soporte_" . date('Y-m-d_His') . ".xls";
    
    // Configuración de Cabeceras HTTP para forzar la descarga del archivo
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Construcción de la estructura de tabla compatible con Excel
    echo "<table border='1'>";
    
    if (!empty($data)) {
        // Renderizado de Encabezados con Estilo DEMEX (Rojo/Blanco)
        echo "<tr style='background-color: #d9534f; color: white; font-weight: bold;'>";
        foreach (array_keys($data[0]) as $col) {
            echo "<th>" . utf8_decode($col) . "</th>";
        }
        echo "</tr>";
        
        // Iteración de Datos: Limpieza de caracteres especiales (UTF-8 a ISO)
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>" . utf8_decode($val) . "</td>";
            }
            echo "</tr>";
        }
    }
    echo "</table>";

    /**
     * 3. DEPURE/LIMPIEZA DE LA BASE DE DATOS
     * Acción ejecutada tras la descarga exitosa del respaldo.
     * Elimina registros con estatus 'Cerrado' o 'Cancelado'.
     */
    if (isset($_GET['clean']) && $_GET['clean'] === 'true') {
        try {
            // Inicio de bloque transaccional para garantizar integridad
            $pdo->beginTransaction();

            // Paso A: Eliminar registros dependientes en Detalles_Costos_Tiempos
            $sql_del_detalles = "DELETE FROM Detalles_Costos_Tiempos 
                                WHERE id_ticket IN (SELECT id_ticket FROM Tickets_Soporte 
                                                   WHERE estatus IN ('Cerrado', 'Cancelado'))";
            $pdo->exec($sql_del_detalles);

            // Paso B: Eliminar registros maestros en Tickets_Soporte
            $sql_del_tickets = "DELETE FROM Tickets_Soporte WHERE estatus IN ('Cerrado', 'Cancelado')";
            $pdo->exec($sql_del_tickets);

            // Consolidación de cambios
            $pdo->commit();
            
        } catch (Exception $e) {
            // Reversión total en caso de error de ejecución
            $pdo->rollBack();
            error_log("Error en depuración: " . $e->getMessage());
        }
    }
    exit;
}