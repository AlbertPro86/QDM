<?php
/**
 * CRM QUANTUN Digital — Guardar / leer config SMTP
 * GET  → devuelve config actual (sin password)
 * POST → guarda en .env y devuelve resultado
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$envPath = BASE_PATH . '/.env';

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
    // Verificar permisos
    if (file_exists($path) && !is_writable($path)) {
        throw new RuntimeException('El archivo .env no tiene permisos de escritura. En Hostinger: Administrador de archivos → clic derecho en .env → Permisos → 644.');
    }
    if (!file_exists($path) && !is_writable(dirname($path))) {
        throw new RuntimeException('No se puede crear el archivo .env. Verifica los permisos del directorio.');
    }

    $lineas = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
    $escritos = [];
    $resultado = [];
    foreach ($lineas as $linea) {
        $l = trim($linea);
        if (!str_starts_with($l, '#') && str_contains($l, '=')) {
            [$k] = explode('=', $l, 2);
            $k = trim($k);
            if (array_key_exists($k, $nuevos)) {
                $resultado[] = "$k={$nuevos[$k]}";
                $escritos[] = $k;
                continue;
            }
        }
        $resultado[] = $linea;
    }
    // Agregar claves nuevas que no existían
    foreach ($nuevos as $k => $v) {
        if (!in_array($k, $escritos)) $resultado[] = "$k=$v";
    }
    $bytes = file_put_contents($path, implode("\n", $resultado) . "\n");
    if ($bytes === false) {
        throw new RuntimeException('Error al escribir el archivo .env. Verifica permisos del servidor.');
    }
}

// GET: devolver config actual
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $env = leerEnv($envPath);
    jsonResponse(['success' => true, 'data' => [
        'host'         => $env['MAIL_HOST']         ?? 'smtp.gmail.com',
        'port'         => $env['MAIL_PORT']         ?? '587',
        'encryption'   => $env['MAIL_ENCRYPTION']   ?? 'tls',
        'username'     => $env['MAIL_USERNAME']     ?? '',
        'from_address' => $env['MAIL_FROM_ADDRESS'] ?? '',
        'from_name'    => $env['MAIL_FROM_NAME']    ?? 'QUANTUN Digital',
        'configured'   => !empty($env['MAIL_PASSWORD']) && $env['MAIL_PASSWORD'] !== 'tu_app_password',
    ]]);
}

// POST: guardar o probar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? 'guardar';

    if ($accion === 'probar') {
        // Probar con la config enviada (sin guardar)
        $_ENV['MAIL_HOST']         = $input['host']         ?? '';
        $_ENV['MAIL_PORT']         = $input['port']         ?? '587';
        $_ENV['MAIL_ENCRYPTION']   = $input['encryption']   ?? 'tls';
        $_ENV['MAIL_USERNAME']     = $input['username']     ?? '';
        $_ENV['MAIL_PASSWORD']     = $input['password']     ?? '';
        $_ENV['MAIL_FROM_ADDRESS'] = $input['from_address'] ?? $input['username'] ?? '';
        $_ENV['MAIL_FROM_NAME']    = $input['from_name']    ?? 'QUANTUN Digital';
        putenv('MAIL_HOST='       . $_ENV['MAIL_HOST']);
        putenv('MAIL_PORT='       . $_ENV['MAIL_PORT']);
        putenv('MAIL_ENCRYPTION=' . $_ENV['MAIL_ENCRYPTION']);
        putenv('MAIL_USERNAME='   . $_ENV['MAIL_USERNAME']);
        putenv('MAIL_PASSWORD='   . $_ENV['MAIL_PASSWORD']);
        putenv('MAIL_FROM_ADDRESS='. $_ENV['MAIL_FROM_ADDRESS']);
        putenv('MAIL_FROM_NAME='  . $_ENV['MAIL_FROM_NAME']);

        $dest = trim($input['test_email'] ?? $input['username'] ?? '');
        if (!filter_var($dest, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Email de prueba inválido'], 400);
        }
        $mailer = new Mailer();
        $r = $mailer->send($dest, 'Prueba de correo — CRM QUANTUN', '
            <div style="font-family:sans-serif;max-width:500px;margin:0 auto;padding:32px">
                <h2 style="color:#0f172a">✅ Correo de prueba</h2>
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

    // Guardar
    $nuevos = [
        'MAIL_HOST'         => $input['host']         ?? 'smtp.gmail.com',
        'MAIL_PORT'         => $input['port']         ?? '587',
        'MAIL_ENCRYPTION'   => $input['encryption']   ?? 'tls',
        'MAIL_USERNAME'     => $input['username']     ?? '',
        'MAIL_FROM_ADDRESS' => $input['from_address'] ?? $input['username'] ?? '',
        'MAIL_FROM_NAME'    => $input['from_name']    ?? 'QUANTUN Digital',
    ];
    if (!empty($input['password'])) {
        $nuevos['MAIL_PASSWORD'] = $input['password'];
    }
    try {
        escribirEnv($envPath, $nuevos);
        jsonResponse(['success' => true, 'message' => 'Configuración guardada', 'env_path' => $envPath]);
    } catch (RuntimeException $e) {
        jsonResponse(['error' => $e->getMessage(), 'env_path' => $envPath], 500);
    }
}

jsonResponse(['error' => 'Método no permitido'], 405);
