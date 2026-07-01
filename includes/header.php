<?php
/**
 * @file header.php
 * @package Portal_Demex
 * @version 5.6 - Arquitectura Adaptativa con Captura de Feedback Unificada
 * @brief Layout maestro centralizado con ruteo inteligente, menú persistente y modal asíncrono de feedback.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * FUNCIÓN GLOBAL DE CONTROL DE ACCESOS
 * Evalúa si el usuario logueado cuenta con al menos uno de los roles autorizados.
 * Respeta de forma estricta las mayúsculas iniciales fijadas en el catálogo de la BD.
 */
function tieneAcceso($roles_permitidos) {
    if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles'])) {
        return false;
    }
    foreach ($roles_permitidos as $rol) {
        if (in_array($rol, $_SESSION['roles'])) {
            return true; // Acceso concedido con una coincidencia
        }
    }
    return false;
}

/* 1. SEGURIDAD CENTRALIZADA Y CONTROL DE ACCESO MULTI-ROL */
$url_actual = $_SERVER['PHP_SELF'];
// Usamos SCRIPT_NAME para obtener el nombre físico del archivo real en ejecución
$pagina_actual_php = basename($_SERVER['SCRIPT_NAME']);

$en_soporte = (strpos($url_actual, '/Soporte/') !== false);
$en_ventas = (strpos($url_actual, '/Ventas/') !== false);
$en_almacen = (strpos($url_actual, '/Almacen/') !== false); // <-- AGREGADO
$en_subcarpeta = ($en_soporte || $en_ventas || $en_almacen);  // <-- ACTUALIZADO

// Lista de páginas públicas que bajo ninguna circunstancia deben validar sesión o redirigir
$paginas_publicas = ['login.php', 'recuperar.php', 'restablecer.php', 'verificar.php'];

// Si la página actual no es pública, aplicamos el guardián de seguridad
if (!in_array($pagina_actual_php, $paginas_publicas)) {
    if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles']) || !tieneAcceso(['Administrador', 'Soporte', 'Ventas', 'Almacen', 'Cliente'])) {
        
        // Calculamos la ruta de escape al login de forma exacta basándonos en la profundidad del archivo
        $regreso_login = $en_subcarpeta ? '../' : './';
        
        // Destruimos cualquier posible residuo de sesión inválida para limpiar el estado del navegador
        session_unset();
        
        header("Location: " . $regreso_login . "login.php?error=no_autorizado");
        exit();
    }
}

/* 2. GUARDIÁN DE INACTIVIDAD AUTOMÁTICO (15 Minutos) */
$tiempo_maximo_inactividad = 900;
if (isset($_SESSION['ultima_actividad']) && !in_array($pagina_actual_php, $paginas_publicas)) {
    $tiempo_inactivo = time() - $_SESSION['ultima_actividad'];
    if ($tiempo_inactivo > $tiempo_maximo_inactividad) {
        session_unset();
        session_destroy();
        $regreso_login = $en_subcarpeta ? '../' : './';
        header("Location: " . $regreso_login . "login.php?error=sesion_expirada");
        exit();
    }
}
// Solo actualizamos la actividad si el usuario ya está autenticado en el sistema protegido
if (isset($_SESSION['roles'])) {
    $_SESSION['ultima_actividad'] = time();
}

/* 3. RESPALDO SILENCIOSO DE SEGURIDAD Y CARGA CACHÉ DE AVATAR */
require_once __DIR__ . '/../config/backup.php';

// Si el usuario está logueado pero su sesión de foto no se ha definido, la consultamos una sola vez como caché
if (isset($_SESSION['id_usuario']) && !isset($_SESSION['foto_perfil_base64']) && isset($pdo)) {
    try {
        $stmtFoto = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id_usuario = ? LIMIT 1");
        $stmtFoto->execute([$_SESSION['id_usuario']]);
        $resFoto = $stmtFoto->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($resFoto['foto_perfil'])) {
            $_SESSION['foto_perfil_base64'] = base64_encode($resFoto['foto_perfil']);
        } else {
            $_SESSION['foto_perfil_base64'] = '';
        }
    } catch (Exception $e) {
        $_SESSION['foto_perfil_base64'] = '';
    }
}

if (isset($pdo)) {
    ejecutarRespaldoSilencioso($pdo);
}

