<?php
/**
 * ARCHIVO: actions/procesar_importacion_tickets.php
 * DESCRIPCIÓN: Importador avanzado de Historial de Soporte.
 * Maneja 46 columnas, evita errores por duplicados y redirige al index.
 */
ini_set('max_execution_time', 600);
require_once '../config/db.php';

// --- FUNCIONES AUXILIARES ---

function limpiarDinero($valor) {
    return (float)preg_replace('/[^0-9.]/', '', $valor);
}

function convertirFecha($fecha) {
    if (empty($fecha) || strpos($fecha, '#') !== false) return null;
    $fecha = str_replace('-', '/', trim($fecha));
    $p = explode('/', $fecha);
    if (count($p) === 3) {
        $anio = (strlen($p[2]) == 2) ? '20' . $p[2] : $p[2];
        return "$anio-$p[1]-$p[0]";
    }
    return null;
}

function normalizarEnum($valor, $opciones, $default) {
    $valor_busqueda = mb_strtolower(trim($valor), 'UTF-8');
    foreach ($opciones as $opcion) {
        if (mb_strtolower($opcion, 'UTF-8') == $valor_busqueda) return $opcion;
    }
    return $default;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_csv'])) {
    $file = $_FILES['archivo_csv']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $firstLine = fgets($handle);
        $separador = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle);

        $pdo->beginTransaction();
        $insertados = 0; 
        $fila = 0;

        try {
            while (($data = fgetcsv($handle, 2000, $separador)) !== FALSE) {
                $fila++;
                if ($fila <= 2) continue;

                $cliente_nom = trim($data[1] ?? '');
                if (empty($cliente_nom)) continue;

                $serie   = trim($data[5] ?? '');
                $modelo  = trim($data[4] ?? 'DEMEX 313');
                
                $tipo_ll = normalizarEnum($data[3] ?? '', ['Venta Refacciones','Información','Capacitaciones','Soporte'], 'Soporte');
                $falla   = normalizarEnum($data[9] ?? '', ['Mecánica','Refrigeración','Electrónica','Regulador','Materia prima','Otra'], 'Otra');
                $func    = (strtoupper(trim($data[8] ?? '')) == 'SI' || trim($data[8] ?? '') == '1') ? 1 : 0;
                $gar_val = normalizarEnum($data[7] ?? '', ['Válida','No válida','Pendiente'], 'Pendiente');
                $estatus = normalizarEnum($data[10] ?? '', ['Abierto','Cerrado','Cancelado'], 'Cerrado');
                
                $f_ini   = convertirFecha($data[11] ?? '') ?: date('Y-m-d H:i:s');
                $f_cie   = convertirFecha($data[12] ?? '');
                $n_llam  = (int)($data[13] ?? 1);
                $obs     = trim($data[45] ?? '');

                $accion_raw = trim($data[17] ?? '');
                $accion = normalizarEnum($accion_raw, ['Ninguna','Envio técnico','Envio refacciones','Envio técnico y refacciones','Envio base','Reparación en taller','Información','Cambio de maquina'], 'Información');

                $f_ini_acc = null; $f_fin_acc = null; $t_acc = null;
                switch ($accion) {
                    case 'Envio técnico':               $f_ini_acc = $data[21]; $f_fin_acc = $data[22]; $t_acc = $data[23]; break;
                    case 'Envio refacciones':           $f_ini_acc = $data[24]; $f_fin_acc = $data[25]; $t_acc = $data[26]; break;
                    case 'Envio técnico y refacciones': $f_ini_acc = $data[27]; $f_fin_acc = $data[28]; $t_acc = $data[29]; break;
                    case 'Envio base':                  $f_ini_acc = $data[30]; $f_fin_acc = $data[31]; $t_acc = $data[32]; break;
                    case 'Reparación en taller':        $f_ini_acc = $data[33]; $f_fin_acc = $data[34]; $t_acc = $data[35]; break;
                    case 'Cambio de maquina':           $f_ini_acc = $data[36]; $f_fin_acc = $data[37]; $t_acc = $data[38]; break;
                }

                // 1. CLIENTE
                $st = $pdo->prepare("SELECT id_cliente FROM Clientes WHERE nombre_cliente = ?");
                $st->execute([$cliente_nom]);
                $id_c = $st->fetchColumn();
                if (!$id_c) {
                    $ins = $pdo->prepare("INSERT INTO Clientes (nombre_cliente, telefono) VALUES (?, ?)");
                    $ins->execute([$cliente_nom, trim($data[2] ?? '')]);
                    $id_c = $pdo->lastInsertId();
                }

                // 2. EQUIPO (INSERT IGNORE)
                if (!empty($serie)) {
                    $stE = $pdo->prepare("SELECT fecha_termino FROM Equipos_Garantia WHERE no_serie = ?");
                    $stE->execute([$serie]);
                    $equipo_bd = $stE->fetch();

                    if (!$equipo_bd) {
                        $f_venc = convertirFecha($data[6] ?? '');
                        if (!$f_venc) $f_venc = date('Y-m-d', strtotime($f_ini . ' + 1 year'));
                        
                        $modelos_v = ['DEMEX 313','DEMEX 313T','DEMEX 513','DEMEX 613','DEMEX 1020','DEMEX 125','SPICE MT15','SPICE MV89'];
                        $mod_final = in_array($modelo, $modelos_v) ? $modelo : 'DEMEX 313';

                        $insE = $pdo->prepare("INSERT IGNORE INTO Equipos_Garantia (no_serie, id_cliente, modelo, fecha_inicio, fecha_termino) VALUES (?,?,?,?,?)");
                        $insE->execute([$serie, $id_c, $mod_final, $f_ini, $f_venc]);
                    } else {
                        if (strpos($data[6] ?? '', '#') !== false || empty($data[6])) {
                            $gar_val = (strtotime($equipo_bd['fecha_termino']) >= strtotime($f_ini)) ? 'Válida' : 'No válida';
                        }
                    }
                }

                // 3. TICKET
                $sqlT = "INSERT INTO Tickets_Soporte (no_serie, id_cliente, tipo_llamada, tipo_falla, maquina_func, garantia_valida, estatus, fecha_inicial, fecha_cierre, no_llamadas, observaciones) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
                $stT = $pdo->prepare($sqlT);
                $stT->execute([(!empty($serie)?$serie:null), $id_c, $tipo_ll, $falla, $func, $gar_val, $estatus, $f_ini, ($f_cie?:null), $n_llam, $obs]);
                $id_t = $pdo->lastInsertId();

                // 4. COSTOS
                $req_fac = (strtoupper(trim($data[15] ?? '')) == 'SI') ? 1 : 0;
                $est_pago = normalizarEnum($data[16] ?? '', ['Pendiente','Pagado'], null);

                $sqlD = "INSERT INTO Detalles_Costos_Tiempos (id_ticket, accion, fecha_inicio_acc, fecha_fin_acc, tiempo_accion, costo_refac_garantia, costo_refac_venta, costo_base, costo_tecnico, costo_envio, costo_total, no_cotizacion, requiere_factura, estatus_pago) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $stD = $pdo->prepare($sqlD);
                $stD->execute([
                    $id_t, $accion, convertirFecha($f_ini_acc), convertirFecha($f_fin_acc), (int)$t_acc,
                    limpiarDinero($data[39] ?? 0), limpiarDinero($data[40] ?? 0), limpiarDinero($data[41] ?? 0),
                    limpiarDinero($data[42] ?? 0), limpiarDinero($data[43] ?? 0), limpiarDinero($data[44] ?? 0),
                    trim($data[14] ?? ''), $req_fac, $est_pago
                ]);

                $insertados++;
            }
            $pdo->commit();
            // CORRECCIÓN: Enviamos al index.php
            header("Location: ../index.php?msg=import_success&count=$insertados");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            die("Error crítico en fila $fila: " . $e->getMessage());
        }
        fclose($handle);
    }
}