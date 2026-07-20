<?php
/**
 * ARCHIVO: Soporte/actions/buscar_geografia.php
 * DESCRIPCIÓN: Controlador asíncrono para la consulta del catálogo relacional de México.
 * Procesa peticiones AJAX y retorna bloques de opciones nativas según el estado provisto.
 * @author Israel Fernández Carrera
 * @project Soporte Desarrollo Mexicano (DEMEX)
 * @version 1.6
 */

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $estado_nombre = trim($_POST['estado'] ?? '');

    try {
        if ($accion === 'obtener_municipios_por_estado' && !empty($estado_nombre)) {
            // 1. Identificamos el ID del estado basándonos en el texto seleccionado
            $sqlEstado = "SELECT id FROM estados WHERE estado = ? LIMIT 1";
            $stmtEstado = $pdo->prepare($sqlEstado);
            $stmtEstado->execute([$estado_nombre]);
            $estado = $stmtEstado->fetch(PDO::FETCH_ASSOC);

            if ($estado) {
                // 2. Extracción de municipios mapeando de forma exacta la tabla intermedia y sus campos en plural
                $sqlMunicipios = "SELECT m.municipio 
                                  FROM municipios m
                                  INNER JOIN estados_municipios em ON m.id = em.municipios_id
                                  WHERE em.estados_id = ? 
                                  ORDER BY m.municipio ASC";
                
                $stmtMuni = $pdo->prepare($sqlMunicipios);
                $stmtMuni->execute([$estado['id']]);
                
                // Generamos las opciones nativas para el datalist utilizando la columna correcta
                while ($row = $stmtMuni->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . htmlspecialchars($row['municipio']) . '">';
                }
            }
            exit;
        }
    } catch (PDOException $e) {
        exit;
    }
}