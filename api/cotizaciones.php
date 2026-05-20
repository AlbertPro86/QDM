<?php
/**
 * CRM QUANTUN Digital - API de Cotizaciones
 * Gestiona la creación, búsqueda y recuperación de cotizaciones
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-migración: crear tabla si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cotizaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero VARCHAR(30) NOT NULL,
            cliente_tipo ENUM('cliente','lead') NOT NULL,
            cliente_id INT DEFAULT NULL,
            nombre_cliente VARCHAR(150) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            whatsapp VARCHAR(30) DEFAULT NULL,
            items LONGTEXT NOT NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            notas TEXT DEFAULT NULL,
            vigencia_dias INT NOT NULL DEFAULT 15,
            estado ENUM('borrador','enviada','aceptada','rechazada','pendiente') DEFAULT 'pendiente',
            creado_por INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_estado (estado),
            INDEX idx_cliente (cliente_tipo, cliente_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) { /* tabla ya existe */ }

switch ($method) {
    case 'GET':
        // Buscar destinatarios (clientes + leads)
        if (isset($_GET['q'])) {
            $q    = '%' . trim($_GET['q']) . '%';
            $solo = $_GET['solo'] ?? null; // 'cliente' | 'lead' | null = ambos

            if ($solo === 'cliente') {
                $stmt = $pdo->prepare("
                    SELECT 'cliente' AS tipo, id, nombre_comercial AS nombre,
                           email_facturacion AS email, telefono AS whatsapp, estado
                    FROM clientes
                    WHERE estado = 'activo' AND (nombre_comercial LIKE ? OR email_facturacion LIKE ? OR telefono LIKE ?)
                    ORDER BY nombre_comercial ASC LIMIT 15
                ");
                $stmt->execute([$q, $q, $q]);
            } elseif ($solo === 'lead') {
                $stmt = $pdo->prepare("
                    SELECT 'lead' AS tipo, id, nombre,
                           email, whatsapp, estado
                    FROM leads
                    WHERE estado != 'perdido' AND (nombre LIKE ? OR email LIKE ? OR whatsapp LIKE ?)
                    ORDER BY nombre ASC LIMIT 15
                ");
                $stmt->execute([$q, $q, $q]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT 'cliente' AS tipo, id, nombre_comercial AS nombre,
                           email_facturacion AS email, telefono AS whatsapp, estado
                    FROM clientes
                    WHERE estado = 'activo' AND (nombre_comercial LIKE ? OR email_facturacion LIKE ?)
                    UNION ALL
                    SELECT 'lead' AS tipo, id, nombre,
                           email, whatsapp, estado
                    FROM leads
                    WHERE estado != 'perdido' AND (nombre LIKE ? OR email LIKE ?)
                    ORDER BY nombre ASC LIMIT 15
                ");
                $stmt->execute([$q, $q, $q, $q]);
            }
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        // Recuperar cotización por ID
        elseif (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
            $stmt->execute([$id]);
            $cot = $stmt->fetch();
            if (!$cot) {
                jsonResponse(['error' => 'Cotización no encontrada'], 404);
            }
            jsonResponse(['success' => true, 'data' => $cot]);
        }
        break;

    case 'POST':
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // ── Acción: Clonar cotización ─────────────────────────────────────
            if (($input['action'] ?? '') === 'clonar') {
                $origenId = intval($input['id'] ?? 0);
                if (!$origenId) jsonResponse(['error' => 'ID requerido'], 400);

                $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
                $stmt->execute([$origenId]);
                $origen = $stmt->fetch();
                if (!$origen) jsonResponse(['error' => 'Cotización no encontrada'], 404);

                // Generar nuevo número
                $año  = date('Y');
                $mes  = date('m');
                $stmt = $pdo->query("SELECT COUNT(*)+1 AS siguiente FROM cotizaciones WHERE YEAR(created_at)=YEAR(NOW())");
                $sig  = $stmt->fetchColumn();
                $nuevoNumero = 'COT-' . str_pad($sig, 4, '0', STR_PAD_LEFT) . '-' . $año . $mes;

                $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;

                $stmt = $pdo->prepare("
                    INSERT INTO cotizaciones
                    (numero, cliente_tipo, cliente_id, nombre_cliente, email, whatsapp, items,
                     subtotal, descuento, total, notas, vigencia_dias, moneda, estado, creado_por)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?)
                ");
                $stmt->execute([
                    $nuevoNumero,
                    $origen['cliente_tipo'],
                    $origen['cliente_id'],
                    $origen['nombre_cliente'],
                    $origen['email'],
                    $origen['whatsapp'],
                    $origen['items'],
                    $origen['subtotal'],
                    $origen['descuento'],
                    $origen['total'],
                    $origen['notas'],
                    $origen['vigencia_dias'],
                    $origen['moneda'] ?? 'COP',
                    $usuario_id
                ]);

                $nuevoId = $pdo->lastInsertId();
                jsonResponse(['success' => true, 'id' => $nuevoId, 'numero' => $nuevoNumero]);
            }

            // ── Acción: Crear cotización nueva ────────────────────────────────
            if (empty($input['cliente_tipo']) || empty($input['nombre_cliente'])) {
                jsonResponse(['error' => 'Tipo de cliente y nombre son requeridos'], 400);
            }

            $items = is_string($input['items']) ? $input['items'] : json_encode($input['items'] ?? []);

            // Agregar columnas opcionales si no existen
            try { $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN moneda VARCHAR(10) NOT NULL DEFAULT 'COP'"); } catch(PDOException $e){}
            try { $pdo->exec("ALTER TABLE cotizaciones MODIFY COLUMN estado ENUM('borrador','enviada','aceptada','rechazada','pendiente') DEFAULT 'pendiente'"); } catch(PDOException $e){}

            // Insertar cotización
            $stmt = $pdo->prepare("
                INSERT INTO cotizaciones
                (cliente_tipo, cliente_id, nombre_cliente, email, whatsapp, items,
                 subtotal, descuento, total, notas, vigencia_dias, moneda, estado, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $estadoValido = in_array($input['estado'] ?? '', ['borrador','enviada','aceptada','rechazada','pendiente'])
                ? $input['estado'] : 'pendiente';

            $stmt->execute([
                $input['cliente_tipo'],
                $input['cliente_id'] ?? null,
                $input['nombre_cliente'],
                $input['email'] ?? null,
                $input['whatsapp'] ?? null,
                $items,
                floatval($input['subtotal'] ?? 0),
                floatval($input['descuento'] ?? 0),
                floatval($input['total'] ?? 0),
                $input['notas'] ?? null,
                intval($input['vigencia_dias'] ?? 15),
                $input['moneda'] ?? 'USD',
                $estadoValido,
                $_SESSION['user_id'] ?? null
            ]);

            $id = $pdo->lastInsertId();

            // Generar número único
            $numero = 'COT-' . str_pad($id, 4, '0', STR_PAD_LEFT) . '-' . date('Ymd');

            // Actualizar número
            $update = $pdo->prepare("UPDATE cotizaciones SET numero = ? WHERE id = ?");
            $update->execute([$numero, $id]);

            jsonResponse([
                'success' => true,
                'id' => $id,
                'numero' => $numero,
                'message' => 'Cotización guardada correctamente'
            ]);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = intval($input['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

            // ── Solo actualizar estado ─────────────────────────────────────
            if (isset($input['estado']) && !isset($input['items'])) {
                $estadosValidos = ['borrador','enviada','aceptada','rechazada','pendiente'];
                if (!in_array($input['estado'], $estadosValidos)) jsonResponse(['error' => 'Estado inválido'], 400);
                $stmt = $pdo->prepare("UPDATE cotizaciones SET estado=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$input['estado'], $id]);
                jsonResponse(['success' => true, 'message' => 'Estado actualizado']);
            }

            // ── Actualización completa (modo edición) ─────────────────────
            if (empty($input['nombre_cliente'])) jsonResponse(['error' => 'Nombre del cliente requerido'], 400);
            $items   = is_string($input['items']) ? $input['items'] : json_encode($input['items'] ?? []);
            $estadoV = in_array($input['estado'] ?? '', ['borrador','enviada','aceptada','rechazada','pendiente']) ? $input['estado'] : 'pendiente';

            $stmt = $pdo->prepare("UPDATE cotizaciones SET
                cliente_tipo=?, cliente_id=?, nombre_cliente=?, email=?, whatsapp=?,
                items=?, subtotal=?, descuento=?, total=?, notas=?, vigencia_dias=?,
                moneda=?, estado=?, updated_at=NOW()
                WHERE id=?");
            $stmt->execute([
                $input['cliente_tipo'] ?? 'lead',
                $input['cliente_id']  ?? null,
                $input['nombre_cliente'],
                $input['email']       ?? null,
                $input['whatsapp']    ?? null,
                $items,
                floatval($input['subtotal']  ?? 0),
                floatval($input['descuento'] ?? 0),
                floatval($input['total']     ?? 0),
                $input['notas']       ?? null,
                intval($input['vigencia_dias'] ?? 15),
                $input['moneda']      ?? 'COP',
                $estadoV,
                $id
            ]);

            // Obtener número actual
            $row = $pdo->prepare("SELECT numero FROM cotizaciones WHERE id=?");
            $row->execute([$id]);
            $numero = $row->fetchColumn();

            jsonResponse(['success' => true, 'id' => $id, 'numero' => $numero, 'message' => 'Cotización actualizada']);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $ids = $input['ids'] ?? null;

            if (!$id && !$ids) {
                jsonResponse(['error' => 'ID requerido'], 400);
            }

            // Eliminar una sola cotización
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM cotizaciones WHERE id = ?");
                $stmt->execute([$id]);
                jsonResponse(['success' => true, 'message' => 'Cotización eliminada']);
            }
            // Eliminar múltiples cotizaciones
            else if (is_array($ids) && count($ids) > 0) {
                $ids = array_map('intval', $ids);
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM cotizaciones WHERE id IN ($ph)");
                $stmt->execute($ids);
                jsonResponse(['success' => true, 'message' => count($ids) . ' cotizaciones eliminadas']);
            }
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
