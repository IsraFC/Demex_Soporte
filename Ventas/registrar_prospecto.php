<?php
/**
 * ARCHIVO: Ventas/registrar_prospecto.php
 * DESCRIPCIÓN: Registro manual de prospectos sin alterar el esquema de la BD.
 * Captura directa y limpia del Tipo de Interés Comercial (Maquinaria o Materia Prima).
 * La especificación granular de modelos o insumos se define al emitir la cotización.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 3.0 (Selector Directo de Interés Comercial)
 */

$page_title = "Registrar Prospecto | CRM Ventas";
require_once '../config/db.php';

$mensaje_error = '';
$canales_disponibles = ['Página Web', 'Facebook', 'YouTube', 'WhatsApp', 'Recomendación'];

// Procesamiento del Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre          = trim($_POST['nombre'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');
    $correo          = trim($_POST['correo'] ?? '');
    $pais            = trim($_POST['pais'] ?? 'México');
    $estado_region   = trim($_POST['estado_region'] ?? '');
    $maquina_interes = trim($_POST['maquina_interes'] ?? '');
    $canal_origen    = trim($_POST['canal_origen'] ?? 'Página Web');

    if (empty($nombre) or empty($telefono) or empty($estado_region) or empty($maquina_interes)) {
        $mensaje_error = "Todos los campos marcados con (*) son obligatorios.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insertar en tabla formulario
            $sqlForm = "INSERT INTO formulario (nombre, telefono, correo, pais, estado_region, maquina_interes, canal_origen, fecha_registro) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmtForm = $pdo->prepare($sqlForm);
            $stmtForm->execute([
                substr($nombre, 0, 50),
                substr($telefono, 0, 20),
                !empty($correo) ? substr($correo, 0, 255) : null,
                substr($pais, 0, 100),
                substr($estado_region, 0, 50),
                $maquina_interes,
                $canal_origen
            ]);

            $id_formulario = $pdo->lastInsertId();

            // 2. Insertar en tabla prospectos
            $sqlPros = "INSERT INTO prospectos (id_formulario, status_operativo, fecha_contacto, fecha_ultimo_contacto, status_comercial) 
                        VALUES (?, 'Consulta', NOW(), NOW(), 'Consultado')";
            $stmtPros = $pdo->prepare($sqlPros);
            $stmtPros->execute([$id_formulario]);

            $pdo->commit();

            header("Location: leads_crm.php?msg=success");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensaje_error = "Error al registrar en base de datos: " . $e->getMessage();
        }
    }
}

$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<div class="row mb-4 align-items-center animate__animated animate__fadeIn">
    <div class="col-md-7">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Prospecto</h1>
        <p class="text-muted small">Captura directa de prospectos para seguimiento en embudo comercial.</p>
    </div>
    <div class="col-md-5 text-md-end">
        <a href="leads_crm.php" class="btn btn-secondary py-2 px-3 fw-bold shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-arrow-left-short fs-5"></i> Regresar al Panel
        </a>
    </div>
</div>

<div class="row justify-content-center animate__animated animate__fadeInUp">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm p-4 bg-white rounded border-top border-4 border-danger">
            
            <?php if (!empty($mensaje_error)): ?>
                <div class="alert alert-danger shadow-sm border-0 border-start border-4 border-danger py-2 mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($mensaje_error) ?>
                </div>
            <?php endif; ?>

            <form action="registrar_prospecto.php" method="POST" id="formRegistroProspecto">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" maxlength="50" class="form-control" placeholder="Ej. Carlos Martínez" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Teléfono / WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="telefono" maxlength="20" class="form-control" placeholder="Ej. 2221234567" required value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Correo Electrónico</label>
                        <input type="email" name="correo" maxlength="255" class="form-control" placeholder="correo@ejemplo.com" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">País <span class="text-danger">*</span></label>
                        <input type="text" name="pais" maxlength="100" class="form-control" value="<?= htmlspecialchars($_POST['pais'] ?? 'México') ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Estado / Región <span class="text-danger">*</span></label>
                        <input type="text" name="estado_region" maxlength="50" class="form-control" placeholder="Ej. Puebla" value="<?= htmlspecialchars($_POST['estado_region'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Canal de Origen</label>
                        <select name="canal_origen" class="form-select">
                            <?php foreach ($canales_disponibles as $c): ?>
                                <option value="<?= $c ?>" <?= (($_POST['canal_origen'] ?? 'Página Web') === $c) ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- SELECTOR DIRECTO DE INTERÉS GENERAL -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Interés Comercial <span class="text-danger">*</span></label>
                        <select name="maquina_interes" class="form-select" required>
                            <option value="" selected disabled>Selecciona la línea de interés...</option>
                            <option value="Maquinaria" <?= (($_POST['maquina_interes'] ?? '') === 'Maquinaria') ? 'selected' : '' ?>>Maquinaria (Equipos de Helado)</option>
                            <option value="Materia Prima" <?= (($_POST['maquina_interes'] ?? '') === 'Materia Prima') ? 'selected' : '' ?>>Materia Prima (Bases y Saborizantes)</option>
                        </select>
                        <small class="text-muted" style="font-size: 0.72rem;">El o los productos específicos se elegirán al emitir la cotización formal.</small>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <a href="leads_crm.php" class="btn btn-light border px-4 me-2 fw-semibold">Cancelar</a>
                        <button type="submit" class="btn btn-danger px-4 fw-bold shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Guardar Prospecto
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>