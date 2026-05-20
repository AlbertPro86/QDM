<?php
/**
 * CRM QUANTUN Digital - Dashboard Principal
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pdo = db();

// ── Filtro mes ──────────────────────────────────────────────────────────────
$mesParam = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mesParam)) $mesParam = date('Y-m');
if ($mesParam > date('Y-m')) $mesParam = date('Y-m');

[$year, $month] = explode('-', $mesParam);
$mesInicio = "$year-$month-01";
$mesFin    = date('Y-m-t', strtotime($mesInicio));
$hoy       = date('Y-m-d');
if ($mesFin > $hoy) $mesFin = $hoy;

$prevMes   = date('Y-m', strtotime("$mesInicio -1 month"));
$nextMes   = date('Y-m', strtotime("$mesInicio +1 month"));
$canNext   = $nextMes <= date('Y-m');
$isCurrent = $mesParam === date('Y-m');

$meses    = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mesLabel = $meses[(int)$month - 1] . ' ' . $year;

// ── Mes anterior (comparación) ──────────────────────────────────────────────
$prevInicio = date('Y-m-01', strtotime("$mesInicio -1 month"));
$prevFin    = date('Y-m-t',  strtotime("$mesInicio -1 month"));

function qSum($pdo, $tipo, $estado, $desde, $hasta) {
    $s = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE tipo=? AND estado=? AND COALESCE(fecha_vencimiento,DATE(created_at)) BETWEEN ? AND ?");
    $s->execute([$tipo,$estado,$desde,$hasta]);
    return (float)$s->fetchColumn();
}

$ingresosmes  = qSum($pdo,'ingreso','pagado',$mesInicio,$mesFin);
$egresosmes   = qSum($pdo,'egreso','pagado',$mesInicio,$mesFin);
$gananciaNet  = $ingresosmes - $egresosmes;
$porCobrar    = qSum($pdo,'ingreso','pendiente',$mesInicio,$mesFin);
$porPagar     = qSum($pdo,'egreso','pendiente',$mesInicio,$mesFin);

$prevIng = qSum($pdo,'ingreso','pagado',$prevInicio,$prevFin);
$prevEgr = qSum($pdo,'egreso','pagado',$prevInicio,$prevFin);
$prevNet = $prevIng - $prevEgr;

function pct($curr, $prev) {
    if ($prev == 0) return $curr > 0 ? 100 : 0;
    return round(($curr - $prev) / $prev * 100);
}
$pctIng = pct($ingresosmes, $prevIng);
$pctEgr = pct($egresosmes,  $prevEgr);
$pctNet = pct($gananciaNet, $prevNet);

// ── Clientes ────────────────────────────────────────────────────────────────
$clientesActivos  = (int)$pdo->query("SELECT COUNT(*) FROM clientes WHERE estado='activo'")->fetchColumn();
$renovacionesMes  = (int)$pdo->query("SELECT COUNT(*) FROM cliente_servicios WHERE MONTH(fecha_vencimiento)=MONTH(NOW()) AND YEAR(fecha_vencimiento)=YEAR(NOW()) AND estado='activo'")->fetchColumn();

// ── Leads (mes seleccionado) ─────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT estado, COUNT(*) AS cnt FROM leads WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY estado");
$stmt->execute([$mesInicio,$mesFin]);
$lCount = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$lTotal   = array_sum($lCount);
$lNuevo   = (int)($lCount['nuevo'] ?? 0);
$lCont    = (int)($lCount['contactado'] ?? 0);
$lNegoc   = (int)($lCount['en_negociacion'] ?? 0);
$lGanado  = (int)($lCount['ganado'] ?? 0);
$lPerdido = (int)($lCount['perdido'] ?? 0);
$tasaConv = $lTotal > 0 ? round($lGanado / $lTotal * 100) : 0;

// Leads mes anterior para comparar
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt2->execute([$prevInicio,$prevFin]);
$lTotalPrev = (int)$stmt2->fetchColumn();
$pctLeads   = pct($lTotal, $lTotalPrev);

$leadsRecientes = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 5")->fetchAll();

// ── Tareas ───────────────────────────────────────────────────────────────────
$tPend   = (int)$pdo->query("SELECT COUNT(*) FROM tareas WHERE estado='pendiente'")->fetchColumn();
$tProg   = (int)$pdo->query("SELECT COUNT(*) FROM tareas WHERE estado='en_progreso'")->fetchColumn();
$tRev    = (int)$pdo->query("SELECT COUNT(*) FROM tareas WHERE estado='revision'")->fetchColumn();
$tAtras  = (int)$pdo->query("SELECT COUNT(*) FROM tareas WHERE estado NOT IN ('completado','cancelado') AND fecha_limite < CURDATE()")->fetchColumn();
$tCompMes= (int)$pdo->query("SELECT COUNT(*) FROM tareas WHERE estado='completado' AND MONTH(updated_at)=MONTH(NOW()) AND YEAR(updated_at)=YEAR(NOW())")->fetchColumn();
$tAlta   = (int)$pdo->query("SELECT COUNT(*) FROM tareas WHERE prioridad='alta' AND estado NOT IN ('completado','cancelado')")->fetchColumn();

$tareasRecientes = $pdo->query("SELECT t.*, c.nombre_comercial AS cnom FROM tareas t LEFT JOIN clientes c ON c.id=t.cliente_id WHERE t.estado NOT IN ('completado','cancelado') ORDER BY FIELD(t.prioridad,'alta','media','baja'), t.fecha_limite IS NULL, t.fecha_limite ASC LIMIT 5")->fetchAll();

$pageTitle    = 'Dashboard';
$pageSubtitle = '';
include __DIR__ . '/includes/header.php';
?>

<style>
.dash-card        { background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04); }
.dash-card-dark   { background:#0f172a;border-radius:16px;padding:22px 24px; }
.dash-card-red    { background:#dc2626;border-radius:16px;padding:22px 24px; }
.dash-card-lime   { background:#c9f31d;border-radius:16px;padding:22px 24px; }
.dash-card-slate  { background:#334155;border-radius:16px;padding:22px 24px; }
.dash-card-indigo { background:#4f46e5;border-radius:16px;padding:22px 24px; }

.big-num   { font-size:36px;font-weight:900;line-height:1;letter-spacing:-.02em; }
.big-num-sm{ font-size:28px;font-weight:900;line-height:1;letter-spacing:-.02em; }
.cop-label { font-size:10px;font-weight:700;letter-spacing:.08em;margin-top:4px;margin-bottom:14px; }

.pct-pill  { display:inline-flex;align-items:center;gap:3px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700; }

.pipe-row       { display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid rgba(0,0,0,.05); }
.pipe-row:last-child { border-bottom:none; }
.pipe-bar-track { flex:1;height:9px;background:#f1f5f9;border-radius:5px;overflow:hidden; }
.pipe-bar-fill  { height:100%;border-radius:5px;background:repeating-linear-gradient(-55deg,#c9f31d,#c9f31d 6px,#b5dd18 6px,#b5dd18 12px);transition:width .5s ease; }
.pipe-bar-fill.red   { background:repeating-linear-gradient(-55deg,#fca5a5,#fca5a5 6px,#f87171 6px,#f87171 12px); }
.pipe-bar-fill.indigo{ background:repeating-linear-gradient(-55deg,#a5b4fc,#a5b4fc 6px,#818cf8 6px,#818cf8 12px); }
.pipe-bar-fill.slate { background:repeating-linear-gradient(-55deg,#94a3b8,#94a3b8 6px,#7b90a8 6px,#7b90a8 12px); }
.pipe-bar-fill.warn  { background:repeating-linear-gradient(-55deg,#fcd34d,#fcd34d 6px,#f59e0b 6px,#f59e0b 12px); }

.task-row { display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9; }
.task-row:last-child { border-bottom:none; }

.month-nav { display:inline-flex;align-items:center;gap:0;border-radius:10px;overflow:hidden;border:1.5px solid var(--color-border); }
.month-nav a, .month-nav span { padding:7px 14px;font-size:13px;font-weight:700;color:var(--color-text-muted);text-decoration:none;transition:background .15s; }
.month-nav a:hover { background:#f1f5f9; }
.month-nav .active { background:#0f172a;color:#fff; }
.month-nav .sep { padding:0;width:1px;background:var(--color-border); }

@media(max-width:960px){
  .dash-main-grid { grid-template-columns:1fr !important; }
  .dash-fin-grid  { grid-template-columns:1fr 1fr !important; }
  .dash-bot-grid  { grid-template-columns:1fr !important; }
}
@media(max-width:600px){
  .dash-fin-grid  { grid-template-columns:1fr !important; }
  .big-num        { font-size:28px !important; }
}
</style>

<!-- ── Header ────────────────────────────────────────────────────────────── -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:14px">
    <div>
        <h2 style="font-size:24px;font-weight:900;color:var(--color-text);margin:0;letter-spacing:-.02em">Panel Operativo</h2>
        <p style="font-size:13px;color:var(--color-text-muted);margin:4px 0 0">Resumen mensual de tu agencia</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <!-- Navegación de mes -->
        <div class="month-nav">
            <a href="?mes=<?=$prevMes?>" title="Mes anterior">&#8592;</a>
            <span class="sep"></span>
            <span class="active"><?=$mesLabel?></span>
            <span class="sep"></span>
            <?php if($canNext): ?>
            <a href="?mes=<?=$nextMes?>" title="Mes siguiente">&#8594;</a>
            <?php else: ?>
            <span style="opacity:.3;cursor:default">&#8594;</span>
            <?php endif; ?>
        </div>
        <?php if(!$isCurrent): ?>
        <a href="?" class="btn btn-outline btn-sm" style="font-size:12px">Mes actual</a>
        <?php endif; ?>
        <a href="finanzas.php" class="btn btn-secondary btn-sm">Ir a Finanzas</a>
        <button id="btnEnviarResumen" onclick="enviarResumenDiario()"
            style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#0f172a;color:#c9f31d;border:none;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;transition:filter .15s"
            onmouseenter="this.style.filter='brightness(1.3)'" onmouseleave="this.style.filter=''">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Enviar resumen
        </button>
    </div>
</div>

<!-- ── Fila financiera principal ─────────────────────────────────────────── -->
<div class="dash-fin-grid" style="display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr;gap:14px;margin-bottom:20px">

    <!-- Ingresos — card oscura hero -->
    <a href="finanzas.php" class="dash-card-dark" style="text-decoration:none;display:block;position:relative;overflow:hidden">
        <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(201,243,29,.06)"></div>
        <div style="position:absolute;bottom:-40px;right:20px;width:80px;height:80px;border-radius:50%;background:rgba(201,243,29,.04)"></div>
        <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Ingresos · <?=$mesLabel?></div>
        <div class="big-num" style="color:#fff"><?=moneyNum($ingresosmes)?></div>
        <div class="cop-label" style="color:rgba(255,255,255,.35)">COP</div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="pct-pill" style="background:<?=$pctIng>=0?'rgba(201,243,29,.18)':'rgba(239,68,68,.2)'?>;color:<?=$pctIng>=0?'#c9f31d':'#f87171'?>">
                <?=$pctIng>=0?'↑':'↓'?> <?=abs($pctIng)?>%
            </span>
            <span style="font-size:11px;color:rgba(255,255,255,.35)">vs mes anterior</span>
        </div>
        <?php if($porCobrar>0): ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.08);font-size:11px;color:rgba(255,255,255,.4)"><?=formatMoney($porCobrar)?> por cobrar</div>
        <?php endif; ?>
    </a>

    <!-- Egresos -->
    <a href="finanzas.php" class="dash-card" style="text-decoration:none;display:block">
        <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Egresos</div>
        <div class="big-num-sm" style="color:#0f172a"><?=moneyNum($egresosmes)?></div>
        <div class="cop-label" style="color:#cbd5e1">COP</div>
        <div style="display:flex;align-items:center;gap:8px">
            <span class="pct-pill" style="background:<?=$pctEgr<=0?'#dcfce7':'#fee2e2'?>;color:<?=$pctEgr<=0?'#16a34a':'#dc2626'?>">
                <?=$pctEgr>=0?'↑':'↓'?> <?=abs($pctEgr)?>%
            </span>
            <span style="font-size:11px;color:#94a3b8">vs anterior</span>
        </div>
        <?php if($porPagar>0): ?>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:11px;color:#94a3b8"><?=formatMoney($porPagar)?> por pagar</div>
        <?php endif; ?>
    </a>

    <!-- Ganancia Neta -->
    <?php $netPos = $gananciaNet >= 0; ?>
    <a href="finanzas.php" class="dash-card" style="text-decoration:none;display:block;<?=$netPos?'background:#f0fdf4':''?>">
        <div style="font-size:10px;font-weight:700;color:<?=$netPos?'#86efac':'#fca5a5'?>;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Ganancia Neta</div>
        <div class="big-num-sm" style="color:<?=$netPos?'#16a34a':'#dc2626'?>"><?=moneyNum($gananciaNet)?></div>
        <div class="cop-label" style="color:<?=$netPos?'#86efac':'#fca5a5'?>">COP</div>
        <div>
            <span class="pct-pill" style="background:<?=$netPos?'#dcfce7':'#fee2e2'?>;color:<?=$netPos?'#16a34a':'#dc2626'?>">
                <?=$netPos?'Positivo':'Negativo'?>
            </span>
        </div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid <?=$netPos?'#dcfce7':'#fee2e2'?>;font-size:11px;color:<?=$netPos?'#86efac':'#fca5a5'?>">
            <?=$netPos?'Rentable este período':'Balance en rojo'?>
        </div>
    </a>

    <!-- Clientes Activos -->
    <a href="clientes.php" class="dash-card" style="text-decoration:none;display:block">
        <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Clientes Activos</div>
        <div class="big-num-sm" style="color:#0f172a"><?=$clientesActivos?></div>
        <div class="cop-label" style="color:transparent;user-select:none">·</div>
        <span class="pct-pill" style="background:#e0e7ff;color:#4f46e5">Cartera</span>
        <?php if($renovacionesMes>0): ?>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:11px;color:#f59e0b;font-weight:600">⚠ <?=$renovacionesMes?> renovac. este mes</div>
        <?php else: ?>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:11px;color:#94a3b8">Sin renovaciones urgentes</div>
        <?php endif; ?>
    </a>

</div>

<!-- ── Cuerpo principal ───────────────────────────────────────────────────── -->
<div class="dash-main-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

    <!-- ── LEADS ──────────────────────────────────────────────────────────── -->
    <div>
        <!-- Header leads -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
            <div>
                <h3 style="font-size:18px;font-weight:800;color:var(--color-text);margin:0;letter-spacing:-.01em">Pipeline de Leads</h3>
                <p style="font-size:12px;color:var(--color-text-muted);margin:4px 0 0"><?=$mesLabel?></p>
            </div>
            <a href="leads.php" class="btn btn-outline btn-sm" style="font-size:12px">Ver todos →</a>
        </div>

        <!-- Totales leads hero -->
        <div class="dash-card-indigo" style="margin-bottom:14px;display:flex;align-items:center;gap:20px">
            <div>
                <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.08em">Total leads mes</div>
                <div style="font-size:42px;font-weight:900;color:#fff;line-height:1;margin:6px 0 4px"><?=$lTotal?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.5)">
                    <?php if($lTotalPrev>0): ?>
                    <span style="color:<?=$pctLeads>=0?'#c9f31d':'#fca5a5'?>;font-weight:700"><?=$pctLeads>=0?'↑':'↓'?> <?=abs($pctLeads)?>%</span> vs mes ant.
                    <?php else: ?>Primer registro<?php endif; ?>
                </div>
            </div>
            <div style="flex:1"></div>
            <div style="text-align:right">
                <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Conversión</div>
                <div style="font-size:36px;font-weight:900;color:#c9f31d;line-height:1"><?=$tasaConv?>%</div>
                <div style="font-size:11px;color:rgba(255,255,255,.45);margin-top:3px"><?=$lGanado?> ganados</div>
            </div>
        </div>

        <!-- Pipeline funnel bars -->
        <div class="dash-card" style="padding:18px 20px">
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;margin-bottom:14px">Distribución por etapa</div>
            <?php
            $stages = [
                ['Nuevos',       $lNuevo,   'indigo'],
                ['Contactados',  $lCont,    'slate'],
                ['Negociando',   $lNegoc,   'warn'],
                ['Ganados',      $lGanado,  ''],
                ['Perdidos',     $lPerdido, 'red'],
            ];
            $maxStage = max(1, ...array_column($stages,1));
            foreach($stages as [$label,$cnt,$cls]):
                $w = $lTotal > 0 ? round($cnt/$lTotal*100) : 0;
            ?>
            <div class="pipe-row">
                <span style="font-size:12px;font-weight:600;color:#475569;width:90px;flex-shrink:0"><?=$label?></span>
                <div class="pipe-bar-track">
                    <div class="pipe-bar-fill <?=$cls?>" style="width:<?=$w?>%"></div>
                </div>
                <span style="font-size:13px;font-weight:800;color:#0f172a;width:28px;text-align:right;flex-shrink:0"><?=$cnt?></span>
                <span style="font-size:11px;color:#94a3b8;width:32px;flex-shrink:0"><?=$w?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── TAREAS ──────────────────────────────────────────────────────────── -->
    <div>
        <!-- Header tareas -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
            <div>
                <h3 style="font-size:18px;font-weight:800;color:var(--color-text);margin:0;letter-spacing:-.01em">Gestión de Tareas</h3>
                <p style="font-size:12px;color:var(--color-text-muted);margin:4px 0 0">Estado actual del equipo</p>
            </div>
            <a href="tareas.php" class="btn btn-outline btn-sm" style="font-size:12px">Ver todas →</a>
        </div>

        <!-- Totales tareas hero -->
        <div class="dash-card-dark" style="margin-bottom:14px;display:flex;align-items:center;gap:16px">
            <div>
                <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.08em">Tareas activas</div>
                <div style="font-size:42px;font-weight:900;color:#fff;line-height:1;margin:6px 0 4px"><?=$tPend+$tProg+$tRev?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.4)"><?=$tCompMes?> completadas este mes</div>
            </div>
            <div style="flex:1"></div>
            <?php if($tAtras>0): ?>
            <div style="text-align:right">
                <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Atrasadas</div>
                <div style="font-size:36px;font-weight:900;color:#f87171;line-height:1"><?=$tAtras?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.4);margin-top:3px">Requieren atención</div>
            </div>
            <?php else: ?>
            <div style="text-align:right">
                <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Alta prioridad</div>
                <div style="font-size:36px;font-weight:900;color:#c9f31d;line-height:1"><?=$tAlta?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.4);margin-top:3px">Urgentes activas</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Status grid tareas -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px">
            <?php
            $tStatCards = [
                ['Pendiente',   $tPend,    '#f8fafc','#0f172a','#e2e8f0','#475569'],
                ['En Progreso', $tProg,    '#eef2ff','#4f46e5','#e0e7ff','#4f46e5'],
                ['Revisión',    $tRev,     '#fffbeb','#b45309','#fef3c7','#b45309'],
                ['Completadas', $tCompMes, '#f0fdf4','#16a34a','#dcfce7','#16a34a'],
            ];
            foreach($tStatCards as [$lbl,$val,$bg,$num,$pbg,$pcol]): ?>
            <div style="background:<?=$bg?>;border-radius:12px;padding:12px;text-align:center;border:1.5px solid <?=$pbg?>">
                <div style="font-size:22px;font-weight:900;color:<?=$num?>;line-height:1"><?=$val?></div>
                <div style="font-size:10px;font-weight:700;color:<?=$pcol?>;margin-top:5px;text-transform:uppercase;letter-spacing:.04em"><?=$lbl?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Lista tareas activas -->
        <div class="dash-card" style="padding:0;overflow:hidden">
            <div style="padding:12px 18px;border-bottom:1px solid #f1f5f9;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em">
                Próximas a vencer
            </div>
            <?php if(empty($tareasRecientes)): ?>
            <div style="padding:32px;text-align:center;color:#94a3b8;font-size:13px">Sin tareas activas.</div>
            <?php else: ?>
            <?php
            $pCol = ['alta'=>'#dc2626','media'=>'#b45309','baja'=>'#64748b'];
            $pBg  = ['alta'=>'#fee2e2','media'=>'#fef3c7','baja'=>'#f1f5f9'];
            $pLbl = ['alta'=>'Alta','media'=>'Media','baja'=>'Baja'];
            foreach($tareasRecientes as $t):
                $vencida = $t['fecha_limite'] && $t['fecha_limite'] < $hoy;
            ?>
            <div class="task-row" style="padding:11px 18px;<?=$vencida?'background:#fff5f5':''?>">
                <div style="width:4px;height:36px;border-radius:2px;background:<?=$pCol[$t['prioridad']]??'#64748b'?>;flex-shrink:0"></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:700;color:<?=$vencida?'#dc2626':'#0f172a'?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=sanitize($t['titulo'])?></div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                        <?=$t['cnom'] ? sanitize($t['cnom']) : ($t['responsable'] ? sanitize($t['responsable']) : '—') ?>
                        <?php if($t['fecha_limite']): ?> · <span style="font-weight:<?=$vencida?'700':'400'?>;color:<?=$vencida?'#dc2626':'#94a3b8'?>"><?=date('d M',strtotime($t['fecha_limite']))?><?=$vencida?' ⚠':''?></span><?php endif; ?>
                    </div>
                </div>
                <span style="font-size:10px;font-weight:700;background:<?=$pBg[$t['prioridad']]??'#f1f5f9'?>;color:<?=$pCol[$t['prioridad']]??'#64748b'?>;padding:3px 9px;border-radius:20px;flex-shrink:0"><?=$pLbl[$t['prioridad']]??'—'?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── Fila inferior: Leads recientes + Actividad de conversión ──────────── -->
<div class="dash-bot-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Leads Recientes -->
    <div class="dash-card" style="padding:0;overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:14px;font-weight:800;color:#0f172a">Leads Recientes</span>
            <a href="leads.php" style="font-size:12px;font-weight:600;color:#4f46e5;text-decoration:none">Ver todos →</a>
        </div>
        <?php if(empty($leadsRecientes)): ?>
        <div style="padding:40px;text-align:center;color:#94a3b8;font-size:13px">Sin leads registrados.</div>
        <?php else: ?>
        <?php foreach($leadsRecientes as $l): ?>
        <a href="leads.php" style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f8fafc;text-decoration:none;transition:background .12s" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background=''">
            <div style="width:36px;height:36px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:#475569;flex-shrink:0"><?=getInitials($l['nombre'])?></div>
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=sanitize($l['nombre'])?></div>
                <div style="font-size:11px;color:#94a3b8"><?=sanitize($l['servicio_interes'])?></div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <span class="badge <?=getLeadStatusBadge($l['estado'])?>" style="font-size:10px"><?=getLeadStatusLabel($l['estado'])?></span>
                <div style="font-size:10px;color:#cbd5e1;margin-top:3px"><?=date('d M',strtotime($l['created_at']))?></div>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Resumen financiero del mes -->
    <div class="dash-card" style="padding:0;overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:14px;font-weight:800;color:#0f172a">Resumen Financiero</span>
            <a href="finanzas.php" style="font-size:12px;font-weight:600;color:#4f46e5;text-decoration:none">Ver finanzas →</a>
        </div>
        <div style="padding:20px">
            <!-- Barra ingresos vs egresos -->
            <?php
            $totalFlow = $ingresosmes + $egresosmes;
            $ingW = $totalFlow > 0 ? round($ingresosmes/$totalFlow*100) : 50;
            $egrW = 100 - $ingW;
            ?>
            <div style="margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:7px">
                    <span>INGRESOS <?=$ingW?>%</span><span>EGRESOS <?=$egrW?>%</span>
                </div>
                <div style="height:10px;border-radius:5px;overflow:hidden;display:flex;background:#f1f5f9">
                    <div style="width:<?=$ingW?>%;background:repeating-linear-gradient(-55deg,#c9f31d,#c9f31d 5px,#b5dd18 5px,#b5dd18 10px);border-radius:5px 0 0 5px;transition:width .5s"></div>
                    <div style="width:<?=$egrW?>%;background:repeating-linear-gradient(-55deg,#fca5a5,#fca5a5 5px,#f87171 5px,#f87171 10px);border-radius:0 5px 5px 0;transition:width .5s"></div>
                </div>
            </div>
            <!-- Datos clave -->
            <?php
            $finRows = [
                ['Ingresos cobrados',  formatMoney($ingresosmes),  '#16a34a', '#f0fdf4'],
                ['Egresos pagados',    formatMoney($egresosmes),   '#dc2626', '#fff5f5'],
                ['Ganancia neta',      formatMoney($gananciaNet),  $gananciaNet>=0?'#16a34a':'#dc2626', $gananciaNet>=0?'#f0fdf4':'#fff5f5'],
                ['Por cobrar',         formatMoney($porCobrar),    '#b45309', '#fffbeb'],
                ['Por pagar',          formatMoney($porPagar),     '#64748b', '#f8fafc'],
            ];
            foreach($finRows as [$lbl,$val,$col,$bgr]): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border-radius:8px;background:<?=$bgr?>;margin-bottom:6px">
                <span style="font-size:12px;font-weight:600;color:#475569"><?=$lbl?></span>
                <span style="font-size:13px;font-weight:800;color:<?=$col?>"><?=$val?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
async function enviarResumenDiario() {
    const btn = document.getElementById('btnEnviarResumen');
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Enviando...';
    try {
        const r = await fetch('api/notificacion_resumen.php', { credentials: 'include' });
        const d = await r.json();
        if (d.success) {
            showToast(`✓ Resumen enviado · ${d.tareas} tareas · ${d.renovaciones} renovaciones`, 'success');
        } else {
            showToast(d.error || 'Error al enviar', 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/> </svg> Enviar resumen';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
