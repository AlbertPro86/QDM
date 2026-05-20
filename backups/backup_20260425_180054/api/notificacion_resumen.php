<?php
/**
 * CRM QUANTUN Digital — Notificación de resumen diario
 * Envía al administrador: tareas pendientes + renovaciones mes actual y próximo mes
 * Renovaciones agrupadas por cliente (una fila por cliente)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

$esCron = (php_sapi_name() === 'cli');
if (!$esCron && !isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$pdo = db();


$notifActiva   = getCfg($pdo, 'notif_activa', '1');
$emailAdmin    = getCfg($pdo, 'notif_email',  env('ADMIN_EMAIL', 'contacto@quantundigital.com'));
$whatsappAdmin = getCfg($pdo, 'notif_whatsapp', '');
$notifDiasJson = getCfg($pdo, 'notif_dias',   json_encode([1,2,3,4,5]));
$inclTareas    = getCfg($pdo, 'notif_incluir_tareas', '1');
$inclRenov     = getCfg($pdo, 'notif_incluir_renovaciones', '1');
$notifDias     = json_decode($notifDiasJson, true) ?: [1,2,3,4,5];

if ($esCron) {
    if ($notifActiva !== '1') exit(0);
    if (!in_array((int)date('w'), $notifDias)) exit(0);
}

// ── Verificar SMTP ────────────────────────────────────────────────────────────
$smtpUser = env('MAIL_USERNAME', '');
$smtpPass = env('MAIL_PASSWORD', '');
if (!$smtpUser || $smtpUser === 'tu_correo@gmail.com' || !$smtpPass || $smtpPass === 'tu_app_password') {
    jsonResponse(['error' => 'El servidor de correo no está configurado.'], 503);
}

// ── 1. Tareas pendientes ──────────────────────────────────────────────────────
$tareas = [];
if ($inclTareas === '1') {
    try {
        $stmt = $pdo->query("
            SELECT t.titulo, t.prioridad, t.fecha_limite, t.responsable, t.estado,
                   COALESCE(c.nombre_comercial, l.nombre, '') AS cliente_nombre
            FROM tareas t
            LEFT JOIN clientes c ON t.cliente_id = c.id
            LEFT JOIN leads l    ON t.lead_id    = l.id
            WHERE t.estado IN ('pendiente','en_progreso')
            ORDER BY CASE t.prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END,
                     t.fecha_limite IS NULL, t.fecha_limite ASC
            LIMIT 50
        ");
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// ── 2. Renovaciones mes actual — agrupadas por cliente ────────────────────────
$renovMesActual = [];
if ($inclRenov === '1') {
    try {
        $stmt = $pdo->query("
            SELECT
                c.id AS cliente_id,
                c.nombre_comercial,
                MIN(cs.fecha_vencimiento)    AS fecha_vencimiento,
                SUM(cs.monto_renovacion)     AS total_renovacion,
                DATEDIFF(MIN(cs.fecha_vencimiento), CURDATE()) AS dias_restantes
            FROM cliente_servicios cs
            INNER JOIN clientes c ON cs.cliente_id = c.id
            WHERE cs.estado = 'activo'
              AND c.estado  = 'activo'
              AND cs.frecuencia != 'unico'
              AND MONTH(cs.fecha_vencimiento) = MONTH(CURDATE())
              AND YEAR(cs.fecha_vencimiento)  = YEAR(CURDATE())
            GROUP BY c.id, c.nombre_comercial
            ORDER BY MIN(cs.fecha_vencimiento) ASC
        ");
        $renovMesActual = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// ── 3. Renovaciones próximo mes — agrupadas por cliente ───────────────────────
$renovProxMes = [];
if ($inclRenov === '1') {
    try {
        $stmt = $pdo->query("
            SELECT
                c.id AS cliente_id,
                c.nombre_comercial,
                MIN(cs.fecha_vencimiento)    AS fecha_vencimiento,
                SUM(cs.monto_renovacion)     AS total_renovacion,
                DATEDIFF(MIN(cs.fecha_vencimiento), CURDATE()) AS dias_restantes
            FROM cliente_servicios cs
            INNER JOIN clientes c ON cs.cliente_id = c.id
            WHERE cs.estado = 'activo'
              AND c.estado  = 'activo'
              AND cs.frecuencia != 'unico'
              AND MONTH(cs.fecha_vencimiento) = MONTH(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
              AND YEAR(cs.fecha_vencimiento)  = YEAR(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
            GROUP BY c.id, c.nombre_comercial
            ORDER BY MIN(cs.fecha_vencimiento) ASC
        ");
        $renovProxMes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtFecha($f): string {
    return $f ? (new DateTime($f))->format('d/m/Y') : '—';
}
function fmtMoney($n): string {
    return '$&nbsp;' . number_format((float)$n, 0, ',', '.') . '&nbsp;COP';
}
function colorPrioridad($p): string {
    return match($p) { 'alta' => '#ef4444', 'media' => '#f59e0b', default => '#10b981' };
}
function badgePrioridad($p): string {
    $color = colorPrioridad($p);
    $label = match($p) { 'alta' => 'Alta', 'media' => 'Media', default => 'Baja' };
    return "<span style='display:inline-block;padding:2px 8px;background:{$color};color:#fff;border-radius:12px;font-size:11px;font-weight:700'>{$label}</span>";
}
function colorDias($d): string {
    if ($d < 0)  return '#ef4444';
    if ($d <= 7) return '#f59e0b';
    return '#10b981';
}
function mesEnEspanol(int $n, int $y): string {
    $m = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    return $m[$n - 1] . ' ' . $y;
}

// Genera filas de renovaciones agrupadas por cliente
function filasRenovCliente(array $lista, string $colorBorde): string {
    if (empty($lista)) {
        return "<tr><td colspan='3' style='padding:18px;text-align:center;color:#94a3b8;font-size:12px'>Sin renovaciones este mes</td></tr>";
    }
    $rows = '';
    foreach ($lista as $r) {
        $dias  = (int)$r['dias_restantes'];
        $cDias = colorDias($dias);
        $fechaFmt = fmtFecha($r['fecha_vencimiento']);
        $colorF   = $dias < 0 ? '#ef4444' : ($dias <= 7 ? '#b45309' : '#475569');
        $sufijo   = $dias < 0 ? ' (vencido)' : ($dias === 0 ? ' (hoy)' : ($dias <= 7 ? " ({$dias}d)" : ''));
        $rows .= "
        <tr>
            <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#0f172a;font-weight:600;white-space:nowrap'>" . htmlspecialchars($r['nombre_comercial']) . "</td>
            <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;color:{$colorF};font-weight:600;white-space:nowrap'>{$fechaFmt}{$sufijo}</td>
            <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;font-weight:700;color:#0f172a;text-align:right;white-space:nowrap'>" . fmtMoney($r['total_renovacion']) . "</td>
        </tr>";
    }
    $gran = array_sum(array_column($lista, 'total_renovacion'));
    $rows .= "
    <tr style='background:#f8fafc'>
        <td colspan='2' style='padding:8px 12px;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em'>Total</td>
        <td style='padding:8px 12px;font-size:13px;font-weight:900;color:#0f172a;text-align:right;white-space:nowrap'>" . fmtMoney($gran) . "</td>
    </tr>";
    return $rows;
}

// ── Logo — Base64 embebido (visible en TODOS los clientes de correo) ──────────
$logoSrc  = getLogoEmailSrc($pdo);
$logoHtml = $logoSrc
    ? "<img src='{$logoSrc}' alt='QUANTUN Digital' height='40' style='display:block;height:40px;max-width:200px;object-fit:contain'>"
    : "<span style='font-size:16px;font-weight:900;color:#c9f31d'>QUANTUN Digital</span>";

// ── Fechas y conteos ──────────────────────────────────────────────────────────
$hoy          = date('d/m/Y');
$mesN         = (int)date('n');
$mesY         = (int)date('Y');
$mesFmt       = mesEnEspanol($mesN, $mesY);
$proxMesN     = $mesN === 12 ? 1 : $mesN + 1;
$proxMesY     = $mesN === 12 ? $mesY + 1 : $mesY;
$proxMesFmt   = mesEnEspanol($proxMesN, $proxMesY);
$totalT       = count($tareas);
$totalRActual = count($renovMesActual);
$totalRProx   = count($renovProxMes);
$sumaRActual  = array_sum(array_column($renovMesActual, 'total_renovacion'));
$sumaRProx    = array_sum(array_column($renovProxMes,   'total_renovacion'));

// ── HTML Tareas ───────────────────────────────────────────────────────────────
$filasT = '';
foreach ($tareas as $t) {
    $vencida   = $t['fecha_limite'] && $t['fecha_limite'] < date('Y-m-d');
    $bgFila    = $vencida ? '#fff5f5' : '#ffffff';
    $fechaCol  = $t['fecha_limite']
        ? "<span style='color:" . ($vencida ? '#ef4444' : '#64748b') . ";font-weight:" . ($vencida ? '600' : '400') . "'>" . fmtFecha($t['fecha_limite']) . "</span>"
        : "<span style='color:#cbd5e1'>—</span>";
    $filasT .= "
    <tr style='background:{$bgFila}'>
        <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#0f172a;font-weight:600;white-space:nowrap;overflow:hidden;max-width:200px'>" . htmlspecialchars($t['titulo']) . "</td>
        <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#475569;white-space:nowrap'>" . htmlspecialchars($t['cliente_nombre'] ?: '—') . "</td>
        <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap'>{$fechaCol}</td>
    </tr>";
}
if (!$filasT) $filasT = "<tr><td colspan='3' style='padding:14px;text-align:center;color:#94a3b8;font-size:12px'>Sin tareas pendientes</td></tr>";

$theadRenov = "
<thead><tr style='background:#f8fafc'>
    <th style='padding:7px 12px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap'>Cliente</th>
    <th style='padding:7px 12px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap'>Vence</th>
    <th style='padding:7px 12px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap'>Total</th>
</tr></thead>";

// ── HTML del correo ───────────────────────────────────────────────────────────
$html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Resumen CRM &ndash; ' . $hoy . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

  <!-- ═══ HEADER ════════════════════════════════════════════════════════════ -->
  <tr>
    <td style="background:#0f172a;padding:18px 32px;border-radius:12px 12px 0 0">
      <table width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="vertical-align:middle">' . $logoHtml . '</td>
        <td style="text-align:right;vertical-align:middle">
          <span style="display:block;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em">Resumen diario</span>
          <span style="display:block;font-size:13px;font-weight:700;color:#e2e8f0;margin-top:2px">' . $hoy . '</span>
        </td>
      </tr></table>
    </td>
  </tr>

  <!-- ═══ TAREAS ════════════════════════════════════════════════════════════ -->
  <tr>
    <td style="padding:16px 32px 0 32px;background:#ffffff">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:8px;overflow:hidden;border:1px solid #0f172a">
        <thead>
          <tr style="background:#0f172a">
            <th colspan="3" style="padding:9px 14px;text-align:left;font-size:12px;font-weight:700;color:#ffffff">Tareas pendientes</th>
          </tr>
          <tr style="background:#1e293b">
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:#ffffff;white-space:nowrap;width:44%">Tarea</th>
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:#ffffff;white-space:nowrap;width:32%">Cliente</th>
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:#ffffff;white-space:nowrap;width:24%">Fecha</th>
          </tr>
        </thead>
        <tbody>' . $filasT . '</tbody>
      </table>
    </td>
  </tr>

  <!-- ═══ RENOVACIONES MES ACTUAL ══════════════════════════════════════════ -->
  <tr>
    <td style="padding:12px 32px 0 32px;background:#ffffff">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:8px;overflow:hidden;border:1px solid #c9f31d">
        <thead>
          <tr style="background:#0f172a">
            <th colspan="3" style="padding:9px 14px;text-align:left;font-size:12px;font-weight:700;color:#c9f31d">Renovaciones &mdash; ' . $mesFmt . '</th>
          </tr>
          <tr style="background:#1e293b">
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:#c9f31d;white-space:nowrap">Cliente</th>
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:#c9f31d;white-space:nowrap">Vence</th>
            <th style="padding:7px 12px;text-align:right;font-size:10px;font-weight:700;color:#c9f31d;white-space:nowrap">Total</th>
          </tr>
        </thead>
        <tbody>' . filasRenovCliente($renovMesActual, '#0c4a6e') . '</tbody>
      </table>
    </td>
  </tr>

  <!-- ═══ RENOVACIONES PRÓXIMO MES ═════════════════════════════════════════ -->
  <tr>
    <td style="padding:12px 32px 20px 32px;background:#ffffff">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:8px;overflow:hidden;border:1px solid #f97316">
        <thead>
          <tr style="background:#0f172a">
            <th colspan="3" style="padding:9px 14px;text-align:left;font-size:12px;font-weight:700;color:#f97316">Renovaciones &mdash; ' . $proxMesFmt . '</th>
          </tr>
          <tr style="background:#1e293b">
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:#f97316;white-space:nowrap">Cliente</th>
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:#f97316;white-space:nowrap">Vence</th>
            <th style="padding:7px 12px;text-align:right;font-size:10px;font-weight:700;color:#f97316;white-space:nowrap">Total</th>
          </tr>
        </thead>
        <tbody>' . filasRenovCliente($renovProxMes, '#14532d') . '</tbody>
      </table>
    </td>
  </tr>

  <!-- ═══ FOOTER ════════════════════════════════════════════════════════════ -->
  <tr>
    <td style="background:#f8fafc;padding:18px 32px;border-radius:0 0 12px 12px;border-top:1px solid #e2e8f0">
      <table width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="vertical-align:middle">
          <span style="font-size:11px;color:#94a3b8">CRM QUANTUN Digital &mdash; resumen automático</span>' . ($whatsappAdmin ? '<br><span style="font-size:10px;color:#64748b;margin-top:4px;display:block">📱 WhatsApp: <strong>' . htmlspecialchars($whatsappAdmin) . '</strong></span>' : '') . '
        </td>
        <td style="text-align:right;vertical-align:middle">
          <a href="' . APP_URL . '/dashboard.php"
             style="display:inline-block;padding:8px 20px;background:#c9f31d;color:#0f172a;border-radius:20px;font-size:12px;font-weight:800;text-decoration:none;letter-spacing:.02em">
            Ir al CRM &rarr;
          </a>
        </td>
      </tr></table>
    </td>
  </tr>

  <!-- CRÉDITO -->
  <tr>
    <td style="padding:14px;text-align:center;font-size:10px;color:#cbd5e1">
      Enviado desde CRM QUANTUN Digital
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>';

// ── Enviar ────────────────────────────────────────────────────────────────────
$mailer = new Mailer();
$asunto = "📋 Resumen CRM · {$totalT} tareas · {$totalRActual} renov. {$mesFmt} · {$totalRProx} renov. {$proxMesFmt} · {$hoy}";
$result = $mailer->send("QUANTUN Digital <{$emailAdmin}>", $asunto, $html);

if ($result['ok']) {
    jsonResponse(['success' => true, 'message' => "Resumen enviado a {$emailAdmin}", 'tareas' => $totalT, 'renovaciones_mes_actual' => $totalRActual, 'renovaciones_prox_mes' => $totalRProx]);
} else {
    jsonResponse(['error' => 'Error al enviar: ' . $result['error']], 500);
}
