<?php
/**
 * CRM QUANTUN Digital - API de Servicios de Clientes
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

// Migraciones automáticas
try { $pdo->exec("ALTER TABLE cliente_servicios ADD COLUMN nombre_display VARCHAR(255) NULL DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cliente_servicios ADD COLUMN frecuencia VARCHAR(20) NOT NULL DEFAULT 'año'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cliente_servicios MODIFY COLUMN frecuencia VARCHAR(20) NOT NULL DEFAULT 'año'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cliente_servicios ADD COLUMN paquete_id INT NULL DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cliente_servicios ADD COLUMN notif_count TINYINT NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cliente_servicios ADD COLUMN notif_r1_at DATETIME DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cliente_servicios ADD COLUMN notif_r2_at DATETIME DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cliente_servicios ADD COLUMN notif_r3_at DATETIME DEFAULT NULL"); } catch (PDOException $e) {}
// Normalizar registros sin frecuencia
$pdo->exec("UPDATE cliente_servicios SET frecuencia = 'año' WHERE frecuencia IS NULL OR TRIM(frecuencia) = ''");

switch ($method) {
    case 'GET':
        $cliente_id = $_GET['cliente_id'] ?? null;
        if(!$cliente_id) jsonResponse(['error' => 'Cliente ID es requerido'], 400);

        $stmt = $pdo->prepare("
            SELECT cs.*,
                   CASE
                       WHEN cs.paquete_id IS NOT NULL AND p.nombre IS NOT NULL THEN p.nombre
                       WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
                       ELSE s.nombre
                   END AS servicio_nombre,
                   s.enlace_pago
            FROM cliente_servicios cs
            JOIN servicios s ON cs.servicio_id = s.id
            LEFT JOIN paquetes p ON cs.paquete_id = p.id
            WHERE cs.cliente_id = ?
            ORDER BY cs.fecha_vencimiento ASC
        ");
        $stmt->execute([$cliente_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if(empty($input['cliente_id'])) jsonResponse(['error' => 'Cliente ID es requerido'], 400);
            if(empty($input['servicio_id'])) jsonResponse(['error' => 'Servicio es requerido'], 400);
            if(empty($input['fecha_vencimiento'])) jsonResponse(['error' => 'Fecha de vencimiento es requerida'], 400);

            $nombreDisplay = $input['nombre_display'] ?? null;

            $paqueteId = !empty($input['paquete_id']) ? (int)$input['paquete_id'] : null;
            $stmt = $pdo->prepare("INSERT INTO cliente_servicios (cliente_id, servicio_id, paquete_id, nombre_display, monto_renovacion, descuento, costo_servicio, fecha_inicio, fecha_vencimiento, frecuencia, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['cliente_id'],
                $input['servicio_id'],
                $paqueteId,
                $nombreDisplay,
                $input['monto_renovacion'] ?? 0,
                $input['descuento'] ?? 0,
                $input['costo_servicio'] ?? 0,
                $input['fecha_inicio'] ?? date('Y-m-d'),
                $input['fecha_vencimiento'],
                $input['frecuencia'] ?? 'año',
                $input['estado'] ?? 'activo'
            ]);

            $sName = $nombreDisplay ?: (function() use ($pdo, $input) {
                $s = $pdo->prepare("SELECT nombre FROM servicios WHERE id = ?");
                $s->execute([$input['servicio_id']]);
                return $s->fetchColumn();
            })();
            $descuento = floatval($input['descuento'] ?? 0);
            $descNote = ($descuento > 0) ? " (Descuento: $" . number_format($descuento, 0, '.', ',') . ")" : "";
            $pdo->prepare("INSERT INTO clientes_notas (cliente_id, usuario_id, nota) VALUES (?, ?, ?)")
                ->execute([$input['cliente_id'], $_SESSION['user_id'] ?? 0, "Nuevo servicio asignado: $sName $descNote"]);

            jsonResponse(['success' => true, 'message' => 'Servicio asignado correctamente']);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            if(!$id) jsonResponse(['error' => 'ID es requerido'], 400);

            $fields = ['monto_renovacion', 'descuento', 'costo_servicio', 'fecha_inicio', 'fecha_vencimiento', 'frecuencia', 'estado', 'nombre_display', 'notif_count', 'notif_r1_at', 'notif_r2_at', 'notif_r3_at'];
            $up = []; $vals = [];
            foreach($fields as $f) { if(isset($input[$f])) { $up[] = "$f = ?"; $vals[] = $input[$f]; } }
            $vals[] = $id;

            if (empty($up)) jsonResponse(['error' => 'No hay campos para actualizar'], 400);

            $pdo->prepare("UPDATE cliente_servicios SET ".implode(',', $up)." WHERE id = ?")->execute($vals);
            jsonResponse(['success' => true, 'message' => 'Servicio actualizado']);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        try {
            $id = $_GET['id'] ?? null;
            if(!$id) jsonResponse(['error' => 'ID es requerido'], 400);
            $pdo->prepare("DELETE FROM cliente_servicios WHERE id = ?")->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'Servicio eliminado']);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
        break;
}
