<?php
/**
 * CRM QUANTUN Digital - Upload de archivos adjuntos a transacciones
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

$result = [];

// Procesar archivo factura (PDF)
if (isset($_FILES['factura']) && $_FILES['factura']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['factura'];
    if ($file['type'] === 'application/pdf' && pathinfo($file['name'], PATHINFO_EXTENSION) === 'pdf') {
        $filename = 'factura_' . uniqid() . '.pdf';
        $filepath = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $result['factura_path'] = 'uploads/transacciones/' . $filename;
        }
    }
}

// Procesar archivo imagen
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['imagen'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($file['type'], $allowedMimes) && in_array($ext, $allowedExts)) {
        $filename = 'imagen_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $result['imagen_path'] = 'uploads/transacciones/' . $filename;
        }
    }
}

// Procesar archivo documento
if (isset($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['documento'];
    $allowedMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'];
    $allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($file['type'], $allowedMimes) && in_array($ext, $allowedExts)) {
        $filename = 'documento_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $result['documento_path'] = 'uploads/transacciones/' . $filename;
        }
    }
}

jsonResponse(['success' => true, 'data' => $result]);
