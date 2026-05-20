<?php
require_once __DIR__ . '/config/database.php';
$pdo = db();

echo "=== CLIENTE_SERVICIOS activos ===\n";
$rows = $pdo->query("
    SELECT cs.cliente_id, c.nombre_comercial,
           COALESCE(cs.nombre_display,s.nombre) as svc,
           cs.monto_renovacion, cs.descuento, cs.costo_servicio, cs.frecuencia, cs.estado
    FROM cliente_servicios cs
    JOIN clientes c ON cs.cliente_id=c.id
    JOIN servicios s ON cs.servicio_id=s.id
    WHERE cs.estado='activo'
    ORDER BY cs.cliente_id, cs.id
")->fetchAll();
foreach($rows as $r)
    echo "  cliente={$r['cliente_id']} ({$r['nombre_comercial']}) | {$r['svc']} | monto={$r['monto_renovacion']} desc={$r['descuento']} costo={$r['costo_servicio']} frec={$r['frecuencia']}\n";

echo "\n=== TRANSACCIONES (ingresos) ===\n";
$rows = $pdo->query("
    SELECT t.id, t.titulo, t.monto, t.estado, t.tipo, t.cliente_id, c.nombre_comercial
    FROM transacciones t
    LEFT JOIN clientes c ON t.cliente_id=c.id
    WHERE t.tipo='ingreso'
    ORDER BY t.cliente_id, t.id
")->fetchAll();
foreach($rows as $r)
    echo "  id={$r['id']} | {$r['titulo']} | monto={$r['monto']} estado={$r['estado']} cliente_id=".($r['cliente_id']??'NULL')." ({$r['nombre_comercial']})\n";

echo "\n=== MRR actual (query clientes.php — SUM DISTINCT = BUG) ===\n";
$rows = $pdo->query("
    SELECT c.id, c.nombre_comercial,
           COALESCE(SUM(DISTINCT CASE WHEN cs.estado='activo' AND LOWER(COALESCE(cs.frecuencia,''))!='unico' THEN cs.monto_renovacion ELSE 0 END),0) as mrr_buggy,
           COALESCE((SELECT SUM(monto_renovacion - descuento) FROM cliente_servicios WHERE cliente_id=c.id AND estado='activo' AND LOWER(COALESCE(frecuencia,''))!='unico'),0) as mrr_correcto
    FROM clientes c
    LEFT JOIN cliente_servicios cs ON c.id=cs.cliente_id
    GROUP BY c.id
")->fetchAll();
foreach($rows as $r)
    echo "  {$r['nombre_comercial']}: mrr_buggy={$r['mrr_buggy']} mrr_correcto={$r['mrr_correcto']}\n";
