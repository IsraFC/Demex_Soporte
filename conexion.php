<?php
/**
 * @file conexion.php
 * @package Portal_Demex
 * @author Israel Fernández Carrera & Mauricio
 * @date 2026-05-21
 * @brief Componente de conexión centralizado para la base de datos mediante PDO.
 * * Este archivo inicializa la abstracción de datos para el portal unificado,
 * garantizando el manejo seguro de excepciones y configurando codificación UTF-8.
 */

// Configuración del entorno de base de datos
$host    = 'localhost';
$db      = 'demex_soporte'; // Nombre unificado de la Base de Datos
$user    = 'root';
$pass    = '';              // Ajustar credencial según el entorno local de Ubuntu
$charset = 'utf8mb4';

// Data Source Name: Define el driver, host, base de datos y cotejamiento
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

/**
 * Opciones de configuración para la instancia de PDO:
 * - ATTR_ERRMODE: Lanza excepciones nativas en caso de fallos en SQL.
 * - ATTR_DEFAULT_FETCH_MODE: Retorna los registros como arreglos asociativos por defecto.
 * - ATTR_EMULATE_PREPARES: Desactiva la emulación para mitigar ataques de Inyección SQL.
 */
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    /** @var PDO $pdo_portal Instancia global de conexión al repositorio de datos */
    $pdo_portal = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    /**
     * En producción se debe redirigir a una bitácora de errores (Log) interna.
     * Se enmascara el mensaje real para evitar exponer rutas del servidor.
     */
    die("Error crítico de infraestructura: No se pudo establecer comunicación con el almacén de datos.");
}