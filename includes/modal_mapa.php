<?php
/**
 * ARCHIVO: includes/modal_mapa.php
 * DESCRIPCIÓN: Componente modular e independiente del modal de geolocalización.
 * Diseñado para ser incluido dinámicamente en cualquier módulo del portal.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.0
 */
?>
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold" id="mapTitle">Ubicación Geográfica</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light" style="height: 450px;">
                <iframe id="mapIframe" width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
            <div class="modal-footer bg-white border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                    <span id="mapAddressText"></span>
                </div>
                <a href="#" id="btnGoogleMaps" target="_blank" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-google me-2"></i>Ver en Google Maps
                </a>
            </div>
        </div>
    </div>
</div>