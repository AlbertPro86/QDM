<?php
/**
 * CRM QUANTUN Digital - API de Archivos de Clientes
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

// Auto-migración
try { $pdo->exec("ALTER TABLE clientes_archivos ADD COLUMN descripcion VARCHAR(500) DEFAULT NULL"); } catch(PDOException $e){}

switch ($method) {
    case 'GET':
        $cliente_id = $_GET['cliente_id'] ?? null;
        if(!$cliente_id) jsonResponse(['error' => 'ID de cliente requerido'], 400);
        $stmt = $pdo->prepare("SELECT * FROM clientes_archivos WHERE cliente_id = ? ORDER BY created_at DESC");
        $stmt->execute([$cliente_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $cliente_id = $_POST['cliente_id'] ?? null;
        if(!$cliente_id || empty($_FILES['archivo'])) jsonResponse(['error' => 'Datos incompletos'], 400);

        $file = $_FILES['archivo'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = uniqid('cnt_') . '.' . $ext;
        $targetDir = __DIR__ . '/../uploads/clientes/';
        $targetPath = $targetDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
            $stmt = $pdo->prepare("INSERT INTO clientes_archivos (cliente_id, nombre_archivo, archivo_url, tipo_archivo, peso_archivo, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $cliente_id,
                $file['name'],
                'uploads/clientes/' . $newName,
                $file['type'],
                $file['size'],
                $descripcion ?: null,
            ]);
            jsonResponse(['success' => true, 'message' => 'Archivo subido correctamente']);
        } else {
            jsonResponse(['error' => 'Error al mover el archivo'], 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if(!$id) jsonResponse(['error' => 'ID requerido'], 400);
        
        $stmt = $pdo->prepare("SELECT archivo_url FROM clientes_archivos WHERE id = ?");
        $stmt->execute([$id]);
        $archivo = $stmt->fetch();
        
        if($archivo) {
            $filePath = __DIR__ . '/../' . $archivo['archivo_url'];
            if(file_exists($filePath)) unlink($filePath);
            $pdo->prepare("DELETE FROM clientes_archivos WHERE id = ?")->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'Archivo eliminado']);
        } else {
            jsonResponse(['error' => 'Archivo no encontrado'], 404);
        }
        break;
}
