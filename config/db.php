<?php
/**
 * db.php - Gestión de conexión a la base de datos demex_soporte
 * Utiliza el driver PDO para mayor seguridad y flexibilidad.
 */

$host = 'localhost';
$db   = 'demex_soporte';
$user = 'root';
$pass = ''; // Por defecto en XAMPP es vacío
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Reporta errores de SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Los resultados son arreglos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Seguridad ante inyecciones
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // En desarrollo mostramos el error, en producción se oculta
     die("Error de conexión: " . $e->getMessage());
}