<?php
/**
 * CRM QUANTUN Digital - Funciones Helper
 */

/**
 * Formatear moneda en COP/USD
 */
function formatMoney($amount, $symbol = '$') {
    return $symbol . ' ' . number_format((float)$amount, 0, ',', '.') . ' COP';
}

/**
 * Solo número formateado sin COP — para valores grandes en cards
 */
function moneyNum($amount, $symbol = '$') {
    return $symbol . ' ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Formatear fecha legible
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return '—';
    return date($format, strtotime($date));
}

/**
 * Formatear fecha relativa (hace X minutos/horas/días)
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return "hace {$diff->y} año" . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return "hace {$diff->m} mes" . ($diff->m > 1 ? 'es' : '');
    if ($diff->d > 0) return "hace {$diff->d} día" . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return "hace {$diff->h} hora" . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return "hace {$diff->i} min";
    return "ahora";
}

/**
 * Obtener clase CSS para el badge de estado del lead
 */
function getLeadStatusBadge($estado) {
    $badges = [
        'nuevo' => 'badge-info',
        'contactado' => 'badge-primary',
        'en_negociacion' => 'badge-warning',
        'ganado' => 'badge-success',
        'perdido' => 'badge-danger'
    ];
    return $badges[$estado] ?? 'badge-default';
}

/**
 * Obtener etiqueta legible del estado del lead
 */
function getLeadStatusLabel($estado) {
    $labels = [
        'nuevo' => 'Nuevo',
        'contactado' => 'Contactado',
        'en_negociacion' => 'En Negociación',
        'ganado' => 'Ganado',
        'perdido' => 'Perdido'
    ];
    return $labels[$estado] ?? ucfirst($estado);
}

/**
 * Obtener clase CSS para badge de transacción
 */
function getTransactionStatusBadge($estado) {
    $badges = [
        'pendiente' => 'badge-warning',
        'pagado' => 'badge-success',
        'vencido' => 'badge-danger'
    ];
    return $badges[$estado] ?? 'badge-default';
}

/**
 * Obtener etiqueta legible del estado de transacción
 */
function getTransactionStatusLabel($estado) {
    $labels = [
        'pendiente' => 'Pendiente',
        'pagado' => 'Pagado',
        'vencido' => 'Vencido'
    ];
    return $labels[$estado] ?? ucfirst($estado);
}

/**
 * Sanitizar input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generar CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar CSRF token
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Respuesta JSON para APIs
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtener la página actual para marcar el sidebar activo
 */
function isActivePage($page) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    return $currentPage === $page ? 'active' : '';
}

/**
 * Flash messages
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Obtener valor de configuración desde crm_configuraciones
 */
function getCfg($pdo, $clave, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = ?");
        $stmt->execute([$clave]);
        $valor = $stmt->fetchColumn();
        return $valor !== false ? $valor : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Retorna el src del logo para usar en correos HTML.
 * Prioridad:
 *   1. URL externa válida (no localhost) pasada como parámetro
 *   2. URL externa válida (no localhost) guardada en crm_configuraciones.notif_logo_url
 *   3. URL del sitio web derivada de APP_URL (funciona en producción sin base64)
 *   4. Base64 embebido del archivo local (fallback para localhost/desarrollo)
 */
function getLogoEmailSrc(PDO $pdo, string $logoUrlParam = ''): string {
    // URL es externa válida y no es una ruta heredada/rota
    $isExternal = function(string $url): bool {
        $url = trim($url);
        if (!$url) return false;
        if (stripos($url, 'localhost')    !== false) return false;
        if (stripos($url, '127.0.0.1')   !== false) return false;
        if (stripos($url, '::1')          !== false) return false;
        if (stripos($url, '/wp-content/') !== false) return false; // URLs de WordPress heredadas
        if (stripos($url, '/wp-admin/')   !== false) return false;
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    };

    // 1. Parámetro directo
    if ($isExternal($logoUrlParam)) return trim($logoUrlParam);

    // 2. Configuración en BD (auto-limpia URLs rotas de WordPress)
    try {
        $stmt = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = 'notif_logo_url'");
        $stmt->execute();
        $dbUrl = (string)($stmt->fetchColumn() ?: '');
        if (stripos($dbUrl, '/wp-content/') !== false || stripos($dbUrl, '/wp-admin/') !== false) {
            // Limpiar URL obsoleta de WordPress
            $pdo->prepare("UPDATE crm_configuraciones SET valor = '' WHERE clave = 'notif_logo_url'")->execute();
            $dbUrl = '';
        }
        if ($isExternal($dbUrl)) return trim($dbUrl);
    } catch (PDOException $e) {}

    // 3. Derivar URL del sitio web desde APP_URL
    //    APP_URL en producción = https://quantundigital.com/crm
    //    El logo del sitio está en  https://quantundigital.com/assets/quantun-logo.png
    $siteBase = rtrim(preg_replace('#/crm/?$#i', '', rtrim(defined('APP_URL') ? APP_URL : '', '/')), '/');
    $siteLogoUrl = $siteBase . '/assets/quantun-logo.png';
    if ($isExternal($siteLogoUrl)) return $siteLogoUrl;

    // 4. Base64 embebido (fallback para localhost / desarrollo)
    foreach (['logo_quantun_digital_negro.png', 'logo_quantun_digital_blanco.png'] as $f) {
        $path = BASE_PATH . '/Assets/' . $f;
        if (file_exists($path)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
        }
    }

    return '';
}

/**
 * Retorna la URL pública del logo del sitio derivada de APP_URL.
 * Útil para mostrar previsualizaciones en el CRM.
 */
function getSiteLogoUrl(): string {
    $siteBase = rtrim(preg_replace('#/crm/?$#i', '', rtrim(defined('APP_URL') ? APP_URL : '', '/')), '/');
    return $siteBase . '/assets/quantun-logo.png';
}
