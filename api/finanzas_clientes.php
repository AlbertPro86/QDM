<?php
/**
 * CRM QUANTUN Digital - API Resumen Financiero desde Clientes
 * Calcula ingresos, egresos, balance y MRR basado en cliente_servicios
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();

// Filtro por rango de fechas (opcional)
$fechaDesde = $_GET['desde'] ?? null;
$fechaHasta = $_GET['hasta'] ?? null;

// Factores para normalizar a mensual (MRR)
$mrrFactor = [
    'mes'       => 1,
    'trimestre' => 1 / 3,
    'semestre'  => 1 / 6,
    'año'       => 1 / 12,
    'unico'     => 0,
    'ninguna'   => 0,
];

// Auto-sincronizar costo_servicio NULL/0 desde sub-servicios (display compuesto "Padre — Sub")
$pdo->exec("
    UPDATE cliente_servicios cs
    JOIN servicios s  ON cs.servicio_id = s.id
    JOIN sub_servicios ss ON ss.servicio_id = s.id
        AND cs.nombre_display = CONCAT(s.nombre, ' — ', ss.nombre)
    SET cs.costo_servicio = ss.costo
    WHERE cs.estado = 'activo'
      AND cs.paquete_id IS NULL
      AND (cs.costo_servicio IS NULL OR cs.costo_servicio = 0)
      AND ss.costo > 0
");

// Auto-sincronizar costo_servicio NULL/0 desde catálogo de servicios (asignaciones directas sin sub-servicio)
$pdo->exec("
    UPDATE cliente_servicios cs
    JOIN servicios s ON cs.servicio_id = s.id
    SET cs.costo_servicio = s.costo
    WHERE cs.estado = 'activo'
      AND cs.paquete_id IS NULL
      AND (cs.costo_servicio IS NULL OR cs.costo_servicio = 0)
      AND (cs.nombre_display IS NULL OR cs.nombre_display NOT LIKE '% — %')
      AND s.costo > 0
");

// Todos los servicios activos de clientes activos
$stmt = $pdo->query("
    SELECT
        cs.id,
        cs.cliente_id,
        cs.servicio_id,
        cs.monto_renovacion,
        cs.descuento,
        cs.costo_servicio,
        cs.fecha_inicio,
        cs.fecha_vencimiento,
        cs.frecuencia,
        cs.estado         AS cs_estado,
        c.nombre_comercial,
        c.estado          AS cliente_estado,
        CASE
            WHEN cs.paquete_id IS NOT NULL AND p.nombre IS NOT NULL THEN p.nombre
            WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
            ELSE s.nombre
        END AS servicio_nombre
    FROM cliente_servicios cs
    JOIN clientes c ON cs.cliente_id = c.id
    JOIN servicios s ON cs.servicio_id = s.id
    LEFT JOIN paquetes p ON cs.paquete_id = p.id
    WHERE cs.estado = 'activo'
      AND c.estado  = 'activo'
    ORDER BY cs.fecha_vencimiento ASC
");
$rows = $stmt->fetchAll();

// Calcular período para mostrar
$periodoText = ($fechaDesde && $fechaHasta)
    ? date('d M Y', strtotime($fechaDesde)) . ' - ' . date('d M Y', strtotime($fechaHasta))
    : 'Clientes Activos';

// Meses disponibles para el selector
$mesesStmt = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(cs.fecha_vencimiento, '%Y-%m') AS mes
    FROM cliente_servicios cs
    JOIN clientes c ON cs.cliente_id = c.id
    WHERE cs.estado = 'activo' AND c.estado = 'activo' AND cs.fecha_vencimiento IS NOT NULL
    ORDER BY mes DESC
");
$mesesDisp = $mesesStmt->fetchAll(PDO::FETCH_COLUMN);

$totalIngresos  = 0;
$totalEgresos   = 0;
$mrr            = 0;
$ingresosMes    = 0;
$egresosMes     = 0;
$mesActual      = date('Y-m');
$hoy            = new DateTime();
$proximas       = [];
$porCliente     = [];
$porServicio    = [];

foreach ($rows as $cs) {
    $ingreso = max(0, floatval($cs['monto_renovacion']) - floatval($cs['descuento']));
    $egreso  = max(0, floatval($cs['costo_servicio']));
    $freq    = strtolower(trim($cs['frecuencia'] ?? 'mes'));
    $esUnico = $freq === 'unico';
    $factor  = $mrrFactor[$freq] ?? 1;

    // Los pagos únicos se contabilizan en ingresos totales pero NO en MRR ni renovaciones
    $totalIngresos += $ingreso;
    $totalEgresos  += $egreso;
    $mrr           += $ingreso * $factor; // factor=0 para 'unico', ya excluido

    // Ingresos/egresos del mes en curso (por fecha_vencimiento)
    if ($cs['fecha_vencimiento']) {
        $venceMes = substr($cs['fecha_vencimiento'], 0, 7);
        if ($venceMes === $mesActual) {
            $ingresosMes += $ingreso;
            $egresosMes  += $egreso;
        }

        // Próximas renovaciones (≤ 30 días) — SOLO servicios recurrentes
        if (!$esUnico) {
            $vence = new DateTime($cs['fecha_vencimiento']);
            $diff  = (int)$hoy->diff($vence)->format('%r%a');
            if ($diff >= 0 && $diff <= 30) {
                $grupoKey = $cs['cliente_id'] . '_' . $cs['fecha_vencimiento'];

                if (!isset($proximas[$grupoKey])) {
                    $proximas[$grupoKey] = [
                        'cliente_id'        => $cs['cliente_id'],
                        'cliente'           => $cs['nombre_comercial'],
                        'servicios'         => [],
                        'ingreso_neto'      => 0,
                        'costo'             => 0,
                        'margen'            => 0,
                        'fecha_vencimiento' => $cs['fecha_vencimiento'],
                        'dias'              => $diff,
                        'frecuencia'        => $cs['frecuencia'],
                        'es_unico'          => false,
                    ];
                }
                $proximas[$grupoKey]['servicios'][]   = $cs['servicio_nombre'];
                $proximas[$grupoKey]['ingreso_neto'] += $ingreso;
                $proximas[$grupoKey]['costo']        += $egreso;
                $proximas[$grupoKey]['margen']       += ($ingreso - $egreso);
            }
        }
    }

    // Agrupado por cliente
    $cid = $cs['cliente_id'];
    if (!isset($porCliente[$cid])) {
        $porCliente[$cid] = [
            'id'                 => $cid,
            'nombre'             => $cs['nombre_comercial'],
            'ingresos'           => 0,
            'egresos'            => 0,
            'balance'            => 0,
            'margen_pct'         => 0,
            'mrr'                => 0,
            'servicios'          => 0,
            'servicios_ids'      => [],
            'proxima_renovacion' => null,
        ];
    }
    $porCliente[$cid]['ingresos'] += $ingreso;
    $porCliente[$cid]['egresos']  += $egreso;
    $porCliente[$cid]['mrr']      += $ingreso * $factor;
    $porCliente[$cid]['servicios']++;
    $svcId = (int)$cs['servicio_id'];
    if (!in_array($svcId, $porCliente[$cid]['servicios_ids'])) {
        $porCliente[$cid]['servicios_ids'][] = $svcId;
    }
    // proxima_renovacion solo aplica a servicios recurrentes
    if (!$esUnico && $cs['fecha_vencimiento'] &&
        (!$porCliente[$cid]['proxima_renovacion'] ||
         $cs['fecha_vencimiento'] < $porCliente[$cid]['proxima_renovacion'])) {
        $porCliente[$cid]['proxima_renovacion'] = $cs['fecha_vencimiento'];
    }

    // Agrupado por servicio
    $sid = $cs['servicio_id'];
    if (!isset($porServicio[$sid])) {
        $porServicio[$sid] = [
            'id'       => $sid,
            'nombre'   => $cs['servicio_nombre'],
            'ingresos' => 0,
            'egresos'  => 0,
            'balance'  => 0,
            'clientes' => 0,
        ];
    }
    $porServicio[$sid]['ingresos'] += $ingreso;
    $porServicio[$sid]['egresos']  += $egreso;
    $porServicio[$sid]['clientes']++;
}

// Calcular balances y márgenes por cliente
foreach ($porCliente as &$pc) {
    $pc['balance']    = round($pc['ingresos'] - $pc['egresos'], 2);
    $pc['margen_pct'] = $pc['ingresos'] > 0
        ? round(($pc['balance'] / $pc['ingresos']) * 100, 1)
        : 0;
    $pc['ingresos'] = round($pc['ingresos'], 2);
    $pc['egresos']  = round($pc['egresos'],  2);
    $pc['mrr']      = round($pc['mrr'],      2);
}
unset($pc);

// Calcular balances por servicio
foreach ($porServicio as &$ps) {
    $ps['balance'] = round($ps['ingresos'] - $ps['egresos'], 2);
    $ps['ingresos'] = round($ps['ingresos'], 2);
    $ps['egresos']  = round($ps['egresos'],  2);
}
unset($ps);

// Ordenar por ingresos desc
$porClienteArr  = array_values($porCliente);
$porServicioArr = array_values($porServicio);
usort($porClienteArr,  fn($a, $b) => $b['ingresos'] <=> $a['ingresos']);
usort($porServicioArr, fn($a, $b) => $b['ingresos'] <=> $a['ingresos']);

$balance   = $totalIngresos - $totalEgresos;
$margenPct = $totalIngresos > 0 ? round(($balance / $totalIngresos) * 100, 1) : 0;

// Suscripciones vencidas (fecha_vencimiento < hoy) → por cobrar desde cliente_servicios
// Excluir las que ya tienen transacción pendiente/vencida (evita doble conteo con transacciones.por_cobrar)
$stmtVencidas = $pdo->query("
    SELECT
        COALESCE(SUM(cs.monto_renovacion - COALESCE(cs.descuento, 0)), 0) AS total_vencidas,
        COUNT(*) AS count_vencidas
    FROM cliente_servicios cs
    JOIN clientes c ON cs.cliente_id = c.id
    WHERE cs.estado = 'activo'
      AND c.estado  = 'activo'
      AND cs.frecuencia != 'unico'
      AND cs.fecha_vencimiento IS NOT NULL
      AND cs.fecha_vencimiento < CURDATE()
      AND NOT EXISTS (
          SELECT 1 FROM transacciones t
          WHERE t.cliente_id = cs.cliente_id
            AND t.servicio_id = cs.servicio_id
            AND t.tipo = 'ingreso'
            AND t.estado IN ('pendiente', 'vencido')
      )
");
$vencidas = $stmtVencidas->fetch();

jsonResponse([
    'success' => true,
    'periodo' => $periodoText,
    'resumen' => [
        'ingresos_totales'      => round($totalIngresos, 2),
        'egresos_totales'       => round($totalEgresos,  2),
        'balance'               => round($balance, 2),
        'margen_pct'            => $margenPct,
        'mrr'                   => round($mrr, 2),
        'clientes_activos'      => count($porCliente),
        'renovaciones_proximas' => count($proximas),
        'total_vencidas_subs'   => round((float)$vencidas['total_vencidas'], 2),
        'count_vencidas_subs'   => (int)$vencidas['count_vencidas'],
    ],
    'por_cliente'  => $porClienteArr,
    'por_servicio' => $porServicioArr,
    'proximas_renovaciones' => array_values($proximas),
]);
