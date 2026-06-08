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
               c.telefono,
               CASE
                   WHEN cs.paquete_id IS NOT NULL AND p.nombre IS NOT NULL THEN p.nombre
                   WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
                   ELSE s.nombre
               END AS servicio_nombre
        FROM cliente_servicios cs
        JOIN clientes c ON cs.cliente_id = c.id
        JOIN servicios s ON cs.servicio_id = s.id
        LEFT JOIN paquetes p ON cs.paquete_id = p.id
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
               c.telefono,
               CASE
                   WHEN cs.paquete_id IS NOT NULL AND p.nombre IS NOT NULL THEN p.nombre
                   WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
                   ELSE s.nombre
               END AS servicio_nombre
        FROM cliente_servicios cs
        JOIN clientes c ON cs.cliente_id = c.id
        JOIN servicios s ON cs.servicio_id = s.id
        LEFT JOIN paquetes p ON cs.paquete_id = p.id
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

// Fetch template (by ID → default de la categoría → cualquier default)
$template = null;
if ($plantillaIdOver > 0) {
    $stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$plantillaIdOver]);
    $template = $stmt->fetch() ?: null;
}
if (!$template) {
    // Default específico para la categoría del documento
    $stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE es_default = 1 AND activo = 1 AND categoria = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$docTipo]);
    $template = $stmt->fetch() ?: null;
}
if (!$template) {
    // Cualquier plantilla default (fallback)
    $stmt = $pdo->prepare("SELECT * FROM plantillas_factura WHERE es_default = 1 AND activo = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $template = $stmt->fetch() ?: null;
}

if (!$template) {
    $template = [
        'layout_tipo'      => 'clasica',
        'color_primario'   => '#0E0E0C',
        'color_secundario' => '#C6F24E',
        'fuente'           => 'Poppins',
        'empresa_nombre'   => 'QUANTUN Digital',
        'empresa_nit'      => '',
        'empresa_email'    => 'gerencia@ceicar.co',
        'empresa_tel'      => '+57 (314) 597-9983',
        'empresa_dir'      => 'Montería, Córdoba',
        'logo_url'         => '',
        'notas_pie'        => 'Gracias por su preferencia. El pago debe realizarse en los próximos 30 días.',
        'mostrar_banco'    => 0,
        'es_default'       => 0,
    ];
}

// ── Fallbacks de datos de empresa ─────────────────────────────────────────────
// Si la plantilla tiene los campos vacíos, completar desde crm_configuraciones
// y, en último caso, con valores por defecto de QUANTUN. Así el bloque de info
// bajo el logo NUNCA sale vacío (espejo de los fallbacks del preview JS).
$cfgEmpresa = [];
try {
    $sc_cfg = $pdo->query("SELECT clave, valor FROM crm_configuraciones WHERE clave IN ('empresa_nombre','empresa_nit','empresa_email','empresa_tel','empresa_telefono','empresa_direccion','empresa_dir')");
    foreach ($sc_cfg->fetchAll(PDO::FETCH_KEY_PAIR) as $k => $v) { $cfgEmpresa[$k] = $v; }
} catch (PDOException $e) {}

$_pick = function(array $keys, string $default) use ($template, $cfgEmpresa): string {
    foreach ($keys as $k) {
        if (isset($template[$k]) && trim((string)$template[$k]) !== '') return trim((string)$template[$k]);
    }
    foreach ($keys as $k) {
        if (isset($cfgEmpresa[$k]) && trim((string)$cfgEmpresa[$k]) !== '') return trim((string)$cfgEmpresa[$k]);
    }
    return $default;
};

$template['empresa_nombre'] = $_pick(['empresa_nombre'], 'QUANTUN Digital');
$template['empresa_nit']    = $_pick(['empresa_nit'], '');
$template['empresa_email']  = $_pick(['empresa_email'], 'gerencia@ceicar.co');
$template['empresa_tel']    = $_pick(['empresa_tel','empresa_telefono'], '+57 (314) 597-9983');
$template['empresa_dir']    = $_pick(['empresa_dir','empresa_direccion'], 'Montería, Córdoba');

// ── Logo para email: CID inline attachment ───────────────────────────────────
// Gmail bloquea data: URIs y las URLs externas requieren clic del usuario.
// CID embebe la imagen como parte MIME → visible en TODOS los clientes sin clic.
$logoFilePath = '';
$logoMime     = 'image/png';
$logoCid      = 'logo_qd@quantun.digital';   // formato addr para máxima compatibilidad
$tmpLogo      = '';
$logoUrlTemplate = !empty($template['logo_url']) ? trim($template['logo_url']) : '';

// Helper: detectar si un color HEX es oscuro (para elegir logo con contraste adecuado)
$_bgDark = (function(string $hex): bool {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
    return (0.299*$r + 0.587*$g + 0.114*$b) / 255 < 0.5;
})(trim($template['color_primario'] ?? '#0f172a'));

// 1. Intentar usar el logo configurado en la plantilla
if ($logoUrlTemplate !== '') {
    if (preg_match('#^https?://#i', $logoUrlTemplate)) {
        // URL remota → descargar y escribir en archivo temporal
        $rawImg = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($logoUrlTemplate);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => 'CRM-QUANTUN/1.0',
            ]);
            $rawImg = curl_exec($ch);
            $ctype  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'image/png';
            curl_close($ch);
        }
        if ($rawImg && strlen($rawImg) > 100) {
            $logoMime = trim(explode(';', $ctype)[0]);
            $extMap   = ['image/png'=>'png','image/jpeg'=>'jpg','image/gif'=>'gif','image/webp'=>'webp'];
            $ext      = $extMap[$logoMime] ?? 'png';
            $tmpLogo  = tempnam(sys_get_temp_dir(), 'qlogo_') . '.' . $ext;
            if (file_put_contents($tmpLogo, $rawImg) !== false) {
                $logoFilePath = $tmpLogo;
            }
        }
    } else {
        // Ruta local relativa (p.ej. "uploads/facturas/logo.png" o "/Assets/logo.png")
        $localPath = BASE_PATH . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logoUrlTemplate), DIRECTORY_SEPARATOR);
        foreach ([$localPath, $logoUrlTemplate] as $_lp) {
            if ($_lp && @is_file($_lp)) {
                $logoFilePath = $_lp;
                $ext = strtolower(pathinfo($_lp, PATHINFO_EXTENSION));
                $logoMime = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp'][$ext] ?? 'image/png';
                break;
            }
        }
    }
}

