<?php
/**
 * @file menu_ventas.php
 * @package Portal_Demex
 * @brief Sub-módulo modular para renderizar el menú de ventas en el sidebar.
 */
// INDICAMOS A PHP QUE JALE LAS VARIABLES GLOBALES DEL HEADER
global $link_prefix_ventas, $pagina_actual_php, $en_ventas;
?>
<div class="seccion-herramientas pt-3 mt-2 mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Ventas & CRM</span>
</div>

<a href="<?= $link_prefix_ventas ?>leads_crm.php" class="sidebar-link <?= ($pagina_actual_php === 'leads_crm.php' && $en_ventas) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-funnel"></i></div> <span>Prospectos</span>
</a>

<!-- NUEVA OPCIÓN: Integrada con la estructura nativa del sidebar-link -->
<a href="<?= $link_prefix_ventas ?>recompras_crm.php" class="sidebar-link <?= ($pagina_actual_php === 'recompras_crm.php' && $en_ventas) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-arrow-repeat"></i></div> <span>Recompras</span>
</a>
<a href="<?= $link_prefix_ventas ?>clientes.php" class="sidebar-link <?= ($pagina_actual_php === 'clientes.php' && $en_ventas) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-people-fill"></i></div> <span>Clientes</span>
</a>

<div class="seccion-herramientas pt-3 mt-2 mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Analítica</span>
</div>

<a href="<?= $link_prefix_ventas ?>dashboard_marketing.php" class="sidebar-link <?= ($pagina_actual_php === 'dashboard_marketing.php' && $en_ventas) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-pie-chart"></i></div> <span>Métricas Marketing</span>
</a>