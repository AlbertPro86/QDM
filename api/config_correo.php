<?php
/**
 * CRM QUANTUN Digital — Guardar / leer config SMTP
 * Guarda en crm_configuraciones (BD) y también actualiza .env como respaldo.
 * GET  → devuelve config actual (sin password)
 * POST → guarda o prueba
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo     = db();
$envPath = BASE_PATH . '/.env';

// ── Helpers ────────────────────────────────────────────────────────────────────

function getCfg(PDO $pdo, string $clave, string $default = ''): string {
    $st = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = ?");
    $st->execute([$clave]);
    $v = $st->fetchColumn();
    return ($v !== false && $v !== null) ? (string)$v : $default;
}

function setCfg(PDO $pdo, string $clave, string $valor): void {
    $st = $pdo->prepare("INSERT INTO crm_configuraciones (clave, valor)
                          VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $st->execute([$clave, $valor]);
}

function leerEnv(string $path): array {
    $data = [];
    if (!file_exists($path)) return $data;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $data[trim($k)] = trim($v);
    }
    return $data;
}

function escribirEnv(string $path, array $nuevos): void {
    $lineas    = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
    $escritos  = [];
    $resultado = [];
    foreach ($lineas as $linea) {
        $l = trim($linea);
        if (!str_starts_with($l, '#') && str_contains($l, '=')) {
            [$k] = explode('=', $l, 2);
            $k   = trim($k);
            if (array_key_exists($k, $nuevos)) {
                $resultado[] = "$k={$nuevos[$k]}";
                $escritos[]  = $k;
                continue;
            }
        }
        $resultado[] = $linea;
    }
    foreach ($nuevos as $k => $v) {
        if (!in_array($k, $escritos)) $resultado[] = "$k=$v";
    }
    @file_put_contents($path, implode("\n", $resultado) . "\n");
}

/** Carga config SMTP desde BD (preferido) y rellena huecos con .env */
function cargarSmtpConfig(PDO $pdo, string $envPath): array {
    $env = leerEnv($envPath);
    return [
        'host'         => getCfg($pdo, 'smtp_host',        $env['MAIL_HOST']         ?? 'smtp.zoho.com'),
        'port'         => getCfg($pdo, 'smtp_port',        $env['MAIL_PORT']         ?? '587'),
        'encryption'   => getCfg($pdo, 'smtp_encryption',  $env['MAIL_ENCRYPTION']   ?? 'tls'),
        'username'     => getCfg($pdo, 'smtp_username',     $env['MAIL_USERNAME']     ?? ''),
        'from_address' => getCfg($pdo, 'smtp_from_address', $env['MAIL_FROM_ADDRESS'] ?? ''),
        'from_name'    => getCfg($pdo, 'smtp_from_name',   $env['MAIL_FROM_NAME']    ?? 'QUANTUN Digital'),
        'has_password' => (getCfg($pdo, 'smtp_password', $env['MAIL_PASSWORD'] ?? '') !== ''),
    ];
}

/** Aplica config al entorno PHP actual para que Mailer la use de inmediato */
function aplicarEnvSmtp(array $cfg, string $pass): void {
    $map = [
        'MAIL_HOST'         => $cfg['host'],
        'MAIL_PORT'         => $cfg['port'],
        'MAIL_ENCRYPTION'   => $cfg['encryption'],
        'MAIL_USERNAME'     => $cfg['username'],
        'MAIL_FROM_ADDRESS' => $cfg['from_address'] ?: $cfg['username'],
        'MAIL_FROM_NAME'    => $cfg['from_name'],
        'MAIL_PASSWORD'     => $pass,
    ];
    foreach ($map as $k => $v) {
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}

// ── GET: devolver config actual ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cfg = cargarSmtpConfig($pdo, $envPath);
    jsonResponse(['success' => true, 'data' => [
        'host'         => $cfg['host'],
        'port'         => $cfg['port'],
        'encryption'   => $cfg['encryption'],
        'username'     => $cfg['username'],
        'from_address' => $cfg['from_address'],
        'from_name'    => $cfg['from_name'],
        'configured'   => $cfg['has_password'],
    ]]);
}

// ── POST: guardar o probar ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $input['accion'] ?? 'guardar';

    $host       = trim($input['host']       ?? 'smtp.zoho.com');
    $port       = trim($input['port']       ?? '587');
    $encryption = trim($input['encryption'] ?? 'tls');
    $username   = trim($input['username']   ?? '');
    $password   = $input['password']        ?? '';
    $from_name  = trim($input['from_name']  ?? 'QUANTUN Digital');
    $from_addr  = trim($input['from_address'] ?? $username);

    // ── Probar ────────────────────────────────────────────────────────────────
    if ($accion === 'probar') {
        if (!$password) {
            jsonResponse(['error' => 'Debes ingresar la contraseña para probar'], 400);
        }
        $cfg = compact('host', 'port', 'encryption', 'username', 'from_name', 'from_address');
        $cfg['from_address'] = $from_addr;
        aplicarEnvSmtp($cfg, $password);

        $dest = filter_var($username, FILTER_VALIDATE_EMAIL) ? $username : '';
        if (!$dest) jsonResponse(['error' => 'Correo de usuario inválido'], 400);

        $mailer = new Mailer();
        $r = $mailer->send($dest, 'Prueba de correo — CRM QUANTUN', '
            <div style="font-family:sans-serif;max-width:500px;margin:0 auto;padding:32px">
                <h2 style="color:#0f172a">Correo de prueba</h2>
                <p style="color:#475569">Si recibes este mensaje, la configuración SMTP está correcta.</p>
                <p style="color:#94a3b8;font-size:12px">CRM QUANTUN Digital</p>
            </div>
        ');
        if ($r['ok']) {
            jsonResponse(['success' => true, 'message' => "Correo de prueba enviado a $dest"]);
        } else {
            jsonResponse(['error' => $r['error']], 500);
        }
    }

    // ── Guardar ───────────────────────────────────────────────────────────────
    // 1. Guardar en BD
    setCfg($pdo, 'smtp_host',        $host);
    setCfg($pdo, 'smtp_port',        $port);
    setCfg($pdo, 'smtp_encryption',  $encryption);
    setCfg($pdo, 'smtp_username',    $username);
    setCfg($pdo, 'smtp_from_address', $from_addr);
    setCfg($pdo, 'smtp_from_name',   $from_name);
    if ($password !== '') {
        setCfg($pdo, 'smtp_password', $password);
    }

    // 2. Intentar también escribir en .env (respaldo para cron CLI)
    $envNuevos = [
        'MAIL_HOST'         => $host,
        'MAIL_PORT'         => $port,
        'MAIL_ENCRYPTION'   => $encryption,
        'MAIL_USERNAME'     => $username,
        'MAIL_FROM_ADDRESS' => $from_addr ?: $username,
        'MAIL_FROM_NAME'    => $from_name,
    ];
    if ($password !== '') $envNuevos['MAIL_PASSWORD'] = $password;
    escribirEnv($envPath, $envNuevos);

    jsonResponse(['success' => true, 'message' => 'Configuración SMTP guardada correctamente']);
}

jsonResponse(['error' => 'Método no permitido'], 405);
