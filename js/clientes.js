/**
 * CRM QUANTUN Digital - Clientes JS Logic
 */
let allClients = [];
let _sortState = { col: null, dir: 1 };

function sortClients(col) {
    if (_sortState.col === col) _sortState.dir *= -1;
    else { _sortState.col = col; _sortState.dir = 1; }
    ['nombre','contacto','renovacion','ingresos'].forEach(c => {
        const el = document.getElementById('sort_' + c);
        if (el) el.textContent = '';
    });
    const el = document.getElementById('sort_' + col);
    if (el) el.textContent = _sortState.dir === 1 ? ' ▲' : ' ▼';
    renderClients(allClients);
}

async function loadClients() {
    try {
        const r = await fetch('api/clientes.php');
        const d = await r.json();
        if (d.success) {
            allClients = d.data;
            renderClientKPIs(d.data);
            renderClients(allClients);
        }
    } catch(e) { showToast('Error al cargar clientes', 'error'); }
}

function fmtNum(n) {
    return '$ ' + parseFloat(n||0).toLocaleString('es-CO', {minimumFractionDigits:0, maximumFractionDigits:0});
}

function renderClientKPIs(clients) {
    const total     = clients.length;
    const activos   = clients.filter(c => c.estado === 'activo').length;
    const mora      = clients.filter(c => c.estado === 'en_mora').length;
    const pct       = total ? Math.round(activos / total * 100) : 0;
    const cobrado   = clients.reduce((s,c) => s + parseFloat(c.total_cobrado||0), 0);
    const porCobrar = clients.reduce((s,c) => s + parseFloat(c.total_por_cobrar||0), 0);
    const mrr       = clients.reduce((s,c) => s + parseFloat(c.mrr||0), 0);

    const kpi = (href, label, value, sub, cop, bg, borderCol, accent, pillBg, pillCol, pill) =>
        `<a href="${href}" style="background:${bg};border:1.5px solid ${borderCol};border-radius:3px;padding:16px 20px;display:block;text-decoration:none;transition:box-shadow .15s"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:${accent};text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">${label}</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1">${value}</div>
            ${cop ? '<div style="font-size:10px;color:#8A867C;margin-top:5px">COP</div>' : ''}
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D">${sub}</span>
                <span style="font-size:10px;font-weight:700;background:${pillBg};color:${pillCol};padding:2px 8px;border-radius:100px;white-space:nowrap">${pill}</span>
            </div>
        </a>`;

    const kpisEl = document.getElementById('clientKpis');
    if (!kpisEl) return;
    kpisEl.innerHTML =
        kpi('facturacion.php', 'Ingresos Cobrados', fmtNum(cobrado),   `${fmtNum(porCobrar)} por cobrar`,
            true, '#E3F1E8', '#B8DEC5', '#1B5A39', '#C8EAD3', '#1B5A39', 'Cobrado')
      + kpi('finanzas.php',    'MRR Servicios',     fmtNum(mrr),       `${activos} clientes activos`,
            true, '#C6F24E', '#A8D87A', 'rgba(0,0,0,.55)', 'rgba(0,0,0,0.12)', '#0E0E0C', 'Mensual')
      + kpi('clientes.php',    'En Mora',           mora,              'Requieren atención',
            false, '#F4DEDB', '#E8BCB8', '#6E211B', '#EFCFCC', '#6E211B', 'Mora')
      + kpi('clientes.php',    'Total Cartera',     total,             `${pct}% activos`,
            false, '#FAFAF7', '#E8E5DD', '#57544D', '#E8E5DD', '#0E0E0C', 'Clientes');
}

