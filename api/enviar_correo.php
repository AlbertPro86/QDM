<?php
/**
 * CRM QUANTUN Digital — API Envío de Correo
 * POST { cotizacion_id, asunto, cuerpo_html, adjuntar_imagen? }
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/cotizacion_email_html.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

$pdo   = db();
$input = json_decode(file_get_contents('php://input'), true);

$cotId      = intval($input['cotizacion_id'] ?? 0);
$asunto     = trim($input['asunto']          ?? '');
$mensaje    = trim($input['mensaje_texto']   ?? '');
$imagenPath = trim($input['imagen_path']     ?? '');
$logoUrl    = trim($input['logo_url']        ?? '');
$plantillaId = intval($input['plantilla_id'] ?? 0);

if (!$cotId)  jsonResponse(['error' => 'ID de cotización requerido'], 400);
if (!$asunto) jsonResponse(['error' => 'Asunto requerido'], 400);

// Obtener cotización
$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
$stmt->execute([$cotId]);
$cot = $stmt->fetch();
if (!$cot) jsonResponse(['error' => 'Cotización no encontrada'], 404);

$emailDest = trim($cot['email'] ?? '');
if (!$emailDest || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'La cotización no tiene un email válido registrado'], 422);
}

// Verificar config SMTP
$smtpUser = env('MAIL_USERNAME', '');
$smtpPass = env('MAIL_PASSWORD', '');
if (!$smtpUser || $smtpUser === 'tu_correo@gmail.com' || !$smtpPass || $smtpPass === 'tu_app_password') {
    jsonResponse(['error' => 'El servidor de correo no está configurado.'], 503);
}

// Resolver plantilla de diseño (plantillas_factura)
$plantilla = null;
if ($plantillaId) {
    try {
        $ps = $pdo->prepare("SELECT * FROM plantillas_factura WHERE id = ? AND activo = 1");
        $ps->execute([$plantillaId]);
        $plantilla = $ps->fetch() ?: null;
    } catch (Exception $e) { /* no fatal */ }
}

// Fallback: plantilla default si no se especificó una
if (!$plantilla) {
    try {
        $plantilla = $pdo->query("SELECT * FROM plantillas_factura WHERE es_default = 1 AND activo = 1 LIMIT 1")->fetch() ?: null;
    } catch (Exception $e) { /* no fatal */ }
}

// Si no hay logo_url explícito y la plantilla lo tiene, usarlo
if (!$logoUrl && $plantilla && $plantilla['logo_url']) {
    $logoUrl = $plantilla['logo_url'];
}

// Si aún no hay logo_url, buscar en configuraciones
if (!$logoUrl) {
    $logoUrl = getCfg($pdo, 'notif_logo_url', '');
}

// Generar HTML completo con el nuevo diseño usando datos de la plantilla
$htmlFinal = generarEmailCotizacion($cot, $mensaje, $imagenPath ?: null, $logoUrl ?: null, null, $plantilla ?: null);

// Enviar (sin adjuntos — imagen va embebida en el HTML)
$mailer = new Mailer();
$result = $mailer->send(
    $cot['nombre_cliente'] . ' <' . $emailDest . '>',
    $asunto,
    $htmlFinal
);

if ($result['ok']) {
    $pdo->prepare("UPDATE cotizaciones SET estado='enviada', updated_at=NOW() WHERE id=?")
        ->execute([$cotId]);
    jsonResponse(['success' => true, 'message' => "Correo enviado a $emailDest"]);
} else {
    jsonResponse(['error' => 'Error al enviar: ' . $result['error']], 500);
}
