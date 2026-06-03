<?php
/**
 * CRM QUANTUN Digital — Gestión del Portal Cliente
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle    = 'Portal Cliente';
$pageSubtitle = 'Administra accesos, contraseñas y cuentas del portal de autoservicio';
include __DIR__ . '/includes/header.php';
?>

<style>
/* ── Stats cards ─────────────────────────────────────────── */
.pa-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.pa-stat {
    background: #fff;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 16px 18px;
    cursor: default;
}
.pa-stat-label { font-size: 11.5px; color: var(--color-text-muted); font-weight: 500; margin-bottom: 5px; }
.pa-stat-val   { font-size: 26px; font-weight: 800; line-height: 1.1; }
.pa-stat-sub   { font-size: 11px; color: var(--color-text-light); margin-top: 2px; }
.pa-stat.green .pa-stat-val { color: var(--color-success); }
.pa-stat.red   .pa-stat-val { color: var(--color-danger); }
.pa-stat.grey  .pa-stat-val { color: var(--color-text-muted); }

/* ── Toolbar ──────────────────────────────────────────────── */
.pa-toolbar {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    margin-bottom: 14px;
}
.pa-search {
    flex: 1; min-width: 180px; max-width: 320px;
    padding: 8px 12px 8px 34px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 13.5px; font-family: inherit;
    background: #fff; color: var(--color-text);
    outline: none; transition: border-color var(--transition-fast);
    position: relative;
}
.pa-search:focus { border-color: var(--color-text); }
.pa-search-wrap  { position: relative; flex: 1; min-width: 180px; max-width: 320px; }
.pa-search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; }
.pa-filters { display: flex; gap: 4px; flex-wrap: wrap; }
.pa-filter-btn {
    padding: 7px 13px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 12.5px; font-weight: 500;
    font-family: inherit; cursor: pointer;
    background: #fff; color: var(--color-text-muted);
    transition: all var(--transition-fast);
}
.pa-filter-btn:hover { border-color: var(--color-text-muted); color: var(--color-text); }
.pa-filter-btn.active {
    background: var(--color-text);
    color: #fff; border-color: var(--color-text);
    font-weight: 600;
}
.pa-filter-btn .cnt {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 16px; border-radius: 100px;
    font-size: 10px; font-weight: 800; padding: 0 4px; margin-left: 4px;
    background: rgba(255,255,255,.18);
    color: inherit;
}
.pa-filter-btn:not(.active) .cnt {
    background: var(--color-surface-alt);
    color: var(--color-text-muted);
}

/* ── Client List ──────────────────────────────────────────── */
.pa-list { display: flex; flex-direction: column; gap: 0; }
.pa-row {
    display: grid;
    grid-template-columns: 1fr 200px 130px 110px 100px auto;
    align-items: center;
    gap: 12px;
    padding: 13px 16px;
    background: #fff;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    margin-bottom: 6px;
    transition: box-shadow var(--transition-fast);
}
.pa-row:hover { box-shadow: var(--shadow-md); }

