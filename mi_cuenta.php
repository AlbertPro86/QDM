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
body{font-family:'Inter',sans-serif;background:#EDEAE3;color:#252422;min-height:100vh}

/* ── Nav ─────────────────────────────────────────── */
.nav{background:#252422;height:48px;display:flex;align-items:center;padding:0 18px;gap:10px;position:sticky;top:0;z-index:100}
.nav-logo img{height:20px;display:block}
.nav-sep{width:1px;height:14px;background:rgba(255,255,255,.1)}
.nav-tag{font-size:9.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#C6F24E;background:rgba(198,242,78,.1);padding:2px 7px;border-radius:100px}
.nav-sp{flex:1}
.nav-av{width:26px;height:26px;border-radius:50%;background:#C6F24E;color:#252422;font-size:9px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.nav-nm{font-size:12px;font-weight:600;color:rgba(255,255,255,.7);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nav-out{display:flex;align-items:center;gap:4px;padding:4px 10px;background:transparent;border:1px solid rgba(255,255,255,.12);border-radius:5px;font-size:11px;font-weight:500;color:rgba(255,255,255,.4);cursor:pointer;font-family:inherit;transition:all .15s}
.nav-out:hover{border-color:rgba(255,255,255,.25);color:rgba(255,255,255,.75)}

/* ── Hero ─────────────────────────────────────────── */
.hero{background:#252422;padding:18px 18px 22px}
.hero-inner{max-width:900px;margin:0 auto;display:flex;align-items:flex-start;justify-content:space-between;gap:24px}
.hero-id{flex:1;min-width:0}
.hero-label{font-size:10px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:5px}
.hero-company{font-size:22px;font-weight:900;color:#fff;line-height:1.1;letter-spacing:-.02em}
.hero-person{font-size:12px;color:rgba(255,255,255,.5);margin-top:3px;display:none}
.hero-person span{color:rgba(255,255,255,.75);font-weight:600}
.hero-nit{font-size:11px;color:rgba(255,255,255,.28);margin-top:2px}
.hero-pills{display:flex;gap:5px;flex-wrap:wrap;margin-top:10px}
.hpill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:100px;font-size:11px;font-weight:600}
.hpill.ok    {background:rgba(45,143,90,.2);color:#6EE7A8}
.hpill.warn  {background:rgba(180,122,30,.2);color:#FCD34D}
.hpill.danger{background:rgba(176,56,47,.2);color:#FCA5A5}
.hpill.grey  {background:rgba(255,255,255,.07);color:rgba(255,255,255,.4)}
.hero-val-block{text-align:right;flex-shrink:0}
.hero-val-lbl{font-size:10px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.28);margin-bottom:4px}
.hero-val{font-size:36px;font-weight:900;color:#fff;line-height:1;letter-spacing:-.03em}
.hero-val-cop{font-size:11px;font-weight:700;color:rgba(255,255,255,.3);margin-top:3px;letter-spacing:.04em}
.hero-renov{margin-top:8px;font-size:11px;color:rgba(255,255,255,.38)}
.hero-renov strong{color:rgba(255,255,255,.6)}

/* ── Wrap ─────────────────────────────────────────── */
.wrap{max-width:900px;margin:0 auto;padding:14px 14px 60px}

/* ── Alert ────────────────────────────────────────── */
.alert-bar{display:none;align-items:center;gap:8px;padding:9px 13px;border-radius:8px;margin-bottom:12px;font-size:12px;font-weight:500;line-height:1.4}
.alert-bar.ok    {background:#E3F1E8;color:#1A6B41}
.alert-bar.warn  {background:#FEF3C7;color:#92400E}
.alert-bar.danger{background:#FEE2E2;color:#991B1B}

/* ── Stats strip ──────────────────────────────────── */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px}
.stat{background:#fff;border:1px solid #E4E1D9;border-radius:10px;padding:11px 13px}
.stat-n{font-size:18px;font-weight:900;color:#252422;letter-spacing:-.02em;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.stat-n.sm{font-size:13px;margin-top:2px}
.stat-lbl{font-size:10px;color:#8A867C;margin-top:3px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ── Main 2-col grid ──────────────────────────────── */
.mgrid{display:grid;grid-template-columns:1fr 310px;gap:12px;align-items:start}
.col-main{}
.col-side{}

/* ── Block (bloque blanco) ────────────────────────── */
.blk{background:#fff;border:1px solid #E4E1D9;border-radius:12px;overflow:hidden;margin-bottom:12px}
.blk:last-child{margin-bottom:0}
.blk-hdr{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-bottom:1px solid #F0EDE6}
.blk-title{font-size:11.5px;font-weight:700;color:#252422}
.blk-badge{font-size:10.5px;font-weight:800;padding:1px 8px;border-radius:100px;background:#F0EDE6;color:#57544D}
.blk-empty{padding:24px 14px;text-align:center;color:#8A867C;font-size:12px}
.blk-empty svg{display:block;margin:0 auto 8px;opacity:.2}
.blk-more{padding:9px 14px;border-top:1px solid #F0EDE6;text-align:center}
.blk-more button{background:none;border:none;font-size:12px;font-weight:600;color:#57544D;cursor:pointer;font-family:inherit;transition:color .15s}
.blk-more button:hover{color:#252422}

/* ── List row ─────────────────────────────────────── */
.lrow{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid #F5F3EF;transition:background .1s}
.lrow:last-child{border-bottom:none}
.lrow:hover{background:#FAFAF8}
.lrow-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:4px}
.lrow-body{flex:1;min-width:0}
.lrow-name{font-size:13px;font-weight:600;color:#252422;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lrow-meta{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-top:3px}
.lrow-right{flex-shrink:0;text-align:right;min-width:72px}
.lrow-val{font-size:13px;font-weight:800;color:#252422;white-space:nowrap}
.lrow-sub{font-size:10.5px;color:#8A867C;margin-top:1px;text-align:right}

/* ── Chips pequeños ───────────────────────────────── */
.ch{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:100px;font-size:10.5px;font-weight:600;white-space:nowrap}
.ch.ok    {background:#E3F1E8;color:#1A6B41}
.ch.warn  {background:#FEF3C7;color:#B45309}
.ch.danger{background:#FEE2E2;color:#991B1B}
.ch.grey  {background:#F0EDE6;color:#8A867C}
.ch.blue  {background:#EEF2FB;color:#3F5E9E}
.ch.alta  {background:#FEE2E2;color:#991B1B}
.ch.media {background:#FEF3C7;color:#B45309}
.ch.baja  {background:#E3F1E8;color:#1A6B41}
.ch.pagado    {background:#E3F1E8;color:#1A6B41}
.ch.pendiente {background:#FEF3C7;color:#B45309}
.ch.vencido   {background:#FEE2E2;color:#991B1B}
.ch.completado{background:#E3F1E8;color:#1A6B41}
.ch.en_progreso,.ch.revision{background:#EEF2FB;color:#3F5E9E}
.ch.cancelado {background:#FEE2E2;color:#991B1B}

/* ── Perfil ───────────────────────────────────────── */
.pf-grid{display:grid;grid-template-columns:1fr 1fr}
.pf-row{padding:9px 14px;border-bottom:1px solid #F5F3EF}
.pf-row:nth-child(odd){border-right:1px solid #F5F3EF}
.pf-row.full{grid-column:1/-1;border-right:none}
.pf-lbl{font-size:9.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#8A867C;margin-bottom:3px}
.pf-val{font-size:12.5px;font-weight:600;color:#252422}
.pf-val.e{color:#C4C0BA;font-weight:400;font-style:italic}
.pwd-hdr{padding:11px 14px;border-top:1px solid #F0EDE6;border-bottom:1px solid #F0EDE6;font-size:11px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.05em}
.pwd-wrap{padding:12px 14px;display:flex;gap:7px;flex-wrap:wrap}
.inp{padding:7px 10px;border:1.5px solid #E4E1D9;border-radius:6px;font-size:12px;font-family:inherit;background:#FAFAF7;color:#252422;outline:none;transition:border-color .15s;flex:1;min-width:110px}
.inp:focus{border-color:#252422;background:#fff}
.btn-sm{padding:7px 14px;background:#252422;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;font-family:inherit;cursor:pointer;white-space:nowrap;flex-shrink:0;transition:opacity .15s}
.btn-sm:hover{opacity:.8}
.btn-sm:disabled{opacity:.4;cursor:not-allowed}

/* ── Documentos compact row ───────────────────────── */
.drow{display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid #F5F3EF;text-decoration:none;color:inherit;transition:background .1s}
.drow:last-child{border-bottom:none}
.drow:hover{background:#FAFAF8}
.drow-ext{font-size:8.5px;font-weight:800;padding:3px 6px;border-radius:4px;letter-spacing:.03em;text-transform:uppercase;flex-shrink:0;min-width:30px;text-align:center}
.drow-name{flex:1;font-size:12.5px;font-weight:600;color:#252422;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.drow-date{font-size:10.5px;color:#8A867C;flex-shrink:0}

/* ── Loading / Empty ──────────────────────────────── */
.loading{padding:22px;text-align:center;color:#8A867C;font-size:12px}

/* ── Toast ────────────────────────────────────────── */
#toast{position:fixed;bottom:16px;right:16px;z-index:9999;padding:9px 14px;border-radius:7px;font-size:12px;font-weight:600;color:#fff;background:#252422;box-shadow:0 4px 16px rgba(0,0,0,.15);opacity:0;transform:translateY(5px);transition:all .2s;pointer-events:none}
#toast.show{opacity:1;transform:none}
#toast.ok{background:#1A6B41}
#toast.err{background:#991B1B}

/* ── Revocado ─────────────────────────────────────── */
#revocado{display:none;position:fixed;inset:0;z-index:9000;background:#fff;flex-direction:column;align-items:center;justify-content:center;gap:12px;text-align:center;padding:24px}
#revocado.show{display:flex}

/* ── Responsive ───────────────────────────────────── */
@media(max-width:820px){
    .mgrid{grid-template-columns:1fr}
    .col-side{display:contents}
    /* reordenar columna lateral inline */
}
@media(max-width:600px){
    .hero-inner{flex-direction:column;gap:0}
    .hero-val-block{width:100%;text-align:left;padding-top:12px;margin-top:12px;border-top:1px solid rgba(255,255,255,.08)}
    .hero-company{font-size:19px}
    .hero-val{font-size:28px}
    .stats{grid-template-columns:repeat(2,1fr)}
    .wrap{padding:12px 10px 50px}
}
@media(max-width:420px){
    .nav-nm{display:none}
    .hero{padding:14px 12px 18px}
    .lrow-name{font-size:12.5px}
    .lrow-val{font-size:12.5px}
    .pf-grid{grid-template-columns:1fr}
    .pf-row:nth-child(odd){border-right:none}
    .pf-row.full{grid-column:1}
    .pwd-wrap{flex-direction:column}
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
        <div class="hero-id">
            <div class="hero-label">Portal de cliente</div>
            <div class="hero-company" id="heroNombre"><?= $nombre ?></div>
            <div class="hero-person" id="heroContact">Encargado: <span id="heroContactName"></span></div>
            <div class="hero-nit" id="heroNit"></div>
            <div class="hero-pills" id="heroPills">
                <span class="hpill grey">Cargando…</span>
            </div>
        </div>
        <div class="hero-val-block">
            <div class="hero-val-lbl">Valor suscripción</div>
            <div class="hero-val" id="heroValor">—</div>
            <div class="hero-val-cop">COP</div>
            <div class="hero-renov" id="heroRenov"></div>
        </div>
    </div>
</div>

<!-- Contenido -->
<div class="wrap">

    <!-- Alert -->
    <div class="alert-bar" id="alertBar">
        <span id="alertIco" style="font-size:14px;flex-shrink:0"></span>
        <span id="alertTxt"></span>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat"><div class="stat-n" id="stActivos">—</div><div class="stat-lbl">Servicios activos</div></div>
        <div class="stat"><div class="stat-n" id="stValor" style="font-size:14px;margin-top:1px">—</div><div class="stat-lbl">Suscripción</div></div>
        <div class="stat"><div class="stat-n" id="stPagado" style="font-size:14px;margin-top:1px">—</div><div class="stat-lbl">Total pagado</div></div>
        <div class="stat"><div class="stat-n" id="stRenov" style="font-size:13px;margin-top:1px">—</div><div class="stat-lbl">Próx. renovación</div></div>
    </div>

    <!-- Grid principal -->
    <div class="mgrid">

        <!-- Columna principal -->
        <div class="col-main">

            <!-- Servicios -->
            <div class="blk" id="blkSvcs">
                <div class="blk-hdr">
                    <span class="blk-title">Mis servicios</span>
                    <span class="blk-badge" id="cntSvcs"></span>
                </div>
                <div id="listSvcs"><div class="loading">Cargando…</div></div>
            </div>

            <!-- Pagos -->
            <div class="blk">
                <div class="blk-hdr">
                    <span class="blk-title">Historial de pagos</span>
                    <span class="blk-badge" id="cntPagos"></span>
                </div>
                <div id="listPagos"><div class="loading">Cargando…</div></div>
                <div class="blk-more" id="morePagos" style="display:none">
                    <button onclick="loadMorePagos()">Ver más pagos →</button>
                </div>
            </div>

        </div><!-- /col-main -->

        <!-- Columna lateral -->
        <div class="col-side">

            <!-- Tareas -->
            <div class="blk">
                <div class="blk-hdr">
                    <span class="blk-title">Tareas</span>
                    <span class="blk-badge" id="cntTareas"></span>
                </div>
                <div id="listTareas"><div class="loading">Cargando…</div></div>
            </div>

            <!-- Mi Perfil -->
            <div class="blk">
                <div class="blk-hdr"><span class="blk-title">Mi perfil</span></div>
                <div id="perfilContent"><div class="loading">Cargando…</div></div>
            </div>

            <!-- Documentos (se muestra solo si hay docs) -->
            <div class="blk" id="blkDocs" style="display:none">
                <div class="blk-hdr">
                    <span class="blk-title">Documentos</span>
                    <span class="blk-badge" id="cntDocs"></span>
                </div>
                <div id="listDocs"></div>
            </div>

            <!-- Notificaciones (se muestra solo si hay) -->
            <div class="blk" id="blkNotif" style="display:none">
                <div class="blk-hdr">
                    <span class="blk-title">Notificaciones enviadas</span>
                    <span class="blk-badge" id="cntNotif"></span>
                </div>
                <div id="listNotif"></div>
            </div>

        </div><!-- /col-side -->

    </div><!-- /mgrid -->

</div><!-- /wrap -->

<div id="revocado">
    <svg width="38" height="38" fill="none" stroke="#991B1B" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div style="font-size:16px;font-weight:800">Acceso desactivado</div>
    <div style="font-size:12.5px;color:#57544D;max-width:300px">Tu acceso fue desactivado. Comunícate con QUANTUN Digital.</div>
    <a href="portal_cliente.php" style="margin-top:4px;padding:8px 18px;background:#252422;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;font-size:12.5px">Volver al inicio</a>
</div>
<div id="toast"></div>

<script>
// ── Utils ──────────────────────────────────────────
const M=['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
function fd(d){if(!d)return'—';const s=d.split('T')[0].split('-');return`${+s[2]} ${M[+s[1]]} ${s[0]}`;}
function fn(n){return'$ '+Number(n).toLocaleString('es-CO',{maximumFractionDigits:0});}
function fnShort(n){const v=Number(n);if(v>=1000000)return'$'+(v/1000000).toFixed(1).replace('.0','')+'M';if(v>=1000)return'$'+(v/1000).toFixed(0)+'k';return fn(v);}
function ff(f){const m={mensual:'Mensual',anual:'Anual',trimestral:'Trimestral',semestral:'Semestral',mes:'Mensual','año':'Anual',trimestre:'Trimestral',semestre:'Semestral',unico:'Único'};return m[(f||'').toLowerCase()]||f||'—';}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function toast(msg,t=''){const el=document.getElementById('toast');el.textContent=msg;el.className='show '+t;clearTimeout(el._t);el._t=setTimeout(()=>el.className='',3200);}
function revocado(d){if(d&&d.revocado){document.getElementById('revocado').classList.add('show');return true;}return false;}
function emptyBlk(msg,icon=''){return`<div class="blk-empty">${icon?`<svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2">${icon}</svg>`:''}${esc(msg)}</div>`;}

// ── Logout ─────────────────────────────────────────
async function doLogout(){
    await fetch('api/portal_auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
    location.href='portal_cliente.php';
}

// ── Hero + Stats ───────────────────────────────────
async function loadHero(){
    try{
        const[sd,pd]=await Promise.all([
            fetch('api/portal_data.php?action=stats').then(r=>r.json()),
            fetch('api/portal_data.php?action=perfil').then(r=>r.json()),
        ]);
        if(revocado(sd)||revocado(pd))return;

        // Perfil
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

        // Valor hero
        const val=sd.valor_suscripcion||0;
        document.getElementById('heroValor').textContent=val>0?fn(val).replace('$ ',''):'0';

        // Renovación hero
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            let txt='';
            if(dias<0) txt=`Vencida hace ${Math.abs(dias)} días`;
            else if(dias===0) txt='Vence hoy';
            else txt=`Renueva <strong>${fd(sd.proxima_renovacion)}</strong> · en ${dias} días`;
            document.getElementById('heroRenov').innerHTML=txt;
        }

        // Pills
        const pills=[];
        if(sd.servicios_activos>0)
            pills.push(`<span class="hpill ok"><svg width="7" height="7" fill="#6EE7A8" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>${sd.servicios_activos} activo${sd.servicios_activos!==1?'s':''}</span>`);
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            let cls='grey',txt='';
            if(dias<0){cls='danger';txt='Vencido';}
            else if(dias<=7){cls='danger';txt=`Vence en ${dias}d`;}
            else if(dias<=30){cls='warn';txt=`${dias}d para renovar`;}
            else{cls='ok';txt='Al día';}
            pills.push(`<span class="hpill ${cls}"><svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>${txt}</span>`);
        }
        if(sd.total_pendiente>0)
            pills.push(`<span class="hpill warn">${fn(sd.total_pendiente)} por pagar</span>`);
        document.getElementById('heroPills').innerHTML=pills.join('')||'<span class="hpill grey">Sin servicios activos</span>';

        // Stats strip
        document.getElementById('stActivos').textContent=sd.servicios_activos||'0';
        document.getElementById('stValor').textContent=val>0?fnShort(val):'$ 0';
        document.getElementById('stPagado').textContent=sd.total_pagado>0?fnShort(sd.total_pagado):'$ 0';
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            const el=document.getElementById('stRenov');
            el.textContent=fd(sd.proxima_renovacion);
            el.style.color=dias<0?'#991B1B':dias<=7?'#B45309':'#252422';
            el.nextElementSibling.textContent=dias<0?'Vencida':dias===0?'Hoy':dias<=30?`En ${dias} días`:'Próx. renovación';
        }else{
            document.getElementById('stRenov').textContent='—';
            document.getElementById('stRenov').nextElementSibling.textContent='Próx. renovación';
        }

        // Alert bar
        if(sd.proxima_renovacion){
            const dias=sd.dias_renovacion;
            if(dias<=30){
                const ab=document.getElementById('alertBar');
                document.getElementById('alertIco').textContent=dias<0?'⚠️':dias<=7?'🔔':'📅';
                document.getElementById('alertTxt').innerHTML=dias<0
                    ?`<strong>Suscripción vencida hace ${Math.abs(dias)} días.</strong> Comunícate con QUANTUN Digital.`
                    :dias<=7
                    ?`<strong>Vence en ${dias} día${dias!==1?'s':''}.</strong> Realiza tu pago para evitar la suspensión.`
                    :`Próxima renovación el <strong>${fd(sd.proxima_renovacion)}</strong>, en ${dias} días.`;
                ab.className='alert-bar '+(dias<0||dias<=7?'danger':'warn');
                ab.style.display='flex';
            }
        }
    }catch(e){}
}

// ── Servicios ──────────────────────────────────────
async function loadSvcs(){
    const el=document.getElementById('listSvcs');
    try{
        const d=await fetch('api/portal_data.php?action=servicios').then(r=>r.json());
        if(revocado(d))return;
        if(!d.success||!d.data.length){
            document.getElementById('blkSvcs').style.display='none';
            return;
        }
        document.getElementById('cntSvcs').textContent=d.data.length;
        const stDot={activo:'#1A6B41',suspendido:'#B45309',cancelado:'#991B1B'};
        el.innerHTML=d.data.map(sv=>{
            const val=Math.max(0,parseFloat(sv.valor_suscripcion)||0);
            const dias=parseInt(sv.dias_para_vencimiento);
            const esUnico=(sv.frecuencia||'').toLowerCase()==='unico';
            let rCls='grey',rTxt='—';
            if(sv.estado==='activo'&&sv.fecha_vencimiento&&!esUnico){
                if(dias<0){rCls='danger';rTxt=`Venció hace ${Math.abs(dias)}d`;}
                else if(dias<=7){rCls='danger';rTxt=`Vence en ${dias}d`;}
                else if(dias<=30){rCls='warn';rTxt=`Vence ${fd(sv.fecha_vencimiento)}`;}
                else{rCls='ok';rTxt=`Vigente hasta ${fd(sv.fecha_vencimiento)}`;}
            }else if(esUnico){rCls='grey';rTxt='Pago único';}
            return`<div class="lrow">
                <div class="lrow-dot" style="background:${stDot[sv.estado]||'#8A867C'}"></div>
                <div class="lrow-body">
                    <div class="lrow-name">${esc(sv.nombre_servicio)}</div>
                    <div class="lrow-meta">
                        <span class="ch grey">${esc(ff(sv.frecuencia))}</span>
                        <span class="ch ${rCls}">${esc(rTxt)}</span>
                    </div>
                </div>
                <div class="lrow-right">
                    ${val>0?`<div class="lrow-val">${fn(val)}</div><div class="lrow-sub">COP</div>`:''}
                </div>
            </div>`;
        }).join('');
    }catch(e){el.innerHTML=emptyBlk('Error al cargar');}
}

// ── Pagos ──────────────────────────────────────────
let _pOff=0,_pTotal=0;
async function loadPagos(append=false){
    const el=document.getElementById('listPagos');
    if(!append)el.innerHTML='<div class="loading">Cargando…</div>';
    try{
        const d=await fetch(`api/portal_data.php?action=historial&limite=15&offset=${_pOff}`).then(r=>r.json());
        if(revocado(d))return;
        _pTotal=d.total||0;
        document.getElementById('cntPagos').textContent=_pTotal;
        if(!d.data.length&&!append){
            el.innerHTML=emptyBlk('Sin registros de pago','<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>');
            return;
        }
        const estL={pagado:'Pagado',pendiente:'Pendiente',vencido:'Vencido'};
        const rows=d.data.map(tx=>{
            const fch=tx.fecha_pago||tx.fecha_vencimiento||tx.created_at;
            const srv=esc(tx.servicio_nombre||tx.titulo||tx.concepto||'');
            const tit=esc(tx.titulo||tx.concepto||'');
            return`<div class="lrow">
                <div class="lrow-body">
                    <div class="lrow-name">${tit}</div>
                    <div class="lrow-meta">
                        <span class="ch ${esc(tx.estado)}">${estL[tx.estado]||tx.estado}</span>
                        ${srv&&srv!==tit?`<span style="font-size:10.5px;color:#8A867C">${srv}</span>`:''}
                        <span style="font-size:10.5px;color:#8A867C">${fd(fch)}</span>
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
    }catch(e){if(!append)el.innerHTML=emptyBlk('Error al cargar');}
}
function loadMorePagos(){loadPagos(true);}

// ── Tareas ─────────────────────────────────────────
async function loadTareas(){
    const el=document.getElementById('listTareas');
    try{
        const d=await fetch('api/portal_data.php?action=tareas').then(r=>r.json());
        if(revocado(d))return;
        const cnt=d.data?d.data.length:0;
        document.getElementById('cntTareas').textContent=cnt;
        if(!cnt){
            el.innerHTML=emptyBlk('Sin tareas asignadas','<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>');
            return;
        }
        const estL={pendiente:'Pendiente',en_progreso:'En progreso',revision:'Revisión',completado:'Completado',cancelado:'Cancelado'};
        const estC={pendiente:'warn',en_progreso:'en_progreso',revision:'revision',completado:'completado',cancelado:'cancelado'};
        const prioC={alta:'#EF4444',media:'#F59E0B',baja:'#10B981'};
        el.innerHTML=d.data.map(t=>{
            const pc=prioC[t.prioridad]||'#8A867C';
            const meta=[];
            if(t.responsable) meta.push(`<span style="font-size:10.5px;color:#8A867C">${esc(t.responsable)}</span>`);
            if(t.fecha_limite) meta.push(`<span style="font-size:10.5px;color:#8A867C">Límite: ${fd(t.fecha_limite)}</span>`);
            return`<div class="lrow">
                <div class="lrow-dot" style="background:${pc}"></div>
                <div class="lrow-body">
                    <div class="lrow-name">${esc(t.titulo)}</div>
                    <div class="lrow-meta">
                        <span class="ch ${estC[t.estado]||'grey'}">${estL[t.estado]||t.estado}</span>
                        ${meta.join('')}
                    </div>
                </div>
            </div>`;
        }).join('');
    }catch(e){el.innerHTML=emptyBlk('Error al cargar');}
}

// ── Documentos ─────────────────────────────────────
async function loadDocs(){
    try{
        const d=await fetch('api/portal_data.php?action=documentos').then(r=>r.json());
        if(revocado(d))return;
        const items=[];
        (d.archivos||[]).forEach(a=>{
            const ext=((a.archivo_url||'').split('.').pop()||'').toLowerCase();
            items.push({url:a.archivo_url,name:a.nombre_archivo||'Archivo',date:a.created_at,ext});
        });
        (d.tx_docs||[]).forEach(tx=>{
            ['factura_path','documento_path','imagen_path'].forEach(k=>{
                if(tx[k]){const ext=(tx[k].split('.').pop()||'').toLowerCase();items.push({url:tx[k],name:tx.nombre_archivo||'Documento',date:tx.created_at,ext});}
            });
        });
        document.getElementById('cntDocs').textContent=items.length;
        if(!items.length)return;
        const cc={pdf:'#EF4444',doc:'#3B82F6',docx:'#3B82F6',xls:'#10B981',xlsx:'#10B981',jpg:'#F59E0B',jpeg:'#F59E0B',png:'#8B5CF6',webp:'#8B5CF6'};
        const gc=e=>cc[e]||'#6B7280';
        document.getElementById('listDocs').innerHTML=items.map(it=>{
            const c=gc(it.ext),bg=c+'18';
            return`<a class="drow" href="${esc(it.url)}" target="_blank" rel="noopener">
                <span class="drow-ext" style="background:${bg};color:${c}">${it.ext.toUpperCase()||'—'}</span>
                <span class="drow-name">${esc(it.name)}</span>
                <span class="drow-date">${fd(it.date)}</span>
            </a>`;
        }).join('');
        document.getElementById('blkDocs').style.display='block';
    }catch(e){}
}

// ── Notificaciones ─────────────────────────────────
async function loadNotif(){
    try{
        const d=await fetch('api/portal_data.php?action=notificaciones').then(r=>r.json());
        if(revocado(d))return;
        const cnt=d.data?d.data.length:0;
        document.getElementById('cntNotif').textContent=cnt;
        if(!cnt)return;
        document.getElementById('listNotif').innerHTML=d.data.map(n=>{
            return`<div class="lrow">
                <div class="lrow-body">
                    <div class="lrow-name">${esc(n.label)} de renovación</div>
                    <div class="lrow-meta">
                        ${n.nombre_servicio?`<span style="font-size:10.5px;color:#8A867C">${esc(n.nombre_servicio)}</span>`:''}
                        ${n.vencimiento?`<span style="font-size:10.5px;color:#8A867C">Vence: ${fd(n.vencimiento)}</span>`:''}
                    </div>
                </div>
                <div class="lrow-right"><div class="lrow-sub" style="font-size:11px">${fd(n.fecha)}</div></div>
            </div>`;
        }).join('');
        document.getElementById('blkNotif').style.display='block';
    }catch(e){}
}

// ── Perfil ─────────────────────────────────────────
async function loadPerfil(){
    const el=document.getElementById('perfilContent');
    try{
        const d=await fetch('api/portal_data.php?action=perfil').then(r=>r.json());
        if(revocado(d))return;
        if(!d.success||!d.data)throw new Error();
        const p=d.data;
        const prow=(lbl,val,full=false)=>`<div class="pf-row${full?' full':''}"><div class="pf-lbl">${lbl}</div><div class="pf-val ${!val?'e':''}">${val?esc(val):'—'}</div></div>`;
        const pwdSt=p.has_custom_password
            ?'<span style="color:#1A6B41;font-size:11px;font-weight:700">✓ Contraseña personalizada</span>'
            :'<span style="color:#8A867C;font-size:11px;font-style:italic">Usando NIT por defecto</span>';
        el.innerHTML=`
        <div class="pf-grid">
            ${prow('Empresa / Razón social',p.nombre_comercial)}
            ${prow('NIT / Identificación',p.nit_cedula)}
            ${prow('Persona de contacto',p.persona_contacto)}
            ${prow('Teléfono',p.telefono)}
            ${prow('Correo de contacto',p.email_contacto,true)}
            ${prow('Correo facturación',p.email_facturacion,true)}
            ${prow('Dirección',p.direccion,true)}
            <div class="pf-row full"><div class="pf-lbl">Acceso al portal</div><div class="pf-val">${pwdSt}</div></div>
        </div>
        <div class="pwd-hdr">Cambiar contraseña</div>
        <div class="pwd-wrap">
            <input class="inp" type="password" id="pwdA" placeholder="Contraseña actual" autocomplete="current-password">
            <input class="inp" type="password" id="pwdN" placeholder="Nueva (mín. 6 caracteres)" autocomplete="new-password">
            <input class="inp" type="password" id="pwdC" placeholder="Confirmar nueva" autocomplete="new-password">
            <button class="btn-sm" onclick="cambiarPwd()">Guardar</button>
        </div>`;
    }catch(e){el.innerHTML=emptyBlk('Error al cargar perfil');}
}

async function cambiarPwd(){
    const btn=document.querySelector('.btn-sm');
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

// ── Init ───────────────────────────────────────────
loadHero();
loadSvcs();
loadPagos();
loadTareas();
loadDocs();
loadNotif();
loadPerfil();
</script>
</body>
</html>
