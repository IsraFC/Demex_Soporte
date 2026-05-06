<?php
/**
 * ARCHIVO: actions/procesar_cliente.php
 * DESCRIPCIÓN: Controlador centralizado para la gestión de Clientes.
 * Implementa lógica dual (UPSERT) para manejar inserciones y actualizaciones
 * de registros en una sola transacción lógica.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.2
 */

require_once '../config/db.php';

// Bloque de seguridad para restringir el acceso a peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    /**
     * 1. CAPTURA DE DATOS
     * Se utiliza el operador de fusión de nulidad (??) para determinar la acción:
     * - Si $id es null: Operación INSERT (Nuevo registro).
     * - Si $id tiene valor: Operación UPDATE (Edición de registro existente).
     */
    $id     = $_POST['id_cliente'] ?? null; 
    $nombre = $_POST['nombre_cliente'];
    $tel    = $_POST['telefono'];
    $ubi    = $_POST['ubicacion'];

    try {
        if ($id) {
            /**
             * 2. LÓGICA DE ACTUALIZACIÓN (UPDATE)
             * Actualiza los datos del cliente basándose en su ID único.
             */
            $sql = "UPDATE Clientes SET nombre_cliente = ?, telefono = ?, ubicacion = ? WHERE id_cliente = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $tel, $ubi, $id]);
            $msg = "edit_success"; // Flag para alerta de edición exitosa
        } else {
            /**
             * 3. LÓGICA DE CREACIÓN (INSERT)
             * Genera un nuevo registro en el directorio de clientes.
             */
            $sql = "INSERT INTO Clientes (nombre_cliente, telefono, ubicacion) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $tel, $ubi]);
            $msg = "success"; // Flag para alerta de registro nuevo exitoso
        }

        // Redirección con parámetro de mensaje para feedback en la UI
        header("Location: ../clientes.php?msg=" . $msg);

    } catch (PDOException $e) {
        /**
         * 4. MANEJO DE EXCEPCIONES
         * Captura errores de base de datos (ej. pérdida de conexión) para evitar 
         * exponer trazas de error al usuario final.
         */
        header("Location: ../clientes.php?msg=error");
    }
} else {
    // Protección contra acceso directo por URL
    header("Location: ../clientes.php");
}

exit();