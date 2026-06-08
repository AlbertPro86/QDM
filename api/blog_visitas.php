<?php
/**
 * CRM QUANTUN Digital — API Blog Visitas
 * POST (público)  → registra una visita desde el artículo
 * GET  (auth)     → devuelve estadísticas por artículo
 * DELETE (auth)   → elimina visitas de un slug
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// CORS: permitir desde el sitio web
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$pdo = db();

// Auto-migración tablas
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blog_visitas (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            slug        VARCHAR(120)  NOT NULL,
            titulo      VARCHAR(250)  NOT NULL DEFAULT '',
            ip_address  VARCHAR(45)   DEFAULT NULL,
            user_agent  VARCHAR(350)  DEFAULT NULL,
            referer     VARCHAR(350)  DEFAULT NULL,
            created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blog_activos (
            session_id  VARCHAR(80)   NOT NULL PRIMARY KEY,
            pagina      VARCHAR(150)  NOT NULL DEFAULT 'home',
            ip_address  VARCHAR(45)   DEFAULT NULL,
            updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET activos (público rápido, sin auth) ─────────────────────────────────
if ($method === 'GET' && $action === 'activos') {
    try {
        // Limpiar sesiones viejas (>90s)
        $pdo->exec("DELETE FROM blog_activos WHERE updated_at < DATE_SUB(NOW(), INTERVAL 90 SECOND)");
        $total  = (int)$pdo->query("SELECT COUNT(*) FROM blog_activos")->fetchColumn();
        $paginas = $pdo->query("SELECT pagina, COUNT(*) AS n FROM blog_activos GROUP BY pagina ORDER BY n DESC")->fetchAll();
        jsonResponse(['success' => true, 'activos' => $total, 'paginas' => $paginas]);
    } catch (Exception $e) {
        jsonResponse(['success' => true, 'activos' => 0, 'paginas' => []]);
    }
}

// ── POST ping (público) ────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'ping') {
    $in  = json_decode(file_get_contents('php://input'), true) ?: [];
    $sid = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $in['session_id'] ?? ''), 0, 80);
    $pag = substr(trim($in['pagina'] ?? 'home'), 0, 150);
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($sid) {
        try {
            $pdo->prepare("INSERT INTO blog_activos (session_id, pagina, ip_address) VALUES (?,?,?)
                           ON DUPLICATE KEY UPDATE pagina=VALUES(pagina), ip_address=VALUES(ip_address), updated_at=NOW()")
                ->execute([$sid, $pag, $ip]);
        } catch (Exception $e) {}
    }
    jsonResponse(['success' => true]);
}

// ── GET detalle (autenticado) — visitas individuales de un slug ───────────
if ($method === 'GET' && $action === 'detalle') {
    if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);
    $slug  = trim($_GET['slug'] ?? '');
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = 50;
    $off   = ($page - 1) * $limit;
    if (!$slug) jsonResponse(['error' => 'slug requerido'], 400);

    $stC = $pdo->prepare("SELECT COUNT(*) FROM blog_visitas WHERE slug = ?");
    $stC->execute([$slug]);
    $total = (int)$stC->fetchColumn();

    $stV = $pdo->prepare("SELECT id, created_at, ip_address, user_agent, referer
                           FROM blog_visitas WHERE slug = ?
                           ORDER BY id DESC LIMIT ? OFFSET ?");
    $stV->execute([$slug, $limit, $off]);
    $visitas = $stV->fetchAll();

    jsonResponse([
        'success' => true,
        'visitas' => $visitas,
        'total'   => $total,
        'page'    => $page,
        'pages'   => (int)ceil($total / $limit),
    ]);
}

switch ($method) {

    /* ── POST: público — registra visita ─────────────────────────── */
    case 'POST':
        $in    = json_decode(file_get_contents('php://input'), true) ?: [];
        $slug  = trim($in['slug']   ?? '');
        $titulo = trim($in['titulo'] ?? '');

        if (!$slug) { jsonResponse(['error' => 'slug requerido'], 400); }

        // Rate-limit suave: no contar recarga inmediata (<10s) del mismo IP+slug
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rl = $pdo->prepare("SELECT COUNT(*) FROM blog_visitas WHERE slug = ? AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)");
        $rl->execute([$slug, $ip]);
        if ($rl->fetchColumn() > 0) {
            jsonResponse(['success' => true, 'skipped' => true]);
        }

        $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 350);
        $ref = substr($_SERVER['HTTP_REFERER']    ?? '', 0, 350);

        $pdo->prepare("INSERT INTO blog_visitas (slug, titulo, ip_address, user_agent, referer) VALUES (?, ?, ?, ?, ?)")
            ->execute([$slug, $titulo, $ip, $ua ?: null, $ref ?: null]);

        jsonResponse(['success' => true]);
        break;

    /* ── GET: autenticado — estadísticas ─────────────────────────── */
    case 'GET':
        if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

        // Resumen por artículo
        $rows = $pdo->query("
            SELECT
                slug,
                MAX(titulo)                                                       AS titulo,
                COUNT(*)                                                          AS total,
                COUNT(DISTINCT ip_address)                                        AS unicos,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7  DAY) THEN 1 ELSE 0 END) AS ultimos_7d,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS ultimos_30d,
                MAX(created_at)                                                   AS ultima_visita
            FROM blog_visitas
            GROUP BY slug
            ORDER BY total DESC
        ")->fetchAll();

        // Totales globales
        $totales = $pdo->query("
            SELECT
                COUNT(*)                                                           AS total,
                COUNT(DISTINCT ip_address)                                         AS unicos,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7  DAY) THEN 1 ELSE 0 END) AS ultimos_7d,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS ultimos_30d
            FROM blog_visitas
        ")->fetch();

        // Evolución diaria (últimos 30 días) para mini-gráfico
        $diario = $pdo->query("
            SELECT DATE(created_at) AS dia, COUNT(*) AS visitas
            FROM blog_visitas
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY dia ASC
        ")->fetchAll();

        // Top fuentes (referer) — agrupa por dominio origen
        $fuentes = $pdo->query("
            SELECT
                CASE
                    WHEN referer IS NULL OR referer = ''          THEN 'Directo / Sin fuente'
                    WHEN referer LIKE '%facebook.com%'            THEN 'Facebook'
                    WHEN referer LIKE '%instagram.com%'           THEN 'Instagram'
                    WHEN referer LIKE '%t.co%' OR referer LIKE '%twitter.com%' OR referer LIKE '%x.com%' THEN 'X / Twitter'
                    WHEN referer LIKE '%linkedin.com%'            THEN 'LinkedIn'
                    WHEN referer LIKE '%whatsapp.com%' OR referer LIKE '%wa.me%' THEN 'WhatsApp'
                    WHEN referer LIKE '%google.com%' OR referer LIKE '%google.co%' THEN 'Google'
                    WHEN referer LIKE '%bing.com%'                THEN 'Bing'
                    WHEN referer LIKE '%youtube.com%'             THEN 'YouTube'
                    WHEN referer LIKE '%tiktok.com%'              THEN 'TikTok'
                    WHEN referer LIKE '%quantundigital.com%'      THEN 'Sitio propio'
                    ELSE CONCAT('Otro: ', SUBSTRING_INDEX(REPLACE(REPLACE(referer,'https://',''),'http://',''),'/',1))
                END AS fuente,
                COUNT(*) AS visitas
            FROM blog_visitas
            GROUP BY fuente
            ORDER BY visitas DESC
            LIMIT 10
        ")->fetchAll();

        jsonResponse([
            'success'   => true,
            'articulos' => $rows,
            'totales'   => $totales,
            'diario'    => $diario,
            'fuentes'   => $fuentes,
        ]);
        break;

    /* ── DELETE: autenticado — borrar visitas de un slug ─────────── */
    case 'DELETE':
        if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);
        $slug = trim($_GET['slug'] ?? '');
        if (!$slug) jsonResponse(['error' => 'slug requerido'], 400);
        $pdo->prepare("DELETE FROM blog_visitas WHERE slug = ?")->execute([$slug]);
        jsonResponse(['success' => true, 'message' => 'Visitas eliminadas']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
