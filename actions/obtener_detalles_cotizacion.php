<?php
/**
 * ARCHIVO: actions/obtener_detalles_cotizacion.php
 * DESCRIPCIÓN: Retorna la estructura HTML detallada de una cotización para el modal dinámico.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.6 (Rutas de Conexión Corregidas)
 */

// CORREGIDO: Subir dos niveles para encontrar correctamente la configuración de la BD
require_once '../config/db.php';

$id_cotizacion = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;

if ($id_cotizacion <= 0) {
    echo '<div class="alert alert-danger m-2"><i class="bi bi-exclamation-octagon-fill me-2"></i>Identificador de cotización inválido.</div>';
    exit();
}

try {
    // Consulta limpia vinculando cotizaciones, maquinaria, prospectos y formularios
    $sql = "SELECT c.*, m.modelo AS maquina_modelo, f.nombre, f.apellidos, f.correo, f.telefono
            FROM cotizacion c
            INNER JOIN maquinaria m ON c.id_maquina = m.id_maquina
            INNER JOIN prospectos p ON c.id_prospecto = p.id_prospecto
            INNER JOIN formulario f ON p.id_formulario = f.id_formulario
            WHERE c.id_cotizacion = ? LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_cotizacion]);
    $cot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cot) {
        echo '<div class="alert alert-warning m-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>No se encontraron los datos de la cotización nº #' . $id_cotizacion . '</div>';
        exit();
    }

    // Cálculos financieros para el desglose económico de la tabla
    $subtotal = floatval($cot['precio_base_origen']) * intval($cot['cantidad']);
    $precio_pactado_total = floatval($cot['precio_pactado']) * intval($cot['cantidad']);
    $descuento_total = $subtotal - $precio_pactado_total;
    $total_general = $precio_pactado_total + floatval($cot['costo_envio']);
    ?>
    
    <div class="container-fluid py-1">
        <div class="row mb-3 pb-2 border-bottom align-items-center">
            <div class="col-md-6">
                <span class="text-muted small text-uppercase fw-bold">Folio de Cotización</span>
                <h3 class="fw-bold text-danger mb-0">#<?= $cot['id_cotizacion'] ?></h3>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="badge text-uppercase px-3 py-2 rounded-pill shadow-sm bg-dark text-white">
                    <?= htmlspecialchars($cot['sucursal']) ?>
                </span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 border-end">
                <h6 class="fw-bold text-dark text-uppercase mb-3">
                    <i class="bi bi-person-badge text-danger me-2"></i>Expediente Comercial
                </h6>
                
                <table class="table table-sm table-borderless small">
                    <tr>
                        <td class="text-muted fw-bold" width="40%">Cliente:</td>
                        <td class="fw-bold"><?= htmlspecialchars($cot['nombre'] . ' ' . $cot['apellidos']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">RFC Receptor:</td>
                        <td class="text-danger fw-bold"><?= htmlspecialchars($cot['rfc_receptor'] ?: 'XAXX010101000') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Canal / Tipo:</td>
                        <td><?= htmlspecialchars($cot['tipo_cliente']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Correo:</td>
                        <td><?= htmlspecialchars($cot['correo']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Teléfono:</td>
                        <td><?= htmlspecialchars($cot['telefono']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Ubicación Entrega:</td>
                        <td class="small text-secondary"><?= htmlspecialchars($cot['direccion_entrega'] ?: 'Recoge en Planta / Sucursal') ?></td>
                    </tr>
                </table>

                <div class="p-2 bg-light border rounded mt-3">
                    <small class="text-muted fw-bold d-block border-bottom mb-1" style="font-size: 0.65rem;">ESPECIFICACIÓN ADICIONAL SOLICITADA</small>
                    <p class="mb-0 small text-secondary font-monospace" style="white-space: pre-wrap; max-height: 100px; overflow-y: auto;"><?= htmlspecialchars($cot['especificacion_cotizada'] ?: 'Sin especificaciones técnicas extraordinarias.') ?></p>
                </div>
            </div>

            <div class="col-md-6">
                <h6 class="fw-bold text-dark text-uppercase mb-3">
                    <i class="bi bi-calculator text-danger me-2"></i>Resumen Financiero
                </h6>
                
                <div class="bg-white p-3 border rounded shadow-sm">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Equipo de Interés:</span>
                        <span class="badge bg-danger-sutil text-danger fw-bold"><?= htmlspecialchars($cot['maquina_modelo']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Cantidad Solicitada:</span>
                        <span class="fw-bold"><?= $cot['cantidad'] ?> <?= htmlspecialchars($cot['unidad']) ?>(s)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Precio Lista Unitario:</span>
                        <span class="fw-bold">$<?= number_format($cot['precio_base_origen'], 2) ?></span>
                    </div>
                    
                    <hr class="my-2">
                    
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Subtotal Lista:</span>
                        <span class="fw-bold">$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    
                    <?php if ($descuento_total > 0): ?>
                    <div class="d-flex justify-content-between mb-1 small text-success fw-semibold">
                        <span>Descuento Otorgado:</span>
                        <span>-$<?= number_format($descuento_total, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Costo Envío / Logística:</span>
                        <span class="fw-bold">$<?= number_format($cot['costo_envio'], 2) ?></span>
                    </div>
                    
                    <hr class="my-2">
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-start">
                            <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">ESTATUS DOC.</small>
                            <span class="badge bg-dark text-white text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($cot['status_cotizacion']) ?></span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">TOTAL NETO</small>
                            <span class="h4 fw-bold text-danger mb-0">$<?= number_format($total_general, 2) ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($cot['notes'])): ?>
                <div class="col-12 mt-3">
                    <div class="p-2 bg-light border rounded">
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.65rem;"><i class="bi bi-journal-text"></i> NOTAS INTERNAS DE LA OPERACIÓN</small>
                        <p class="mb-0 small text-secondary italic">"<?= htmlspecialchars($cot['notes']) ?>"</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
} catch (\Exception $e) { 
    echo '<div class="alert alert-danger m-2"><i class="bi bi-exclamation-octagon-fill me-2"></i>Error técnico de consulta: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
}