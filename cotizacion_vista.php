<?php
/**
 * CRM QUANTUN Digital - Vista Imprimible de Cotización
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$id = intval($_GET['id'] ?? 0);
if (!$id) die('Cotización no encontrada');

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
$stmt->execute([$id]);
$cot = $stmt->fetch();

if (!$cot) die('Cotización no encontrada');

$items = json_decode($cot['items'], true) ?? [];
$fechaEmision = date('d/m/Y', strtotime($cot['created_at']));
$fechaVence = date('d/m/Y', strtotime($cot['created_at'] . ' +' . $cot['vigencia_dias'] . ' days'));
$numero = $cot['numero'];
$total = floatval($cot['total']);
$subtotal = floatval($cot['subtotal']);
$descuento = floatval($cot['descuento']);

// ── Plantilla ─────────────────────────────────────────────────────────────────
$plantilla = null;
$plantillaId = intval($_GET['plantilla_id'] ?? 0);
if ($plantillaId) {
    $ps = $pdo->prepare("SELECT * FROM plantillas_factura WHERE id = ? AND activo = 1");
    $ps->execute([$plantillaId]);
    $plantilla = $ps->fetch() ?: null;
}
if (!$plantilla) {
    // Intentar plantilla default
    $plantilla = $pdo->query("SELECT * FROM plantillas_factura WHERE es_default = 1 AND activo = 1 LIMIT 1")->fetch() ?: null;
}

// Tokens de diseño con fallback a defaults
$pColPrim  = $plantilla['color_primario']   ?? '#0f172a';
$pColSec   = $plantilla['color_secundario'] ?? '#c9f31d';
$pNombreEmp = $plantilla['empresa_nombre'] ?? 'QUANTUN Digital';
$pEmailEmp  = $plantilla['empresa_email']  ?? 'contacto@quantundigital.com';
$pTelEmp    = $plantilla['empresa_tel']    ?? '333 274 7801';
$pDirEmp    = $plantilla['empresa_dir']    ?? '';
$pNitEmp    = $plantilla['empresa_nit']    ?? '';
$pLogoUrl   = $plantilla['logo_url']       ?? '';
$pNotasPie  = $plantilla['notas_pie']      ?? '';
$pNombrePlan = $plantilla['nombre']        ?? 'Básica';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización <?= $numero ?></title>
    <style>
        :root {
            --p-col1: <?= sanitize($pColPrim) ?>;
            --p-col2: <?= sanitize($pColSec) ?>;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #0f172a;
            background: #f3f4f6;
            padding: 20px;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .actions button, .actions a {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
            font-family: inherit;
        }
        .actions .btn-print {
            background: var(--p-col1);
            color: white;
        }
        .actions .btn-print:hover { opacity: .85; }
        .actions .btn-accent {
            background: var(--p-col2);
            color: #0f172a;
        }
        .actions .btn-accent:hover { opacity: .88; }
        .invoice-box {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 40px;
            gap: 20px;
            align-items: start;
        }
        .logo {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .company-info {
            text-align: right;
            color: #64748b;
            font-size: 12px;
        }
        .title {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--p-col1);
            padding-bottom: 20px;
        }
        .title h1 {
            font-size: 28px;
            font-weight: 900;
            color: var(--p-col1);
            margin-bottom: 4px;
        }
        .title p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }
        .billing {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
            font-size: 12px;
        }
        .billing-column {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .billing-label {
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .billing-value {
            color: #0f172a;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 12px;
        }
        thead {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        thead th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        tfoot {
            background: #f8fafc;
            border-top: 2px solid #e2e8f0;
        }
        tfoot td {
            padding: 12px 14px;
            font-weight: 600;
            color: #0f172a;
            font-size: 13px;
        }
        .desc-col {
            text-align: left;
        }
        .type-col {
            text-align: center;
            color: #64748b;
            font-size: 11px;
        }
        .freq-col {
            text-align: center;
            color: #64748b;
            font-size: 11px;
        }
        .qty-col {
            text-align: center;
        }
        .price-col {
            text-align: right;
        }
        .subtotal-col {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
        }
        .totals {
            margin-bottom: 30px;
            text-align: right;
            font-size: 12px;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            gap: 100px;
            margin-bottom: 8px;
        }
        .total-label {
            min-width: 120px;
            text-align: right;
            color: #64748b;
        }
        .total-value {
            min-width: 100px;
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }
        .grand-total {
            border-top: 2px solid #e2e8f0;
            padding-top: 12px;
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
            text-align: center;
        }
        .validity-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        @media print {
            body { background: white; padding: 0; }
            .actions { display: none; }
            .invoice-box { max-width: 100%; border: none; box-shadow: none; }
            @page { margin: 20mm; }
        }
        @media screen and (max-width: 768px) {
            .invoice-box { padding: 20px; }
            .header { grid-template-columns: 1fr; text-align: center; }
            .company-info { text-align: center; }
            .billing { grid-template-columns: 1fr; gap: 20px; }
            .title h1 { font-size: 20px; }
            table { font-size: 11px; }
            thead th, tbody td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<div class="actions">
    <button class="btn-print" onclick="window.print()">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H9a2 2 0 00-2 2v2a2 2 0 002 2h6a2 2 0 002-2v-2a2 2 0 00-2-2m-6-4h.01M9 16h.01"/>
        </svg>
        Imprimir / Guardar PDF
    </button>
    <?php if ($pNombrePlan !== 'Básica'): ?>
    <span style="display:inline-flex;align-items:center;gap:5px;padding:0 12px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:11px;font-weight:700;color:#64748b;background:#f8fafc">
        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        <?= sanitize($pNombrePlan) ?>
    </span>
    <?php endif; ?>
</div>

<div class="invoice-box">
    <!-- Barra de color de plantilla -->
    <div style="height:4px;background:linear-gradient(90deg,<?= sanitize($pColPrim) ?> 0%,<?= sanitize($pColSec) ?> 100%);margin:-40px -40px 32px;border-radius:10px 10px 0 0"></div>
    <div class="header">
        <div class="logo">
            <?php if ($pLogoUrl): ?>
                <img src="<?= sanitize($pLogoUrl) ?>" alt="<?= sanitize($pNombreEmp) ?>"
                     style="height:40px;object-fit:contain;display:block;filter:brightness(0)">
            <?php else: ?>
                <img src="Assets/logo_quantun_digital_negro.png" alt="QUANTUN Digital"
                     style="height:36px;object-fit:contain;display:block;filter:brightness(0)">
            <?php endif; ?>
        </div>
        <div class="company-info">
            <div style="font-weight:800;margin-bottom:4px;color:<?= sanitize($pColPrim) ?>"><?= sanitize($pNombreEmp) ?></div>
            <?php if ($pNitEmp): ?><div>NIT: <?= sanitize($pNitEmp) ?></div><?php endif; ?>
            <?php if ($pEmailEmp): ?><div><?= sanitize($pEmailEmp) ?></div><?php endif; ?>
            <?php if ($pTelEmp): ?><div><?= sanitize($pTelEmp) ?></div><?php endif; ?>
            <?php if ($pDirEmp): ?><div><?= sanitize($pDirEmp) ?></div><?php endif; ?>
        </div>
    </div>

    <div class="title">
        <h1>COTIZACIÓN</h1>
        <p>#<?= sanitize($numero) ?> • Emitida el <?= $fechaEmision ?></p>
    </div>

    <div class="billing">
        <div class="billing-column">
            <div class="billing-label">Cotización para:</div>
            <div class="billing-value">
                <strong><?= sanitize($cot['nombre_cliente']) ?></strong><br>
                <?php if ($cot['email']): ?>
                    <?= sanitize($cot['email']) ?><br>
                <?php endif; ?>
                <?php if ($cot['whatsapp']): ?>
                    <?= sanitize($cot['whatsapp']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="billing-column" style="text-align:right">
            <div class="billing-label">Válida hasta:</div>
            <div class="billing-value">
                <strong><?= $fechaVence ?></strong><br>
                <span style="color:#ef4444;font-weight:600"><?= $cot['vigencia_dias'] ?> días</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:50%">Descripción</th>
                <th class="type-col" style="width:12%">Tipo</th>
                <th class="freq-col" style="width:12%">Frecuencia</th>
                <th class="qty-col" style="width:10%">Cant.</th>
                <th class="price-col" style="width:16%">Precio Unit.</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalSubtotal = 0;
            foreach ($items as $item):
                $subtotalItem = floatval($item['subtotal'] ?? 0);
                $totalSubtotal += $subtotalItem;
            ?>
            <tr>
                <td class="desc-col">
                    <strong><?= sanitize($item['nombre']) ?></strong>
                    <?php if (!empty($item['descripcion'])): ?>
                        <div style="color:#64748b;font-size:11px;margin-top:2px"><?= sanitize($item['descripcion']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="type-col"><?= ucfirst(str_replace('_', ' ', $item['tipo'])) ?></td>
                <td class="freq-col"><?= sanitize($item['frecuencia'] ?? 'N/A') ?></td>
                <td class="qty-col"><?= intval($item['cantidad'] ?? 1) ?></td>
                <td class="price-col"><?= formatMoney($item['precio_unit'] ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <div class="total-label">Subtotal</div>
            <div class="total-value"><?= formatMoney($subtotal) ?></div>
        </div>
        <?php if ($descuento > 0): ?>
        <div class="total-row">
            <div class="total-label">Descuento</div>
            <div class="total-value" style="color:#ef4444">-<?= formatMoney($descuento) ?></div>
        </div>
        <?php endif; ?>
        <div class="total-row grand-total">
            <div class="total-label">TOTAL</div>
            <div class="total-value"><?= formatMoney($total) ?></div>
        </div>
    </div>

    <div class="footer">
        <div class="validity-badge" style="background:<?= sanitize($pColSec) ?>;color:#0f172a">Válida por <?= $cot['vigencia_dias'] ?> días desde la emisión</div>
        <p style="margin-bottom:8px">
            <?php if (!empty($cot['notas'])): ?>
                <strong>Notas:</strong><br><?= nl2br(sanitize($cot['notas'])) ?><br><br>
            <?php endif; ?>
            <?php if ($pNotasPie): ?>
                <?= nl2br(sanitize($pNotasPie)) ?>
            <?php else: ?>
                Esta cotización es válida únicamente para los términos y condiciones especificados.<br>
                Para más información contáctenos por email o teléfono.
            <?php endif; ?>
        </p>
    </div>
</div>

</body>
</html>
