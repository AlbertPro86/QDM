<?php
/**
 * CRM QUANTUN Digital - Configuración General
 * Carga variables de entorno y define constantes globales.
 */

// Cargar variables de entorno desde .env
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || empty($line)) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Cargar .env
loadEnv(__DIR__ . '/../.env');

// Función helper para obtener variable de entorno
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    // Convertir valores string a tipos PHP
    switch (strtolower($value)) {
        case 'true': return true;
        case 'false': return false;
        case 'null': return null;
    }
    return $value;
}

// ─── Constantes de la Aplicación ───
define('APP_NAME', env('APP_NAME', 'CRM QUANTUN Digital'));
define('APP_URL', env('APP_URL', 'http://localhost/CRM-QUANTUN-Digital'));
define('APP_DEBUG', env('APP_DEBUG', false));
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', APP_URL);
define('APP_VERSION', max(
    filemtime(BASE_PATH . '/css/styles.css'),
    filemtime(BASE_PATH . '/js/leads.js'),
    filemtime(BASE_PATH . '/js/clientes.js'),
    filemtime(BASE_PATH . '/js/finanzas.js'),
    filemtime(BASE_PATH . '/js/plantillas_factura.js')
));

// ─── Configuración de Uploads ───
define('MAX_UPLOAD_SIZE', (int) env('MAX_UPLOAD_SIZE', 10485760)); // 10MB
define('UPLOAD_DIR', env('UPLOAD_DIR', 'uploads/facturas/'));

// ─── Configuración de Sesión ───
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── reCAPTCHA v3 ───
define('RECAPTCHA_SITE_KEY',   env('RECAPTCHA_SITE_KEY',   '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'));
define('RECAPTCHA_SECRET_KEY', env('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'));
define('RECAPTCHA_MIN_SCORE',  (float) env('RECAPTCHA_MIN_SCORE', 0.5));

// ─── Zona Horaria ───
date_default_timezone_set('America/Bogota');

// ─── Manejo de Errores ───
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
