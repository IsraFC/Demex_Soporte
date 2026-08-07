<?php
/**
 * @file menu_inicio.php
 * @package Portal_Demex
 * @brief Sub-módulo modular para renderizar el botón de Inicio en la parte superior del sidebar.
 */
global $pagina_actual_php, $base_path;
?>

<div class="seccion-herramientas pt-3 mt-2 mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Navegación</span>
</div>

<a href="<?= $base_path ?>inicio.php" class="sidebar-link <?= ($pagina_actual_php === 'inicio.php') ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-house-door-fill"></i></div> <span>Inicio</span>
</a>