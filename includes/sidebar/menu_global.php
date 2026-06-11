<?php
/**
 * @file menu_global.php
 * @package Portal_Demex
 * @brief Sub-módulo modular para renderizar las herramientas globales y de administración en el sidebar.
 */
// INDICAMOS A PHP QUE JALE LAS VARIABLES GLOBALES DEL HEADER
global $staff_link, $logout_link, $pagina_actual_php, $en_subcarpeta;
?>

<div class="seccion-herramientas pt-3 mt-2 mx-3">
    <span class="text-uppercase text-light fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Global</span>
</div>
<a href="<?= $staff_link ?>" class="sidebar-link <?= (($pagina_actual_php === 'usuarios.php' || $pagina_actual_php === 'personal_staff.php') && !$en_subcarpeta) ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-shield-lock-fill"></i></div> <span>Personal Staff</span>
</a>
<a href="<?= $base_path ?>control_feedback.php" class="sidebar-link <?= ($pagina_actual_php === 'control_feedback.php') ? 'active-page no-anim' : '' ?>">
    <div class="sidebar-icon"><i class="bi bi-shield-check"></i></div> <span>Control de Calidad</span>
</a>