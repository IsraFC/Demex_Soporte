<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Interés | Desarrollo Mexicano</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* Corrección del tamaño visual del menú desplegable */
        .form-select {
            font-size: 1rem !important;
            line-height: 1.5 !important;
            padding-top: 0.85rem !important;   /* Un toque extra para que no se vea chiquito */
            padding-bottom: 0.85rem !important;
            padding-right: 2.5rem !important;  /* Espacio de seguridad para la flecha */
            height: auto !important;
        }
        
        .form-select option {
            font-family: 'Poppins', sans-serif !important;
            font-size: 1rem !important;
            padding: 12px !important;
        }

        /* Contenedor de seguridad para evitar que Flexbox estire la página verticalmente */
        .formulario-wrapper {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body data-theme="global" class="bg-light py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-9">
            
            <div class="formulario-wrapper">
                <div class="card-main" style="border-top: 5px solid var(--primary-color) !important; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                    
                    <div class="text-center mb-4">
                        <div class="logo-login-container mb-2">
                            <img src="img/logo_demex.png" alt="Desarrollo Mexicano" class="img-fluid logo-highlight" style="max-height: 85px;">
                        </div>
                        <h3 class="fw-bold text-dark h4 mb-2">¿Te interesa alguno de nuestros equipos?</h3>
                        <p class="text-muted small px-md-5">Por favor, llena este breve formulario para que un asesor te envíe una cotización formal.</p>
                    </div>

                    <form action="actions/procesar_lead.php" method="POST" id="formLead">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="nombre" class="form-label fw-semibold text-dark mb-2">Nombre(s) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Juan" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="apellidos" class="form-label fw-semibold text-dark mb-2">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" placeholder="Ej. Pérez López" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="telefono" class="form-label fw-semibold text-dark mb-2">Teléfono / WhatsApp <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-whatsapp"></i></span>
                                    <input type="tel" class="form-control border-start-0" id="telefono" name="telefono" placeholder="10 dígitos" required pattern="[0-9]{10}">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="correo" class="form-label fw-semibold text-dark mb-2">Correo Electrónico <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control border-start-0" id="correo" name="correo" placeholder="ejemplo@correo.com" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="pais" class="form-label fw-semibold text-dark mb-2">País <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pais" name="pais" value="México" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="estado_region" class="form-label fw-semibold text-dark mb-2">Estado / Región <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="estado_region" name="estado_region" placeholder="Ej. Puebla" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="maquina_interes" class="form-label fw-semibold text-dark mb-2">Máquina de tu Interés <span class="text-danger">*</span></label>
                                <select class="form-select" id="maquina_interes" name="maquina_interes" required>
                                    <option value="" selected disabled>Selecciona un modelo...</option>
                                    <option value="SPICE MT15">SPICE MT15 (Helado Suave - Barra)</option>
                                    <option value="SPICE MV89">SPICE MV89 (Helado Suave - Piso)</option>
                                    <option value="DEMEX 313T">DEMEX 313T (Helado Suave - Barra)</option>
                                    <option value="DEMEX 313">DEMEX 313 (Helado Suave - Piso)</option>
                                    <option value="DEMEX 513">DEMEX 513 (Helado Suave - Piso Grande)</option>
                                    <option value="DEMEX 613">DEMEX 613 (Helado Suave - Alta Production)</option>
                                    <option value="DEMEX 125">DEMEX 125 (Helado Duro - Nieve artesanal)</option>
                                    <option value="DEMEX 1020">DEMEX 1020 (Helado Duro - Fábrica Grande)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn-demex w-100 py-3 text-uppercase tracking-wide fs-6">
                                <i class="bi bi-send-fill me-2"></i> Enviar Formulario
                            </button>
                            <div class="text-center mt-3">
                                <small class="text-muted" style="font-size: 0.8rem;">Tus datos serán procesados inmediatamente en nuestro CRM de ventas.</small>
                            </div>
                        </div>

                    </form>

                </div> </div> </div>
    </div>
</div>

</body>
</html>