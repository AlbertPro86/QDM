<?php
/**
 * CRM QUANTUN Digital - Contactos adicionales del cliente
 * GET  ?cliente_id=N              -> lista
 * PUT  { cliente_id, contactos[] } -> reemplaza la lista completa
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();
ensureClienteContactos($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cid = (int)($_GET['cliente_id'] ?? 0);
    if (!$cid) jsonResponse(['error' => 'Cliente requerido'], 400);
    jsonResponse(['success' => true, 'data' => getClienteContactos($pdo, $cid)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $in  = json_decode(file_get_contents('php://input'), true) ?: [];
    $cid = (int)($in['cliente_id'] ?? 0);
    if (!$cid) jsonResponse(['error' => 'Cliente requerido'], 400);

    $filas = [];
    foreach (($in['contactos'] ?? []) as $c) {
        $nombre = trim((string)($c['nombre'] ?? ''));
        $email  = trim((string)($c['email'] ?? ''));
        $tel    = trim((string)($c['telefono'] ?? ''));
        if ($nombre === '' && $email === '' && $tel === '') continue;
        if ($nombre === '') jsonResponse(['error' => 'Cada contacto necesita un nombre'], 422);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Correo no válido: ' . $email], 422);
        }
        $filas[] = [
            mb_substr($nombre, 0, 120),
            mb_substr(trim((string)($c['rol'] ?? '')), 0, 80) ?: null,
            $email ?: null,
            $tel ?: null,
        ];
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM cliente_contactos WHERE cliente_id = ?")->execute([$cid]);
        $ins = $pdo->prepare("INSERT INTO cliente_contactos (cliente_id, nombre, rol, email, telefono, orden) VALUES (?,?,?,?,?,?)");
        foreach ($filas as $i => $f) $ins->execute([$cid, $f[0], $f[1], $f[2], $f[3], $i]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'No se pudieron guardar los contactos'], 500);
    }
    jsonResponse(['success' => true, 'data' => getClienteContactos($pdo, $cid)]);
}

jsonResponse(['error' => 'Método no permitido'], 405);
