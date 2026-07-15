<?php
/**
 * ARCHIVO: config/backup.php
 * PROYECTO: Sistema de Gestión General DEMEX
 * DESCRIPCIÓN: Gestiona respaldos automáticos de la base de datos MySQL.
 * Implementa una política de retención de los últimos 5 archivos para optimizar
 * el espacio en disco y garantizar la disponibilidad de versiones históricas.
 * * @author Israel Fernández Carrera
 * @version 1.6
 */

function ejecutarRespaldoSilencioso($pdo) {
    date_default_timezone_set('America/Mexico_City');
    
    // PRUEBA DE VIDA: Forzamos que se cree el log para saber que la función responde
    file_put_contents(__DIR__ . '/debug_backup.log', "Iniciando proceso de respaldo... \n");
    
    $archivo_registro = __DIR__ . '/ultimo_respaldo.txt'; 
    $tiempo_actual = time();
    $intervalo = 3600;

    /**
     * 1. VALIDACIÓN DE INTERVALO TEMPORAL
     */
    if (file_exists($archivo_registro)) {
        $ultimo_respaldo = (int)file_get_contents($archivo_registro);
        if (($tiempo_actual - $ultimo_respaldo) < $intervalo) {
            file_put_contents(__DIR__ . '/debug_backup.log', "Proceso omitido: El último respaldo fue hace menos de una hora.\n", FILE_APPEND);
            return; 
        }
    }

    /**
     * 2. CONFIGURACIÓN DEL ENTORNO Y BASE DE DATOS
     */
    $db = 'portal_demex';

    // ASIGNACIÓN DE RUTAS ABSOLUTAS SEGÚN EL SISTEMA OPERATIVO
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // --- ENTORNO LOCAL (XAMPP WINDOWS) ---
        $folder = __DIR__ . "/../backups/";
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        $mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
        $timestamp = date('Y-m-d_Hi');
        $nuevo_backup = $folder . "db_backup_{$timestamp}.sql";
        $comando = "\"{$mysqldump}\" -h localhost -u root {$db} > \"{$nuevo_backup}\" 2>&1";
    } else {
        // --- ENTORNO PRODUCCIÓN (HETZNER LINUX) ---
        // Usamos la ruta real absoluta del sistema de archivos de tu servidor
        $folder = "/var/www/html/Soporte/backups/";
        
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        
        $db_user = 'admin_demex';
        $db_pass = 'M@rietta2015';
        $timestamp = date('Y-m-d_Hi');
        $nuevo_backup = $folder . "db_backup_{$timestamp}.sql";
        
        // Comando limpio con redirección estándar de errores
        $comando = "mysqldump -h localhost -u {$db_user} -p'{$db_pass}' {$db} > '{$nuevo_backup}' 2>&1";
    }

    /**
     * 3. EJECUCIÓN DEL RESPALDO
     */
    $output = [];
    $result_code = null;
    exec($comando, $output, $result_code);

    // Guardamos el código resultante en nuestra bitácora visual
    $log_status = "Código de salida del comando: " . $result_code . "\n" . implode("\n", $output) . "\n";
    file_put_contents(__DIR__ . '/debug_backup.log', $log_status, FILE_APPEND);

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
    file_put_contents(__DIR__ . '/debug_backup.log', "Respaldo finalizado con éxito. Archivo de control actualizado.\n", FILE_APPEND);
}