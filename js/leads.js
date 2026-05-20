/**
 * CRM QUANTUN Digital - Leads JS Logic
 */
let allLeads = [];
let currentView = 'table';
const statusLabels = { nuevo: 'Nuevo', contactado: 'Contactado', en_negociacion: 'En Negociación', ganado: 'Ganado', perdido: 'Perdido' };
const statusBadges = { nuevo: 'badge-info', contactado: 'badge-primary', en_negociacion: 'badge-warning', ganado: 'badge-success', perdido: 'badge-danger' };

function escapeHtml(str) { if(!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

async function loadLeads() {
    const estado = document.getElementById('filterEstado').value;
    const buscar = document.getElementById('searchInput').value;
    let url = 'api/leads.php?limite=100';
    if (estado !== 'todos') url += '&estado=' + estado;
    if (buscar) url += '&buscar=' + encodeURIComponent(buscar);
    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.success) {
            allLeads = data.data;
            renderKPIs(data.counts);
            currentView === 'table' ? renderTable(allLeads) : renderKanban(allLeads);
        }
    } catch(e) { showToast('Error al cargar leads', 'error'); }
}

function renderKPIs(counts) {
    const total = Object.values(counts||{}).reduce((a,b)=>a+parseInt(b),0);
    const perdidos = counts?.perdido || 0;
    const tasa = total ? Math.round((counts?.ganado||0) / total * 100) : 0;
    const kpi = (label, value, footer, bg, tMain, tSub, pBg, pCol, pill) =>
        `<div style="background:${bg};border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s"
            onmouseenter="this.style.filter='brightness(1.08)'" onmouseleave="this.style.filter='brightness(1)'">
            <div style="flex:1;min-width:0">
                <div style="font-size:10px;font-weight:700;color:${tSub};text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">${label}</div>
                <div style="font-size:22px;font-weight:900;color:${tMain};line-height:1">${value}</div>
                <div style="font-size:11px;color:${tSub};margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${footer}</div>
            </div>
            <span style="font-size:10px;font-weight:700;background:${pBg};color:${pCol};padding:3px 9px;border-radius:20px;white-space:nowrap;flex-shrink:0">${pill}</span>
        </div>`;

    document.getElementById('leadsKpiBar').innerHTML =
        kpi('Total Leads',    total,                   'Pipeline completo',
            '#0f172a', '#ffffff', 'rgba(255,255,255,0.5)', 'rgba(255,255,255,0.15)', '#ffffff', 'Total')
      + kpi('Nuevos',         counts?.nuevo||0,        'Sin contactar aún',
            '#4f46e5', '#ffffff', 'rgba(255,255,255,0.6)', 'rgba(255,255,255,0.2)',  '#ffffff', 'Nuevos')
      + kpi('En Negociación', counts?.en_negociacion||0, `${counts?.contactado||0} contactados`,
            '#334155', '#ffffff', 'rgba(255,255,255,0.5)', 'rgba(255,255,255,0.15)', '#ffffff', 'Activos')
      + kpi('Ganados',        counts?.ganado||0,       `${tasa}% tasa de cierre`,
            '#c9f31d', '#0f172a', 'rgba(0,0,0,0.45)',      'rgba(0,0,0,0.15)',       '#0f172a', 'Ganados');
}

function renderTable(leads) {
    const tbody = document.getElementById('leadsTableBody');
    if (!leads.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:60px;color:var(--color-text-light)">No se encontraron leads.</td></tr>';
        clearLeadsSelection();
        return;
    }
    tbody.innerHTML = leads.map(l => `<tr class="animate-fade-in" data-id="${l.id}" style="cursor:pointer;transition:background-color 0.2s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''" onclick="handleLeadRowClick(event, ${l.id})">
        <td style="padding-left:16px" onclick="event.stopPropagation()">
            <input type="checkbox" class="lead-row-check" value="${l.id}" onchange="onLeadRowCheck()"
                style="width:15px;height:15px;cursor:pointer;accent-color:#dc2626">
        </td>
        <td><div style="display:flex;align-items:center;gap:12px"><div style="width:36px;height:36px;border-radius:50%;background:var(--color-surface);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:var(--color-text-muted);flex-shrink:0">${l.nombre.split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase()}</div><div><div class="cell-primary">${escapeHtml(l.nombre)}</div><div style="font-size:12px;color:var(--color-text-light)">${l.email||''}</div></div></div></td>
        <td><a href="https://wa.me/${l.whatsapp.replace(/[^0-9]/g,'')}" target="_blank" style="color:#25d366;font-weight:600">${escapeHtml(l.whatsapp)}</a></td>
        <td>${escapeHtml(l.servicio_interes)}</td>
        <td class="cell-primary">${formatMoney(l.presupuesto)}</td>
        <td><span class="badge ${statusBadges[l.estado]}">${statusLabels[l.estado]}</span></td>
        <td style="font-size:13px;color:var(--color-text-muted)">${new Date(l.created_at).toLocaleDateString('es-CO',{day:'2-digit',month:'short'})}</td>
        <td onclick="event.stopPropagation()"><div style="display:flex;gap:4px">
            <button class="btn btn-ghost btn-icon sm" onclick="editLead(${l.id})" title="Editar"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
            <button class="btn btn-ghost btn-icon sm" onclick="deleteLead(${l.id},'${escapeHtml(l.nombre)}')" title="Eliminar" style="color:var(--color-danger)"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div></td></tr>`).join('');
    updateLeadsBulkBar();
}

