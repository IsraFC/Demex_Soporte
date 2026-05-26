<?php
/**
 * @file verificar.php
 * @package Portal_Demex
 * @version 1.4 - Activación de Cuenta en Raíz del Sistema
 * @date 2026-05-25
 */

require_once 'config/db.php';

// Si no hay token, redirige directamente a login.php (ya sin el ../)
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    header("Location: login.php");
    exit();
}

$token = trim($_GET['token']);
$cerrarVentana = false;

try {
    // Buscar al usuario con estatus 0 (pendiente) y que coincida el token
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE token_verificacion = ? AND estatus = 0");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Cambiar estatus a 1 (Activo) y eliminar el token por seguridad
        $update = $pdo->prepare("UPDATE usuarios SET estatus = 1, token_verificacion = NULL WHERE id_usuario = ?");
        $update->execute([$usuario['id_usuario']]);

        $icon = "success";
        $title = "¡Cuenta Activada!";
        $text = "Tu cuenta ha sido verificada correctamente. Esta ventana se cerrará automáticamente.";
        $color = "#2E7D32";
        $cerrarVentana = true;
    } else {
        $icon = "error";
        $title = "Enlace Inválido";
        $text = "El token es incorrecto, ya expiró o la cuenta ya se encuentra activa.";
        $color = "#C62828";
        $cerrarVentana = false;
    }
} catch (PDOException $e) {
    die("Error en el proceso de verificación: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Cuenta - DEMEX</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; background-color: #F8F9FA; }</style>
</head>
<body>
    <script>
        Swal.fire({
            icon: '<?= $icon ?>',
            title: '<?= $title ?>',
            text: '<?= $text ?>',
            confirmButtonColor: '<?= $color ?>'
        }).then(() => {
            <?php if ($cerrarVentana): ?>
                // Intenta cerrar la ventana/pestaña actual de forma automática
                window.close();
                
                // Respaldo de seguridad en caso de que el navegador bloquee el cierre directo (redirige a login.php sin ../)
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 500);
            <?php else: ?>
                // Si el enlace falló o es inválido, redirige al login sin ../
                window.location.href = 'login.php';
            <?php endif; ?>
        });
    </script>
</body>
</html>