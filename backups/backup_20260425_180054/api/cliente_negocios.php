<?php
/**
 * CRM QUANTUN Digital — API Negocios por Cliente
 * GET    ?cliente_id=X           → listar negocios del cliente
 * POST   {cliente_id, nombre, tipo, estado, descripcion, monto, fecha_inicio, fecha_entrega}
 * PUT    {id, ...campos}         → actualizar
 * DELETE ?id=X                  → eliminar
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

// ── Auto-migración ─────────────────────────────────────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cliente_negocios (
            id            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
            cliente_id    INT          NOT NULL,
            nombre        VARCHAR(150) NOT NULL,
            tipo          VARCHAR(80)  DEFAULT NULL,
            estado        ENUM('activo','en_progreso','completado','pausado','cancelado')
                          NOT NULL DEFAULT 'activo',
            descripcion   TEXT         DEFAULT NULL,
            monto         DECIMAL(12,2) DEFAULT NULL,
            fecha_inicio  DATE         DEFAULT NULL,
            fecha_entrega DATE         DEFAULT NULL,
            notas         TEXT         DEFAULT NULL,
            created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_negocio_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

// ── RUTAS ─────────────────────────────────────────────────────────────────────
switch ($method) {

    case 'GET':
        $clienteId = intval($_GET['cliente_id'] ?? 0);
        if (!$clienteId) jsonResponse(['error' => 'cliente_id requerido'], 400);

        $stmt = $pdo->prepare("
            SELECT * FROM cliente_negocios
            WHERE cliente_id = ?
            ORDER BY
                FIELD(estado,'en_progreso','activo','pausado','completado','cancelado'),
                created_at DESC
        ");
        $stmt->execute([$clienteId]);
        $negocios = $stmt->fetchAll();

        jsonResponse(['success' => true, 'data' => $negocios]);
        break;

    case 'POST':
        $input     = json_decode(file_get_contents('php://input'), true) ?? [];
        $clienteId = intval($input['cliente_id'] ?? 0);
        $nombre    = trim($input['nombre']    ?? '');

        if (!$clienteId || !$nombre) {
            jsonResponse(['error' => 'cliente_id y nombre son requeridos'], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO cliente_negocios
                (cliente_id, nombre, tipo, estado, descripcion, monto, fecha_inicio, fecha_entrega, notas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clienteId,
            $nombre,
            $input['tipo']          ?? null,
            $input['estado']        ?? 'activo',
            $input['descripcion']   ?? null,
            !empty($input['monto'])         ? floatval($input['monto'])         : null,
            !empty($input['fecha_inicio'])  ? $input['fecha_inicio']            : null,
            !empty($input['fecha_entrega']) ? $input['fecha_entrega']           : null,
            $input['notas']         ?? null,
        ]);

        $id = $pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'crear', 'cliente_negocios', $id, "Negocio: {$nombre}");

        $stmt = $pdo->prepare("SELECT * FROM cliente_negocios WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetch(), 'message' => 'Negocio creado'], 201);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = intval($input['id'] ?? ($_GET['id'] ?? 0));
        if (!$id) jsonResponse(['error' => 'id requerido'], 400);

        $allowed = ['nombre','tipo','estado','descripcion','monto','fecha_inicio','fecha_entrega','notas'];
        $fields  = [];
        $values  = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $input)) {
                $fields[] = "{$f} = ?";
                $values[] = ($input[$f] === '' || $input[$f] === null) ? null : $input[$f];
            }
        }
        if (!$fields) jsonResponse(['error' => 'Nada que actualizar'], 400);

        $values[] = $id;
        $pdo->prepare("UPDATE cliente_negocios SET " . implode(', ', $fields) . " WHERE id = ?")
            ->execute($values);
        logActivity($_SESSION['user_id'], 'actualizar', 'cliente_negocios', $id, 'Negocio actualizado');

        $stmt = $pdo->prepare("SELECT * FROM cliente_negocios WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetch(), 'message' => 'Negocio actualizado']);
        break;

    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'id requerido'], 400);

        $pdo->prepare("DELETE FROM cliente_negocios WHERE id = ?")->execute([$id]);
        logActivity($_SESSION['user_id'], 'eliminar', 'cliente_negocios', $id, 'Negocio eliminado');
        jsonResponse(['success' => true, 'message' => 'Negocio eliminado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
