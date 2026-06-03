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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{-webkit-font-smoothing:antialiased}
body{font-family:'Inter',sans-serif;background:#F0EDE6;color:#0E0E0C;min-height:100vh}

/* ── Nav ──────────────────────────────────────────────────── */
.nav{background:#0E0E0C;height:50px;display:flex;align-items:center;padding:0 20px;gap:10px;position:sticky;top:0;z-index:100}
.nav-logo img{height:22px;display:block}
.nav-sep{width:1px;height:15px;background:rgba(255,255,255,.1)}
.nav-tag{font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#C6F24E;background:rgba(198,242,78,.1);padding:2px 7px;border-radius:100px}
.nav-sp{flex:1}
.nav-av{width:27px;height:27px;border-radius:50%;background:#C6F24E;color:#0E0E0C;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.nav-nm{font-size:12px;font-weight:600;color:rgba(255,255,255,.8);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nav-out{display:flex;align-items:center;gap:4px;padding:5px 10px;background:transparent;border:1px solid rgba(255,255,255,.1);border-radius:5px;font-size:11.5px;font-weight:500;color:rgba(255,255,255,.45);cursor:pointer;font-family:inherit;transition:all .15s}
.nav-out:hover{border-color:rgba(255,255,255,.2);color:rgba(255,255,255,.8)}

/* ── HERO ─────────────────────────────────────────────────── */
.hero{background:#0E0E0C;padding:28px 20px 32px}
.hero-inner{max-width:860px;margin:0 auto;display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap}
.hero-left{}
.hero-greeting{font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:8px}
.hero-name{font-size:28px;font-weight:900;color:#fff;line-height:1.1;letter-spacing:-.02em;margin-bottom:6px}
.hero-nit{font-size:12px;color:rgba(255,255,255,.35);margin-bottom:14px}
.hero-pills{display:flex;gap:6px;flex-wrap:wrap}
.hero-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:100px;font-size:11.5px;font-weight:600}
.hero-pill.ok    {background:rgba(45,143,90,.2);color:#6EE7A8}
.hero-pill.warn  {background:rgba(180,122,30,.2);color:#FCD34D}
.hero-pill.danger{background:rgba(176,56,47,.2);color:#FCA5A5}
.hero-pill.grey  {background:rgba(255,255,255,.07);color:rgba(255,255,255,.45)}
.hero-pill svg   {opacity:.8}

.hero-right{text-align:right;flex-shrink:0}
.hero-value-label{font-size:10.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:4px}
.hero-value{font-size:44px;font-weight:900;color:#fff;line-height:1;letter-spacing:-.03em}
.hero-value-cop{font-size:12px;font-weight:700;color:rgba(255,255,255,.35);margin-top:3px;letter-spacing:.04em}
.hero-renov{margin-top:10px;font-size:11.5px;color:rgba(255,255,255,.4)}
.hero-renov strong{color:rgba(255,255,255,.65)}

/* ── Wrap ─────────────────────────────────────────────────── */
.wrap{max-width:860px;margin:0 auto;padding:20px 16px 64px}

/* ── Section header ───────────────────────────────────────── */
.sh{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.sh-title{font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#8A867C}
.sh-badge{font-size:11px;font-weight:800;padding:2px 9px;border-radius:100px;background:#E8E5DD;color:#57544D;min-width:22px;text-align:center}

/* ── Services grid ────────────────────────────────────────── */
.svcs{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;margin-bottom:28px}
.svc{background:#fff;border:1px solid #E4E1D9;border-radius:14px;padding:20px;transition:box-shadow .18s,transform .18s;cursor:default}
.svc:hover{box-shadow:0 6px 24px rgba(0,0,0,.07);transform:translateY(-1px)}
.svc-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}
.svc-head-left{flex:1;min-width:0}
.svc-name{font-size:15px;font-weight:800;color:#0E0E0C;line-height:1.25;margin-bottom:6px}
.svc-price{text-align:right;flex-shrink:0;padding-top:1px}
.svc-price-num{font-size:20px;font-weight:900;color:#0E0E0C;line-height:1;letter-spacing:-.02em}
.svc-price-cop{font-size:10px;font-weight:700;color:#8A867C;letter-spacing:.05em;margin-top:2px}
.svc-divider{height:1px;background:#F0EDE6;margin:0 0 10px}
.svc-meta{display:flex;gap:6px;flex-wrap:wrap}
.svc-chip{display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:100px;font-size:11px;font-weight:600;white-space:nowrap}
.svc-chip.ok     {background:#E3F1E8;color:#1A6B41}
.svc-chip.warn   {background:#FEF3C7;color:#92400E}
.svc-chip.danger {background:#FEE2E2;color:#991B1B}
.svc-chip.grey   {background:#F0EDE6;color:#8A867C}
.svc-chip.blue   {background:#EEF2FB;color:#3F5E9E}
.badge-st{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:100px;font-size:11px;font-weight:700}
.badge-st::before{content:'';width:6px;height:6px;border-radius:50%}
.badge-st.activo    {background:#E3F1E8;color:#1A6B41}.badge-st.activo::before{background:#1A6B41}
.badge-st.suspendido{background:#FEF3C7;color:#92400E}.badge-st.suspendido::before{background:#B45309}
.badge-st.cancelado {background:#FEE2E2;color:#991B1B}.badge-st.cancelado::before{background:#991B1B}

/* ── Activity tabs ────────────────────────────────────────── */
.tabs-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;margin-bottom:14px}
.tabs-wrap::-webkit-scrollbar{display:none}
.tabs{display:inline-flex;min-width:100%;gap:2px;background:#fff;border:1px solid #E4E1D9;border-radius:10px;padding:4px}
.tab{flex:1;min-width:0;padding:8px 6px;border:none;border-radius:7px;background:transparent;font-size:12.5px;font-weight:500;color:#8A867C;cursor:pointer;font-family:inherit;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:5px;white-space:nowrap}
.tab:hover{color:#0E0E0C;background:#F5F3EE}
.tab.on{background:#0E0E0C;color:#fff;font-weight:700;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.tab.on svg{opacity:1}
.tab svg{opacity:.55;flex-shrink:0}
.tab-cnt{font-size:10px;padding:1px 6px;border-radius:100px;background:rgba(255,255,255,.18);color:inherit;font-weight:800;line-height:1.5;display:none}
.tab.on .tab-cnt{display:inline}
.pane{display:none}.pane.on{display:block}

/* ── Activity content ─────────────────────────────────────── */
.act-wrap{background:#fff;border:1px solid #E4E1D9;border-radius:12px;overflow:hidden}
.act-row{display:flex;align-items:flex-start;gap:14px;padding:14px 18px;border-bottom:1px solid #F0EDE6;transition:background .1s}
.act-row:last-child{border-bottom:none}
.act-row:hover{background:#FAFAF8}
.act-ico{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.act-body{flex:1;min-width:0}
.act-title{font-size:13.5px;font-weight:600;color:#0E0E0C;line-height:1.35}
.act-sub{font-size:11.5px;color:#8A867C;margin-top:2px;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.act-right{text-align:right;flex-shrink:0;min-width:80px}
.act-val{font-size:14px;font-weight:800;color:#0E0E0C;letter-spacing:-.01em}
.act-est{font-size:11px;font-weight:700;margin-top:2px;display:inline-block;padding:2px 7px;border-radius:100px}
.act-est.pagado    {background:#E3F1E8;color:#1A6B41}
.act-est.pendiente {background:#FEF3C7;color:#B45309}
.act-est.vencido   {background:#FEE2E2;color:#991B1B}
.act-est.completado{background:#E3F1E8;color:#1A6B41}
.act-est.en_progreso{background:#E1E7F2;color:#1E429F}
.act-est.pendiente2{background:#FEF3C7;color:#B45309}
.act-est.cancelado {background:#FEE2E2;color:#991B1B}
.act-more{text-align:center;padding:12px;border-top:1px solid #F0EDE6}
.act-more button{background:none;border:1px solid #E4E1D9;border-radius:6px;padding:6px 16px;font-size:12.5px;font-weight:600;color:#57544D;cursor:pointer;font-family:inherit;transition:all .15s}
.act-more button:hover{border-color:#0E0E0C;color:#0E0E0C;background:#F5F3EE}
.prioridad-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;display:inline-block;margin-right:5px;vertical-align:middle}
.pr-alta{background:#EF4444}.pr-media{background:#F59E0B}.pr-baja{background:#10B981}

/* ── Docs ─────────────────────────────────────────────────── */
.docs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));gap:10px;padding:14px 14px}
.doc{background:#FAFAF7;border:1px solid #EFECE5;border-radius:8px;padding:11px 11px 9px;display:flex;flex-direction:column;gap:6px;text-decoration:none;color:inherit;transition:all .15s}
.doc:hover{border-color:#B5B0A6;background:#fff}
.doc-ico{width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center}
.doc-nm{font-size:11.5px;font-weight:600;color:#0E0E0C;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.doc-dt{font-size:10.5px;color:#8A867C}

/* ── Perfil ───────────────────────────────────────────────── */
.perfil-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;background:#fff;border:1px solid #E4E1D9;border-radius:10px;overflow:hidden;margin-bottom:12px}
.pf-item{padding:12px 16px;border-right:1px solid #F0EDE6;border-bottom:1px solid #F0EDE6}
.pf-item:nth-child(even){border-right:none}
.pf-lbl{font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#8A867C;margin-bottom:3px}
.pf-val{font-size:13px;font-weight:600;color:#0E0E0C}
.pf-val.e{color:#C4C0BA;font-weight:400;font-style:italic}
.pwd-section{background:#fff;border:1px solid #E4E1D9;border-radius:10px;padding:16px 16px 14px}
.pwd-title{font-size:12.5px;font-weight:700;margin-bottom:11px;color:#0E0E0C}
.pwd-row{display:flex;gap:7px;flex-wrap:wrap}
.inp{padding:8px 11px;border:1.5px solid #E4E1D9;border-radius:6px;font-size:12.5px;font-family:inherit;background:#FAFAF7;color:#0E0E0C;outline:none;transition:border-color .15s;flex:1;min-width:130px}
.inp:focus{border-color:#0E0E0C;background:#fff}
.btn-dark{padding:8px 16px;background:#0E0E0C;color:#fff;border:none;border-radius:6px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;white-space:nowrap;flex-shrink:0;transition:opacity .15s}
.btn-dark:hover{opacity:.8}
.btn-dark:disabled{opacity:.4;cursor:not-allowed}

/* ── Empty / Loading ──────────────────────────────────────── */
.empty{text-align:center;padding:36px 20px;color:#8A867C;font-size:13px}
.empty svg{display:block;margin:0 auto 10px;opacity:.2}
.loading{padding:28px;text-align:center;color:#8A867C;font-size:12.5px}

/* ── Alert bar ────────────────────────────────────────────── */
.alert-bar{display:none;align-items:center;gap:9px;padding:9px 14px;border-radius:8px;margin-bottom:14px;font-size:12.5px;font-weight:500;line-height:1.4}
.alert-bar.ok    {background:#E3F1E8;color:#1A6B41}
.alert-bar.warn  {background:#FEF3C7;color:#92400E}
.alert-bar.danger{background:#FEE2E2;color:#991B1B}

/* ── Toast ────────────────────────────────────────────────── */
#toast{position:fixed;bottom:18px;right:18px;z-index:9999;padding:10px 15px;border-radius:7px;font-size:12.5px;font-weight:600;color:#fff;background:#0E0E0C;box-shadow:0 4px 16px rgba(0,0,0,.15);opacity:0;transform:translateY(5px);transition:all .2s;pointer-events:none}
#toast.show{opacity:1;transform:none}
#toast.ok{background:#1A6B41}
#toast.err{background:#991B1B}

/* ── Revocado ─────────────────────────────────────────────── */
#revocado{display:none;position:fixed;inset:0;z-index:9000;background:#fff;flex-direction:column;align-items:center;justify-content:center;gap:14px;text-align:center;padding:24px}
#revocado.show{display:flex}

/* ── Responsive ───────────────────────────────────────────── */
@media(max-width:860px){
    .svcs{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
}
@media(max-width:640px){
    .hero-inner{flex-direction:column;gap:16px}
    .hero-right{text-align:left;width:100%;padding-top:12px;border-top:1px solid rgba(255,255,255,.08)}
    .hero-name{font-size:22px}
    .hero-value{font-size:34px}
    .wrap{padding:16px 12px 56px}
    .svcs{grid-template-columns:1fr;gap:10px}
    .act-row{gap:10px;padding:12px 14px}
    .act-right{min-width:70px}
    .act-val{font-size:13px}
}
@media(max-width:480px){
    .nav-nm{display:none}
    .hero{padding:20px 14px 24px}
    .hero-name{font-size:19px}
    .hero-value{font-size:30px}
    .hero-pills{gap:5px}
    .tab span.tl{display:none}
    .tab{padding:8px 10px}
    .act-row{flex-wrap:wrap}
    .act-right{width:100%;text-align:left;padding-left:50px;margin-top:2px}
    .act-est{font-size:10.5px}
    .perfil-grid{grid-template-columns:1fr}
    .pf-item{border-right:none}
    .pwd-row{flex-direction:column}
    .svc-name{font-size:14px}
}
</style>
</head>
<body>

<!-- Nav -->
<nav class="nav">
    <div class="nav-logo"><img src="Assets/logo_quantun_digital_blanco.png" alt="QUANTUN"></div>
    <div class="nav-sep"></div>
    <span class="nav-tag">Portal</span>
    <div class="nav-sp"></div>
    <div style="display:flex;align-items:center;gap:8px">
        <div class="nav-av"><?= $iniciales ?></div>
        <span class="nav-nm"><?= $nombre ?></span>
    </div>
    <button class="nav-out" onclick="doLogout()">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Salir
    </button>
</nav>

<!-- HERO -->
<div class="hero" id="heroSection">
    <div class="hero-inner">
        <div class="hero-left">
            <div class="hero-greeting">Bienvenido a tu portal</div>
            <div class="hero-name" id="heroNombre"><?= $nombre ?></div>
            <div class="hero-nit" id="heroNit"></div>
            <div class="hero-pills" id="heroPills">
                <span class="hero-pill grey"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Cargando…</span>
            </div>
        </div>
        <div class="hero-right">
            <div class="hero-value-label">Valor de suscripción</div>
            <div class="hero-value" id="heroValor">—</div>
            <div class="hero-value-cop">COP</div>
            <div class="hero-renov" id="heroRenov"></div>
        </div>
    </div>
</div>

<!-- Content -->
<div class="wrap">

    <!-- Alert -->
    <div class="alert-bar" id="alertBar">
        <span id="alertIco" style="font-size:15px;flex-shrink:0"></span>
        <span id="alertTxt"></span>
    </div>

    <!-- SECCIÓN 2: Servicios -->
    <div class="sh" id="shSvcs" style="display:none">
        <span class="sh-title">Mis servicios</span>
        <span class="sh-badge" id="svcsBadge"></span>
    </div>
    <div class="svcs" id="svcsGrid"></div>

    <!-- SECCIÓN 3: Actividad -->
    <div class="sh" style="margin-top:4px">
        <span class="sh-title">Actividad y detalle</span>
    </div>

    <div class="tabs-wrap">
    <div class="tabs">
        <button class="tab on" id="tab-pagos" onclick="setTab('pagos',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span class="tl">Pagos</span>
            <span class="tab-cnt" id="cntPagos">0</span>
        </button>
        <button class="tab" id="tab-tareas" onclick="setTab('tareas',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="tl">Tareas</span>
            <span class="tab-cnt" id="cntTareas">0</span>
        </button>
        <button class="tab" id="tab-notif" onclick="setTab('notif',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span class="tl">Notificaciones</span>
            <span class="tab-cnt" id="cntNotif">0</span>
        </button>
        <button class="tab" id="tab-docs" onclick="setTab('docs',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            <span class="tl">Documentos</span>
            <span class="tab-cnt" id="cntDocs">0</span>
        </button>
        <button class="tab" id="tab-perfil" onclick="setTab('perfil',this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="tl">Mi Perfil</span>
        </button>
    </div>
    </div>

    <!-- Pane pagos -->
    <div class="pane on" id="pane-pagos">
        <div class="act-wrap"><div id="listPagos"><div class="loading">Cargando…</div></div><div class="act-more" id="morePagos" style="display:none"><button onclick="loadMorePagos()">Cargar más ↓</button></div></div>
    </div>
    <!-- Pane tareas -->
    <div class="pane" id="pane-tareas">
        <div class="act-wrap"><div id="listTareas"><div class="loading">Cargando…</div></div></div>
    </div>
    <!-- Pane notif -->
    <div class="pane" id="pane-notif">
        <div class="act-wrap"><div id="listNotif"><div class="loading">Cargando…</div></div></div>
    </div>
    <!-- Pane docs -->
    <div class="pane" id="pane-docs">
        <div class="act-wrap"><div id="listDocs"><div class="loading">Cargando…</div></div></div>
    </div>
    <!-- Pane perfil -->
    <div class="pane" id="pane-perfil">
        <div id="perfilContent"><div class="loading">Cargando…</div></div>
    </div>

</div><!-- /wrap -->

<div id="revocado">
    <svg width="42" height="42" fill="none" stroke="#991B1B" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div style="font-size:17px;font-weight:800">Acceso desactivado</div>
    <div style="font-size:13px;color:#57544D;max-width:320px">Tu acceso fue desactivado. Comunícate con QUANTUN Digital.</div>
    <a href="portal_cliente.php" style="margin-top:4px;padding:9px 18px;background:#0E0E0C;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;font-size:13px">Volver al inicio</a>
</div>
<div id="toast"></div>

<script>
// ── Utils ─────────────────────────────────────────────────
const M=['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
function fd(d){if(!d)return'—';const s=d.split('T')[0].split('-');return`${+s[2]} ${M[+s[1]]} ${s[0]}`;}
function fn(n){return'$ '+Number(n).toLocaleString('es-CO',{maximumFractionDigits:0});}
function ff(f){const m={mensual:'Mensual',anual:'Anual',trimestral:'Trimestral',semestral:'Semestral',mes:'Mensual','año':'Anual',trimestre:'Trimestral',semestre:'Semestral',unico:'Único'};return m[(f||'').toLowerCase()]||f||'—';}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function toast(msg,t=''){const el=document.getElementById('toast');el.textContent=msg;el.className='show '+t;clearTimeout(el._t);el._t=setTimeout(()=>el.className='',3200);}
function revocado(d){if(d&&d.revocado){document.getElementById('revocado').classList.add('show');return true;}return false;}

// ── Logout ─────────────────────────────────────────────────
async function doLogout(){
    await fetch('api/portal_auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
    location.href='portal_cliente.php';
}

// ── Tab switcher ───────────────────────────────────────────
const _ld={pagos:false,tareas:false,notif:false,docs:false,perfil:false};
function setTab(name,btn){
    document.querySelectorAll('.tab').forEach(b=>b.classList.remove('on'));
    document.querySelectorAll('.pane').forEach(p=>p.classList.remove('on'));
    btn.classList.add('on');
    document.getElementById('pane-'+name).classList.add('on');
    if(!_ld[name]){_ld[name]=true;({pagos:loadPagos,tareas:loadTareas,notif:loadNotif,docs:loadDocs,perfil:loadPerfil})[name]();}
}

// ── HERO + Stats ──────────────────────────────────────────
async function loadHero(){
    try{
        const[sd,pd]=await Promise.all([
            fetch('api/portal_data.php?action=stats').then(r=>r.json()),
            fetch('api/portal_data.php?action=perfil').then(r=>r.json()),
        ]);
        if(revocado(sd)||revocado(pd))return;

        // Perfil en hero
        if(pd.success&&pd.data){
            const p=pd.data;
            document.getElementById('heroNombre').textContent=p.nombre_comercial||'<?= $nombre ?>';
            if(p.nit_cedula) document.getElementById('heroNit').textContent='NIT: '+p.nit_cedula;
        }

        if(!sd.success)return;

        // Valor de suscripción
        const val=sd.valor_suscripcion||0;
        document.getElementById('heroValor').textContent=val>0?fn(val).replace('$ ',''):'0';

        // Próxima renovación
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            let txt='';
            if(dias<0) txt=`Vencida hace ${Math.abs(dias)} días`;
            else if(dias===0) txt='Vence hoy';
            else txt=`Próxima renovación: <strong>${fd(sd.proxima_renovacion)}</strong> (en ${dias} días)`;
            document.getElementById('heroRenov').innerHTML=txt;
        }

        // Pills estado
        const pills=[];
        if(sd.servicios_activos>0){
            pills.push(`<span class="hero-pill ok"><svg width="8" height="8" viewBox="0 0 8 8" fill="none"><circle cx="4" cy="4" r="4" fill="#6EE7A8"/></svg>${sd.servicios_activos} servicio${sd.servicios_activos!==1?'s':''} activo${sd.servicios_activos!==1?'s':''}</span>`);
        }
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            let cls='grey',txt='';
            if(dias<0){cls='danger';txt=`Vencido`;}
            else if(dias<=7){cls='danger';txt=`Vence en ${dias} día${dias!==1?'s':''}`;}
            else if(dias<=30){cls='warn';txt=`Renueva en ${dias} días`;}
            else{cls='ok';txt=`Al día`;}
            pills.push(`<span class="hero-pill ${cls}"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>${txt}</span>`);
        }
        if(sd.total_pendiente>0){
            pills.push(`<span class="hero-pill warn"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>${fn(sd.total_pendiente)} por pagar</span>`);
        }
        document.getElementById('heroPills').innerHTML=pills.join('')||'<span class="hero-pill grey">Sin servicios activos</span>';

        // Alert bar
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            if(dias<=30){
                const ab=document.getElementById('alertBar');
                document.getElementById('alertIco').textContent=dias<0?'⚠️':dias<=7?'🔔':'📅';
                document.getElementById('alertTxt').innerHTML=dias<0
                    ?`<strong>Suscripción vencida hace ${Math.abs(dias)} días.</strong> Comunícate con QUANTUN Digital para renovar tu servicio.`
                    :dias<=7
                    ?`<strong>Vence en ${dias} día${dias!==1?'s':''}.</strong> Realiza tu pago para evitar la suspensión.`
                    :`Próxima renovación el <strong>${fd(sd.proxima_renovacion)}</strong>, en ${dias} días.`;
                ab.className='alert-bar '+(dias<0||dias<=7?'danger':'warn');
                ab.style.display='flex';
            }
        }
    }catch(e){}
}

// ── Servicios ─────────────────────────────────────────────
async function loadSvcs(){
    const grid=document.getElementById('svcsGrid');
    const sh=document.getElementById('shSvcs');
    try{
        const d=await fetch('api/portal_data.php?action=servicios').then(r=>r.json());
        if(revocado(d))return;
        if(!d.success||!d.data.length){grid.innerHTML='';sh.style.display='none';return;}
        sh.style.display='flex';
        document.getElementById('svcsBadge').textContent=d.data.length;

        grid.innerHTML=d.data.map(sv=>{
            const val=Math.max(0,parseFloat(sv.valor_suscripcion)||0);
            const dias=parseInt(sv.dias_para_vencimiento);
            const esUnico=(sv.frecuencia||'').toLowerCase()==='unico';
            let chipCls='grey',chipTxt='—';
            if(sv.estado==='activo'&&sv.fecha_vencimiento&&!esUnico){
                if(dias<0){chipCls='danger';chipTxt=`Venció hace ${Math.abs(dias)} días`;}
                else if(dias<=7){chipCls='danger';chipTxt=`Vence en ${dias} día${dias!==1?'s':''}`;}
                else if(dias<=30){chipCls='warn';chipTxt=`Vence el ${fd(sv.fecha_vencimiento)}`;}
                else{chipCls='ok';chipTxt=`Vigente hasta ${fd(sv.fecha_vencimiento)}`;}
            }else if(esUnico){chipCls='grey';chipTxt='Pago único';}

            return`<div class="svc">
                <div class="svc-head">
                    <div class="svc-head-left">
                        <div class="svc-name">${esc(sv.nombre_servicio)}</div>
                        <span class="badge-st ${esc(sv.estado)}">${sv.estado.charAt(0).toUpperCase()+sv.estado.slice(1)}</span>
                    </div>
                    ${val>0?`<div class="svc-price"><div class="svc-price-num">${fn(val)}</div><div class="svc-price-cop">COP / ${esc(ff(sv.frecuencia))}</div></div>`:''}
                </div>
                <div class="svc-divider"></div>
                <div class="svc-meta">
                    ${!esUnico?`<span class="svc-chip blue"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>${esc(ff(sv.frecuencia))}</span>`:''}
                    <span class="svc-chip ${chipCls}"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>${esc(chipTxt)}</span>
                    ${sv.fecha_inicio?`<span class="svc-chip grey"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Desde ${fd(sv.fecha_inicio)}</span>`:''}
                </div>
            </div>`;
        }).join('');
    }catch(e){grid.innerHTML='';}
}

// ── Pagos ──────────────────────────────────────────────────
let _pOff=0,_pTotal=0;
async function loadPagos(append=false){
    const el=document.getElementById('listPagos');
    if(!append)el.innerHTML='<div class="loading">Cargando…</div>';
    try{
        const d=await fetch(`api/portal_data.php?action=historial&limite=20&offset=${_pOff}`).then(r=>r.json());
        if(revocado(d))return;
        _pTotal=d.total||0;
        document.getElementById('cntPagos').textContent=_pTotal;
        if(!d.data.length&&!append){el.innerHTML='<div class="empty"><svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Sin registros de pago</div>';return;}
        const ic={pagado:{bg:'#E3F1E8',c:'#1A6B41'},pendiente:{bg:'#FEF3C7',c:'#B45309'},vencido:{bg:'#FEE2E2',c:'#991B1B'}};
        const estLabel={pagado:'Pagado',pendiente:'Pendiente',vencido:'Vencido'};
        const rows=d.data.map(tx=>{
            const col=ic[tx.estado]||{bg:'#F0EDE6',c:'#57544D'};
            const fch=tx.fecha_pago||tx.fecha_vencimiento||tx.created_at;
            const sub=[tx.servicio_nombre?esc(tx.servicio_nombre):'',fd(fch)].filter(Boolean).join(' · ');
            return`<div class="act-row"><div class="act-ico" style="background:${col.bg}"><svg width="15" height="15" fill="none" stroke="${col.c}" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="act-body"><div class="act-title">${esc(tx.titulo||tx.concepto)}</div><div class="act-sub">${sub}</div></div><div class="act-right"><div class="act-val">${fn(tx.monto)}</div><div class="act-est ${esc(tx.estado)}">${estLabel[tx.estado]||tx.estado}</div></div></div>`;
        }).join('');
        if(append)el.insertAdjacentHTML('beforeend',rows);else el.innerHTML=rows;
        _pOff+=d.data.length;
        document.getElementById('morePagos').style.display=_pOff<_pTotal?'block':'none';
    }catch(e){if(!append)el.innerHTML='<div class="empty">Error al cargar</div>';}
}
function loadMorePagos(){loadPagos(true);}

// ── Tareas ─────────────────────────────────────────────────
async function loadTareas(){
    const el=document.getElementById('listTareas');
    try{
        const d=await fetch('api/portal_data.php?action=tareas').then(r=>r.json());
        if(revocado(d))return;
        const cnt=d.data?d.data.length:0;
        document.getElementById('cntTareas').textContent=cnt;
        if(!cnt){el.innerHTML='<div class="empty"><svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Sin tareas registradas</div>';return;}

        const estadoLabel={pendiente:'Pendiente',en_progreso:'En progreso',revision:'En revisión',completado:'Completado',cancelado:'Cancelado'};
        const estadoCls  ={pendiente:'pendiente2',en_progreso:'en_progreso',revision:'en_progreso',completado:'completado',cancelado:'cancelado'};
        const prioColors ={alta:'#EF4444',media:'#F59E0B',baja:'#10B981'};
        const bgEst={pendiente:'#F5F4EF',en_progreso:'#E1E7F2',revision:'#FEF3C7',completado:'#E3F1E8',cancelado:'#FEE2E2'};
        const cEst ={pendiente:'#57544D',en_progreso:'#1E429F',revision:'#92400E',completado:'#1A6B41',cancelado:'#991B1B'};

        el.innerHTML=d.data.map(t=>{
            const sub=[t.responsable?`Asignado: ${esc(t.responsable)}`:'',t.fecha_limite?`Límite: ${fd(t.fecha_limite)}`:''].filter(Boolean).join(' · ');
            return`<div class="act-row"><div class="act-ico" style="background:${bgEst[t.estado]||'#F5F4EF'}"><svg width="14" height="14" fill="none" stroke="${cEst[t.estado]||'#57544D'}" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="act-body"><div class="act-title"><span class="prioridad-dot pr-${esc(t.prioridad)}" style="background:${prioColors[t.prioridad]||'#ccc'}"></span>${esc(t.titulo)}</div>${sub?`<div class="act-sub">${sub}</div>`:''}</div><div class="act-right"><div class="act-est ${estadoCls[t.estado]||''}">${estadoLabel[t.estado]||t.estado}</div>${t.fecha_limite&&!t.updated_at?'':'<div class="act-sub" style="font-size:10.5px">'+fd(t.updated_at)+'</div>'}</div></div>`;
        }).join('');
    }catch(e){el.innerHTML='<div class="empty">Error al cargar</div>';}
}

// ── Notificaciones ─────────────────────────────────────────
async function loadNotif(){
    const el=document.getElementById('listNotif');
    try{
        const d=await fetch('api/portal_data.php?action=notificaciones').then(r=>r.json());
        if(revocado(d))return;
        const cnt=d.data?d.data.length:0;
        document.getElementById('cntNotif').textContent=cnt;
        if(!cnt){el.innerHTML='<div class="empty"><svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>Sin notificaciones enviadas</div>';return;}

        el.innerHTML=d.data.map(n=>{
            const sub=[n.nombre_servicio?esc(n.nombre_servicio):'',n.vencimiento?`Vencimiento: ${fd(n.vencimiento)}`:''].filter(Boolean).join(' · ');
            return`<div class="act-row"><div class="act-ico" style="background:#E1E7F2"><svg width="14" height="14" fill="none" stroke="#3F5E9E" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div><div class="act-body"><div class="act-title">${esc(n.label)} de renovación</div><div class="act-sub">${sub}</div></div><div class="act-right"><div class="act-sub" style="font-size:11px">${fd(n.fecha)}</div></div></div>`;
        }).join('');
    }catch(e){el.innerHTML='<div class="empty">Error al cargar</div>';}
}

// ── Documentos ─────────────────────────────────────────────
async function loadDocs(){
    const el=document.getElementById('listDocs');
    try{
        const d=await fetch('api/portal_data.php?action=documentos').then(r=>r.json());
        if(revocado(d))return;
        const items=[];
        (d.archivos||[]).forEach(a=>{const ext=((a.archivo_url||'').split('.').pop()||'').toLowerCase();items.push({url:a.archivo_url,name:a.nombre_archivo||'Archivo',date:a.created_at,ext,isImg:['jpg','jpeg','png','webp'].includes(ext)});});
        (d.tx_docs||[]).forEach(tx=>{['factura_path','documento_path','imagen_path'].forEach(k=>{if(tx[k]){const ext=(tx[k].split('.').pop()||'').toLowerCase();items.push({url:tx[k],name:tx.nombre_archivo||'Documento',date:tx.created_at,ext,isImg:['jpg','jpeg','png','webp'].includes(ext)});}});});
        document.getElementById('cntDocs').textContent=items.length;
        if(!items.length){el.innerHTML='<div class="empty" style="padding:28px"><svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>Sin documentos</div>';return;}
        const cc={pdf:'#EF4444',doc:'#3B82F6',docx:'#3B82F6',xls:'#10B981',xlsx:'#10B981',jpg:'#F59E0B',jpeg:'#F59E0B',png:'#8B5CF6',webp:'#8B5CF6'};
        const gc=e=>cc[e]||'#6B7280';
        el.innerHTML=`<div class="docs-grid">${items.map(it=>{const c=gc(it.ext),bg=c+'1a';const ico=it.isImg?`<img src="${esc(it.url)}" style="width:30px;height:30px;object-fit:cover;border-radius:5px" onerror="this.style.display='none'">`:`<svg width="15" height="15" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`;return`<a class="doc" href="${esc(it.url)}" target="_blank" rel="noopener"><div class="doc-ico" style="background:${bg}">${ico}</div><div class="doc-nm">${esc(it.name)}</div><div class="doc-dt">${fd(it.date)}</div></a>`;}).join('')}</div>`;
    }catch(e){el.innerHTML='<div class="empty" style="padding:28px">Error al cargar</div>';}
}

// ── Perfil ─────────────────────────────────────────────────
async function loadPerfil(){
    const el=document.getElementById('perfilContent');
    try{
        const d=await fetch('api/portal_data.php?action=perfil').then(r=>r.json());
        if(revocado(d))return;
        if(!d.success||!d.data)throw new Error();
        const p=d.data;
        const fi=(lbl,val)=>`<div class="pf-item"><div class="pf-lbl">${lbl}</div><div class="pf-val ${!val?'e':''}">${val?esc(val):'No registrado'}</div></div>`;
        const pwdTxt=p.has_custom_password?'<span style="color:#1A6B41;font-weight:700">Contraseña propia ✓</span>':'<span style="color:#8A867C;font-style:italic">NIT por defecto</span>';
        el.innerHTML=`<div class="perfil-grid">${fi('Empresa / Razón social',p.nombre_comercial)}${fi('NIT / Identificación',p.nit_cedula)}${fi('Persona de contacto',p.persona_contacto)}${fi('Correo contacto',p.email_contacto)}${fi('Correo facturación',p.email_facturacion)}${fi('Teléfono',p.telefono)}${fi('Dirección',p.direccion)}<div class="pf-item"><div class="pf-lbl">Contraseña portal</div><div class="pf-val">${pwdTxt}</div></div></div>
        <div class="pwd-section"><div class="pwd-title">Cambiar contraseña de acceso</div><div class="pwd-row"><input class="inp" type="password" id="pwdA" placeholder="Contraseña actual" autocomplete="current-password"><input class="inp" type="password" id="pwdN" placeholder="Nueva contraseña (mín. 6)" autocomplete="new-password"><input class="inp" type="password" id="pwdC" placeholder="Confirmar nueva" autocomplete="new-password"><button class="btn-dark" onclick="cambiarPwd()">Guardar</button></div></div>`;
    }catch(e){el.innerHTML='<div class="empty">Error al cargar perfil</div>';}
}
async function cambiarPwd(){
    const btn=document.querySelector('.btn-dark');
    const a=document.getElementById('pwdA').value.trim();
    const n=document.getElementById('pwdN').value.trim();
    const c=document.getElementById('pwdC').value.trim();
    btn.disabled=true;btn.textContent='Guardando…';
    try{
        const d=await fetch('api/portal_auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'change_password',actual:a,nueva:n,confirm:c})}).then(r=>r.json());
        toast(d.success?d.message:d.error,d.success?'ok':'err');
        if(d.success){document.getElementById('pwdA').value=document.getElementById('pwdN').value=document.getElementById('pwdC').value='';}
    }catch(e){toast('Error de conexión','err');}
    btn.disabled=false;btn.textContent='Guardar';
}

// ── Init ───────────────────────────────────────────────────
loadHero();
loadSvcs();
_ld.pagos=true; loadPagos();
</script>
</body>
</html>
