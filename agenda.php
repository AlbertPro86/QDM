<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle = 'Agenda';
include __DIR__ . '/includes/header.php';
?>
<style>
/* ── Reset área de contenido para el tablero ── */
.page-content { padding: 0 !important; }
.main-content > .page-content,
.main-content > div:not(.topbar):not(.toast-container) { padding: 0; }

/* ── Wrapper del tablero ── */
.ag-wrap {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 60px);
    background: #1C1917;
    overflow: hidden;
    position: relative;
}

/* ── Barra superior ── */
.ag-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: rgba(0,0,0,.25);
    flex-shrink: 0;
}
.ag-bar-title {
    font-size: 15px;
    font-weight: 800;
    color: #fff;
    font-family: var(--font-secondary, 'JetBrains Mono', monospace);
    letter-spacing: -.3px;
}
.ag-bar-sep { width: 1px; height: 20px; background: rgba(255,255,255,.2); }
.ag-bar-search-wrap { position: relative; }
.ag-bar-search-wrap svg { position: absolute; left: 8px; top: 50%; transform: translateY(-50%); pointer-events: none; color: rgba(255,255,255,.5); }
.ag-bar-search {
    height: 30px; padding: 0 10px 0 30px;
    border-radius: 6px; border: none;
    background: rgba(255,255,255,.15); color: #fff;
    font-size: 12px; font-family: inherit; width: 180px;
    transition: background .15s, width .2s;
}
.ag-bar-search::placeholder { color: rgba(255,255,255,.5); }
.ag-bar-search:focus { outline: none; background: rgba(255,255,255,.25); width: 220px; }

/* ── Zona columnas ── */
.ag-cols {
    display: flex;
    gap: 10px;
    padding: 10px 16px;
    overflow-x: auto;
    flex: 1;
    align-items: flex-start;
}
.ag-cols::-webkit-scrollbar { height: 8px; }
.ag-cols::-webkit-scrollbar-track { background: rgba(0,0,0,.2); border-radius: 4px; }
.ag-cols::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 4px; }

