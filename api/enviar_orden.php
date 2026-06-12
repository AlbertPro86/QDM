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
// Gmail bloquea data: URIs y URLs externas requieren clic del usuario.
// CID embebe la imagen como parte MIME → visible en TODOS los clientes sin clic.
//
// Estrategia: usar getLogoEmailInline() centralizada (misma que cotizaciones
// y notificaciones), que busca archivos locales y embebe como CID.
// Así TODOS los emails del CRM usan la misma lógica robusta.
$logoCid      = 'logo_qd@quantun.digital';
$logoFilePath = '';
$logoMime     = 'image/png';
$tmpLogo      = '';
$logoSrc      = '';

// Buscar logo en disco: primero el negro (fondo del email siempre es blanco)
$_logoCandidates = [
    BASE_PATH . '/Assets/logo_quantun_digital_negro.png',
    BASE_PATH . '/assets/logo_quantun_digital_negro.png',
    BASE_PATH . '/../assets/quantun-logo.png',
    BASE_PATH . '/Assets/logo_quantun_digital_blanco.png',
    BASE_PATH . '/assets/logo_quantun_digital_blanco.png',
];

// Si la plantilla tiene un logo_url local, intentar primero
$logoUrlTemplate = !empty($template['logo_url']) ? trim($template['logo_url']) : '';
if ($logoUrlTemplate !== '' && !preg_match('#^https?://#i', $logoUrlTemplate)) {
    $localPath = BASE_PATH . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logoUrlTemplate), DIRECTORY_SEPARATOR);
    array_unshift($_logoCandidates, $localPath);
}

// Buscar el primer archivo que exista
foreach ($_logoCandidates as $_lf) {
    if (@is_file($_lf) && @filesize($_lf) > 100) {
        $logoFilePath = $_lf;
        $ext = strtolower(pathinfo($_lf, PATHINFO_EXTENSION));
        $logoMime = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp'][$ext] ?? 'image/png';
        break;
    }
}

// Si no hay archivo local y la plantilla tiene URL remota, descargar
if (!$logoFilePath && $logoUrlTemplate !== '' && preg_match('#^https?://#i', $logoUrlTemplate)) {
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
        $ctypeLower = strtolower(trim(explode(';', $ctype)[0]));
        $isPng  = substr($rawImg, 0, 4) === "\x89PNG";
        $isJpeg = substr($rawImg, 0, 2) === "\xFF\xD8";
        $isGif  = substr($rawImg, 0, 3) === 'GIF';
        $isWebp = strlen($rawImg) > 12 && substr($rawImg, 8, 4) === 'WEBP';
        $isSvg  = stripos(substr($rawImg, 0, 200), '<svg') !== false;
        $isImage = ($isPng || $isJpeg || $isGif || $isWebp || $isSvg) || (strpos($ctypeLower, 'image/') === 0);
        $isHtml  = stripos(substr($rawImg, 0, 300), '<html') !== false || stripos(substr($rawImg, 0, 300), '<!doctype') !== false;
        if ($isImage && !$isHtml) {
            $logoMime = $ctypeLower ?: 'image/png';
            if ($isSvg) $logoMime = 'image/svg+xml';
            $extMap  = ['image/png'=>'png','image/jpeg'=>'jpg','image/gif'=>'gif','image/webp'=>'webp','image/svg+xml'=>'svg'];
            $ext     = $extMap[$logoMime] ?? 'png';
            $tmpLogo = tempnam(sys_get_temp_dir(), 'qlogo_') . '.' . $ext;
            if (file_put_contents($tmpLogo, $rawImg) !== false) {
                $logoFilePath = $tmpLogo;
            }
        }
    }
}

// Armar la referencia CID para el HTML
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

// Enriquecer servicios con sub-ítems y features del paquete (igual que orden_compra.php)
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

