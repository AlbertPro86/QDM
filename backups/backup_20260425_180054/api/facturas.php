<?php
/**
 * CRM QUANTUN Digital - API de Facturas (Upload)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT f.*, u.nombre AS subido_por_nombre FROM facturas f LEFT JOIN usuarios u ON f.subido_por = u.id ORDER BY f.created_at DESC");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        // Upload de archivo
        if (empty($_FILES['archivo'])) {
            jsonResponse(['error' => 'No se recibió ningún archivo.'], 400);
        }

        $file = $_FILES['archivo'];
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            jsonResponse(['error' => 'Tipo de archivo no permitido. Solo PDF, JPG, PNG o WebP.'], 400);
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            jsonResponse(['error' => 'El archivo excede el tamaño máximo permitido (10MB).'], 400);
        }

        // Crear directorio si no existe
        $uploadDir = BASE_PATH . '/' . UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Nombre único
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFilename = uniqid('factura_') . '.' . $ext;
        $destination = $uploadDir . $safeFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            jsonResponse(['error' => 'Error al guardar el archivo.'], 500);
        }

        // Guardar en BD
        $archivoUrl = UPLOAD_DIR . $safeFilename;
        $stmt = $pdo->prepare("INSERT INTO facturas (nombre_archivo, archivo_url, tipo, peso_bytes, subido_por) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $file['name'],
            $archivoUrl,
            $file['type'],
            $file['size'],
            $_SESSION['user_id']
        ]);

        $id = $pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'upload', 'facturas', $id, "Archivo subido: {$file['name']}");

        jsonResponse([
            'success' => true, 
            'data' => [
                'id' => (int)$id,
                'nombre_archivo' => $file['name'],
                'archivo_url' => $archivoUrl,
                'tipo' => $file['type'],
                'peso_bytes' => $file['size']
            ],
            'message' => 'Archivo subido exitosamente'
        ], 201);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID es requerido.'], 400);

        $stmt = $pdo->prepare("SELECT archivo_url FROM facturas WHERE id = ?");
        $stmt->execute([$id]);
        $factura = $stmt->fetch();

        if ($factura) {
            $filepath = BASE_PATH . '/' . $factura['archivo_url'];
            if (file_exists($filepath)) unlink($filepath);
            $pdo->prepare("DELETE FROM facturas WHERE id = ?")->execute([$id]);
            logActivity($_SESSION['user_id'], 'eliminar', 'facturas', $id, "Factura eliminada");
        }

        jsonResponse(['success' => true, 'message' => 'Factura eliminada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
