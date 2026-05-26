<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pdo = db();

// Auto-migración
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS mejoras_plataforma (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        texto        VARCHAR(600) NOT NULL,
        categoria    VARCHAR(40)  NOT NULL DEFAULT 'otro',
        completada   TINYINT(1)   NOT NULL DEFAULT 0,
        created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completada_at DATETIME    DEFAULT NULL
    )");
} catch (PDOException $e) {}

$pageTitle    = 'Mejoras';
$pageSubtitle = 'Lista interna de mejoras pendientes';
include __DIR__ . '/includes/header.php';
?>

<style>
.mj-wrap        { max-width: 720px; }
.mj-add         { display:flex; gap:10px; margin-bottom:28px; align-items:flex-end; flex-wrap:wrap; }
.mj-add-input   { flex:1; min-width:220px; }
.mj-add-cat     { width:160px; }

.mj-tabs        { display:flex; gap:0; border-bottom:1.5px solid var(--color-border); margin-bottom:20px; }
.mj-tab         { padding:8px 20px; font-size:12px; font-weight:700; color:var(--color-text-muted);
                  background:none; border:none; cursor:pointer; border-bottom:2px solid transparent;
                  margin-bottom:-1.5px; transition:all .15s; font-family:inherit; }
.mj-tab.active  { color:var(--color-text); border-bottom-color:#0E0E0C; }
.mj-tab:hover:not(.active) { color:var(--color-text); }

.mj-count       { display:inline-flex; align-items:center; justify-content:center;
                  min-width:18px; height:18px; padding:0 5px; border-radius:100px;
                  background:#EFECE5; color:#57544D; font-size:10px; font-weight:700;
                  margin-left:6px; }

.mj-progress    { height:4px; background:#F1F0EC; border-radius:100px; margin-bottom:24px; overflow:hidden; }
.mj-progress-bar{ height:100%; background:#0E0E0C; border-radius:100px; transition:width .4s ease; }

.mj-list        { display:flex; flex-direction:column; gap:6px; }
.mj-item        { display:flex; align-items:center; gap:12px;
                  padding:12px 14px; background:#fff;
                  border:1px solid #E8E5DD; border-radius:6px;
                  transition:all .15s; }
.mj-item:hover  { border-color:#C6C2BB; box-shadow:0 2px 8px rgba(0,0,0,.05); }
.mj-item.done   { background:#FAFAF7; }
.mj-item.done .mj-text { text-decoration:line-through; color:#94a3b8; }

.mj-check       { width:18px; height:18px; border-radius:4px; border:1.5px solid #C6C2BB;
                  background:#fff; cursor:pointer; flex-shrink:0; display:flex;
                  align-items:center; justify-content:center; transition:all .15s; }
.mj-check:hover { border-color:#0E0E0C; }
.mj-check.checked { background:#0E0E0C; border-color:#0E0E0C; }
.mj-check.checked svg { display:block; }
.mj-check svg   { display:none; }

.mj-text        { flex:1; font-size:13px; font-weight:500; color:var(--color-text); line-height:1.4; }

.mj-cat-pill    { font-size:10px; font-weight:700; padding:3px 9px; border-radius:100px;
                  white-space:nowrap; flex-shrink:0; }
.cat-diseño     { background:#E1E7F2; color:#3F5E9E; }
.cat-funcionalidad { background:#E3F1E8; color:#1B5A39; }
.cat-bug        { background:#F4DEDB; color:#6E211B; }
.cat-rendimiento { background:#FEF3C7; color:#92400E; }
.cat-otro       { background:#EFECE5; color:#57544D; }

.mj-del         { width:26px; height:26px; border-radius:4px; border:none; background:transparent;
                  cursor:pointer; display:flex; align-items:center; justify-content:center;
                  color:#CBD5E1; flex-shrink:0; transition:all .15s; }
.mj-del:hover   { background:#FEE2E2; color:#ef4444; }

.mj-empty       { text-align:center; padding:48px 0; color:var(--color-text-muted); font-size:13px; }
.mj-empty svg   { opacity:.25; margin-bottom:12px; }
</style>

<div class="mj-wrap animate-fade-up">

    <!-- Formulario agregar -->
    <div class="card" style="padding:18px 20px;margin-bottom:8px">
        <form id="mjForm" onsubmit="agregarMejora(event)" autocomplete="off">
            <div class="mj-add">
                <div class="mj-add-input">
                    <label class="form-label" style="margin-bottom:6px">Nueva mejora</label>
                    <input type="text" id="mjTexto" class="form-input" placeholder="Describe qué quieres mejorar..." required maxlength="600">
                </div>
                <div class="mj-add-cat">
                    <label class="form-label" style="margin-bottom:6px">Categoría</label>
                    <select id="mjCat" class="form-select">
                        <option value="funcionalidad">Funcionalidad</option>
                        <option value="diseño">Diseño</option>
                        <option value="bug">Bug</option>
                        <option value="rendimiento">Rendimiento</option>
                        <option value="otro" selected>Otro</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary" style="white-space:nowrap;flex-shrink:0">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Agregar
                </button>
            </div>
        </form>
    </div>

    <!-- Barra de progreso -->
    <div style="padding:0 4px 4px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <span style="font-size:11px;font-weight:600;color:#8A867C" id="mjProgressLabel">Cargando...</span>
            <span style="font-size:11px;font-weight:700;color:#0E0E0C" id="mjProgressPct">0%</span>
        </div>
        <div class="mj-progress"><div class="mj-progress-bar" id="mjProgressBar" style="width:0%"></div></div>
    </div>

    <!-- Tabs -->
    <div class="mj-tabs">
        <button class="mj-tab active" onclick="setTab('pendientes',this)">
            Pendientes <span class="mj-count" id="cntPendientes">0</span>
        </button>
        <button class="mj-tab" onclick="setTab('completadas',this)">
            Completadas <span class="mj-count" id="cntCompletadas">0</span>
        </button>
        <button class="mj-tab" onclick="setTab('todas',this)">
            Todas
        </button>
    </div>

    <!-- Lista -->
    <div class="mj-list" id="mjList"></div>
</div>

<script>
let _mejoras = [];
let _tab = 'pendientes';

const CAT_LABEL = {
    diseño: 'Diseño', funcionalidad: 'Funcionalidad',
    bug: 'Bug', rendimiento: 'Rendimiento', otro: 'Otro'
};

async function loadMejoras() {
    const r = await fetch('api/mejoras.php');
    const d = await r.json();
    if (d.success) { _mejoras = d.data; render(); }
}

function render() {
    const total     = _mejoras.length;
    const done      = _mejoras.filter(m => m.completada == 1).length;
    const pendientes = total - done;
    const pct       = total ? Math.round((done / total) * 100) : 0;

    document.getElementById('cntPendientes').textContent  = pendientes;
    document.getElementById('cntCompletadas').textContent = done;
    document.getElementById('mjProgressBar').style.width  = pct + '%';
    document.getElementById('mjProgressPct').textContent  = pct + '%';
    document.getElementById('mjProgressLabel').textContent =
        total ? `${done} de ${total} completadas` : 'Sin mejoras registradas aún';

    const lista = _mejoras.filter(m => {
        if (_tab === 'pendientes')  return m.completada == 0;
        if (_tab === 'completadas') return m.completada == 1;
        return true;
    });

    const list = document.getElementById('mjList');
    if (!lista.length) {
        const msgs = {
            pendientes:  ['Sin pendientes', '¡Todo al día por ahora!'],
            completadas: ['Nada completado aún', 'Marca ítems como listos para verlos aquí.'],
            todas:       ['Sin mejoras', 'Agrega la primera mejora arriba.']
        };
        const [t, s] = msgs[_tab];
        list.innerHTML = `<div class="mj-empty">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2" style="display:block;margin:0 auto 10px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div style="font-weight:700;font-size:14px;margin-bottom:4px">${t}</div>
            <div style="font-size:12px">${s}</div>
        </div>`;
        return;
    }

    list.innerHTML = lista.map(m => `
        <div class="mj-item ${m.completada == 1 ? 'done' : ''}" id="mj-${m.id}">
            <div class="mj-check ${m.completada == 1 ? 'checked' : ''}" onclick="toggleMejora(${m.id})">
                <svg width="11" height="11" fill="none" stroke="#fff" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <span class="mj-text">${escapeHtml(m.texto)}</span>
            <span class="mj-cat-pill cat-${m.categoria}">${CAT_LABEL[m.categoria] || m.categoria}</span>
            <button class="mj-del" onclick="deleteMejora(${m.id})" title="Eliminar">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>`).join('');
}

function setTab(tab, btn) {
    _tab = tab;
    document.querySelectorAll('.mj-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    render();
}

async function agregarMejora(e) {
    e.preventDefault();
    const texto = document.getElementById('mjTexto').value.trim();
    const cat   = document.getElementById('mjCat').value;
    if (!texto) return;

    const r = await fetch('api/mejoras.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ texto, categoria: cat })
    });
    const d = await r.json();
    if (d.success) {
        document.getElementById('mjTexto').value = '';
        _mejoras.unshift({ id: d.id, texto, categoria: cat, completada: 0 });
        render();
    }
}

async function toggleMejora(id) {
    const item = _mejoras.find(m => m.id == id);
    if (!item) return;
    const completada = item.completada == 1 ? 0 : 1;

    const r = await fetch('api/mejoras.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, completada })
    });
    const d = await r.json();
    if (d.success) { item.completada = completada; render(); }
}

async function deleteMejora(id) {
    const ok = await confirmAction('Se eliminará esta mejora permanentemente.', { title: '¿Eliminar mejora?' });
    if (!ok) return;
    const r = await fetch(`api/mejoras.php?id=${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.success) { _mejoras = _mejoras.filter(m => m.id != id); render(); }
}

function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

loadMejoras();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
