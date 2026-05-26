<?php
/**
 * CRM QUANTUN Digital — API Tareas
 * CRUD completo con auto-migración
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

// ── Auto-migración ────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS tareas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    titulo       VARCHAR(255) NOT NULL,
    descripcion  TEXT         DEFAULT NULL,
    prioridad    ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
    estado       ENUM('pendiente','en_progreso','revision','completado','cancelado') NOT NULL DEFAULT 'pendiente',
    responsable  VARCHAR(120) DEFAULT NULL,
    cliente_id   INT          DEFAULT NULL,
    lead_id      INT          DEFAULT NULL,
    fecha_limite DATE         DEFAULT NULL,
    notas        TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

switch ($method) {

    /* ── GET ─────────────────────────────────────────────────────────── */
    case 'GET':
        $id     = $_GET['id']       ?? null;
        $estado = $_GET['estado']   ?? null;
        $prior  = $_GET['prioridad']?? null;
        $buscar = $_GET['buscar']   ?? null;
        $desde  = $_GET['desde']    ?? null;
        $hasta  = $_GET['hasta']    ?? null;

        $base = "SELECT t.*,
                        c.nombre_comercial AS cliente_nombre,
                        l.nombre AS lead_nombre
                 FROM tareas t
                 LEFT JOIN clientes c ON c.id = t.cliente_id
                 LEFT JOIN leads    l ON l.id  = t.lead_id";

        if ($id) {
            $s = $pdo->prepare("$base WHERE t.id = ?");
            $s->execute([$id]);
            $row = $s->fetch();
            if (!$row) jsonResponse(['error' => 'Tarea no encontrada'], 404);
            jsonResponse(['success' => true, 'data' => $row]);
        }

        $cliente_id_filter = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        $limite    = isset($_GET['limite'])   ? intval($_GET['limite'])   : 500;
        $historial = isset($_GET['historial']) && $_GET['historial'] == '1';

        $where  = [];
        $params = [];

        if ($historial) {
            // Historial: solo completadas y canceladas (eliminadas)
            $where[] = "t.estado IN ('completado','cancelado')";
        } elseif ($cliente_id_filter) {
            // Vista de cliente: todas las tareas del cliente, incluso canceladas
            $where[] = "t.cliente_id = ?";
            $params[] = $cliente_id_filter;
        } else {
            // Vista general: excluir canceladas
            $where[] = "t.estado != 'cancelado'";
        }

        if ($estado) { $where[] = "t.estado = ?";    $params[] = $estado; }
        if ($prior)  { $where[] = "t.prioridad = ?"; $params[] = $prior;  }
        if ($buscar) {
            $where[]  = "(t.titulo LIKE ? OR t.descripcion LIKE ? OR t.responsable LIKE ?)";
            $term     = "%$buscar%";
            $params   = array_merge($params, [$term, $term, $term]);
        }
        if ($desde) { $where[] = "DATE(t.created_at) >= ?"; $params[] = $desde; }
        if ($hasta) { $where[] = "DATE(t.created_at) <= ?"; $params[] = $hasta; }

        $whereStr = $where ? "WHERE " . implode(' AND ', $where) : "";
        $sql = "$base $whereStr ORDER BY FIELD(t.estado,'pendiente','en_progreso','revision','completado','cancelado'), FIELD(t.prioridad,'alta','media','baja'), t.fecha_limite ASC LIMIT " . $limite;

        $s = $pdo->prepare($sql);
        $s->execute($params);
        $rows = $s->fetchAll();

        // Resumen por estado
        $resumen = $pdo->query(
            "SELECT estado, COUNT(*) AS total FROM tareas
             WHERE estado != 'cancelado' GROUP BY estado"
        )->fetchAll();

        $resArr = [];
        foreach ($resumen as $r) $resArr[$r['estado']] = (int)$r['total'];

        jsonResponse(['success' => true, 'data' => $rows, 'total' => count($rows), 'resumen' => $resArr]);

    /* ── POST ────────────────────────────────────────────────────────── */
    case 'POST':
        $in = json_decode(file_get_contents('php://input'), true);
        if (empty($in['titulo'])) jsonResponse(['error' => 'El título es requerido'], 400);

        $s = $pdo->prepare(
            "INSERT INTO tareas (titulo,descripcion,prioridad,estado,responsable,cliente_id,lead_id,fecha_limite,notas)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $s->execute([
            $in['titulo'],
            $in['descripcion'] ?? null,
            $in['prioridad']   ?? 'media',
            $in['estado']      ?? 'pendiente',
            $in['responsable'] ?? null,
            $in['cliente_id']  ?? null,
            $in['lead_id']     ?? null,
            $in['fecha_limite'] ? $in['fecha_limite'] : null,
            $in['notas']       ?? null,
        ]);
        $newId = $pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'crear', 'tareas', $newId, "Nueva tarea: {$in['titulo']}");

        $s = $pdo->prepare("SELECT * FROM tareas WHERE id = ?");
        $s->execute([$newId]);
        jsonResponse(['success' => true, 'data' => $s->fetch(), 'message' => 'Tarea creada'], 201);

    /* ── PUT ─────────────────────────────────────────────────────────── */
    case 'PUT':
        $in = json_decode(file_get_contents('php://input'), true);
        $id = $in['id'] ?? ($_GET['id'] ?? null);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        $allowed = ['titulo','descripcion','prioridad','estado','responsable','cliente_id','lead_id','fecha_limite','notas'];
        $fields  = [];
        $values  = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = ?";
                $values[] = ($in[$f] === '' && in_array($f, ['fecha_limite','cliente_id','lead_id'])) ? null : $in[$f];
            }
        }
        if (!$fields) jsonResponse(['error' => 'Sin campos para actualizar'], 400);

        $values[] = $id;
        $pdo->prepare("UPDATE tareas SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
        logActivity($_SESSION['user_id'], 'editar', 'tareas', $id, "Tarea editada: ID $id");

        $s = $pdo->prepare("SELECT * FROM tareas WHERE id = ?");
        $s->execute([$id]);
        jsonResponse(['success' => true, 'data' => $s->fetch(), 'message' => 'Tarea actualizada']);

    /* ── DELETE ──────────────────────────────────────────────────────── */
    case 'DELETE':
        $id         = $_GET['id'] ?? (json_decode(file_get_contents('php://input'), true)['id'] ?? null);
        $permanente = isset($_GET['permanente']) && $_GET['permanente'] == '1';
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        if ($permanente) {
            $pdo->prepare("DELETE FROM tareas WHERE id = ?")->execute([$id]);
            logActivity($_SESSION['user_id'], 'eliminar', 'tareas', $id, "Tarea eliminada permanentemente: ID $id");
            jsonResponse(['success' => true, 'message' => 'Tarea eliminada permanentemente']);
        } else {
            $pdo->prepare("UPDATE tareas SET estado = 'cancelado' WHERE id = ?")->execute([$id]);
            logActivity($_SESSION['user_id'], 'eliminar', 'tareas', $id, "Tarea cancelada: ID $id");
            jsonResponse(['success' => true, 'message' => 'Tarea eliminada']);
        }

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
