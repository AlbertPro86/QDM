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
body{font-family:'Inter',sans-serif;background:#F0EDE6;color:#252422;min-height:100vh}

/* ── Nav ──────────────────────────────────────────────────── */
.nav{background:#252422;height:50px;display:flex;align-items:center;padding:0 20px;gap:10px;position:sticky;top:0;z-index:100}
.nav-logo img{height:22px;display:block}
.nav-sep{width:1px;height:15px;background:rgba(255,255,255,.1)}
.nav-tag{font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#C6F24E;background:rgba(198,242,78,.1);padding:2px 7px;border-radius:100px}
.nav-sp{flex:1}
.nav-av{width:27px;height:27px;border-radius:50%;background:#C6F24E;color:#252422;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.nav-nm{font-size:12px;font-weight:600;color:rgba(255,255,255,.8);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nav-out{display:flex;align-items:center;gap:4px;padding:5px 10px;background:transparent;border:1px solid rgba(255,255,255,.1);border-radius:5px;font-size:11.5px;font-weight:500;color:rgba(255,255,255,.45);cursor:pointer;font-family:inherit;transition:all .15s}
.nav-out:hover{border-color:rgba(255,255,255,.2);color:rgba(255,255,255,.8)}

/* ── HERO ─────────────────────────────────────────────────── */
.hero{background:#252422;padding:28px 20px 32px}
.hero-inner{max-width:860px;margin:0 auto;display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap}
.hero-left{}
.hero-greeting{font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:8px}
.hero-name{font-size:28px;font-weight:900;color:#fff;line-height:1.1;letter-spacing:-.02em;margin-bottom:4px}
.hero-contact{font-size:13px;font-weight:500;color:rgba(255,255,255,.5);margin-bottom:4px;display:none}
.hero-contact span{color:rgba(255,255,255,.75);font-weight:600}
.hero-nit{font-size:11.5px;color:rgba(255,255,255,.3);margin-bottom:14px}
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
.svc-name{font-size:15px;font-weight:800;color:#252422;line-height:1.25;margin-bottom:6px}
.svc-price{text-align:right;flex-shrink:0;padding-top:1px}
.svc-price-num{font-size:20px;font-weight:900;color:#252422;line-height:1;letter-spacing:-.02em}
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
.tab:hover{color:#252422;background:#F5F3EE}
.tab.on{background:#252422;color:#fff;font-weight:700;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.tab.on svg{opacity:1}
.tab svg{opacity:.55;flex-shrink:0}
.tab-cnt{font-size:10px;padding:1px 6px;border-radius:100px;background:rgba(255,255,255,.18);color:inherit;font-weight:800;line-height:1.5;display:none}
.tab.on .tab-cnt{display:inline}
.pane{display:none}.pane.on{display:block}

/* ── Estado badges (usados en cards) ──────────────────────── */
.est-badge{font-size:11px;font-weight:700;display:inline-block;padding:3px 9px;border-radius:100px;white-space:nowrap;flex-shrink:0}
.est-badge.pagado    {background:#E3F1E8;color:#1A6B41}
.est-badge.pendiente {background:#FEF3C7;color:#B45309}
.est-badge.vencido   {background:#FEE2E2;color:#991B1B}
.est-badge.completado{background:#E3F1E8;color:#1A6B41}
.est-badge.en_progreso,.est-badge.revision{background:#E1E7F2;color:#1E429F}
.est-badge.cancelado {background:#FEE2E2;color:#991B1B}
.est-badge.pendiente2{background:#FEF3C7;color:#B45309}
.prio-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:100px;font-size:11px;font-weight:700}

/* ── Item cards grid ──────────────────────────────────────── */
.cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(255px,1fr));gap:10px}
.icard{background:#fff;border:1px solid #E4E1D9;border-radius:12px;padding:16px 18px;display:flex;flex-direction:column;transition:box-shadow .18s,transform .18s}
.icard:hover{box-shadow:0 4px 18px rgba(0,0,0,.07);transform:translateY(-1px)}
.icard-top{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:9px}
.icard-srv{font-size:11.5px;color:#8A867C;font-weight:500;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.4}
.icard-amount{font-size:30px;font-weight:900;color:#252422;letter-spacing:-.03em;line-height:1}
.icard-cop{font-size:10.5px;font-weight:700;color:#8A867C;letter-spacing:.04em;margin-top:3px}
.icard-sep{height:1px;background:#F0EDE6;margin:12px 0}
.icard-footer{display:flex;align-items:center;justify-content:space-between;gap:6px;margin-top:auto}
.icard-date{font-size:11px;color:#8A867C;flex-shrink:0}
.icard-title{font-size:13.5px;font-weight:700;color:#252422;line-height:1.3;margin-bottom:5px}
.icard-desc{font-size:12px;color:#6B6762;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.icard-meta{font-size:11.5px;color:#8A867C;padding-top:10px;margin-top:auto;border-top:1px solid #F0EDE6;display:flex;flex-wrap:wrap;gap:6px 12px}
.icard-meta span{display:inline-flex;align-items:center;gap:4px}
/* Botón cargar más */
.more-btn{margin-top:12px}
.more-btn button{width:100%;background:#fff;border:1px solid #E4E1D9;border-radius:8px;padding:10px;font-size:12.5px;font-weight:600;color:#57544D;cursor:pointer;font-family:inherit;transition:all .15s}
.more-btn button:hover{border-color:#252422;color:#252422;background:#F5F3EE}

/* ── Documentos grid ──────────────────────────────────────── */
.docs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
.doc{background:#fff;border:1px solid #E4E1D9;border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:8px;text-decoration:none;color:inherit;transition:box-shadow .15s,transform .15s}
.doc:hover{box-shadow:0 4px 14px rgba(0,0,0,.07);transform:translateY(-1px)}
.doc-ico{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.doc-nm{font-size:12px;font-weight:600;color:#252422;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.doc-ext{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;opacity:.7}
.doc-dt{font-size:10.5px;color:#8A867C;margin-top:auto}

/* ── Perfil cards ─────────────────────────────────────────── */
.pf-card{background:#fff;border:1px solid #E4E1D9;border-radius:12px;overflow:hidden;margin-bottom:12px}
.pf-card-hdr{padding:13px 18px;border-bottom:1px solid #F0EDE6;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#8A867C}
.pf-fields{display:grid;grid-template-columns:1fr 1fr}
.pf-item{padding:13px 18px;border-bottom:1px solid #F5F3EF}
.pf-fields .pf-item:nth-child(odd){border-right:1px solid #F5F3EF}
.pf-item.pf-full{grid-column:1/-1;border-right:none}
.pf-lbl{font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#8A867C;margin-bottom:4px}
.pf-val{font-size:13px;font-weight:600;color:#252422}
.pf-val.e{color:#C4C0BA;font-weight:400;font-style:italic}
.pwd-row{display:flex;gap:7px;flex-wrap:wrap;padding:16px 18px}
.inp{padding:8px 11px;border:1.5px solid #E4E1D9;border-radius:6px;font-size:12.5px;font-family:inherit;background:#FAFAF7;color:#252422;outline:none;transition:border-color .15s;flex:1;min-width:130px}
.inp:focus{border-color:#252422;background:#fff}
.btn-dark{padding:8px 16px;background:#252422;color:#fff;border:none;border-radius:6px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;white-space:nowrap;flex-shrink:0;transition:opacity .15s}
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
#toast{position:fixed;bottom:18px;right:18px;z-index:9999;padding:10px 15px;border-radius:7px;font-size:12.5px;font-weight:600;color:#fff;background:#252422;box-shadow:0 4px 16px rgba(0,0,0,.15);opacity:0;transform:translateY(5px);transition:all .2s;pointer-events:none}
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
    .hero-inner{flex-direction:column;gap:0}
    .hero-left{width:100%}
    .hero-right{text-align:left;width:100%;padding:14px 0 0;margin-top:14px;border-top:1px solid rgba(255,255,255,.08)}
    .hero-name{font-size:22px}
    .hero-value{font-size:34px}
    .wrap{padding:16px 12px 56px}
    .svcs{grid-template-columns:1fr;gap:10px}
    .act-row{gap:10px;padding:12px 14px}
    .act-right{min-width:70px}
    .act-val{font-size:13px}
}
@media(max-width:520px){
    /* Nav */
    .nav-nm{display:none}
    /* Hero */
    .hero{padding:18px 14px 22px}
    .hero-name{font-size:20px}
    .hero-value{font-size:30px}
    .hero-pills{gap:5px}
    /* Tabs: grid 3 columnas arriba + 2 centradas abajo */
    .tabs-wrap{overflow-x:unset}
    .tabs{
        display:grid;
        grid-template-columns:repeat(6,1fr);
        flex-wrap:unset;
        gap:3px;
        padding:4px
    }
    .tab{
        flex-direction:column;
        gap:3px;
        padding:9px 4px;
        font-size:10.5px;
        min-height:52px;
        white-space:normal
    }
    .tab span.tl{display:block !important;font-size:10px;line-height:1.2;text-align:center}
    .tab svg{width:14px;height:14px}
    .tab-cnt{font-size:9px;padding:0px 5px}
    /* Columnas: tabs 1-3 ocupan 2 col cada uno (total 6) */
    #tab-pagos {grid-column:1/3}
    #tab-tareas{grid-column:3/5}
    #tab-notif {grid-column:5/7}
    /* Tabs 4-5 ocupan 3 col cada uno (mitad del total) */
    #tab-docs  {grid-column:1/4}
    #tab-perfil{grid-column:4/7}
    /* Filas de actividad */
    .act-row{flex-wrap:wrap;gap:8px;padding:11px 12px}
    .act-right{width:100%;display:flex;align-items:center;gap:8px;padding-left:44px;margin-top:-2px}
    .act-val{font-size:13px}
    .act-est{font-size:10.5px;margin-top:0}
    /* Perfil */
    .perfil-grid{grid-template-columns:1fr}
    .pf-item{border-right:none}
    .pwd-row{flex-direction:column}
    /* Servicios */
    .svc-name{font-size:14px}
    .svc-price-num{font-size:18px}
    /* Cards */
    .cards-grid{grid-template-columns:1fr}
    .icard-amount{font-size:26px}
    .docs-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}
    /* Perfil */
    .pf-fields{grid-template-columns:1fr}
    .pf-fields .pf-item:nth-child(odd){border-right:none}
    .pf-item.pf-full{grid-column:1}
    .pwd-row{flex-direction:column;padding:14px 16px}
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
            <div class="hero-contact" id="heroContact">Encargado: <span id="heroContactName"></span></div>
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
        <div id="listPagos"><div class="loading">Cargando…</div></div>
        <div class="more-btn" id="morePagos" style="display:none"><button onclick="loadMorePagos()">Ver más pagos</button></div>
    </div>
    <!-- Pane tareas -->
    <div class="pane" id="pane-tareas">
        <div id="listTareas"><div class="loading">Cargando…</div></div>
    </div>
    <!-- Pane notif -->
    <div class="pane" id="pane-notif">
        <div id="listNotif"><div class="loading">Cargando…</div></div>
    </div>
    <!-- Pane docs -->
    <div class="pane" id="pane-docs">
        <div id="listDocs"><div class="loading">Cargando…</div></div>
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
    <a href="portal_cliente.php" style="margin-top:4px;padding:9px 18px;background:#252422;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;font-size:13px">Volver al inicio</a>
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
            if(p.nit_cedula) document.getElementById('heroNit').textContent='NIT / ID: '+p.nit_cedula;
            if(p.persona_contacto){
                document.getElementById('heroContactName').textContent=p.persona_contacto;
                document.getElementById('heroContact').style.display='block';
            }
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
        if(!d.data.length&&!append){
            el.innerHTML='<div class="empty"><svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Sin registros de pago</div>';
            return;
        }
        const estLabel={pagado:'Pagado',pendiente:'Pendiente',vencido:'Vencido'};
        const cards=d.data.map(tx=>{
            const fch=tx.fecha_pago||tx.fecha_vencimiento||tx.created_at;
            const srv=esc(tx.servicio_nombre||'');
            const concepto=esc(tx.titulo||tx.concepto||'');
            return`<div class="icard">
                <div class="icard-top">
                    <div class="icard-srv">${srv||concepto}</div>
                    <span class="est-badge ${esc(tx.estado)}">${estLabel[tx.estado]||tx.estado}</span>
                </div>
                <div class="icard-amount">${fn(tx.monto)}</div>
                <div class="icard-cop">COP</div>
                <div class="icard-sep"></div>
                <div class="icard-footer">
                    <span class="icard-date">${fd(fch)}</span>
                    ${srv&&concepto&&srv!==concepto?`<span style="font-size:11px;color:#57544D;font-weight:500;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${concepto}</span>`:''}
                </div>
            </div>`;
        }).join('');
        if(append) el.querySelector('.cards-grid').insertAdjacentHTML('beforeend',cards);
        else el.innerHTML=`<div class="cards-grid">${cards}</div>`;
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
        if(!cnt){
            el.innerHTML='<div class="empty"><svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Sin tareas registradas</div>';
            return;
        }
        const estL={pendiente:'Pendiente',en_progreso:'En progreso',revision:'En revisión',completado:'Completado',cancelado:'Cancelado'};
        const estC={pendiente:'pendiente2',en_progreso:'en_progreso',revision:'revision',completado:'completado',cancelado:'cancelado'};
        const prioC={alta:'#EF4444',media:'#F59E0B',baja:'#10B981'};
        const prioL={alta:'Alta',media:'Media',baja:'Baja'};
        el.innerHTML='<div class="cards-grid">'+d.data.map(t=>{
            const pc=prioC[t.prioridad]||'#8A867C';
            const meta=[];
            if(t.responsable) meta.push(`<span><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>${esc(t.responsable)}</span>`);
            if(t.fecha_limite) meta.push(`<span><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>${fd(t.fecha_limite)}</span>`);
            return`<div class="icard">
                <div class="icard-top">
                    <span class="prio-badge" style="background:${pc}20;color:${pc}"><span style="width:6px;height:6px;border-radius:50%;background:${pc};display:inline-block;flex-shrink:0"></span>${prioL[t.prioridad]||t.prioridad}</span>
                    <span class="est-badge ${estC[t.estado]||''}">${estL[t.estado]||t.estado}</span>
                </div>
                <div class="icard-title">${esc(t.titulo)}</div>
                ${t.descripcion?`<div class="icard-desc">${esc(t.descripcion)}</div>`:''}
                ${meta.length?`<div class="icard-meta">${meta.join('')}</div>`:''}
            </div>`;
        }).join('')+'</div>';
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
        if(!cnt){
            el.innerHTML='<div class="empty"><svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>Sin notificaciones enviadas</div>';
            return;
        }
        el.innerHTML='<div class="cards-grid">'+d.data.map(n=>{
            return`<div class="icard">
                <div class="icard-top">
                    <div class="icard-srv">${esc(n.nombre_servicio||'Servicio')}</div>
                    <span style="font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:100px;background:#EEF2FB;color:#3F5E9E;white-space:nowrap;flex-shrink:0">${esc(n.label)}</span>
                </div>
                <div class="icard-title">Recordatorio de renovación</div>
                <div class="icard-sep"></div>
                <div class="icard-footer">
                    <span class="icard-date"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:middle;margin-right:3px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>Enviado ${fd(n.fecha)}</span>
                    ${n.vencimiento?`<span class="icard-date">Vence ${fd(n.vencimiento)}</span>`:''}
                </div>
            </div>`;
        }).join('')+'</div>';
    }catch(e){el.innerHTML='<div class="empty">Error al cargar</div>';}
}

// ── Documentos ─────────────────────────────────────────────
async function loadDocs(){
    const el=document.getElementById('listDocs');
    try{
        const d=await fetch('api/portal_data.php?action=documentos').then(r=>r.json());
        if(revocado(d))return;
        const items=[];
        (d.archivos||[]).forEach(a=>{
            const ext=((a.archivo_url||'').split('.').pop()||'').toLowerCase();
            items.push({url:a.archivo_url,name:a.nombre_archivo||'Archivo',date:a.created_at,ext,isImg:['jpg','jpeg','png','webp'].includes(ext)});
        });
        (d.tx_docs||[]).forEach(tx=>{
            ['factura_path','documento_path','imagen_path'].forEach(k=>{
                if(tx[k]){const ext=(tx[k].split('.').pop()||'').toLowerCase();items.push({url:tx[k],name:tx.nombre_archivo||'Documento',date:tx.created_at,ext,isImg:['jpg','jpeg','png','webp'].includes(ext)});}
            });
        });
        document.getElementById('cntDocs').textContent=items.length;
        if(!items.length){
            el.innerHTML='<div class="empty"><svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>Sin documentos</div>';
            return;
        }
        const cc={pdf:'#EF4444',doc:'#3B82F6',docx:'#3B82F6',xls:'#10B981',xlsx:'#10B981',jpg:'#F59E0B',jpeg:'#F59E0B',png:'#8B5CF6',webp:'#8B5CF6'};
        const gc=e=>cc[e]||'#6B7280';
        el.innerHTML='<div class="docs-grid">'+items.map(it=>{
            const c=gc(it.ext),bg=c+'18';
            const ico=it.isImg
                ?`<img src="${esc(it.url)}" style="width:36px;height:36px;object-fit:cover;border-radius:7px" onerror="this.parentNode.style.background='${bg}'">`
                :`<svg width="17" height="17" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`;
            return`<a class="doc" href="${esc(it.url)}" target="_blank" rel="noopener">
                <div class="doc-ico" style="background:${bg}">${ico}</div>
                <div class="doc-nm">${esc(it.name)}</div>
                <div class="doc-ext" style="color:${c}">${it.ext.toUpperCase()||'—'}</div>
                <div class="doc-dt">${fd(it.date)}</div>
            </a>`;
        }).join('')+'</div>';
    }catch(e){el.innerHTML='<div class="empty">Error al cargar</div>';}
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
        const pwdTxt=p.has_custom_password
            ?'<span style="color:#1A6B41;font-weight:700">✓ Contraseña personalizada activa</span>'
            :'<span style="color:#8A867C;font-style:italic">Usando NIT como contraseña por defecto</span>';
        el.innerHTML=`
        <div class="pf-card">
            <div class="pf-card-hdr">Información del cliente</div>
            <div class="pf-fields">
                ${fi('Empresa / Razón social',p.nombre_comercial)}
                ${fi('NIT / Identificación',p.nit_cedula)}
                ${fi('Persona de contacto',p.persona_contacto)}
                ${fi('Correo de contacto',p.email_contacto)}
                ${fi('Correo facturación',p.email_facturacion)}
                ${fi('Teléfono',p.telefono)}
                <div class="pf-item pf-full"><div class="pf-lbl">Dirección</div><div class="pf-val ${!p.direccion?'e':''}">${p.direccion?esc(p.direccion):'No registrada'}</div></div>
                <div class="pf-item pf-full"><div class="pf-lbl">Acceso al portal</div><div class="pf-val">${pwdTxt}</div></div>
            </div>
        </div>
        <div class="pf-card">
            <div class="pf-card-hdr">Cambiar contraseña de acceso</div>
            <div class="pwd-row">
                <input class="inp" type="password" id="pwdA" placeholder="Contraseña actual" autocomplete="current-password">
                <input class="inp" type="password" id="pwdN" placeholder="Nueva contraseña (mín. 6)" autocomplete="new-password">
                <input class="inp" type="password" id="pwdC" placeholder="Confirmar nueva contraseña" autocomplete="new-password">
                <button class="btn-dark" onclick="cambiarPwd()">Guardar</button>
            </div>
        </div>`;
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
