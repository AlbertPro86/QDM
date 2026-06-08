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

<style>
/* ── Resumen ── */
.blog-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.bstat-card{background:var(--color-surface);border:1px solid var(--color-border);border-radius:14px;padding:20px 22px}
.bstat-card__label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--color-text-muted);margin-bottom:8px}
.bstat-card__num{font-size:30px;font-weight:700;color:var(--color-text);line-height:1;font-family:'JetBrains Mono',monospace}
.bstat-card__sub{font-size:12px;color:var(--color-text-muted);margin-top:4px}
.bstat-card--accent{background:var(--color-text);color:#fff}
.bstat-card--accent .bstat-card__label{color:rgba(255,255,255,.6)}
.bstat-card--accent .bstat-card__num{color:#fff}
.bstat-card--accent .bstat-card__sub{color:rgba(255,255,255,.55)}

/* ── Tabla artículos ── */
.art-table{width:100%;border-collapse:collapse}
.art-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);padding:10px 14px;text-align:left;border-bottom:1px solid var(--color-border)}
.art-table td{padding:13px 14px;border-bottom:1px solid var(--color-border);vertical-align:middle}
.art-table tr:last-child td{border-bottom:none}
.art-table tr[data-slug]{cursor:pointer}
.art-table tr[data-slug]:hover td{background:var(--color-bg-hover,rgba(0,0,0,.025))}
.art-bar{height:5px;border-radius:99px;background:var(--color-border);margin-top:5px;overflow:hidden}
.art-bar span{display:block;height:100%;border-radius:99px;background:var(--color-text);transition:width .6s ease}
.art-slug{font-size:11px;color:var(--color-text-muted);font-family:'JetBrains Mono',monospace}
.art-title{font-size:13.5px;font-weight:600;color:var(--color-text);line-height:1.3}
.art-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:2px 9px;border-radius:99px}
.art-badge--up{background:rgba(34,197,94,.12);color:#15803d}
.art-ver-btn{font-size:11px;font-weight:600;padding:4px 10px;border-radius:7px;border:1px solid var(--color-border);background:transparent;color:var(--color-text-muted);cursor:pointer;transition:all .15s;white-space:nowrap}
.art-ver-btn:hover{background:var(--color-text);color:var(--color-surface,#fff);border-color:var(--color-text)}

/* ── Mini chart ── */
.no-data{text-align:center;padding:60px 20px;color:var(--color-text-muted)}
.no-data svg{margin:0 auto 12px;display:block;opacity:.3}
.chart-wrap{background:var(--color-surface);border:1px solid var(--color-border);border-radius:14px;padding:20px 22px;margin-bottom:28px}
.chart-wrap h3{font-size:13px;font-weight:700;color:var(--color-text);margin-bottom:16px}
.mini-bars{display:flex;align-items:flex-end;gap:3px;height:60px}
.mini-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px}
.mini-bar-col span{display:block;width:100%;background:var(--color-border);border-radius:3px 3px 0 0;min-height:2px;transition:height .4s ease}
.mini-bar-col span.has-data{background:var(--color-text)}
.mini-bar-col small{font-size:8px;color:var(--color-text-muted);white-space:nowrap}

/* ── Vista detalle (full-screen inline) ── */
.dv-header{display:flex;align-items:center;gap:14px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:14px;padding:16px 20px;margin-bottom:20px}
.dv-header__info{flex:1;min-width:0}
.dv-header__title{font-size:15px;font-weight:700;color:var(--color-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dv-header__sub{font-size:12px;color:var(--color-text-muted);margin-top:2px}

/* ── Tabla detalle visitas ── */
.vd-table{width:100%;border-collapse:collapse}
.vd-table th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);padding:10px 14px;text-align:left;border-bottom:1px solid var(--color-border);white-space:nowrap;background:var(--color-surface)}
.vd-table td{padding:11px 14px;border-bottom:1px solid var(--color-border);font-size:12px;color:var(--color-text);vertical-align:middle}
.vd-table tr:last-child td{border-bottom:none}
.vd-table tr:hover td{background:var(--color-bg-hover,rgba(0,0,0,.02))}
.vd-time{font-family:'JetBrains Mono',monospace;font-size:11.5px;white-space:nowrap}
.vd-ip{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--color-text-muted)}
.vd-badge{display:inline-block;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;white-space:nowrap}
.vd-badge--mobile{background:rgba(99,102,241,.1);color:#4338ca}
.vd-badge--desktop{background:rgba(34,197,94,.1);color:#15803d}
.vd-badge--tablet{background:rgba(245,158,11,.1);color:#b45309}
.vd-src{font-size:11px;color:var(--color-text-muted);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block}
.vd-pager{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--color-border);font-size:12px;color:var(--color-text-muted)}

@keyframes livePulse{0%,100%{box-shadow:0 0 0 3px rgba(34,197,94,.2)}50%{box-shadow:0 0 0 6px rgba(34,197,94,.05)}}
@media(max-width:900px){.blog-stat-grid{grid-template-columns:repeat(2,1fr)}#chartFuentesGrid{grid-template-columns:1fr}}
@media(max-width:560px){.blog-stat-grid{grid-template-columns:1fr 1fr}.dv-header{flex-wrap:wrap}}
</style>

<!-- ══ CABECERA DE PÁGINA ══ -->
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
    <!-- Botones vista stats -->
    <div class="page-header-right" id="phStats">
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
    <!-- Botón vista detalle -->
    <div class="page-header-right" id="phDetalle" style="display:none">
        <button class="btn btn-secondary btn-sm" onclick="cerrarDetalle()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a estadísticas
        </button>
    </div>
</div>

<!-- ══ VISTA ESTADÍSTICAS ══ -->
<div id="statsView">

    <!-- Conectados ahora -->
    <div style="display:flex;align-items:center;gap:10px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:12px;padding:14px 20px;margin-bottom:20px">
        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.2);flex-shrink:0;animation:livePulse 1.8s ease-in-out infinite"></span>
        <span style="font-size:13px;color:var(--color-text-muted)">En el sitio ahora mismo:</span>
        <span id="liveCount" style="font-size:18px;font-weight:800;color:var(--color-text);font-family:'JetBrains Mono',monospace">—</span>
        <span style="font-size:13px;color:var(--color-text-muted)">visitante(s) activo(s)</span>
        <div id="livePaginas" style="margin-left:8px;display:flex;gap:6px;flex-wrap:wrap"></div>
        <span id="liveUpdate" style="margin-left:auto;font-size:11px;color:var(--color-text-muted)"></span>
    </div>

    <!-- Tarjetas resumen -->
    <div class="blog-stat-grid">
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

</div><!-- /statsView -->

<!-- ══ VISTA DETALLE ══ -->
<div id="detalleView" style="display:none">

    <!-- Sub-cabecera del artículo -->
    <div class="dv-header">
        <div class="dv-header__info">
            <div class="dv-header__title" id="dvTitle">—</div>
            <div class="dv-header__sub" id="dvSub"></div>
        </div>
        <div id="dvPagerInfo" style="font-size:12px;color:var(--color-text-muted);white-space:nowrap"></div>
    </div>

    <!-- Tabla visitas individuales -->
    <div class="card" style="padding:0;overflow:hidden">
        <div id="dvBody" style="overflow-x:auto">
            <div style="padding:40px;text-align:center;color:var(--color-text-muted)">Cargando…</div>
        </div>
        <div class="vd-pager" id="dvPager" style="display:none">
            <span id="dvPagerText"></span>
            <div style="display:flex;gap:6px">
                <button class="btn btn-secondary btn-sm" id="dvPrev" onclick="cambiarPagina(-1)">← Anterior</button>
                <button class="btn btn-secondary btn-sm" id="dvNext" onclick="cambiarPagina(1)">Siguiente →</button>
            </div>
        </div>
    </div>

</div><!-- /detalleView -->

<script>
const API = (location.hostname === 'localhost' ? '/CRM-QUANTUN-Digital' : '/crm') + '/api/blog_visitas.php';

const TITULOS = {
    'ecosistema-digital':    'Por qué tu empresa necesita un ecosistema digital',
    'redes-sociales-leads':  'Cómo usar las redes sociales para captar prospectos',
    'ia-para-negocios':      'Inteligencia Artificial para tu negocio: guía práctica',
    'seo-ia-visibilidad':    'SEO e IA: cómo mejorar tu visibilidad en buscadores',
    'correos-profesionales': 'Correos profesionales: la primera impresión que genera confianza',
};

function fmt(n){ return Number(n||0).toLocaleString('es-CO'); }

function fmtDt(ts){
    if (!ts) return '—';
    return new Date(ts.replace(' ','T') + '-05:00').toLocaleString('es-CO',{
        day:'2-digit', month:'short', year:'numeric',
        hour:'2-digit', minute:'2-digit', second:'2-digit'
    });
}

/* ── Parsers ── */
function parseFuente(ref){
    if (!ref) return 'Directo';
    if (/facebook\.com/i.test(ref))               return 'Facebook';
    if (/instagram\.com/i.test(ref))              return 'Instagram';
    if (/t\.co|twitter\.com|x\.com/i.test(ref))   return 'X / Twitter';
    if (/linkedin\.com/i.test(ref))               return 'LinkedIn';
    if (/whatsapp\.com|wa\.me/i.test(ref))         return 'WhatsApp';
    if (/google\./i.test(ref))                    return 'Google';
    if (/bing\.com/i.test(ref))                   return 'Bing';
    if (/youtube\.com/i.test(ref))                return 'YouTube';
    if (/tiktok\.com/i.test(ref))                 return 'TikTok';
    if (/quantundigital\.com/i.test(ref))         return 'Sitio propio';
    try { return new URL(ref).hostname.replace('www.',''); } catch(e){ return ref.slice(0,30); }
}
function parseDevice(ua){
    if (!ua) return { label:'Desconocido', cls:'' };
    if (/iPad|Tablet/i.test(ua))
        return { label:'Tablet', cls:'vd-badge--tablet' };
    if (/Mobile|Android|iPhone|iPod|IEMobile|Opera Mini/i.test(ua))
        return { label:'Móvil', cls:'vd-badge--mobile' };
    return { label:'Escritorio', cls:'vd-badge--desktop' };
}
function parseBrowser(ua){
    if (!ua) return '';
    if (/Edg\//i.test(ua))   return ' · Edge';
    if (/Chrome/i.test(ua))  return ' · Chrome';
    if (/Firefox/i.test(ua)) return ' · Firefox';
    if (/Safari/i.test(ua))  return ' · Safari';
    return '';
}

/* ── Vista estadísticas ── */
function renderChart(diario){
    const wrap = document.getElementById('miniChart');
    if (!diario || !diario.length){
        wrap.innerHTML = '<div style="padding:16px;text-align:center;color:var(--color-text-muted);font-size:12px">Sin datos todavía</div>';
        return;
    }
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
        var pct   = Math.round((d.v / max) * 100);
        var label = d.dia.slice(5);
        return '<div class="mini-bar-col" title="' + d.dia + ': ' + d.v + ' visitas">'
            + '<span style="height:' + Math.max(4, pct * 0.6) + 'px" class="' + (d.v > 0 ? 'has-data' : '') + '"></span>'
            + '<small>' + (label.endsWith('-01') || label.endsWith('-15') ? label : '') + '</small>'
            + '</div>';
    }).join('');
}

const FUENTE_ICONS = {
    'Facebook':'F','Instagram':'IG','X / Twitter':'X','LinkedIn':'in',
    'WhatsApp':'W','Google':'G','Bing':'B','YouTube':'YT','TikTok':'TK',
    'Sitio propio':'Q','Directo / Sin fuente':'—',
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
        return '<div style="margin-bottom:10px">'
            + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px">'
            + '<span style="font-size:12px;font-weight:600;color:var(--color-text)">' + f.fuente + '</span>'
            + '<span style="font-size:12px;font-weight:700;color:var(--color-text);font-family:\'JetBrains Mono\',monospace">' + fmt(f.visitas) + '</span>'
            + '</div>'
            + '<div style="height:5px;border-radius:99px;background:var(--color-border);overflow:hidden">'
            + '<div style="height:100%;width:' + pct + '%;background:var(--color-text);border-radius:99px;transition:width .5s ease"></div>'
            + '</div></div>';
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
        + '<th>Artículo</th>'
        + '<th style="width:80px;text-align:right">Total</th>'
        + '<th style="width:80px;text-align:right">Únicos</th>'
        + '<th style="width:80px;text-align:right">7 días</th>'
        + '<th style="width:80px;text-align:right">30 días</th>'
        + '<th style="width:140px">Última visita</th>'
        + '<th style="width:90px"></th>'
        + '</tr></thead><tbody>'
        + articulos.map(function(a){
            var titulo = a.titulo || TITULOS[a.slug] || a.slug;
            var pct    = Math.round((parseInt(a.total) / maxTotal) * 100);
            var fecha  = fmtDt(a.ultima_visita);
            var slug   = a.slug.replace(/'/g,"\\'");
            var tit    = titulo.replace(/'/g,"\\'");
            return '<tr data-slug="' + a.slug + '" onclick="verDetalle(\'' + slug + '\',\'' + tit + '\',' + a.total + ')">'
                + '<td><div class="art-title">' + titulo + '</div>'
                + '<div class="art-slug">' + a.slug + '</div>'
                + '<div class="art-bar"><span style="width:' + pct + '%"></span></div></td>'
                + '<td style="text-align:right;font-weight:700;font-size:14px">' + fmt(a.total) + '</td>'
                + '<td style="text-align:right;color:var(--color-text-muted)">' + fmt(a.unicos) + '</td>'
                + '<td style="text-align:right">'
                + (parseInt(a.ultimos_7d) > 0 ? '<span class="art-badge art-badge--up">+' + fmt(a.ultimos_7d) + '</span>' : '<span style="color:var(--color-text-muted)">0</span>')
                + '</td>'
                + '<td style="text-align:right;color:var(--color-text-muted)">' + fmt(a.ultimos_30d) + '</td>'
                + '<td style="font-size:11px;color:var(--color-text-muted)">' + fecha + '</td>'
                + '<td><button class="art-ver-btn" onclick="event.stopPropagation();verDetalle(\'' + slug + '\',\'' + tit + '\',' + a.total + ')">Ver visitas</button></td>'
                + '</tr>';
        }).join('')
        + '</tbody></table>';
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

/* ── Contador en tiempo real ── */
const API_LIVE = (location.hostname === 'localhost' ? '/CRM-QUANTUN-Digital' : '/crm') + '/api/blog_visitas.php?action=activos';
const PAGINA_LABELS = { home:'Inicio', blog:'Blog', 'ecosistema-digital':'Ecosistema digital', 'redes-sociales-leads':'Redes sociales', 'ia-para-negocios':'IA para negocios', 'seo-ia-visibilidad':'SEO e IA', 'correos-profesionales':'Correos profesionales' };

async function actualizarLive(){
    try {
        const r = await fetch(API_LIVE, { credentials: 'include' });
        const d = await r.json();
        if (!d.success) return;
        document.getElementById('liveCount').textContent = d.activos;
        const chips = (d.paginas || []).map(function(p){
            var label = PAGINA_LABELS[p.pagina] || p.pagina;
            return '<span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px;background:rgba(34,197,94,.1);color:#15803d">'
                + label + ' (' + p.n + ')</span>';
        }).join('');
        document.getElementById('livePaginas').innerHTML = chips;
        document.getElementById('liveUpdate').textContent = 'Actualizado ' + new Date().toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
    } catch(e){}
}
actualizarLive();
setInterval(actualizarLive, 30000);

/* ══ VISTA DETALLE ══ */
var _dSlug = '', _dTitulo = '', _dPage = 1, _dPages = 1, _dTotal = 0;

function verDetalle(slug, titulo, total){
    _dSlug   = slug;
    _dTitulo = titulo;
    _dTotal  = parseInt(total) || 0;
    _dPage   = 1;

    document.getElementById('dvTitle').textContent = titulo;
    document.getElementById('dvSub').textContent   = slug + ' · ' + fmt(_dTotal) + ' visita' + (_dTotal !== 1 ? 's' : '') + ' en total';
    document.getElementById('dvBody').innerHTML    = '<div style="padding:48px;text-align:center;color:var(--color-text-muted)">Cargando…</div>';
    document.getElementById('dvPager').style.display = 'none';
    document.getElementById('dvPagerInfo').textContent = '';

    // Cambiar vistas
    document.getElementById('statsView').style.display   = 'none';
    document.getElementById('detalleView').style.display = 'block';
    document.getElementById('phStats').style.display     = 'none';
    document.getElementById('phDetalle').style.display   = 'flex';

    window.scrollTo({ top: 0, behavior: 'smooth' });
    cargarDetalle();
}

function cerrarDetalle(){
    document.getElementById('detalleView').style.display = 'none';
    document.getElementById('statsView').style.display   = 'block';
    document.getElementById('phDetalle').style.display   = 'none';
    document.getElementById('phStats').style.display     = 'flex';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cambiarPagina(dir){
    var np = _dPage + dir;
    if (np < 1 || np > _dPages) return;
    _dPage = np;
    document.getElementById('dvBody').innerHTML = '<div style="padding:48px;text-align:center;color:var(--color-text-muted)">Cargando…</div>';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    cargarDetalle();
}

async function cargarDetalle(){
    try {
        var url = API + '?action=detalle&slug=' + encodeURIComponent(_dSlug) + '&page=' + _dPage;
        var r   = await fetch(url, { credentials: 'include' });
        var d   = await r.json();
        if (!d.success) throw new Error(d.error || 'Error');

        _dPages  = d.pages || 1;
        var vis  = d.visitas || [];
        var body = document.getElementById('dvBody');
        var pager= document.getElementById('dvPager');

        if (!vis.length){
            body.innerHTML = '<div style="padding:48px;text-align:center;color:var(--color-text-muted)">Sin visitas registradas.</div>';
            pager.style.display = 'none';
            return;
        }

        body.innerHTML = '<table class="vd-table"><thead><tr>'
            + '<th style="width:36px;text-align:right">#</th>'
            + '<th style="min-width:160px">Fecha y hora</th>'
            + '<th style="min-width:120px">IP</th>'
            + '<th style="min-width:140px">Dispositivo</th>'
            + '<th style="min-width:110px">Fuente</th>'
            + '<th>URL de referencia</th>'
            + '</tr></thead><tbody>'
            + vis.map(function(v, i){
                var num    = (_dPage - 1) * 50 + i + 1;
                var dt     = fmtDt(v.created_at);
                var ip     = v.ip_address || '—';
                var dev    = parseDevice(v.user_agent);
                var brow   = parseBrowser(v.user_agent);
                var fuente = parseFuente(v.referer);
                var refHtml= v.referer
                    ? '<span class="vd-src" title="' + v.referer.replace(/"/g,'&quot;') + '">' + v.referer + '</span>'
                    : '<span style="color:var(--color-text-muted);font-style:italic;font-size:11px">Sin referencia</span>';
                return '<tr>'
                    + '<td style="text-align:right;color:var(--color-text-muted);font-size:11px">' + num + '</td>'
                    + '<td><span class="vd-time">' + dt + '</span></td>'
                    + '<td><span class="vd-ip">' + ip + '</span></td>'
                    + '<td><span class="vd-badge ' + dev.cls + '">' + dev.label + brow + '</span></td>'
                    + '<td style="font-size:12px;font-weight:600">' + fuente + '</td>'
                    + '<td>' + refHtml + '</td>'
                    + '</tr>';
            }).join('')
            + '</tbody></table>';

        /* paginador */
        var start = (_dPage - 1) * 50 + 1;
        var end   = Math.min(_dPage * 50, d.total);
        var info  = start + '–' + end + ' de ' + fmt(d.total) + ' visitas  ·  Pág. ' + _dPage + ' / ' + _dPages;
        document.getElementById('dvPagerInfo').textContent = info;
        document.getElementById('dvPagerText').textContent = info;
        document.getElementById('dvPrev').disabled = _dPage <= 1;
        document.getElementById('dvNext').disabled = _dPage >= _dPages;
        pager.style.display = d.total > 50 ? 'flex' : 'none';

    } catch(e){
        document.getElementById('dvBody').innerHTML = '<div style="padding:30px;text-align:center;color:var(--color-danger,#ef4444)">Error: ' + e.message + '</div>';
    }
}

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && document.getElementById('detalleView').style.display !== 'none') cerrarDetalle();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
