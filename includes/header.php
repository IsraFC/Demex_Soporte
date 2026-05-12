<?php
/**
 * ARCHIVO: includes/header.php
 * DESCRIPCIÓN: Estructura de encabezado global con menú de importación masiva.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.7
 */
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
    <title>DEMEX | Gestión de Soporte</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="css/estilos.css">

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .nav-link.active-page {
            background-color: white !important;
            color: #C62828 !important; 
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .nav-link:hover:not(.active-page) {
            background-color: rgba(255,255,255,0.1);
            border-radius: 50px;
        }

        .filter-drop-shadow {
            filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2));
        }

        /* Estilo para el dropdown de importación para que no rompa la estética */
        .dropdown-menu-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-top: 10px;
        }
        .dropdown-item-custom:hover {
            background-color: #f8f9fa;
            color: #C62828;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #C62828; border-bottom: 4px solid #8E1C1C;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="img/logo_demex.png" alt="DEMEX" width="256" class="me-2 filter-drop-shadow">
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto gap-2 align-items-center">
                
                <li class="nav-item">
                    <a class="nav-link px-4 <?= (isset($pagina_actual) && $pagina_actual == 'inicio') ? 'active-page' : '' ?>" href="index.php">
                        <i class="bi bi-house-door me-1"></i> Inicio
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-4 <?= (isset($pagina_actual) && $pagina_actual == 'maquinas') ? 'active-page' : '' ?>" href="maquinas.php">
                        <i class="bi bi-cpu me-1"></i> Máquinas
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-4 <?= (isset($pagina_actual) && $pagina_actual == 'clientes') ? 'active-page' : '' ?>" href="clientes.php">
                        <i class="bi bi-people me-1"></i> Clientes
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-4 <?= (isset($pagina_actual) && $pagina_actual == 'estadisticas') ? 'active-page' : '' ?>" href="estadisticas.php">
                        <i class="bi bi-bar-chart-line me-1"></i> Estadísticas
                    </a>
                </li>

                <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle px-4 border border-light border-opacity-25 rounded-pill" href="#" id="importDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Importar
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom p-2" aria-labelledby="importDropdown">
                        <li><h6 class="dropdown-header small text-uppercase fw-bold">Cargas Masivas CSV</h6></li>
                        <li>
                            <a class="dropdown-item dropdown-item-custom py-2" href="importar_clientes.php">
                                <i class="bi bi-person-plus-fill me-2 text-primary"></i> Clientes y Máquinas
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item dropdown-item-custom py-2" href="importar_tickets.php">
                                <i class="bi bi-ticket-detailed-fill me-2 text-danger"></i> Historial de Tickets
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item ms-lg-3">
                    <button class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold border border-white border-opacity-50" data-bs-toggle="modal" data-bs-target="#modalNuevoTicket">
                        <i class="bi bi-plus-circle-fill me-2"></i> NUEVO TICKET
                    </button>
                </li>

            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">