<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$currentPage  = 'proveedores';
$pageTitle    = 'Proveedores';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Proveedores</span>';
include __DIR__ . '/includes/header.php';
?>


<div class="page-header" style="margin-top:5px">
    <div class="page-header-left" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1">
        <div style="position:relative">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--color-text-light);pointer-events:none">
                <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" id="filtroNombre" placeholder="Buscar proveedor…"
                oninput="aplicarFiltroCategoria()"
                style="width:220px;padding:8px 12px 8px 33px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;background:#fff;color:var(--color-text);outline:none"
                onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
        </div>
        <select id="filtroCategoria" onchange="aplicarFiltroCategoria()"
            style="padding:8px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;font-family:inherit;background:#fff;color:var(--color-text);outline:none;cursor:pointer">
            <option value="">Todas las categorías</option>
        </select>
        <span id="filtroConteo" style="font-size:12px;color:var(--color-text-muted);white-space:nowrap"></span>
    </div>
    <div class="page-header-right">
        <button class="btn btn-accent" onclick="openProvModal()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Nuevo Proveedor
        </button>
    </div>
</div>

<div style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:4px;overflow:hidden;box-shadow:0 1px 2px rgba(14,14,12,.06);margin-top:10px">
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px" id="proveedoresTable">
            <thead>
                <tr style="background:var(--color-surface);border-bottom:1.5px solid var(--color-border)">
                    <th style="padding:11px 14px;text-align:left;font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Nombre</th>
                    <th style="padding:11px 14px;text-align:left;font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Categoría</th>
                    <th style="padding:11px 14px;text-align:left;font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Email</th>
                    <th style="padding:11px 14px;text-align:left;font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Teléfono</th>
                    <th style="padding:11px 14px;text-align:left;font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">NIT</th>
                    <th style="padding:11px 14px;text-align:left;font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Ciudad</th>
                    <th style="padding:11px 14px;text-align:center;font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Acciones</th>
                </tr>
            </thead>
            <tbody id="proveedoresTbody">
                <tr><td colspan="7" style="padding:48px;text-align:center;color:var(--color-text-light)">Cargando proveedores...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Modal Proveedor ─────────────────────────────────────── -->
<div class="modal-overlay" id="provModal">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <h3 class="modal-title" id="provModalTitle">Nuevo Proveedor</h3>
            <button class="modal-close" onclick="closeProvModal()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body modal-body-lg">
            <form id="provForm" onsubmit="saveProv(event)">
                <input type="hidden" id="provId">
                <div class="form-group">
                    <label class="form-label">Nombre de la empresa *</label>
                    <input type="text" class="form-input" id="provNombre" required placeholder="Ej: Hostinger, GoDaddy, AWS..." style="border:1.5px solid var(--color-border);padding:10px 12px;font-size:13px;border-radius:3px">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" class="form-input" id="provEmail" placeholder="contacto@proveedor.com" style="border:1.5px solid var(--color-border);padding:10px 12px;font-size:13px;border-radius:3px">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-input" id="provTelefono" placeholder="+57 300 123 4567" style="border:1.5px solid var(--color-border);padding:10px 12px;font-size:13px;border-radius:3px">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">NIT / RUT</label>
                        <input type="text" class="form-input" id="provNit" placeholder="Ej: 900.123.456-7" style="border:1.5px solid var(--color-border);padding:10px 12px;font-size:13px;border-radius:3px">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" id="provCategoria" onchange="onProvCatChange()" style="border:1.5px solid var(--color-border);padding:10px 12px;font-size:13px;border-radius:3px;background:var(--color-surface)">
                            <!-- Se llena dinámicamente por JS -->
                        </select>
                        <div id="provNuevaCatWrap" style="display:none;margin-top:8px">
                            <div style="display:flex;gap:8px">
                                <input type="text" id="provNuevaCatInput" placeholder="Nueva categoría"
                                    style="flex:1;padding:9px 11px;border:1.5px solid var(--color-border);border-radius:3px;font-size:13px;font-family:inherit;outline:none;transition:all .15s"
                                    onfocus="this.style.borderColor='var(--color-secondary)'" onblur="this.style.borderColor='var(--color-border)'"
                                    onkeydown="if(event.key==='Enter'){event.preventDefault();confirmarNuevaCat()}">
                                <button type="button" onclick="confirmarNuevaCat()"
                                    style="padding:9px 14px;background:var(--q-lima);color:var(--color-text);border:none;border-radius:3px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;transition:opacity .12s"
                                    onmouseenter="this.style.opacity='.9'" onmouseleave="this.style.opacity='1'">
                                    + Agregar
                                </button>
                                <button type="button" onclick="cancelarNuevaCat()"
                                    style="padding:9px 11px;background:none;border:1.5px solid var(--color-border);border-radius:3px;font-size:13px;color:var(--color-text-muted);cursor:pointer;transition:all .12s"
                                    onmouseenter="this.style.borderColor='var(--color-text-muted)'" onmouseleave="this.style.borderColor='var(--color-border)'">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Ciudad</label>
                        <input type="text" class="form-input" id="provCiudad" placeholder="Ej: Bogotá, Medellín..." style="border:1.5px solid var(--color-border);padding:10px 12px;font-size:13px;border-radius:3px">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Dirección</label>
                        <input type="text" class="form-input" id="provDireccion" placeholder="Ej: Cra 7 # 32-45" style="border:1.5px solid var(--color-border);padding:10px 12px;font-size:13px;border-radius:3px">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea class="form-textarea" id="provNotas" rows="3" placeholder="Condiciones de pago, contacto clave, etc..." style="border:1.5px solid var(--color-border);padding:10px 12px;font-size:13px;border-radius:3px;font-family:inherit"></textarea>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:28px">
                    <button type="button" class="btn btn-outline" onclick="closeProvModal()" style="padding:10px 16px;font-size:13px">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="padding:10px 18px;font-size:13px;font-weight:700">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal Detalle Proveedor ──────────────────────────────── -->
