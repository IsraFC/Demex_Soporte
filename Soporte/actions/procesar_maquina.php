<?php
/**
 * ARCHIVO: actions/procesar_maquina.php
 * DESCRIPCIÓN: Controlador lógico para el alta de equipos.
 * Realiza la traducción de nombre de cliente a ID, calcula fechas de expiración
 * y gestiona la integridad de la base de datos mediante bloques try-catch.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.1
 */

// Importación de la conexión (Subiendo un nivel desde la carpeta /actions/)
require_once '../../config/db.php';
// Bloque de seguridad: Solo procesa si la petición es vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    /**
     * 1. CAPTURA Y SANITIZACIÓN DE DATOS
     * Se reciben las variables del formulario de registro.
     */
    $no_serie       = $_POST['no_serie'];
    $nombre_cliente = $_POST['nombre_cliente'];
    $modelo         = $_POST['modelo'];
    $fecha_inicio   = $_POST['fecha_inicio'];
    $vigencia_anios = (int)$_POST['vigencia']; // Casteo a entero para operaciones matemáticas

    /**
     * 2. TRADUCCIÓN NOMINAL (Nombre -> ID)
     * Como el formulario usa un datalist, recibimos el nombre, pero la BD 
     * requiere el id_cliente para mantener la integridad referencial.
     */
    $stmt_cli = $pdo->prepare("SELECT id_cliente FROM Clientes WHERE nombre_cliente = ?");
    $stmt_cli->execute([$nombre_cliente]);
    $cliente = $stmt_cli->fetch();

    if ($cliente) {
        $id_cliente = $cliente['id_cliente'];

        /**
         * 3. CÁLCULO DE VIGENCIA TEMPORAL
         * Se utiliza la función strtotime para sumar años a la fecha de inicio
         * de forma dinámica según la selección del usuario (1 o 2 años).
         */
        $fecha_termino = date('Y-m-d', strtotime($fecha_inicio . " + $vigencia_anios year"));

        /**
         * 4. PERSISTENCIA DE DATOS (INSERT)
         * Uso de Prepared Statements para prevenir ataques de Inyección SQL.
         */
        try {
            $sql = "INSERT INTO Equipos_Garantia (no_serie, id_cliente, modelo, fecha_inicio, fecha_termino) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$no_serie, $id_cliente, $modelo, $fecha_inicio, $fecha_termino]);

            // Redirección exitosa: El flujo concluye volviendo a la tabla principal
            header("Location: ../maquinas.php?msg=success");
            
        } catch (PDOException $e) {
            /**
             * MANEJO DE EXCEPCIONES:
             * Si la PK (no_serie) ya existe, el catch atrapa el error de BD
             * y evita el colapso del script, informando al frontend.
             */
            header("Location: ../maquinas.php?msg=error&detail=duplicado");
        }
    } else {
        // Validación de error: El cliente ingresado no existe en el catálogo
        header("Location: ../registro_maquina.php?msg=error_cliente");
    }
} else {
    // Protección de acceso directo: Si se accede por URL, redirige al inicio
    header("Location: ../maquinas.php");
}

// Finalización forzada del script tras redirección
exit();