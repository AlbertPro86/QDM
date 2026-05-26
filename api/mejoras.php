<?php
/**
 * CRM QUANTUN Digital - API Mejoras Plataforma
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-migración
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS mejoras_plataforma (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        texto         VARCHAR(600) NOT NULL,
        categoria     VARCHAR(40)  NOT NULL DEFAULT 'otro',
        completada    TINYINT(1)   NOT NULL DEFAULT 0,
        created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completada_at DATETIME     DEFAULT NULL
    )");
} catch (PDOException $e) {}

switch ($method) {
    case 'GET':
        $rows = $pdo->query("SELECT * FROM mejoras_plataforma ORDER BY completada ASC, created_at DESC")->fetchAll();
        jsonResponse(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['texto'])) jsonResponse(['error' => 'Texto requerido'], 400);

        $stmt = $pdo->prepare("INSERT INTO mejoras_plataforma (texto, categoria) VALUES (?, ?)");
        $stmt->execute([
            substr(trim($input['texto']), 0, 600),
            $input['categoria'] ?? 'otro'
        ]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        $completada = isset($input['completada']) ? (int)$input['completada'] : null;

        if ($completada !== null) {
            $at = $completada ? date('Y-m-d H:i:s') : null;
            $pdo->prepare("UPDATE mejoras_plataforma SET completada=?, completada_at=? WHERE id=?")
                ->execute([$completada, $at, $id]);
        }
        jsonResponse(['success' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $pdo->prepare("DELETE FROM mejoras_plataforma WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;
}
