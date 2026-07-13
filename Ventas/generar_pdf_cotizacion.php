<?php
/**
 * ARCHIVO: Ventas/generar_pdf_cotizacion.php
 * DESCRIPCIÓN: Compilador y renderizador en formato de Cotización Real Impresible.
 * Integra el panel superior con el diseño y la paleta roja oficial del sistema de DEMEX Central (.btn-danger).
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 8.3 (RFC Dinámico y Dirección de Entrega Opcional Blindada)
 */

$page_title = "Propuesta Comercial Generada | CRM Ventas";
require_once '../config/db.php';

// Capturamos el ID de la cotización que nos mandó el backend por la URL
$id_cotizacion = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;

if ($id_cotizacion === 0) {
    echo "<h3>Error: ID de cotización no válido.</h3>";
    exit();
}

// CORREGIDO: Modificamos la consulta SQL unificada para extraer la información de clientes (Cartera) o leads (Formulario)
$sql = "SELECT c.*, 
               m.modelo AS maquina_nombre,
               f.nombre AS lead_cliente_nombre,
               f.telefono AS lead_cliente_telefono,
               f.correo AS lead_cliente_correo,
               cl.nombre_cliente AS cartera_cliente_nombre,
               cl.telefono AS cartera_cliente_telefono,
               cl.correo AS cartera_cliente_correo,
               u.nombre AS asesor_nombre
        FROM cotizacion c
        INNER JOIN maquinaria m ON c.id_maquina = m.id_maquina
        INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
        LEFT JOIN prospectos p ON c.id_prospecto = p.id_prospecto
        LEFT JOIN formulario f ON p.id_formulario = f.id_formulario
        LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
        WHERE c.id_cotizacion = :id_cotizacion LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id_cotizacion' => $id_cotizacion]);
$cotizacion = $stmt->fetch();

if (!$cotizacion) {
    echo "<h3>Error: La cotización no existe en el sistema o los IDs relacionales fallaron.</h3>";
    exit();
}

// CORREGIDO: Definición limpia y dinámica del cliente/canal para evitar cruces con tipos de clientes
$nombre_cliente_final   = !empty($cotizacion['id_cliente']) ? $cotizacion['cartera_cliente_nombre'] : ($cotizacion['lead_cliente_nombre'] ?? 'Público General');
$telefono_cliente_final = !empty($cotizacion['id_cliente']) ? $cotizacion['cartera_cliente_telefono'] : ($cotizacion['lead_cliente_telefono'] ?? '');
$correo_cliente_final   = !empty($cotizacion['id_cliente']) ? $cotizacion['cartera_cliente_correo'] : ($cotizacion['lead_cliente_correo'] ?? '');

// Definición dinámica del botón de regreso según el origen del documento
$url_regresar = "leads_crm.php";
if (!empty($cotizacion['id_cliente']) && intval($cotizacion['id_cliente']) > 0) {
    $url_regresar = "recompras_crm.php";
}

// --- CORREGIDO: PROCESADOR DE DESEMPAQUETADO BANCARIO Y OBSERVACIONES ORIGINALES ---
$notas_limpias = $cotizacion['notes']; // Se inicializa con el valor crudo de la celda de la base de datos
$bancos = [
    'condicion' => "Precios de promoción para pagos por transferencia o efectivo.\nNo incluyen el envío.",
    'b1_nom'    => "BANORTE", 'b1_cta' => "0434571284", 'b1_clabe' => "072 650 00434571284 8",
    'b2_nom'    => "BANAMEX", 'b2_cta' => "7213722", 'b2_clabe' => "002 650 70107213722 1", 'b2_suc' => "7010"
];
$incluye_iva = 1; 

if (strpos($cotizacion['notes'], '|||') !== false) {
    $partes_notas = explode('|||', $cotizacion['notes']);
    $notas_limpias = trim($partes_notas[0]); // Captura de forma limpia el texto de las observaciones escritas a mano
    $json_desencriptado = json_decode(base64_decode($partes_notas[1]), true);
    if ($json_desencriptado) {
        $bancos = $json_desencriptado;
        if (isset($json_desencriptado['incluye_iva'])) {
            $incluye_iva = intval($json_desencriptado['incluye_iva']);
        }
    }
}

// RECÁLCULO DINÁMICO EXACTO EN PHP
$subtotal_partida = $cotizacion['precio_pactado'] * $cotizacion['cantidad'];
$subtotal_con_envio = $subtotal_partida + $cotizacion['costo_envio'];

$iva_traslado = ($incluye_iva === 1) ? ($subtotal_con_envio * 0.16) : 0;
$gran_total_neto = $subtotal_con_envio + $iva_traslado;

include '../includes/header.php';
?>

