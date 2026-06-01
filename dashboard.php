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

// ── Actividad reciente ────────────────────────────────────────────────────────
$actividadLog = $pdo->query("
    SELECT al.accion, al.entidad, al.entidad_id, al.detalles, al.created_at,
           u.nombre AS user_nombre
    FROM actividad_log al
    LEFT JOIN usuarios u ON u.id = al.usuario_id
    ORDER BY al.created_at DESC
    LIMIT 25
")->fetchAll();

// Helpers para actividad
function actIconEmoji(string $accion): string {
    if (strpos($accion,'notif') !== false || strpos($accion,'renovacion') !== false) return '🔔';
    if (strpos($accion,'login')  !== false) return '🔐';
    if (strpos($accion,'logout') !== false) return '🔓';
    if (strpos($accion,'webhook') !== false) return '🌐';
    if (strpos($accion,'eliminar') !== false || strpos($accion,'delete') !== false) return '🗑️';
    if (strpos($accion,'enviar') !== false || strpos($accion,'email') !== false || strpos($accion,'correo') !== false) return '📧';
    if (strpos($accion,'crear') !== false || strpos($accion,'nuevo') !== false) return '➕';
    if (strpos($accion,'editar') !== false || strpos($accion,'actualizar') !== false || strpos($accion,'update') !== false) return '✏️';
    if (strpos($accion,'pago') !== false || strpos($accion,'cobro') !== false || strpos($accion,'transac') !== false) return '💳';
    if (strpos($accion,'nota') !== false) return '📝';
    if (strpos($accion,'chat') !== false) return '💬';
    if (strpos($accion,'cotiz') !== false) return '📄';
    return '📋';
}

function actIconBg(string $accion): string {
    if (strpos($accion,'notif') !== false || strpos($accion,'renovacion') !== false) return '#FEF3C7';
    if (strpos($accion,'login')  !== false || strpos($accion,'logout') !== false)    return '#E0E7FF';
    if (strpos($accion,'webhook') !== false) return '#F3E8FF';
    if (strpos($accion,'eliminar') !== false) return '#FEE2E2';
    if (strpos($accion,'enviar') !== false || strpos($accion,'email') !== false || strpos($accion,'correo') !== false) return '#D1FAE5';
    if (strpos($accion,'crear') !== false || strpos($accion,'nuevo') !== false) return '#E0F2FE';
    return '#EFECE5';
}

function actDesc(string $accion, ?string $entidad): string {
    $entLabels = ['leads'=>'Lead','clientes'=>'Cliente','tareas'=>'Tarea','transacciones'=>'Transacción','clientes_archivos'=>'Archivo','cotizaciones'=>'Cotización','cliente_servicios'=>'Servicio','crm_chat_sessions'=>'Chat'];
    $entLabel = $entLabels[$entidad ?? ''] ?? (strlen($entidad??'') ? ucfirst($entidad) : '');
    $map = [
        'webhook_lead'   => 'Nuevo lead de WordPress',
        'crear'          => 'Creó' . ($entLabel ? " · $entLabel" : ''),
        'editar'         => 'Editó' . ($entLabel ? " · $entLabel" : ''),
        'actualizar'     => 'Actualizó' . ($entLabel ? " · $entLabel" : ''),
        'eliminar'       => 'Eliminó' . ($entLabel ? " · $entLabel" : ''),
        'enviar'         => 'Envió' . ($entLabel ? " · $entLabel" : ''),
        'login'          => 'Inició sesión',
        'logout'         => 'Cerró sesión',
        'notif_enviada'  => 'Notificación enviada',
        'cambiar_estado' => 'Cambió estado' . ($entLabel ? " · $entLabel" : ''),
    ];
    foreach ($map as $k => $v) {
        if (strpos($accion, $k) !== false) return $v;
    }
    return $accion . ($entLabel ? " · $entLabel" : '');
}

$pageTitle    = 'Dashboard';
$pageSubtitle = '';
include __DIR__ . '/includes/header.php';
?>

<style>
/* ── DS tokens aplicados al dashboard ─────────────────────────────────── */
.dash-card        { background:#FFFFFF;border-radius:3px;padding:20px 22px;border:1.5px solid #E8E5DD;transition:box-shadow .15s; }
.dash-card:hover  { box-shadow:0 2px 8px rgba(14,14,12,.08); }
.dash-card-dark   { background:#0E0E0C;border-radius:3px;padding:20px 22px;border:1.5px solid #1A1A18; }
.dash-card-indigo { background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:3px;padding:20px 22px; }

.big-num   { font-size:28px;font-weight:900;line-height:1; }
.big-num-sm{ font-size:26px;font-weight:900;line-height:1; }
.cop-label { font-size:10px;color:#8A867C;margin-top:5px;margin-bottom:10px; }

.pct-pill  { display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:100px;font-size:10px;font-weight:700; }

.pipe-row       { display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid rgba(14,14,12,.05); }
.pipe-row:last-child { border-bottom:none; }
.pipe-bar-track { flex:1;height:9px;background:#EFECE5;border-radius:3px;overflow:hidden; }
.pipe-bar-fill  { height:100%;border-radius:3px;background:repeating-linear-gradient(-55deg,#0E0E0C,#0E0E0C 6px,#2A2926 6px,#2A2926 12px);transition:width .5s ease; }
.pipe-bar-fill.red   { background:repeating-linear-gradient(-55deg,#B0382F,#B0382F 6px,#8a2b23 6px,#8a2b23 12px); }
.pipe-bar-fill.indigo{ background:repeating-linear-gradient(-55deg,#57544D,#57544D 6px,#3F3D38 6px,#3F3D38 12px); }
.pipe-bar-fill.slate { background:repeating-linear-gradient(-55deg,#8A867C,#8A867C 6px,#6e6a63 6px,#6e6a63 12px); }
.pipe-bar-fill.warn  { background:repeating-linear-gradient(-55deg,#B47A1E,#B47A1E 6px,#8e5f17 6px,#8e5f17 12px); }

.task-row { display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #EFECE5; }
.task-row:last-child { border-bottom:none; }

.month-nav { display:inline-flex;align-items:center;gap:0;border-radius:3px;overflow:hidden;border:1.5px solid var(--color-border); }
.month-nav a, .month-nav span { padding:7px 14px;font-size:13px;font-weight:700;color:var(--color-text-muted);text-decoration:none;transition:background .15s; }
.month-nav a:hover { background:#EFECE5; }
.month-nav .active { background:#0E0E0C;color:#fff; }
.month-nav .sep { padding:0;width:1px;background:var(--color-border); }

/* ── Widget panel ────────────────────────────────────────────────── */
.wg-overlay {
    position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1099;
    opacity:0;pointer-events:none;transition:opacity .2s;
}
.wg-overlay.open { opacity:1;pointer-events:all; }
.wg-panel {
    position:fixed;top:0;right:0;width:300px;height:100dvh;
    background:#fff;z-index:1100;
    box-shadow:-4px 0 24px rgba(0,0,0,.12);
    transform:translateX(100%);transition:transform .25s cubic-bezier(.4,0,.2,1);
    overflow-y:auto;display:flex;flex-direction:column;
}
.wg-panel.open { transform:translateX(0); }
.wg-toggle-row {
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 20px;border-bottom:1px solid #EFECE5;cursor:pointer;
    transition:background .12s;gap:12px;
}
.wg-toggle-row:hover { background:#FAFAF7; }
.wg-toggle-row label { flex:1;cursor:pointer;font-size:13.5px;font-weight:600;color:#0E0E0C;user-select:none; }
.wg-toggle-row small { font-size:11px;color:#8A867C;display:block;font-weight:400;margin-top:1px; }
/* custom checkbox toggle */
.wg-switch { position:relative;width:38px;height:22px;flex-shrink:0; }
.wg-switch input { opacity:0;width:0;height:0; }
.wg-switch-slider {
    position:absolute;inset:0;background:#E8E5DD;border-radius:12px;cursor:pointer;
    transition:background .2s;
}
.wg-switch-slider::before {
    content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;
    left:3px;top:3px;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.25);
}
.wg-switch input:checked + .wg-switch-slider { background:#0E0E0C; }
.wg-switch input:checked + .wg-switch-slider::before { transform:translateX(16px); }

/* ── Activity feed ───────────────────────────────────────────────── */
.act-item {
    display:flex;align-items:flex-start;gap:11px;
    padding:11px 18px;border-bottom:1px solid #EFECE5;
    transition:background .12s;
}
.act-item:last-child { border-bottom:none; }
.act-item:hover { background:#FAFAF7; }
.act-icon {
    width:30px;height:30px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    font-size:14px;flex-shrink:0;margin-top:1px;
}

/* Widget hidden — usa !important para no pisar display:grid de inline styles */
[data-widget].widget-hidden { display:none !important; }

@media(max-width:960px){
  .dash-main-grid { grid-template-columns:1fr !important; }
  .dash-fin-grid  { grid-template-columns:1fr 1fr !important; }
  .dash-bot-grid  { grid-template-columns:1fr !important; }
}
@media(max-width:600px){
  .dash-fin-grid  { grid-template-columns:1fr !important; }
  .big-num        { font-size:24px !important; }
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
        <a href="?" class="btn btn-secondary">Mes actual</a>
        <?php endif; ?>
        <a href="finanzas.php" class="btn btn-secondary">Ir a Finanzas</a>
        <button onclick="openWidgetPanel()" class="btn btn-secondary" title="Personalizar widgets">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            Personalizar
        </button>
        <button id="btnEnviarResumen" onclick="enviarResumenDiario()" class="btn btn-accent">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Enviar resumen
        </button>
    </div>
</div>

<!-- ── Fila financiera principal ─────────────────────────────────────────── -->
<div data-widget="finanzas" class="dash-fin-grid" style="display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr;gap:14px;margin-bottom:20px">

    <!-- Ingresos — card hero -->
    <a href="finanzas.php" style="text-decoration:none;display:block;background:#E3F1E8;border:1.5px solid #B8DEC5;border-radius:3px;padding:20px 22px;transition:box-shadow .15s"
        onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
        <div style="font-size:10px;font-weight:700;color:#1B5A39;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Ingresos · <?=$mesLabel?></div>
        <div class="big-num" style="color:#0E0E0C"><?=moneyNum($ingresosmes)?></div>
        <div class="cop-label">COP</div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
            <span style="font-size:11px;color:#57544D"><?=$porCobrar>0 ? formatMoney($porCobrar).' por cobrar' : 'Sin pendientes'?></span>
            <span class="pct-pill" style="background:<?=$pctIng>=0?'#C8EAD3':'#EFCFCC'?>;color:<?=$pctIng>=0?'#1B5A39':'#6E211B'?>">
                <?=$pctIng>=0?'↑':'↓'?> <?=abs($pctIng)?>%
            </span>
        </div>
    </a>

    <!-- Egresos -->
    <a href="finanzas.php" style="text-decoration:none;display:block;background:#F4DEDB;border:1.5px solid #E8BCB8;border-radius:3px;padding:20px 22px;transition:box-shadow .15s"
        onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
        <div style="font-size:10px;font-weight:700;color:#6E211B;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Egresos</div>
        <div class="big-num-sm" style="color:#0E0E0C"><?=moneyNum($egresosmes)?></div>
        <div class="cop-label">COP</div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
            <span style="font-size:11px;color:#57544D"><?=$porPagar>0 ? formatMoney($porPagar).' por pagar' : 'Sin pendientes'?></span>
            <span class="pct-pill" style="background:<?=$pctEgr<=0?'#C8EAD3':'#EFCFCC'?>;color:<?=$pctEgr<=0?'#1B5A39':'#6E211B'?>">
                <?=$pctEgr>=0?'↑':'↓'?> <?=abs($pctEgr)?>%
            </span>
        </div>
    </a>

    <!-- Ganancia Neta -->
    <?php $netPos = $gananciaNet >= 0; ?>
    <a href="finanzas.php" style="text-decoration:none;display:block;background:<?=$netPos?'#C6F24E':'#F4DEDB'?>;border:1.5px solid <?=$netPos?'#A8D87A':'#E8BCB8'?>;border-radius:3px;padding:20px 22px;transition:box-shadow .15s"
        onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
        <div style="font-size:10px;font-weight:700;color:<?=$netPos?'rgba(0,0,0,.55)':'#6E211B'?>;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Ganancia Neta</div>
        <div class="big-num-sm" style="color:#0E0E0C"><?=moneyNum($gananciaNet)?></div>
        <div class="cop-label">COP</div>
        <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:11px;color:<?=$netPos?'rgba(0,0,0,.5)':'#8A4A44'?>"><?=$netPos?'Rentable este período':'Balance en rojo'?></span>
            <span class="pct-pill" style="background:<?=$netPos?'rgba(0,0,0,0.12)':'#EFCFCC'?>;color:<?=$netPos?'#0E0E0C':'#6E211B'?>">
                <?=$netPos?'Positivo':'Negativo'?>
            </span>
        </div>
    </a>

    <!-- Clientes Activos -->
    <a href="clientes.php" style="text-decoration:none;display:block;background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:3px;padding:20px 22px;transition:box-shadow .15s"
        onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
        <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Clientes Activos</div>
        <div class="big-num-sm" style="color:#0E0E0C"><?=$clientesActivos?></div>
        <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
            <?php if($renovacionesMes>0): ?>
            <span style="font-size:11px;color:#B47A1E;font-weight:600">⚠ <?=$renovacionesMes?> renovac. este mes</span>
            <?php else: ?>
            <span style="font-size:11px;color:#57544D">Sin renovaciones urgentes</span>
            <?php endif; ?>
            <span class="pct-pill" style="background:#E8E5DD;color:#0E0E0C">Cartera</span>
        </div>
    </a>

</div>

<!-- ── Cuerpo principal ───────────────────────────────────────────────────── -->
<div class="dash-main-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

    <!-- ── LEADS ──────────────────────────────────────────────────────────── -->
    <div data-widget="leads">
        <!-- Header leads -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
            <div>
                <h3 style="font-size:18px;font-weight:800;color:var(--color-text);margin:0;letter-spacing:-.01em">Pipeline de Leads</h3>
                <p style="font-size:12px;color:var(--color-text-muted);margin:4px 0 0"><?=$mesLabel?></p>
            </div>
            <a href="leads.php" class="btn btn-secondary btn-sm">Ver todos →</a>
        </div>

        <!-- Totales leads hero -->
        <a href="leads.php" class="dash-card-indigo" style="margin-bottom:14px;display:flex;align-items:center;gap:20px;text-decoration:none;display:flex;transition:box-shadow .15s" onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div>
                <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Total leads mes</div>
                <div style="font-size:28px;font-weight:900;color:#0E0E0C;line-height:1;margin-bottom:4px"><?=$lTotal?></div>
                <div style="font-size:11px;color:#8A867C">
                    <?php if($lTotalPrev>0): ?>
                    <span style="color:<?=$pctLeads>=0?'#1B5A39':'#6E211B'?>;font-weight:700"><?=$pctLeads>=0?'↑':'↓'?> <?=abs($pctLeads)?>%</span> vs mes ant.
                    <?php else: ?>Primer registro<?php endif; ?>
                </div>
            </div>
            <div style="flex:1"></div>
            <div style="text-align:right">
                <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Conversión</div>
                <div style="font-size:28px;font-weight:900;color:#0E0E0C;line-height:1"><?=$tasaConv?>%</div>
                <div style="font-size:11px;color:#8A867C;margin-top:4px"><?=$lGanado?> ganados</div>
            </div>
        </a>

        <!-- Pipeline funnel bars -->
        <div class="dash-card" style="padding:18px 20px">
            <div style="font-size:11px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.07em;margin-bottom:14px">Distribución por etapa</div>
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
                <span style="font-size:12px;font-weight:600;color:#57544D;width:90px;flex-shrink:0"><?=$label?></span>
                <div class="pipe-bar-track">
                    <div class="pipe-bar-fill <?=$cls?>" style="width:<?=$w?>%"></div>
                </div>
                <span style="font-size:13px;font-weight:700;color:#0E0E0C;width:28px;text-align:right;flex-shrink:0"><?=$cnt?></span>
                <span style="font-size:11px;color:#8A867C;width:32px;flex-shrink:0"><?=$w?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── TAREAS ──────────────────────────────────────────────────────────── -->
    <div data-widget="tareas">
        <!-- Header tareas -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
            <div>
                <h3 style="font-size:18px;font-weight:800;color:var(--color-text);margin:0;letter-spacing:-.01em">Gestión de Tareas</h3>
                <p style="font-size:12px;color:var(--color-text-muted);margin:4px 0 0">Estado actual del equipo</p>
            </div>
            <a href="tareas.php" class="btn btn-secondary btn-sm">Ver todas →</a>
        </div>

        <!-- Totales tareas hero -->
        <a href="tareas.php" class="dash-card-dark" style="margin-bottom:14px;display:flex;align-items:center;gap:16px;text-decoration:none;transition:box-shadow .15s" onmouseenter="this.style.boxShadow='0 3px 12px rgba(0,0,0,.25)'" onmouseleave="this.style.boxShadow='none'">
            <div>
                <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Tareas activas</div>
                <div style="font-size:28px;font-weight:900;color:#fff;line-height:1;margin-bottom:4px"><?=$tPend+$tProg+$tRev?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.45)"><?=$tCompMes?> completadas este mes</div>
            </div>
            <div style="flex:1"></div>
            <?php if($tAtras>0): ?>
            <div style="text-align:right">
                <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Atrasadas</div>
                <div style="font-size:28px;font-weight:900;color:#F4DEDB;line-height:1"><?=$tAtras?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.45);margin-top:4px">Requieren atención</div>
            </div>
            <?php else: ?>
            <div style="text-align:right">
                <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Alta prioridad</div>
                <div style="font-size:28px;font-weight:900;color:#C6F24E;line-height:1"><?=$tAlta?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.45);margin-top:4px">Urgentes activas</div>
            </div>
            <?php endif; ?>
        </a>

        <!-- Status grid tareas -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px">
            <?php
            $tStatCards = [
                ['Pendiente',   $tPend,    '#FAFAF7','#0E0E0C','#E8E5DD','#57544D', 'tareas.php'],
                ['En Progreso', $tProg,    '#FAFAF7','#0E0E0C','#E8E5DD','#57544D', 'tareas.php'],
                ['Revisión',    $tRev,     '#F5EBD3','#6E4A12','#D6D2C7','#B47A1E', 'tareas.php'],
                ['Completadas', $tCompMes, '#E3F1E8','#1B5A39','#E8E5DD','#2D8F5A', 'tareas.php'],
            ];
            foreach($tStatCards as [$lbl,$val,$bg,$num,$pbg,$pcol,$href]): ?>
            <a href="<?=$href?>" style="background:<?=$bg?>;border-radius:3px;padding:12px;text-align:center;border:1.5px solid <?=$pbg?>;transition:box-shadow .15s;text-decoration:none;display:block"
                onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
                <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1"><?=$val?></div>
                <div style="font-size:10px;font-weight:700;color:<?=$pcol?>;margin-top:5px;text-transform:uppercase;letter-spacing:.07em"><?=$lbl?></div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Lista tareas activas -->
        <div class="dash-card" style="padding:0;overflow:hidden">
            <div style="padding:12px 18px;border-bottom:1px solid #EFECE5;font-size:11px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.07em">
                Próximas a vencer
            </div>
            <?php if(empty($tareasRecientes)): ?>
            <div style="padding:32px;text-align:center;color:#8A867C;font-size:13px">Sin tareas activas.</div>
            <?php else: ?>
            <?php
            $pCol = ['alta'=>'#B0382F','media'=>'#B47A1E','baja'=>'#57544D'];
            $pBg  = ['alta'=>'#F4DEDB','media'=>'#F5EBD3','baja'=>'#EFECE5'];
            $pLbl = ['alta'=>'Alta','media'=>'Media','baja'=>'Baja'];
            foreach($tareasRecientes as $t):
                $vencida = $t['fecha_limite'] && $t['fecha_limite'] < $hoy;
            ?>
            <div class="task-row" style="padding:11px 18px;<?=$vencida?'background:#F4DEDB':''?>">
                <div style="width:4px;height:36px;border-radius:2px;background:<?=$pCol[$t['prioridad']]??'#57544D'?>;flex-shrink:0"></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:700;color:<?=$vencida?'#6E211B':'#0E0E0C'?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=sanitize($t['titulo'])?></div>
                    <div style="font-size:11px;color:#8A867C;margin-top:2px">
                        <?=$t['cnom'] ? sanitize($t['cnom']) : ($t['responsable'] ? sanitize($t['responsable']) : '—') ?>
                        <?php if($t['fecha_limite']): ?> · <span style="font-weight:<?=$vencida?'700':'400'?>;color:<?=$vencida?'#B0382F':'#8A867C'?>"><?=date('d M',strtotime($t['fecha_limite']))?><?=$vencida?' ⚠':''?></span><?php endif; ?>
                    </div>
                </div>
                <span style="font-size:10px;font-weight:700;background:<?=$pBg[$t['prioridad']]??'#EFECE5'?>;color:<?=$pCol[$t['prioridad']]??'#57544D'?>;padding:3px 9px;border-radius:3px;flex-shrink:0"><?=$pLbl[$t['prioridad']]??'—'?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── Fila inferior ─────────────────────────────────────────────────────── -->
<div class="dash-bot-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px">

    <!-- Leads Recientes -->
    <div data-widget="leads_recientes" class="dash-card" style="padding:0;overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid #EFECE5;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:14px;font-weight:700;color:#0E0E0C">Leads Recientes</span>
            <a href="leads.php" style="font-size:12px;font-weight:600;color:#57544D;text-decoration:none">Ver todos →</a>
        </div>
        <?php if(empty($leadsRecientes)): ?>
        <div style="padding:40px;text-align:center;color:#8A867C;font-size:13px">Sin leads registrados.</div>
        <?php else: ?>
        <?php foreach($leadsRecientes as $l): ?>
        <a href="leads.php" style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #EFECE5;text-decoration:none;transition:background .12s" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background=''">
            <div style="width:36px;height:36px;border-radius:3px;background:#EFECE5;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#57544D;flex-shrink:0"><?=getInitials($l['nombre'])?></div>
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:700;color:#0E0E0C;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=sanitize($l['nombre'])?></div>
                <div style="font-size:11px;color:#8A867C"><?=sanitize($l['servicio_interes'])?></div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <span class="badge <?=getLeadStatusBadge($l['estado'])?>" style="font-size:10px"><?=getLeadStatusLabel($l['estado'])?></span>
                <div style="font-size:10px;color:#8A867C;margin-top:3px"><?=date('d M',strtotime($l['created_at']))?></div>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Resumen financiero del mes -->
    <div data-widget="resumen_financiero" class="dash-card" style="padding:0;overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid #EFECE5;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:14px;font-weight:700;color:#0E0E0C">Resumen Financiero</span>
            <a href="finanzas.php" style="font-size:12px;font-weight:600;color:#57544D;text-decoration:none">Ver finanzas →</a>
        </div>
        <div style="padding:20px">
            <!-- Barra ingresos vs egresos -->
            <?php
            $totalFlow = $ingresosmes + $egresosmes;
            $ingW = $totalFlow > 0 ? round($ingresosmes/$totalFlow*100) : 50;
            $egrW = 100 - $ingW;
            ?>
            <div style="margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:#8A867C;margin-bottom:7px">
                    <span>INGRESOS <?=$ingW?>%</span><span>EGRESOS <?=$egrW?>%</span>
                </div>
                <div style="height:10px;border-radius:3px;overflow:hidden;display:flex;background:#EFECE5">
                    <div style="width:<?=$ingW?>%;background:repeating-linear-gradient(-55deg,#2D8F5A,#2D8F5A 5px,#1B5A39 5px,#1B5A39 10px);border-radius:3px 0 0 3px;transition:width .5s"></div>
                    <div style="width:<?=$egrW?>%;background:repeating-linear-gradient(-55deg,#B0382F,#B0382F 5px,#8a2b23 5px,#8a2b23 10px);border-radius:0 3px 3px 0;transition:width .5s"></div>
                </div>
            </div>
            <!-- Datos clave -->
            <?php
            $finRows = [
                ['Ingresos cobrados',  formatMoney($ingresosmes),  '#1B5A39', '#E3F1E8'],
                ['Egresos pagados',    formatMoney($egresosmes),   '#6E211B', '#F4DEDB'],
                ['Ganancia neta',      formatMoney($gananciaNet),  $gananciaNet>=0?'#1B5A39':'#6E211B', $gananciaNet>=0?'#E3F1E8':'#F4DEDB'],
                ['Por cobrar',         formatMoney($porCobrar),    '#6E4A12', '#F5EBD3'],
                ['Por pagar',          formatMoney($porPagar),     '#57544D', '#FAFAF7'],
            ];
            foreach($finRows as [$lbl,$val,$col,$bgr]): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border-radius:4px;background:<?=$bgr?>;margin-bottom:6px">
                <span style="font-size:12px;font-weight:600;color:#57544D"><?=$lbl?></span>
                <span style="font-size:13px;font-weight:700;color:<?=$col?>"><?=$val?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Historial de actividad -->
    <div data-widget="actividad" class="dash-card" style="padding:0;overflow:hidden;display:flex;flex-direction:column">
        <div style="padding:16px 20px;border-bottom:1px solid #EFECE5;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
            <span style="font-size:14px;font-weight:700;color:#0E0E0C">Actividad Reciente</span>
            <span style="font-size:11px;color:#8A867C">Últimas acciones</span>
        </div>
        <?php if(empty($actividadLog)): ?>
        <div style="padding:40px;text-align:center;color:#8A867C;font-size:13px">Sin actividad registrada.</div>
        <?php else: ?>
        <div style="overflow-y:auto;flex:1;max-height:420px">
            <?php foreach($actividadLog as $act):
                $ico   = actIconEmoji($act['accion']);
                $icoBg = actIconBg($act['accion']);
                $desc  = actDesc($act['accion'], $act['entidad']);
                $det   = $act['detalles'] ? mb_strimwidth($act['detalles'], 0, 58, '…') : '';
                $who   = $act['user_nombre'] ? sanitize($act['user_nombre']) : 'Sistema';
                $ts    = $act['created_at'];
            ?>
            <div class="act-item">
                <div class="act-icon" style="background:<?=$icoBg?>"><?=$ico?></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:12.5px;font-weight:700;color:#0E0E0C;line-height:1.3"><?=htmlspecialchars($desc,ENT_QUOTES)?></div>
                    <?php if($det): ?>
                    <div style="font-size:11px;color:#57544D;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($det,ENT_QUOTES)?></div>
                    <?php endif; ?>
                    <div style="font-size:10px;color:#8A867C;margin-top:3px;display:flex;gap:6px">
                        <span><?=$who?></span>
                        <span>·</span>
                        <span data-ts="<?=htmlspecialchars($ts,ENT_QUOTES)?>"><?=date('d M H:i',strtotime($ts))?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Widget config panel ────────────────────────────────────────────────── -->
<div class="wg-overlay" id="wgOverlay" onclick="closeWidgetPanel()"></div>
<div class="wg-panel" id="wgPanel">
    <div style="padding:18px 20px;border-bottom:1.5px solid #EFECE5;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
        <div>
            <div style="font-size:15px;font-weight:800;color:#0E0E0C">Personalizar Dashboard</div>
            <div style="font-size:11px;color:#8A867C;margin-top:2px">Activa o desactiva cada widget</div>
        </div>
        <button onclick="closeWidgetPanel()" style="background:none;border:none;cursor:pointer;padding:6px;color:#8A867C;border-radius:6px;transition:background .15s" onmouseenter="this.style.background='#EFECE5'" onmouseleave="this.style.background='none'">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div style="flex:1;overflow-y:auto">
        <?php
        $wgDefs = [
            'finanzas'           => ['Tarjetas Financieras',  'Ingresos, egresos, ganancia, clientes'],
            'leads'              => ['Pipeline de Leads',     'Embudo y distribución por etapa'],
            'tareas'             => ['Gestión de Tareas',     'Estado, prioridades y próximas a vencer'],
            'leads_recientes'    => ['Leads Recientes',       'Últimos 5 leads del sistema'],
            'resumen_financiero' => ['Resumen Financiero',    'Barras y datos clave del mes'],
            'actividad'          => ['Actividad Reciente',    'Historial de acciones del CRM'],
        ];
        foreach($wgDefs as $wid => [$wname, $wdesc]):
        ?>
        <div class="wg-toggle-row" onclick="document.getElementById('wgcb-<?=$wid?>').click()">
            <div style="flex:1">
                <label for="wgcb-<?=$wid?>" style="font-size:13.5px;font-weight:600;color:#0E0E0C;cursor:pointer"><?=$wname?></label>
                <small style="font-size:11px;color:#8A867C;display:block;margin-top:1px"><?=$wdesc?></small>
            </div>
            <label class="wg-switch" onclick="event.stopPropagation()">
                <input type="checkbox" id="wgcb-<?=$wid?>" checked onchange="toggleWidget('<?=$wid?>',this.checked)">
                <span class="wg-switch-slider"></span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="padding:16px 20px;border-top:1px solid #EFECE5;flex-shrink:0">
        <button onclick="resetWidgetPrefs()" style="width:100%;padding:9px;background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:6px;font-size:12px;font-weight:600;color:#57544D;cursor:pointer;transition:background .15s" onmouseenter="this.style.background='#EFECE5'" onmouseleave="this.style.background='#FAFAF7'">
            Restablecer todo
        </button>
    </div>
</div>

<script>
// ── Resumen diario ────────────────────────────────────────────────────────
async function enviarResumenDiario() {
    const btn = document.getElementById('btnEnviarResumen');
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Enviando…';
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

// ── Widget system ─────────────────────────────────────────────────────────
const WIDGET_KEY     = 'dash_widgets_v1';
const WIDGET_IDS     = ['finanzas','leads','tareas','leads_recientes','resumen_financiero','actividad'];

function loadWidgetPrefs() {
    const defaults = {};
    WIDGET_IDS.forEach(id => defaults[id] = true);
    try {
        const saved = JSON.parse(localStorage.getItem(WIDGET_KEY) || '{}');
        return Object.assign(defaults, saved);
    } catch { return defaults; }
}

function saveWidgetPrefs(prefs) {
    localStorage.setItem(WIDGET_KEY, JSON.stringify(prefs));
}

function applyWidgetPrefs(prefs) {
    WIDGET_IDS.forEach(id => {
        const el = document.querySelector(`[data-widget="${id}"]`);
        if (!el) return;
        // Usar clase en lugar de style.display para no pisar los display:grid inline
        el.classList.toggle('widget-hidden', !prefs[id]);
    });
    // Adjust main grid columns
    const mainGrid = document.querySelector('.dash-main-grid');
    if (mainGrid) {
        const vis = [...mainGrid.children].filter(c => !c.classList.contains('widget-hidden'));
        if (vis.length === 0) {
            mainGrid.classList.add('widget-hidden');
        } else {
            mainGrid.classList.remove('widget-hidden');
            mainGrid.style.gridTemplateColumns = vis.length === 1 ? '1fr' : '1fr 1fr';
        }
    }
    // Adjust bot grid columns
    const botGrid = document.querySelector('.dash-bot-grid');
    if (botGrid) {
        const vis = [...botGrid.children].filter(c => !c.classList.contains('widget-hidden'));
        if (vis.length === 0) {
            botGrid.classList.add('widget-hidden');
        } else {
            botGrid.classList.remove('widget-hidden');
            const cols = Math.min(vis.length, 3);
            botGrid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
        }
    }
}

function openWidgetPanel() {
    const prefs = loadWidgetPrefs();
    WIDGET_IDS.forEach(id => {
        const cb = document.getElementById(`wgcb-${id}`);
        if (cb) cb.checked = prefs[id] !== false;
    });
    document.getElementById('wgOverlay').classList.add('open');
    document.getElementById('wgPanel').classList.add('open');
}

function closeWidgetPanel() {
    document.getElementById('wgOverlay').classList.remove('open');
    document.getElementById('wgPanel').classList.remove('open');
}

function toggleWidget(id, checked) {
    const prefs = loadWidgetPrefs();
    prefs[id] = checked;
    saveWidgetPrefs(prefs);
    applyWidgetPrefs(prefs);
}

function resetWidgetPrefs() {
    localStorage.removeItem(WIDGET_KEY);
    const prefs = loadWidgetPrefs();
    WIDGET_IDS.forEach(id => {
        const cb = document.getElementById(`wgcb-${id}`);
        if (cb) cb.checked = true;
    });
    applyWidgetPrefs(prefs);
    showToast('Dashboard restablecido', 'success');
}

// ── Tiempos relativos ─────────────────────────────────────────────────────
function timeAgo(dateStr) {
    const d   = new Date(dateStr.replace(' ', 'T'));
    const now = new Date();
    const s   = Math.floor((now - d) / 1000);
    if (isNaN(s)) return dateStr;
    if (s < 60)      return 'hace un momento';
    if (s < 3600)    return `hace ${Math.floor(s / 60)} min`;
    if (s < 86400)   return `hace ${Math.floor(s / 3600)}h`;
    if (s < 604800)  return `hace ${Math.floor(s / 86400)}d`;
    return d.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' });
}

document.addEventListener('DOMContentLoaded', () => {
    // Apply stored prefs
    applyWidgetPrefs(loadWidgetPrefs());
    // Relative timestamps in activity feed
    document.querySelectorAll('[data-ts]').forEach(el => {
        el.textContent = timeAgo(el.dataset.ts);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
