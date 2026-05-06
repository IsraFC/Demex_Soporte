<?php
/**
 * ARCHIVO: actions/verificar_cliente.php
 * DESCRIPCIÓN: Validador de disponibilidad de nombre de cliente.
 * Soporta validación inteligente tanto para registros nuevos como para ediciones,
 * evitando colisiones de nombres duplicados entre distintos usuarios.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.1
 */

require_once '../config/db.php';

// Verifica que la petición contenga el nombre a validar
if (isset($_POST['nombre'])) {
    
    // Limpieza de espacios en blanco al inicio y final del texto
    $nombre = trim($_POST['nombre']);
    
    /**
     * CAPTURA DE CONTEXTO:
     * Si recibimos un ID, significa que el usuario está en modo EDICIÓN.
     * Si es null, el usuario está en modo REGISTRO.
     */
    $id_actual = $_POST['id_cliente'] ?? null;

    if ($id_actual) {
        /**
         * MODO EDICIÓN:
         * La consulta busca si el nombre ya está tomado por ALGUIEN MÁS.
         * Se usa la cláusula 'AND id_cliente != ?' para excluir el registro propio
         * y permitir que el usuario mantenga su nombre actual sin disparar el error.
         */
        $sql = "SELECT COUNT(*) FROM Clientes WHERE nombre_cliente = ? AND id_cliente != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $id_actual]);
    } else {
        /**
         * MODO REGISTRO:
         * Validación estándar de unicidad en toda la tabla.
         */
        $sql = "SELECT COUNT(*) FROM Clientes WHERE nombre_cliente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre]);
    }
    
    /**
     * RETORNO DE ESTADO:
     * El script devuelve un texto plano que el frontend procesa:
     * 'existe' -> Deshabilita el botón de guardado.
     * 'libre'  -> Habilita el envío del formulario.
     */
    echo ($stmt->fetchColumn() > 0) ? 'existe' : 'libre';
}