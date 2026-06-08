<?php
/**
 * CRM QUANTUN Digital — Cron: Resumen diario de notificaciones
 *
 * Llamar desde Hostinger hPanel → Cron Jobs:
 *   Comando: php /home/u260705801/domains/quantundigital.com/public_html/crm/cron/resumen_diario.php
 *   Frecuencia: Diario a la hora configurada (ej. 0 8 * * *)
 *
 * También puede llamarse por URL con token:
 *   https://quantundigital.com/crm/cron/resumen_diario.php?token=CRON_SECRET
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$isCli = php_sapi_name() === 'cli';

// Protección si se llama por HTTP
if (!$isCli) {
    $token = $_GET['token'] ?? '';
    $cronSecret = env('CRON_SECRET', '');
    if (!$cronSecret || $token !== $cronSecret) {
        http_response_code(403);
        die('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$log = function(string $msg) use ($isCli) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($isCli) echo $line . PHP_EOL;
    else        echo $line . "\n";
};

$log('=== Resumen diario iniciado ===');

try {
    $pdo = db();
} catch (Exception $e) {
    $log('ERROR BD: ' . $e->getMessage());
    exit(1);
}

// ── Verificar SMTP ────────────────────────────────────────────────────────────
$smtpUser = env('MAIL_USERNAME', '');
$smtpPass = env('MAIL_PASSWORD', '');
if (!$smtpUser || $smtpUser === 'tu_correo@gmail.com' || !$smtpPass || $smtpPass === 'tu_app_password') {
    $log('ERROR: SMTP no configurado en .env');
    exit(1);
}

// ── Cargar configuración ──────────────────────────────────────────────────────
function getCronCfg(PDO $pdo, string $clave, string $default = ''): string {
    $st = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = ?");
    $st->execute([$clave]);
    $v = $st->fetchColumn();
    return ($v !== false && $v !== null) ? (string)$v : $default;
}

$notifActiva = getCronCfg($pdo, 'notif_activa', '1');
if ($notifActiva !== '1') {
    $log('Notificaciones desactivadas. Saliendo.');
    exit(0);
}

$emailDest = getCronCfg($pdo, 'notif_email', env('ADMIN_EMAIL', ''));
$logoUrl   = getCronCfg($pdo, 'notif_logo_url', '');
$diasJson  = getCronCfg($pdo, 'notif_dias', '[1,2,3,4,5]');
$diasActivos = json_decode($diasJson, true) ?: [1,2,3,4,5];

if (!$emailDest || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
    $log('ERROR: No hay correo destinatario configurado en Configuraciones.');
    exit(1);
}

// Verificar día de la semana (1=lunes … 7=domingo, PHP isodate)
$diaSemana = (int)date('N'); // 1=lun … 7=dom
if (!in_array($diaSemana, $diasActivos)) {
    $log("Hoy es día $diaSemana — no está en los días activos. Saliendo.");
    exit(0);
}

$log("Enviando resumen a: $emailDest");

// ── Contar datos ──────────────────────────────────────────────────────────────
$nTareas = 0;
$nRenovActual = 0;
$nRenovProx   = 0;
$nLeadsNuevos = 0;

try {
    $nTareas = (int)$pdo->query("SELECT COUNT(*) FROM tareas WHERE estado IN ('pendiente','en_progreso')")->fetchColumn();
} catch (Exception $e) {}

try {
    $hoy       = date('Y-m-d');
    $finMes    = date('Y-m-t');
    $inicioProx= date('Y-m-01', strtotime('+1 month'));
    $finProx   = date('Y-m-t',  strtotime('+1 month'));
    $st = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE fecha_renovacion BETWEEN ? AND ?");
    $st->execute([$hoy, $finMes]);
    $nRenovActual = (int)$st->fetchColumn();
    $st->execute([$inicioProx, $finProx]);
    $nRenovProx = (int)$st->fetchColumn();
} catch (Exception $e) {}

try {
    $hoy7 = date('Y-m-d', strtotime('-7 days'));
    $st = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE created_at >= ?");
    $st->execute([$hoy7]);
    $nLeadsNuevos = (int)$st->fetchColumn();
} catch (Exception $e) {}

// ── Logo ──────────────────────────────────────────────────────────────────────
$logoSrc = getLogoEmailSrc($pdo, $logoUrl);
$logoTag = $logoSrc
    ? '<img src="' . htmlspecialchars($logoSrc) . '" alt="QUANTUN Digital" height="36" style="display:block">'
    : '<span style="font-size:18px;font-weight:900;color:#0E0E0C;letter-spacing:-0.5px">QUANTUN Digital</span>';

// ── HTML del correo ───────────────────────────────────────────────────────────
$fecha = date('d/m/Y');
$hora  = date('H:i');

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#ECEAE3;font-family:Segoe UI,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#ECEAE3;padding:28px 12px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
  <tr><td style="background:#ffffff;padding:18px 28px;border-bottom:3px solid #C6F24E">' . $logoTag . '</td></tr>
  <tr><td style="padding:24px 28px 8px">
    <div style="font-size:13px;font-weight:700;color:#0E0E0C;margin-bottom:4px">Resumen del día</div>
    <div style="font-size:11px;color:#57544D">' . $fecha . ' · ' . $hora . '</div>
  </td></tr>
  <tr><td style="padding:16px 28px">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="padding:12px 16px;background:#F5F5F2;border-radius:6px;width:30%">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#57544D;margin-bottom:5px">Tareas</div>
          <div style="font-size:26px;font-weight:900;color:#0E0E0C">' . $nTareas . '</div>
          <div style="font-size:10px;color:#57544D;margin-top:2px">Pendiente / progreso</div>
        </td>
        <td style="width:3%"></td>
        <td style="padding:12px 16px;background:#F5F5F2;border-radius:6px;width:30%">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#57544D;margin-bottom:5px">Renovaciones</div>
          <div style="font-size:26px;font-weight:900;color:#0E0E0C">' . ($nRenovActual + $nRenovProx) . '</div>
          <div style="font-size:10px;color:#57544D;margin-top:2px">Este y próximo mes</div>
        </td>
        <td style="width:3%"></td>
        <td style="padding:12px 16px;background:#F5F5F2;border-radius:6px;width:30%">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#57544D;margin-bottom:5px">Leads nuevos</div>
          <div style="font-size:26px;font-weight:900;color:#0E0E0C">' . $nLeadsNuevos . '</div>
          <div style="font-size:10px;color:#57544D;margin-top:2px">Últimos 7 días</div>
        </td>
      </tr>
    </table>
  </td></tr>
  <tr><td style="padding:16px 28px 24px">
    <a href="https://quantundigital.com/crm/dashboard.php" style="display:inline-block;background:#0E0E0C;color:#C6F24E;text-decoration:none;padding:10px 20px;border-radius:6px;font-size:12px;font-weight:700">
      Ver CRM
    </a>
  </td></tr>
  <tr><td style="padding:14px 28px;border-top:1px solid #EEECE6;font-size:10px;color:#8A867C;text-align:center">
    Resumen automático diario · CRM QUANTUN Digital
  </td></tr>
</table>
</td></tr>
</table>
</body></html>';

// ── Enviar ────────────────────────────────────────────────────────────────────
$mailer = new Mailer();
$result = $mailer->send(
    'CRM Admin <' . $emailDest . '>',
    'Resumen del día — CRM QUANTUN · ' . $fecha,
    $html
);

if ($result['ok']) {
    $log("OK: Correo enviado a $emailDest");
    exit(0);
} else {
    $log('ERROR al enviar: ' . $result['error']);
    exit(1);
}
