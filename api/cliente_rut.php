<?php
/**
 * CRM QUANTUN Digital - API RUT del Cliente
 * GET    ?cliente_id=X  → devuelve la URL del RUT
 * POST   (multipart)    → sube/reemplaza el RUT
 * DELETE ?cliente_id=X  → elimina el RUT
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) { jsonResponse(['error' => 'No autorizado'], 401); }

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = db();

// Auto-migración por si la columna aún no existe
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN rut_url VARCHAR(255) NULL DEFAULT NULL"); } catch (PDOException $e) {}

switch ($method) {

    // ── GET ─────────────────────────────────────────────────────────────────
    case 'GET':
        $cid = intval($_GET['cliente_id'] ?? 0);
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);
        $stmt = $pdo->prepare("SELECT rut_url FROM clientes WHERE id = ?");
        $stmt->execute([$cid]);
        $row  = $stmt->fetch();
        if (!$row) jsonResponse(['error' => 'Cliente no encontrado'], 404);
        jsonResponse(['success' => true, 'rut_url' => $row['rut_url'] ?: null]);
        break;

    // ── POST ────────────────────────────────────────────────────────────────
    case 'POST':
        $cid = intval($_POST['cliente_id'] ?? 0);
        if (!$cid)               jsonResponse(['error' => 'cliente_id requerido'], 400);
        if (empty($_FILES['rut'])) jsonResponse(['error' => 'Archivo RUT requerido'], 400);

        $file = $_FILES['rut'];

        // Solo PDF o imagen
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            jsonResponse(['error' => 'Solo se permiten PDF o imágenes (JPG, PNG)'], 400);
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            jsonResponse(['error' => 'El archivo supera los 10 MB'], 400);
        }

        $targetDir = __DIR__ . '/../uploads/rut/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        // Eliminar RUT anterior si existe
        $stmt = $pdo->prepare("SELECT rut_url FROM clientes WHERE id = ?");
        $stmt->execute([$cid]);
        $old = $stmt->fetchColumn();
        if ($old) {
            $oldPath = __DIR__ . '/../' . $old;
            if (file_exists($oldPath)) @unlink($oldPath);
        }

        $ext     = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'pdf';
        $newName = 'rut_' . $cid . '_' . time() . '.' . $ext;
        $target  = $targetDir . $newName;
        $relPath = 'uploads/rut/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            jsonResponse(['error' => 'Error al guardar el archivo'], 500);
        }

        $stmt = $pdo->prepare("UPDATE clientes SET rut_url = ? WHERE id = ?");
        $stmt->execute([$relPath, $cid]);

        jsonResponse(['success' => true, 'rut_url' => $relPath, 'message' => 'RUT guardado correctamente']);
        break;

    // ── DELETE ──────────────────────────────────────────────────────────────
    case 'DELETE':
        parse_str(file_get_contents('php://input'), $body);
        $cid = intval($body['cliente_id'] ?? $_GET['cliente_id'] ?? 0);
        if (!$cid) jsonResponse(['error' => 'cliente_id requerido'], 400);

        $stmt = $pdo->prepare("SELECT rut_url FROM clientes WHERE id = ?");
        $stmt->execute([$cid]);
        $url = $stmt->fetchColumn();
        if ($url) {
            $path = __DIR__ . '/../' . $url;
            if (file_exists($path)) @unlink($path);
            $pdo->prepare("UPDATE clientes SET rut_url = NULL WHERE id = ?")->execute([$cid]);
        }
        jsonResponse(['success' => true, 'message' => 'RUT eliminado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
