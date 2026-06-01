<?php
/**
 * @file editar_usuario.php
 * @package Portal_Demex
 * @brief Controlador encargado de modificar información y estatus lógicos del personal.
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

// Control de acceso de seguridad estricto
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    die("Acceso denegado de forma estricta.");
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
    $nombre     = trim($_POST['nombre']);
    $apellidos  = trim($_POST['apellidos']);
    $rol        = $_POST['rol'];
    $estatus    = isset($_POST['estatus']) ? intval($_POST['estatus']) : 1;

    if ($id_usuario === 0 || empty($nombre) || empty($apellidos) || empty($rol)) {
        die("Parámetros obligatorios incompletos.");
    }

    try {
        // Ejecución de la consulta preparada con PDO
        $sql = "UPDATE usuarios 
                SET nombre = ?, apellidos = ?, rol = ?, estatus = ? 
                WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellidos, $rol, $estatus, $id_usuario]);

        // Retorno visual exitoso utilizando SweetAlert2
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <body style='font-family: sans-serif;'>
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Cambios Guardados!',
                text: 'La información del usuario ha sido actualizada con éxito.',
                confirmButtonColor: '#C62828'
            }).then(() => { window.location.href = '../usuarios.php'; });
        </script>
        </body>";
        exit();

    } catch (\PDOException $e) {
        die("Error crítico al actualizar la información del personal: " . $e->getMessage());
    }
}