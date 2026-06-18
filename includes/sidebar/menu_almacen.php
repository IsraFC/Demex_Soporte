<?php
/**
 * @file menu_almacen.php
 * @package Portal_Demex
 * @brief Sub-módulo modular para renderizar el menú de Almacén en el sidebar.
 */
// Indicamos a PHP que jale las variables de enrutamiento del header
global $link_prefix_almacen, $pagina_actual_php, $en_almacen; ?>

<div class="seccion-herramientas pt-2 mt-1 mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Almacén</span>
</div>

<a href="<?= $link_prefix_almacen ?>index.php" class="sidebar-link <?= ($pagina_actual_php === 'index.php' && $en_almacen) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-boxes"></i></div> <span>Control Lotes</span>
</a>

<a href="<?= $link_prefix_almacen ?>indicadores.php" class="sidebar-link <?= ($pagina_actual_php === 'indicadores.php' && $en_almacen) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-graph-up-arrow"></i></div> <span>Indicadores</span>
</a>