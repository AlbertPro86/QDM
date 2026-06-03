<?php
/**
 * CRM QUANTUN Digital — API de Gestión del Portal Cliente (ADMIN)
 * Solo accesible por usuarios autenticados en el CRM.
 *
 * GET  ?action=list                           → todos los clientes con estado portal
 * GET  ?action=stats                          → métricas resumen
 * POST {action:'set_password', id, password}  → establece contraseña personalizada
 * POST {action:'reset_password', id}          → resetea al NIT (borra portal_password)
 * POST {action:'toggle_access', id, activo}   → activa/bloquea acceso
 * POST {action:'set_all_active'}              → activa acceso a todos los clientes con email
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$pdo = db();

// Auto-migraciones (por si acaso no se ejecutaron desde portal_auth)
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_password VARCHAR(255) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_activo TINYINT(1) NOT NULL DEFAULT 1"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_ultimo_acceso DATETIME DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_config TEXT DEFAULT NULL"); } catch (PDOException $e) {}

$method = $_SERVER['REQUEST_METHOD'];
$input  = ($method === 'POST') ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];
$action = $input['action'] ?? $_GET['action'] ?? '';

// ─── LIST ─────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $buscar = trim($_GET['buscar'] ?? '');
    $filtro = $_GET['filtro'] ?? 'todos'; // todos | activos | bloqueados | sin_email

    $where  = [];
    $params = [];

    // Filtros
    if ($filtro === 'activos') {
        $where[] = "c.portal_activo = 1";
        $where[] = "(c.email_contacto IS NOT NULL AND c.email_contacto != '' OR c.email_facturacion IS NOT NULL AND c.email_facturacion != '')";
    } elseif ($filtro === 'bloqueados') {
        $where[] = "c.portal_activo = 0";
    } elseif ($filtro === 'sin_email') {
        $where[] = "(COALESCE(TRIM(c.email_contacto),'') = '' AND COALESCE(TRIM(c.email_facturacion),'') = '')";
    }

    if ($buscar !== '') {
        $where[] = "(c.nombre_comercial LIKE ? OR c.email_contacto LIKE ? OR c.email_facturacion LIKE ? OR c.nit_cedula LIKE ?)";
        $like = "%{$buscar}%";
        $params = array_merge($params, [$like, $like, $like, $like]);
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.nombre_comercial,
            c.nit_cedula,
            c.email_contacto,
            c.email_facturacion,
            c.estado,
            c.portal_activo,
            c.portal_ultimo_acceso,
            (c.portal_password IS NOT NULL AND c.portal_password != '') AS has_custom_password,
            (
                COALESCE(TRIM(c.email_contacto),'') != '' OR
                COALESCE(TRIM(c.email_facturacion),'') != ''
            ) AS has_email,
            c.portal_config
        FROM clientes c
        {$whereClause}
        ORDER BY c.nombre_comercial ASC
    ");
    $stmt->execute($params);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── STATS ────────────────────────────────────────────────────────────────────
if ($action === 'stats') {
    $row = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN portal_activo = 1 AND (COALESCE(TRIM(email_contacto),'') != '' OR COALESCE(TRIM(email_facturacion),'') != '') THEN 1 ELSE 0 END) AS con_acceso,
            SUM(CASE WHEN portal_activo = 0 THEN 1 ELSE 0 END) AS bloqueados,
            SUM(CASE WHEN COALESCE(TRIM(email_contacto),'') = '' AND COALESCE(TRIM(email_facturacion),'') = '' THEN 1 ELSE 0 END) AS sin_email,
            SUM(CASE WHEN portal_ultimo_acceso IS NOT NULL THEN 1 ELSE 0 END) AS han_ingresado,
            SUM(CASE WHEN portal_password IS NOT NULL AND portal_password != '' THEN 1 ELSE 0 END) AS con_pwd_custom
        FROM clientes
    ")->fetch();
    jsonResponse(['success' => true, 'data' => $row]);
}

// ─── TOGGLE ACCESS ────────────────────────────────────────────────────────────
if ($action === 'toggle_access') {
    $id     = intval($input['id']     ?? 0);
    $activo = intval($input['activo'] ?? 1); // 1=activar, 0=bloquear
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

    $pdo->prepare("UPDATE clientes SET portal_activo = ? WHERE id = ?")->execute([$activo, $id]);

    $label = $activo ? 'Acceso activado' : 'Acceso bloqueado';
    jsonResponse(['success' => true, 'message' => $label, 'activo' => $activo]);
}

// ─── SET PASSWORD ─────────────────────────────────────────────────────────────
if ($action === 'set_password') {
    $id  = intval($input['id'] ?? 0);
    $pwd = trim($input['password'] ?? '');
    if (!$id)          jsonResponse(['error' => 'ID requerido'], 400);
    if (strlen($pwd) < 4) jsonResponse(['error' => 'La contraseña debe tener al menos 4 caracteres'], 400);

    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE clientes SET portal_password = ? WHERE id = ?")->execute([$hash, $id]);
    jsonResponse(['success' => true, 'message' => 'Contraseña establecida correctamente']);
}

// ─── RESET PASSWORD (vuelve al NIT por defecto) ───────────────────────────────
if ($action === 'reset_password') {
    $id = intval($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

    $pdo->prepare("UPDATE clientes SET portal_password = NULL WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Contraseña restablecida al NIT por defecto']);
}

// ─── GET CONFIG ───────────────────────────────────────────────────────────────
if ($action === 'get_config') {
    $id = intval($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
    $stmt = $pdo->prepare("SELECT portal_config FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $cfg = $row ? json_decode($row['portal_config'] ?? 'null', true) : null;
    jsonResponse(['success' => true, 'config' => $cfg ?: new stdClass()]);
}

// ─── SAVE CONFIG ──────────────────────────────────────────────────────────────
if ($action === 'save_config') {
    $id  = intval($input['id'] ?? 0);
    $cfg = $input['config'] ?? [];
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
    // Sanitizar: solo keys permitidos, valores bool
    $keys = ['stats','servicios','pagos','tareas','notificaciones','documentos','accesos','perfil'];
    $clean = [];
    foreach ($keys as $k) {
        $clean[$k] = isset($cfg[$k]) ? (bool)$cfg[$k] : true;
    }
    $pdo->prepare("UPDATE clientes SET portal_config = ? WHERE id = ?")->execute([json_encode($clean), $id]);
    jsonResponse(['success' => true, 'message' => 'Configuración guardada']);
}

// ─── ACTIVAR TODOS ────────────────────────────────────────────────────────────
if ($action === 'set_all_active') {
    $pdo->exec("UPDATE clientes SET portal_activo = 1 WHERE COALESCE(TRIM(email_contacto),'') != '' OR COALESCE(TRIM(email_facturacion),'') != ''");
    jsonResponse(['success' => true, 'message' => 'Todos los clientes con email habilitados']);
}

jsonResponse(['error' => 'Acción no válida'], 400);
