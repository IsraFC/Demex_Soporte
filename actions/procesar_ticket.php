<?php
/**
 * ARCHIVO: actions/procesar_ticket.php
 * DESCRIPCIÓN: Motor unificado de persistencia. Gestiona de forma transaccional 
 * tanto el alta de nuevos folios como la edición de los existentes.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.5
 */
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        /**
         * INICIO DE TRANSACCIÓN:
         * Asegura que los cambios se apliquen en ambas tablas (Tickets_Soporte y Detalles_Costos_Tiempos)
         * simultáneamente. Si una falla, se cancelan ambas para evitar datos huérfanos.
         */
        $pdo->beginTransaction();

        // --- 1. CAPTURA DE DATOS COMUNES ---
        $id_ticket      = $_POST['id_ticket'] ?? null; // Discriminador: Si existe -> UPDATE, si no -> INSERT
        $id_cliente     = $_POST['id_cliente'] ?? null;
        $no_serie       = !empty($_POST['no_serie']) ? $_POST['no_serie'] : null;
        $tipo_llamada   = $_POST['tipo_llamada'];
        $tipo_falla     = $_POST['tipo_falla'];
        $maquina_func   = (isset($_POST['maquina_func']) && $_POST['maquina_func'] == "1") ? 1 : 0;
        $garantia_valida= $_POST['garantia_valida'];
        $no_llamadas    = $_POST['no_llamadas'] ?: 1;
        $observaciones  = $_POST['observaciones'];
        $accion         = $_POST['accion'];

        // --- 2. CAPTURA DE FASE 2 (COSTOS Y LOGÍSTICA) ---
        $f_inicio       = !empty($_POST['fecha_inicio_acc']) ? $_POST['fecha_inicio_acc'] : null;
        $f_fin          = !empty($_POST['fecha_fin_acc']) ? $_POST['fecha_fin_acc'] : null;
        $t_accion       = !empty($_POST['tiempo_accion']) ? $_POST['tiempo_accion'] : 0;
        $c_refac_v      = !empty($_POST['costo_refac_venta']) ? $_POST['costo_refac_venta'] : 0;
        $c_refac_g      = !empty($_POST['costo_refac_garantia']) ? $_POST['costo_refac_garantia'] : 0;
        $c_base         = !empty($_POST['costo_base']) ? $_POST['costo_base'] : 0;
        $c_tec          = !empty($_POST['costo_tecnico']) ? $_POST['costo_tecnico'] : 0;
        $c_envio        = !empty($_POST['costo_envio']) ? $_POST['costo_envio'] : 0;
        $c_total        = !empty($_POST['costo_total']) ? $_POST['costo_total'] : 0;
        $no_cotiz       = !empty($_POST['no_cotizacion']) ? $_POST['no_cotizacion'] : null;
        $factura        = isset($_POST['requiere_factura']) ? 1 : 0;

        // --- 3. LÓGICA DE PAGO INTELIGENTE ---
        // Se define como NULL (N/A) si es una consulta informativa o no hay costos involucrados.
        if ($accion === 'Ninguna' || $accion === 'Información' || $c_total == 0) {
            $pago = null; 
        } else {
            $pago = (isset($_POST['estatus_pago']) && $_POST['estatus_pago'] === 'Pagado') ? 'Pagado' : 'Pendiente';
        }

        if ($id_ticket) {
            // ==========================================
            // LOGICA DE ACTUALIZACIÓN (UPDATE)
            // ==========================================
            
            // Actualización de datos generales de soporte
            $sql1 = "UPDATE Tickets_Soporte SET 
                        no_serie = ?, tipo_llamada = ?, tipo_falla = ?, 
                        maquina_func = ?, garantia_valida = ?, 
                        no_llamadas = ?, observaciones = ? 
                     WHERE id_ticket = ?";
            $pdo->prepare($sql1)->execute([$no_serie, $tipo_llamada, $tipo_falla, $maquina_func, $garantia_valida, $no_llamadas, $observaciones, $id_ticket]);

            // Actualización de desglose financiero y logística
            $sql2 = "UPDATE Detalles_Costos_Tiempos SET 
                        accion = ?, fecha_inicio_acc = ?, fecha_fin_acc = ?, 
                        tiempo_accion = ?, costo_refac_garantia = ?, 
                        costo_refac_venta = ?, costo_base = ?, costo_tecnico = ?, 
                        costo_envio = ?, costo_total = ?, no_cotizacion = ?, 
                        requiere_factura = ?, estatus_pago = ? 
                     WHERE id_ticket = ?";
            $pdo->prepare($sql2)->execute([$accion, $f_inicio, $f_fin, $t_accion, $c_refac_g, $c_refac_v, $c_base, $c_tec, $c_envio, $c_total, $no_cotiz, $factura, $pago, $id_ticket]);

        } else {
            // ==========================================
            // LOGICA DE NUEVO REGISTRO (INSERT)
            // ==========================================
            
            // Creación del ticket base con estatus inicial 'Abierto'
            $sql1 = "INSERT INTO Tickets_Soporte (no_serie, id_cliente, tipo_llamada, tipo_falla, maquina_func, garantia_valida, estatus, fecha_inicial, no_llamadas, observaciones) 
                     VALUES (?, ?, ?, ?, ?, ?, 'Abierto', NOW(), ?, ?)";
            $stmt = $pdo->prepare($sql1);
            $stmt->execute([$no_serie, $id_cliente, $tipo_llamada, $tipo_falla, $maquina_func, $garantia_valida, $no_llamadas, $observaciones]);
            
            // Obtenemos el ID generado para vincular la tabla de detalles
            $nuevo_id = $pdo->lastInsertId();

            // Inserción de detalles financieros vinculados al nuevo ID
            $sql2 = "INSERT INTO Detalles_Costos_Tiempos (id_ticket, accion, fecha_inicio_acc, fecha_fin_acc, tiempo_accion, costo_refac_garantia, costo_refac_venta, costo_base, costo_tecnico, costo_envio, costo_total, no_cotizacion, requiere_factura, estatus_pago) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql2)->execute([$nuevo_id, $accion, $f_inicio, $f_fin, $t_accion, $c_refac_g, $c_refac_v, $c_base, $c_tec, $c_envio, $c_total, $no_cotiz, $factura, $pago]);
        }

        // Si todo salió bien, guardamos cambios definitivamente
        $pdo->commit();
        header("Location: ../index.php?res=ok");

    } catch (Exception $e) {
        // Si algo falla, revertimos para no dejar datos corruptos o incompletos
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error crítico en el motor de tickets: " . $e->getMessage());
    }
}