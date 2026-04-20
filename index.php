<?php 
/**
 * Punto de entrada principal - Dashboard
 * Verifica la correcta integración de la plantilla base y conexión a BD.
 */
require_once 'config/db.php'; // Cargamos el puente de conexión
include 'includes/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="card-main">
            <h1 class="fw-bold text-danger">Panel de Control</h1>
            <p class="lead text-muted">Bienvenido al sistema de gestión interna DEMEX.</p>
            
            <div class="d-flex flex-wrap gap-2 mb-4">
                <div class="alert alert-info shadow-sm m-0">
                    <i class="bi bi-info-circle-fill me-2"></i> Plantilla cargada.
                </div>
                <div class="alert alert-success shadow-sm m-0">
                    <i class="bi bi-check-circle-fill me-2"></i> Éxito simulado.
                </div>
                <div class="alert alert-danger shadow-sm m-0">
                    <i class="bi bi-x-circle-fill me-2"></i> Error simulado.
                </div>
                <div class="alert alert-warning shadow-sm m-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Advertencia simulada.
                </div>
                <div class="alert alert-secondary shadow-sm m-0">
                    <i class="bi bi-slash-circle-fill me-2"></i> Estado neutro simulado.
                </div>
                <div class="alert alert-dark shadow-sm m-0">
                    <i class="bi bi-shield-lock-fill me-2"></i> Seguridad simulada.
                </div>
                <div class="alert alert-primary shadow-sm m-0">
                    <i class="bi bi-stars me-2"></i> Información adicional simulada.
                </div>
                <div class="alert alert-light shadow-sm m-0">
                    <i class="bi bi-lightning-fill me-2"></i> Acción rápida simulada.
                </div>
                <div class="alert alert-white shadow-sm m-0">
                    <i class="bi bi-cloud-fill me-2"></i> Estado de nube simulado.
                </div>
                <div class="alert alert-gradient shadow-sm m-0">
                    <i class="bi bi-gem me-2"></i> Información premium simulada.
                </div>
            </div>

            <hr class="my-4">

            <h4 class="fw-bold mb-3">Estado de la Infraestructura</h4>
            <?php
            try {
                // Intentamos una operación básica en la BD
                $db_test = $pdo->query("SELECT DATABASE()")->fetchColumn();
                if ($db_test) {
                    echo '
                    <div class="card bg-light border-0 shadow-sm p-3 d-inline-block">
                        <div class="d-flex align-items-center">
                            <div class="bg-success rounded-circle p-2 me-3">
                                <i class="bi bi-database-check text-white"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-bold text-success">Base de Datos Online</p>
                                <small class="text-muted">Conectado a: ' . $db_test . '</small>
                            </div>
                        </div>
                    </div>';
                }
            } catch (PDOException $e) {
                echo '
                <div class="alert alert-warning border-start border-4 border-warning">
                    <h5 class="alert-heading fw-bold"><i class="bi bi-database-exclamation me-2"></i>Error de Enlace</h5>
                    <p class="mb-0 small">No se pudo establecer comunicación con el servidor de datos. Revisa el archivo <code>config/db.php</code>.</p>
                </div>';
            }
            ?>

            <p class="text-muted mt-5"><small>Este panel es una demostración de la plantilla base. Las funcionalidades operativas se implementarán en breve.</small></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>