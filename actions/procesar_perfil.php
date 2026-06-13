<?php
/**
 * @file procesar_perfil.php
 * @package Portal_Demex
 * @version 1.6 - Procesador Asíncrono con Respuesta JSON
 * @date 2026-06-08
 * @brief Recibe datos y devuelve estados estructurados en formato JSON para consumo de Fetch API.
 */

session_start();
// Cambiamos el tipo de contenido a JSON para la API
header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';

// 1. CONTROL DE ACCESO
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'title' => 'Acceso denegado', 'text' => 'Sesión no válida.']);
    exit();
}

$id_usuario = (int)$_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre            = trim($_POST['nombre'] ?? '');
    $apellidos         = trim($_POST['apellidos'] ?? '');
    $correo            = trim($_POST['correo'] ?? '');
    $eliminar_foto     = isset($_POST['eliminar_foto']) ? (int)$_POST['eliminar_foto'] : 0;
    $foto_comprimida   = trim($_POST['foto_comprimida_base64'] ?? '');

    if (empty($nombre) || empty($apellidos) || empty($correo)) {
        echo json_encode(['status' => 'error', 'title' => 'Campos vacíos', 'text' => 'Todos los campos requeridos deben ser completados.']);
        exit();
    }

    $correo = filter_var($correo, FILTER_SANITIZE_EMAIL);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'title' => 'Correo Inválido', 'text' => 'El formato del correo electrónico no es válido.']);
        exit();
    }

    try {
        // 2. VALIDAR DUPLICIDAD DE CORREO
        $checkEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ? LIMIT 1");
        $checkEmail->execute([$correo, $id_usuario]);
        
        if ($checkEmail->fetch()) {
            echo json_encode(['status' => 'warning', 'title' => 'Correo en Uso', 'text' => 'El correo electrónico ya pertenece a otro miembro de DEMEX.']);
            exit();
        }

        // 3. ACTUALIZACIÓN DE DATOS TEXTUALES
        $sqlTexto = "UPDATE usuarios SET nombre = ?, apellidos = ?, correo = ? WHERE id_usuario = ?";
        $stmtTexto = $pdo->prepare($sqlTexto);
        $stmtTexto->execute([$nombre, $apellidos, $correo, $id_usuario]);

        // 4. PROCESAMIENTO DEL STRING BINARIO OPTIMIZADO
        $actualizar_foto = false;

        if ($eliminar_foto === 1) {
            $sqlFoto = "UPDATE usuarios SET foto_perfil = NULL WHERE id_usuario = ?";
            $stmtFoto = $pdo->prepare($sqlFoto);
            $stmtFoto->execute([$id_usuario]);
            
            $_SESSION['foto_perfil_base64'] = '';
            $actualizar_foto = true;
        } elseif (!empty($foto_comprimida)) {
            $datos_binarios = base64_decode($foto_comprimida);

            $sqlFoto = "UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?";
            $stmtFoto = $pdo->prepare($sqlFoto);
            $stmtFoto->bindValue(1, $datos_binarios, PDO::PARAM_LOB);
            $stmtFoto->bindValue(2, $id_usuario, PDO::PARAM_INT);
            $stmtFoto->execute();

            $_SESSION['foto_perfil_base64'] = $foto_comprimida;
            $actualizar_foto = true;
        }

        // 5. ACTUALIZAR VARIABLES DE SESIÓN TEXTUALES
        $_SESSION['nombre']    = $nombre;
        $_SESSION['apellidos'] = $apellidos;
        $_SESSION['correo']    = $correo;

        // Respondemos éxito en formato JSON estructurado
        echo json_encode([
            'status' => 'success',
            'title' => '¡Perfil Actualizado!',
            'text' => 'Tus datos personales se han modificado con éxito de forma segura.'
        ]);
        exit();

    } catch (\PDOException $e) {
        echo json_encode(['status' => 'error', 'title' => 'Error de Servidor', 'text' => $e->getMessage()]);
        exit();
    }
}