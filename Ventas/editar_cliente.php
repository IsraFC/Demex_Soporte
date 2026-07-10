<?php
/**
 * ARCHIVO: Ventas/editar_cliente.php
 * DESCRIPCIÓN: Formulario de Modificación y Procesador de Datos de Clientes.
 * Actualiza la Razón Social y ubicación manteniendo el estándar limpio sin apellidos.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 (Edición Unificada de Clientes)
 */

require_once '../config/db.php';

$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0;

if ($id_cliente <= 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Error: ID de cliente inválido.</div></div>";
    exit();
}

// 1. Recuperamos los datos actuales del cliente
$stmt_get = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1");
$stmt_get->execute([$id_cliente]);
$cliente = $stmt_get->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Error: El cliente solicitado no existe.</div></div>";
    exit();
}

$mensaje_exito = false;
$error_msg = "";

// 2. Procesador del Formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
    $telefono       = trim($_POST['telefono'] ?? '');
    $correo         = trim($_POST['correo'] ?? '');
    $rfc_receptor   = !empty($_POST['rfc_receptor']) ? strtoupper(trim($_POST['rfc_receptor'])) : 'XAXX010101000';
    $ubicacion      = trim($_POST['ubicacion'] ?? '');
    $pais           = trim($_POST['pais'] ?? 'México');
    $tipo_cliente   = trim($_POST['tipo_cliente'] ?? 'Publico General');

    if (empty($nombre_cliente) || empty($telefono)) {
        $error_msg = "Por favor, llena los campos obligatorios.";
    } else {
        try {
            $pdo->beginTransaction();

            // Validar que el nuevo nombre no choque con otro cliente diferente
            $stmt_check = $pdo->prepare("SELECT id_cliente FROM clientes WHERE nombre_cliente = ? AND id_cliente != ? LIMIT 1");
            $stmt_check->execute([$nombre_cliente, $id_cliente]);
            if ($stmt_check->fetchColumn()) {
                throw new Exception("Ya existe otra empresa registrada con este mismo nombre o Razón Social.");
            }

            // UPDATE unificado
            $sql = "UPDATE clientes 
                    SET nombre_cliente = ?, 
                        telefono = ?, 
                        correo = ?, 
                        rfc_receptor = ?, 
                        ubicacion = ?, 
                        pais = ?, 
                        tipo_cliente = ? 
                    WHERE id_cliente = ?";
            
            $stmt_up = $pdo->prepare($sql);
            $stmt_up->execute([
                $nombre_cliente,
                $telefono,
                !empty($correo) ? $correo : null,
                $rfc_receptor,
                $ubicacion,
                $pais,
                $tipo_cliente,
                $id_cliente
            ]);

            $pdo->commit();
            $mensaje_exito = true;
            
            // Refrescamos los datos para la UI
            $cliente['nombre_cliente'] = $nombre_cliente;
            $cliente['telefono'] = $telefono;
            $cliente['correo'] = $correo;
            $cliente['rfc_receptor'] = $rfc_receptor;
            $cliente['ubicacion'] = $ubicacion;
            $cliente['pais'] = $pais;
            $cliente['tipo_cliente'] = $tipo_cliente;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = $e->getMessage();
        }
    }
}

$page_title = "Editar Cliente | CRM Ventas";
$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<?php if ($mensaje_exito): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            title: '¡Cambios Guardados!',
            text: 'La información del cliente ha sido actualizada exitosamente.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            willClose: () => {
                window.location.href = 'clientes.php';
            }
        });
    });
</script>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            title: 'Error de Actualización',
            text: '<?= htmlspecialchars($error_msg) ?>',
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Revisar'
        });
    });
</script>
<?php endif; ?>

<div class="row mb-4 align-items-center">
    <div class="col-md-12">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-pencil-square"></i> Editar Cliente</h1>
        <p class="text-muted small">Modifica los datos generales, perfiles fiscales o de contacto de la cartera activa.</p>
    </div>
</div>

<div class="card-main mb-5 py-4 px-4 shadow-lg border-top border-4 border-danger bg-white rounded" style="max-width: 850px; margin: 0 auto;">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-file-earmark-person text-danger me-2"></i> Expediente ID: #<?= $id_cliente ?></h5>
    
    <form action="editar_cliente.php?id_cliente=<?= $id_cliente ?>" method="POST">
        
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-8">
                <label class="form-label fw-semibold small text-dark">Nombre Completo o Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre_cliente" value="<?= htmlspecialchars($cliente['nombre_cliente']) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold small text-dark">Perfil Comercial <span class="text-danger">*</span></label>
                <select class="form-select" name="tipo_cliente" required>
                    <option value="Publico General" <?= ($cliente['tipo_cliente'] === 'Publico General') ? 'selected' : '' ?>>Público General</option>
                    <option value="Distribuidor" <?= ($cliente['tipo_cliente'] === 'Distribuidor') ? 'selected' : '' ?>>Distribuidor</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold small text-dark">Teléfono / WhatsApp <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-whatsapp"></i></span>
                    <input type="text" class="form-control border-start-0" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>" maxlength="10" pattern="\d{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold small text-dark">Correo Electrónico</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control border-start-0" name="correo" value="<?= htmlspecialchars($cliente['correo'] ?? '') ?>" placeholder="correo@ejemplo.com">
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold small text-dark">RFC Facturación</label>
                <input type="text" class="form-control text-uppercase" name="rfc_receptor" value="<?= htmlspecialchars($cliente['rfc_receptor']) ?>" maxlength="13">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold small text-dark">Estado / Región <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="ubicacion" value="<?= htmlspecialchars($cliente['ubicacion']) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold small text-dark">País <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="pais" value="<?= htmlspecialchars($cliente['pais'] ?? 'México') ?>" required>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-4">
            <a href="clientes.php" class="btn btn-secondary py-2.5 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Cancelar y Regresar
            </a>
            <button type="submit" class="btn btn-danger py-2.5 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-file-earmark-check-fill me-2"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>