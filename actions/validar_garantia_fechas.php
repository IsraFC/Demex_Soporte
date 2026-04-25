<?php
/**
 * ARCHIVO: actions/validar_garantia_fechas.php
 * DESCRIPCIÓN: Compara la fecha actual con la fecha_termino de la tabla.
 */
header('Content-Type: application/json');
require_once '../config/db.php';

if (isset($_POST['no_serie'])) {
    $serie = trim($_POST['no_serie']);
    $resultado = 'Pendiente'; 

    try {
        // Seleccionamos la fecha de término real de tu DB
        $stmt = $pdo->prepare("SELECT fecha_termino FROM Equipos_Garantia WHERE no_serie = ? LIMIT 1");
        $stmt->execute([$serie]);
        $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($equipo && $equipo['fecha_termino']) {
            $fecha_termino = new DateTime($equipo['fecha_termino']);
            $hoy = new DateTime();
            
            // LÓGICA DIRECTA:
            // Si HOY es menor o igual a la FECHA DE TÉRMINO, la garantía es Válida.
            if ($hoy <= $fecha_termino) {
                $resultado = 'Válida';
            } else {
                $resultado = 'No válida';
            }
        } else {
            // Si la serie no existe en Equipos_Garantia
            $resultado = 'Pendiente';
        }
    } catch (Exception $e) {
        $resultado = 'Pendiente';
    }

    echo json_encode(['resultado' => $resultado]);
}