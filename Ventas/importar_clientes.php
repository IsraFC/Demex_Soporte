<?php
/**
 * ARCHIVO: Ventas/importar_clientes.php
 * DESCRIPCIÓN: Interfaz unificada asíncrona para la carga masiva de Clientes Históricos y sus Compras.
 * Adaptado a la paleta oficial de Ventas DEMEX.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 - Carga masiva con XHR y SweetAlert2
 */

require_once '../config/db.php';
$page_title = "Importar Historial de Clientes | CRM Ventas";
$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-8 col-lg-6">
        
        <div class="card border-0 shadow-lg" style="border-radius: 25px; overflow: hidden;">
            
            <div class="card-header bg-white border-0 pt-5 pb-4 text-center">
                <div class="mb-3 mx-auto shadow-sm d-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; background-color: #f8f9fa; border-radius: 50%;">
                    <i class="bi bi-file-earmark-excel text-danger" style="font-size: 2.5rem;"></i>
                </div>
                <h3 class="fw-bold text-dark">Importación Histórica de Clientes</h3>
                <p class="text-muted px-4 small">Carga tu base de datos de Excel guardada en formato CSV para migrar clientes antiguos con sus respectivos historiales de compra.</p>
            </div>

            <div class="card-body px-4 px-md-5 pb-5">
                
                <div class="alert border-0 mb-4" style="background-color: #fdf2f2; border-radius: 15px;">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-layout-three-columns text-danger fs-4 me-3 mt-1"></i>
                        <div>
                            <span class="fw-bold d-block small text-uppercase text-danger">Orden de las columnas requerido:</span>
                            <span class="text-dark small style-code fw-semibold">Nombre, Apellidos, Teléfono, Correo, Ubicación, Tipo Cliente (Publico General/Distribuidor), Modelo Máquina, Cantidad, Precio Pactado Neto, Fecha Compra (AAAA-MM-DD)</span>
                        </div>
                    </div>
                </div>

                <form action="../actions/procesar_importacion_clientes.php" method="POST" enctype="multipart/form-data" id="formImportar">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Seleccionar archivo de base (.csv)</label>
                        <div class="input-group">
                            <input type="file" name="archivo_csv" id="archivo_csv" class="form-control form-control-lg border-0 bg-light shadow-sm" 
                                   accept=".csv" style="border-radius: 15px;" required>
                        </div>
                    </div>

                    <div id="contenedor-progreso" class="mb-4" style="display: none;">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="texto-progreso" class="small fw-bold text-muted text-uppercase">Subiendo archivo al servidor comercial...</span>
                            <span id="porcentaje-progreso" class="small fw-bold text-danger">0%</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 6px; background-color: #f0f0f0;">
                            <div id="barra-progreso" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" 
                                 role="progressbar" style="width: 0%; border-radius: 6px;"></div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" id="btnProcesar" class="btn btn-danger btn-lg rounded-pill fw-bold shadow-sm py-3 mt-2">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Procesar Migración de Datos
                        </button>
                        <a href="clientes.php" id="btnVolver" class="btn btn-link text-muted btn-sm text-decoration-none mt-2">
                            <i class="bi bi-arrow-left"></i> Regresar al Catálogo de Clientes
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
        e.preventDefault();

        const formulario = this;
        const fileInput = $('#archivo_csv')[0];

        if (fileInput.files.length === 0) return;

        $('#btnProcesar').prop('disabled', true);
        $('#btnVolver').addClass('disabled');
        
        $('#contenedor-progreso').slideDown();
        const barra = $('#barra-progreso');
        const txtProgreso = $('#texto-progreso');
        const lblPorcentaje = $('#porcentaje-progreso');

        const xhr = new XMLHttpRequest();

        // 1. MONITOR ASÍNCRONO EN TIEMPO REAL
        xhr.upload.addEventListener("progress", function(evt) {
            if (evt.lengthComputable) {
                const porcentaje = Math.round((evt.loaded / evt.total) * 100);
                
                barra.css('width', porcentaje + '%');
                lblPorcentaje.text(porcentaje + '%');

                if (porcentaje === 100) {
                    txtProgreso.html('<span class="spinner-border spinner-border-sm text-danger me-2"></span>Escribiendo historial de ventas en base de datos...');
                    barra.addClass('bg-success').removeClass('bg-danger'); 
                }
            }
        }, false);

        // 2. EVALUACIÓN DE RESPUESTA JSON DEL SERVIDOR
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);

                        Swal.fire({
                            icon: data.status,
                            title: data.title,
                            text: data.text,
                            confirmButtonColor: data.status === 'success' ? '#dc3545' : '#333'
                        }).then(() => {
                            if (data.status === 'success') {
                                window.location.href = 'clientes.php';
                            } else {
                                reiniciarEstadoFormulario();
                            }
                        });
                    } catch (e) {
                        mostrarErrorCritico("Error de compilación del motor: La respuesta del servidor no es un JSON limpio. Detalle: " + xhr.responseText);
                    }
                } else {
                    mostrarErrorCritico("Error HTTP de comunicación: Código de red " + xhr.status);
                }
            }
        };

        const datosFormulario = new FormData(formulario);
        xhr.open("POST", formulario.action, true);
        xhr.send(datosFormulario);

        function reiniciarEstadoFormulario() {
            $('#btnProcesar').prop('disabled', false);
            $('#btnVolver').removeClass('disabled');
            $('#contenedor-progreso').slideUp();
            barra.css('width', '0%').addClass('bg-danger').removeClass('bg-success');
            lblPorcentaje.text('0%');
            txtProgreso.text('Subiendo archivo al servidor comercial...');
        }

        function mostrarErrorCritico(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Falla Operativa',
                text: mensaje,
                confirmButtonColor: '#dc3545'
            }).then(() => {
                reiniciarEstadoFormulario();
            });
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>