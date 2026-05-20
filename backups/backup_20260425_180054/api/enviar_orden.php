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

$clienteId = intval($input['cliente_id'] ?? 0);
$csId = intval($input['cs_id'] ?? 0);
$csIds = $input['cs_ids'] ?? null;

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

$emailDest = trim($data['email_facturacion'] ?? '');
if (!$emailDest || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'El cliente no tiene un correo válido registrado'], 422);
}

// Verificar config SMTP
$smtpUser = env('MAIL_USERNAME', '');
$smtpPass = env('MAIL_PASSWORD', '');
if (!$smtpUser || $smtpUser === 'tu_correo@gmail.com' || !$smtpPass || $smtpPass === 'tu_app_password') {
    jsonResponse(['error' => 'El servidor de correo no está configurado.'], 503);
}

// Generar número de orden
$orderType = ($csId || (count($servicios) === 1)) ? "S" : "OC";
$orderNumber = $orderType . "-" . str_pad($clienteId, 4, '0', STR_PAD_LEFT) . "-" . date('Ymd');

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
    $monto = $svc['monto_renovacion'];
    $descuento = $svc['descuento'] ?? 0;
    $subtotal = $monto - $descuento;
    $tablasServicios .= '<tr style="border-bottom:1px solid #f1f5f9">
        <td style="padding:11px 14px;font-size:12px;color:#475569">' . htmlspecialchars($svc['servicio_nombre']) . '</td>
        <td style="padding:11px 14px;font-size:12px;color:#475569;text-align:center">1</td>
        <td style="padding:11px 14px;font-size:12px;color:#475569;text-align:right">$ ' . number_format($monto, 0, ',', '.') . '</td>
        <td style="padding:11px 14px;font-size:12px;font-weight:700;color:#0f172a;text-align:right">$ ' . number_format($subtotal, 0, ',', '.') . '</td>
    </tr>';
}

$htmlFinal = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orden de Compra ' . $orderNumber . '</title>
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
' . htmlspecialchars($template['empresa_tel']) . ' &nbsp;&middot;&nbsp; ' . htmlspecialchars($template['empresa_dir']) . '
</div>
</td>
<td style="padding:24px 36px 24px 36px;width:45%;vertical-align:bottom;text-align:right">
<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:' . $template['color_secundario'] . '">Orden de Compra</div>
<div style="font-size:24px;font-weight:900;color:#ffffff;margin-top:4px">' . htmlspecialchars($orderNumber) . '</div>
<div style="font-size:11px;color:#94a3b8;margin-top:4px">' . date('d/m/Y') . '</div>
</td>
</tr>
</table>

<table style="width:100%;border-collapse:collapse;background:#f8fafc">
<tr>
<td style="padding:18px 36px;border-bottom:2px solid #e2e8f0;vertical-align:middle">
<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:5px">Facturado a</div>
<div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:3px">' . htmlspecialchars($data['nombre_comercial']) . '</div>
<div style="font-size:11px;color:#64748b">' . htmlspecialchars($data['direccion'] ?? '') . '</div>
</td>
<td style="padding:18px 36px;border-bottom:2px solid #e2e8f0;vertical-align:middle;text-align:center;white-space:nowrap;width:1%">
<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:block;margin-bottom:5px">Emisión</span>
<span style="font-size:12px;font-weight:700;color:#0f172a;display:block">' . date('d/m/Y') . '</span>
</td>
<td style="padding:18px 36px;border-bottom:2px solid #e2e8f0;vertical-align:middle;text-align:center;white-space:nowrap;width:1%">
<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:block;margin-bottom:5px">Método de Pago</span>
<span style="font-size:11px;font-weight:700;color:#0f172a;display:block">Transferencia / PSE / QR</span>
</td>
</tr>
</table>

<table style="width:100%;border-collapse:collapse">
<tr><td style="padding:28px 36px 10px 36px">
<table style="width:100%;border-collapse:collapse">
<thead>
<tr style="background:' . $template['color_primario'] . '">
<th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:' . $template['color_secundario'] . '">Descripción</th>
<th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:' . $template['color_secundario'] . '">Qty</th>
<th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:' . $template['color_secundario'] . '">Precio</th>
<th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:' . $template['color_secundario'] . '">Total</th>
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

<table style="width:100%;border-collapse:collapse;background:' . $template['color_primario'] . '">
<tr>
<td style="padding:14px 36px;font-size:11px;color:rgba(255,255,255,.5);width:100%">' . htmlspecialchars($template['notas_pie']) . ($whatsappOrg ? '<br><span style="margin-top:8px;display:block">📱 WhatsApp: ' . htmlspecialchars($whatsappOrg) . '</span>' : '') . '</td>
<td style="padding:14px 12px;width:40px">
<div style="width:40px;height:4px;background:' . $template['color_secundario'] . ';border-radius:2px"></div>
</td>
</tr>
</table>

</div>
</body>
</html>';

// Enviar correo
$mailer = new Mailer();
$result = $mailer->send(
    $data['nombre_comercial'] . ' <' . $emailDest . '>',
    'Orden de Compra #' . $orderNumber . ' - QUANTUN Digital',
    $htmlFinal
);

if ($result['ok']) {
    // Log
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cliente_notas (cliente_id, nota, usuario_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $userId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        $stmt->execute([$clienteId, '[📧] Orden de Compra #' . $orderNumber . ' enviada por correo a ' . $emailDest, $userId]);
    } catch (Exception $e) {}

    jsonResponse(['success' => true, 'message' => "Orden enviada a $emailDest"]);
} else {
    jsonResponse(['error' => 'Error al enviar: ' . $result['error']], 500);
}
