<?php
/**
 * ARCHIVO: Almacen/actions/obtener_historial_comentarios.php
 * DESCRIPCIÓN: Carga historial de notas por Lote.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';

$id_lote = intval($_GET['id_lote'] ?? 0);

if ($id_lote <= 0) {
    echo json_encode(["success" => false, "comentarios" => [], "ultimo_id" => 0]);
    exit;
}

try {
    $sql = "SELECT ac.id_comentario, ac.comentario, ac.fecha_registro, ac.id_usuario,
                   CONCAT(u.nombre, ' ', u.apellidos) AS nombre_completo, u.foto_perfil
            FROM almacen_comentarios ac
            INNER JOIN usuarios u ON ac.id_usuario = u.id_usuario
            WHERE ac.id_lote = ?
            ORDER BY ac.id_comentario ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_lote]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ultimo_id = 0;
    foreach ($comentarios as &$msg) {
        if (!empty($msg['foto_perfil'])) {
            $msg['foto_src'] = 'data:image/jpeg;base64,' . base64_encode($msg['foto_perfil']);
        } else {
            $msg['foto_src'] = '../../img/default-avatar.png';
        }
        unset($msg['foto_perfil']);
        $msg['fecha_formateada'] = date('d/m H:i', strtotime($msg['fecha_registro']));
        $ultimo_id = $msg['id_comentario'];
    }

    echo json_encode(["success" => true, "comentarios" => $comentarios, "ultimo_id" => $ultimo_id]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "comentarios" => [], "ultimo_id" => 0, "error" => $e->getMessage()]);
}