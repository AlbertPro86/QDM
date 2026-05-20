<?php
/**
 * CRM QUANTUN Digital — Genera el HTML de cotización listo para enviar por email
 * Todo con CSS inline para compatibilidad con Gmail, Outlook, Zoho Mail, etc.
 */

function generarEmailCotizacion(array $cot, string $mensajeTexto = '', ?string $imagenPlantilla = null, ?string $logoUrl = null, ?string $whatsappOrg = null): string {
    $items      = json_decode($cot['items'] ?? '[]', true) ?? [];
    $numero     = htmlspecialchars($cot['numero'] ?? '', ENT_QUOTES);
    $cliente    = htmlspecialchars($cot['nombre_cliente'] ?? '', ENT_QUOTES);
    $email      = htmlspecialchars($cot['email'] ?? '', ENT_QUOTES);
    $whatsapp   = htmlspecialchars($cot['whatsapp'] ?? '', ENT_QUOTES);
    $moneda     = htmlspecialchars($cot['moneda'] ?? 'COP', ENT_QUOTES);
    $notas      = htmlspecialchars($cot['notas'] ?? '', ENT_QUOTES);
    $vigencia   = intval($cot['vigencia_dias'] ?? 15);
    $subtotal   = floatval($cot['subtotal'] ?? 0);
    $descuento  = floatval($cot['descuento'] ?? 0);
    $total      = floatval($cot['total'] ?? 0);

    $fechaEmision = date('d/m/Y', strtotime($cot['created_at'] ?? 'now'));
    $fechaVence   = date('d/m/Y', strtotime(($cot['created_at'] ?? 'now') . " +{$vigencia} days"));

    // Si no se proporciona WhatsApp, intentar obtenerlo de la configuración
    if (!$whatsappOrg) {
        try {
            $pdo = db();
            $sc_ws = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = 'notif_whatsapp'");
            $sc_ws->execute();
            $whatsappOrg = $sc_ws->fetchColumn() ?: '';
        } catch (Exception $e) {
            $whatsappOrg = '';
        }
    }

    $fmt = fn($n) => $moneda . ' ' . number_format($n, 0, ',', '.');

    // ── Logo — Base64 embebido (visible en TODOS los clientes de correo) ────────
    $pdo2    = db();
    $logoSrc = getLogoEmailSrc($pdo2, $logoUrl ?? '');

    // ── Mensaje superior de la plantilla ──────────────────────────────────────
    $mensajeHtml = '';
    if ($mensajeTexto) {
        $parrafos = array_map(
            fn($l) => '<p style="margin:0 0 10px 0;color:#475569;font-size:14px;line-height:1.6">' . htmlspecialchars($l, ENT_QUOTES) . '</p>',
            explode("\n", trim($mensajeTexto))
        );
        $mensajeHtml = '
        <tr><td style="padding:24px 32px 8px 32px">
            ' . implode('', $parrafos) . '
        </td></tr>';
    }

    // ── Imagen de plantilla ───────────────────────────────────────────────────
    $imagenHtml = '';
    if ($imagenPlantilla) {
        $absUrl = rtrim(APP_URL, '/') . '/' . ltrim($imagenPlantilla, '/');
        $imagenHtml = '
        <tr><td style="padding:0 32px 20px 32px">
            <img src="' . $absUrl . '" style="max-width:100%;border-radius:8px;display:block" alt="imagen">
        </td></tr>';
    }

    // ── Filas de items ────────────────────────────────────────────────────────
    $itemsHtml = '';
    foreach ($items as $i => $item) {
        $bg      = $i % 2 === 0 ? '#ffffff' : '#f8fafc';
        $nombre  = htmlspecialchars($item['nombre'] ?? '', ENT_QUOTES);
        $desc    = htmlspecialchars($item['descripcion'] ?? '', ENT_QUOTES);
        $tipo    = ucfirst(str_replace('_', ' ', $item['tipo'] ?? ''));
        $freq    = htmlspecialchars($item['frecuencia'] ?? 'N/A', ENT_QUOTES);
        $cant    = intval($item['cantidad'] ?? 1);
        $precio  = $fmt(floatval($item['precio_unit'] ?? 0));
        $subItem = $fmt(floatval($item['subtotal'] ?? 0));

        $itemsHtml .= "
        <tr style=\"background:{$bg}\">
            <td style=\"padding:10px 14px;font-size:12px;color:#0f172a;border-bottom:1px solid #f1f5f9\">
                <strong>{$nombre}</strong>" .
                ($desc ? "<div style=\"color:#64748b;font-size:11px;margin-top:2px\">{$desc}</div>" : '') . "
            </td>
            <td style=\"padding:10px 14px;font-size:11px;color:#64748b;text-align:center;border-bottom:1px solid #f1f5f9\">{$tipo}</td>
            <td style=\"padding:10px 14px;font-size:11px;color:#64748b;text-align:center;border-bottom:1px solid #f1f5f9\">{$freq}</td>
            <td style=\"padding:10px 14px;font-size:12px;text-align:center;border-bottom:1px solid #f1f5f9\">{$cant}</td>
            <td style=\"padding:10px 14px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9\">{$precio}</td>
        </tr>";
    }

    // ── Totales ───────────────────────────────────────────────────────────────
    $descuentoHtml = '';
    if ($descuento > 0) {
        $descuentoHtml = '
        <tr>
            <td colspan="4" style="padding:6px 14px;text-align:right;font-size:12px;color:#64748b">Descuento:</td>
            <td style="padding:6px 14px;text-align:right;font-size:12px;color:#ef4444;font-weight:600">-' . $fmt($descuento) . '</td>
        </tr>';
    }

    $notasHtml = '';
    if ($notas) {
        $notasHtml = '<p style="margin:0 0 8px 0;font-size:12px;color:#475569"><strong>Notas:</strong><br>' . nl2br($notas) . '</p>';
    }

    return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cotización ' . $numero . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

    <!-- HEADER MARCA -->
    <tr>
        <td style="background:#0f172a;padding:18px 32px;border-radius:12px 12px 0 0">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:middle">
                        ' . ($logoSrc ? '<img src="' . $logoSrc . '" alt="QUANTUN Digital" height="38" style="display:block;height:38px;max-width:200px;object-fit:contain">' : '<span style="color:#ffffff;font-size:18px;font-weight:900">QUANTUN Digital</span>') . '
                    </td>
                    <td style="text-align:right;vertical-align:middle">' .
                        ($whatsappOrg ?
                            '<a href="https://wa.me/' . preg_replace('/[^\d+]/', '', $whatsappOrg) . '" style="display:inline-flex;align-items:center;gap:6px;background:#25D366;color:#ffffff;text-decoration:none;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                                ' . $whatsappOrg . '
                            </a>'
                            : ''
                        ) . '
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- CUERPO PRINCIPAL -->
    <tr>
        <td style="background:#ffffff">
            <table width="100%" cellpadding="0" cellspacing="0">

                <!-- Mensaje plantilla -->
                ' . $mensajeHtml . '

                <!-- Imagen plantilla -->
                ' . $imagenHtml . '

                <!-- Título cotización -->
                <tr>
                    <td style="padding:24px 32px 16px 32px;border-top:2px solid #f1f5f9;text-align:center">
                        <div style="font-size:24px;font-weight:900;color:#0f172a;letter-spacing:-0.5px">COTIZACIÓN</div>
                        <div style="font-size:13px;color:#64748b;margin-top:4px">#' . $numero . ' &nbsp;·&nbsp; Emitida el ' . $fechaEmision . '</div>
                    </td>
                </tr>

                <!-- Datos cliente / vigencia -->
                <tr>
                    <td style="padding:0 32px 20px 32px">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:50%;vertical-align:top">
                                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Cotización para</div>
                                    <div style="font-size:14px;font-weight:700;color:#0f172a">' . $cliente . '</div>
                                    ' . ($email    ? '<div style="font-size:12px;color:#64748b;margin-top:2px">' . $email    . '</div>' : '') . '
                                    ' . ($whatsapp ? '<div style="font-size:12px;color:#64748b;margin-top:2px">' . $whatsapp . '</div>' : '') . '
                                </td>
                                <td style="width:50%;vertical-align:top;text-align:right">
                                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Válida hasta</div>
                                    <div style="font-size:14px;font-weight:700;color:#0f172a">' . $fechaVence . '</div>
                                    <div style="font-size:12px;color:#ef4444;font-weight:600;margin-top:2px">' . $vigencia . ' días</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Tabla items -->
                <tr>
                    <td style="padding:0 32px 0 32px">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:8px;overflow:hidden;border:1px solid #e2e8f0">
                            <thead>
                                <tr style="background:#f8fafc">
                                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Descripción</th>
                                    <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Tipo</th>
                                    <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Freq.</th>
                                    <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Cant.</th>
                                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Precio</th>
                                </tr>
                            </thead>
                            <tbody>' . $itemsHtml . '</tbody>
                        </table>
                    </td>
                </tr>

                <!-- Totales -->
                <tr>
                    <td style="padding:0 32px 24px 32px">
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px">
                            <tr>
                                <td colspan="4" style="padding:6px 14px;text-align:right;font-size:12px;color:#64748b">Subtotal:</td>
                                <td style="padding:6px 14px;text-align:right;font-size:12px;color:#0f172a;font-weight:600">' . $fmt($subtotal) . '</td>
                            </tr>
                            ' . $descuentoHtml . '
                            <tr style="border-top:2px solid #e2e8f0">
                                <td colspan="4" style="padding:12px 14px;text-align:right;font-size:15px;font-weight:900;color:#0f172a">TOTAL:</td>
                                <td style="padding:12px 14px;text-align:right;font-size:15px;font-weight:900;color:#0f172a">' . $fmt($total) . '</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Notas -->
                ' . ($notasHtml ? '<tr><td style="padding:0 32px 20px 32px;font-size:12px;color:#475569">' . $notasHtml . '</td></tr>' : '') . '

            </table>
        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background:#f8fafc;padding:20px 32px;border-radius:0 0 12px 12px;border-top:1px solid #e2e8f0;text-align:center">
            <div style="display:inline-block;background:#fef3c7;color:#92400e;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;margin-bottom:10px">
                Válida por ' . $vigencia . ' días desde la emisión
            </div>
            <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6">
                Esta cotización es válida únicamente para los términos especificados.' .
                ($whatsappOrg ? '<br>Para más información escríbenos por WhatsApp al <a href="https://wa.me/' . preg_replace('/[^\d+]/', '', $whatsappOrg) . '" style="color:#25D366;font-weight:700;text-decoration:none">' . htmlspecialchars($whatsappOrg) . '</a>' : '') . '
            </p>
        </td>
    </tr>

    <!-- CRÉDITO CRM -->
    <tr>
        <td style="padding:16px;text-align:center;font-size:10px;color:#cbd5e1">
            Enviado desde CRM QUANTUN Digital
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>';
}
