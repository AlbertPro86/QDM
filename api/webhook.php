<?php
/**
 * CRM QUANTUN Digital - Webhook para WordPress (Contact Form 7)
 * Endpoint público que recibe leads desde WordPress.
 * URL: /api/webhook.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

// Verificar token de autorización
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$expectedToken = env('WEBHOOK_TOKEN', '');

if (empty($expectedToken) || !hash_equals($expectedToken, $token)) {
    jsonResponse(['error' => 'Token de autorización inválido'], 403);
}

// Parsear body
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input)) {
    jsonResponse(['error' => 'Body vacío o JSON inválido'], 400);
}

// Validar campos mínimos
$nombre = $input['nombre'] ?? $input['your-name'] ?? 'Lead Sin Nombre';
$whatsapp = $input['whatsapp'] ?? $input['phone'] ?? '';
$servicio = $input['servicio_interes'] ?? $input['servicio'] ?? 'No especificado';
$presupuesto = $input['presupuesto'] ?? 0;
$url = $input['url_actual'] ?? $input['url'] ?? null;
$email = $input['email'] ?? null;

if (empty($whatsapp)) {
    jsonResponse(['error' => 'El campo WhatsApp es requerido'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO leads (nombre, whatsapp, email, servicio_interes, presupuesto, url_actual, fuente, estado) 
        VALUES (?, ?, ?, ?, ?, ?, 'wordpress', 'nuevo')
    ");

    $stmt->execute([
        $nombre,
        $whatsapp,
        $email,
        $servicio,
        $presupuesto,
        $url
    ]);

    $leadId = $pdo->lastInsertId();

    // Log de actividad (sistema)
    $pdo->prepare("INSERT INTO actividad_log (accion, entidad, entidad_id, detalles, ip_address) VALUES ('webhook_lead', 'leads', ?, ?, ?)")
        ->execute([$leadId, "Lead desde WordPress: {$nombre}", $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);

    // Intentar enviar notificación WhatsApp
    try {
        require_once __DIR__ . '/../services/EvolutionApiService.php';
        $evolution = new EvolutionApiService();
        
        $adminNumber = '573145979983'; // Número del admin para notificaciones
        $mensaje = "🚀 *Nuevo Lead en CRM QUANTUN*\n\n"
                 . "👤 *Nombre:* {$nombre}\n"
                 . "📱 *WhatsApp:* {$whatsapp}\n"
                 . "🎯 *Servicio:* {$servicio}\n"
                 . "💰 *Presupuesto:* $" . number_format($presupuesto, 2) . "\n"
                 . "🌐 *Fuente:* WordPress\n\n"
                 . "📋 Revisa tu panel: " . APP_URL . "/leads.php";
        
        $evolution->sendTextMessage($adminNumber, $mensaje);
    } catch (Exception $e) {
        // No fallar si WhatsApp no funciona
        error_log("Webhook WhatsApp error: " . $e->getMessage());
    }

    jsonResponse([
        'success' => true,
        'message' => 'Lead recibido correctamente',
        'lead_id' => (int)$leadId
    ], 201);

} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}
