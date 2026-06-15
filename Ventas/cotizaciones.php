<?php
/**
 * ARCHIVO: cotizaciones.php
 * DESCRIPCIÓN: Formulario de Configuración Comercial de Cotizaciones.
 * Soporta creación fluida por pasos asíncronos cuando se genera desde 0.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 4.4 (Sincronización Total de Máquina de Interés e Imagen Previa)
 */

$page_title = "Generador de Cotizaciones | CRM Ventas";
require_once '../config/db.php';

// Catálogo estricto de los 8 modelos de máquinas reales
$maquinas_reales = [
    'DEMEX 313',
    'DEMEX 313T',
    'DEMEX 513',
    'DEMEX 613',
    'DEMEX 1020',
    'DEMEX 125',
    'SPICE MT15',
    'SPICE MV89'
];

// Matriz de precios oficiales (Público y Distribuidor)
$catalogo_precios = [
        'SPICE MT15'  => ['publico' => 45885.00,  'distribuidor' => 38900.00],
        'SPICE MV89'  => ['publico' => 49335.00,  'distribuidor' => 41800.00],
        'DEMEX 313T'  => ['publico' => 50000.00,  'distribuidor' => 41500.00],
        'DEMEX 313'   => ['publico' => 66000.00,  'distribuidor' => 55000.00],
        'DEMEX 513'   => ['publico' => 78000.00,  'distribuidor' => 64000.00],
        'DEMEX 613'   => ['publico' => 88000.00,  'distribuidor' => 74000.00],
        'DEMEX 125'   => ['publico' => 98000.00,  'distribuidor' => 82000.00],
        'DEMEX 1020'  => ['publico' => 150000.00, 'distribuidor' => 130000.00]
];

// Detección de Prospecto enlazado si viene de la Bandeja de Leads
$id_prospecto = isset($_GET['id_prospecto']) ? intval($_GET['id_prospecto']) : 0;
$cliente_nombre = "";
$cliente_apellidos = "";
$cliente_correo = "";
$cliente_telefono = "";
$cliente_region = "Puebla";
$cliente_pais = "México";
$maquina_interes = "";
$canal_origen = "";

if ($id_prospecto > 0) {
    $sql_lead = "SELECT f.* FROM prospectos p 
                 INNER JOIN formulario f ON p.id_formulario = f.id_formulario 
                 WHERE p.id_prospecto = :id_prospecto LIMIT 1";
    $stmt_lead = $pdo->prepare($sql_lead);
    $stmt_lead->execute([':id_prospecto' => $id_prospecto]);
    $lead_data = $stmt_lead->fetch();
    
    if ($lead_data) {
        $cliente_nombre = $lead_data['nombre'];
        $cliente_apellidos = $lead_data['apellidos'];
        $cliente_correo = $lead_data['correo'];
        $cliente_telefono = $lead_data['telefono'];
        $cliente_region = $lead_data['estado_region'];
        $cliente_pais = $lead_data['pais'];
        $maquina_interes = $lead_data['maquina_interes'];
        $canal_origen = $lead_data['canal_origen'];
    }
}

include '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-12">
        <h1 class="fw-bold text-danger mb-0"><i class="bi bi-file-earmark-pdf"></i> Generador de Cotizaciones</h1>
        <p class="text-muted small">Configuración formal de precios, especificaciones y condiciones comerciales.</p>
    </div>
</div>

