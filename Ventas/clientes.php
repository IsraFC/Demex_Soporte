<?php
/**
 * ARCHIVO: Ventas/clientes.php
 * DESCRIPCIÓN: Panel de Control y Dashboard Avanzado de Clientes CRM.
 * Gestiona el historial unificado de ventas, indicadores CLV y visor de flotas comerciales.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.3 (Dashboard Comercial Unificado - Ordenamiento y Filtro de Gran Volumen)
 */

$page_title = "Catálogo Histórico de Clientes | CRM Ventas";
require_once '../config/db.php';

/**
 * KPIs - INDICADORES CLAVE DE RENDIMIENTO (PHP Base Unificado)
 */
// 1. Total de clientes únicos (Ventas + Soporte combinados en la misma tabla)
$total_clientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();

// 2. Valor de Vida del Cliente (CLV Total) - Sumatoria total neta de ventas históricas
$clv_total = $pdo->query("SELECT IFNULL(SUM(precio_pactado_neto * cantidad), 0) FROM ventas_historial")->fetchColumn();

// 3. Clientes Frecuentes (Clientes con 2 o más registros de compras)
$clientes_frecuentes = $pdo->query("SELECT COUNT(*) FROM (SELECT id_cliente FROM ventas_historial GROUP BY id_cliente HAVING COUNT(id_venta) >= 6) AS frecuentes")->fetchColumn();

// 4. Clientes en Riesgo (Más de 6 meses sin comprar, basándose en la fecha actual del sistema: 2026)
$clientes_riesgo = $pdo->query("SELECT COUNT(DISTINCT id_cliente) FROM clientes WHERE id_cliente NOT IN (SELECT DISTINCT id_cliente FROM ventas_historial WHERE fecha_compra >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH))")->fetchColumn();

