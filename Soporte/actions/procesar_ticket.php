<?php
/**
 * ARCHIVO: actions/procesar_ticket.php
 * DESCRIPCIÓN: Motor unificado de persistencia para Tickets y Detalles de Costos.
 * * LÓGICA DE INTEGRIDAD V2.7 (Doble Auditoría de Usuarios):
 * 1. Captura de Operador: Recupera el id_usuario desde la sesión activa para auditoría.
 * 2. Creador vs Editor: Separa la persistencia para id_usuario_creador e id_usuario_editor.
 * 3. Auto-registro de Equipos: Crea la máquina si no existe usando la fecha del modal.
 * 4. Validación de Garantía: Calcula si es "Válida" o "No válida" según la fecha de compra elegida.
 * * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 2.7
 */
require_once '../../config/db.php';

// Aseguramos que la sesión esté iniciada para recuperar al operador activo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // --- 0. RECUPERACIÓN DEL OPERADOR LOGUEADO ---
        $id_usuario = $_SESSION['id_usuario'] ?? null;

        // --- 1. RECEPCIÓN DE DATOS TÉCNICOS ---
        $id_ticket      = $_POST['id_ticket'] ?? null;
        $id_cliente     = $_POST['id_cliente'];
        $no_serie       = !empty($_POST['no_serie']) ? trim($_POST['no_serie']) : null;
        $modelo         = $_POST['modelo'] ?? null;
        $tipo_llamada   = $_POST['tipo_llamada'];
        $tipo_falla     = $_POST['tipo_falla'];
        $maquina_func   = $_POST['maquina_func'] ?? 1;
        $garantia_valida= $_POST['garantia_valida']; 
        $no_llamadas    = $_POST['no_llamadas'] ?: 1;
        $observaciones  = trim($_POST['observaciones']);
        $accion         = $_POST['accion'];

        // Fecha capturada en el modal para equipos nuevos
        $fecha_compra   = !empty($_POST['fecha_compra_nueva']) ? $_POST['fecha_compra_nueva'] : date('Y-m-d');

        // --- 2. FASE DE INTEGRIDAD Y CÁLCULO DE GARANTÍA ---
        if (!empty($no_serie)) {
            $checkEq = $pdo->prepare("SELECT no_serie FROM equipos_garantia WHERE no_serie = ?");
            $checkEq->execute([$no_serie]);
            
            if (!$checkEq->fetch()) {
                // CAPTURA LA VIGENCIA (Si no viene, por defecto es 1)
                $vigencia_anios = !empty($_POST['vigencia_nueva']) ? (int)$_POST['vigencia_nueva'] : 1;
                
                // Calcula la fecha de término sumando los años elegidos (1 o 2)
                $fecha_termino = date('Y-m-d', strtotime($fecha_compra . " + $vigencia_anios year"));
                $hoy = date('Y-m-d');

                $sqlEq = "INSERT INTO equipos_garantia (no_serie, id_cliente, modelo, fecha_inicio, fecha_termino) 
                        VALUES (?, ?, ?, ?, ?)";
                $pdo->prepare($sqlEq)->execute([$no_serie, $id_cliente, $modelo, $fecha_compra, $fecha_termino]);
                
                // Recalcula el estatus de la garantía para el ticket actual
                $garantia_valida = (strtotime($fecha_termino) >= strtotime($hoy)) ? "Válida" : "No válida";
            }
        }

        // --- 3. RECEPCIÓN DE DATOS FINANCIEROS ---
        $f_inicio  = !empty($_POST['fecha_inicio_acc']) ? $_POST['fecha_inicio_acc'] : null;
        $f_fin     = !empty($_POST['fecha_fin_acc']) ? $_POST['fecha_fin_acc'] : null;

        // LÓGICA DE AUDITORÍA: Recalcular días directamente en el servidor
        $t_acc_recalculado = 0;
        if ($f_inicio && $f_fin) {
            $d_ini = new DateTime($f_inicio);
            $d_fin = new DateTime($f_fin);
            
            if ($d_fin >= $d_ini) {
                $intervalo = $d_ini->diff($d_fin);
                $t_acc_recalculado = $intervalo->days;
            }
        }

        $t_accion  = $t_acc_recalculado;
        
        $c_refac_v = (float)($_POST['costo_refac_venta'] ?? 0);
        $c_refac_g = (float)($_POST['costo_refac_garantia'] ?? 0);
        $c_base    = (float)($_POST['costo_base'] ?? 0);
        $c_tecnico = (float)($_POST['costo_tecnico'] ?? 0);
        $c_envio   = (float)($_POST['costo_envio'] ?? 0);
        $c_total   = (float)($_POST['costo_total'] ?? 0);
        
        $no_cotiz  = $_POST['no_cotizacion'] ?? null;
        $factura   = isset($_POST['requiere_factura']) ? 1 : 0;

        // Lógica de Pago Inteligente (0.00 = NO APLICA)
        if ($c_total <= 0) {
            $pago = 'NO APLICA';
        } else {
            $pago = (isset($_POST['estatus_pago']) && $_POST['estatus_pago'] === 'Pagado') ? 'Pagado' : 'Pendiente';
        }

        // --- 4. PERSISTENCIA EN TABLAS (AUDITORÍA DOBLE) ---
        if ($id_ticket) {
            // MODO EDICIÓN: Dejamos intacto al creador original y sobreescribimos únicamente id_usuario_editor
            $sqlT = "UPDATE tickets_soporte SET no_serie = ?, tipo_llamada = ?, tipo_falla = ?, maquina_func = ?, garantia_valida = ?, no_llamadas = ?, observaciones = ?, id_usuario_editor = ? WHERE id_ticket = ?";
            $pdo->prepare($sqlT)->execute([$no_serie, $tipo_llamada, $tipo_falla, $maquina_func, $garantia_valida, $no_llamadas, $observaciones, $id_usuario, $id_ticket]);

            $sqlD = "UPDATE detalles_costos_tiempos SET 
                        accion = ?, fecha_inicio_acc = ?, fecha_fin_acc = ?, tiempo_accion = ?, 
                        costo_refac_garantia = ?, costo_refac_venta = ?, costo_base = ?, 
                        costo_tecnico = ?, costo_envio = ?, costo_total = ?, 
                        no_cotizacion = ?, requiere_factura = ?, estatus_pago = ? 
                     WHERE id_ticket = ?";
            $pdo->prepare($sqlD)->execute([
                $accion, $f_inicio, $f_fin, $t_accion, 
                $c_refac_g, $c_refac_v, $c_base, $c_tecnico, $c_envio, $c_total, 
                $no_cotiz, $factura, $pago, $id_ticket
            ]);
        } else {
            // MODO NUEVO REGISTRO: Al crear el ticket, guardamos tu ID tanto en el creador como en el editor inicial
            $sqlT = "INSERT INTO tickets_soporte (no_serie, id_cliente, tipo_llamada, tipo_falla, maquina_func, garantia_valida, estatus, fecha_inicial, no_llamadas, observaciones, id_usuario_creador, id_usuario_editor) 
                     VALUES (?, ?, ?, ?, ?, ?, 'Abierto', NOW(), ?, ?, ?, ?)";
            $stmtT = $pdo->prepare($sqlT);
            $stmtT->execute([$no_serie, $id_cliente, $tipo_llamada, $tipo_falla, $maquina_func, $garantia_valida, $no_llamadas, $observaciones, $id_usuario, $id_usuario]);
            
            $nuevo_id = $pdo->lastInsertId();

            $sqlD = "INSERT INTO detalles_costos_tiempos (id_ticket, accion, fecha_inicio_acc, fecha_fin_acc, tiempo_accion, costo_refac_garantia, costo_refac_venta, costo_base, costo_tecnico, costo_envio, costo_total, no_cotizacion, requiere_factura, estatus_pago) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sqlD)->execute([
                $nuevo_id, $accion, $f_inicio, $f_fin, $t_accion, 
                $c_refac_g, $c_refac_v, $c_base, $c_tecnico, $c_envio, $c_total, 
                $no_cotiz, $factura, $pago
            ]);
        }

        $pdo->commit();
        header("Location: ../index.php?res=ok");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("ERROR CRÍTICO: " . $e->getMessage());
    }
}