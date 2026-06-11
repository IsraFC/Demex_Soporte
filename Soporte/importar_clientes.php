<?php
/**
 * ARCHIVO: importar_clientes.php
 * DESCRIPCIÓN: Interfaz unificada asíncrona para la carga masiva de Clientes y Máquinas.
 * @author Israel Fernández Carrera
 * @version 2.0 - Integración de Barra de Progreso Avanzada con XHR
 * @date 2026-06-08
 */
require_once '../config/db.php';
$page_title = "Importar Clientes - Soporte";
$modulo_actual = 'soporte';
include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-5">
        
        <div class="card border-0 shadow-lg" style="border-radius: 25px; overflow: hidden;">
            
            <div class="card-header bg-white border-0 pt-5 pb-4 text-center">
                <div class="mb-3 mx-auto shadow-sm d-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; background-color: #f8f9fa; border-radius: 50%;">
                    <i class="bi bi-people-fill text-primary" style="font-size: 2.5rem;"></i>
                </div>
                <h3 class="fw-bold text-dark">Carga de Base</h3>
                <p class="text-muted px-4 small">Importa tu lista de clientes y sus equipos directamente desde un archivo de Excel (guardado como CSV).</p>
            </div>

            <div class="card-body px-4 px-md-5 pb-5">
                
                <div class="alert border-0 mb-4" style="background-color: #eef2f7; border-radius: 15px;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-layout-three-columns text-primary fs-4 me-3"></i>
                        <div>
                            <span class="fw-bold d-block small text-uppercase text-primary">Orden de las columnas:</span>
                            <span class="text-dark small">Nombre, Teléfono, Ubicación, Modelo, Serie, Fecha Inicio, Fecha Fin.</span>
                        </div>
                    </div>
                </div>

                <form action="actions/procesar_importacion.php" method="POST" enctype="multipart/form-data" id="formImportar">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Seleccionar archivo .csv</label>
                        <div class="input-group">
                            <input type="file" name="archivo_csv" id="archivo_csv" class="form-control form-control-lg border-0 bg-light shadow-sm" 
                                   accept=".csv" style="border-radius: 15px;" required>
                        </div>
                    </div>

                    <div id="contenedor-progreso" class="mb-4" style="display: none;">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="texto-progreso" class="small fw-bold text-muted text-uppercase">Subiendo archivo al servidor...</span>
                            <span id="porcentaje-progreso" class="small fw-bold text-primary">0%</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 6px; background-color: #f0f0f0;">
                            <div id="barra-progreso" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" style="width: 0%; border-radius: 6px;"></div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" id="btnProcesar" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3 mt-2">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Procesar Información
                        </button>
                        <a href="maquinas.php" id="btnVolver" class="btn btn-link text-muted btn-sm text-decoration-none mt-2">
                            <i class="bi bi-arrow-left"></i> Volver al panel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    $('#formImportar').on('submit', function(e) {
        e.preventDefault(); // Detiene el comportamiento tradicional de recarga

        const formulario = this;
        const fileInput = $('#archivo_csv')[0];

        if (fileInput.files.length === 0) return;

        // Deshabilitamos interacciones para evitar clics dobles
        $('#btnProcesar').prop('disabled', true);
        $('#btnVolver').addClass('disabled'); // 🎯 CORREGIDO: Se eliminó el paréntesis colado
        
        // Mostramos la barra de progreso
        $('#contenedor-progreso').slideDown();
        const barra = $('#barra-progreso');
        const txtProgreso = $('#texto-progreso');
        const lblPorcentaje = $('#porcentaje-progreso');

        // Inicializamos el objeto de transferencia nativo (XHR)
        const xhr = new XMLHttpRequest();

        // 1. MEDIDOR DE PROGRESO DE SUBIDA EN TIEMPO REAL
        xhr.upload.addEventListener("progress", function(evt) {
            if (evt.lengthComputable) {
                const porcentaje = Math.round((evt.loaded / evt.total) * 100);
                
                barra.css('width', porcentaje + '%');
                lblPorcentaje.text(porcentaje + '%');

                if (porcentaje === 100) {
                    // Fase 2: El archivo ya está en el servidor, PHP lo está leyendo
                    txtProgreso.html('<span class="spinner-border spinner-border-sm text-danger me-2"></span>Escribiendo en base de datos...');
                    barra.removeClass('bg-primary').addClass('bg-danger'); 
                }
            }
        }, false);

        // 2. RECEPTOR DE LA RESPUESTA DEL BACKEND
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);

                        Swal.fire({
                            icon: data.status,
                            title: data.title,
                            text: data.text,
                            confirmButtonColor: data.status === 'success' ? '#d15b00' : '#C62828'
                        }).then(() => {
                            if (data.status === 'success') {
                                window.location.href = 'maquinas.php'; // Redirección al inventario
                            } else {
                                reiniciarEstadoFormulario();
                            }
                        });
                    } catch (e) {
                        // Si PHP tiró un aviso de texto plano en vez de JSON, lo cachamos aquí
                        mostrarErrorCritico("Error interno del servidor: La respuesta no es un JSON puro. Detalle: " + xhr.responseText);
                    }
                } else {
                    mostrarErrorCritico("Error de comunicación HTTP: Código " + xhr.status);
                }
            }
        };

        // 3. ENVÍO ASÍNCRONO DE LOS DATOS
        const datosFormulario = new FormData(formulario);
        xhr.open("POST", formulario.action, true);
        xhr.send(datosFormulario);

        function reiniciarEstadoFormulario() {
            $('#btnProcesar').prop('disabled', false);
            $('#btnVolver').removeClass('disabled');
            $('#contenedor-progreso').slideUp();
            barra.css('width', '0%').removeClass('bg-danger').addClass('bg-primary');
            lblPorcentaje.text('0%');
            txtProgreso.text('Subiendo archivo al servidor...');
        }

        function mostrarErrorCritico(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Falla Operativa',
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