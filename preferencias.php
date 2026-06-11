<?php
/**
 * ARCHIVO: preferencias.php
 * DESCRIPCIÓN: Panel de personalización de entorno, esquema de color y accesibilidad.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.3 (Diseño y Lógica Unificada de Entorno)
 */

require_once 'config/db.php';
$page_title = "Preferencias del Entorno";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guardián de seguridad básico: Debe estar autenticado en el portal
if (!isset($_SESSION['roles'])) {
    header("Location: login.php");
    exit();
}

$modulo_actual = 'global'; // Estética premium neutral de la raíz
include 'includes/header.php';
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-md-8 col-lg-7">
        
        <div class="card border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #ffffff;">
            
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-3">
                <div class="shadow-sm d-flex align-items-center justify-content-center" 
                     style="width: 48px; height: 48px; background-color: #fff5f5; border-radius: 16px;">
                    <i class="bi bi-sliders text-danger fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-family: 'Poppins', sans-serif;">Preferencias del Portal</h4>
                    <p class="text-muted small mb-0">Personaliza la interfaz de datos a tu estilo visual y ergonómico.</p>
                </div>
            </div>

            <div class="card-body p-4">
                
                <div class="mb-4 p-3 bg-light" style="border-radius: 18px;">
                    <div class="row align-items-center g-3">
                        <div class="col-sm-8 d-flex align-items-start gap-3">
                            <span class="fs-4 text-danger mt-1"><i class="bi bi-palette"></i></span>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Esquema de Color / Tema</h6>
                                <p class="text-muted small mb-0" style="font-size: 12px;">Elige tu preferencia visual para el entorno o permite que el portal se acople a la configuración nativa de tu dispositivo.</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <select id="prefTemaVisual" class="form-select border-0 bg-white fw-semibold text-secondary shadow-sm rounded-pill px-3 py-2" style="font-size: 13px; cursor: pointer;">
                                <option value="claro">Claro</option>
                                <option value="oscuro">Oscuro</option>
                                <option value="sistema">Igual que el sistema</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-4 p-3 bg-light" style="border-radius: 18px;">
                    <div class="row align-items-center g-3">
                        <div class="col-sm-8 d-flex align-items-start gap-3">
                            <span class="fs-4 text-danger mt-1"><i class="bi bi-type-strikethrough"></i></span>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Densidad y Tamaño de Texto</h6>
                                <p class="text-muted small mb-0" style="font-size: 12px;">Ajusta la escala tipográfica de los formularios y las filas del DataTables para mejorar la visibilidad o compactar datos.</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <select id="prefTamañoTexto" class="form-select border-0 bg-white fw-semibold text-secondary shadow-sm rounded-pill px-3 py-2" style="font-size: 13px; cursor: pointer;">
                                <option value="compacto">Compacto (Líneas juntas)</option>
                                <option value="normal">Normal (Por Defecto)</option>
                                <option value="ampliado">Ampliado (Lectura fácil)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-4 p-3 bg-light" style="border-radius: 18px;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-start gap-3">
                            <span class="fs-4 text-danger mt-1"><i class="bi bi-volume-up"></i></span>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Efectos de Sonido del Sistema</h6>
                                <p class="text-muted small mb-0" style="font-size: 12px;">Habilitar alertas sonoras cortas y sutiles al confirmar operaciones exitosas o registrar advertencias.</p>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0 pb-1">
                            <input class="form-check-input style-switch-danger" type="checkbox" id="prefAlertasSonido" style="transform: scale(1.2); cursor: pointer;">
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border-0 small text-secondary p-3 d-flex align-items-start gap-2 mb-0" style="border-radius: 14px; background-color: #fcfcfc;">
                    <i class="bi bi-info-circle text-danger fs-6 mt-0.5"></i>
                    <span>Cualquier cambio seleccionado se aplicará de forma reactiva e instantánea en la interfaz de navegación, guardándose localmente en el perfil actual.</span>
                </div>

            </div>
            
            <div class="card-footer border-0 bg-light px-4 py-3 d-flex align-items-center justify-content-between" style="border-radius: 0 0 24px 24px;">
                <a href="<?= $base_path ?>index.php" class="btn btn-light btn-sm rounded-pill px-4 py-2 fw-bold text-secondary border shadow-sm" style="font-size: 13px;">
                    <i class="bi bi-arrow-left-circle me-1.5 text-danger"></i> Regresar al Inicio
                </a>

                <button type="button" id="btnRestablecerPref" class="btn btn-outline-secondary btn-sm rounded-pill px-4 py-2 fw-bold bg-white shadow-sm" style="font-size: 13px;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restablecer por Defecto
                </button>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 🎯 Captura de los elementos de la interfaz
    const selectTema = document.getElementById('prefTemaVisual');
    const selectTexto = document.getElementById('prefTamañoTexto');
    const swSonido = document.getElementById('prefAlertasSonido');
    const btnRestablecer = document.getElementById('btnRestablecerPref');

    // =================================================================
    // 1. CARGAR PREFERENCIAS GUARDADAS AL ABRIR LA PÁGINA
    // =================================================================
    
    // Configuración del Tema (Por defecto: claro)
    const temaGuardado = localStorage.getItem('demex_user_theme_override') || 'claro';
    selectTema.value = temaGuardado;

    // Configuración del Tamaño de Texto (Por defecto: normal)
    const textoGuardado = localStorage.getItem('demex_user_font_size') || 'normal';
    selectTexto.value = textoGuardado;

    // Configuración de Sonido (Por defecto: false / desactivado)
    const sonidoGuardado = localStorage.getItem('demex_audio_alerts') === 'true';
    swSonido.checked = sonidoGuardado;


    // =================================================================
    // 2. ESCUCHAR LOS CAMBIOS EN CALIENTE (REACTIVIDAD)
    // =================================================================

    // Cambio de Tema
    selectTema.addEventListener('change', function() {
        const valor = this.value;
        localStorage.setItem('demex_user_theme_override', valor);
        
        // Aplicamos el atributo en el body en caliente para ver el cambio instantáneo
        if (valor === 'sistema') {
            const mql = window.matchMedia('(prefers-color-scheme: dark)');
            document.body.setAttribute('data-theme', mql.matches ? 'oscuro' : 'claro');
        } else {
            document.body.setAttribute('data-theme', valor);
        }

        notificarCambio('Esquema de color actualizado');
    });

    // Cambio de Tamaño de Texto
    selectTexto.addEventListener('change', function() {
        const valor = this.value;
        localStorage.setItem('demex_user_font_size', valor);
        
        // Remover escalas viejas y aplicar la nueva en el body
        document.body.classList.remove('font-compact', 'font-ampliado');
        if (valor === 'compacto') document.body.classList.add('font-compact');
        if (valor === 'ampliado') document.body.classList.add('font-ampliado');

        notificarCambio('Escala tipográfica actualizada');
    });

    // Cambio de Switch de Sonido
    swSonido.addEventListener('change', function() {
        localStorage.setItem('demex_audio_alerts', this.checked ? 'true' : 'false');
        notificarCambio(this.checked ? 'Alertas sonoras activadas' : 'Alertas sonoras desactivadas');
    });


    // =================================================================
    // 3. MOTOR DEL BOTÓN RESTABLECER POR DEFECTO
    // =================================================================
    btnRestablecer.addEventListener('click', function() {
        // Forzar los valores por defecto requeridos en el LocalStorage
        localStorage.setItem('demex_user_theme_override', 'claro');
        localStorage.setItem('demex_user_font_size', 'normal');
        localStorage.setItem('demex_audio_alerts', 'false');

        // Sincronizar los controles visuales (Selects y Switches)
        selectTema.value = 'claro';
        selectTexto.value = 'normal';
        swSonido.checked = false;

        // Reestablecer los atributos del body en tiempo real
        document.body.setAttribute('data-theme', 'claro');
        document.body.classList.remove('font-compact', 'font-ampliado');

        // Alerta formal de SweetAlert2 confirmando el reset de fábrica
        Swal.fire({
            icon: 'success',
            title: 'Valores Restablecidos',
            text: 'La configuración de la interfaz ha regresado a los valores estándar del portal.',
            timer: 2000,
            showConfirmButton: false
        });
    });

    // Función auxiliar para lanzar Toasts sutiles en la esquina superior derecha
    function notificarCambio(mensaje) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: mensaje,
            showConfirmButton: false,
            timer: 1500
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>