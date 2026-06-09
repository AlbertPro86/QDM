<?php
/**
 * CRM QUANTUN Digital — API Notificación / Prueba de resumen
 * GET → Envía un correo de prueba al email configurado en crm_configuraciones
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();

// ── Cargar configuración ──────────────────────────────────────────────────
function getCfgVal(PDO $pdo, string $clave, string $default = ''): string {
    $st = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = ?");
    $st->execute([$clave]);
    $v = $st->fetchColumn();
    return ($v !== false && $v !== null) ? (string)$v : $default;
}

$emailDest = getCfgVal($pdo, 'notif_email', env('ADMIN_EMAIL', ''));
$logoUrl   = getCfgVal($pdo, 'notif_logo_url', '');

if (!$emailDest || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'No hay correo destinatario configurado. Configúralo en Configuraciones.'], 422);
}

// Verificar SMTP configurado
$smtpUser = env('MAIL_USERNAME', '');
$smtpPass = env('MAIL_PASSWORD', '');
if (!$smtpUser || $smtpUser === 'tu_correo@gmail.com' || !$smtpPass || $smtpPass === 'tu_app_password') {
    jsonResponse(['error' => 'El servidor de correo SMTP no está configurado en el archivo .env'], 503);
}

// ── Contar datos para el resumen ──────────────────────────────────────────
$nTareas = 0;
$nRenovacionesActual = 0;
$nRenovacionesProx   = 0;

try {
    $st = $pdo->query("SELECT COUNT(*) FROM tareas WHERE estado IN ('pendiente','en_progreso','revision')");
    $nTareas = (int)$st->fetchColumn();
} catch (Exception $e) {}

try {
    $hoy        = date('Y-m-d');
    $inicioMes  = date('Y-m-01');           // 1ro del mes actual
    $finMes     = date('Y-m-t');            // último día del mes actual
    $inicioProx = date('Y-m-01', strtotime('+1 month'));
    $finProx    = date('Y-m-t',  strtotime('+1 month'));

    // Renovaciones del mes actual (todos los servicios activos que vencen este mes)
    $st = $pdo->prepare("SELECT COUNT(*) FROM cliente_servicios
                          WHERE estado = 'activo' AND fecha_vencimiento BETWEEN ? AND ?");
    $st->execute([$inicioMes, $finMes]);
    $nRenovacionesActual = (int)$st->fetchColumn();

    // Renovaciones del próximo mes
    $st->execute([$inicioProx, $finProx]);
    $nRenovacionesProx = (int)$st->fetchColumn();
} catch (Exception $e) {}

// ── Construir HTML del correo ─────────────────────────────────────────────
$fecha    = date('d/m/Y');
$hora     = date('H:i');
$logoSrc  = getLogoEmailSrc($pdo, $logoUrl);
$logoTag  = $logoSrc
    ? '<img src="' . htmlspecialchars($logoSrc) . '" alt="QUANTUN Digital" height="36" style="display:block">'
    : '<span style="font-size:18px;font-weight:900;color:#0E0E0C;letter-spacing:-0.5px">QUANTUN Digital</span>';

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#ECEAE3;font-family:Segoe UI,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#ECEAE3;padding:28px 12px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
  <tr><td style="background:#ffffff;padding:18px 28px;border-bottom:3px solid #C6F24E">
    ' . $logoTag . '
  </td></tr>
  <tr><td style="padding:24px 28px 8px">
    <div style="font-size:13px;font-weight:700;color:#0E0E0C;margin-bottom:4px">Resumen de prueba</div>
    <div style="font-size:11px;color:#57544D">' . $fecha . ' · ' . $hora . '</div>
  </td></tr>
  <tr><td style="padding:16px 28px">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="padding:12px 16px;background:#F5F5F2;border-radius:6px;width:48%">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#57544D;margin-bottom:5px">Tareas activas</div>
          <div style="font-size:26px;font-weight:900;color:#0E0E0C">' . $nTareas . '</div>
          <div style="font-size:10px;color:#57544D;margin-top:2px">Pendiente o en progreso</div>
        </td>
        <td style="width:4%"></td>
        <td style="padding:12px 16px;background:#F5F5F2;border-radius:6px;width:48%">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#57544D;margin-bottom:5px">Renovaciones</div>
          <div style="font-size:26px;font-weight:900;color:#0E0E0C">' . ($nRenovacionesActual + $nRenovacionesProx) . '</div>
          <div style="font-size:10px;color:#57544D;margin-top:2px">Este mes y el próximo</div>
        </td>
      </tr>
    </table>
  </td></tr>
  <tr><td style="padding:16px 28px 24px">
    <div style="background:#C6F24E;border-radius:6px;padding:12px 16px;font-size:12px;color:#0E0E0C;font-weight:600">
      ✓ El sistema de notificaciones está funcionando correctamente.
    </div>
  </td></tr>
  <tr><td style="padding:14px 28px;border-top:1px solid #EEECE6;font-size:10px;color:#8A867C;text-align:center">
    Este es un correo de prueba enviado desde CRM QUANTUN Digital
  </td></tr>
</table>
</td></tr>
</table>
</body></html>';

// ── Enviar ────────────────────────────────────────────────────────────────
$mailer = Mailer::fromDb($pdo);
$result = $mailer->send(
    'CRM Admin <' . $emailDest . '>',
    '✓ Prueba de notificación — CRM QUANTUN Digital',
    $html
);

if ($result['ok']) {
    jsonResponse([
        'success'                => true,
        'tareas'                 => $nTareas,
        'renovaciones_mes_actual'=> $nRenovacionesActual,
        'renovaciones_prox_mes'  => $nRenovacionesProx,
        'enviado_a'              => $emailDest,
        'smtp_host'              => env('MAIL_HOST', ''),
        'nota'                   => 'Revisa también la carpeta de Spam si no ves el correo.',
    ]);
} else {
    $smtpInfo = env('MAIL_HOST','?') . ':' . env('MAIL_PORT','?') . ' (' . env('MAIL_ENCRYPTION','?') . ')';
    jsonResponse([
        'error'     => $result['error'],
        'smtp_info' => $smtpInfo,
        'enviado_a' => $emailDest,
        'ayuda'     => 'Verifica las credenciales SMTP en Configuraciones → Correo.',
    ], 500);
}
