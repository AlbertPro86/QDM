<?php
/**
 * CRM QUANTUN Digital — API Configuración de Notificaciones por Cliente
 * GET  ?cliente_id=X  → obtiene configuración
 * POST {cliente_id, activa, dias_antes, hora_envio, asunto_personalizado, mensaje_personalizado}
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();

// Auto-migración
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

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $cid = intval($_GET['cliente_id'] ?? 0);
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);

        $s = $pdo->prepare("SELECT * FROM crm_cliente_notif_config WHERE cliente_id = ?");
        $s->execute([$cid]);
        $row = $s->fetch();

        // Defaults si no existe
        if (!$row) {
            $row = [
                'cliente_id'             => $cid,
                'activa'                 => 1,
                'dias_antes'             => 15,
                'hora_envio'             => '08:00:00',
                'asunto_personalizado'   => '',
                'mensaje_personalizado'  => '',
            ];
        }
        jsonResponse(['success' => true, 'data' => $row]);
        break;

    case 'POST':
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $cid = intval($in['cliente_id'] ?? 0);
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);

        $activa   = isset($in['activa']) ? (int)(bool)$in['activa'] : 1;
        $dias     = max(1, intval($in['dias_antes'] ?? 15));
        $hora     = $in['hora_envio'] ?? '08:00';
        $asunto   = trim($in['asunto_personalizado']   ?? '');
        $mensaje  = trim($in['mensaje_personalizado']  ?? '');

        $s = $pdo->prepare("
            INSERT INTO crm_cliente_notif_config
                (cliente_id, activa, dias_antes, hora_envio, asunto_personalizado, mensaje_personalizado)
            VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                activa                 = VALUES(activa),
                dias_antes             = VALUES(dias_antes),
                hora_envio             = VALUES(hora_envio),
                asunto_personalizado   = VALUES(asunto_personalizado),
                mensaje_personalizado  = VALUES(mensaje_personalizado),
                updated_at             = NOW()
        ");
        $s->execute([$cid, $activa, $dias, $hora, $asunto, $mensaje]);
        jsonResponse(['success' => true, 'message' => 'Configuración guardada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
