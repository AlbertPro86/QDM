'use strict';
/**
 * CRM QUANTUN Digital — Módulo de Facturación
 * Handles: Facturas tab (transactions list + KPIs) + Proveedores tab (CRUD)
 */

/* ═══════════════════════════════════════════════════════════════════════════
   ESTADO GLOBAL
═══════════════════════════════════════════════════════════════════════════ */
const facState = {
    page:         1,
    perPage:      20,
    total:        0,
    debTimer:     null,
    provDebTimer: null,
    provEditId:   null,
    provElimId:   null,
};

// Estado del sistema de preview de plantillas
let _facActual      = null;   // factura abierta actualmente
let _plantillasList = [];     // caché de plantillas disponibles
let _cfgActual      = null;   // cfg de la plantilla seleccionada

/* ═══════════════════════════════════════════════════════════════════════════
   TABS
═══════════════════════════════════════════════════════════════════════════ */
function switchFacTab(tab, btn) {
    document.querySelectorAll('.facTab').forEach(b => {
        b.style.borderBottom = '2.5px solid transparent';
        b.style.color = '#94a3b8';
    });
    btn.style.borderBottom = '2.5px solid #0f172a';
    btn.style.color = '#0f172a';

    document.getElementById('tab-facturas').style.display   = tab === 'facturas'    ? 'block' : 'none';
    document.getElementById('tab-proveedores').style.display = tab === 'proveedores' ? 'block' : 'none';

    if (tab === 'proveedores') cargarProveedores();
}

