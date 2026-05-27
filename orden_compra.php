<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$id    = $_GET['id']     ?? $_POST['id']     ?? null;
$cs_id = $_GET['cs_id']  ?? $_POST['cs_id']  ?? null;
$cs_ids = $_GET['cs_ids'] ?? $_POST['cs_ids'] ?? null;

$pdo = db();

if($cs_ids) {
    $idArray = explode(',', $cs_ids);
    $placeholders = implode(',', array_fill(0, count($idArray), '?'));
    $stmt = $pdo->prepare("SELECT cs.*, c.nombre_comercial, c.nit_cedula, c.direccion, c.email_facturacion, c.telefono, s.nombre as servicio_nombre FROM cliente_servicios cs JOIN clientes c ON cs.cliente_id = c.id JOIN servicios s ON cs.servicio_id = s.id WHERE cs.id IN ($placeholders)");
    $stmt->execute($idArray);
    $servicios = $stmt->fetchAll();
    if(empty($servicios)) die("Servicios no encontrados");
    $cliente = $servicios[0];
    $id = $cliente['cliente_id'];
} else if($cs_id) {
    $stmt = $pdo->prepare("SELECT cs.*, c.nombre_comercial, c.nit_cedula, c.direccion, c.email_facturacion, c.telefono, s.nombre as servicio_nombre FROM cliente_servicios cs JOIN clientes c ON cs.cliente_id = c.id JOIN servicios s ON cs.servicio_id = s.id WHERE cs.id = ?");
    $stmt->execute([$cs_id]);
    $data = $stmt->fetch();
    if(!$data) die("Servicio no encontrado");
    $cliente = $data;
    $servicios = [$data];
    $id = $data['cliente_id'];
} else if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch();
    if (!$cliente) die("Cliente no encontrado");
    $stmt = $pdo->prepare("SELECT cs.*, s.nombre as servicio_nombre FROM cliente_servicios cs JOIN servicios s ON cs.servicio_id = s.id WHERE cs.cliente_id = ? ORDER BY cs.fecha_vencimiento ASC");
    $stmt->execute([$id]);
    $servicios = $stmt->fetchAll();
} else {
    die("ID de cliente o servicio requerido");
}

// Override de items si vienen del editor del modal (POST)
if (!empty($_POST['items_json'])) {
    $overrideItems = json_decode($_POST['items_json'], true);
    if (is_array($overrideItems) && count($overrideItems) > 0) {
        $base = !empty($servicios) ? $servicios[0] : [];
        $servicios = array_map(function($item) use ($base) {
            $qty = intval($item['qty'] ?? 1);
            $precio = floatval($item['precio'] ?? 0);
            return array_merge($base, [
                'servicio_nombre'  => $item['descripcion'] ?? '',
                'monto_renovacion' => $precio * $qty,
                'descuento'        => floatval($item['descuento'] ?? 0),
                '_qty'             => $qty,
                '_precio_unit'     => $precio,
            ]);
        }, $overrideItems);
    }
}
$metodoPago = !empty($_POST['metodo_pago']) ? htmlspecialchars($_POST['metodo_pago']) : 'Transferencia Bancaria / PSE / QR';
$bancarios = [];
if (!empty($_POST['bancarios_json'])) {
    $bancarios = json_decode($_POST['bancarios_json'], true) ?: [];
}
$fechaUltPago = '';
if (!empty($_POST['fecha_ult_pago'])) {
    $d = DateTime::createFromFormat('Y-m-d', $_POST['fecha_ult_pago']);
    $fechaUltPago = $d ? $d->format('d/m/Y') : '';
}
$fechaEmision = date('d/m/Y');
if (!empty($_POST['fecha_emision'])) {
    $d = DateTime::createFromFormat('Y-m-d', $_POST['fecha_emision']);
    if ($d) $fechaEmision = $d->format('d/m/Y');
}
$linkPago = trim($_POST['link_pago'] ?? '');
$docTipo  = $_POST['doc_tipo'] ?? 'orden_compra';
$plantillaIdOverride = intval($_POST['plantilla_id'] ?? 0);

