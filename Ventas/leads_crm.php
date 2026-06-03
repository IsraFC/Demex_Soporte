<?php
/**
 * ARCHIVO: leads_crm.php
 * DESCRIPCIÓN: Panel de Control de Leads CRM con Conexión PDO Centralizada.
 * Mantiene lógica de indicadores (KPI) y sistema de semáforos de inactividad comercial.
 * @project Módulo Ventas DEMEX
 */

// 1. CONTROL DE SESIÓN SEGURO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

// 2. CONEXIÓN A LA BASE DE DATOS PDO (Ruta absoluta basada en directorio)
require_once __DIR__ . '/../config/db.php';

// 3. CONFIGURACIÓN DE VARIABLES DEL LAYOUT DE ISRA
$page_title = "Panel de Leads CRM | Ventas";
$modulo_actual = 'ventas'; // Esto activa automáticamente el tema verde de ventas en estilos.css

// Cargamos la cabecera unificada usando la ruta absoluta de directorios
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 mt-4 page-fade-wrapper fade-in-active">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold text-dark h2">Control de Leads Web</h1>
            <p class="text-muted small mb-0">Prospectos capturados desde el formulario público de la página web.</p>
        </div>
        <div>
            <span class="badge bg-success p-2 fs-6 shadow-sm" style="background-color: var(--primary-color) !important;">
                <i class="bi bi-shield-check me-1"></i> Módulo CRM Ventas
            </span>
        </div>
    </div>

    <div class="card-main mb-4 py-4 px-3 shadow-sm bg-white rounded">
        <h5 class="fw-bold text-dark mb-4">
            <i class="bi bi-funnel text-success me-2" style="color: var(--primary-color) !important;"></i> 
            Bandeja de Entrada de Clientes
        </h5>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaLeads" style="width:100%;">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha Registro</th>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Ubicación</th>
                        <th>Equipo de Interés</th>
                        <th>Semáforo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Consulta SQL con INNER JOIN adaptada a PDO
                    $sql = "SELECT f.*, p.id_prospecto, p.status_operativo, p.fecha_ultimo_contacto 
                            FROM formulario f
                            INNER JOIN prospectos p ON f.id_formulario = p.id_formulario
                            WHERE p.status_operativo = 'Consulta'
                            ORDER BY f.fecha_registro DESC";
                    
                    $stmt = $pdo->query($sql);
                    $leads = $stmt->fetchAll();

                    if (count($leads) > 0) {
                        foreach ($leads as $lead) {
                            
                            // --- ALGORITMO MATEMÁTICO DEL SEMÁFORO VISUAL COMMERCIAL ---
                            $fechaUltimo = new DateTime($lead['fecha_ultimo_contacto']);
                            $fechaActual = new DateTime();
                            $diferencia = $fechaActual->diff($fechaUltimo);
                            $diasInactivo = $diferencia->days;

                            if ($diasInactivo <= 2) {
                                $semaforoBadge = '<span class="badge bg-success"><i class="bi bi-circle-fill me-1"></i> Al día</span>';
                                $rowClass = '';
                            } elseif ($diasInactivo <= 5) {
                                $semaforoBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i> Atención</span>';
                                $rowClass = 'table-warning-sutil';
                            } else {
                                $semaforoBadge = '<span class="badge bg-danger animate__animated animate__headShake"><i class="bi bi-fire me-1"></i> Urgente</span>';
                                $rowClass = 'table-danger-sutil';
                            }
                            ?>
                            <tr class="<?= $rowClass; ?>">
                                <td class="small fw-semibold"><?= date('d/m/Y g:i A', strtotime($lead['fecha_registro'])); ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($lead['nombre'] . ' ' . $lead['apellidos']); ?></div>
                                    <small class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;"><?= $lead['canal_origen']; ?></small>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($lead['correo']); ?></div>
                                    <div class="small mt-1">
                                        <a href="https://wa.me/52<?= $lead['telefono']; ?>" target="_blank" class="text-success text-decoration-none fw-semibold">
                                            <i class="bi bi-whatsapp me-1"></i><?= $lead['telefono']; ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="small"><i class="bi bi-geo-alt-fill text-secondary me-1"></i><?= htmlspecialchars($lead['estado_region'] . ', ' . $lead['pais']); ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($lead['maquina_interes']); ?></span></td>
                                <td><?= $semaforoBadge; ?></td>
                                <td class="text-center">
                                    <a href="cotizaciones.php?id_prospecto=<?= $lead['id_prospecto']; ?>" class="btn btn-sm btn-demex py-1 px-3 fs-7 shadow-sm" style="background-color: var(--primary-color) !important; color: white;">
                                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Cotizar
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-emoji-smile text-success fs-3 d-block mb-2" style="color: var(--primary-color) !important;"></i>
                                <span class="fw-semibold">No hay leads nuevos pendientes en la bandeja de entrada.</span>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php 
// Cargamos el pie de página unificado usando la ruta de directorio nativa
include __DIR__ . '/../includes/footer.php'; 
?>