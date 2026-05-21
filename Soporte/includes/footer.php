<?php
/**
 * ARCHIVO: includes/footer.php
 * DESCRIPCIÓN: Cierre de interfaz, modales unificados y lógica cinemática sincronizada v2.7.
 * @author Israel Fernández Carrera & Mauricio
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 2.7
 * @date 2026-05-21
 */
?>
        </div> <footer class="py-4 mt-auto" style="background: transparent;">
            <div class="container-fluid px-4">
                <div class="row align-items-center border-top border-secondary border-opacity-10 pt-3">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0 text-muted small">
                            &copy; <?= date('Y') ?> <strong>Desarrollo Mexicano S.A. de C.V.</strong> 
                            <span class="mx-2 text-danger">|</span> Departamento de Sistemas
                        </p>
                    </div>
                    <div class="col-md-6 text-center text-md-end d-none d-md-block">
                        <img src="img/logo_demex.png" 
                             alt="Logo DEMEX" 
                             width="90" 
                             style="opacity: 0.25; filter: grayscale(100%);">
                    </div>
                </div>
            </div>
        </footer>

    </div> </div> <div class="modal fade" id="modalNuevoTicket" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Registrar Nuevo Ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-pills nav-fill mb-4 bg-light rounded-pill p-1" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active rounded-pill small fw-bold" data-bs-toggle="pill" data-bs-target="#busquedaSerie">POR SERIE</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill small fw-bold" data-bs-toggle="pill" data-bs-target="#busquedaCliente">POR CLIENTE</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="busquedaSerie">
                        <label class="form-label fw-bold small text-muted text-uppercase">Número de Serie</label>
                        <div class="input-group input-group-lg mb-3">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-upc-scan text-danger"></i></span>
                            <input type="text" id="inputBusquedaSerie" class="form-control bg-light border-0 fw-bold" placeholder="Escriba la serie..." autocomplete="off">
                        </div>
                    </div>

                    <div class="tab-pane fade" id="busquedaCliente">
                        <label class="form-label fw-bold small text-muted text-uppercase">Nombre del Cliente / Empresa</label>
                        <div class="input-group input-group-lg mb-2">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-person-search text-danger"></i></span>
                            <input type="text" id="inputBusquedaCliente" list="listaClientesBusqueda" class="form-control bg-light border-0 fw-bold" placeholder="Buscar cliente...">
                            <datalist id="listaClientesBusqueda">
                                <?php
                                $stmt_c = $pdo->query("SELECT id_cliente, nombre_cliente FROM Clientes ORDER BY nombre_cliente ASC");
                                while($c = $stmt_c->fetch()) {
                                    echo "<option data-id='".$c['id_cliente']."' value='".htmlspecialchars($c['nombre_cliente'])."'>";
                                }
                                ?>
                            </datalist>
                        </div>
                        <div class="text-end">
                            <a href="registro_cliente.php" id="linkClienteNuevo" class="text-danger small fw-bold text-decoration-none">
                                <i class="bi bi-person-plus-fill me-1"></i> ¿Cliente nuevo? Regístralo aquí
                            </a>
                        </div>
                    </div>
                </div>
                
                <div id="resultadoBusquedaRapid" class="mt-3" style="display: none;">
                    <div class="p-3 rounded-4 border-start border-4 border-success bg-light">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.7rem;">REGISTRO IDENTIFICADO:</small>
                        <h6 id="nombreClienteDetectado" class="fw-bold mb-1">---</h6>
                        <p id="modeloDetectado" class="text-muted small mb-0">---</p>
                    </div>
                </div>

                <div id="noEncontrado" class="mt-3 text-center" style="display: none;">
                    <div class="alert alert-warning border-0 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
                        <p class="small fw-bold mb-2">Esta serie no está en el sistema.</p>
                        <div class="d-grid">
                            <a href="registro_maquina.php" id="btnNuevaMaquinaRapida" class="btn btn-dark btn-sm rounded-pill">
                                <i class="bi bi-plus-circle me-1"></i> Registrar Máquina Nueva
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btnIrARegistro" class="btn btn-danger rounded-pill px-4 fw-bold shadow disabled">Continuar al Registro</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // 🎬 1. ANIMACIÓN DE ENTRADA PÁGINAS
    setTimeout(function() {
        $('#master-fade-container').addClass('fade-in-active');
    }, 50);

    // 🎬 2. INTERCEPTOR CINEMÁTICO SINCRONIZADO (BOTÓN + PÁGINA)
    $('#sidebar-menu-list .sidebar-link').on('click', function(e) {
        var destinoUrl = $(this).attr('href');
        var paginaActual = window.location.pathname.split("/").pop();

        if (destinoUrl === paginaActual) return;

        e.preventDefault(); // Congelamos la destrucción inmediata del navegador

        // A) Sincronía del Botón: Desplazamos visualmente la clase de forma instantánea
        $('#sidebar-menu-list .sidebar-link').removeClass('active-page');
        $(this).addClass('active-page');

        // B) Sincronía de la Página: Desvanecemos el bloque principal hacia arriba en paralelo
        $('#master-fade-container').addClass('fade-out-active');

        // C) Liberación del Navegador: Brincamos en cuanto terminan los 300ms de ambas transiciones
        setTimeout(function() {
            window.location.href = destinoUrl;
        }, 300);
    });

    var timerBusqueda;

    // --- LÓGICA SMART SCROLL ---
    var ultimoScrollTop = 0;
    var navbar = $('#main-top-navbar');
    var contenedorScroll = $('#page-content-wrapper');

    contenedorScroll.on('scroll', function() {
        var scrollTopActual = $(this).scrollTop();
        if (Math.abs(ultimoScrollTop - scrollTopActual) <= 5) return;
        
        if (scrollTopActual > ultimoScrollTop && scrollTopActual > 70) {
            navbar.addClass('navbar-hidden');
        } else {
            navbar.removeClass('navbar-hidden');
        }
        ultimoScrollTop = scrollTopActual;
    });

    // --- MANEJADOR DEL SIDEBAR INTERACTIVO ---
    $('#menu-toggle').on('click', function(e) {
        e.preventDefault();
        var wrapper = $('#wrapper');
        wrapper.toggleClass('toggled');
        var isCollapsed = wrapper.hasClass('toggled');
        localStorage.setItem('sidebar_collapsed', isCollapsed);
    });

    // --- INTERFACES AJAX: BÚSQUEDA POR SERIE ---
    $('#inputBusquedaSerie').on('input', function() {
        clearTimeout(timerBusqueda);
        var serie = $(this).val();
        
        if (serie.length > 2) {
            timerBusqueda = setTimeout(function() {
                $.ajax({
                    url: 'actions/buscar_cliente_por_serie.php',
                    method: 'POST',
                    data: { no_serie: serie },
                    dataType: 'json',
                    success: function(res) {
                        if (res.encontrado) {
                            $('#resultadoBusquedaRapid').fadeIn();
                            $('#noEncontrado').hide();
                            $('#nombreClienteDetectado').text(res.nombre_cliente);
                            $('#modeloDetectado').text('Modelo: ' + res.modelo);
                            $('#btnIrARegistro').removeClass('disabled').attr('href', 'registro_ticket.php?id_cliente=' + res.id_cliente + '&no_serie=' + serie + '&modelo=' + encodeURIComponent(res.modelo));
                        } else {
                            $('#resultadoBusquedaRapid').hide();
                            $('#noEncontrado').fadeIn();
                            $('#btnNuevaMaquinaRapida').attr('href', 'registro_maquina.php?no_serie=' + encodeURIComponent(serie));
                            $('#btnIrARegistro').addClass('disabled');
                        }
                    }
                });
            }, 500);
        } else {
            $('#resultadoBusquedaRapid, #noEncontrado').hide();
            $('#btnIrARegistro').addClass('disabled');
        }
    });

    // --- INTERFACES AJAX: BÚSQUEDA POR CLIENTE ---
    $('#inputBusquedaCliente').on('input', function() {
        var val = $(this).val();
        var option = $('#listaClientesBusqueda option').filter(function() { return this.value === val; });

        if (val.length > 0) {
            $('#linkClienteNuevo').attr('href', 'registro_cliente.php?nombre=' + encodeURIComponent(val));
        } else {
            $('#linkClienteNuevo').attr('href', 'registro_cliente.php');
        }

        if (option.length) {
            var id_cli = option.data('id');
            $('#resultadoBusquedaRapid').fadeIn();
            $('#nombreClienteDetectado').text(val);
            $('#modeloDetectado').text('Registro por selección manual de cliente');
            $('#btnIrARegistro').removeClass('disabled').attr('href', 'registro_ticket.php?id_cliente=' + id_cli);
        } else {
            $('#resultadoBusquedaRapid').hide();
            $('#btnIrARegistro').addClass('disabled');
        }
    });

    // LIMPIEZA AUTOMÁTICA AL CERRAR EL MODAL
    $('#modalNuevoTicket').on('hidden.bs.modal', function () {
        $(this).find('input').val('');
        $('#resultadoBusquedaRapid, #noEncontrado').hide();
        $('#btnIrARegistro').addClass('disabled').attr('href', '#');
    });
});
</script>
</body>
</html>