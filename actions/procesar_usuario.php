<?php
/**
 * @file procesar_usuario.php
 * @package Portal_Demex
 * @version 2.0 - Registro sin contraseña previa (Establecimiento vía Token)
 * @date 2026-05-29
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    die("Acceso denegado de forma estricta.");
}

require_once '../config/db.php';
require_once '../config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../libs/PHPMailer/Exception.php';
require '../libs/PHPMailer/PHPMailer.php';
require '../libs/PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $correo    = trim($_POST['correo']);
    $rol       = $_POST['rol'];

    // Validamos únicamente los campos disponibles ahora
    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($rol)) {
        die("Todos los campos obligatorios deben ser completados.");
    }

    try {
        $pdo->beginTransaction();

        // 1. Validar duplicidad de correo electrónico
        $checkEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        $checkEmail->execute([$correo]);
        
        if ($checkEmail->fetch()) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <body style='font-family: sans-serif;'>
            <script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Correo Duplicado',
                    text: 'El correo electrónico ingresado ya pertenece a un miembro del staff.',
                    confirmButtonColor: '#C62828'
                }).then(() => { window.history.back(); });
            </script>
            </body>";
            exit();
        }

        // 2. Generación del token de activación seguro
        $token = bin2hex(random_bytes(32)); 

        // 3. Inserción con Estatus Fijo en 0 y contraseña vacía/provisional temporalmente
        $sql = "INSERT INTO usuarios (nombre, apellidos, correo, password, rol, estatus, token_verificacion) 
                VALUES (?, ?, ?, '', ?, 0, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellidos, $correo, $rol, $token]);

        // 4. Construcción del URL dinámico de verificación
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $rutaBase = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $rutaBase = rtrim($rutaBase, '/\\');

        $enlaceVerificacion = $protocolo . $host . $rutaBase . "/verificar.php?token=" . $token;

        // 5. Configuración y envío mediante PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;                     
        $mail->SMTPAuth   = true;                                 
        $mail->Username   = SMTP_USER;     
        $mail->Password   = SMTP_PASS;                  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       
        $mail->Port       = SMTP_PORT;                                  
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($correo, $nombre . ' ' . $apellidos);

        $mail->isHTML(true);
        $mail->Subject = 'Activación de Cuenta y Asignación de Contraseña - DEMEX';
        
        $mail->Body    = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #F8F9FA; border-radius: 8px;'>
                <h2 style='color: #C62828;'>Bienvenido {$nombre},</h2>
                <p>Se ha generado tu perfil de acceso para el sistema corporativo de <strong>DEMEX</strong>.</p>
                <p>Para activar tu cuenta, confirmar tu correo y **establecer tu contraseña de seguridad**, haz clic en el siguiente enlace:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$enlaceVerificacion}' style='background-color: #C62828; color: #FFFFFF; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>CONFIGURAR MI CUENTA</a>
                </div>
                <p style='font-size: 12px; color: #757575;'>Si el botón no funciona, copia y pega esta URL en tu navegador:<br>{$enlaceVerificacion}</p>
            </div>";

        $mail->send();
        $pdo->commit();

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <body style='font-family: sans-serif;'>
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Invitación Enviada!',
                text: 'El usuario fue registrado. Se envió el correo para que configure su propia contraseña.',
                confirmButtonColor: '#C62828'
            }).then(() => { window.location.href = '../usuarios.php'; });
        </script>
        </body>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMessage = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
        die("Error en el despacho del correo: {$errorMessage}");
    } catch (\PDOException $e) {
        $pdo->rollBack();
        die("Error crítico de base de datos: " . $e->getMessage());
    }
}