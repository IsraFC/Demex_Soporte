<?php
/**
 * ARCHIVO: footer.php
 * DESCRIPCIÓN: Componente de cierre de interfaz y pie de página institucional.
 * Este archivo se encarga de finalizar el contenedor principal y renderizar 
 * los créditos de autoría y el logotipo corporativo.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.2
 */
?>
    </div> 

        <footer class="mt-5 py-4 bg-white border-top shadow-sm">
            <div class="container">
                <div class="row align-items-center">
                    
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0 text-muted small">
                            &copy; <?= date('Y') ?> <strong>Desarrollo Mexicano S.A. de C.V.</strong> 
                            <span class="mx-2 text-danger">|</span> Departamento de Sistemas
                        </p>
                    </div>
                    
                    <div class="col-md-6 text-center text-md-end">
                        <img src="img/logo_demex.png" 
                            alt="Logo DEMEX" 
                            width="100" 
                            style="opacity: 0.4; filter: grayscale(100%);">
                    </div>
                    
                </div>
            </div>
        </footer>

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
                                    <a href="registro_cliente.php" class="text-danger small fw-bold text-decoration-none">
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
            var timerBusqueda;

            // --- BÚSQUEDA POR SERIE ---
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
                                    $('#btnNuevaMaquinaRapida').attr('href', 'registro_maquina.php?no_serie=' + serie);
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

            // --- BÚSQUEDA POR CLIENTE (Datalist) ---
            $('#inputBusquedaCliente').on('input', function() {
                var val = $(this).val();
                var option = $('#listaClientesBusqueda option').filter(function() { return this.value === val; });

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

            // LIMPIEZA AL CERRAR
            $('#modalNuevoTicket').on('hidden.bs.modal', function () {
                $(this).find('input').val('');
                $('#resultadoBusquedaRapid, #noEncontrado').hide();
                $('#btnIrARegistro').addClass('disabled').attr('href', '#');
            });
        });
        </script>
    </body>
</html>