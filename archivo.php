<?php
/**
 * CRM QUANTUN Digital — Módulo Archivo
 * Explorador de carpetas: Clientes → Año → Mes → Archivos
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle      = 'Archivo';
$pageSubtitle   = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Archivo</span>';

include __DIR__ . '/includes/header.php';
?>

<style>
/* ── Layout ──────────────────────────────────────────────────────────── */
.arc-wrap {
    display:flex; gap:16px;
    height:calc(100vh - 120px); min-height:500px;
}

/* ── Sidebar ─────────────────────────────────────────────────────────── */
.arc-sidebar {
    width:210px; flex-shrink:0;
    background:#FAFAF7; border:1.5px solid #E8E5DD;
    border-radius:8px; overflow-y:auto;
    display:flex; flex-direction:column;
}
.arc-sidebar-title {
    padding:13px 14px 8px; font-size:10px; font-weight:800;
    text-transform:uppercase; letter-spacing:.07em; color:#8A867C;
    border-bottom:1px solid #E8E5DD; flex-shrink:0;
}
.arc-tree-item {
    display:flex; align-items:center; gap:7px;
    padding:7px 14px; font-size:12px; font-weight:600; color:#57544D;
    cursor:pointer; transition:background .12s,color .12s;
    border:none; background:none; width:100%; text-align:left;
}
.arc-tree-item:hover  { background:#EFECE5; color:#0E0E0C; }
.arc-tree-item.active { background:#0E0E0C; color:#C6F24E; }
.arc-tree-item.active svg { stroke:#C6F24E !important; }
.arc-tree-item .badge {
    margin-left:auto; font-size:10px; font-weight:700;
    background:#E8E5DD; color:#57544D; border-radius:10px;
    padding:1px 6px; min-width:18px; text-align:center;
}
.arc-tree-item.active .badge { background:#3A3A2E; color:#C6F24E; }
.arc-tree-indent { padding-left:28px; }
.arc-tree-section {
    padding:8px 14px 3px; font-size:9px; font-weight:800;
    text-transform:uppercase; letter-spacing:.07em; color:#B0AB9F;
}
.arc-tree-divider { height:1px; background:#E8E5DD; margin:4px 0; }

/* ── Main area ───────────────────────────────────────────────────────── */
.arc-main {
    flex:1; min-width:0; display:flex; flex-direction:column; gap:10px; overflow:hidden;
}

/* ── Breadcrumb bar ──────────────────────────────────────────────────── */
.arc-breadcrumb-bar {
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    background:#FAFAF7; border:1.5px solid #E8E5DD;
    border-radius:8px; padding:9px 14px; flex-shrink:0;
    flex-wrap:wrap;
}
.arc-breadcrumb {
    display:flex; align-items:center; gap:2px; flex-wrap:wrap; flex:1; min-width:0;
}
.arc-crumb {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 8px; border-radius:4px;
    font-size:12px; font-weight:700; color:#57544D;
    cursor:pointer; transition:all .12s; border:none; background:none; font-family:inherit;
}
.arc-crumb:hover    { background:#EFECE5; color:#0E0E0C; }
.arc-crumb.current  { color:#0E0E0C; cursor:default; }
.arc-crumb.current:hover { background:none; }
.arc-crumb-sep      { color:#C8C4BB; font-size:12px; user-select:none; }

/* ── Filter strip ────────────────────────────────────────────────────── */
.arc-filters {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    background:#FAFAF7; border:1.5px solid #E8E5DD;
    border-radius:8px; padding:9px 14px; flex-shrink:0;
}
.arc-filters input, .arc-filters select {
    padding:5px 9px; border:1.5px solid #E8E5DD; border-radius:4px;
    font-size:12px; font-family:inherit; outline:none;
    background:#fff; color:#0E0E0C; transition:border-color .15s;
}
.arc-filters input:focus, .arc-filters select:focus { border-color:#0E0E0C; }
.arc-view-btn {
    padding:5px 7px; border:1.5px solid #E8E5DD; border-radius:4px;
    background:#fff; cursor:pointer; color:#8A867C; transition:all .15s;
    display:inline-flex; align-items:center;
}
.arc-view-btn.active, .arc-view-btn:hover { border-color:#0E0E0C; color:#0E0E0C; background:#FAFAF7; }
.arc-stat {
    display:inline-flex; align-items:center; gap:5px;
    font-size:11px; font-weight:700; color:#57544D;
    background:#EFECE5; border-radius:4px; padding:4px 9px;
}
.arc-clear-btn {
    display:inline-flex; align-items:center; gap:4px;
    padding:5px 10px; border:1.5px solid #E8E5DD; border-radius:4px;
    background:#fff; cursor:pointer; font-size:11px; font-weight:700; color:#57544D;
    font-family:inherit; transition:all .14s; white-space:nowrap;
}
.arc-clear-btn:hover { border-color:#ef4444; color:#ef4444; background:#FEF2F2; }

/* ── Scroll area ─────────────────────────────────────────────────────── */
.arc-scroll { flex:1; overflow-y:auto; }

/* ── Folder cards grid ───────────────────────────────────────────────── */
.arc-folder-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(170px, 1fr));
    gap:12px; padding:2px 2px 20px;
}
.arc-folder-card {
    background:#fff; border:1.5px solid #E8E5DD; border-radius:10px;
    overflow:hidden; cursor:pointer; transition:box-shadow .15s, border-color .15s, transform .12s;
    display:flex; flex-direction:column;
}
.arc-folder-card:hover {
    border-color:#0E0E0C; box-shadow:0 4px 16px rgba(14,14,12,.1);
    transform:translateY(-1px);
}
.arc-folder-card-top {
    padding:20px 16px 14px;
    display:flex; flex-direction:column; align-items:flex-start; gap:10px;
}
.arc-folder-icon-wrap {
    width:44px; height:44px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px; flex-shrink:0;
}
.arc-folder-label {
    font-size:13px; font-weight:800; color:#0E0E0C;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    width:100%;
}
.arc-folder-sub {
    font-size:11px; color:#8A867C; margin-top:1px;
}
.arc-folder-card-foot {
    border-top:1px solid #E8E5DD;
    padding:8px 14px;
    display:flex; align-items:center; justify-content:space-between;
    background:#FAFAF7;
}
.arc-folder-count {
    font-size:11px; font-weight:700;
    background:#E8E5DD; color:#57544D;
    border-radius:10px; padding:2px 8px;
}
.arc-folder-date { font-size:10px; color:#B0AB9F; }

/* ── File cards (grid) ───────────────────────────────────────────────── */
.arc-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(158px, 1fr));
    gap:12px; padding:2px 2px 20px;
}
.arc-card {
    background:#fff; border:1.5px solid #E8E5DD; border-radius:8px;
    overflow:hidden; display:flex; flex-direction:column;
    transition:box-shadow .15s, border-color .15s; cursor:pointer;
}
.arc-card:hover { border-color:#0E0E0C; box-shadow:0 2px 12px rgba(14,14,12,.1); }
.arc-card-thumb {
    height:96px; background:#F5F3EE;
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; flex-shrink:0;
}
.arc-card-thumb img { width:100%; height:100%; object-fit:cover; }
.arc-card-body { padding:8px 10px; flex:1; min-width:0; }
.arc-card-name {
    font-size:11px; font-weight:700; color:#0E0E0C;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:2px;
}
.arc-card-meta { font-size:10px; color:#8A867C; }
.arc-card-actions { display:flex; border-top:1px solid #E8E5DD; flex-shrink:0; }
.arc-card-actions button {
    flex:1; padding:6px; border:none; background:none; cursor:pointer;
    color:#8A867C; font-size:10px; font-weight:700; transition:all .12s;
    display:flex; align-items:center; justify-content:center; gap:3px;
}
.arc-card-actions button:hover { background:#FAFAF7; color:#0E0E0C; }
.arc-card-actions button.del:hover { background:#FEF2F2; color:#ef4444; }
.arc-card-actions button + button { border-left:1px solid #E8E5DD; }

/* ── File list (rows) ────────────────────────────────────────────────── */
.arc-list-wrap { background:#fff; border:1.5px solid #E8E5DD; border-radius:8px; overflow:hidden; }
.arc-list-header {
    display:flex; align-items:center; gap:12px; padding:8px 12px;
    background:#F5F3EE; border-bottom:1px solid #E8E5DD;
}
.arc-list-hcol { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#8A867C; }
.arc-list-row {
    display:flex; align-items:center; gap:12px; padding:9px 12px;
    border-bottom:1px solid #E8E5DD; transition:background .12s; cursor:pointer;
}
.arc-list-row:hover { background:#FAFAF7; }
.arc-list-row:last-child { border-bottom:none; }
.arc-list-icon { width:34px; height:34px; border-radius:6px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.arc-list-info { flex:1; min-width:0; }
.arc-list-name { font-size:12px; font-weight:700; color:#0E0E0C; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.arc-list-meta { font-size:10px; color:#8A867C; margin-top:1px; }
.arc-list-cliente { font-size:11px; color:#57544D; font-weight:600; min-width:120px; flex-shrink:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.arc-list-date { font-size:10px; color:#8A867C; flex-shrink:0; white-space:nowrap; min-width:75px; text-align:right; }
.arc-list-actions { display:flex; gap:4px; flex-shrink:0; }
.arc-list-actions button {
    padding:4px 7px; border:1.5px solid #E8E5DD; border-radius:4px;
    background:#fff; cursor:pointer; color:#8A867C; font-size:10px; font-weight:700;
    transition:all .12s; display:inline-flex; align-items:center; gap:3px; white-space:nowrap;
}
.arc-list-actions button:hover { border-color:#0E0E0C; color:#0E0E0C; }
.arc-list-actions button.del:hover { border-color:#ef4444; color:#ef4444; background:#FEF2F2; }

/* ── Type chips ──────────────────────────────────────────────────────── */
.chip { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:3px; font-size:10px; font-weight:700; }
.chip-pdf   { background:#FEE2E2; color:#991B1B; }
.chip-image { background:#DBEAFE; color:#1E40AF; }
.chip-word  { background:#DCFCE7; color:#14532D; }
.chip-excel { background:#D1FAE5; color:#065F46; }
.chip-doc   { background:#F3F4F6; color:#374151; }

/* ── Preview modal ───────────────────────────────────────────────────── */
#arcPreviewModal {
    position:fixed; inset:0; background:rgba(0,0,0,.72);
    z-index:2000; display:none; align-items:center; justify-content:center; padding:16px;
}
#arcPreviewModal.show { display:flex; }
.arc-preview-box {
    background:#fff; border-radius:10px; width:100%; max-width:820px;
    max-height:90vh; overflow:hidden; display:flex; flex-direction:column;
    box-shadow:0 8px 48px rgba(0,0,0,.35);
}
.arc-preview-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 20px; border-bottom:1.5px solid #E8E5DD; flex-shrink:0;
}
.arc-preview-title { font-size:14px; font-weight:700; color:#0E0E0C; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:520px; }
.arc-preview-body { flex:1; overflow:auto; display:flex; align-items:center; justify-content:center; background:#F5F3EE; min-height:300px; }
.arc-preview-body img { max-width:100%; max-height:70vh; object-fit:contain; }
.arc-preview-body iframe { width:100%; height:70vh; border:none; }
.arc-preview-footer { padding:12px 20px; border-top:1.5px solid #E8E5DD; display:flex; gap:8px; justify-content:flex-end; flex-shrink:0; }

/* ── Folder card empty state ─────────────────────────────────────────── */
.arc-folder-card.empty { opacity:.6; }
.arc-folder-card.empty:hover { opacity:1; border-color:#C8C4BB; box-shadow:none; transform:none; }
.arc-folder-card.papelera { border-color:#FECACA; }
.arc-folder-card.papelera:hover { border-color:#ef4444; }

/* ── Papelera section header ─────────────────────────────────────────── */
.arc-section-header {
    display:flex; align-items:center; gap:8px;
    padding:4px 2px 10px; font-size:11px; font-weight:800;
    text-transform:uppercase; letter-spacing:.06em; color:#8A867C;
}

/* ── Empty state ─────────────────────────────────────────────────────── */
.arc-empty { text-align:center; padding:60px 20px; color:#8A867C; }
.arc-empty svg { margin:0 auto 12px; display:block; opacity:.3; }
.arc-empty p { font-size:13px; font-weight:700; color:#57544D; margin-bottom:4px; }
.arc-empty small { font-size:12px; color:#B0AB9F; }

@media (max-width:768px) {
    .arc-wrap { flex-direction:column; height:auto; }
    .arc-sidebar { width:100%; max-height:200px; }
    .arc-folder-grid { grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); }
    .arc-grid { grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); }
}
</style>

<div class="arc-wrap">

    <!-- ── Sidebar ────────────────────────────────────────────────────── -->
    <aside class="arc-sidebar" id="arcSidebar">
        <div class="arc-sidebar-title">Explorador</div>

        <button class="arc-tree-item active" data-type="root" onclick="arcGoRoot(this)">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            Todas las carpetas
            <span class="badge" id="badgeTotal">—</span>
        </button>

        <div class="arc-tree-divider"></div>
        <div class="arc-tree-section">Tipo de archivo</div>

        <button class="arc-tree-item" data-type="type" data-val="image" onclick="arcTypeFilter('image',this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Imágenes
            <span class="badge" id="badgeImage">—</span>
        </button>

        <button class="arc-tree-item" data-type="type" data-val="pdf" onclick="arcTypeFilter('pdf',this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            PDFs
            <span class="badge" id="badgePdf">—</span>
        </button>

        <button class="arc-tree-item" data-type="type" data-val="word" onclick="arcTypeFilter('word',this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Word / Docs
            <span class="badge" id="badgeWord">—</span>
        </button>

        <button class="arc-tree-item" data-type="type" data-val="excel" onclick="arcTypeFilter('excel',this)">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M3 3h18v18H3V3z"/>
            </svg>
            Excel / CSV
            <span class="badge" id="badgeExcel">—</span>
        </button>

        <div class="arc-tree-divider"></div>
        <div class="arc-tree-section">Clientes</div>
        <div id="arcClientTree" style="overflow-y:auto;flex:1;min-height:0"></div>

        <div class="arc-tree-divider"></div>
        <button class="arc-tree-item" id="arcPapeleraBtn" onclick="arcOpenPapelera(this)" style="color:#ef4444" title="Archivos de clientes eliminados">
            <svg width="14" height="14" fill="none" stroke="#ef4444" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Papelera
            <span class="badge" id="badgePapelera" style="background:#FEE2E2;color:#991B1B">—</span>
        </button>
    </aside>

    <!-- ── Área principal ─────────────────────────────────────────────── -->
    <div class="arc-main">

        <!-- Breadcrumb -->
        <div class="arc-breadcrumb-bar">
            <nav class="arc-breadcrumb" id="arcBreadcrumb">
                <!-- Populated by JS -->
            </nav>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                <div class="arc-stat" id="arcStatChip">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/></svg>
                    <span id="arcStatLabel">—</span>
                </div>
                <button class="arc-clear-btn" id="arcClearBtn" onclick="arcClearAll()" title="Limpiar filtros" style="display:none">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Limpiar
                </button>
            </div>
        </div>

        <!-- Filtros (solo visibles en nivel de archivos) -->
        <div class="arc-filters" id="arcFiltersBar" style="display:none">
            <div style="position:relative;flex:1;min-width:160px;max-width:280px">
                <svg width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);pointer-events:none">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="arcSearch" type="text" placeholder="Buscar en esta carpeta…" style="padding-left:28px;width:100%;box-sizing:border-box" oninput="arcDebouncedSearch()">
            </div>
            <button class="arc-view-btn active" id="btnViewGrid" onclick="arcSetView('grid')" title="Cuadrícula">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            </button>
            <button class="arc-view-btn" id="btnViewList" onclick="arcSetView('list')" title="Lista">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            </button>
        </div>

        <!-- Contenido -->
        <div class="arc-scroll" id="arcContent">
            <div class="arc-empty">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
                <p>Cargando carpetas…</p>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal previsualización ─────────────────────────────────────────── -->
<div id="arcPreviewModal" onclick="if(event.target===this)arcClosePreview()">
    <div class="arc-preview-box">
        <div class="arc-preview-header">
            <h3 class="arc-preview-title" id="arcPreviewTitle">Vista previa</h3>
            <button onclick="arcClosePreview()" style="background:none;border:none;cursor:pointer;padding:4px;border-radius:4px;color:#8A867C;transition:color .15s" onmouseenter="this.style.color='#0E0E0C'" onmouseleave="this.style.color='#8A867C'">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="arc-preview-body" id="arcPreviewBody"><p style="color:#8A867C">Cargando…</p></div>
        <div class="arc-preview-footer">
            <span id="arcPreviewMeta" style="flex:1;font-size:11px;color:#8A867C;display:flex;align-items:center"></span>
            <a id="arcPreviewDl" href="#" download
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#0E0E0C;color:#C6F24E;border-radius:4px;font-size:12px;font-weight:700;text-decoration:none;transition:filter .15s"
               onmouseenter="this.style.filter='brightness(1.2)'" onmouseleave="this.style.filter=''">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Descargar
            </a>
            <button onclick="arcClosePreview()" style="padding:8px 16px;border:1.5px solid #E8E5DD;border-radius:4px;background:#fff;font-size:12px;font-weight:600;cursor:pointer;color:#57544D">Cerrar</button>
        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════════════
   ESTADO DE NAVEGACIÓN
   _arcPath: [] = raíz | [{type:'client',id,label}] | [...,{type:'year',id,label}] | [...,{type:'month',id,label}]
   _arcAllFiles: todos los archivos del contexto actual (cache)
   _arcTypeFilter: '' | 'image' | 'pdf' | 'word' | 'excel'
   ═══════════════════════════════════════════════════════════════════════ */
let _arcPath       = [];
let _arcAllFiles   = [];
let _arcTypeFilter = '';
let _arcView       = 'grid';
let _arcSearch     = '';
let _arcDebTimer   = null;
let _arcClientes   = [];

const MONTHS = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const FOLDER_COLORS = ['#3B82F6','#8B5CF6','#EC4899','#F59E0B','#10B981','#EF4444','#06B6D4','#F97316','#6366F1','#14B8A6'];

/* ── Init ────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    _loadClientes();
    arcLoadLevel();
});

/* ── Clientes para el sidebar ────────────────────────────────────────── */
async function _loadClientes() {
    try {
        const r = await fetch('api/archivo_manager.php?action=all_clients', { credentials:'include' });
        const d = await r.json();
        _arcClientes = d.data || [];
        _renderClientTree();
        _loadPapeleraBadge();
    } catch(e) {}
}

async function _loadPapeleraBadge() {
    try {
        const r = await fetch('api/archivo_manager.php?action=papelera', { credentials:'include' });
        const d = await r.json();
        const count = (d.data || []).length;
        const badge = document.getElementById('badgePapelera');
        badge.textContent = count;
        badge.style.display = count > 0 ? '' : 'none';
        document.getElementById('arcPapeleraBtn').style.display = count > 0 ? '' : 'none';
    } catch(e) {}
}

function _renderClientTree() {
    const el = document.getElementById('arcClientTree');
    if (!_arcClientes.length) {
        el.innerHTML = '<div style="padding:10px 14px;font-size:11px;color:#B0AB9F">Sin clientes</div>';
        return;
    }
    el.innerHTML = _arcClientes.map((c,i) => {
        const color = FOLDER_COLORS[i % FOLDER_COLORS.length];
        const hasFiles = (c.total_archivos || 0) > 0;
        return `<button class="arc-tree-item arc-tree-indent" data-type="client" data-id="${c.id}"
            onclick="arcOpenClient(${c.id},'${_esc(c.nombre_comercial)}',this)"
            title="${_esc(c.nombre_comercial)}" style="${!hasFiles?'opacity:.6':''}">
            <svg width="13" height="13" fill="none" stroke="${color}" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">${_esc(c.nombre_comercial)}</span>
            ${hasFiles ? `<span style="font-size:9px;color:${color};font-weight:800;flex-shrink:0">${c.total_archivos}</span>` : ''}
        </button>`;
    }).join('');
}

/* ── Navegación principal ────────────────────────────────────────────── */
function arcGoRoot(btn) {
    _arcPath = [];
    _arcTypeFilter = '';
    _arcSearch = '';
    if (document.getElementById('arcSearch')) document.getElementById('arcSearch').value = '';
    _sidebarActive(btn);
    arcLoadLevel();
}

function arcOpenClient(id, label, btn) {
    _arcPath = [{ type:'client', id, label }];
    _arcTypeFilter = '';
    _sidebarActive(btn);
    arcLoadLevel();
}

function arcOpenYear(year) {
    // _arcPath[0] must be client
    _arcPath = [_arcPath[0], { type:'year', id:year, label:String(year) }];
    arcLoadLevel();
}

function arcOpenMonth(month) {
    // _arcPath[0]=client, [1]=year
    _arcPath = [_arcPath[0], _arcPath[1], { type:'month', id:month, label:MONTHS[month] }];
    arcLoadLevel();
}

function arcNavTo(depth) {
    _arcPath = _arcPath.slice(0, depth);
    _arcTypeFilter = '';
    _arcSearch = '';
    if (document.getElementById('arcSearch')) document.getElementById('arcSearch').value = '';
    // sincronizar sidebar
    if (depth === 0) {
        _sidebarActive(document.querySelector('[data-type="root"]'));
    } else if (depth === 1) {
        if (_arcPath[0].type === 'papelera') {
            _sidebarActive(document.getElementById('arcPapeleraBtn'));
        } else {
            const btn = document.querySelector(`[data-type="client"][data-id="${_arcPath[0].id}"]`);
            _sidebarActive(btn);
        }
    }
    arcLoadLevel();
}

function arcOpenPapelera(btn) {
    _arcPath = [{ type:'papelera', id:'papelera', label:'Papelera' }];
    _arcTypeFilter = '';
    _sidebarActive(btn);
    arcLoadLevel();
}

function arcTypeFilter(type, btn) {
    // Filtro de tipo: muestra todos los archivos de ese tipo (nivel archivo)
    _arcTypeFilter = (_arcTypeFilter === type) ? '' : type;
    if (_arcTypeFilter) {
        _arcPath = []; // volver a raíz con filtro
        _sidebarActive(btn);
        arcLoadLevel();
    } else {
        arcGoRoot(document.querySelector('[data-type="root"]'));
    }
}

function arcClearAll() {
    _arcPath = [];
    _arcTypeFilter = '';
    _arcSearch = '';
    if (document.getElementById('arcSearch')) document.getElementById('arcSearch').value = '';
    _sidebarActive(document.querySelector('[data-type="root"]'));
    arcLoadLevel();
}

function arcDebouncedSearch() {
    _arcSearch = document.getElementById('arcSearch')?.value.trim() || '';
    clearTimeout(_arcDebTimer);
    _arcDebTimer = setTimeout(() => _arcRenderFiles(_arcAllFiles), 350);
}

function arcSetView(v) {
    _arcView = v;
    document.getElementById('btnViewGrid').classList.toggle('active', v==='grid');
    document.getElementById('btnViewList').classList.toggle('active', v==='list');
    _arcRenderFiles(_arcAllFiles);
}

function _sidebarActive(btn) {
    document.querySelectorAll('.arc-tree-item').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
}

/* ── Carga según nivel ───────────────────────────────────────────────── */
async function arcLoadLevel() {
    _arcUpdateBreadcrumb();
    const content = document.getElementById('arcContent');
    content.innerHTML = `<div class="arc-empty"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="animation:spin 1.2s linear infinite;opacity:.4"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><p style="margin-top:8px;font-size:12px;color:#B0AB9F">Cargando…</p></div>`;

    const level = _arcPath.length;

    try {
        // Papelera
        if (level === 1 && _arcPath[0].type === 'papelera') {
            document.getElementById('arcFiltersBar').style.display = 'flex';
            document.getElementById('arcClearBtn').style.display = 'inline-flex';
            const d = await _arcFetch(new URLSearchParams({ action:'papelera' }));
            _arcAllFiles = d.data || [];
            _arcRenderPapelera(_arcAllFiles);
            _arcUpdateStat(`${_arcAllFiles.length} archivo${_arcAllFiles.length!==1?'s':''} en papelera`);
            return;
        }

        // Si hay filtro de tipo, cargar todos los archivos de ese tipo
        if (_arcTypeFilter && level === 0) {
            const params = new URLSearchParams({ action:'list', type: _arcTypeFilter });
            const d = await _arcFetch(params);
            _arcAllFiles = d.data || [];
            _arcUpdateBadgesGlobal(_arcAllFiles);
            document.getElementById('arcFiltersBar').style.display = 'flex';
            _arcRenderFiles(_arcAllFiles);
            _arcUpdateStat(`${_arcAllFiles.length} archivo${_arcAllFiles.length!==1?'s':''}`);
            document.getElementById('arcClearBtn').style.display = 'inline-flex';
            return;
        }

        if (level === 0) {
            // Raíz: mostrar TODAS las carpetas de clientes
            document.getElementById('arcFiltersBar').style.display = 'none';
            document.getElementById('arcClearBtn').style.display = 'none';
            // Cargar todos los clientes y los archivos para badges
            const [dClients, dFiles] = await Promise.all([
                _arcFetch(new URLSearchParams({ action:'all_clients' })),
                _arcFetch(new URLSearchParams({ action:'list' })),
            ]);
            _arcAllFiles = dFiles.data || [];
            _arcUpdateBadgesGlobal(_arcAllFiles);
            _arcRenderClientFolders(dClients.data || [], _arcAllFiles);
        }
        else if (level === 1) {
            // Dentro de un cliente: mostrar carpetas de año
            document.getElementById('arcFiltersBar').style.display = 'none';
            document.getElementById('arcClearBtn').style.display = 'inline-flex';
            const params = new URLSearchParams({ action:'list', cliente_id: _arcPath[0].id });
            const d = await _arcFetch(params);
            const all = d.data || [];
            _arcAllFiles = all;
            _arcRenderYearFolders(all);
        }
        else if (level === 2) {
            // Dentro de cliente+año: mostrar carpetas de mes
            document.getElementById('arcFiltersBar').style.display = 'none';
            document.getElementById('arcClearBtn').style.display = 'inline-flex';
            const params = new URLSearchParams({ action:'list', cliente_id: _arcPath[0].id, year: _arcPath[1].id });
            const d = await _arcFetch(params);
            const all = d.data || [];
            _arcAllFiles = all;
            _arcRenderMonthFolders(all);
        }
        else if (level === 3) {
            // Nivel archivo: cliente+año+mes
            document.getElementById('arcFiltersBar').style.display = 'flex';
            document.getElementById('arcClearBtn').style.display = 'inline-flex';
            const params = new URLSearchParams({ action:'list', cliente_id: _arcPath[0].id, year: _arcPath[1].id, month: _arcPath[2].id });
            const d = await _arcFetch(params);
            const all = d.data || [];
            _arcAllFiles = all;
            _arcRenderFiles(all);
            _arcUpdateStat(`${all.length} archivo${all.length!==1?'s':''}`);
        }
    } catch(e) {
        content.innerHTML = '<div class="arc-empty"><p style="color:#ef4444">Error al cargar archivos.</p></div>';
    }
}

async function _arcFetch(params) {
    const r = await fetch(`api/archivo_manager.php?${params}`, { credentials:'include' });
    return await r.json();
}

/* ── Render: Carpetas de clientes (TODOS) ────────────────────────────── */
function _arcRenderClientFolders(allClients, allFiles) {
    // Construir mapa de conteo y última fecha desde los archivos
    const fileMap = {};
    allFiles.forEach(f => {
        const k = f.cliente_id;
        if (!fileMap[k]) fileMap[k] = { count:0, lastDate:'' };
        fileMap[k].count++;
        if (!fileMap[k].lastDate || f.fecha_full > fileMap[k].lastDate) fileMap[k].lastDate = f.fecha_full;
    });

    _arcUpdateStat(`${allClients.length} carpeta${allClients.length!==1?'s':''}`);

    if (!allClients.length) {
        document.getElementById('arcContent').innerHTML = `<div class="arc-empty">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
            <p>Sin clientes registrados</p><small>Crea clientes para ver sus carpetas aquí.</small>
        </div>`;
        return;
    }

    const html = allClients.map((c,i) => {
        const color   = FOLDER_COLORS[i % FOLDER_COLORS.length];
        const info    = fileMap[c.id] || { count:0, lastDate:'' };
        const count   = info.count;
        const isEmpty = count === 0;
        return `<div class="arc-folder-card${isEmpty?' empty':''}" onclick="arcOpenClient(${c.id},'${_esc(c.nombre_comercial)}',null)" title="${_esc(c.nombre_comercial)}">
            <div class="arc-folder-card-top">
                <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
                    <div class="arc-folder-icon-wrap" style="background:${color}18">
                        <svg width="24" height="24" fill="none" stroke="${color}" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                    </div>
                    <svg width="14" height="14" fill="none" stroke="#C8C4BB" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div style="width:100%">
                    <div class="arc-folder-label">${_esc(c.nombre_comercial)}</div>
                    <div class="arc-folder-sub">${isEmpty ? 'Carpeta vacía' : 'Carpeta del cliente'}</div>
                </div>
            </div>
            <div class="arc-folder-card-foot">
                <span class="arc-folder-count" style="${isEmpty?'background:#F3F4F6;color:#9CA3AF':''}">${count} archivo${count!==1?'s':''}</span>
                <span class="arc-folder-date">${isEmpty ? '' : _fmtDateShort(info.lastDate)}</span>
            </div>
        </div>`;
    }).join('');

    document.getElementById('arcContent').innerHTML = `<div class="arc-folder-grid">${html}</div>`;
}

/* ── Render: Papelera ────────────────────────────────────────────────── */
function _arcRenderPapelera(all) {
    if (!all.length) {
        document.getElementById('arcContent').innerHTML = `<div class="arc-empty">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            <p>Papelera vacía</p><small>Los archivos de clientes eliminados aparecen aquí.</small>
        </div>`;
        return;
    }
    // Filtrar si hay búsqueda
    let data = all;
    if (_arcSearch) {
        const q = _arcSearch.toLowerCase();
        data = data.filter(f => f.nombre.toLowerCase().includes(q) || f.cliente_nombre.toLowerCase().includes(q));
    }
    // Renderizar como lista de archivos con aviso
    const warning = `<div style="display:flex;align-items:center;gap:8px;background:#FEF2F2;border:1.5px solid #FECACA;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:12px;color:#991B1B;font-weight:600">
        <svg width="14" height="14" fill="none" stroke="#ef4444" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        Estos archivos pertenecen a clientes que fueron eliminados del sistema. Los archivos permanecen hasta que los elimines manualmente.
    </div>`;
    if (_arcView === 'grid') {
        document.getElementById('arcContent').innerHTML = warning + `<div class="arc-grid">${data.map(_arcCardHtml).join('')}</div>`;
    } else {
        document.getElementById('arcContent').innerHTML = warning + `
            <div class="arc-list-wrap">
                <div class="arc-list-header">
                    <div style="width:34px;flex-shrink:0"></div>
                    <div class="arc-list-hcol" style="flex:1">Nombre</div>
                    <div class="arc-list-hcol" style="min-width:120px">Cliente (eliminado)</div>
                    <div class="arc-list-hcol" style="min-width:75px;text-align:right">Fecha</div>
                    <div class="arc-list-hcol" style="min-width:100px;text-align:right">Acciones</div>
                </div>
                <div>${data.map(_arcListRowHtml).join('')}</div>
            </div>`;
    }
}

/* ── Render: Carpetas de año ─────────────────────────────────────────── */
function _arcRenderYearFolders(all) {
    const map = {};
    all.forEach(f => {
        const y = f.fecha ? f.fecha.substring(0,4) : '—';
        if (!map[y]) map[y] = { year:parseInt(y)||0, label:y, count:0, lastDate:'' };
        map[y].count++;
        if (!map[y].lastDate || f.fecha_full > map[y].lastDate) map[y].lastDate = f.fecha_full;
    });

    const years = Object.values(map).sort((a,b) => b.year - a.year);
    _arcUpdateStat(`${years.length} año${years.length!==1?'s':''} · ${all.length} archivos`);

    if (!years.length) {
        document.getElementById('arcContent').innerHTML = `<div class="arc-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><p>Sin archivos</p></div>`;
        return;
    }

    const html = years.map(y => `
        <div class="arc-folder-card" onclick="arcOpenYear(${y.year})" title="Abrir año ${y.label}">
            <div class="arc-folder-card-top">
                <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
                    <div class="arc-folder-icon-wrap" style="background:#F3F4F6">
                        <svg width="24" height="24" fill="none" stroke="#6B7280" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <svg width="14" height="14" fill="none" stroke="#C8C4BB" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div style="width:100%">
                    <div class="arc-folder-label" style="font-size:18px;letter-spacing:-.5px">${y.label}</div>
                    <div class="arc-folder-sub">Año</div>
                </div>
            </div>
            <div class="arc-folder-card-foot">
                <span class="arc-folder-count">${y.count} archivo${y.count!==1?'s':''}</span>
                <span class="arc-folder-date">${_fmtDateShort(y.lastDate)}</span>
            </div>
        </div>`).join('');

    document.getElementById('arcContent').innerHTML = `<div class="arc-folder-grid">${html}</div>`;
}

/* ── Render: Carpetas de mes ─────────────────────────────────────────── */
function _arcRenderMonthFolders(all) {
    const map = {};
    all.forEach(f => {
        const m = f.fecha ? parseInt(f.fecha.substring(5,7)) : 0;
        if (!map[m]) map[m] = { month:m, label:MONTHS[m]||'—', count:0, lastDate:'' };
        map[m].count++;
        if (!map[m].lastDate || f.fecha_full > map[m].lastDate) map[m].lastDate = f.fecha_full;
    });

    const months = Object.values(map).sort((a,b) => b.month - a.month);
    _arcUpdateStat(`${months.length} mes${months.length!==1?'es':''} · ${all.length} archivos`);

    if (!months.length) {
        document.getElementById('arcContent').innerHTML = `<div class="arc-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><p>Sin archivos</p></div>`;
        return;
    }

    const monthColors = ['','#EF4444','#F97316','#EAB308','#22C55E','#10B981','#06B6D4','#3B82F6','#8B5CF6','#EC4899','#F59E0B','#14B8A6','#6366F1'];
    const html = months.map(m => {
        const color = monthColors[m.month] || '#6B7280';
        return `<div class="arc-folder-card" onclick="arcOpenMonth(${m.month})" title="Abrir ${m.label}">
            <div class="arc-folder-card-top">
                <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
                    <div class="arc-folder-icon-wrap" style="background:${color}18">
                        <svg width="24" height="24" fill="none" stroke="${color}" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                    </div>
                    <svg width="14" height="14" fill="none" stroke="#C8C4BB" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div style="width:100%">
                    <div class="arc-folder-label">${m.label}</div>
                    <div class="arc-folder-sub">${_arcPath[1]?.label || ''}</div>
                </div>
            </div>
            <div class="arc-folder-card-foot">
                <span class="arc-folder-count">${m.count} archivo${m.count!==1?'s':''}</span>
                <span class="arc-folder-date">${_fmtDateShort(m.lastDate)}</span>
            </div>
        </div>`;
    }).join('');

    document.getElementById('arcContent').innerHTML = `<div class="arc-folder-grid">${html}</div>`;
}

/* ── Render: Archivos ────────────────────────────────────────────────── */
function _arcRenderFiles(all) {
    let data = all;
    // Filtro por tipo (si hay)
    if (_arcTypeFilter) data = data.filter(f => f.categoria === _arcTypeFilter);
    // Filtro por búsqueda
    if (_arcSearch) {
        const q = _arcSearch.toLowerCase();
        data = data.filter(f => f.nombre.toLowerCase().includes(q) || (f.descripcion||'').toLowerCase().includes(q));
    }

    _arcUpdateStat(`${data.length} archivo${data.length!==1?'s':''}`);

    if (!data.length) {
        document.getElementById('arcContent').innerHTML = `<div class="arc-empty">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h7l2 2h7a2 2 0 012 2v3"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 19l2 2 4-4"/></svg>
            <p>Sin archivos aquí</p><small>No hay archivos que coincidan con los filtros.</small>
        </div>`;
        return;
    }

    if (_arcView === 'grid') {
        document.getElementById('arcContent').innerHTML = `<div class="arc-grid">${data.map(_arcCardHtml).join('')}</div>`;
    } else {
        document.getElementById('arcContent').innerHTML = `
            <div class="arc-list-wrap">
                <div class="arc-list-header">
                    <div style="width:34px;flex-shrink:0"></div>
                    <div class="arc-list-hcol" style="flex:1">Nombre</div>
                    <div class="arc-list-hcol" style="min-width:120px">Cliente</div>
                    <div class="arc-list-hcol" style="min-width:75px;text-align:right">Fecha</div>
                    <div class="arc-list-hcol" style="min-width:130px;text-align:right">Acciones</div>
                </div>
                <div>${data.map(_arcListRowHtml).join('')}</div>
            </div>`;
    }
}

/* ── Breadcrumb ──────────────────────────────────────────────────────── */
function _arcUpdateBreadcrumb() {
    const bc = document.getElementById('arcBreadcrumb');
    const parts = [{ label:'Archivo', depth:0 }, ..._arcPath.map((p,i) => ({ label:p.label, depth:i+1 }))];
    bc.innerHTML = parts.map((p,i) => {
        const isLast = i === parts.length - 1;
        const sep    = i > 0 ? `<span class="arc-crumb-sep">/</span>` : '';
        const icon   = i === 0 ? `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>` : '';
        if (isLast) return `${sep}<span class="arc-crumb current">${icon}${_esc(p.label)}</span>`;
        return `${sep}<button class="arc-crumb" onclick="arcNavTo(${p.depth})">${icon}${_esc(p.label)}</button>`;
    }).join('');
}

/* ── Badges globales (sidebar) ───────────────────────────────────────── */
function _arcUpdateBadgesGlobal(data) {
    const counts = { image:0, pdf:0, word:0, excel:0 };
    data.forEach(f => { if (counts[f.categoria] !== undefined) counts[f.categoria]++; });
    document.getElementById('badgeTotal').textContent = data.length;
    document.getElementById('badgeImage').textContent = counts.image;
    document.getElementById('badgePdf').textContent   = counts.pdf;
    document.getElementById('badgeWord').textContent  = counts.word;
    document.getElementById('badgeExcel').textContent = counts.excel;
}

function _arcUpdateStat(text) {
    document.getElementById('arcStatLabel').textContent = text;
}

/* ── Fallback cuando una imagen no carga ─────────────────────────────── */
function _arcImgFallback(img, bg, cat, col) {
    const p = img.parentElement;
    if (!p) return;
    p.style.background = bg;
    p.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">' + _arcIconSvg(cat, 32, col) + '</div>';
}

/* ── Card/Row HTML ───────────────────────────────────────────────────── */
function _arcCardHtml(f) {
    const ib = { image:'#DBEAFE', pdf:'#FEE2E2', word:'#DCFCE7', excel:'#D1FAE5', doc:'#F3F4F6' };
    const ic = { image:'#1E40AF', pdf:'#991B1B', word:'#14532D', excel:'#065F46', doc:'#374151' };
    const bg  = ib[f.categoria] || '#F3F4F6';
    const col = ic[f.categoria] || '#374151';
    // IMPORTANTE: no inyectar SVG (con comillas dobles) dentro de atributos HTML con comillas dobles
    const thumb = f.categoria === 'image'
        ? `<img src="${_esc(f.url)}" alt="" style="width:100%;height:100%;object-fit:cover" onerror="_arcImgFallback(this,'${bg}','image','${col}')">`
        : `<div style="background:${bg};width:100%;height:100%;display:flex;align-items:center;justify-content:center">${_arcIconSvg(f.categoria,32,col)}</div>`;
    const fd = JSON.stringify(f).replace(/"/g,'&quot;');
    return `<div class="arc-card" title="${_esc(f.nombre)}">
        <div class="arc-card-thumb" onclick="arcPreview(${fd})">${thumb}</div>
        <div class="arc-card-body" onclick="arcPreview(${fd})">
            <div class="arc-card-name">${_esc(f.nombre)}</div>
            <div class="arc-card-meta">${_fmtDate(f.fecha)} · ${f.peso_fmt}</div>
        </div>
        <div class="arc-card-actions">
            <button onclick="arcPreview(${fd})">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Ver
            </button>
            <button onclick="arcDownload('${_esc(f.url)}','${_esc(f.nombre)}')">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Bajar
            </button>
            <button class="del" onclick="arcDelete(${f.id},'${f.source}','${_esc(f.nombre)}')">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </div>`;
}

function _arcListRowHtml(f) {
    const ib = { image:'#DBEAFE', pdf:'#FEE2E2', word:'#DCFCE7', excel:'#D1FAE5', doc:'#F3F4F6' };
    const ic = { image:'#1E40AF', pdf:'#991B1B', word:'#14532D', excel:'#065F46', doc:'#374151' };
    const bg  = ib[f.categoria] || '#F3F4F6';
    const col = ic[f.categoria] || '#374151';
    const fd  = JSON.stringify(f).replace(/"/g,'&quot;');
    // Para imágenes: miniatura real en lugar de solo ícono
    const iconContent = f.categoria === 'image'
        ? `<img src="${_esc(f.url)}" alt="" style="width:34px;height:34px;object-fit:cover;border-radius:6px" onerror="_arcImgFallback(this,'${bg}','image','${col}')">`
        : _arcIconSvg(f.categoria, 18, col);
    return `<div class="arc-list-row" onclick="arcPreview(${fd})">
        <div class="arc-list-icon" style="background:${f.categoria==='image'?'transparent':bg};overflow:hidden">${iconContent}</div>
        <div class="arc-list-info">
            <div class="arc-list-name">${_esc(f.nombre)}</div>
            <div class="arc-list-meta">${f.peso_fmt}${f.descripcion?' · '+_esc(f.descripcion).substring(0,45):''}</div>
        </div>
        <div class="arc-list-cliente">${_esc(f.cliente_nombre)}</div>
        <div class="arc-list-date">${_fmtDate(f.fecha)}</div>
        <div class="arc-list-actions" onclick="event.stopPropagation()">
            <button onclick="arcPreview(${fd})">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>Ver
            </button>
            <button onclick="arcDownload('${_esc(f.url)}','${_esc(f.nombre)}')">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Bajar
            </button>
            <button class="del" onclick="arcDelete(${f.id},'${f.source}','${_esc(f.nombre)}')">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </div>`;
}

function _arcIconSvg(cat, size, color) {
    const s = size||20, c = color||'#374151';
    const icons = {
        image:`<svg width="${s}" height="${s}" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
        pdf:  `<svg width="${s}" height="${s}" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>`,
        word: `<svg width="${s}" height="${s}" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
        excel:`<svg width="${s}" height="${s}" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M3 3h18v18H3V3z"/></svg>`,
        doc:  `<svg width="${s}" height="${s}" fill="none" stroke="${c}" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>`,
    };
    return icons[cat] || icons.doc;
}

/* ── Acciones sobre archivos ─────────────────────────────────────────── */
function arcPreview(f) {
    document.getElementById('arcPreviewTitle').textContent = f.nombre;
    document.getElementById('arcPreviewMeta').textContent  = f.cliente_nombre + ' · ' + _fmtDate(f.fecha) + ' · ' + f.peso_fmt;
    document.getElementById('arcPreviewDl').href     = f.url;
    document.getElementById('arcPreviewDl').download = f.nombre;
    const body = document.getElementById('arcPreviewBody');
    if (f.categoria === 'image') {
        body.innerHTML = `<img src="${_esc(f.url)}" alt="${_esc(f.nombre)}" onerror="this.alt='No se puede mostrar'">`;
    } else if (f.categoria === 'pdf') {
        body.innerHTML = `<iframe src="${_esc(f.url)}#toolbar=1"></iframe>`;
    } else {
        body.innerHTML = `<div style="text-align:center;padding:40px 20px">
            ${_arcIconSvg(f.categoria,64,'#B0AB9F')}
            <p style="margin-top:16px;font-size:15px;font-weight:700;color:#0E0E0C">${_esc(f.nombre)}</p>
            <p style="font-size:12px;color:#8A867C;margin-top:4px">${f.peso_fmt} · ${_fmtDate(f.fecha)}</p>
            <p style="font-size:12px;color:#B0AB9F;margin-top:16px">Sin previsualización disponible. Usa "Descargar".</p>
        </div>`;
    }
    document.getElementById('arcPreviewModal').classList.add('show');
}

function arcClosePreview() {
    document.getElementById('arcPreviewModal').classList.remove('show');
    document.getElementById('arcPreviewBody').innerHTML = '';
}

function arcDownload(url, nombre) {
    const a = document.createElement('a');
    a.href = url; a.download = nombre; a.style.display = 'none';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}

async function arcDelete(id, source, nombre) {
    const ok = await confirmAction(
        `¿Eliminar "${nombre}"? Esta acción borrará el archivo del servidor y no se puede deshacer.`,
        { title:'Eliminar archivo', okText:'Eliminar', okColor:'#ef4444', okHover:'#dc2626' }
    );
    if (!ok) return;
    try {
        const r = await fetch(`api/archivo_manager.php?id=${id}&source=${source}`, { method:'DELETE', credentials:'include' });
        const d = await r.json();
        if (d.success) {
            showToast('Archivo eliminado', 'success');
            arcLoadLevel();
            _loadClientes(); // refresca sidebar + badge papelera
        } else {
            showToast(d.error || 'Error al eliminar', 'error');
        }
    } catch(e) { showToast('Error de conexión', 'error'); }
}

/* ── Helpers ─────────────────────────────────────────────────────────── */
function _fmtDate(str) {
    if (!str) return '—';
    try {
        const d = new Date(str.includes('T') ? str : str+'T12:00:00');
        return d.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
    } catch { return str; }
}
function _fmtDateShort(str) {
    if (!str) return '—';
    try {
        const d = new Date(str.includes('T') ? str : str+'T12:00:00');
        return d.toLocaleDateString('es-CO', { day:'2-digit', month:'short' });
    } catch { return str; }
}
function _esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

document.addEventListener('keydown', e => { if (e.key==='Escape') arcClosePreview(); });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
