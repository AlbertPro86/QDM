<?php
/**
 * CRM QUANTUN Digital — Estadísticas de Blog
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle      = 'Estadísticas de Blog';
$pageSubtitle   = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Blog · Estadísticas</span>';
include __DIR__ . '/includes/header.php';
?>

<!-- Estilos de página -->
<style>
.blog-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.bstat-card{background:var(--color-surface);border:1px solid var(--color-border);border-radius:14px;padding:20px 22px}
.bstat-card__label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--color-text-muted);margin-bottom:8px}
.bstat-card__num{font-size:30px;font-weight:700;color:var(--color-text);line-height:1;font-family:'JetBrains Mono',monospace}
.bstat-card__sub{font-size:12px;color:var(--color-text-muted);margin-top:4px}
.bstat-card--accent{background:var(--color-text);color:#fff}
.bstat-card--accent .bstat-card__label{color:rgba(255,255,255,.6)}
.bstat-card--accent .bstat-card__num{color:#fff}
.bstat-card--accent .bstat-card__sub{color:rgba(255,255,255,.55)}

.art-table{width:100%;border-collapse:collapse}
.art-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);padding:10px 14px;text-align:left;border-bottom:1px solid var(--color-border)}
.art-table td{padding:14px 14px;border-bottom:1px solid var(--color-border);vertical-align:middle}
.art-table tr:last-child td{border-bottom:none}
.art-table tr:hover td{background:var(--color-bg-hover,rgba(0,0,0,.025))}
.art-bar{height:6px;border-radius:99px;background:var(--color-border);margin-top:4px;overflow:hidden}
.art-bar span{display:block;height:100%;border-radius:99px;background:var(--color-text);transition:width .6s ease}
.art-slug{font-size:11px;color:var(--color-text-muted);font-family:'JetBrains Mono',monospace}
.art-title{font-size:13.5px;font-weight:600;color:var(--color-text);line-height:1.3}
.art-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:2px 9px;border-radius:99px}
.art-badge--up{background:rgba(34,197,94,.12);color:#15803d}
.no-data{text-align:center;padding:60px 20px;color:var(--color-text-muted)}
.no-data svg{margin:0 auto 12px;display:block;opacity:.3}
.chart-wrap{background:var(--color-surface);border:1px solid var(--color-border);border-radius:14px;padding:20px 22px;margin-bottom:28px}
.chart-wrap h3{font-size:13px;font-weight:700;color:var(--color-text);margin-bottom:16px}
.mini-bars{display:flex;align-items:flex-end;gap:3px;height:60px}
.mini-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px}
.mini-bar-col span{display:block;width:100%;background:var(--color-border);border-radius:3px 3px 0 0;min-height:2px;transition:height .4s ease}
.mini-bar-col span.has-data{background:var(--color-text)}
.mini-bar-col small{font-size:8px;color:var(--color-text-muted);white-space:nowrap}
@media(max-width:900px){.blog-stat-grid{grid-template-columns:repeat(2,1fr)}#chartFuentesGrid{grid-template-columns:1fr}}
@media(max-width:560px){.blog-stat-grid{grid-template-columns:1fr 1fr}}
</style>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="vertical-align:middle;margin-right:6px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Estadísticas de Blog
        </h1>
        <?php if (!empty($pageBreadcrumb)): ?>
            <div class="page-breadcrumb"><?= $pageBreadcrumb ?></div>
        <?php endif; ?>
    </div>
    <div class="page-header-right">
        <button class="btn btn-secondary btn-sm" id="btnRefresh" onclick="cargarStats()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Actualizar
        </button>
        <a href="https://quantundigital.com/blog.html" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Ver blog
        </a>
    </div>
</div>

<!-- Tarjetas resumen -->
<div class="blog-stat-grid" id="statCards">
    <div class="bstat-card">
        <div class="bstat-card__label">Visitas totales</div>
        <div class="bstat-card__num" id="numTotal">—</div>
        <div class="bstat-card__sub">Todas las páginas</div>
    </div>
    <div class="bstat-card">
        <div class="bstat-card__label">Visitantes únicos</div>
        <div class="bstat-card__num" id="numUnicos">—</div>
        <div class="bstat-card__sub">Por IP</div>
    </div>
    <div class="bstat-card">
        <div class="bstat-card__label">Últimos 7 días</div>
        <div class="bstat-card__num" id="num7d">—</div>
        <div class="bstat-card__sub">Visitas recientes</div>
    </div>
    <div class="bstat-card bstat-card--accent">
        <div class="bstat-card__label">Últimos 30 días</div>
        <div class="bstat-card__num" id="num30d">—</div>
        <div class="bstat-card__sub">Este mes</div>
    </div>
</div>

<!-- Gráfico diario + Fuentes -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;margin-bottom:28px" id="chartFuentesGrid">
    <div class="chart-wrap" style="margin-bottom:0">
        <h3>Visitas diarias — últimos 30 días</h3>
        <div class="mini-bars" id="miniChart">
            <div style="width:100%;text-align:center;color:var(--color-text-muted);font-size:12px;padding:16px 0">Cargando…</div>
        </div>
    </div>
    <div class="chart-wrap" style="margin-bottom:0">
        <h3>Fuentes de tráfico</h3>
        <div id="fuentesWrap">
            <div style="text-align:center;color:var(--color-text-muted);font-size:12px;padding:16px 0">Cargando…</div>
        </div>
    </div>
</div>

<!-- Tabla de artículos -->
<div class="card" style="padding:0;overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--color-border)">
        <h2 style="font-size:15px;font-weight:700;margin:0">Artículos</h2>
        <span id="artCount" style="font-size:12px;color:var(--color-text-muted)"></span>
    </div>
    <div id="artTableWrap">
        <div style="padding:40px;text-align:center;color:var(--color-text-muted)">Cargando estadísticas…</div>
    </div>
</div>

<script>
const API = (location.hostname === 'localhost' ? '/CRM-QUANTUN-Digital' : '/crm') + '/api/blog_visitas.php';

// Títulos legibles por slug (fallback si no vienen de la BD)
const TITULOS = {
    'ecosistema-digital':   'Por qué tu empresa necesita un ecosistema digital',
    'redes-sociales-leads': 'Cómo usar las redes sociales para captar prospectos',
    'ia-para-negocios':     'Inteligencia Artificial para tu negocio: guía práctica',
    'seo-ia-visibilidad':   'SEO e IA: cómo mejorar tu visibilidad en buscadores',
    'correos-profesionales':'Correos profesionales: la primera impresión que genera confianza',
};

function fmt(n){ return Number(n||0).toLocaleString('es-CO'); }

function renderChart(diario){
    const wrap = document.getElementById('miniChart');
    if (!diario || !diario.length){ wrap.innerHTML = '<div style="padding:16px;text-align:center;color:var(--color-text-muted);font-size:12px">Sin datos todavía</div>'; return; }

    // Rellenar los últimos 30 días
    const mapa = {};
    diario.forEach(function(d){ mapa[d.dia] = parseInt(d.visitas); });
    const days = [];
    for(var i = 29; i >= 0; i--){
        var dt = new Date(); dt.setDate(dt.getDate() - i);
        var key = dt.toISOString().slice(0,10);
        days.push({ dia: key, v: mapa[key] || 0 });
    }
    const max = Math.max(1, ...days.map(function(d){ return d.v; }));

    wrap.innerHTML = days.map(function(d){
        var pct = Math.round((d.v / max) * 100);
        var label = d.dia.slice(5); // MM-DD
        return '<div class="mini-bar-col" title="' + d.dia + ': ' + d.v + ' visitas">'
            + '<span style="height:' + Math.max(4, pct * 0.6) + 'px" class="' + (d.v > 0 ? 'has-data' : '') + '"></span>'
            + '<small>' + (label.endsWith('-01') || label.endsWith('-15') ? label : '') + '</small>'
            + '</div>';
    }).join('');
}

function renderTable(articulos){
    const wrap = document.getElementById('artTableWrap');
    document.getElementById('artCount').textContent = articulos.length + ' artículo' + (articulos.length !== 1 ? 's' : '');

    if(!articulos.length){
        wrap.innerHTML = '<div class="no-data"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><p>Aún no hay visitas registradas.<br>Publica un artículo y comparte el link.</p></div>';
        return;
    }
    const maxTotal = Math.max(1, ...articulos.map(function(a){ return parseInt(a.total); }));

    wrap.innerHTML = '<table class="art-table"><thead><tr>'
        + '<th>Artículo</th><th style="width:90px;text-align:right">Total</th>'
        + '<th style="width:90px;text-align:right">Únicos</th>'
        + '<th style="width:90px;text-align:right">7 días</th>'
        + '<th style="width:90px;text-align:right">30 días</th>'
        + '<th style="width:140px">Última visita</th>'
        + '</tr></thead><tbody>'
        + articulos.map(function(a, i){
            var titulo = a.titulo || TITULOS[a.slug] || a.slug;
            var pct = Math.round((parseInt(a.total) / maxTotal) * 100);
            var fecha = a.ultima_visita ? new Date(a.ultima_visita).toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—';
            return '<tr>'
                + '<td><div class="art-title">' + titulo + '</div>'
                + '<div class="art-slug">' + a.slug + '</div>'
                + '<div class="art-bar"><span style="width:' + pct + '%"></span></div></td>'
                + '<td style="text-align:right;font-weight:700;font-size:14px">' + fmt(a.total) + '</td>'
                + '<td style="text-align:right;color:var(--color-text-muted)">' + fmt(a.unicos) + '</td>'
                + '<td style="text-align:right">'
                + (parseInt(a.ultimos_7d) > 0 ? '<span class="art-badge art-badge--up">+' + fmt(a.ultimos_7d) + '</span>' : '<span style="color:var(--color-text-muted)">0</span>')
                + '</td>'
                + '<td style="text-align:right;color:var(--color-text-muted)">' + fmt(a.ultimos_30d) + '</td>'
                + '<td style="font-size:12px;color:var(--color-text-muted)">' + fecha + '</td>'
                + '</tr>';
        }).join('')
        + '</tbody></table>';
}

const FUENTE_ICONS = {
    'Facebook':          '🟦',
    'Instagram':         '🟣',
    'X / Twitter':       '⬛',
    'LinkedIn':          '🔵',
    'WhatsApp':          '🟢',
    'Google':            '🔴',
    'Bing':              '🟡',
    'YouTube':           '🔴',
    'TikTok':            '⬛',
    'Sitio propio':      '🌐',
    'Directo / Sin fuente': '🔗',
};

function renderFuentes(fuentes){
    const wrap = document.getElementById('fuentesWrap');
    if(!fuentes || !fuentes.length){
        wrap.innerHTML = '<div style="text-align:center;color:var(--color-text-muted);font-size:12px;padding:16px 0">Sin datos todavía</div>';
        return;
    }
    const maxV = Math.max(1, ...fuentes.map(function(f){ return parseInt(f.visitas); }));
    wrap.innerHTML = fuentes.map(function(f){
        var pct = Math.round((parseInt(f.visitas) / maxV) * 100);
        var icon = FUENTE_ICONS[f.fuente] || '🌍';
        return '<div style="margin-bottom:10px">'
            + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px">'
            + '<span style="font-size:12px;font-weight:600;color:var(--color-text)">' + icon + ' ' + f.fuente + '</span>'
            + '<span style="font-size:12px;font-weight:700;color:var(--color-text);font-family:\'JetBrains Mono\',monospace">' + fmt(f.visitas) + '</span>'
            + '</div>'
            + '<div style="height:5px;border-radius:99px;background:var(--color-border);overflow:hidden">'
            + '<div style="height:100%;width:' + pct + '%;background:var(--color-text);border-radius:99px;transition:width .5s ease"></div>'
            + '</div></div>';
    }).join('');
}

async function cargarStats(){
    const btn = document.getElementById('btnRefresh');
    if(btn){ btn.disabled = true; btn.textContent = 'Actualizando…'; }
    try {
        const r = await fetch(API, { credentials: 'include' });
        const d = await r.json();
        if(!d.success) throw new Error(d.error || 'Error');

        const t = d.totales || {};
        document.getElementById('numTotal').textContent  = fmt(t.total);
        document.getElementById('numUnicos').textContent = fmt(t.unicos);
        document.getElementById('num7d').textContent     = fmt(t.ultimos_7d);
        document.getElementById('num30d').textContent    = fmt(t.ultimos_30d);

        renderChart(d.diario);
        renderFuentes(d.fuentes || []);
        renderTable(d.articulos || []);
    } catch(e){
        document.getElementById('artTableWrap').innerHTML = '<div style="padding:30px;text-align:center;color:var(--color-danger,#ef4444)">Error al cargar estadísticas: ' + e.message + '</div>';
    } finally {
        if(btn){ btn.disabled = false; btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Actualizar'; }
    }
}

cargarStats();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
