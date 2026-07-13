<?php
/**
 * ARCHIVO: Ventas/registrar_cliente.php
 * DESCRIPCIÓN: Formulario de Alta Manual de Clientes y Procesador unificado en base de datos.
 * Guarda la Razón Social / Nombre Completo sin apellidos y permite el correo opcional.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 1.0 (Registro Manual Directo a Cartera)
 */

require_once '../config/db.php';

$mensaje_exito = false;
$error_msg = "";

// PROCESADOR DEL FORMULARIO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
    $telefono       = trim($_POST['telefono'] ?? '');
    $correo         = trim($_POST['correo'] ?? '');
    $rfc_receptor   = !empty($_POST['rfc_receptor']) ? strtoupper(trim($_POST['rfc_receptor'])) : 'XAXX010101000';
    $ubicacion      = trim($_POST['ubicacion'] ?? 'Puebla');
    $pais           = !empty($_POST['pais']) ? trim($_POST['pais']) : 'México';
    $tipo_cliente   = trim($_POST['tipo_cliente'] ?? 'Publico General');

    if (empty($nombre_cliente) || empty($telefono)) {
        $error_msg = "Por favor, llena los campos obligatorios (Nombre/Razón Social y Teléfono).";
    } else {
        try {
            $pdo->beginTransaction();

            // Verificar si la Razón Social ya existe para evitar duplicados
            $stmt_check = $pdo->prepare("SELECT id_cliente FROM clientes WHERE nombre_cliente = ? LIMIT 1");
            $stmt_check->execute([$nombre_cliente]);
            if ($stmt_check->fetchColumn()) {
                throw new Exception("Esta Razón Social o Nombre Completo ya se encuentra registrado en la cartera.");
            }

            // Inserción limpia en la tabla clientes (Estructura sin apellidos_cliente)
            $sql = "INSERT INTO clientes (nombre_cliente, telefono, correo, rfc_receptor, ubicacion, pais, id_prospecto_origen, tipo_cliente, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, ?, NULL, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre_cliente,
                $telefono,
                !empty($correo) ? $correo : null, // Guarda NULL si se dejó en blanco
                $rfc_receptor,
                $ubicacion,
                $pais,
                $tipo_cliente
            ]);

            $pdo->commit();
            $mensaje_exito = true;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = $e->getMessage();
        }
    }
}

$page_title = "Registrar Cliente Nuevo | CRM Ventas";
$modulo_actual = 'ventas';
include '../includes/header.php';
?>

<!-- Si el registro fue exitoso, disparamos la alerta de Isra y redireccionamos en caliente -->
<?php if ($mensaje_exito): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            title: '¡Cliente Registrado!',
            text: 'La empresa o distribuidor se ha dado de alta exitosamente en la cartera.',
            icon: 'success',
            timer: 2200,
            showConfirmButton: false,
            willClose: () => {
                window.location.href = 'clientes.php';
            }
        });
    });
</script>
<?php endif; ?>

<!-- Si ocurrió un error técnico o de duplicación, disparamos el feedback de SweetAlert -->
<?php if (!empty($error_msg)): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            title: 'Error al Registrar',
            text: '<?= htmlspecialchars($error_msg) ?>',
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Corregir Datos'
        });
    });
</script>
<?php endif; ?>

<div class="row mb-4 align-items-center">
    <div class="col-md-12">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-person-plus-fill"></i> Alta de Cliente</h1>
        <p class="text-muted small">Incorpora empresas, distribuidores o clientes directos de forma inmediata a la cartera general.</p>
    </div>
</div>

<div class="card-main mb-5 py-4 px-4 shadow-lg border-top border-4 border-danger bg-white rounded" style="max-width: 850px; margin: 0 auto;">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-file-earmark-person text-danger me-2"></i> Formulario de Registro Interno</h5>
    
    <form action="registrar_cliente.php" method="POST" id="formRegistroCliente">
        
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-8">
                <label for="nombre_cliente" class="form-label fw-semibold small text-dark">Nombre Completo o Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" placeholder="Ej. Máquinas de Helado del Centro SA o Juan Pérez" required>
            </div>
            <div class="col-12 col-md-4">
                <label for="tipo_cliente" class="form-label fw-semibold small text-dark">Perfil Comercial <span class="text-danger">*</span></label>
                <select class="form-select" id="tipo_cliente" name="tipo_cliente" required>
                    <option value="Publico General" selected>Público General</option>
                    <option value="Distribuidor">Distribuidor</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold small text-dark">Teléfono / WhatsApp de Contacto <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-whatsapp"></i></span>
                    <input type="text" class="form-control border-start-0" id="telefono" name="telefono" placeholder="10 dígitos (Ej. 2221234567)" maxlength="10" pattern="\d{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold small text-dark">Correo Electrónico (Opcional)</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control border-start-0" id="correo" name="correo" placeholder="correo@ejemplo.com">
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-4">
                <label for="rfc_receptor" class="form-label fw-semibold small text-dark">RFC Facturación</label>
                <input type="text" class="form-control text-uppercase" id="rfc_receptor" name="rfc_receptor" placeholder="XAXX010101000" maxlength="13">
            </div>
            <div class="col-12 col-md-4">
                <label for="ubicacion" class="form-label fw-semibold small text-dark">Estado / Región <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ej. Puebla" value="Puebla" required>
            </div>
            <div class="col-12 col-md-4">
                <label for="pais" class="form-label fw-semibold small text-dark">País <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="pais" name="pais" placeholder="Ej. México" value="México" required>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-4">
            <a href="clientes.php" class="btn btn-secondary py-2.5 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Cancelar y Regresar
            </a>
            <button type="submit" class="btn btn-danger py-2.5 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-person-check-fill me-2"></i> Guardar
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>