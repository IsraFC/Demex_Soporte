<?php
/**
 * ARCHIVO: actions/obtener_detalles_ticket.php
 * DESCRIPCIÓN: Genera la vista detallada del ticket mediante una consulta multi-tabla.
 * Incorpora el despliegue del nombre completo del técnico operativo asignado al servicio.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.8 - Corrección de alias de geolocalización de técnicos
 * @date 2026-07-15
 */
require_once '../../config/db.php';

$id_ticket = $_GET['id_ticket'] ?? null;
if (!$id_ticket) { echo "ID no válido."; exit; }

try {
    $sql = "SELECT t.*, c.nombre_cliente, c.telefono, c.ubicacion, 
                   e.modelo, d.*, tec.nombre AS tecnico_asignado, tec.zona AS tecnico_zona, tec.estado AS tecnico_estado
            FROM tickets_soporte t
            JOIN clientes c ON t.id_cliente = c.id_cliente
            LEFT JOIN equipos_garantia e ON t.no_serie = e.no_serie
            LEFT JOIN detalles_costos_tiempos d ON t.id_ticket = d.id_ticket
            LEFT JOIN tecnicos tec ON d.id_tecnico_asignado = tec.id_tecnico
            WHERE t.id_ticket = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ticket]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) { echo "Sin datos."; exit; }

    $pago_no_aplica = (empty($data['estatus_pago']) || $data['accion'] == 'Ninguna' || $data['accion'] == 'Información');
    $textoPago = $pago_no_aplica ? "N/A" : $data['estatus_pago'];
    $badgePago = $pago_no_aplica ? "bg-secondary" : (($data['estatus_pago'] == 'Pagado') ? 'bg-success' : 'bg-danger');

    $colorEstatus = [
        'Abierto'   => 'bg-warning text-dark',
        'Cerrado'   => 'bg-success text-white',
        'Cancelado' => 'bg-secondary text-white'
    ];
    $badgeEstatus = $colorEstatus[$data['estatus']] ?? 'bg-secondary';
?>

