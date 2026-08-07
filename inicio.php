<?php
/**
 * ARCHIVO: inicio.php
 * DESCRIPCIÓN: Pantalla de Bienvenida Principal del CRM/Portal DEMEX.
 * Hero Corporativo con logo estático firme, destello en bucle, estado en esquina y reloj ejecutivo.
 * @author Sergio Mauricio Campos Carranza
 * @project Portal DEMEX
 * @version 2.2 (Rediseño: Estado en esquina con indicador verde lento y reloj protagónico)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Inicio | Portal DEMEX";
$modulo_actual = 'inicio';

// Determinamos el saludo según la hora del día
date_default_timezone_set('America/Mexico_City');
$hora = intval(date('H'));
if ($hora >= 6 && $hora < 12) {
    $saludo = "¡Buenos días!";
} elseif ($hora >= 12 && $hora < 19) {
    $saludo = "¡Buenas tardes!";
} else {
    $saludo = "¡Buenas noches!";
}

$nombre_usuario = $_SESSION['usuario_nombre'] ?? $_SESSION['nombre'] ?? 'Usuario';
$mis_roles     = $_SESSION['roles'];

include 'includes/header.php';
?>

<style>
/* === CONTENEDOR HERO FULLSCREEN CORPORATIVO === */
.welcome-hero-fullscreen {
    min-height: calc(100vh - 110px);
    background: radial-gradient(circle at 50% 25%, #ffffff 0%, #f8f9fa 65%, #f1f3f5 100%);
    border-radius: 24px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
}

/* === GLOW AMBIENTAL ROJO DE FONDO === */
.welcome-hero-fullscreen::before {
    content: '';
    position: absolute;
    top: 25%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 450px;
    height: 450px;
    background: radial-gradient(circle, rgba(220, 53, 69, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

/* === MODERNA BARRA DE ESTADO EN ESQUINA SUPERIOR DERECHA === */
.top-status-bar {
    position: absolute;
    top: 20px;
    right: 25px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    border-radius: 12px;
    padding: 8px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10;
}

/* FOQUITO VERDE CON RESPIRACIÓN SUAVE (BREATHING DOT) */
.green-status-dot {
    width: 9px;
    height: 9px;
    background-color: #10b981;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    animation: breathGreen 3s ease-in-out infinite;
}

@keyframes breathGreen {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5);
    }
    50% {
        transform: scale(1.15);
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
    }
    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

.status-text {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.6px;
}

/* === LOGO FIRME === */
.logo-wrapper {
    position: relative;
    display: inline-block;
    padding: 10px;
}

.logo-hero-xl {
    max-width: 380px;
    width: 100%;
    height: auto;
    filter: drop-shadow(0px 8px 20px rgba(220, 53, 69, 0.12));
}

/* DESTELLO LUMINOSO EN BUCLE SOBRE EL LOGO */
.shimmer-effect {
    position: relative;
    overflow: hidden;
    display: inline-block;
    border-radius: 16px;
}

.shimmer-effect::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -150%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        60deg, 
        transparent 30%, 
        rgba(255, 255, 255, 0.85) 50%, 
        transparent 70%
    );
    transform: rotate(25deg);
    animation: destelloBucle 4s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

@keyframes destelloBucle {
    0% { left: -150%; }
    35% { left: 150%; }
    100% { left: 150%; }
}

/* === TIPOGRAFÍA CORPORATIVA === */
.greeting-title {
    font-size: 3.2rem;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.text-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* === WIDGET DE RELOJ EJECUTIVO (PROTAGONISTA) === */
.clock-widget-container {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
    border-radius: 20px;
    padding: 16px 38px;
    display: inline-flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease;
}

.clock-widget-container:hover {
    transform: translateY(-2px);
}

.clock-digits {
    font-family: 'Poppins', sans-serif;
    font-size: 2.3rem;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: 1px;
    line-height: 1;
}

.clock-ampm {
    font-size: 0.9rem;
    font-weight: 700;
    color: #dc3545;
    text-transform: uppercase;
}
</style>

<div class="container-fluid py-2">

    <!-- === HERO CENTRAL FULLSCREEN === -->
    <div class="welcome-hero-fullscreen p-4 p-md-5 text-center animate__animated animate__fadeIn">
        
        <!-- BARRA DE ESTADO ESQUINA SUPERIOR DERECHA (NUEVO DISEÑO ELEGANTE) -->
        <div class="top-status-bar animate__animated animate__fadeInDown">
            <div class="d-flex align-items-center gap-2">
                <span class="green-status-dot"></span>
                <span class="status-text">Sesión Activa</span>
            </div>
            <div class="vr bg-secondary opacity-25" style="height: 14px;"></div>
            <div class="d-flex align-items-center gap-1.5">
                <i class="bi bi-shield-lock-fill text-danger me-1" style="font-size: 0.85rem;"></i>
                <span class="status-text"><?= htmlspecialchars(implode(' | ', $mis_roles)) ?></span>
            </div>
        </div>

        <div class="row justify-content-center align-items-center w-100 position-relative" style="z-index: 2;">
            <div class="col-12 col-lg-10 col-xl-8">
                
                <!-- Logo Firme con Destello Metálico -->
                <div class="shimmer-effect logo-wrapper mb-3">
                    <img src="img/logo_demex_rj.png" alt="Desarrollo Mexicano DEMEX" class="logo-hero-xl img-fluid">
                </div>

                <!-- Saludo Principal -->
                <h1 class="greeting-title text-dark mb-2">
                    <?= $saludo ?> <span class="text-gradient-danger"><?= htmlspecialchars($nombre_usuario) ?></span>
                </h1>
                
                <p class="text-muted fs-6 mb-4 mx-auto" style="max-width: 600px; line-height: 1.6;">
                    Portal Corporativo Central de Gestión Comercial y Soporte Técnico <strong class="text-dark">DEMEX</strong>.
                </p>

                <!-- Widget del Reloj Digital Ejecutivo (Protagonista) -->
                <div class="mt-2">
                    <div class="clock-widget-container">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock-fill fs-3 text-danger"></i>
                        </div>
                        <div class="text-start">
                            <small class="d-block text-uppercase fw-bold text-muted" style="font-size: 0.65rem; letter-spacing: 1px;">Hora Local Servidor</small>
                            <div class="d-flex align-items-baseline gap-1">
                                <span id="liveClockDigits" class="clock-digits">--:--:--</span>
                                <span id="liveClockAmPm" class="clock-ampm">--</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- Script del Reloj Digital Formato 12 Horas con AM/PM -->
<script>
function actualizarReloj() {
    const ahora = new Date();
    let horas = ahora.getHours();
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');
    
    const ampm = horas >= 12 ? 'P.M.' : 'A.M.';
    horas = horas % 12;
    horas = horas ? horas : 12; // La hora '0' se muestra como '12'
    const horasStr = String(horas).padStart(2, '0');
    
    const elemDigits = document.getElementById('liveClockDigits');
    const elemAmPm = document.getElementById('liveClockAmPm');
    
    if (elemDigits && elemAmPm) {
        elemDigits.textContent = `${horasStr}:${minutos}:${segundos}`;
        elemAmPm.textContent = ampm;
    }
}
setInterval(actualizarReloj, 1000);
actualizarReloj();
</script>

<?php include 'includes/footer.php'; ?>