$totalOriginal = 0;
$totalDescuento = 0;
foreach($servicios as $s) {
    $totalOriginal += $s['monto_renovacion'];
    $totalDescuento += ($s['descuento'] ?? 0);
}
$totalFinal = $totalOriginal - $totalDescuento;
$docTipoLabels = [
    'orden_renovacion' => 'Orden de Renovación',
    'orden_compra'     => 'Orden de Compra',
    'cotizacion'       => 'Cotización',
    'factura'          => 'Factura',
];
$docTipoPrefixes = [
    'orden_renovacion' => 'QD-OR-',
    'orden_compra'     => 'QD-OC-',
    'cotizacion'       => 'QD-COT-',
    'factura'          => 'QD-FAC-',
];
$docTipoLabel = $docTipoLabels[$docTipo]   ?? 'Orden de Compra';
$orderPrefix  = $docTipoPrefixes[$docTipo] ?? (($cs_id || $cs_ids) ? 'S-' : 'OC-');
$orderNumber  = $orderPrefix . str_pad($id, 4, '0', STR_PAD_LEFT) . "-" . date('Ymd');

// Fetch template (by ID if provided, else default)
if ($plantillaIdOverride > 0) {
    $stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$plantillaIdOverride]);
    $template = $stmt->fetch();
} else {
    // Intentar buscar plantilla predeterminada de la categoría correspondiente
    $categoria = ($docTipo === 'orden_renovacion') ? 'orden_renovacion' : 'cotizacion';
    $stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE categoria = ? AND es_default = 1 AND activo = 1 LIMIT 1");
    $stmt->execute([$categoria]);
    $template = $stmt->fetch();
    
    // Si no se encuentra, buscar cualquier predeterminada activa
    if (!$template) {
        $stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE es_default = 1 AND activo = 1 LIMIT 1");
        $stmt->execute();
        $template = $stmt->fetch();
    }
}

if (!$template) {
    $template = [
        'layout_tipo' => 'ejecutiva',
        'color_primario' => '#0E0E0C',
        'color_secundario' => '#C6F24E',
        'fuente' => 'Poppins',
        'empresa_nombre' => 'QUANTUN Digital',
        'empresa_nit' => '900.567.123-4',
        'empresa_email' => 'gerencia@ceicar.co',
        'empresa_tel' => '+57 (314) 597-9983',
        'empresa_dir' => 'Cra 42 # 43A-12, Montería, Córdoba',
        'logo_url' => '',
        'notas_pie' => 'Gracias por su preferencia. El pago debe realizarse en los próximos 30 días.'
    ];
}
// Rellenar campos de empresa vacíos: primero crm_configuraciones, luego cualquier plantilla activa que los tenga
$empresaFields = ['empresa_nombre', 'empresa_nit', 'empresa_email', 'empresa_tel', 'empresa_dir'];
$needsFill = false;
foreach ($empresaFields as $f) { if (empty($template[$f])) { $needsFill = true; break; } }

if ($needsFill) {
    // 1) Desde crm_configuraciones
    try {
        $cfgRows = $pdo->query("SELECT clave, valor FROM crm_configuraciones")->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($empresaFields as $f) {
            if (empty($template[$f]) && !empty($cfgRows[$f])) $template[$f] = $cfgRows[$f];
        }
        if (empty($template['logo_url']) && !empty($cfgRows['logo_url'])) $template['logo_url'] = $cfgRows['logo_url'];
    } catch (Exception $e) {}

    // 2) Desde cualquier otra plantilla activa que tenga los datos
    $stillNeeds = false;
    foreach ($empresaFields as $f) { if (empty($template[$f])) { $stillNeeds = true; break; } }
    if ($stillNeeds) {
        try {
            $stmtE = $pdo->query("SELECT empresa_nombre, empresa_nit, empresa_email, empresa_tel, empresa_dir, logo_url FROM plantillas_factura WHERE activo = 1 AND (empresa_email <> '' OR empresa_tel <> '') ORDER BY es_default DESC, id ASC LIMIT 1");
            $donor = $stmtE->fetch();
            if ($donor) {
                foreach ($empresaFields as $f) {
                    if (empty($template[$f]) && !empty($donor[$f])) $template[$f] = $donor[$f];
                }
                if (empty($template['logo_url']) && !empty($donor['logo_url'])) $template['logo_url'] = $donor['logo_url'];
            }
        } catch (Exception $e) {}
    }
}

// Aplicar override de notas_pie DESPUÉS de obtener la plantilla
if (!empty($_POST['notas_pie_override'])) {
    $template['notas_pie'] = $_POST['notas_pie_override'];
}

