<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle = 'Mejoras';
include __DIR__ . '/includes/header.php';
?>

<style>
/* ── Grid principal ── */
.mj-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
.mj-main  { display:grid; grid-template-columns:minmax(0,1fr) 330px; gap:18px; align-items:start; }

/* ── Stat cards ── */
.mj-sc { background:#fff; border:1px solid #E8E5DD; border-radius:8px; padding:16px 18px;
         display:flex; align-items:center; gap:14px; transition:box-shadow .15s; }
.mj-sc:hover { box-shadow:0 3px 12px rgba(0,0,0,.07); }
.mj-sc-icon { width:40px; height:40px; border-radius:8px; display:flex; align-items:center;
              justify-content:center; flex-shrink:0; }
.mj-sc-num  { font-size:26px; font-weight:800; line-height:1; font-family:var(--font-secondary); color:#0E0E0C; }
.mj-sc-lbl  { font-size:11px; font-weight:600; color:#8A867C; margin-top:2px; }

/* ── Pills ── */
.mj-pill { font-size:10px; font-weight:700; padding:2px 8px; border-radius:100px; white-space:nowrap; flex-shrink:0; }
.cat-diseño        { background:#E1E7F2; color:#3F5E9E; }
.cat-funcionalidad { background:#E3F1E8; color:#1B5A39; }
.cat-bug           { background:#F4DEDB; color:#6E211B; }
.cat-rendimiento   { background:#FEF3C7; color:#92400E; }
.cat-otro          { background:#EFECE5; color:#57544D; }
.est-idea        { background:#F1F0EC; color:#57544D; }
.est-planeado    { background:#E1E7F2; color:#3F5E9E; }
.est-en_progreso { background:#FEF3C7; color:#92400E; }
.est-completado  { background:#E3F1E8; color:#1B5A39; }

/* ── Dot prioridad ── */
.mj-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; display:inline-block; }
.prio-alta  { background:#ef4444; }
.prio-media { background:#f59e0b; }
.prio-baja  { background:#cbd5e1; }

/* ── Lista card ── */
.mj-list-head { display:flex; align-items:center; justify-content:space-between;
                padding:12px 16px; border-bottom:1px solid var(--color-border); flex-wrap:wrap; gap:8px; }
.mj-filters   { display:flex; gap:6px; padding:8px 12px; border-bottom:1px solid #F1F0EC;
                background:#FAFAF7; flex-wrap:wrap; align-items:center; }
.mj-filters .form-input,
.mj-filters .form-select { height:30px; font-size:12px; }

/* ── Filas ── */
.mj-row { display:flex; align-items:center; gap:10px; padding:9px 14px;
          border-bottom:1px solid #F5F4F1; cursor:pointer; transition:background .12s; }
.mj-row:last-of-type { border-bottom:none; }
.mj-row:hover, .mj-row.open { background:#FAFAF7; }
.mj-row-title { flex:1; font-size:13px; font-weight:500; color:var(--color-text);
                line-height:1.4; min-width:0; }
.mj-row-title.done { text-decoration:line-through; color:#94a3b8; }
.mj-edit-input { flex:1; font-size:13px; font-weight:500; border:1.5px solid #0E0E0C;
                 border-radius:5px; padding:2px 8px; font-family:inherit; outline:none; background:#fff; }
.mj-row-right { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.mj-ico { width:24px; height:24px; display:flex; align-items:center; justify-content:center;
          border:none; background:transparent; border-radius:4px; cursor:pointer;
          color:#C6C2BB; transition:all .14s; flex-shrink:0; }
.mj-ico:hover { background:#F1F0EC; color:#57544D; }
.mj-ico.del:hover { background:#FEE2E2; color:#ef4444; }
.mj-ico.edit { opacity:0; }
.mj-row:hover .mj-ico.edit { opacity:1; }

/* ── Panel expandido ── */
.mj-body { display:none; padding:12px 16px 14px 32px; border-bottom:1px solid #F1F0EC; background:#FAFAF7; }
.mj-body.open { display:block; }
.mj-ta { width:100%; min-height:68px; resize:vertical; font-size:12px; line-height:1.55;
         background:#fff; border:1.5px solid #E8E5DD; border-radius:6px; padding:8px 11px;
         font-family:var(--font-primary); transition:border-color .15s; box-sizing:border-box; }
.mj-ta:focus { outline:none; border-color:#0E0E0C; }
.mj-ta::placeholder { color:#C6C2BB; }
.mj-ob  { display:flex; gap:12px; margin-top:10px; flex-wrap:wrap; }
.mj-og  { display:flex; flex-direction:column; gap:4px; }
.mj-ol  { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; }
.mj-br  { display:flex; gap:3px; flex-wrap:wrap; }
.mj-ob-btn { padding:3px 10px; font-size:11px; font-weight:600; border-radius:100px;
             border:1.5px solid #E8E5DD; background:#fff; cursor:pointer; color:#57544D;
             font-family:inherit; transition:all .12s; white-space:nowrap; }
.mj-ob-btn:hover { border-color:#0E0E0C; color:#0E0E0C; }
.mj-ob-btn.on.prio-alta   { background:#FEE2E2; color:#B91C1C; border-color:#FECACA; }
.mj-ob-btn.on.prio-media  { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-ob-btn.on.prio-baja   { background:#F1F5F9; color:#475569; border-color:#CBD5E1; }
.mj-ob-btn.on.est-idea        { background:#F1F0EC; color:#57544D; border-color:#D6D2C7; }
.mj-ob-btn.on.est-planeado    { background:#E1E7F2; color:#3F5E9E; border-color:#B3C3E0; }
.mj-ob-btn.on.est-en_progreso { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-ob-btn.on.est-completado  { background:#E3F1E8; color:#1B5A39; border-color:#B7DFCA; }
.mj-ob-btn.on.cpx-facil   { background:#E3F1E8; color:#1B5A39; border-color:#B7DFCA; }
.mj-ob-btn.on.cpx-media   { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-ob-btn.on.cpx-dificil { background:#F4DEDB; color:#6E211B; border-color:#F4DEDB; }
.mj-save-lbl { font-size:10px; color:#94a3b8; min-height:14px; margin-top:3px; }

/* ── Calendario ── */
.cal-nav  { display:flex; align-items:center; justify-content:space-between; padding:12px 16px 8px;
            border-bottom:1px solid var(--color-border); }
.cal-month{ font-size:13px; font-weight:700; color:#0E0E0C; }
.cal-btn  { width:28px; height:28px; border:none; background:transparent; border-radius:5px;
            cursor:pointer; display:flex; align-items:center; justify-content:center;
            color:#57544D; transition:background .12s; }
.cal-btn:hover { background:#EFECE5; }
.cal-grid { display:grid; grid-template-columns:repeat(7,1fr); padding:8px 10px 12px; gap:2px; }
.cal-dow  { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
            color:#94a3b8; text-align:center; padding:4px 0; }
.cal-day  { border-radius:6px; padding:4px 2px; min-height:42px; cursor:pointer;
            transition:background .12s; display:flex; flex-direction:column; align-items:center; gap:2px; }
.cal-day:hover { background:#F1F0EC; }
.cal-day.other-month .cal-dn { color:#D6D2C7; }
.cal-day.today .cal-dn { background:#0E0E0C; color:#fff; border-radius:50%; width:22px; height:22px;
                         display:flex; align-items:center; justify-content:center; }
.cal-day.selected { background:#EFECE5; }
.cal-day.has-items { }
.cal-dn   { font-size:11px; font-weight:600; color:#0E0E0C; min-width:22px; text-align:center; line-height:22px; }
.cal-dots { display:flex; gap:2px; flex-wrap:wrap; justify-content:center; }
.cal-dot  { width:5px; height:5px; border-radius:50%; }

/* ── Próximos ── */
.prox-item { display:flex; align-items:center; gap:10px; padding:8px 0;
             border-bottom:1px solid #F5F4F1; }
.prox-item:last-child { border-bottom:none; }
.prox-date-box { width:38px; height:38px; border-radius:6px; background:#EFECE5;
                 display:flex; flex-direction:column; align-items:center; justify-content:center;
                 flex-shrink:0; }
.prox-day  { font-size:14px; font-weight:800; color:#0E0E0C; line-height:1; }
.prox-mon  { font-size:9px; font-weight:700; color:#8A867C; text-transform:uppercase; }
.prox-venc { font-size:10px; font-weight:700; color:#ef4444; }

/* ── Modal nueva ── */
#mjModal .modal { max-width:520px; }

/* Responsive */
@media(max-width:960px) {
    .mj-stats { grid-template-columns:repeat(2,1fr); }
    .mj-main  { grid-template-columns:1fr; }
}
</style>

<!-- Stats -->
<div class="mj-stats">
    <div class="mj-sc">
        <div class="mj-sc-icon" style="background:#F1F0EC">
            <svg width="20" height="20" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        </div>
        <div>
            <div class="mj-sc-num" id="st-idea">0</div>
            <div class="mj-sc-lbl">Ideas</div>
        </div>
    </div>
    <div class="mj-sc">
        <div class="mj-sc-icon" style="background:#E1E7F2">
            <svg width="20" height="20" fill="none" stroke="#3F5E9E" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
            <div class="mj-sc-num" id="st-planeado">0</div>
            <div class="mj-sc-lbl">Planeado</div>
        </div>
    </div>
    <div class="mj-sc">
        <div class="mj-sc-icon" style="background:#FEF3C7">
            <svg width="20" height="20" fill="none" stroke="#92400E" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
            <div class="mj-sc-num" id="st-en_progreso">0</div>
            <div class="mj-sc-lbl">En progreso</div>
        </div>
    </div>
    <div class="mj-sc">
        <div class="mj-sc-icon" style="background:#E3F1E8">
            <svg width="20" height="20" fill="none" stroke="#1B5A39" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="mj-sc-num" id="st-completado">0</div>
            <div class="mj-sc-lbl">Completado</div>
        </div>
    </div>
</div>

<!-- Main grid -->
<div class="mj-main">

    <!-- ── Columna izquierda: Lista ── -->
    <div class="card animate-fade-up" style="overflow:hidden">
        <div class="mj-list-head">
            <div>
                <h3 class="card-title" style="margin:0">Lista de mejoras</h3>
                <p style="font-size:11px;color:#8A867C;margin:2px 0 0" id="mjSubtitle">Cargando...</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:100px;height:5px;background:#F1F0EC;border-radius:100px;overflow:hidden">
                    <div id="mjBar" style="height:100%;background:#0E0E0C;border-radius:100px;width:0%;transition:width .4s ease"></div>
                </div>
                <span id="mjPct" style="font-size:11px;font-weight:800;color:#0E0E0C;min-width:30px">0%</span>
                <button class="btn btn-secondary sm" onclick="openMjModal()">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nueva
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="mj-filters">
            <div style="position:relative;flex:1;min-width:140px">
                <svg style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" class="form-input" id="mjSearch" placeholder="Buscar..."
                       style="padding-left:27px;height:30px;font-size:12px" oninput="render()">
            </div>
            <select class="form-select" id="fEstado" onchange="render()" style="width:136px">
                <option value="">Todos los estados</option>
                <option value="idea">Idea</option>
                <option value="planeado">Planeado</option>
                <option value="en_progreso">En progreso</option>
                <option value="completado">Completado</option>
            </select>
            <select class="form-select" id="fPrio" onchange="render()" style="width:118px">
                <option value="">Toda prioridad</option>
                <option value="alta">Alta</option>
                <option value="media">Media</option>
                <option value="baja">Baja</option>
            </select>
            <select class="form-select" id="fCat" onchange="render()" style="width:136px">
                <option value="">Toda categoría</option>
                <option value="funcionalidad">Funcionalidad</option>
                <option value="diseño">Diseño</option>
                <option value="bug">Bug</option>
                <option value="rendimiento">Rendimiento</option>
                <option value="otro">Otro</option>
            </select>
            <button id="btnClearDate" onclick="clearDateFilter()"
                style="display:none;padding:0 10px;height:30px;font-size:11px;font-weight:700;
                       background:#EFECE5;border:none;border-radius:5px;cursor:pointer;
                       color:#57544D;white-space:nowrap;font-family:inherit">
                × Limpiar fecha
            </button>
        </div>

        <!-- Items -->
        <div id="mjList"></div>
    </div>

    <!-- ── Columna derecha ── -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Calendario -->
        <div class="card animate-fade-up stagger-1" style="overflow:hidden">
            <div class="cal-nav">
                <button class="cal-btn" onclick="calNav(-1)">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <span class="cal-month" id="calTitle">—</span>
                <button class="cal-btn" onclick="calNav(1)">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            <div class="cal-grid" id="calGrid"></div>
        </div>

        <!-- Próximos vencimientos -->
        <div class="card animate-fade-up stagger-2" style="overflow:hidden">
            <div class="card-header" style="padding:12px 16px">
                <h3 class="card-title" style="margin:0;font-size:13px">Próximos objetivos</h3>
            </div>
            <div style="padding:4px 16px 8px" id="proxList">
                <p style="font-size:12px;color:#94a3b8;padding:12px 0;text-align:center">Sin fechas asignadas</p>
            </div>
        </div>

        <!-- Resumen por categoría -->
        <div class="card animate-fade-up stagger-3" style="overflow:hidden">
            <div class="card-header" style="padding:12px 16px">
                <h3 class="card-title" style="margin:0;font-size:13px">Por categoría</h3>
            </div>
            <div style="padding:10px 16px 14px" id="catChart"></div>
        </div>

    </div><!-- /.right -->
</div><!-- /.mj-main -->

<!-- Modal Nueva Mejora -->
<div class="modal-overlay" id="mjModal" onclick="if(event.target===this)closeMjModal()">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3 class="modal-title">Nueva mejora</h3>
            <button class="modal-close" onclick="closeMjModal()">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="mjForm" onsubmit="agregarMejora(event)" autocomplete="off">
                <div class="form-group">
                    <label class="form-label">¿Qué quieres mejorar? *</label>
                    <input type="text" id="mjTexto" class="form-input" placeholder="Describe brevemente la mejora..." required maxlength="600">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Categoría</label>
                        <select id="mjCat" class="form-select">
                            <option value="funcionalidad">Funcionalidad</option>
                            <option value="diseño">Diseño</option>
                            <option value="bug">Bug</option>
                            <option value="rendimiento">Rendimiento</option>
                            <option value="otro" selected>Otro</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Prioridad</label>
                        <select id="mjPrio" class="form-select">
                            <option value="alta">🔴 Alta</option>
                            <option value="media" selected>🟡 Media</option>
                            <option value="baja">⚪ Baja</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Complejidad</label>
                        <select id="mjCpx" class="form-select">
                            <option value="facil">✅ Fácil</option>
                            <option value="media" selected>🔧 Media</option>
                            <option value="dificil">⚡ Difícil</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Estado inicial</label>
                        <select id="mjEst" class="form-select">
                            <option value="idea" selected>Idea</option>
                            <option value="planeado">Planeado</option>
                            <option value="en_progreso">En progreso</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Fecha objetivo</label>
                        <input type="date" id="mjFecha" class="form-input">
                    </div>
                </div>
                <div class="form-group" style="margin-top:12px;margin-bottom:0">
                    <label class="form-label">Notas iniciales <span style="font-weight:400;color:#94a3b8">(opcional)</span></label>
                    <textarea id="mjNotasInicial" class="form-textarea" rows="2"
                        placeholder="¿Cómo podrías implementarlo? ¿Qué habría que revisar?"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeMjModal()">Cancelar</button>
            <button class="btn btn-secondary" onclick="document.getElementById('mjForm').requestSubmit()">Agregar mejora</button>
        </div>
    </div>
</div>

<script>
let _mj       = [];
let _open     = new Set();
let _editing  = null;
let _saveTmr  = {};
let _calY, _calM, _selDate = null;

const CAT_LBL  = { diseño:'Diseño', funcionalidad:'Funcionalidad', bug:'Bug', rendimiento:'Rendimiento', otro:'Otro' };
const EST_LBL  = { idea:'Idea', planeado:'Planeado', en_progreso:'En progreso', completado:'Completado' };
const PRIO_LBL = { alta:'Alta', media:'Media', baja:'Baja' };
const CPX_LBL  = { facil:'Fácil', media:'Media', dificil:'Difícil' };
const MESES    = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const MESES_S  = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtD(d){ return d ? new Date(d+'T00:00:00').toLocaleDateString('es-CO',{day:'numeric',month:'short',year:'numeric'}) : ''; }
function toISO(d){ /* date -> YYYY-MM-DD */ if(!d) return null; const dt=new Date(d+'T00:00:00'); return dt.toISOString().slice(0,10); }

async function loadMejoras() {
    const r = await fetch('api/mejoras.php');
    const d = await r.json();
    if (d.success) { _mj = d.data; render(); renderCal(); renderProx(); renderCatChart(); }
}

/* ─────────── RENDER LISTA ─────────── */
function render() {
    const cnt = {idea:0,planeado:0,en_progreso:0,completado:0};
    _mj.forEach(m => { if(cnt[m.estado]!==undefined) cnt[m.estado]++; });
    Object.entries(cnt).forEach(([k,v])=>{ const el=document.getElementById('st-'+k); if(el) el.textContent=v; });
    const total=_mj.length, done=cnt.completado;
    const pct=total?Math.round(done/total*100):0;
    document.getElementById('mjBar').style.width=pct+'%';
    document.getElementById('mjPct').textContent=pct+'%';
    document.getElementById('mjSubtitle').textContent=
        total ? `${done} de ${total} completadas · ${cnt.en_progreso} en progreso` : 'Sin mejoras aún';

    const q   = (document.getElementById('mjSearch').value||'').toLowerCase();
    const fEs = document.getElementById('fEstado').value;
    const fPr = document.getElementById('fPrio').value;
    const fCt = document.getElementById('fCat').value;

    let lista = _mj.filter(m =>
        (!fEs || m.estado===fEs) &&
        (!fPr || m.prioridad===fPr) &&
        (!fCt || m.categoria===fCt) &&
        (!q   || m.texto.toLowerCase().includes(q)||(m.notas||'').toLowerCase().includes(q))
    );
    if (_selDate) {
        lista = lista.filter(m => m.fecha_objetivo && m.fecha_objetivo.slice(0,10)===_selDate);
    }

    const box = document.getElementById('mjList');
    if (!lista.length) {
        box.innerHTML=`<div style="text-align:center;padding:48px 0;color:#94a3b8">
            <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3"
                 style="display:block;margin:0 auto 10px;opacity:.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <div style="font-size:13px;font-weight:600">Sin resultados</div>
            <div style="font-size:12px;margin-top:3px">Ajusta los filtros o agrega una nueva mejora.</div>
        </div>`;
        return;
    }

    box.innerHTML = lista.map(m => {
        const isOpen = _open.has(m.id);
        const isDone = m.estado==='completado';
        const hasTxt = !!(m.notas&&m.notas.trim());
        const hasDate = !!m.fecha_objetivo;
        const today = new Date().toISOString().slice(0,10);
        const isVenc = hasDate && m.fecha_objetivo.slice(0,10) < today && !isDone;

        const prioOpts=['alta','media','baja'].map(p=>
            `<button type="button" class="mj-ob-btn prio-${p}${m.prioridad===p?' on':''}"
                onclick="event.stopPropagation();setF(${m.id},'prioridad','${p}')">${PRIO_LBL[p]}</button>`).join('');
        const estOpts=['idea','planeado','en_progreso','completado'].map(e=>
            `<button type="button" class="mj-ob-btn est-${e}${m.estado===e?' on':''}"
                onclick="event.stopPropagation();setF(${m.id},'estado','${e}')">${EST_LBL[e]}</button>`).join('');
        const cpxOpts=['facil','media','dificil'].map(c=>
            `<button type="button" class="mj-ob-btn cpx-${c}${(m.complejidad||'media')===c?' on':''}"
                onclick="event.stopPropagation();setF(${m.id},'complejidad','${c}')">${CPX_LBL[c]}</button>`).join('');

        return `
        <div id="mj-${m.id}">
          <div class="mj-row${isOpen?' open':''}" onclick="toggleOpen(${m.id})">
            <div class="mj-dot prio-${m.prioridad||'media'}"></div>
            <span class="mj-row-title${isDone?' done':''}" id="tit-${m.id}">${esc(m.texto)}</span>
            <div class="mj-row-right">
                ${isVenc?`<span style="font-size:9px;font-weight:700;color:#ef4444;background:#FEE2E2;padding:1px 6px;border-radius:100px;white-space:nowrap">VENCIDA</span>`:''}
                ${hasDate&&!isVenc?`<span style="font-size:9px;color:#8A867C;white-space:nowrap">${fmtD(m.fecha_objetivo)}</span>`:''}
                ${hasTxt?`<svg width="11" height="11" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`:''}
                <span class="mj-pill cat-${m.categoria}">${CAT_LBL[m.categoria]||m.categoria}</span>
                <span class="mj-pill est-${m.estado}">${EST_LBL[m.estado]||m.estado}</span>
                <button class="mj-ico edit" title="Editar título" onclick="event.stopPropagation();startEdit(${m.id})">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <div class="mj-ico" style="opacity:.35">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"
                         style="transition:transform .2s;transform:rotate(${isOpen?'180':'0'}deg)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
          </div>
          <div class="mj-body${isOpen?' open':''}">
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:5px">Notas de implementación</div>
            <textarea class="mj-ta" id="ta-${m.id}"
                placeholder="¿Cómo implementarlo? ¿Qué falta investigar? ¿Referencias o ideas?"
                oninput="schedSave(${m.id})" onclick="event.stopPropagation()">${esc(m.notas||'')}</textarea>
            <div class="mj-save-lbl" id="sv-${m.id}"></div>
            <div class="mj-ob">
                <div class="mj-og"><div class="mj-ol">Estado</div><div class="mj-br">${estOpts}</div></div>
                <div class="mj-og"><div class="mj-ol">Prioridad</div><div class="mj-br">${prioOpts}</div></div>
                <div class="mj-og"><div class="mj-ol">Complejidad</div><div class="mj-br">${cpxOpts}</div></div>
                <div class="mj-og">
                    <div class="mj-ol">Fecha objetivo</div>
                    <input type="date" value="${m.fecha_objetivo?m.fecha_objetivo.slice(0,10):''}"
                           style="height:28px;font-size:11px;border:1.5px solid #E8E5DD;border-radius:5px;padding:0 8px;font-family:inherit;background:#fff;cursor:pointer"
                           onchange="event.stopPropagation();setF(${m.id},'fecha_objetivo',this.value||null)"
                           onclick="event.stopPropagation()">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #E8E5DD">
                <span style="font-size:10px;color:#94a3b8">Creado ${fmtD(m.created_at)}${m.completada_at?' · Completado '+fmtD(m.completada_at):''}</span>
                <button class="mj-ico del" style="opacity:1;color:#FECACA;width:auto;padding:0 8px;gap:4px;font-size:11px;font-weight:700"
                    onclick="event.stopPropagation();delMejora(${m.id})">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Eliminar
                </button>
            </div>
          </div>
        </div>`;
    }).join('');
}

/* ─────────── CALENDARIO ─────────── */
function renderCal() {
    const now = new Date();
    if (_calY===undefined) { _calY=now.getFullYear(); _calM=now.getMonth(); }
    document.getElementById('calTitle').textContent = MESES[_calM] + ' ' + _calY;

    // Mapear fechas con mejoras
    const dateMap = {};
    _mj.forEach(m => {
        if (!m.fecha_objetivo) return;
        const k = m.fecha_objetivo.slice(0,10);
        if (!dateMap[k]) dateMap[k] = [];
        dateMap[k].push(m.prioridad||'media');
    });

    const firstDay = new Date(_calY, _calM, 1).getDay(); // 0=dom
    const startOff = (firstDay+6)%7; // semana lun-dom
    const daysInMonth = new Date(_calY, _calM+1, 0).getDate();
    const todayISO = now.toISOString().slice(0,10);

    const DOWS = ['L','M','X','J','V','S','D'];
    let html = DOWS.map(d=>`<div class="cal-dow">${d}</div>`).join('');

    // Días mes anterior
    const prevDays = new Date(_calY,_calM,0).getDate();
    for (let i=startOff-1;i>=0;i--) {
        html += `<div class="cal-day other-month"><div class="cal-dn">${prevDays-i}</div></div>`;
    }
    // Días del mes
    for (let d=1;d<=daysInMonth;d++) {
        const iso = `${_calY}-${String(_calM+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const isToday = iso===todayISO;
        const isSel   = iso===_selDate;
        const hasMj   = dateMap[iso];
        const dotsHtml = hasMj ? '<div class="cal-dots">' +
            hasMj.slice(0,4).map(p=>`<div class="cal-dot prio-${p}"></div>`).join('') +
            '</div>' : '';
        html += `<div class="cal-day${isToday?' today':''}${isSel?' selected':''}"
                     onclick="selectDate('${iso}')">
                    <div class="cal-dn">${d}</div>
                    ${dotsHtml}
                 </div>`;
    }
    // Días mes siguiente
    const totalCells = Math.ceil((startOff+daysInMonth)/7)*7;
    for (let d=1; d<=totalCells-(startOff+daysInMonth); d++) {
        html += `<div class="cal-day other-month"><div class="cal-dn">${d}</div></div>`;
    }

    document.getElementById('calGrid').innerHTML = html;
}

function calNav(dir) {
    _calM += dir;
    if (_calM>11){_calM=0;_calY++;}
    if (_calM<0) {_calM=11;_calY--;}
    renderCal();
}

function selectDate(iso) {
    if (_selDate===iso) { _selDate=null; document.getElementById('btnClearDate').style.display='none'; }
    else { _selDate=iso; document.getElementById('btnClearDate').style.display='inline-flex'; }
    renderCal();
    render();
}
function clearDateFilter() { _selDate=null; document.getElementById('btnClearDate').style.display='none'; renderCal(); render(); }

/* ─────────── PRÓXIMOS ─────────── */
function renderProx() {
    const today = new Date().toISOString().slice(0,10);
    const in30  = new Date(Date.now()+30*864e5).toISOString().slice(0,10);
    const items = _mj
        .filter(m => m.fecha_objetivo && m.fecha_objetivo.slice(0,10)>=today && m.fecha_objetivo.slice(0,10)<=in30 && m.estado!=='completado')
        .sort((a,b)=>a.fecha_objetivo.localeCompare(b.fecha_objetivo))
        .slice(0,6);
    const box = document.getElementById('proxList');
    if (!items.length) {
        box.innerHTML='<p style="font-size:12px;color:#94a3b8;padding:12px 0;text-align:center">Sin objetivos en los próximos 30 días</p>';
        return;
    }
    box.innerHTML = items.map(m=>{
        const dt = new Date(m.fecha_objetivo+'T00:00:00');
        const diff = Math.ceil((dt-new Date().setHours(0,0,0,0))/864e5);
        const urgente = diff<=3;
        return `<div class="prox-item">
            <div class="prox-date-box" style="background:${urgente?'#FEE2E2':'#EFECE5'}">
                <div class="prox-day" style="color:${urgente?'#B91C1C':'#0E0E0C'}">${dt.getDate()}</div>
                <div class="prox-mon" style="color:${urgente?'#B91C1C':'#8A867C'}">${MESES_S[dt.getMonth()]}</div>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:12px;font-weight:600;color:var(--color-text);line-height:1.3;
                            overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(m.texto)}</div>
                <div style="font-size:10px;color:#8A867C;margin-top:2px">${diff===0?'Hoy':diff===1?'Mañana':'En '+diff+' días'}</div>
            </div>
            <div class="mj-dot prio-${m.prioridad||'media'}"></div>
        </div>`;
    }).join('');
}

/* ─────────── GRÁFICO CATEGORÍAS ─────────── */
function renderCatChart() {
    const cats = ['funcionalidad','diseño','bug','rendimiento','otro'];
    const total = _mj.length || 1;
    const counts = {};
    cats.forEach(c=>counts[c]=0);
    _mj.filter(m=>m.estado!=='completado').forEach(m=>{ if(counts[m.categoria]!==undefined) counts[m.categoria]++; });
    const colors = { funcionalidad:'#1B5A39', diseño:'#3F5E9E', bug:'#6E211B', rendimiento:'#92400E', otro:'#57544D' };
    const bgs    = { funcionalidad:'#E3F1E8', diseño:'#E1E7F2', bug:'#F4DEDB', rendimiento:'#FEF3C7', otro:'#EFECE5' };

    document.getElementById('catChart').innerHTML = cats.map(c=>{
        const n = counts[c]; if(!n) return '';
        const pct = Math.round(n/(_mj.filter(m=>m.estado!=='completado').length||1)*100);
        return `<div style="margin-bottom:8px">
            <div style="display:flex;justify-content:space-between;margin-bottom:3px">
                <span style="font-size:11px;font-weight:600;color:${colors[c]}">${CAT_LBL[c]}</span>
                <span style="font-size:11px;font-weight:700;color:#0E0E0C">${n}</span>
            </div>
            <div style="height:6px;background:#F1F0EC;border-radius:100px;overflow:hidden">
                <div style="height:100%;width:${pct}%;background:${colors[c]};border-radius:100px;transition:width .4s ease"></div>
            </div>
        </div>`;
    }).join('');
}

/* ─────────── INTERACCIONES ─────────── */
function toggleOpen(id) {
    if (_editing===id) return;
    if(_open.has(id)) _open.delete(id); else _open.add(id);
    render();
    if(_open.has(id)) setTimeout(()=>{ const ta=document.getElementById('ta-'+id); if(ta) ta.focus(); },40);
}

function startEdit(id) {
    const m=_mj.find(x=>x.id==id); if(!m) return;
    _editing=id;
    const span=document.getElementById('tit-'+id); if(!span) return;
    const inp=document.createElement('input');
    inp.className='mj-edit-input';
    inp.value=m.texto;
    inp.onclick=e=>e.stopPropagation();
    inp.onkeydown=e=>{ if(e.key==='Enter'){e.preventDefault();commitEdit(id,inp.value);} if(e.key==='Escape'){_editing=null;render();} };
    inp.onblur=()=>commitEdit(id,inp.value);
    span.replaceWith(inp); inp.focus(); inp.select();
}
async function commitEdit(id,val) {
    _editing=null; val=val.trim();
    if(!val){render();return;}
    const m=_mj.find(x=>x.id==id);
    if(!m||m.texto===val){render();return;}
    m.texto=val; render();
    await fetch('api/mejoras.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,texto:val})});
}

async function setF(id,field,value) {
    const m=_mj.find(x=>x.id==id); if(!m) return;
    m[field]=value; render(); renderCal(); renderProx(); renderCatChart();
    await fetch('api/mejoras.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,[field]:value})});
}

function schedSave(id) {
    const el=document.getElementById('sv-'+id);
    if(el) el.textContent='Guardando...';
    clearTimeout(_saveTmr[id]);
    _saveTmr[id]=setTimeout(async()=>{
        const ta=document.getElementById('ta-'+id); if(!ta) return;
        const notas=ta.value;
        const m=_mj.find(x=>x.id==id); if(m) m.notas=notas;
        await fetch('api/mejoras.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,notas})});
        if(el){el.textContent='Guardado ✓';setTimeout(()=>{if(el)el.textContent='';},2000);}
    },900);
}

async function agregarMejora(e) {
    e.preventDefault();
    const texto=document.getElementById('mjTexto').value.trim(); if(!texto) return;
    const payload={
        texto,
        categoria:   document.getElementById('mjCat').value,
        prioridad:   document.getElementById('mjPrio').value,
        complejidad: document.getElementById('mjCpx').value,
        estado:      document.getElementById('mjEst').value,
        fecha_objetivo: document.getElementById('mjFecha').value||null,
        notas:       document.getElementById('mjNotasInicial').value.trim()||null,
    };
    const r=await fetch('api/mejoras.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const d=await r.json();
    if(d.success){
        closeMjModal();
        _mj.unshift({id:d.id,...payload,completada:0,created_at:new Date().toISOString()});
        _open.add(d.id);
        render(); renderCal(); renderProx(); renderCatChart();
        document.getElementById('mjForm').reset();
        document.getElementById('mjNotasInicial').value='';
    }
}

async function delMejora(id) {
    const ok=await confirmAction('Se eliminará esta mejora permanentemente.',{title:'¿Eliminar mejora?'});
    if(!ok) return;
    const r=await fetch(`api/mejoras.php?id=${id}`,{method:'DELETE'});
    const d=await r.json();
    if(d.success){_mj=_mj.filter(m=>m.id!=id);_open.delete(id);render();renderCal();renderProx();renderCatChart();}
}

function openMjModal()  { document.getElementById('mjModal').classList.add('show'); setTimeout(()=>document.getElementById('mjTexto').focus(),80); }
function closeMjModal() { document.getElementById('mjModal').classList.remove('show'); }

loadMejoras();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
