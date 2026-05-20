<?php
/**
 * CRM QUANTUN Digital — API Editor de Detalles del Cliente
 * GET  ?cliente_id=X  → obtiene el contenido del editor
 * POST {cliente_id, contenido} → guarda/actualiza (upsert)
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
        CREATE TABLE IF NOT EXISTS crm_cliente_editor (
            cliente_id  INT          NOT NULL PRIMARY KEY,
            contenido   LONGTEXT     NOT NULL DEFAULT '',
            updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $cid = intval($_GET['cliente_id'] ?? 0);
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);
        $s = $pdo->prepare("SELECT contenido, updated_at FROM crm_cliente_editor WHERE cliente_id = ?");
        $s->execute([$cid]);
        $row = $s->fetch();
        jsonResponse(['success' => true, 'contenido' => $row ? $row['contenido'] : '', 'updated_at' => $row['updated_at'] ?? null]);
        break;

    case 'POST':
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $cid      = intval($in['cliente_id'] ?? 0);
        $contenido = $in['contenido'] ?? '';
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);

        $s = $pdo->prepare("
            INSERT INTO crm_cliente_editor (cliente_id, contenido)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE contenido = VALUES(contenido), updated_at = NOW()
        ");
        $s->execute([$cid, $contenido]);
        jsonResponse(['success' => true, 'message' => 'Contenido guardado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
