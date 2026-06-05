<?php
/**
 * @file header.php
 * @package Portal_Demex
 * @version 4.7 - Arquitectura Adaptativa Multi-Módulo Unificada
 * @brief Layout maestro centralizado con ruteo inteligente bidireccional y guardián estricto.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 1. SEGURIDAD CENTRALIZADA Y CONTROL DE ACCESO MULTI-ROL */
$url_actual = $_SERVER['PHP_SELF'];
$en_soporte = (strpos($url_actual, '/Soporte/') !== false);
$en_ventas = (strpos($url_actual, '/Ventas/') !== false);
$en_subcarpeta = ($en_soporte || $en_ventas);

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte' && $_SESSION['rol'] !== 'ventas')) {
    $regreso_login = $en_subcarpeta ? '../' : './';
    header("Location: " . $regreso_login . "login.php?error=no_autorizado");
    exit();
}

/* 2. GUARDIÁN DE INACTIVIDAD AUTOMÁTICO (15 Minutos) */
$tiempo_maximo_inactividad = 900;
if (isset($_SESSION['ultima_actividad'])) {
    $tiempo_inactivo = time() - $_SESSION['ultima_actividad'];
    if ($tiempo_inactivo > $tiempo_maximo_inactividad) {
        session_unset();
        session_destroy();
        $regreso_login = $en_subcarpeta ? '../' : './';
        header("Location: " . $regreso_login . "login.php?error=sesion_expirada");
        exit();
    }
}
$_SESSION['ultima_actividad'] = time();

/* 3. RESPALDO SILENCIOSO DE SEGURIDAD */
require_once __DIR__ . '/../config/backup.php';
if (isset($pdo)) {
    ejecutarRespaldoSilencioso($pdo);
}

/* 4. MOTOR DE ENRUTAMIENTO GEOMÉTRICO (PREFIJOS INDEPENDIENTES) */
if ($en_subcarpeta) {
    $base_path = "../";
    $staff_link = "../usuarios.php";
    $logout_link = "../logout.php";
    
    // Si estás físicamente en Ventas, el acceso a Soporte debe subir un nivel
    if ($en_ventas) {
        $link_prefix_soporte = "../Soporte/";
        $link_prefix_ventas  = "./";
    } else {
        // Si estás físicamente en Soporte, el acceso a Ventas debe subir un nivel
        $link_prefix_soporte = "./";
        $link_prefix_ventas  = "../Ventas/";
    }
} else {
    // Si estás parado en la raíz del proyecto
    $base_path = "./";
    $link_prefix_soporte = "Soporte/";
    $link_prefix_ventas  = "Ventas/";
    $staff_link = "usuarios.php";
    $logout_link = "logout.php";
}

// Si no se definió manualmente el módulo en la vista, lo forzamos analizando la URL actual de PHP
if (!isset($modulo_actual)) {
    if (strpos($url_actual, '/Soporte/') !== false) {
        $tema_sistema = 'soporte';
    } elseif (strpos($url_actual, '/Ventas/') !== false) {
        $tema_sistema = 'ventas';
    } else {
        $tema_sistema = 'global';
    }
} else {
    $tema_sistema = $modulo_actual;
}

$pagina_actual_php = basename($_SERVER['PHP_SELF']);
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
    
    <link rel="stylesheet" href="/desarrollo_mexicano/css/estilos.css">

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-theme="<?= $tema_sistema ?>">

<script>
    (function() {
        const temaAnterior = localStorage.getItem('demex_last_module_theme');
        const temaNuevo = '<?= $tema_sistema ?>';
        
        if (temaAnterior && temaAnterior !== temaNuevo && temaAnterior !== 'global') {
            document.body.setAttribute('data-theme', temaAnterior);
            window.requestAnimationFrame(() => {
                document.body.setAttribute('data-theme', temaNuevo);
            });
        }
        localStorage.setItem('demex_last_module_theme', temaNuevo);
    })();
</script>

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

                <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte'): ?>
                    <?php include __DIR__ . '/sidebar/menu_soporte.php'; ?>
                <?php endif; ?>

                <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'ventas'): ?>
                    <?php include __DIR__ . '/sidebar/menu_ventas.php'; ?>
                <?php endif; ?>
                
                <?php include __DIR__ . '/sidebar/menu_global.php'; ?>
                
            </div>
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

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const sidebarScroll = document.querySelector('.sidebar-menu-scroll');
                
                if (sidebarScroll) {
                    // 1. Recuperar la posición guardada al cargar la página
                    const posicionGuardada = localStorage.getItem('sidebar_scroll_position');
                    if (posicionGuardada) {
                        sidebarScroll.scrollTop = posicionGuardada;
                    }

                    // 2. Escuchar el evento de scroll para guardar la posición en tiempo real
                    sidebarScroll.addEventListener('scroll', function() {
                        localStorage.setItem('sidebar_scroll_position', sidebarScroll.scrollTop);
                    });
                }
            });
        </script>

        <div class="container-fluid px-4 py-4 flex-grow-1 page-fade-wrapper" id="master-fade-container">