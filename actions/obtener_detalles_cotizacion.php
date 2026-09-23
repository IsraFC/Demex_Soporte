<?php
/**
 * ARCHIVO: actions/obtener_detalles_cotizacion.php
 * DESCRIPCIÓN: Retorna la estructura HTML detallada de una cotización para el modal dinámico.
 * Soporta Leads Nuevos y Clientes Recurrentes (Recompras).
 * MODIFICACIÓN: Compatible con cotizaciones unitarias de Maquinaria y multipartida de Materia Prima.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 9.0 (Soporte Multipartida en Modal con Cotización Detalle)
 */

require_once '../config/db.php';

$id_cotizacion = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;

if ($id_cotizacion <= 0) {
    echo '<div class="alert alert-danger m-2"><i class="bi bi-exclamation-octagon-fill me-2"></i>Identificador de cotización inválido.</div>';
    exit();
}

try {
    // 1. Consulta cabecera con LEFT JOIN a productos (id_producto es NULL en materia prima)
    $sql = "SELECT c.*, p.nombre AS maquina_modelo,
                   f.nombre AS lead_nombre, f.correo AS lead_correo, f.telefono AS lead_telefono,
                   cl.nombre_cliente, cl.correo AS cliente_correo, cl.telefono AS cliente_telefono
            FROM cotizacion c
            LEFT JOIN productos p ON c.id_producto = p.id_producto
            LEFT JOIN prospectos p_lead ON c.id_prospecto = p_lead.id_prospecto
            LEFT JOIN formulario f ON p_lead.id_formulario = f.id_formulario
            LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
            WHERE c.id_cotizacion = ? LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_cotizacion]);
    $cot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cot) {
        echo '<div class="alert alert-warning m-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>No se encontraron los datos de la cotización nº #' . $id_cotizacion . '</div>';
        exit();
    }

    // 2. Consulta de partidas asociadas en cotizacion_detalle
    $sql_partidas = "SELECT cd.*, prod.nombre AS producto_nombre, prod.sku_codigo
                     FROM cotizacion_detalle cd
                     INNER JOIN productos prod ON cd.id_producto = prod.id_producto
                     WHERE cd.id_cotizacion = ?
                     ORDER BY cd.id_detalle ASC";
    $stmt_partidas = $pdo->prepare($sql_partidas);
    $stmt_partidas->execute([$id_cotizacion]);
    $partidas_cotizadas = $stmt_partidas->fetchAll(PDO::FETCH_ASSOC);

    $es_recompra = !empty($cot['id_cliente']);
    $nombre_completo  = $es_recompra ? $cot['nombre_cliente'] : $cot['lead_nombre'];
    $correo_display   = $es_recompra ? $cot['cliente_correo'] : $cot['lead_correo'];
    $telefono_display = $es_recompra ? $cot['cliente_telefono'] : $cot['lead_telefono'];

    // Tratamiento de notas y bloque bancario
    $notas_limpias = $cot['notes'] ?? '';
    $incluye_iva = 1;
    if (strpos($cot['notes'] ?? '', '|||') !== false) {
        $partes_notas = explode('|||', $cot['notes']);
        $notas_limpias = trim($partes_notas[0]);
        $json_bancos = json_decode(base64_decode($partes_notas[1]), true);
        if ($json_bancos && isset($json_bancos['incluye_iva'])) {
            $incluye_iva = intval($json_bancos['incluye_iva']);
        }
    }

    // Cálculos de importes
    $subtotal_partidas = 0;
    if (!empty($partidas_cotizadas)) {
        foreach ($partidas_cotizadas as $p) {
            $subtotal_partidas += floatval($p['subtotal']);
        }
    } else {
        $subtotal_partidas = floatval($cot['precio_pactado']) * intval($cot['cantidad']);
    }

    $costo_envio = floatval($cot['costo_envio'] ?? 0);
    $base_con_envio = $subtotal_partidas + $costo_envio;
    $iva_monto = ($incluye_iva === 1) ? ($base_con_envio * 0.16) : 0;
    $total_general = $base_con_envio + $iva_monto;
    ?>
    
    <div class="container-fluid py-1">
        <div class="row mb-3 pb-2 border-bottom align-items-center">
            <div class="col-md-6">
                <span class="text-muted small text-uppercase fw-bold">Folio de Cotización</span>
                <h3 class="fw-bold text-danger mb-0">#<?= $cot['id_cotizacion'] ?></h3>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="badge text-uppercase px-3 py-2 rounded-pill shadow-sm bg-dark text-white">
                    Sucursal: <?= htmlspecialchars($cot['sucursal']) ?>
                </span>
            </div>
        </div>

        <div class="row g-4">
            <!-- COLUMNA 1: EXPEDIENTE COMERCIAL -->
            <div class="col-md-6 border-end">
                <h6 class="fw-bold text-dark text-uppercase mb-3">
                    <i class="bi bi-person-badge text-danger me-2"></i>Expediente Comercial
                </h6>
                
                <table class="table table-sm table-borderless small">
                    <tr>
                        <td class="text-muted fw-bold" width="38%">Cliente / Contacto:</td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($nombre_completo ?? 'N/D') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">RFC Receptor:</td>
                        <td class="text-danger fw-bold"><?= htmlspecialchars($cot['rfc_receptor'] ?: 'XAXX010101000') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Canal / Tipo:</td>
                        <td>
                            <span class="fw-semibold"><?= htmlspecialchars($cot['tipo_cliente']) ?></span>
                            <span class="badge bg-light text-muted border ms-1" style="font-size: 0.6rem;"><?= $es_recompra ? 'RECOMPRA' : 'PROSPECTO' ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Correo:</td>
                        <td><?= htmlspecialchars($correo_display ?: 'Sin correo registrado') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Teléfono:</td>
                        <td><?= htmlspecialchars($telefono_display ?? 'N/D') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Ubicación Entrega:</td>
                        <td class="small text-secondary"><?= htmlspecialchars($cot['direccion_entrega'] ?: 'Recoge en Planta / Sucursal Central') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Vigencia Promoción:</td>
                        <td class="fw-semibold text-danger"><?= date('d/m/Y', strtotime($cot['fecha_vencimiento'])) ?></td>
                    </tr>
                </table>

                <?php if (!empty($cot['especificacion_cotizada'])): ?>
                <div class="p-2 bg-light border rounded mt-2">
                    <small class="text-muted fw-bold d-block border-bottom mb-1" style="font-size: 0.65rem;">ESPECIFICACIÓN / NOTAS TÉCNICAS</small>
                    <p class="mb-0 small text-secondary" style="white-space: pre-wrap; max-height: 90px; overflow-y: auto; line-height: 1.35;"><?= htmlspecialchars($cot['especificacion_cotizada']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- COLUMNA 2: RESUMEN FINANCIERO Y PARTIDAS -->
            <div class="col-md-6">
                <h6 class="fw-bold text-dark text-uppercase mb-3">
                    <i class="bi bi-calculator text-danger me-2"></i>Conceptos Cotizados
                </h6>
                
                <div class="bg-white p-3 border rounded shadow-sm">
                    <?php if (!empty($partidas_cotizadas)): ?>
                        <div class="table-responsive mb-2" style="max-height: 170px; overflow-y: auto;">
                            <table class="table table-sm table-bordered m-0" style="font-size: 0.76rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">P. Pactado</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($partidas_cotizadas as $partida): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($partida['producto_nombre']) ?></td>
                                            <td class="text-center"><?= $partida['cantidad'] ?> <?= htmlspecialchars($partida['unidad']) ?></td>
                                            <td class="text-end">$<?= number_format($partida['precio_pactado'], 2) ?></td>
                                            <td class="text-end fw-bold">$<?= number_format($partida['subtotal'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Caso de una sola máquina histórica -->
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Producto / Equipo:</span>
                            <span class="badge bg-success-subtle text-success fw-bold"><?= htmlspecialchars($cot['maquina_modelo'] ?? 'Equipo DEMEX') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Cantidad Solicitada:</span>
                            <span class="fw-bold"><?= $cot['cantidad'] ?> <?= htmlspecialchars($cot['unidad']) ?>(s)</span>
                        </div>
                    <?php endif; ?>
                    
                    <hr class="my-2">
                    
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Subtotal Productos:</span>
                        <span class="fw-bold text-dark">$<?= number_format($subtotal_partidas, 2) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Costo Envío / Flete:</span>
                        <span class="fw-bold text-dark">$<?= number_format($costo_envio, 2) ?></span>
                    </div>

                    <?php if ($incluye_iva === 1): ?>
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>IVA Traslado (16%):</span>
                        <span class="fw-semibold">$<?= number_format($iva_monto, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <hr class="my-2">
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-start">
                            <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">ESTATUS DOC.</small>
                            <?php if ($cot['status_cotizacion'] === 'Vencida'): ?>
                                <span class="badge bg-danger text-uppercase" style="font-size: 0.7rem;"><i class="bi bi-calendar-x me-1"></i> Vencida</span>
                            <?php else: ?>
                                <span class="badge bg-success text-uppercase" style="font-size: 0.7rem;"><i class="bi bi-calendar-check me-1"></i> Vigente</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block fw-bold" style="font-size: 0.65rem;">GRAN TOTAL NETO</small>
                            <span class="h4 fw-bold text-success mb-0">$<?= number_format($total_general, 2) ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($notas_limpias)): ?>
                <div class="mt-2">
                    <div class="p-2 bg-light border rounded">
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.65rem;"><i class="bi bi-journal-text me-1"></i> NOTAS INTERNAS</small>
                        <p class="mb-0 small text-secondary fst-italic">"<?= htmlspecialchars($notas_limpias) ?>"</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mt-4 pt-2 border-top">
            <div class="col-12 text-end">
                <a href="generar_pdf_cotizacion.php?id_cotizacion=<?= $cot['id_cotizacion'] ?>" class="btn btn-danger px-4 fw-bold shadow-sm d-inline-flex align-items-center" style="border-radius: 8px; font-size: 0.88rem;" target="_blank">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i> Abrir / Imprimir PDF Oficial
                </a>
            </div>
        </div>
    </div>

    <?php
} catch (\Exception $e) { 
    echo '<div class="alert alert-danger m-2"><i class="bi bi-exclamation-octagon-fill me-2"></i>Error técnico al consultar el desglose: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
}