<div class="card-main mb-4 py-4 px-4 shadow-sm border-top border-4 border-danger bg-white rounded" id="cardPasoCliente">
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-badge-fill text-danger me-2"></i> Paso 1: Datos de Contacto del Cliente</h5>
    <p class="text-muted small">Registra los datos de contacto para mapearlos correctamente en el panel de control del CRM.</p>
    
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold small text-dark">Nombre(s) <span class="text-danger">*</span></label>
            <input type="text" class="form-control ctrl-lead" id="lead_nombre" value="<?= htmlspecialchars($cliente_nombre) ?>" placeholder="Ej. Sergio Mauricio" <?= ($id_prospecto > 0) ? 'readonly' : '' ?> required>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold small text-dark">Apellidos <span class="text-danger">*</span></label>
            <input type="text" class="form-control ctrl-lead" id="lead_apellidos" value="<?= htmlspecialchars($cliente_apellidos) ?>" placeholder="Ej. Campos Carranza" <?= ($id_prospecto > 0) ? 'readonly' : '' ?> required>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold small text-dark">Canal de Origen <span class="text-danger">*</span></label>
            <?php if ($id_prospecto > 0): ?>
                <input type="text" class="form-control" value="<?= htmlspecialchars($canal_origen) ?>" readonly>
            <?php else: ?>
                <select class="form-select ctrl-lead" id="lead_canal" required>
                    <option value="WhatsApp" selected>WhatsApp</option>
                    <option value="Facebook">Facebook</option>
                    <option value="YouTube">YouTube</option>
                    <option value="Página Web">Página Web</option>
                    <option value="Llamada Telefónica">Llamada Telefónica</option>
                    <option value="Recomendación">Recomendación</option>
                </select>
            <?php endif; ?>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold small text-dark">Teléfono Directo <span class="text-danger">*</span></label>
            <input type="text" class="form-control ctrl-lead" id="lead_telefono" value="<?= htmlspecialchars($cliente_telefono) ?>" placeholder="10 dígitos" maxlength="15" <?= ($id_prospecto > 0) ? 'readonly' : '' ?> required>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold small text-dark">Correo Electrónico</label>
            <input type="email" class="form-control ctrl-lead" id="lead_correo" value="<?= htmlspecialchars($cliente_correo) ?>" placeholder="cliente@correo.com" <?= ($id_prospecto > 0) ? 'readonly' : '' ?>>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold small text-dark">Estado / Región</label>
            <input type="text" class="form-control ctrl-lead" id="lead_region" value="<?= htmlspecialchars($cliente_region) ?>" placeholder="Ej. Puebla" <?= ($id_prospecto > 0) ? 'readonly' : '' ?>>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold small text-dark">País</label>
            <input type="text" class="form-control ctrl-lead" id="lead_pais" value="<?= htmlspecialchars($cliente_pais) ?>" placeholder="Ej. México" <?= ($id_prospecto > 0) ? 'readonly' : '' ?>>
        </div>
        
        <?php if ($id_prospecto <= 0): ?>
        <div class="col-12 col-md-6 mt-2">
            <label class="form-label fw-semibold small text-dark">Equipo de Interés Inicial <span class="text-danger">*</span></label>
            <select class="form-select ctrl-lead" id="lead_maquina" required>
                <option value="" selected disabled>Selecciona la máquina consultada...</option>
                <?php foreach ($maquinas_reales as $maquina): ?>
                    <option value="<?= htmlspecialchars($maquina) ?>"><?= htmlspecialchars($maquina) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 text-end border-top pt-3 mt-3">
            <button type="button" class="btn btn-danger px-4 fw-bold small" id="btnCrearLeadManual" style="border-radius: 6px;">
                Validar y Continuar a Cotización
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card-main mb-4 py-4 px-4 shadow-sm border-top border-4 border-danger bg-white rounded <?= ($id_prospecto <= 0) ? 'opacity-50' : '' ?>" id="cardSeccionCotizacion" style="<?= ($id_prospecto <= 0) ? 'pointer-events: none;' : '' ?>">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-calculator text-danger me-2"></i> Paso 2: Detalles y Precios de la Cotización</h5>
    
    <form action="../actions/procesar_cotizacion.php" method="POST" id="formCotizacion">
        <input type="hidden" name="id_prospecto" id="sec_id_prospecto" value="<?= $id_prospecto ?>">

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Cliente / Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control fw-bold bg-light" id="txt_cliente_fiscal" name="cliente" value="<?= htmlspecialchars($cliente_nombre . ' ' . $cliente_apellidos) ?>" placeholder="Se auto-rellenará..." readonly required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">RFC Receptor</label>
                <input type="text" class="form-control text-uppercase" name="rfc_receptor" placeholder="XAXX010101000" maxlength="13">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Sucursal</label>
                <input type="text" class="form-control" name="sucursal" value="Matriz" placeholder="Ej. Matriz Puebla">
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Dirección de Entrega<span class="text-danger">*</span></label>
                <textarea class="form-control" name="direccion_entrega" rows="2" placeholder="Dirección completa de entrega" required></textarea>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Cantidad</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" value="1" min="1" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Unidad de Medida</label>
                <input type="text" class="form-control" name="unidad" value="Pieza" readonly style="background-color: #f8f9fa;">
            </div>
        </div>

        <div class="row g-3 mb-3 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Selección del Modelo de Máquina <span class="text-danger">*</span></label>
                <select class="form-select" id="maquina_select" name="maquina" required>
                    <option value="" selected disabled>Selecciona un modelo...</option>
                    <?php foreach ($maquinas_reales as $maquina): ?>
                        <option value="<?= htmlspecialchars($maquina) ?>" <?= ($maquina_interes === $maquina) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($maquina) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Tipo de Cliente Comercial<span class="text-danger">*</span></label>
                <select class="form-select" id="tipo_cliente" name="tipo_cliente" required>
                    <option value="Publico General" selected>Público General</option>
                    <option value="Distribuidor">Distribuidor</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Precio Base de Lista ($ MXN)</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control fw-bold" id="precio_base_origen" name="precio_base_origen" readonly style="background-color: #f8f9fa;" step="0.01" required>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Descuento Especial</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="descuento_porcentaje" name="descuento_porcentaje" min="0" max="100" step="1" value="0">
                    <span class="input-group-text bg-light fw-bold">%</span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Costo de Envío</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">$</span>
                    <input type="number" class="form-control" id="costo_envio" name="costo_envio" min="0" step="0.01" value="0.00">
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 border-top pt-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Especificaciones Técnicas Incluidas</label>
                <textarea class="form-control small text-muted" id="especificion_cotizada" name="especificion_cotizada" style="background-color: #f8f9fa; height: 320px; resize: none;" placeholder="Se auto-rellenarán según la máquina seleccionada..."></textarea>
            </div>

            <div class="col-12 col-md-6 d-flex align-items-center justify-content-center">
                <div class="p-3 text-center rounded shadow-sm bg-light border w-100" style="min-height: 320px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: #fafafa;">
                    <small class="text-muted d-block mb-3 fw-semibold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.8px;">Vista Previa del Equipo</small>
                    <img id="img_maquina_preview" src="../img/maquinas/default.png" alt="Previsualización" class="img-fluid rounded animate__animated animate__fadeIn" style="max-height: 260px; width: auto; object-fit: contain; display: none;">
                    <div id="img_placeholder" class="text-muted small py-4"><i class="bi bi-image fs-2 d-block mb-2 text-danger"></i>Selecciona un modelo para ver su imagen</div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small">Notas / Observaciones</label>
                <textarea class="form-control" name="notas" rows="4" placeholder="Garantías, plazos de entrega o condiciones de pago..." style="height: 180px; resize: none;"></textarea>
            </div>

            <div class="col-12 col-md-6 ms-auto mt-3">
                <div class="p-3 rounded shadow-sm bg-light" style="border-left: 5px solid var(--primary-color);">
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Precio Unitario Base:</span>
                        <span id="lbl_base_unitario">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-danger fw-semibold">
                        <span>Descuento Otorgado:</span>
                        <span id="lbl_descuento_monto">-$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Gastos Logísticos (Envío):</span>
                        <span id="lbl_flete_monto">$0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2 fw-semibold text-dark">
                        <span>Precio Pactado Subtotal:</span>
                        <span id="lbl_subtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-muted small">
                        <span>IVA Traslado (16%):</span>
                        <span id="lbl_iva">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-success border-top pt-2">
                        <span>Gran Total Neto:</span>
                        <span id="lbl_total">$0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-3">
            <button type="submit" class="btn btn-danger py-2.5 px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-file-earmark-check-fill me-2"></i> Procesar y Guardar Cotización
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script>
const matrizPrecios = <?= json_encode($catalogo_precios) ?>;

