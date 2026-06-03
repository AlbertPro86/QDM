<?php
/**
 * CRM QUANTUN Digital — Portal Cliente · Mi Cuenta
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['portal_logged_in'])) {
    header('Location: portal_cliente.php'); exit;
}

$nombre   = htmlspecialchars($_SESSION['portal_nombre'] ?? 'Cliente');
$iniciales = implode('', array_map(fn($p) => mb_strtoupper(mb_substr($p,0,1)),
             array_slice(explode(' ', trim($_SESSION['portal_nombre'] ?? 'C')), 0, 2)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mi Cuenta | QUANTUN Digital</title>
<link rel="icon" href="Assets/logo_quantun_digital_negro.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/styles.css?v=<?= APP_VERSION ?>">
<style>
/* ── Reset portal ──────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #F5F4EF; color: #0E0E0C; min-height: 100vh; }

/* ── Top bar ───────────────────────────────────────────── */
.mc-nav {
    background: #0E0E0C;
    height: 52px;
    display: flex; align-items: center;
    padding: 0 20px; gap: 12px;
    position: sticky; top: 0; z-index: 100;
}
.mc-nav-logo img { height: 24px; display: block; }
.mc-nav-divider  { width: 1px; height: 16px; background: rgba(255,255,255,.12); }
.mc-nav-badge    { font-size: 10.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #C6F24E; background: rgba(198,242,78,.1); padding: 2px 8px; border-radius: 100px; }
.mc-nav-spacer   { flex: 1; }
.mc-nav-user     { display: flex; align-items: center; gap: 8px; }
.mc-nav-avatar   { width: 28px; height: 28px; border-radius: 50%; background: #C6F24E; color: #0E0E0C; font-size: 10.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mc-nav-name     { font-size: 12.5px; font-weight: 600; color: rgba(255,255,255,.85); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mc-nav-logout   { display: flex; align-items: center; gap: 5px; padding: 5px 10px; background: transparent; border: 1px solid rgba(255,255,255,.12); border-radius: 5px; font-size: 12px; font-weight: 500; color: rgba(255,255,255,.5); cursor: pointer; font-family: inherit; transition: all .15s; }
.mc-nav-logout:hover { border-color: rgba(255,255,255,.25); color: rgba(255,255,255,.85); }

/* ── Wrapper ───────────────────────────────────────────── */
.mc-wrap { max-width: 860px; margin: 0 auto; padding: 24px 16px 64px; }

/* ── Banner de alerta ──────────────────────────────────── */
.mc-alert {
    display: none; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 8px; margin-bottom: 16px;
    font-size: 13px; font-weight: 500; line-height: 1.4;
}
.mc-alert.ok     { background: #E3F1E8; color: #1A6B41; }
.mc-alert.warn   { background: #FEF3C7; color: #92400E; }
.mc-alert.danger { background: #FEE2E2; color: #991B1B; }
.mc-alert-icon   { font-size: 16px; flex-shrink: 0; }

/* ── Stats strip ───────────────────────────────────────── */
.mc-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    background: #fff;
    border: 1px solid #E8E5DD;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 20px;
}
.mc-stat {
    padding: 14px 16px;
    border-right: 1px solid #E8E5DD;
}
.mc-stat:last-child { border-right: none; }
.mc-stat-lbl { font-size: 11px; color: #8A867C; font-weight: 500; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
.mc-stat-val { font-size: 20px; font-weight: 800; line-height: 1.1; color: #0E0E0C; }
.mc-stat-val.sm { font-size: 15px; }
.mc-stat-cop { font-size: 10px; color: #8A867C; font-weight: 600; margin-top: 1px; }

/* ── Tabs ──────────────────────────────────────────────── */
.mc-tabs     { display: flex; gap: 3px; margin-bottom: 16px; background: #fff; border: 1px solid #E8E5DD; border-radius: 8px; padding: 4px; }
.mc-tab      { flex: 1; padding: 7px 8px; border: none; border-radius: 5px; background: transparent; font-size: 12.5px; font-weight: 500; color: #8A867C; cursor: pointer; font-family: inherit; transition: all .15s; display: flex; align-items: center; justify-content: center; gap: 5px; white-space: nowrap; }
.mc-tab:hover { background: #F5F4EF; color: #0E0E0C; }
.mc-tab.active { background: #0E0E0C; color: #fff; font-weight: 700; }
.mc-tab svg { opacity: .7; }
.mc-tab.active svg { opacity: 1; }
.mc-pane     { display: none; }
.mc-pane.on  { display: block; }

/* ── Service cards ─────────────────────────────────────── */
.mc-svcs     { display: flex; flex-direction: column; gap: 10px; }
.mc-svc {
    background: #fff; border: 1px solid #E8E5DD; border-radius: 10px;
    padding: 16px 18px; display: flex; align-items: flex-start; gap: 14px;
}
.mc-svc-icon { width: 38px; height: 38px; border-radius: 8px; background: #F5F4EF; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mc-svc-body { flex: 1; min-width: 0; }
.mc-svc-top  { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap; }
.mc-svc-name { font-size: 14.5px; font-weight: 800; color: #0E0E0C; }
.mc-svc-right { text-align: right; flex-shrink: 0; }
.mc-svc-price { font-size: 20px; font-weight: 800; color: #0E0E0C; line-height: 1; }
.mc-svc-pcop  { font-size: 10px; color: #8A867C; font-weight: 600; margin-top: 2px; }
.mc-svc-meta  { display: flex; gap: 14px; flex-wrap: wrap; }
.mc-svc-meta-item { font-size: 12px; color: #57544D; display: flex; align-items: center; gap: 4px; }

/* Estado badge */
.mc-badge { display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 100px; font-size: 11px; font-weight: 700; }
.mc-badge.activo     { background: #E3F1E8; color: #1A6B41; }
.mc-badge.suspendido { background: #FEF3C7; color: #92400E; }
.mc-badge.cancelado  { background: #FEE2E2; color: #991B1B; }

/* Chip de vencimiento */
.mc-chip { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 100px; font-size: 11.5px; font-weight: 600; }
.mc-chip.ok     { background: #E3F1E8; color: #1A6B41; }
.mc-chip.warn   { background: #FEF3C7; color: #92400E; }
.mc-chip.danger { background: #FEE2E2; color: #991B1B; }
.mc-chip.grey   { background: #F5F4EF; color: #8A867C; }

/* ── Historial ─────────────────────────────────────────── */
.mc-hist-wrap { background: #fff; border: 1px solid #E8E5DD; border-radius: 10px; overflow: hidden; }
.mc-hist-row  { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #F0EDE6; transition: background .1s; }
.mc-hist-row:last-child { border-bottom: none; }
.mc-hist-row:hover { background: #FAFAF7; }
.mc-hist-ico  { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mc-hist-body { flex: 1; min-width: 0; }
.mc-hist-ttl  { font-size: 13px; font-weight: 600; color: #0E0E0C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mc-hist-sub  { font-size: 11.5px; color: #8A867C; margin-top: 1px; }
.mc-hist-right { text-align: right; flex-shrink: 0; }
.mc-hist-amt   { font-size: 13.5px; font-weight: 700; color: #0E0E0C; }
.mc-hist-est   { font-size: 11px; font-weight: 700; margin-top: 1px; }
.mc-hist-est.pagado    { color: #1A6B41; }
.mc-hist-est.pendiente { color: #B45309; }
.mc-hist-est.vencido   { color: #991B1B; }
.mc-hist-more { text-align: center; padding: 12px; border-top: 1px solid #F0EDE6; }
.mc-hist-more button { background: none; border: none; font-size: 12.5px; font-weight: 600; color: #57544D; cursor: pointer; font-family: inherit; }
.mc-hist-more button:hover { color: #0E0E0C; }

/* ── Documentos ────────────────────────────────────────── */
.mc-docs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px,1fr)); gap: 10px; }
.mc-doc {
    background: #fff; border: 1px solid #E8E5DD; border-radius: 8px;
    padding: 12px 12px 10px; display: flex; flex-direction: column; gap: 7px;
    text-decoration: none; color: inherit; transition: all .15s;
}
.mc-doc:hover { border-color: #B5B0A6; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.mc-doc-icon  { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
.mc-doc-name  { font-size: 12px; font-weight: 600; color: #0E0E0C; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.mc-doc-date  { font-size: 11px; color: #8A867C; }

/* ── Perfil ────────────────────────────────────────────── */
.mc-perfil { background: #fff; border: 1px solid #E8E5DD; border-radius: 10px; overflow: hidden; margin-bottom: 14px; }
.mc-perfil-head { background: #0E0E0C; padding: 20px 20px 16px; display: flex; align-items: center; gap: 14px; }
.mc-perfil-av   { width: 44px; height: 44px; border-radius: 50%; background: #C6F24E; color: #0E0E0C; font-size: 15px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mc-perfil-nm   { font-size: 16px; font-weight: 800; color: #fff; }
.mc-perfil-since { font-size: 12px; color: rgba(255,255,255,.4); margin-top: 2px; }
.mc-perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.mc-perfil-item { padding: 11px 18px; border-right: 1px solid #F0EDE6; border-bottom: 1px solid #F0EDE6; }
.mc-perfil-item:nth-child(even) { border-right: none; }
.mc-perfil-item:nth-last-child(-n+2) { border-bottom: none; }
.mc-perfil-lbl  { font-size: 10.5px; color: #8A867C; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px; }
.mc-perfil-val  { font-size: 13px; font-weight: 600; color: #0E0E0C; }
.mc-perfil-val.empty { color: #B5B0A6; font-weight: 400; font-style: italic; }

/* Cambiar contraseña */
.mc-pwd { background: #fff; border: 1px solid #E8E5DD; border-radius: 10px; padding: 18px 20px; }
.mc-pwd-title { font-size: 13.5px; font-weight: 700; margin-bottom: 12px; }
.mc-pwd-grid  { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 8px; align-items: end; }
.mc-input { width: 100%; padding: 8px 11px; border: 1.5px solid #E8E5DD; border-radius: 6px; font-size: 13px; font-family: inherit; background: #FAFAF7; color: #0E0E0C; outline: none; transition: border-color .15s; }
.mc-input:focus { border-color: #0E0E0C; background: #fff; }
.mc-btn { padding: 8px 16px; background: #0E0E0C; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer; white-space: nowrap; transition: opacity .15s; }
.mc-btn:hover { opacity: .8; }
.mc-btn:disabled { opacity: .4; cursor: not-allowed; }

/* ── Empty / Loading ───────────────────────────────────── */
.mc-empty { text-align: center; padding: 40px 20px; color: #8A867C; font-size: 13px; }
.mc-empty svg { display: block; margin: 0 auto 10px; opacity: .25; }
.mc-loading { padding: 32px; text-align: center; color: #8A867C; font-size: 13px; }
.mc-skel { background: #E8E5DD; border-radius: 8px; animation: mcsk 1s ease-in-out infinite alternate; }
@keyframes mcsk { from{opacity:.5} to{opacity:1} }

/* ── Toast ─────────────────────────────────────────────── */
#mcToast { position:fixed; bottom:20px; right:20px; z-index:9999; padding:10px 16px; border-radius:7px; font-size:13px; font-weight:600; color:#fff; background:#0E0E0C; box-shadow:0 4px 16px rgba(0,0,0,.15); opacity:0; transform:translateY(6px); transition:all .2s; pointer-events:none; }
#mcToast.show { opacity:1; transform:none; }
#mcToast.ok  { background:#1A6B41; }
#mcToast.err { background:#991B1B; }

/* ── Revocado overlay ──────────────────────────────────── */
#mcRevocado { display:none; position:fixed; inset:0; z-index:9000; background:#fff; flex-direction:column; align-items:center; justify-content:center; gap:14px; text-align:center; padding:24px; }
#mcRevocado.show { display:flex; }

/* ── Responsive ────────────────────────────────────────── */
@media(max-width:640px) {
    .mc-stats { grid-template-columns: 1fr 1fr; }
    .mc-stat:nth-child(2) { border-right: none; }
    .mc-stat:nth-child(3) { border-top: 1px solid #E8E5DD; }
    .mc-svc { flex-direction: column; }
    .mc-svc-right { text-align: left; }
    .mc-perfil-grid { grid-template-columns: 1fr; }
    .mc-perfil-item { border-right: none; }
    .mc-perfil-item:nth-last-child(-n+2) { border-bottom: 1px solid #F0EDE6; }
    .mc-perfil-item:last-child { border-bottom: none; }
    .mc-pwd-grid { grid-template-columns: 1fr; }
    .mc-tab span.tab-lbl { display: none; }
}
</style>
</head>
<body>

<!-- Top bar -->
<nav class="mc-nav">
    <div class="mc-nav-logo"><img src="Assets/logo_quantun_digital_blanco.png" alt="QUANTUN"></div>
    <div class="mc-nav-divider"></div>
    <span class="mc-nav-badge">Mi Cuenta</span>
    <div class="mc-nav-spacer"></div>
    <div class="mc-nav-user">
        <div class="mc-nav-avatar"><?= htmlspecialchars($iniciales) ?></div>
        <span class="mc-nav-name"><?= $nombre ?></span>
    </div>
    <button class="mc-nav-logout" onclick="mcLogout()">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Salir
    </button>
</nav>

<div class="mc-wrap">

    <!-- Alert renovación -->
    <div class="mc-alert" id="mcAlert">
        <span class="mc-alert-icon" id="mcAlertIco"></span>
        <span id="mcAlertTxt"></span>
    </div>

    <!-- Stats strip -->
    <div class="mc-stats">
        <div class="mc-stat">
            <div class="mc-stat-lbl">Servicios activos</div>
            <div class="mc-stat-val" id="stSvcs">—</div>
        </div>
        <div class="mc-stat">
            <div class="mc-stat-lbl">Total pagado</div>
            <div class="mc-stat-val sm" id="stPag">—</div>
            <div class="mc-stat-cop">COP</div>
        </div>
        <div class="mc-stat">
            <div class="mc-stat-lbl">Por pagar</div>
            <div class="mc-stat-val sm" id="stPend">—</div>
            <div class="mc-stat-cop">COP</div>
        </div>
        <div class="mc-stat">
            <div class="mc-stat-lbl">Próxima renovación</div>
            <div class="mc-stat-val sm" id="stRen">—</div>
            <div class="mc-stat-cop" id="stRenSub"></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mc-tabs">
        <button class="mc-tab active" id="tab-svcs" onclick="mcTab('svcs',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            <span class="tab-lbl">Mis Servicios</span>
        </button>
        <button class="mc-tab" id="tab-hist" onclick="mcTab('hist',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span class="tab-lbl">Historial de pagos</span>
        </button>
        <button class="mc-tab" id="tab-docs" onclick="mcTab('docs',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            <span class="tab-lbl">Documentos</span>
        </button>
        <button class="mc-tab" id="tab-perfil" onclick="mcTab('perfil',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="tab-lbl">Mi Perfil</span>
        </button>
    </div>

    <!-- Pane: Servicios -->
    <div class="mc-pane on" id="pane-svcs">
        <div class="mc-svcs" id="svcList"><div class="mc-loading">Cargando…</div></div>
    </div>

    <!-- Pane: Historial -->
    <div class="mc-pane" id="pane-hist">
        <div class="mc-hist-wrap">
            <div id="histList"><div class="mc-loading">Cargando…</div></div>
            <div class="mc-hist-more" id="histMore" style="display:none">
                <button onclick="mcHistMore()">Cargar más registros ↓</button>
            </div>
        </div>
    </div>

    <!-- Pane: Documentos -->
    <div class="mc-pane" id="pane-docs">
        <div id="docsList"><div class="mc-loading">Cargando…</div></div>
    </div>

    <!-- Pane: Perfil -->
    <div class="mc-pane" id="pane-perfil">
        <div id="perfilContent"><div class="mc-loading">Cargando…</div></div>
    </div>

</div><!-- /mc-wrap -->

<!-- Revocado overlay -->
<div id="mcRevocado">
    <svg width="44" height="44" fill="none" stroke="#991B1B" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div style="font-size:18px;font-weight:800">Acceso desactivado</div>
    <div style="font-size:13.5px;color:#57544D;max-width:340px">Tu acceso al portal fue desactivado. Comunícate con QUANTUN Digital para más información.</div>
    <a href="portal_cliente.php" style="margin-top:4px;padding:9px 20px;background:#0E0E0C;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;font-size:13.5px">Volver al inicio</a>
</div>

<div id="mcToast"></div>

<script>
// ── Helpers ──────────────────────────────────────────────
const MESES = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
function fmtNum(n) { return '$ ' + Number(n).toLocaleString('es-CO',{maximumFractionDigits:0}); }
function fmtDate(d) {
    if (!d) return '—';
    const s = d.split('T')[0].split('-');
    return `${parseInt(s[2])} ${MESES[parseInt(s[1])]} ${s[0]}`;
}
function fmtFrec(f) {
    const m = {mensual:'Mensual',anual:'Anual',trimestral:'Trimestral',semestral:'Semestral',mes:'Mensual',año:'Anual',trimestre:'Trimestral',semestre:'Semestral',unico:'Único'};
    return m[(f||'').toLowerCase()] || f || '—';
}
function esc(s) { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

function toast(msg, type='') {
    const t = document.getElementById('mcToast');
    t.textContent = msg; t.className = 'show ' + type;
    clearTimeout(t._t); t._t = setTimeout(()=>t.className='',3000);
}

function checkRevocado(d) {
    if (d && d.revocado) { document.getElementById('mcRevocado').classList.add('show'); return true; }
    return false;
}

// ── Logout ────────────────────────────────────────────────
async function mcLogout() {
    await fetch('api/portal_auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
    location.href = 'portal_cliente.php';
}

// ── Stats + Alert ─────────────────────────────────────────
async function mcLoadStats() {
    try {
        const d = await fetch('api/portal_data.php?action=stats').then(r=>r.json());
        if (checkRevocado(d)) return;
        if (!d.success) return;

        document.getElementById('stSvcs').textContent = d.servicios_activos;
        document.getElementById('stPag').textContent  = d.total_pagado > 0 ? fmtNum(d.total_pagado).replace('$ ','') : '0';
        document.getElementById('stPend').textContent = d.total_pendiente > 0 ? fmtNum(d.total_pendiente).replace('$ ','') : '0';

        if (d.proxima_renovacion) {
            const dias = d.dias_renovacion;
            document.getElementById('stRen').textContent    = fmtDate(d.proxima_renovacion);
            document.getElementById('stRenSub').textContent = dias < 0 ? 'Vencida' : dias === 0 ? 'Hoy' : `en ${dias} días`;
        }

        // Alert
        if (d.proxima_renovacion) {
            const dias = d.dias_renovacion;
            if (dias <= 30) {
                const al  = document.getElementById('mcAlert');
                const ico = document.getElementById('mcAlertIco');
                const txt = document.getElementById('mcAlertTxt');
                if (dias < 0)     { al.className='mc-alert danger'; ico.textContent='⚠️'; txt.innerHTML=`<strong>Tu suscripción venció hace ${Math.abs(dias)} días.</strong> Comunícate con QUANTUN Digital para renovar.`; }
                else if (dias<=7) { al.className='mc-alert danger'; ico.textContent='🔔'; txt.innerHTML=`<strong>Vence en ${dias} día${dias!==1?'s':''}.</strong> Realiza tu pago para continuar usando el servicio.`; }
                else              { al.className='mc-alert warn';   ico.textContent='📅'; txt.innerHTML=`Próxima renovación en <strong>${dias} días</strong> — ${fmtDate(d.proxima_renovacion)}.`; }
                al.style.display = 'flex';
            }
        }
    } catch(e) {}
}

// ── Tab switching ─────────────────────────────────────────
const _loaded = { svcs: false, hist: false, docs: false, perfil: false };
function mcTab(name, btn) {
    document.querySelectorAll('.mc-tab').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.mc-pane').forEach(p=>p.classList.remove('on'));
    btn.classList.add('active');
    document.getElementById('pane-'+name).classList.add('on');
    if (!_loaded[name]) { _loaded[name]=true; mcLoad(name); }
}

function mcLoad(name) {
    if (name==='svcs')   mcLoadSvcs();
    if (name==='hist')   mcLoadHist();
    if (name==='docs')   mcLoadDocs();
    if (name==='perfil') mcLoadPerfil();
}

// ── Servicios ─────────────────────────────────────────────
async function mcLoadSvcs() {
    const el = document.getElementById('svcList');
    try {
        const d = await fetch('api/portal_data.php?action=servicios').then(r=>r.json());
        if (checkRevocado(d)) return;
        if (!d.success || !d.data.length) {
            el.innerHTML = `<div class="mc-empty"><svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>No tienes servicios registrados.</div>`; return;
        }
        el.innerHTML = d.data.map(sv => {
            const val  = Math.max(0, parseFloat(sv.valor_suscripcion)||0);
            const dias = parseInt(sv.dias_para_vencimiento);
            const frec = fmtFrec(sv.frecuencia);
            const esUnico = (sv.frecuencia||'').toLowerCase() === 'unico';

            let chipCls='grey', chipTxt='—';
            if (sv.estado==='activo' && sv.fecha_vencimiento && !esUnico) {
                if      (dias<0)  { chipCls='danger'; chipTxt=`Venció hace ${Math.abs(dias)} días`; }
                else if (dias<=7) { chipCls='danger'; chipTxt=`Vence en ${dias} día${dias!==1?'s':''}`; }
                else if (dias<=30){ chipCls='warn';   chipTxt=`Vence el ${fmtDate(sv.fecha_vencimiento)}`; }
                else              { chipCls='ok';     chipTxt=`Vigente hasta ${fmtDate(sv.fecha_vencimiento)}`; }
            } else if (esUnico) {
                chipCls='grey'; chipTxt='Pago único';
            }

            return `<div class="mc-svc">
                <div class="mc-svc-icon">
                    <svg width="18" height="18" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <div class="mc-svc-body">
                    <div class="mc-svc-top">
                        <span class="mc-svc-name">${esc(sv.nombre_servicio)}</span>
                        <span class="mc-badge ${esc(sv.estado)}">${sv.estado.charAt(0).toUpperCase()+sv.estado.slice(1)}</span>
                    </div>
                    <div class="mc-svc-meta">
                        <span class="mc-svc-meta-item">
                            <svg width="11" height="11" fill="none" stroke="#8A867C" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            ${esc(frec)}
                        </span>
                        ${sv.fecha_inicio ? `<span class="mc-svc-meta-item">
                            <svg width="11" height="11" fill="none" stroke="#8A867C" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Desde ${fmtDate(sv.fecha_inicio)}
                        </span>` : ''}
                        <span class="mc-chip ${chipCls}" style="font-size:11px">
                            <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            ${esc(chipTxt)}
                        </span>
                    </div>
                </div>
                ${val > 0 ? `<div class="mc-svc-right">
                    <div class="mc-svc-price">${fmtNum(val)}</div>
                    <div class="mc-svc-pcop">COP</div>
                </div>` : ''}
            </div>`;
        }).join('');
    } catch(e) { el.innerHTML='<div class="mc-empty">Error al cargar servicios.</div>'; }
}

// ── Historial ─────────────────────────────────────────────
let _hOff=0, _hTotal=0;

async function mcLoadHist(append=false) {
    const el = document.getElementById('histList');
    if (!append) el.innerHTML = '<div class="mc-loading">Cargando…</div>';
    try {
        const d = await fetch(`api/portal_data.php?action=historial&limite=20&offset=${_hOff}`).then(r=>r.json());
        if (checkRevocado(d)) return;
        if (!d.success) throw new Error();
        _hTotal = d.total;

        if (!d.data.length && !append) {
            el.innerHTML = `<div class="mc-empty"><svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Sin registros de pago.</div>`; return;
        }

        const estIco = { pagado:{bg:'#E3F1E8',c:'#1A6B41'}, pendiente:{bg:'#FEF3C7',c:'#B45309'}, vencido:{bg:'#FEE2E2',c:'#991B1B'} };
        const rows = d.data.map(tx => {
            const ic = estIco[tx.estado] || {bg:'#F5F4EF',c:'#57544D'};
            const fch = tx.fecha_pago || tx.fecha_vencimiento || tx.created_at;
            const sub = [tx.servicio_nombre ? esc(tx.servicio_nombre) : '', fmtDate(fch)].filter(Boolean).join(' · ');
            return `<div class="mc-hist-row">
                <div class="mc-hist-ico" style="background:${ic.bg}">
                    <svg width="14" height="14" fill="none" stroke="${ic.c}" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="mc-hist-body">
                    <div class="mc-hist-ttl">${esc(tx.titulo||tx.concepto)}</div>
                    <div class="mc-hist-sub">${sub}</div>
                </div>
                <div class="mc-hist-right">
                    <div class="mc-hist-amt">${fmtNum(tx.monto)}</div>
                    <div class="mc-hist-est ${esc(tx.estado)}">${tx.estado.charAt(0).toUpperCase()+tx.estado.slice(1)}</div>
                </div>
            </div>`;
        }).join('');

        if (append) document.getElementById('histList').insertAdjacentHTML('beforeend', rows);
        else el.innerHTML = rows;

        _hOff += d.data.length;
        document.getElementById('histMore').style.display = _hOff < _hTotal ? 'block' : 'none';
    } catch(e) { if (!append) el.innerHTML='<div class="mc-empty">Error al cargar historial.</div>'; }
}

function mcHistMore() { mcLoadHist(true); }

// ── Documentos ────────────────────────────────────────────
async function mcLoadDocs() {
    const el = document.getElementById('docsList');
    try {
        const d = await fetch('api/portal_data.php?action=documentos').then(r=>r.json());
        if (checkRevocado(d)) return;
        const items = [];
        (d.archivos||[]).forEach(a => {
            const ext=((a.archivo_url||'').split('.').pop()||'').toLowerCase();
            items.push({url:a.archivo_url, name:a.nombre_archivo||'Archivo', date:a.created_at, ext, isImg:['jpg','jpeg','png','webp'].includes(ext)});
        });
        (d.tx_docs||[]).forEach(tx => {
            ['factura_path','documento_path','imagen_path'].forEach(k=>{
                if (tx[k]) {
                    const ext=(tx[k].split('.').pop()||'').toLowerCase();
                    items.push({url:tx[k], name:tx.nombre_archivo||k.replace('_path',''), date:tx.created_at, ext, isImg:['jpg','jpeg','png','webp'].includes(ext)});
                }
            });
        });
        if (!items.length) { el.innerHTML='<div class="mc-empty"><svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>Sin documentos disponibles.</div>'; return; }

        const colors = {pdf:'#EF4444',doc:'#3B82F6',docx:'#3B82F6',xls:'#10B981',xlsx:'#10B981',jpg:'#F59E0B',jpeg:'#F59E0B',png:'#8B5CF6',webp:'#8B5CF6'};
        const getC = e => colors[e]||'#6B7280';

        el.innerHTML = `<div class="mc-docs-grid">${items.map(it=>{
            const c = getC(it.ext), bg=c+'1a';
            const ico = it.isImg
                ? `<img src="${esc(it.url)}" style="width:32px;height:32px;object-fit:cover;border-radius:5px" onerror="this.style.display='none'">`
                : `<svg width="16" height="16" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`;
            return `<a class="mc-doc" href="${esc(it.url)}" target="_blank" rel="noopener">
                <div class="mc-doc-icon" style="background:${bg}">${ico}</div>
                <div class="mc-doc-name">${esc(it.name)}</div>
                <div class="mc-doc-date">${fmtDate(it.date)}</div>
            </a>`;
        }).join('')}</div>`;
    } catch(e) { el.innerHTML='<div class="mc-empty">Error al cargar documentos.</div>'; }
}

// ── Perfil ────────────────────────────────────────────────
async function mcLoadPerfil() {
    const el = document.getElementById('perfilContent');
    try {
        const d = await fetch('api/portal_data.php?action=perfil').then(r=>r.json());
        if (checkRevocado(d)) return;
        if (!d.success || !d.data) throw new Error();
        const p = d.data;

        const fi = (lbl, val) => {
            const empty = !val;
            return `<div class="mc-perfil-item">
                <div class="mc-perfil-lbl">${lbl}</div>
                <div class="mc-perfil-val ${empty?'empty':''}">${empty ? 'No registrado' : esc(val)}</div>
            </div>`;
        };

        const pwdLabel = p.has_custom_password
            ? '<span style="color:#1A6B41;font-weight:700">Contraseña personalizada ✓</span>'
            : '<span style="color:#8A867C;font-style:italic">NIT/identificación (por defecto)</span>';

        el.innerHTML = `
        <div class="mc-perfil" style="margin-bottom:14px">
            <div class="mc-perfil-head">
                <div class="mc-perfil-av"><?= htmlspecialchars($iniciales) ?></div>
                <div>
                    <div class="mc-perfil-nm">${esc(p.nombre_comercial)}</div>
                    <div class="mc-perfil-since">Cliente desde ${fmtDate(p.created_at)}</div>
                </div>
            </div>
            <div class="mc-perfil-grid">
                ${fi('Empresa / Nombre',    p.nombre_comercial)}
                ${fi('NIT / Identificación',p.nit_cedula)}
                ${fi('Persona de contacto', p.persona_contacto)}
                ${fi('Correo de contacto',  p.email_contacto)}
                ${fi('Correo de facturación',p.email_facturacion)}
                ${fi('Teléfono',            p.telefono)}
                ${fi('Dirección',           p.direccion)}
                <div class="mc-perfil-item"><div class="mc-perfil-lbl">Contraseña portal</div><div class="mc-perfil-val">${pwdLabel}</div></div>
            </div>
        </div>

        <div class="mc-pwd">
            <div class="mc-pwd-title">Cambiar contraseña de acceso</div>
            <div class="mc-pwd-grid">
                <input class="mc-input" type="password" id="pwdA" placeholder="Contraseña actual" autocomplete="current-password">
                <input class="mc-input" type="password" id="pwdN" placeholder="Nueva contraseña" autocomplete="new-password">
                <input class="mc-input" type="password" id="pwdC" placeholder="Confirmar nueva" autocomplete="new-password">
                <button class="mc-btn" onclick="mcCambiarPwd()">Guardar</button>
            </div>
            <div style="font-size:11.5px;color:#8A867C;margin-top:8px">La contraseña debe tener al menos 6 caracteres.</div>
        </div>`;
    } catch(e) { el.innerHTML='<div class="mc-empty">Error al cargar perfil.</div>'; }
}

async function mcCambiarPwd() {
    const btn = document.querySelector('.mc-pwd .mc-btn');
    const a=document.getElementById('pwdA').value.trim();
    const n=document.getElementById('pwdN').value.trim();
    const c=document.getElementById('pwdC').value.trim();
    btn.disabled=true; btn.textContent='Guardando…';
    try {
        const d = await fetch('api/portal_auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'change_password',actual:a,nueva:n,confirm:c})}).then(r=>r.json());
        if (d.success) {
            toast(d.message||'Contraseña actualizada','ok');
            document.getElementById('pwdA').value=document.getElementById('pwdN').value=document.getElementById('pwdC').value='';
            _loaded.perfil=false;
        } else { toast(d.error||'Error','err'); }
    } catch(e) { toast('Error de conexión','err'); }
    btn.disabled=false; btn.textContent='Guardar';
}

// ── Init ──────────────────────────────────────────────────
mcLoadStats();
_loaded.svcs = true;
mcLoadSvcs();
</script>
</body>
</html>
