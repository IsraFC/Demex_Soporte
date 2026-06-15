<?php
// Incluimos la conexión PDO oficial de Isra usando ruta absoluta
require_once __DIR__ . '/../config/db.php';

// Validamos que los datos realmente vengan por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recolección y limpieza de datos
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $pais = isset($_POST['pais']) ? trim($_POST['pais']) : 'México';
    $estado_region = trim($_POST['estado_region']);
    $maquina_interes = trim($_POST['maquina_interes']);
    $canal_origen = 'Formulario Web'; 

    if (empty($nombre) || empty($apellidos) || empty($telefono) || empty($correo) || empty($estado_region) || empty($maquina_interes)) {
        die("Error: Todos los campos obligatorios deben estar llenos.");
    }

    // Usamos el objeto $pdo nativo para arrancar la transacción
    $pdo->beginTransaction();

    try {
        // SQL para tabla formulario
        $sqlFormulario = "INSERT INTO formulario (nombre, apellidos, telefono, correo, pais, estado_region, maquina_interes, canal_origen) 
                          VALUES (:nombre, :apellidos, :telefono, :correo, :pais, :estado_region, :maquina_interes, :canal_origen)";
        
        $stmtForm = $pdo->prepare($sqlFormulario);
        $stmtForm->execute([
            ':nombre'          => $nombre,
            ':apellidos'       => $apellidos,
            ':telefono'        => $telefono,
            ':correo'          => $correo,
            ':pais'            => $pais,
            ':estado_region'   => $estado_region,
            ':maquina_interes' => $maquina_interes,
            ':canal_origen'    => $canal_origen
        ]);
        
        // Recuperamos el ID auto-incremental en PDO
        $id_formulario = $pdo->lastInsertId();

        // SQL para tabla prospectos
        $sqlProspecto = "INSERT INTO prospectos (id_formulario, status_operativo, fecha_contacto, fecha_ultimo_contacto) 
                         VALUES (:id_formulario, 'Consulta', NOW(), NOW())";
        
        $stmtPros = $pdo->prepare($sqlProspecto);
        $stmtPros->execute([
            ':id_formulario' => $id_formulario
        ]);

        // Si todo va bien, consolidamos la transacción
        $pdo->commit();

            // --- INTERFAZ DE ÉXITO ANIMADA AL ESTILO DE ISRA ---
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Procesando Registro...</title>
                <!-- Bootstrap y Estilos Oficiales -->
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="../css/estilos.css">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                <style>
                    body {
                        background-color: #F8F9FA !important;
                        height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-family: 'Poppins', sans-serif;
                    }
                    /* Tarjeta con animación de entrada fluida (Fade-In de Isra) */
                    .modal-exito {
                        max-width: 480px;
                        width: 90%;
                        background: #ffffff;
                        border-radius: 20px;
                        box-shadow: 0 15px 35px rgba(230, 81, 0, 0.08);
                        border-top: 5px solid var(--primary-color);
                        padding: 2.5rem;
                        text-align: center;
                        opacity: 0;
                        transform: translateY(20px);
                        transition: all 0.6s cubic-bezier(0.25, 1, 0.5, 1);
                    }
                    .modal-exito.show {
                        opacity: 1;
                        transform: translateY(0);
                    }
                    /* Círculo del check animado */
                    .icon-scale {
                        width: 80px;
                        height: 80px;
                        background-color: rgba(230, 81, 0, 0.05);
                        color: var(--primary-color);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 1.5rem auto;
                        font-size: 2.5rem;
                        transform: scale(0.5);
                        transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.2s;
                    }
                    .modal-exito.show .icon-scale {
                        transform: scale(1);
                    }
                    /* Barra de progreso líquida inferior */
                    .progress-bar-container {
                        width: 100%;
                        height: 4px;
                        background-color: #E0E0E0;
                        border-radius: 2px;
                        overflow: hidden;
                        margin-top: 2rem;
                    }
                    .progress-bar-fill {
                        width: 0%;
                        height: 100%;
                        background-color: var(--primary-color);
                        transition: width 3s linear;
                    }
                </style>
            </head>
            <body>

            <div class="modal-exito shadow-lg" id="alertCard">
                <div class="icon-scale">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">¡Solicitud Recibida!</h3>
                <p class="text-muted small mb-0">
                    Tu interés por el equipo <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($maquina_interes); ?></strong> ha sido registrado en nuestro CRM.
                </p>
                <p class="text-muted small mt-2">Un asesor se pondrá en contacto contigo a la brevedad.</p>
                
                <!-- Barra de carga cinemática antes de redireccionar -->
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" id="barFill"></div>
                </div>
            </div>

            <script>
                // Al cargar la página, disparamos las animaciones visuales
                window.addEventListener('DOMContentLoaded', () => {
                    const card = document.getElementById('alertCard');
                    const bar = document.getElementById('barFill');
                    
                    // Activa el efecto fade-in suave de la tarjeta e icono
                    setTimeout(() => {
                        card.classList.add('show');
                    }, 100);

                    // Inicia el llenado fluido de la barra de progreso
                    setTimeout(() => {
                        bar.style.width = '100%';
                    }, 200);

                    // Redirección automática tras 3.2 segundos
                    setTimeout(() => {
                        window.location.href = '../formulario.php';
                    }, 4200);
                });
            </script>
            </body>
            </html>
            <?php
        exit();

    } catch (Exception $e) {
        // Si falla, revertimos con PDO
        $pdo->rollBack();
        die("Error crítico al procesar el lead en el sistema: " . $e->getMessage());
    }

} else {
    header("Location: ../formulario.php");
    exit();
}
?>