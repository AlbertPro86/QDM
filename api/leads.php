<?php
/**
 * CRM QUANTUN Digital - API de Leads
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación (excepto para webhook)
if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

switch ($method) {
    case 'GET':
        // ─── Listar Leads ───
        $estado = $_GET['estado'] ?? null;
        $buscar = $_GET['buscar'] ?? null;
        $limite = min((int)($_GET['limite'] ?? 50), 100);
        $offset = max((int)($_GET['offset'] ?? 0), 0);

        $where = [];
        $params = [];

        if ($estado && $estado !== 'todos') {
            $where[] = "l.estado = ?";
            $params[] = $estado;
        }

        if ($buscar) {
            $where[] = "(l.nombre LIKE ? OR l.whatsapp LIKE ? OR l.email LIKE ? OR l.servicio_interes LIKE ?)";
            $searchTerm = "%{$buscar}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads l {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Fetch data
        $sql = "SELECT l.*, u.nombre AS asignado_nombre 
                FROM leads l 
                LEFT JOIN usuarios u ON l.asignado_a = u.id 
                {$whereClause}
                ORDER BY l.created_at DESC 
                LIMIT {$limite} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $leads = $stmt->fetchAll();

        // Counts by estado
        $countByStatus = $pdo->query("
            SELECT estado, COUNT(*) as total 
            FROM leads 
            GROUP BY estado
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        jsonResponse([
            'success' => true,
            'data' => $leads,
            'total' => (int)$total,
            'counts' => $countByStatus
        ]);
        break;

    case 'POST':
        // ─── Crear Lead ───
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Solo nombre es requerido (whatsapp y servicio_interes son opcionales al crear desde transacción)
        if (empty($input['nombre'])) {
            jsonResponse(['error' => 'El nombre es requerido.'], 400);
        }
        // Validación estricta solo si viene del formulario de leads normal
        if (empty($input['fuente']) || $input['fuente'] === 'manual') {
            if (empty($input['whatsapp']) || empty($input['servicio_interes'])) {
                jsonResponse(['error' => 'Nombre, WhatsApp y servicio de interés son requeridos.'], 400);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO leads (nombre, whatsapp, email, servicio_interes, presupuesto, url_actual, fuente, estado, notas, asignado_a)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'nuevo', ?, ?)
        ");

        $stmt->execute([
            $input['nombre'],
            $input['whatsapp'] ?? null,
            $input['email'] ?? null,
            $input['servicio_interes'] ?? 'Sin especificar',
            $input['presupuesto'] ?? 0,
            $input['url_actual'] ?? null,
            $input['fuente'] ?? 'manual',
            $input['notas'] ?? null,
            $input['asignado_a'] ?? null
        ]);

        $leadId = $pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'crear', 'leads', $leadId, "Nuevo lead: {$input['nombre']}");

        // Obtener el lead creado
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$leadId]);
        $newLead = $stmt->fetch();

        jsonResponse(['success' => true, 'data' => $newLead, 'message' => 'Lead creado exitosamente'], 201);
        break;

    case 'PUT':
        // ─── Actualizar Lead ───
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? ($_GET['id'] ?? null);

        if (!$id) {
            jsonResponse(['error' => 'ID del lead es requerido.'], 400);
        }

        // Verificar que existe
        $check = $pdo->prepare("SELECT id FROM leads WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            jsonResponse(['error' => 'Lead no encontrado.'], 404);
        }

        $fields = [];
        $values = [];

        $updatableFields = ['nombre', 'whatsapp', 'email', 'servicio_interes', 'presupuesto', 'url_actual', 'fuente', 'estado', 'notas', 'asignado_a'];
        
        foreach ($updatableFields as $field) {
            if (isset($input[$field])) {
                $fields[] = "{$field} = ?";
                $values[] = $input[$field];
            }
        }

        if (empty($fields)) {
            jsonResponse(['error' => 'No hay campos para actualizar.'], 400);
        }

        $values[] = $id;
        $sql = "UPDATE leads SET " . implode(', ', $fields) . " WHERE id = ?";
        $pdo->prepare($sql)->execute($values);

        logActivity($_SESSION['user_id'], 'actualizar', 'leads', $id, "Lead actualizado");

        // Obtener actualizado
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'data' => $stmt->fetch(), 'message' => 'Lead actualizado']);
        break;

    case 'DELETE':
        // ─── Eliminar Lead ───
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonResponse(['error' => 'ID del lead es requerido.'], 400);
        }

        $check = $pdo->prepare("SELECT nombre FROM leads WHERE id = ?");
        $check->execute([$id]);
        $lead = $check->fetch();

        if (!$lead) {
            jsonResponse(['error' => 'Lead no encontrado.'], 404);
        }

        $pdo->prepare("DELETE FROM leads WHERE id = ?")->execute([$id]);
        logActivity($_SESSION['user_id'], 'eliminar', 'leads', $id, "Lead eliminado: {$lead['nombre']}");

        jsonResponse(['success' => true, 'message' => 'Lead eliminado exitosamente']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
