<?php
/**
 * @file procesar_usuario.php
 * @package Portal_Demex
 * @version 3.0 - Registro Multi-Rol con Plantilla de Correo Unificada
 * @date 2026-06-08
 * @brief Registro de usuarios con soporte para múltiples perfiles y vinculación en tabla intermedia.
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

require_once '../config/db.php';
require_once '../includes/header.php'; // Requerido para consumir la función global tieneAcceso()

// Control de acceso adaptado al entorno de múltiples roles
if (!tieneAcceso(['Administrador'])) {
    die("Acceso denegado de forma estricta.");
}

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
    $roles     = $_POST['roles'] ?? []; // Recupera el arreglo de IDs de roles seleccionados

    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($roles)) {
        die("Todos los campos obligatorios deben ser completados, incluyendo al menos un rol.");
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

        // 3. Inserción del usuario sin columna obsoleta 'rol'
        $sql = "INSERT INTO usuarios (nombre, apellidos, correo, password, estatus, token_verificacion) 
                VALUES (?, ?, ?, '', 0, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellidos, $correo, $token]);

        // Recuperamos el ID autogenerado para el nuevo usuario
        $id_usuario_nuevo = $pdo->lastInsertId();

        // 4. Inserción de los múltiples roles asignados en la tabla intermedia
        $sql_intermedia = "INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)";
        $stmt_intermedia = $pdo->prepare($sql_intermedia);

        foreach ($roles as $id_rol) {
            $stmt_intermedia->execute([$id_usuario_nuevo, $id_rol]);
        }

        // 5. Construcción del URL dinámico de verificación
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $rutaBase = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $rutaBase = rtrim($rutaBase, '/\\');

        $enlaceVerificacion = $protocolo . $host . $rutaBase . "/verificar.php?token=" . $token;

        // 6. Configuración y envío mediante PHPMailer
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
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #d15b00; margin: 0;'>Portal DEMEX</h2>
                    <p style='color: #777; font-size: 14px; margin: 5px 0 0 0;'>Control de Acceso Corporativo</p>
                </div>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p>Hola, <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                <p>Se ha generado exitosamente tu perfil de acceso para el sistema de <strong>Desarrollo Mexicano</strong>.</p>
                <p>Para activar tu cuenta, confirmar tu dirección de correo electrónico y <strong>establecer tu contraseña</strong>, por favor haz clic en el siguiente botón seguro:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . $enlaceVerificacion . "' style='background-color: #d15b00; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-transform: uppercase; font-size: 13px;'>Configurar mi Cuenta</a>
                </div>
                
                <p style='font-size: 12px; color: #666;'>Si el botón superior no responde de forma correcta, puedes copiar y pegar la siguiente URL en tu navegador web:</p>
                <p style='font-size: 11px; color: #0066cc; word-break: break-all;'>" . $enlaceVerificacion . "</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 11px; color: #999; text-align: center; margin: 0;'>Este es un correo automático generado por el sistema de seguridad de DEMEX. Por favor no respondas a este mensaje.</p>
            </div>
        ";

        $mail->send();
        $pdo->commit();

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <body style='font-family: sans-serif;'>
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Invitación Enviada!',
                text: 'El usuario fue registrado con éxito. Se envió el correo corporativo para la asignación de su contraseña.',
                confirmButtonColor: '#d15b00'
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
?>