$maquinas_reales = ['DEMEX 313', 'DEMEX 313T', 'DEMEX 513', 'DEMEX 613', 'DEMEX 1020', 'DEMEX 125', 'SPICE MT15', 'SPICE MV89'];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-people-fill"></i> Catálogo de Clientes</h1>
        <p class="text-muted small">Base de datos unificada de clientes recurrentes e historiales de compra.</p>
    </div>
    <div class="col-md-7 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-secondary border-4 text-center" style="min-width: 110px;">
                <span class="d-block fw-bold fs-5 text-dark"><?= $total_clientes ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">TOTAL CLIENTES</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-success border-4 text-center" style="min-width: 140px;">
                <span class="d-block fw-bold fs-5 text-success">$<?= number_format($clv_total, 0, '.', ',') ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">VALOR COMERCIAL (CLV)</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-primary border-4 text-center" style="min-width: 110px;">
                <span class="d-block fw-bold fs-5 text-primary"><?= $clientes_frecuentes ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">COMPRADOR FREC.</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 110px;">
                <span class="d-block fw-bold fs-5 text-warning"><?= $clientes_riesgo ?></span>
                <small class="text-muted" style="font-size: 0.6rem; font-weight: 700;">INACTIVOS > 6M</small>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-0 align-items-center px-3 justify-content-between">
        <div class="col-auto" style="width: 25%;">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Buscar Cliente...">
            </div>
        </div>
        <div class="col-auto">
            <select id="filterTipo" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 180px;">
                <option value="">Todos los Perfiles</option>
                <option value="Publico General">Público General</option>
                <option value="Distribuidor">Distribuidor</option>
            </select>
        </div>
        <div class="col-auto">
            <select id="filterMaquina" class="form-select form-select-sm border-0 bg-light fw-bold text-muted shadow-sm px-3" style="min-width: 200px;">
                <option value="">Máquina Comprada</option>
                <?php foreach ($maquinas_reales as $maquina): ?>
                    <option value="<?= htmlspecialchars($maquina) ?>"><?= htmlspecialchars($maquina) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-auto">
            <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                <input class="form-check-input" type="checkbox" id="btnFiltrarFrecuentes" style="cursor:pointer;">
                <label class="form-check-label small fw-bold text-muted" style="cursor:pointer;" for="btnFiltrarFrecuentes">Mostrar Clientes Frecuentes</label>
            </div>
        </div>

        <div class="col-auto">
            <a href="importar_clientes.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold shadow-sm py-2">
                <i class="bi bi-file-earmark-excel me-1"></i> Carga CSV
            </a>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaClientes" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr class="text-uppercase small fw-bold text-muted">
                    <th>Cliente</th>
                    <th>Contacto Directo</th>
                    <th>Ubicación</th>
                    <th class="text-center">Última Compra</th>
                    <th class="text-center" style="width: 100px;">Total Equipos</th>
                    <th class="text-end">Inversión Total</th>
                    <th style="display:none;">Equipos Flota</th>
                    <th class="text-center" style="width: 130px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // CONSULTA CON LOGICA DE ORDENAMIENTO: Primero Prospectos a Clientes, luego los de Almacén/Soporte
                $sql = "SELECT c.*, 
                               hist.ultima_fecha_compra,
                               COALESCE(hist.equipos_ventas, 0) AS total_equipos,
                               COALESCE(hist.inversion_acumulada, 0.00) AS inversion_acumulada,
                               hist.maquinas_ventas AS maquinas_compradas
                        FROM clientes c
                        
                        -- Subconsulta A: Extrae exclusivamente el histórico de ventas comerciales
                        LEFT JOIN (
                            SELECT id_cliente,
                                   MAX(fecha_compra) AS ultima_fecha_compra,
                                   COUNT(id_venta) AS equipos_ventas,
                                   SUM(precio_pactado_neto * cantidad) AS inversion_acumulada,
                                   GROUP_CONCAT(DISTINCT m.modelo SEPARATOR ' | ') AS maquinas_ventas
                            FROM ventas_historial vh
                            LEFT JOIN maquinaria m ON vh.id_maquina = m.id_maquina
                            GROUP BY id_cliente
                        ) hist ON c.id_cliente = hist.id_cliente
                        
                        ORDER BY 
                            CASE WHEN c.id_prospecto_origen IS NOT NULL THEN 1 ELSE 2 END ASC,
                            hist.ultima_fecha_compra DESC, 
                            c.id_cliente DESC";
                
                $stmt = $pdo->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $fecha_compra_formato = !empty($row['ultima_fecha_compra']) ? date('d/m/Y', strtotime($row['ultima_fecha_compra'])) : '<em>Ninguna</em>';
                ?>
                <tr class="row-cliente-item" data-equipos-count="<?= $row['total_equipos'] ?>">
                    <td>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($row['nombre_cliente'] . ' ' . ($row['apellidos_cliente'] ?? '')) ?></div>
                        <span class="badge mt-1 text-uppercase text-muted border bg-light" style="font-size: 0.65rem; padding: 0.2rem 0.4rem; border-radius: 4px;"><?= htmlspecialchars($row['tipo_cliente'] ?? 'Publico General') ?></span>
                    </td>
                    <td>
                        <?php if(!empty($row['correo'])): ?>
                            <div class="small text-dark mb-1"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($row['correo']) ?></div>
                        <?php endif; ?>
                        <?php if(!empty($row['telefono'])): ?>
                            <div>
                                <a href="https://wa.me/52<?= $row['telefono'] ?>" target="_blank" class="text-success text-decoration-none fw-semibold small d-inline-flex align-items-center">
                                    <i class="bi bi-whatsapp me-1 fs-6"></i><?= htmlspecialchars($row['telefono']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="small text-secondary">
                        <i class="bi bi-geo-alt-fill text-muted me-1"></i><?= htmlspecialchars(!empty($row['ubicacion']) ? $row['ubicacion'] : 'Sin registrar') ?>
                    </td>
                    <td class="text-center small fw-semibold text-secondary"><?= $fecha_compra_formato ?></td>
                    <td class="text-center">
                        <span class="badge bg-danger rounded-circle fs-6 px-2.5 py-1" style="font-weight: 600; min-width: 32px;">
                            <?= $row['total_equipos'] ?>
                        </span>
                    </td>
                    <td class="text-end fw-bold text-dark">$<?= number_format($row['inversion_acumulada'], 2, '.', ',') ?></td>
                    
                    <td style="display:none;"><?= htmlspecialchars($row['maquinas_compradas'] ?? '') ?></td>
                    
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-dark border-0" onclick="verHistorialCliente(<?= $row['id_cliente'] ?>)" title="Ver Historial de Flota e Inversiones">
                                <i class="bi bi-clock-history fs-5"></i>
                            </button>
                            <a href="cotizaciones.php?id_prospecto=0&cliente_recompra=<?= $row['id_cliente'] ?>" class="btn btn-outline-danger border-0" title="Generar Nueva Cotización (Recompra)">
                                <i class="bi bi-file-earmark-plus-fill fs-5"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalHistorialCliente" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 12px;">
            <div class="modal-header bg-dark text-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-journal-text me-2"></i> Historial Cronológico de Adquisiciones</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="cuerpoModalHistorial">
                <div class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#tablaClientes').DataTable({
        "language": { "emptyTable": "No hay datos", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "0 registros", "infoFiltered": "(filtrado de _MAX_)", "zeroRecords": "Sin coincidencias", "paginate": { "next": "Sig.", "previous": "Ant." } },
        "dom": 'rtip', 
        "pageLength": 10, 
        "responsive": true,
        "ordering": false // Desactivamos el ordenamiento por clicks para preservar la prioridad fija de SQL
    });

    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    $('#filterTipo').on('change', function() { table.column(0).search(this.value).draw(); });
    $('#filterMaquina').on('change', function() { table.column(6).search(this.value).draw(); });

    // Algoritmo de filtrado personalizado para Clientes de Gran Volumen (6 o más compras)
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== "tablaClientes") return true;

        var row = $(table.row(dataIndex).node());
        var cantidadEquipos = parseInt(row.attr('data-equipos-count')) || 0;
        
        // Condición estricta: Si el switch está activo, solo deja pasar a los que tienen 6 o más máquinas
        var cumpleGranVolumen = !$('#btnFiltrarFrecuentes').is(':checked') || cantidadEquipos >= 6;
        
        return cumpleGranVolumen;
    });

    // Escuchador reactivo para redibujar la tabla en caliente al accionar el Switch
    $('#btnFiltrarFrecuentes').on('change', function() {
        table.draw();
    });
});

function verHistorialCliente(idCliente) {
    $('#cuerpoModalHistorial').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-danger" role="status"><span class="visually-hidden">Cargando...</span></div>
            <p class="text-muted small mt-2">Consultando expediente comercial en base de datos...</p>
        </div>
    `);
    
    $('#modalHistorialCliente').appendTo("body").modal('show');
    
    $.ajax({
        url: '../actions/obtener_historial_compras_cliente.php',
        method: 'GET',
        data: { id_cliente: idCliente },
        success: function(response) {
            $('#cuerpoModalHistorial').html(response);
        },
        error: function() {
            $('#cuerpoModalHistorial').html('<div class="alert alert-danger m-0"><i class="bi bi-exclamation-octagon-fill me-2"></i> Error al conectar con la base de datos de auditoría comercial.</div>');
        }
    });
}
</script>