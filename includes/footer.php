<?php
/**
 * ARCHIVO: footer.php
 * DESCRIPCIÓN: Componente de cierre de interfaz y pie de página institucional.
 * Este archivo se encarga de finalizar el contenedor principal y renderizar 
 * los créditos de autoría y el logotipo corporativo.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.1
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

    </body>
</html>