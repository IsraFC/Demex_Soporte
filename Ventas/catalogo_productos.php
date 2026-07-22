<?php
/**
 * ARCHIVO: Ventas/catalogo_productos.php
 * DESCRIPCIÓN: Panel Central del Catálogo de Productos DEMEX.
 * Muestra las 4 categorías comerciales con tarjetas animadas y acceso al alta centralizada.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 (Diseño unificado y adaptado al CRM)
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
    }
    
    .card-catalogo:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.13) !important;
        border-color: #dc3545 !important; /* Brillo rojo sutil al pasar el cursor */
    }

    .icon-box-avatar {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
</style>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-box-seam"></i> Catálogo Global de Productos</h1>
        <p class="text-muted small">Gestión centralizada de maquinaria, insumos oficiales y refacciones de la empresa.</p>
    </div>
    <div class="col-md-5 text-md-end">
        <!-- Botón que lleva a la página de registro reactivo -->
        <a href="alta_producto.php" class="btn btn-danger py-2 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-plus-circle-fill me-2"></i> Registrar Nuevo Producto
        </a>
    </div>
</div>

<!-- Contenedor de las 4 Tarjetas de Categorías -->
<div class="row g-4 animate__animated animate__fadeInUp">
    
    <!-- Tarjeta 1: Máquinas -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo h-100 p-3 shadow-sm bg-white rounded border-top border-4 border-danger" onclick="location.href='lista_maquinas.php'">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center">
                <div class="icon-box-avatar bg-danger bg-opacity-10 text-danger mb-3">
                    <i class="bi bi-cpu fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Máquinas</h5>
                <p class="text-muted small mb-0">Líneas Demex y Spice de helado suave y duro.</p>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2: Bases para Helado -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo h-100 p-3 shadow-sm bg-white rounded border-top border-4 border-danger" onclick="location.href='lista_bases.php'">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center">
                <div class="icon-box-avatar bg-danger bg-opacity-10 text-danger mb-3">
                    <i class="bi bi-moisture fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Bases para Helado</h5>
                <p class="text-muted small mb-0">Insumos base en bulto y fórmulas listas.</p>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3: Saborizantes -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo h-100 p-3 shadow-sm bg-white rounded border-top border-4 border-danger" onclick="location.href='lista_saborizantes.php'">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center">
                <div class="icon-box-avatar bg-danger bg-opacity-10 text-danger mb-3">
                    <i class="bi bi-funnel-fill fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Saborizantes</h5>
                <p class="text-muted small mb-0">Concentrados y veteados comerciales premium.</p>
            </div>
        </div>
    </div>

    <!-- Tarjeta 4: Refacciones -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo h-100 p-3 shadow-sm bg-white rounded border-top border-4 border-danger" onclick="location.href='lista_refacciones.php'">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center">
                <div class="icon-box-avatar bg-danger bg-opacity-10 text-danger mb-3">
                    <i class="bi bi-nut fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Refacciones</h5>
                <p class="text-muted small mb-0">Componentes críticos, empaques y navajas.</p>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>