// Generar HTML de la orden
$totalOriginal = 0;
$totalDescuento = 0;
foreach ($servicios as $svc) {
    $totalOriginal += $svc['monto_renovacion'];
    $totalDescuento += ($svc['descuento'] ?? 0);
}
$totalFinal = $totalOriginal - $totalDescuento;

// Generar tabla de servicios (con sub-ítems y features si es paquete)
$tablasServicios = '';
foreach ($servicios as $idx => $svc) {
    $qty       = $svc['_qty'] ?? 1;
    $precioU   = $svc['_precio_unit'] ?? $svc['monto_renovacion'];
    $descuento = $svc['descuento'] ?? 0;
    $subtotal  = $svc['monto_renovacion'] - $descuento;
    $pkgItems  = $svc['_pkg_items']    ?? [];
    $pkgFeats  = $svc['_pkg_features'] ?? [];
    $hasPkg    = !empty($pkgItems);

    $rowBg = ($idx % 2 === 0) ? '#ffffff' : '#FAFAF7';
    if ($hasPkg) {
        // Fila cabecera del paquete
        $tablasServicios .= '<tr style="background:#F3F2EE;border-bottom:1px solid #F0EFEB">
            <td class="itm-td" style="padding:10px 0;font-size:13px;font-weight:700;color:#0E0E0C;word-break:break-word">' . htmlspecialchars($svc['servicio_nombre']) . '</td>
            <td class="itm-td" style="padding:10px 0;font-size:13px;font-weight:700;color:#0E0E0C;text-align:center">' . $qty . '</td>
            <td class="itm-td" style="padding:10px 0;font-size:13px;font-weight:700;color:#0E0E0C;text-align:right;white-space:nowrap">$ ' . number_format($precioU, 0, ',', '.') . '</td>
            <td class="itm-td" style="padding:10px 0;font-size:13px;font-weight:700;color:#0E0E0C;text-align:right;white-space:nowrap">$ ' . number_format($subtotal, 0, ',', '.') . '</td>
        </tr>';
        // Sub-ítems
        foreach ($pkgItems as $item) {
            $tablasServicios .= '<tr style="background:#ffffff;border-bottom:1px solid #F0EFEB">
                <td colspan="3" class="itm-td" style="padding:7px 0 7px 14px;font-size:11px;color:#57544D;word-break:break-word">
                    <span style="display:inline-block;width:4px;height:4px;border-radius:50%;background:#C6C2BB;vertical-align:middle;margin-right:7px"></span>
                    <strong style="color:#2D2B28">' . htmlspecialchars($item['svc_nombre']) . '</strong>
                    <span style="color:#B0AB9F"> &#8212; </span>' . htmlspecialchars($item['ss_nombre']) . '
                </td>
                <td class="itm-td" style="padding:7px 0;font-size:11px;color:#8A867C;text-align:right;white-space:nowrap">
                    $ ' . number_format($item['precio'], 0, ',', '.') . '<span style="font-size:10px;opacity:.6"> /año</span>
                </td>
            </tr>';
        }
        // Features
        if (!empty($pkgFeats)) {
            $featHtml = '';
            foreach ($pkgFeats as $feat) {
                $featHtml .= '<span style="font-size:10px;color:#57544D;margin-right:14px;white-space:nowrap;display:inline-block">'
                    . '<svg width="10" height="10" fill="none" stroke="#2D8F5A" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin-right:3px"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                    . htmlspecialchars($feat) . '</span>';
            }
            $tablasServicios .= '<tr style="background:#FAFAF7;border-bottom:1px solid #E8E5DD">
                <td colspan="4" class="itm-td" style="padding:7px 0 9px 14px">' . $featHtml . '</td>
            </tr>';
        }
    } else {
        $tablasServicios .= '<tr style="background:' . $rowBg . ';border-bottom:1px solid #F0EFEB">
            <td class="itm-td" style="padding:10px 0;font-size:12px;color:#57544D;word-break:break-word">' . htmlspecialchars($svc['servicio_nombre']) . '</td>
            <td class="itm-td" style="padding:10px 0;font-size:12px;color:#8A867C;text-align:center">' . $qty . '</td>
            <td class="itm-td" style="padding:10px 0;font-size:12px;color:#8A867C;text-align:right;white-space:nowrap">$ ' . number_format($precioU, 0, ',', '.') . '</td>
            <td class="itm-td" style="padding:10px 0;font-size:12px;font-weight:700;color:#0E0E0C;text-align:right;white-space:nowrap">$ ' . number_format($subtotal, 0, ',', '.') . '</td>
        </tr>';
    }
}