/* ─── SELECCIÓN MASIVA LEADS ────────────────────────────────────────────────── */

function getCheckedLeads() {
    return [...document.querySelectorAll('.lead-row-check:checked')].map(c => parseInt(c.value));
}

function onLeadRowCheck() {
    const all = document.querySelectorAll('.lead-row-check');
    const checked = document.querySelectorAll('.lead-row-check:checked');
    const master = document.getElementById('leadsCheckAll');
    master.indeterminate = checked.length > 0 && checked.length < all.length;
    master.checked = checked.length === all.length && all.length > 0;
    updateLeadsBulkBar();
}

function leadsToggleAll(master) {
    document.querySelectorAll('.lead-row-check').forEach(c => c.checked = master.checked);
    updateLeadsBulkBar();
}

function updateLeadsBulkBar() {
    const ids = getCheckedLeads();
    const bar = document.getElementById('leadsBulkBar');
    if (ids.length > 0) {
        bar.style.display = 'flex';
        document.getElementById('leadsBulkCount').textContent =
            ids.length === 1 ? '1 lead seleccionado' : `${ids.length} leads seleccionados`;
    } else {
        bar.style.display = 'none';
    }
}

function clearLeadsSelection() {
    document.querySelectorAll('.lead-row-check').forEach(c => c.checked = false);
    const master = document.getElementById('leadsCheckAll');
    if (master) { master.checked = false; master.indeterminate = false; }
    updateLeadsBulkBar();
}

function handleLeadRowClick(event, leadId) {
    // Si el click fue en checkbox o botones, no abrir modal
    if (event.target.closest('.lead-row-check') || event.target.closest('button')) return;
    editLead(leadId);
}

async function deleteSelectedLeads() {
    const ids = getCheckedLeads();
    if (!ids.length) return;
    const label = ids.length === 1 ? '1 lead' : `${ids.length} leads`;
    const confirmed = await confirmAction('Esta acción no se puede deshacer.', { title: `¿Eliminar ${label}?` });
    if (!confirmed) return;

    let ok = 0, fail = 0;
    for (const id of ids) {
        try {
            const r = await fetch(`api/leads.php?id=${id}`, { method: 'DELETE' });
            const d = await r.json();
            if (d.success) ok++; else fail++;
        } catch { fail++; }
    }

    if (ok) showToast(`${ok} lead${ok > 1 ? 's' : ''} eliminado${ok > 1 ? 's' : ''}`, 'success');
    if (fail) showToast(`${fail} no pudo${fail > 1 ? 'ron' : ''} eliminarse`, 'error');
    loadLeads();
}

function renderKanban(leads) {
    ['nuevo','contactado','en_negociacion','ganado','perdido'].forEach(s => {
        const col = document.getElementById('kanban-'+s), count = document.getElementById('count-'+s);
        const f = leads.filter(l=>l.estado===s);
        count.textContent = f.length;
        col.innerHTML = f.length ? f.map(l => `<div class="kanban-card" draggable="true" data-id="${l.id}" ondragstart="handleDragStart(event,${l.id})" ondragend="handleDragEnd(event)" onclick="editLead(${l.id})">
            <div class="kanban-card-title">${escapeHtml(l.nombre)}</div>
            <div style="font-size:12px;color:var(--color-text-muted);margin-bottom:10px">${escapeHtml(l.servicio_interes)}</div>
            <div class="kanban-card-meta">${formatMoney(l.presupuesto)}</div></div>`).join('')
            : '<div style="text-align:center;padding:20px;color:var(--color-text-light);font-size:13px">Sin leads</div>';
    });
}

