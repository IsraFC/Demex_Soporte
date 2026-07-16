<?php
/**
 * ARCHIVO: actions/procesar_ticket.php
 * DESCRIPCIÓN: Motor unificado asíncrono de persistencia para Tickets y Detalles de Costos.
 * Incorpora el registro y actualización del identificador del técnico asignado de forma transaccional.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 3.1 - Respuesta JSON Asíncrona con Asignación de Técnico
 * @date 2026-07-15
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // --- 0. RECUPERACIÓN DEL OPERADOR LOGUEADO ---
        $id_usuario =$_SESSION['id_usuario'] ?? null;

        // --- 1. RECEPCIÓN DE DATOS TÉCNICOS ---
        $id_ticket       = !empty($_POST['id_ticket']) ? intval($_POST['id_ticket']) : null;
        $id_cliente      = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : null;
        $no_serie        = !empty($_POST['no_serie']) ? trim($_POST['no_serie']) : null;
        $modelo          =$_POST['modelo'] ?? null;
        $tipo_llamada    = $_POST['tipo_llamada'] ?? '';$tipo_falla      = $_POST['tipo_falla'] ?? '';$maquina_func    = isset($_POST['maquina_func']) ? intval($_POST['maquina_func']) : 1;
        $garantia_valida = $_POST['garantia_valida'] ?? 'Pendiente';$no_llamadas     = !empty($_POST['no_llamadas']) ? intval($_POST['no_llamadas']) : 1;
        $observaciones   = trim($_POST['observaciones'] ?? '');
        $accion          =$_POST['accion'] ?? 'Ninguna';

        // Captura y normalización del id de técnico asignado
        $id_tecnico_asignado = !empty($_POST['id_tecnico_asignado']) ? intval($_POST['id_tecnico_asignado']) : null;

        if (!$id_cliente) {
            echo json_encode(['status' => 'error', 'title' => 'Error de Origen', 'text' => 'No se identificó el cliente propietario del ticket.']);
            exit();
        }

        // Fecha capturada en el modal para equipos nuevos
        $fecha_compra   = !empty($_POST['fecha_compra_nueva']) ?$_POST['fecha_compra_nueva'] : date('Y-m-d');

        // --- 2. FASE DE INTEGRIDAD Y CÁLCULO DE GARANTÍA ---
        if (!empty($no_serie)) {
            $checkEq =$pdo->prepare("SELECT no_serie FROM equipos_garantia WHERE no_serie = ?");
            $checkEq->execute([$no_serie]);
            
            if (!$checkEq->fetch()) {$vigencia_anios = !empty($_POST['vigencia_nueva']) ? (int)$_POST['vigencia_nueva'] : 1;
                $fecha_termino = date('Y-m-d', strtotime($fecha_compra . " + $vigencia_anios year"));
                $hoy = date('Y-m-d');

                $sqlEq = "INSERT INTO equipos_garantia (no_serie, id_cliente, modelo, fecha_inicio, fecha_termino) 
                          VALUES (?, ?, ?, ?, ?)";
                $pdo->prepare($sqlEq)->execute([$no_serie,$id_cliente, $modelo,$fecha_compra, $fecha_termino]);$garantia_valida = (strtotime($fecha_termino) >= strtotime($hoy)) ? "Válida" : "No válida";
            }
        }

        // --- 3. RECEPCIÓN DE DATOS FINANCIEROS ---
        $f_inicio  = !empty($_POST['fecha_inicio_acc']) ?$_POST['fecha_inicio_acc'] : null;
        $f_fin     = !empty($_POST['fecha_fin_acc']) ?$_POST['fecha_fin_acc'] : null;

        $t_acc_recalculado = 0;
        if ($f_inicio &&$f_fin) {
            $d_ini = new DateTime($f_inicio);
            $d_fin = new DateTime($f_fin);
            
            if ($d_fin >= $d_ini) {$intervalo = $d_ini->diff($d_fin);
                $t_acc_recalculado =$intervalo->days;
            }
        }

        $t_accion  =$t_acc_recalculado;
        
        $c_refac_v = (float)($_POST['costo_refac_venta'] ?? 0);
        $c_refac_g = (float)($_POST['costo_refac_garantia'] ?? 0);
        $c_base    = (float)($_POST['costo_base'] ?? 0);
        $c_tecnico = (float)($_POST['costo_tecnico'] ?? 0);
        $c_envio   = (float)($_POST['costo_envio'] ?? 0);
        $c_total   = (float)($_POST['costo_total'] ?? 0);
        
        $no_cotiz  =$_POST['no_cotizacion'] ?? null;
        $factura   = isset($_POST['requiere_factura']) ? 1 : 0;

        if ($c_total <= 0) {$pago = 'NO APLICA';
        } else {
            $pago = (isset($_POST['estatus_pago']) &&$_POST['estatus_pago'] === 'Pagado') ? 'Pagado' : 'Pendiente';
        }

        // --- 4. PERSISTENCIA EN TABLAS (AUDITORÍA DOBLE) ---
        if ($id_ticket) {
            // MODO EDICIÓN
            $sqlT = "UPDATE tickets_soporte SET no_serie = ?, tipo_llamada = ?, tipo_falla = ?, maquina_func = ?, garantia_valida = ?, no_llamadas = ?, observaciones = ?, id_usuario_editor = ? WHERE id_ticket = ?";
            $pdo->prepare($sqlT)->execute([$no_serie, $tipo_llamada,$tipo_falla, $maquina_func,$garantia_valida, $no_llamadas,$observaciones, $id_usuario,$id_ticket]);

            $sqlD = "UPDATE detalles_costos_tiempos SET 
                        accion = ?, id_tecnico_asignado = ?, fecha_inicio_acc = ?, fecha_fin_acc = ?, tiempo_accion = ?, 
                        costo_refac_garantia = ?, costo_refac_venta = ?, costo_base = ?, 
                        costo_tecnico = ?, costo_envio = ?, costo_total = ?, 
                        no_cotizacion = ?, requiere_factura = ?, estatus_pago = ? 
                     WHERE id_ticket = ?";
            $pdo->prepare($sqlD)->execute([$accion, $id_tecnico_asignado,$f_inicio, $f_fin,$t_accion, 
                $c_refac_g,$c_refac_v, $c_base,$c_tecnico, $c_envio,$c_total, 
                $no_cotiz,$factura, $pago,$id_ticket
            ]);

            $titleMsg = "¡Ticket Actualizado!";
            $textMsg = "Las modificaciones técnicas y financieras han sido registradas en el folio #{$id_ticket}.";
        } else {
            // MODO NUEVO REGISTRO
            $sqlT = "INSERT INTO tickets_soporte (no_serie, id_cliente, tipo_llamada, tipo_falla, maquina_func, garantia_valida, estatus, fecha_inicial, no_llamadas, observaciones, id_usuario_creador, id_usuario_editor) 
                     VALUES (?, ?, ?, ?, ?, ?, 'Abierto', NOW(), ?, ?, ?, ?)";
            $stmtT =$pdo->prepare($sqlT);$stmtT->execute([$no_serie,$id_cliente, $tipo_llamada,$tipo_falla, $maquina_func,$garantia_valida, $no_llamadas,$observaciones, $id_usuario,$id_usuario]);
            
            $nuevo_id =$pdo->lastInsertId();

            $sqlD = "INSERT INTO detalles_costos_tiempos (id_ticket, id_tecnico_asignado, accion, fecha_inicio_acc, fecha_fin_acc, tiempo_accion, costo_refac_garantia, costo_refac_venta, costo_base, costo_tecnico, costo_envio, costo_total, no_cotizacion, requiere_factura, estatus_pago) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sqlD)->execute([$nuevo_id, $id_tecnico_asignado,$accion, $f_inicio,$f_fin, $t_accion,$c_refac_g, $c_refac_v,$c_base, $c_tecnico,$c_envio, $c_total,$no_cotiz, $factura,$pago
            ]);

            $titleMsg = "¡Ticket Generado!";
            $textMsg = "El folio de soporte técnico ha sido abierto de forma exitosa.";
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'title' => $titleMsg, 'text' =>$textMsg]);
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {$pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'title' => 'Falla de Transacción', 'text' => 'Fallo al procesar el ticket: ' . $e->getMessage()]);
        exit();
    }
}