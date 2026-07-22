<?php
/**
 * ARCHIVO: Almacen/actions/abrir_modal_fase.php
 * DESCRIPCIÓN: Renderiza dinámicamente el formulario interno del modal según la fase actual de la máquina.
 * CONTROL LOGÍSTICO: Secuencia lineal con redirección comercial de Comodato a Pagada.
 * @project Almacén Técnico DEMEX
 * @version 6.1 - Sincronización con Subtablas Child Rows
 * @author Israel Fernández Carrera
 */

require_once '../../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0 && isset($_REQUEST['id'])) {
    $id = intval($_REQUEST['id']);
}

if ($id <= 0) {
    echo '<div class="alert alert-danger m-3 small">ID de equipo inválido o vacío.</div>';
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM almacen_inventario WHERE id = ?");
$stmt->execute([$id]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    echo '<div class="alert alert-danger m-3 small">No se encontró el equipo seleccionado.</div>';
    exit();
}

$estatus_actual = $equipo['estatus'];

$siguiente_estatus = '';
$label_fecha = '';
$nombre_campo_fecha = '';

switch ($estatus_actual) {
    case 'SIN REVISAR':
        $siguiente_estatus = 'EN REVISIÓN ALMACÉN';
        $label_fecha = 'Fecha en que Almacén abre caja para primeros ajustes';
        $nombre_campo_fecha = 'fecha_inicio_ajustes_almacen';
        break;
    case 'EN REVISIÓN ALMACÉN':
        $siguiente_estatus = 'DISPONIBLE PARA SOPORTE';
        $label_fecha = 'Fecha en que Almacén termina y deja la máquina disponible para Soporte';
        $nombre_campo_fecha = 'fecha_disponible_soporte';
        break;
    case 'DISPONIBLE PARA SOPORTE':
        $siguiente_estatus = 'EN REVISIÓN SOPORTE';
        $label_fecha = 'Fecha en que Soporte toma la máquina y comienza su proceso';
        $nombre_campo_fecha = 'fecha_entrega_soporte';
        break;
    case 'EN REVISIÓN SOPORTE':
        $siguiente_estatus = 'REINGRESO A ALMACÉN';
        $label_fecha = 'Fecha en que Soporte termina y regresa la máquina a Almacén (Embalaje)';
        $nombre_campo_fecha = 'fecha_reingreso_almacen';
        break;
    case 'REINGRESO A ALMACÉN':
        $siguiente_estatus = 'DISPONIBLE PARA VENTA';
        $label_fecha = 'Fecha de reingreso y disponibilidad para asignación comercial';
        $nombre_campo_fecha = 'fecha_reingreso_almacen';
        break;
    case 'DISPONIBLE PARA VENTA':
        $siguiente_estatus = 'PAGADA / POR ENTREGAR';
        $label_fecha = 'Fecha de facturación, liquidación o apartado comercial';
        $nombre_campo_fecha = 'fecha_entrega_cliente';
        break;
    case 'COMODATO':
        $siguiente_estatus = 'PAGADA / POR ENTREGAR';
        $label_fecha = 'Fecha en que el Comodato se convierte en Venta o Cierre Definitivo';
        $nombre_campo_fecha = 'fecha_entrega_cliente';
        break;
    case 'PAGADA / POR ENTREGAR':
    case 'CAMBIO':
        $siguiente_estatus = '';
        break;
    case 'ENTREGADA':
        $siguiente_estatus = '';
        break;
}

$serie_mostrar = !empty($equipo['no_serie']) ? htmlspecialchars($equipo['no_serie']) : 'S/N PENDIENTE';
?>

<form id="formCambiarFase" novalidate>
    <div class="modal-body p-4">
        
        <div class="bg-light p-3 mb-3 rounded-4 border-start border-danger border-4 small shadow-sm">
            <span class="d-block text-secondary text-uppercase fw-bold" style="font-size: 10px;">Equipo Seleccionado</span>
            <div class="fw-bold text-dark fs-6 mt-1">Modelo: <?= htmlspecialchars($equipo['modelo']) ?></div>
            <div class="text-muted" style="font-size: 11px;">
                Serie: <code class="text-danger fw-bold"><?= $serie_mostrar ?></code> | Contenedor: <?= htmlspecialchars($equipo['contenedor']) ?> | Estatus: <span class="badge bg-secondary p-1" style="font-size: 9px"><?= $estatus_actual ?></span>
            </div>
        </div>

        <?php if (empty($siguiente_estatus) && ($estatus_actual === 'PAGADA / POR ENTREGAR' || $estatus_actual === 'CAMBIO')): ?>
            <div class="alert alert-info border-0 rounded-4 text-center p-3 mb-0" style="background-color: #f0f7ff;">
                <span class="fw-bold text-dark small">Esta máquina requiere asignación de cliente y activación de póliza de garantía. Cierra este cuadro y utiliza el botón verde de "Entregar".</span>
            </div>
        <?php elseif (empty($siguiente_estatus)): ?>
            <div class="alert alert-success border-0 rounded-4 text-center p-3 mb-0" style="background-color: #f4fbf7;">
                <span class="fw-bold text-dark small">Esta máquina ya se encuentra en su fase final (ENTREGADA). No requiere más firmas logísticas.</span>
            </div>
        <?php else: ?>
            
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="campo_fecha" value="<?= $nombre_campo_fecha ?>">

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Nuevo Estatus del Equipo</label>
                <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                    <select class="form-select border-0 bg-transparent fw-bold text-dark p-1" name="nuevo_estatus" id="nuevo_estatus" style="font-size: 14px;">
                        <?php if ($estatus_actual === 'REINGRESO A ALMACÉN'): ?>
                            <option value="DISPONIBLE PARA VENTA" selected>Pasar a: DISPONIBLE PARA VENTA</option>
                        <?php elseif ($estatus_actual === 'DISPONIBLE PARA VENTA'): ?>
                            <option value="PAGADA / POR ENTREGAR" selected>Pasar a: PAGADA / POR ENTREGAR</option>
                            <option value="COMODATO">Pasar a: COMODATO</option>
                            <option value="CAMBIO">Pasar a: CAMBIO</option>
                        <?php else: ?>
                            <option value="<?= $siguiente_estatus ?>" selected>Pasar a: <?= $siguiente_estatus ?></option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="mb-1">
                <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 11px;"><?= $label_fecha ?></label>
                <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                    <input type="date" class="form-control bg-transparent border-0 fw-semibold text-muted" name="fecha_fase" id="fecha_fase" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

        <?php endif; ?>

    </div>
    
    <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2 justify-content-end">
        <button type="button" class="btn btn-light btn-sm rounded-pill px-4 py-2 fw-bold text-secondary border shadow-sm" data-bs-dismiss="modal" style="font-size: 13px;">Regresar</button>
        <?php if (!empty($siguiente_estatus)): ?>
            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 py-2 fw-bold shadow-sm" style="font-size: 13px; background-color: #dc3545;">
                Firmar Fase
            </button>
        <?php endif; ?>
    </div>
</form>

<script>
document.getElementById('formCambiarFase')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    Swal.fire({ 
        title: 'Actualizando fase...', 
        text: 'Guardando marca de tiempo logística.', 
        allowOutsideClick: false, 
        didOpen: () => { Swal.showLoading(); } 
    });

    fetch('actions/actualizar_fase.php', { method: 'POST', body: formData })
    .then(response => {
        if (!response.ok) throw new Error('Ocurrió un error en el servidor local.');
        return response.json();
    })
    .then(data => {
        Swal.close();
        if (data.success) {
            // 1. Ocultar modal
            const modalEl = document.getElementById('modalActualizarFase');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            Swal.fire({ icon: 'success', title: '¡Fase Actualizada!', text: data.message, timer: 1500, showConfirmButton: false });

            // 2. Refrescar DataTable principal
            if (typeof table !== 'undefined') {
                table.ajax.reload(null, false);
            }

            // 3. Refrescar filas hijas desplegadas si están abiertas
            $('#tablaLotes tr.shown').each(function() {
                var row = table.row(this);
                if (row.child.isShown()) {
                    $.ajax({
                        url: 'actions/obtener_desglose_lote.php',
                        method: 'GET',
                        data: { id_lote: row.data().id_lote },
                        success: function (html) {
                            row.child(html).show();
                        }
                    });
                }
            });

            if (typeof actualizarKPIs === 'function') {
                actualizarKPIs();
            }

        } else {
            Swal.fire({ icon: 'error', title: 'Falla al Actualizar', text: data.message, confirmButtonColor: '#dc3545' });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({ 
            icon: 'error', 
            title: 'Respuesta Inesperada', 
            text: error.message, 
            confirmButtonColor: '#dc3545' 
        });
    });
});
</script>