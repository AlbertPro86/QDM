<?php
/**
 * CRM QUANTUN Digital — Envío de correo a cliente (sin cotización)
 * POST { cliente_id, asunto, mensaje_texto, imagen_path? }
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/crm_config.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

$pdo   = db();
$input = json_decode(file_get_contents('php://input'), true);

$clienteId  = intval($input['cliente_id']   ?? 0);
$asunto     = trim($input['asunto']          ?? '');
$mensaje    = trim($input['mensaje_texto']   ?? '');
$imagenPath = trim($input['imagen_path']     ?? '');
$logoUrl    = trim($input['logo_url']        ?? '');

if (!$clienteId) jsonResponse(['error' => 'ID de cliente requerido'], 400);
if (!$asunto)    jsonResponse(['error' => 'Asunto requerido'], 400);

// Obtener cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$clienteId]);
$cliente = $stmt->fetch();
if (!$cliente) jsonResponse(['error' => 'Cliente no encontrado'], 404);

$emailDest = trim($cliente['email_facturacion'] ?? '');
if (!$emailDest || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'El cliente no tiene un correo válido registrado'], 422);
}

// Verificar config SMTP
$smtpUser = env('MAIL_USERNAME', '');
$smtpPass = env('MAIL_PASSWORD', '');
if (!$smtpUser || $smtpUser === 'tu_correo@gmail.com' || !$smtpPass || $smtpPass === 'tu_app_password') {
    jsonResponse(['error' => 'El servidor de correo no está configurado.'], 503);
}

// Generar HTML del correo
$nombreCliente = htmlspecialchars($cliente['nombre_comercial'] ?? '', ENT_QUOTES);

// ── Logo — Base64 embebido (visible en TODOS los clientes de correo) ──────────
$logoSrc  = getLogoEmailSrc($pdo, $logoUrl);
$logoHtml = $logoSrc
    ? '<img src="' . $logoSrc . '" alt="QUANTUN Digital" height="38" style="display:block;height:38px;max-width:200px;object-fit:contain">'
    : '<span style="font-size:16px;font-weight:900;color:#0E0E0C">QUANTUN Digital</span>';

// Imagen adjunta (si existe)
$imagenHtml = '';
if ($imagenPath) {
    $absUrl = rtrim(APP_URL, '/') . '/' . ltrim($imagenPath, '/');
    $imagenHtml = '<tr><td style="padding:0 32px 20px 32px">
        <img src="' . $absUrl . '" style="max-width:100%;border-radius:8px;display:block" alt="imagen">
    </td></tr>';
}

// Párrafos del mensaje
$parrafos = array_map(
    fn($l) => '<p style="margin:0 0 10px 0;color:#475569;font-size:14px;line-height:1.6">' . htmlspecialchars($l, ENT_QUOTES) . '</p>',
    explode("\n", trim($mensaje))
);
$mensajeHtml = implode('', $parrafos);

$htmlFinal = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . $asunto . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

    <!-- HEADER -->
    <tr>
        <td style="background:#ffffff;padding:18px 32px;border-radius:12px 12px 0 0;border-bottom:3px solid #C6F24E">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:middle">
                        ' . $logoHtml . '
                    </td>
                    <td style="text-align:right;vertical-align:middle">
                        <a href="https://wa.me/' . crmWaNum('empresa_tel','573332747801') . '" style="display:inline-flex;align-items:center;gap:6px;background:#25D366;color:#ffffff;text-decoration:none;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700">
                            ' . htmlspecialchars(crmConfig('empresa_tel','333 274 7801')) . '
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- CUERPO -->
    <tr>
        <td style="background:#ffffff">
            <table width="100%" cellpadding="0" cellspacing="0">

                <!-- Saludo -->
                <tr><td style="padding:28px 32px 8px 32px">
                    <p style="margin:0 0 14px 0;font-size:16px;font-weight:700;color:#0f172a">Hola, ' . $nombreCliente . '</p>
                    ' . $mensajeHtml . '
                </td></tr>

                <!-- Imagen si hay -->
                ' . $imagenHtml . '

            </table>
        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background:#f8fafc;padding:20px 32px;border-radius:0 0 12px 12px;border-top:1px solid #e2e8f0;text-align:center">
            <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6">
                Para más información escríbenos por WhatsApp al
                <a href="https://wa.me/' . crmWaNum('empresa_tel','573332747801') . '" style="color:#25D366;font-weight:700;text-decoration:none">' . htmlspecialchars(crmConfig('empresa_tel','333 274 7801')) . '</a>
            </p>
        </td>
    </tr>

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

// Enviar
$mailer = new Mailer();
$result = $mailer->send(
    $cliente['nombre_comercial'] . ' <' . $emailDest . '>',
    $asunto,
    $htmlFinal
);

if ($result['ok']) {
    jsonResponse(['success' => true, 'message' => "Correo enviado a $emailDest"]);
} else {
    jsonResponse(['error' => 'Error al enviar: ' . $result['error']], 500);
}
