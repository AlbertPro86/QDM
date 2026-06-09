<?php
/**
 * CRM QUANTUN Digital — Genera el HTML de cotización listo para enviar por email
 * CSS inline para compatibilidad con Gmail, Outlook, Zoho Mail, etc.
 */

function generarEmailCotizacion(
    array   $cot,
    string  $mensajeTexto   = '',
    ?string $imagenPlantilla = null,
    ?string $logoUrl         = null,
    ?string $whatsappOrg     = null,
    ?array  $plantilla       = null
): string {

    $items     = json_decode($cot['items'] ?? '[]', true) ?? [];
    $numero    = htmlspecialchars($cot['numero'] ?? '', ENT_QUOTES);
    $cliente   = htmlspecialchars($cot['nombre_cliente'] ?? '', ENT_QUOTES);
    $emailCli  = htmlspecialchars($cot['email'] ?? '', ENT_QUOTES);
    $whatsapp  = htmlspecialchars($cot['whatsapp'] ?? '', ENT_QUOTES);
    $moneda    = htmlspecialchars($cot['moneda'] ?? 'COP', ENT_QUOTES);
    $notas     = htmlspecialchars($cot['notas'] ?? '', ENT_QUOTES);
    $vigencia  = intval($cot['vigencia_dias'] ?? 15);
    $subtotal  = floatval($cot['subtotal'] ?? 0);
    $descuento = floatval($cot['descuento'] ?? 0);
    $total     = floatval($cot['total'] ?? 0);

    $fechaEmision = date('d/m/Y', strtotime($cot['created_at'] ?? 'now'));
    $fechaVence   = date('d/m/Y', strtotime(($cot['created_at'] ?? 'now') . " +{$vigencia} days"));

    // ── Tokens de plantilla (con fallback a defaults QUANTUN) ─────────────────
    $colPrim   = htmlspecialchars($plantilla['color_primario']   ?? '#0f172a',           ENT_QUOTES);
    $colSec    = htmlspecialchars($plantilla['color_secundario'] ?? '#c9f31d',           ENT_QUOTES);
    $empNombre = htmlspecialchars($plantilla['empresa_nombre']   ?? 'QUANTUN Digital',   ENT_QUOTES);
    $empNit    = htmlspecialchars($plantilla['empresa_nit']      ?? '',                  ENT_QUOTES);
    $empEmail  = htmlspecialchars($plantilla['empresa_email']    ?? '',                  ENT_QUOTES);
    $empTel    = htmlspecialchars($plantilla['empresa_tel']      ?? '',                  ENT_QUOTES);
    $empDir    = htmlspecialchars($plantilla['empresa_dir']      ?? '',                  ENT_QUOTES);
    $notasPie  = htmlspecialchars($plantilla['notas_pie']        ?? '',                  ENT_QUOTES);

    // Logo: plantilla tiene prioridad, luego parámetro logoUrl
    $logoResolved = $plantilla['logo_url'] ?? $logoUrl ?? '';

    // ── Logo inline CID (visible en Gmail y todos los clientes) ──────────────
    $pdo = db();
    [$logoSrc, $_cotInlineImages] = getLogoEmailInline($pdo, $logoResolved, '40');
    // Exponemos las inline images para que el caller las pase a $mailer->send()
    if (!isset($GLOBALS['_cotizacion_inline_images'])) $GLOBALS['_cotizacion_inline_images'] = [];
    $GLOBALS['_cotizacion_inline_images'] = $_cotInlineImages;

    // ── Formato de moneda ─────────────────────────────────────────────────────
    $fmt = fn($n) => $moneda . ' ' . number_format($n, 0, ',', '.');

    // ── Mensaje del remitente ─────────────────────────────────────────────────
    $mensajeHtml = '';
    if ($mensajeTexto) {
        $parrafos = array_map(
            fn($l) => '<p style="margin:0 0 8px 0;font-size:14px;color:#57544D;line-height:1.65">'
                      . htmlspecialchars($l, ENT_QUOTES) . '</p>',
            explode("\n", trim($mensajeTexto))
        );
        $mensajeHtml = '
        <tr>
            <td style="padding:20px 32px 0 32px">
                <div style="background:#F5F5F2;border:1px solid #E8E5DD;padding:14px 16px;border-radius:6px">
                    ' . implode('', $parrafos) . '
                </div>
            </td>
        </tr>';
    }

    // ── Filas de items ────────────────────────────────────────────────────────
    $itemsHtml = '';
    foreach ($items as $i => $item) {
        $bgRow   = $i % 2 === 0 ? '#ffffff' : '#F9F9F7';
        $nombre  = htmlspecialchars($item['nombre']      ?? '', ENT_QUOTES);
        $desc    = htmlspecialchars($item['descripcion'] ?? '', ENT_QUOTES);
        $cant    = intval($item['cantidad']   ?? 1);
        $precio  = $fmt(floatval($item['precio_unit'] ?? 0));
        $subItem = $fmt(floatval($item['subtotal']    ?? 0));

        $itemsHtml .= "
        <tr style=\"background:{$bgRow}\">
            <td style=\"padding:11px 16px;font-size:13px;color:#0E0E0C;border-bottom:1px solid #EEECE6\">
                <strong style=\"font-weight:600\">{$nombre}</strong>" .
                ($desc ? "<div style=\"color:#57544D;font-size:11px;margin-top:3px\">{$desc}</div>" : '') . "
            </td>
            <td style=\"padding:11px 16px;font-size:13px;color:#0E0E0C;text-align:center;border-bottom:1px solid #EEECE6;white-space:nowrap\">{$cant}</td>
            <td style=\"padding:11px 16px;font-size:13px;color:#57544D;text-align:right;border-bottom:1px solid #EEECE6;white-space:nowrap\">{$precio}</td>
            <td style=\"padding:11px 16px;font-size:13px;color:#0E0E0C;font-weight:700;text-align:right;border-bottom:1px solid #EEECE6;white-space:nowrap\">{$subItem}</td>
        </tr>";
    }

    // ── Totales ───────────────────────────────────────────────────────────────
    $descuentoRow = '';
    if ($descuento > 0) {
        $descuentoRow = '
        <tr>
            <td colspan="3" style="padding:5px 16px;text-align:right;font-size:12px;color:#57544D">Descuento</td>
            <td style="padding:5px 16px;text-align:right;font-size:12px;color:#ef4444;font-weight:600;white-space:nowrap">-' . $fmt($descuento) . '</td>
        </tr>';
    }

    // ── Información empresa (columna derecha del header) ──────────────────────
    $empInfoRows = '';
    if ($empNit)   $empInfoRows .= '<div style="font-size:11px;color:#57544D;margin-top:2px">NIT: ' . $empNit . '</div>';
    if ($empEmail) $empInfoRows .= '<div style="font-size:11px;color:#57544D;margin-top:2px">' . $empEmail . '</div>';
    if ($empTel)   $empInfoRows .= '<div style="font-size:11px;color:#57544D;margin-top:2px">' . $empTel . '</div>';
    if ($empDir)   $empInfoRows .= '<div style="font-size:11px;color:#57544D;margin-top:2px">' . $empDir . '</div>';

    // ── Footer text ───────────────────────────────────────────────────────────
    $footerText = $notasPie
        ?: 'Esta cotización es válida únicamente por los ' . $vigencia . ' días desde la emisión.<br>Para más información, contáctenos por email o teléfono.';

    return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cotización ' . $numero . ' — ' . $empNombre . '</title>
</head>
<body style="margin:0;padding:0;background-color:#ECEAE3;font-family:\'Segoe UI\',Arial,sans-serif;-webkit-font-smoothing:antialiased">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#ECEAE3;padding:32px 12px">
<tr><td align="center">

<!-- Contenedor principal 600px -->
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08)">

    <!-- BARRA DE COLOR TOP -->
    <tr>
        <td style="background:linear-gradient(90deg,' . $colPrim . ' 0%,' . $colSec . ' 100%);height:5px;font-size:1px;line-height:1px">&nbsp;</td>
    </tr>

    <!-- HEADER: Logo/Empresa | Tipo de documento -->
    <tr>
        <td style="padding:28px 32px 20px 32px;border-bottom:1px solid #EEECE6">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <!-- Logo + Empresa -->
                    <td style="vertical-align:top;width:55%">
                        ' . $logoSrc . '
                        <div style="font-size:13px;font-weight:700;color:#0E0E0C;margin-bottom:2px">' . $empNombre . '</div>
                        ' . $empInfoRows . '
                    </td>
                    <!-- Tipo + Número -->
                    <td style="vertical-align:top;text-align:right;width:45%">
                        <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px">Cotización</div>
                        <div style="font-size:22px;font-weight:900;color:' . $colPrim . ';letter-spacing:-0.5px">#' . $numero . '</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- FECHAS -->
    <tr>
        <td style="padding:0 32px">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;border:1px solid #EEECE6;border-radius:8px;overflow:hidden">
                <tr>
                    <td style="width:50%;padding:12px 18px;border-right:1px solid #EEECE6;background:#F9F9F7">
                        <div style="font-size:9px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px">Emisión</div>
                        <div style="font-size:14px;font-weight:700;color:#0E0E0C">' . $fechaEmision . '</div>
                    </td>
                    <td style="width:50%;padding:12px 18px;background:#F9F9F7">
                        <div style="font-size:9px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px">Vence</div>
                        <div style="font-size:14px;font-weight:700;color:#0E0E0C">' . $fechaVence . '</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- PARA (cliente) -->
    <tr>
        <td style="padding:0 32px 20px 32px">
            <div style="font-size:9px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px">Para</div>
            <div style="font-size:15px;font-weight:800;color:#0E0E0C;margin-bottom:4px">' . $cliente . '</div>
            ' . ($emailCli ? '<div style="font-size:12px;color:#57544D">' . $emailCli . '</div>' : '') . '
            ' . ($whatsapp ? '<div style="font-size:12px;color:#57544D">' . $whatsapp . '</div>' : '') . '
        </td>
    </tr>

    <!-- MENSAJE DEL REMITENTE (si hay) -->
    ' . $mensajeHtml . '

    <!-- TABLA DE ITEMS -->
    <tr>
        <td style="padding:' . ($mensajeTexto ? '16px' : '0') . ' 32px 0 32px">
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #EEECE6;border-radius:8px;overflow:hidden">
                <!-- Encabezado tabla -->
                <tr style="background:#F2F0E8">
                    <th style="padding:10px 16px;text-align:left;font-size:9px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:0.8px">Descripción</th>
                    <th style="padding:10px 16px;text-align:center;font-size:9px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap">Cant.</th>
                    <th style="padding:10px 16px;text-align:right;font-size:9px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap">Precio Unit.</th>
                    <th style="padding:10px 16px;text-align:right;font-size:9px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap">Total</th>
                </tr>
                ' . $itemsHtml . '
            </table>
        </td>
    </tr>

    <!-- TOTALES -->
    <tr>
        <td style="padding:0 32px 0 32px">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:4px">
                <tr>
                    <td colspan="3" style="padding:8px 16px;text-align:right;font-size:12px;color:#57544D">Subtotal</td>
                    <td style="padding:8px 16px;text-align:right;font-size:12px;color:#0E0E0C;font-weight:600;white-space:nowrap">' . $fmt($subtotal) . '</td>
                </tr>
                ' . $descuentoRow . '
            </table>
        </td>
    </tr>

    <!-- TOTAL FINAL (caja oscura) -->
    <tr>
        <td style="padding:0 32px 28px 32px">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="background:#0E0E0C;border-radius:8px;padding:16px 20px">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:12px;font-weight:700;color:' . $colSec . ';text-transform:uppercase;letter-spacing:1px">Total a Pagar</td>
                                <td style="text-align:right;font-size:22px;font-weight:900;color:' . $colSec . ';letter-spacing:-0.5px;white-space:nowrap">' . $fmt($total) . '</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- NOTAS DE LA COTIZACIÓN -->
    ' . ($notas ? '
    <tr>
        <td style="padding:0 32px 24px 32px">
            <div style="background:#F9F9F7;border-radius:8px;padding:14px 16px">
                <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px">Notas</div>
                <div style="font-size:12px;color:#57544D;line-height:1.6">' . nl2br($notas) . '</div>
            </div>
        </td>
    </tr>' : '') . '

    <!-- FOOTER -->
    <tr>
        <td style="background:#F5F5F2;border-top:1px solid #EEECE6;padding:20px 32px;border-radius:0 0 12px 12px">
            <p style="margin:0 0 6px 0;font-size:11px;color:#57544D;line-height:1.65;font-style:italic;text-align:center">' . $footerText . '</p>
            ' . ($empEmail || $empTel ? '
            <p style="margin:8px 0 0 0;font-size:11px;color:#888580;text-align:center">
                ' . ($empEmail ? '<a href="mailto:' . $empEmail . '" style="color:#888580;text-decoration:none">' . $empEmail . '</a>' : '') . '
                ' . ($empEmail && $empTel ? ' &nbsp;·&nbsp; ' : '') . '
                ' . ($empTel ? '<a href="tel:' . preg_replace('/[^\d+]/', '', $empTel) . '" style="color:#888580;text-decoration:none">' . $empTel . '</a>' : '') . '
            </p>' : '') . '
            <p style="margin:10px 0 0 0;font-size:10px;color:#BDBBB5;text-align:center">
                Enviado desde CRM QUANTUN Digital
            </p>
        </td>
    </tr>

</table>
<!-- / Contenedor -->

</td></tr>
</table>

</body>
</html>';
}
