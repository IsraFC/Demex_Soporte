<?php
/**
 * ARCHIVO: importar_tickets.php
 * DESCRIPCIÓN: Interfaz de carga masiva asíncrona para el Historial de Tickets (Estilo Unificado).
 * @author Israel Fernández Carrera
 * @version 3.0 - Integración de Barra de Progreso Avanzada con XHR
 * @date 2026-06-08
 */
require_once '../config/db.php';
$page_title = "Importar Tickets - Soporte";
$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        
        <div class="card border-0 shadow-lg" style="border-radius: 25px; overflow: hidden;">
            
            <div class="card-header bg-white border-0 pt-5 pb-4 text-center">
                <div class="mb-3 mx-auto shadow-sm d-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; background-color: #fff5f5; border-radius: 50%;">
                    <i class="bi bi-ticket-perforated-fill text-danger" style="font-size: 2.5rem;"></i>
                </div>
                <h3 class="fw-bold text-dark">Historial de Tickets</h3>
                <p class="text-muted px-4 small">Importa el registro histórico de llamadas, servicios técnicos y costos de refacciones.</p>
            </div>

            <div class="card-body px-4 px-md-5 pb-5">
                
                <div class="alert border-0 mb-4" style="background-color: #fff5f5; border-radius: 15px;">
                    <div class="d-flex">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-3"></i>
                        <div>
                            <span class="fw-bold d-block small text-uppercase text-danger">Importación Masiva:</span>
                            <p class="mb-0 small text-dark">Este proceso creará automáticamente tickets y desglosará costos. Se recomienda haber importado la base de <strong>Clientes y Máquinas</strong> primero para validar garantías.</p>
                        </div>
                    </div>
                </div>

                <form action="actions/procesar_importacion_tickets.php" method="POST" enctype="multipart/form-data" id="formImportarTickets">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Archivo CSV de Historial</label>
                        <div class="input-group">
                            <input type="file" name="archivo_csv" id="archivo_csv" class="form-control form-control-lg border-0 bg-light shadow-sm" 
                                   accept=".csv" style="border-radius: 15px;" required>
                        </div>
                        <div class="form-text mt-3 text-center small text-muted">
                            <i class="bi bi-table me-1"></i> 
                            El sistema saltará automáticamente las filas de encabezado.
                        </div>
                    </div>

                    <div id="contenedor-progreso" class="mb-4" style="display: none;">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="texto-progreso" class="small fw-bold text-muted text-uppercase">Subiendo base histórica...</span>
                            <span id="porcentaje-progreso" class="small fw-bold text-danger">0%</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 6px; background-color: #f0f0f0;">
                            <div id="barra-progreso" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" 
                                 role="progressbar" style="width: 0%; border-radius: 6px;"></div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" id="btnProcesar" class="btn btn-danger btn-lg rounded-pill fw-bold shadow-sm py-3 mt-2">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Iniciar Carga Histórica
                        </button>
                        <a href="index.php" id="btnVolver" class="btn btn-link text-muted btn-sm text-decoration-none mt-2">
                            <i class="bi bi-arrow-left"></i> Cancelar y volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    $('#formImportarTickets').on('submit', function(e) {
        e.preventDefault();

        const formulario = this;
        const fileInput = $('#archivo_csv')[0];

        if (fileInput.files.length === 0) return;

        // Deshabilitar UI para mitigar duplicados por clics rápidos
        $('#btnProcesar').prop('disabled', true);
        $('#btnVolver').addClass('disabled');
        
        // Desplegar contenedor
        $('#contenedor-progreso').slideDown();
        const barra = $('#barra-progreso');
        const txtProgreso = $('#texto-progreso');
        const lblPorcentaje = $('#porcentaje-progreso');

        // Inicializar objeto nativo XHR
        const xhr = new XMLHttpRequest();

        // 1. ESCUCHA DE BUFFER DE SUBIDA
        xhr.upload.addEventListener("progress", function(evt) {
            if (evt.lengthComputable) {
                const porcentaje = Math.round((evt.loaded / evt.total) * 100);
                
                barra.css('width', porcentaje + '%');
                lblPorcentaje.text(porcentaje + '%');

                if (porcentaje === 100) {
                    // Fase de inserciones transaccionales PHP activas
                    txtProgreso.html('<span class="spinner-border spinner-border-sm text-danger me-2"></span>Escribiendo folios y desglosando costos...');
                }
            }
        }, false);

        // 2. RETORNO DE CONTROL JSON
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);

                        Swal.fire({
                            icon: data.status,
                            title: data.title,
                            text: data.text,
                            confirmButtonColor: '#C62828'
                        }).then(() => {
                            if (data.status === 'success') {
                                window.location.href = 'index.php'; // Redirección al panel unificado de Soporte
                            } else {
                                reiniciarEstadoFormulario();
                            }
                        });
                    } catch (e) {
                        mostrarErrorCritico("Respuesta inválida del servidor. Detalle: " + xhr.responseText);
                    }
                } else {
                    mostrarErrorCritico("Error de red de transporte HTTP: Código " + xhr.status);
                }
            }
        };

        // 3. DISPARAR PETICIÓN
        const datosFormulario = new FormData(formulario);
        xhr.open("POST", formulario.action, true);
        xhr.send(datosFormulario);

        function reiniciarEstadoFormulario() {
            $('#btnProcesar').prop('disabled', false);
            $('#btnVolver').removeClass('disabled');
            $('#contenedor-progreso').slideUp();
            barra.css('width', '0%');
            lblPorcentaje.text('0%');
            txtProgreso.text('Subiendo base histórica...');
        }

        function mostrarErrorCritico(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Falla de Procesamiento',
                text: mensaje,
                confirmButtonColor: '#C62828'
            }).then(() => {
                reiniciarEstadoFormulario();
            });
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>