<div class="modal-overlay" id="provDetalleModal">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3 class="modal-title" id="provDetalleTitulo">Detalle del Proveedor</h3>
            <button class="modal-close" onclick="closeProvDetalle()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body" id="provDetalleBody" style="padding:20px 24px"></div>
        <div style="padding:16px 24px;border-top:1px solid var(--color-border);display:flex;gap:8px;justify-content:flex-end">
            <button class="btn btn-outline" onclick="closeProvDetalle()">Cerrar</button>
            <button class="btn btn-secondary" id="provDetalleEditBtn">Editar</button>
        </div>
    </div>
</div>

<script>
const PROV_API = 'api/proveedores.php';

/* ── Categorías dinámicas ──────────────────────────────────────────────────── */

const CATS_DEFAULT = ['Software','Servicios','Arriendo','Nómina','Marketing','Logística','Equipo','Impuestos','Otro'];
const CATS_KEY     = 'prov_categorias_v1';

function getCats() {
    try {
        const stored = JSON.parse(localStorage.getItem(CATS_KEY));
        if (Array.isArray(stored) && stored.length) return stored;
    } catch(e) {}
    return [...CATS_DEFAULT];
}

function saveCats(cats) {
    localStorage.setItem(CATS_KEY, JSON.stringify(cats));
}

function buildCatOptions(selectedVal = '') {
    const cats = getCats();
    return `<option value="">Sin categoría</option>`
        + cats.map(c => `<option value="${c}"${c === selectedVal ? ' selected' : ''}>${c}</option>`).join('')
        + `<option value="__nueva__">＋ Nueva categoría...</option>`;
}

function refreshCatSelects(selectedVal = '') {
    const sel = document.getElementById('provCategoria');
    if (sel) sel.innerHTML = buildCatOptions(selectedVal);
    // Filtro
    const fil = document.getElementById('filtroCategoria');
    if (fil) {
        const cur = fil.value;
        fil.innerHTML = `<option value="">Todas las categorías</option>`
            + getCats().map(c => `<option value="${c}"${c === cur ? ' selected' : ''}>${c}</option>`).join('');
    }
}

