<?php
/**
 * CRM QUANTUN Digital — Email Open Tracking
 * GET ?eid={envio_id}  → registra apertura, devuelve pixel 1×1 transparente
 * No requiere autenticación (es invocado por el cliente de correo del destinatario)
 */
require_once __DIR__ . '/../config/database.php';

// Registrar apertura (silenciosa — nunca devolver error al email client)
try {
    $eid = (int) ($_GET['eid'] ?? 0);
    if ($eid > 0) {
        $pdo = db();
        $pdo->prepare("
            UPDATE email_envios
            SET abierto = 1, abierto_at = NOW()
            WHERE id = ? AND abierto = 0
        ")->execute([$eid]);
    }
} catch (Throwable $e) {
    // silencioso
}

// Devolver imagen GIF 1×1 transparente
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// GIF 1×1 transparente (43 bytes)
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;
