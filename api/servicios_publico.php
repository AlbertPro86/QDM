<?php
/**
 * CRM QUANTUN Digital — API pública de servicios (sin precios)
 * GET → devuelve servicios activos con nombre, descripción, icono, features
 *       SIN precios, costos ni enlaces de pago
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$pdo = db();

// Servicios activos
$stmt = $pdo->query("SELECT id, nombre, descripcion, icono FROM servicios WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
$svcs = $stmt->fetchAll();

if (!$svcs) {
    jsonResponse(['success' => true, 'data' => []]);
}

$ids = array_column($svcs, 'id');
$ph  = implode(',', array_fill(0, count($ids), '?'));

// Features de servicios (sin sub_servicio)
$featStmt = $pdo->prepare("SELECT servicio_id, texto FROM servicio_features WHERE servicio_id IN ($ph) AND sub_servicio_id IS NULL ORDER BY orden ASC, id ASC");
$featStmt->execute($ids);
$featMap = [];
foreach ($featStmt->fetchAll() as $f) {
    $featMap[$f['servicio_id']][] = $f['texto'];
}

// Sub-servicios (solo nombre, sin precios)
$subStmt = $pdo->prepare("SELECT id, servicio_id, nombre FROM sub_servicios WHERE servicio_id IN ($ph) AND activo = 1 ORDER BY id ASC");
$subStmt->execute($ids);
$subMap = [];
foreach ($subStmt->fetchAll() as $sub) {
    $subMap[$sub['servicio_id']][] = [
        'id'     => (int)$sub['id'],
        'nombre' => $sub['nombre'],
    ];
}

// Armar respuesta limpia
$result = [];
foreach ($svcs as $svc) {
    $result[] = [
        'id'          => (int)$svc['id'],
        'nombre'      => $svc['nombre'],
        'descripcion' => $svc['descripcion'] ?? '',
        'icono'       => $svc['icono'] ?? '',
        'features'    => $featMap[$svc['id']] ?? [],
        'planes'      => $subMap[$svc['id']] ?? [],
    ];
}

jsonResponse(['success' => true, 'data' => $result]);
