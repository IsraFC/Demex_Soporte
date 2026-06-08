<?php
/**
 * ARCHIVO: Ventas/generar_pdf_cotizacion.php
 * DESCRIPCIÓN: Compilador y renderizador en formato de Cotización Real Impresible.
 * Oculta menús del sistema y genera un formato corporativo formal.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 3.0 (Formato Ejecutivo de Impresión)
 */

$page_title = "Propuesta Comercial Generada | CRM Ventas";
require_once '../config/db.php';

// Capturamos el ID de la cotización que nos mandó el backend por la URL
$id_cotizacion = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;

if ($id_cotizacion === 0) {
    echo "<h3>Error: ID de cotización no válido.</h3>";
    exit();
}

// Jalamos los datos mediante JOIN estricto usando las relaciones reales de tu SQL
$sql = "SELECT c.*, 
               m.modelo AS maquina_nombre,
               CONCAT(f.nombre, ' ', f.apellidos) AS cliente_nombre,
               f.telefono AS cliente_telefono,
               f.correo AS cliente_correo,
               CONCAT(u.nombre, ' ', u.apellidos) AS asesor_nombre
        FROM cotizacion c
        INNER JOIN maquinaria m ON c.id_maquina = m.id_maquina
        INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
        LEFT JOIN prospectos p ON c.id_prospecto = p.id_prospecto
        LEFT JOIN formulario f ON p.id_formulario = f.id_formulario
        WHERE c.id_cotizacion = :id_cotizacion LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id_cotizacion' => $id_cotizacion]);
$cotizacion = $stmt->fetch();

if (!$cotizacion) {
    echo "<h3>Error: La cotización no existe en el sistema o los IDs relacionales fallaron.</h3>";
    exit();
}

// RECÁLCULO DINÁMICO EXACTO EN PHP
$subtotal_partida = $cotizacion['precio_pactado'] * $cotizacion['cantidad'];
$subtotal_con_envio = $subtotal_partida + $cotizacion['costo_envio'];
$iva_traslado = $subtotal_con_envio * 0.16;
$gran_total_neto = $subtotal_con_envio + $iva_traslado;

include '../includes/header.php';
?>

