<?php
/**
 * ARCHIVO: config/backup.php
 * PROYECTO: Sistema de Gestión General DEMEX
 * DESCRIPCIÓN: Gestiona respaldos automáticos de la base de datos MySQL.
 * Implementa una política de retención de los últimos 5 archivos para optimizar
 * el espacio en disco y garantizar la disponibilidad de versiones históricas.
 * * @author Israel Fernández Carrera
 * @version 1.3
 */

/**
 * Ejecuta un volcado de la base de datos si el intervalo de tiempo ha expirado.
 * * @param PDO $pdo Instancia de conexión a la base de datos.
 */
function ejecutarRespaldoSilencioso($pdo) {
    date_default_timezone_set('America/Mexico_City');
    
    // Al estar en config/ (en la raíz), el archivo de marcas de tiempo se mantiene local en este directorio
    $archivo_registro = __DIR__ . '/ultimo_respaldo.txt'; 
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
     * Sincronizado con el nuevo nombre global de la base de datos.
     */
    $host = 'localhost';
    $user = 'root';
    $pass = ''; 
    $db   = 'portal_demex';
    
    // Ajuste de ruta relativo: la carpeta backups/ está saliendo de config/ un nivel hacia atrás
    $folder = __DIR__ . "/../backups/";
    
    // Generación de nombre de archivo único
    $timestamp = date('Y-m-d_Hi');
    $nuevo_backup = $folder . "db_backup_{$timestamp}.sql";
    
    // Ruta absoluta al ejecutable de MySQL en entorno XAMPP
    $mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

    /**
     * 3. EJECUCIÓN DEL RESPALDO (MYSQL DUMP)
     */
    $pass_param = empty($pass) ? "" : "-p\"{$pass}\""; 
    $comando = "\"{$mysqldump}\" --opt -h {$host} -u {$user} {$pass_param} {$db} > \"{$nuevo_backup}\" 2>&1";
    exec($comando);

    /**
     * 4. LÓGICA DE ROTACIÓN DE ARCHIVOS (Retention Policy: 5)
     */
    $archivos = glob($folder . "*.sql");
    
    usort($archivos, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });

    while (count($archivos) > 5) {
        $viejo = array_shift($archivos); 
        if (file_exists($viejo)) {
            unlink($viejo); 
        }
    }

    /**
     * 5. ACTUALIZACIÓN DEL REGISTRO DE CONTROL
     */
    file_put_contents($archivo_registro, $tiempo_actual);
}