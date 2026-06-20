<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'POST':
        $tipo = $_POST['tipo'] ?? 'enlace';

        if ($tipo === 'archivo') {
            $tid = intval($_POST['tarjeta_id'] ?? 0);
            if (!$tid || empty($_FILES['archivo'])) jsonResponse(['error' => 'Datos incompletos'], 400);

            $file = $_FILES['archivo'];
            if ($file['error'] !== UPLOAD_ERR_OK) jsonResponse(['error' => 'Error en la subida del archivo'], 400);
            if ($file['size'] > 20 * 1024 * 1024) jsonResponse(['error' => 'Archivo supera 20 MB'], 400);

            $allowedExt = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv',
                           'jpg','jpeg','png','gif','webp','svg','mp4','mp3','zip','rar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin');
            if (!in_array($ext, $allowedExt, true)) {
                jsonResponse(['error' => 'Tipo de archivo no permitido'], 400);
            }

            // Validar MIME real del archivo (no el reportado por el cliente)
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($file['tmp_name']);
            $allowedMime = [
                'application/pdf','application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain','text/csv',
                'image/jpeg','image/png','image/gif','image/webp','image/svg+xml',
                'video/mp4','audio/mpeg',
                'application/zip','application/x-rar-compressed','application/octet-stream',
            ];
            if (!in_array($realMime, $allowedMime, true)) {
                jsonResponse(['error' => 'Tipo de archivo no permitido'], 400);
            }
            $mime = $realMime; // usar el MIME real, no el del cliente

            // Verificar que la tarjeta existe
            $exists = $pdo->prepare("SELECT 1 FROM agenda_tarjetas WHERE id = ?");
            $exists->execute([$tid]);
            if (!$exists->fetchColumn()) jsonResponse(['error' => 'Tarjeta no existe'], 404);

            $year    = date('Y'); $month = date('m');
            $dir     = __DIR__ . '/../uploads/agenda/' . $year . '/' . $month . '/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $newName = 'ag_' . uniqid() . '.' . $ext;
            $relPath = 'uploads/agenda/' . $year . '/' . $month . '/' . $newName;

            if (!move_uploaded_file($file['tmp_name'], $dir . $newName)) {
                jsonResponse(['error' => 'Error al guardar el archivo en el servidor'], 500);
            }

            $stmt = $pdo->prepare("INSERT INTO agenda_adjuntos (tarjeta_id, tipo, nombre, url, mime, peso) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$tid, 'archivo', $file['name'], $relPath, $mime, $file['size']]);
            $id = $pdo->lastInsertId();
            $row = $pdo->prepare("SELECT * FROM agenda_adjuntos WHERE id=?"); $row->execute([$id]);
            jsonResponse(['success' => true, 'adjunto' => $row->fetch()]);

        } else {
            // enlace — puede venir como JSON body
            $raw   = file_get_contents('php://input');
            $input = $raw ? (json_decode($raw, true) ?: []) : [];
            // también acepta form-data
            $tid  = intval($_POST['tarjeta_id'] ?? $input['tarjeta_id'] ?? 0);
            $url  = trim($_POST['url']    ?? $input['url']    ?? '');
            $nom  = trim($_POST['nombre'] ?? $input['nombre'] ?? '');
            if (!$tid || !$url) jsonResponse(['error' => 'tarjeta_id y url requeridos'], 400);
            if (!filter_var($url, FILTER_VALIDATE_URL)) jsonResponse(['error' => 'URL no válida'], 400);

            // Verificar que la tarjeta existe
            $exists = $pdo->prepare("SELECT 1 FROM agenda_tarjetas WHERE id = ?");
            $exists->execute([$tid]);
            if (!$exists->fetchColumn()) jsonResponse(['error' => 'Tarjeta no existe'], 404);

            $nombre = $nom ?: $url;
            $stmt = $pdo->prepare("INSERT INTO agenda_adjuntos (tarjeta_id, tipo, nombre, url) VALUES (?,?,?,?)");
            $stmt->execute([$tid, 'enlace', substr($nombre, 0, 255), substr($url, 0, 1000)]);
            $id = $pdo->lastInsertId();
            $row = $pdo->prepare("SELECT * FROM agenda_adjuntos WHERE id=?"); $row->execute([$id]);
            jsonResponse(['success' => true, 'adjunto' => $row->fetch()]);
        }
        break;

    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        $adj = $pdo->prepare("SELECT * FROM agenda_adjuntos WHERE id=?"); $adj->execute([$id]);
        $adj = $adj->fetch();
        if (!$adj) jsonResponse(['error' => 'No encontrado'], 404);

        if ($adj['tipo'] === 'archivo') {
            $realBase = realpath(__DIR__ . '/../uploads');
            $realPath = realpath(__DIR__ . '/../' . $adj['url']);
            if ($realBase && $realPath && str_starts_with($realPath, $realBase)) {
                unlink($realPath);
            }
        }
        $pdo->prepare("DELETE FROM agenda_adjuntos WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
