<?php
/**
 * ARCHIVO: config/backup.php
 * PROYECTO: Soporte Técnico DEMEX
 * DESCRIPCIÓN: Gestiona respaldos automáticos de la base de datos MySQL.
 * Implementa una política de retención de los últimos 5 archivos para optimizar
 * el espacio en disco y garantizar la disponibilidad de versiones históricas.
 * 
 * @author Israel Fernández Carrera
 * @version 1.2
 */

/**
 * Ejecuta un volcado de la base de datos si el intervalo de tiempo ha expirado.
 * 
 * @param PDO $pdo Instancia de conexión a la base de datos.
 */
function ejecutarRespaldoSilencioso($pdo) {
    date_default_timezone_set('America/Mexico_City');
    
    // Definición de rutas y parámetros de control
    $archivo_registro = __DIR__ . '/ultimo_respaldo.txt'; // Almacena el timestamp de la última ejecución
    $tiempo_actual = time();
    $intervalo = 3600; // Bloqueo de seguridad: 1 hora (3600 segundos)

    /**
     * 1. VALIDACIÓN DE INTERVALO TEMPORAL
     * Evita sobrecargar el servidor con ejecuciones redundantes en cada carga de página.
     */
    if (file_exists($archivo_registro)) {
        $ultimo_respaldo = (int)file_get_contents($archivo_registro);
        // Si la diferencia es menor a una hora, abortamos el proceso
        if (($tiempo_actual - $ultimo_respaldo) < $intervalo) {
            return; 
        }
    }

    /**
     * 2. CONFIGURACIÓN DEL ENTORNO Y BASE DE DATOS
     * Nota: Estas credenciales deben actualizarse al migrar de XAMPP a producción.
     */
    $host = 'localhost';
    $user = 'root';
    $pass = ''; 
    $db   = 'demex_soporte';
    
    // Directorio de almacenamiento (Carpeta raíz del proyecto /backups/)
    $folder = __DIR__ . "/../backups/";
    
    // Generación de nombre de archivo único: db_backup_2026-04-29_1300.sql
    $timestamp = date('Y-m-d_Hi');
    $nuevo_backup = $folder . "db_backup_{$timestamp}.sql";
    
    // Ruta absoluta al ejecutable de MySQL en entorno XAMPP
    $mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

    /**
     * 3. EJECUCIÓN DEL RESPALDO (MYSQL DUMP)
     * Utiliza el operador de redirección '>' para crear el archivo físico.
     * El comando incluye '--opt' para optimizar el archivo para importaciones futuras.
     */
    $comando = "\"{$mysqldump}\" --opt -h {$host} -u {$user} -p\"{$pass}\" {$db} > \"{$nuevo_backup}\" 2>&1";
    exec($comando);

    /**
     * 4. LÓGICA DE ROTACIÓN DE ARCHIVOS (Retention Policy: 5)
     * Este bloque asegura que solo se conserven los 5 respaldos más recientes.
     */
    
    // Escaneo de la carpeta para listar todos los archivos de respaldo existentes
    $archivos = glob($folder . "*.sql");
    
    // Ordenamiento de la lista por fecha de modificación (de más antiguo a más reciente)
    usort($archivos, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });

    /**
     * Ciclo de limpieza:
     * Mientras existan más de 5 archivos, se elimina el más antiguo de la lista.
     */
    while (count($archivos) > 5) {
        $viejo = array_shift($archivos); // Extrae el archivo más antiguo (inicio del array)
        if (file_exists($viejo)) {
            unlink($viejo); // Eliminación física del archivo del sistema de archivos
        }
    }

    /**
     * 5. ACTUALIZACIÓN DEL REGISTRO DE CONTROL
     * Se guarda la marca de tiempo actual para reiniciar el contador de 1 hora.
     */
    file_put_contents($archivo_registro, $tiempo_actual);
}