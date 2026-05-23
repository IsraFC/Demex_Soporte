<?php
/**
 * @file header.php
 * @package Portal_Demex
 * @version 2.8 - Liquid Menu Animations & Premium Glow Button
 * @date 2026-05-21
 * @brief Layout maestro con animaciones sincronizadas en menús y botón de ticket premium con relieve 3D.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte')) {
    header("Location: ../login.php?error=no_autorizado");
    exit();
}

require_once 'config/backup.php';
if (isset($pdo)) {
    ejecutarRespaldoSilencioso($pdo);
}
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
    <link rel="stylesheet" href="css/estilos.css">

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F8F9FA;
            overflow-x: hidden;
        }

        #wrapper {
            display: flex;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }

        /* --- SIDEBAR MAESTRO --- */
        #sidebar-wrapper {
            min-height: 100vh;
            width: 280px;
            background-color: #1E1E1E;
            transition: width 0.3s cubic-bezier(0.25, 1, 0.5, 1);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
        }

        #wrapper.toggled #sidebar-wrapper {
            width: 75px;
        }

        #page-content-wrapper {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .sidebar-heading {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            height: 77px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: padding 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }

        #wrapper.toggled .sidebar-heading {
            padding: 1.2rem 0;
            justify-content: center;
        }

        .brand-container {
            display: flex;
            align-items: center;
            transition: opacity 0.15s ease, max-width 0.25s ease;
            max-width: 180px;
            opacity: 1;
        }

        #wrapper.toggled .brand-container {
            max-width: 0;
            opacity: 0;
            pointer-events: none;
        }

        /* --- MICROINTERACCIONES DEL MENÚ CON DESPLAZAMIENTO SUAVE --- */
        .sidebar-link {
            padding: 0.8rem 1.5rem;
            color: #B3B3B3 !important;
            display: flex;
            align-items: center;
            justify-content: flex-start !important;
            text-decoration: none;
            margin: 0.2rem 0.8rem;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            z-index: 1;
            transition: color 0.3s cubic-bezier(0.25, 1, 0.5, 1), padding 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }

        /* Capa trasera líquida para la cortina roja */
        .sidebar-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #C62828;
            z-index: -1;
            transform: scaleX(0);
            transform-origin: left;
            /* 🔥 CORRECCIÓN: Agregamos la regla de transición aquí para que se mueva suavemente */
            transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1);
            border-radius: 10px;
        }

        .sidebar-icon {
            min-width: 28px;
            max-width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .sidebar-link span,
        .seccion-herramientas {
            display: inline-block;
            max-width: 180px;
            opacity: 1;
            white-space: nowrap;
            overflow: hidden;
            transition: max-width 0.3s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.2s ease, margin 0.3s;
            margin-left: 10px;
        }

        /* COMPRIMIR A MINI MODO */
        #wrapper.toggled .sidebar-link {
            padding: 0.8rem 0;
            margin: 0.2rem 0.6rem;
            justify-content: center !important;
        }

        #wrapper.toggled .sidebar-link span,
        #wrapper.toggled .seccion-herramientas {
            max-width: 0;
            opacity: 0;
            margin-left: 0;
        }

        /* ESTADO ACTIVO SELECCIONADO */
        .sidebar-link.active-page {
            color: #FFFFFF !important;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(198, 40, 40, 0.25);
        }

        .sidebar-link.active-page::before {
            transform: scaleX(1);
        }

        .sidebar-link.active-page .sidebar-icon {
            transform: scale(1.15);
        }

        .sidebar-link:hover:not(.active-page) {
            color: #FFFFFF !important;
            background-color: rgba(255, 255, 255, 0.04);
        }

        .btn-sidebar-trigger {
            background: none;
            border: none;
            color: #FFFFFF;
            font-size: 1.3rem;
            padding: 4px 10px;
            border-radius: 8px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .btn-sidebar-trigger:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        /* --- NAVBAR PRINCIPAL --- */
        .top-navbar {
            background-color: #C62828 !important;
            border-bottom: 4px solid #8E1C1C;
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
            transition: transform 0.3s ease-in-out;
            height: 77px;
        }

        .top-navbar.navbar-hidden {
            transform: translateY(-100%);
        }

        /* --- 💎 BOTÓN NUEVO TICKET PREMIUM CON RELIEVE 3D Y BRILLO LÍQUIDO --- */
        .btn-ticket-premium {
            position: relative;
            background: linear-gradient(180deg, #E53935 0%, #C62828 100%) !important; /* Degradado con relieve */
            color: #FFFFFF !important;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.6rem 1.6rem;
            border-radius: 50px;
            border: 1px solid #B71C1C !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.3), 
                        inset 0 -2px 0 rgba(0, 0, 0, 0.2) !important; /* Sombras internas 3D */
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        /* Efecto destello de luz (Shimmer Effect) */
        .btn-ticket-premium::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 30%;
            height: 200%;
            background: linear-gradient(
                to right, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.4) 50%, 
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(25deg);
            animation: shimmer 4s infinite linear; /* Brillo infinito cada 4 segundos */
        }

        .btn-ticket-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.4), 
                        inset 0 -2px 0 rgba(0, 0, 0, 0.2) !important;
            filter: brightness(1.08);
        }

        .btn-ticket-premium:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.1), 
                        inset 0 2px 3px rgba(0, 0, 0, 0.2) !important; /* Se hunde al presionarlo */
        }

        /* Definición matemática del recorrido del brillo */
        @keyframes shimmer {
            0% { left: -60%; }
            15% { left: 130%; }
            100% { left: 130%; }
        }

        .dropdown-menu-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .filter-drop-shadow {
            filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2));
        }

        /* --- APARTADO DE CAPAS CINEMÁTICAS --- */
        .page-fade-wrapper {
            opacity: 0;
            transform: translateY(12px);
            transition: opacity 0.3s cubic-bezier(0.25, 1, 0.5, 1), transform 0.3s cubic-bezier(0.25, 1, 0.5, 1);
            will-change: opacity, transform;
        }

        .page-fade-wrapper.fade-in-active {
            opacity: 1;
            transform: translateY(0);
        }

        .page-fade-wrapper.fade-out-active {
            opacity: 0;
            transform: translateY(-10px);
        }
    </style>
