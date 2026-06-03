<?php
/**
 * CRM QUANTUN Digital — Portal Cliente · Mi Cuenta
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Guard: requiere sesión de portal
if (empty($_SESSION['portal_logged_in'])) {
    header('Location: portal_cliente.php');
    exit;
}

$clienteNombre = htmlspecialchars($_SESSION['portal_nombre'] ?? 'Cliente');
$iniciales = '';
foreach (array_slice(explode(' ', trim($_SESSION['portal_nombre'] ?? 'C')), 0, 2) as $p) {
    $iniciales .= mb_strtoupper(mb_substr($p, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta | QUANTUN Digital</title>
    <link rel="icon" type="image/png" href="Assets/logo_quantun_digital_negro.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=<?= APP_VERSION ?>">
    <style>
    /* ── Portal Layout ─────────────────────────────── */
    body { background: var(--color-surface); min-height: 100vh; }

    /* Top Bar */
    .pt-topbar {
        position: sticky; top: 0; z-index: 200;
        background: var(--color-primary);
        border-bottom: 1px solid rgba(255,255,255,.06);
        padding: 0 24px;
        height: 54px;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .pt-topbar-logo img { height: 26px; display: block; }
    .pt-topbar-sep {
        width: 1px; height: 20px;
        background: rgba(255,255,255,.12);
    }
    .pt-topbar-badge {
        font-size: 11px; font-weight: 700;
        letter-spacing: .05em; text-transform: uppercase;
        color: var(--q-lima);
        padding: 2px 8px;
        background: rgba(198,242,78,.1);
        border-radius: var(--radius-full);
    }
    .pt-topbar-spacer { flex: 1; }
    .pt-topbar-user {
        display: flex; align-items: center; gap: 9px;
    }
    .pt-topbar-avatar {
        width: 30px; height: 30px;
        border-radius: 50%;
        background: var(--q-lima);
        color: #0E0E0C;
        font-size: 11px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .pt-topbar-name {
        font-size: 13px; font-weight: 600; color: #fff;
        max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .pt-logout-btn {
        display: flex; align-items: center; gap: 5px;
        padding: 5px 12px;
        background: transparent;
        border: 1px solid rgba(255,255,255,.15);
        border-radius: var(--radius-md);
        font-size: 12.5px; font-weight: 500;
        color: rgba(255,255,255,.65);
        cursor: pointer; font-family: inherit;
        transition: all var(--transition-fast);
    }
    .pt-logout-btn:hover {
        background: rgba(255,255,255,.06);
        color: #fff;
        border-color: rgba(255,255,255,.25);
    }

    /* ── Content ─────────────────────────────────────── */
    .pt-content { max-width: 900px; margin: 0 auto; padding: 28px 20px 60px; }

    /* Renewal Banner */
    .pt-banner {
        border-radius: var(--radius-lg);
        padding: 14px 18px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        font-size: 13.5px;
        font-weight: 500;
    }
    .pt-banner.ok     { background: var(--color-success-bg); color: var(--color-success); }
    .pt-banner.warn   { background: var(--color-warning-bg); color: var(--color-warning); }
    .pt-banner.danger { background: var(--color-danger-bg);  color: var(--color-danger); }
    .pt-banner.info   { background: var(--color-info-bg);    color: var(--color-info); }
    .pt-banner-icon { flex-shrink: 0; }
    .pt-banner strong { font-weight: 700; }

    /* Stats Row */
    .pt-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 28px;
    }
    .pt-stat {
        background: #fff;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 16px 18px;
    }
    .pt-stat-label { font-size: 11.5px; color: var(--color-text-muted); font-weight: 500; margin-bottom: 4px; }
    .pt-stat-val   { font-size: 22px; font-weight: 800; color: var(--color-text); line-height: 1.15; }
    .pt-stat-sub   { font-size: 11px; color: var(--color-text-light); margin-top: 2px; }

    /* Tabs */
    .pt-tabs {
        display: flex; gap: 2px;
        border-bottom: 1.5px solid var(--color-border);
        margin-bottom: 24px;
    }
    .pt-tab {
        padding: 8px 16px;
        font-size: 13.5px; font-weight: 500;
        color: var(--color-text-muted);
        border: none; background: none;
        cursor: pointer; font-family: inherit;
        border-bottom: 2.5px solid transparent;
        margin-bottom: -1.5px;
        transition: all var(--transition-fast);
        display: flex; align-items: center; gap: 6px;
    }
    .pt-tab:hover { color: var(--color-text); }
    .pt-tab.active {
        color: var(--color-text);
        font-weight: 700;
        border-bottom-color: var(--color-text);
    }
    .pt-tab-pane { display: none; }
    .pt-tab-pane.active { display: block; }

    /* Service Cards */
    .pt-services { display: grid; gap: 12px; }
    .pt-svc-card {
        background: #fff;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 18px 20px;
        display: flex; align-items: flex-start; gap: 16px;
    }
    .pt-svc-icon {
        width: 40px; height: 40px;
        border-radius: var(--radius-md);
        background: var(--color-surface-alt);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .pt-svc-body { flex: 1; min-width: 0; }
    .pt-svc-name { font-size: 15px; font-weight: 700; color: var(--color-text); margin-bottom: 3px; }
    .pt-svc-meta { font-size: 12.5px; color: var(--color-text-muted); display: flex; gap: 12px; flex-wrap: wrap; }
    .pt-svc-right { text-align: right; flex-shrink: 0; }
    .pt-svc-price { font-size: 18px; font-weight: 800; color: var(--color-text); }
    .pt-svc-price-sub { font-size: 11px; color: var(--color-text-light); margin-top: 1px; }
    .pt-svc-renewal {
        margin-top: 8px;
        padding: 6px 10px;
        border-radius: var(--radius-sm);
        font-size: 12px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .pt-svc-renewal.ok     { background: var(--color-success-bg); color: var(--color-success); }
    .pt-svc-renewal.warn   { background: var(--color-warning-bg); color: var(--color-warning); }
    .pt-svc-renewal.danger { background: var(--color-danger-bg);  color: var(--color-danger); }
    .pt-svc-renewal.grey   { background: var(--color-surface-alt); color: var(--color-text-light); }

    /* Badge estado */
    .pt-badge {
        display: inline-flex; align-items: center;
        padding: 2px 8px;
        border-radius: var(--radius-full);
        font-size: 11px; font-weight: 700;
    }
    .pt-badge.activo     { background: var(--color-success-bg); color: var(--color-success); }
    .pt-badge.suspendido { background: var(--color-warning-bg); color: var(--color-warning); }
    .pt-badge.cancelado  { background: var(--color-danger-bg);  color: var(--color-danger); }

    /* Historial Table */
    .pt-historial { display: flex; flex-direction: column; gap: 0; }
    .pt-hist-row {
        display: flex; align-items: center; gap: 14px;
        padding: 13px 4px;
        border-bottom: 1px solid var(--color-border-light);
    }
    .pt-hist-row:last-child { border-bottom: none; }
    .pt-hist-icon {
        width: 36px; height: 36px;
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .pt-hist-body { flex: 1; min-width: 0; }
    .pt-hist-title { font-size: 13.5px; font-weight: 600; color: var(--color-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pt-hist-sub   { font-size: 12px; color: var(--color-text-muted); margin-top: 1px; }
    .pt-hist-right { text-align: right; flex-shrink: 0; }
    .pt-hist-amount { font-size: 14px; font-weight: 700; color: var(--color-text); }
    .pt-hist-estado { font-size: 11px; font-weight: 600; margin-top: 2px; }
    .pt-hist-estado.pagado    { color: var(--color-success); }
    .pt-hist-estado.pendiente { color: var(--color-warning); }
    .pt-hist-estado.vencido   { color: var(--color-danger); }

    /* Documentos */
    .pt-docs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
    }
    .pt-doc-card {
        background: #fff;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 14px 14px 12px;
        display: flex; flex-direction: column; gap: 8px;
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none; color: inherit;
    }
    .pt-doc-card:hover { border-color: var(--color-text-muted); box-shadow: var(--shadow-md); }
    .pt-doc-icon {
        width: 36px; height: 36px;
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
    }
    .pt-doc-name { font-size: 12.5px; font-weight: 600; color: var(--color-text); line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .pt-doc-date { font-size: 11px; color: var(--color-text-light); }

    /* Perfil */
    .pt-perfil { background: #fff; border: 1px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden; }
    .pt-perfil-header { background: var(--color-primary); padding: 24px 24px 20px; display: flex; align-items: center; gap: 16px; }
    .pt-perfil-avatar {
        width: 52px; height: 52px; border-radius: 50%;
        background: var(--q-lima); color: #0E0E0C;
        font-size: 18px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .pt-perfil-name  { font-size: 18px; font-weight: 800; color: #fff; }
    .pt-perfil-since { font-size: 12.5px; color: rgba(255,255,255,.5); margin-top: 2px; }
    .pt-perfil-body  { padding: 20px 24px; }
    .pt-perfil-row   { display: flex; padding: 11px 0; border-bottom: 1px solid var(--color-border-light); gap: 16px; }
    .pt-perfil-row:last-child { border-bottom: none; }
    .pt-perfil-lbl   { width: 140px; flex-shrink: 0; font-size: 12.5px; color: var(--color-text-muted); font-weight: 500; padding-top: 1px; }
    .pt-perfil-val   { font-size: 13.5px; color: var(--color-text); font-weight: 600; flex: 1; }
    .pt-perfil-val.empty { color: var(--color-text-light); font-weight: 400; font-style: italic; }

    /* Cambiar contraseña */
    .pt-pwd-form {
        margin-top: 20px;
        padding: 18px 20px;
        background: var(--color-surface-alt);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
    }
    .pt-pwd-title { font-size: 14px; font-weight: 700; margin-bottom: 14px; }
    .pt-pwd-grid  { display: grid; gap: 10px; }
    .pt-input {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid var(--color-border);
        border-radius: var(--radius-md);
        font-size: 13.5px; font-family: inherit;
        background: #fff; color: var(--color-text);
        outline: none;
        transition: border-color var(--transition-fast);
    }
    .pt-input:focus { border-color: var(--color-text); }
    .pt-btn-primary {
        padding: 9px 18px;
        background: var(--color-primary);
        color: #fff;
        border: none; border-radius: var(--radius-md);
        font-size: 13.5px; font-weight: 600;
        font-family: inherit; cursor: pointer;
        transition: opacity var(--transition-fast);
    }
    .pt-btn-primary:hover { opacity: .82; }
    .pt-btn-primary:disabled { opacity: .45; cursor: not-allowed; }

    /* Empty/Loading states */
    .pt-empty {
        text-align: center; padding: 48px 24px;
        color: var(--color-text-light); font-size: 13.5px;
    }
    .pt-empty svg { display: block; margin: 0 auto 12px; opacity: .35; }
    .pt-loading { text-align: center; padding: 40px; color: var(--color-text-light); font-size: 13px; }

    /* Toast */
    #ptToast {
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        padding: 11px 18px;
        border-radius: var(--radius-md);
        font-size: 13.5px; font-weight: 500;
        color: #fff; background: var(--color-text);
        box-shadow: var(--shadow-xl);
        opacity: 0; transform: translateY(8px);
        transition: all .2s ease;
        pointer-events: none;
    }
    #ptToast.show { opacity: 1; transform: translateY(0); }
    #ptToast.success { background: var(--color-success); }
    #ptToast.error   { background: var(--color-danger); }

    /* Responsive */
    @media (max-width: 600px) {
        .pt-content { padding: 16px 12px 48px; }
        .pt-topbar  { padding: 0 14px; }
        .pt-topbar-name { max-width: 110px; }
        .pt-svc-card { flex-direction: column; gap: 12px; }
        .pt-svc-right { text-align: left; }
        .pt-stats { grid-template-columns: 1fr 1fr; }
        .pt-perfil-lbl { width: 110px; }
    }
    </style>
</head>
<body>

<!-- ── Top Bar ─────────────────────────────────────────────────────────────── -->
<div class="pt-topbar">
    <div class="pt-topbar-logo">
        <img src="Assets/logo_quantun_digital_blanco.png" alt="QUANTUN Digital">
    </div>
    <div class="pt-topbar-sep"></div>
    <span class="pt-topbar-badge">Mi Cuenta</span>
    <div class="pt-topbar-spacer"></div>
    <div class="pt-topbar-user">
        <div class="pt-topbar-avatar"><?= htmlspecialchars($iniciales) ?></div>
        <span class="pt-topbar-name"><?= $clienteNombre ?></span>
    </div>
    <button class="pt-logout-btn" onclick="ptLogout()">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        Salir
    </button>
</div>

<!-- ── Content ─────────────────────────────────────────────────────────────── -->
<div class="pt-content">

    <!-- Banner de renovación -->
    <div id="ptBanner" style="display:none"></div>

    <!-- Stats -->
    <div class="pt-stats" id="ptStats">
        <div class="pt-stat">
            <div class="pt-stat-label">Servicios activos</div>
            <div class="pt-stat-val" id="statSvcs">—</div>
        </div>
        <div class="pt-stat">
            <div class="pt-stat-label">Total pagado</div>
            <div class="pt-stat-val" id="statPagado" style="font-size:17px">—</div>
            <div class="pt-stat-sub">COP</div>
        </div>
        <div class="pt-stat">
            <div class="pt-stat-label">Por pagar</div>
            <div class="pt-stat-val" id="statPend" style="font-size:17px">—</div>
            <div class="pt-stat-sub">COP</div>
        </div>
        <div class="pt-stat">
            <div class="pt-stat-label">Próxima renovación</div>
            <div class="pt-stat-val" id="statRen" style="font-size:15px;line-height:1.2">—</div>
            <div class="pt-stat-sub" id="statRenSub"></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="pt-tabs">
        <button class="pt-tab active" onclick="ptTab('servicios',this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            Mis Servicios
        </button>
        <button class="pt-tab" onclick="ptTab('historial',this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Historial
        </button>
        <button class="pt-tab" onclick="ptTab('documentos',this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            Documentos
        </button>
        <button class="pt-tab" onclick="ptTab('perfil',this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Mi Perfil
        </button>
    </div>

    <!-- Tab: Servicios -->
    <div class="pt-tab-pane active" id="pane-servicios">
        <div id="ptSvcList" class="pt-services">
            <div class="pt-loading">Cargando servicios…</div>
        </div>
    </div>

    <!-- Tab: Historial -->
    <div class="pt-tab-pane" id="pane-historial">
        <div id="ptHistList" class="pt-historial">
            <div class="pt-loading">Cargando historial…</div>
        </div>
        <div id="ptHistMore" style="display:none;text-align:center;margin-top:16px">
            <button onclick="ptLoadMore()" class="pt-btn-primary" style="background:transparent;color:var(--color-text);border:1.5px solid var(--color-border)">
                Cargar más
            </button>
        </div>
    </div>

    <!-- Tab: Documentos -->
    <div class="pt-tab-pane" id="pane-documentos">
        <div id="ptDocList">
            <div class="pt-loading">Cargando documentos…</div>
        </div>
    </div>

    <!-- Tab: Perfil -->
    <div class="pt-tab-pane" id="pane-perfil">
        <div id="ptPerfilContent">
            <div class="pt-loading">Cargando perfil…</div>
        </div>
    </div>

</div><!-- /pt-content -->

<!-- Toast -->
<div id="ptToast"></div>

<script>
// ── Helpers ────────────────────────────────────────────────────────────────
function ptFmt(n)   { return '$ ' + Number(n).toLocaleString('es-CO', {maximumFractionDigits:0}); }
function ptDate(d)  {
    if (!d) return '—';
    const [y,m,day] = d.split('T')[0].split('-');
    const meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return `${parseInt(day)} ${meses[parseInt(m)]} ${y}`;
}
function ptFrecLabel(f) {
    const map = {mensual:'Mensual',anual:'Anual',trimestral:'Trimestral',semestral:'Semestral',mes:'Mensual',año:'Anual',trimestre:'Trimestral',semestre:'Semestral',unico:'Único'};
    return map[(f||'').toLowerCase()] || f || '—';
}
function ptEsc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function ptToast(msg, type='') {
    const t = document.getElementById('ptToast');
    t.textContent = msg;
    t.className   = 'show ' + type;
    clearTimeout(t._timer);
    t._timer = setTimeout(() => { t.className = type; }, 3200);
}

// ── Tab Switching ──────────────────────────────────────────────────────────
const _ptLoaded = {};
function ptTab(name, btn) {
    document.querySelectorAll('.pt-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.pt-tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('pane-' + name).classList.add('active');
    if (!_ptLoaded[name]) { _ptLoaded[name] = true; ptLoadTab(name); }
}
function ptLoadTab(name) {
    if (name === 'servicios')  ptLoadServicios();
    if (name === 'historial')  ptLoadHistorial();
    if (name === 'documentos') ptLoadDocumentos();
    if (name === 'perfil')     ptLoadPerfil();
}

// ── Logout ─────────────────────────────────────────────────────────────────
async function ptLogout() {
    await fetch('api/portal_auth.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'logout'})
    });
    window.location.href = 'portal_cliente.php';
}

// ── Stats + Banner ─────────────────────────────────────────────────────────
async function ptLoadStats() {
    try {
        const r = await fetch('api/portal_data.php?action=stats');
        const d = await r.json();
        if (!d.success) return;

        document.getElementById('statSvcs').textContent    = d.servicios_activos;
        document.getElementById('statPagado').textContent  = d.total_pagado > 0 ? ptFmt(d.total_pagado).replace('$ ','') : '0';
        document.getElementById('statPend').textContent    = d.total_pendiente > 0 ? ptFmt(d.total_pendiente).replace('$ ','') : '0';

        if (d.proxima_renovacion) {
            const dias = d.dias_renovacion;
            document.getElementById('statRen').textContent    = ptDate(d.proxima_renovacion);
            document.getElementById('statRenSub').textContent = dias < 0 ? 'Vencida' : dias === 0 ? 'Hoy' : `en ${dias} días`;
        } else {
            document.getElementById('statRen').textContent    = '—';
            document.getElementById('statRenSub').textContent = '';
        }

        // Banner
        if (d.proxima_renovacion) {
            const dias = d.dias_renovacion;
            let cls = 'ok', ico = '', msg = '';
            if (dias < 0) {
                cls = 'danger';
                ico = '⚠️';
                msg = `<strong>Renovación vencida hace ${Math.abs(dias)} días.</strong> Comunícate con QUANTUN Digital para renovar tu servicio.`;
            } else if (dias <= 7) {
                cls = 'danger';
                ico = '🔔';
                msg = `<strong>Tu suscripción vence en ${dias} día${dias!==1?'s':''}.</strong> Realiza tu pago pronto para evitar la suspensión del servicio.`;
            } else if (dias <= 30) {
                cls = 'warn';
                ico = '📅';
                msg = `<strong>Próxima renovación en ${dias} días</strong> (${ptDate(d.proxima_renovacion)}). Ten lista tu forma de pago.`;
            } else {
                cls = 'ok';
                ico = '✅';
                msg = `<strong>Todo al día.</strong> Tu próxima renovación es el ${ptDate(d.proxima_renovacion)} (en ${dias} días).`;
            }
            const banner = document.getElementById('ptBanner');
            banner.innerHTML = `<div class="pt-banner ${cls}"><span class="pt-banner-icon" style="font-size:18px">${ico}</span><span>${msg}</span></div>`;
            banner.style.display = 'block';
        }
    } catch(e) {}
}

// ── Servicios ──────────────────────────────────────────────────────────────
async function ptLoadServicios() {
    const el = document.getElementById('ptSvcList');
    try {
        const r = await fetch('api/portal_data.php?action=servicios');
        const d = await r.json();
        if (!d.success || !d.data.length) {
            el.innerHTML = `<div class="pt-empty">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                No tienes servicios registrados aún.
            </div>`;
            return;
        }

        el.innerHTML = d.data.map(sv => {
            const dias  = parseInt(sv.dias_para_vencimiento);
            const val   = parseFloat(sv.valor_suscripcion) || 0;
            const valFmt = '$ ' + val.toLocaleString('es-CO', {maximumFractionDigits:0});
            const frecLabel = ptFrecLabel(sv.frecuencia);

            let renCls = 'grey', renTxt = '—';
            if (sv.estado === 'activo' && sv.fecha_vencimiento && sv.frecuencia !== 'unico') {
                if (dias < 0)      { renCls='danger'; renTxt=`Vencido hace ${Math.abs(dias)} días`; }
                else if (dias<=7)  { renCls='danger'; renTxt=`Vence en ${dias} día${dias!==1?'s':''}`; }
                else if (dias<=30) { renCls='warn';   renTxt=`Vence el ${ptDate(sv.fecha_vencimiento)}`; }
                else               { renCls='ok';     renTxt=`Vigente hasta ${ptDate(sv.fecha_vencimiento)}`; }
            } else if (sv.frecuencia === 'unico') {
                renCls = 'grey'; renTxt = 'Pago único';
            } else if (sv.estado !== 'activo') {
                renCls = 'grey'; renTxt = sv.estado.charAt(0).toUpperCase() + sv.estado.slice(1);
            }

            return `<div class="pt-svc-card">
                <div class="pt-svc-icon">
                    <svg width="20" height="20" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div class="pt-svc-body">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                        <div class="pt-svc-name">${ptEsc(sv.nombre_servicio)}</div>
                        <span class="pt-badge ${ptEsc(sv.estado)}">${ptEsc(sv.estado.charAt(0).toUpperCase()+sv.estado.slice(1))}</span>
                    </div>
                    <div class="pt-svc-meta">
                        ${sv.fecha_inicio ? `<span>Desde: ${ptDate(sv.fecha_inicio)}</span>` : ''}
                        <span>Frecuencia: ${ptEsc(frecLabel)}</span>
                    </div>
                    <div style="margin-top:8px">
                        <span class="pt-svc-renewal ${renCls}">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            ${ptEsc(renTxt)}
                        </span>
                    </div>
                </div>
                <div class="pt-svc-right">
                    ${val > 0 ? `<div class="pt-svc-price">${valFmt}</div><div class="pt-svc-price-sub">COP</div>` : ''}
                </div>
            </div>`;
        }).join('');
    } catch(e) {
        el.innerHTML = '<div class="pt-empty">Error al cargar servicios.</div>';
    }
}

// ── Historial ──────────────────────────────────────────────────────────────
let _ptHistOffset = 0;
let _ptHistTotal  = 0;

async function ptLoadHistorial(append = false) {
    const el = document.getElementById('ptHistList');
    if (!append) { el.innerHTML = '<div class="pt-loading">Cargando historial…</div>'; }

    try {
        const r = await fetch(`api/portal_data.php?action=historial&limite=20&offset=${_ptHistOffset}`);
        const d = await r.json();
        if (!d.success) throw new Error();
        _ptHistTotal = d.total;

        if (!d.data.length && !append) {
            el.innerHTML = `<div class="pt-empty">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                No tienes transacciones registradas.
            </div>`;
            return;
        }

        const icoMap = {
            pagado:    {bg:'#E3F1E8',c:'#2D8F5A'},
            pendiente: {bg:'#F5EBD3',c:'#B47A1E'},
            vencido:   {bg:'#F4DEDB',c:'#B0382F'},
        };

        const rows = d.data.map(tx => {
            const ic = icoMap[tx.estado] || {bg:'#EFECE5',c:'#57544D'};
            const fch = tx.fecha_pago || tx.fecha_vencimiento || tx.created_at;
            const desc = tx.servicio_nombre ? ptEsc(tx.servicio_nombre) : (tx.frecuencia ? ptFrecLabel(tx.frecuencia) : '');
            return `<div class="pt-hist-row">
                <div class="pt-hist-icon" style="background:${ic.bg}">
                    <svg width="16" height="16" fill="none" stroke="${ic.c}" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="pt-hist-body">
                    <div class="pt-hist-title">${ptEsc(tx.titulo || tx.concepto)}</div>
                    <div class="pt-hist-sub">${desc}${desc && fch ? ' · ' : ''}${fch ? ptDate(fch) : ''}</div>
                </div>
                <div class="pt-hist-right">
                    <div class="pt-hist-amount">${ptFmt(tx.monto)}</div>
                    <div class="pt-hist-estado ${ptEsc(tx.estado)}">${tx.estado.charAt(0).toUpperCase()+tx.estado.slice(1)}</div>
                </div>
            </div>`;
        }).join('');

        if (append) {
            el.insertAdjacentHTML('beforeend', rows);
        } else {
            el.innerHTML = rows;
        }

        _ptHistOffset += d.data.length;
        document.getElementById('ptHistMore').style.display =
            (_ptHistOffset < _ptHistTotal) ? 'block' : 'none';

    } catch(e) {
        if (!append) el.innerHTML = '<div class="pt-empty">Error al cargar historial.</div>';
    }
}

function ptLoadMore() { ptLoadHistorial(true); }

// ── Documentos ─────────────────────────────────────────────────────────────
async function ptLoadDocumentos() {
    const el = document.getElementById('ptDocList');
    try {
        const r = await fetch('api/portal_data.php?action=documentos');
        const d = await r.json();
        if (!d.success) throw new Error();

        const items = [];

        // Archivos directos
        (d.archivos || []).forEach(a => {
            const ext  = (a.archivo_url || '').split('.').pop().toLowerCase();
            const isImg = ['jpg','jpeg','png','gif','webp'].includes(ext);
            items.push({ url: a.archivo_url, name: a.nombre_archivo, date: a.created_at, isImg, ext });
        });

        // Adjuntos de transacciones
        (d.tx_docs || []).forEach(tx => {
            ['factura_path','documento_path','imagen_path'].forEach(k => {
                if (tx[k]) {
                    const ext  = tx[k].split('.').pop().toLowerCase();
                    const isImg = ['jpg','jpeg','png','gif','webp'].includes(ext);
                    items.push({ url: tx[k], name: tx.nombre_archivo || k.replace('_path',''), date: tx.created_at, isImg, ext });
                }
            });
        });

        if (!items.length) {
            el.innerHTML = `<div class="pt-empty">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                No tienes documentos disponibles.
            </div>`;
            return;
        }

        const extColors = { pdf:'#EF4444', doc:'#3B82F6', docx:'#3B82F6', xls:'#10B981', xlsx:'#10B981', jpg:'#F59E0B', jpeg:'#F59E0B', png:'#8B5CF6', default:'#6B7280' };
        function extColor(e) { return extColors[e] || extColors.default; }

        el.innerHTML = `<div class="pt-docs-grid">${items.map(it => {
            const col  = extColor(it.ext);
            const bg   = col + '18';
            const thumb = it.isImg
                ? `<img src="${ptEsc(it.url)}" style="width:36px;height:36px;object-fit:cover;border-radius:6px" onerror="this.parentElement.style.background='${bg}';this.outerHTML='<svg width=18 height=18 fill=none stroke=${col} viewBox=&quot;0 0 24 24&quot; stroke-width=1.8><path stroke-linecap=round stroke-linejoin=round d=&quot;M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z&quot;/></svg>'">`
                : `<svg width="18" height="18" fill="none" stroke="${col}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`;
            return `<a class="pt-doc-card" href="${ptEsc(it.url)}" target="_blank" rel="noopener">
                <div class="pt-doc-icon" style="background:${bg}">${thumb}</div>
                <div class="pt-doc-name">${ptEsc(it.name)}</div>
                <div class="pt-doc-date">${ptDate(it.date)}</div>
            </a>`;
        }).join('')}</div>`;

    } catch(e) {
        el.innerHTML = '<div class="pt-empty">Error al cargar documentos.</div>';
    }
}

// ── Perfil ─────────────────────────────────────────────────────────────────
async function ptLoadPerfil() {
    const el = document.getElementById('ptPerfilContent');
    try {
        const r = await fetch('api/portal_data.php?action=perfil');
        const d = await r.json();
        if (!d.success || !d.data) throw new Error();
        const p = d.data;

        const row = (lbl, val) => {
            const empty = !val || val === '—';
            return `<div class="pt-perfil-row">
                <div class="pt-perfil-lbl">${lbl}</div>
                <div class="pt-perfil-val ${empty ? 'empty' : ''}">${empty ? 'No registrado' : ptEsc(val)}</div>
            </div>`;
        };

        const pwdHint = p.has_custom_password
            ? '••••••••&nbsp;&nbsp;<span style="font-size:11px;color:var(--color-success);font-weight:600">Personalizada</span>'
            : '<span style="font-size:12px;color:var(--color-text-muted);font-style:italic">NIT / Número de identificación (por defecto)</span>';

        el.innerHTML = `
            <div class="pt-perfil">
                <div class="pt-perfil-header">
                    <div class="pt-perfil-avatar"><?= htmlspecialchars($iniciales) ?></div>
                    <div>
                        <div class="pt-perfil-name">${ptEsc(p.nombre_comercial)}</div>
                        <div class="pt-perfil-since">Cliente desde ${ptDate(p.created_at)}</div>
                    </div>
                </div>
                <div class="pt-perfil-body">
                    ${row('Empresa / Nombre', p.nombre_comercial)}
                    ${row('NIT / Identificación', p.nit_cedula)}
                    ${row('Persona de contacto', p.persona_contacto)}
                    ${row('Correo contacto', p.email_contacto)}
                    ${row('Correo facturación', p.email_facturacion)}
                    ${row('Teléfono', p.telefono)}
                    ${row('Dirección', p.direccion)}
                    <div class="pt-perfil-row">
                        <div class="pt-perfil-lbl">Contraseña portal</div>
                        <div class="pt-perfil-val">${pwdHint}</div>
                    </div>
                </div>
            </div>

            <div class="pt-pwd-form">
                <div class="pt-pwd-title">Cambiar contraseña</div>
                <div class="pt-pwd-grid">
                    <input class="pt-input" type="password" id="pwdActual"  placeholder="Contraseña actual" autocomplete="current-password">
                    <input class="pt-input" type="password" id="pwdNueva"   placeholder="Nueva contraseña (mín. 6 caracteres)" autocomplete="new-password">
                    <input class="pt-input" type="password" id="pwdConfirm" placeholder="Confirmar nueva contraseña" autocomplete="new-password">
                    <div>
                        <button class="pt-btn-primary" id="pwdBtn" onclick="ptChangePwd()">Actualizar contraseña</button>
                    </div>
                </div>
            </div>`;

    } catch(e) {
        el.innerHTML = '<div class="pt-empty">Error al cargar perfil.</div>';
    }
}

async function ptChangePwd() {
    const btn    = document.getElementById('pwdBtn');
    const actual = document.getElementById('pwdActual').value.trim();
    const nueva  = document.getElementById('pwdNueva').value.trim();
    const conf   = document.getElementById('pwdConfirm').value.trim();

    btn.disabled = true; btn.textContent = 'Guardando…';
    try {
        const r = await fetch('api/portal_auth.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'change_password', actual, nueva, confirm: conf })
        });
        const d = await r.json();
        if (d.success) {
            ptToast(d.message || 'Contraseña actualizada', 'success');
            document.getElementById('pwdActual').value  = '';
            document.getElementById('pwdNueva').value   = '';
            document.getElementById('pwdConfirm').value = '';
            _ptLoaded['perfil'] = false; // re-cargar para actualizar indicador
        } else {
            ptToast(d.error || 'Error al cambiar contraseña', 'error');
        }
    } catch(e) {
        ptToast('Error de conexión', 'error');
    }
    btn.disabled = false; btn.textContent = 'Actualizar contraseña';
}

// ── Init ────────────────────────────────────────────────────────────────────
(function init() {
    ptLoadStats();
    _ptLoaded['servicios'] = true;
    ptLoadServicios();
})();
</script>
</body>
</html>
