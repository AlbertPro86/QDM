<?php
/**
 * CRM QUANTUN Digital - Upload de archivos adjuntos a transacciones
 * Soporta claves individuales (factura, imagen, documento) y múltiples (archivo_0, archivo_1…)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$uploadDir = __DIR__ . '/../uploads/transacciones/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$imgMimes  = ['image/jpeg','image/png','image/gif','image/webp'];
$imgExts   = ['jpg','jpeg','png','gif','webp'];
$docMimes  = ['application/pdf','application/msword',
              'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
              'application/vnd.ms-excel',
              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              'text/plain'];
$docExts   = ['pdf','doc','docx','xls','xlsx','txt'];

$result  = [];
$extras  = [];   // archivos adicionales más allá del primero de cada tipo

function saveFile($file, $prefix, $destDir) {
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = $prefix . '_' . uniqid() . '.' . $ext;
    $dest = $destDir . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'uploads/transacciones/' . $name;
    }
    return null;
}

// ── Claves individuales legacy (factura / imagen / documento) ─────────────
if (!empty($_FILES['factura']) && $_FILES['factura']['error'] === UPLOAD_ERR_OK) {
    $f   = $_FILES['factura'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if ($f['type'] === 'application/pdf' && $ext === 'pdf') {
        $p = saveFile($f, 'factura', $uploadDir);
        if ($p) $result['factura_path'] = $p;
    }
}
if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $f   = $_FILES['imagen'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (in_array($f['type'], $imgMimes) && in_array($ext, $imgExts)) {
        $p = saveFile($f, 'imagen', $uploadDir);
        if ($p) $result['imagen_path'] = $p;
    }
}
if (!empty($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
    $f   = $_FILES['documento'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (in_array($f['type'], $docMimes) && in_array($ext, $docExts)) {
        $p = saveFile($f, 'documento', $uploadDir);
        if ($p) $result['documento_path'] = $p;
    }
}

// ── Claves múltiples archivo_0, archivo_1… ────────────────────────────────
foreach ($_FILES as $key => $file) {
    if (!preg_match('/^archivo_\d+$/', $key)) continue;
    if ($file['error'] !== UPLOAD_ERR_OK) continue;

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = $file['type'];

    if (in_array($mime, $imgMimes) && in_array($ext, $imgExts)) {
        // Imagen
        if (!isset($result['imagen_path'])) {
            $p = saveFile($file, 'imagen', $uploadDir);
            if ($p) $result['imagen_path'] = $p;
        } else {
            $p = saveFile($file, 'imagen', $uploadDir);
            if ($p) $extras[] = ['tipo' => 'imagen', 'path' => $p, 'nombre' => $file['name']];
        }
    } elseif ($mime === 'application/pdf' || $ext === 'pdf') {
        // PDF → factura
        if (!isset($result['factura_path'])) {
            $p = saveFile($file, 'factura', $uploadDir);
            if ($p) $result['factura_path'] = $p;
        } else {
            $p = saveFile($file, 'factura', $uploadDir);
            if ($p) $extras[] = ['tipo' => 'factura', 'path' => $p, 'nombre' => $file['name']];
        }
    } elseif (in_array($mime, $docMimes) && in_array($ext, $docExts)) {
        // Documento
        if (!isset($result['documento_path'])) {
            $p = saveFile($file, 'documento', $uploadDir);
            if ($p) $result['documento_path'] = $p;
        } else {
            $p = saveFile($file, 'documento', $uploadDir);
            if ($p) $extras[] = ['tipo' => 'documento', 'path' => $p, 'nombre' => $file['name']];
        }
    }
}

jsonResponse(['success' => true, 'data' => $result, 'extras' => $extras]);
