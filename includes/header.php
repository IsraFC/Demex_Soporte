<?php
/**
 * ARCHIVO: header.php
 * DESCRIPCIÓN: Estructura de encabezado global y carga de recursos críticos.
 * Centraliza las dependencias CSS/JS, fuentes tipográficas y la navegación principal.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.2
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
            <ul class="navbar-nav ms-auto">
                
                <?php if (isset($pagina_actual) && $pagina_actual != 'inicio'): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="index.php">
                            <i class="bi bi-house-door"></i> Inicio
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($pagina_actual) && $pagina_actual != 'maquinas'): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="maquinas.php">
                            <i class="bi bi-cpu"></i> Maquinas
                        </a>
                    </li>
                <?php endif; ?>  
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">