<style>
    /* ---- AJUSTES DE MAQUETACIÓN EN PANTALLA (Estándar Global del CRM) ---- */
    .crm-control-card {
        background: #ffffff !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 0.35rem !important;
    }
    
    .crm-text-header {
        font-size: 1rem;
        font-weight: 600;
        color: #333333;
    }

    /* ---- REGLAS ESTRICTAS PARA GENERACIÓN DE PDF / IMPRESIÓN ---- */
    @media print {
        .sidebar, #sidebar-wrapper, .navbar, header, footer, .d-print-none, .btn, .nav, #menu-toggle, .crm-control-card {
            display: none !important;
            visibility: hidden !important;
        }

        html, body, #wrapper, #page-content-wrapper, .main-content {
            overflow: hidden !important;
            overflow-x: hidden !important;
            overflow-y: hidden !important;
            background: #ffffff !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            height: auto !important;
        }

        .cotizacion-print-block {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            background: #ffffff !important;
        }

        th, .table-light {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<div class="container-fluid mt-3 mb-4 d-print-none">
    <div class="card crm-control-card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center py-3 px-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-file-earmark-pdf-fill text-danger me-2 fs-5"></i>
                <span class="crm-text-header text-dark">Vista previa del documento</span>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $url_regresar ?>" class="btn btn-secondary shadow-sm px-3 d-inline-flex align-items-center fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> Regresar
                </a>
                <button onclick="window.print();" class="btn btn-danger shadow-sm px-4 d-inline-flex align-items-center fw-semibold">
                    <i class="bi bi-printer-fill me-2"></i> Imprimir PDF
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid user-select-none pb-5">
    <div class="card cotizacion-print-block p-4 bg-white border shadow-sm mx-auto" style="max-width: 950px; border-radius: 8px;">
        
        <div class="row align-items-start mb-4">
            <div class="col-7">
                <img src="../img/logo_demex_rj.png" alt="Desarrollo Mexicano Logo" class="mb-2" style="max-height: 85px; width: auto; object-fit: contain;" onerror="this.src='../img/maquinas/default.png';">
                <p class="text-muted small m-0" style="font-size: 0.82rem; line-height: 1.4;">
                    RFC: DEM160408QF8 | Tel. 2228892629<br>
                    San Andrés Cholula, Puebla, México<br>
                    Atendido por: <span class="fw-semibold text-dark">Nadia Torres Fernández</span>
                </p>
            </div>
            <div class="col-5 text-end">
                <h4 class="fw-bold text-danger mb-1" style="letter-spacing: -0.5px;">COTIZACIÓN #<?= $cotizacion['id_cotizacion'] ?></h4>
                <div class="p-2 border rounded bg-light text-start d-inline-block" style="font-size: 0.8rem; min-width: 230px;">
                    <div class="d-flex justify-content-between"><strong>Fecha Emisión:</strong> <span><?= date('d/m/Y', strtotime($cotizacion['fecha_emision'])) ?></span></div>
                    <div class="d-flex justify-content-between"><strong>Vencimiento:</strong> <span><?= date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])) ?></span></div>
                    <div class="d-flex justify-content-between"><strong>Sucursal:</strong> <span class="text-uppercase fw-semibold"><?= htmlspecialchars($cotizacion['sucursal']) ?></span></div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="text-uppercase fw-bold text-muted mb-2 pb-1 border-bottom" style="font-size: 0.8rem; letter-spacing: 0.5px;">Datos del Cliente</div>
                <div class="row" style="font-size: 0.85rem;">
                    <div class="col-7">
                        <small class="text-muted d-block">Razón Social / Nombre:</small>
                        <span class="text-dark fw-bold fs-6"><?= htmlspecialchars($nombre_cliente_final) ?></span>
                        
                        <small class="text-muted d-block mt-2">Dirección de Destino:</small>
                        <!-- MODIFICADO: Valida si la dirección de entrega existe, si no, coloca un texto corporativo gris -->
                        <span class="text-muted d-block shadow-none" style="line-height: 1.3;">
                            <?= !empty($cotizacion['direccion_entrega']) ? nl2br(htmlspecialchars($cotizacion['direccion_entrega'])) : '<em>Dirección por confirmar / Entrega en Sucursal Central</em>' ?>
                        </span>
                    </div>
                    <div class="col-5">
                        <small class="text-muted d-block">RFC Receptor:</small>
                        <!-- MODIFICADO: Valida si el RFC viene vacío de la base de datos para inyectar el genérico de inmediato -->
                        <span class="text-uppercase text-dark fw-bold d-block mb-2">
                            <?= !empty($cotizacion['rfc_receptor']) ? htmlspecialchars($cotizacion['rfc_receptor']) : 'XAXX010101000' ?>
                        </span>
                        
                        <?php if(!empty($telefono_cliente_final)): ?>
                            <strong>Teléfono:</strong> <?= htmlspecialchars($telefono_cliente_final) ?><br>
                        <?php endif; ?>
                        <?php if(!empty($correo_cliente_final)): ?>
                            <strong>Correo:</strong> <?= htmlspecialchars($correo_cliente_final) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="text-uppercase fw-bold text-muted mb-2 pb-1 border-bottom" style="font-size: 0.8rem; letter-spacing: 0.5px;">Conceptos Cotizados</div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light text-uppercase" style="font-size: 0.75rem; font-weight: 700;">
                            <tr>
                                <th class="text-center" style="width: 8%;">Cant.</th>
                                <th class="text-center" style="width: 12%;">Unidad</th>
                                <th style="width: 50%;">Descripción Comercial / Ficha Técnica</th>
                                <th class="text-end" style="width: 15%;">P. Unitario</th>
                                <th class="text-end" style="width: 15%;">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center fw-bold fs-6"><?= $cotizacion['cantidad'] ?>.00</td>
                                <td class="text-center text-uppercase text-muted" style="font-size: 0.78rem;"><?= htmlspecialchars($cotizacion['unidad']) ?></td>
                                <td>
                                    <strong class="text-dark text-uppercase">MÁQUINA DE HELADO MODELO <?= htmlspecialchars($cotizacion['maquina_nombre']) ?></strong>
                                    <div class="text-muted mt-1" style="font-size: 0.78rem; white-space: pre-wrap; line-height: 1.4;"><?= htmlspecialchars($cotizacion['especificacion_cotizada']) ?></div>
                                </td>
                                <td class="text-end fw-semibold">$<?= number_format($cotizacion['precio_pactado'], 2, '.', ',') ?></td>
                                <td class="text-end fw-bold text-dark">$<?= number_format($subtotal_partida, 2, '.', ',') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row align-items-start mt-2">
            <div class="col-7" style="font-size: 0.72rem; color: #555; line-height: 1.4;">
                <div class="fw-bold text-uppercase text-secondary mb-1" style="letter-spacing: 0.3px;">Condiciones Comercial y de Garantía:</div>
                <div class="p-2 border rounded bg-light mb-2 text-muted">
                    <ul class="mb-0 ps-3">
                        <?= implode('', array_map(function($linea) { return "<li>" . htmlspecialchars(trim($linea)) . "</li>"; }, explode("\n", $bancos['condicion']))) ?>
                        <li>Garantía de 1 año integral contra cualquier defectuación de fábrica (excepto consumibles).</li>
                        <li>Garantía de 2 años en componentes críticos (compresor principal y tarjetas electrónicas).</li>
                        <li>Precios cotizados en Pesos Mexicanos ($ MXN) con vigencia ligada a la fecha de vencimiento.</li>
                    </ul>
                </div>

                <div class="fw-bold text-uppercase text-secondary mb-1" style="letter-spacing: 0.3px;">Datos Bancarios para Liquidación:</div>
                <div class="p-3 border rounded bg-white mb-2 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
                    <div class="mb-2"><strong>Beneficiario:</strong> <span class="text-dark fw-bold">DEMEXTOR SA DE CV</span></div>
                    <div class="row small g-2">
                        <div class="col-6 border-end pe-2">
                            <span class="badge bg-dark mb-1 text-uppercase" style="font-size: 0.6rem;">Opción 1: <?= htmlspecialchars($bancos['b1_nom']) ?></span>
                            <div class="mt-1"><strong>Cuenta:</strong> <?= htmlspecialchars($bancos['b1_cta']) ?></div>
                            <div><strong>Clabe:</strong> <?= htmlspecialchars($bancos['b1_clabe']) ?></div>
                        </div>
                        <div class="col-6 ps-2">
                            <span class="badge bg-primary mb-1 text-uppercase" style="font-size: 0.6rem; background-color: #002d72 !important;">Opción 2: <?= htmlspecialchars($bancos['b2_nom']) ?></span>
                            <div class="mt-1"><strong>Cuenta:</strong> <?= htmlspecialchars($bancos['b2_cta']) ?></div>
                            <div><strong>Clabe:</strong> <?= htmlspecialchars($bancos['b2_clabe']) ?></div>
                            <div><strong>Sucursal:</strong> <?= htmlspecialchars($bancos['b2_suc']) ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if(!empty($notas_limpias)): ?>
                    <div class="fw-bold text-uppercase text-secondary mb-1" style="letter-spacing: 0.3px;">Observaciones Especiales:</div>
                    <div class="p-2 border rounded bg-white text-dark border-start border-3 border-danger" style="white-space: pre-wrap;"><?= htmlspecialchars($notas_limpias) ?></div>
                <?php endif; ?>
            </div>
            
            <div class="col-5 ms-auto">
                <table class="table table-sm table-borderless text-end mb-0" style="font-size: 0.85rem;">
                    <tr>
                        <td class="text-muted">Subtotal:</td>
                        <td class="fw-semibold text-dark" style="width: 45%;">$<?= number_format($subtotal_partida, 2, '.', ',') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Gasto de Envío:</td>
                        <td class="fw-semibold text-dark">$<?= number_format($cotizacion['costo_envio'], 2, '.', ',') ?></td>
                    </tr>
                    <?php if ($incluye_iva === 1): ?>
                    <tr class="border-bottom">
                        <td class="text-muted">I.V.A. Traslado (16%):</td>
                        <td class="fw-semibold text-dark">$<?= number_format($iva_traslado, 2, '.', ',') ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="fs-6">
                        <td class="fw-bold text-dark pt-2">Total Neto:</td>
                        <td class="fw-bold text-danger pt-2">$<?= number_format($gran_total_neto, 2, '.', ',') ?> MXN</td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
</div>

<?php 
include '../includes/footer.php';
?>