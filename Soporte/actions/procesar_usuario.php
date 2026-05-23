<?php
/**
 * @file procesar_usuario.php
 * @package Portal_Demex
 * @version 1.7 - Alta con PHPMailer Local e Inserción Numérica
 * @date 2026-05-22
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    die("Acceso denegado de forma estricta.");
}

require_once '../config/db.php';
// Inclusión obligatoria del archivo de credenciales privadas
require_once '../config/mail_config.php';

// Definición de los espacios de nombres de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carga manual de los archivos descargados
require '../libs/PHPMailer/Exception.php';
require '../libs/PHPMailer/PHPMailer.php';
require '../libs/PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($password) || empty($rol)) {
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

        // 2. Procesamiento criptográfico de contraseña y token de activación
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $token = bin2hex(random_bytes(32)); 

        // 3. Inserción con Estatus Fijo en 0 (Pendiente - TINYINT)
        $sql = "INSERT INTO usuarios (nombre, apellidos, correo, password, rol, estatus, token_verificacion) 
                VALUES (?, ?, ?, ?, ?, 0, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellidos, $correo, $passwordHash, $rol, $token]);

        // 4. Construcción del URL dinámico de verificación incluyendo subcarpetas del proyecto
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];

        // dirname($_SERVER['SCRIPT_NAME']) devuelve la ruta desde el host hasta la carpeta actual (/desarrollo_mexicano/Soporte/actions)
        // Usamos dirname() una segunda vez para subir un nivel y situarnos en la raíz de la carpeta Soporte
        $rutaBase = dirname(dirname($_SERVER['SCRIPT_NAME']));

        // Aseguramos que las barras diagonales queden limpias y consistentes
        $rutaBase = rtrim($rutaBase, '/\\');

        $enlaceVerificacion = $protocolo . $host . $rutaBase . "/verificar.php?token=" . $token;

        // 5. Instanciación y Configuración del objeto PHPMailer
        $mail = new PHPMailer(true);

        // Configuración protegida mediante las constantes globales de mail_config.php
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
        $mail->Subject = 'Activación de Cuenta - Portal Staff DEMEX';
        
        // Estructura visual del correo electrónico
        $mail->Body    = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #F8F9FA; border-radius: 8px;'>
                <h2 style='color: #C62828;'>Hola {$nombre},</h2>
                <p>Se ha generado tu acceso para el sistema de soporte técnico de <strong>DEMEX</strong>.</p>
                <p>Para activar tu cuenta y poder ingresar al portal, es necesario que verifiques tu dirección de correo haciendo clic en el siguiente enlace:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$enlaceVerificacion}' style='background-color: #C62828; color: #FFFFFF; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>ACTIVAR MI CUENTA</a>
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
                title: '¡Acceso Registrado!',
                text: 'El usuario ha sido creado en estado pendiente. Se envió el correo de activación.',
                confirmButtonColor: '#C62828'
            }).then(() => { window.location.href = '../usuarios.php'; });
        </script>
        </body>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMessage = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
        die("Error en el despacho del correo: {$errorMessage}");
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error crítico de base de datos: " . $e->getMessage());
    }
}