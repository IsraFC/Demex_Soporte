<?php
/**
 * ARCHIVO: importar_clientes.php
 * DESCRIPCIÓN: Interfaz unificada para la carga de Clientes y Máquinas.
 * @author Israel Fernández Carrera
 */
require_once 'config/db.php';
include 'includes/header.php';
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

                <form action="actions/procesar_importacion.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Seleccionar archivo .csv</label>
                        <div class="input-group">
                            <input type="file" name="archivo_csv" class="form-control form-control-lg border-0 bg-light shadow-sm" 
                                   accept=".csv" style="border-radius: 15px;" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3 mt-2">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Procesar Información
                        </button>
                        <a href="index.php" class="btn btn-link text-muted btn-sm text-decoration-none mt-2">
                            <i class="bi bi-arrow-left"></i> Volver al panel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>