let draggedLeadId = null;
function handleDragStart(e,id) { draggedLeadId=id; e.target.classList.add('dragging'); e.dataTransfer.effectAllowed='move'; }
function handleDragEnd(e) { e.target.classList.remove('dragging'); document.querySelectorAll('.kanban-column-body').forEach(c=>c.classList.remove('drag-over')); }
function handleDragOver(e) { e.preventDefault(); e.currentTarget.classList.add('drag-over'); }
function handleDragLeave(e) { e.currentTarget.classList.remove('drag-over'); }
async function handleDrop(e, status) {
    e.preventDefault(); e.currentTarget.classList.remove('drag-over');
    if(!draggedLeadId) return;
    try { const r = await fetch('api/leads.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:draggedLeadId,estado:status})});
        const d = await r.json(); if(d.success){showToast('Estado actualizado','success');loadLeads();}
    } catch(e){showToast('Error','error');} draggedLeadId=null;
}

function openLeadModal(lead=null) {
    document.getElementById('leadForm').reset(); document.getElementById('leadId').value='';
    if(lead){
        document.getElementById('leadModalTitle').textContent='Editar Lead';
        document.getElementById('leadEstadoGroup').style.display='block';
        document.getElementById('leadId').value=lead.id;
        document.getElementById('leadNombre').value=lead.nombre;
        document.getElementById('leadWhatsapp').value=lead.whatsapp;
        document.getElementById('leadEmail').value=lead.email||'';
        document.getElementById('leadServicio').value=lead.servicio_interes;
        document.getElementById('leadPresupuesto').value=lead.presupuesto;
        document.getElementById('leadUrl').value=lead.url_actual||'';
        document.getElementById('leadFuente').value=lead.fuente;
        document.getElementById('leadNotas').value=lead.notas||'';
        document.getElementById('leadEstado').value=lead.estado;
    } else {
        document.getElementById('leadModalTitle').textContent='Nuevo Lead';
        document.getElementById('leadEstadoGroup').style.display='none';
    }
    document.getElementById('leadModal').classList.add('show');
}
function closeLeadModal() { document.getElementById('leadModal').classList.remove('show'); }
function editLead(id) { const l = allLeads.find(x=>x.id==id); if(l) openLeadModal(l); }

async function saveLead(e) {
    e.preventDefault();
    const id = document.getElementById('leadId').value;
    const payload = { nombre:document.getElementById('leadNombre').value, whatsapp:document.getElementById('leadWhatsapp').value,
        email:document.getElementById('leadEmail').value, servicio_interes:document.getElementById('leadServicio').value,
        presupuesto:parseFloat(document.getElementById('leadPresupuesto').value)||0, url_actual:document.getElementById('leadUrl').value,
        fuente:document.getElementById('leadFuente').value, notas:document.getElementById('leadNotas').value };
    if(id){payload.id=parseInt(id);payload.estado=document.getElementById('leadEstado').value;}
    try { const r = await fetch('api/leads.php',{method:id?'PUT':'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const d = await r.json(); if(d.success){showToast(d.message,'success');closeLeadModal();loadLeads();} else showToast(d.error||'Error','error');
    } catch(e){showToast('Error de conexión','error');}
}

async function deleteLead(id,nombre) {
    const ok = await confirmAction(`"${nombre}" será eliminado permanentemente.`, { title: '¿Eliminar lead?' });
    if (!ok) return;
    try { const r = await fetch(`api/leads.php?id=${id}`,{method:'DELETE'}); const d = await r.json();
        if(d.success){showToast('Lead eliminado','success');loadLeads();}
    } catch(e){showToast('Error','error');}
}

// View toggle
document.querySelectorAll('.view-toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.view-toggle-btn').forEach(b=>b.classList.remove('active')); btn.classList.add('active');
        currentView = btn.dataset.view;
        document.getElementById('tableView').classList.toggle('hidden', currentView!=='table');
        document.getElementById('kanbanView').classList.toggle('hidden', currentView!=='kanban');
        currentView==='table' ? renderTable(allLeads) : renderKanban(allLeads);
    });
});

document.getElementById('filterEstado').addEventListener('change', loadLeads);
let st; document.getElementById('searchInput').addEventListener('input', function(){clearTimeout(st);st=setTimeout(loadLeads,300);});
document.getElementById('leadModal').addEventListener('click', function(e){if(e.target===this)closeLeadModal();});
document.addEventListener('keydown', function(e){if(e.key==='Escape')closeLeadModal();});

loadLeads();
