<?php
/**
 * CRM QUANTUN Digital — API de autenticación del Portal Cliente
 * POST { action:'login',  email, password } → inicia sesión de cliente
 * POST { action:'logout' }                  → cierra sesión
 * GET  { action:'check'  }                  → devuelve estado de sesión
 * POST { action:'change_password', actual, nueva, confirm } → cambio de contraseña
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function portalJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$pdo = db();

// Auto-migraciones: columnas del portal en tabla clientes
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_password VARCHAR(255) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_activo TINYINT(1) NOT NULL DEFAULT 1"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_ultimo_acceso DATETIME DEFAULT NULL"); } catch (PDOException $e) {}

$method = $_SERVER['REQUEST_METHOD'];
$input  = ($method === 'POST') ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];
$action = $input['action'] ?? $_GET['action'] ?? '';

// ─── CHECK ────────────────────────────────────────────────────────────────────
if ($action === 'check') {
    portalJson([
        'logged_in' => !empty($_SESSION['portal_logged_in']),
        'cliente'   => !empty($_SESSION['portal_cliente_id']) ? [
            'id'     => $_SESSION['portal_cliente_id'],
            'nombre' => $_SESSION['portal_nombre'] ?? '',
        ] : null,
    ]);
}

// ─── LOGOUT ───────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    unset(
        $_SESSION['portal_logged_in'],
        $_SESSION['portal_cliente_id'],
        $_SESSION['portal_nombre'],
        $_SESSION['portal_email']
    );
    portalJson(['success' => true]);
}

// ─── LOGIN ────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    $email = strtolower(trim($input['email'] ?? ''));
    $pass  = trim($input['password'] ?? '');

    if (!$email || !$pass) {
        portalJson(['error' => 'Email y contraseña son requeridos'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT id, nombre_comercial, nit_cedula, portal_password, portal_activo
        FROM clientes
        WHERE LOWER(TRIM(email_contacto)) = ?
           OR LOWER(TRIM(email_facturacion)) = ?
        LIMIT 1
    ");
    $stmt->execute([$email, $email]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        portalJson(['error' => 'Credenciales incorrectas. Verifica tu email.'], 401);
    }

    // Verificar si el acceso está activo
    if (!$cliente['portal_activo']) {
        portalJson(['error' => 'Tu acceso al portal está desactivado. Comunícate con QUANTUN Digital.'], 403);
    }

    // Verificar contraseña
    $valid = false;
    if (!empty($cliente['portal_password'])) {
        $valid = password_verify($pass, $cliente['portal_password']);
    } else {
        // Contraseña por defecto: NIT / número de identificación
        $valid = ($pass !== '' && trim($pass) === trim($cliente['nit_cedula'] ?? ''));
    }

    if (!$valid) {
        portalJson(['error' => 'Credenciales incorrectas. Verifica tu contraseña.'], 401);
    }

    // Actualizar último acceso
    $pdo->prepare("UPDATE clientes SET portal_ultimo_acceso = NOW() WHERE id = ?")->execute([$cliente['id']]);

    // Guardar sesión de portal (coexiste con la sesión CRM sin conflicto)
    $_SESSION['portal_logged_in']  = true;
    $_SESSION['portal_cliente_id'] = (int)$cliente['id'];
    $_SESSION['portal_nombre']     = $cliente['nombre_comercial'];
    $_SESSION['portal_email']      = $email;

    portalJson([
        'success' => true,
        'nombre'  => $cliente['nombre_comercial'],
    ]);
}

// ─── CAMBIAR CONTRASEÑA (cliente autenticado) ────────────────────────────────
if ($action === 'change_password') {
    if (empty($_SESSION['portal_cliente_id'])) {
        portalJson(['error' => 'No autenticado'], 401);
    }

    $actual  = trim($input['actual']  ?? '');
    $nueva   = trim($input['nueva']   ?? '');
    $confirm = trim($input['confirm'] ?? '');

    if (!$actual || !$nueva || !$confirm) {
        portalJson(['error' => 'Todos los campos son requeridos'], 400);
    }
    if (strlen($nueva) < 6) {
        portalJson(['error' => 'La nueva contraseña debe tener al menos 6 caracteres'], 400);
    }
    if ($nueva !== $confirm) {
        portalJson(['error' => 'Las contraseñas no coinciden'], 400);
    }

    $cid  = (int)$_SESSION['portal_cliente_id'];
    $stmt = $pdo->prepare("SELECT nit_cedula, portal_password FROM clientes WHERE id = ?");
    $stmt->execute([$cid]);
    $cliente = $stmt->fetch();

    $valid = false;
    if (!empty($cliente['portal_password'])) {
        $valid = password_verify($actual, $cliente['portal_password']);
    } else {
        $valid = ($actual === trim($cliente['nit_cedula'] ?? ''));
    }

    if (!$valid) {
        portalJson(['error' => 'La contraseña actual no es correcta'], 400);
    }

    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE clientes SET portal_password = ? WHERE id = ?")->execute([$hash, $cid]);
    portalJson(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
}

portalJson(['error' => 'Acción no válida'], 400);