function onProvCatChange() {
    const sel = document.getElementById('provCategoria');
    const wrap = document.getElementById('provNuevaCatWrap');
    if (sel.value === '__nueva__') {
        wrap.style.display = '';
        document.getElementById('provNuevaCatInput').value = '';
        setTimeout(() => document.getElementById('provNuevaCatInput').focus(), 50);
        sel.value = ''; // volver a Sin categoría hasta que confirme
    } else {
        wrap.style.display = 'none';
    }
}

function confirmarNuevaCat() {
    const inp = document.getElementById('provNuevaCatInput');
    const nombre = inp.value.trim();
    if (!nombre) { inp.focus(); return; }

    const cats = getCats();
    if (!cats.includes(nombre)) {
        cats.push(nombre);
        saveCats(cats);
    }
    refreshCatSelects(nombre);
    document.getElementById('provNuevaCatWrap').style.display = 'none';
    showToast(`Categoría "${nombre}" agregada`, 'success');
}

function cancelarNuevaCat() {
    document.getElementById('provNuevaCatWrap').style.display = 'none';
    document.getElementById('provNuevaCatInput').value = '';
}

/* ── Filtro por categoría ─────────────────────────────────────────────────── */

let _allProveedores = [];

function aplicarFiltroCategoria() {
    const cat = document.getElementById('filtroCategoria').value;
    const q   = (document.getElementById('filtroNombre')?.value || '').toLowerCase().trim();

    const filtrados = _allProveedores.filter(p => {
        const matchCat    = !cat || p.categoria === cat;
        const matchNombre = !q
            || (p.nombre   || '').toLowerCase().includes(q)
            || (p.email    || '').toLowerCase().includes(q)
            || (p.nit      || '').toLowerCase().includes(q)
            || (p.ciudad   || '').toLowerCase().includes(q);
        return matchCat && matchNombre;
    });

    const conteo = document.getElementById('filtroConteo');
    if (conteo) conteo.textContent = filtrados.length + ' de ' + _allProveedores.length + ' proveedores';

    renderProveedores(filtrados);
}

/* ── Modal Crear / Editar ─────────────────────────────────────────────────── */

function openProvModal(id = null) {
    document.getElementById('provForm').reset();
    document.getElementById('provId').value = '';
    document.getElementById('provNuevaCatWrap').style.display = 'none';
    refreshCatSelects('');

    if (id) {
        document.getElementById('provModalTitle').textContent = 'Editar Proveedor';
        loadProvForEdit(id);
    } else {
        document.getElementById('provModalTitle').textContent = 'Nuevo Proveedor';
    }
    document.getElementById('provModal').classList.add('show');
}

function closeProvModal() {
    document.getElementById('provModal').classList.remove('show');
}

async function loadProvForEdit(id) {
    try {
        const r = await fetch(`${PROV_API}?id=${id}`);
        const d = await r.json();
        if (!d.success) { showToast('Error al cargar proveedor', 'error'); return; }
        const p = d.data;
        // Si la categoría no está en la lista, añadirla automáticamente
        if (p.categoria) {
            const cats = getCats();
            if (!cats.includes(p.categoria)) { cats.push(p.categoria); saveCats(cats); }
        }
        refreshCatSelects(p.categoria || '');
        document.getElementById('provId').value        = p.id;
        document.getElementById('provNombre').value    = p.nombre;
        document.getElementById('provEmail').value     = p.email     || '';
        document.getElementById('provTelefono').value  = p.telefono  || '';
        document.getElementById('provNit').value       = p.nit       || '';
        document.getElementById('provCiudad').value    = p.ciudad    || '';
        document.getElementById('provDireccion').value = p.direccion || '';
        document.getElementById('provNotas').value     = p.notas     || '';
    } catch(e) {
        showToast('Error al cargar proveedor', 'error');
    }
}

/* ── Modal Detalle ────────────────────────────────────────────────────────── */