/* ═══════════════════════════════════════════════════════════════════════════
   FACTURAS / TRANSACCIONES
═══════════════════════════════════════════════════════════════════════════ */
function cargarFacturas() {
    const tipo   = document.getElementById('facFiltroTipo').value;
    const estado = document.getElementById('facFiltroEstado').value;
    const mes    = document.getElementById('facFiltroMes').value;
    const buscar = document.getElementById('facBuscar').value.trim();
    const offset = (facState.page - 1) * facState.perPage;

    const params = new URLSearchParams({ limite: facState.perPage, offset });
    if (tipo)   params.set('tipo',   tipo);
    if (estado) params.set('estado', estado);
    if (mes)    params.set('mes',    mes);
    if (buscar) params.set('buscar', buscar);

    document.getElementById('facTbody').innerHTML =
        `<tr><td colspan="8" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px">Cargando...</td></tr>`;

    fetch(`api/transacciones.php?${params}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Error al cargar transacciones');
            facState.total = res.total;
            renderKpisFac(res.resumen, res.total);
            renderFacturas(res.data, res.total);
        })
        .catch(err => {
            document.getElementById('facTbody').innerHTML =
                `<tr><td colspan="8" style="padding:48px;text-align:center;color:#ef4444;font-size:13px">${err.message}</td></tr>`;
        });
}

/* ── KPIs ─────────────────────────────────────────────────────────────── */
function renderKpisFac(resumen, total) {
    const fmt = v => (typeof moneyNum === 'function') ? moneyNum(parseFloat(v || 0)) : '$ ' + parseFloat(v || 0).toLocaleString('es-CO');

    document.getElementById('kpiIngresos').textContent = fmt(resumen.total_ingresos);
    document.getElementById('kpiEgresos').textContent  = fmt(resumen.total_egresos);

    const balance = parseFloat(resumen.total_ingresos || 0) - parseFloat(resumen.total_egresos || 0);
    document.getElementById('kpiBalance').textContent = fmt(Math.abs(balance));

    const pill = document.getElementById('kpiBalancePill');
    const card = document.getElementById('kpiBalanceCard');
    if (balance >= 0) {
        pill.textContent        = 'Positivo';
        pill.style.background   = 'rgba(0,0,0,0.12)';
        pill.style.color        = '#0E0E0C';
        if (card) { card.style.background = '#C6F24E'; card.style.borderColor = '#A8D87A'; }
    } else {
        pill.textContent        = 'Negativo';
        pill.style.background   = '#E8BCB8';
        pill.style.color        = '#6E211B';
        if (card) { card.style.background = '#F4DEDB'; card.style.borderColor = '#E8BCB8'; }
    }

    document.getElementById('kpiPendientes').textContent = `${resumen.total_pendientes || 0} pendientes · ${resumen.total_vencidos || 0} vencidos`;
    document.getElementById('kpiTotal').textContent      = total;
    const cIng = parseInt(resumen.count_ingresos || 0);
    const cEgr = parseInt(resumen.count_egresos  || 0);
    document.getElementById('kpiIngresosCount').textContent = `${cIng} factura${cIng !== 1 ? 's' : ''}`;
    document.getElementById('kpiEgresosCount').textContent  = `${cEgr} egreso${cEgr !== 1 ? 's' : ''}`;
    document.getElementById('kpiPagadas').textContent = `${total} registradas`;
}

/* ── TABLA ────────────────────────────────────────────────────────────── */
function renderFacturas(rows, total) {
    const tbody = document.getElementById('facTbody');

    if (!rows || !rows.length) {
        tbody.innerHTML = `
            <tr><td colspan="8" style="padding:64px;text-align:center;color:#94a3b8;font-size:13px">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"
                     style="opacity:.3;margin:0 auto 10px;display:block">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Sin transacciones para los filtros seleccionados
            </td></tr>`;
        document.getElementById('facPaginacion').innerHTML = '';
        return;
    }

    const fmt = v => (typeof formatMoney === 'function') ? formatMoney(parseFloat(v || 0)) : '$ ' + parseFloat(v || 0).toLocaleString('es-CO');

    const tipoBadge = t => t === 'ingreso'
        ? `<span style="background:#dcfce7;color:#16a34a;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.04em;white-space:nowrap">↑ Ingreso</span>`
        : `<span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.04em;white-space:nowrap">↓ Egreso</span>`;

    const estadoBadge = e => {
        const map = {
            pendiente: ['#fef3c7','#d97706','Pendiente'],
            pagado:    ['#dcfce7','#16a34a','Pagado'],
            vencido:   ['#fee2e2','#dc2626','Vencido'],
        };
        const [bg, color, label] = map[e] || ['#f1f5f9','#64748b', e];
        return `<span style="background:${bg};color:${color};padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700">${label}</span>`;
    };

    const frecBadge = f => {
        if (!f || f === 'unico') return '<span style="color:#cbd5e1;font-size:12px">—</span>';
        const map = { mensual:'Mensual', trimestral:'Trimestral', semestral:'Semestral', anual:'Anual' };
        return `<span style="background:#f0f9ff;color:#0369a1;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700">${map[f] || f}</span>`;
    };

    const archivoIcons = row => {
        let icons = '';
        const facUrl = row.factura_url || row.factura_path;
        if (facUrl) icons += `
            <a href="${facUrl}" target="_blank" title="Factura PDF"
               style="color:#6366f1;display:inline-flex" onclick="event.stopPropagation()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </a>`;
        if (row.imagen_path) icons += `
            <a href="${row.imagen_path}" target="_blank" title="Imagen"
               style="color:#0891b2;display:inline-flex" onclick="event.stopPropagation()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
            </a>`;
        if (row.documento_path) icons += `
            <a href="${row.documento_path}" target="_blank" title="Documento"
               style="color:#0369a1;display:inline-flex" onclick="event.stopPropagation()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </a>`;
        return icons || '<span style="color:#d1d5db;font-size:12px">—</span>';
    };

    const fmtDate = d => {
        if (!d) return '—';
        try {
            return new Date(d.split(' ')[0] + 'T00:00:00').toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
        } catch { return d; }
    };

    tbody.innerHTML = rows.map(row => `
        <tr style="border-bottom:1px solid #f1f5f9;transition:background .12s"
            onmouseenter="this.style.background='#fafafa'"
            onmouseleave="this.style.background='transparent'"
            data-id="${row.id}">

            <td style="padding-left:16px">
                <input type="checkbox" class="fac-row-check" value="${row.id}" onchange="facOnRowCheck()" style="width:15px;height:15px;cursor:pointer;accent-color:#dc2626">
            </td>

            <td style="padding:12px 16px">${tipoBadge(row.tipo)}</td>

            <td style="padding:12px;max-width:200px">
                <div style="font-weight:700;font-size:13px;color:#0f172a;line-height:1.3;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    ${row.titulo || row.concepto || '—'}
                </div>
                ${row.titulo
                    ? `<div style="font-size:11px;color:#94a3b8;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${row.concepto}</div>`
                    : ''}
            </td>

            <td style="padding:12px;font-size:12px;color:#475569;max-width:160px;
                       white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                ${row.tipo === 'ingreso' ? (row.lead_nombre || row.cliente_nombre || '—') : (row.proveedor || row.cliente_nombre || row.lead_nombre || '—')}
            </td>

            <td style="padding:12px;text-align:center">${frecBadge(row.frecuencia)}</td>

            <td style="padding:12px;text-align:right;font-weight:800;font-size:14px;
                       color:${row.tipo === 'ingreso' ? '#16a34a' : '#dc2626'};white-space:nowrap">
                ${row.tipo === 'ingreso' ? '+' : '-'}${fmt(row.monto)}
            </td>

            <td style="padding:12px;text-align:center">${estadoBadge(row.estado)}</td>

            <td style="padding:12px;text-align:center;font-size:12px;color:#64748b;white-space:nowrap">
                ${fmtDate(row.fecha_vencimiento)}
            </td>

            <td style="padding:12px;text-align:right">
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:2px">
                    ${archivoIcons(row)}
                    <button onclick="event.stopPropagation();verFactura(${row.id})" title="Ver detalle"
                        style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;color:#94a3b8;transition:all .12s"
                        onmouseenter="this.style.background='#f0f9ff';this.style.color='#0369a1'"
                        onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <button onclick="event.stopPropagation();editarFactura(${row.id})" title="Editar"
                        style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;color:#94a3b8;transition:all .12s"
                        onmouseenter="this.style.background='#f1f5f9';this.style.color='#0f172a'"
                        onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button onclick="event.stopPropagation();deleteFactura(${row.id})" title="Eliminar"
                        style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;color:#94a3b8;transition:all .12s"
                        onmouseenter="this.style.background='#fee2e2';this.style.color='#ef4444'"
                        onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    facClearSelection();
    renderFacPaginacion(total);
}

/* ── PAGINACIÓN ───────────────────────────────────────────────────────── */
function renderFacPaginacion(total) {
    const pag       = document.getElementById('facPaginacion');
    const totalPags = Math.ceil(total / facState.perPage);
    const page      = facState.page;

    if (totalPags <= 1) {
        pag.innerHTML = `<span style="color:#64748b;font-size:12px">${total} transacciones</span>`;
        return;
    }

    const start = (page - 1) * facState.perPage + 1;
    const end   = Math.min(page * facState.perPage, total);

    let btns = '';
    for (let i = 1; i <= totalPags; i++) {
        const show = i === 1 || i === totalPags || (i >= page - 1 && i <= page + 1);
        const ellipsis = i === page - 2 || i === page + 2;
        if (show) {
            btns += `<button onclick="facGoPage(${i})"
                style="padding:4px 10px;border:1.5px solid ${i === page ? '#0E0E0C' : '#E8E5DD'};
                       background:${i === page ? '#0E0E0C' : '#fff'};
                       color:${i === page ? '#C6F24E' : '#57544D'};
                       border-radius:3px;font-size:12px;font-weight:700;cursor:pointer">${i}</button>`;
        } else if (ellipsis) {
            btns += `<span style="color:#94a3b8;padding:0 2px">…</span>`;
        }
    }

    const navBtn = (label, target, disabled) =>
        `<button onclick="facGoPage(${target})" ${disabled ? 'disabled' : ''}
            style="padding:4px 10px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;
                   border-radius:6px;font-size:12px;cursor:pointer;${disabled ? 'opacity:.4;cursor:default' : ''}">${label}</button>`;

    pag.innerHTML = `
        <span style="font-size:12px;color:#64748b">${start}–${end} de ${total}</span>
        <div style="display:flex;align-items:center;gap:4px">
            ${navBtn('‹', page - 1, page <= 1)}
            ${btns}
            ${navBtn('›', page + 1, page >= totalPags)}
        </div>`;
}

function facGoPage(p) {
    const totalPags = Math.ceil(facState.total / facState.perPage);
    if (p < 1 || p > totalPags) return;
    facState.page = p;
    cargarFacturas();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── FILTROS ──────────────────────────────────────────────────────────── */
function debounceFac() {
    clearTimeout(facState.debTimer);
    facState.debTimer = setTimeout(() => {
        facState.page = 1;
        cargarFacturas();
    }, 280);
}

function limpiarFiltrosFac() {
    document.getElementById('facFiltroTipo').value   = '';
    document.getElementById('facFiltroEstado').value = '';
    document.getElementById('facFiltroMes').value    = '';
    document.getElementById('facBuscar').value       = '';
    facState.page = 1;
    cargarFacturas();
}

/* ── NUEVA TRANSACCIÓN ────────────────────────────────────────────────── */
function abrirModalTransaccion() {
    // Redirige a Finanzas donde vive el modal completo con catálogo e ítems
    window.location.href = 'finanzas.php';
}

/* ═══════════════════════════════════════════════════════════════════════════
   CATEGORÍAS DE PROVEEDORES — dinámicas
═══════════════════════════════════════════════════════════════════════════ */

// Caché local de categorías para no recargar en cada apertura
let _categoriasCache = null;

async function cargarCategoriasSelect(selectEl, valorActual = '') {
    try {
        // Usar caché si está disponible
        if (!_categoriasCache) {
            const res = await fetch('api/proveedores.php?categorias=1').then(r => r.json());
            _categoriasCache = res.success ? res.data : [];
        }

        selectEl.innerHTML = '<option value="">Sin categoría</option>'
            + _categoriasCache.map(c =>
                `<option value="${c}" ${c === valorActual ? 'selected' : ''}>${c}</option>`
            ).join('');
    } catch {
        // Si falla, dejar solo "Sin categoría"
    }
}

async function refrescarFiltroCategoria() {
    _categoriasCache = null; // Forzar recarga
    const filtro = document.getElementById('provFiltroCategoria');
    const valorActual = filtro.value;
    try {
        const res = await fetch('api/proveedores.php?categorias=1').then(r => r.json());
        _categoriasCache = res.success ? res.data : [];
        filtro.innerHTML = '<option value="">Todas las categorías</option>'
            + _categoriasCache.map(c =>
                `<option value="${c}" ${c === valorActual ? 'selected' : ''}>${c}</option>`
            ).join('');
    } catch { /* ignorar */ }
}

function toggleNuevaCatForm() {
    const form = document.getElementById('nuevaCatForm');
    const isOpen = form.style.display !== 'none';
    if (isOpen) {
        cerrarNuevaCatForm();
    } else {
        form.style.display = 'flex';
        document.getElementById('nuevaCatInput').value = '';
        document.getElementById('btnGuardarCat').disabled = true;
        document.getElementById('btnGuardarCat').style.opacity = '.5';
        setTimeout(() => document.getElementById('nuevaCatInput').focus(), 50);
    }
}

function cerrarNuevaCatForm() {
    document.getElementById('nuevaCatForm').style.display = 'none';
    document.getElementById('nuevaCatInput').value = '';
}

function agregarNuevaCategoria() {
    const input = document.getElementById('nuevaCatInput');
    const nombre = input.value.trim();
    if (!nombre) return;

    const select = document.getElementById('provCategoria');

    // Verificar si ya existe (case-insensitive)
    const existe = Array.from(select.options).some(o => o.value.toLowerCase() === nombre.toLowerCase());
    if (existe) {
        // Solo seleccionarla
        select.value = Array.from(select.options).find(o => o.value.toLowerCase() === nombre.toLowerCase()).value;
        cerrarNuevaCatForm();
        return;
    }

    // Agregar al select del modal
    const opt = new Option(nombre, nombre, true, true);
    select.appendChild(opt);
    select.value = nombre;

    // Actualizar caché local
    if (_categoriasCache && !_categoriasCache.includes(nombre)) {
        _categoriasCache = [..._categoriasCache, nombre].sort((a, b) => a.localeCompare(b));
    }

    // Actualizar el filtro de categorías en la barra
    refrescarFiltroCategoria();
    cerrarNuevaCatForm();

    if (typeof showToast === 'function') showToast(`Categoría "${nombre}" creada`, 'success');
}

/* ═══════════════════════════════════════════════════════════════════════════
   PROVEEDORES
═══════════════════════════════════════════════════════════════════════════ */
function cargarProveedores() {
    // Actualizar el filtro de categorías (sin bloquear)
    refrescarFiltroCategoria();

    const q   = document.getElementById('provBuscar').value.trim();
    const cat = document.getElementById('provFiltroCategoria').value;

    const params = new URLSearchParams({ activo: 1 });
    if (q)   params.set('q',   q);
    if (cat) params.set('cat', cat);

    document.getElementById('provGrid').innerHTML =
        `<div style="grid-column:1/-1;text-align:center;padding:48px;color:#94a3b8;font-size:13px">Cargando...</div>`;

    fetch(`api/proveedores.php?${params}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Error al cargar proveedores');
            renderProveedores(res.data);
        })
        .catch(err => {
            document.getElementById('provGrid').innerHTML =
                `<div style="grid-column:1/-1;text-align:center;padding:48px;color:#ef4444;font-size:13px">${err.message}</div>`;
        });
}

function renderProveedores(provs) {
    const grid = document.getElementById('provGrid');

    if (!provs || !provs.length) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:64px 20px;color:#94a3b8">
                <svg width="52" height="52" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"
                     style="opacity:.25;margin:0 auto 14px;display:block">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p style="font-size:14px;font-weight:700;margin:0 0 6px;color:#475569">Sin proveedores registrados</p>
                <p style="font-size:12px;margin:0">Agrega tu primer proveedor con el botón de arriba</p>
            </div>`;
        return;
    }

    const catStyle = {
        Software:  ['#ede9fe','#7c3aed'],
        Servicios: ['#dbeafe','#2563eb'],
        Arriendo:  ['#fef3c7','#d97706'],
        Nómina:    ['#dcfce7','#16a34a'],
        Marketing: ['#fce7f3','#db2777'],
        Logística: ['#e0f2fe','#0369a1'],
        Equipo:    ['#f1f5f9','#475569'],
        Impuestos: ['#fee2e2','#dc2626'],
        Otro:      ['#f8fafc','#64748b'],
    };

    grid.innerHTML = provs.map(p => {
        const [bg, color] = catStyle[p.categoria] || ['#f8fafc','#64748b'];
        const initials = (p.nombre || 'P').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
        const safe = str => (str || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');

        return `
        <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:20px;
                    position:relative;transition:border-color .15s,box-shadow .15s"
             onmouseenter="this.style.borderColor='#cbd5e1';this.style.boxShadow='0 4px 16px rgba(0,0,0,.07)'"
             onmouseleave="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">

            <!-- Acciones -->
            <div style="position:absolute;top:14px;right:14px;display:flex;gap:4px">
                <button onclick="editarProveedor(${p.id})" title="Editar"
                    style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;color:#94a3b8;transition:all .12s"
                    onmouseenter="this.style.background='#f1f5f9';this.style.color='#0f172a'"
                    onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </button>
                <button onclick="confirmarEliminarProveedor(${p.id},'${safe(p.nombre)}')" title="Eliminar"
                    style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;color:#94a3b8;transition:all .12s"
                    onmouseenter="this.style.background='#fee2e2';this.style.color='#ef4444'"
                    onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>

            <!-- Avatar + Nombre -->
            <div style="display:flex;align-items:flex-start;gap:14px;padding-right:64px">
                <div style="width:46px;height:46px;border-radius:12px;background:${bg};color:${color};
                            display:flex;align-items:center;justify-content:center;
                            font-size:15px;font-weight:800;flex-shrink:0;letter-spacing:-.5px">${initials}</div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:14px;font-weight:800;color:#0f172a;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${p.nombre}</div>
                    ${p.categoria
                        ? `<span style="background:${bg};color:${color};padding:2px 9px;border-radius:20px;
                                        font-size:10px;font-weight:700;margin-top:5px;display:inline-block">${p.categoria}</span>`
                        : ''}
                </div>
            </div>

            <!-- Detalles -->
            <div style="margin-top:14px;display:flex;flex-direction:column;gap:7px">
                ${p.nit ? `
                    <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:#475569">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                        </svg>
                        <span>NIT: ${p.nit}</span>
                    </div>` : ''}
                ${p.email ? `
                    <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:#475569">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <a href="mailto:${p.email}" style="color:inherit;text-decoration:none" onclick="event.stopPropagation()">${p.email}</a>
                    </div>` : ''}
                ${p.telefono ? `
                    <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:#475569">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.5 10.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012.43 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.57 6.57l1.27-.77a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                        <a href="tel:${p.telefono}" style="color:inherit;text-decoration:none">${p.telefono}</a>
                    </div>` : ''}
                ${p.direccion ? `
                    <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:#475569">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${p.direccion}</span>
                    </div>` : ''}
                ${p.notas ? `
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;line-height:1.5;
                                display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                        ${p.notas}
                    </div>` : ''}
            </div>
        </div>`;
    }).join('');
}

function debounceProvBuscar() {
    clearTimeout(facState.provDebTimer);
    facState.provDebTimer = setTimeout(cargarProveedores, 280);
}

/* ── MODAL PROVEEDOR ──────────────────────────────────────────────────── */
async function abrirModalProveedor(prov = null) {
    facState.provEditId = prov ? prov.id : null;

    document.getElementById('modalProvTitulo').textContent = prov ? 'Editar proveedor' : 'Nuevo proveedor';
    document.getElementById('provId').value        = prov ? (prov.id        || '') : '';
    document.getElementById('provNombre').value    = prov ? (prov.nombre    || '') : '';
    document.getElementById('provNit').value       = prov ? (prov.nit       || '') : '';
    document.getElementById('provEmail').value     = prov ? (prov.email     || '') : '';
    document.getElementById('provTelefono').value  = prov ? (prov.telefono  || '') : '';
    document.getElementById('provDireccion').value = prov ? (prov.direccion || '') : '';
    document.getElementById('provNotas').value     = prov ? (prov.notas     || '') : '';

    // Cargar categorías dinámicas desde API
    const selectCat = document.getElementById('provCategoria');
    await cargarCategoriasSelect(selectCat, prov ? (prov.categoria || '') : '');

    // Edge case: categoría del proveedor no estaba en el catálogo → agregarla
    if (prov && prov.categoria && selectCat.value !== prov.categoria) {
        const opt = new Option(prov.categoria, prov.categoria, true, true);
        selectCat.appendChild(opt);
        selectCat.value = prov.categoria;
    }

    // Reset del formulario de nueva categoría
    cerrarNuevaCatForm();

    document.getElementById('modalProveedor').style.display = 'flex';
    setTimeout(() => document.getElementById('provNombre').focus(), 80);
}

function cerrarModalProveedor() {
    document.getElementById('modalProveedor').style.display = 'none';
    facState.provEditId = null;
}

async function guardarProveedor() {
    const nombre = document.getElementById('provNombre').value.trim();
    if (!nombre) {
        if (typeof showToast === 'function') showToast('El nombre del proveedor es requerido', 'error');
        document.getElementById('provNombre').focus();
        document.getElementById('provNombre').style.borderColor = '#ef4444';
        setTimeout(() => document.getElementById('provNombre').style.borderColor = '#e2e8f0', 2000);
        return;
    }

    const btn = document.getElementById('provBtnGuardar');
    btn.disabled = true;
    btn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" class="spin">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
    </svg> Guardando...`;

    const payload = {
        nombre,
        nit:       document.getElementById('provNit').value.trim()       || null,
        categoria: document.getElementById('provCategoria').value         || null,
        email:     document.getElementById('provEmail').value.trim()      || null,
        telefono:  document.getElementById('provTelefono').value.trim()   || null,
        direccion: document.getElementById('provDireccion').value.trim()  || null,
        notas:     document.getElementById('provNotas').value.trim()      || null,
    };

    const isEdit = !!facState.provEditId;
    if (isEdit) payload.id = facState.provEditId;

    try {
        const res = await fetch('api/proveedores.php', {
            method:  isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        }).then(r => r.json());

        if (!res.success) throw new Error(res.error || 'Error al guardar');

        if (typeof showToast === 'function')
            showToast(isEdit ? 'Proveedor actualizado ✓' : 'Proveedor creado ✓', 'success');

        cerrarModalProveedor();
        cargarProveedores();

    } catch (err) {
        if (typeof showToast === 'function') showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg> Guardar`;
    }
}

async function editarProveedor(id) {
    try {
        const res = await fetch(`api/proveedores.php?id=${id}`).then(r => r.json());
        if (!res.success) throw new Error(res.error || 'No encontrado');
        abrirModalProveedor(res.data);
    } catch (err) {
        if (typeof showToast === 'function') showToast('Error al cargar proveedor: ' + err.message, 'error');
    }
}

function confirmarEliminarProveedor(id, nombre) {
    facState.provElimId = id;
    document.getElementById('modalElimProvNombre').textContent =
        `Esta acción desactivará a "${nombre}" del sistema. Puedes reactivarlo después.`;
    document.getElementById('modalEliminarProv').style.display = 'flex';

    // Reasignar handler para evitar closures acumulados
    const btn = document.getElementById('btnConfirmarElimProv');
    const handler = async () => {
        btn.removeEventListener('click', handler);
        btn.disabled = true;
        btn.textContent = 'Eliminando...';
        try {
            const res = await fetch(`api/proveedores.php?id=${id}`, { method: 'DELETE' }).then(r => r.json());
            if (!res.success) throw new Error(res.error);
            document.getElementById('modalEliminarProv').style.display = 'none';
            if (typeof showToast === 'function') showToast('Proveedor eliminado', 'success');
            cargarProveedores();
        } catch (err) {
            if (typeof showToast === 'function') showToast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
        }
    };
    btn.addEventListener('click', handler);
}

/* ═══════════════════════════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    // Carga inicial de facturas
    cargarFacturas();

    // ESC cierra modals
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        cerrarModalProveedor();
        cerrarModalFactura();
        document.getElementById('modalEliminarProv').style.display = 'none';
    });

    // Click outside para modal factura
    document.getElementById('modalFactura').addEventListener('click', e => {
        if (e.target === document.getElementById('modalFactura'))
            cerrarModalFactura();
    });

    // Click outside para modal eliminar proveedor
    document.getElementById('modalEliminarProv').addEventListener('click', e => {
        if (e.target === document.getElementById('modalEliminarProv'))
            document.getElementById('modalEliminarProv').style.display = 'none';
    });

    // Enter en form proveedor
    ['provNombre','provNit','provEmail','provTelefono','provDireccion'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('keydown', e => { if (e.key === 'Enter') guardarProveedor(); });
    });
});

/* ═══════════════════════════════════════════════════════════════════════════
   SELECCIÓN MASIVA DE FACTURAS
═══════════════════════════════════════════════════════════════════════════ */

function facGetChecked() {
    return [...document.querySelectorAll('.fac-row-check:checked')].map(c => parseInt(c.value));
}

function facOnRowCheck() {
    const all = document.querySelectorAll('.fac-row-check');
    const checked = document.querySelectorAll('.fac-row-check:checked');
    const checkAll = document.getElementById('facCheckAll');
    checkAll.indeterminate = checked.length > 0 && checked.length < all.length;
    checkAll.checked = checked.length === all.length && all.length > 0;
    facUpdateBulkBar();
}

function facToggleAll(master) {
    document.querySelectorAll('.fac-row-check').forEach(c => c.checked = master.checked);
    facUpdateBulkBar();
}

function facUpdateBulkBar() {
    const ids = facGetChecked();
    const bar = document.getElementById('facBulkBar');
    if (ids.length > 0) {
        bar.style.display = 'flex';
        document.getElementById('facBulkCount').textContent =
            ids.length === 1 ? '1 factura seleccionada' : `${ids.length} facturas seleccionadas`;
    } else {
        bar.style.display = 'none';
    }
}

function facClearSelection() {
    document.querySelectorAll('.fac-row-check').forEach(c => c.checked = false);
    const ca = document.getElementById('facCheckAll');
    if (ca) { ca.checked = false; ca.indeterminate = false; }
    facUpdateBulkBar();
}

async function deleteSelectedFac() {
    const ids = facGetChecked();
    if (!ids.length) return;

    const label = ids.length === 1 ? '1 factura' : `${ids.length} facturas`;
    const confirmed = await confirmAction('Esta acción no se puede deshacer.', { title: `¿Eliminar ${label}?` });
    if (!confirmed) return;

    let ok = 0, fail = 0;
    for (const id of ids) {
        try {
            const r = await fetch(`api/transacciones.php?id=${id}`, { method: 'DELETE' });
            const d = await r.json();
            if (d.success) ok++; else fail++;
        } catch { fail++; }
    }

    if (ok) showToast(`${ok} factura${ok > 1 ? 's' : ''} eliminada${ok > 1 ? 's' : ''}`, 'success');
    if (fail) showToast(`${fail} no pudo${fail > 1 ? 'ron' : ''} eliminarse`, 'error');
    cargarFacturas();
}

async function editSelectedFac() {
    const ids = facGetChecked();
    if (!ids.length) return;
    if (ids.length > 1) {
        if (typeof showToast === 'function') showToast('Selecciona solo una factura para editar', 'warning');
        return;
    }
    await editarFactura(ids[0]);
}

/* ── MODAL FACTURA: VER / EDITAR ──────────────────────────────────────── */
function switchFacMode(mode) {
    const isView = mode === 'view';
    document.getElementById('facViewPanel').style.display    = isView ? 'block' : 'none';
    document.getElementById('facEditPanel').style.display    = isView ? 'none'  : 'flex';
    document.getElementById('facViewFooter').style.display   = isView ? 'flex'  : 'none';
    document.getElementById('facEditFooter').style.display   = isView ? 'none'  : 'flex';
    document.getElementById('modalFacturaTitle').textContent = isView ? 'Ver Factura' : 'Editar Factura';
}

async function verFactura(id) {
    try {
        const r = await fetch(`api/transacciones.php?id=${id}`);
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No encontrado');

        const fac = d.data;
        _facActual = fac;

        document.getElementById('facId').value          = id;
        document.getElementById('facTipo').value        = fac.tipo || 'ingreso';
        document.getElementById('facEstado').value      = fac.estado || 'pendiente';
        document.getElementById('facTitulo').value      = fac.titulo || fac.concepto || '';
        document.getElementById('facMonto').value       = fac.monto || '';
        document.getElementById('facDescripcion').value = fac.descripcion || '';
        document.getElementById('facVencimiento').value = fac.fecha_vencimiento ? fac.fecha_vencimiento.split(' ')[0] : '';

        switchFacMode('view');
        document.getElementById('modalFactura').style.display = 'flex';

        // Carga plantillas (usa caché si ya fue cargado) y renderiza
        await _cargarPlantillas();
        renderFacTemplate();
    } catch (err) {
        if (typeof showToast === 'function') showToast('Error al cargar: ' + err.message, 'error');
    }
}

/* ── HELPERS DE PLANTILLA ─────────────────────────────────────────────── */
function _dbToCfg(p) {
    return {
        colorPrimario:   p.color_primario   || '#0f172a',
        colorSecundario: p.color_secundario || '#c9f31d',
        fuente:          p.fuente           || 'Poppins',
        empresaNombre:   p.empresa_nombre   || 'QUANTUN Digital',
        empresaNit:      p.empresa_nit      || '',
        empresaEmail:    p.empresa_email    || '',
        empresaTel:      p.empresa_tel      || '',
        empresaDir:      p.empresa_dir      || '',
        logoUrl:         p.logo_url         || '',
        notasPie:        p.notas_pie        || 'Gracias por su preferencia.',
        layout:          p.layout_tipo      || 'clasica',
    };
}

function _facToData(fac) {
    const fmtD = d => {
        if (!d) return '—';
        try { return new Date(d.split(' ')[0]+'T00:00:00').toLocaleDateString('es-CO',{day:'2-digit',month:'long',year:'numeric'}); }
        catch { return d; }
    };
    return {
        numero:     `FAC-${String(fac.id).padStart(4,'0')}`,
        fecha:      fmtD(fac.created_at),
        vence:      fmtD(fac.fecha_vencimiento),
        cliente:    fac.lead_nombre || fac.cliente_nombre || fac.proveedor || '—',
        clienteNit: '',
        clienteDir: '',
        items: fac.items && fac.items.length
            ? fac.items.map(i => ({ desc: i.nombre||'—', qty: parseInt(i.cantidad)||1, price: parseFloat(i.precio_unitario)||0 }))
            : [{ desc: fac.titulo||fac.concepto||'—', qty: 1, price: parseFloat(fac.monto)||0 }],
        descuento: parseFloat(fac.descuento) || 0,
    };
}

async function _cargarPlantillas() {
    if (_plantillasList.length) return; // usa caché
    try {
        const d = await fetch('api/plantillas_factura.php').then(r => r.json());
        if (!d.success || !d.data.length) return;
        _plantillasList = d.data;

        const sel = document.getElementById('facTemplateSel');
        if (sel) {
            sel.innerHTML = _plantillasList.map(p =>
                `<option value="${p.id}" ${p.es_default?'selected':''}>${p.nombre}</option>`
            ).join('');
        }
        const def = _plantillasList.find(p => p.es_default) || _plantillasList[0];
        if (def) _cfgActual = _dbToCfg(def);
    } catch { /* sin plantillas — fallback */ }
}

function renderFacTemplate() {
    const content = document.getElementById('facTemplateContent');
    if (!content || !_facActual) return;

    const data = _facToData(_facActual);

    if (_cfgActual && typeof generarFacturaConDatos === 'function') {
        content.innerHTML = generarFacturaConDatos(_cfgActual, data);
    } else {
        // Fallback simple si no hay plantillas
        const fmt = v => '$ ' + parseFloat(v||0).toLocaleString('es-CO');
        content.innerHTML = `
            <div style="padding:40px;font-family:sans-serif">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">Factura</div>
                <div style="font-size:22px;font-weight:900;color:#0f172a;margin-bottom:4px">${data.cliente}</div>
                <div style="font-size:13px;color:#94a3b8;margin-bottom:20px">${data.numero}</div>
                <div style="font-size:32px;font-weight:900;color:#0f172a">${fmt(_facActual.monto)}</div>
                <div style="font-size:11px;color:#94a3b8;margin-bottom:20px">COP · Vence: ${data.vence}</div>
                <p style="font-size:13px;color:#64748b">Crea una plantilla en <strong>Plantillas de Factura</strong> para ver el diseño completo.</p>
            </div>`;
    }
}

function cambiarPlantillaVista() {
    const sel = document.getElementById('facTemplateSel');
    const id  = parseInt(sel.value);
    const p   = _plantillasList.find(t => t.id === id);
    if (p) { _cfgActual = _dbToCfg(p); renderFacTemplate(); }
}

function imprimirFactura() {
    const html = document.getElementById('facTemplateContent')?.innerHTML;
    if (!html) return;
    const win = window.open('', '_blank', 'width=920,height=780');
    win.document.write(`<!DOCTYPE html><html><head>
        <meta charset="UTF-8"><title>Factura</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Montserrat:wght@400;600;700;800;900&family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
        <style>*{box-sizing:border-box}body{margin:0}@media print{body{margin:0}}</style>
    </head><body>${html}</body></html>`);
    win.document.close();
    win.onload = () => { win.focus(); win.print(); };
}

async function editarFactura(id) {
    await verFactura(id);
    switchFacMode('edit');
}

function populateFacView(fac) {
    const fmt = v => (typeof formatMoney === 'function') ? formatMoney(parseFloat(v || 0)) : '$ ' + parseFloat(v||0).toLocaleString('es-CO');

    // Badges tipo + estado
    const tipoBg    = fac.tipo === 'ingreso' ? '#dcfce7' : '#fee2e2';
    const tipoColor = fac.tipo === 'ingreso' ? '#16a34a' : '#dc2626';
    const tipoLabel = fac.tipo === 'ingreso' ? '↑ Ingreso' : '↓ Egreso';
    const estadoMap = { pendiente:['#fef3c7','#d97706','Pendiente'], pagado:['#dcfce7','#16a34a','Pagado'], vencido:['#fee2e2','#dc2626','Vencido'] };
    const [eBg, eColor, eLabel] = estadoMap[fac.estado] || ['#f1f5f9','#64748b', fac.estado];

    document.getElementById('facViewBadges').innerHTML = `
        <span style="background:${tipoBg};color:${tipoColor};padding:4px 12px;border-radius:20px;font-size:11px;font-weight:800;letter-spacing:.04em">${tipoLabel}</span>
        <span style="background:${eBg};color:${eColor};padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700">${eLabel}</span>`;

    document.getElementById('facViewTitulo').textContent = fac.titulo || fac.concepto || '—';
    const conceptoEl = document.getElementById('facViewConcepto');
    if (fac.titulo && fac.concepto && fac.titulo !== fac.concepto) {
        conceptoEl.textContent  = fac.concepto;
        conceptoEl.style.display = 'block';
    } else {
        conceptoEl.style.display = 'none';
    }

    const montoEl = document.getElementById('facViewMonto');
    montoEl.textContent = (fac.tipo === 'ingreso' ? '+' : '-') + fmt(fac.monto);
    montoEl.style.color = fac.tipo === 'ingreso' ? '#16a34a' : '#dc2626';

    const fmtDate = d => {
        if (!d) return '—';
        try { return new Date(d.split(' ')[0]+'T00:00:00').toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'}); }
        catch { return d; }
    };
    const destLabel = fac.tipo === 'ingreso' ? 'Cliente / Lead' : 'Proveedor';
    const destValue = fac.tipo === 'ingreso'
        ? (fac.lead_nombre || fac.cliente_nombre || '—')
        : (fac.proveedor   || fac.cliente_nombre || fac.lead_nombre || '—');
    const frecMap = { mensual:'Mensual', trimestral:'Trimestral', semestral:'Semestral', anual:'Anual', unico:'Único' };

    const infoFields = [
        [destLabel, destValue],
        ['Vencimiento', fmtDate(fac.fecha_vencimiento)],
        ['Frecuencia', frecMap[fac.frecuencia] || '—'],
        ...(parseFloat(fac.descuento) > 0 ? [['Descuento', fmt(fac.descuento)]] : []),
    ];
    document.getElementById('facViewInfo').innerHTML = infoFields.map(([l,v]) => `
        <div>
            <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">${l}</div>
            <div style="font-size:13px;font-weight:600;color:#0f172a">${v}</div>
        </div>`).join('');

    // Descripción
    const descC = document.getElementById('facViewDescContainer');
    if (fac.descripcion) {
        document.getElementById('facViewDesc').textContent = fac.descripcion;
        descC.style.display = 'block';
    } else {
        descC.style.display = 'none';
    }

    // Items
    const itemsC = document.getElementById('facViewItemsContainer');
    if (fac.items && fac.items.length) {
        const total = fac.items.reduce((s, i) => s + parseFloat(i.subtotal || 0), 0);
        document.getElementById('facViewItemsTable').innerHTML = `
            <thead>
                <tr style="background:#f8fafc;border-bottom:1.5px solid #e2e8f0">
                    <th style="padding:8px 12px;text-align:left;font-weight:700;color:#64748b;font-size:10px;text-transform:uppercase">Servicio</th>
                    <th style="padding:8px 12px;text-align:center;font-weight:700;color:#64748b;font-size:10px;text-transform:uppercase">Cant.</th>
                    <th style="padding:8px 12px;text-align:right;font-weight:700;color:#64748b;font-size:10px;text-transform:uppercase">Precio Unit.</th>
                    <th style="padding:8px 12px;text-align:right;font-weight:700;color:#64748b;font-size:10px;text-transform:uppercase">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                ${fac.items.map(item => `
                    <tr style="border-bottom:1px solid #f1f5f9">
                        <td style="padding:8px 12px;color:#0f172a;font-weight:600">${item.nombre || '—'}</td>
                        <td style="padding:8px 12px;text-align:center;color:#64748b">${item.cantidad}</td>
                        <td style="padding:8px 12px;text-align:right;color:#64748b">${fmt(item.precio_unitario)}</td>
                        <td style="padding:8px 12px;text-align:right;font-weight:700;color:#0f172a">${fmt(item.subtotal)}</td>
                    </tr>`).join('')}
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc">
                    <td colspan="3" style="padding:8px 12px;text-align:right;font-weight:800;font-size:12px;color:#64748b">Total</td>
                    <td style="padding:8px 12px;text-align:right;font-weight:900;font-size:14px;color:#0f172a">${fmt(total)}</td>
                </tr>
            </tfoot>`;
        itemsC.style.display = 'block';
    } else {
        itemsC.style.display = 'none';
    }

    // Archivos
    const filesC = document.getElementById('facViewFilesContainer');
    const files  = [];
    const facUrl = fac.factura_url || fac.factura_path;
    if (facUrl)             files.push(['Factura PDF', facUrl]);
    if (fac.imagen_path)    files.push(['Imagen', fac.imagen_path]);
    if (fac.documento_path) files.push(['Documento', fac.documento_path]);
    if (files.length) {
        document.getElementById('facViewFiles').innerHTML = files.map(([label, url]) => `
            <a href="${url}" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;color:#475569;text-decoration:none"
               onmouseenter="this.style.borderColor='#cbd5e1'" onmouseleave="this.style.borderColor='#e2e8f0'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                ${label}
            </a>`).join('');
        filesC.style.display = 'block';
    } else {
        filesC.style.display = 'none';
    }
}

async function deleteFactura(id) {
    if (!id) return;
    const confirmed = await confirmAction('Esta acción no se puede deshacer.', { title: '¿Eliminar esta factura?' });
    if (!confirmed) return;
    try {
        const r = await fetch(`api/transacciones.php?id=${id}`, { method: 'DELETE' });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'Error al eliminar');
        if (typeof showToast === 'function') showToast('Factura eliminada', 'success');
        cerrarModalFactura();
        cargarFacturas();
    } catch (err) {
        if (typeof showToast === 'function') showToast(err.message, 'error');
    }
}

function cerrarModalFactura() {
    document.getElementById('modalFactura').style.display = 'none';
    _facActual = null;
    facClearSelection();
}

async function guardarFactura() {
    const id = document.getElementById('facId').value;
    if (!id) return;

    const payload = {
        id:                parseInt(id),
        tipo:              document.getElementById('facTipo').value,
        estado:            document.getElementById('facEstado').value,
        titulo:            document.getElementById('facTitulo').value,
        monto:             parseFloat(document.getElementById('facMonto').value) || 0,
        descripcion:       document.getElementById('facDescripcion').value,
        fecha_vencimiento: document.getElementById('facVencimiento').value || null
    };

    const btn = document.getElementById('facBtnGuardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
        const r = await fetch('api/transacciones.php', {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'Error al guardar');

        if (typeof showToast === 'function') showToast('Factura actualizada ✓', 'success');
        if (d.data) { _facActual = d.data; renderFacTemplate(); }
        switchFacMode('view');
        cargarFacturas();
    } catch (err) {
        if (typeof showToast === 'function') showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar`;
    }
}