// 2. Fallback: logo propio según contraste del fondo del encabezado
if (!$logoFilePath) {
    // Encabezado oscuro → logo blanco; encabezado claro → logo negro
    $logoFile = BASE_PATH . '/Assets/' . ($_bgDark ? 'logo_quantun_digital_blanco.png' : 'logo_quantun_digital_negro.png');
    if (!@is_file($logoFile)) {
        // Intentar el otro si no existe
        $logoFile = BASE_PATH . '/Assets/' . ($_bgDark ? 'logo_quantun_digital_negro.png' : 'logo_quantun_digital_blanco.png');
    }
    if (@is_file($logoFile)) {
        $logoFilePath = $logoFile;
        $logoMime     = 'image/png';
    }
}

// En el HTML se referencia como cid:logo_qd@quantun.digital
$logoSrc = $logoFilePath ? 'cid:' . $logoCid : '';

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
        <td class="itm-td" style="padding:10px 12px;font-size:12px;color:#475569;word-break:break-word">' . htmlspecialchars($svc['servicio_nombre']) . '</td>
        <td class="itm-td" style="padding:10px 12px;font-size:12px;color:#475569;text-align:center">' . $qty . '</td>
        <td class="itm-td" style="padding:10px 12px;font-size:12px;color:#475569;text-align:right;white-space:nowrap">$ ' . number_format($precioU, 0, ',', '.') . '</td>
        <td class="itm-td" style="padding:10px 12px;font-size:12px;font-weight:700;color:#0f172a;text-align:right;white-space:nowrap">$ ' . number_format($subtotal, 0, ',', '.') . '</td>
    </tr>';
}

// Atajos para interpolación limpia en el HTML
$cpri  = $template['color_primario'];
$csec  = $template['color_secundario'];
$cfont = $template['fuente'];

