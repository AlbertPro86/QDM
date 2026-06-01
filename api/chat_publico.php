<?php
/**
 * CRM QUANTUN Digital — API pública de chat en vivo (sin auth)
 *
 * POST action=init → crear sesión
 * POST action=send → enviar mensaje visitante
 * GET  action=poll → obtener mensajes nuevos
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

/* ── Auto-crear tablas ── */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_chat_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        visitor_name VARCHAR(100) NOT NULL,
        visitor_email VARCHAR(150) DEFAULT NULL,
        visitor_ip VARCHAR(45) DEFAULT NULL,
        token VARCHAR(64) NOT NULL,
        status ENUM('activo','cerrado') DEFAULT 'activo',
        last_message_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        sender ENUM('visitor','agent') NOT NULL,
        message TEXT NOT NULL,
        seen TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_id),
        INDEX idx_seen (seen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) { /* tablas ya existen */ }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? $action;
}

/* ── Helper: validar token de sesión ── */
function chatSession($pdo, $sid, $tok) {
    $st = $pdo->prepare("SELECT id, status FROM crm_chat_sessions WHERE id = ? AND token = ?");
    $st->execute([$sid, $tok]);
    return $st->fetch();
}

switch ($action) {

    /* ========== INIT ========== */
    case 'init':
        if ($method !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        $name  = trim($input['name']  ?? '');
        $email = trim($input['email'] ?? '');
        if (!$name || mb_strlen($name) < 2) jsonResponse(['error' => 'Nombre requerido'], 422);

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $st = $pdo->prepare("SELECT COUNT(*) FROM crm_chat_sessions WHERE visitor_ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $st->execute([$ip]);
        if ($st->fetchColumn() >= 5) jsonResponse(['error' => 'Demasiadas sesiones, intenta luego.'], 429);

        $token = bin2hex(random_bytes(32));
        $pdo->prepare("INSERT INTO crm_chat_sessions (visitor_name, visitor_email, visitor_ip, token, last_message_at) VALUES (?,?,?,?,NOW())")
             ->execute([$name, $email ?: null, $ip, $token]);
        $sid = (int)$pdo->lastInsertId();

        $welcome = '¡Hola ' . htmlspecialchars($name) . '! Gracias por escribirnos. ¿En qué podemos ayudarte?';
        $pdo->prepare("INSERT INTO crm_chat_messages (session_id, sender, message) VALUES (?, 'agent', ?)")
             ->execute([$sid, $welcome]);

        jsonResponse(['success' => true, 'session_id' => $sid, 'token' => $token]);
        break;

    /* ========== SEND ========== */
    case 'send':
        if ($method !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        $sid = (int)($input['session_id'] ?? 0);
        $tok = $input['token'] ?? '';
        $msg = trim($input['message'] ?? '');
        if (!$sid || !$tok)                   jsonResponse(['error' => 'Sesión inválida'], 401);
        if (!$msg || mb_strlen($msg) > 2000)  jsonResponse(['error' => 'Mensaje inválido'], 422);

        $s = chatSession($pdo, $sid, $tok);
        if (!$s)                              jsonResponse(['error' => 'Sesión no encontrada'], 401);
        if ($s['status'] === 'cerrado')       jsonResponse(['error' => 'Chat cerrado'], 403);

        $pdo->prepare("INSERT INTO crm_chat_messages (session_id, sender, message) VALUES (?, 'visitor', ?)")->execute([$sid, $msg]);
        $pdo->prepare("UPDATE crm_chat_sessions SET last_message_at = NOW() WHERE id = ?")->execute([$sid]);

        jsonResponse(['success' => true, 'message_id' => (int)$pdo->lastInsertId()]);
        break;

    /* ========== POLL ========== */
    case 'poll':
        if ($method !== 'GET') jsonResponse(['error' => 'Método no permitido'], 405);

        $sid   = (int)($_GET['session_id'] ?? 0);
        $tok   = $_GET['token'] ?? '';
        $after = (int)($_GET['after'] ?? 0);
        if (!$sid || !$tok) jsonResponse(['error' => 'Sesión inválida'], 401);

        $s = chatSession($pdo, $sid, $tok);
        if (!$s) jsonResponse(['error' => 'Sesión no encontrada'], 401);

        /* Las notas internas (type='note') no se envían al visitante */
        $st = $pdo->prepare("SELECT id, sender, message, created_at FROM crm_chat_messages
                              WHERE session_id = ? AND id > ?
                              AND (type IS NULL OR type = 'message')
                              ORDER BY id ASC");
        $st->execute([$sid, $after]);
        $msgs = $st->fetchAll(PDO::FETCH_ASSOC);

        if ($msgs) {
            $pdo->prepare("UPDATE crm_chat_messages SET seen = 1 WHERE session_id = ? AND sender = 'agent' AND seen = 0")->execute([$sid]);
        }

        jsonResponse(['success' => true, 'messages' => $msgs, 'closed' => $s['status'] === 'cerrado']);
        break;

    default:
        jsonResponse(['error' => 'Acción no válida'], 400);
}
