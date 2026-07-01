<?php
/**
 * ARCHIVO: Almacen/actions/stream_comentarios.php
 * DESCRIPCIÓN: Canalización de datos en tiempo real (Server-Sent Events) para el Chat del Lote.
 * @author Sistemas Demex
 */
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../../config/db.php';

$id_inventario = intval($_GET['id'] ?? 0);
if ($id_inventario <= 0) { exit; }

$ultimo_id_visto = intval($_GET['last_id'] ?? 0);

while (true) {
    try {
        // CORRECCIÓN: Campo 'foto_perfil' integrado
        $sql = "SELECT ac.id_comentario, ac.comentario, ac.fecha_registro, ac.id_usuario,
                       CONCAT(u.nombre, ' ', u.apellidos) AS nombre_completo, u.foto_perfil
                FROM almacen_comentarios ac
                INNER JOIN usuarios u ON ac.id_usuario = u.id_usuario
                WHERE ac.id_inventario = :id AND ac.id_comentario > :last_id
                ORDER BY ac.id_comentario ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_inventario, ':last_id' => $ultimo_id_visto]);
        $nuevos_mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($nuevos_mensajes)) {
            foreach ($nuevos_mensajes as $msg) {
                // CORRECCIÓN BLOB A BASE64
                if (!empty($msg['foto_perfil'])) {
                    $msg['foto_src'] = 'data:image/jpeg;base64,' . base64_encode($msg['foto_perfil']);
                } else {
                    $msg['foto_src'] = '../../img/default-avatar.png';
                }
                unset($msg['foto_perfil']);

                $msg['fecha_formateada'] = date('d/m H:i', strtotime($msg['fecha_registro']));
                
                echo "data: " . json_encode($msg) . "\n\n";
                $ultimo_id_visto = $msg['id_comentario'];
            }
            ob_flush();
            flush();
        }
    } catch (Exception $e) {
        exit;
    }
    sleep(1);
}