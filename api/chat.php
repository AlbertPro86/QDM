<?php
/**
 * CRM QUANTUN Digital — API de chat para agentes (requiere auth)
 *
 * GET                → listar sesiones activas/cerradas
 * GET  session_id=X  → mensajes de una sesión (incluye notas)
 * POST action=lead   → convertir sesión a lead
 * POST               → enviar mensaje o nota (type=note)
 * PUT                → cambiar estado de sesión
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

header('Content-Type: application/json; charset=utf-8');

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

/* ── Migración: añadir columna type si no existe ── */
try {
    $pdo->exec("ALTER TABLE crm_chat_messages ADD COLUMN type ENUM('message','note') NOT NULL DEFAULT 'message' AFTER message");
} catch (Exception $e) { /* columna ya existe */ }

/* ══════════════ GET ══════════════ */
if ($method === 'GET') {
    $sid = $_GET['session_id'] ?? null;

    if ($sid) {
        /* Mensajes de una sesión (agente ve TODO, incluyendo notas) */
        $st = $pdo->prepare("SELECT id, sender, message, type, seen, created_at
                              FROM crm_chat_messages
                              WHERE session_id = ?
                              ORDER BY id ASC");
        $st->execute([(int)$sid]);
        $msgs = $st->fetchAll(PDO::FETCH_ASSOC);

        /* Marcar mensajes del visitante como vistos */
        $pdo->prepare("UPDATE crm_chat_messages SET seen = 1
                       WHERE session_id = ? AND sender = 'visitor' AND seen = 0")
            ->execute([(int)$sid]);

        $ss = $pdo->prepare("SELECT id, visitor_name, visitor_email, visitor_ip, status, created_at
                              FROM crm_chat_sessions WHERE id = ?");
        $ss->execute([(int)$sid]);
        jsonResponse(['success' => true, 'session' => $ss->fetch(PDO::FETCH_ASSOC), 'messages' => $msgs]);
    }

    /* Listar sesiones */
    $status = in_array($_GET['status'] ?? '', ['activo','cerrado']) ? $_GET['status'] : 'activo';
    $st = $pdo->prepare("
        SELECT s.*,
            (SELECT COUNT(*) FROM crm_chat_messages m
             WHERE m.session_id = s.id AND m.sender = 'visitor' AND m.seen = 0) AS unread,
            (SELECT m2.message FROM crm_chat_messages m2
             WHERE m2.session_id = s.id AND m2.type = 'message'
             ORDER BY m2.id DESC LIMIT 1) AS last_message
        FROM crm_chat_sessions s
        WHERE s.status = ?
        ORDER BY s.last_message_at DESC
    ");
    $st->execute([$status]);
    $sessions = $st->fetchAll(PDO::FETCH_ASSOC);

    $unread = (int)$pdo->query("
        SELECT COUNT(*) FROM crm_chat_messages m
        JOIN crm_chat_sessions s ON s.id = m.session_id
        WHERE m.sender = 'visitor' AND m.seen = 0 AND s.status = 'activo'
    ")->fetchColumn();

    jsonResponse(['success' => true, 'sessions' => $sessions, 'total_unread' => $unread]);
}

/* ══════════════ POST ══════════════ */
if ($method === 'POST') {
    $in     = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $in['action'] ?? '';

    /* ── Crear lead desde sesión de chat ── */
    if ($action === 'lead') {
        $sid      = (int)($in['session_id'] ?? 0);
        $nombre   = trim($in['nombre']   ?? '');
        $whatsapp = trim($in['whatsapp'] ?? '');
        $servicio = trim($in['servicio_interes'] ?? '');

        if (!$nombre || !$whatsapp || !$servicio) {
            jsonResponse(['error' => 'Nombre, WhatsApp y servicio son requeridos'], 422);
        }

        $chk = $pdo->prepare("SELECT id FROM crm_chat_sessions WHERE id = ?");
        $chk->execute([$sid]);
        if (!$chk->fetch()) jsonResponse(['error' => 'Sesión no encontrada'], 404);

        $userId = $_SESSION['user_id'] ?? null;

        $pdo->prepare("INSERT INTO leads (nombre, whatsapp, email, servicio_interes, presupuesto, fuente, estado, notas, asignado_a)
                       VALUES (?, ?, ?, ?, ?, 'chat', 'nuevo', ?, ?)")
            ->execute([
                $nombre,
                $whatsapp,
                trim($in['email'] ?? '') ?: null,
                $servicio,
                (float)($in['presupuesto'] ?? 0) ?: null,
                trim($in['notas'] ?? '') ?: null,
                $userId,
            ]);
        $leadId = (int)$pdo->lastInsertId();

        /* Nota interna automática */
        $nota = "✅ Lead creado: {$nombre} | {$servicio}" . ($whatsapp ? " | WA: {$whatsapp}" : '');
        $pdo->prepare("INSERT INTO crm_chat_messages (session_id, sender, message, type) VALUES (?, 'agent', ?, 'note')")
            ->execute([$sid, $nota]);

        jsonResponse(['success' => true, 'lead_id' => $leadId]);
    }

    /* ── Enviar mensaje o nota ── */
    $sid  = (int)($in['session_id'] ?? 0);
    $msg  = trim($in['message'] ?? '');
    $type = ($in['type'] ?? 'message') === 'note' ? 'note' : 'message';

    if (!$sid || !$msg) jsonResponse(['error' => 'session_id y message requeridos'], 422);

    $chk = $pdo->prepare("SELECT id FROM crm_chat_sessions WHERE id = ?");
    $chk->execute([$sid]);
    if (!$chk->fetch()) jsonResponse(['error' => 'Sesión no encontrada'], 404);

    $pdo->prepare("INSERT INTO crm_chat_messages (session_id, sender, message, type) VALUES (?, 'agent', ?, ?)")
        ->execute([$sid, $msg, $type]);
    $msgId = (int)$pdo->lastInsertId();

    /* Solo actualizar last_message_at para mensajes reales */
    if ($type === 'message') {
        $pdo->prepare("UPDATE crm_chat_sessions SET last_message_at = NOW() WHERE id = ?")
            ->execute([$sid]);
    }

    jsonResponse(['success' => true, 'message_id' => $msgId]);
}

/* ══════════════ PUT — cambiar estado ══════════════ */
if ($method === 'PUT') {
    $in     = json_decode(file_get_contents('php://input'), true) ?? [];
    $sid    = (int)($in['session_id'] ?? 0);
    $status = $in['status'] ?? '';

    if (!$sid || !in_array($status, ['activo', 'cerrado'])) {
        jsonResponse(['error' => 'Datos inválidos'], 422);
    }

    $pdo->prepare("UPDATE crm_chat_sessions SET status = ? WHERE id = ?")->execute([$status, $sid]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Método no permitido'], 405);
