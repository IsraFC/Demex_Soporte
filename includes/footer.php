</div> 
        <footer class="py-4 mt-auto" style="background: transparent;">
            <div class="container-fluid px-4">
                <div class="row align-items-center border-top border-secondary border-opacity-10 pt-3">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0 text-muted small">
                            &copy; <?= date('Y') ?> <strong>Desarrollo Mexicano S.A. de C.V.</strong> 
                            <span class="mx-2 text-danger">|</span> Departamento de Sistemas
                        </p>
                    </div>
                    <div class="col-md-6 text-center text-md-end d-none d-md-block">
                        <img src="<?= $base_path ?>img/logo_demex.png" 
                             alt="Logo DEMEX" 
                             width="90" 
                             style="opacity: 0.25; filter: grayscale(100%);">
                    </div>
                </div>
            </div>
        </footer>

    </div> 
</div> 

<div class="modal fade" id="modalNuevoTicket" tabindex="-1" aria-hidden="true">
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
                            <a href="<?= $base_path ?><?= $link_prefix ?>registro_cliente.php" id="linkClienteNuevo" class="text-danger small fw-bold text-decoration-none">
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
                            <a href="<?= $base_path ?><?= $link_prefix ?>registro_maquina.php" id="btnNuevaMaquinaRapida" class="btn btn-dark btn-sm rounded-pill">
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
    
    /* 1. CONTROL DE ENTRADA CINEMATICA DE LA CARD PRINCIPAL */
    setTimeout(function() {
        $('#master-fade-container').addClass('fade-in-active');
    }, 40);

    /* 2. INTERCEPTOR CENTRALIZADO DE NAVEGACION Y ANIMACION DE TRANSICION */
    $('#sidebar-menu-list .sidebar-link').on('click', function(e) {
        var destinoUrl = $(this).attr('href');
        var paginaActualFile = window.location.pathname.split("/").pop();

        // Si se hace clic en la pagina donde ya nos encontramos, se aborta la operacion
        if (destinoUrl === paginaActualFile || (paginaActualFile === "" && destinoUrl === "index.php")) return;

        // Se detiene la navegacion sincronica para dar tiempo a la ejecucion de la animacion
        e.preventDefault(); 

        // Evaluacion dinamica de rutas absolutas para alternar los esquemas de color del sistema
        var urlAbsoluta = e.currentTarget.href;
        var vaHaciaStaff = urlAbsoluta.includes('usuarios.php') || urlAbsoluta.includes('personal_staff.php');
        var esUrlDeSoporte = urlAbsoluta.includes('/Soporte/');

        if (esUrlDeSoporte) {
            if (!vaHaciaStaff) {
                document.body.setAttribute('data-theme', 'soporte');
            } else {
                document.body.setAttribute('data-theme', 'global');
            }
        } else {
            if (urlAbsoluta.includes('Soporte/')) {
                document.body.setAttribute('data-theme', 'soporte');
            } else {
                document.body.setAttribute('data-theme', 'global');
            }
        }

        // CONTROL DE ANIMACION SIMETRICA EN LA BARRA LATERAL
        // Remueve la clase del boton que la tenia activada originalmente (inicia el efecto de vaciado)
        $('#sidebar-menu-list .sidebar-link.active-page').removeClass('active-page');
        
        // Aplica la clase activa al boton cliqueado (inicia el efecto de llenado)
        // Se remueve explicitamente '.no-anim' en caso de que viniera renderizada desde PHP
        $(this).removeClass('no-anim').addClass('active-page');

        // Desvanecimiento hacia arriba del contenedor principal de la vista actual
        $('#master-fade-container').addClass('fade-out-active');

        // Ejecucion del cambio de locacion tras completarse el ciclo visual (300 milisegundos)
        setTimeout(function() {
            window.location.href = destinoUrl;
        }, 300);
    });

    var timerBusqueda;
    var pathDestinoAcciones = "<?= $base_path ?><?= $link_prefix ?>";

    /* 3. MANEJADOR SMART SCROLL PARA OCULTAR LA BARRA DE NAVEGACION SUPERIOR */
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

    /* 4. CONTROL INTERACTIVO DE COLAPSO DE LA BARRA LATERAL (SIDEBAR) */
    $('#menu-toggle').on('click', function(e) {
        e.preventDefault();
        var wrapper = $('#wrapper');
        wrapper.toggleClass('toggled');
        var isCollapsed = wrapper.hasClass('toggled');
        localStorage.setItem('sidebar_collapsed', isCollapsed);
    });

    /* 5. CONSULTA ASINCRONA (AJAX): BUSQUEDA POR NUMERO DE SERIE */
    $('#inputBusquedaSerie').on('input', function() {
        clearTimeout(timerBusqueda);
        var serie = $(this).val();
        
        if (serie.length > 2) {
            timerBusqueda = setTimeout(function() {
                $.ajax({
                    url: pathDestinoAcciones + 'actions/buscar_cliente_por_serie.php',
                    method: 'POST',
                    data: { no_serie: serie },
                    dataType: 'json',
                    success: function(res) {
                        if (res.encontrado) {
                            $('#resultadoBusquedaRapid').fadeIn();
                            $('#noEncontrado').hide();
                            $('#nombreClienteDetectado').text(res.nombre_cliente);
                            $('#modeloDetectado').text('Modelo: ' + res.modelo);
                            $('#btnIrARegistro').removeClass('disabled').attr('href', pathDestinoAcciones + 'registro_ticket.php?id_cliente=' + res.id_cliente + '&no_serie=' + serie + '&modelo=' + encodeURIComponent(res.modelo));
                        } else {
                            $('#resultadoBusquedaRapid').hide();
                            $('#noEncontrado').fadeIn();
                            $('#btnNuevaMaquinaRapida').attr('href', pathDestinoAcciones + 'registro_maquina.php?no_serie=' + encodeURIComponent(serie));
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

    /* 6. CONSULTA ASINCRONA (AJAX): BUSQUEDA POR SELECCION DE CLIENTE */
    $('#inputBusquedaCliente').on('input', function() {
        var val = $(this).val();
        var option = $('#listaClientesBusqueda option').filter(function() { return this.value === val; });

        if (val.length > 0) {
            $('#linkClienteNuevo').attr('href', pathDestinoAcciones + 'registro_cliente.php?nombre=' + encodeURIComponent(val));
        } else {
            $('#linkClienteNuevo').attr('href', pathDestinoAcciones + 'registro_cliente.php');
        }

        if (option.length) {
            var id_cli = option.data('id');
            $('#resultadoBusquedaRapid').fadeIn();
            $('#nombreClienteDetectado').text(val);
            $('#modeloDetectado').text('Registro por selección manual de cliente');
            $('#btnIrARegistro').removeClass('disabled').attr('href', pathDestinoAcciones + 'registro_ticket.php?id_cliente=' + id_cli);
        } else {
            $('#resultadoBusquedaRapid').hide();
            $('#btnIrARegistro').addClass('disabled');
        }
    });

    /* 7. REESTABLECIMIENTO INTEGRAL DE CAMPOS AL CERRAR EL MODAL */
    $('#modalNuevoTicket').on('hidden.bs.modal', function () {
        $(this).find('input').val('');
        $('#resultadoBusquedaRapid, #noEncontrado').hide();
        $('#btnIrARegistro').addClass('disabled').attr('href', '#');
    });
});
</script>
</body>
</html>