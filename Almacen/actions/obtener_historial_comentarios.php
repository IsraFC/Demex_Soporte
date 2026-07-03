<?php
/**
 * ARCHIVO: Almacen/actions/obtener_historial_comentarios.php
 * DESCRIPCIÓN: Carga síncrona inicial de todo el historial de notas previas de un lote.
 * @author Sistemas Demex
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

// Subimos dos niveles para alcanzar la raíz desde Almacen/actions/
require_once '../../config/db.php';

$id_inventario = intval($_GET['id'] ?? 0);

if ($id_inventario <= 0) {
    echo json_encode(["success" => false, "comentarios" => [], "ultimo_id" => 0, "error" => "ID inválido"]);
    exit;
}

try {
    // CORRECCIÓN: Seleccionamos el campo real 'foto_perfil' de tu tabla usuarios
    $sql = "SELECT ac.id_comentario, ac.comentario, ac.fecha_registro, ac.id_usuario,
                   CONCAT(u.nombre, ' ', u.apellidos) AS nombre_completo, u.foto_perfil
            FROM almacen_comentarios ac
            INNER JOIN usuarios u ON ac.id_usuario = u.id_usuario
            WHERE ac.id_inventario = ?
            ORDER BY ac.id_comentario ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_inventario]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ultimo_id = 0;
    foreach ($comentarios as &$msg) {
        // CORRECCIÓN BLOB: Si existe la foto en binario, la convertimos a cadena Base64 legible por el navegador
        if (!empty($msg['foto_perfil'])) {
            $msg['foto_src'] = 'data:image/jpeg;base64,' . base64_encode($msg['foto_perfil']);
        } else {
            // Imagen por defecto si el BLOB está vacío (sube dos niveles relativo a Almacen/index.php)
            $msg['foto_src'] = '../../img/default-avatar.png';
        }
        
        // Limpiamos el binario pesado para no saturar el JSON devuelto
        unset($msg['foto_perfil']);

        $msg['fecha_formateada'] = date('d/m H:i', strtotime($msg['fecha_registro']));
        $ultimo_id = $msg['id_comentario'];
    }

    echo json_encode([
        "success" => true,
        "comentarios" => $comentarios,
        "ultimo_id" => $ultimo_id
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "comentarios" => [], "ultimo_id" => 0, "error" => $e->getMessage()]);
}