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
     $error_msg = $e->getMessage();
     ?>
     <!DOCTYPE html>
     <html lang="es">
     <head>
         <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <title>Error de Conexión | DEMEX</title>
         <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
         <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
         <style>
             body { 
                 background-color: #F8F9FA; 
                 font-family: 'Poppins', sans-serif; 
                 display: flex; 
                 align-items: center; 
                 justify-content: center; 
                 height: 100vh; 
                 margin: 0; 
             }
             .error-card { 
                 background: #ffffff; 
                 border: none; 
                 border-radius: 20px; 
                 box-shadow: 0 8px 30px rgba(0,0,0,0.08); 
                 padding: 3rem 2rem; 
                 max-width: 500px; 
                 width: 90%;
                 text-align: center;
                 border-top: 5px solid #C62828;
             }
             .icon-container {
                 width: 80px;
                 height: 80px;
                 background-color: #fff5f5;
                 border-radius: 50%;
                 display: flex;
                 align-items: center;
                 justify-content: center;
                 margin: 0 auto 1.5rem auto;
             }
         </style>
     </head>
     <body>
         <div class="error-card">
             <div class="icon-container shadow-sm">
                 <i class="bi bi-database-fill-x text-danger" style="font-size: 2.5rem;"></i>
             </div>
             <h3 class="fw-bold text-dark mb-2">Servicio Fuera de Línea</h3>
             <p class="text-muted small mb-4">
                 No pudimos establecer conexión con la base de datos. Por favor, verifica que el servicio de MySQL esté activo y funcionando.
             </p>
             
             <div class="alert bg-light border text-start p-3 mb-4 rounded-3" style="word-break: break-word;">
                 <span class="d-block fw-bold small text-danger text-uppercase mb-1"><i class="bi bi-bug-fill me-1"></i> Detalle Técnico:</span>
                 <code class="text-muted small"><?php echo htmlspecialchars($error_msg); ?></code>
             </div>

             <button onclick="location.reload();" class="btn btn-danger rounded-pill px-4 py-2 fw-bold shadow-sm">
                 <i class="bi bi-arrow-clockwise me-2"></i> Reintentar Conexión
             </button>
         </div>
     </body>
     </html>
     <?php
     // Es crucial detener la ejecución para evitar que el resto del sistema intente cargar sin base de datos
     exit();
}