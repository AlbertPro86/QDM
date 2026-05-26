<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle = 'Mejoras';
include __DIR__ . '/includes/header.php';
?>

<style>
.mj-wrap { max-width: 780px; }

/* ── Píldoras de estado inline ── */
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

/* ── Punto de prioridad ── */
.mj-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.prio-alta  { background:#ef4444; }
.prio-media { background:#f59e0b; }
.prio-baja  { background:#cbd5e1; }

/* ── Fila de ítem ── */
.mj-row { display:flex; align-items:center; gap:10px; padding:10px 14px;
          border-bottom:1px solid #F1F0EC; transition:background .12s; cursor:pointer; }
.mj-row:last-child { border-bottom:none; }
.mj-row:hover { background:#FAFAF7; }
.mj-row.open  { background:#FAFAF7; }

.mj-title { flex:1; font-size:13px; font-weight:500; color:var(--color-text);
            line-height:1.4; min-width:0; word-break:break-word; }
.mj-title.done { text-decoration:line-through; color:#94a3b8; }

.mj-edit-input { flex:1; font-size:13px; font-weight:500; color:var(--color-text);
                 border:1.5px solid #0E0E0C; border-radius:5px; padding:3px 8px;
                 font-family:inherit; outline:none; background:#fff; }

.mj-row-actions { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.mj-icon-btn { width:26px; height:26px; display:flex; align-items:center; justify-content:center;
               border:none; background:transparent; border-radius:4px; cursor:pointer;
               color:#C6C2BB; transition:all .15s; flex-shrink:0; }
.mj-icon-btn:hover { background:#F1F0EC; color:#57544D; }
.mj-icon-btn.del:hover { background:#FEE2E2; color:#ef4444; }
.mj-icon-btn.edit-btn { opacity:0; }
.mj-row:hover .mj-icon-btn.edit-btn { opacity:1; }

/* ── Panel expandido ── */
.mj-body { display:none; padding:12px 14px 14px 32px; border-bottom:1px solid #F1F0EC;
           background:#FAFAF7; }
.mj-body.open { display:block; }

.mj-notas-ta { width:100%; min-height:72px; resize:vertical; font-size:12px;
               line-height:1.55; color:var(--color-text); background:#fff;
               border:1.5px solid #E8E5DD; border-radius:6px; padding:9px 11px;
               font-family:var(--font-primary); transition:border-color .15s;
               box-sizing:border-box; }
.mj-notas-ta:focus { outline:none; border-color:#0E0E0C; }
.mj-notas-ta::placeholder { color:#C6C2BB; font-size:12px; }

.mj-opts { display:flex; gap:14px; margin-top:10px; flex-wrap:wrap; }
.mj-opt-group { display:flex; flex-direction:column; gap:4px; }
.mj-opt-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; }
.mj-btn-row { display:flex; gap:3px; }
.mj-opt-btn { padding:3px 10px; font-size:11px; font-weight:600; border-radius:100px;
              border:1.5px solid #E8E5DD; background:#fff; cursor:pointer; color:#57544D;
              font-family:inherit; transition:all .12s; white-space:nowrap; }
.mj-opt-btn:hover { border-color:#0E0E0C; color:#0E0E0C; }
.mj-opt-btn.on.prio-alta  { background:#FEE2E2; color:#B91C1C; border-color:#FECACA; }
.mj-opt-btn.on.prio-media { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-opt-btn.on.prio-baja  { background:#F1F5F9; color:#475569; border-color:#CBD5E1; }
.mj-opt-btn.on.est-idea        { background:#F1F0EC; color:#57544D; border-color:#D6D2C7; }
.mj-opt-btn.on.est-planeado    { background:#E1E7F2; color:#3F5E9E; border-color:#B3C3E0; }
.mj-opt-btn.on.est-en_progreso { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-opt-btn.on.est-completado  { background:#E3F1E8; color:#1B5A39; border-color:#B7DFCA; }
.mj-opt-btn.on.cpx-facil  { background:#E3F1E8; color:#1B5A39; border-color:#B7DFCA; }
.mj-opt-btn.on.cpx-media  { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-opt-btn.on.cpx-dificil{ background:#F4DEDB; color:#6E211B; border-color:#F4DEDB; }

.mj-saving { font-size:10px; color:#94a3b8; margin-top:3px; min-height:14px; }

/* ── Modal agregar ── */
#mjModal .modal { max-width:520px; }
</style>

<div class="mj-wrap animate-fade-up">
<div class="card" style="overflow:hidden">

    <!-- Cabecera de la card -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--color-border);flex-wrap:wrap;gap:10px">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
            <h3 class="card-title" style="margin:0">Mejoras</h3>
            <!-- Mini stats -->
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="display:flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#8A867C">
                    <span style="width:7px;height:7px;border-radius:50%;background:#8A867C;display:inline-block"></span>
                    <span id="st-idea">0</span> Ideas
                </span>
                <span style="color:#E8E5DD;font-size:10px">·</span>
                <span style="display:flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#3F5E9E">
                    <span style="width:7px;height:7px;border-radius:50%;background:#3F5E9E;display:inline-block"></span>
                    <span id="st-planeado">0</span> Planeado
                </span>
                <span style="color:#E8E5DD;font-size:10px">·</span>
                <span style="display:flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#92400E">
                    <span style="width:7px;height:7px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
                    <span id="st-en_progreso">0</span> En progreso
                </span>
                <span style="color:#E8E5DD;font-size:10px">·</span>
                <span style="display:flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#1B5A39">
                    <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block"></span>
                    <span id="st-completado">0</span> Completado
                </span>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <!-- Barra de progreso compacta -->
            <div style="display:flex;align-items:center;gap:6px">
                <div style="width:80px;height:4px;background:#F1F0EC;border-radius:100px;overflow:hidden">
                    <div id="mjBar" style="height:100%;background:#0E0E0C;border-radius:100px;width:0%;transition:width .4s ease"></div>
                </div>
                <span id="mjPct" style="font-size:11px;font-weight:700;color:#0E0E0C;min-width:28px">0%</span>
            </div>
            <button class="btn btn-secondary sm" onclick="openMjModal()" style="white-space:nowrap">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nueva
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div style="display:flex;gap:8px;padding:10px 14px;border-bottom:1px solid #F1F0EC;flex-wrap:wrap;align-items:center;background:#FAFAF7">
        <div style="position:relative;flex:1;min-width:150px">
            <svg style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="form-input" id="mjSearch" placeholder="Buscar..."
                   style="height:30px;font-size:12px;padding-left:28px"
                   oninput="render()">
        </div>
        <select class="form-select" id="fEstado" onchange="render()" style="height:30px;font-size:12px;width:138px">
            <option value="">Todos los estados</option>
            <option value="idea">Idea</option>
            <option value="planeado">Planeado</option>
            <option value="en_progreso">En progreso</option>
            <option value="completado">Completado</option>
        </select>
        <select class="form-select" id="fPrio" onchange="render()" style="height:30px;font-size:12px;width:120px">
            <option value="">Toda prioridad</option>
            <option value="alta">Alta</option>
            <option value="media">Media</option>
            <option value="baja">Baja</option>
        </select>
        <select class="form-select" id="fCat" onchange="render()" style="height:30px;font-size:12px;width:138px">
            <option value="">Toda categoría</option>
            <option value="funcionalidad">Funcionalidad</option>
            <option value="diseño">Diseño</option>
            <option value="bug">Bug</option>
            <option value="rendimiento">Rendimiento</option>
            <option value="otro">Otro</option>
        </select>
    </div>

    <!-- Lista -->
    <div id="mjList"></div>

</div><!-- /.card -->
</div><!-- /.mj-wrap -->

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
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
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
                <div class="form-group" style="margin-top:14px;margin-bottom:0">
                    <label class="form-label">Notas iniciales <span style="font-weight:400;color:#94a3b8">(opcional)</span></label>
                    <textarea id="mjNotasInicial" class="form-textarea" rows="3"
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
let _mj      = [];
let _open    = new Set();
let _editing = null;
let _saveTmr = {};

const CAT_LBL  = { diseño:'Diseño', funcionalidad:'Funcionalidad', bug:'Bug', rendimiento:'Rendimiento', otro:'Otro' };
const EST_LBL  = { idea:'Idea', planeado:'Planeado', en_progreso:'En progreso', completado:'Completado' };
const PRIO_LBL = { alta:'Alta', media:'Media', baja:'Baja' };
const CPX_LBL  = { facil:'Fácil', media:'Media', dificil:'Difícil' };

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtD(d){ return d ? new Date(d).toLocaleDateString('es-CO',{day:'numeric',month:'short',year:'numeric'}) : ''; }

async function loadMejoras() {
    const r = await fetch('api/mejoras.php');
    const d = await r.json();
    if (d.success) { _mj = d.data; render(); }
}

function render() {
    // Stats
    const cnt = {idea:0,planeado:0,en_progreso:0,completado:0};
    _mj.forEach(m => { if(cnt[m.estado]!==undefined) cnt[m.estado]++; });
    Object.entries(cnt).forEach(([k,v])=>{ const el=document.getElementById('st-'+k); if(el) el.textContent=v; });
    const total = _mj.length, done = cnt.completado;
    const pct   = total ? Math.round(done/total*100) : 0;
    document.getElementById('mjBar').style.width = pct+'%';
    document.getElementById('mjPct').textContent = pct+'%';

    // Filtros
    const q    = (document.getElementById('mjSearch').value||'').toLowerCase();
    const fEst = document.getElementById('fEstado').value;
    const fPr  = document.getElementById('fPrio').value;
    const fCt  = document.getElementById('fCat').value;
    const lista = _mj.filter(m =>
        (!fEst || m.estado===fEst) &&
        (!fPr  || m.prioridad===fPr) &&
        (!fCt  || m.categoria===fCt) &&
        (!q    || m.texto.toLowerCase().includes(q) || (m.notas||'').toLowerCase().includes(q))
    );

    const box = document.getElementById('mjList');
    if (!lista.length) {
        box.innerHTML = `<div style="text-align:center;padding:48px 0;color:var(--color-text-muted)">
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
        const isDone = m.estado === 'completado';
        const hasTxt = !!(m.notas && m.notas.trim());

        const prioOpts = ['alta','media','baja'].map(p=>
            `<button type="button" class="mj-opt-btn prio-${p}${m.prioridad===p?' on':''}"
                onclick="event.stopPropagation();setF(${m.id},'prioridad','${p}')">${PRIO_LBL[p]}</button>`).join('');
        const estOpts = ['idea','planeado','en_progreso','completado'].map(e=>
            `<button type="button" class="mj-opt-btn est-${e}${m.estado===e?' on':''}"
                onclick="event.stopPropagation();setF(${m.id},'estado','${e}')">${EST_LBL[e]}</button>`).join('');
        const cpxOpts = ['facil','media','dificil'].map(c=>
            `<button type="button" class="mj-opt-btn cpx-${c}${(m.complejidad||'media')===c?' on':''}"
                onclick="event.stopPropagation();setF(${m.id},'complejidad','${c}')">${CPX_LBL[c]}</button>`).join('');

        return `
        <div id="mj-${m.id}">
          <div class="mj-row${isOpen?' open':''}" onclick="toggleOpen(${m.id})">
            <div class="mj-dot prio-${m.prioridad||'media'}"></div>
            <span class="mj-title${isDone?' done':''}" id="title-${m.id}">${esc(m.texto)}</span>
            <div class="mj-row-actions">
                ${hasTxt ? `<svg width="11" height="11" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>` : ''}
                <span class="mj-pill cat-${m.categoria}">${CAT_LBL[m.categoria]||m.categoria}</span>
                <span class="mj-pill est-${m.estado}">${EST_LBL[m.estado]||m.estado}</span>
                <button class="mj-icon-btn edit-btn" title="Editar título"
                    onclick="event.stopPropagation();startEdit(${m.id})">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button class="mj-icon-btn" style="opacity:.4;flex-shrink:0" title="${isOpen?'Cerrar':'Expandir'}">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"
                         style="transition:transform .2s;transform:rotate(${isOpen?'180':'0'}deg)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
          </div>
          <div class="mj-body${isOpen?' open':''}">
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:5px">Notas de implementación</div>
            <textarea class="mj-notas-ta" id="ta-${m.id}"
                placeholder="¿Cómo implementarlo? ¿Qué falta investigar? ¿Referencias o ideas?"
                oninput="schedSave(${m.id})" onclick="event.stopPropagation()">${esc(m.notas||'')}</textarea>
            <div class="mj-saving" id="sv-${m.id}"></div>
            <div class="mj-opts">
                <div class="mj-opt-group">
                    <div class="mj-opt-lbl">Estado</div>
                    <div class="mj-btn-row">${estOpts}</div>
                </div>
                <div class="mj-opt-group">
                    <div class="mj-opt-lbl">Prioridad</div>
                    <div class="mj-btn-row">${prioOpts}</div>
                </div>
                <div class="mj-opt-group">
                    <div class="mj-opt-lbl">Complejidad</div>
                    <div class="mj-btn-row">${cpxOpts}</div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:10px;border-top:1px solid #E8E5DD">
                <span style="font-size:10px;color:#94a3b8">Agregado ${fmtD(m.created_at)}${m.completada_at?' · Completado '+fmtD(m.completada_at):''}</span>
                <button class="mj-icon-btn del" onclick="event.stopPropagation();delMejora(${m.id})" title="Eliminar" style="opacity:1;color:#FECACA">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
          </div>
        </div>`;
    }).join('');
}

function toggleOpen(id) {
    if (_editing === id) return;
    if (_open.has(id)) _open.delete(id); else _open.add(id);
    render();
    if (_open.has(id)) setTimeout(()=>{ const ta=document.getElementById('ta-'+id); if(ta) ta.focus(); },40);
}

/* ── Editar título ── */
function startEdit(id) {
    const m = _mj.find(x=>x.id==id);
    if (!m) return;
    _editing = id;
    const span = document.getElementById('title-'+id);
    if (!span) return;
    const inp = document.createElement('input');
    inp.className = 'mj-edit-input';
    inp.value = m.texto;
    inp.onclick = e => e.stopPropagation();
    inp.onkeydown = e => {
        if (e.key==='Enter')  { e.preventDefault(); commitEdit(id, inp.value); }
        if (e.key==='Escape') { _editing=null; render(); }
    };
    inp.onblur = () => commitEdit(id, inp.value);
    span.replaceWith(inp);
    inp.focus(); inp.select();
}

async function commitEdit(id, val) {
    _editing = null;
    val = val.trim();
    if (!val) { render(); return; }
    const m = _mj.find(x=>x.id==id);
    if (!m || m.texto===val) { render(); return; }
    m.texto = val;
    render();
    await fetch('api/mejoras.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,texto:val})});
}

/* ── Cambiar campo ── */
async function setF(id, field, value) {
    const m = _mj.find(x=>x.id==id);
    if (!m) return;
    m[field] = value;
    render();
    await fetch('api/mejoras.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,[field]:value})});
}

/* ── Autoguardado notas ── */
function schedSave(id) {
    const el = document.getElementById('sv-'+id);
    if (el) el.textContent = 'Guardando...';
    clearTimeout(_saveTmr[id]);
    _saveTmr[id] = setTimeout(async()=>{
        const ta = document.getElementById('ta-'+id);
        if (!ta) return;
        const notas = ta.value;
        const m = _mj.find(x=>x.id==id);
        if (m) m.notas = notas;
        await fetch('api/mejoras.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,notas})});
        if (el) { el.textContent='Guardado ✓'; setTimeout(()=>{ if(el) el.textContent=''; },2000); }
    }, 900);
}

/* ── Agregar ── */
async function agregarMejora(e) {
    e.preventDefault();
    const texto = document.getElementById('mjTexto').value.trim();
    if (!texto) return;
    const payload = {
        texto,
        categoria:   document.getElementById('mjCat').value,
        prioridad:   document.getElementById('mjPrio').value,
        complejidad: document.getElementById('mjCpx').value,
        notas:       document.getElementById('mjNotasInicial').value.trim() || null,
        estado:      'idea',
    };
    const r = await fetch('api/mejoras.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const d = await r.json();
    if (d.success) {
        closeMjModal();
        _mj.unshift({id:d.id,...payload,completada:0,created_at:new Date().toISOString()});
        _open.add(d.id);
        render();
        // Limpiar form
        document.getElementById('mjForm').reset();
        document.getElementById('mjNotasInicial').value = '';
    }
}

/* ── Eliminar ── */
async function delMejora(id) {
    const ok = await confirmAction('Se eliminará esta mejora permanentemente.',{title:'¿Eliminar mejora?'});
    if (!ok) return;
    const r = await fetch(`api/mejoras.php?id=${id}`,{method:'DELETE'});
    const d = await r.json();
    if (d.success) { _mj=_mj.filter(m=>m.id!=id); _open.delete(id); render(); }
}

/* ── Modal ── */
function openMjModal()  { document.getElementById('mjModal').classList.add('show'); setTimeout(()=>document.getElementById('mjTexto').focus(),80); }
function closeMjModal() { document.getElementById('mjModal').classList.remove('show'); }

loadMejoras();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
