<?php
/**
 * ARCHIVO: Almacen/actions/procesar_lote.php
 * DESCRIPCIÓN: Controlador asíncrono unificado para creación y edición (Upsert) de Lotes.
 * CORRECCIÓN: Detecta modelos eliminados del formulario y purga sus unidades 'SIN REVISAR'.
 * @project Almacén Técnico DEMEX
 * @version 6.4 - Detección de Eliminación de Modelos
 * @author Israel Fernández Carrera
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
    exit();
}

// 1. Recepción y normalización de variables
$id_lote       = isset($_POST['id_lote']) && !empty($_POST['id_lote']) ? intval($_POST['id_lote']) : null;
$contenedor    = trim($_POST['contenedor'] ?? '');
$tipo          = trim($_POST['tipo'] ?? 'ORIGINAL');
$fecha_ingreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');
$observaciones = trim($_POST['observaciones'] ?? '');

$modelos    = $_POST['modelos'] ?? [];
$cantidades = $_POST['cantidades'] ?? [];

if (empty($contenedor)) {
    echo json_encode(['success' => false, 'message' => 'El identificador del contenedor es obligatorio.']);
    exit();
}

try {
    $pdo->beginTransaction();

    if ($id_lote) {
        // ==========================================
        // MODO EDICIÓN / ACTUALIZACIÓN
        // ==========================================

        // 1. Validar duplicidad de nombre de contenedor contra OTROS lotes
        $stmtCheck = $pdo->prepare("SELECT id_lote FROM almacen_lotes WHERE contenedor = ? AND id_lote != ?");
        $stmtCheck->execute([$contenedor, $id_lote]);
        if ($stmtCheck->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => "El nombre de contenedor '{$contenedor}' ya está registrado en otro lote."]);
            exit();
        }

        // 2. Actualizar cabecera del lote
        $sqlLote = "UPDATE almacen_lotes 
                    SET contenedor = ?, tipo = ?, fecha_ingreso = ?, observaciones = ? 
                    WHERE id_lote = ?";
        $stmtLote = $pdo->prepare($sqlLote);
        $stmtLote->execute([
            $contenedor, 
            $tipo, 
            $fecha_ingreso, 
            !empty($observaciones) ? $observaciones : null, 
            $id_lote
        ]);

        // 3. Sincronizar datos generales en las unidades hijas
        $sqlUnidades = "UPDATE almacen_inventario 
                        SET contenedor = ?, tipo = ?, fecha_ingreso_contenedor = ? 
                        WHERE id_lote = ?";
        $stmtUnidades = $pdo->prepare($sqlUnidades);
        $stmtUnidades->execute([$contenedor, $tipo, $fecha_ingreso, $id_lote]);

        // 4. PASO CLAVE: Mapear modelos actualmente en DB para este lote
        $stmtModelosDB = $pdo->prepare("SELECT DISTINCT modelo FROM almacen_inventario WHERE id_lote = ?");
        $stmtModelosDB->execute([$id_lote]);
        $modelosEnDB = $stmtModelosDB->fetchAll(PDO::FETCH_COLUMN);

        // Lista de modelos limpios recibidos del formulario
        $modelosRecibidos = array_map('trim', $modelos);

        // 4.1 BORRAR MODELOS QUE YA NO VIENEN EN EL FORMULARIO
        $stmtDeleteModeloCompleto = $pdo->prepare("DELETE FROM almacen_inventario WHERE id_lote = ? AND modelo = ? AND estatus = 'SIN REVISAR'");

        foreach ($modelosEnDB as $modDB) {
            if (!in_array($modDB, $modelosRecibidos)) {
                // Si el modelo estaba en la BD pero fue eliminado del formulario, borramos sus unidades 'SIN REVISAR'
                $stmtDeleteModeloCompleto->execute([$id_lote, $modDB]);
            }
        }

        // 4.2 AJUSTAR CANTIDADES DE LOS MODELOS QUE SÍ SE MANTIENEN
        if (!empty($modelosRecibidos) && !empty($cantidades)) {
            $stmtInsertUnidad = $pdo->prepare("INSERT INTO almacen_inventario (id_lote, contenedor, modelo, tipo, estatus, fecha_ingreso_contenedor) VALUES (?, ?, ?, ?, 'SIN REVISAR', ?)");

            for ($i = 0; $i < count($modelosRecibidos); $i++) {
                $mod = $modelosRecibidos[$i];
                $cantDeseada = intval($cantidades[$i]);

                if (empty($mod) || $cantDeseada <= 0) continue;

                // Contar cuántas hay actualmente de este modelo
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM almacen_inventario WHERE id_lote = ? AND modelo = ?");
                $stmtCount->execute([$id_lote, $mod]);
                $cantActual = intval($stmtCount->fetchColumn());

                if ($cantDeseada > $cantActual) {
                    // Si aumentó la cantidad, agregamos las faltantes
                    $diferencia = $cantDeseada - $cantActual;
                    for ($k = 0; $k < $diferencia; $k++) {
                        $stmtInsertUnidad->execute([$id_lote, $contenedor, $mod, $tipo, $fecha_ingreso]);
                    }
                } elseif ($cantDeseada < $cantActual) {
                    // Si redujo la cantidad, borramos las sobrantes en estado 'SIN REVISAR'
                    $diferencia = $cantActual - $cantDeseada;

                    // Traemos los IDs de las filas 'SIN REVISAR' sobrantes para eliminarlas limpiamente
                    $stmtGetIds = $pdo->prepare("SELECT id FROM almacen_inventario WHERE id_lote = ? AND modelo = ? AND estatus = 'SIN REVISAR' LIMIT " . intval($diferencia));
                    $stmtGetIds->execute([$id_lote, $mod]);
                    $idsToDelete = $stmtGetIds->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($idsToDelete)) {
                        $inQuery = implode(',', array_map('intval', $idsToDelete));
                        $pdo->exec("DELETE FROM almacen_inventario WHERE id IN ($inQuery)");
                    }
                }
            }
        }

        $mensajeExito = "Los datos del lote '{$contenedor}' y su desglose de piezas se actualizaron correctamente.";

    } else {
        // ==========================================
        // MODO CREACIÓN
        // ==========================================

        if (empty($modelos) || empty($cantidades)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Debe agregar al menos un modelo con su cantidad.']);
            exit();
        }

        // 1. Validar duplicidad
        $stmtCheck = $pdo->prepare("SELECT id_lote FROM almacen_lotes WHERE contenedor = ?");
        $stmtCheck->execute([$contenedor]);
        if ($stmtCheck->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => "El identificador de contenedor '{$contenedor}' ya fue registrado previamente."]);
            exit();
        }

        // 2. Insertar Lote Maestro
        $sqlLote = "INSERT INTO almacen_lotes (contenedor, tipo, fecha_ingreso, observaciones) VALUES (?, ?, ?, ?)";
        $stmtLote = $pdo->prepare($sqlLote);
        $stmtLote->execute([
            $contenedor, 
            $tipo, 
            $fecha_ingreso, 
            !empty($observaciones) ? $observaciones : null
        ]);
        $id_lote = $pdo->lastInsertId();

        // 3. Generar unidades individuales para cada modelo
        $sqlUnidad = "INSERT INTO almacen_inventario (id_lote, contenedor, modelo, tipo, estatus, fecha_ingreso_contenedor) 
                      VALUES (?, ?, ?, ?, 'SIN REVISAR', ?)";
        $stmtUnidad = $pdo->prepare($sqlUnidad);

        $total_maquinas_registradas = 0;

        for ($i = 0; $i < count($modelos); $i++) {
            $modelo_actual = trim($modelos[$i]);
            $cant_actual   = intval($cantidades[$i]);

            if (!empty($modelo_actual) && $cant_actual > 0) {
                for ($k = 0; $k < $cant_actual; $k++) {
                    $stmtUnidad->execute([$id_lote, $contenedor, $modelo_actual, $tipo, $fecha_ingreso]);
                    $total_maquinas_registradas++;
                }
            }
        }

        $mensajeExito = "Se ha generado con éxito el lote '{$contenedor}' con {$total_maquinas_registradas} unidades en inventario.";
    }

    $pdo->commit();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => $mensajeExito
    ]);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    exit();
}