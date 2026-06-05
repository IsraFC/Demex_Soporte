<?php
/**
 * ARCHIVO: leads_crm.php
 * DESCRIPCIÓN: Panel de Control de Leads CRM con Motor de Búsqueda Asíncrono.
 * Mantiene la consistencia de interfaz premium, KPIs, filtros y DataTables de Isra.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 3.3 (Modelos ENUM Sincronizados)
 */

$page_title = "Panel de Seguimiento | CRM Ventas";
require_once '../config/db.php';

/**
 * CONSULTAS DE INDICADORES (KPIs):
 * Alimentan las tarjetas de estatus superiores imitando la simetría de Soporte.
 */
$total_leads = $pdo->query("SELECT COUNT(*) FROM prospectos")->fetchColumn();
$pendientes_leads = $pdo->query("SELECT COUNT(*) FROM prospectos WHERE status_operativo = 'Consulta'")->fetchColumn();

// Lógica de inactividad comercial (Semaforización de Leads Congelados > 5 días)
$criticos_leads = $pdo->query("SELECT COUNT(*) FROM prospectos 
                               WHERE status_operativo = 'Consulta' 
                               AND DATEDIFF(CURDATE(), fecha_ultimo_contacto) > 5")->fetchColumn();

// Catálogo estricto de los 8 modelos de máquinas reales (Estructura ENUM de la BD)
$maquinas_reales = [
    'DEMEX 313',
    'DEMEX 313T',
    'DEMEX 513',
    'DEMEX 613',
    'DEMEX 1020',
    'DEMEX 125',
    'SPICE MT15',
    'SPICE MV89'
];

include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-5">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-funnel"></i> Control de Prospectos a Clientes</h1>
        <p class="text-muted small">Prospectos capturados desde el formulario público de la página web.</p>
    </div>
    <div class="col-md-7 text-md-end">
        <div class="d-inline-flex gap-2">
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 90px;">
                <span class="d-block fw-bold fs-5"><?= $total_leads ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">PROSPECTOS</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-warning border-4 text-center" style="min-width: 90px;">
                <span id="kpi_pendientes" class="d-block fw-bold fs-5 text-warning"><?= $pendientes_leads ?></span>
                <small class="text-muted" style="font-size: 0.6rem;">PENDIENTES</small>
            </div>
            <div class="p-2 bg-white shadow-sm rounded border-start border-danger border-4 text-center" style="min-width: 90px;">
                <span id="kpi_criticos" class="d-block fw-bold fs-5 <?= ($criticos_leads > 0) ? 'text-danger ms-1-animate' : 'text-muted' ?>">
                    <?= $criticos_leads ?>
                </span>
                <small class="text-muted" style="font-size: 0.6rem;">URGENTES</small>
            </div>
        </div>
    </div>
</div>

<?php if ($criticos_leads > 0): ?>
    <div class="alert alert-danger shadow-sm border-0 border-start border-4 border-danger bg-white d-flex align-items-center justify-content-between animate__animated animate__headShake" role="alert" id="alertaCriticos">
        <div>
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
            <span class="fw-bold">Atención:</span> Hay <strong><?= $criticos_leads ?></strong> prospectos comerciales con más de 5 días de inactividad.
        </div>
        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold" id="btnFiltrarCriticos">
            <i class="bi bi-funnel-fill me-1"></i> Ver Urgentes
        </button>
    </div>
<?php endif; ?>

<div class="card-main mb-4 py-3 shadow-sm border-top border-4 border-danger bg-white rounded">
    <div class="row g-3 align-items-center px-3 mb-2">
        <div class="col-md-4">
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text border-0 bg-transparent"><i class="bi bi-search text-danger"></i></span>
                <input type="text" id="customSearch" class="form-control bg-transparent border-0" placeholder="Buscar Prospecto o Correo...">
            </div>
        </div>
        <div class="col-md-4">
            
            <select id="filterCanal" class="form-select border-0 bg-light fw-bold text-muted shadow-sm">
                <option value="">Todos los Canales de Origen</option>
                <option value="Página Web">Página Web</option>
                <option value="Facebook">Facebook</option>
                <option value="YouTube">YouTube</option>
                <option value="WhatsApp">WhatsApp</option>
                <option value="Recomendación">Recomendación</option>
            </select>
        </div>
        <div class="col-md-4">
            <select id="filterEquipo" class="form-select border-0 bg-light fw-bold text-muted shadow-sm">
                <option value="">Todos los Equipos</option>
                <?php foreach ($maquinas_reales as $maquina): ?>
                    <option value="<?= htmlspecialchars($maquina) ?>"><?= htmlspecialchars($maquina) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="card-main shadow-lg p-4 bg-white rounded">
    <div class="table-responsive">
        <table id="tablaLeads" class="table table-hover align-middle w-100">
            <thead class="table-light text-uppercase small fw-bold">
                <tr>
                    <th>Fecha Registro</th>
                    <th>Cliente / Canal</th>
                    <th>Contacto Directo</th>
                    <th>Ubicación</th>
                    <th>Equipo de Interés</th>
                    <th>Semáforo</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT f.*, p.id_prospecto, p.status_operativo, p.fecha_ultimo_contacto 
                        FROM formulario f
                        INNER JOIN prospectos p ON f.id_formulario = p.id_formulario
                        WHERE p.status_operativo = 'Consulta'
                        ORDER BY f.fecha_registro DESC";
                
                $stmt = $pdo->query($sql);
                $leads = $stmt->fetchAll();

                if (count($leads) > 0) {
                    foreach ($leads as $lead) {
                        
                        $fechaUltimo = new DateTime($lead['fecha_ultimo_contacto']);
                        $fechaActual = new DateTime();
                        $diferencia = $fechaActual->diff($fechaUltimo);
                        $diasInactivo = $diferencia->days;

                        if ($diasInactivo <= 2) {
                            $semaforoBadge = '<span class="badge" style="background-color: #E8F5E9; color: #2E7D32; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Al día</span>';
                            $rowClass = '';
                        } elseif ($diasInactivo <= 5) {
                            $semaforoBadge = '<span class="badge" style="background-color: #FFFDE7; color: #F57F17; font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Atención</span>';
                            $rowClass = 'table-warning-sutil';
                        } else {
                            $semaforoBadge = '<span class="badge bg-danger animate__animated animate__headShake" style="font-weight: 600; border-radius: 8px; padding: 0.4rem 0.6rem;"><i class="bi bi-fire me-1"></i> Urgente</span>';
                            $rowClass = 'table-danger-sutil';
                        }
                        ?>
                        <!-- Tu fila ya tiene perfectamente inyectadas las clases de control y atributos data -->
                        <tr class="<?= $rowClass ?>" data-origen="<?= htmlspecialchars($lead['canal_origen']) ?>" data-equipo="<?= htmlspecialchars($lead['maquina_interes']) ?>" data-urgente="<?= ($diasInactivo > 5) ? '1' : '0' ?>">
                            <td class="small fw-semibold text-secondary"><?= date('d/m/Y g:i A', strtotime($lead['fecha_registro'])) ?></td>
                            <td>
                                <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($lead['nombre'] . ' ' . $lead['apellidos']) ?></div>
                                <span class="badge mt-1 text-uppercase text-muted border bg-white" style="font-size: 0.65rem; letter-spacing: 0.5px; font-weight: 500; padding: 0.2rem 0.4rem; border-radius: 4px;"><?= htmlspecialchars($lead['canal_origen']) ?></span>
                            </td>
                            <td>
                                <div class="small text-dark"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($lead['correo']) ?></div>
                                <div class="small mt-1">
                                    <a href="https://wa.me/52<?= $lead['telefono'] ?>" target="_blank" class="text-success text-decoration-none fw-semibold d-inline-flex align-items-center">
                                        <i class="bi bi-whatsapp me-1 fs-6"></i><?= htmlspecialchars($lead['telefono']) ?>
                                    </a>
                                </div>
                            </td>
                            <td class="small text-secondary">
                                <i class="bi bi-geo-alt-fill text-muted me-1"></i><?= htmlspecialchars($lead['estado_region'] . ', ' . $lead['pais']) ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border py-1.5 px-2.5 fw-semibold" style="border-radius: 6px; font-size: 0.75rem;">
                                    <?= htmlspecialchars($lead['maquina_interes']) ?>
                                </span>
                            </td>
                            <td><?= $semaforoBadge ?></td>
                            <td class="text-center">
                                <a href="cotizaciones.php?id_prospecto=<?= $lead['id_prospecto'] ?>" class="btn btn-demex py-1.5 px-3 shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.8rem; border-radius: 8px;">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> Cotizar
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#tablaLeads')) {
        $('#tablaLeads').DataTable().destroy();
    }
    
    var table = $('#tablaLeads').DataTable({
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sInfo":           "Mostrando _START_ al _END_ de _TOTAL_ prospectos",
            "sInfoEmpty":      "Mostrando 0 al 0 de 0",
            "sInfoFiltered":   "(filtrado de _MAX_ registros)",
            "sSearch":         "Buscar:",
            "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Sig", "sPrevious": "Ant" }
        },
        "dom": 'rtip', 
        "pageLength": 10,
        "order": [[0, "desc"]],
        "responsive": true
    });

    $('#customSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // MOTOR DE FILTRADO AVANZADO DE DATATABLES
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var row = $(table.row(dataIndex).node());
        // CORREGIDO: Apuntamos al ID real '#filterCanal' que lee tu ENUM
        var filterCanal  = $('#filterCanal').val();
        var filterEquipo = $('#filterEquipo').val();
        var soloUrgentes = $('#btnFiltrarCriticos').hasClass('btn-dark') ? '1' : '';

        // Comparamos contra los atributos data- de las filas correspondientes
        var matchOrigen  = filterCanal === "" || row.attr('data-origen') === filterCanal;
        var matchEquipo  = filterEquipo === "" || row.attr('data-equipo') === filterEquipo;
        var matchUrgente = soloUrgentes === "" || row.attr('data-urgente') === soloUrgentes;

        return matchOrigen && matchEquipo && matchUrgente;
    });

    // CORREGIDO: Listener apuntando a '#filterCanal' para redibujar la tabla al vuelo
    $('#filterCanal, #filterEquipo').on('change', function() {
        table.draw();
    });

    $('#btnFiltrarCriticos').on('click', function() {
        var btn = $(this);
        btn.toggleClass('btn-danger btn-dark');
        var activo = btn.hasClass('btn-dark');
        btn.html(activo ? '<i class="bi bi-arrow-counterclockwise me-1"></i> Quitar Filtro' : '<i class="bi bi-funnel-fill me-1"></i> Ver Urgentes');
        table.draw();
    });
});
</script>