function renderClients(clients) {
    const tbody = document.getElementById('clientsTableBody');
    if(!clients.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:60px;color:var(--color-text-light)">No se encontraron clientes con los filtros aplicados.</td></tr>';
        clearSelection();
        return;
    }

    // Filtros locales
    const search = document.getElementById('clientSearch').value.toLowerCase();
    const svcType = document.getElementById('filterSvcType').value;
    const status = document.getElementById('filterStatus').value;

    const frecuencia = document.getElementById('filterFrecuencia').value;

    const filtered = clients.filter(c => {
        const matchSearch = !search || c.nombre_comercial.toLowerCase().includes(search)
            || (c.nit_cedula && c.nit_cedula.toLowerCase().includes(search))
            || (c.responsable && c.responsable.toLowerCase().includes(search))
            || (c.persona_contacto && c.persona_contacto.toLowerCase().includes(search));
        const matchSvc = svcType === 'todos' || (c.servicios_nombres && c.servicios_nombres.includes(svcType));
        const matchStatus = status === 'todos' || c.estado === status;
        const matchFreq = frecuencia === 'todos' || (c.frecuencias_activas || '').split(',').map(f => f.trim()).includes(frecuencia);
        // Excluir clientes cuya ÚNICA frecuencia activa es 'unico' → pertenecen al tab "Pagos únicos"
        const freqs = (c.frecuencias_activas || '').split(',').map(f => f.trim()).filter(Boolean);
        const soloUnico = freqs.length > 0 && freqs.every(f => f === 'unico');
        return matchSearch && matchSvc && matchStatus && matchFreq && !soloUnico;
    });

    if(!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:60px;color:var(--color-text-light)">No hay resultados para los filtros seleccionados.</td></tr>';
        clearSelection();
        return;
    }

    // Ordenamiento por columna; si no hay columna activa → orden por renovación del mes
    if (_sortState.col) {
        filtered.sort((a, b) => {
            let va, vb;
            if (_sortState.col === 'nombre') {
                va = (a.nombre_comercial || '').toLowerCase();
                vb = (b.nombre_comercial || '').toLowerCase();
                return _sortState.dir * va.localeCompare(vb, 'es');
            } else if (_sortState.col === 'contacto') {
                va = (a.persona_contacto || '').toLowerCase();
                vb = (b.persona_contacto || '').toLowerCase();
                return _sortState.dir * va.localeCompare(vb, 'es');
            } else if (_sortState.col === 'renovacion') {
                va = a.proxima_renovacion ? new Date(a.proxima_renovacion).getTime() : Infinity;
                vb = b.proxima_renovacion ? new Date(b.proxima_renovacion).getTime() : Infinity;
                return _sortState.dir * (va - vb);
            } else if (_sortState.col === 'ingresos') {
                va = parseFloat(a.mrr || 0);
                vb = parseFloat(b.mrr || 0);
                return _sortState.dir * (va - vb);
            }
            return 0;
        });
    } else {
        // Orden predeterminado: vencidos → mes actual → próximos 30 días → resto (alfabético)
        const hoyMs   = new Date().setHours(0, 0, 0, 0);
        const mesMes  = new Date().toISOString().substring(0, 7); // 'YYYY-MM'
        const ms30    = hoyMs + 30 * 86400000;

        const prioridad = c => {
            if (!c.proxima_renovacion) return 4; // sin fecha → al final
            const t  = new Date(c.proxima_renovacion + 'T12:00:00').getTime();
            const ym = c.proxima_renovacion.substring(0, 7);
            if (t < hoyMs)       return 0; // vencido
            if (ym === mesMes)   return 1; // este mes
            if (t <= ms30)       return 2; // próximos 30 días
            return 3;
        };

        filtered.sort((a, b) => {
            const pa = prioridad(a), pb = prioridad(b);
            if (pa !== pb) return pa - pb;
            // Dentro de mismo grupo → por fecha ASC (más próximos primero)
            const ta = a.proxima_renovacion ? new Date(a.proxima_renovacion + 'T12:00:00').getTime() : Infinity;
            const tb = b.proxima_renovacion ? new Date(b.proxima_renovacion + 'T12:00:00').getTime() : Infinity;
            if (ta !== tb) return ta - tb;
            // Mismo día → alfabético
            return (a.nombre_comercial || '').localeCompare(b.nombre_comercial || '', 'es');
        });
    }

    // Mapa: persona_contacto → cuántas marcas tiene en TODO allClients
    const marcasPorContacto = {};
    allClients.forEach(x => {
        if (x.persona_contacto) marcasPorContacto[x.persona_contacto] = (marcasPorContacto[x.persona_contacto] || 0) + 1;
    });

    tbody.innerHTML = filtered.map(c => {
        const renDate = c.proxima_renovacion ? new Date(c.proxima_renovacion + 'T12:00:00') : null;
        const renStr  = renDate ? renDate.toLocaleDateString('es-CO', {day:'2-digit', month:'short', year:'numeric'}) : '—';
        const hoyD    = new Date(); hoyD.setHours(0,0,0,0);
        const mesMes  = new Date().toISOString().substring(0, 7);

        let renClass = 'badge-success';
        let rowStyle = '';
        let renExtra = '';

        if (renDate) {
            const diff = (renDate - hoyD) / (1000 * 60 * 60 * 24);
            const ym   = c.proxima_renovacion.substring(0, 7);
            if (diff < 0) {
                renClass = 'badge-danger';
                rowStyle = 'background:#fef2f2;border-left:3px solid #ef4444;';
                renExtra = '<div style="font-size:10px;font-weight:700;color:#ef4444;margin-top:2px">⚠ Vencida</div>';
            } else if (ym === mesMes) {
                renClass = 'badge-warning';
                rowStyle = 'background:#fff7ed;border-left:3px solid #f97316;';
                renExtra = `<div style="font-size:10px;font-weight:700;color:#ea580c;margin-top:2px">Este mes${diff <= 7 ? ' · ' + Math.round(diff) + 'd' : ''}</div>`;
            } else if (diff <= 30) {
                renClass = 'badge-warning';
                rowStyle = 'background:#fffbeb;border-left:3px solid #f59e0b;';
                renExtra = `<div style="font-size:10px;color:#b45309;margin-top:2px">${Math.round(diff)}d restantes</div>`;
            }
        }

        const svcs = (c.servicios_nombres || '').split(', ').map(s => `<span class="badge" style="background:var(--color-surface);color:var(--color-text);font-size:10px;padding:2px 6px;margin:2px">${s}</span>`).join('');

        const mrr       = parseFloat(c.mrr            || 0);
        const cobrado   = parseFloat(c.total_cobrado   || 0);
        const porCobrar = parseFloat(c.total_por_cobrar || 0);
        const telClean  = (c.telefono || '').replace(/\D/g,'');

        // Frecuencias de servicios activos → badges (deduplicated, reflect actual service frequencies)
        const freqMap    = { mes:'Mensual', trimestre:'Trimestral', semestre:'Semestral', año:'Anual', unico:'Único', ninguna:'Ninguna' };
        const freqColors = { mes:'#dbeafe:#2563eb', trimestre:'#ede9fe:#7c3aed', semestre:'#fef3c7:#d97706', año:'#dcfce7:#16a34a', unico:'#F0EFEB:#57544D', ninguna:'#F0EFEB:#8A867C' };
        const freqList   = [...new Set((c.frecuencias_activas || '').split(', ').map(f => f.trim()).filter(Boolean))];
        const freqBadges = freqList.length
            ? freqList.map(f => {
                const [bg, color] = (freqColors[f] || '#F0EFEB:#57544D').split(':');
                return `<span style="background:${bg};color:${color};padding:2px 8px;border-radius:100px;font-size:10px;font-weight:700;white-space:nowrap">${freqMap[f] || f}</span>`;
              }).join(' ')
            : '<span style="color:var(--color-text-muted);font-size:12px">—</span>';

        // Contacto: persona_contacto con fallback a responsable
        const contacto = c.persona_contacto || c.responsable || '';

        // Chips de negocios
        const negNombres = (c.negocios_nombres || '').split('|||').filter(Boolean);
        const negIds     = (c.negocios_ids     || '').split('|||').filter(Boolean);
        const negChips   = negNombres.map((n, i) =>
            `<a href="cliente_detalle.php?id=${c.id}"
                style="display:inline-flex;align-items:center;gap:4px;background:#E8E5DD;color:#0E0E0C;padding:2px 8px;border-radius:100px;font-size:10px;font-weight:700;text-decoration:none;white-space:nowrap;transition:background .12s"
                onmouseenter="this.style.background='#D6D2C7'" onmouseleave="this.style.background='#E8E5DD'"
                title="Ir a ${escapeHtml(n)}">
              <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              ${escapeHtml(n)}
            </a>`
        ).join('');

        return `
        <tr class="animate-fade-in" data-id="${c.id}" style="${rowStyle}">
            <td style="padding-left:16px">
                <input type="checkbox" class="row-check" value="${c.id}" onchange="onRowCheck()"
                    style="width:15px;height:15px;cursor:pointer;accent-color:#dc2626">
            </td>
            <td>
                <div style="font-weight:700;color:var(--color-primary);font-size:14px">${escapeHtml(c.nombre_comercial)}</div>
                <div style="font-size:11px;color:var(--color-text-muted)">${escapeHtml(c.nit_cedula || 'SIN NIT')}</div>
                ${negChips ? `<div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:5px">${negChips}</div>` : ''}
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:6px">
                    ${c.persona_contacto && (marcasPorContacto[c.persona_contacto] || 0) > 1
                        ? `<svg width="12" height="12" fill="none" stroke="#8A867C" viewBox="0 0 24 24" stroke-width="2" title="${marcasPorContacto[c.persona_contacto]} marcas" style="flex-shrink:0"><rect x="2" y="3" width="7" height="7" rx="1"/><rect x="15" y="3" width="7" height="7" rx="1"/><rect x="2" y="14" width="7" height="7" rx="1"/><rect x="15" y="14" width="7" height="7" rx="1"/></svg>`
                        : ''}
                    <span style="font-weight:600;font-size:13px;color:#0E0E0C">${contacto ? escapeHtml(contacto) : '<span style="color:#8A867C">—</span>'}</span>
                </div>
                ${c.responsable && c.persona_contacto && c.responsable !== c.persona_contacto
                    ? `<div style="font-size:10px;color:#8A867C;margin-top:2px">${escapeHtml(c.responsable)}</div>` : ''}
            </td>
            <td><div style="display:flex;flex-wrap:wrap">${svcs || '<span style="color:var(--color-text-muted);font-size:12px">—</span>'}</div></td>
            <td><span class="badge ${renClass}">${renStr}</span>${renExtra}</td>
            <td style="text-align:right">
                <div style="font-weight:800;font-size:13px;color:#10b981">${mrr > 0 ? fmtNum(mrr) : '—'}</div>
                ${cobrado > 0 ? `<div style="font-size:10px;color:#57544D;font-weight:600">${fmtNum(cobrado)} cobrado</div>` : ''}
                ${porCobrar > 0 ? `<div style="font-size:10px;color:#f59e0b;font-weight:700">${fmtNum(porCobrar)} p/cobrar</div>` : ''}
            </td>
            <td>
                ${(() => {
                    if (!renDate) return '<span style="color:#8A867C;font-size:12px">—</span>';
                    const diff = (renDate - hoyD) / 86400000;
                    const ym   = c.proxima_renovacion.substring(0, 7);
                    if (diff < 0)          return '<span style="display:inline-flex;align-items:center;gap:4px;background:#fef2f2;color:#dc2626;padding:4px 10px;border-radius:100px;font-size:11px;font-weight:700;white-space:nowrap"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Vencida</span>';
                    if (ym === mesMes)     return '<span style="display:inline-flex;align-items:center;gap:4px;background:#fff7ed;color:#ea580c;padding:4px 10px;border-radius:100px;font-size:11px;font-weight:700;white-space:nowrap"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4l2.5 2.5"/></svg>Este mes</span>';
                    if (diff <= 30)        return '<span style="display:inline-flex;align-items:center;gap:4px;background:#fffbeb;color:#b45309;padding:4px 10px;border-radius:100px;font-size:11px;font-weight:700;white-space:nowrap"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4l2.5 2.5"/></svg>Próxima</span>';
                    return '<span style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;color:#16a34a;padding:4px 10px;border-radius:100px;font-size:11px;font-weight:700;white-space:nowrap"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Al día</span>';
                })()}
            </td>
            <td><div style="display:flex;flex-wrap:wrap;gap:4px">${freqBadges}</div></td>
            <td style="text-align:right;white-space:nowrap">
                <button class="btn btn-ghost btn-icon sm" onclick="window.location.href='cliente_detalle.php?id=${c.id}'" title="Gestionar">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                </button>
            </td>
        </tr>`;
    }).join('');

    // Restaurar estado de checkAll si aplica
    updateBulkBar();
}

/* ─── SELECCIÓN MASIVA ──────────────────────────────────────────────────────── */

function getChecked() {
    return [...document.querySelectorAll('.row-check:checked')].map(c => parseInt(c.value));
}

function onRowCheck() {
    const all = document.querySelectorAll('.row-check');
    const checked = document.querySelectorAll('.row-check:checked');
    const checkAll = document.getElementById('checkAll');
    checkAll.indeterminate = checked.length > 0 && checked.length < all.length;
    checkAll.checked = checked.length === all.length && all.length > 0;
    updateBulkBar();
}

function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = master.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const ids = getChecked();
    const bar = document.getElementById('bulkBar');
    if (ids.length > 0) {
        bar.style.display = 'flex';
        document.getElementById('bulkCount').textContent =
            ids.length === 1 ? '1 cliente seleccionado' : `${ids.length} clientes seleccionados`;
    } else {
        bar.style.display = 'none';
    }
}

function clearSelection() {
    document.querySelectorAll('.row-check').forEach(c => c.checked = false);
    const ca = document.getElementById('checkAll');
    if (ca) { ca.checked = false; ca.indeterminate = false; }
    updateBulkBar();
}

async function deleteSelected() {
    const ids = getChecked();
    if (!ids.length) return;
    const label = ids.length === 1 ? '1 cliente' : `${ids.length} clientes`;
    const confirmed = await confirmAction('Esta acción no se puede deshacer.', { title: `¿Eliminar ${label}?` });
    if (!confirmed) return;

    let ok = 0, fail = 0;
    for (const id of ids) {
        try {
            const r = await fetch(`api/clientes.php?id=${id}`, { method: 'DELETE' });
            const d = await r.json();
            if (d.success) ok++; else fail++;
        } catch { fail++; }
    }

    if (ok) showToast(`${ok} cliente${ok > 1 ? 's' : ''} eliminado${ok > 1 ? 's' : ''}`, 'success');
    if (fail) showToast(`${fail} no pudo${fail > 1 ? 'ron' : ''} eliminarse`, 'error');
    loadClients();
}

// Event Listeners para Filtros
document.getElementById('clientSearch').addEventListener('input', () => {
    const v = document.getElementById('clientSearch').value;
    document.getElementById('clientSearchClear').style.display = v ? 'block' : 'none';
    renderClients(allClients);
});
document.getElementById('filterSvcType').addEventListener('change', () => renderClients(allClients));
document.getElementById('filterFrecuencia').addEventListener('change', () => renderClients(allClients));
document.getElementById('filterStatus').addEventListener('change', () => renderClients(allClients));

/* ── Búsqueda de contacto en campo "Persona de Contacto" ────────────── */
let _contactoSeleccionado = null;

function buscarContactoExistente(q) {
    if (document.getElementById('clientId').value) return; // edición → ignorar
    const wrap = document.getElementById('clientBuscarResults');
    const q2   = q.trim().toLowerCase();

    if (q2.length < 2) { wrap.style.display = 'none'; return; }

    // Filtrar por persona_contacto (nombre del contacto, no del negocio)
    const matches = allClients.filter(c => {
        const contacto = (c.persona_contacto || '').toLowerCase();
        return contacto.includes(q2);
    }).slice(0, 6);

    if (!matches.length) {
        wrap.innerHTML = `<div style="padding:11px 14px;font-size:12px;color:#8A867C;text-align:center">Sin contactos con ese nombre — se creará nuevo</div>`;
        wrap.style.display = 'block';
        return;
    }

    wrap.innerHTML = matches.map(c => {
        const negs  = (c.negocios_nombres || '').split('|||').filter(Boolean);
        const letra = escapeHtml((c.persona_contacto || c.nombre_comercial || '?').charAt(0).toUpperCase());
        return `
        <div onclick="seleccionarContactoExistente(${c.id})"
             style="display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;border-bottom:1px solid #E8E5DD;transition:background .1s"
             onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background=''">
          <div style="width:34px;height:34px;border-radius:4px;background:#0E0E0C;color:#C6F24E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0">${letra}</div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:700;color:#0E0E0C">${escapeHtml(c.persona_contacto || c.nombre_comercial)}</div>
            <div style="font-size:11px;color:#57544D">${escapeHtml(c.nombre_comercial)}${c.ubicacion ? ' · ' + escapeHtml(c.ubicacion) : ''}</div>
            ${negs.length ? `<div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:3px">${negs.map(n=>`<span style="background:#E8E5DD;color:#0E0E0C;padding:1px 7px;border-radius:100px;font-size:10px;font-weight:700">${escapeHtml(n)}</span>`).join('')}</div>` : ''}
          </div>
          <span style="flex-shrink:0;background:#C6F24E;color:#0E0E0C;border-radius:4px;padding:4px 10px;font-size:11px;font-weight:700">Usar</span>
        </div>`;
    }).join('');
    wrap.style.display = 'block';
}

function seleccionarContactoExistente(clienteId) {
    const c = allClients.find(x => x.id === clienteId);
    if (!c) return;
    _contactoSeleccionado = c;

    // Cerrar dropdown
    document.getElementById('clientBuscarResults').style.display = 'none';

    // Mostrar banner verde
    const negs = (c.negocios_nombres || '').split('|||').filter(Boolean);
    document.getElementById('clientContactoAvatar').textContent     = (c.persona_contacto || c.nombre_comercial || '?').charAt(0).toUpperCase();
    document.getElementById('clientContactoCardNombre').textContent = c.persona_contacto || c.nombre_comercial;
    document.getElementById('clientContactoCardNegocios').textContent = negs.length
        ? 'Negocios: ' + negs.join(', ')
        : 'Sin negocios aún';
    document.getElementById('clientContactoSelCard').style.display = 'block';

    // Solo copiar el nombre del contacto — todo lo demás es nuevo
    document.getElementById('clientContacto').value = c.persona_contacto || '';

    // Vaciar todos los demás campos
    document.getElementById('clientNombre').value = '';
    document.getElementById('clientNit').value    = '';
    document.getElementById('clientTel').value    = '';
    document.getElementById('clientEmail').value  = '';
    document.getElementById('clientResp').value   = '';
    document.getElementById('clientUbi').value    = '';
    document.getElementById('clientDir').value    = '';

    setTimeout(() => document.getElementById('clientNombre').focus(), 50);
}

function limpiarContactoSeleccionado() {
    _contactoSeleccionado = null;
    document.getElementById('clientContactoSelCard').style.display = 'none';
    document.getElementById('clientContacto').value = '';
}

// Cerrar dropdown al clic fuera
document.addEventListener('click', e => {
    const wrap  = document.getElementById('clientBuscarResults');
    const input = document.getElementById('clientContacto');
    if (wrap && !wrap.contains(e.target) && e.target !== input) {
        wrap.style.display = 'none';
    }
});

/* ── Modal Cliente ──────────────────────────────────────────────────── */
function _resetClientModal() {
    _contactoSeleccionado = null;
    const res  = document.getElementById('clientBuscarResults');
    if (res)  res.style.display  = 'none';
    const card = document.getElementById('clientContactoSelCard');
    if (card) card.style.display = 'none';
    const btn  = document.getElementById('clientModalSaveBtn');
    if (btn)  { btn.textContent = 'Guardar'; btn.disabled = false; btn.onclick = () => document.getElementById('clientForm').requestSubmit(); }
}

function openClientModal(client = null) {
    document.getElementById('clientForm').reset();
    document.getElementById('clientId').value = '';
    document.getElementById('clientLeadId').value = '';
    _resetClientModal();
    if (client) {
        document.getElementById('clientModalTitle').textContent = 'Editar Cliente';
        document.getElementById('clientId').value               = client.id;
        document.getElementById('clientNombre').value           = client.nombre_comercial;
        document.getElementById('clientNit').value              = client.nit_cedula || '';
        document.getElementById('clientContacto').value         = client.persona_contacto || '';
        document.getElementById('clientTel').value              = client.telefono;
        document.getElementById('clientEmail').value            = client.email_facturacion || '';
        document.getElementById('clientDir').value              = client.direccion || '';
        document.getElementById('clientResp').value             = client.responsable || '';
        document.getElementById('clientUbi').value              = client.ubicacion || '';
        document.getElementById('clientModalTitle').textContent = 'Editar Cliente';
    } else {
        document.getElementById('clientModalTitle').textContent = 'Nuevo Cliente';
    }
    document.getElementById('clientModal').classList.add('show');
}

function closeClientModal() {
    document.getElementById('clientModal').classList.remove('show');
    _resetClientModal();
}

async function saveClient(e) {
    e.preventDefault();
    const id = document.getElementById('clientId').value;
    const leadId = document.getElementById('clientLeadId').value;
    const payload = {
        lead_id: leadId || null,
        nombre_comercial: document.getElementById('clientNombre').value,
        nit_cedula: document.getElementById('clientNit').value,
        persona_contacto: document.getElementById('clientContacto').value,
        telefono: document.getElementById('clientTel').value,
        email_facturacion: document.getElementById('clientEmail').value,
        direccion: document.getElementById('clientDir').value,
        responsable: document.getElementById('clientResp').value,
        ubicacion: document.getElementById('clientUbi').value
    };
    if (id) payload.id = parseInt(id);

    try {
        const r = await fetch('api/clientes.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.success) {
            showToast(d.message, 'success');
            closeClientModal();
            loadClients();
            // Si era conversión de lead, recargar la página para actualizar el contador de la pipeline
            if (leadId) setTimeout(() => location.reload(), 500);
        } else { showToast(d.error, 'error'); }
    } catch(e) { showToast('Error al guardar', 'error'); }
}

function convertLead(leadId, nombre, whatsapp, email, servicio) {
    document.getElementById('clientForm').reset();
    document.getElementById('clientId').value = '';
    document.getElementById('clientLeadId').value = leadId;
    document.getElementById('clientNombre').value = nombre;
    document.getElementById('clientTel').value = whatsapp || '';
    document.getElementById('clientEmail').value = email || '';
    document.getElementById('clientResp').value = 'Por asignar';
    document.getElementById('clientModalTitle').textContent = 'Convertir Lead a Cliente';
    document.getElementById('clientModal').classList.add('show');
}

async function loadRenewals() {
    try {
        const r = await fetch('api/cliente_servicios_all.php'); // Need a new API for all renewals
        const d = await r.json();
        if(d.success) renderRenewals(d.data);
    } catch(e) { console.error('Error cargando renovaciones'); }
}

function renderRenewals(svcs) {
    const tbody = document.getElementById('renewalsTableBody');
    if(!svcs.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:60px">No hay renovaciones pendientes.</td></tr>'; return; }
    
    tbody.innerHTML = svcs.map(s => {
        const vence = new Date(s.fecha_vencimiento);
        const hoy = new Date();
        const diff = Math.ceil((vence - hoy) / (1000 * 60 * 60 * 24));
        let badgeClass = 'badge-success';
        if(diff < 0) badgeClass = 'badge-danger';
        else if(diff < 30) badgeClass = 'badge-warning';

        const svcs = (s.servicio_nombre || '').split(', ').map(svc => `<span class="badge" style="background:var(--color-surface);color:var(--color-text);font-size:10px;padding:2px 6px;margin:2px">${svc}</span>`).join('');

        return `<tr>
            <td style="font-weight:700;color:var(--color-primary)">${escapeHtml(s.cliente_nombre)}</td>
            <td><div style="display:flex;flex-wrap:wrap">${svcs}</div></td>
            <td>${vence.toLocaleDateString()} <span style="font-size:11px;color:var(--color-text-muted)">(${diff} días)</span></td>
            <td style="font-weight:700">${formatMoney(s.monto_renovacion)}</td>
            <td><span class="badge ${badgeClass}">${s.estado.toUpperCase()}</span></td>
            <td>
                <button class="btn btn-ghost sm" onclick="window.location.href='cliente_detalle.php?id=${s.cliente_id}'" title="Ver Gestión">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                </button>
            </td>
        </tr>`;
    }).join('');
}

/* ── Pagos únicos — carga desde transacciones ──────────────────────────────── */
let unicosTransData = [];

async function loadUnicosTransacciones() {
    const tbody = document.getElementById('unicosTableBody');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:#8A867C;font-size:13px">Cargando pagos únicos…</td></tr>';
    try {
        const r = await fetch('api/transacciones.php?frecuencia=unico&limite=500');
        const d = await r.json();
        unicosTransData = d.success ? d.data : [];
        renderUnicoClients();
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:#ef4444;font-size:13px">Error al cargar pagos únicos.</td></tr>';
    }
}

function renderUnicoClients() {
    const tbody   = document.getElementById('unicosTableBody');
    const resumen = document.getElementById('unicosResumen');
    const buscar  = (document.getElementById('unicoBuscar')?.value || '').toLowerCase().trim();
    const estado  = document.getElementById('unicoEstado')?.value || 'todos';

    const data = unicosTransData.filter(t => {
        if (!t.cliente_id) return false; // Sin cliente → pertenece a proveedores
        const nombre   = (t.cliente_nombre || '').toLowerCase();
        const concepto = (t.concepto || t.titulo || '').toLowerCase();
        const matchB   = !buscar || nombre.includes(buscar) || concepto.includes(buscar);
        const matchE   = estado === 'todos' || t.estado === estado;
        return matchB && matchE;
    });

    // Resumen contador
    if (resumen) {
        const pend = unicosTransData.filter(t => t.cliente_id && t.estado === 'pendiente').length;
        const venc = unicosTransData.filter(t => t.cliente_id && t.estado === 'vencido').length;
        resumen.innerHTML = [
            pend ? `<span style="color:#d97706;font-weight:700">${pend} pendiente${pend!==1?'s':''}</span>` : '',
            venc ? `<span style="color:#dc2626;font-weight:700">${venc} vencido${venc!==1?'s':''}</span>` : '',
        ].filter(Boolean).join(' · ') || `<span style="color:#16a34a">${unicosTransData.filter(t=>t.cliente_id).length} registro${unicosTransData.filter(t=>t.cliente_id).length!==1?'s':''}</span>`;
    }

    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:60px;color:var(--color-text-light);font-size:13px">
            ${unicosTransData.length ? 'Sin resultados para los filtros aplicados.' : 'No hay pagos únicos registrados.'}
        </td></tr>`;
        return;
    }

    const ecMap = {
        pagado:   { bg:'#f0fdf4', color:'#16a34a', label:'✓ Pagado' },
        pendiente:{ bg:'#fffbeb', color:'#d97706', label:'◷ Pendiente' },
        vencido:  { bg:'#fef2f2', color:'#dc2626', label:'⚠ Vencido' },
    };

    tbody.innerHTML = data.map(t => {
        const nombre  = t.cliente_nombre || t.lead_nombre || '— Sin cliente —';
        const cid     = t.cliente_id;
        const ec      = ecMap[t.estado] || { bg:'#F0EFEB', color:'#57544D', label: t.estado };
        const monto   = '$ ' + parseFloat(t.monto || 0).toLocaleString('es-CO', {minimumFractionDigits:0, maximumFractionDigits:0});
        const telRaw  = t.cliente_telefono || '';
        const telClean= telRaw.replace(/\D/g,'');
        const fecha   = t.fecha_vencimiento
            ? new Date(t.fecha_vencimiento + 'T00:00:00').toLocaleDateString('es-CO', {day:'2-digit',month:'short',year:'numeric'})
            : '—';

        const waBtn = telClean
            ? `<a href="https://wa.me/${telClean.startsWith('57') ? telClean : '57'+telClean}" target="_blank"
                  style="color:#25D366;display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;text-decoration:none">
                  <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.438 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                  ${escapeHtml(telRaw)}
               </a>`
            : '<span style="color:#8A867C;font-size:12px">—</span>';

        return `<tr style="border-bottom:1px solid #E8E5DD;transition:background .12s"
                    onmouseenter="this.style.background='#fafafa'" onmouseleave="this.style.background=''">
            <td style="padding:12px 14px">
                <div style="font-weight:700;color:#0E0E0C;font-size:13px">${escapeHtml(nombre)}</div>
            </td>
            <td style="padding:12px 14px">
                <div style="font-size:13px;color:#57544D;font-weight:600">${escapeHtml(t.concepto || '—')}</div>
                ${t.titulo && t.titulo !== t.concepto ? `<div style="font-size:11px;color:#8A867C;margin-top:1px">${escapeHtml(t.titulo)}</div>` : ''}
            </td>
            <td style="padding:12px 14px;text-align:right;white-space:nowrap">
                <div style="font-weight:700;font-size:14px;color:#2D8F5A">${monto}</div>
                <div style="font-size:10px;color:#8A867C">COP</div>
            </td>
            <td style="padding:12px 14px">
                <span style="font-size:11px;font-weight:700;background:${ec.bg};color:${ec.color};padding:3px 10px;border-radius:100px;white-space:nowrap">${ec.label}</span>
            </td>
            <td style="padding:12px 14px;font-size:12px;color:#57544D;white-space:nowrap">${fecha}</td>
            <td style="padding:12px 14px">${waBtn}</td>
            <td style="padding:12px 8px;text-align:right;white-space:nowrap;display:flex;align-items:center;justify-content:flex-end;gap:6px">
                ${(t.estado === 'pendiente' || t.estado === 'vencido') ? `
                <button onclick="marcarPagoUnicoPagado(${t.id}, this)"
                    title="Marcar como pagado"
                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#E3F1E8;border:1.5px solid #2D8F5A;border-radius:3px;font-size:11px;font-weight:700;color:#2D8F5A;cursor:pointer;transition:all .12s"
                    onmouseenter="this.style.background='#2D8F5A';this.style.color='#fff'"
                    onmouseleave="this.style.background='#E3F1E8';this.style.color='#2D8F5A'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Pagado
                </button>` : ''}
                ${cid ? `<button onclick="window.location.href='cliente_detalle.php?id=${cid}'"
                    title="Ver cliente"
                    style="display:inline-flex;align-items:center;padding:5px 10px;background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:3px;font-size:11px;font-weight:700;color:#57544D;cursor:pointer;transition:all .12s"
                    onmouseenter="this.style.background='#0E0E0C';this.style.color='#C6F24E';this.style.borderColor='#0E0E0C'"
                    onmouseleave="this.style.background='#FAFAF7';this.style.color='#57544D';this.style.borderColor='#E8E5DD'">
                    Ver →
                </button>` : ''}
            </td>
        </tr>`;
    }).join('');
}

/* ── Marcar pago único como pagado ─────────────────────────────────────── */
async function marcarPagoUnicoPagado(id, btn) {
    const ok = await confirmAction('¿Confirmas que este pago ya fue recibido?', {
        title: 'Marcar como pagado',
        okText: 'Sí, está pagado',
        okColor: '#2D8F5A',
        okHover: '#246b46'
    });
    if (!ok) return;

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';

    try {
        const r = await fetch('api/transacciones.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, estado: 'pagado', fecha_pago: new Date().toISOString().split('T')[0] })
        });
        const d = await r.json();
        if (d.success === false && d.error) throw new Error(d.error);

        // Actualizar dato local y re-renderizar
        const tx = unicosTransData.find(t => t.id === id);
        if (tx) tx.estado = 'pagado';
        renderUnicoClients();
        showToast('Pago marcado como pagado ✓', 'success');
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        showToast('Error al actualizar: ' + e.message, 'error');
    }
}

