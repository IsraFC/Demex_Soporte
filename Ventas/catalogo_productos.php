<?php
/**
 * ARCHIVO: Ventas/catalogo_productos.php
 * DESCRIPCIÓN: Panel Central del Catálogo de Productos DEMEX.
 * Muestra las 4 categorías comerciales con tarjetas visuales enriquecidas con imágenes de catálogo.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 2.0 (Diseño visual con imágenes adaptado al estándar de tarjetas corporativas)
 */

$page_title = "Catálogo de Productos | CRM Ventas";
require_once '../config/db.php';

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<style>
    /* Efecto de elevación y escala suave para las tarjetas del catálogo */
    .card-catalogo {
        transition: all 0.3s ease-in-out;
        cursor: pointer;
        border: 1px solid #dee2e6 !important;
        overflow: hidden; /* Evita que la imagen se salga de las esquinas redondeadas de la tarjeta */
    }
    
    .card-catalogo:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.13) !important;
        border-color: #dc3545 !important; /* Brillo rojo corporativo */
    }

    /* Ajuste de proporción fija para que todas las imágenes del catálogo tengan el mismo tamaño */
    .img-catalogo-top {
        height: 160px;
        object-fit: cover; /* Recorta la imagen proporcionalmente para llenar el contenedor */
        width: 100%;
    }
</style>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-box-seam"></i> Catálogo de Productos</h1>
        <p class="text-muted small">Gestión centralizada de maquinaria, insumos oficiales y refacciones de la empresa.</p>
    </div>
    <div class="col-md-5 text-md-end">
        <a href="alta_producto.php" class="btn btn-danger py-2 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-plus-circle-fill me-2"></i> Registrar Nuevo Producto
        </a>
    </div>
</div>

<!-- Contenedor de las 4 Tarjetas de Categorías con Formato de Imagen Superior -->
<div class="row g-4 animate__animated animate__fadeInUp">
    
    <!-- Tarjeta 1: Máquinas -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo h-100 shadow-sm bg-white rounded border-top border-4 border-danger" onclick="location.href='lista_maquinas.php'">
            <img src="../img/maquinas.jpg" class="card-img-top img-catalogo-top" alt="Maquinaria DEMEX">
            <div class="card-body p-3">
                <span class="text-uppercase text-danger fw-bold tracking-wider mb-1 d-block" style="font-size: 0.75rem;">Equipos</span>
                <h5 class="fw-bold text-dark mb-1">Máquinas</h5>
                <p class="text-muted small mb-0">Líneas Demex y Spice de helado suave y duro.</p>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2: Bases para Helado -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo h-100 shadow-sm bg-white rounded border-top border-4 border-danger" onclick="location.href='lista_bases.php'">
            <img src="../img/bases.jpg" class="card-img-top img-catalogo-top" alt="Bases e Insumos">
            <div class="card-body p-3">
                <span class="text-uppercase text-danger fw-bold tracking-wider mb-1 d-block" style="font-size: 0.75rem;">Materia Prima</span>
                <h5 class="fw-bold text-dark mb-1">Bases para Helado</h5>
                <p class="text-muted small mb-0">Insumos base en bulto y fórmulas listas.</p>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3: Saborizantes -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo h-100 shadow-sm bg-white rounded border-top border-4 border-danger" onclick="location.href='lista_saborizantes.php'">
            <img src="../img/saborizantes.jpg" class="card-img-top img-catalogo-top" alt="Saborizantes Premium">
            <div class="card-body p-3">
                <span class="text-uppercase text-danger fw-bold tracking-wider mb-1 d-block" style="font-size: 0.75rem;">Materia Prima</span>
                <h5 class="fw-bold text-dark mb-1">Saborizantes</h5>
                <p class="text-muted small mb-0">Concentrados y veteados comerciales premium.</p>
            </div>
        </div>
    </div>

    <!-- Tarjeta 4: Refacciones -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo h-100 shadow-sm bg-white rounded border-top border-4 border-danger" onclick="location.href='lista_refacciones.php'">
            <img src="../img/refacciones.jpg" class="card-img-top img-catalogo-top" alt="Refacciones Técnicas">
            <div class="card-body p-3">
                <span class="text-uppercase text-danger fw-bold tracking-wider mb-1 d-block" style="font-size: 0.75rem;">Equipos</span>
                <h5 class="fw-bold text-dark mb-1">Refacciones</h5>
                <p class="text-muted small mb-0">Componentes, empaques y piezas.</p>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>