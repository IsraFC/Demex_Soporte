<?php
/**
 * ARCHIVO: includes/header.php
 * DESCRIPCIÓN: Estructura de encabezado global y carga de recursos críticos.
 * Gestiona la navegación dinámica con resaltado de página activa (estilo cápsula)
 * y centraliza las dependencias de Bootstrap, DataTables y fuentes.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.4
 */
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

    <style>
        /**
         * DISEÑO DE NAVEGACIÓN DINÁMICA
         * .active-page: Crea un efecto de cápsula blanca para resaltar la sección actual.
         */
        .nav-link.active-page {
            background-color: white !important;
            color: #C62828 !important; /* Rojo institucional DEMEX */
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        /* Efecto hover suave para items no activos */
        .nav-link:hover:not(.active-page) {
            background-color: rgba(255,255,255,0.1);
            border-radius: 50px;
        }

        /* Sombra sutil para el logotipo */
        .filter-drop-shadow {
            filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2));
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
            <ul class="navbar-nav ms-auto gap-2">
                
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

            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">