// Tab Switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.remove('hidden');
        if(btn.dataset.tab === 'renewals') loadRenewals();
        if(btn.dataset.tab === 'activeClients') loadClients();
        if(btn.dataset.tab === 'uniqueClients') {
            loadUnicosTransacciones();
        }
        // SubServicios y Paquetes se cargan desde PHP, no necesitan función adicional
    });
});

function escapeHtml(str) { if(!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

/* ══════════════════════════════════════════════════════
   MODAL MENSAJES CLIENTE
══════════════════════════════════════════════════════ */
let _msgCanal       = 'wa';        // 'wa' | 'email'
let _msgCliente     = null;        // objeto cliente actual
let _msgPlantilla   = null;        // plantilla seleccionada
let _msgPlantillas  = [];          // cache plantillas

async function abrirModalMsgCliente(clienteId) {
    _msgCliente = allClients.find(c => c.id === clienteId);
    if (!_msgCliente) return;

    document.getElementById('msgClienteNombre').textContent = _msgCliente.nombre_comercial;
    msgSwitchTab('wa');
    msgMostrarStep(1);

    // Cargar plantillas (con caché)
    if (!_msgPlantillas.length) {
        const r = await fetch('api/mensajes_plantillas.php?filtro=todas');
        const d = await r.json();
        _msgPlantillas = d.success ? d.data : [];
    }
    _msgRenderPlantillas();

    document.getElementById('modalMsgCliente').classList.add('show');
}

function cerrarModalMsgCliente() {
    document.getElementById('modalMsgCliente').classList.remove('show');
    _msgCliente = null; _msgPlantilla = null;
}

function msgSwitchTab(canal) {
    _msgCanal = canal;
    const tabWA    = document.getElementById('msgTabWA');
    const tabEmail = document.getElementById('msgTabEmail');
    tabWA.style.borderBottomColor    = canal === 'wa'    ? '#25D366' : 'transparent';
    tabWA.style.color                = canal === 'wa'    ? '#0E0E0C' : '#8A867C';
    tabEmail.style.borderBottomColor = canal === 'email' ? '#4f46e5' : 'transparent';
    tabEmail.style.color             = canal === 'email' ? '#0E0E0C' : '#8A867C';
    msgMostrarStep(1);
}

function msgMostrarStep(n) {
    document.getElementById('msgStep1').style.display      = n === 1 ? '' : 'none';
    document.getElementById('msgStep2WA').style.display    = (n === 2 && _msgCanal === 'wa')    ? 'flex' : 'none';
    document.getElementById('msgStep2Email').style.display = (n === 2 && _msgCanal === 'email') ? 'flex' : 'none';
    document.getElementById('msgBtnWA').style.display      = (n === 2 && _msgCanal === 'wa')    ? 'inline-flex' : 'none';
    document.getElementById('msgBtnEmail').style.display   = (n === 2 && _msgCanal === 'email') ? 'inline-flex' : 'none';
}

function msgVolverStep1() {
    _msgPlantilla = null;
    msgMostrarStep(1);
}

function _msgRenderPlantillas() {
    const grid = document.getElementById('msgPlantillasGrid');
    if (!_msgPlantillas.length) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#8A867C;font-size:13px">Sin plantillas disponibles</div>';
        return;
    }
    grid.innerHTML = '';
    _msgPlantillas.forEach(p => {
        const card = document.createElement('div');
        card.style.cssText = 'border:1.5px solid #E8E5DD;border-radius:4px;padding:12px 14px;cursor:pointer;transition:all .15s;background:#FFFFFF';
        card.onmouseenter = () => { card.style.borderColor = '#4f46e5'; card.style.background = '#fafaff'; };
        card.onmouseleave = () => { card.style.borderColor = '#E8E5DD'; card.style.background = '#FFFFFF'; };
        const preview = p.contenido.length > 80 ? p.contenido.substring(0,80) + '…' : p.contenido;
        const badge   = p.es_predefinida
            ? '<span style="background:#0E0E0C;color:#C6F24E;padding:2px 7px;border-radius:100px;font-size:9px;font-weight:700">Predefinida</span>'
            : '<span style="background:#eef2ff;color:#4f46e5;padding:2px 7px;border-radius:100px;font-size:9px;font-weight:700">Personal</span>';
        const imgBadge = p.imagen
            ? '<span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;color:#10b981;font-weight:600;margin-top:4px"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg>Con imagen</span>' : '';
        card.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px">
                <div style="font-size:12px;font-weight:800;color:#0E0E0C;line-height:1.3">${escapeHtml(p.nombre)}</div>
                ${badge}
            </div>
            <div style="font-size:11px;color:#57544D;font-style:italic;line-height:1.5">"${escapeHtml(preview)}"</div>
            ${imgBadge}`;
        card.onclick = () => _msgSeleccionarPlantilla(p);
        grid.appendChild(card);
    });
}

function _msgReemplazarVars(texto) {
    if (!_msgCliente) return texto;
    return texto
        .replace(/\{\{cliente_nombre\}\}/g, _msgCliente.nombre_comercial || '')
        .replace(/\{\{numero_cotizacion\}\}/g, '')
        .replace(/\{\{total\}\}/g, '')
        .replace(/\{\{moneda\}\}/g, 'COP')
        .replace(/\{\{fecha\}\}/g, new Date().toLocaleDateString('es-CO'))
        .replace(/\{\{vigencia\}\}/g, '');
}

function _msgSeleccionarPlantilla(p) {
    _msgPlantilla = p;
    const textoReemplazado = _msgReemplazarVars(p.contenido);

    if (_msgCanal === 'wa') {
        const tel = (_msgCliente.telefono || '').replace(/\D/g,'');
        document.getElementById('msgWADest').textContent = _msgCliente.nombre_comercial + (tel ? ' · ' + tel : ' (sin teléfono)');
        document.getElementById('msgWAPreview').textContent = textoReemplazado;
        const imgBox = document.getElementById('msgWAImagenBox');
        if (p.imagen) {
            imgBox.style.display = 'flex';
            document.getElementById('msgWAImagenThumb').src = p.imagen;
        } else {
            imgBox.style.display = 'none';
        }
        msgMostrarStep(2);

    } else {
        const email = _msgCliente.email_facturacion || '';
        document.getElementById('msgEmailDest').textContent = _msgCliente.nombre_comercial + (email ? ' · ' + email : ' (sin correo)');
        document.getElementById('msgEmailAsunto').value = 'Mensaje de QUANTUN Digital para ' + _msgCliente.nombre_comercial;
        document.getElementById('msgEmailPreview').textContent = textoReemplazado;
        msgMostrarStep(2);
    }
}

function msgConfirmarWA() {
    if (!_msgPlantilla || !_msgCliente) return;
    const tel = (_msgCliente.telefono || '').replace(/\D/g,'');
    if (!tel) { showToast('Este cliente no tiene teléfono registrado', 'error'); return; }
    const texto = _msgReemplazarVars(_msgPlantilla.contenido);
    // Copiar imagen al portapapeles en background si hay
    if (_msgPlantilla.imagen) _msgCopiarImagen(_msgPlantilla.imagen);
    window.open('https://wa.me/57' + tel + '?text=' + encodeURIComponent(texto), '_blank');
    cerrarModalMsgCliente();
}

async function _msgCopiarImagen(imgPath) {
    try {
        const r = await fetch(imgPath);
        const blob = await r.blob();
        const png  = new Promise(res => { const c=document.createElement('canvas'); const img=new Image(); img.onload=()=>{c.width=img.width;c.height=img.height;c.getContext('2d').drawImage(img,0,0);c.toBlob(b=>res(b),'image/png');}; img.src=URL.createObjectURL(blob); });
        await navigator.clipboard.write([new ClipboardItem({'image/png': await png})]);
        showToast('Imagen copiada al portapapeles', 'success');
    } catch(e) {}
}

async function msgConfirmarEmail() {
    if (!_msgPlantilla || !_msgCliente) return;
    const email = _msgCliente.email_facturacion || '';
    if (!email) { showToast('Este cliente no tiene correo registrado', 'error'); return; }
    const asunto = document.getElementById('msgEmailAsunto').value.trim();
    if (!asunto) { showToast('Escribe el asunto del correo', 'warning'); return; }

    const btn = document.getElementById('msgBtnEmail');
    btn.disabled = true; btn.textContent = 'Enviando...';

    const texto = _msgReemplazarVars(_msgPlantilla.contenido);
    try {
        const r = await fetch('api/enviar_correo_cliente.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                cliente_id:    _msgCliente.id,
                asunto:        asunto,
                mensaje_texto: texto,
                imagen_path:   _msgPlantilla.imagen || ''
            })
        });
        const d = await r.json();
        if (d.success) {
            showToast('Correo enviado a ' + email, 'success');
            cerrarModalMsgCliente();
        } else {
            showToast(d.error || 'Error al enviar', 'error');
        }
    } catch(e) { showToast('Error de conexión', 'error'); }

    btn.disabled = false; btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Enviar correo';
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('modalMsgCliente')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalMsgCliente();
    });
});

loadClients();

/* ─── FILTRO POR CONTACTO ────────────────────────────────────────────────── */

function limpiarTodosLosFiltros() {
    document.getElementById('clientSearch').value = '';
    document.getElementById('clientSearchClear').style.display = 'none';
    document.getElementById('filterSvcType').value = 'todos';
    document.getElementById('filterFrecuencia').value = 'todos';
    document.getElementById('filterStatus').value = 'todos';
    renderClients(allClients);
}

function limpiarBusqueda() {
    document.getElementById('clientSearch').value = '';
    document.getElementById('clientSearchClear').style.display = 'none';
    renderClients(allClients);
}

function onClientSearch() {
    const val = document.getElementById('clientSearch').value;
    document.getElementById('clientSearchClear').style.display = val ? 'block' : 'none';
    renderClients(allClients);
}

/* ─── MODAL MARCAS POR CONTACTO ─────────────────────────────────────────── */

let _marcasActuales = [];

function abrirModalMarcas(personaContacto) {
    _marcasActuales = allClients.filter(c => c.persona_contacto === personaContacto);
    document.getElementById('modalMarcasTitle').textContent = personaContacto;
    document.getElementById('modalMarcasSubtitle').textContent =
        _marcasActuales.length + ' marca' + (_marcasActuales.length !== 1 ? 's' : '') + ' registrada' + (_marcasActuales.length !== 1 ? 's' : '');
    document.getElementById('marcasSearch').value = '';
    renderMarcasGrid(_marcasActuales);
    document.getElementById('modalMarcas').classList.add('show');
}

function cerrarModalMarcas() {
    document.getElementById('modalMarcas').classList.remove('show');
}

function filtrarMarcas() {
    const q = document.getElementById('marcasSearch').value.toLowerCase().trim();
    renderMarcasGrid(q ? _marcasActuales.filter(c =>
        c.nombre_comercial.toLowerCase().includes(q) || (c.ubicacion || '').toLowerCase().includes(q)
    ) : _marcasActuales);
}

function renderMarcasGrid(marcas) {
    const grid = document.getElementById('modalMarcasGrid');
    grid.innerHTML = marcas.map(c => {
        const inicial  = (c.nombre_comercial || '?').charAt(0).toUpperCase();
        const dotColor = c.estado === 'activo' ? '#22c55e' : c.estado === 'en_mora' ? '#ef4444' : '#8A867C';
        const svcs     = c.servicios_nombres ? c.servicios_nombres.split('|||').filter(Boolean) : [];
        return `
        <div onclick="window.location.href='cliente_detalle.php?id=${c.id}'"
            style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #E8E5DD;border-radius:3px;cursor:pointer;transition:all .15s;background:#FFFFFF"
            onmouseenter="this.style.borderColor='#0E0E0C';this.style.background='#FAFAF7'"
            onmouseleave="this.style.borderColor='#E8E5DD';this.style.background='#fff'">
            <div style="width:40px;height:40px;border-radius:3px;background:#0E0E0C;color:#C6F24E;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:17px;flex-shrink:0">
                ${inicial}
            </div>
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:7px;margin-bottom:3px">
                    <span style="font-size:13px;font-weight:800;color:#0E0E0C">${escapeHtml(c.nombre_comercial)}</span>
                    <span style="width:6px;height:6px;border-radius:50%;background:${dotColor};flex-shrink:0"></span>
                </div>
                <div style="font-size:11px;color:#57544D">
                    ${c.ubicacion ? escapeHtml(c.ubicacion) + (svcs.length ? ' · ' : '') : ''}${svcs.length ? svcs.slice(0,2).join(', ') + (svcs.length > 2 ? ' +' + (svcs.length-2) : '') : (!c.ubicacion ? 'Sin servicios activos' : '')}
                </div>
            </div>
            <svg width="15" height="15" fill="none" stroke="#8A867C" viewBox="0 0 24 24" stroke-width="2.5" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </div>`;
    }).join('');
}

document.getElementById('modalMarcas').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalMarcas();
});
