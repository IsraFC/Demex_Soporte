<?php
/**
 * ARCHIVO: actions/obtener_feedback_datatable.php
 * DESCRIPCIÓN: Proveedor de datos JSON optimizado para peticiones POST de DataTables.
 * @author Israel Fernández Carrera
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if (!isset($_SESSION['roles']) || !in_array('Administrador', $_SESSION['roles'])) {
    echo json_encode(["data" => []]);
    exit();
}

try {
    $filtroEstatus = $_POST['estatus'] ?? '';

    $sql = "SELECT 
                f.id_feedback,
                CONCAT(u.nombre, ' ', u.apellidos) AS usuario_staff,
                f.tipo_reporte,
                f.descripcion,
                f.url_pantalla,
                f.fecha_registro,
                f.estatus
            FROM reportes_feedback f
            INNER JOIN usuarios u ON f.id_usuario = u.id_usuario";

    // Inyección condicional limpia
    if (in_array($filtroEstatus, ['Pendiente', 'Resuelto'])) {
        $sql .= " WHERE f.estatus = :estatus";
    }

    $sql .= " ORDER BY f.id_feedback DESC";

    $stmt = $pdo->prepare($sql);

    if (in_array($filtroEstatus, ['Pendiente', 'Resuelto'])) {
        $stmt->bindValue(':estatus', $filtroEstatus, PDO::PARAM_STR);
    }

    $stmt->execute();
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($reportes as $row) {
        
        $badgeClass = 'bg-secondary';
        switch ($row['tipo_reporte']) {
            case 'Bug': $badgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25'; break;
            case 'Visual': $badgeClass = 'bg-info bg-opacity-10 text-info border border-info border-opacity-25'; break;
            case 'Lento': $badgeClass = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-50'; break;
            case 'Seguridad': $badgeClass = 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25'; break;
            case 'BaseDatos': $badgeClass = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25'; break;
            case 'Mejora': $badgeClass = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25'; break;
        }
        $badgeTipo = "<span class='badge {$badgeClass} px-3 py-2 fw-bold text-uppercase' style='font-size: 10px; border-radius: 8px;'>{$row['tipo_reporte']}</span>";

        if ($row['estatus'] === 'Resuelto') {
            $badgeEstatusClass = 'bg-success text-white';
            $textoEstatus = 'Resuelto';
            $btnEstatus = "<button class='btn btn-outline-secondary btn-sm rounded-pill px-2' title='Reabrir Incidencia' onclick='cambiarEstatusFeedback({$row['id_feedback']}, \"Pendiente\")'>
                            <i class='bi bi-arrow-counterclockwise'></i>
                           </button>";
        } else {
            $badgeEstatusClass = 'bg-warning text-dark';
            $textoEstatus = 'Pendiente';
            $btnEstatus = "<button class='btn btn-success btn-sm rounded-pill px-2' title='Marcar como Resuelto' onclick='cambiarEstatusFeedback({$row['id_feedback']}, \"Resuelto\")'>
                            <i class='bi bi-check-lg'></i> Resolver
                           </button>";
        }
        $badgeEstatus = "<span class='badge {$badgeEstatusClass} px-2.5 py-1.5 fw-bold' style='font-size: 10px;'>{$textoEstatus}</span>";

        $fecha = !empty($row['fecha_registro']) ? date('d/m/Y H:i', strtotime($row['fecha_registro'])) : '---';

        $linkPantalla = "<a href='{$row['url_pantalla']}' target='_blank' class='text-decoration-none small text-muted fw-semibold text-truncate d-block' style='max-width: 140px;' title='Abrir pantalla de la incidencia'>
                            <i class='bi bi-link-45deg text-danger me-1'></i>" . htmlspecialchars(basename($row['url_pantalla'])) . "
                         </a>";

        $acciones = "<div class='d-flex align-items-center gap-1.5 justify-content-center'>
                        <button class='btn btn-light btn-sm rounded-pill px-3 fw-bold border shadow-sm' onclick='verDetalleFeedback(" . json_encode($row['descripcion']) . ", \"{$row['usuario_staff']}\")'>
                            <i class='bi bi-eye-fill text-danger me-1'></i> Ver
                        </button>
                        {$btnEstatus}
                     </div>";

        $data[] = [
            "id"          => "<strong>#" . str_pad($row['id_feedback'], 4, "0", STR_PAD_LEFT) . "</strong>",
            "usuario"     => "<span class='fw-semibold text-dark small'><i class='bi bi-person-circle text-secondary me-1.5'></i> " . htmlspecialchars($row['usuario_staff']) . "</span>",
            "tipo"        => $badgeTipo,
            "descripcion" => "<span class='text-muted small text-truncate d-block' style='max-width: 220px;'>" . htmlspecialchars($row['descripcion']) . "</span>",
            "pantalla"    => $linkPantalla,
            "fecha"       => "<small class='text-secondary fw-medium'>{$fecha}</small>",
            "estatus"     => $badgeEstatus,
            "acciones"    => $acciones
        ];
    }

    echo json_encode(["data" => $data]);

} catch (Exception $e) {
    echo json_encode(["data" => [], "error" => $e->getMessage()]);
}