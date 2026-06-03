<?php
/**
 * @file menu_ventas.php
 * @package Portal_Demex
 * @brief Sub-módulo modular para renderizar el menú de ventas en el sidebar.
 */
// INDICAMOS A PHP QUE JALE LAS VARIABLES GLOBALES DEL HEADER
global $link_prefix, $pagina_actual_php, $en_subcarpeta;
?>
<div class="seccion-herramientas border-top border-secondary border-opacity-25 pt-3 mt-2 mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Ventas & CRM</span>
</div>

<!-- Usamos el prefijo de Isra y salimos una carpeta con ../ antes de entrar a Ventas -->
<a href="<?= $link_prefix ?>../Ventas/leads_crm.php" class="sidebar-link <?= ($pagina_actual_php === 'leads_crm.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-funnel"></i></div> <span>Bandeja de Leads</span>
</a>
<a href="<?= $link_prefix ?>../Ventas/cotizaciones.php" class="sidebar-link <?= ($pagina_actual_php === 'cotizaciones.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-file-earmark-pdf"></i></div> <span>Generar Cotización</span>
</a>

<div class="seccion-herramientas border-top border-secondary border-opacity-25 pt-3 mt-2 mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Analítica</span>
</div>

<a href="<?= $link_prefix ?>../Ventas/dashboard_marketing.php" class="sidebar-link <?= ($pagina_actual_php === 'dashboard_marketing.php' && $en_subcarpeta) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-pie-chart"></i></div> <span>Métricas Marketing</span>
</a>