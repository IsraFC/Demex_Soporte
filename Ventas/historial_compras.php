<?php
/**
 * ARCHIVO: Ventas/historial_compras.php
 * DESCRIPCIÓN: Historial Cronológico y Expediente de Adquisiciones por Cliente.
 * Centraliza las métricas de inversión (CLV del cliente), cantidad de equipos y el desglose de documentos.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 (Vista Independiente Dedicada)
 */

$page_title = "Historial de Compras | CRM Ventas";
require_once '../config/db.php';

// Capturamos el ID del cliente desde la URL
$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0;

if ($id_cliente <= 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Error: ID de cliente no válido.</div></div>";
    exit();
}

// 1. Obtener los datos base del cliente (Estructura limpia sin apellidos)
$sql_cliente = "SELECT * FROM clientes WHERE id_cliente = :id_cliente LIMIT 1";
$stmt_cli = $pdo->prepare($sql_cliente);
$stmt_cli->execute([':id_cliente' => $id_cliente]);
$cliente = $stmt_cli->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Error: El cliente solicitado no existe en el sistema.</div></div>";
    exit();
}

// 2. KPIs individuales del Cliente (Equipos comprados e Inversión acumulada)
$sql_kpis = "SELECT COUNT(id_venta) AS total_equipos,
                    IFNULL(SUM(precio_pactado_neto * cantidad), 0) AS inversion_total,
                    MAX(fecha_compra) AS ultima_fecha
             FROM ventas_historial 
             WHERE id_cliente = :id_cliente";
$stmt_kpis = $pdo->prepare($sql_kpis);
$stmt_kpis->execute([':id_cliente' => $id_cliente]);
$kpis = $stmt_kpis->fetch(PDO::FETCH_ASSOC);

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-clock-history"></i> Historial de Cliente</h1>
        <p class="text-muted small mb-0">Expediente comercial corporativo de compras y cotizaciones.</p>
        <h4 class="fw-bold text-dark mt-2 mb-0"><?= htmlspecialchars($cliente['nombre_cliente']) ?></h4>
        <span class="badge text-uppercase text-muted border bg-white mt-1"><?= htmlspecialchars($cliente['tipo_cliente'] ?? 'Publico General') ?></span>
    </div>
    <div class="col-md-6 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 120px;">
                <span class="d-block fw-bold fs-5 text-danger"><?= $kpis['total_equipos'] ?></span>
                <small class="text-muted" style="font-size: 0.65rem; font-weight: 700;">EQUIPOS ADQUIRIDOS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 160px;">
                <span class="d-block fw-bold fs-5 text-success">$<?= number_format($kpis['inversion_total'], 2, '.', ',') ?></span>
                <small class="text-muted" style="font-size: 0.65rem; font-weight: 700;">INVERSIÓN TOTAL</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 p-4 shadow-sm bg-white rounded border-top border-danger border-3">
    <div class="row g-3 small">
        <div class="col-12 col-md-3">
            <strong><i class="bi bi-telephone text-muted me-1"></i> Teléfono:</strong><br>
            <span class="text-secondary"><?= htmlspecialchars($cliente['telefono'] ?: 'Sin registrar') ?></span>
        </div>
        <div class="col-12 col-md-3">
            <strong><i class="bi bi-envelope text-muted me-1"></i> Correo Electrónico:</strong><br>
            <span class="text-secondary"><?= htmlspecialchars($cliente['correo'] ?: 'Sin registrar') ?></span>
        </div>
        <div class="col-12 col-md-3">
            <strong><i class="bi bi-card-text text-muted me-1"></i> RFC Receptor:</strong><br>
            <span class="text-danger fw-bold"><?= htmlspecialchars($cliente['rfc_receptor'] ?: 'XAXX010101000') ?></span>
        </div>
        <div class="col-12 col-md-3">
            <strong><i class="bi bi-geo-alt text-muted me-1"></i> Ubicación Fija:</strong><br>
            <span class="text-secondary"><?= htmlspecialchars($cliente['ubicacion'] . ', ' . ($cliente['pais'] ?? 'México')) ?></span>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-journal-text text-danger me-2"></i> Registro Histórico de Compras</h5>
    
    <div class="table-responsive">
        <table id="tablaHistorialCompras" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>Fecha Compra</th>
                    <th>Modelo de Máquina</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-end">P. Unitario Neto</th>
                    <th class="text-end">Importe Neto</th>
                    <th>Observaciones del Cierre</th>
                    <th class="text-center" style="width: 100px;">Documento</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Consultamos el historial unido con la tabla de maquinaria
                $sql_list = "SELECT vh.*, m.modelo AS modelo_nombre 
                             FROM ventas_historial vh
                             LEFT JOIN maquinaria m ON vh.id_maquina = m.id_maquina
                             WHERE vh.id_cliente = :id_cliente
                             ORDER BY vh.fecha_compra DESC, vh.id_venta DESC";
                
                $stmt_list = $pdo->prepare($sql_list);
                $stmt_list->execute([':id_cliente' => $id_cliente]);
                
                while ($item = $stmt_list->fetch(PDO::FETCH_ASSOC)):
                    $subtotal_neto = $item['precio_pactado_neto'] * $item['cantidad'];
                ?>
                <tr>
                    <td class="small fw-semibold text-secondary"><?= date('d/m/Y', strtotime($item['fecha_compra'])) ?></td>
                    <td><span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($item['modelo_nombre']) ?></span></td>
                    <td class="text-center fw-bold"><?= $item['cantidad'] ?></td>
                    <td class="text-end fw-semibold">$<?= number_format($item['precio_pactado_neto'], 2, '.', ',') ?></td>
                    <td class="text-end fw-bold text-dark">$<?= number_format($subtotal_neto, 2, '.', ',') ?></td>
                    <td class="small text-muted" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($item['observaciones_venta']) ?>">
                        <?= htmlspecialchars($item['observaciones_venta']) ?>
                    </td>
                    <td class="text-center">
                        <?php if ($item['id_cotizacion_origen'] > 0): ?>
                            <a href="generar_pdf_cotizacion.php?id_cotizacion=<?= $item['id_cotizacion_origen'] ?>" class="btn btn-sm btn-outline-danger border-0" title="Ver Cotización Original">
                                <i class="bi bi-file-pdf fs-5"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-muted small"><em>Manual</em></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-start border-top pt-3 mt-4">
        <a href="clientes.php" class="btn btn-secondary py-2 px-4 fw-bold small" style="border-radius: 8px;">
            <i class="bi bi-arrow-left me-1"></i> Regresar al Catálogo
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#tablaHistorialCompras').DataTable({
        "language": { "emptyTable": "Este cliente no cuenta con adquisiciones registradas aún.", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "0 registros", "infoFiltered": "(filtrado de _MAX_)", "zeroRecords": "Sin coincidencias", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtip', 
        "pageLength": 10, 
        "responsive": true,
        "ordering": false 
    });
});
</script>