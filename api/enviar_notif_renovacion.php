<?php
/**
 * CRM QUANTUN Digital — Notificación de renovaciones (3 recordatorios)
 *
 * Modos:
 *   GET  ?cliente_id=X&test=1   → envía prueba inmediata (requiere sesión)
 *   CLI  php enviar_notif_renovacion.php  → revisa TODOS los clientes activos
 *        y envía los recordatorios programados (R1, R2, R3)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/crm_config.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);
}

$pdo = db();

// ── Función: construir y enviar correo de recordatorio ────────────────────────
function enviarNotifCliente(PDO $pdo, int $clienteId, int $numRecordatorio, bool $esPrueba = false): array {

    $sc = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $sc->execute([$clienteId]);
    $cliente = $sc->fetch();
    if (!$cliente) return ['ok' => false, 'error' => 'Cliente no encontrado'];

    $emailDest = trim($cliente['email_facturacion'] ?? '');
    if (!$emailDest || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'El cliente no tiene email de facturación válido'];
    }

    $sc2 = $pdo->prepare("SELECT * FROM crm_cliente_notif_config WHERE cliente_id = ?");
    $sc2->execute([$clienteId]);
    $cfg = $sc2->fetch() ?: [];

    $diasField = "r{$numRecordatorio}_dias";
    $diasAntes = intval($cfg[$diasField] ?? ($numRecordatorio === 1 ? 15 : ($numRecordatorio === 2 ? 7 : 2)));

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

    $nombreCliente = htmlspecialchars($cliente['nombre_comercial']);
    // Plantilla per-recordatorio: buscar contenido si hay plantilla_id configurada
    $plantillaField  = "r{$numRecordatorio}_plantilla_id";
    $plantillaId     = intval($cfg[$plantillaField] ?? 0);
    $mensajePlantilla = '';
    if ($plantillaId) {
        try {
            $sp = $pdo->prepare("SELECT contenido FROM mensajes_plantillas WHERE id = ? AND activa = 1");
            $sp->execute([$plantillaId]);
            $mensajePlantilla = $sp->fetchColumn() ?: '';
        } catch (PDOException $e) {}
    }
    $mensajeBase   = $mensajePlantilla ?: ($cfg['mensaje_personalizado'] ?? '');
    $mensajeExtra  = $mensajeBase
        ? '<p style="color:#475569;font-size:14px;margin:0 0 20px">' . nl2br(htmlspecialchars($mensajeBase)) . '</p>'
        : '';

    $filasServicios = '';
    foreach ($servicios as $sv) {
        $vence    = $sv['fecha_vencimiento'] ? date('d M Y', strtotime($sv['fecha_vencimiento'])) : '—';
        $diasRest = $sv['fecha_vencimiento']
            ? (int)ceil((strtotime($sv['fecha_vencimiento']) - time()) / 86400)
            : null;
        $badgeColor = $diasRest !== null && $diasRest <= 7 ? '#ef4444' : '#f59e0b';
        $badgeTxt   = $diasRest !== null ? "en {$diasRest} día" . ($diasRest !== 1 ? 's' : '') : '';
        $monto      = '$&nbsp;' . number_format($sv['monto_renovacion'] ?? 0, 0, ',', '.') . '&nbsp;COP';

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

    $logoSrc = getLogoEmailSrc($pdo);
    $whatsappOrg = '';
    try {
        $sc_ws = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = 'notif_whatsapp'");
        $sc_ws->execute();
        $whatsappOrg = $sc_ws->fetchColumn() ?: '';
    } catch (PDOException $e) {}

    $logoTag = $logoSrc
        ? "<img src='{$logoSrc}' alt='QUANTUN Digital' style='height:32px;max-width:160px;object-fit:contain;display:block'>"
        : "<span style='font-size:16px;font-weight:900;color:#c9f31d'>QUANTUN Digital</span>";

    $whatsappBtn = '';
    if ($whatsappOrg) {
        $numeroLimpio = preg_replace('/\D/', '', $whatsappOrg);
        $whatsappBtn = "<a href=\"https://wa.me/{$numeroLimpio}\" style=\"display:inline-flex;align-items:center;gap:6px;background:#25D366;color:#ffffff;text-decoration:none;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700\">📱 " . htmlspecialchars($whatsappOrg) . "</a>";
    }

    $badgeRecordatorio = ['1er recordatorio', '2do recordatorio', '3er recordatorio'][$numRecordatorio - 1] ?? "Recordatorio {$numRecordatorio}";
    $asunto = !empty($cfg['asunto_personalizado'])
        ? $cfg['asunto_personalizado']
        : "⏰ {$badgeRecordatorio}: Servicios próximos a vencer — {$nombreCliente}";

    $html = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f8fafc;font-family:Inter,Arial,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f8fafc;padding:32px 16px'>
<tr><td align='center'>
<table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%'>
  <tr><td style='background:#0f172a;border-radius:16px 16px 0 0;padding:24px 32px;text-align:left'>
    <table width='100%'><tr>
      <td style='vertical-align:middle'>{$logoTag}</td>
      <td align='right' style='vertical-align:middle;text-align:right'>
        <div style='font-size:11px;color:#c9f31d;font-weight:700;letter-spacing:1px;margin-bottom:8px'>AVISO DE RENOVACIÓN</div>
        {$whatsappBtn}
      </td>
    </tr></table>
  </td></tr>
  <tr><td style='background:#ffffff;padding:32px 32px 8px'>
    <div style='display:inline-block;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:6px 14px;margin-bottom:16px'>
      <span style='font-size:12px;font-weight:700;color:#c2410c'>⏰ {$badgeRecordatorio} · {$diasAntes} días antes del vencimiento</span>
    </div>
    <h1 style='font-size:22px;font-weight:800;color:#0f172a;margin:0 0 8px'>Hola, {$nombreCliente}</h1>
    <p style='font-size:14px;color:#64748b;line-height:1.6;margin:0 0 16px'>
      Te recordamos que los siguientes servicios están próximos a vencer.
      Para mantener la continuidad sin interrupciones, te recomendamos gestionar la renovación con anticipación.
    </p>
    {$mensajeExtra}
  </td></tr>
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
  <tr><td style='background:#ffffff;padding:0 32px 32px;text-align:center'>
    <a href='mailto:" . crmConfig('empresa_email', env('MAIL_FROM_ADDRESS','contacto@quantundigital.com')) . "?subject=Renovación%20de%20servicios%20-%20{$nombreCliente}'
       style='display:inline-block;background:#c9f31d;color:#0f172a;font-size:14px;font-weight:800;padding:14px 32px;border-radius:10px;text-decoration:none;letter-spacing:.3px'>
      Solicitar renovación →
    </a>
    <p style='font-size:12px;color:#94a3b8;margin:16px 0 0'>Si ya gestionaste la renovación, por favor ignora este mensaje.</p>
  </td></tr>
  <tr><td style='background:#0f172a;border-radius:0 0 16px 16px;padding:20px 32px;text-align:center'>
    <p style='font-size:11px;color:#64748b;margin:0'>
      © " . date('Y') . " <span style='color:#c9f31d;font-weight:700'>QUANTUN Digital</span> · Gestión automatizada de servicios"
      . ($whatsappOrg ? "<br><span style='font-size:10px;color:#94a3b8;margin-top:4px;display:inline-block'>📱 " . htmlspecialchars($whatsappOrg) . "</span>" : "") . "
    </p>
  </td></tr>
</table>
</td></tr>
</table>
</body></html>";

    $smtpUser = env('MAIL_USERNAME', '');
    $smtpPass = env('MAIL_PASSWORD', '');
    if (!$smtpUser || !$smtpPass || $smtpUser === 'tu_correo@gmail.com') {
        return ['ok' => false, 'error' => 'SMTP no configurado'];
    }

    $mailer = new Mailer();
    $result = $mailer->send($emailDest, $asunto, $html);
    return $result;
}

// ── Modo web (envío manual desde el modal) ───────────────────────────────────
if (!$isCli) {
    ob_start();
    try {
        $clienteId = intval($_GET['cliente_id'] ?? 0);
        if (!$clienteId) { ob_end_clean(); jsonResponse(['error' => 'cliente_id requerido'], 400); }

        // Determinar qué recordatorio corresponde según el estado actual
        $svcStmt = $pdo->prepare("
            SELECT id, notif_count FROM cliente_servicios
            WHERE cliente_id = ? AND estado = 'activo' AND frecuencia != 'unico'
            ORDER BY fecha_vencimiento ASC LIMIT 1
        ");
        $svcStmt->execute([$clienteId]);
        $svcRow      = $svcStmt->fetch();
        $currentCount = (int)($svcRow['notif_count'] ?? 0);

        if ($currentCount >= 3) {
            ob_end_clean();
            jsonResponse(['error' => 'Ya se enviaron los 3 recordatorios. Para reiniciar el ciclo usa "Reiniciar recordatorios" en el modal.'], 400);
        }

        $numToSend = $currentCount + 1;

        // esPrueba=true garantiza contenido en el email aunque no haya vencimientos próximos
        $resultado = enviarNotifCliente($pdo, $clienteId, $numToSend, true);
        ob_end_clean();

        if ($resultado['ok'] ?? false) {
            // Registrar el envío en BD — actualizar TODOS los servicios activos del cliente
            $dateField = "notif_r{$numToSend}_at";
            $pdo->prepare("
                UPDATE cliente_servicios
                SET    notif_count  = GREATEST(notif_count, ?),
                       {$dateField} = COALESCE({$dateField}, NOW())
                WHERE  cliente_id = ? AND estado = 'activo' AND frecuencia != 'unico'
            ")->execute([$numToSend, $clienteId]);

            // Registrar en historial de notas
            $pdo->prepare("INSERT INTO clientes_notas (cliente_id, usuario_id, nota) VALUES (?, ?, ?)")
                ->execute([$clienteId, $_SESSION['user_id'] ?? 0,
                    "🔔 Recordatorio {$numToSend}/3 enviado manualmente"]);

            $labels = ['1er', '2do', '3er'];
            $label  = $labels[$numToSend - 1] ?? "{$numToSend}°";
            jsonResponse(['success' => true, 'message' => "{$label} recordatorio enviado y registrado"]);
        } else {
            jsonResponse(['error' => $resultado['error'] ?? 'Error al enviar']);
        }
    } catch (\Throwable $e) {
        ob_end_clean();
        jsonResponse(['error' => 'Error interno: ' . $e->getMessage()]);
    }
    exit;
}

// ── Modo CLI (cron diario) ────────────────────────────────────────────────────
$horaActual = date('H:i');
$hoy        = date('Y-m-d');

$stmt = $pdo->query("
    SELECT c.id, c.nombre_comercial,
           n.activa, n.r1_dias, n.r1_hora, n.r2_dias, n.r2_hora, n.r3_dias, n.r3_hora,
           n.r1_fecha, n.r2_fecha, n.r3_fecha
    FROM   clientes c
    JOIN   crm_cliente_notif_config n ON n.cliente_id = c.id
    WHERE  c.estado = 'activo'
      AND  (
          n.activa = 1
          OR n.r1_fecha IS NOT NULL
          OR n.r2_fecha IS NOT NULL
          OR n.r3_fecha IS NOT NULL
      )
");

$enviados = 0; $errores = 0;

while ($row = $stmt->fetch()) {
    $clienteId = (int)$row['id'];

    // Obtener el servicio activo más próximo a vencer del cliente
    $ss = $pdo->prepare("
        SELECT * FROM cliente_servicios
        WHERE cliente_id = ? AND estado = 'activo' AND frecuencia != 'unico'
        ORDER BY fecha_vencimiento ASC LIMIT 1
    ");
    $ss->execute([$clienteId]);
    $svc = $ss->fetch();
    if (!$svc) continue;

    $notifCount = (int)($svc['notif_count'] ?? 0);

    // Revisar R1, R2, R3 en orden — solo si no se ha enviado ese recordatorio aún
    $recordatorios = [
        1 => ['dias' => (int)$row['r1_dias'], 'hora' => substr($row['r1_hora'], 0, 5)],
        2 => ['dias' => (int)$row['r2_dias'], 'hora' => substr($row['r2_hora'], 0, 5)],
        3 => ['dias' => (int)$row['r3_dias'], 'hora' => substr($row['r3_hora'], 0, 5)],
    ];

    foreach ($recordatorios as $num => $r) {
        // Ya se envió este recordatorio o uno posterior
        if ($notifCount >= $num) continue;

        // Verificar si es momento de enviar: usar r{N}_fecha si está configurada
        $fechaClave  = "r{$num}_fecha";
        $fechaProg   = $row[$fechaClave] ?? null;
        if ($fechaProg) {
            // Enviar si la fecha programada ya llegó (dentro del minuto actual)
            $ts = strtotime($fechaProg);
            if ($ts === false || $ts > time() + 60) continue;
        } else {
            // Fallback: lógica de días antes + hora
            if ($r['hora'] !== $horaActual) continue;
            $diasRestantes = (int)ceil((strtotime($svc['fecha_vencimiento']) - time()) / 86400);
            if ($diasRestantes > $r['dias']) continue;
        }

        // Enviar
        $res = enviarNotifCliente($pdo, $clienteId, $num, false);
        if (($res['ok'] ?? false) && !($res['skipped'] ?? false)) {
            // Actualizar notif_count y fecha en TODOS los servicios activos del cliente
            $dateField = "notif_r{$num}_at";
            $pdo->prepare("
                UPDATE cliente_servicios
                SET    notif_count  = GREATEST(notif_count, ?),
                       {$dateField} = COALESCE({$dateField}, NOW())
                WHERE  cliente_id = ? AND estado = 'activo' AND frecuencia != 'unico'
            ")->execute([$num, $clienteId]);
            // Registrar nota
            $pdo->prepare("INSERT INTO clientes_notas (cliente_id, usuario_id, nota) VALUES (?, 0, ?)")
                ->execute([$clienteId, "🔔 Recordatorio {$num}/3 enviado automáticamente ({$svc['fecha_vencimiento']})"]);
            echo "[OK R{$num}] {$row['nombre_comercial']}\n";
            $enviados++;
        } elseif ($res['skipped'] ?? false) {
            echo "[SKIP R{$num}] {$row['nombre_comercial']}: sin servicios en período\n";
        } else {
            echo "[ERR R{$num}] {$row['nombre_comercial']}: " . ($res['error'] ?? '?') . "\n";
            $errores++;
        }
        break; // solo un recordatorio por ejecución por cliente
    }
}

echo "Fin: {$enviados} enviados, {$errores} errores.\n";
