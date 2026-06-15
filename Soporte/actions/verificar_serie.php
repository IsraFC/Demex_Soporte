<?php
/**
 * ARCHIVO: actions/verificar_serie.php
 * DESCRIPCIÓN: Endpoint de validación asíncrona.
 * Verifica la existencia de un número de serie en la base de datos y retorna 
 * un estado simple para ser procesado por una petición AJAX en el frontend.
 * * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.1 - Adaptado para lecturas universales REQUEST
 */

// Importación de la conexión a la base de datos (Directorio superior)
require_once '../../config/db.php';

/**
 * LÓGICA DE VALIDACIÓN:
 * Se adapta a $_REQUEST para procesar peticiones dinámicas vía GET o POST.
 */
if (isset($_REQUEST['no_serie'])) {
    
    $no_serie = trim($_REQUEST['no_serie']);

    /**
     * CONSULTA DE EXISTENCIA:
     * Se realiza un conteo (COUNT) del número de serie proporcionado.
     * Al ser Llave Primaria (PK), el resultado solo puede ser 0 o 1.
     */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Equipos_Garantia WHERE no_serie = ?");
    $stmt->execute([$no_serie]);
    
    /**
     * RESPUESTA DEL SERVIDOR:
     * Si el conteo es mayor a 0, el registro ya existe (duplicado).
     * El frontend recibirá una cadena de texto simple ('existe' o 'libre').
     */
    echo ($stmt->fetchColumn() > 0) ? 'existe' : 'libre';
}
exit();