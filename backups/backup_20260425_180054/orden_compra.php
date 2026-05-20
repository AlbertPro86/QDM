<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$id = $_GET['id'] ?? null;
$cs_id = $_GET['cs_id'] ?? null;
$cs_ids = $_GET['cs_ids'] ?? null;

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

$totalOriginal = 0;
$totalDescuento = 0;
foreach($servicios as $s) {
    $totalOriginal += $s['monto_renovacion'];
    $totalDescuento += ($s['descuento'] ?? 0);
}
$totalFinal = $totalOriginal - $totalDescuento;
$orderNumber = ($cs_id || $cs_ids ? "S-" : "OC-") . str_pad($id, 4, '0', STR_PAD_LEFT) . "-" . date('Ymd');

// Fetch default template
$stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE es_default = 1 AND activo = 1 LIMIT 1");
$stmt->execute();
$template = $stmt->fetch();

if (!$template) {
    $template = [
        'layout_tipo' => 'ejecutiva',
        'color_primario' => '#0f172a',
        'color_secundario' => '#c9f31d',
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

$fmtCOP = function($n) {
    return 'COP ' . number_format($n, 0, ',', '.');
};

function generarTablaServicios($servicios, $fmtCOP) {
    $html = '';
    foreach($servicios as $svc) {
        $subtotal = $svc['monto_renovacion'] - ($svc['descuento'] ?? 0);
        $html .= '<tr style="border-bottom:1px solid #f1f5f9">
            <td style="padding:11px 14px;font-size:12px;color:#475569">' . htmlspecialchars($svc['servicio_nombre']) . '</td>
            <td style="padding:11px 14px;font-size:12px;color:#475569;text-align:center">1</td>
            <td style="padding:11px 14px;font-size:12px;color:#475569;text-align:right">$ ' . number_format($svc['monto_renovacion'], 0, ',', '.') . '</td>
            <td style="padding:11px 14px;font-size:12px;font-weight:700;color:#0f172a;text-align:right">$ ' . number_format($subtotal, 0, ',', '.') . '</td>
        </tr>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra - <?= sanitize($cliente['nombre_comercial']) ?></title>
    <style>
        body { font-family:'<?= $template['fuente'] ?>', system-ui, sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 40px; background: #f3f4f6; }
        .invoice { background: white; max-width: 900px; margin: auto; }
        .header-dark { background: <?= $template['color_primario'] ?>; padding: 0; }
        .header-accent { background: <?= $template['color_secundario'] ?>; height: 6px; }
        .header-content { padding: 28px 36px; display: flex; justify-content: space-between; align-items: flex-end; }
        .logo-section { }
        .logo-section h2 { margin: 0; color: white; font-size: 20px; font-weight: 900; letter-spacing: -0.5px; }
        .company-details { margin-top: 14px; font-size: 11px; color: rgba(255,255,255,0.55); line-height: 1.8; }
        .order-info { text-align: right; }
        .order-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .15em; color: <?= $template['color_secundario'] ?>; }
        .order-number { font-size: 26px; font-weight: 900; color: white; letter-spacing: -1px; margin-top: 4px; }
        .order-date { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 4px; }
        .client-section { background: #f8fafc; padding: 20px 36px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; }
        .client-info h3 { margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; }
        .client-info p { margin: 3px 0; font-size: 11px; color: #64748b; }
        .label-small { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 6px; }
        .dates-group { display: flex; gap: 28px; }
        .date-item { text-align: center; }
        .date-value { font-size: 12px; font-weight: 700; color: #0f172a; }
        .items-section { padding: 28px 36px; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table thead tr { background: <?= $template['color_primario'] ?>; }
        .items-table th { padding: 10px 14px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: <?= $template['color_secundario'] ?>; }
        .items-table td { padding: 11px 14px; font-size: 12px; color: #475569; border-bottom: 1px solid #f1f5f9; }
        .items-table tr:nth-child(odd) { background: white; }
        .items-table tr:nth-child(even) { background: #f8fafc; }
        .amount { text-align: right; font-weight: 700; color: #0f172a; }
        .totals { display: flex; justify-content: flex-end; margin-top: 20px; }
        .totals-box { min-width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 12px; font-size: 12px; color: #64748b; }
        .total-final { display: flex; justify-content: space-between; align-items: center; padding: 14px 12px; background: <?= $template['color_primario'] ?>; border-radius: 8px; margin-top: 10px; }
        .total-final span:first-child { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: <?= $template['color_secundario'] ?>; }
        .total-final span:last-child { font-size: 18px; font-weight: 900; color: white; }
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
                        <?php if($template['empresa_nit']): ?>
                            NIT: <?= htmlspecialchars($template['empresa_nit']) ?> &nbsp;·&nbsp;
                        <?php endif; ?>
                        <?= htmlspecialchars($template['empresa_email']) ?><br>
                        <?= htmlspecialchars($template['empresa_tel']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($template['empresa_dir']) ?>
                    </div>
                </div>
                <div class="order-info">
                    <div class="order-label">Orden de Compra</div>
                    <div class="order-number"><?= $orderNumber ?></div>
                    <div class="order-date"><?= date('d/m/Y') ?></div>
                </div>
            </div>
        </div>

        <!-- Cliente + Fechas -->
        <div class="client-section">
            <div class="client-info">
                <div class="label-small">Facturado a</div>
                <h3><?= htmlspecialchars($cliente['nombre_comercial']) ?></h3>
                <p><?= htmlspecialchars($cliente['direccion'] ?? '') ?></p>
            </div>
            <div class="dates-group">
                <div class="date-item">
                    <div class="label-small">Emisión</div>
                    <div class="date-value"><?= date('d/m/Y') ?></div>
                </div>
                <div class="date-item">
                    <div class="label-small">Método de Pago</div>
                    <div class="date-value" style="font-size:11px">Transferencia Bancaria / PSE / QR</div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th style="text-align:center">Qty</th>
                        <th style="text-align:right">Precio</th>
                        <th style="text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($servicios as $svc):
                        $subtotal = $svc['monto_renovacion'] - ($svc['descuento'] ?? 0);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($svc['servicio_nombre']) ?></td>
                        <td style="text-align:center">1</td>
                        <td style="text-align:right">$ <?= number_format($svc['monto_renovacion'], 0, ',', '.') ?></td>
                        <td class="amount">$ <?= number_format($subtotal, 0, ',', '.') ?></td>
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

        <!-- Footer -->
        <div class="footer-dark">
            <div class="footer-text"><?= htmlspecialchars($template['notas_pie']) ?></div>
            <div class="footer-accent"></div>
        </div>
    </div>
</body>
</html>