</head>
<body>

<script>
    (function() {
        const sidebarState = localStorage.getItem('sidebar_collapsed');
        if (sidebarState === 'true') {
            document.documentElement.classList.add('sidebar-pre-collapsed');
        }
    })();
</script>

<div id="wrapper">

    <div id="sidebar-wrapper" class="shadow">
        <div class="sidebar-heading">
            <div class="brand-container">
                <img src="img/logo_demex.png" alt="DEMEX" width="160" class="filter-drop-shadow">
            </div>
            <button class="btn-sidebar-trigger" id="menu-toggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
        
        <div class="list-group list-group-flush flex-grow-1 mt-3" id="sidebar-menu-list">
            <a href="index.php" class="sidebar-link">
                <div class="sidebar-icon"><i class="bi bi-house-door"></i></div> <span>Inicio</span>
            </a>
            <a href="maquinas.php" class="sidebar-link">
                <div class="sidebar-icon"><i class="bi bi-cpu"></i></div> <span>Máquinas</span>
            </a>
            <a href="clientes.php" class="sidebar-link">
                <div class="sidebar-icon"><i class="bi bi-people"></i></div> <span>Clientes</span>
            </a>
            <a href="estadisticas.php" class="sidebar-link">
                <div class="sidebar-icon"><i class="bi bi-bar-chart-line"></i></div> <span>Estadísticas</span>
            </a>

            <div class="seccion-herramientas border-top border-secondary border-opacity-25 pt-3 mt-2 mx-3">
                <span class="text-uppercase text-muted fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Herramientas</span>
            </div>
            
            <a href="importar_clientes.php" class="sidebar-link py-2">
                <div class="sidebar-icon"><i class="bi bi-person-plus"></i></div> <span>Imp. Clientes</span>
            </a>
            <a href="importar_tickets.php" class="sidebar-link py-2">
                <div class="sidebar-icon"><i class="bi bi-ticket-detailed"></i></div> <span>Imp. Tickets</span>
            </a>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
                <a href="usuarios.php" class="sidebar-link <?= (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'active' : ''; ?>">
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
                        <div class="bg-white text-danger rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 14px;">
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
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center text-danger small py-2" href="../logout.php">
                                <i class="bi bi-box-arrow-right me-2 fs-6"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <script>
            if (localStorage.getItem('sidebar_collapsed') === 'true') {
                document.getElementById('wrapper').classList.add('toggled');
            }

            const paginaActualUrl = window.location.pathname.split("/").pop();
            const enlacesSidebar = document.querySelectorAll('#sidebar-menu-list .sidebar-link');

            enlacesSidebar.forEach(enlace => {
                if (enlace.getAttribute('href') === paginaActualUrl) {
                    enlace.classList.add('active-page');
                }
            });
        </script>

        <div class="container-fluid px-4 py-4 flex-grow-1 page-fade-wrapper" id="master-fade-container">