const especificacionesMaquinas = {
    'SPICE MT15': "LÍNEA SPICE - HELADO SUAVE (25 LTS x HR)\n• Dimensiones: 75 x 56 x 78 cm | Peso: 95 kg\n• Fabricada en Acero Inoxidable\n• Potencia Energética: 2.0 KW | Corriente: 110V/60Hz\n• Componentes: Cilindros de 1.8 LT x 2 | Depósito de Alimentación: 5 LT x 2 | Motor: 1.0 HP\n• Características: Modo Nocturno o Preenfriado, Bomba de Aire con Niveles, Display y Control de Sistema Automático Digital, Sistema Contador de Helados, Reductor de Velocidad Hidráulico.\n• Refrigeración: Compresor de 1.0 HP (R410A) | Condensador: Aire/Chico R134A | Compresor de Preenfriado de 1/8 HP R134A | Regulador de temperatura de Modo Nocturno.\n• Requisito: Uso recomendado de regulador de corriente de 4 KVA, dejar libre espacio de ventilación de 40 cm a los lados y 15 cm atrás.",
    'SPICE MV89': "LÍNEA SPICE - HELADO SUAVE (25 LTS x HR)\n• Dimensiones: 75 x 56 x 138 cm | Peso: 120 kg\n• Fabricada en Acero Inoxidable\n• Potencia Energética: 2.0 KW | Corriente de Entrada: Monofásica 110V/60Hz\n• Componentes: Cilindros de 1.8 L x 2 | Depósito de Alimentación: 5L x 2 | Motor de 1.0 HP\n• Características: Modo Nocturno o Preenfriado, Bomba de Aire con Niveles, Display y Control de Sistema Automático Digital, Sistema Contador de Helados.\n• Refrigeración: Compresor de 1.0 HP (R410A) | Condensador de Aire/Mediano R134A | Compresor de Preenfriado de 1/8 HP R134A | Regulador de temperatura de Modo Nocturno.\n• Requisito: Uso recomendado de regulador de corriente de 4 KVA, dejar libre espacio de ventilación de 40 cm a los lados y 15 cm atrás.",
    'DEMEX 313T': "HELADO SUAVE (33 LTS x HR)\n• Dimensiones: 67 x 55 x 83 CM | Peso Neto: 115 KG\n• Fabricada en Acero Inoxidable\n• Potencia Energética: 2.7 KW/HR | Corriente de Entrada: Monofásica 110V/60 HZ\n• Componentes: Cilindros de 2 Litros x 2 | Depósito de Alimentación: 5 Litros x 2 | Motor de 1.5 HP | Micromotor 1400 RPM 120 Watts\n• Características: Modo Nocturno o Preenfriado, Bomba de Aire con Niveles, Tarjeta Electrónica Programable, Reductor de Velocidad Hidráulico, Display y Control de Sistema Automático Digital, Sistema de Lavado Automático, Sistema Contador de Helados.\n• Refrigeración: Compresor Panasonic 1.0 HP (R410) | Condensador: Aire / Mediano | Compresor de Preenfriado de 1/8 HP R134A | Regulador de Temperatura de Modo Nocturno.\n• Requisito: Uso recomendado de regulador de corriente de 4 KVA, dejar libre espacio de ventilación de 40 cm a los lados y 15 cm atrás.",
    'DEMEX 313': "HELADO SUAVE (35 LTS x HR)\n• Dimensiones: 67 x 55 x 138 CM | Peso Neto: 144 KG\n• Fabricada en Acero Inoxidable\n• Potencia Energética: 2.7 KW/HR | Corriente de Entrada: Monofásica 110V/60 HZ\n• Componentes: Cilindros de 2 Litros x 2 | Depósito de Alimentación: 5 Litros x 2 | Motor de 1.5 HP | Micromotor 1400 RPM 120 Watts\n• Características: Modo Nocturno o Preenfriado, Bomba de Aire con Niveles, Tarjeta Electrónica Programable, Reductor de Velocidad Hidráulico, Display y Control de Sistema Automático Digital, Sistema de Lavado Automático, Sistema Contador de Helados.\n• Refrigeración: Compresor Panasonic 1.0 HP (R410A) | Condensador: Aire / Grande | Compresor de Preenfriado de 1/8 HP R134A | Regulador de Temperatura de Modo Nocturno.\n• Requisito: Uso recomendado de regulador de corriente de 4 KVA, dejar libre espacio de ventilación de 40 cm a los lados y 15 cm atrás.",
    'DEMEX 513': "HELADO SUAVE (35 LTS x HR)\n• Dimensiones: 77 x 60 x 146 CM | Peso Neto: 160 KG\n• Fabricada en Acero Inoxidable\n• Potencia Energética: 2.7 KW/HR | Corriente de Entrada: Monofásica 110V/60 HZ\n• Componentes: Cilindros de 2 Litros x 2 | Depósito de Alimentación: 12 Litros x 2 | Motor de 1.5 HP | Micromotor 1400 RPM 120 Watts\n• Características: Modo Nocturno o Preenfriado, Bomba de Aire con Niveles, Tarjeta Electrónica Programable, Reductor de Velocidad Hidráulico, Display y Control de Sistema Automático Digital, Sistema de Lavado Automático, Sistema Contador de Helados.\n• Refrigeración: Compresor Panasonic 1.0 HP (R410A) | Condensador: Aire / Extra Grande | Compresor de Preenfriado de 1/8 HP R134A | Regulador de Temperatura de Modo Nocturno.\n• Requisito: Dejar libre espacio de ventilación de 40 cm por ambos lados y 15 cm en la parte trasera.",
    'DEMEX 613': "HELADO SUAVE (46-52 LTS x HR)\n• Dimensiones: 77 x 60 x 146 CM | Peso Neto: 175 KG\n• Fabricada en Acero Inoxidable\n• Potencia Energética: 3.7 KW/HR | Corriente de Entrada: Bifásica 220V/60HZ\n• Componentes: Cilindros de 2 Litros x 2 | Depósito de Alimentación: 12 Litros x 2 | Motor de 1.5 HP | Micromotor 1400 RPM 120 Watts\n• Características: Modo Nocturno o Preenfriado, Bomba de Aire con Niveles, Tarjeta Electrónica Programable, Reductor de Velocidad Hidráulico, Display y Control de Sistema Automático Digital, Sistema de Lavado Automático, Sistema Contador de Helados.\n• Refrigeración: Compresor Panasonic 3.0 HP (R410A) | Condensador: Aire / Extra Grande | Compresor de Enfriado de 1/8 HP R134A | Regulador de Temperatura de Preenfriado de Modo Nocturno.\n• Requisito: Dejar libre espacio de ventilación de 40 cm por ambos lados y 15 cm en la parte trasera.",
    'DEMEX 125': "HELADO DURO (PRODUCCIÓN CADA 9-11 MIN. TODO EL DÍA)\n• Dimensiones: 70 x 56 x 132 CM | Peso Neto: 180 KG\n• Fabricada en Acero Inoxidable | Batidor de Acero Inoxidable\n• Potencia Energética: 3.4 KW/HR | Corriente de Entrada: Monofásica 110V/60HZ\n• Componentes: Cilindro de 13.5 Litros | Motor de 1.5 HP | Micromotor 110V/60Hz 1450 RPM 150 Watts\n• Características: Tarjeta Electrónica Programable, Reductor de Velocidad Hidráulico, Display y Control de Sistema Automático Digital, Sistema de Lavado Automático.\n• Refrigeración: Compresor Panasonic 1.0 HP x 2 (R410A) | Condensador: Aire.\n• Requisito: Se recomienda conectar ampliamente a una pastilla (Brake) de 40 Amperes.",
    'DEMEX 1020': "HELADO DURO (PRODUCCIÓN CADA 8-10 MIN. TODO EL DÍA)\n• Dimensiones: 70 x 60 x 149 CM | Peso Neto: 200 KG\n• Fabricada en Acero Inoxidable | Batidor de Acero Inoxidable\n• Potencia Energética: 5.1 KW/HR | Corriente de Entrada: Bifásica 220V/60HZ\n• Componentes: Cilindros de 20 Litros | Motor de 2.0 HP | Micromotor 220V/60Hz 1400 RPM 120 Watts\n• Características: Tarjeta Electrónica Programable, Reductor de Velocidad Hidráulico, Display y Control de Sistema Automático Digital, Sistema de Lavado Automático.\n• Refrigeración: Compresores Panasonic 2.3 HP x 2 (R410A) | Condensadores: 2 (1 x Compresor) | Condensación: Aire.\n• Requisito: Se recomienda conectar ampliamente a una pastilla (Brake) de 30 Amperes Bifásica."
};

