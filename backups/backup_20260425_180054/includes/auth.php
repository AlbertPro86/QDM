<?php
/**
 * CRM QUANTUN Digital - Sistema de Autenticación
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Intentar autenticar un usuario
 */
function login($email, $password) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Actualizar último login
        $update = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);

        // Guardar en sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['user_avatar'] = $user['avatar_url'];
        $_SESSION['logged_in'] = true;

        // Registrar actividad
        logActivity($user['id'], 'login', 'usuarios', $user['id'], 'Inicio de sesión');

        return true;
    }
    return false;
}

/**
 * Cerrar sesión
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'usuarios', $_SESSION['user_id'], 'Cierre de sesión');
    }
    session_unset();
    session_destroy();
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Proteger ruta: redirigir al login si no autenticado
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

/**
 * Verificar si el usuario es admin
 */
function isAdmin() {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
}

/**
 * Obtener datos del usuario actual
 */
function getCurrentUser() {
    if (!isAuthenticated()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_nombre'],
        'email' => $_SESSION['user_email'],
        'rol' => $_SESSION['user_rol'],
        'avatar' => $_SESSION['user_avatar'],
        'initials' => getInitials($_SESSION['user_nombre'])
    ];
}

/**
 * Obtener iniciales del nombre
 */
function getInitials($nombre) {
    $parts = explode(' ', trim($nombre));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials ?: 'U';
}

/**
 * Registrar actividad en el log
 */
function logActivity($userId, $accion, $entidad, $entidadId = null, $detalles = null) {
    try {
        $pdo = db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $pdo->prepare("INSERT INTO actividad_log (usuario_id, accion, entidad, entidad_id, detalles, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $accion, $entidad, $entidadId, $detalles, $ip]);
    } catch (Exception $e) {
        // No interrumpir el flujo por error de log
        if (APP_DEBUG) error_log("Log error: " . $e->getMessage());
    }
}
