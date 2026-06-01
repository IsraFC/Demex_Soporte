<?php
/**
 * @file header.php
 * @package Portal_Demex
 * @version 4.5 - Absolute Route Fix con Control de Inactividad
 * @date 2026-06-01
 * @brief Layout maestro unificado centralizado en la raíz con ruteo adaptativo y guardián de inactividad.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 1. SEGURIDAD CENTRALIZADA AUTOMÁTICA Y CONTROL DE ACCESO */
$url_actual = $_SERVER['PHP_SELF'];
$en_subcarpeta = (strpos($url_actual, '/Soporte/') !== false);

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte')) {
    $regreso_login = $en_subcarpeta ? '../' : './';
    header("Location: " . $regreso_login . "login.php?error=no_autorizado");
    exit();
}

/* NUEVO: CONTROL DE INACTIVIDAD (Límite: 15 minutos = 900 segundos) */
$tiempo_maximo_inactividad = 900; // 15 minutos en segundos

if (isset($_SESSION['ultima_actividad'])) {
    $tiempo_inactivo = time() - $_SESSION['ultima_actividad'];
    
    if ($tiempo_inactivo > $tiempo_maximo_inactividad) {
        // La sesión expiró por abandono: limpiamos y destruimos la sesión
        session_unset();
        session_destroy();
        
        // Redirigimos al usuario usando el ruteo adaptativo que ya tienes
        $regreso_login = $en_subcarpeta ? '../' : './';
        header("Location: " . $regreso_login . "login.php?error=sesion_expirada");
        exit();
    }
}
// Actualizamos la estampa de tiempo con la actividad más reciente del usuario
$_SESSION['ultima_actividad'] = time();


/* 2. MOTOR DE RESPALDO SILENCIOSO */
require_once __DIR__ . '/../config/backup.php';
if (isset($pdo)) {
    ejecutarRespaldoSilencioso($pdo);
}

/**
 * 3. BLINDAJE DE ENRUTAMIENTO ESTRICTO
 * Asignamos las rutas relativas correspondientes analizando la ubicación del script
 */
if ($en_subcarpeta) {
    $base_path = "../";
    $link_prefix = "";          /* Si ya estoy dentro, mis enlaces son limpios (ej: index.php) */
    $staff_link = "../usuarios.php";
    $logout_link = "../logout.php";
} else {
    $base_path = "./";
    $link_prefix = "Soporte/";  /* Si estoy en la raíz, obligo a entrar a la subcarpeta */
    $staff_link = "usuarios.php";
    $logout_link = "logout.php";
}

$tema_sistema = $modulo_actual ?? 'global';
$pagina_actual_php = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEMEX | Panel de Control</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <link rel="stylesheet" href="<?= $base_path ?>css/estilos.css">

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-theme="<?= $tema_sistema ?>">

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
        
        <div class="list-group list-group-flush flex-grow-1 mt-3" id="sidebar-menu-list">

            <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte'): ?>
                <div class="seccion-herramientas mx-3">
                    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Soporte</span>
                </div>

                <a href="<?= $link_prefix ?>index.php" class="sidebar-link <?= ($pagina_actual_php === 'index.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
                    <div class="sidebar-icon"><i class="bi bi-house-door"></i></div> <span>Inicio</span>
                </a>
                <a href="<?= $link_prefix ?>maquinas.php" class="sidebar-link <?= ($pagina_actual_php === 'maquinas.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
                    <div class="sidebar-icon"><i class="bi bi-cpu"></i></div> <span>Máquinas</span>
                </a>
                <a href="<?= $link_prefix ?>clientes.php" class="sidebar-link <?= ($pagina_actual_php === 'clientes.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
                    <div class="sidebar-icon"><i class="bi bi-people"></i></div> <span>Clientes</span>
                </a>
                <a href="<?= $link_prefix ?>estadisticas.php" class="sidebar-link <?= ($pagina_actual_php === 'estadisticas.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
                    <div class="sidebar-icon"><i class="bi bi-bar-chart-line"></i></div> <span>Estadísticas</span>
                </a>

                <div class="seccion-herramientas border-top border-secondary border-opacity-25 pt-3 mt-2 mx-3">
                    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Herramientas</span>
                </div>
                
                <a href="<?= $link_prefix ?>importar_clientes.php" class="sidebar-link py-2 <?= ($pagina_actual_php === 'importar_clientes.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
                    <div class="sidebar-icon"><i class="bi bi-person-plus"></i></div> <span>Imp. Clientes</span>
                </a>
                <a href="<?= $link_prefix ?>importar_tickets.php" class="sidebar-link py-2 <?= ($pagina_actual_php === 'importar_tickets.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
                    <div class="sidebar-icon"><i class="bi bi-ticket-detailed"></i></div> <span>Imp. Tickets</span>
                </a>
            <?php endif; ?>

            <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <div class="seccion-herramientas border-top border-secondary border-opacity-25 pt-3 mt-2 mx-3">
                    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Global</span>
                </div>
                <a href="<?= $staff_link ?>" class="sidebar-link <?= (($pagina_actual_php === 'usuarios.php' || $pagina_actual_php === 'personal_staff.php') && !$en_subcarpeta) ? 'active-page no-anim' : '' ?>">
                    <div class="sidebar-icon"><i class="bi bi-shield-lock-fill"></i></div> <span>Personal Staff</span>
                </a>
            <?php endif; ?>
            
        </div>
    </div>

    <div id="page-content-wrapper">
        <nav class="navbar top-navbar d-flex align-items-center justify-content-between shadow-sm" id="main-top-navbar">
            <div></div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-ticket-premium shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoTicket">
                    <i class="bi bi-plus-circle-fill"></i> NUEVO TICKET
                </button>

                <div class="vr bg-white opacity-25" style="height: 24px;"></div>

                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 14px;">
                            <?= isset($_SESSION['nombre']) ? strtoupper(substr($_SESSION['nombre'], 0, 1)) : 'U' ?>
                        </div>
                        <span class="ms-2 d-none d-sm-inline small fw-semibold text-white">
                            <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom p-2 mt-2 shadow border-0" aria-labelledby="profileDropdown" style="min-width: 220px;">
                        <li>
                            <div class="dropdown-header text-start py-1">
                                <span class="d-block text-dark fw-bold small lh-sm"><?= htmlspecialchars(($_SESSION['nombre'] ?? 'Usuario') . ' ' . ($_SESSION['apellidos'] ?? '')) ?></span>
                                <span class="d-block text-muted text-truncate" style="font-size: 11px;"><?= htmlspecialchars($_SESSION['correo'] ?? '') ?></span>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-uppercase mt-1" style="font-size: 9px;"><?= htmlspecialchars($_SESSION['rol'] ?? 'Soporte') ?></span>
                            </div>
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

        <div class="container-fluid px-4 py-4 flex-grow-1 page-fade-wrapper" id="master-fade-container">