<?php
/**
 * ARCHIVO: Almacen/actions/abrir_modal_entrega.php
 * DESCRIPCIÓN: Formulario dinámico de asignación con datalist, captura de serie física y cliente.
 * @project Almacén Técnico DEMEX
 * @version 6.1 - Captura de Serie Física de Lote
 * @author Israel Fernández Carrera
 */

require_once '../../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger m-3 small">ID de maquinaria inválido o vacío.</div>';
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM almacen_inventario WHERE id = ?");
$stmt->execute([$id]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    echo '<div class="alert alert-danger m-3 small">No se encontró el registro de stock seleccionado.</div>';
    exit();
}

try {
    $clientes = $pdo->query("SELECT id_cliente, nombre_cliente FROM clientes ORDER BY nombre_cliente ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger m-3 small">Error al cargar catálogo de clientes: ' . $e->getMessage() . '</div>';
    exit();
}

$fecha_hoy = date('Y-m-d');
?>

<form id="formProcesarEntrega" novalidate>
    <div class="modal-body p-4">
        
        <div class="bg-light p-3 mb-3 rounded-4 border-start border-success border-4 small shadow-sm">
            <span class="d-block text-secondary text-uppercase fw-bold" style="font-size: 10px;">Maquinaria Lista para Despliegue</span>
            <div class="fw-bold text-dark fs-6 mt-1">Modelo: <?= htmlspecialchars($equipo['modelo']) ?></div>
            <div class="text-muted" style="font-size: 11px;">
                Contenedor: <code class="text-dark fw-bold"><?= htmlspecialchars($equipo['contenedor']) ?></code> | Estatus: <span class="badge bg-dark"><?= htmlspecialchars($equipo['estatus']) ?></span>
            </div>
        </div>

        <input type="hidden" name="id_almacen" value="<?= $id ?>">
        <input type="hidden" name="modelo" value="<?= htmlspecialchars($equipo['modelo']) ?>">
        <input type="hidden" name="fecha_termino" id="entrega_fecha_termino">

        <!-- Campo obligatorio para la Serie Física -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-danger text-uppercase" style="font-size: 11px;">Número de Serie Físico de la Máquina *</label>
            <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                <span class="input-group-text border-0 bg-transparent text-danger"><i class="bi bi-barcode"></i></span>
                <input type="text" class="form-control bg-transparent border-0 fw-bold text-uppercase text-danger p-1" 
                       name="no_serie" id="entrega_no_serie" 
                       value="<?= htmlspecialchars($equipo['no_serie'] ?? '') ?>" 
                       placeholder="Ingresa la serie..." required autocomplete="off" style="font-size: 14px;">
            </div>
        </div>

        <div class="form-check form-switch mb-3 bg-light p-2 rounded-pill ps-5 border shadow-sm">
            <input class="form-check-input" type="checkbox" role="switch" id="switchClienteNuevo" name="es_cliente_nuevo" value="1">
            <label class="form-check-label small fw-bold text-danger text-uppercase" style="font-size: 11px;" for="switchClienteNuevo">¿Es un Cliente Nuevo?</label>
        </div>

        <div id="wrapperClienteExistente">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Buscar o Seleccionar Cliente</label>
                <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm mb-2">
                    <span class="input-group-text border-0 bg-transparent text-muted"><i class="bi bi-person-search"></i></span>
                    <input type="text" class="form-control bg-transparent border-0 fw-bold text-dark p-1" id="buscador_cliente_datalist" placeholder="Da clic para ver la lista o escribe para buscar..." list="lista_clientes_maestra" style="font-size: 14px;">
                    <input type="hidden" name="id_cliente" id="entrega_id_cliente">
                </div>
                
                <datalist id="lista_clientes_maestra">
                    <?php foreach ($clientes as $cliente): ?>
                        <option data-id="<?= $cliente['id_cliente'] ?>" value="<?= htmlspecialchars($cliente['nombre_cliente']) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <div id="wrapperClienteNuevo" style="display: none;">
            <div class="bg-light p-3 border rounded-4 mb-3 shadow-sm animate__animated animate__fadeIn">
                <h6 class="fw-bold text-danger small text-uppercase mb-3"><i class="bi bi-person-plus-fill me-2"></i>Datos del Nuevo Cliente</h6>
                
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-secondary" style="font-size: 11px;">Nombre / Razón Social *</label>
                    <input type="text" class="form-control form-control-sm rounded-pill" name="nuevo_nombre" id="nuevo_nombre" placeholder="Nombre completo comercial">
                </div>
                
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary" style="font-size: 11px;">Teléfono</label>
                        <input type="text" class="form-control form-control-sm rounded-pill" name="nuevo_telefono" id="nuevo_telefono" placeholder="Opcional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary" style="font-size: 11px;">Ubicación / Planta *</label>
                        <input type="text" class="form-control form-control-sm rounded-pill" name="nueva_ubicacion" id="nueva_ubicacion" placeholder="Dirección física de la empresa">
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Fecha de Entrega (Inicio)</label>
                <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                    <input type="date" class="form-control bg-transparent border-0 fw-semibold text-dark" name="fecha_inicio" id="entrega_fecha_inicio" value="<?= $fecha_hoy ?>" required>
                </div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary text-uppercase d-block mb-2" style="font-size: 11px;">Plazo de Póliza de Garantía</label>
                <div class="d-flex gap-3 p-1">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="plazo_anios_radio" id="plazo_1_anio" value="1" checked>
                        <label class="form-check-label small fw-bold text-dark" for="plazo_1_anio">1 Año</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="plazo_anios_radio" id="plazo_2_anios" value="2">
                        <label class="form-check-label small fw-bold text-dark" for="plazo_2_anios">2 Años</label>
                    </div>
                </div>
            </div>
        </div>

    </div>
    
    <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2 justify-content-end">
        <button type="button" class="btn btn-light btn-sm rounded-pill px-4 py-2 fw-bold text-secondary border shadow-sm" data-bs-dismiss="modal" style="font-size: 13px;">Regresar</button>
        <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 py-2 fw-bold shadow-sm" style="font-size: 13px; background-color: #198754;">
            Confirmar y Activar
        </button>
    </div>
</form>

<script>
function calcularFechaVencimiento() {
    let inputInicio = document.getElementById('entrega_fecha_inicio').value;
    let radioActivo = document.querySelector('input[name="plazo_anios_radio"]:checked');
    let plazoAnios = radioActivo ? parseInt(radioActivo.value, 10) : 1;
    let inputTermino = document.getElementById('entrega_fecha_termino');

    if (inputInicio) {
        let fecha = new Date(inputInicio);
        if (!isNaN(fecha.getTime())) {
            fecha.setFullYear(fecha.getFullYear() + plazoAnios);
            inputTermino.value = fecha.toISOString().split('T')[0];
        }
    }
}

document.getElementById('entrega_fecha_inicio')?.addEventListener('change', calcularFechaVencimiento);
document.querySelectorAll('input[name="plazo_anios_radio"]').forEach(radio => {
    radio.addEventListener('change', calcularFechaVencimiento);
});

calcularFechaVencimiento();

document.getElementById('buscador_cliente_datalist')?.addEventListener('input', function() {
    let valorInput = this.value;
    let opciones = document.getElementById('lista_clientes_maestra').options;
    let inputOculto = document.getElementById('entrega_id_cliente');
    inputOculto.value = "";
    for (let i = 0; i < opciones.length; i++) {
        if (opciones[i].value === valorInput) {
            inputOculto.value = opciones[i].getAttribute('data-id');
            break;
        }
    }
});

document.getElementById('switchClienteNuevo')?.addEventListener('change', function() {
    let exist = document.getElementById('wrapperClienteExistente');
    let nuevo = document.getElementById('wrapperClienteNuevo');
    if (this.checked) {
        exist.style.display = 'none';
        nuevo.style.display = 'block';
        document.getElementById('buscador_cliente_datalist').value = "";
        document.getElementById('entrega_id_cliente').value = "";
    } else {
        exist.style.display = 'block';
        nuevo.style.display = 'none';
        document.getElementById('nuevo_nombre').value = "";
        document.getElementById('nuevo_telefono').value = "";
        document.getElementById('nueva_ubicacion').value = "";
    }
});

document.getElementById('formProcesarEntrega')?.addEventListener('submit', function(e) {
    e.preventDefault();
    let esNuevo = document.getElementById('switchClienteNuevo').checked;
    let serie = document.getElementById('entrega_no_serie').value.trim();

    if (!serie) {
        Swal.fire({ icon: 'warning', title: 'Serie Requerida', text: 'Por favor ingresa el número de serie físico.', confirmButtonColor: '#dc3545' });
        return false;
    }
    
    if (!esNuevo && !document.getElementById('entrega_id_cliente').value) {
        Swal.fire({ icon: 'warning', title: 'Cliente Inválido', text: 'Debes seleccionar un cliente válido de la lista.', confirmButtonColor: '#dc3545' });
        return false;
    }
    
    if (esNuevo) {
        let nom = document.getElementById('nuevo_nombre').value.trim();
        let ubi = document.getElementById('nueva_ubicacion').value.trim();
        if (nom === "" || ubi === "") {
            Swal.fire({ icon: 'warning', title: 'Campos Requeridos', text: 'Por favor asigna el Nombre y la Ubicación del nuevo cliente.', confirmButtonColor: '#dc3545' });
            return false;
        }
    }

    calcularFechaVencimiento();

    Swal.fire({ title: 'Procesando despliegue...', text: 'Registrando cliente y activando póliza.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

    fetch('actions/procesar_entrega_final.php', { method: 'POST', body: new FormData(this) })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            const modalEl = document.getElementById('modalAsignarCliente');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            Swal.fire({ icon: 'success', title: 'Despliegue Exitoso', text: data.message, timer: 2000, showConfirmButton: false });

            // Refrescar tabla principal y desplegable
            if (typeof table !== 'undefined') table.ajax.reload(null, false);
            
            $('#tablaLotes tr.shown').each(function() {
                var row = table.row(this);
                if (row.child.isShown()) {
                    $.ajax({
                        url: 'actions/obtener_desglose_lote.php',
                        method: 'GET',
                        data: { id_lote: row.data().id_lote },
                        success: function (html) { row.child(html).show(); }
                    });
                }
            });

        } else {
            Swal.fire({ icon: 'error', title: 'Falla Operativa', text: data.message, confirmButtonColor: '#dc3545' });
        }
    })
    .catch(() => {
        Swal.close();
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'Ocurrió un error en la comunicación.', confirmButtonColor: '#dc3545' });
    });
});
</script>