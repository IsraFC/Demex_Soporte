<?php
/**
 * ARCHIVO: actions/procesar_importacion.php
 * DESCRIPCIÓN: Importador maestro asíncrono con cálculo automático de garantías y respuesta JSON.
 * @author Israel Fernández Carrera
 * @version 2.0 - Arquitectura Asíncrona Sanitizada
 * @date 2026-06-08
 */

ini_set('max_execution_time', 300);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db.php';

// Función para convertir fecha de DD/MM/YYYY a YYYY-MM-DD y manejar vacíos
function formatearFechaSQL($fecha_txt) {
    if (empty($fecha_txt) || trim($fecha_txt) == '') return null;
    
    $fecha_txt = str_replace('-', '/', trim($fecha_txt));
    $partes = explode('/', $fecha_txt);
    
    if (count($partes) === 3) {
        $anio = (strlen($partes[2]) == 2) ? '20' . $partes[2] : $partes[2];
        return $anio . '-' . $partes[1] . '-' . $partes[0];
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_csv'])) {
    $file = $_FILES['archivo_csv']['tmp_name'];
    
    if (empty($file) || !is_uploaded_file($file)) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Archivo Inválido',
            'text' => 'No se pudo leer el archivo cargado, intente nuevamente.'
        ]);
        exit();
    }

    if (($handle = fopen($file, "r")) !== FALSE) {
        $firstLine = fgets($handle);
        $separador = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle);

        try {
            $pdo->beginTransaction();
            $insertados = 0; $duplicados = 0; $fila = 0;

            while (($data = fgetcsv($handle, 1000, $separador)) !== FALSE) {
                $fila++;

                // MAPEADO (Nombre, Tel, Ubi, Modelo, Serie, Inicio, Fin)
                $nombre    = isset($data[0]) ? trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data[0])) : '';
                $telefono  = isset($data[1]) ? trim($data[1]) : '';
                $ubicacion = isset($data[2]) ? trim($data[2]) : '';
                $modelo    = isset($data[3]) ? trim($data[3]) : '';
                $serie     = isset($data[4]) ? trim($data[4]) : '';
                
                $f_ini_csv = isset($data[5]) ? formatearFechaSQL($data[5]) : null;
                $f_fin_csv = isset($data[6]) ? formatearFechaSQL($data[6]) : null;

                // Salto perimetral de cabeceras
                if ($fila === 1 && (strtoupper($nombre) === 'NOMBRE' || strpos(strtoupper($serie), 'SERIE') !== false)) {
                    continue;
                }

                if (empty($nombre) || empty($serie)) continue;

                // --- LÓGICA DE CÁLCULO AUTOMÁTICO DE FECHAS ---
                $fecha_final_inicio = $f_ini_csv;
                $fecha_final_fin    = $f_fin_csv;

                if (!$fecha_final_inicio) {
                    $fecha_final_inicio = date('Y-m-d');
                }

                if (!$fecha_final_fin) {
                    $fecha_final_fin = date('Y-m-d', strtotime($fecha_final_inicio . ' + 1 year'));
                }

                // 1. CLIENTE
                $stC = $pdo->prepare("SELECT id_cliente FROM Clientes WHERE nombre_cliente = ?");
                $stC->execute([$nombre]);
                $id_cliente = $stC->fetchColumn();

                if (!$id_cliente) {
                    $insC = $pdo->prepare("INSERT INTO Clientes (nombre_cliente, telefono, ubicacion) VALUES (?, ?, ?)");
                    $insC->execute([$nombre, $telefono, $ubicacion]);
                    $id_cliente = $pdo->lastInsertId();
                }

                // 2. EQUIPO (Con INSERT IGNORE)
                $modelos_validos = ['DEMEX 313','DEMEX 313T','DEMEX 513','DEMEX 613','DEMEX 1020','DEMEX 125','SPICE MT15','SPICE MV89'];
                $modelo_final = in_array($modelo, $modelos_validos) ? $modelo : 'DEMEX 313';

                $insM = $pdo->prepare("INSERT IGNORE INTO Equipos_Garantia (no_serie, id_cliente, modelo, fecha_inicio, fecha_termino) VALUES (?, ?, ?, ?, ?)");
                $insM->execute([$serie, $id_cliente, $modelo_final, $fecha_final_inicio, $fecha_final_fin]);

                if ($insM->rowCount() > 0) $insertados++;
                else $duplicados++;
            }

            $pdo->commit();
            fclose($handle);

            echo json_encode([
                'status' => 'success',
                'title' => '¡Importación Completada!',
                'text' => "Se procesaron exitosamente {$fila} registros. Nuevos incorporados: {$insertados}, Duplicados omitidos: {$duplicados}."
            ]);
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            fclose($handle);
            
            echo json_encode([
                'status' => 'error',
                'title' => 'Falla en Fila ' . $fila,
                'text' => 'Error de consistencia de datos: ' . $e->getMessage()
            ]);
            exit();
        }
    }
} else {
    echo json_encode(['status' => 'error', 'title' => 'Acceso Denegado', 'text' => 'Petición incorrecta.']);
    exit();
}