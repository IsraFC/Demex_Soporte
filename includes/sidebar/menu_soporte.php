<?php
/**
 * @file menu_soporte.php
 * @package Portal_Demex
 * @brief Sub-módulo modular para renderizar el menú de soporte en el sidebar.
 */
// INDICAMOS A PHP QUE JALE LAS VARIABLES GLOBALES DEL HEADER
global $link_prefix_soporte, $pagina_actual_php, $en_soporte; ?>
<div class="seccion-herramientas mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Soporte</span>
</div>

<a href="<?= $link_prefix_soporte ?>index.php" class="sidebar-link <?= ($pagina_actual_php === 'index.php' && $en_soporte) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-house-door"></i></div> <span>Inicio</span>
</a>

<a href="<?= $link_prefix_soporte ?>tickets_usuario.php" class="sidebar-link <?= ($pagina_actual_php === 'tickets_usuario.php' && $en_soporte) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-person-workspace"></i></div> <span>Mis Tickets</span>
</a>

<a href="<?= $link_prefix_soporte ?>maquinas.php" class="sidebar-link <?= ($pagina_actual_php === 'maquinas.php' && $en_soporte) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-cpu"></i></div> <span>Máquinas</span>
</a>
<a href="<?= $link_prefix_soporte ?>clientes.php" class="sidebar-link <?= ($pagina_actual_php === 'clientes.php' && $en_soporte) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-people"></i></div> <span>Clientes</span>
</a>
<a href="<?= $link_prefix_soporte ?>estadisticas.php" class="sidebar-link <?= ($pagina_actual_php === 'estadisticas.php' && $en_soporte) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-bar-chart-line"></i></div> <span>Estadísticas</span>
</a>

<div class="seccion-herramientas pt-3 mt-2 mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Herramientas</span>
</div>

<a href="<?= $link_prefix_soporte ?>importar_clientes.php" class="sidebar-link py-2 <?= ($pagina_actual_php === 'importar_clientes.php' && $en_soporte) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-person-plus"></i></div> <span>Imp. Clientes</span>
</a>
<a href="<?= $link_prefix_soporte ?>importar_tickets.php" class="sidebar-link py-2 <?= ($pagina_actual_php === 'importar_tickets.php' && $en_soporte) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-ticket-detailed"></i></div> <span>Imp. Tickets</span>
</a>