/* 4. MOTOR DE ENRUTAMIENTO GEOMÉTRICO (PREFIJOS INDEPENDIENTES) */
if ($en_subcarpeta) {
    $base_path = "../";
    $staff_link = "../usuarios.php";
    $logout_link = "../logout.php";
    
    if ($en_ventas) {
        $link_prefix_soporte = "../Soporte/";
        $link_prefix_ventas  = "./";
        $link_prefix_almacen = "../Almacen/";
    } elseif ($en_soporte) {
        $link_prefix_soporte = "./";
        $link_prefix_ventas  = "../Ventas/";
        $link_prefix_almacen = "../Almacen/";
    } elseif ($en_almacen) { 
        $link_prefix_soporte = "../Soporte/";
        $link_prefix_ventas  = "../Ventas/";
        $link_prefix_almacen = "./";
    }
} else {
    $base_path = "./";
    $link_prefix_soporte = "Soporte/";
    $link_prefix_ventas  = "Ventas/";
    $link_prefix_almacen = "Almacen/";
    $staff_link = "usuarios.php";
    $logout_link = "logout.php";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEMEX | <?= htmlspecialchars($page_title ?? 'Panel de Control') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <link rel="stylesheet" href="/css/estilos.css">

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>


<div id="wrapper" class="shadow-sm">
    <script>
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.getElementById('wrapper').classList.add('toggled');
        }
    </script>
    <div id="sidebar-wrapper" class="shadow">
        <div class="sidebar-heading">
            <div class="brand-container">
                <img src="<?= $base_path ?>img/logo_demex.png" alt="DEMEX" width="160" class="filter-drop-shadow">
            </div>
            <button class="btn-sidebar-trigger" id="menu-toggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
        
        <div class="sidebar-menu-scroll">
            <div class="list-group list-group-flush mt-2" id="sidebar-menu-list">

                <?php if (tieneAcceso(['Administrador', 'Soporte'])): ?>
                    <?php include __DIR__ . '/sidebar/menu_soporte.php'; ?>
                <?php endif; ?>

                <?php if (tieneAcceso(['Administrador', 'Ventas'])): ?>
                    <?php include __DIR__ . '/sidebar/menu_ventas.php'; ?>
                <?php endif; ?>
                
                <?php if (tieneAcceso(['Administrador', 'Almacen'])): ?>
                    <?php include __DIR__ . '/sidebar/menu_almacen.php'; ?>
                <?php endif; ?>
                
                <?php if (tieneAcceso(['Administrador'])): ?>
                    <?php include __DIR__ . '/sidebar/menu_global.php'; ?>
                <?php endif; ?>
                
            </div>
        </div> 
    </div>
    <div id="page-content-wrapper">
        <nav class="navbar top-navbar d-flex align-items-center justify-content-between shadow-sm" id="main-top-navbar">
            <div></div>
            <div class="d-flex align-items-center gap-3">
                
                <?php if (tieneAcceso(['Administrador', 'Soporte']) && $en_soporte): ?>
                    <button class="btn btn-ticket-premium shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoTicket">
                        <i class="bi bi-plus-circle-fill"></i> NUEVO TICKET
                    </button>
                <?php endif; ?>

                <div class="vr bg-white opacity-25" style="height: 24px;"></div>

                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        
                        <?php if (!empty($_SESSION['foto_perfil_base64'])): ?>
                            <img src="data:image/jpeg;base64,<?= $_SESSION['foto_perfil_base64'] ?>" 
                                 alt="Avatar" 
                                 class="rounded-circle shadow-sm object-fit-cover" 
                                 style="width: 36px; height: 36px;">
                        <?php else: ?>
                            <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 14px;">
                                <?= isset($_SESSION['nombre']) ? strtoupper(substr($_SESSION['nombre'], 0, 1)) : 'U' ?>
                            </div>
                        <?php endif; ?>

                        <span class="ms-2 d-none d-sm-inline small fw-semibold text-white">
                            <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom p-2 mt-2 shadow border-0" id="dropdownMenuPerfil" aria-labelledby="profileDropdown" style="min-width: 220px;">
                        <li>
                            <div class="dropdown-header text-start py-1">
                                <span class="d-block text-dark fw-bold small lh-sm"><?= htmlspecialchars(($_SESSION['nombre'] ?? 'Usuario') . ' ' . ($_SESSION['apellidos'] ?? '')) ?></span>
                                <span class="d-block text-muted text-truncate" style="font-size: 11px;"><?= htmlspecialchars($_SESSION['correo'] ?? '') ?></span>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-uppercase mt-1" style="font-size: 9px;">
                                    <?= htmlspecialchars(implode(', ', $_SESSION['roles'] ?? ['Soporte'])) ?>
                                </span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <li>
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center small py-2" href="<?= $base_path ?>perfil.php">
                                <i class="bi bi-person-gear me-2 fs-6 text-secondary"></i> Mi Perfil
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center small py-2" href="<?= $base_path ?>preferencias.php">
                                <i class="bi bi-gear me-2 fs-6 text-secondary"></i> Preferencias del Portal
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center small py-2" href="#" data-bs-toggle="modal" data-bs-target="#modalReportarFeedback">
                                <i class="bi bi-chat-square-heart me-2 fs-6 text-secondary"></i> Reportar Error / Feedback
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center text-danger small py-2" href="<?= $logout_link ?>">
                                <i class="bi bi-box-arrow-right me-2 fs-6"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="modal fade animate__animated animate__fadeIn" id="modalReportarFeedback" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; background: #ffffff;">
                    
                    <div class="modal-header border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="shadow-sm d-flex align-items-center justify-content-center" 
                                style="width: 45px; height: 45px; background-color: #fff5f5; border-radius: 16px;">
                                <i class="bi bi-chat-square-heart-fill text-danger fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0" style="font-family: 'Poppins', sans-serif;">Centro de Soporte Técnico</h5>
                                <small class="text-muted" style="font-size: 0.75rem;">Control de calidad e incidencias internas</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close bg-light rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close" id="btnCerrarFeedbackTop" style="font-size: 0.75rem;"></button>
                    </div>
                    
                    <form id="formFeedbackPortal" novalidate>
                        <div class="modal-body px-4 py-3">
                            <div class="alert alert-light border-0 small text-secondary mb-4 p-3 d-flex align-items-start gap-2" style="border-radius: 16px; background-color: #f8f9fa;">
                                <i class="bi bi-info-circle-fill text-danger mt-0.5 fs-6"></i>
                                <span>Este canal recopila de forma automática los datos del usuario y la pantalla actual. Las solicitudes son enviadas al departamento de desarrollo técnico para su análisis e integración.</span>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider" style="font-size: 11px;">Tipo de Incidencia o Solicitud</label>
                                <div class="input-group border rounded-pill px-3 py-1 bg-light shadow-sm">
                                    <span class="input-group-text border-0 bg-transparent text-danger"><i class="bi bi-layers-half"></i></span>
                                    <select class="form-select border-0 bg-transparent fw-semibold text-dark p-1" name="tipo_feedback" id="tipo_feedback" style="font-size: 14px;" required>
                                        <option value="Bug">Falla en el Sistema / Error de Ejecución</option>
                                        <option value="Visual">Interfaz / Problema de Diseño u Optimización Visual</option>
                                        <option value="Lento">Rendimiento / Carga Lenta de Tablas o Procesos</option>
                                        <option value="Seguridad">Permisos / Problema de Acceso o Autenticación</option>
                                        <option value="BaseDatos">Datos Incorrectos / Error en Consultas de Base de Datos</option>
                                        <option value="Mejora">Sugerencia / Propuesta de Nueva Característica o Módulo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider" style="font-size: 11px;">Descripción Detallada</label>
                                <textarea class="form-control border-0 bg-light shadow-sm p-3 small text-dark" 
                                        name="desc_feedback" 
                                        id="desc_feedback" 
                                        rows="4" 
                                        style="border-radius: 18px; font-size: 13px; resize: none;" 
                                        placeholder="Indique detalladamente la acción que estaba realizando o especifique su sugerencia de cambio..." required></textarea>
                            </div>
                            
                            <input type="hidden" name="url_incidencia" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                        </div>
                        
                        <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2 justify-content-end">
                            <button type="button" class="btn btn-light btn-sm rounded-pill px-4 py-2 fw-bold text-secondary border shadow-sm" data-bs-dismiss="modal" id="btnCancelarFeedback" style="font-size: 13px;">Cancelar</button>
                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 py-2 fw-bold shadow-sm" style="font-size: 13px; background-color: #dc3545;">
                                <i class="bi bi-send-fill me-1.5"></i> Enviar Reporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const sidebarScroll = document.querySelector('.sidebar-menu-scroll');
                
                if (sidebarScroll) {
                    const posicionGuardada = localStorage.getItem('sidebar_scroll_position');
                    if (posicionGuardada) {
                        sidebarScroll.scrollTop = posicionGuardada;
                    }

                    sidebarScroll.addEventListener('scroll', function() {
                        localStorage.setItem('sidebar_scroll_position', sidebarScroll.scrollTop);
                    });
                }

                // 🎯 INTERCEPTOR DE CLICS: Detiene el cierre imprevisto dentro del recuadro del perfil
                const recuadroPerfil = document.getElementById('dropdownMenuPerfil');
                if (recuadroPerfil) {
                    recuadroPerfil.addEventListener('click', function(e) {
                        e.stopPropagation(); // Evita que Bootstrap capte el evento y cierre el menú
                    });
                }

                // 🎯 CONTROLADOR ASÍNCRONO: Envío transaccional del Reporte de Errores con Fetch API
                const formFeedback = document.getElementById('formFeedbackPortal');
                if (formFeedback) {
                    formFeedback.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const tipo = document.getElementById('tipo_feedback').value;
                        const desc = document.getElementById('desc_feedback').value.trim();

                        if (desc === "") {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Campos Vacíos',
                                text: 'Por favor, describe los detalles de la incidencia antes de enviarla.',
                                confirmButtonColor: '#dc3545'
                            });
                            return;
                        }

                        // Creamos la petición asíncrona hacia el controlador central de acciones
                        const formData = new FormData(formFeedback);
                        
                        Swal.fire({
                            title: 'Procesando reporte...',
                            text: 'Guardando traza de auditoría en la base de datos.',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        fetch('<?= $base_path ?>actions/procesar_feedback.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalReportarFeedback'));
                                if (modalInstance) modalInstance.hide();
                                formFeedback.reset();

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Reporte Recibido',
                                    text: data.message || 'La incidencia ha sido registrada en el sistema de control técnico para su revisión.',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Falla del Motor',
                                    text: data.message || 'No se pudo registrar la traza técnica.'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error del Servidor',
                                text: 'Ocurrió un colapso en la petición asíncrona de red.'
                            });
                        });
                    });
                }
            });
        </script>

        <div class="container-fluid px-4 py-4 flex-grow-1 page-fade-wrapper" id="master-fade-container">