/* Client info */
.pa-row-client { display: flex; align-items: center; gap: 10px; min-width: 0; }
.pa-row-avatar {
    width: 34px; height: 34px; border-radius: var(--radius-md);
    background: var(--color-surface-alt);
    color: var(--color-text);
    font-size: 13px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.pa-row-name  { font-size: 13.5px; font-weight: 700; color: var(--color-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pa-row-nit   { font-size: 11px; color: var(--color-text-light); margin-top: 1px; }

/* Email */
.pa-row-email { font-size: 12.5px; color: var(--color-text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pa-row-email.noemail { color: var(--color-danger); font-size: 12px; font-style: italic; }

/* Toggle switch */
.pa-toggle-wrap { display: flex; align-items: center; gap: 7px; }
.pa-toggle {
    position: relative; width: 38px; height: 22px;
    cursor: pointer; flex-shrink: 0;
}
.pa-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.pa-toggle-slider {
    position: absolute; inset: 0;
    background: var(--color-danger-bg);
    border: 1.5px solid #f3b9b4;
    border-radius: 999px;
    transition: all .2s;
}
.pa-toggle-slider::before {
    content: '';
    position: absolute; top: 2px; left: 2px;
    width: 14px; height: 14px;
    border-radius: 50%; background: var(--color-danger);
    transition: all .2s;
}
.pa-toggle input:checked + .pa-toggle-slider {
    background: var(--color-success-bg);
    border-color: #9dd6b5;
}
.pa-toggle input:checked + .pa-toggle-slider::before {
    background: var(--color-success);
    transform: translateX(16px);
}
.pa-toggle-label { font-size: 12px; font-weight: 600; color: var(--color-text-muted); white-space: nowrap; }
.pa-toggle input:checked ~ .pa-toggle-label { color: var(--color-success); }

/* Último acceso */
.pa-lastaccess { font-size: 12px; color: var(--color-text-muted); }
.pa-lastaccess.never { color: var(--color-text-light); font-style: italic; }

/* Password type */
.pa-pwdtype {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11.5px; font-weight: 600;
    padding: 3px 8px; border-radius: var(--radius-full);
}
.pa-pwdtype.custom  { background: #E1E7F2; color: var(--color-info); }
.pa-pwdtype.default { background: var(--color-surface-alt); color: var(--color-text-muted); }

/* Actions */
.pa-actions { display: flex; gap: 4px; align-items: center; justify-content: flex-end; }
.pa-btn-icon {
    width: 30px; height: 30px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--color-text-muted);
    transition: all var(--transition-fast);
    font-family: inherit;
}
.pa-btn-icon:hover { background: var(--color-surface-alt); color: var(--color-text); border-color: var(--color-text-muted); }
.pa-btn-icon.danger:hover { background: var(--color-danger-bg); color: var(--color-danger); border-color: #f3b9b4; }

/* Header de la tabla */
.pa-list-header {
    display: grid;
    grid-template-columns: 1fr 200px 130px 110px 100px auto;
    gap: 12px;
    padding: 6px 16px 8px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; color: var(--color-text-light);
}

/* Modal */
.pa-modal-overlay {
    display: none;
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(14,14,12,.5);
    align-items: center; justify-content: center;
}
.pa-modal-overlay.open { display: flex; }
.pa-modal {
    background: #fff;
    border-radius: var(--radius-xl);
    padding: 28px 28px 24px;
    width: 100%; max-width: 420px;
    box-shadow: var(--shadow-xl);
}
.pa-modal-title { font-size: 16px; font-weight: 800; margin-bottom: 4px; }
.pa-modal-sub   { font-size: 13px; color: var(--color-text-muted); margin-bottom: 20px; }
.pa-modal-input {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 13.5px; font-family: inherit;
    background: var(--color-surface);
    color: var(--color-text);
    outline: none; margin-bottom: 10px;
    transition: border-color var(--transition-fast);
}
.pa-modal-input:focus { border-color: var(--color-text); background: #fff; }
.pa-modal-hint {
    font-size: 12px; color: var(--color-text-muted);
    margin-bottom: 18px; line-height: 1.5;
}
.pa-modal-hint strong { color: var(--color-text); }
.pa-modal-actions { display: flex; gap: 8px; justify-content: flex-end; }
.pa-modal-cancel {
    padding: 9px 18px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    background: transparent; font-size: 13.5px;
    font-family: inherit; font-weight: 500;
    cursor: pointer; color: var(--color-text-muted);
    transition: all var(--transition-fast);
}
.pa-modal-cancel:hover { border-color: var(--color-text-muted); color: var(--color-text); }
.pa-modal-save {
    padding: 9px 20px;
    background: var(--color-text);
    color: #fff; border: none;
    border-radius: var(--radius-md);
    font-size: 13.5px; font-weight: 700;
    font-family: inherit; cursor: pointer;
    transition: opacity var(--transition-fast);
}
.pa-modal-save:hover { opacity: .82; }
.pa-modal-save:disabled { opacity: .4; cursor: not-allowed; }
.pa-modal-opt {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 12px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    cursor: pointer; margin-bottom: 8px;
    transition: all var(--transition-fast);
    font-size: 13px;
}
.pa-modal-opt:hover { border-color: var(--color-text-muted); background: var(--color-surface-alt); }
.pa-modal-opt input { accent-color: var(--color-text); }

/* Config modal */
.pa-cfg-modal {
    background: #fff;
    border-radius: var(--radius-xl);
    padding: 26px 26px 22px;
    width: 100%; max-width: 480px;
    box-shadow: var(--shadow-xl);
}
.pa-cfg-title { font-size: 15px; font-weight: 800; margin-bottom: 3px; }
.pa-cfg-sub   { font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 18px; line-height: 1.4; }
.pa-cfg-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px; }
.pa-cfg-item  {
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; padding: 10px 13px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    cursor: pointer; transition: border-color .15s, background .15s;
}
.pa-cfg-item:hover { border-color: var(--color-text-muted); background: var(--color-surface-alt); }
.pa-cfg-item.off   { background: var(--color-surface-alt); border-color: var(--color-border); opacity: .65; }
.pa-cfg-item-label { display: flex; align-items: center; gap: 7px; }
.pa-cfg-item-label svg { flex-shrink: 0; }
.pa-cfg-item-name  { font-size: 12.5px; font-weight: 600; color: var(--color-text); }
.pa-cfg-item-desc  { font-size: 10.5px; color: var(--color-text-muted); margin-top: 1px; }
/* Tiny toggle */
.pa-cfg-toggle { position:relative; width:32px; height:18px; flex-shrink:0; }
.pa-cfg-toggle input { opacity:0; width:0; height:0; position:absolute; }
.pa-cfg-toggle-sl {
    position:absolute; inset:0;
    background: #E2E8F0; border-radius:999px; transition: background .2s;
}
.pa-cfg-toggle-sl::before {
    content:''; position:absolute; top:2px; left:2px;
    width:14px; height:14px; border-radius:50%; background:#fff;
    box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s;
}
.pa-cfg-toggle input:checked + .pa-cfg-toggle-sl { background:var(--color-success); }
.pa-cfg-toggle input:checked + .pa-cfg-toggle-sl::before { transform:translateX(14px); }

/* Empty state */
.pa-empty {
    text-align: center; padding: 52px 24px;
    color: var(--color-text-light); font-size: 13.5px;
}
.pa-empty svg { display: block; margin: 0 auto 12px; opacity: .3; }

/* Loading skeleton */
.pa-skeleton {
    height: 58px; background: var(--color-surface-alt);
    border-radius: var(--radius-lg);
    margin-bottom: 6px;
    animation: paSkel 1.2s ease-in-out infinite alternate;
}
@keyframes paSkel { from{opacity:.5} to{opacity:1} }

/* Toast */
#paToast {
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
#paToast.show { opacity: 1; transform: translateY(0); }
#paToast.success { background: var(--color-success); }
#paToast.error   { background: var(--color-danger); }

/* Responsive: ocultar columnas menores en pantallas chicas */
@media (max-width: 900px) {
    .pa-row, .pa-list-header { grid-template-columns: 1fr 130px 100px auto; }
    .pa-col-email, .pa-col-pwd { display: none; }
}
@media (max-width: 600px) {
    .pa-row, .pa-list-header { grid-template-columns: 1fr 100px auto; }
    .pa-col-email, .pa-col-pwd, .pa-col-acc { display: none; }
}
</style>

<div class="page-header animate-fade-up">
    <div>
        <h1 class="page-title">Portal Cliente</h1>
        <p class="page-subtitle">Gestiona accesos, contraseñas y cuentas del portal de autoservicio</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
        <a href="portal_cliente.php" target="_blank" class="btn btn-outline" style="display:flex;align-items:center;gap:6px;text-decoration:none">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Ver Portal
        </a>
    </div>
</div>

<!-- Stats -->
<div class="pa-stats animate-fade-up stagger-1" id="paStats">
    <div class="pa-stat"><div class="pa-stat-label">Total clientes</div><div class="pa-stat-val" id="stTotal">—</div></div>
    <div class="pa-stat green"><div class="pa-stat-label">Con acceso activo</div><div class="pa-stat-val" id="stActivos">—</div></div>
    <div class="pa-stat red"><div class="pa-stat-label">Bloqueados</div><div class="pa-stat-val" id="stBloq">—</div></div>
    <div class="pa-stat grey"><div class="pa-stat-label">Sin correo</div><div class="pa-stat-val" id="stSinEmail">—</div><div class="pa-stat-sub">no pueden ingresar</div></div>
    <div class="pa-stat"><div class="pa-stat-label">Han ingresado</div><div class="pa-stat-val" id="stHanIngresado">—</div><div class="pa-stat-sub">al menos una vez</div></div>
    <div class="pa-stat"><div class="pa-stat-label">Con clave propia</div><div class="pa-stat-val" id="stCustPwd">—</div></div>
</div>

<!-- Toolbar -->
<div class="pa-toolbar animate-fade-up stagger-2">
    <div class="pa-search-wrap">
        <svg width="14" height="14" fill="none" stroke="var(--color-text-light)" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" class="pa-search" id="paSearch" placeholder="Buscar cliente, correo, NIT…" oninput="paSearchDebounce()">
    </div>
    <div class="pa-filters" id="paFilters">
        <button class="pa-filter-btn active" data-f="todos"     onclick="paSetFiltro('todos',this)">Todos <span class="cnt" id="cntTodos">—</span></button>
        <button class="pa-filter-btn"        data-f="activos"   onclick="paSetFiltro('activos',this)">Activos <span class="cnt" id="cntActivos">—</span></button>
        <button class="pa-filter-btn"        data-f="bloqueados" onclick="paSetFiltro('bloqueados',this)">Bloqueados <span class="cnt" id="cntBloq">—</span></button>
        <button class="pa-filter-btn"        data-f="sin_email" onclick="paSetFiltro('sin_email',this)">Sin correo <span class="cnt" id="cntSinEmail">—</span></button>
    </div>
    <div style="margin-left:auto;flex-shrink:0">
        <button class="btn btn-outline sm" onclick="paActivarTodos()" title="Activar portal a todos los clientes que tengan correo registrado">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Activar todos
        </button>
    </div>
</div>

<!-- List -->
<div class="animate-fade-up stagger-3">
    <div class="pa-list-header">
        <div>Cliente</div>
        <div class="pa-col-email">Correo de acceso</div>
        <div>Estado portal</div>
        <div class="pa-col-acc">Último acceso</div>
        <div class="pa-col-pwd">Contraseña</div>
        <div></div>
    </div>
    <div id="paList">
        <div class="pa-skeleton"></div>
        <div class="pa-skeleton" style="opacity:.7"></div>
        <div class="pa-skeleton" style="opacity:.5"></div>
    </div>
</div>

<!-- Modal Config Secciones -->
<div class="pa-modal-overlay" id="paCfgModal" onclick="if(event.target===this)paCerrarCfg()">
    <div class="pa-cfg-modal">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px">
            <div>
                <div class="pa-cfg-title">Secciones visibles en el portal</div>
                <div class="pa-cfg-sub" id="paCfgClienteNombre">—</div>
            </div>
            <button onclick="paCerrarCfg()" style="width:28px;height:28px;border:1.5px solid var(--color-border);border-radius:var(--radius-md);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);flex-shrink:0">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <input type="hidden" id="paCfgClienteId">
        <div class="pa-cfg-grid" id="paCfgGrid"><!-- JS --></div>
        <div style="display:flex;justify-content:flex-end;gap:8px">
            <button class="pa-modal-cancel" onclick="paCerrarCfg()">Cancelar</button>
            <button class="pa-modal-save" id="paCfgSaveBtn" onclick="paSaveConfig()">Guardar cambios</button>
        </div>
    </div>
</div>

<!-- Modal Contraseña -->
<div class="pa-modal-overlay" id="paModalPwd" onclick="if(event.target===this)paCerrarModal()">
    <div class="pa-modal">
        <div class="pa-modal-title">Gestionar contraseña</div>
        <div class="pa-modal-sub" id="paModalClienteNombre">—</div>

        <input type="hidden" id="paModalClienteId">

        <!-- Opciones -->
        <label class="pa-modal-opt" id="optCustom" style="display:flex">
            <input type="radio" name="pwdTipo" value="custom" checked onchange="paModeChange()">
            <span>
                <strong>Establecer contraseña personalizada</strong><br>
                <span style="font-size:12px;color:var(--color-text-muted)">Ingresa una clave que el cliente deberá usar para ingresar</span>
            </span>
        </label>
        <label class="pa-modal-opt" id="optReset">
            <input type="radio" name="pwdTipo" value="reset" onchange="paModeChange()">
            <span>
                <strong>Restablecer al NIT / identificación</strong><br>
                <span style="font-size:12px;color:var(--color-text-muted)">El cliente ingresará usando su NIT como contraseña (por defecto)</span>
            </span>
        </label>

        <div id="paModalPwdFields" style="margin-top:12px">
            <input type="password" class="pa-modal-input" id="paNuevaPwd" placeholder="Nueva contraseña (mín. 4 caracteres)" autocomplete="new-password">
            <div class="pa-modal-hint">
                El cliente podrá cambiar esta contraseña desde su perfil en el portal.
            </div>
        </div>
        <div id="paModalResetInfo" style="display:none;margin-top:12px">
            <div class="pa-modal-hint">
                Se eliminará la contraseña personalizada. El cliente usará su <strong>NIT o número de identificación</strong> para ingresar.
            </div>
        </div>

        <div class="pa-modal-actions">
            <button class="pa-modal-cancel" onclick="paCerrarModal()">Cancelar</button>
            <button class="pa-modal-save" id="paModalSaveBtn" onclick="paGuardarPwd()">Guardar</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="paToast"></div>

<script>
// ── State ──────────────────────────────────────────────────────────────────
let _paFiltro  = 'todos';
let _paBuscar  = '';
let _paTimer   = null;
let _paCounts  = {todos:0, activos:0, bloqueados:0, sin_email:0};

// ── Helpers ────────────────────────────────────────────────────────────────
function paEsc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function paDate(d) {
    if (!d) return null;
    const dt = new Date(d.replace(' ','T'));
    const now = new Date();
    const diff = Math.floor((now - dt) / 864e5);
    if (diff === 0) return 'Hoy';
    if (diff === 1) return 'Ayer';
    if (diff < 7)  return `Hace ${diff} días`;
    if (diff < 30) return `Hace ${Math.floor(diff/7)} sem.`;
    const m = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return `${dt.getDate()} ${m[dt.getMonth()]} ${dt.getFullYear()}`;
}

function paToast(msg, type='') {
    const t = document.getElementById('paToast');
    t.textContent = msg; t.className = 'show ' + type;
    clearTimeout(t._t);
    t._t = setTimeout(() => t.className = type, 3200);
}

function paInitials(nombre) {
    return (nombre||'').split(' ').slice(0,2).map(p=>p[0]||'').join('').toUpperCase() || '?';
}

// ── Cargar stats ───────────────────────────────────────────────────────────
async function paLoadStats() {
    const r = await fetch('api/portal_admin.php?action=stats');
    const d = await r.json();
    if (!d.success) return;
    const s = d.data;
    document.getElementById('stTotal').textContent       = s.total;
    document.getElementById('stActivos').textContent     = s.con_acceso;
    document.getElementById('stBloq').textContent        = s.bloqueados;
    document.getElementById('stSinEmail').textContent    = s.sin_email;
    document.getElementById('stHanIngresado').textContent= s.han_ingresado;
    document.getElementById('stCustPwd').textContent     = s.con_pwd_custom;

    // Counters de filtros
    _paCounts = {
        todos:     parseInt(s.total),
        activos:   parseInt(s.con_acceso),
        bloqueados:parseInt(s.bloqueados),
        sin_email: parseInt(s.sin_email),
    };
    document.getElementById('cntTodos').textContent     = _paCounts.todos;
    document.getElementById('cntActivos').textContent   = _paCounts.activos;
    document.getElementById('cntBloq').textContent      = _paCounts.bloqueados;
    document.getElementById('cntSinEmail').textContent  = _paCounts.sin_email;
}

// ── Cargar lista ───────────────────────────────────────────────────────────
async function paLoadList() {
    const el = document.getElementById('paList');
    el.innerHTML = '<div class="pa-skeleton"></div><div class="pa-skeleton" style="opacity:.7"></div><div class="pa-skeleton" style="opacity:.5"></div>';

    const params = new URLSearchParams({ action:'list', filtro: _paFiltro });
    if (_paBuscar) params.set('buscar', _paBuscar);

    const r = await fetch('api/portal_admin.php?' + params);
    const d = await r.json();
    if (!d.success) { el.innerHTML = '<div class="pa-empty">Error al cargar datos.</div>'; return; }
    if (!d.data.length) {
        el.innerHTML = `<div class="pa-empty">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            No se encontraron clientes con estos filtros.
        </div>`;
        return;
    }

    el.innerHTML = d.data.map(c => paRenderRow(c)).join('');
}

function paRenderRow(c) {
    const ini       = paInitials(c.nombre_comercial);
    const email     = c.email_contacto || c.email_facturacion || '';
    const hasEmail  = !!c.has_email;
    const activo    = parseInt(c.portal_activo) === 1;
    const hasPwd    = parseInt(c.has_custom_password) === 1;
    const lastAcc   = paDate(c.portal_ultimo_acceso);
    const disabled  = !hasEmail ? 'disabled title="Sin correo registrado"' : '';
    const toggleClass = !hasEmail ? 'style="opacity:.4;pointer-events:none"' : '';

    // Calcular estado del portal
    let statusBadge;
    if (!hasEmail) {
        statusBadge = `<span style="font-size:11.5px;color:var(--color-text-light);font-style:italic">Sin correo</span>`;
    } else if (activo) {
        statusBadge = `<span style="font-size:11.5px;font-weight:700;color:var(--color-success);display:flex;align-items:center;gap:4px">
            <span style="width:7px;height:7px;border-radius:50%;background:var(--color-success);display:inline-block"></span>Activo</span>`;
    } else {
        statusBadge = `<span style="font-size:11.5px;font-weight:700;color:var(--color-danger);display:flex;align-items:center;gap:4px">
            <span style="width:7px;height:7px;border-radius:50%;background:var(--color-danger);display:inline-block"></span>Bloqueado</span>`;
    }

    return `<div class="pa-row" id="paRow_${c.id}">

        <!-- Cliente -->
        <div class="pa-row-client">
            <div class="pa-row-avatar">${paEsc(ini)}</div>
            <div>
                <div class="pa-row-name" title="${paEsc(c.nombre_comercial)}">${paEsc(c.nombre_comercial)}</div>
                <div class="pa-row-nit">${c.nit_cedula ? 'NIT: ' + paEsc(c.nit_cedula) : '<span style="font-style:italic">Sin NIT</span>'}</div>
            </div>
        </div>

        <!-- Correo -->
        <div class="pa-col-email pa-row-email ${!hasEmail?'noemail':''}"
             title="${hasEmail ? paEsc(email) : 'Sin correo registrado'}">
            ${hasEmail
                ? `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:4px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>${paEsc(email)}`
                : `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>Sin correo`
            }
        </div>

        <!-- Toggle + estado -->
        <div>
            <div style="display:flex;align-items:center;gap:8px" ${toggleClass}>
                <label class="pa-toggle" title="${activo?'Bloquear acceso':'Activar acceso'}">
                    <input type="checkbox" id="tog_${c.id}" ${activo?'checked':''} ${disabled}
                           onchange="paToggle(${c.id}, this.checked)">
                    <span class="pa-toggle-slider"></span>
                </label>
                <div id="togLabel_${c.id}">${statusBadge}</div>
            </div>
        </div>

        <!-- Último acceso -->
        <div class="pa-col-acc pa-lastaccess ${lastAcc?'':'never'}"
             title="${c.portal_ultimo_acceso ? 'Último acceso: ' + c.portal_ultimo_acceso : 'Nunca ha ingresado'}">
            ${lastAcc || 'Nunca'}
        </div>

        <!-- Tipo contraseña -->
        <div class="pa-col-pwd">
            <span class="pa-pwdtype ${hasPwd?'custom':'default'}">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                ${hasPwd ? 'Personalizada' : 'Por NIT'}
            </span>
        </div>

        <!-- Acciones -->
        <div class="pa-actions">
            <button class="pa-btn-icon" onclick="paAbrirConfig(${c.id}, '${paEsc(c.nombre_comercial).replace(/'/g,"&#39;")}', ${JSON.stringify(c.portal_config||null)})"
                    title="Personalizar secciones visibles">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
            <button class="pa-btn-icon" onclick="paAbrirModalPwd(${c.id}, '${paEsc(c.nombre_comercial).replace(/'/g,"&#39;")}')"
                    title="Gestionar contraseña">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </button>
            <a href="cliente_detalle.php?id=${c.id}" class="pa-btn-icon" title="Ver ficha del cliente" style="text-decoration:none;display:flex;align-items:center;justify-content:center">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </a>
        </div>

    </div>`;
}

// ── Toggle acceso ──────────────────────────────────────────────────────────
async function paToggle(id, activar) {
    const labelEl = document.getElementById('togLabel_' + id);
    labelEl.innerHTML = '<span style="font-size:11px;color:var(--color-text-light)">Guardando…</span>';

    const r = await fetch('api/portal_admin.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ action:'toggle_access', id, activo: activar ? 1 : 0 })
    });
    const d = await r.json();

    if (d.success) {
        const activo = parseInt(d.activo) === 1;
        if (activo) {
            labelEl.innerHTML = `<span style="font-size:11.5px;font-weight:700;color:var(--color-success);display:flex;align-items:center;gap:4px">
                <span style="width:7px;height:7px;border-radius:50%;background:var(--color-success);display:inline-block"></span>Activo</span>`;
        } else {
            labelEl.innerHTML = `<span style="font-size:11.5px;font-weight:700;color:var(--color-danger);display:flex;align-items:center;gap:4px">
                <span style="width:7px;height:7px;border-radius:50%;background:var(--color-danger);display:inline-block"></span>Bloqueado</span>`;
        }
        paToast(d.message, activo ? 'success' : 'error');
        paLoadStats();
    } else {
        paToast(d.error || 'Error al cambiar estado', 'error');
        // Revertir toggle
        document.getElementById('tog_' + id).checked = !activar;
    }
}

// ── Activar todos ──────────────────────────────────────────────────────────
async function paActivarTodos() {
    const ok = await confirmAction({
        title:   'Activar acceso a todos',
        message: 'Se activará el portal para todos los clientes que tengan correo registrado. ¿Continuar?',
        confirmLabel: 'Activar todos',
        confirmClass: 'btn-success',
    });
    if (!ok) return;

    const r = await fetch('api/portal_admin.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'set_all_active'})
    });
    const d = await r.json();
    paToast(d.success ? d.message : d.error, d.success ? 'success' : 'error');
    if (d.success) { paLoadStats(); paLoadList(); }
}

// ── Filtros / búsqueda ─────────────────────────────────────────────────────
function paSetFiltro(f, btn) {
    _paFiltro = f;
    document.querySelectorAll('.pa-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    paLoadList();
}

function paSearchDebounce() {
    clearTimeout(_paTimer);
    _paTimer = setTimeout(() => {
        _paBuscar = document.getElementById('paSearch').value.trim();
        paLoadList();
    }, 320);
}

// ── Modal contraseña ───────────────────────────────────────────────────────
function paAbrirModalPwd(id, nombre) {
    document.getElementById('paModalClienteId').value     = id;
    document.getElementById('paModalClienteNombre').textContent = nombre;
    document.getElementById('paNuevaPwd').value           = '';
    // Resetear a modo "custom"
    document.querySelector('input[name="pwdTipo"][value="custom"]').checked = true;
    paModeChange();
    document.getElementById('paModalPwd').classList.add('open');
    setTimeout(() => document.getElementById('paNuevaPwd').focus(), 100);
}

function paCerrarModal() {
    document.getElementById('paModalPwd').classList.remove('open');
}

function paModeChange() {
    const modo = document.querySelector('input[name="pwdTipo"]:checked').value;
    document.getElementById('paModalPwdFields').style.display  = modo === 'custom' ? 'block' : 'none';
    document.getElementById('paModalResetInfo').style.display  = modo === 'reset'  ? 'block' : 'none';
    document.getElementById('paModalSaveBtn').textContent = modo === 'reset' ? 'Restablecer' : 'Guardar contraseña';
}

async function paGuardarPwd() {
    const id   = parseInt(document.getElementById('paModalClienteId').value);
    const modo = document.querySelector('input[name="pwdTipo"]:checked').value;
    const btn  = document.getElementById('paModalSaveBtn');

    btn.disabled = true; btn.textContent = 'Guardando…';

    let body, r, d;
    if (modo === 'reset') {
        body = JSON.stringify({action:'reset_password', id});
    } else {
        const pwd = document.getElementById('paNuevaPwd').value.trim();
        if (pwd.length < 4) {
            paToast('La contraseña debe tener al menos 4 caracteres', 'error');
            btn.disabled = false; btn.textContent = 'Guardar contraseña';
            return;
        }
        body = JSON.stringify({action:'set_password', id, password: pwd});
    }

    r = await fetch('api/portal_admin.php', { method:'POST', headers:{'Content-Type':'application/json'}, body });
    d = await r.json();

    btn.disabled = false;
    paModeChange(); // restaura label

    if (d.success) {
        paToast(d.message, 'success');
        paCerrarModal();
        paLoadList(); // refresca la lista para mostrar nuevo tipo de clave
    } else {
        paToast(d.error || 'Error al guardar', 'error');
    }
}

// Cerrar modal con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { paCerrarModal(); paCerrarCfg(); }
});

// ── Modal Config secciones ─────────────────────────────────────────────────
const PA_SECCIONES = [
    { key:'stats',          icon:'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', name:'Estadísticas',      desc:'4 cifras resumen' },
    { key:'servicios',      icon:'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', name:'Mis servicios',      desc:'Lista de servicios activos' },
    { key:'pagos',          icon:'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z', name:'Historial de pagos', desc:'Transacciones e ingresos' },
    { key:'tareas',         icon:'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', name:'Tareas',             desc:'Tareas asignadas al cliente' },
    { key:'notificaciones', icon:'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', name:'Notificaciones',    desc:'Recordatorios enviados' },
    { key:'documentos',     icon:'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', name:'Documentos',        desc:'Archivos y facturas' },
    { key:'accesos',        icon:'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z', name:'Accesos',           desc:'Credenciales e inventario' },
    { key:'perfil',         icon:'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', name:'Mi Perfil',         desc:'Datos y cambio de clave' },
];

let _paCfgState = {};

function paAbrirConfig(id, nombre, cfgRaw) {
    document.getElementById('paCfgClienteId').value = id;
    document.getElementById('paCfgClienteNombre').textContent = nombre;

    // Construir estado: true por defecto si no hay config
    _paCfgState = {};
    PA_SECCIONES.forEach(s => {
        _paCfgState[s.key] = cfgRaw && cfgRaw[s.key] === false ? false : true;
    });

    _renderCfgGrid();
    document.getElementById('paCfgModal').classList.add('open');
}

function _renderCfgGrid() {
    document.getElementById('paCfgGrid').innerHTML = PA_SECCIONES.map(s => {
        const on = _paCfgState[s.key] !== false;
        return `<div class="pa-cfg-item ${on?'':'off'}" id="cfgItem_${s.key}" onclick="paCfgToggle('${s.key}')">
            <div class="pa-cfg-item-label">
                <svg width="14" height="14" fill="none" stroke="${on?'var(--color-text)':'var(--color-text-light)'}" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="${s.icon}"/>
                </svg>
                <div>
                    <div class="pa-cfg-item-name" style="color:${on?'var(--color-text)':'var(--color-text-light)'}">${s.name}</div>
                    <div class="pa-cfg-item-desc">${s.desc}</div>
                </div>
            </div>
            <label class="pa-cfg-toggle" onclick="event.stopPropagation()">
                <input type="checkbox" id="cfgChk_${s.key}" ${on?'checked':''} onchange="paCfgToggle('${s.key}')">
                <span class="pa-cfg-toggle-sl"></span>
            </label>
        </div>`;
    }).join('');
}

function paCfgToggle(key) {
    _paCfgState[key] = !_paCfgState[key];
    _renderCfgGrid();
}

function paCerrarCfg() {
    document.getElementById('paCfgModal').classList.remove('open');
}

async function paSaveConfig() {
    const id  = parseInt(document.getElementById('paCfgClienteId').value);
    const btn = document.getElementById('paCfgSaveBtn');
    btn.disabled = true; btn.textContent = 'Guardando…';

    const r = await fetch('api/portal_admin.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ action:'save_config', id, config: _paCfgState })
    });
    const d = await r.json();
    btn.disabled = false; btn.textContent = 'Guardar cambios';

    if (d.success) {
        paToast('Configuración guardada', 'success');
        paCerrarCfg();
        paLoadList(); // refresca para actualizar portal_config en caché de fila
    } else {
        paToast(d.error || 'Error al guardar', 'error');
    }
}

// ── Init ────────────────────────────────────────────────────────────────────
paLoadStats();
paLoadList();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
