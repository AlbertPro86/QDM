<?php
/**
 * CRM QUANTUN Digital — API Email Marketing
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$pdo = db();

// ── Auto-create tables ─────────────────────────────────────────────────────
$pdo->exec("
CREATE TABLE IF NOT EXISTS email_plantillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    asunto VARCHAR(300) NOT NULL,
    cuerpo LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS email_campanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    asunto VARCHAR(300) NOT NULL,
    cuerpo LONGTEXT NOT NULL,
    plantilla_id INT NULL,
    filtro_estado VARCHAR(50) DEFAULT 'todos',
    filtro_servicio VARCHAR(200) DEFAULT '',
    clientes_ids TEXT NULL,
    total INT DEFAULT 0,
    enviados INT DEFAULT 0,
    fallidos INT DEFAULT 0,
    estado ENUM('borrador','enviando','enviada','error') DEFAULT 'borrador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enviada_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
try { $pdo->exec("ALTER TABLE email_campanas ADD COLUMN clientes_ids TEXT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE email_campanas ADD COLUMN programada_at DATETIME NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE email_campanas MODIFY COLUMN estado ENUM('borrador','enviando','enviada','error','programada') DEFAULT 'borrador'"); } catch(PDOException $e){}

$pdo->exec("
CREATE TABLE IF NOT EXISTS email_envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campana_id INT NOT NULL,
    cliente_id INT NOT NULL,
    email VARCHAR(200) NOT NULL,
    nombre_dest VARCHAR(200) DEFAULT '',
    estado ENUM('pendiente','enviado','fallido') DEFAULT 'pendiente',
    error_msg TEXT NULL,
    enviado_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$method = $_SERVER['REQUEST_METHOD'];

// ── GET ────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $resource = $_GET['resource'] ?? '';

    // GET plantillas
    if ($resource === 'plantillas') {
        $rows = $pdo->query("SELECT * FROM email_plantillas ORDER BY updated_at DESC")->fetchAll();
        jsonResponse(['ok' => true, 'data' => $rows]);
    }

    // GET campanas
    if ($resource === 'campanas') {
        // Disparar ejecución de campañas programadas pendientes en background
        $pending = (int)$pdo->query(
            "SELECT COUNT(*) FROM email_campanas WHERE estado='programada' AND programada_at IS NOT NULL AND programada_at <= NOW()"
        )->fetchColumn();
        if ($pending > 0) {
            $phpBin = PHP_BINARY;
            $script = realpath(__DIR__ . '/ejecutar_campanas_email.php');
            if ($phpBin && $script) {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    popen('start /B "" "' . $phpBin . '" "' . $script . '"', 'r');
                } else {
                    exec('"' . $phpBin . '" "' . $script . '" > /dev/null 2>&1 &');
                }
            }
        }

        $rows = $pdo->query("
            SELECT c.*,
                (SELECT COUNT(*) FROM email_envios e WHERE e.campana_id = c.id) AS total_envios
            FROM email_campanas c
            ORDER BY c.created_at DESC
        ")->fetchAll();
        jsonResponse(['ok' => true, 'data' => $rows]);
    }

    // GET destinatarios (with optional filters)
    if ($resource === 'destinatarios') {
        $filtroEstado   = $_GET['filtro_estado']   ?? 'todos';
        $filtroServicio = $_GET['filtro_servicio'] ?? '';

        $where  = ["c.email_facturacion IS NOT NULL", "c.email_facturacion != ''"];
        $params = [];

        if ($filtroEstado !== 'todos') {
            $where[]  = "c.estado = ?";
            $params[] = $filtroEstado;
        }

        $joins = '';
        if ($filtroServicio !== '') {
            $joins    = "JOIN cliente_servicios cs ON cs.cliente_id = c.id
                         JOIN sub_servicios ss ON ss.id = cs.sub_servicio_id";
            $where[]  = "ss.nombre LIKE ?";
            $params[] = '%' . $filtroServicio . '%';
        }

        $whereSQL = implode(' AND ', $where);
        $sql = "SELECT DISTINCT c.id, c.nombre_comercial, c.persona_contacto, c.email_facturacion
                FROM clientes c
                $joins
                WHERE $whereSQL
                ORDER BY c.nombre_comercial";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        jsonResponse(['ok' => true, 'data' => $rows, 'total' => count($rows)]);
    }

    // GET historial for a campaign
    if ($resource === 'historial') {
        $campanaId = (int) ($_GET['campana_id'] ?? 0);
        if (!$campanaId) {
            jsonResponse(['error' => 'campana_id requerido'], 422);
        }
        $stmt = $pdo->prepare("
            SELECT e.*, c.nombre_comercial
            FROM email_envios e
            LEFT JOIN clientes c ON c.id = e.cliente_id
            WHERE e.campana_id = ?
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$campanaId]);
        $rows = $stmt->fetchAll();
        jsonResponse(['ok' => true, 'data' => $rows]);
    }

    jsonResponse(['error' => 'Recurso no encontrado'], 404);
}

// ── POST ───────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    // save_plantilla
    if ($action === 'save_plantilla') {
        $id     = (int) ($body['id'] ?? 0);
        $nombre = trim($body['nombre'] ?? '');
        $asunto = trim($body['asunto'] ?? '');
        $cuerpo = trim($body['cuerpo'] ?? '');

        if (!$nombre || !$asunto || !$cuerpo) {
            jsonResponse(['error' => 'Nombre, asunto y cuerpo son requeridos'], 422);
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE email_plantillas SET nombre=?, asunto=?, cuerpo=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$nombre, $asunto, $cuerpo, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO email_plantillas (nombre, asunto, cuerpo) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $asunto, $cuerpo]);
            $id = (int) $pdo->lastInsertId();
        }
        jsonResponse(['ok' => true, 'id' => $id]);
    }

    // save_campana
    if ($action === 'save_campana') {
        $id             = (int) ($body['id'] ?? 0);
        $nombre         = trim($body['nombre'] ?? '');
        $asunto         = trim($body['asunto'] ?? '');
        $cuerpo         = trim($body['cuerpo'] ?? '');
        $filtroEstado   = trim($body['filtro_estado']   ?? 'todos');
        $filtroServicio = trim($body['filtro_servicio'] ?? '');
        $plantillaId    = ($body['plantilla_id'] ?? null) ? (int) $body['plantilla_id'] : null;
        // clientes_ids: JSON array of IDs or null for segment mode
        $clientesIds    = isset($body['clientes_ids']) && is_array($body['clientes_ids']) && count($body['clientes_ids'])
                          ? json_encode(array_map('intval', $body['clientes_ids']))
                          : null;
        $programadaAt   = !empty($body['programada_at']) ? $body['programada_at'] : null;
        $estadoFinal    = $programadaAt ? 'programada' : 'borrador';

        if (!$nombre || !$asunto || !$cuerpo) {
            jsonResponse(['error' => 'Nombre, asunto y cuerpo son requeridos'], 422);
        }

        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE email_campanas
                SET nombre=?, asunto=?, cuerpo=?, filtro_estado=?, filtro_servicio=?, plantilla_id=?, clientes_ids=?, programada_at=?, estado=?
                WHERE id=? AND estado IN ('borrador','programada','error')
            ");
            $stmt->execute([$nombre, $asunto, $cuerpo, $filtroEstado, $filtroServicio, $plantillaId, $clientesIds, $programadaAt, $estadoFinal, $id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO email_campanas (nombre, asunto, cuerpo, filtro_estado, filtro_servicio, plantilla_id, clientes_ids, programada_at, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $asunto, $cuerpo, $filtroEstado, $filtroServicio, $plantillaId, $clientesIds, $programadaAt, $estadoFinal]);
            $id = (int) $pdo->lastInsertId();
        }
        jsonResponse(['ok' => true, 'id' => $id, 'estado' => $estadoFinal]);
    }

    // enviar
    if ($action === 'enviar') {
        $campanaId = (int) ($body['campana_id'] ?? 0);
        if (!$campanaId) {
            jsonResponse(['error' => 'campana_id requerido'], 422);
        }

        $campana = $pdo->prepare("SELECT * FROM email_campanas WHERE id = ?");
        $campana->execute([$campanaId]);
        $campana = $campana->fetch();

        if (!$campana) {
            jsonResponse(['error' => 'Campaña no encontrada'], 404);
        }
        if ($campana['estado'] === 'enviada') {
            jsonResponse(['error' => 'Esta campaña ya fue enviada'], 422);
        }

        // Mark as sending
        $pdo->prepare("UPDATE email_campanas SET estado='enviando' WHERE id=?")->execute([$campanaId]);

        // Build recipients query
        // Accept clientes_ids from request body as authoritative override (fixes race-condition with DB save)
        if (isset($body['clientes_ids']) && is_array($body['clientes_ids']) && count($body['clientes_ids'])) {
            $clientesIds = array_map('intval', $body['clientes_ids']);
            // Persist to DB if not already stored
            $pdo->prepare("UPDATE email_campanas SET clientes_ids=? WHERE id=?")
                ->execute([json_encode($clientesIds), $campanaId]);
        } else {
            $clientesIds = !empty($campana['clientes_ids']) ? json_decode($campana['clientes_ids'], true) : null;
        }
        $filtroEstado   = $campana['filtro_estado']   ?? 'todos';
        $filtroServicio = $campana['filtro_servicio'] ?? '';

        if ($clientesIds && count($clientesIds)) {
            // Manual selection mode — use exact IDs
            $ph2  = implode(',', array_fill(0, count($clientesIds), '?'));
            $sql  = "SELECT c.id, c.nombre_comercial, c.persona_contacto, c.email_facturacion
                     FROM clientes c
                     WHERE c.id IN ($ph2)
                       AND c.email_facturacion IS NOT NULL AND c.email_facturacion != ''
                     ORDER BY c.nombre_comercial";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($clientesIds);
            $recipients = $stmt->fetchAll();
        } else {
            // Segment mode
            $where  = ["c.email_facturacion IS NOT NULL", "c.email_facturacion != ''"];
            $params = [];

            if ($filtroEstado !== 'todos') {
                $where[]  = "c.estado = ?";
                $params[] = $filtroEstado;
            }

            $joins = '';
            if ($filtroServicio !== '') {
                $joins    = "JOIN cliente_servicios cs ON cs.cliente_id = c.id
                             JOIN sub_servicios ss ON ss.id = cs.sub_servicio_id";
                $where[]  = "ss.nombre LIKE ?";
                $params[] = '%' . $filtroServicio . '%';
            }

            $whereSQL = implode(' AND ', $where);
            $sql = "SELECT DISTINCT c.id, c.nombre_comercial, c.persona_contacto, c.email_facturacion
                    FROM clientes c $joins WHERE $whereSQL";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $recipients = $stmt->fetchAll();
        }

        if (empty($recipients)) {
            $pdo->prepare("UPDATE email_campanas SET estado='borrador' WHERE id=?")->execute([$campanaId]);
            jsonResponse(['error' => 'No hay destinatarios para esta campaña'], 422);
        }

        // Delete any previous pending envios for this campaign
        $pdo->prepare("DELETE FROM email_envios WHERE campana_id = ? AND estado = 'pendiente'")->execute([$campanaId]);

        // Insert envio records
        $insertEnvio = $pdo->prepare("
            INSERT INTO email_envios (campana_id, cliente_id, email, nombre_dest, estado)
            VALUES (?, ?, ?, ?, 'pendiente')
        ");
        foreach ($recipients as $r) {
            $insertEnvio->execute([
                $campanaId,
                $r['id'],
                $r['email_facturacion'],
                $r['nombre_comercial']
            ]);
        }

        // Send emails
        set_time_limit(120);

        $mailer   = new Mailer();
        $enviados = 0;
        $fallidos = 0;
        $errors   = [];

        $updateEnvio = $pdo->prepare("
            UPDATE email_envios
            SET estado=?, error_msg=?, enviado_at=NOW()
            WHERE campana_id=? AND cliente_id=?
        ");

        foreach ($recipients as $r) {
            $email    = trim($r['email_facturacion']);
            $nombre   = $r['nombre_comercial'];
            $contacto = $r['persona_contacto'] ?? '';

            // Replace template variables
            $htmlBody = str_replace(
                ['{{nombre_comercial}}', '{{persona_contacto}}', '{{email}}'],
                [htmlspecialchars($nombre, ENT_QUOTES), htmlspecialchars($contacto, ENT_QUOTES), htmlspecialchars($email, ENT_QUOTES)],
                $campana['cuerpo']
            );
            $asunto = str_replace(
                ['{{nombre_comercial}}', '{{persona_contacto}}', '{{email}}'],
                [$nombre, $contacto, $email],
                $campana['asunto']
            );

            $result = $mailer->send($email, $asunto, $htmlBody);

            if ($result['ok']) {
                $enviados++;
                $updateEnvio->execute(['enviado', null, $campanaId, $r['id']]);
            } else {
                $fallidos++;
                $errMsg = $result['error'] ?? 'Error desconocido';
                $errors[] = ['email' => $email, 'error' => $errMsg];
                $updateEnvio->execute(['fallido', $errMsg, $campanaId, $r['id']]);
            }
        }

        // Update campaign stats
        $finalEstado = ($fallidos > 0 && $enviados === 0) ? 'error' : 'enviada';
        $pdo->prepare("
            UPDATE email_campanas
            SET estado=?, total=?, enviados=?, fallidos=?, enviada_at=NOW()
            WHERE id=?
        ")->execute([$finalEstado, count($recipients), $enviados, $fallidos, $campanaId]);

        jsonResponse([
            'ok'       => true,
            'enviados' => $enviados,
            'fallidos' => $fallidos,
            'total'    => count($recipients),
            'errors'   => $errors
        ]);
    }

    // change_estado
    if ($action === 'change_estado') {
        $campanaId  = (int) ($body['campana_id'] ?? 0);
        $nuevoEstado = trim($body['estado'] ?? '');
        $allowed    = ['borrador', 'programada'];
        if (!$campanaId || !in_array($nuevoEstado, $allowed)) {
            jsonResponse(['error' => 'Parámetros inválidos'], 422);
        }
        // Cancelar programación: limpiar programada_at al volver a borrador
        $programadaAt = ($nuevoEstado === 'programada') ? ($body['programada_at'] ?? null) : null;
        $stmt = $pdo->prepare("
            UPDATE email_campanas
            SET estado=?, programada_at=?
            WHERE id=? AND estado IN ('borrador','programada','error')
        ");
        $stmt->execute([$nuevoEstado, $programadaAt, $campanaId]);
        if ($stmt->rowCount() === 0) {
            jsonResponse(['error' => 'No se pudo cambiar el estado'], 422);
        }
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['error' => 'Acción no reconocida'], 400);
}

// ── DELETE ─────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $resource = $_GET['resource'] ?? '';
    $id       = (int) ($_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(['error' => 'ID requerido'], 422);
    }

    if ($resource === 'plantillas') {
        $pdo->prepare("DELETE FROM email_plantillas WHERE id=?")->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    if ($resource === 'campanas') {
        $pdo->prepare("DELETE FROM email_envios WHERE campana_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM email_campanas WHERE id=?")->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['error' => 'Recurso no encontrado'], 404);
}

jsonResponse(['error' => 'Método no permitido'], 405);
