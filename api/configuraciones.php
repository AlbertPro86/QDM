<?php
/**
 * CRM QUANTUN Digital — API de Configuraciones del sistema
 * GET  → devuelve todas las configuraciones
 * POST → guarda una o varias configuraciones
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();

// Auto-migración
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_configuraciones (
            clave      VARCHAR(80)  NOT NULL PRIMARY KEY,
            valor      TEXT         DEFAULT NULL,
            updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

// Valores por defecto
$defaults = [
    'notif_activa'               => '1',
    'notif_email'                => env('ADMIN_EMAIL', 'contacto@quantundigital.com'),
    'notif_whatsapp'             => '',
    'notif_hora'                 => '08:00',
    'notif_dias'                 => json_encode([1,2,3,4,5]),
    'notif_incluir_tareas'       => '1',
    'notif_incluir_renovaciones' => '1',
    'notif_logo_url'             => '',
];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $stmt  = $pdo->query("SELECT clave, valor FROM crm_configuraciones");
        $rows  = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $cfg   = array_merge($defaults, $rows);
        // Decodificar JSON arrays
        if (isset($cfg['notif_dias']) && is_string($cfg['notif_dias'])) {
            $cfg['notif_dias'] = json_decode($cfg['notif_dias'], true);
        }
        jsonResponse(['success' => true, 'data' => $cfg]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(['error' => 'Datos inválidos'], 400);

        $stmt = $pdo->prepare("
            INSERT INTO crm_configuraciones (clave, valor)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
        ");

        foreach ($input as $clave => $valor) {
            if (!array_key_exists($clave, $defaults)) continue; // solo claves conocidas
            $valorStr = is_array($valor) ? json_encode($valor) : (string)$valor;
            $stmt->execute([$clave, $valorStr]);
        }

        // Si cambia el email, actualizar .env también
        if (isset($input['notif_email'])) {
            $envPath = BASE_PATH . '/.env';
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/^ADMIN_EMAIL=.*/m', 'ADMIN_EMAIL=' . trim($input['notif_email']), $envContent);
            if (!str_contains($envContent, 'ADMIN_EMAIL=')) {
                $envContent .= "\nADMIN_EMAIL=" . trim($input['notif_email']);
            }
            file_put_contents($envPath, $envContent);
        }

        jsonResponse(['success' => true, 'message' => 'Configuración guardada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
