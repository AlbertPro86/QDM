<?php
/**
 * CRM QUANTUN Digital — Solicitudes Web
 * Módulo para gestionar solicitudes de servicios desde el sitio público
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle    = 'Solicitudes Web';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Solicitudes Web</span>';
include __DIR__ . '/includes/header.php';
?>

<!-- KPIs -->
<div id="swKpis" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px"></div>

<div class="page-header">
    <div class="page-header-left">
        <h2 style="font-size:16px;color:var(--color-text-muted);font-weight:500">Solicitudes de servicios desde el sitio web</h2>
    </div>
    <div class="page-header-right">
        <div class="search-bar" style="width:260px">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="swSearch" placeholder="Buscar solicitud..." oninput="loadSolicitudes()">
        </div>
        <select class="form-select" id="swFilterEstado" style="width:180px;padding:10px 40px 10px 14px" onchange="loadSolicitudes()">
            <option value="todos">Todos los estados</option>
            <option value="nuevo">Nuevos</option>
            <option value="contactado">Contactados</option>
            <option value="cerrado">Cerrados</option>
        </select>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table" id="swTable">
            <thead>
                <tr>
                    <th style="width:22%">Nombre</th>
                    <th style="width:18%">Email</th>
                    <th style="width:12%">Teléfono</th>
                    <th style="width:16%">Servicio</th>
                    <th style="width:10%">Plan</th>
                    <th style="width:8%">Estado</th>
                    <th style="width:10%">Fecha</th>
                    <th style="width:4%"></th>
                </tr>
            </thead>
            <tbody id="swTableBody">
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--color-text-light)">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal detalle -->
<div class="modal-overlay" id="swModal">
    <div class="modal" style="max-width:620px">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="swModalTitle">Detalle de solicitud</h3>
                <p style="font-size:11px;color:#94a3b8;margin:3px 0 0" id="swModalDate"></p>
            </div>
            <button class="modal-close" onclick="closeSwModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <!-- Info del solicitante (solo lectura) -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                <div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Nombre</div>
                    <div id="sw_nombre" style="font-size:14px;font-weight:600;color:#0f172a"></div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Empresa</div>
                    <div id="sw_empresa" style="font-size:14px;color:#0f172a"></div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Email</div>
                    <div id="sw_email" style="font-size:14px;color:#0f172a"></div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Teléfono</div>
                    <div id="sw_telefono" style="font-size:14px;color:#0f172a"></div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                <div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Servicio solicitado</div>
                    <div id="sw_servicio" style="font-size:14px;font-weight:600;color:#0f172a"></div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Plan</div>
                    <div id="sw_plan" style="font-size:14px;color:#0f172a"></div>
                </div>
            </div>
            <div style="margin-bottom:16px" id="sw_mensaje_box">
                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Mensaje del solicitante</div>
                <div id="sw_mensaje" style="font-size:13px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;line-height:1.5"></div>
            </div>

            <div style="height:1px;background:#e2e8f0;margin:16px 0"></div>

            <!-- Campos editables -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="sw_estado">
                        <option value="nuevo">Nuevo</option>
                        <option value="contactado">Contactado</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Atendido por</label>
                    <div id="sw_atendido" style="font-size:13px;color:#475569;padding:10px 0"></div>
                </div>
            </div>
            <div class="form-group" style="margin:0 0 16px">
                <label class="form-label">Notas internas</label>
                <textarea class="form-textarea" id="sw_notas" rows="3" placeholder="Notas del equipo sobre esta solicitud..."></textarea>
            </div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:space-between;align-items:center">
            <div style="display:flex;gap:8px">
                <button class="btn btn-outline sm" id="swBtnWhatsapp" onclick="contactarWhatsapp()" title="Contactar por WhatsApp">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    WhatsApp
                </button>
                <button class="btn btn-outline sm" id="swBtnCrearCliente" onclick="crearClienteDesde()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Crear como Cliente
                </button>
            </div>
            <div style="display:flex;gap:8px">
                <button class="btn btn-outline sm" onclick="closeSwModal()">Cancelar</button>
                <button class="btn btn-accent sm" onclick="guardarSolicitud()">Guardar cambios</button>
            </div>
        </div>
    </div>
</div>

<script>
let swData = [];
let swCurrent = null;

async function loadSolicitudes() {
    try {
        const estado = document.getElementById('swFilterEstado').value;
        const buscar = document.getElementById('swSearch').value.trim();
        const params = new URLSearchParams();
        if (estado && estado !== 'todos') params.set('estado', estado);
        if (buscar) params.set('buscar', buscar);

        const r = await fetch('api/solicitudes_web.php?' + params.toString());
        const d = await r.json();
        if (d.success) {
            swData = d.data;
            renderKpis(d.counts);
            renderTable(d.data);
        }
    } catch(e) { console.error('Error cargando solicitudes', e); }
}

function renderKpis(c) {
    const el = document.getElementById('swKpis');
    if (!el) return;
    const kpis = [
        { label: 'Total solicitudes', value: c.total, color: '#0f172a', bg: '#f8fafc', border: '#e2e8f0' },
        { label: 'Nuevos', value: c.nuevo, color: '#2563eb', bg: '#eff6ff', border: '#bfdbfe' },
        { label: 'Contactados', value: c.contactado, color: '#ca8a04', bg: '#fefce8', border: '#fde68a' },
        { label: 'Cerrados', value: c.cerrado, color: '#16a34a', bg: '#f0fdf4', border: '#bbf7d0' },
    ];
    el.innerHTML = kpis.map(k => `
        <div style="background:${k.bg};border:1.5px solid ${k.border};border-radius:var(--radius);padding:16px 20px">
            <div style="font-size:11px;font-weight:700;color:${k.color};text-transform:uppercase;letter-spacing:.06em;opacity:.7">${k.label}</div>
            <div style="font-size:28px;font-weight:800;color:${k.color};margin-top:4px">${k.value}</div>
        </div>
    `).join('');
}

function _badge(estado) {
    const m = {
        nuevo:      { bg:'#eff6ff', c:'#2563eb', border:'#bfdbfe', t:'Nuevo' },
        contactado: { bg:'#fefce8', c:'#ca8a04', border:'#fde68a', t:'Contactado' },
        cerrado:    { bg:'#f0fdf4', c:'#16a34a', border:'#bbf7d0', t:'Cerrado' },
    };
    const s = m[estado] || m.nuevo;
    return `<span style="display:inline-block;font-size:10px;font-weight:700;color:${s.c};background:${s.bg};border:1px solid ${s.border};border-radius:20px;padding:3px 10px;white-space:nowrap">${s.t}</span>`;
}

function _esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function renderTable(data) {
    const tbody = document.getElementById('swTableBody');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--color-text-light)">No hay solicitudes</td></tr>';
        return;
    }
    tbody.innerHTML = data.map(s => {
        const d = new Date(s.created_at);
        const fecha = d.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
        return `
        <tr style="cursor:pointer" onclick="openSwModal(${s.id})">
            <td>
                <div style="font-weight:600;color:var(--color-text)">${_esc(s.nombre)}</div>
                ${s.empresa ? `<div style="font-size:11px;color:var(--color-text-muted)">${_esc(s.empresa)}</div>` : ''}
            </td>
            <td style="font-size:13px;color:var(--color-text-muted)">${_esc(s.email)}</td>
            <td style="font-size:13px">${_esc(s.telefono)}</td>
            <td style="font-size:13px;font-weight:600">${_esc(s.servicio_solicitado)}</td>
            <td style="font-size:12px;color:var(--color-text-muted)">${_esc(s.plan_solicitado) || '—'}</td>
            <td>${_badge(s.estado)}</td>
            <td style="font-size:12px;color:var(--color-text-muted)">${fecha}</td>
            <td>
                <button class="btn btn-outline sm" style="padding:4px 8px" onclick="event.stopPropagation();eliminarSolicitud(${s.id})" title="Eliminar">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function openSwModal(id) {
    const s = swData.find(x => x.id == id);
    if (!s) return;
    swCurrent = s;

    document.getElementById('swModalTitle').textContent = s.nombre;
    const d = new Date(s.created_at);
    document.getElementById('swModalDate').textContent = d.toLocaleDateString('es-CO', { weekday:'long', day:'2-digit', month:'long', year:'numeric' }) + ' · ' + d.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });

    document.getElementById('sw_nombre').textContent   = s.nombre;
    document.getElementById('sw_empresa').textContent  = s.empresa || '—';
    document.getElementById('sw_email').innerHTML       = `<a href="mailto:${_esc(s.email)}" style="color:#2563eb;text-decoration:none">${_esc(s.email)}</a>`;
    document.getElementById('sw_telefono').textContent  = s.telefono;
    document.getElementById('sw_servicio').textContent  = s.servicio_solicitado;
    document.getElementById('sw_plan').textContent      = s.plan_solicitado || '—';

    const msgBox = document.getElementById('sw_mensaje_box');
    if (s.mensaje) {
        msgBox.style.display = '';
        document.getElementById('sw_mensaje').textContent = s.mensaje;
    } else {
        msgBox.style.display = 'none';
    }

    document.getElementById('sw_estado').value   = s.estado;
    document.getElementById('sw_notas').value    = s.notas_internas || '';
    document.getElementById('sw_atendido').textContent = s.atendido_nombre || 'Sin asignar';

    document.getElementById('swModal').classList.add('show');
}

function closeSwModal() {
    document.getElementById('swModal').classList.remove('show');
    swCurrent = null;
}

async function guardarSolicitud() {
    if (!swCurrent) return;
    try {
        const r = await fetch('api/solicitudes_web.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: swCurrent.id,
                estado: document.getElementById('sw_estado').value,
                notas_internas: document.getElementById('sw_notas').value,
                atendido_por: <?= $_SESSION['user_id'] ?? 0 ?>
            })
        });
        const d = await r.json();
        if (d.success) {
            showToast('Solicitud actualizada', 'success');
            closeSwModal();
            loadSolicitudes();
        } else showToast(d.error || 'Error', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

async function eliminarSolicitud(id) {
    const ok = await confirmAction('Esta solicitud será eliminada permanentemente.', {
        title: '¿Eliminar solicitud?', okText: 'Eliminar', okColor: '#ef4444', okHover: '#dc2626'
    });
    if (!ok) return;
    try {
        const r = await fetch(`api/solicitudes_web.php?id=${id}`, { method: 'DELETE' });
        const d = await r.json();
        if (d.success) { showToast('Solicitud eliminada', 'success'); loadSolicitudes(); }
        else showToast(d.error || 'Error', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

function contactarWhatsapp() {
    if (!swCurrent) return;
    const num = swCurrent.telefono.replace(/\D/g, '');
    const msg = encodeURIComponent(`Hola ${swCurrent.nombre}, soy de QUANTUN Digital. Recibimos tu solicitud sobre "${swCurrent.servicio_solicitado}". ¿En qué momento podemos hablar?`);
    window.open(`https://wa.me/${num}?text=${msg}`, '_blank');
}

function crearClienteDesde() {
    if (!swCurrent) return;
    const params = new URLSearchParams({
        from_solicitud: swCurrent.id,
        nombre: swCurrent.nombre,
        email: swCurrent.email,
        telefono: swCurrent.telefono,
        empresa: swCurrent.empresa || ''
    });
    window.location.href = `clientes.php?nuevo=1&${params.toString()}`;
}

// Iniciar
loadSolicitudes();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
