<?php
/**
 * @file procesar_recuperacion.php
 * @package Portal_Demex
 * @brief Controlador para generar tokens de recuperación de contraseña y enviar correos mediante PHPMailer.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Importar la conexión a la base de datos, tu configuración de correo y PHPMailer
require_once '../config/db.php';
require_once '../config/mail_config.php'; // <--- NUEVO: Importamos tus constantes corporativas
require_once '../libs/PHPMailer/Exception.php';
require_once '../libs/PHPMailer/PHPMailer.php';
require_once '../libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Validar que la petición venga estrictamente por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../recuperar.php");
    exit();
}

$correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';

// 3. Validar el formato del correo electrónico
if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['old_correo'] = $correo;
    header("Location: ../recuperar.php?error=correo_invalido");
    exit();
}

try {
    // 4. Verificar si el correo pertenece a un usuario activo en el sistema
    $stmt = $pdo->prepare("SELECT id_usuario, nombre FROM usuarios WHERE correo = ? AND estatus = 1 LIMIT 1");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $_SESSION['old_correo'] = $correo;
        header("Location: ../recuperar.php?error=usuario_no_encontrado");
        exit();
    }

    // 5. Generar el Token Criptográfico Seguro (64 caracteres) y su expiración (+1 hora)
    $token = bin2hex(random_bytes(32)); 
    $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 6. Guardar el token en la base de datos
    $updateStmt = $pdo->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expira = ? WHERE id_usuario = ?");
    $updateStmt->execute([$token, $fecha_expiracion, $usuario['id_usuario']]);

    // 7. Construir el enlace adaptativo para el correo electrónico (100% Dinámico)
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    
    // Detectamos la ruta base del proyecto de forma automática sin importar el nombre de la carpeta local
    $rutaBase = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $rutaBase = rtrim($rutaBase, '/\\');

    // Construimos la URL final apuntando a restablecer.php en la raíz
    $enlace_restablecer = $protocolo . $host . $rutaBase . "/restablecer.php?token=" . $token;

    // 8. Configurar y enviar el correo electrónico con PHPMailer
    $mail = new PHPMailer(true);

    // ---- USAMOS TUS CONSTANTES DE MAIL_CONFIG.PHP ----
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;                     
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;    
    $mail->Password   = SMTP_PASS;        
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // ---- DESTINATARIOS Y REMITENTE ----
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($correo, $usuario['nombre']);

    // ---- CONTENIDO DEL CORREO ----
    $mail->isHTML(true);
    $mail->Subject = 'Restablecer Contraseña - Portal DEMEX';
    
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <h2 style='color: #d15b00; margin: 0;'>Portal DEMEX</h2>
                <p style='color: #777; font-size: 14px; margin: 5px 0 0 0;'>Control de Acceso Corporativo</p>
            </div>
            <hr style='border: 0; border-top: 1px solid #eee;'>
            <p>Hola, <strong>" . htmlspecialchars($usuario['nombre']) . "</strong>,</p>
            <p>Hemos recibido una solicitud para restablecer la contraseña de acceso a tu cuenta en el portal de <strong>Desarrollo Mexicano</strong>.</p>
            <p>Para continuar con el proceso, por favor haz clic en el siguiente botón seguro. Ten en cuenta que este enlace tiene una <strong>validez de 1 hora</strong> por motivos de protección de datos:</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . $enlace_restablecer . "' style='background-color: #d15b00; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>Restablecer mi Contraseña</a>
            </div>
            
            <p style='font-size: 12px; color: #666;'>Si el botón superior no funciona, puedes copiar y pegar la siguiente URL en tu navegador de internet:</p>
            <p style='font-size: 11px; color: #0066cc; word-break: break-all;'>" . $enlace_restablecer . "</p>
            <hr style='border: 0; border-top: 1px solid #eee;'>
            <p style='font-size: 11px; color: #999; text-align: center; margin: 0;'>Si tú no solicitaste este cambio, puedes ignorar este correo de forma segura; tu contraseña actual se mantendrá sin modificaciones.</p>
        </div>
    ";

    $mail->send();

    header("Location: ../recuperar.php?status=enviado");
    exit();

} catch (Exception $e) {
    header("Location: ../recuperar.php?error=envio_fallido");
    exit();
} catch (\Exception $e) {
    header("Location: ../recuperar.php?error=servidor");
    exit();
}