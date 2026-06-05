<?php
/**
 * ARCHIVO: cotizaciones.php
 * DESCRIPCIÓN: Formulario de Configuración Comercial de Cotizaciones.
 * @author Sergio Mauricio Campos Carranza
 * @project Módulo Ventas DEMEX
 * @version 4.2 (Modelos Sincronizados y Cálculos Dinámicos)
 */

$page_title = "Generador de Cotizaciones | CRM Ventas";
require_once '../config/db.php';

// Catálogo estricto de los 8 modelos de máquinas reales (Estructura de tu código de leads)
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
$maquina_interes = "";

if ($id_prospecto > 0) {
    $sql_lead = "SELECT f.nombre, f.apellidos, f.maquina_interes 
                 FROM prospectos p 
                 INNER JOIN formulario f ON p.id_formulario = f.id_formulario 
                 WHERE p.id_prospecto = :id_prospecto LIMIT 1";
    $stmt_lead = $pdo->prepare($sql_lead);
    $stmt_lead->execute([':id_prospecto' => $id_prospecto]);
    $lead_data = $stmt_lead->fetch();
    
    if ($lead_data) {
        $cliente_nombre = $lead_data['nombre'] . ' ' . $lead_data['apellidos'];
        $maquina_interes = $lead_data['maquina_interes'];
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

<div class="card-main mb-4 py-4 px-4 shadow-sm border-top border-4 border-danger bg-white rounded">
    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-calculator text-danger me-2"></i> Configuración</h5>
    
    <form action="../actions/procesar_cotizacion.php" method="POST" id="formCotizacion">
        <input type="hidden" name="id_prospecto" value="<?= $id_prospecto ?>">

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Cliente / Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="cliente" value="<?= htmlspecialchars($cliente_nombre) ?>" placeholder="Nombre o Razón Social" required>
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
                <label class="form-label small d-block">&nbsp;</label>
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

<script>
const matrizPrecios = <?= json_encode($catalogo_precios) ?>;

// Fichas técnicas automatizadas para inyección dinámica
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

    // 1. Extraemos el precio del catálogo estático
    const precioBaseOriginal = (tipoCliente === 'Publico General') ? matrizPrecios[modeloTexto]['publico'] : matrizPrecios[modeloTexto]['distribuidor'];
    $('#precio_base_origen').val(precioBaseOriginal.toFixed(2));

    // 2. Rellenado dinámico de especificaciones técnicas
    $('#especificion_cotizada').val(especificacionesMaquinas[modeloTexto] || "");

    // 3. Cálculos matemáticos comerciales
    const montoDescuentoUnitario = precioBaseOriginal * (pctDesc / 100);
    const precioPactadoUnitario = precioBaseOriginal - montoDescuentoUnitario;
    
    const subtotalPactadoAcumulado = (precioPactadoUnitario * cantidad) + flete;
    const ivaCalculado = subtotalPactadoAcumulado * 0.16;
    const totalNeto = subtotalPactadoAcumulado + ivaCalculado;

    // 4. Formateador de Divisas en Pesos Mexicanos (MXN)
    const formatoMXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    // 5. Inyección a las etiquetas visuales
    $('#lbl_base_unitario').text(formatoMXN.format(precioBaseOriginal));
    $('#lbl_descuento_monto').text('-' + formatoMXN.format(montoDescuentoUnitario * cantidad));
    $('#lbl_flete_monto').text(formatoMXN.format(flete));
    $('#lbl_subtotal').text(formatoMXN.format(subtotalPactadoAcumulado));
    $('#lbl_iva').text(formatoMXN.format(ivaCalculado));
    $('#lbl_total').text(formatoMXN.format(totalNeto));

    // Mapeo de archivos de previsualización
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
    $('#maquina_select, #tipo_cliente').on('change', calcularFlujoComercial);
    $('#descuento_porcentaje, #costo_envio, #cantidad').on('keyup change', calcularFlujoComercial);
    
    calcularFlujoComercial();
});
</script>

<?php 
include '../includes/footer.php'; 
?>