$htmlFinal = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($docTipoLabel) . ' ' . htmlspecialchars($orderNumber) . '</title>
<style type="text/css">
body{margin:0;padding:0;background:#f3f4f6}
table{border-spacing:0;mso-table-lspace:0;mso-table-rspace:0}
img{border:0;height:auto;line-height:100%;outline:none;text-decoration:none}
.em-wrap{background:#ffffff;max-width:600px;margin:0 auto}
.em-msg{max-width:600px;margin:0 auto}
@media only screen and (max-width:620px){
  body{padding:0 !important}
  .em-wrap,.em-msg{max-width:100% !important;width:100% !important}
  /* Una sola columna en móvil: todo apilado al 100% */
  .hdr-tbl,.hdr-tbl tbody,.hdr-tbl tr{display:block !important;width:100% !important}
  .hdr-logo{width:100% !important;display:block !important;padding:20px 22px 6px 22px !important;text-align:center !important}
  .hdr-logo img{margin:0 auto !important}
  .hdr-info{width:100% !important;display:block !important;text-align:center !important;padding:6px 22px 18px !important}
  .hdr-meta{text-align:center !important;color:#e2e8f0 !important}
  .cli-tbl,.cli-tbl tbody,.cli-tbl tr{display:block !important;width:100% !important}
  .cli-main{width:100% !important;display:block !important;padding:16px 22px 8px !important;box-sizing:border-box !important;border-bottom:none !important}
  .cli-date{width:100% !important;display:block !important;text-align:left !important;white-space:normal !important;padding:7px 22px !important;border-left:none !important;border-bottom:none !important;box-sizing:border-box !important}
  .cli-date:last-child{padding-bottom:14px !important}
  .banc-cell{width:100% !important;display:block !important;box-sizing:border-box !important}
  .banc-fill{display:none !important}
  .itm-td{padding:7px 6px !important;font-size:10px !important}
  .px-sec{padding-left:14px !important;padding-right:14px !important}
  .totals-tbl{width:100% !important}
}
</style>
</head>
<body style="margin:0;padding:20px 10px;background:#f3f4f6;font-family:' . $cfont . ',system-ui,sans-serif;color:#1e293b;line-height:1.5">

<table class="em-wrap" width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="background:#ffffff;max-width:600px;margin:0 auto;border-radius:4px;overflow:hidden">
<tr><td>

<!-- Encabezado -->
<table class="hdr-tbl" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff">
<tr><td colspan="2" style="background:' . $csec . ';height:4px;font-size:0;line-height:0">&nbsp;</td></tr>
<tr>
<td class="hdr-logo" style="padding:22px 28px;width:55%;vertical-align:middle">
' . ($logoSrc ? '<img src="' . $logoSrc . '" alt="Logo" style="display:block;max-width:140px;max-height:46px;height:auto;border:0">' : '') . '
<div class="hdr-meta" style="margin-top:10px;font-size:10px;color:#57544D;line-height:1.8">
' . (isset($template['empresa_nit']) && $template['empresa_nit'] ? 'NIT: ' . htmlspecialchars($template['empresa_nit']) . ' &nbsp;&middot;&nbsp; ' : '') . htmlspecialchars($template['empresa_email'] ?? '') . '<br>
' . htmlspecialchars($template['empresa_tel'] ?? '') . ' &nbsp;&middot;&nbsp; ' . htmlspecialchars($template['empresa_dir'] ?? '') . '
</div>
</td>
<td class="hdr-info" style="padding:22px 28px;width:45%;vertical-align:middle;text-align:right">
<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:#57544D;margin-bottom:4px">' . htmlspecialchars($docTipoLabel) . '</div>
<div style="font-size:22px;font-weight:900;color:#0E0E0C;margin-top:4px">' . htmlspecialchars($orderNumber) . '</div>
<div style="font-size:11px;color:#8A867C;margin-top:4px">' . $fechaEmision . '</div>
</td>
</tr>
</table>

<!-- Datos del cliente -->
<table class="cli-tbl" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc">
<tr>
<td class="cli-main" style="padding:16px 28px;border-bottom:2px solid #e2e8f0;vertical-align:top;width:55%">
<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:5px">Facturado a</div>
<div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:3px">' . htmlspecialchars($data['nombre_comercial'] ?? '') . '</div>
' . (!empty($data['nit_cedula']) ? '<div style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:2px">NIT / Cédula: ' . htmlspecialchars($data['nit_cedula']) . '</div>' : '') . '
<div style="font-size:11px;color:#64748b">' . htmlspecialchars($data['direccion'] ?? '') . '</div>
</td>
<td class="cli-date" style="padding:14px 16px;border-bottom:2px solid #e2e8f0;vertical-align:top;text-align:center;width:1%;white-space:nowrap">
<span style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:block;margin-bottom:4px">Emisión</span>
<span style="font-size:12px;font-weight:700;color:#0f172a;display:block">' . $fechaEmision . '</span>
</td>
' . ($fechaUltPago ? '<td class="cli-date" style="padding:14px 16px;border-bottom:2px solid #e2e8f0;vertical-align:top;text-align:center;width:1%;white-space:nowrap">
<span style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:block;margin-bottom:4px">Último Pago</span>
<span style="font-size:12px;font-weight:700;color:#0f172a;display:block">' . htmlspecialchars($fechaUltPago) . '</span>
</td>' : '') . '
<td class="cli-date" style="padding:14px 16px;border-bottom:2px solid #e2e8f0;vertical-align:top;text-align:center;width:1%;white-space:nowrap">
<span style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:block;margin-bottom:4px">Método</span>
<span style="font-size:11px;font-weight:700;color:#0f172a;display:block">' . htmlspecialchars($metodoPagoOver ?? '') . '</span>
</td>
</tr>
</table>

<!-- Tabla de servicios -->
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td class="px-sec" style="padding:22px 28px 10px 28px">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<thead>
<tr style="background:' . $cpri . '">
<th class="itm-td" style="padding:9px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#ffffff">Descripción</th>
<th class="itm-td" style="padding:9px 12px;text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#ffffff;width:36px">Cant.</th>
<th class="itm-td" style="padding:9px 12px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#ffffff;width:80px">Precio</th>
<th class="itm-td" style="padding:9px 12px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#ffffff;width:80px">Total</th>
</tr>
</thead>
<tbody>
' . $tablasServicios . '
</tbody>
</table>
</td></tr>

<!-- Totales -->
<tr><td class="px-sec" style="padding:0 28px 24px 28px">
<table class="totals-tbl" cellpadding="0" cellspacing="0" border="0" style="width:280px;margin-left:auto">
<tr>
<td style="padding:5px 10px;font-size:12px;color:#64748b">Subtotal</td>
<td style="padding:5px 10px;font-size:12px;color:#64748b;text-align:right">$ ' . number_format($totalOriginal, 0, ',', '.') . '</td>
</tr>
' . ($totalDescuento > 0 ? '<tr>
<td style="padding:5px 10px;font-size:12px;color:#ea4335">Descuento</td>
<td style="padding:5px 10px;font-size:12px;color:#ea4335;text-align:right">- $ ' . number_format($totalDescuento, 0, ',', '.') . '</td>
</tr>' : '') . '
<tr><td colspan="2" style="padding:0;padding-top:8px">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . $cpri . ';border-radius:6px">
<tr>
<td style="padding:12px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:' . $csec . '">Total a pagar</td>
<td style="padding:12px 14px;font-size:18px;font-weight:900;color:#ffffff;text-align:right">$ ' . number_format($totalFinal, 0, ',', '.') . '</td>
</tr>
</table>
</td></tr>
</table>
</td></tr>
</table>

' . (function() use ($bancariosOver, $cpri, $csec) {
    $bancFields = ['titular'=>'Titular','cedula'=>'Cédula / NIT','banco'=>'Banco','cuenta'=>'N° de Cuenta','tipo'=>'Tipo de Cuenta','llave'=>'Llave'];
    $allVals = [];
    foreach ($bancFields as $k => $lbl) {
        if (!empty($bancariosOver[$k])) $allVals[] = ['lbl'=>$lbl,'val'=>$bancariosOver[$k]];
    }
    if (!$allVals) return '';
    $rows = '';
    for ($i = 0; $i < count($allVals); $i += 2) {
        $bg0 = ($i % 4 === 0) ? '#FAFAF7' : '#ffffff';
        $cell0 = '<td class="banc-cell" style="padding:10px 14px;background:'.$bg0.';vertical-align:top;width:50%">'
               . '<div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#B0AB9F;margin-bottom:3px">'.htmlspecialchars($allVals[$i]['lbl']).'</div>'
               . '<div style="font-size:12px;font-weight:600;color:#2D2B28">'.htmlspecialchars($allVals[$i]['val']).'</div>'
               . '</td>';
        $cell1 = isset($allVals[$i+1])
            ? '<td class="banc-cell" style="padding:10px 14px;background:#ffffff;vertical-align:top;width:50%">'
              . '<div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#B0AB9F;margin-bottom:3px">'.htmlspecialchars($allVals[$i+1]['lbl']).'</div>'
              . '<div style="font-size:12px;font-weight:600;color:#2D2B28">'.htmlspecialchars($allVals[$i+1]['val']).'</div>'
              . '</td>'
            : '<td class="banc-fill" style="width:50%"></td>';
        $rows .= '<tr>'.$cell0.$cell1.'</tr>';
    }
    return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="px-sec" style="padding:0 28px 24px">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1.5px solid #E8E5DD;border-radius:6px;overflow:hidden">
<tr><td colspan="2" style="padding:10px 14px;background:'.$cpri.'">
  <table cellpadding="0" cellspacing="0" border="0"><tr>
    <td style="padding:0 8px 0 0;vertical-align:middle"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="'.$csec.'" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></td>
    <td style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:'.$csec.'">Datos para el pago</td>
  </tr></table>
</td></tr>'
. $rows .
'</table></td></tr></table>';
})() . '

' . ($linkPago ? '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:20px 28px;text-align:center">
<a href="' . htmlspecialchars($linkPago) . '" target="_blank" style="display:inline-block;padding:13px 36px;background:' . $cpri . ';color:' . $csec . ';font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;border-radius:6px">&#x1F4B3; Pagar Ahora</a>
<div style="font-size:11px;color:#94a3b8;margin-top:8px">' . htmlspecialchars($linkPago) . '</div>
</td></tr></table>' : '') . '

<!-- Pie de página -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . $cpri . '">
<tr>
<td style="padding:14px 28px;font-size:11px;color:rgba(255,255,255,.5)">' . htmlspecialchars($template['notas_pie'] ?? '') . ($whatsappOrg ? '<br><span style="margin-top:8px;display:inline-block">&#x1F4F1; WhatsApp: ' . htmlspecialchars($whatsappOrg) . '</span>' : '') . '</td>
<td style="padding:14px 10px;width:32px">
<div style="width:32px;height:4px;background:' . $csec . ';border-radius:2px"></div>
</td>
</tr>
</table>

</td></tr>
</table>

</body>
</html>';

// Asunto del correo: personalizado o por defecto
$emailAsunto = $asuntoOver ?: ($docTipoLabel . ' #' . $orderNumber . ' - QUANTUN Digital');

// Bloque de mensaje personalizado (va antes del documento)
$mensajeHtml = '';
if ($mensajeOver) {
    $mensajeHtml = '<table class="em-msg" width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="max-width:600px;margin:0 auto">'
        . '<tr><td style="padding:16px 10px 0 10px;font-family:' . $cfont . ',system-ui,sans-serif">'
        . '<div style="background:#f8fafc;border-left:4px solid ' . $cpri . ';padding:14px 18px;border-radius:0 6px 6px 0;font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap">'
        . htmlspecialchars($mensajeOver)
        . '</div></td></tr></table>';
    // Insertar antes del bloque principal del email
    $htmlFinal = str_replace(
        '<table class="em-wrap"',
        $mensajeHtml . "\n" . '<table class="em-wrap"',
        $htmlFinal
    );
}

// Previsualización en navegador (no envía): cid: → ruta web del logo
if (!empty($input['preview_only'])) {
    $logoWeb = '';
    if ($logoFilePath && strpos($logoFilePath, BASE_PATH) === 0) {
        $rel = str_replace('\\', '/', substr($logoFilePath, strlen(BASE_PATH)));
        $logoWeb = APP_URL . '/' . ltrim($rel, '/');
    }
    header('Content-Type: text/html; charset=utf-8');
    echo str_replace('cid:' . $logoCid, $logoWeb, $htmlFinal);
    exit;
}

// Enviar correo — logo como CID inline para compatibilidad universal
$mailer       = new Mailer();
$inlineImages = [];
if ($logoFilePath && @file_exists($logoFilePath)) {
    $inlineImages[] = ['path' => $logoFilePath, 'cid' => $logoCid, 'mime' => $logoMime];
}
$result = $mailer->send(
    $data['nombre_comercial'] . ' <' . $emailDest . '>',
    $emailAsunto,
    $htmlFinal,
    [],
    $inlineImages
);
// Limpiar archivo temporal del logo si se creó
if (!empty($tmpLogo) && @file_exists($tmpLogo)) {
    @unlink($tmpLogo);
}

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
