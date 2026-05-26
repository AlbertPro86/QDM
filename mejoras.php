<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle    = 'Mejoras';
$pageSubtitle = 'Roadmap interno de la plataforma';
include __DIR__ . '/includes/header.php';
?>

<style>
/* ── Layout ── */
.mj-wrap { max-width: 860px; }

/* ── Stats ── */
.mj-stats { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.mj-stat  { flex:1; min-width:110px; padding:12px 16px; background:#fff;
            border:1px solid #E8E5DD; border-radius:6px; text-align:center; }
.mj-stat__num  { font-size:24px; font-weight:800; line-height:1; color:#0E0E0C;
                 font-family:var(--font-secondary); }
.mj-stat__lbl  { font-size:10px; font-weight:700; text-transform:uppercase;
                 letter-spacing:.06em; color:#8A867C; margin-top:4px; }
.mj-stat__dot  { width:8px; height:8px; border-radius:50%; margin:0 auto 6px; }

/* ── Progress ── */
.mj-progress     { height:5px; background:#F1F0EC; border-radius:100px; overflow:hidden; margin-bottom:24px; }
.mj-progress-bar { height:100%; background:linear-gradient(90deg,#C6F24E,#0E0E0C);
                   border-radius:100px; transition:width .5s cubic-bezier(.4,0,.2,1); }

/* ── Add form ── */
.mj-add-card { background:#fff; border:1px solid #E8E5DD; border-radius:8px;
               padding:16px 18px; margin-bottom:16px; }
.mj-add-row  { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
.mj-add-row .form-input,
.mj-add-row .form-select { height:36px; font-size:13px; }
.mj-add-titulo { flex:1; min-width:200px; }
.mj-add-sel    { width:148px; }
.mj-add-sel-sm { width:120px; }

/* ── Filters ── */
.mj-filters { display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap; align-items:center; }
.mj-filters .form-input,
.mj-filters .form-select { height:32px; font-size:12px; padding:0 10px; }
.mj-search  { flex:1; min-width:160px; position:relative; }
.mj-search input { padding-left:30px; }
.mj-search svg   { position:absolute; left:9px; top:50%; transform:translateY(-50%);
                   color:#94a3b8; pointer-events:none; }

/* ── List ── */
.mj-list { display:flex; flex-direction:column; gap:6px; }

/* ── Item ── */
.mj-item { background:#fff; border:1px solid #E8E5DD; border-radius:8px;
           transition:box-shadow .15s, border-color .15s; overflow:hidden; }
.mj-item:hover { border-color:#C6C2BB; box-shadow:0 2px 10px rgba(0,0,0,.06); }
.mj-item.is-done { background:#FAFAF7; }
.mj-item.is-done .mj-item-title { text-decoration:line-through; color:#94a3b8; }

.mj-item-head { display:flex; align-items:center; gap:10px;
                padding:11px 14px; cursor:pointer; user-select:none; }
.mj-item-head:hover .mj-expand-btn { opacity:1; }

.mj-prio-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
.prio-alta   { background:#ef4444; }
.prio-media  { background:#f59e0b; }
.prio-baja   { background:#cbd5e1; }

.mj-item-title { flex:1; font-size:13px; font-weight:600; color:var(--color-text);
                 line-height:1.4; min-width:0; }

.mj-pills { display:flex; gap:6px; align-items:center; flex-shrink:0; flex-wrap:wrap; }
.mj-pill  { font-size:10px; font-weight:700; padding:2px 8px; border-radius:100px;
            white-space:nowrap; }

/* Categoría */
.cat-diseño        { background:#E1E7F2; color:#3F5E9E; }
.cat-funcionalidad { background:#E3F1E8; color:#1B5A39; }
.cat-bug           { background:#F4DEDB; color:#6E211B; }
.cat-rendimiento   { background:#FEF3C7; color:#92400E; }
.cat-otro          { background:#EFECE5; color:#57544D; }

/* Estado */
.est-idea        { background:#F1F0EC; color:#57544D; }
.est-planeado    { background:#E1E7F2; color:#3F5E9E; }
.est-en_progreso { background:#FEF3C7; color:#92400E; }
.est-completado  { background:#E3F1E8; color:#1B5A39; }

/* Complejidad */
.cpx-facil  { background:#E3F1E8; color:#1B5A39; }
.cpx-media  { background:#FEF3C7; color:#92400E; }
.cpx-dificil{ background:#F4DEDB; color:#6E211B; }

.mj-expand-btn { width:24px; height:24px; display:flex; align-items:center; justify-content:center;
                 border-radius:4px; color:#94a3b8; opacity:.5; transition:all .15s; flex-shrink:0; }
.mj-expand-btn svg { transition:transform .2s; }
.mj-item.open .mj-expand-btn svg { transform:rotate(180deg); }
.mj-item.open .mj-expand-btn { opacity:1; }

/* ── Panel expandido ── */
.mj-item-body { display:none; border-top:1px solid #F1F0EC;
                padding:14px 16px 16px 33px; background:#FAFAF7; }
.mj-item.open .mj-item-body { display:block; }

.mj-notas-label { font-size:10px; font-weight:700; text-transform:uppercase;
                  letter-spacing:.06em; color:#8A867C; margin-bottom:6px; }
.mj-notas-ta    { width:100%; min-height:90px; resize:vertical; font-size:13px;
                  line-height:1.55; color:var(--color-text); background:#fff;
                  border:1.5px solid #E8E5DD; border-radius:6px; padding:10px 12px;
                  font-family:var(--font-primary); transition:border-color .15s;
                  box-sizing:border-box; }
.mj-notas-ta:focus { outline:none; border-color:#0E0E0C; }
.mj-notas-ta::placeholder { color:#C6C2BB; }
.mj-saving { font-size:10px; color:#94a3b8; margin-top:4px; height:14px; }

.mj-controls { display:flex; gap:16px; margin-top:14px; flex-wrap:wrap; align-items:flex-start; }
.mj-ctrl-group { display:flex; flex-direction:column; gap:6px; }
.mj-ctrl-label { font-size:10px; font-weight:700; text-transform:uppercase;
                 letter-spacing:.05em; color:#8A867C; }
.mj-btn-group  { display:flex; gap:4px; flex-wrap:wrap; }
.mj-opt-btn    { padding:4px 11px; font-size:11px; font-weight:700; border-radius:100px;
                 border:1.5px solid #E8E5DD; background:#fff; cursor:pointer;
                 color:#57544D; font-family:inherit; transition:all .15s; }
.mj-opt-btn:hover   { border-color:#0E0E0C; color:#0E0E0C; }
.mj-opt-btn.active  { border-color:currentColor; }
.mj-opt-btn.active.prio-alta  { background:#FEE2E2; color:#B91C1C; border-color:#FECACA; }
.mj-opt-btn.active.prio-media { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-opt-btn.active.prio-baja  { background:#F1F5F9; color:#475569; border-color:#CBD5E1; }
.mj-opt-btn.active.est-idea        { background:#F1F0EC; color:#57544D; border-color:#D6D2C7; }
.mj-opt-btn.active.est-planeado    { background:#E1E7F2; color:#3F5E9E; border-color:#B3C3E0; }
.mj-opt-btn.active.est-en_progreso { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-opt-btn.active.est-completado  { background:#E3F1E8; color:#1B5A39; border-color:#B7DFCA; }
.mj-opt-btn.active.cpx-facil  { background:#E3F1E8; color:#1B5A39; border-color:#B7DFCA; }
.mj-opt-btn.active.cpx-media  { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
.mj-opt-btn.active.cpx-dificil{ background:#F4DEDB; color:#6E211B; border-color:#F4DEDB; }

.mj-item-footer { display:flex; justify-content:space-between; align-items:center;
                  margin-top:14px; padding-top:12px; border-top:1px solid #E8E5DD; }
.mj-date  { font-size:10px; color:#94a3b8; }
.mj-del   { display:inline-flex; align-items:center; gap:5px; padding:4px 10px;
            font-size:11px; font-weight:700; color:#ef4444; background:transparent;
            border:1px solid #FECACA; border-radius:4px; cursor:pointer;
            font-family:inherit; transition:all .15s; }
.mj-del:hover { background:#FEE2E2; }

/* ── Empty ── */
.mj-empty { text-align:center; padding:52px 0; color:var(--color-text-muted); }
.mj-empty svg { opacity:.2; display:block; margin:0 auto 14px; }
</style>

<div class="mj-wrap animate-fade-up">

    <!-- Stats -->
    <div class="mj-stats">
        <div class="mj-stat">
            <div class="mj-stat__dot" style="background:#8A867C"></div>
            <div class="mj-stat__num" id="st-idea">0</div>
            <div class="mj-stat__lbl">Ideas</div>
        </div>
        <div class="mj-stat">
            <div class="mj-stat__dot" style="background:#3F5E9E"></div>
            <div class="mj-stat__num" id="st-planeado">0</div>
            <div class="mj-stat__lbl">Planeado</div>
        </div>
        <div class="mj-stat">
            <div class="mj-stat__dot" style="background:#f59e0b"></div>
            <div class="mj-stat__num" id="st-en_progreso">0</div>
            <div class="mj-stat__lbl">En progreso</div>
        </div>
        <div class="mj-stat">
            <div class="mj-stat__dot" style="background:#22c55e"></div>
            <div class="mj-stat__num" id="st-completado">0</div>
            <div class="mj-stat__lbl">Completado</div>
        </div>
    </div>

    <!-- Progreso -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
        <span style="font-size:11px;font-weight:600;color:#8A867C" id="mjProgressLabel">—</span>
        <span style="font-size:11px;font-weight:800;color:#0E0E0C" id="mjProgressPct">0%</span>
    </div>
    <div class="mj-progress"><div class="mj-progress-bar" id="mjProgressBar" style="width:0%"></div></div>

    <!-- Agregar -->
    <div class="mj-add-card">
        <form id="mjForm" onsubmit="agregarMejora(event)" autocomplete="off">
            <div class="mj-add-row">
                <div class="mj-add-titulo">
                    <input type="text" id="mjTexto" class="form-input" placeholder="¿Qué quieres mejorar?" required maxlength="600">
                </div>
                <select id="mjCat" class="form-select mj-add-sel">
                    <option value="funcionalidad">Funcionalidad</option>
                    <option value="diseño">Diseño</option>
                    <option value="bug">Bug</option>
                    <option value="rendimiento">Rendimiento</option>
                    <option value="otro" selected>Otro</option>
                </select>
                <select id="mjPrio" class="form-select mj-add-sel-sm">
                    <option value="alta">🔴 Alta</option>
                    <option value="media" selected>🟡 Media</option>
                    <option value="baja">⚪ Baja</option>
                </select>
                <select id="mjCpx" class="form-select mj-add-sel-sm">
                    <option value="facil">✅ Fácil</option>
                    <option value="media" selected>🔧 Media</option>
                    <option value="dificil">⚡ Difícil</option>
                </select>
                <button type="submit" class="btn btn-secondary" style="flex-shrink:0;height:36px;padding:0 16px">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Agregar
                </button>
            </div>
        </form>
    </div>

    <!-- Filtros -->
    <div class="mj-filters">
        <div class="mj-search">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="form-input" id="mjSearch" placeholder="Buscar mejora..." oninput="render()">
        </div>
        <select class="form-select" id="fEstado" onchange="render()" style="width:148px;height:32px;font-size:12px">
            <option value="">Todos los estados</option>
            <option value="idea">Idea</option>
            <option value="planeado">Planeado</option>
            <option value="en_progreso">En progreso</option>
            <option value="completado">Completado</option>
        </select>
        <select class="form-select" id="fPrio" onchange="render()" style="width:130px;height:32px;font-size:12px">
            <option value="">Toda prioridad</option>
            <option value="alta">Alta</option>
            <option value="media">Media</option>
            <option value="baja">Baja</option>
        </select>
        <select class="form-select" id="fCat" onchange="render()" style="width:148px;height:32px;font-size:12px">
            <option value="">Toda categoría</option>
            <option value="funcionalidad">Funcionalidad</option>
            <option value="diseño">Diseño</option>
            <option value="bug">Bug</option>
            <option value="rendimiento">Rendimiento</option>
            <option value="otro">Otro</option>
        </select>
    </div>

    <!-- Lista -->
    <div class="mj-list" id="mjList"></div>
</div>

<script>
let _mejoras  = [];
let _expanded = new Set();
let _saveTmr  = {};

const CAT_LBL  = { diseño:'Diseño', funcionalidad:'Funcionalidad', bug:'Bug', rendimiento:'Rendimiento', otro:'Otro' };
const EST_LBL  = { idea:'Idea', planeado:'Planeado', en_progreso:'En progreso', completado:'Completado' };
const PRIO_LBL = { alta:'Alta', media:'Media', baja:'Baja' };
const CPX_LBL  = { facil:'Fácil', media:'Media', dificil:'Difícil' };

function escapeHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('es-CO',{day:'numeric',month:'short',year:'numeric'});
}

async function loadMejoras() {
    const r = await fetch('api/mejoras.php');
    const d = await r.json();
    if (d.success) { _mejoras = d.data; render(); }
}

function render() {
    // Stats
    const counts = { idea:0, planeado:0, en_progreso:0, completado:0 };
    _mejoras.forEach(m => { if (counts[m.estado] !== undefined) counts[m.estado]++; });
    Object.entries(counts).forEach(([k,v]) => {
        const el = document.getElementById('st-'+k);
        if (el) el.textContent = v;
    });
    const total = _mejoras.length;
    const done  = counts.completado;
    const pct   = total ? Math.round((done / total) * 100) : 0;
    document.getElementById('mjProgressBar').style.width  = pct + '%';
    document.getElementById('mjProgressPct').textContent  = pct + '%';
    document.getElementById('mjProgressLabel').textContent =
        total ? `${done} de ${total} completadas · ${_mejoras.filter(m=>m.estado==='en_progreso').length} en progreso` : 'Sin mejoras registradas aún';

    // Filtros
    const q     = (document.getElementById('mjSearch').value||'').toLowerCase();
    const fEst  = document.getElementById('fEstado').value;
    const fPrio = document.getElementById('fPrio').value;
    const fCat  = document.getElementById('fCat').value;

    const lista = _mejoras.filter(m => {
        if (fEst  && m.estado     !== fEst)  return false;
        if (fPrio && m.prioridad  !== fPrio) return false;
        if (fCat  && m.categoria  !== fCat)  return false;
        if (q && !m.texto.toLowerCase().includes(q) && !(m.notas||'').toLowerCase().includes(q)) return false;
        return true;
    });

    const list = document.getElementById('mjList');
    if (!lista.length) {
        list.innerHTML = `<div class="mj-empty">
            <svg width="44" height="44" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <div style="font-weight:700;font-size:14px;margin-bottom:4px">Sin resultados</div>
            <div style="font-size:12px">Prueba cambiando los filtros o agrega una nueva mejora.</div>
        </div>`;
        return;
    }

    list.innerHTML = lista.map(m => {
        const open  = _expanded.has(m.id);
        const isDone = m.estado === 'completado';

        // Opciones de prioridad
        const prioOpts = ['alta','media','baja'].map(p =>
            `<button type="button" class="mj-opt-btn prio-${p} ${m.prioridad===p?'active':''}"
                onclick="event.stopPropagation();setField(${m.id},'prioridad','${p}')">${PRIO_LBL[p]}</button>`
        ).join('');

        // Opciones de estado
        const estOpts = ['idea','planeado','en_progreso','completado'].map(e =>
            `<button type="button" class="mj-opt-btn est-${e} ${m.estado===e?'active':''}"
                onclick="event.stopPropagation();setField(${m.id},'estado','${e}')">${EST_LBL[e]}</button>`
        ).join('');

        // Opciones de complejidad
        const cpxOpts = ['facil','media','dificil'].map(c =>
            `<button type="button" class="mj-opt-btn cpx-${c} ${(m.complejidad||'media')===c?'active':''}"
                onclick="event.stopPropagation();setField(${m.id},'complejidad','${c}')">${CPX_LBL[c]}</button>`
        ).join('');

        return `
        <div class="mj-item${open?' open':''}${isDone?' is-done':''}" id="mj-${m.id}">
            <div class="mj-item-head" onclick="toggleExpand(${m.id})">
                <div class="mj-prio-dot prio-${m.prioridad||'media'}"></div>
                <span class="mj-item-title">${escapeHtml(m.texto)}</span>
                <div class="mj-pills">
                    <span class="mj-pill cat-${m.categoria}">${CAT_LBL[m.categoria]||m.categoria}</span>
                    <span class="mj-pill est-${m.estado}">${EST_LBL[m.estado]||m.estado}</span>
                    ${m.notas ? `<svg width="12" height="12" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2" title="Tiene notas"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>` : ''}
                </div>
                <div class="mj-expand-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            <div class="mj-item-body">
                <div class="mj-notas-label">Notas de implementación</div>
                <textarea class="mj-notas-ta" id="ta-${m.id}"
                    placeholder="¿Cómo implementarlo? ¿Qué falta investigar? ¿Referencias o ideas?"
                    oninput="scheduleNotasSave(${m.id})">${escapeHtml(m.notas||'')}</textarea>
                <div class="mj-saving" id="saving-${m.id}"></div>

                <div class="mj-controls">
                    <div class="mj-ctrl-group">
                        <div class="mj-ctrl-label">Estado</div>
                        <div class="mj-btn-group">${estOpts}</div>
                    </div>
                    <div class="mj-ctrl-group">
                        <div class="mj-ctrl-label">Prioridad</div>
                        <div class="mj-btn-group">${prioOpts}</div>
                    </div>
                    <div class="mj-ctrl-group">
                        <div class="mj-ctrl-label">Complejidad</div>
                        <div class="mj-btn-group">${cpxOpts}</div>
                    </div>
                </div>

                <div class="mj-item-footer">
                    <span class="mj-date">Agregado ${fmtDate(m.created_at)}${m.completada_at?' · Completado '+fmtDate(m.completada_at):''}</span>
                    <button class="mj-del" onclick="deleteMejora(${m.id})">
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

function toggleExpand(id) {
    if (_expanded.has(id)) _expanded.delete(id);
    else _expanded.add(id);
    render();
    // Restaurar foco en textarea si se acaba de abrir
    if (_expanded.has(id)) {
        setTimeout(() => {
            const ta = document.getElementById('ta-' + id);
            if (ta) ta.focus();
        }, 50);
    }
}

async function agregarMejora(e) {
    e.preventDefault();
    const texto = document.getElementById('mjTexto').value.trim();
    if (!texto) return;

    const payload = {
        texto,
        categoria:   document.getElementById('mjCat').value,
        prioridad:   document.getElementById('mjPrio').value,
        complejidad: document.getElementById('mjCpx').value,
        estado:      'idea',
    };
    const r = await fetch('api/mejoras.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.success) {
        document.getElementById('mjTexto').value = '';
        _mejoras.unshift({ id: d.id, ...payload, completada: 0, notas: '', created_at: new Date().toISOString() });
        _expanded.add(d.id);
        render();
    }
}

async function setField(id, field, value) {
    const item = _mejoras.find(m => m.id == id);
    if (!item) return;
    item[field] = value;
    render();
    await fetch('api/mejoras.php', {
        method: 'PUT', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id, [field]: value })
    });
}

function scheduleNotasSave(id) {
    const el = document.getElementById('saving-' + id);
    if (el) el.textContent = 'Guardando...';
    clearTimeout(_saveTmr[id]);
    _saveTmr[id] = setTimeout(async () => {
        const ta = document.getElementById('ta-' + id);
        if (!ta) return;
        const notas = ta.value;
        const item  = _mejoras.find(m => m.id == id);
        if (item) item.notas = notas;
        await fetch('api/mejoras.php', {
            method: 'PUT', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id, notas })
        });
        if (el) { el.textContent = 'Guardado ✓'; setTimeout(() => { if (el) el.textContent = ''; }, 2000); }
    }, 900);
}

async function deleteMejora(id) {
    const ok = await confirmAction('Se eliminará esta mejora permanentemente.', { title: '¿Eliminar mejora?' });
    if (!ok) return;
    const r = await fetch(`api/mejoras.php?id=${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.success) { _mejoras = _mejoras.filter(m => m.id != id); _expanded.delete(id); render(); }
}

loadMejoras();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