<div class="container-fluid py-1">
    <div class="row mb-3 pb-2 border-bottom align-items-center">
        <div class="col-md-6">
            <span class="text-muted small text-uppercase fw-bold">Folio del Servicio</span>
            <h3 class="fw-bold text-danger mb-0">#<?= $data['id_ticket'] ?></h3>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-inline-block text-start me-3 border-end pe-3">
                <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">MÁQUINA FUNCIONANDO</small>
                <span class="fw-bold"><?= $data['maquina_func'] ? '<i class="bi bi-check-circle-fill text-success"></i> SÍ' : '<i class="bi bi-x-circle-fill text-danger"></i> NO' ?></span>
            </div>
            <span class="badge <?= $badgeEstatus ?> px-3 py-2 rounded-pill shadow-sm">
                <?= strtoupper($data['estatus']) ?>
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 border-end">
            <h6 class="fw-bold text-dark text-uppercase mb-3"><i class="bi bi-clipboard-pulse text-danger me-2"></i>Datos del Ticket</h6>
            
            <table class="table table-sm table-borderless small">
                <tr><td class="text-muted fw-bold" width="40%">Cliente:</td><td class="fw-bold"><?= htmlspecialchars($data['nombre_cliente']) ?></td></tr>
                <tr><td class="text-muted fw-bold">Teléfono:</td><td class="fw-bold text-dark"><i class="bi bi-telephone text-muted me-1"></i><?= htmlspecialchars($data['telefono'] ?: 'Sin Registro') ?></td></tr>
                <tr><td class="text-muted fw-bold">Equipo/Modelo:</td><td><?= $data['modelo'] ?: 'S/M' ?></td></tr>
                <tr><td class="text-muted fw-bold">Número de Serie:</td><td class="text-danger fw-bold"><?= $data['no_serie'] ?: 'N/A' ?></td></tr>
                <tr><td class="text-muted fw-bold">Tipo de Llamada:</td><td><?= $data['tipo_llamada'] ?></td></tr>
                <tr><td class="text-muted fw-bold">Tipo de Falla:</td><td><?= $data['tipo_falla'] ?></td></tr>
                <tr><td class="text-muted fw-bold">Garantía:</td><td class="fw-bold <?= $data['garantia_valida'] == 'Válida' ? 'text-success' : 'text-danger' ?>"><?= $data['garantia_valida'] ?></td></tr>
                <tr><td class="text-muted fw-bold">Número de Llamadas:</td><td><span class="badge bg-light text-dark border"><?= $data['no_llamadas'] ?></span></td></tr>
                <tr><td class="text-muted fw-bold">Fecha Apertura:</td><td><?= date('d/m/Y H:i', strtotime($data['fecha_inicial'])) ?></td></tr>
                <tr><td class="text-muted fw-bold">Fecha Cierre:</td><td><?= $data['fecha_cierre'] ? date('d/m/Y H:i', strtotime($data['fecha_cierre'])) : '<span class="text-muted italic">Pendiente</span>' ?></td></tr>
            </table>

            <div class="p-2 bg-light border rounded mt-3">
                <small class="text-muted fw-bold d-block border-bottom mb-1" style="font-size: 0.65rem;">OBSERVACIONES TÉCNICAS</small>
                <p class="mb-0 small text-secondary italic"><?= nl2br(htmlspecialchars($data['observaciones'])) ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <h6 class="fw-bold text-dark text-uppercase mb-3"><i class="bi bi-calculator text-danger me-2"></i>Gestión Financiera</h6>
            
            <div class="row g-2 mb-3">
                <div class="col-12 p-2 bg-white border rounded">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">ACCIÓN REALIZADA</small>
                    <span class="fw-bold text-danger"><?= $data['accion'] ?: 'Información' ?></span>
                </div>
                
                <?php if (!empty($data['tecnico_asignado'])): ?>
                    <div class="col-12 p-2 bg-dark bg-opacity-10 border border-secondary border-opacity-25 rounded animate__animated animate__fadeIn">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;"><i class="bi bi-person-badge text-danger me-1"></i>TÉCNICO OPERATIVO ASIGNADO</small>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($data['tecnico_asignado']) ?></span>
                        <small class="text-muted d-block" style="font-size: 0.7rem; margin-top: 2px;"><i class="bi bi-geo-alt me-1"></i>Zona Cobertura: <?= htmlspecialchars($data['tecnico_zona'] . ', ' . $data['tecnico_estado']) ?></small>
                    </div>
                <?php endif; ?>

                <div class="col-6 p-2 bg-light rounded border">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">FECHA INICIO ACC.</small>
                    <span class="small"><?= $data['fecha_inicio_acc'] ?: '---' ?></span>
                </div>
                <div class="col-6 p-2 bg-light rounded border">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">FECHA FIN ACC.</small>
                    <span class="small"><?= $data['fecha_fin_acc'] ?: '---' ?></span>
                </div>
            </div>

            <div class="bg-white p-3 border rounded shadow-sm">
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Refacciones Garantía:</span>
                    <span class="fw-bold">$<?= number_format($data['costo_refac_garantia'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Refacciones Venta:</span>
                    <span class="fw-bold">$<?= number_format($data['costo_refac_venta'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Costo Base:</span>
                    <span class="fw-bold">$<?= number_format($data['costo_base'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Costo Técnico:</span>
                    <span class="fw-bold">$<?= number_format($data['costo_tecnico'], 2) ?></span>
                </div>
                <div class="col-md-12 d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Costo Envío:</span>
                    <span class="fw-bold">$<?= number_format($data['costo_envio'], 2) ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-start">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">DÍAS ACCIÓN</small>
                        <span class="badge bg-dark"><?= $data['tiempo_accion'] ?? 0 ?> días</span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">TOTAL NETO</small>
                        <span class="h4 fw-bold text-danger mb-0">$<?= number_format($data['costo_total'], 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="row mt-3 g-2 small">
                <div class="col-6">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">ESTATUS PAGO</small>
                    <span class="badge <?= $badgePago ?> w-100 py-2"><?= $textoPago ?></span>
                </div>
                <div class="col-6 text-center border rounded py-1">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">FACTURA</small>
                    <span class="fw-bold"><?= $data['requiere_factura'] ? '<i class="bi bi-file-earmark-check-fill text-success"></i> SÍ' : '<i class="bi bi-file-earmark-x-fill text-muted"></i> NO' ?></span>
                </div>
                <div class="col-12 mt-2">
                    <div class="p-2 bg-light border rounded">
                        <i class="bi bi-tag-fill text-danger me-1"></i> <strong>Cotización:</strong> <?= $data['no_cotizacion'] ?: 'N/A' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
} catch (Exception $e) { echo "Error técnico detallado: " . $e->getMessage(); }