<style>
    /* Estilos base en pantalla */
    .cotizacion-print-container {
        background-color: #ffffff;
        font-family: 'Arial', sans-serif;
        color: #333333;
        line-height: 1.4;
    }
    .header-logo {
        max-height: 75px;
        width: auto;
        object-fit: contain;
    }
    .text-danger-demex {
        color: #dc3545 !important;
    }
    .bg-danger-demex {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }
    .tabla-cotizacion th {
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 0.75rem;
        border-bottom: 2px solid #dee2e6 !important;
    }
    .tabla-cotizacion td {
        font-size: 0.85rem;
        vertical-align: top;
    }
    .seccion-titulo {
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #dc3545;
        padding-bottom: 3px;
        margin-bottom: 10px;
    }

    /* REGLAS CRÍTICAS PARA CUANDO SE GENERA EL PDF O SE IMPRIME */
    @media print {
        /* Oculta por completo el menú del sistema, sidebar, navbar, headers y footers generales del CRM */
        header, footer, .sidebar, .navbar, .nav, .d-print-none, .btn-acciones-crm, #sidebar-wrapper, .main-header {
            display: none !important;
        }
        
        /* Forzar que el contenedor ocupe toda la página sin márgenes de la app web */
        body, .container, .container-fluid, .main-content {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        
        .cotizacion-print-container {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Asegura que los colores de fondo e imágenes de Bootstrap se rendericen en el PDF */
        th, .bg-danger-demex, .badge {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .seccion-titulo {
            border-bottom: 2px solid #000 !important;
        }

        /* Quitar paginaciones automáticas molestas que rompen tablas */
        tr {
            page-break-inside: avoid;
        }
    }
</style>

<div class="d-flex gap-2 justify-content-end mb-4 d-print-none">
    <a href="cotizaciones.php" class="btn btn-light fw-bold px-4 border shadow-sm" style="border-radius: 8px;">
        <i class="bi bi-arrow-left me-1"></i> Nueva Cotización
    </a>
    <button onclick="window.print();" class="btn btn-danger fw-bold px-4 shadow-sm" style="border-radius: 8px;">
        <i class="bi bi-printer-fill me-1"></i> Imprimir / Guardar PDF
    </button>
</div>

<div class="cotizacion-print-container p-4 border rounded shadow-sm bg-white">
    
    <div class="row align-items-center mb-4">
        <div class="col-7 col-md-7">
            <img src="../img/logo.png" alt="Desarrollo Mexicano Logo" class="header-logo mb-2" onerror="this.src='../img/maquinas/default.png';">
            <h5 class="fw-bold text-dark mb-0">DESARROLLO MEXICANO</h5>
            <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                RFC: DEM160408QF8 | Tel. 2228892629<br>
                San Andrés Cholula, Puebla, México<br>
                Elaborado por: <span class="fw-semibold text-dark"><?= htmlspecialchars($cotizacion['asesor_nombre']) ?></span>
            </p>
        </div>
        <div class="col-5 col-md-5 text-end">
            <h4 class="fw-bold text-danger-demex mb-1" style="letter-spacing: -0.5px;">Cotización: #<?= $cotizacion['id_cotizacion'] ?></h4>
            <div class="p-2 border rounded bg-light d-inline-block text-start" style="font-size: 0.8rem; min-width: 220px;">
                <div class="d-flex justify-content-between"><strong>Fecha Creación:</strong> <span><?= date('d/m/Y', strtotime($cotizacion['fecha_emision'])) ?></span></div>
                <div class="d-flex justify-content-between"><strong>Vencimiento:</strong> <span><?= date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])) ?></span></div>
                <div class="d-flex justify-content-between"><strong>Sucursal:</strong> <span class="text-uppercase"><?= htmlspecialchars($cotizacion['sucursal']) ?></span></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="fw-bold text-uppercase text-secondary seccion-titulo">Datos del Receptor</div>
            <div class="row" style="font-size: 0.85rem;">
                <div class="col-7">
                    <p class="mb-1"><strong>Cliente / Razón Social:</strong><br><span class="text-dark fs-6 fw-semibold"><?= htmlspecialchars($cotizacion['cliente_nombre'] ?? 'Público General') ?></span></p>
                    <p class="mb-0"><strong>Dirección de Entrega:</strong><br><span class="text-muted"><?= nl2br(htmlspecialchars($cotizacion['direccion_entrega'])) ?></span></p>
                </div>
                <div class="col-5">
                    <p class="mb-1"><strong>RFC:</strong> <span class="text-uppercase text-dark fw-mono"><?= htmlspecialchars($cotizacion['rfc_receptor']) ?></span></p>
                    <?php if(!empty($cotizacion['cliente_telefono'])): ?>
                        <p class="mb-1"><strong>Teléfono:</strong> <?= htmlspecialchars($cotizacion['cliente_telefono']) ?></p>
                    <?php endif; ?>
                    <?php if(!empty($cotizacion['cliente_correo'])): ?>
                        <p class="mb-0"><strong>Correo:</strong> <?= htmlspecialchars($cotizacion['cliente_correo']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="fw-bold text-uppercase text-secondary seccion-titulo">Conceptos de la Propuesta</div>
            <div class="table-responsive">
                <table class="table table-bordered tabla-cotizacion mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 8%;">Cantidad</th>
                            <th class="text-center" style="width: 12%;">Unidad</th>
                            <th style="width: 50%;">Descripción / Especificación Técnica</th>
                            <th class="text-end" style="width: 15%;">Precio Unitario</th>
                            <th class="text-end" style="width: 15%;">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center fw-bold"><?= $cotizacion['cantidad'] ?>.00</td>
                            <td class="text-center text-uppercase text-muted"><?= htmlspecialchars($cotizacion['unidad']) ?></td>
                            <td>
                                <strong class="text-dark text-uppercase">MÁQUINA DE HELADO MODELO <?= htmlspecialchars($cotizacion['maquina_nombre']) ?></strong>
                                <div class="text-muted mt-1" style="font-size: 0.78rem; white-space: pre-wrap; line-height: 1.3;"><?= htmlspecialchars($cotizacion['especificacion_cotizada']) ?></div>
                            </td>
                            <td class="text-end fw-semibold">$<?= number_format($cotizacion['precio_pactado'], 2, '.', ',') ?></td>
                            <td class="text-end fw-bold">$<?= number_format($subtotal_partida, 2, '.', ',') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row align-items-start pt-2">
        <div class="col-7 col-md-7" style="font-size: 0.72rem; color: #555;">
            <div class="fw-bold text-uppercase text-secondary small mb-1" style="letter-spacing: 0.5px;">Términos y Condiciones Generales:</div>
            <div class="p-2 border rounded bg-light mb-2" style="line-height: 1.3;">
                <ul class="margin-0 padding-0 ps-3 mb-0 text-muted">
                    <li>Incluye 1 año de garantía ante defectos de fábrica (excepto piezas de desgaste mecánico).</li>
                    <li>Garantía extendida de 2 años en compresores principales y tarjetas lógicas de control.</li>
                    <li>Precios expresados en Moneda Nacional ($ MXN) sujetos a cambios sin previo aviso según vigencia.</li>
                </ul>
            </div>
            <?php if(!empty($cotizacion['notes'])): ?>
                <div class="fw-bold text-uppercase text-secondary small mb-1" style="letter-spacing: 0.5px;">Observaciones Especiales del Asesor:</div>
                <div class="p-2 border rounded bg-white text-dark border-start border-3 border-danger" style="white-space: pre-wrap; line-height: 1.3;"><?= htmlspecialchars($cotizacion['notes']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-5 col-md-5 ms-auto">
            <table class="table table-sm table-borderless mb-0 text-end" style="font-size: 0.85rem;">
                <tr>
                    <td class="text-muted">Subtotal Conceptos:</td>
                    <td class="fw-semibold text-dark" style="width: 45%;">$<?= number_format($subtotal_partida, 2, '.', ',') ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Gastos Logísticos / Envío:</td>
                    <td class="fw-semibold text-dark">$<?= number_format($cotizacion['costo_envio'], 2, '.', ',') ?></td>
                </tr>
                <tr class="border-bottom">
                    <td class="text-muted">I.V.A. Traslado (16%):</td>
                    <td class="fw-semibold text-dark">$<?= number_format($iva_traslado, 2, '.', ',') ?></td>
                </tr>
                <tr style="font-size: 1.05rem;">
                    <td class="fw-bold text-dark pt-2">Total Neto:</td>
                    <td class="fw-bold text-danger-demex pt-2">$<?= number_format($gran_total_neto, 2, '.', ',') ?> MXN</td>
                </tr>
            </table>
        </div>
    </div>

</div>

<?php 
include '../includes/footer.php'; 
?>