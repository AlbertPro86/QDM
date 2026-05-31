<?php
/**
 * CRM QUANTUN Digital — API pública de paquetes/planes (sin costos ni ganancias)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$pdo = db();

$stmt = $pdo->query("
    SELECT id, nombre, descripcion, precio_venta, frecuencia, icono, orden
    FROM paquetes
    WHERE activo = 1
    ORDER BY orden ASC, precio_venta ASC
");
$paquetes = $stmt->fetchAll();

if (!$paquetes) {
    jsonResponse(['success' => true, 'data' => []]);
}

$ids = array_column($paquetes, 'id');
$ph  = implode(',', array_fill(0, count($ids), '?'));

// Sub-servicios incluidos (solo nombre, sin precio)
$itemsStmt = $pdo->prepare("
    SELECT pi.paquete_id, ss.nombre
    FROM paquete_items pi
    JOIN sub_servicios ss ON ss.id = pi.sub_servicio_id
    WHERE pi.paquete_id IN ($ph)
    ORDER BY pi.id ASC
");
$itemsStmt->execute($ids);
$itemsMap = [];
foreach ($itemsStmt->fetchAll() as $item) {
    $itemsMap[$item['paquete_id']][] = $item['nombre'];
}

// Features agrupadas de los sub-servicios
$featStmt = $pdo->prepare("
    SELECT pi.paquete_id, sf.texto
    FROM paquete_items pi
    JOIN servicio_features sf ON sf.sub_servicio_id = pi.sub_servicio_id
    WHERE pi.paquete_id IN ($ph)
    ORDER BY pi.id ASC, sf.orden ASC
");
$featStmt->execute($ids);
$featMap = [];
foreach ($featStmt->fetchAll() as $f) {
    if (!in_array($f['texto'], $featMap[$f['paquete_id']] ?? [])) {
        $featMap[$f['paquete_id']][] = $f['texto'];
    }
}

$result = [];
foreach ($paquetes as $p) {
    // Nombre corto: extraer solo la parte después del pipe
    $nombreCorto = trim(str_replace('Suscripción | Servicios Digitales ', '', $p['nombre']));
    $nombreCorto = trim(str_replace(' Año', '', $nombreCorto));

    $result[] = [
        'id'          => (int)$p['id'],
        'nombre'      => $p['nombre'],
        'nombre_corto'=> $nombreCorto,
        'descripcion' => $p['descripcion'] ?? '',
        'precio'      => (float)$p['precio_venta'],
        'frecuencia'  => $p['frecuencia'],
        'icono'       => $p['icono'] ?? '',
        'items'       => $itemsMap[$p['id']] ?? [],
        'features'    => $featMap[$p['id']] ?? [],
    ];
}

jsonResponse(['success' => true, 'data' => $result]);
