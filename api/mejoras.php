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

// Auto-migración: tabla base
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

// Auto-migración: columnas nuevas
foreach ([
    "ALTER TABLE mejoras_plataforma ADD COLUMN prioridad   VARCHAR(10)  NOT NULL DEFAULT 'media'",
    "ALTER TABLE mejoras_plataforma ADD COLUMN estado      VARCHAR(20)  NOT NULL DEFAULT 'idea'",
    "ALTER TABLE mejoras_plataforma ADD COLUMN notas       TEXT         DEFAULT NULL",
    "ALTER TABLE mejoras_plataforma ADD COLUMN complejidad    VARCHAR(10)  NOT NULL DEFAULT 'media'",
    "ALTER TABLE mejoras_plataforma ADD COLUMN fecha_objetivo DATE         DEFAULT NULL",
] as $ddl) {
    try { $pdo->exec($ddl); } catch (PDOException $e) {}
}

// Sincronizar campo legado `completada` con `estado`
// (por si hay registros viejos con completada=1 pero estado='idea')
try {
    $pdo->exec("UPDATE mejoras_plataforma SET estado='completado' WHERE completada=1 AND estado='idea'");
} catch (PDOException $e) {}

switch ($method) {
    case 'GET':
        $rows = $pdo->query("
            SELECT * FROM mejoras_plataforma
            ORDER BY
                FIELD(estado,'en_progreso','planeado','idea','completado'),
                FIELD(prioridad,'alta','media','baja'),
                created_at DESC
        ")->fetchAll();
        jsonResponse(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['texto'])) jsonResponse(['error' => 'Texto requerido'], 400);

        $stmt = $pdo->prepare("
            INSERT INTO mejoras_plataforma (texto, categoria, prioridad, estado, notas, complejidad, fecha_objetivo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            substr(trim($input['texto']), 0, 600),
            $input['categoria']      ?? 'otro',
            $input['prioridad']      ?? 'media',
            $input['estado']         ?? 'idea',
            $input['notas']          ?? null,
            $input['complejidad']    ?? 'media',
            $input['fecha_objetivo'] ?? null,
        ]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        $fields = ['texto', 'categoria', 'prioridad', 'estado', 'notas', 'complejidad', 'completada', 'fecha_objetivo'];
        $set = []; $vals = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $input)) {
                $set[]  = "$f = ?";
                $vals[] = $input[$f];
            }
        }
        // Sincronizar completada ↔ estado
        if (isset($input['estado'])) {
            $completada = $input['estado'] === 'completado' ? 1 : 0;
            $set[]  = "completada = ?";
            $vals[] = $completada;
            if ($completada) {
                $set[]  = "completada_at = ?";
                $vals[] = date('Y-m-d H:i:s');
            } else {
                $set[]  = "completada_at = NULL";
            }
        }
        if (empty($set)) jsonResponse(['success' => true]);
        $vals[] = $id;
        $pdo->prepare("UPDATE mejoras_plataforma SET " . implode(', ', $set) . " WHERE id = ?")->execute($vals);
        jsonResponse(['success' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $pdo->prepare("DELETE FROM mejoras_plataforma WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;
}
