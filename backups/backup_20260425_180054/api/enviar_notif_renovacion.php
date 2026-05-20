<?php
/**
 * CRM QUANTUN Digital — Notificación de renovaciones al cliente
 *
 * Modos:
 *   GET  ?cliente_id=X&test=1   → envía prueba inmediata (requiere sesión)
 *   CLI  php enviar_notif_renovacion.php  → revisa TODOS los clientes activos
 *        y envía donde corresponda según días antes configurados
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);
}

$pdo = db();

// Auto-migración tabla config (por si no existe aún)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_cliente_notif_config (
            cliente_id              INT          NOT NULL PRIMARY KEY,
            activa                  TINYINT(1)   NOT NULL DEFAULT 1,
            dias_antes              INT          NOT NULL DEFAULT 15,
            hora_envio              TIME         NOT NULL DEFAULT '08:00:00',
            asunto_personalizado    VARCHAR(255) NOT NULL DEFAULT '',
            mensaje_personalizado   TEXT         NOT NULL DEFAULT '',
            updated_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

// ── Función principal: construye y envía el correo ─────────────────────────────
function enviarNotifCliente(PDO $pdo, int $clienteId, bool $esPrueba = false): array {

    // Datos del cliente
    $sc = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $sc->execute([$clienteId]);
    $cliente = $sc->fetch();
    if (!$cliente) return ['ok' => false, 'error' => 'Cliente no encontrado'];

    $emailDest = trim($cliente['email_facturacion'] ?? '');
    if (!$emailDest || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'El cliente no tiene email de facturación válido'];
    }

    // Config notificaciones del cliente
    $sc2 = $pdo->prepare("SELECT * FROM crm_cliente_notif_config WHERE cliente_id = ?");
    $sc2->execute([$clienteId]);
    $cfg = $sc2->fetch() ?: ['dias_antes' => 15, 'asunto_personalizado' => '', 'mensaje_personalizado' => ''];

    $diasAntes = intval($cfg['dias_antes'] ?? 15);

    // Servicios que vencen en los próximos N días
    $fechaLimite = date('Y-m-d', strtotime("+{$diasAntes} days"));
    $hoy         = date('Y-m-d');

    $ss = $pdo->prepare("
        SELECT cs.*, s.nombre AS servicio_nombre
        FROM   cliente_servicios cs
        JOIN   servicios s ON s.id = cs.servicio_id
        WHERE  cs.cliente_id   = ?
          AND  cs.estado       = 'activo'
          AND  cs.fecha_vencimiento BETWEEN ? AND ?
        ORDER  BY cs.fecha_vencimiento ASC
    ");
    $ss->execute([$clienteId, $hoy, $fechaLimite]);
    $servicios = $ss->fetchAll();

    if (!$servicios && !$esPrueba) {
        return ['ok' => true, 'skipped' => true, 'message' => 'Sin servicios por vencer en el período'];
    }

    // Si es prueba y no hay servicios próximos, usar TODOS los activos del cliente
    if ($esPrueba && !$servicios) {
        $ss2 = $pdo->prepare("
            SELECT cs.*, s.nombre AS servicio_nombre
            FROM   cliente_servicios cs
            JOIN   servicios s ON s.id = cs.servicio_id
            WHERE  cs.cliente_id = ? AND cs.estado = 'activo'
            ORDER  BY cs.fecha_vencimiento ASC LIMIT 5
        ");
        $ss2->execute([$clienteId]);
        $servicios = $ss2->fetchAll();
    }

    // ── Plantilla de correo ───────────────────────────────────────────────────
    $nombreCliente = htmlspecialchars($cliente['nombre_comercial']);
    $mensajeExtra  = $cfg['mensaje_personalizado']
        ? '<p style="color:#475569;font-size:14px;margin:0 0 20px">' . nl2br(htmlspecialchars($cfg['mensaje_personalizado'])) . '</p>'
        : '';

    $filasServicios = '';
    foreach ($servicios as $sv) {
        $vence     = $sv['fecha_vencimiento'] ? date('d M Y', strtotime($sv['fecha_vencimiento'])) : '—';
        $diasRest  = $sv['fecha_vencimiento']
            ? (int)ceil((strtotime($sv['fecha_vencimiento']) - time()) / 86400)
            : null;
        $badgeColor = $diasRest !== null && $diasRest <= 7 ? '#ef4444' : '#f59e0b';
        $badgeTxt   = $diasRest !== null ? "en {$diasRest} día" . ($diasRest !== 1 ? 's' : '') : '';
        $monto = '$&nbsp;' . number_format($sv['monto'] ?? 0, 0, ',', '.') . '&nbsp;COP';

        $filasServicios .= "
        <tr>
            <td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#1e293b;font-weight:600'>"
                . htmlspecialchars($sv['servicio_nombre']) . "</td>
            <td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569'>{$vence}</td>
            <td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;text-align:center'>
                <span style='background:{$badgeColor};color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap'>{$badgeTxt}</span>
            </td>
            <td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:800;color:#0f172a;text-align:right'>{$monto}</td>
        </tr>";
    }

    // ── Logo — Base64 embebido (visible en TODOS los clientes de correo) ────────
    $logoSrc     = getLogoEmailSrc($pdo);
    $whatsappOrg = '';
    try {
        $sc_ws = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = 'notif_whatsapp'");
        $sc_ws->execute();
        $whatsappOrg = $sc_ws->fetchColumn() ?: '';
    } catch (PDOException $e) {}

    $logoTag = $logoSrc
        ? "<img src='{$logoSrc}' alt='QUANTUN Digital' style='height:32px;max-width:160px;object-fit:contain;display:block'>"
        : "<span style='font-size:16px;font-weight:900;color:#c9f31d'>QUANTUN Digital</span>";

    // Botón WhatsApp en header
    $whatsappBtn = '';
    if ($whatsappOrg) {
        $numeroLimpio = preg_replace('/\D/', '', $whatsappOrg);
        $whatsappBtn = "<a href=\"https://wa.me/{$numeroLimpio}\" style=\"display:inline-flex;align-items:center;gap:6px;background:#25D366;color:#ffffff;text-decoration:none;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700\">📱 " . htmlspecialchars($whatsappOrg) . "</a>";
    }

    $asunto = $cfg['asunto_personalizado']
        ?: "⏰ Recordatorio: Servicios próximos a vencer — {$nombreCliente}";

    $html = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f8fafc;font-family:Inter,Arial,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f8fafc;padding:32px 16px'>
<tr><td align='center'>
<table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%'>

  <!-- Header -->
  <tr><td style='background:#0f172a;border-radius:16px 16px 0 0;padding:24px 32px;text-align:left'>
    <table width='100%'><tr>
      <td style='vertical-align:middle'>{$logoTag}</td>
      <td align='right' style='vertical-align:middle;text-align:right'>
        <div style='font-size:11px;color:#c9f31d;font-weight:700;letter-spacing:1px;margin-bottom:8px'>AVISO DE RENOVACIÓN</div>
        {$whatsappBtn}
      </td>
    </tr></table>
  </td></tr>

  <!-- Hero -->
  <tr><td style='background:#ffffff;padding:32px 32px 8px'>
    <div style='display:inline-block;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:6px 14px;margin-bottom:16px'>
      <span style='font-size:12px;font-weight:700;color:#c2410c'>⏰ {$diasAntes} días antes del vencimiento</span>
    </div>
    <h1 style='font-size:22px;font-weight:800;color:#0f172a;margin:0 0 8px'>Hola, {$nombreCliente}</h1>
    <p style='font-size:14px;color:#64748b;line-height:1.6;margin:0 0 16px'>
      Te recordamos que los siguientes servicios están próximos a vencer.
      Para mantener la continuidad sin interrupciones, te recomendamos gestionar la renovación con anticipación.
    </p>
    {$mensajeExtra}
  </td></tr>

  <!-- Tabla de servicios -->
  <tr><td style='background:#ffffff;padding:0 32px 24px'>
    <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>
      <thead>
        <tr style='background:#f8fafc'>
          <th style='padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px'>Servicio</th>
          <th style='padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px'>Vence</th>
          <th style='padding:10px 16px;text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px'>Estado</th>
          <th style='padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px'>Valor</th>
        </tr>
      </thead>
      <tbody>{$filasServicios}</tbody>
    </table>
  </td></tr>

  <!-- CTA -->
  <tr><td style='background:#ffffff;padding:0 32px 32px;text-align:center'>
    <a href='mailto:" . env('MAIL_FROM_ADDRESS','contacto@quantundigital.com') . "?subject=Renovación%20de%20servicios%20-%20{$nombreCliente}'
       style='display:inline-block;background:#c9f31d;color:#0f172a;font-size:14px;font-weight:800;padding:14px 32px;border-radius:10px;text-decoration:none;letter-spacing:.3px'>
      Solicitar renovación →
    </a>
    <p style='font-size:12px;color:#94a3b8;margin:16px 0 0'>
      Si ya gestionaste la renovación, por favor ignora este mensaje.
    </p>
  </td></tr>

  <!-- Footer -->
  <tr><td style='background:#0f172a;border-radius:0 0 16px 16px;padding:20px 32px;text-align:center'>
    <p style='font-size:11px;color:#64748b;margin:0'>
      © " . date('Y') . " <span style='color:#c9f31d;font-weight:700'>QUANTUN Digital</span> · Gestión automatizada de servicios" . ($whatsappOrg ? "<br><span style='font-size:10px;color:#94a3b8;margin-top:4px;display:inline-block'>📱 " . htmlspecialchars($whatsappOrg) . "</span>" : "") . "
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body></html>";

    // Verificar SMTP
    $smtpUser = env('MAIL_USERNAME', '');
    $smtpPass = env('MAIL_PASSWORD', '');
    if (!$smtpUser || !$smtpPass || $smtpUser === 'tu_correo@gmail.com') {
        return ['ok' => false, 'error' => 'SMTP no configurado'];
    }

    $mailer = new Mailer();
    $result = $mailer->send($emailDest, $asunto, $html);
    return $result;
}

// ── Modo web (test desde el modal) ────────────────────────────────────────────
if (!$isCli) {
    ob_start(); // capturar cualquier output inesperado
    try {
        $clienteId = intval($_GET['cliente_id'] ?? 0);
        if (!$clienteId) {
            ob_end_clean();
            jsonResponse(['error' => 'cliente_id requerido'], 400);
        }

        $resultado = enviarNotifCliente($pdo, $clienteId, true);
        ob_end_clean();

        if ($resultado['ok'] ?? false) {
            jsonResponse(['success' => true, 'message' => 'Correo de prueba enviado correctamente']);
        } else {
            jsonResponse(['error' => $resultado['error'] ?? 'Error al enviar']);
        }
    } catch (\Throwable $e) {
        ob_end_clean();
        jsonResponse(['error' => 'Error interno: ' . $e->getMessage()]);
    }
    exit;
}

// ── Modo CLI (cron diario) ─────────────────────────────────────────────────────
$horaActual = date('H:i');
$hoy        = (int) date('w'); // 0=Dom … 6=Sáb

$stmt = $pdo->query("
    SELECT c.id, c.nombre_comercial, c.email_facturacion,
           n.activa, n.dias_antes, n.hora_envio
    FROM   clientes c
    JOIN   crm_cliente_notif_config n ON n.cliente_id = c.id
    WHERE  c.estado = 'activo'
      AND  n.activa = 1
");

$enviados = 0; $errores = 0;
while ($row = $stmt->fetch()) {
    $horaConfig = substr($row['hora_envio'], 0, 5);
    if ($horaConfig !== $horaActual) continue;

    $res = enviarNotifCliente($pdo, (int)$row['id'], false);
    if ($res['ok'] ?? false) {
        echo "[OK]  {$row['nombre_comercial']}\n";
        $enviados++;
    } elseif (!($res['skipped'] ?? false)) {
        echo "[ERR] {$row['nombre_comercial']}: " . ($res['error'] ?? '?') . "\n";
        $errores++;
    }
}
echo "Fin: {$enviados} enviados, {$errores} errores.\n";
