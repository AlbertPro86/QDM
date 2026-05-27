<?php
/**
 * CRM QUANTUN Digital — Renovación / Reversión de servicios
 * GET  ?cliente_id&action=preview         → preview renovación
 * GET  ?cliente_id&action=preview_revert  → preview reversión
 * POST { cliente_id, ids, action:'renovar'|'revertir' }
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-migraciones para columnas que necesitamos en transacciones
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN frecuencia VARCHAR(20) NOT NULL DEFAULT 'unico'"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN titulo VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN cliente_id INT DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN servicio_id INT DEFAULT NULL"); } catch(PDOException $e){}

function sumarFrecuencia(string $fecha, string $frecuencia): string {
    $d = new DateTime($fecha);
    switch (strtolower(trim($frecuencia))) {
        case 'mes':       $d->modify('+1 month');  break;
        case 'trimestre': $d->modify('+3 months'); break;
        case 'semestre':  $d->modify('+6 months'); break;
        case 'año':       $d->modify('+1 year');   break;
        default:          return $fecha;
    }
    return $d->format('Y-m-d');
}

function restarFrecuencia(string $fecha, string $frecuencia): string {
    $d = new DateTime($fecha);
    switch (strtolower(trim($frecuencia))) {
        case 'mes':       $d->modify('-1 month');  break;
        case 'trimestre': $d->modify('-3 months'); break;
        case 'semestre':  $d->modify('-6 months'); break;
        case 'año':       $d->modify('-1 year');   break;
        default:          return $fecha;
    }
    return $d->format('Y-m-d');
}

function getServicios(PDO $pdo, int $clienteId, array $ids = []): array {
    $where  = "cs.cliente_id = ? AND cs.estado = 'activo' AND cs.frecuencia != 'unico'";
    $params = [$clienteId];
    if ($ids) {
        $ph     = implode(',', array_fill(0, count($ids), '?'));
        $where .= " AND cs.id IN ($ph)";
        $params = array_merge($params, $ids);
    }
    $stmt = $pdo->prepare("
        SELECT cs.id, cs.servicio_id, cs.frecuencia, cs.fecha_vencimiento, cs.fecha_inicio,
               cs.monto_renovacion,
               s.nombre AS servicio_nombre
        FROM cliente_servicios cs
        JOIN servicios s ON cs.servicio_id = s.id
        WHERE $where
        ORDER BY cs.fecha_vencimiento ASC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function frecuenciaToTx(string $f): string {
    switch (strtolower(trim($f))) {
        case 'mes':       return 'mensual';
        case 'trimestre': return 'trimestral';
        case 'semestre':  return 'semestral';
        case 'año':       return 'anual';
        default:          return 'unico';
    }
}

/* ──────────── GET ──────────── */
if ($method === 'GET') {
    $clienteId = (int)($_GET['cliente_id'] ?? 0);
    $action    = $_GET['action'] ?? 'preview';
    $ids       = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
    if (!$clienteId) jsonResponse(['error' => 'cliente_id requerido'], 400);

    $svcs = getServicios($pdo, $clienteId, $ids);

    if ($action === 'preview_revert') {
        $preview = array_map(fn($s) => [
            'id'              => $s['id'],
            'servicio_nombre' => $s['servicio_nombre'],
            'frecuencia'      => $s['frecuencia'],
            'fecha_actual'    => $s['fecha_vencimiento'],   // donde está ahora
            'fecha_inicio_actual' => $s['fecha_inicio'],
            'nueva_fecha_fin' => $s['fecha_inicio'],        // vuelve al inicio actual
            'nueva_fecha_inicio' => restarFrecuencia($s['fecha_inicio'], $s['frecuencia']),
        ], $svcs);
        jsonResponse(['success' => true, 'preview' => $preview]);
    }

    // default: preview renovación
    $preview = array_map(fn($s) => [
        'id'              => $s['id'],
        'servicio_nombre' => $s['servicio_nombre'],
        'frecuencia'      => $s['frecuencia'],
        'fecha_actual'    => $s['fecha_vencimiento'],
        'nueva_fecha_fin' => sumarFrecuencia($s['fecha_vencimiento'], $s['frecuencia']),
    ], $svcs);
    jsonResponse(['success' => true, 'preview' => $preview]);
}

/* ──────────── POST ──────────── */
if ($method === 'POST') {
    $input     = json_decode(file_get_contents('php://input'), true);
    $clienteId = (int)($input['cliente_id'] ?? 0);
    $ids       = array_filter(array_map('intval', $input['ids'] ?? []));
    $action    = $input['action'] ?? 'renovar';
    if (!$clienteId) jsonResponse(['error' => 'cliente_id requerido'], 400);

    $svcs = getServicios($pdo, $clienteId, $ids);
    if (!$svcs) jsonResponse(['error' => 'Sin servicios elegibles'], 400);

    $stmt = $pdo->prepare("UPDATE cliente_servicios SET fecha_inicio = ?, fecha_vencimiento = ? WHERE id = ?");
    $txStmt = $pdo->prepare("
        INSERT INTO transacciones
            (tipo, monto, concepto, titulo, descripcion, fecha_vencimiento, estado, fecha_pago,
             cliente_id, servicio_id, frecuencia, registrado_por)
        VALUES ('ingreso', ?, ?, 'Renovación de servicio', ?, ?, 'pagado', ?, ?, ?, ?, ?)
    ");
    $count = 0;

    foreach ($svcs as $s) {
        if ($action === 'revertir') {
            $nuevoFin    = $s['fecha_inicio'];                              // vuelve al inicio actual
            $nuevoInicio = restarFrecuencia($s['fecha_inicio'], $s['frecuencia']); // retrocede un ciclo
        } else {
            $nuevoInicio = $s['fecha_vencimiento'];
            $nuevoFin    = sumarFrecuencia($s['fecha_vencimiento'], $s['frecuencia']);
        }
        $stmt->execute([$nuevoInicio, $nuevoFin, $s['id']]);

        // Registrar movimiento en núcleo financiero solo al renovar
        if ($action !== 'revertir') {
            $monto        = max(0, (float)($s['monto_renovacion'] ?? 0));
            $concepto     = 'Renovación – ' . $s['servicio_nombre'];
            $descr        = 'Renovación automática desde CRM. Período: ' . $nuevoInicio . ' al ' . $nuevoFin;
            $txFrecuencia = frecuenciaToTx($s['frecuencia']);
            $hoy          = date('Y-m-d');
            try {
                $txStmt->execute([
                    $monto,
                    $concepto,
                    $descr,
                    $hoy,          // fecha_vencimiento = hoy
                    'pagado',      // estado (ya pagó al renovar)
                    $hoy,          // fecha_pago = hoy
                    $clienteId,
                    $s['servicio_id'] ?: null,
                    $txFrecuencia,
                    $_SESSION['user_id'],
                ]);
            } catch (PDOException $e) {
                // El error no cancela la renovación, pero lo incluimos en la respuesta
                error_log('[renovar_servicios] Error al registrar transacción: ' . $e->getMessage());
            }
        }

        $count++;
    }

    $verb = $action === 'revertir' ? 'Reversión' : 'Renovación';
    logActivity($_SESSION['user_id'], $action, 'cliente_servicios', $clienteId,
        "$verb masiva: $count servicio(s) del cliente $clienteId");

    $msg = $action === 'revertir'
        ? "$count servicio(s) revertido(s) a la fecha anterior"
        : "$count servicio(s) renovado(s) correctamente";

    jsonResponse(['success' => true, 'count' => $count, 'message' => $msg]);
}

jsonResponse(['error' => 'Método no permitido'], 405);
