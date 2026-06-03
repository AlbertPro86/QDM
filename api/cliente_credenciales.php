<?php
/**
 * CRM QUANTUN Digital — API Inventario de Credenciales de Clientes
 * GET    ?cliente_id=X          → lista credenciales del cliente
 * POST   {cliente_id,nombre,correo,clave}  → crear
 * PUT    {id,nombre,correo,clave}          → actualizar
 * DELETE ?id=X                             → eliminar
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
        CREATE TABLE IF NOT EXISTS crm_cliente_credenciales (
            id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
            cliente_id  INT          NOT NULL,
            nombre      VARCHAR(255) NOT NULL DEFAULT '',
            correo      VARCHAR(255) NOT NULL DEFAULT '',
            clave       VARCHAR(500) NOT NULL DEFAULT '',
            url         VARCHAR(500) NOT NULL DEFAULT '',
            created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cliente (cliente_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        // Auto-migración de columna url si no existe
        try { $pdo->exec("ALTER TABLE crm_cliente_credenciales ADD COLUMN url VARCHAR(500) NOT NULL DEFAULT ''"); } catch (PDOException $e) {}

        $cid = intval($_GET['cliente_id'] ?? 0);
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);
        $s = $pdo->prepare("SELECT * FROM crm_cliente_credenciales WHERE cliente_id = ? ORDER BY created_at DESC");
        $s->execute([$cid]);
        jsonResponse(['success' => true, 'data' => $s->fetchAll()]);
        break;

    case 'POST':
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $cid  = intval($in['cliente_id'] ?? 0);
        $nom  = trim($in['nombre'] ?? '');
        $mail = trim($in['correo'] ?? '');
        $pass = trim($in['clave']  ?? '');
        $url  = trim($in['url']    ?? '');
        if (!$cid || !$nom) jsonResponse(['error' => 'cliente_id y nombre son requeridos'], 400);

        $s = $pdo->prepare("INSERT INTO crm_cliente_credenciales (cliente_id, nombre, correo, clave, url) VALUES (?,?,?,?,?)");
        $s->execute([$cid, $nom, $mail, $pass, $url]);
        $id = $pdo->lastInsertId();
        $row = $pdo->prepare("SELECT * FROM crm_cliente_credenciales WHERE id = ?");
        $row->execute([$id]);
        jsonResponse(['success' => true, 'data' => $row->fetch(), 'message' => 'Credencial guardada'], 201);
        break;

    case 'PUT':
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $id   = intval($in['id'] ?? 0);
        $nom  = trim($in['nombre'] ?? '');
        $mail = trim($in['correo'] ?? '');
        $pass = trim($in['clave']  ?? '');
        $url  = trim($in['url']   ?? '');
        if (!$id || !$nom) jsonResponse(['error' => 'id y nombre son requeridos'], 400);

        $s = $pdo->prepare("UPDATE crm_cliente_credenciales SET nombre=?, correo=?, clave=?, url=? WHERE id=?");
        $s->execute([$nom, $mail, $pass, $url, $id]);
        jsonResponse(['success' => true, 'message' => 'Credencial actualizada']);
        break;

    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'id requerido'], 400);
        $pdo->prepare("DELETE FROM crm_cliente_credenciales WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Credencial eliminada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
