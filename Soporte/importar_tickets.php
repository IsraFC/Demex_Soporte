<?php
/**
 * ARCHIVO: importar_tickets.php
 * DESCRIPCIÓN: Interfaz de carga masiva para el Historial de Tickets (Estilo Unificado).
 * @author Israel Fernández Carrera
 */
require_once 'config/db.php';
include 'includes/header.php';
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

                <form action="actions/procesar_importacion_tickets.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Archivo CSV de Historial</label>
                        <div class="input-group">
                            <input type="file" name="archivo_csv" class="form-control form-control-lg border-0 bg-light shadow-sm" 
                                   accept=".csv" style="border-radius: 15px;" required>
                        </div>
                        <div class="form-text mt-3 text-center small text-muted">
                            <i class="bi bi-table me-1"></i> 
                            El sistema saltará automáticamente las filas de encabezado.
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg rounded-pill fw-bold shadow-sm py-3 mt-2">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Iniciar Carga Histórica
                        </button>
                        <a href="index.php" class="btn btn-link text-muted btn-sm text-decoration-none mt-2">
                            <i class="bi bi-arrow-left"></i> Cancelar y volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>