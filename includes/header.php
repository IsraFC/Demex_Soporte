<?php
/**
 * @file header.php
 * @package Portal_Demex
 * @version 5.3 - Arquitectura Adaptativa con Control Cinemático de Contexto (Soporte)
 * @brief Layout maestro centralizado con ruteo inteligente y validación de visibilidad de tickets.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * FUNCIÓN GLOBAL DE CONTROL DE ACCESOS
 * Evalúa si el usuario logueado cuenta con al menos uno de los roles autorizados.
 * Respeta de forma estricta las mayúsculas iniciales fijadas en el catálogo de la BD.
 */
function tieneAcceso($roles_permitidos) {
    if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles'])) {
        return false;
    }
    foreach ($roles_permitidos as $rol) {
        if (in_array($rol, $_SESSION['roles'])) {
            return true; // Acceso concedido con una coincidencia
        }
    }
    return false;
}

/* 1. SEGURIDAD CENTRALIZADA Y CONTROL DE ACCESO MULTI-ROL */
$url_actual = $_SERVER['PHP_SELF'];
// Usamos SCRIPT_NAME para obtener el nombre físico del archivo real en ejecución
$pagina_actual_php = basename($_SERVER['SCRIPT_NAME']);

$en_soporte = (strpos($url_actual, '/Soporte/') !== false);
$en_ventas = (strpos($url_actual, '/Ventas/') !== false);
$en_subcarpeta = ($en_soporte || $en_ventas);

// Lista de páginas públicas que bajo ninguna circunstancia deben validar sesión o redirigir
$paginas_publicas = ['login.php', 'recuperar.php', 'restablecer.php', 'verificar.php'];

// Si la página actual no es pública, aplicamos el guardián de seguridad
if (!in_array($pagina_actual_php, $paginas_publicas)) {
    if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles']) || !tieneAcceso(['Administrador', 'Soporte', 'Ventas', 'Cliente'])) {
        
        // Calculamos la ruta de escape al login de forma exacta basándonos en la profundidad del archivo
        $regreso_login = $en_subcarpeta ? '../' : './';
        
        // Destruimos cualquier posible residuo de sesión inválida para limpiar el estado del navegador
        session_unset();
        
        header("Location: " . $regreso_login . "login.php?error=no_autorizado");
        exit();
    }
}

/* 2. GUARDIÁN DE INACTIVIDAD AUTOMÁTICO (15 Minutos) */
$tiempo_maximo_inactividad = 900;
if (isset($_SESSION['ultima_actividad']) && !in_array($pagina_actual_php, $paginas_publicas)) {
    $tiempo_inactivo = time() - $_SESSION['ultima_actividad'];
    if ($tiempo_inactivo > $tiempo_maximo_inactividad) {
        session_unset();
        session_destroy();
        $regreso_login = $en_subcarpeta ? '../' : './';
        header("Location: " . $regreso_login . "login.php?error=sesion_expirada");
        exit();
    }
}
// Solo actualizamos la actividad si el usuario ya está autenticado en el sistema protegido
if (isset($_SESSION['roles'])) {
    $_SESSION['ultima_actividad'] = time();
}

/* 3. RESPALDO SILENCIOSO DE SEGURIDAD Y CARGA CACHÉ DE AVATAR */
require_once __DIR__ . '/../config/backup.php';

// Si el usuario está logueado pero su sesión de foto no se ha definido, la consultamos una sola vez como caché
if (isset($_SESSION['id_usuario']) && !isset($_SESSION['foto_perfil_base64']) && isset($pdo)) {
    try {
        $stmtFoto = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id_usuario = ? LIMIT 1");
        $stmtFoto->execute([$_SESSION['id_usuario']]);
        $resFoto = $stmtFoto->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($resFoto['foto_perfil'])) {
            $_SESSION['foto_perfil_base64'] = base64_encode($resFoto['foto_perfil']);
        } else {
            $_SESSION['foto_perfil_base64'] = '';
        }
    } catch (Exception $e) {
        $_SESSION['foto_perfil_base64'] = '';
    }
}

if (isset($pdo)) {
    ejecutarRespaldoSilencioso($pdo);
}

/* 4. MOTOR DE ENRUTAMIENTO GEOMÉTRICO (PREFIJOS INDEPENDIENTES) */
if ($en_subcarpeta) {
    $base_path = "../";
    $staff_link = "../usuarios.php";
    $logout_link = "../logout.php";
    
    if ($en_ventas) {
        $link_prefix_soporte = "../Soporte/";
        $link_prefix_ventas  = "./";
    } else {
        $link_prefix_soporte = "./";
        $link_prefix_ventas  = "../Ventas/";
    }
} else {
    $base_path = "./";
    $link_prefix_soporte = "Soporte/";
    $link_prefix_ventas  = "Ventas/";
    $staff_link = "usuarios.php";
    $logout_link = "logout.php";
}

