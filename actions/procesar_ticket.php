<?php
/**
 * ARCHIVO: actions/procesar_ticket.php
 * DESCRIPCIÓN: Procesa tanto el registro NUEVO como la ACTUALIZACIÓN de tickets existentes.
 */
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // --- 1. CAPTURA DE DATOS COMUNES ---
        $id_ticket      = $_POST['id_ticket'] ?? null; // Si existe, es edición
        $id_cliente     = $_POST['id_cliente'] ?? null;
        $no_serie       = !empty($_POST['no_serie']) ? $_POST['no_serie'] : null;
        $tipo_llamada   = $_POST['tipo_llamada'];
        $tipo_falla     = $_POST['tipo_falla'];
        $maquina_func   = (isset($_POST['maquina_func']) && $_POST['maquina_func'] == "1") ? 1 : 0;
        $garantia_valida= $_POST['garantia_valida'];
        $no_llamadas    = $_POST['no_llamadas'] ?: 1;
        $observaciones  = $_POST['observaciones'];
        $accion         = $_POST['accion'];

        // Datos de Fase 2 (Costos y Logística)
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
        // --- LÓGICA DE PAGO INTELIGENTE ---
        // Si la acción es informativa o el costo total es 0, el pago NO APLICA (NULL)
        if ($accion === 'Ninguna' || $accion === 'Información' || $c_total == 0) {
            $pago = null; 
        } else {
            // Si hay costo, revisamos si el switch de pago estaba activado
            $pago = (isset($_POST['estatus_pago']) && $_POST['estatus_pago'] === 'Pagado') ? 'Pagado' : 'Pendiente';
        }

        if ($id_ticket) {
            // ==========================================
            // LOGICA DE ACTUALIZACIÓN (UPDATE)
            // ==========================================
            
            // Tabla 1: Soporte
            $sql1 = "UPDATE Tickets_Soporte SET 
                        no_serie = ?, tipo_llamada = ?, tipo_falla = ?, 
                        maquina_func = ?, garantia_valida = ?, 
                        no_llamadas = ?, observaciones = ? 
                     WHERE id_ticket = ?";
            $pdo->prepare($sql1)->execute([$no_serie, $tipo_llamada, $tipo_falla, $maquina_func, $garantia_valida, $no_llamadas, $observaciones, $id_ticket]);

            // Tabla 2: Detalles
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
            
            // Tabla 1: Soporte
            $sql1 = "INSERT INTO Tickets_Soporte (no_serie, id_cliente, tipo_llamada, tipo_falla, maquina_func, garantia_valida, estatus, fecha_inicial, no_llamadas, observaciones) 
                     VALUES (?, ?, ?, ?, ?, ?, 'Abierto', NOW(), ?, ?)";
            $stmt = $pdo->prepare($sql1);
            $stmt->execute([$no_serie, $id_cliente, $tipo_llamada, $tipo_falla, $maquina_func, $garantia_valida, $no_llamadas, $observaciones]);
            
            $nuevo_id = $pdo->lastInsertId();

            // Tabla 2: Detalles
            $sql2 = "INSERT INTO Detalles_Costos_Tiempos (id_ticket, accion, fecha_inicio_acc, fecha_fin_acc, tiempo_accion, costo_refac_garantia, costo_refac_venta, costo_base, costo_tecnico, costo_envio, costo_total, no_cotizacion, requiere_factura, estatus_pago) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql2)->execute([$nuevo_id, $accion, $f_inicio, $f_fin, $t_accion, $c_refac_g, $c_refac_v, $c_base, $c_tec, $c_envio, $c_total, $no_cotiz, $factura, $pago]);
        }

        $pdo->commit();
        header("Location: ../index.php?res=ok");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error en el motor de tickets: " . $e->getMessage());
    }
}