$fmtCOP = function($n) {
    return 'COP ' . number_format($n, 0, ',', '.');
};

// ── Enriquecer servicios con datos del paquete (si aplica) ────────────────
function enrichWithPaquete(array &$servicios, PDO $pdo): void {
    foreach ($servicios as &$svc) {
        $nombre = trim($svc['nombre_display'] ?? $svc['servicio_nombre'] ?? '');
        if (!$nombre) continue;
        $ps = $pdo->prepare("SELECT id FROM paquetes WHERE nombre = ? AND activo = 1 LIMIT 1");
        $ps->execute([$nombre]);
        $pkg = $ps->fetch();
        if (!$pkg) continue;
        $pid = $pkg['id'];
        $is = $pdo->prepare("
            SELECT ss.nombre AS ss_nombre, ss.precio, ss.frecuencia, s.nombre AS svc_nombre
            FROM paquete_items pi
            JOIN sub_servicios ss ON ss.id = pi.sub_servicio_id
            JOIN servicios     s  ON s.id  = ss.servicio_id
            WHERE pi.paquete_id = ? ORDER BY pi.id ASC");
        $is->execute([$pid]);
        $svc['_pkg_items'] = $is->fetchAll();
        $fs = $pdo->prepare("SELECT texto FROM servicio_features WHERE paquete_id = ? ORDER BY orden ASC, id ASC");
        $fs->execute([$pid]);
        $svc['_pkg_features'] = array_column($fs->fetchAll(), 'texto');
    }
    unset($svc);
}
enrichWithPaquete($servicios, $pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $docTipoLabel ?> - <?= sanitize($cliente['nombre_comercial'] ?? '') ?></title>
    <style>
        body { font-family:'<?= $template['fuente'] ?>', system-ui, sans-serif; color: #0E0E0C; line-height: 1.5; margin: 0; padding: 40px; background: #FAFAF7; }
        .invoice { background: white; max-width: 900px; margin: auto; }
        .header-dark { background: <?= $template['color_primario'] ?>; padding: 0; }
        .header-accent { background: <?= $template['color_secundario'] ?>; height: 6px; }
        .header-content { padding: 28px 36px; display: flex; justify-content: space-between; align-items: flex-end; }
        .logo-section { }
        .logo-section h2 { margin: 0; color: white; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; }
        .company-details { margin-top: 14px; font-size: 11px; color: rgba(255,255,255,0.55); line-height: 1.8; }
        .order-info { text-align: right; }
        .order-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .15em; color: <?= $template['color_secundario'] ?>; }
        .order-number { font-size: 26px; font-weight: 700; color: white; letter-spacing: -1px; margin-top: 4px; }
        .order-date { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 4px; }
        .client-section { background: #FAFAF7; padding: 20px 36px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #E8E5DD; }
        .client-info h3 { margin: 0; font-size: 14px; font-weight: 700; color: #0E0E0C; }
        .client-info p { margin: 3px 0; font-size: 11px; color: #57544D; }
        .label-small { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #8A867C; margin-bottom: 6px; }
        .dates-group { display: flex; gap: 28px; }
        .date-item { text-align: center; }
        .date-value { font-size: 12px; font-weight: 700; color: #0E0E0C; }
        .items-section { padding: 28px 0; }
        .items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items-table col.col-desc  { width: auto; }
        .items-table col.col-qty   { width: 60px; }
        .items-table col.col-price { width: 110px; }
        .items-table col.col-total { width: 110px; }
        .items-table th { padding: 10px 14px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        .items-table th:first-child, .items-table td:first-child { padding-left: 36px; }
        .items-table th:last-child,  .items-table td:last-child  { padding-right: 36px; }
        .items-table td { padding: 11px 14px; font-size: 12px; color: #57544D; border-bottom: 1px solid #E8E5DD; }
        .items-table td.col-qty, .items-table td.col-price, .items-table td.col-total { white-space: nowrap; }
        .items-table tbody tr:nth-child(odd) { background: #FFFFFF; }
        .items-table tbody tr:nth-child(even) { background: #FAFAF7; }
        .amount { text-align: right; font-weight: 700; color: #0E0E0C; }
        .totals { display: flex; justify-content: flex-end; margin-top: 20px; padding: 0 36px; }
        .totals-box { min-width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 12px; font-size: 12px; color: #57544D; }
        .total-final { display: flex; justify-content: space-between; align-items: center; padding: 14px 12px; background: <?= $template['color_primario'] ?>; border-radius: 4px; margin-top: 10px; }
        .total-final span:first-child { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: <?= $template['color_secundario'] ?>; }
        .total-final span:last-child { font-size: 18px; font-weight: 700; color: white; }
        .footer-dark { background: <?= $template['color_primario'] ?>; padding: 14px 36px; display: flex; justify-content: space-between; align-items: center; }
        .footer-text { font-size: 11px; color: rgba(255,255,255,.5); }
        .footer-accent { width: 40px; height: 4px; background: <?= $template['color_secundario'] ?>; border-radius: 2px; }
        @media print { body { padding: 0; background: white; } .invoice { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="invoice">
        <!-- Header oscuro -->
        <div class="header-dark">
            <div class="header-accent"></div>
            <div class="header-content">
                <div class="logo-section">
                    <img src="<?= ($template['logo_url'] && trim($template['logo_url']) !== '') ? $template['logo_url'] : 'Assets/logo_quantun_digital_negro.png' ?>" alt="Logo" style="max-height:48px;object-fit:contain;filter:brightness(0) invert(1)">
                    <div class="company-details">
                        <?php if(!empty($template['empresa_nit'])): ?>
                            NIT: <?= htmlspecialchars($template['empresa_nit'] ?? '') ?> &nbsp;·&nbsp;
                        <?php endif; ?>
                        <?= htmlspecialchars($template['empresa_email'] ?? '') ?><br>
                        <?= htmlspecialchars($template['empresa_tel'] ?? '') ?> &nbsp;·&nbsp; <?= htmlspecialchars($template['empresa_dir'] ?? '') ?>
                    </div>
                </div>
                <div class="order-info">
                    <div class="order-label"><?= $docTipoLabel ?></div>
                    <div class="order-number"><?= $orderNumber ?></div>
                    <div class="order-date"><?= $fechaEmision ?></div>
                </div>
            </div>
        </div>

        <!-- Cliente + Fechas -->
        <div class="client-section">
            <div class="client-info">
                <div class="label-small">Facturado a</div>
                <h3><?= htmlspecialchars($cliente['nombre_comercial'] ?? '') ?></h3>
                <?php if(!empty($cliente['nit_cedula'])): ?>
                <p style="font-size:12px;font-weight:700;color:#0E0E0C;margin:2px 0">NIT / Cédula: <?= htmlspecialchars($cliente['nit_cedula'] ?? '') ?></p>
                <?php endif; ?>
                <p><?= htmlspecialchars($cliente['direccion'] ?? '') ?></p>
            </div>
            <div class="dates-group">
                <div class="date-item">
                    <div class="label-small">Emisión</div>
                    <div class="date-value"><?= $fechaEmision ?></div>
                </div>
                <?php if($fechaUltPago): ?>
                <div class="date-item">
                    <div class="label-small">Último Pago</div>
                    <div class="date-value"><?= $fechaUltPago ?></div>
                </div>
                <?php endif; ?>
                <div class="date-item">
                    <div class="label-small">Método de Pago</div>
                    <div class="date-value" style="font-size:11px"><?= $metodoPago ?></div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="items-section">
            <table class="items-table">
                <colgroup>
                    <col class="col-desc">
                    <col class="col-qty">
                    <col class="col-price">
                    <col class="col-total">
                </colgroup>
                <thead>
                    <tr style="background:<?= $template['color_primario'] ?>">
                        <th style="color:#ffffff">Descripción</th>
                        <th style="text-align:center;color:#ffffff;white-space:nowrap">Cant.</th>
                        <th style="text-align:right;color:#ffffff;white-space:nowrap">Precio</th>
                        <th style="text-align:right;color:#ffffff;white-space:nowrap">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($servicios as $svc):
                        $qty      = $svc['_qty'] ?? 1;
                        $precioU  = $svc['_precio_unit'] ?? $svc['monto_renovacion'];
                        $subtotal = ($svc['monto_renovacion'] ?? 0) - ($svc['descuento'] ?? 0);
                        $pkgItems = $svc['_pkg_items']    ?? [];
                        $pkgFeats = $svc['_pkg_features'] ?? [];
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($svc['servicio_nombre'] ?? '') ?></strong>
                            <?php if (!empty($pkgItems)): ?>
                            <table style="width:100%;margin-top:7px;border-collapse:collapse">
                                <?php foreach ($pkgItems as $item): ?>
                                <tr>
                                    <td style="padding:3px 0 3px 10px;font-size:11px;color:#8A867C;border-left:2px solid #E8E5DD;line-height:1.3">
                                        <span style="font-weight:600;color:#57544D"><?= htmlspecialchars($item['svc_nombre']) ?></span>
                                        <span style="color:#B0AB9F"> — </span><?= htmlspecialchars($item['ss_nombre']) ?>
                                    </td>
                                    <td style="padding:3px 0 3px 8px;font-size:11px;color:#8A867C;text-align:right;white-space:nowrap">
                                        $ <?= number_format($item['precio'], 0, ',', '.') ?><span style="opacity:.6"> /<?= htmlspecialchars($item['frecuencia'] ?? 'mes') ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?>
                            <?php if (!empty($pkgFeats)): ?>
                            <div style="margin-top:6px;padding-top:5px;border-top:1px dashed #E8E5DD">
                                <?php foreach ($pkgFeats as $feat): ?>
                                <div style="font-size:10px;color:#8A867C;padding:1px 0">&#10003; <?= htmlspecialchars($feat) ?></div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="col-qty" style="text-align:center"><?= $qty ?></td>
                        <td class="col-price" style="text-align:right">$&nbsp;<?= number_format($precioU, 0, ',', '.') ?></td>
                        <td class="col-total amount">$&nbsp;<?= number_format($subtotal, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="totals">
                <div class="totals-box">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>$ <?= number_format($totalOriginal, 0, ',', '.') ?></span>
                    </div>
                    <?php if($totalDescuento > 0): ?>
                    <div class="total-row" style="color: #ea4335;">
                        <span>Descuento</span>
                        <span>- $ <?= number_format($totalDescuento, 0, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-final">
                        <span>Total a pagar</span>
                        <span>$ <?= number_format($totalFinal, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datos Bancarios -->
        <?php
        $bancFields = [
            'titular' => 'Titular',
            'cedula'  => 'Cédula / NIT',
            'banco'   => 'Banco',
            'cuenta'  => 'N° de Cuenta',
            'tipo'    => 'Tipo de Cuenta',
            'llave'   => 'Nequi / Daviplata / QR',
        ];
        $anyBanc = !empty(array_filter($bancarios));
        if ($anyBanc):
        ?>
        <div style="padding:0 36px 28px">
            <div style="border:1.5px solid #E8E5DD;border-radius:6px;overflow:hidden">
                <!-- Header compacto -->
                <div style="display:flex;align-items:center;gap:8px;padding:10px 16px;background:<?= $template['color_primario'] ?>">
                    <svg width="13" height="13" fill="none" stroke="<?= $template['color_secundario'] ?>" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:<?= $template['color_secundario'] ?>">Datos para el pago</span>
                </div>
                <!-- Grilla de campos -->
                <div style="display:grid;grid-template-columns:1fr 1fr;background:#fff">
                    <?php
                    $idx = 0;
                    foreach($bancFields as $key => $label):
                        if(empty($bancarios[$key])) continue;
                        $bg = $idx % 2 === 0 ? '#FAFAF7' : '#fff';
                    ?>
                    <div style="padding:10px 16px;background:<?= $bg ?>;display:flex;flex-direction:column;gap:2px">
                        <span style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#B0AB9F"><?= $label ?></span>
                        <span style="font-size:12px;font-weight:600;color:#2D2B28"><?= htmlspecialchars($bancarios[$key] ?? '') ?></span>
                    </div>
                    <?php $idx++; endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Enlace de Pago -->
        <?php if ($linkPago): ?>
        <div style="padding:0 36px 24px;text-align:center">
            <a href="<?= htmlspecialchars($linkPago ?? '') ?>" target="_blank" style="display:inline-block;padding:14px 36px;background:<?= $template['color_primario'] ?>;color:<?= $template['color_secundario'] ?>;font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;border-radius:6px">
                💳 Pagar Ahora
            </a>
            <p style="font-size:11px;color:#8A867C;margin:8px 0 0"><?= htmlspecialchars($linkPago ?? '') ?></p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer-dark">
            <div class="footer-text"><?= htmlspecialchars($template['notas_pie'] ?? '') ?></div>
            <div class="footer-accent"></div>
        </div>
    </div>
</body>
</html>