async function verProv(id) {
    try {
        const r = await fetch(`${PROV_API}?id=${id}`);
        const d = await r.json();
        if (!d.success) { showToast('Error al cargar proveedor', 'error'); return; }
        const p = d.data;
        document.getElementById('provDetalleTitulo').textContent = p.nombre;
        document.getElementById('provDetalleEditBtn').onclick = () => { closeProvDetalle(); openProvModal(p.id); };

        const row = (label, val) => val
            ? `<div style="display:flex;gap:12px;padding:9px 0;border-bottom:1px solid var(--color-border)">
                   <span style="font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;min-width:90px;padding-top:1px">${label}</span>
                   <span style="font-size:13px;color:var(--color-text);font-weight:600;flex:1">${sanitizeHtml(val)}</span>
               </div>`
            : '';

        document.getElementById('provDetalleBody').innerHTML = `
            <div style="padding-bottom:8px">
                ${p.categoria ? `<span style="background:#EFECE5;color:#0E0E0C;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700">${sanitizeHtml(p.categoria)}</span>` : ''}
            </div>
            ${row('NIT / RUT',  p.nit)}
            ${row('Email',      p.email)}
            ${row('Teléfono',   p.telefono)}
            ${row('Ciudad',     p.ciudad)}
            ${row('Dirección',  p.direccion)}
            ${p.notas ? `<div style="padding:10px 0">
                <div style="font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Notas</div>
                <p style="font-size:13px;color:var(--color-text);line-height:1.5;margin:0">${sanitizeHtml(p.notas)}</p>
            </div>` : ''}
        `;
        document.getElementById('provDetalleModal').classList.add('show');
    } catch(e) {
        showToast('Error al cargar detalle', 'error');
    }
}

function closeProvDetalle() {
    document.getElementById('provDetalleModal').classList.remove('show');
}

/* ── Guardar ──────────────────────────────────────────────────────────────── */

