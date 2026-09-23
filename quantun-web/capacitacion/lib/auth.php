<?php
/**
 * Sesión, CSRF y control de intentos.
 * Sesión propia con nombre y ruta aisladas para no interferir con el CRM.
 */

require_once __DIR__ . '/store.php';

function cap_sesion_iniciar(): void {
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('QDCAPSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_start();
}

function cap_csrf(): string {
    cap_sesion_iniciar();
    if (empty($_SESSION['cap_csrf'])) {
        $_SESSION['cap_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cap_csrf'];
}

function cap_csrf_valido(?string $t): bool {
    cap_sesion_iniciar();
    return is_string($t) && !empty($_SESSION['cap_csrf']) && hash_equals($_SESSION['cap_csrf'], $t);
}

/** Limita intentos de ingreso por sesión: 6 cada 10 minutos. */
function cap_intento_permitido(): bool {
    cap_sesion_iniciar();
    $ahora = time();
    $reg   = $_SESSION['cap_intentos'] ?? ['n' => 0, 'desde' => $ahora];
    if ($ahora - $reg['desde'] > 600) { $reg = ['n' => 0, 'desde' => $ahora]; }
    $_SESSION['cap_intentos'] = $reg;
    return $reg['n'] < 6;
}

function cap_intento_fallido(): void {
    cap_sesion_iniciar();
    $_SESSION['cap_intentos']['n'] = ($_SESSION['cap_intentos']['n'] ?? 0) + 1;
}

function cap_intento_limpiar(): void {
    cap_sesion_iniciar();
    unset($_SESSION['cap_intentos']);
}

function cap_hay_admin(): bool {
    $d = cap_leer();
    return !empty($d['admin']['hash']);
}

function cap_es_admin(): bool {
    cap_sesion_iniciar();
    return !empty($_SESSION['cap_admin']);
}

/** Nombre de quien actúa en el panel (administrador o supervisor), para la bitácora. */
function cap_admin_nombre(): string {
    cap_sesion_iniciar();
    if (!empty($_SESSION['cap_admin'])) { return $_SESSION['cap_admin_usuario'] ?? 'Administrador'; }
    $sup = cap_supervisor_actual();
    if ($sup) { return cap_nombre_completo($sup); }
    return 'Administrador';
}

/** Supervisor con sesión activa (cacheado por petición). */
function cap_supervisor_actual(): ?array {
    static $cache = [];
    cap_sesion_iniciar();
    $id = $_SESSION['cap_supervisor'] ?? null;
    if (!$id) { return null; }
    if (array_key_exists($id, $cache)) { return $cache[$id]; }
    $e = cap_buscar_estudiante(cap_leer(), $id);
    $ok = $e && !empty($e['activo']) && $e['rol'] === 'supervisor';
    return $cache[$id] = $ok ? $e : null;
}

/** Administrador o supervisor. */
function cap_es_staff(): bool {
    return cap_es_admin() || cap_supervisor_actual() !== null;
}

/** Permisos: el administrador puede todo; el supervisor, lo listado en CAP_PERMISOS_SUPERVISOR. */
function cap_puede(string $permiso): bool {
    if (cap_es_admin()) { return true; }
    if (cap_supervisor_actual()) { return in_array($permiso, CAP_PERMISOS_SUPERVISOR, true); }
    return false;
}

function cap_rol_nombre(): string {
    if (cap_es_admin()) { return 'Administrador'; }
    if (cap_supervisor_actual()) { return 'Supervisor'; }
    return 'Estudiante';
}

function cap_estudiante_id(): ?string {
    cap_sesion_iniciar();
    return $_SESSION['cap_estudiante'] ?? null;
}

function cap_estudiante_actual(): ?array {
    $id = cap_estudiante_id();
    if (!$id) { return null; }
    $e = cap_buscar_estudiante(cap_leer(), $id);
    if (!$e || empty($e['activo']) || $e['rol'] !== 'estudiante') { return null; }
    return $e;
}

function cap_login_admin(string $usuario, string $clave): bool {
    if (!cap_intento_permitido()) { return false; }
    $d = cap_leer();
    $usuarioOk = !empty($d['admin']['usuario'])
        && hash_equals(strtolower(trim($d['admin']['usuario'])), strtolower(trim($usuario)));
    if (empty($d['admin']['hash']) || !$usuarioOk || !password_verify($clave, $d['admin']['hash'])) {
        cap_intento_fallido();
        return false;
    }
    cap_sesion_iniciar();
    session_regenerate_id(true);
    $_SESSION['cap_admin'] = true;
    $_SESSION['cap_admin_usuario'] = $d['admin']['usuario'];
    cap_intento_limpiar();
    return true;
}

/**
 * Login unificado: un solo formulario para instructor y estudiantes.
 * Prueba primero las credenciales de administrador y luego las de estudiante,
 * contando un unico intento fallido (no dos) para el limite de reintentos.
 *
 * @return string|null 'admin', 'estudiante' o null si no coincide
 */
function cap_login(string $usuario, string $clave): ?string {
    if (!cap_intento_permitido()) { return null; }

    // 1) Administrador
    $d = cap_leer();
    if (!empty($d['admin']['hash'])
        && !empty($d['admin']['usuario'])
        && hash_equals(strtolower(trim($d['admin']['usuario'])), strtolower(trim($usuario)))
        && password_verify($clave, $d['admin']['hash'])) {
        cap_sesion_iniciar();
        session_regenerate_id(true);
        unset($_SESSION['cap_supervisor'], $_SESSION['cap_estudiante']);
        $_SESSION['cap_admin']         = true;
        $_SESSION['cap_admin_usuario'] = $d['admin']['usuario'];
        cap_intento_limpiar();
        return 'admin';
    }

    // 2) Estudiante
    $email = strtolower(trim($usuario));
    $encontrado = cap_transaccion(function (array &$dd) use ($email, $clave) {
        foreach ($dd['estudiantes'] as $i => $e) {
            if (strtolower($e['email'] ?? '') !== $email)    { continue; }
            if (empty($e['activo']))                          { continue; }
            if (empty($e['clave_hash']))                      { continue; }
            if (!password_verify($clave, $e['clave_hash']))   { continue; }

            $dd['estudiantes'][$i]['ultimo_ingreso'] = date('c');
            cap_bitacora($dd, cap_nombre_completo($e), 'ingreso', 'Sesion iniciada');
            return $dd['estudiantes'][$i];
        }
        return null;
    });

    if ($encontrado) {
        cap_sesion_iniciar();
        session_regenerate_id(true);
        unset($_SESSION['cap_admin'], $_SESSION['cap_admin_usuario'], $_SESSION['cap_supervisor'], $_SESSION['cap_estudiante']);
        cap_intento_limpiar();
        if (($encontrado['rol'] ?? 'estudiante') === 'supervisor') {
            $_SESSION['cap_supervisor'] = $encontrado['id'];
            return 'supervisor';
        }
        $_SESSION['cap_estudiante'] = $encontrado['id'];
        return 'estudiante';
    }

    cap_intento_fallido();
    return null;
}

function cap_logout(): void {
    cap_sesion_iniciar();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function cap_exigir_admin(): void {
    if (!cap_es_admin()) { header('Location: index.php?v=admin'); exit; }
}

function cap_exigir_staff(): void {
    if (!cap_es_staff()) { header('Location: index.php?v=acceso'); exit; }
}

function cap_exigir_estudiante(): array {
    $e = cap_estudiante_actual();
    if (!$e) { header('Location: index.php?v=acceso'); exit; }
    return $e;
}

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cap_json($payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