// Atajos para interpolación limpia en el HTML
$cpri  = $template['color_primario'];
$csec  = $template['color_secundario'];
$cfont = $template['fuente'];

// Generar sección de bancarios (diseño clásica: fondo oscuro con grid)
$bancHtml = (function() use ($bancariosOver, $cpri, $csec) {
    $bancFields = [
        'titular' => ['lbl'=>'Titular',        'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
        'cedula'  => ['lbl'=>'Cédula / NIT',   'icon'=>'<rect x="3" y="4" width="18" height="16" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h4M8 14h8"/>'],
        'banco'   => ['lbl'=>'Banco',           'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 10v11M12 10v11M16 10v11"/>'],
        'cuenta'  => ['lbl'=>'N° de Cuenta',   'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>'],
        'tipo'    => ['lbl'=>'Tipo de Cuenta', 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
        'llave'   => ['lbl'=>'Llave',           'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>'],
    ];
    $allVals = [];
    foreach ($bancFields as $k => $cfg) {
        if (!empty($bancariosOver[$k])) $allVals[] = ['lbl'=>$cfg['lbl'],'val'=>$bancariosOver[$k],'icon'=>$cfg['icon']];
    }
    if (!$allVals) return '';
    $_mkCell = function(array $item) use ($csec) {
        return '<td style="padding:10px 16px;vertical-align:top;width:50%">'
            . '<div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:' . $csec . ';opacity:.65;margin-bottom:3px">' . htmlspecialchars($item['lbl']) . '</div>'
            . '<div style="font-size:12px;font-weight:700;color:#ffffff">' . htmlspecialchars($item['val']) . '</div>'
            . '</td>';
    };
    $rows = '';
    for ($i = 0; $i < count($allVals); $i += 2) {
        $cell0 = $_mkCell($allVals[$i]);
        $cell1 = isset($allVals[$i+1]) ? $_mkCell($allVals[$i+1]) : '<td style="width:50%"></td>';
        $rows .= '<tr>' . $cell0 . $cell1 . '</tr>';
    }
    return '<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td style="padding:0 28px 24px">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . $cpri . ';border-radius:4px;overflow:hidden">
<tr><td colspan="2" style="padding:12px 16px">
  <table cellpadding="0" cellspacing="0" border="0"><tr>
    <td style="padding:0 7px 0 0;vertical-align:middle"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="' . $csec . '" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></td>
    <td style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:' . $csec . '">Datos para transferencia bancaria</td>
  </tr></table>
</td></tr>
' . $rows . '
</table>
</td></tr></table>';
})();

$htmlFinal = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($docTipoLabel) . ' ' . htmlspecialchars($orderNumber) . '</title>
<style type="text/css">
body{margin:0;padding:0;background:#F5F3EE}
table{border-spacing:0;mso-table-lspace:0;mso-table-rspace:0}
img{border:0;height:auto;line-height:100%;outline:none;text-decoration:none}
.wrap{background:#ffffff;max-width:620px;margin:0 auto}
.msg-pre{max-width:620px;margin:0 auto}
@media only screen and (max-width:640px){
  body{padding:0 !important}
  .wrap,.msg-pre{max-width:100% !important;width:100% !important}
  .hdr-l,.hdr-r{display:block !important;width:100% !important;text-align:left !important;padding:16px 20px 8px !important}
  .hdr-r{text-align:right !important;padding-top:0 !important}
  .cli-l,.cli-r{display:block !important;width:100% !important;padding:12px 20px !important}
  .itm-td{padding:7px 8px !important;font-size:10px !important}
  .px{padding-left:16px !important;padding-right:16px !important}
  .tot-tbl{width:100% !important}
}
</style>
</head>
<body style="margin:0;padding:24px 10px;background:#F5F3EE;font-family:' . $cfont . ',system-ui,sans-serif;color:#0E0E0C;line-height:1.5">

<table class="wrap" width="620" cellpadding="0" cellspacing="0" border="0" align="center"
  style="background:#ffffff;max-width:620px;margin:0 auto;border-radius:3px;overflow:hidden;border:1px solid #E8E5DD">
<tr><td>

<!-- ═══ ENCABEZADO ═══ -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
  style="border-bottom:1.5px solid #E8E5DD">
<tr>
  <td class="hdr-l" style="padding:24px 28px;width:55%;vertical-align:middle">
    ' . ($logoSrc ? '<img src="' . $logoSrc . '" alt="Logo" style="display:block;max-width:150px;max-height:48px;height:auto;border:0">' : '<div style="font-size:16px;font-weight:800;color:#0E0E0C">' . htmlspecialchars($template['empresa_nombre'] ?? 'QUANTUN Digital') . '</div>') . '
    <div style="margin-top:10px;font-size:10px;color:#8A867C;line-height:1.8">
      ' . (!empty($template['empresa_nombre']) ? '<strong style="color:#0E0E0C;font-size:11px">' . htmlspecialchars($template['empresa_nombre']) . '</strong><br>' : '') . '
      ' . (!empty($template['empresa_nit']) ? 'NIT: ' . htmlspecialchars($template['empresa_nit']) . ' &nbsp;&middot;&nbsp; ' : '') . htmlspecialchars($template['empresa_email'] ?? '') . '<br>
      ' . htmlspecialchars($template['empresa_tel'] ?? '') . ' &nbsp;&middot;&nbsp; ' . htmlspecialchars($template['empresa_dir'] ?? '') . '
    </div>
  </td>
  <td class="hdr-r" style="padding:24px 28px;width:45%;vertical-align:top;text-align:right">
    <!-- Badge tipo documento — tabla para compatibilidad Gmail -->
    <table cellpadding="0" cellspacing="0" border="0" align="right">
      <tr><td style="background:' . $cpri . ';color:' . $csec . ';padding:5px 14px;border-radius:3px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;white-space:nowrap">' . htmlspecialchars($docTipoLabel) . '</td></tr>
    </table>
    <div style="clear:both"></div>
    <div style="font-size:22px;font-weight:900;color:#0E0E0C;letter-spacing:-1px;margin-top:8px">' . htmlspecialchars($orderNumber) . '</div>
    <div style="font-size:11px;color:#8A867C;margin-top:4px">' . $fechaEmision . '</div>
    ' . ($fechaUltPago ? '<div style="font-size:10px;color:#8A867C;margin-top:2px">Últ. pago: ' . htmlspecialchars($fechaUltPago) . '</div>' : '') . '
  </td>
</tr>
</table>

<!-- ═══ CLIENTE ═══ -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
  style="border-bottom:1.5px solid #E8E5DD">
<tr>
  <td class="cli-l" style="padding:16px 28px;vertical-align:top">
    <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:' . $cpri . ';margin-bottom:5px">Cobrado a</div>
    <div style="font-size:13px;font-weight:700;color:#0E0E0C">' . htmlspecialchars($data['nombre_comercial'] ?? '') . '</div>
    ' . (!empty($data['nit_cedula']) ? '<div style="font-size:10px;color:#8A867C;margin-top:2px">NIT: ' . htmlspecialchars($data['nit_cedula']) . '</div>' : '') . '
    <div style="font-size:10px;color:#8A867C;margin-top:2px">' . htmlspecialchars($data['direccion'] ?? '') . '</div>
  </td>
  <td class="cli-r" style="padding:16px 28px;vertical-align:top;text-align:right;white-space:nowrap">
    <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#8A867C;margin-bottom:3px">Método de Pago</div>
    <div style="font-size:11px;font-weight:700;color:#0E0E0C">' . htmlspecialchars($metodoPagoOver ?? '') . '</div>
  </td>
</tr>
</table>

<!-- ═══ ITEMS ═══ -->
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td class="px" style="padding:20px 28px 10px">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse">
<thead>
  <tr>
    <th class="itm-td" style="padding:8px 0;text-align:left;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:' . $cpri . ';border-bottom:2px solid ' . $cpri . '">Descripción</th>
    <th class="itm-td" style="padding:8px 0;text-align:center;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:' . $cpri . ';width:40px;border-bottom:2px solid ' . $cpri . '">Cant.</th>
    <th class="itm-td" style="padding:8px 0;text-align:right;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:' . $cpri . ';width:90px;border-bottom:2px solid ' . $cpri . '">Precio unit.</th>
    <th class="itm-td" style="padding:8px 0;text-align:right;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:' . $cpri . ';width:90px;border-bottom:2px solid ' . $cpri . '">Total</th>
  </tr>
</thead>
<tbody>
' . $tablasServicios . '
</tbody>
</table>
</td></tr>

<!-- Totales -->
<tr><td class="px" style="padding:0 28px 28px">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td align="right">
<table class="tot-tbl" width="240" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding:5px 0;font-size:11px;color:#8A867C;border-bottom:1px solid #F0EFEB">Subtotal</td>
    <td style="padding:5px 0;font-size:11px;color:#8A867C;text-align:right;border-bottom:1px solid #F0EFEB">$ ' . number_format($totalOriginal, 0, ',', '.') . '</td>
  </tr>
  ' . ($totalDescuento > 0 ? '<tr>
    <td style="padding:5px 0;font-size:11px;color:#8A867C;border-bottom:1px solid #F0EFEB">Descuento</td>
    <td style="padding:5px 0;font-size:11px;color:#8A867C;text-align:right;border-bottom:1px solid #F0EFEB">&#8211; $ ' . number_format($totalDescuento, 0, ',', '.') . '</td>
  </tr>' : '') . '
  <tr><td colspan="2" style="padding-top:10px">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="' . $cpri . '" style="background:' . $cpri . ';border-radius:3px">
      <tr>
        <td style="padding:12px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:' . $csec . '">Total</td>
        <td style="padding:12px 14px;font-size:18px;font-weight:900;color:' . $csec . ';text-align:right">$ ' . number_format($totalFinal, 0, ',', '.') . '</td>
      </tr>
    </table>
  </td></tr>
</table>
</td></tr></table>
</td></tr>
</table>

<!-- ═══ NOTAS PIE ═══ -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
  style="border-top:1.5px solid #E8E5DD">
<tr>
  <td style="padding:12px 28px;font-size:10px;color:#8A867C;line-height:1.7">
    ' . htmlspecialchars($template['notas_pie'] ?? '') . ($whatsappOrg ? '<br>WhatsApp: ' . htmlspecialchars($whatsappOrg) : '') . '
  </td>
</tr>
</table>

<!-- ═══ BANCARIOS ═══ -->
' . $bancHtml . '

<!-- ═══ LINK DE PAGO ═══ -->
' . ($linkPago ? '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:16px 28px 24px;text-align:center">
<a href="' . htmlspecialchars($linkPago) . '" target="_blank" style="display:inline-block;padding:12px 32px;background:' . $cpri . ';color:' . $csec . ';font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;border-radius:3px">Pagar Ahora</a>
<div style="font-size:10px;color:#94a3b8;margin-top:6px">' . htmlspecialchars($linkPago) . '</div>
</td></tr></table>' : '') . '

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
$mailer       = Mailer::fromDb($pdo);
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