/* ── Columna ── */
.ag-col {
    background: #EFECE5;
    border-radius: 10px;
    width: 272px; min-width: 272px;
    max-height: calc(100vh - 140px);
    display: flex; flex-direction: column;
    flex-shrink: 0;
    transition: outline .1s;
}
.ag-col.drag-over { outline: 2px solid #C6F24E; outline-offset: 1px; }
.ag-col-hd {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 12px 8px; gap: 6px; flex-shrink: 0;
}
.ag-col-title {
    font-size: 13px; font-weight: 700; color: #0E0E0C;
    flex: 1; cursor: text; border-radius: 4px;
    padding: 2px 4px; margin: -2px -4px;
    line-height: 1.4; word-break: break-word;
}
.ag-col-title:focus { outline: 2px solid #0E0E0C; background: #fff; border-radius: 4px; }
.ag-col-count { font-size: 12px; font-weight: 700; color: #8A867C; flex-shrink: 0; }
.ag-col-menu-btn {
    width: 26px; height: 26px; border-radius: 5px; border: none;
    background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #8A867C; transition: background .12s; flex-shrink: 0;
}
.ag-col-menu-btn:hover { background: rgba(0,0,0,.1); color: #0E0E0C; }

/* ── Body columna ── */
.ag-col-body {
    padding: 0 8px; overflow-y: auto; flex: 1; min-height: 8px;
}
.ag-col-body::-webkit-scrollbar { width: 4px; }
.ag-col-body::-webkit-scrollbar-thumb { background: #D6D2C7; border-radius: 2px; }

/* ── Tarjeta ── */
.ag-card {
    background: #fff; border-radius: 8px;
    padding: 8px 10px; margin-bottom: 8px;
    cursor: pointer;
    box-shadow: 0 1px 0 rgba(9,30,66,.25);
    transition: box-shadow .12s;
    position: relative;
}
.ag-card:hover { box-shadow: 0 4px 12px rgba(9,30,66,.18); }
.ag-card.dragging { opacity: .35; }
.ag-card.completada .ag-card-title { text-decoration: line-through; color: #94a3b8; }

.ag-card-labels { display: flex; gap: 4px; margin-bottom: 6px; flex-wrap: wrap; }
.ag-label-bar { height: 8px; min-width: 40px; border-radius: 4px; flex: 0 1 40px; }

.ag-card-title { font-size: 13px; font-weight: 500; color: #0E0E0C; line-height: 1.45; word-break: break-word; }

.ag-card-foot { display: flex; align-items: center; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
.ag-card-ico { display: flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 600; color: #8A867C; }

.ag-date-badge {
    display: flex; align-items: center; gap: 3px;
    padding: 2px 6px; border-radius: 4px;
    font-size: 10px; font-weight: 700;
    background: #E3F1E8; color: #1B5A39;
}
.ag-date-badge.hoy     { background: #FEF3C7; color: #92400E; }
.ag-date-badge.vencida { background: #FEE2E2; color: #B91C1C; }

.ag-done-check {
    width: 16px; height: 16px; border-radius: 50%;
    background: #1B5A39; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* ── Placeholder drag ── */
.ag-placeholder {
    height: 48px; background: rgba(198,242,78,.12);
    border-radius: 8px; margin-bottom: 8px;
    border: 2px dashed #C6F24E;
    pointer-events: none;
}

/* ── Add card ── */
.ag-add-card-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 12px; margin: 0 4px 8px;
    border-radius: 6px; border: none;
    background: transparent; cursor: pointer;
    font-size: 13px; color: #57544D;
    font-family: inherit; width: calc(100% - 8px);
    text-align: left; transition: background .12s; flex-shrink: 0;
}
.ag-add-card-btn:hover { background: rgba(0,0,0,.08); color: #0E0E0C; }
.ag-add-card-btn.adding { display: none; }

.ag-add-card-form { padding: 0 8px 8px; display: none; flex-shrink: 0; }
.ag-add-card-form.show { display: block; }
.ag-add-card-input {
    width: 100%; min-height: 60px;
    border-radius: 8px; border: none;
    box-shadow: 0 1px 0 rgba(9,30,66,.25);
    padding: 8px 10px; font-size: 13px;
    font-family: inherit; resize: none;
    box-sizing: border-box; line-height: 1.4;
}
.ag-add-card-input:focus { outline: 2px solid #0E0E0C; }
.ag-add-card-actions { display: flex; align-items: center; gap: 6px; margin-top: 6px; }
.ag-add-card-save {
    padding: 6px 14px; border-radius: 6px; border: none;
    background: #0E0E0C; color: #C6F24E;
    font-size: 12px; font-weight: 700; cursor: pointer; font-family: inherit;
}
.ag-add-card-cancel {
    width: 28px; height: 28px; border-radius: 6px; border: none;
    background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #57544D; font-size: 18px; transition: background .12s;
}
.ag-add-card-cancel:hover { background: rgba(0,0,0,.08); }

/* ── Añadir lista ── */
.ag-add-col-btn {
    min-width: 272px; width: 272px; flex-shrink: 0;
    background: rgba(255,255,255,.12);
    border-radius: 10px; border: none; cursor: pointer;
    padding: 12px 14px; color: #fff; font-size: 13px;
    font-weight: 600; font-family: inherit; text-align: left;
    display: flex; align-items: center; gap: 8px;
    transition: background .15s; height: fit-content;
}
.ag-add-col-btn:hover { background: rgba(255,255,255,.22); }

/* ── Menú columna ── */
.ag-col-menu {
    position: fixed; background: #fff;
    border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.18);
    z-index: 1000; min-width: 180px; padding: 4px 0;
    border: 1px solid #E8E5DD;
}
.ag-col-menu-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 14px; font-size: 13px; color: #0E0E0C;
    cursor: pointer; transition: background .1s;
}
.ag-col-menu-item:hover { background: #FAFAF7; }
.ag-col-menu-item.del { color: #ef4444; }
.ag-col-menu-item.del:hover { background: #FEE2E2; }

/* ── Nav inferior ── */
.ag-nav {
    display: flex; align-items: center; justify-content: center;
    gap: 2px; padding: 6px 0;
    background: rgba(0,0,0,.3); flex-shrink: 0;
}
.ag-nav-tab {
    display: flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 6px; border: none;
    background: transparent; color: rgba(255,255,255,.65);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: inherit; transition: background .12s, color .12s;
}
.ag-nav-tab:hover  { background: rgba(255,255,255,.12); color: #fff; }
.ag-nav-tab.active { background: rgba(255,255,255,.2);  color: #fff; }
.ag-nav-sep { width: 1px; height: 16px; background: rgba(255,255,255,.15); }

/* ── Modal ── */
.ag-modal-overlay {
    position: fixed; inset: 0; z-index: 2000;
    background: rgba(0,0,0,.65);
    display: none; align-items: flex-start; justify-content: center;
    padding: 40px 16px; overflow-y: auto;
}
.ag-modal-overlay.show { display: flex; }
.ag-modal {
    background: #F1F0EC; border-radius: 12px;
    width: 100%; max-width: 700px;
    min-height: 480px; position: relative; padding-bottom: 24px;
}
.ag-modal-hd {
    padding: 18px 20px 12px;
    display: flex; align-items: flex-start; gap: 12px;
}
.ag-modal-title-wrap { flex: 1; }
.ag-modal-title {
    font-size: 17px; font-weight: 700; color: #0E0E0C;
    background: transparent; border: none; width: 100%;
    font-family: inherit; line-height: 1.4; padding: 4px 6px;
    border-radius: 4px; resize: none; min-height: 28px; overflow: hidden;
}
.ag-modal-title:focus { outline: none; background: #fff; }
.ag-modal-col-label { font-size: 11px; color: #8A867C; margin-top: 2px; padding: 0 6px; }
.ag-modal-close {
    width: 32px; height: 32px; border-radius: 6px; border: none;
    background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #57544D; transition: background .12s; flex-shrink: 0;
}
.ag-modal-close:hover { background: #E8E5DD; }

.ag-modal-body  { display: flex; gap: 16px; padding: 0 20px; }
.ag-modal-main  { flex: 1; min-width: 0; }
.ag-modal-side  { width: 160px; flex-shrink: 0; }

.ag-section { margin-bottom: 20px; }
.ag-section-title {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #8A867C; margin-bottom: 8px;
    display: flex; align-items: center; gap: 6px;
}
.ag-modal-desc {
    width: 100%; min-height: 80px; resize: vertical;
    border-radius: 6px; border: 1.5px solid #E8E5DD;
    background: #fff; padding: 9px 11px;
    font-size: 13px; font-family: inherit; line-height: 1.55;
    transition: border-color .15s; box-sizing: border-box;
}
.ag-modal-desc:focus { outline: none; border-color: #0E0E0C; }
.ag-modal-desc::placeholder { color: #C6C2BB; }

/* Etiquetas */
.ag-labels-grid { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.ag-label-pill {
    display: flex; align-items: center; gap: 4px;
    padding: 5px 10px; border-radius: 4px; cursor: pointer;
    font-size: 11px; font-weight: 700; color: #fff;
    opacity: .4; transition: opacity .12s, outline .1s;
    border: 2px solid transparent; user-select: none;
}
.ag-label-pill:hover { opacity: .75; }
.ag-label-pill.on { opacity: 1; outline: 2px solid rgba(0,0,0,.35); outline-offset: 1px; }

/* Checklist */
.ag-chk-progress { height: 6px; background: #E8E5DD; border-radius: 100px; overflow: hidden; margin-bottom: 10px; }
.ag-chk-bar { height: 100%; background: #1B5A39; border-radius: 100px; transition: width .3s; }
.ag-chk-item { display: flex; align-items: center; gap: 8px; padding: 3px 0; }
.ag-chk-cb {
    width: 16px; height: 16px; border-radius: 3px;
    border: 2px solid #D6D2C7; background: #fff;
    cursor: pointer; flex-shrink: 0; appearance: none;
    transition: all .12s;
}
.ag-chk-cb:checked { background: #1B5A39; border-color: #1B5A39;
    background-image: url("data:image/svg+xml,%3Csvg width='10' height='8' viewBox='0 0 10 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 4l3 3 5-6' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: center;
}
.ag-chk-text {
    flex: 1; font-size: 13px; color: #0E0E0C;
    background: transparent; border: none; font-family: inherit;
    line-height: 1.4; cursor: text; border-radius: 4px; padding: 2px 4px;
}
.ag-chk-text:focus { outline: none; background: #fff; }
.ag-chk-text.done { text-decoration: line-through; color: #94a3b8; }
.ag-chk-del {
    width: 22px; height: 22px; border-radius: 4px; border: none;
    background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #C6C2BB; transition: all .12s; opacity: 0; flex-shrink: 0;
}
.ag-chk-item:hover .ag-chk-del { opacity: 1; }
.ag-chk-del:hover { background: #FEE2E2; color: #ef4444; }
.ag-chk-add {
    display: flex; align-items: center; gap: 6px;
    padding: 6px 4px; cursor: pointer; color: #57544D;
    font-size: 12px; font-weight: 600; border-radius: 4px;
    transition: background .12s; margin-top: 4px;
}
.ag-chk-add:hover { background: #E8E5DD; }

/* Adjuntos */
.ag-adj-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px; background: #fff; border-radius: 6px;
    margin-bottom: 6px; border: 1px solid #E8E5DD;
}
.ag-adj-thumb {
    width: 40px; height: 32px; border-radius: 4px; background: #EFECE5;
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    overflow: hidden; color: #8A867C;
}
.ag-adj-thumb img { width: 100%; height: 100%; object-fit: cover; }
.ag-adj-info { flex: 1; min-width: 0; }
.ag-adj-name {
    font-size: 12px; font-weight: 600; color: #0E0E0C;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block;
}
.ag-adj-name.link { color: #3F5E9E; text-decoration: none; }
.ag-adj-name.link:hover { text-decoration: underline; }
.ag-adj-sub  { font-size: 10px; color: #8A867C; }
.ag-adj-del {
    width: 24px; height: 24px; border-radius: 4px; border: none;
    background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #C6C2BB; transition: all .12s;
}
.ag-adj-del:hover { background: #FEE2E2; color: #ef4444; }

/* Botones lado modal */
.ag-side-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 12px; border-radius: 6px; border: none;
    background: #E8E5DD; cursor: pointer; color: #57544D;
    font-size: 12px; font-weight: 600; font-family: inherit;
    width: 100%; margin-bottom: 6px; transition: background .12s; text-align: left;
}
.ag-side-btn:hover { background: #D6D2C7; }
.ag-side-btn.danger:hover { background: #FEE2E2; color: #ef4444; }

/* Responsive */
@media(max-width: 640px) {
    .ag-modal-body { flex-direction: column; }
    .ag-modal-side { width: 100%; }
    .ag-col { min-width: 240px; width: 240px; }
}
</style>

<div class="ag-wrap" id="agWrap">

    <!-- Barra superior -->
    <div class="ag-bar">
        <span class="ag-bar-title">Agenda</span>
        <div class="ag-bar-sep"></div>
        <div class="ag-bar-search-wrap">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="ag-bar-search" id="agSearch" placeholder="Buscar tarjetas..." oninput="renderBoard()">
        </div>
    </div>

    <!-- Columnas -->
    <div class="ag-cols" id="agCols">
        <div style="color:rgba(255,255,255,.5);font-size:13px;padding:20px">Cargando agenda...</div>
    </div>

    <!-- Nav inferior -->
    <div class="ag-nav">
        <button class="ag-nav-tab" onclick="showToast('Próximamente','info')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            Bandeja de entrada
        </button>
        <div class="ag-nav-sep"></div>
        <button class="ag-nav-tab" onclick="showToast('Próximamente','info')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Planificador
        </button>
        <div class="ag-nav-sep"></div>
        <button class="ag-nav-tab active">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Tablero
        </button>
    </div>
</div>

<!-- Modal detalle -->
<div class="ag-modal-overlay" id="agModal" onclick="if(event.target===this)closeModal()">
    <div class="ag-modal">
        <div class="ag-modal-hd">
            <div class="ag-modal-title-wrap">
                <textarea class="ag-modal-title" id="mTitle" rows="1" oninput="autoResize(this);schedSave('title')" onblur="saveField('title')"></textarea>
                <div class="ag-modal-col-label" id="mColLabel"></div>
            </div>
            <button class="ag-modal-close" onclick="closeModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="ag-modal-body">
            <div class="ag-modal-main">

                <div class="ag-section">
                    <div class="ag-section-title">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 014-4z"/></svg>
                        Etiquetas
                    </div>
                    <div class="ag-labels-grid" id="mLabelsGrid"></div>
                </div>

                <div class="ag-section">
                    <div class="ag-section-title">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h10"/></svg>
                        Descripción
                    </div>
                    <textarea class="ag-modal-desc" id="mDesc" placeholder="Agrega una descripción más detallada..." oninput="schedSave('desc')" onblur="saveField('desc')"></textarea>
                    <div style="font-size:10px;color:#94a3b8;margin-top:3px;min-height:14px" id="mDescSave"></div>
                </div>

                <div class="ag-section">
                    <div class="ag-section-title" style="justify-content:space-between">
                        <span style="display:flex;align-items:center;gap:6px">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Checklist
                        </span>
                        <span id="mChkPct" style="font-size:10px;font-weight:700;color:#8A867C"></span>
                    </div>
                    <div class="ag-chk-progress"><div class="ag-chk-bar" id="mChkBar" style="width:0%"></div></div>
                    <div id="mChkList"></div>
                    <div class="ag-chk-add" onclick="addChkItem()">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Añadir elemento
                    </div>
                </div>

                <div class="ag-section">
                    <div class="ag-section-title">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        Adjuntos
                    </div>
                    <div id="mAdjList"></div>
                    <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap">
                        <label class="ag-side-btn" style="cursor:pointer;margin-bottom:0;width:auto">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Subir archivo
                            <input type="file" id="mFileInput" style="display:none" onchange="uploadFile(this)">
                        </label>
                        <button class="ag-side-btn" style="width:auto;margin-bottom:0" onclick="addLink()">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            Enlace
                        </button>
                    </div>
                </div>

            </div>
            <div class="ag-modal-side">

                <div class="ag-section">
                    <div class="ag-section-title">Fecha de vencimiento</div>
                    <input type="date" id="mFecha" class="form-input" style="height:32px;font-size:12px;width:100%;box-sizing:border-box" onchange="saveField('fecha')">
                </div>

                <div class="ag-section">
                    <div class="ag-section-title">Acciones</div>
                    <button class="ag-side-btn" onclick="toggleComplete()">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span id="mCompleteBtn">Marcar completada</span>
                    </button>
                    <button class="ag-side-btn" onclick="archiveCard()">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        Archivar
                    </button>
                    <button class="ag-side-btn danger" onclick="deleteCard()">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Eliminar
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
const API     = 'api/agenda.php';
const API_ADJ = 'api/agenda_adjuntos.php';
const BASE_URL = location.hostname === 'localhost' ? '/CRM-QUANTUN-Digital' : '/crm';

const LABELS = [
    { key:'verde',   bg:'#1B5A39', name:'Verde'   },
    { key:'azul',    bg:'#3F5E9E', name:'Azul'    },
    { key:'rojo',    bg:'#B91C1C', name:'Rojo'    },
    { key:'naranja', bg:'#D97706', name:'Naranja' },
    { key:'morado',  bg:'#7C3AED', name:'Morado'  },
    { key:'rosado',  bg:'#BE185D', name:'Rosado'  },
    { key:'gris',    bg:'#374151', name:'Gris'    },
    { key:'lima',    bg:'#65A30D', name:'Lima'    },
];

let _cols   = [];
let _cards  = {};
let _modal  = null;
let _chk    = [];
let _adj    = [];
let _drag   = null;
let _saveTmr = {};

/* ── Load ── */
async function loadBoard() {
    try {
        const r = await fetch(API);
        const d = await r.json();
        if (!d.success) return;
        _cols  = d.columnas;
        _cards = {};
        _cols.forEach(c => _cards[c.id] = []);
        d.tarjetas.forEach(t => { if (_cards[t.columna_id]) _cards[t.columna_id].push(t); });
        renderBoard();
    } catch(e) {
        document.getElementById('agCols').innerHTML =
            '<div style="color:rgba(255,100,100,.8);font-size:13px;padding:20px">Error al cargar la agenda.</div>';
    }
}

/* ── Render board ── */
function renderBoard() {
    const q = (document.getElementById('agSearch').value || '').toLowerCase();
    let html = '';
    _cols.forEach(col => {
        const cards = (_cards[col.id] || []).filter(c =>
            !q || c.titulo.toLowerCase().includes(q) || (c.descripcion||'').toLowerCase().includes(q)
        );
        html += renderCol(col, cards);
    });
    html += `<button class="ag-add-col-btn" onclick="addColumn()">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>Añadir otra lista</button>`;
    document.getElementById('agCols').innerHTML = html;
}

function renderCol(col, cards) {
    return `<div class="ag-col" id="col-${col.id}" data-col-id="${col.id}">
        <div class="ag-col-hd">
            <div class="ag-col-title" contenteditable="true" id="coltit-${col.id}"
                 onblur="saveColName(${col.id},this)"
                 onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur()}">${esc(col.nombre)}</div>
            <span class="ag-col-count">${cards.length}</span>
            <button class="ag-col-menu-btn" onclick="openColMenu(event,${col.id})">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="5" r="1" fill="currentColor"/>
                    <circle cx="12" cy="12" r="1" fill="currentColor"/>
                    <circle cx="12" cy="19" r="1" fill="currentColor"/>
                </svg>
            </button>
        </div>
        <div class="ag-col-body" id="colbody-${col.id}"
             ondragover="onColDragOver(event,${col.id})"
             ondragleave="onColDragLeave(event,${col.id})"
             ondrop="onDrop(event,${col.id})">
            ${cards.map(c => renderCard(c)).join('')}
        </div>
        <button class="ag-add-card-btn" id="addbtn-${col.id}" onclick="showAddCard(${col.id})">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>Añade una tarjeta
        </button>
        <div class="ag-add-card-form" id="addform-${col.id}">
            <textarea class="ag-add-card-input" id="addinput-${col.id}"
                placeholder="Introduce un título para esta tarjeta..."
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();saveNewCard(${col.id})}
                           if(event.key==='Escape')hideAddCard(${col.id})"></textarea>
            <div class="ag-add-card-actions">
                <button class="ag-add-card-save" onclick="saveNewCard(${col.id})">Añadir tarjeta</button>
                <button class="ag-add-card-cancel" onclick="hideAddCard(${col.id})">×</button>
            </div>
        </div>
    </div>`;
}

function renderCard(c) {
    const labels = (c.etiquetas||[]).map(k => {
        const l = LABELS.find(x=>x.key===k);
        return l ? `<div class="ag-label-bar" style="background:${l.bg}" title="${esc(l.name)}"></div>` : '';
    }).join('');

    const today = new Date().toISOString().slice(0,10);
    let dateBadge = '';
    if (c.fecha_venc) {
        const fv = c.fecha_venc.slice(0,10);
        const cls = c.completada ? '' : (fv < today ? 'vencida' : fv === today ? 'hoy' : '');
        const fmtd = new Date(fv+'T12:00').toLocaleDateString('es-CO',{day:'numeric',month:'short'});
        dateBadge = `<span class="ag-date-badge ${cls}">
            <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>${fmtd}</span>`;
    }

    const chkIcon = c.chk_total > 0
        ? `<span class="ag-card-ico"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>${c.chk_done}/${c.chk_total}</span>` : '';
    const adjIcon = c.adj_count > 0
        ? `<span class="ag-card-ico"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>${c.adj_count}</span>` : '';
    const descIcon = c.descripcion
        ? `<span class="ag-card-ico"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h10"/></svg></span>` : '';
    const doneIcon = c.completada
        ? `<span class="ag-done-check"><svg width="9" height="9" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>` : '';
    const hasFooter = c.fecha_venc || c.chk_total>0 || c.adj_count>0 || c.descripcion || c.completada;

    return `<div class="ag-card${c.completada?' completada':''}" id="card-${c.id}"
        data-card-id="${c.id}" data-col-id="${c.columna_id}"
        draggable="true"
        ondragstart="onCardDragStart(event,${c.id},${c.columna_id})"
        ondragend="onCardDragEnd(event)"
        onclick="openModal(${c.id})">
        ${(c.etiquetas||[]).length ? `<div class="ag-card-labels">${labels}</div>` : ''}
        <div class="ag-card-title">${esc(c.titulo)}</div>
        ${hasFooter ? `<div class="ag-card-foot">${doneIcon}${dateBadge}${descIcon}${chkIcon}${adjIcon}</div>` : ''}
    </div>`;
}

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ── Add card inline ── */
function showAddCard(colId) {
    document.getElementById('addbtn-'+colId).classList.add('adding');
    const f = document.getElementById('addform-'+colId);
    f.classList.add('show');
    const inp = document.getElementById('addinput-'+colId);
    inp.value=''; inp.focus();
    const body = document.getElementById('colbody-'+colId);
    body.scrollTop = body.scrollHeight;
}
function hideAddCard(colId) {
    document.getElementById('addbtn-'+colId).classList.remove('adding');
    document.getElementById('addform-'+colId).classList.remove('show');
}
async function saveNewCard(colId) {
    const inp = document.getElementById('addinput-'+colId);
    const titulo = (inp.value||'').trim();
    if (!titulo) { hideAddCard(colId); return; }
    const r = await fetch(`${API}?r=card`,{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({columna_id:colId,titulo})});
    const d = await r.json();
    if (d.success) {
        if (!_cards[colId]) _cards[colId]=[];
        _cards[colId].push({id:d.id,columna_id:colId,titulo,descripcion:null,etiquetas:[],
            prioridad:'media',fecha_venc:null,posicion:_cards[colId].length,
            archivada:0,completada:0,chk_total:0,chk_done:0,adj_count:0});
        hideAddCard(colId); renderBoard();
        setTimeout(()=>showAddCard(colId),50);
    }
}

/* ── Add column ── */
async function addColumn() {
    const r = await fetch(`${API}?r=col`,{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({nombre:'Nueva lista',posicion:_cols.length})});
    const d = await r.json();
    if (d.success) {
        _cols.push(d.col); _cards[d.col.id]=[];
        renderBoard();
        setTimeout(()=>{
            const el=document.getElementById('coltit-'+d.col.id);
            if(el){el.focus();document.execCommand('selectAll',false,null);}
        },50);
    }
}

/* ── Column name ── */
async function saveColName(id,el) {
    const nombre=(el.textContent||'').trim()||'Nueva lista';
    el.textContent=nombre;
    const col=_cols.find(c=>c.id==id); if(col) col.nombre=nombre;
    await fetch(`${API}?r=col`,{method:'PUT',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id,nombre})});
}

/* ── Column menu ── */
function openColMenu(e,colId) {
    e.stopPropagation(); closeColMenu();
    const menu=document.createElement('div');
    menu.className='ag-col-menu'; menu.id='agColMenu';
    menu.innerHTML=`
        <div class="ag-col-menu-item" onclick="closeColMenu();const el=document.getElementById('coltit-${colId}');if(el){el.focus();document.execCommand('selectAll',false,null)}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Renombrar lista
        </div>
        <div class="ag-col-menu-item del" onclick="closeColMenu();deleteColumn(${colId})">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Eliminar lista
        </div>`;
    const rect=e.currentTarget.getBoundingClientRect();
    menu.style.top=(rect.bottom+4)+'px';
    menu.style.left=Math.max(8,rect.left-140)+'px';
    document.body.appendChild(menu);
    setTimeout(()=>document.addEventListener('click',closeColMenu,{once:true}),0);
}
function closeColMenu(){const m=document.getElementById('agColMenu');if(m)m.remove();}

async function deleteColumn(colId) {
    const n=(_cards[colId]||[]).length;
    const msg=n>0?`Esta lista tiene ${n} tarjeta(s). Se eliminarán permanentemente.`:'Se eliminará la lista permanentemente.';
    const ok=await confirmAction(msg,{title:'¿Eliminar lista?'});
    if(!ok) return;
    await fetch(`${API}?r=col&id=${colId}`,{method:'DELETE'});
    _cols=_cols.filter(c=>c.id!=colId); delete _cards[colId]; renderBoard();
}

/* ── DRAG & DROP ── */
function onCardDragStart(e,cardId,colId) {
    _drag={cardId,colId};
    e.dataTransfer.effectAllowed='move';
    e.dataTransfer.setData('text/plain',String(cardId));
    setTimeout(()=>{const el=document.getElementById('card-'+cardId);if(el)el.classList.add('dragging');},0);
}
function onCardDragEnd(e) {
    document.querySelectorAll('.ag-card.dragging').forEach(el=>el.classList.remove('dragging'));
    document.querySelectorAll('.ag-placeholder').forEach(el=>el.remove());
    document.querySelectorAll('.ag-col.drag-over').forEach(el=>el.classList.remove('drag-over'));
    _drag=null;
}
function onColDragOver(e,colId) {
    if(!_drag) return;
    e.preventDefault(); e.dataTransfer.dropEffect='move';
    document.getElementById('col-'+colId)?.classList.add('drag-over');
    document.querySelectorAll('.ag-placeholder').forEach(el=>el.remove());
    const body=document.getElementById('colbody-'+colId);
    const cards=[...body.querySelectorAll('.ag-card:not(.dragging)')];
    let insertBefore=null;
    for(const card of cards){
        const rect=card.getBoundingClientRect();
        if(e.clientY<rect.top+rect.height/2){insertBefore=card;break;}
    }
    const ph=document.createElement('div'); ph.className='ag-placeholder';
    if(insertBefore) body.insertBefore(ph,insertBefore); else body.appendChild(ph);
}
function onColDragLeave(e,colId) {
    const col=document.getElementById('col-'+colId);
    if(col&&!col.contains(e.relatedTarget)){
        col.classList.remove('drag-over');
        col.querySelectorAll('.ag-placeholder').forEach(el=>el.remove());
    }
}
async function onDrop(e,targetColId) {
    e.preventDefault();
    if(!_drag) return;
    const {cardId,colId:fromColId}=_drag;
    document.querySelectorAll('.ag-placeholder').forEach(el=>el.remove());
    document.querySelectorAll('.ag-col.drag-over').forEach(el=>el.classList.remove('drag-over'));
    const body=document.getElementById('colbody-'+targetColId);
    const cards=[...body.querySelectorAll('.ag-card:not(.dragging)')];
    let newPos=cards.length;
    for(let i=0;i<cards.length;i++){
        const rect=cards[i].getBoundingClientRect();
        if(e.clientY<rect.top+rect.height/2){newPos=i;break;}
    }
    const fromArr=_cards[fromColId]||[];
    const idx=fromArr.findIndex(c=>c.id==cardId);
    if(idx<0) return;
    const [card]=fromArr.splice(idx,1);
    card.columna_id=targetColId;
    if(!_cards[targetColId])_cards[targetColId]=[];
    _cards[targetColId].splice(newPos,0,card);
    _cards[targetColId].forEach((c,i)=>c.posicion=i);
    if(fromColId!==targetColId)(_cards[fromColId]||[]).forEach((c,i)=>c.posicion=i);
    renderBoard();
    const updates=_cards[targetColId].map((c,i)=>
        fetch(`${API}?r=card`,{method:'PUT',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id:c.id,columna_id:targetColId,posicion:i})})
    );
    await Promise.all(updates);
}

/* ── MODAL ── */
async function openModal(cardId) {
    const r=await fetch(`${API}?r=detail&id=${cardId}`);
    const d=await r.json();
    if(!d.success) return;
    _modal=d.card; _modal.etiquetas=_modal.etiquetas||[];
    _chk=d.checklist; _adj=d.adjuntos;
    const ta=document.getElementById('mTitle');
    ta.value=_modal.titulo; autoResize(ta);
    const col=_cols.find(c=>c.id==_modal.columna_id);
    document.getElementById('mColLabel').textContent='En: '+(col?col.nombre:'');
    document.getElementById('mDesc').value=_modal.descripcion||'';
    document.getElementById('mFecha').value=_modal.fecha_venc?_modal.fecha_venc.slice(0,10):'';
    document.getElementById('mCompleteBtn').textContent=_modal.completada?'Desmarcar completada':'Marcar completada';
    renderModalLabels(); renderModalChk(); renderModalAdj();
    document.getElementById('agModal').classList.add('show');
}
function closeModal(){
    document.getElementById('agModal').classList.remove('show');
    _modal=null;
}

function renderModalLabels(){
    document.getElementById('mLabelsGrid').innerHTML=LABELS.map(l=>{
        const on=(_modal.etiquetas||[]).includes(l.key);
        return `<div class="ag-label-pill${on?' on':''}" style="background:${l.bg}" onclick="toggleLabel('${l.key}')">
            ${on?'<svg width="10" height="10" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>':''}
            ${esc(l.name)}</div>`;
    }).join('');
}

async function toggleLabel(key){
    if(!_modal) return;
    const idx=_modal.etiquetas.indexOf(key);
    if(idx>=0)_modal.etiquetas.splice(idx,1); else _modal.etiquetas.push(key);
    renderModalLabels();
    const card=(_cards[_modal.columna_id]||[]).find(c=>c.id==_modal.id);
    if(card) card.etiquetas=[..._modal.etiquetas];
    renderBoard();
    await fetch(`${API}?r=card`,{method:'PUT',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id:_modal.id,etiquetas:_modal.etiquetas})});
}

function renderModalChk(){
    const total=_chk.length, done=_chk.filter(c=>c.hecho).length;
    const pct=total?Math.round(done/total*100):0;
    document.getElementById('mChkBar').style.width=pct+'%';
    document.getElementById('mChkPct').textContent=total?pct+'%':'';
    document.getElementById('mChkList').innerHTML=_chk.map(item=>`
        <div class="ag-chk-item" id="chk-${item.id}">
            <input type="checkbox" class="ag-chk-cb" ${item.hecho?'checked':''} onchange="toggleChk(${item.id},this.checked)">
            <span class="ag-chk-text${item.hecho?' done':''}" contenteditable="true"
                  onblur="saveChkText(${item.id},this)"
                  onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur()}"
                >${esc(item.texto)}</span>
            <button class="ag-chk-del" onclick="deleteChk(${item.id})">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>`).join('');
    // Sync counts
    if(_modal){
        const card=(_cards[_modal.columna_id]||[]).find(c=>c.id==_modal.id);
        if(card){card.chk_total=total;card.chk_done=done;}
    }
}

async function addChkItem(){
    if(!_modal) return;
    const r=await fetch(`${API}?r=chk`,{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({tarjeta_id:_modal.id,texto:'Nuevo elemento'})});
    const d=await r.json();
    if(d.success){
        _chk.push({id:d.id,tarjeta_id:_modal.id,texto:'Nuevo elemento',hecho:0,posicion:_chk.length});
        renderModalChk();
        setTimeout(()=>{
            const el=document.querySelector(`#chk-${d.id} .ag-chk-text`);
            if(el){el.focus();document.execCommand('selectAll',false,null);}
        },50);
    }
}

async function toggleChk(id,val){
    const item=_chk.find(c=>c.id==id); if(item) item.hecho=val?1:0;
    renderModalChk();
    await fetch(`${API}?r=chk`,{method:'PUT',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id,hecho:val?1:0})});
    renderBoard();
}

async function saveChkText(id,el){
    const texto=(el.textContent||'').trim()||'Elemento';
    el.textContent=texto;
    const item=_chk.find(c=>c.id==id); if(item) item.texto=texto;
    await fetch(`${API}?r=chk`,{method:'PUT',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id,texto})});
}

async function deleteChk(id){
    await fetch(`${API}?r=chk&id=${id}`,{method:'DELETE'});
    _chk=_chk.filter(c=>c.id!=id); renderModalChk(); renderBoard();
}

function renderModalAdj(){
    if(!_adj.length){document.getElementById('mAdjList').innerHTML='';return;}
    document.getElementById('mAdjList').innerHTML=_adj.map(a=>{
        const isImg=a.mime&&a.mime.startsWith('image/');
        const thumb=isImg?`<img src="${BASE_URL}/${esc(a.url)}" alt="">`:
            `<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>`;
        const sub=a.tipo==='archivo'?(a.peso?formatBytes(a.peso):'archivo'):'Enlace';
        const nameEl=a.tipo==='enlace'
            ?`<a class="ag-adj-name link" href="${esc(a.url)}" target="_blank" rel="noopener noreferrer">${esc(a.nombre)}</a>`
            :`<span class="ag-adj-name">${esc(a.nombre)}</span>`;
        return `<div class="ag-adj-item">
            <div class="ag-adj-thumb">${thumb}</div>
            <div class="ag-adj-info">${nameEl}<div class="ag-adj-sub">${sub}</div></div>
            <button class="ag-adj-del" onclick="deleteAdj(${a.id})">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>`;
    }).join('');
}

function formatBytes(b){
    if(b<1024)return b+' B';
    if(b<1048576)return Math.round(b/1024)+' KB';
    return (b/1048576).toFixed(1)+' MB';
}

async function uploadFile(input){
    if(!_modal||!input.files.length) return;
    const fd=new FormData();
    fd.append('tipo','archivo');
    fd.append('tarjeta_id',_modal.id);
    fd.append('archivo',input.files[0]);
    const r=await fetch(API_ADJ,{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){_adj.unshift(d.adjunto);renderModalAdj();adjCountDelta(1);}
    else showToast(d.error||'Error al subir archivo','error');
    input.value='';
}

async function addLink(){
    if(!_modal) return;
    const url=prompt('URL del enlace (https://...)');
    if(!url) return;
    const nombre=prompt('Nombre descriptivo (opcional)')||url;
    const r=await fetch(API_ADJ,{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({tipo:'enlace',tarjeta_id:_modal.id,url,nombre})});
    const d=await r.json();
    if(d.success){_adj.unshift(d.adjunto);renderModalAdj();adjCountDelta(1);}
    else showToast(d.error||'Error al guardar enlace','error');
}

async function deleteAdj(id){
    await fetch(`${API_ADJ}?id=${id}`,{method:'DELETE'});
    _adj=_adj.filter(a=>a.id!=id);
    renderModalAdj(); adjCountDelta(-1);
}

function adjCountDelta(delta){
    if(!_modal) return;
    const card=(_cards[_modal.columna_id]||[]).find(c=>c.id==_modal.id);
    if(card) card.adj_count=Math.max(0,(card.adj_count||0)+delta);
    renderBoard();
}

/* ── Save debounce ── */
function schedSave(field){
    clearTimeout(_saveTmr[field]);
    _saveTmr[field]=setTimeout(()=>saveField(field),800);
}

async function saveField(field){
    if(!_modal) return;
    clearTimeout(_saveTmr[field]);
    const payload={id:_modal.id};
    if(field==='title'){
        const val=document.getElementById('mTitle').value.trim()||'Sin título';
        _modal.titulo=val; payload.titulo=val;
    } else if(field==='desc'){
        const val=document.getElementById('mDesc').value;
        _modal.descripcion=val; payload.descripcion=val;
        const lbl=document.getElementById('mDescSave');
        if(lbl) lbl.textContent='Guardando...';
    } else if(field==='fecha'){
        const val=document.getElementById('mFecha').value||null;
        _modal.fecha_venc=val; payload.fecha_venc=val;
    }
    await fetch(`${API}?r=card`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    if(field==='desc'){
        const lbl=document.getElementById('mDescSave');
        if(lbl){lbl.textContent='Guardado ✓';setTimeout(()=>{if(lbl)lbl.textContent='';},2000);}
    }
    const card=(_cards[_modal.columna_id]||[]).find(c=>c.id==_modal.id);
    if(card){
        if(payload.titulo) card.titulo=payload.titulo;
        if('descripcion' in payload) card.descripcion=payload.descripcion;
        if('fecha_venc'  in payload) card.fecha_venc=payload.fecha_venc;
    }
    renderBoard();
}

async function toggleComplete(){
    if(!_modal) return;
    _modal.completada=_modal.completada?0:1;
    document.getElementById('mCompleteBtn').textContent=_modal.completada?'Desmarcar completada':'Marcar completada';
    await fetch(`${API}?r=card`,{method:'PUT',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id:_modal.id,completada:_modal.completada})});
    const card=(_cards[_modal.columna_id]||[]).find(c=>c.id==_modal.id);
    if(card) card.completada=_modal.completada;
    renderBoard();
}

async function archiveCard(){
    if(!_modal) return;
    const ok=await confirmAction('La tarjeta se archivará y no aparecerá en el tablero.',{title:'¿Archivar tarjeta?'});
    if(!ok) return;
    await fetch(`${API}?r=card`,{method:'PUT',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id:_modal.id,archivada:1})});
    _cards[_modal.columna_id]=(_cards[_modal.columna_id]||[]).filter(c=>c.id!=_modal.id);
    closeModal(); renderBoard();
}

async function deleteCard(){
    if(!_modal) return;
    const ok=await confirmAction('Se eliminará la tarjeta y todos sus datos permanentemente.',{title:'¿Eliminar tarjeta?'});
    if(!ok) return;
    await fetch(`${API}?r=card&id=${_modal.id}`,{method:'DELETE'});
    _cards[_modal.columna_id]=(_cards[_modal.columna_id]||[]).filter(c=>c.id!=_modal.id);
    closeModal(); renderBoard();
}

function autoResize(el){el.style.height='auto';el.style.height=el.scrollHeight+'px';}

loadBoard();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
