<?php
/**
 * CRM QUANTUN Digital — API Solicitudes desde sitio web
 * POST (público)  → recibe solicitud del formulario web
 * GET  (auth)     → lista solicitudes para el CRM
 * PUT  (auth)     → actualiza estado/notas
 * DELETE (auth)   → elimina solicitud
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

// Auto-migración
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_solicitudes_web (
            id                  INT AUTO_INCREMENT PRIMARY KEY,
            nombre              VARCHAR(150) NOT NULL,
            email               VARCHAR(200) NOT NULL,
            telefono            VARCHAR(30)  NOT NULL,
            empresa             VARCHAR(200) DEFAULT NULL,
            servicio_solicitado VARCHAR(150) NOT NULL,
            plan_solicitado     VARCHAR(100) DEFAULT NULL,
            mensaje             TEXT         DEFAULT NULL,
            estado              VARCHAR(20)  NOT NULL DEFAULT 'nuevo',
            created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            atendido_por        INT          DEFAULT NULL,
            notas_internas      TEXT         DEFAULT NULL,
            ip_address          VARCHAR(45)  DEFAULT NULL,
            fuente              VARCHAR(50)  NOT NULL DEFAULT 'quantun-web'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    /* ── POST: público — recibe solicitud del formulario web ──────── */
    case 'POST':
        $in = json_decode(file_get_contents('php://input'), true) ?: [];

        // Honeypot anti-bot
        if (!empty($in['website_url'])) {
            // Bot llenó el campo oculto → respuesta falsa exitosa
            jsonResponse(['success' => true, 'message' => 'Solicitud recibida']);
        }

        // Rate limiting: máx 3 solicitudes por IP en 5 min
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rl = $pdo->prepare("SELECT COUNT(*) FROM crm_solicitudes_web WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $rl->execute([$ip]);
        if ($rl->fetchColumn() >= 3) {
            jsonResponse(['error' => 'Demasiadas solicitudes. Intenta en unos minutos.'], 429);
        }

        // Validar campos requeridos
        $nombre   = trim($in['name']    ?? $in['nombre']   ?? '');
        $email    = trim($in['email']   ?? '');
        $telefono = trim($in['phone']   ?? $in['telefono'] ?? '');
        $servicio = trim($in['service'] ?? $in['servicio_solicitado'] ?? '');

        if (!$nombre)   jsonResponse(['error' => 'El nombre es requerido'], 400);
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Email inválido'], 400);
        if (!$telefono) jsonResponse(['error' => 'El teléfono es requerido'], 400);
        if (!$servicio) jsonResponse(['error' => 'Debe seleccionar un servicio'], 400);

        $empresa = trim($in['company'] ?? $in['empresa'] ?? '');
        $plan    = trim($in['plan']    ?? $in['plan_solicitado'] ?? '');
        $mensaje = trim($in['message'] ?? $in['mensaje'] ?? '');
        $fuente  = trim($in['source']  ?? 'quantun-web');

        $s = $pdo->prepare("
            INSERT INTO crm_solicitudes_web
                (nombre, email, telefono, empresa, servicio_solicitado, plan_solicitado, mensaje, ip_address, fuente)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $s->execute([$nombre, $email, $telefono, $empresa ?: null, $servicio, $plan ?: null, $mensaje ?: null, $ip, $fuente]);

        jsonResponse(['success' => true, 'message' => 'Solicitud recibida correctamente']);
        break;

    /* ── GET: autenticado — listar solicitudes ────────────────────── */
    case 'GET':
        if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

        $estado = $_GET['estado'] ?? '';
        $buscar = $_GET['buscar'] ?? '';
        $limite = max(1, intval($_GET['limite'] ?? 50));
        $offset = max(0, intval($_GET['offset'] ?? 0));

        $where = []; $params = [];

        if ($estado && $estado !== 'todos') {
            $where[] = 's.estado = ?';
            $params[] = $estado;
        }
        if ($buscar) {
            $where[] = '(s.nombre LIKE ? OR s.email LIKE ? OR s.empresa LIKE ? OR s.servicio_solicitado LIKE ?)';
            $like = "%{$buscar}%";
            array_push($params, $like, $like, $like, $like);
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Conteos por estado
        $counts = ['total' => 0, 'nuevo' => 0, 'contactado' => 0, 'cerrado' => 0];
        $cq = $pdo->query("SELECT estado, COUNT(*) as c FROM crm_solicitudes_web GROUP BY estado");
        foreach ($cq->fetchAll() as $r) {
            $counts[$r['estado']] = (int)$r['c'];
            $counts['total'] += (int)$r['c'];
        }

        $sql = "SELECT s.*, u.nombre AS atendido_nombre
                FROM crm_solicitudes_web s
                LEFT JOIN usuarios u ON s.atendido_por = u.id
                {$whereSQL}
                ORDER BY s.created_at DESC
                LIMIT {$limite} OFFSET {$offset}";
        $st = $pdo->prepare($sql);
        $st->execute($params);

        jsonResponse(['success' => true, 'data' => $st->fetchAll(), 'counts' => $counts]);
        break;

    /* ── PUT: autenticado — actualizar estado/notas ───────────────── */
    case 'PUT':
        if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = intval($in['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        $allowed = ['estado', 'atendido_por', 'notas_internas'];
        $set = []; $vals = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $in)) {
                $set[] = "$f = ?";
                $vals[] = $in[$f];
            }
        }
        if (empty($set)) jsonResponse(['error' => 'Sin campos para actualizar'], 400);

        $vals[] = $id;
        $pdo->prepare("UPDATE crm_solicitudes_web SET " . implode(', ', $set) . " WHERE id = ?")
            ->execute($vals);

        jsonResponse(['success' => true, 'message' => 'Solicitud actualizada']);
        break;

    /* ── DELETE: autenticado ──────────────────────────────────────── */
    case 'DELETE':
        if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        $pdo->prepare("DELETE FROM crm_solicitudes_web WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Solicitud eliminada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
