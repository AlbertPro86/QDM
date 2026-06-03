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
body{font-family:'Inter',sans-serif;background:#EDEAE3;color:#1E1D1B;min-height:100vh}

/* ── Nav ───────────────────────────────────────────────────── */
.nav{background:#1E1D1B;height:48px;display:flex;align-items:center;padding:0 20px;gap:10px;position:sticky;top:0;z-index:200}
.nav-logo img{height:20px;display:block}
.nav-sep{width:1px;height:14px;background:rgba(255,255,255,.1)}
.nav-tag{font-size:9.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#C6F24E;background:rgba(198,242,78,.1);padding:2px 7px;border-radius:100px}
.nav-sp{flex:1}
.nav-av{width:26px;height:26px;border-radius:50%;background:#C6F24E;color:#1E1D1B;font-size:9px;font-weight:900;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.nav-nm{font-size:12px;font-weight:600;color:rgba(255,255,255,.65);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nav-out{display:flex;align-items:center;gap:4px;padding:4px 10px;background:transparent;border:1px solid rgba(255,255,255,.12);border-radius:5px;font-size:11px;font-weight:500;color:rgba(255,255,255,.4);cursor:pointer;font-family:inherit;transition:all .15s}
.nav-out:hover{border-color:rgba(255,255,255,.3);color:rgba(255,255,255,.8)}

/* ── Hero ──────────────────────────────────────────────────── */
.hero{background:#1E1D1B;padding:22px 20px 26px}
.hero-inner{max-width:920px;margin:0 auto;display:flex;align-items:flex-start;justify-content:space-between;gap:28px}
.hero-left{flex:1;min-width:0}
.hero-label{font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.28);margin-bottom:6px}
.hero-company{font-size:24px;font-weight:900;color:#fff;line-height:1.1;letter-spacing:-.02em}
.hero-person{font-size:12px;color:rgba(255,255,255,.45);margin-top:4px;display:none}
.hero-person b{color:rgba(255,255,255,.72);font-weight:600}
.hero-nit{font-size:11px;color:rgba(255,255,255,.25);margin-top:2px}
.hero-pills{display:flex;gap:5px;flex-wrap:wrap;margin-top:11px}
.hpill{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;letter-spacing:.01em}
.hpill.ok    {background:rgba(34,197,94,.15);color:#6EE7A8}
.hpill.warn  {background:rgba(234,179,8,.15);color:#FCD34D}
.hpill.danger{background:rgba(239,68,68,.15);color:#FCA5A5}
.hpill.grey  {background:rgba(255,255,255,.07);color:rgba(255,255,255,.38)}
.hero-right{flex-shrink:0;text-align:right}
.hero-vlabel{font-size:10px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:rgba(255,255,255,.25);margin-bottom:5px}
.hero-vnum{font-size:38px;font-weight:900;color:#fff;line-height:1;letter-spacing:-.03em}
.hero-vcop{font-size:11px;font-weight:700;color:rgba(255,255,255,.28);letter-spacing:.04em;margin-top:3px}
.hero-renov{margin-top:9px;font-size:11.5px;color:rgba(255,255,255,.35)}
.hero-renov b{color:rgba(255,255,255,.6)}

/* ── Wrap ──────────────────────────────────────────────────── */
.wrap{max-width:920px;margin:0 auto;padding:16px 16px 64px}

/* ── Alert ─────────────────────────────────────────────────── */
.alert-bar{display:none;align-items:center;gap:8px;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:12.5px;font-weight:500;line-height:1.4}
.alert-bar.warn  {background:#FEF9E7;border:1px solid #F6D860;color:#7A5200}
.alert-bar.danger{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B}

/* ── Stats strip ───────────────────────────────────────────── */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px}
.stat{background:#fff;border:1px solid #E4E1D9;border-radius:10px;padding:13px 14px}
.stat-v{font-size:15px;font-weight:900;color:#1E1D1B;letter-spacing:-.01em;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.stat-v.big{font-size:22px}
.stat-v.ok  {color:#15803D}
.stat-v.warn{color:#B45309}
.stat-v.danger{color:#991B1B}
.stat-l{font-size:10px;color:#8A867C;margin-top:3px;font-weight:500}

/* ── Bloque de servicios ───────────────────────────────────── */
.svc-block{background:#fff;border:1px solid #E4E1D9;border-radius:12px;overflow:hidden;margin-bottom:16px}
.block-hdr{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #F0EDE6}
.block-hdr-title{font-size:12px;font-weight:700;color:#1E1D1B;letter-spacing:.01em}
.block-hdr-badge{font-size:10.5px;font-weight:800;padding:2px 9px;border-radius:100px;background:#F0EDE6;color:#57544D}

/* Filas de servicio */
.svc-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #F5F3EF;transition:background .12s}
.svc-row:last-child{border-bottom:none}
.svc-row:hover{background:#FAFAF8}
.svc-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.svc-info{flex:1;min-width:0}
.svc-name{font-size:13.5px;font-weight:700;color:#1E1D1B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:4px}
.svc-chips{display:flex;gap:5px;flex-wrap:wrap;align-items:center}
.svc-price{text-align:right;flex-shrink:0}
.svc-price-num{font-size:15px;font-weight:900;color:#1E1D1B;letter-spacing:-.01em}
.svc-price-sub{font-size:10px;font-weight:600;color:#8A867C;margin-top:1px}

/* ── Chips ─────────────────────────────────────────────────── */
.ch{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:100px;font-size:10.5px;font-weight:600;white-space:nowrap;line-height:1.5}
.ch.ok    {background:#DCFCE7;color:#15803D}
.ch.warn  {background:#FEF9C3;color:#A16207}
.ch.danger{background:#FEE2E2;color:#991B1B}
.ch.grey  {background:#F0EDE6;color:#78746D}
.ch.blue  {background:#EFF6FF;color:#1D4ED8}
.ch.pagado    {background:#DCFCE7;color:#15803D}
.ch.pendiente {background:#FEF9C3;color:#A16207}
.ch.vencido   {background:#FEE2E2;color:#991B1B}
.ch.completado{background:#DCFCE7;color:#15803D}
.ch.en_progreso,.ch.revision{background:#EFF6FF;color:#1D4ED8}
.ch.cancelado {background:#FEE2E2;color:#991B1B}
.ch.alta {background:#FEE2E2;color:#991B1B}
.ch.media{background:#FEF9C3;color:#A16207}
.ch.baja {background:#DCFCE7;color:#15803D}

/* ── Área de actividad ─────────────────────────────────────── */
.activity{background:#fff;border:1px solid #E4E1D9;border-radius:12px;overflow:hidden}

/* ── Tab bar ───────────────────────────────────────────────── */
.tab-bar{display:flex;border-bottom:1px solid #F0EDE6;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.tab-bar::-webkit-scrollbar{display:none}
.tab-btn{flex:1;min-width:0;display:flex;align-items:center;justify-content:center;gap:6px;padding:13px 8px;border:none;background:transparent;font-size:12.5px;font-weight:500;color:#8A867C;cursor:pointer;font-family:inherit;border-bottom:2.5px solid transparent;margin-bottom:-1px;transition:all .15s;white-space:nowrap}
.tab-btn:hover{color:#1E1D1B;background:#FAFAF8}
.tab-btn.on{font-weight:700;color:#1E1D1B;border-bottom-color:#1E1D1B}
.tab-btn svg{opacity:.5;flex-shrink:0;transition:opacity .15s}
.tab-btn.on svg{opacity:1}
.tab-btn .cnt{font-size:10px;padding:1px 6px;border-radius:100px;background:#F0EDE6;color:#57544D;font-weight:800;line-height:1.5}
.tab-btn.on .cnt{background:#1E1D1B;color:#fff}

/* ── Panes ─────────────────────────────────────────────────── */
.pane{display:none}
.pane.on{display:block}

/* ── List rows (dentro de panes) ──────────────────────────── */
.lrow{display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-bottom:1px solid #F5F3EF;transition:background .1s}
.lrow:last-child{border-bottom:none}
.lrow:hover{background:#FAFAF8}
.lrow-mark{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px}
.lrow-body{flex:1;min-width:0}
.lrow-title{font-size:13px;font-weight:600;color:#1E1D1B;line-height:1.35;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lrow-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:4px}
.lrow-right{flex-shrink:0;text-align:right;min-width:80px}
.lrow-val{font-size:13.5px;font-weight:900;color:#1E1D1B;white-space:nowrap;letter-spacing:-.01em}
.lrow-date{font-size:11px;color:#8A867C;margin-top:2px}

/* ── Más pagos ─────────────────────────────────────────────── */
.load-more{padding:12px 16px;border-top:1px solid #F0EDE6;text-align:center}
.load-more button{background:none;border:none;font-size:12.5px;font-weight:600;color:#57544D;cursor:pointer;font-family:inherit;transition:color .15s;padding:2px 8px}
.load-more button:hover{color:#1E1D1B}

/* ── Documentos ────────────────────────────────────────────── */
.drow{display:flex;align-items:center;gap:12px;padding:11px 16px;border-bottom:1px solid #F5F3EF;text-decoration:none;color:inherit;transition:background .1s}
.drow:last-child{border-bottom:none}
.drow:hover{background:#FAFAF8}
.drow-ico{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.drow-info{flex:1;min-width:0}
.drow-name{font-size:13px;font-weight:600;color:#1E1D1B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.drow-meta{font-size:11px;color:#8A867C;margin-top:2px}
.drow-ext{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}

/* ── Accesos ───────────────────────────────────────────────── */
.acc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;padding:14px 16px}
.acc-card{background:#FAFAF8;border:1px solid #EDEAE3;border-radius:10px;padding:14px 15px}
.acc-title{font-size:13px;font-weight:700;color:#1E1D1B;margin-bottom:10px;display:flex;align-items:center;gap:7px}
.acc-ico{width:28px;height:28px;border-radius:7px;background:#F0EDE6;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.acc-row{display:flex;align-items:center;gap:7px;margin-top:7px}
.acc-lbl{font-size:9.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#8A867C;width:56px;flex-shrink:0}
.acc-val{flex:1;font-size:12px;font-weight:600;color:#1E1D1B;font-family:'Courier New',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.acc-val.hidden-pwd{letter-spacing:.18em;color:#C0BBB3}
.acc-btn{width:26px;height:26px;display:flex;align-items:center;justify-content:center;border:1px solid #E4E1D9;border-radius:5px;background:#fff;cursor:pointer;flex-shrink:0;transition:all .12s;color:#78746D}
.acc-btn:hover{background:#F0EDE6;color:#1E1D1B}
.acc-date{font-size:10px;color:#B8B3AB;margin-top:9px}

/* ── Perfil ────────────────────────────────────────────────── */
.pf-2col{display:grid;grid-template-columns:1fr 1fr}
.pf-field{padding:12px 16px;border-bottom:1px solid #F5F3EF}
.pf-field:nth-child(odd){border-right:1px solid #F5F3EF}
.pf-field.full{grid-column:1/-1;border-right:none}
.pf-label{font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#8A867C;margin-bottom:3px}
.pf-value{font-size:13px;font-weight:600;color:#1E1D1B}
.pf-value.empty{color:#C0BBB3;font-weight:400;font-style:italic}
.pwd-section{padding:14px 16px;border-top:1px solid #F0EDE6;background:#FAFAF8}
.pwd-section-title{font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#8A867C;margin-bottom:10px}
.pwd-fields{display:flex;gap:8px;flex-wrap:wrap}
.inp{flex:1;min-width:120px;padding:8px 11px;border:1.5px solid #E4E1D9;border-radius:7px;font-size:12.5px;font-family:inherit;background:#fff;color:#1E1D1B;outline:none;transition:border-color .15s}
.inp:focus{border-color:#1E1D1B}
.btn-save{padding:8px 16px;background:#1E1D1B;color:#fff;border:none;border-radius:7px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;white-space:nowrap;flex-shrink:0;transition:opacity .15s}
.btn-save:hover{opacity:.8}
.btn-save:disabled{opacity:.4;cursor:not-allowed}

/* ── Loading / Empty ───────────────────────────────────────── */
.loading{padding:28px;text-align:center;color:#8A867C;font-size:12.5px}
.empty-state{padding:32px 20px;text-align:center;color:#8A867C;font-size:13px}
.empty-state svg{display:block;margin:0 auto 10px;opacity:.18}

/* ── Toast ─────────────────────────────────────────────────── */
#toast{position:fixed;bottom:18px;right:18px;z-index:9999;padding:10px 15px;border-radius:8px;font-size:12.5px;font-weight:600;color:#fff;background:#1E1D1B;box-shadow:0 4px 18px rgba(0,0,0,.16);opacity:0;transform:translateY(6px);transition:all .22s;pointer-events:none;max-width:300px}
#toast.show{opacity:1;transform:none}
#toast.ok {background:#15803D}
#toast.err{background:#991B1B}

/* ── Revocado overlay ──────────────────────────────────────── */
#revocado{display:none;position:fixed;inset:0;z-index:9000;background:#fff;flex-direction:column;align-items:center;justify-content:center;gap:12px;text-align:center;padding:24px}
#revocado.show{display:flex}

/* ════════════════════════════════════════════════════════════
   RESPONSIVE
   ════════════════════════════════════════════════════════════ */

/* Tablet y debajo: stats 2 filas */
@media(max-width:700px){
    .stats{grid-template-columns:repeat(2,1fr)}
    .hero-vnum{font-size:30px}
    .hero-company{font-size:20px}
}

/* Móvil: hero apilado */
@media(max-width:580px){
    .hero-inner{flex-direction:column;gap:0}
    .hero-right{width:100%;text-align:left;padding-top:14px;margin-top:14px;border-top:1px solid rgba(255,255,255,.08)}
    .hero-vnum{font-size:28px}
    .hero-company{font-size:19px}
    .wrap{padding:12px 12px 56px}
    .tab-btn{font-size:11.5px;padding:11px 6px;gap:4px}
    .tab-btn svg{width:12px;height:12px}
    .lrow-val{font-size:12.5px}
}

/* Móvil pequeño */
@media(max-width:440px){
    .nav-nm{display:none}
    .hero{padding:16px 14px 20px}
    .tab-btn .tl{display:none}
    .tab-btn{padding:12px}
    .svc-price-num{font-size:13px}
    .lrow-right{min-width:65px}
    .pf-2col{grid-template-columns:1fr}
    .pf-field:nth-child(odd){border-right:none}
    .pf-field.full{grid-column:1}
    .pwd-fields{flex-direction:column}
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
        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Salir
    </button>
</nav>

<!-- Hero -->
<div class="hero">
    <div class="hero-inner">
        <div class="hero-left">
            <div class="hero-label">Portal de cliente</div>
            <div class="hero-company" id="heroNombre"><?= $nombre ?></div>
            <div class="hero-person" id="heroContact">Encargado: <b id="heroContactName"></b></div>
            <div class="hero-nit" id="heroNit"></div>
            <a class="hero-web" id="heroWeb" href="#" target="_blank" rel="noopener" style="display:none;margin-top:5px;font-size:11.5px;font-weight:600;color:rgba(198,242,78,.75);text-decoration:none;align-items:center;gap:5px" onmouseenter="this.style.color='#C6F24E'" onmouseleave="this.style.color='rgba(198,242,78,.75)'">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                <span id="heroWebLabel"></span>
            </a>
            <div class="hero-pills" id="heroPills"><span class="hpill grey">Cargando…</span></div>
        </div>
        <div class="hero-right">
            <div class="hero-vlabel">Valor de suscripción</div>
            <div class="hero-vnum" id="heroValor">—</div>
            <div class="hero-vcop">COP</div>
            <div class="hero-renov" id="heroRenov"></div>
        </div>
    </div>
</div>

<!-- Contenido principal -->
<div class="wrap">

    <!-- Alerta -->
    <div class="alert-bar" id="alertBar">
        <span id="alertIco" style="font-size:15px;flex-shrink:0"></span>
        <span id="alertTxt"></span>
    </div>

    <!-- Stats strip -->
    <div class="stats">
        <div class="stat">
            <div class="stat-v big" id="stActivos">—</div>
            <div class="stat-l">Servicios activos</div>
        </div>
        <div class="stat">
            <div class="stat-v" id="stValor">—</div>
            <div class="stat-l">Suscripción mensual</div>
        </div>
        <div class="stat">
            <div class="stat-v" id="stPagado">—</div>
            <div class="stat-l">Total pagado</div>
        </div>
        <div class="stat">
            <div class="stat-v" id="stRenov">—</div>
            <div class="stat-l" id="stRenovLbl">Próx. renovación</div>
        </div>
    </div>

    <!-- Bloque servicios -->
    <div class="svc-block" id="blkSvcs" style="display:none">
        <div class="block-hdr">
            <span class="block-hdr-title">Mis servicios</span>
            <span class="block-hdr-badge" id="cntSvcs"></span>
        </div>
        <div id="listSvcs"><div class="loading">Cargando…</div></div>
    </div>

    <!-- Actividad: tabs + panes -->
    <div class="activity">

        <!-- Tab bar -->
        <div class="tab-bar">
            <button class="tab-btn on" id="tab-pagos" onclick="setTab('pagos',this)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                <span class="tl">Pagos</span>
                <span class="cnt" id="cntPagos"></span>
            </button>
            <button class="tab-btn" id="tab-tareas" onclick="setTab('tareas',this)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="tl">Tareas</span>
                <span class="cnt" id="cntTareas"></span>
            </button>
            <button class="tab-btn" id="tab-notif" onclick="setTab('notif',this)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="tl">Notificaciones</span>
                <span class="cnt" id="cntNotif"></span>
            </button>
            <button class="tab-btn" id="tab-docs" onclick="setTab('docs',this)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="tl">Documentos</span>
                <span class="cnt" id="cntDocs"></span>
            </button>
            <button class="tab-btn" id="tab-accesos" onclick="setTab('accesos',this)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                <span class="tl">Accesos</span>
                <span class="cnt" id="cntAccesos"></span>
            </button>
            <button class="tab-btn" id="tab-perfil" onclick="setTab('perfil',this)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="tl">Mi Perfil</span>
            </button>
        </div>

        <!-- Pane: Pagos -->
        <div class="pane on" id="pane-pagos">
            <div id="listPagos"><div class="loading">Cargando…</div></div>
            <div class="load-more" id="morePagos" style="display:none">
                <button onclick="loadMorePagos()">Ver más pagos</button>
            </div>
        </div>

        <!-- Pane: Tareas -->
        <div class="pane" id="pane-tareas">
            <div id="listTareas"><div class="loading">Cargando…</div></div>
        </div>

        <!-- Pane: Notificaciones -->
        <div class="pane" id="pane-notif">
            <div id="listNotif"><div class="loading">Cargando…</div></div>
        </div>

        <!-- Pane: Documentos -->
        <div class="pane" id="pane-docs">
            <div id="listDocs"><div class="loading">Cargando…</div></div>
        </div>

        <!-- Pane: Accesos -->
        <div class="pane" id="pane-accesos">
            <div id="listAccesos"><div class="loading">Cargando…</div></div>
        </div>

        <!-- Pane: Perfil -->
        <div class="pane" id="pane-perfil">
            <div id="perfilContent"><div class="loading">Cargando…</div></div>
        </div>

    </div><!-- /activity -->

</div><!-- /wrap -->

<!-- Revocado -->
<div id="revocado">
    <svg width="40" height="40" fill="none" stroke="#991B1B" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div style="font-size:17px;font-weight:800;color:#1E1D1B">Acceso desactivado</div>
    <div style="font-size:13px;color:#57544D;max-width:300px;line-height:1.5">Tu acceso al portal fue desactivado. Comunícate con QUANTUN Digital.</div>
    <a href="portal_cliente.php" style="padding:9px 20px;background:#1E1D1B;color:#fff;border-radius:7px;text-decoration:none;font-weight:700;font-size:13px;margin-top:4px">Volver al inicio</a>
</div>
<div id="toast"></div>

<script>
// ── Utils ──────────────────────────────────────────────────
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

// ── Tabs (lazy load) ───────────────────────────────────────
const _ld={pagos:false,tareas:false,notif:false,docs:false,accesos:false,perfil:false};
function setTab(name,btn){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('on'));
    document.querySelectorAll('.pane').forEach(p=>p.classList.remove('on'));
    btn.classList.add('on');
    document.getElementById('pane-'+name).classList.add('on');
    if(!_ld[name]){
        _ld[name]=true;
        ({pagos:loadPagos,tareas:loadTareas,notif:loadNotif,docs:loadDocs,accesos:loadAccesos,perfil:loadPerfil})[name]();
    }
}

// ── Hero + Stats ───────────────────────────────────────────
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
            if(p.sitio_web){
                const hw=document.getElementById('heroWeb');
                const href=/^https?:\/\//i.test(p.sitio_web)?p.sitio_web:'https://'+p.sitio_web;
                hw.href=href;
                document.getElementById('heroWebLabel').textContent=p.sitio_web.replace(/^https?:\/\//i,'').replace(/\/$/,'');
                hw.style.display='flex';
            }
            if(p.persona_contacto){
                document.getElementById('heroContactName').textContent=p.persona_contacto;
                document.getElementById('heroContact').style.display='block';
            }
        }

        if(!sd.success)return;
        const val=sd.valor_suscripcion||0;

        // Hero valor
        document.getElementById('heroValor').textContent=val>0?fn(val).replace('$ ',''):'0';

        // Hero renovación
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            const txt=dias<0?`Vencida hace ${Math.abs(dias)} días`
                :dias===0?'Vence hoy'
                :`Renueva <b>${fd(sd.proxima_renovacion)}</b> · en ${dias} días`;
            document.getElementById('heroRenov').innerHTML=txt;
        }

        // Hero pills
        const pills=[];
        if(sd.servicios_activos>0)
            pills.push(`<span class="hpill ok"><svg width="7" height="7" fill="#6EE7A8" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>${sd.servicios_activos} servicio${sd.servicios_activos!==1?'s':''} activo${sd.servicios_activos!==1?'s':''}</span>`);
        if(sd.proxima_renovacion){
            const d=sd.dias_renovacion;
            const cls=d<0?'danger':d<=7?'danger':d<=30?'warn':'ok';
            const txt=d<0?'Vencido':d<=7?`Vence en ${d}d`:d<=30?`${d} días para renovar`:'Al día';
            pills.push(`<span class="hpill ${cls}">${txt}</span>`);
        }
        if(sd.total_pendiente>0)
            pills.push(`<span class="hpill warn">${fn(sd.total_pendiente)} pendiente</span>`);
        document.getElementById('heroPills').innerHTML=pills.join('')||'<span class="hpill grey">Sin servicios activos</span>';

        // Stats strip — precios completos
        document.getElementById('stActivos').textContent=sd.servicios_activos||'0';
        document.getElementById('stValor').textContent=val>0?fn(val):'$ 0';
        document.getElementById('stPagado').textContent=sd.total_pagado>0?fn(sd.total_pagado):'$ 0';
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            const el=document.getElementById('stRenov');
            el.textContent=fd(sd.proxima_renovacion);
            el.className='stat-v'+(dias<0?' danger':dias<=7?' danger':dias<=30?' warn':'');
            document.getElementById('stRenovLbl').textContent=dias<0?'¡Vencida!':dias===0?'¡Hoy!':dias<=7?`En ${dias} días`:dias<=30?`En ${dias} días`:'Próx. renovación';
        }else{
            document.getElementById('stRenov').textContent='—';
        }

        // Alert bar
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            if(dias<=30){
                const ab=document.getElementById('alertBar');
                document.getElementById('alertIco').textContent=dias<0?'⚠️':dias<=7?'🔔':'📅';
                document.getElementById('alertTxt').innerHTML=dias<0
                    ?`<strong>Suscripción vencida hace ${Math.abs(dias)} días.</strong> Comunícate con QUANTUN Digital para renovar.`
                    :dias<=7
                    ?`<strong>Tu servicio vence en ${dias} día${dias!==1?'s':''}.</strong> Realiza tu pago para evitar la suspensión.`
                    :`Tu próxima renovación es el <strong>${fd(sd.proxima_renovacion)}</strong>, en ${dias} días.`;
                ab.className='alert-bar '+(dias<=7?'danger':'warn');
                ab.style.display='flex';
            }
        }
    }catch(e){}
}

// ── Servicios ──────────────────────────────────────────────
async function loadSvcs(){
    const el=document.getElementById('listSvcs');
    const blk=document.getElementById('blkSvcs');
    try{
        const d=await fetch('api/portal_data.php?action=servicios').then(r=>r.json());
        if(revocado(d))return;
        if(!d.success||!d.data.length){blk.style.display='none';return;}
        blk.style.display='block';
        document.getElementById('cntSvcs').textContent=d.data.length;
        const dotColor={activo:'#15803D',suspendido:'#B45309',cancelado:'#991B1B'};
        el.innerHTML=d.data.map(sv=>{
            const val=Math.max(0,parseFloat(sv.valor_suscripcion)||0);
            const dias=parseInt(sv.dias_para_vencimiento);
            const esUnico=(sv.frecuencia||'').toLowerCase()==='unico';
            let rCls='grey',rTxt='—';
            if(sv.estado==='activo'&&sv.fecha_vencimiento&&!esUnico){
                if(dias<0){rCls='danger';rTxt=`Venció hace ${Math.abs(dias)} días`;}
                else if(dias<=7){rCls='danger';rTxt=`Vence en ${dias} día${dias!==1?'s':''}`;}
                else if(dias<=30){rCls='warn';rTxt=`Vence el ${fd(sv.fecha_vencimiento)}`;}
                else{rCls='ok';rTxt=`Vigente hasta ${fd(sv.fecha_vencimiento)}`;}
            }else if(esUnico){rCls='grey';rTxt='Pago único';}
            return`<div class="svc-row">
                <div class="svc-dot" style="background:${dotColor[sv.estado]||'#8A867C'}"></div>
                <div class="svc-info">
                    <div class="svc-name">${esc(sv.nombre_servicio)}</div>
                    <div class="svc-chips">
                        ${!esUnico?`<span class="ch blue">${esc(ff(sv.frecuencia))}</span>`:''}
                        <span class="ch ${rCls}">${esc(rTxt)}</span>
                        ${sv.fecha_inicio?`<span class="ch grey">Desde ${fd(sv.fecha_inicio)}</span>`:''}
                    </div>
                </div>
                <div class="svc-price">
                    ${val>0?`<div class="svc-price-num">${fn(val)}</div><div class="svc-price-sub">COP</div>`:''}
                </div>
            </div>`;
        }).join('');
    }catch(e){el.innerHTML='<div class="loading">Error al cargar servicios</div>';}
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
        const cntEl=document.getElementById('cntPagos');
        cntEl.textContent=_pTotal;
        cntEl.style.display=_pTotal?'inline':'none';
        if(!d.data.length&&!append){
            el.innerHTML='<div class="empty-state"><svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>Sin registros de pago</div>';
            return;
        }
        const estL={pagado:'Pagado',pendiente:'Pendiente',vencido:'Vencido'};
        const rows=d.data.map(tx=>{
            const fch=tx.fecha_pago||tx.fecha_vencimiento||tx.created_at;
            const srv=tx.servicio_nombre||'';
            const tit=tx.titulo||tx.concepto||'';
            return`<div class="lrow">
                <div class="lrow-body">
                    <div class="lrow-title">${esc(tit)}</div>
                    <div class="lrow-meta">
                        <span class="ch ${esc(tx.estado)}">${estL[tx.estado]||tx.estado}</span>
                        ${srv&&srv!==tit?`<span style="font-size:11px;color:#78746D">${esc(srv)}</span>`:''}
                        <span style="font-size:11px;color:#78746D">${fd(fch)}</span>
                    </div>
                </div>
                <div class="lrow-right">
                    <div class="lrow-val">${fn(tx.monto)}</div>
                </div>
            </div>`;
        }).join('');
        if(append) el.insertAdjacentHTML('beforeend',rows);
        else el.innerHTML=rows;
        _pOff+=d.data.length;
        document.getElementById('morePagos').style.display=_pOff<_pTotal?'block':'none';
    }catch(e){if(!append)el.innerHTML='<div class="empty-state">Error al cargar</div>';}
}
function loadMorePagos(){loadPagos(true);}

// ── Tareas ─────────────────────────────────────────────────
async function loadTareas(){
    const el=document.getElementById('listTareas');
    try{
        const d=await fetch('api/portal_data.php?action=tareas').then(r=>r.json());
        if(revocado(d))return;
        const cnt=d.data?d.data.length:0;
        const cntEl=document.getElementById('cntTareas');
        cntEl.textContent=cnt;
        cntEl.style.display=cnt?'inline':'none';
        if(!cnt){
            el.innerHTML='<div class="empty-state"><svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Sin tareas asignadas</div>';
            return;
        }
        const estL={pendiente:'Pendiente',en_progreso:'En progreso',revision:'En revisión',completado:'Completado',cancelado:'Cancelado'};
        const estC={pendiente:'warn',en_progreso:'en_progreso',revision:'revision',completado:'completado',cancelado:'cancelado'};
        const prioC={alta:'#EF4444',media:'#F59E0B',baja:'#22C55E'};
        const prioL={alta:'Alta',media:'Media',baja:'Baja'};
        el.innerHTML=d.data.map(t=>{
            const pc=prioC[t.prioridad]||'#8A867C';
            const meta=[];
            if(t.responsable) meta.push(`<span style="font-size:11px;color:#78746D">${esc(t.responsable)}</span>`);
            if(t.fecha_limite) meta.push(`<span style="font-size:11px;color:#78746D">Límite: ${fd(t.fecha_limite)}</span>`);
            return`<div class="lrow">
                <div class="lrow-mark" style="background:${pc}"></div>
                <div class="lrow-body">
                    <div class="lrow-title">${esc(t.titulo)}</div>
                    <div class="lrow-meta">
                        <span class="ch ${estC[t.estado]||'grey'}">${estL[t.estado]||t.estado}</span>
                        <span class="ch ${t.prioridad||'grey'}" style="font-size:10px">${prioL[t.prioridad]||'—'}</span>
                        ${meta.join('')}
                    </div>
                    ${t.descripcion?`<div style="font-size:11.5px;color:#78746D;margin-top:4px;line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">${esc(t.descripcion)}</div>`:''}
                </div>
            </div>`;
        }).join('');
    }catch(e){el.innerHTML='<div class="empty-state">Error al cargar</div>';}
}

// ── Notificaciones ─────────────────────────────────────────
async function loadNotif(){
    const el=document.getElementById('listNotif');
    try{
        const d=await fetch('api/portal_data.php?action=notificaciones').then(r=>r.json());
        if(revocado(d))return;
        const cnt=d.data?d.data.length:0;
        const cntEl=document.getElementById('cntNotif');
        cntEl.textContent=cnt;
        cntEl.style.display=cnt?'inline':'none';
        if(!cnt){
            el.innerHTML='<div class="empty-state"><svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>Sin notificaciones enviadas</div>';
            return;
        }
        el.innerHTML=d.data.map(n=>{
            return`<div class="lrow">
                <div class="lrow-body">
                    <div class="lrow-title">${esc(n.label)} de renovación</div>
                    <div class="lrow-meta">
                        <span class="ch blue">Renovación</span>
                        ${n.nombre_servicio?`<span style="font-size:11px;color:#78746D">${esc(n.nombre_servicio)}</span>`:''}
                        ${n.vencimiento?`<span style="font-size:11px;color:#78746D">Vence: ${fd(n.vencimiento)}</span>`:''}
                    </div>
                </div>
                <div class="lrow-right">
                    <div class="lrow-date">${fd(n.fecha)}</div>
                </div>
            </div>`;
        }).join('');
    }catch(e){el.innerHTML='<div class="empty-state">Error al cargar</div>';}
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
        const cntEl=document.getElementById('cntDocs');
        cntEl.textContent=items.length;
        cntEl.style.display=items.length?'inline':'none';
        if(!items.length){
            el.innerHTML='<div class="empty-state"><svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Sin documentos</div>';
            return;
        }
        const cc={pdf:'#EF4444',doc:'#3B82F6',docx:'#3B82F6',xls:'#15803D',xlsx:'#15803D',jpg:'#F59E0B',jpeg:'#F59E0B',png:'#8B5CF6',webp:'#8B5CF6'};
        const gc=e=>cc[e]||'#6B7280';
        el.innerHTML=items.map(it=>{
            const c=gc(it.ext),bg=c+'18';
            const ico=it.isImg
                ?`<img src="${esc(it.url)}" style="width:34px;height:34px;object-fit:cover;border-radius:7px" onerror="this.style.display='none'">`
                :`<svg width="16" height="16" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`;
            return`<a class="drow" href="${esc(it.url)}" target="_blank" rel="noopener">
                <div class="drow-ico" style="background:${bg}">${ico}</div>
                <div class="drow-info">
                    <div class="drow-name">${esc(it.name)}</div>
                    <div class="drow-meta"><span class="drow-ext" style="color:${c}">${it.ext.toUpperCase()}</span> · ${fd(it.date)}</div>
                </div>
                <svg width="14" height="14" fill="none" stroke="#C0BBB3" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            </a>`;
        }).join('');
    }catch(e){el.innerHTML='<div class="empty-state">Error al cargar</div>';}
}

// ── Accesos ────────────────────────────────────────────────
async function loadAccesos(){
    const el=document.getElementById('listAccesos');
    try{
        const d=await fetch('api/portal_data.php?action=accesos').then(r=>r.json());
        if(revocado(d))return;
        const cnt=d.data?d.data.length:0;
        const cntEl=document.getElementById('cntAccesos');
        cntEl.textContent=cnt;
        cntEl.style.display=cnt?'inline':'none';
        if(!cnt){
            el.innerHTML='<div class="empty-state"><svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>Sin accesos registrados</div>';
            return;
        }
        el.innerHTML='<div class="acc-grid">'+d.data.map((a,i)=>{
            const pid='apwd'+i;
            const hasEmail=a.correo&&a.correo.trim();
            const hasPwd=a.clave&&a.clave.trim();
            return`<div class="acc-card">
                <div class="acc-title">
                    <div class="acc-ico"><svg width="13" height="13" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg></div>
                    ${esc(a.nombre)}
                </div>
                ${hasEmail?`<div class="acc-row">
                    <span class="acc-lbl">Usuario</span>
                    <span class="acc-val" id="ae${i}">${esc(a.correo)}</span>
                    <button class="acc-btn" title="Copiar usuario" onclick="cpTxt(${JSON.stringify(a.correo)},'ae${i}')"><svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
                </div>`:''}
                ${hasPwd?`<div class="acc-row">
                    <span class="acc-lbl">Clave</span>
                    <span class="acc-val hidden-pwd" id="${pid}" data-raw="${esc(a.clave)}" data-vis="0">••••••••</span>
                    <button class="acc-btn" title="Mostrar/ocultar" onclick="togPwd('${pid}')"><svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                    <button class="acc-btn" title="Copiar clave" onclick="cpTxt(${JSON.stringify(a.clave)},'${pid}')"><svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
                </div>`:''}
                <div class="acc-date">Agregado ${fd(a.created_at)}</div>
            </div>`;
        }).join('')+'</div>';
    }catch(e){el.innerHTML='<div class="empty-state">Error al cargar accesos</div>';}
}
function togPwd(id){
    const el=document.getElementById(id);
    if(!el)return;
    const vis=el.dataset.vis==='1';
    el.dataset.vis=vis?'0':'1';
    el.textContent=vis?'••••••••':el.dataset.raw;
    el.classList.toggle('hidden-pwd',vis);
}
function cpTxt(txt,ref){
    navigator.clipboard.writeText(txt).then(()=>toast('Copiado','ok')).catch(()=>{
        const ta=document.createElement('textarea');
        ta.value=txt;ta.style.position='fixed';ta.style.opacity='0';
        document.body.appendChild(ta);ta.select();document.execCommand('copy');
        document.body.removeChild(ta);toast('Copiado','ok');
    });
}

// ── Perfil ─────────────────────────────────────────────────
async function loadPerfil(){
    const el=document.getElementById('perfilContent');
    try{
        const d=await fetch('api/portal_data.php?action=perfil').then(r=>r.json());
        if(revocado(d))return;
        if(!d.success||!d.data)throw new Error();
        const p=d.data;
        const pf=(lbl,val,full=false)=>`<div class="pf-field${full?' full':''}"><div class="pf-label">${lbl}</div><div class="pf-value ${!val?'empty':''}">${val?esc(val):'No registrado'}</div></div>`;
        const acceso=p.has_custom_password
            ?'<span style="color:#15803D;font-weight:700;font-size:12.5px">✓ Contraseña personalizada activa</span>'
            :'<span style="color:#78746D;font-style:italic;font-size:12.5px">Contraseña por defecto (NIT)</span>';
        el.innerHTML=`
        <div class="pf-2col">
            ${pf('Empresa / Razón social',p.nombre_comercial)}
            ${pf('NIT / Identificación',p.nit_cedula)}
            ${pf('Persona de contacto',p.persona_contacto)}
            ${pf('Teléfono',p.telefono)}
            ${pf('Correo de contacto',p.email_contacto,true)}
            ${pf('Correo de facturación',p.email_facturacion,true)}
            ${pf('Dirección',p.direccion,true)}
            <div class="pf-field full"><div class="pf-label">Sitio web</div><div class="pf-value ${!p.sitio_web?'empty':''}">${p.sitio_web?`<a href="${/^https?:\/\//i.test(p.sitio_web)?esc(p.sitio_web):'https://'+esc(p.sitio_web)}" target="_blank" rel="noopener" style="color:#0d9488;text-decoration:none;display:inline-flex;align-items:center;gap:5px;font-weight:600" onmouseenter="this.style.textDecoration='underline'" onmouseleave="this.style.textDecoration='none'"><svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>${esc(p.sitio_web)}</a>`:'No registrado'}</div></div>
            <div class="pf-field full"><div class="pf-label">Acceso al portal</div><div class="pf-value">${acceso}</div></div>
        </div>
        <div class="pwd-section">
            <div class="pwd-section-title">Cambiar contraseña de acceso</div>
            <div class="pwd-fields">
                <input class="inp" type="password" id="pwdA" placeholder="Contraseña actual" autocomplete="current-password">
                <input class="inp" type="password" id="pwdN" placeholder="Nueva contraseña (mín. 6 car.)" autocomplete="new-password">
                <input class="inp" type="password" id="pwdC" placeholder="Confirmar nueva" autocomplete="new-password">
                <button class="btn-save" onclick="cambiarPwd()">Guardar</button>
            </div>
        </div>`;
    }catch(e){el.innerHTML='<div class="empty-state">Error al cargar perfil</div>';}
}

async function cambiarPwd(){
    const btn=document.querySelector('.btn-save');
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
// Pagos: carga inicial (tab activo por defecto)
_ld.pagos=true; loadPagos();
</script>
</body>
</html>