if (!isset($modulo_actual)) {
    if (strpos($url_actual, '/Soporte/') !== false) {
        $tema_sistema = 'soporte';
    } elseif (strpos($url_actual, '/Ventas/') !== false) {
        $tema_sistema = 'ventas';
    } else {
        $tema_sistema = 'global';
    }
} else {
    $tema_sistema = $modulo_actual;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEMEX | <?= htmlspecialchars($page_title ?? 'Panel de Control') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <link rel="stylesheet" href="/desarrollo_mexicano/css/estilos.css">

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-theme="<?= $tema_sistema ?>">

<script>
    (function() {
        const temaAnterior = localStorage.getItem('demex_last_module_theme');
        const temaNuevo = '<?= $tema_sistema ?>';
        
        if (temaAnterior && temaAnterior !== temaNuevo && temaAnterior !== 'global') {
            document.body.setAttribute('data-theme', temaAnterior);
            window.requestAnimationFrame(() => {
                document.body.setAttribute('data-theme', temaNuevo);
            });
        }
        localStorage.setItem('demex_last_module_theme', temaNuevo);
    })();
</script>

<div id="wrapper" class="shadow-sm">
    <script>
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.getElementById('wrapper').classList.add('toggled');
        }
    </script>
    <div id="sidebar-wrapper" class="shadow">
        <div class="sidebar-heading">
            <div class="brand-container">
                <img src="<?= $base_path ?>img/logo_demex.png" alt="DEMEX" width="160" class="filter-drop-shadow">
            </div>
            <button class="btn-sidebar-trigger" id="menu-toggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
        
        <div class="sidebar-menu-scroll">
            <div class="list-group list-group-flush mt-2" id="sidebar-menu-list">

                <?php if (tieneAcceso(['Administrador', 'Soporte'])): ?>
                    <?php include __DIR__ . '/sidebar/menu_soporte.php'; ?>
                <?php endif; ?>

                <?php if (tieneAcceso(['Administrador', 'Ventas'])): ?>
                    <?php include __DIR__ . '/sidebar/menu_ventas.php'; ?>
                <?php endif; ?>
                
                <?php if (tieneAcceso(['Administrador'])): ?>
                    <?php include __DIR__ . '/sidebar/menu_global.php'; ?>
                <?php endif; ?>
                
            </div>
        </div> 
    </div>
    <div id="page-content-wrapper">
        <nav class="navbar top-navbar d-flex align-items-center justify-content-between shadow-sm" id="main-top-navbar">
            <div></div>
            <div class="d-flex align-items-center gap-3">
                
                <?php if (tieneAcceso(['Administrador', 'Soporte']) && isset($en_soporte) && $en_soporte): ?>
                    <button class="btn btn-ticket-premium shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoTicket">
                        <i class="bi bi-plus-circle-fill"></i> NUEVO TICKET
                    </button>
                <?php endif; ?>

                <div class="vr bg-white opacity-25" style="height: 24px;"></div>

                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        
                        <?php if (!empty($_SESSION['foto_perfil_base64'])): ?>
                            <img src="data:image/jpeg;base64,<?= $_SESSION['foto_perfil_base64'] ?>" 
                                 alt="Avatar" 
                                 class="rounded-circle shadow-sm object-fit-cover" 
                                 style="width: 36px; height: 36px;">
                        <?php else: ?>
                            <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 14px;">
                                <?= isset($_SESSION['nombre']) ? strtoupper(substr($_SESSION['nombre'], 0, 1)) : 'U' ?>
                            </div>
                        <?php endif; ?>

                        <span class="ms-2 d-none d-sm-inline small fw-semibold text-white">
                            <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom p-2 mt-2 shadow border-0" aria-labelledby="profileDropdown" style="min-width: 220px;">
                        <li>
                            <div class="dropdown-header text-start py-1">
                                <span class="d-block text-dark fw-bold small lh-sm"><?= htmlspecialchars(($_SESSION['nombre'] ?? 'Usuario') . ' ' . ($_SESSION['apellidos'] ?? '')) ?></span>
                                <span class="d-block text-muted text-truncate" style="font-size: 11px;"><?= htmlspecialchars($_SESSION['correo'] ?? '') ?></span>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-uppercase mt-1" style="font-size: 9px;">
                                    <?= htmlspecialchars(implode(', ', $_SESSION['roles'] ?? ['Soporte'])) ?>
                                </span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <li>
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center small py-2" href="<?= $base_path ?>perfil.php">
                                <i class="bi bi-person-gear me-2 fs-6 text-secondary"></i> Mi Perfil
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center small py-2" href="<?= $base_path ?>tickets_usuario.php">
                                <i class="bi bi-ticket-detailed me-2 fs-6 text-secondary"></i> Mis Tickets
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center small py-2" href="#" data-bs-toggle="modal" data-bs-target="#modalCambiarPasswordGlobal">
                                <i class="bi bi-key me-2 fs-6 text-secondary"></i> Cambiar Contraseña
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item dropdown-item-custom d-flex align-items-center text-danger small py-2" href="<?= $logout_link ?>">
                                <i class="bi bi-box-arrow-right me-2 fs-6"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const sidebarScroll = document.querySelector('.sidebar-menu-scroll');
                
                if (sidebarScroll) {
                    const posicionGuardada = localStorage.getItem('sidebar_scroll_position');
                    if (posicionGuardada) {
                        sidebarScroll.scrollTop = posicionGuardada;
                    }

                    sidebarScroll.addEventListener('scroll', function() {
                        localStorage.setItem('sidebar_scroll_position', sidebarScroll.scrollTop);
                    });
                }
            });
        </script>

        <div class="container-fluid px-4 py-4 flex-grow-1 page-fade-wrapper" id="master-fade-container">