<?php
/**
 * CRM QUANTUN Digital — Envío de Orden de Compra por Correo
 * POST { cliente_id, cs_id }
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true);

$clienteId       = intval($input['cliente_id'] ?? 0);
$csId            = intval($input['cs_id'] ?? 0);
$csIds           = $input['cs_ids'] ?? null;
$itemsOverride   = $input['items_override'] ?? null;
$metodoPagoOver  = $input['metodo_pago'] ?? 'Transferencia / PSE / QR';
$notasPieOver    = $input['notas_pie'] ?? null;
$bancariosOver   = $input['bancarios'] ?? [];
$fechaUltPagoRaw = $input['fecha_ult_pago'] ?? '';
$docTipo         = $input['doc_tipo'] ?? 'orden_compra';
$linkPago        = trim($input['link_pago'] ?? '');
$plantillaIdOver = intval($input['plantilla_id'] ?? 0);
$fechaUltPago    = '';
if ($fechaUltPagoRaw) {
    $d = DateTime::createFromFormat('Y-m-d', $fechaUltPagoRaw);
    $fechaUltPago = $d ? $d->format('d/m/Y') : '';
}
$fechaEmision = date('d/m/Y');
if (!empty($input['fecha_emision'])) {
    $d = DateTime::createFromFormat('Y-m-d', $input['fecha_emision']);
    if ($d) $fechaEmision = $d->format('d/m/Y');
}
$asuntoOver  = trim($input['asunto'] ?? '');
$mensajeOver = trim($input['mensaje'] ?? '');

if (!$clienteId) jsonResponse(['error' => 'Cliente requerido'], 400);
if (!$csId && !$csIds) jsonResponse(['error' => 'Servicio(s) requerido(s)'], 400);

// Obtener servicios y cliente
if ($csIds) {
    // Múltiples servicios
    $idArray = explode(',', $csIds);
    $idArray = array_map('intval', $idArray);
    $placeholders = implode(',', array_fill(0, count($idArray), '?'));
    $stmt = $pdo->prepare("
        SELECT cs.*, c.nombre_comercial, c.nit_cedula, c.direccion, c.email_facturacion,
               c.telefono, s.nombre as servicio_nombre
        FROM cliente_servicios cs
        JOIN clientes c ON cs.cliente_id = c.id
        JOIN servicios s ON cs.servicio_id = s.id
        WHERE cs.id IN ($placeholders) AND cs.cliente_id = ?
    ");
    $params = array_merge($idArray, [$clienteId]);
    $stmt->execute($params);
    $servicios = $stmt->fetchAll();
    if (empty($servicios)) jsonResponse(['error' => 'Servicios no encontrados'], 404);
    $data = $servicios[0];
} else {
    // Servicio único
    $stmt = $pdo->prepare("
        SELECT cs.*, c.nombre_comercial, c.nit_cedula, c.direccion, c.email_facturacion,
               c.telefono, s.nombre as servicio_nombre
        FROM cliente_servicios cs
        JOIN clientes c ON cs.cliente_id = c.id
        JOIN servicios s ON cs.servicio_id = s.id
        WHERE cs.id = ? AND cs.cliente_id = ?
    ");
    $stmt->execute([$csId, $clienteId]);
    $data = $stmt->fetch();
    if (!$data) jsonResponse(['error' => 'Servicio no encontrado'], 404);
    $servicios = [$data];
}

// Usar el email elegido por el usuario, si no, el de facturación del cliente
$emailDest = trim($input['email_destino'] ?? $data['email_facturacion'] ?? '');
if (!$emailDest || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'No hay un correo válido para enviar'], 422);
}

// Verificar config SMTP
$smtpUser = env('MAIL_USERNAME', '');
$smtpPass = env('MAIL_PASSWORD', '');
if (!$smtpUser || $smtpUser === 'tu_correo@gmail.com' || !$smtpPass || $smtpPass === 'tu_app_password') {
    jsonResponse(['error' => 'El servidor de correo no está configurado.'], 503);
}

// Generar número de orden
$docTipoLabels = ['orden_renovacion'=>'Orden de Renovación','orden_compra'=>'Orden de Compra','cotizacion'=>'Cotización','factura'=>'Factura'];
$docTipoLabel  = $docTipoLabels[$docTipo] ?? 'Orden de Compra';
$orderNumber   = 'QD-' . date('Ymd');

// Fetch template (by ID or default)
if ($plantillaIdOver > 0) {
    $stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$plantillaIdOver]);
    $template = $stmt->fetch();
}
if (empty($template)) {
    $stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE es_default = 1 AND activo = 1 LIMIT 1");
    $stmt->execute();
    $template = $stmt->fetch();
}

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

// Logo — Base64 embebido (visible en TODOS los clientes de correo)
$logoUrlTemplate = !empty($template['logo_url']) ? trim($template['logo_url']) : '';
// enviar_orden usa logo negro sobre fondo claro; getLogoEmailSrc usa el blanco,
// así que lo manejamos manualmente con la misma lógica Base64
$logoSrc = '';
if ($logoUrlTemplate && stripos($logoUrlTemplate, 'localhost') === false && stripos($logoUrlTemplate, '127.0.0.1') === false) {
    $logoSrc = $logoUrlTemplate;
} else {
    $logoFile = BASE_PATH . '/Assets/logo_quantun_digital_negro.png';
    if (file_exists($logoFile)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
    }
}

// Obtener WhatsApp de configuración
$whatsappOrg = '';
try {
    $sc_ws = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = 'notif_whatsapp'");
    $sc_ws->execute();
    $whatsappOrg = $sc_ws->fetchColumn() ?: '';
} catch (PDOException $e) {}

// Aplicar override de items si vienen del editor del modal
if (is_array($itemsOverride) && count($itemsOverride) > 0) {
    $base = $servicios[0];
    $servicios = array_map(function($item) use ($base) {
        $qty    = intval($item['qty'] ?? 1);
        $precio = floatval($item['precio'] ?? 0);
        return array_merge($base, [
            'servicio_nombre'  => $item['descripcion'] ?? '',
            'monto_renovacion' => $precio * $qty,
            'descuento'        => floatval($item['descuento'] ?? 0),
            '_qty'             => $qty,
            '_precio_unit'     => $precio,
        ]);
    }, $itemsOverride);
}
if ($notasPieOver !== null) {
    $template['notas_pie'] = $notasPieOver;
}

// Generar HTML de la orden
$totalOriginal = 0;
$totalDescuento = 0;
foreach ($servicios as $svc) {
    $totalOriginal += $svc['monto_renovacion'];
    $totalDescuento += ($svc['descuento'] ?? 0);
}
$totalFinal = $totalOriginal - $totalDescuento;

// Generar tabla de servicios
$tablasServicios = '';
foreach ($servicios as $idx => $svc) {
    $qty      = $svc['_qty'] ?? 1;
    $precioU  = $svc['_precio_unit'] ?? $svc['monto_renovacion'];
    $descuento = $svc['descuento'] ?? 0;
    $subtotal  = $svc['monto_renovacion'] - $descuento;
    $tablasServicios .= '<tr style="border-bottom:1px solid #f1f5f9">
        <td style="padding:11px 14px;font-size:12px;color:#475569">' . htmlspecialchars($svc['servicio_nombre']) . '</td>
        <td style="padding:11px 14px;font-size:12px;color:#475569;text-align:center">' . $qty . '</td>
        <td style="padding:11px 14px;font-size:12px;color:#475569;text-align:right">$ ' . number_format($precioU, 0, ',', '.') . '</td>
        <td style="padding:11px 14px;font-size:12px;font-weight:700;color:#0f172a;text-align:right">$ ' . number_format($subtotal, 0, ',', '.') . '</td>
    </tr>';
}

$htmlFinal = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . $docTipoLabel . ' ' . $orderNumber . '</title>
</head>
<body style="font-family:' . $template['fuente'] . ',system-ui,sans-serif;color:#1e293b;line-height:1.5;margin:0;padding:40px;background:#f3f4f6">
<div style="background:white;max-width:900px;margin:auto">

<table style="width:100%;border-collapse:collapse;background:' . $template['color_primario'] . '">
<tr><td colspan="2" style="padding:0;background:' . $template['color_secundario'] . ';height:6px;font-size:0;line-height:0">&nbsp;</td></tr>
<tr style="vertical-align:bottom">
<td style="padding:24px 36px 24px 36px;width:55%;vertical-align:bottom">
' . ($logoSrc ? '<img src="' . $logoSrc . '" alt="Logo" width="150" style="display:block;max-height:48px;border:0">' : '') . '
<div style="margin-top:12px;font-size:11px;color:#94a3b8;line-height:1.8">
' . (isset($template['empresa_nit']) && $template['empresa_nit'] ? 'NIT: ' . htmlspecialchars($template['empresa_nit']) . ' &nbsp;&middot;&nbsp; ' : '') . htmlspecialchars($template['empresa_email']) . '<br>
' . htmlspecialchars($template['empresa_tel'] ?? '') . ' &nbsp;&middot;&nbsp; ' . htmlspecialchars($template['empresa_dir'] ?? '') . '
</div>
</td>
<td style="padding:24px 36px 24px 36px;width:45%;vertical-align:bottom;text-align:right">
<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:' . $template['color_secundario'] . '">' . htmlspecialchars($docTipoLabel) . '</div>
<div style="font-size:24px;font-weight:900;color:#ffffff;margin-top:4px">' . htmlspecialchars($orderNumber) . '</div>
<div style="font-size:11px;color:#94a3b8;margin-top:4px">' . $fechaEmision . '</div>
</td>
</tr>
</table>

<table style="width:100%;border-collapse:collapse;background:#f8fafc">
<tr>
<td style="padding:18px 36px;border-bottom:2px solid #e2e8f0;vertical-align:middle">
<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:5px">Facturado a</div>
<div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:3px">' . htmlspecialchars($data['nombre_comercial'] ?? '') . '</div>
' . (!empty($data['nit_cedula']) ? '<div style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:2px">NIT / Cédula: ' . htmlspecialchars($data['nit_cedula']) . '</div>' : '') . '
<div style="font-size:11px;color:#64748b">' . htmlspecialchars($data['direccion'] ?? '') . '</div>
</td>
<td style="padding:18px 36px;border-bottom:2px solid #e2e8f0;vertical-align:middle;text-align:center;white-space:nowrap;width:1%">
<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:block;margin-bottom:5px">Emisión</span>
<span style="font-size:12px;font-weight:700;color:#0f172a;display:block">' . $fechaEmision . '</span>
</td>
' . ($fechaUltPago ? '<td style="padding:18px 36px;border-bottom:2px solid #e2e8f0;vertical-align:middle;text-align:center;white-space:nowrap;width:1%">
<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:block;margin-bottom:5px">Último Pago</span>
<span style="font-size:12px;font-weight:700;color:#0f172a;display:block">' . htmlspecialchars($fechaUltPago) . '</span>
</td>' : '') . '
<td style="padding:18px 36px;border-bottom:2px solid #e2e8f0;vertical-align:middle;text-align:center;white-space:nowrap;width:1%">
<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:block;margin-bottom:5px">Método de Pago</span>
<span style="font-size:11px;font-weight:700;color:#0f172a;display:block">' . htmlspecialchars($metodoPagoOver ?? '') . '</span>
</td>
</tr>
</table>

<table style="width:100%;border-collapse:collapse">
<tr><td style="padding:28px 36px 10px 36px">
<table style="width:100%;border-collapse:collapse">
<thead>
<tr style="background:' . $template['color_primario'] . '">
<th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#ffffff">Descripción</th>
<th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#ffffff">Cant.</th>
<th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#ffffff">Precio</th>
<th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#ffffff">Total</th>
</tr>
</thead>
<tbody>
' . $tablasServicios . '
</tbody>
</table>
</td></tr>
<tr><td style="padding:0 36px 28px 36px">
<table style="width:300px;border-collapse:collapse;margin-left:auto">
<tr>
<td style="padding:6px 12px;font-size:12px;color:#64748b;text-align:left">Subtotal</td>
<td style="padding:6px 12px;font-size:12px;color:#64748b;text-align:right">$ ' . number_format($totalOriginal, 0, ',', '.') . '</td>
</tr>
' . ($totalDescuento > 0 ? '<tr>
<td style="padding:6px 12px;font-size:12px;color:#ea4335;text-align:left">Descuento</td>
<td style="padding:6px 12px;font-size:12px;color:#ea4335;text-align:right">- $ ' . number_format($totalDescuento, 0, ',', '.') . '</td>
</tr>' : '') . '
<tr>
<td colspan="2" style="padding:0;padding-top:10px">
<table style="width:100%;border-collapse:collapse;background:' . $template['color_primario'] . ';border-radius:8px">
<tr>
<td style="padding:14px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:' . $template['color_secundario'] . '">Total a pagar</td>
<td style="padding:14px 16px;font-size:18px;font-weight:900;color:#ffffff;text-align:right">$ ' . number_format($totalFinal, 0, ',', '.') . '</td>
</tr>
</table>
</td>
</tr>
</table>
</td></tr>
</table>

' . (function() use ($bancariosOver, $template) {
    $bancFields = ['titular'=>'Titular','cedula'=>'Cédula / NIT','banco'=>'Banco','cuenta'=>'N° de Cuenta','tipo'=>'Tipo de Cuenta','llave'=>'Nequi / Daviplata / QR'];
    $cells = '';
    $idx = 0;
    foreach ($bancFields as $k => $lbl) {
        if (!empty($bancariosOver[$k])) {
            $bg = $idx % 2 === 0 ? '#FAFAF7' : '#ffffff';
            $cells .= '<td style="padding:10px 16px;background:' . $bg . ';vertical-align:top;width:50%">'
                    . '<div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#B0AB9F;margin-bottom:3px">' . $lbl . '</div>'
                    . '<div style="font-size:12px;font-weight:600;color:#2D2B28">' . htmlspecialchars($bancariosOver[$k]) . '</div>'
                    . '</td>';
            $idx++;
        }
    }
    if (!$cells) return '';
    // Group into rows of 2
    $allVals = [];
    foreach ($bancFields as $k => $lbl) {
        if (!empty($bancariosOver[$k])) $allVals[] = ['lbl'=>$lbl,'val'=>$bancariosOver[$k]];
    }
    $rows = '';
    for ($i = 0; $i < count($allVals); $i += 2) {
        $bg0 = $i % 4 === 0 ? '#FAFAF7' : '#ffffff';
        $bg1 = $bg0;
        $cell0 = '<td style="padding:10px 16px;background:'.$bg0.';vertical-align:top;width:50%"><div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#B0AB9F;margin-bottom:3px">'.$allVals[$i]['lbl'].'</div><div style="font-size:12px;font-weight:600;color:#2D2B28">'.htmlspecialchars($allVals[$i]['val']).'</div></td>';
        $cell1 = isset($allVals[$i+1])
            ? '<td style="padding:10px 16px;background:#ffffff;vertical-align:top;width:50%"><div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#B0AB9F;margin-bottom:3px">'.$allVals[$i+1]['lbl'].'</div><div style="font-size:12px;font-weight:600;color:#2D2B28">'.htmlspecialchars($allVals[$i+1]['val']).'</div></td>'
            : '<td style="width:50%"></td>';
        $rows .= '<tr>'.$cell0.$cell1.'</tr>';
    }
    return '<table style="width:100%;border-collapse:collapse"><tr><td style="padding:0 36px 28px">
<table style="width:100%;border-collapse:collapse;border:1.5px solid #E8E5DD;border-radius:6px;overflow:hidden">
<tr><td colspan="2" style="padding:10px 16px;background:' . $template['color_primario'] . '">
  <table style="border-collapse:collapse"><tr>
    <td style="padding:0 8px 0 0;vertical-align:middle"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="' . $template['color_secundario'] . '" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></td>
    <td style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:' . $template['color_secundario'] . '">Datos para el pago</td>
  </tr></table>
</td></tr>'
. $rows .
'</table></td></tr></table>';
})() . '

' . ($linkPago ? '<table style="width:100%;border-collapse:collapse"><tr><td style="padding:24px 36px;text-align:center">
<a href="' . htmlspecialchars($linkPago) . '" target="_blank" style="display:inline-block;padding:14px 40px;background:' . $template['color_primario'] . ';color:' . $template['color_secundario'] . ';font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;border-radius:6px">💳 Pagar Ahora</a>
<div style="font-size:11px;color:#94a3b8;margin-top:8px">' . htmlspecialchars($linkPago) . '</div>
</td></tr></table>' : '') . '

<table style="width:100%;border-collapse:collapse;background:' . $template['color_primario'] . '">
<tr>
<td style="padding:14px 36px;font-size:11px;color:rgba(255,255,255,.5);width:100%">' . htmlspecialchars($template['notas_pie'] ?? '') . ($whatsappOrg ? '<br><span style="margin-top:8px;display:block">📱 WhatsApp: ' . htmlspecialchars($whatsappOrg) . '</span>' : '') . '</td>
<td style="padding:14px 12px;width:40px">
<div style="width:40px;height:4px;background:' . $template['color_secundario'] . ';border-radius:2px"></div>
</td>
</tr>
</table>

</div>
</body>
</html>';

// Asunto del correo: personalizado o por defecto
$emailAsunto = $asuntoOver ?: ($docTipoLabel . ' #' . $orderNumber . ' - QUANTUN Digital');

// Bloque de mensaje personalizado (va antes del documento)
$mensajeHtml = '';
if ($mensajeOver) {
    $mensajeHtml = '<table style="width:100%;max-width:900px;margin:0 auto 0 auto;border-collapse:collapse">'
        . '<tr><td style="padding:24px 36px 0 36px;font-family:' . $template['fuente'] . ',system-ui,sans-serif">'
        . '<div style="background:#f8fafc;border-left:4px solid ' . $template['color_primario'] . ';padding:16px 20px;border-radius:0 8px 8px 0;font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap">'
        . htmlspecialchars($mensajeOver)
        . '</div></td></tr></table>';
    // Insertar antes de <div style="background:white
    $htmlFinal = str_replace(
        '<div style="background:white;max-width:900px;margin:auto">',
        $mensajeHtml . '<div style="background:white;max-width:900px;margin:auto">',
        $htmlFinal
    );
}

// Enviar correo
$mailer = new Mailer();
$result = $mailer->send(
    $data['nombre_comercial'] . ' <' . $emailDest . '>',
    $emailAsunto,
    $htmlFinal
);

if ($result['ok']) {
    $userId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;

    // Log en notas del cliente
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cliente_notas (cliente_id, nota, usuario_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$clienteId, '[📧] ' . $docTipoLabel . ' #' . $orderNumber . ' enviada por correo a ' . $emailDest, $userId]);
    } catch (Exception $e) {}

    // Registrar en cotizaciones si es orden de renovación
    if ($docTipo === 'orden_renovacion') {
        try {
            // Migración por si la tabla no tiene las nuevas columnas
            try { $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN link_pago VARCHAR(500) DEFAULT NULL"); } catch(PDOException $e){}
            try { $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN datos_bancarios LONGTEXT DEFAULT NULL"); } catch(PDOException $e){}
            try { $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN plantilla_id INT DEFAULT NULL"); } catch(PDOException $e){}

            $itemsJson = json_encode($itemsOverride ?: array_map(function($s) {
                return [
                    'descripcion' => $s['servicio_nombre'],
                    'qty'         => $s['_qty'] ?? 1,
                    'precio'      => $s['_precio_unit'] ?? $s['monto_renovacion'],
                    'descuento'   => $s['descuento'] ?? 0,
                ];
            }, $servicios));

            $stmtIns = $pdo->prepare("INSERT INTO cotizaciones
                (numero, cliente_tipo, cliente_id, nombre_cliente, email, items, subtotal, descuento, total, notas, tipo, estado, link_pago, datos_bancarios, plantilla_id, creado_por)
                VALUES (?, 'cliente', ?, ?, ?, ?, ?, ?, ?, ?, 'orden_renovacion', 'enviada', ?, ?, ?, ?)");
            $stmtIns->execute([
                $orderNumber,
                $clienteId,
                $data['nombre_comercial'],
                $emailDest,
                $itemsJson,
                $totalOriginal,
                $totalDescuento,
                $totalFinal,
                $notasPieOver,
                $linkPago ?: null,
                !empty($bancariosOver) ? json_encode($bancariosOver) : null,
                $plantillaIdOver ?: null,
                $userId,
            ]);
        } catch (Exception $e) {}
    }

    jsonResponse(['success' => true, 'message' => "$docTipoLabel enviada a $emailDest"]);
} else {
    jsonResponse(['error' => 'Error al enviar: ' . $result['error']], 500);
}