function calcularFlujoComercial() {
    const modeloTexto = $('#maquina_select').val();
    const tipoCliente = $('#tipo_cliente').val();
    const pctDesc = parseFloat($('#descuento_porcentaje').val()) || 0;
    const flete = parseFloat($('#costo_envio').val()) || 0;
    const cantidad = parseInt($('#cantidad').val()) || 1;

    if (!modeloTexto || !matrizPrecios[modeloTexto]) return;

    const precioBaseOriginal = (tipoCliente === 'Publico General') ? matrizPrecios[modeloTexto]['publico'] : matrizPrecios[modeloTexto]['distribuidor'];
    $('#precio_base_origen').off('change input').val(precioBaseOriginal.toFixed(2));

    $('#especificion_cotizada').val(especificacionesMaquinas[modeloTexto] || "");

    const montoDescuentoUnitario = precioBaseOriginal * (pctDesc / 100);
    const precioPactadoUnitario = precioBaseOriginal - montoDescuentoUnitario;
    
    const subtotalPactadoAcumulado = (precioPactadoUnitario * cantidad) + flete;
    const ivaCalculado = subtotalPactadoAcumulado * 0.16;
    const totalNeto = subtotalPactadoAcumulado + ivaCalculado;

    const formatoMXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    $('#lbl_base_unitario').text(formatoMXN.format(precioBaseOriginal));
    $('#lbl_descuento_monto').text('-' + formatoMXN.format(montoDescuentoUnitario * cantidad));
    $('#lbl_flete_monto').text(formatoMXN.format(flete));
    $('#lbl_subtotal').text(formatoMXN.format(subtotalPactadoAcumulado));
    $('#lbl_iva').text(formatoMXN.format(ivaCalculado));
    $('#lbl_total').text(formatoMXN.format(totalNeto));

    const imagenesMaquinas = {
        'SPICE MT15': 'spice_mt15.png',
        'SPICE MV89': 'spice_mv89.png',
        'DEMEX 313T': 'demex_313t.png',
        'DEMEX 313':  'demex_313.png',
        'DEMEX 513':  'demex_513.png',
        'DEMEX 613':  'demex_613.png',
        'DEMEX 125':  'demex_125.png',
        'DEMEX 1020': 'demex_1020.png'
    };

    if (imagenesMaquinas[modeloTexto]) {
        $('#img_maquina_preview').attr('src', '../img/maquinas/' + imagenesMaquinas[modeloTexto]).show();
        $('#img_placeholder').hide();
    } else {
        $('#img_maquina_preview').hide();
        $('#img_placeholder').show();
    }
}

