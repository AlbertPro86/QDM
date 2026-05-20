<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = db();

if ($method === 'POST') {
    $id = $_POST['id'] ?? null;
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

    if (empty($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK)
        jsonResponse(['error' => 'No se recibió imagen'], 400);

    $file    = $_FILES['imagen'];
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed))
        jsonResponse(['error' => 'Formato no permitido. Usa JPG, PNG, WEBP o GIF'], 400);
    if ($file['size'] > 5 * 1024 * 1024)
        jsonResponse(['error' => 'Imagen demasiado grande (máx 5MB)'], 400);

    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = 'pkg_' . $id . '_' . time() . '.' . strtolower($ext);
    $dest = __DIR__ . '/../uploads/paquetes/' . $name;

    // Eliminar imagen anterior si existe
    $prev = $pdo->prepare("SELECT imagen FROM paquetes WHERE id = ?");
    $prev->execute([$id]);
    $row  = $prev->fetch();
    if ($row && $row['imagen']) {
        $old = __DIR__ . '/../' . $row['imagen'];
        if (file_exists($old)) unlink($old);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest))
        jsonResponse(['error' => 'Error al guardar imagen'], 500);

    $ruta = 'uploads/paquetes/' . $name;
    $pdo->prepare("UPDATE paquetes SET imagen = ? WHERE id = ?")->execute([$ruta, $id]);

    jsonResponse(['success' => true, 'imagen' => $ruta]);
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

    $prev = $pdo->prepare("SELECT imagen FROM paquetes WHERE id = ?");
    $prev->execute([$id]);
    $row  = $prev->fetch();
    if ($row && $row['imagen']) {
        $old = __DIR__ . '/../' . $row['imagen'];
        if (file_exists($old)) unlink($old);
    }
    $pdo->prepare("UPDATE paquetes SET imagen = NULL WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Método no permitido'], 405);
