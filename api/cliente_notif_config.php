<?php
/**
 * CRM QUANTUN Digital — API Configuración de Notificaciones por Cliente
 * GET  ?cliente_id=X  → obtiene configuración
 * POST {cliente_id, activa, r1_dias, r1_hora, r2_dias, r2_hora, r3_dias, r3_hora,
 *       asunto_personalizado, mensaje_personalizado}
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();

// Auto-migración: crear tabla base si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_cliente_notif_config (
            cliente_id              INT          NOT NULL PRIMARY KEY,
            activa                  TINYINT(1)   NOT NULL DEFAULT 1,
            dias_antes              INT          NOT NULL DEFAULT 15,
            hora_envio              TIME         NOT NULL DEFAULT '08:00:00',
            asunto_personalizado    VARCHAR(255) NOT NULL DEFAULT '',
            mensaje_personalizado   TEXT         NOT NULL DEFAULT '',
            updated_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

// Auto-migraciones: columnas de 3 recordatorios
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r1_dias INT NOT NULL DEFAULT 15"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r1_hora TIME NOT NULL DEFAULT '08:00:00'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r2_dias INT NOT NULL DEFAULT 7"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r2_hora TIME NOT NULL DEFAULT '08:00:00'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r3_dias INT NOT NULL DEFAULT 2"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r3_hora TIME NOT NULL DEFAULT '08:00:00'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r1_plantilla_id INT NULL DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r2_plantilla_id INT NULL DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r3_plantilla_id INT NULL DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r1_fecha DATETIME NULL DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r2_fecha DATETIME NULL DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_cliente_notif_config ADD COLUMN r3_fecha DATETIME NULL DEFAULT NULL"); } catch (PDOException $e) {}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $cid = intval($_GET['cliente_id'] ?? 0);
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);

        $s = $pdo->prepare("SELECT * FROM crm_cliente_notif_config WHERE cliente_id = ?");
        $s->execute([$cid]);
        $row = $s->fetch();

        if (!$row) {
            $row = [
                'cliente_id'            => $cid,
                'activa'                => 1,
                'dias_antes'            => 15,
                'hora_envio'            => '08:00:00',
                'r1_dias'               => 15,
                'r1_hora'               => '08:00:00',
                'r2_dias'               => 7,
                'r2_hora'               => '08:00:00',
                'r3_dias'               => 2,
                'r3_hora'               => '08:00:00',
                'r1_plantilla_id'       => null,
                'r2_plantilla_id'       => null,
                'r3_plantilla_id'       => null,
                'r1_fecha'              => null,
                'r2_fecha'              => null,
                'r3_fecha'              => null,
                'asunto_personalizado'  => '',
                'mensaje_personalizado' => '',
            ];
        }
        jsonResponse(['success' => true, 'data' => $row]);
        break;

    case 'POST':
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $cid = intval($in['cliente_id'] ?? 0);
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);

        // Ensure row exists with defaults before partial update
        $pdo->prepare("
            INSERT IGNORE INTO crm_cliente_notif_config (cliente_id) VALUES (?)
        ")->execute([$cid]);

        // Build partial UPDATE — only fields explicitly present in payload
        $allowed = [
            'activa', 'dias_antes', 'hora_envio',
            'r1_dias', 'r1_hora', 'r2_dias', 'r2_hora', 'r3_dias', 'r3_hora',
            'r1_plantilla_id', 'r2_plantilla_id', 'r3_plantilla_id',
            'r1_fecha', 'r2_fecha', 'r3_fecha',
            'asunto_personalizado', 'mensaje_personalizado',
        ];
        $setClauses = [];
        $vals       = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $in)) continue;
            $val = $in[$field];
            // Sanitize per field type
            if ($field === 'activa') {
                $val = (int)(bool)$val;
            } elseif (in_array($field, ['dias_antes','r1_dias','r2_dias','r3_dias'])) {
                $val = max(1, intval($val));
            } elseif (in_array($field, ['r1_plantilla_id','r2_plantilla_id','r3_plantilla_id'])) {
                $val = !empty($val) ? (int)$val : null;
            } elseif (in_array($field, ['r1_fecha','r2_fecha','r3_fecha'])) {
                $val = !empty($val) ? $val : null;
            } elseif (in_array($field, ['asunto_personalizado','mensaje_personalizado'])) {
                $val = trim($val);
            }
            // Keep dias_antes / hora_envio in sync with r1 (backwards compat)
            if ($field === 'r1_dias')  { $setClauses[] = 'dias_antes = ?'; $vals[] = $val; }
            if ($field === 'r1_hora')  { $setClauses[] = 'hora_envio = ?'; $vals[] = $val; }
            $setClauses[] = "$field = ?";
            $vals[]       = $val;
        }

        if (empty($setClauses)) jsonResponse(['error' => 'No hay campos para actualizar'], 400);

        $setClauses[] = 'updated_at = NOW()';
        $vals[]       = $cid;
        $pdo->prepare("UPDATE crm_cliente_notif_config SET " . implode(', ', $setClauses) . " WHERE cliente_id = ?")
            ->execute($vals);

        jsonResponse(['success' => true, 'message' => 'Configuración guardada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