async function saveProv(e) {
    e.preventDefault();
    const id  = document.getElementById('provId').value;
    const cat = document.getElementById('provCategoria').value;
    const data = {
        nombre:    document.getElementById('provNombre').value,
        email:     document.getElementById('provEmail').value,
        telefono:  document.getElementById('provTelefono').value,
        nit:       document.getElementById('provNit').value,
        categoria: cat,
        ciudad:    document.getElementById('provCiudad').value,
        direccion: document.getElementById('provDireccion').value,
        notas:     document.getElementById('provNotas').value
    };
    if (id) data.id = id;

    try {
        const r = await fetch(PROV_API, {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const d = await r.json();
        if (d.success) {
            showToast(id ? 'Proveedor actualizado' : 'Proveedor creado', 'success');
            closeProvModal();
            loadProveedores();
        } else {
            showToast(d.error || 'Error al guardar', 'error');
        }
    } catch(e) {
        showToast('Error al guardar proveedor', 'error');
    }
}

/* ── Eliminar ─────────────────────────────────────────────────────────────── */

async function deleteProv(id) {
    if (!await confirmAction('Se desactivará el proveedor del sistema.', {title: '¿Eliminar Proveedor?'})) return;
    try {
        const r = await fetch(`${PROV_API}?id=${id}`, { method: 'DELETE' });
        const d = await r.json();
        if (d.success) { showToast('Proveedor eliminado', 'success'); loadProveedores(); }
        else showToast('Error al eliminar', 'error');
    } catch(e) {
        showToast('Error al eliminar proveedor', 'error');
    }
}

/* ── Render tabla ─────────────────────────────────────────────────────────── */

function renderProveedores(data) {
    if (!data || data.length === 0) {
        document.getElementById('proveedoresTbody').innerHTML =
            '<tr><td colspan="7" style="padding:48px;text-align:center;color:var(--color-text-light)">No hay proveedores con esos filtros.</td></tr>';
        return;
    }
    document.getElementById('proveedoresTbody').innerHTML = data.map(p => `
        <tr style="border-bottom:1px solid var(--color-border);transition:all .15s;background:transparent"
            onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">
            <td style="padding:11px 14px;font-weight:800;color:var(--color-primary);font-size:14px">${sanitizeHtml(p.nombre)}</td>
            <td style="padding:11px 14px">
                ${p.categoria
                    ? `<span style="background:#EFECE5;color:#0E0E0C;padding:5px 11px;border-radius:3px;font-size:12px;font-weight:700">${sanitizeHtml(p.categoria)}</span>`
                    : '<span style="color:var(--color-text-muted);font-size:13px">—</span>'}
            </td>
            <td style="padding:11px 14px;font-size:13px;font-weight:500">
                ${p.email ? `<a href="mailto:${sanitizeHtml(p.email)}" style="color:#3F5E9E;text-decoration:none;font-weight:600;transition:all .15s" onmouseenter="this.style.color='#2d4d80;text-decoration:underline'" onmouseleave="this.style.color='#3F5E9E;text-decoration:none'">${sanitizeHtml(p.email)}</a>` : '<span style="color:var(--color-text-muted)">—</span>'}
            </td>
            <td style="padding:11px 14px;font-size:13px;font-weight:500">
                ${p.telefono ? `<a href="tel:${sanitizeHtml(p.telefono)}" style="color:#3F5E9E;text-decoration:none;font-weight:600;transition:all .15s" onmouseenter="this.style.color='#2d4d80;text-decoration:underline'" onmouseleave="this.style.color='#3F5E9E;text-decoration:none'">${sanitizeHtml(p.telefono)}</a>` : '<span style="color:var(--color-text-muted)">—</span>'}
            </td>
            <td style="padding:11px 14px;color:var(--color-text);font-size:13px;font-weight:600;font-family:monospace">
                ${p.nit ? sanitizeHtml(p.nit) : '<span style="color:var(--color-text-muted)">—</span>'}
            </td>
            <td style="padding:11px 14px;color:var(--color-text);font-size:13px;font-weight:600">
                ${p.ciudad ? sanitizeHtml(p.ciudad) : '<span style="color:var(--color-text-muted)">—</span>'}
            </td>
            <td style="padding:10px 16px;text-align:center">
                <div style="display:flex;gap:6px;justify-content:center">
                    <button onclick="verProv(${p.id})" title="Ver detalle"
                        style="background:none;border:none;padding:7px 8px;border-radius:3px;color:#475569;cursor:pointer;transition:all .12s;display:inline-flex;align-items:center;justify-content:center"
                        onmouseenter="this.style.background='#E1E7F2';this.style.color='#3F5E9E'"
                        onmouseleave="this.style.background='transparent';this.style.color='#57544D'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button onclick="openProvModal(${p.id})" title="Editar"
                        style="background:none;border:none;padding:7px 8px;border-radius:3px;color:#ca8a04;cursor:pointer;transition:all .12s;display:inline-flex;align-items:center;justify-content:center"
                        onmouseenter="this.style.background='#fef3c7'" onmouseleave="this.style.background='transparent'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="deleteProv(${p.id})" title="Eliminar"
                        style="background:none;border:none;padding:7px 8px;border-radius:3px;color:#ef4444;cursor:pointer;transition:all .12s;display:inline-flex;align-items:center;justify-content:center"
                        onmouseenter="this.style.background='#fee2e2'" onmouseleave="this.style.background='transparent'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>`).join('');
}

async function loadProveedores() {
    try {
        const r = await fetch(PROV_API);
        const d = await r.json();
        if (!d.success) {
            document.getElementById('proveedoresTbody').innerHTML =
                '<tr><td colspan="7" style="padding:48px;text-align:center;color:#ef4444">Error al cargar proveedores</td></tr>';
            return;
        }
        _allProveedores = d.data || [];

        // Añadir al listado de cats cualquier categoría nueva que venga de la BD
        const cats = getCats();
        let changed = false;
        _allProveedores.forEach(p => {
            if (p.categoria && !cats.includes(p.categoria)) { cats.push(p.categoria); changed = true; }
        });
        if (changed) saveCats(cats);

        refreshCatSelects('');
        aplicarFiltroCategoria();
    } catch(e) {
        document.getElementById('proveedoresTbody').innerHTML =
            '<tr><td colspan="7" style="padding:48px;text-align:center;color:#ef4444">Error al cargar proveedores</td></tr>';
    }
}

function sanitizeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Init
document.addEventListener('DOMContentLoaded', loadProveedores);
document.getElementById('provModal')?.addEventListener('click', function(e) { if (e.target === this) closeProvModal(); });
document.getElementById('provDetalleModal')?.addEventListener('click', function(e) { if (e.target === this) closeProvDetalle(); });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
