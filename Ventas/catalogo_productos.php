<?php
/**
 * ARCHIVO: Ventas/catalogo_productos.php
 * DESCRIPCIÓN: Panel Central del Catálogo de Productos DEMEX.
 * Muestra las 4 categorías comerciales con métricas dinámicas en tiempo real y UI corporativa ultra redondeada.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 2.3 (Diseño Redondeado Premium + Conteo de Productos Asíncrono/SQL)
 */

$page_title = "Catálogo de Productos | CRM Ventas";
require_once '../config/db.php';

// Consultas rápidas para contar el total de productos activos por categoría
$totales = [
    'maquinas'     => $pdo->query("SELECT COUNT(*) FROM productos WHERE id_categoria = 1")->fetchColumn() ?: 0,
    'bases'        => $pdo->query("SELECT COUNT(*) FROM productos WHERE id_categoria = 2")->fetchColumn() ?: 0,
    'saborizantes' => $pdo->query("SELECT COUNT(*) FROM productos WHERE id_categoria = 3")->fetchColumn() ?: 0,
    'refacciones'  => $pdo->query("SELECT COUNT(*) FROM productos WHERE id_categoria = 4")->fetchColumn() ?: 0,
];

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<style>
        /* === BOTÓN PRINCIPAL REDONDEADITO Y CORPORATIVO (SIN SUBRAYADO) === */
        .btn-demex-pill {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            color: #ffffff !important;
            font-weight: 700;
            border: none;
            border-radius: 50px;
            padding: 10px 28px;
            box-shadow: 0 8px 18px rgba(220, 53, 69, 0.25);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.92rem;
            text-decoration: none !important; /* <--- QUITA EL SUBRAYADO */
        }

        /* Forzamos a que el span y cualquier estado no muestren subrayado */
        .btn-demex-pill, 
        .btn-demex-pill:hover, 
        .btn-demex-pill:focus, 
        .btn-demex-pill span {
            text-decoration: none !important;
            color: #ffffff !important;
        }

        .btn-demex-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 22px rgba(220, 53, 69, 0.38);
            background: linear-gradient(135deg, #e34353 0%, #bd2c3a 100%);
        }

        .btn-demex-pill i {
            transition: transform 0.3s ease;
        }

        .btn-demex-pill:hover i {
            transform: rotate(90deg);
        }

    /* === TARJETAS DE CATÁLOGO MODERNAS === */
    .card-catalogo-modern {
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        border-radius: 20px !important;
        background: #ffffff;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
    }

    .card-catalogo-modern:hover {
        transform: translateY(-8px);
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.08) !important;
        border-color: rgba(220, 53, 69, 0.3) !important;
    }

    /* CONTENEDOR DE IMAGEN CON ZOOM AL HOVER */
    .img-catalogo-wrapper {
        height: 175px;
        overflow: hidden;
        position: relative;
        background-color: #f8f9fa;
    }

    .img-catalogo-top {
        height: 100%;
        width: 100%;
        object-fit: cover;
        transition: transform 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .card-catalogo-modern:hover .img-catalogo-top {
        transform: scale(1.08);
    }

    /* BADGE FLOTANTE DE CATEGORÍA SOBRE LA IMAGEN */
    .category-badge-floating {
        position: absolute;
        top: 12px;
        left: 12px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(8px);
        color: #dc3545;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 5px 14px;
        border-radius: 30px;
        border: 1px solid rgba(220, 53, 69, 0.15);
        box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    }

    /* METRICA EN VIVO DE PRODUCTOS */
    .badge-metric-count {
        background: #f8f9fa;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 6px;
        display: inline-block;
    }

    /* ACCIÓN FLECHA EN EL FOOTER DE LA TARJETA */
    .card-action-link {
        font-size: 0.82rem;
        font-weight: 700;
        color: #dc3545;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: gap 0.3s ease;
    }

    .card-catalogo-modern:hover .card-action-link {
        gap: 10px;
    }
</style>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-7">
        <!-- Manteniendo el color rojo original del título -->
        <h1 class="fw-bold text-danger mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-box-seam-fill"></i> Catálogo de Productos
        </h1>
        <p class="text-muted small mb-0">Gestión centralizada de maquinaria, insumos oficiales y refacciones de la empresa.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <!-- Botón completamente redondeado -->
        <a href="alta_producto.php" class="btn-demex-pill shadow-sm">
            <i class="bi bi-plus-lg"></i>
            <span>Registrar Nuevo Producto</span>
        </a>
    </div>
</div>

<!-- Contenedor de las 4 Tarjetas de Categorías -->
<div class="row g-4 animate__animated animate__fadeInUp">
    
    <!-- Tarjeta 1: Máquinas -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo-modern h-100 shadow-sm" onclick="location.href='lista_maquinas.php'">
            <div class="img-catalogo-wrapper">
                <span class="category-badge-floating"><i class="bi bi-cpu-fill me-1"></i> Equipos</span>
                <img src="../img/maquinas.jpg" class="img-catalogo-top" alt="Maquinaria DEMEX">
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="fw-bold text-dark mb-0">Máquinas</h5>
                        <span class="badge-metric-count"><?= $totales['maquinas'] ?> Eq</span>
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">Líneas Demex y Spice de helado suave y duro.</p>
                </div>
                <div class="card-action-link border-top pt-2 mt-auto">
                    <span>Explorar Equipos</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2: Bases para Helado -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo-modern h-100 shadow-sm" onclick="location.href='lista_bases.php'">
            <div class="img-catalogo-wrapper">
                <span class="category-badge-floating"><i class="bi bi-moisture me-1"></i> Materia Prima</span>
                <img src="../img/bases.jpg" class="img-catalogo-top" alt="Bases e Insumos">
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="fw-bold text-dark mb-0">Bases para Helado</h5>
                        <span class="badge-metric-count"><?= $totales['bases'] ?> Ins</span>
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">Insumos base en bulto y fórmulas listas para producción.</p>
                </div>
                <div class="card-action-link border-top pt-2 mt-auto">
                    <span>Ver Fórmulas e Insumos</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3: Saborizantes -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo-modern h-100 shadow-sm" onclick="location.href='lista_saborizantes.php'">
            <div class="img-catalogo-wrapper">
                <span class="category-badge-floating"><i class="bi bi-funnel-fill me-1"></i> Materia Prima</span>
                <img src="../img/saborizantes.jpg" class="img-catalogo-top" alt="Saborizantes Premium">
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="fw-bold text-dark mb-0">Saborizantes</h5>
                        <span class="badge-metric-count"><?= $totales['saborizantes'] ?> Sab</span>
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">Concentrados de fruta y veteados comerciales premium.</p>
                </div>
                <div class="card-action-link border-top pt-2 mt-auto">
                    <span>Ver Catálogo de Sabores</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 4: Refacciones -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-catalogo-modern h-100 shadow-sm" onclick="location.href='lista_refacciones.php'">
            <div class="img-catalogo-wrapper">
                <span class="category-badge-floating"><i class="bi bi-tools me-1"></i> Equipos</span>
                <img src="../img/refacciones.jpg" class="img-catalogo-top" alt="Refacciones Técnicas">
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="fw-bold text-dark mb-0">Refacciones</h5>
                        <span class="badge-metric-count"><?= $totales['refacciones'] ?> Pzs</span>
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">Componentes mecánicos, empaques y piezas de repuesto.</p>
                </div>
                <div class="card-action-link border-top pt-2 mt-auto">
                    <span>Consultar Piezas</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>