<?php
/**
 * CRM QUANTUN Digital - API Pendientes del Mes
 * Retorna transacciones vencidas/pendientes + renovaciones próximas
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();
$hoy      = date('Y-m-d');
$mes      = date('Y-m');
$en30dias = date('Y-m-d', strtotime('+30 days'));

// ── Transacciones vencidas
$stmt = $pdo->prepare("
    SELECT t.id, t.concepto, t.titulo, t.monto, t.tipo, t.estado, t.fecha_vencimiento,
           t.cliente_id, t.lead_id,
           COALESCE(l.nombre, c.nombre_comercial) AS cliente
    FROM transacciones t
    LEFT JOIN leads l ON t.lead_id = l.id
    LEFT JOIN clientes c ON t.cliente_id = c.id
    WHERE t.estado = 'vencido'
    ORDER BY t.fecha_vencimiento ASC
    LIMIT 10
");
$stmt->execute();
$vencidas = $stmt->fetchAll();

// ── Transacciones pendientes del mes
$stmt = $pdo->prepare("
    SELECT t.id, t.concepto, t.titulo, t.monto, t.tipo, t.estado, t.fecha_vencimiento,
           t.cliente_id, t.lead_id,
           COALESCE(l.nombre, c.nombre_comercial) AS cliente
    FROM transacciones t
    LEFT JOIN leads l ON t.lead_id = l.id
    LEFT JOIN clientes c ON t.cliente_id = c.id
    WHERE t.estado = 'pendiente'
      AND (t.fecha_vencimiento IS NULL OR t.fecha_vencimiento <= ?)
    ORDER BY t.fecha_vencimiento ASC
    LIMIT 10
");
$stmt->execute([$en30dias]);
$pendientes = $stmt->fetchAll();

// ── Renovaciones de clientes próximas (≤ 30 días)
$stmt = $pdo->prepare("
    SELECT cs.id, cs.fecha_vencimiento,
           cs.monto_renovacion, cs.descuento,
           c.id AS cliente_id, c.nombre_comercial AS cliente,
           CASE
               WHEN cs.paquete_id IS NOT NULL AND p.nombre IS NOT NULL THEN p.nombre
               WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
               ELSE s.nombre
           END AS servicio
    FROM cliente_servicios cs
    JOIN clientes c ON cs.cliente_id = c.id
    JOIN servicios s ON cs.servicio_id = s.id
    LEFT JOIN paquetes p ON cs.paquete_id = p.id
    WHERE cs.estado = 'activo'
      AND c.estado  = 'activo'
      AND LOWER(COALESCE(cs.frecuencia, '')) != 'unico'
      AND cs.fecha_vencimiento BETWEEN ? AND ?
    ORDER BY cs.fecha_vencimiento ASC
    LIMIT 10
");
$stmt->execute([$hoy, $en30dias]);
$renovaciones = $stmt->fetchAll();

// ── Calcular días restantes y formatear
function diasRestantes($fecha) {
    if (!$fecha) return null;
    $diff = (new DateTime($fecha))->diff(new DateTime())->format('%r%a');
    return (int)$diff;
}

function urlTransaccion($r) {
    if (!empty($r['cliente_id'])) return 'cliente_detalle.php?id=' . $r['cliente_id'];
    return 'finanzas.php';
}

$vencidasFmt = array_map(fn($r) => [
    'id'      => $r['id'],
    'tipo'    => 'vencida',
    'titulo'  => $r['titulo'] ?: $r['concepto'],
    'cliente' => $r['cliente'] ?? '— Interno —',
    'monto'   => floatval($r['monto']),
    'tx_tipo' => $r['tipo'],
    'fecha'   => $r['fecha_vencimiento'],
    'dias'    => $r['fecha_vencimiento'] ? abs(diasRestantes($r['fecha_vencimiento'])) : null,
    'url'     => urlTransaccion($r),
], $vencidas);

$pendientesFmt = array_map(fn($r) => [
    'id'      => $r['id'],
    'tipo'    => 'pendiente',
    'titulo'  => $r['titulo'] ?: $r['concepto'],
    'cliente' => $r['cliente'] ?? '— Interno —',
    'monto'   => floatval($r['monto']),
    'tx_tipo' => $r['tipo'],
    'fecha'   => $r['fecha_vencimiento'],
    'dias'    => $r['fecha_vencimiento'] ? diasRestantes($r['fecha_vencimiento']) : null,
    'url'     => urlTransaccion($r),
], $pendientes);

$renovacionesFmt = array_map(fn($r) => [
    'id'      => $r['id'],
    'tipo'    => 'renovacion',
    'titulo'  => $r['servicio'],
    'cliente' => $r['cliente'],
    'monto'   => max(0, floatval($r['monto_renovacion']) - floatval($r['descuento'])),
    'tx_tipo' => 'ingreso',
    'fecha'   => $r['fecha_vencimiento'],
    'dias'    => diasRestantes($r['fecha_vencimiento']),
    'url'     => 'cliente_detalle.php?id=' . $r['cliente_id'],
], $renovaciones);

$total = count($vencidas) + count($pendientes) + count($renovaciones);

jsonResponse([
    'success'     => true,
    'total'       => $total,
    'vencidas'    => $vencidasFmt,
    'pendientes'  => $pendientesFmt,
    'renovaciones'=> $renovacionesFmt,
]);
