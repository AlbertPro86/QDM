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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización <?= $numero ?></title>
    <style>
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
        }
        .actions button, .actions a {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .actions .btn-print {
            background: #0f172a;
            color: white;
        }
        .actions .btn-print:hover {
            background: #1e293b;
        }
        .actions .btn-wa {
            background: #25d366;
            color: white;
        }
        .actions .btn-wa:hover {
            background: #1fa252;
        }
        .actions .btn-email {
            background: #3b82f6;
            color: white;
        }
        .actions .btn-email:hover {
            background: #2563eb;
        }
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
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        .title h1 {
            font-size: 28px;
            font-weight: 900;
            color: #0f172a;
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
        Imprimir / Guardar como PDF
    </button>
    <button class="btn-wa" onclick="enviarWhatsApp()">
        <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-3.055 2.556-3.848 7.594-1.792 11.648 1.955 3.979 6.62 5.557 10.582 3.535 3.961-2.022 5.33-6.93 3.02-10.864-.793-1.595-2.068-2.9-3.464-3.728-1.396-.827-3.037-1.213-4.711-.969"/>
        </svg>
        Enviar por WhatsApp
    </button>
    <button class="btn-email" onclick="enviarEmail()">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        Enviar por Email
    </button>
</div>

<div class="invoice-box">
    <div class="header">
        <div class="logo">
            <img src="Assets/logo_quantun_digital_negro.png" alt="QUANTUN Digital"
                 style="height:36px;object-fit:contain;display:block">
        </div>
        <div class="company-info">
            <div style="font-weight:700;margin-bottom:4px">QUANTUN Digital</div>
            <div>contacto@quantundigital.com</div>
            <div style="margin-top:6px">
                <a href="https://wa.me/573332747801" target="_blank"
                   style="display:inline-flex;align-items:center;gap:5px;background:#25D366;color:#fff;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;text-decoration:none">
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                    333 274 7801
                </a>
            </div>
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
        <div class="validity-badge">Válida por <?= $cot['vigencia_dias'] ?> días desde la emisión</div>
        <p style="margin-bottom:8px">
            <?php if (!empty($cot['notas'])): ?>
                <strong>Notas:</strong><br><?= nl2br(sanitize($cot['notas'])) ?><br><br>
            <?php endif; ?>
            Esta cotización es válida únicamente para los términos y condiciones especificados.<br>
            Para más información contactenos a través de WhatsApp o email.
        </p>
    </div>
</div>

<script>
function enviarWhatsApp() {
    const numero = '<?= addslashes($cot['whatsapp'] ?? '') ?>';
    const url = window.location.href;
    if (!numero) {
        alert('No hay número de WhatsApp disponible');
        return;
    }
    const msg = 'Hola, te comparto la cotización solicitada: ' + url;
    window.open('https://wa.me/' + numero.replace(/\D/g, '') + '?text=' + encodeURIComponent(msg), '_blank');
}

function enviarEmail() {
    const email = '<?= addslashes($cot['email'] ?? '') ?>';
    const url = window.location.href;
    if (!email) {
        alert('No hay email disponible');
        return;
    }
    const subject = 'Cotización <?= sanitize($numero) ?>';
    const body = 'Hola,\n\nTE envío la cotización que solicitaste:\n' + url + '\n\nQuedamos atentos a tus preguntas.\n\nSaludos,\nQUANTUN Digital';
    window.location.href = 'mailto:' + email + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
}
</script>

</body>
</html>
