<?php
/**
 * ARCHIVO: actions/validar_garantia_fechas.php
 * DESCRIPCIÓN: Validador proactivo vía AJAX. Compara la fecha actual del servidor 
 * con la fecha de vencimiento del equipo para determinar el estatus de garantía.
 * @author Israel Fernández Carrera
 * @project Soporte Técnico DEMEX
 * @version 1.5
 */
header('Content-Type: application/json');
require_once '../config/db.php';

if (isset($_POST['no_serie'])) {
    $serie = trim($_POST['no_serie']);
    $resultado = 'Pendiente'; // Estatus por defecto si hay error o no se encuentra

    try {
        /**
         * CONSULTA DE VIGENCIA:
         * Se busca específicamente el campo 'fecha_termino' en la tabla de Equipos_Garantia.
         */
        $stmt = $pdo->prepare("SELECT fecha_termino FROM Equipos_Garantia WHERE no_serie = ? LIMIT 1");
        $stmt->execute([$serie]);
        $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($equipo && $equipo['fecha_termino']) {
            // Instanciamos objetos DateTime para una comparación de fechas precisa
            $fecha_termino = new DateTime($equipo['fecha_termino']);
            $hoy = new DateTime();
            
            /**
             * LÓGICA DE VALIDACIÓN:
             * Si la fecha actual (hoy) es menor o igual a la fecha de término,
             * el equipo aún goza de cobertura técnica.
             */
            if ($hoy <= $fecha_termino) {
                $resultado = 'Válida';
            } else {
                $resultado = 'No válida';
            }
        } else {
            /**
             * ESCENARIO: SERIE INEXISTENTE
             * Si la serie no está en el catálogo de garantías, se marca como Pendiente
             * para que el técnico valide manualmente.
             */
            $resultado = 'Pendiente';
        }
    } catch (Exception $e) {
        // En caso de fallo en la BD, mantenemos el estatus neutral
        $resultado = 'Pendiente';
    }

    // Retorno de respuesta para ser procesada por el frontend (jQuery/AJAX)
    echo json_encode(['resultado' => $resultado]);
}