$(document).ready(function() {
    // Escucha inicial del Paso 2
    $('#maquina_select, #tipo_cliente').on('change', function() {
        calcularFlujoComercial();
    });
    
    $('#descuento_porcentaje, #costo_envio, #cantidad').on('input', function() {
        calcularFlujoComercial();
    });
    
    calcularFlujoComercial();

    // --- NUEVO: EVENTO AJAX PASO 1 (ALTA MANUAL CON TRASLADO AUTOMÁTICO DE EQUIPO) ---
    $('#btnCrearLeadManual').on('click', function() {
        const nombre = $('#lead_nombre').val().trim();
        const apellidos = $('#lead_apellidos').val().trim();
        const canal = $('#lead_canal').val();
        const telefono = $('#lead_telefono').val().trim();
        const correo = $('#lead_correo').val().trim();
        const region = $('#lead_region').val().trim();
        const pais = $('#lead_pais').val().trim();
        const maquinaSeleccionada = $('#lead_maquina').val(); // <-- Captura el modelo del paso 1

        if (nombre === '' || apellidos === '' || telefono === '' || !maquinaSeleccionada) {
            Swal.fire({ title: 'Campos Obligatorios', text: 'Por favor, llena el nombre, apellidos, teléfono y equipo de interés.', icon: 'warning' });
            return;
        }

        $.ajax({
            url: '../actions/crear_lead_manual.php',
            method: 'POST',
            data: {
                nombre: nombre,
                apellidos: apellidos,
                canal_origen: canal,
                telefono: telefono,
                correo: correo,
                estado_region: region,
                pais: pais,
                maquina_interes: maquinaSeleccionada // <-- Mandamos el equipo real al Backend
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({ title: '¡Lead Registrado!', text: 'Expediente comercial creado. Configura los costos de la cotización.', icon: 'success', timer: 1500, showConfirmButton: false });
                    
                    // 1. Inyectamos el ID generado en el formulario oculto de la cotización
                    $('#sec_id_prospecto').val(response.id_prospecto);
                    
                    // 2. Concatenamos e inyectamos el nombre completo en la sección fiscal
                    $('#txt_cliente_fiscal').val(nombre + ' ' + apellidos);
                    
                    // 3. CAMBIO CLAVE: Sincroniza la máquina seleccionada con el select del Paso 2
                    $('#maquina_select').val(maquinaSeleccionada).trigger('change');
                    
                    // 4. Desbloqueamos visualmente el paso 2 de la cotización
                    $('#cardSeccionCotizacion').removeClass('opacity-50').css('pointer-events', 'auto');
                    
                    // 5. Bloqueamos los controles del paso 1 para mantener integridad
                    $('.ctrl-lead').attr('readonly', true).attr('disabled', true);
                    $('#btnCrearLeadManual').hide();
                } else {
                    Swal.fire({ title: 'Error', text: response.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error de Servidor', text: 'No se pudo registrar el expediente comercial de forma manual.', icon: 'error' });
            }
        });
    });
});
</script>