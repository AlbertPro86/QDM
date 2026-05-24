'use strict';
/**
 * CRM QUANTUN Digital — Módulo de Tareas
 */

const trState = {
    vista:    'lista',
    debTimer: null,
    editId:   null,
};

/* ═══════════════════════════════════════════════════════════ VISTA TOGGLE */
function switchVista(v) {
    trState.vista = v;

    const btnLista  = document.getElementById('btnVista-lista');
    const btnKanban = document.getElementById('btnVista-kanban');

    btnLista.classList.toggle('active',  v === 'lista');
    btnKanban.classList.toggle('active', v === 'kanban');

    document.getElementById('vistaLista').style.display  = v === 'lista'  ? 'block' : 'none';
    document.getElementById('vistaKanban').style.display = v === 'kanban' ? 'block' : 'none';

    cargarTareas();
}

/* ═══════════════════════════════════════════════════════ CARGAR / RENDER */
function cargarTareas() {
    const estado  = document.getElementById('filtroEstado').value;
    const prior   = document.getElementById('filtroPrioridad').value;
    const buscar  = document.getElementById('filtroBuscar').value.trim();
    const desde   = document.getElementById('filtroFechaDesde').value;
    const hasta   = document.getElementById('filtroFechaHasta').value;

    const params = new URLSearchParams();
    if (estado) params.set('estado',    estado);
    if (prior)  params.set('prioridad', prior);
    if (buscar) params.set('buscar',    buscar);
    if (desde)  params.set('desde',     desde);
    if (hasta)  params.set('hasta',     hasta);

    // Placeholder de carga
    if (trState.vista === 'lista') {
        document.getElementById('tareasTbody').innerHTML =
            `<tr><td colspan="6" style="padding:48px;text-align:center;color:#94a3b8">Cargando...</td></tr>`;
    }

    fetch(`api/tareas.php?${params}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Error al cargar tareas');
            actualizarKpis(res.resumen);
            if (trState.vista === 'lista') {
                renderLista(res.data);
            } else {
                renderKanban(res.data, res.resumen);
            }
        })
        .catch(err => {
            const msg = `<tr><td colspan="6" style="padding:48px;text-align:center;color:#ef4444">${err.message}</td></tr>`;
            document.getElementById('tareasTbody').innerHTML = msg;
        });
}

function actualizarKpis(resumen) {
    ['pendiente','en_progreso','revision','completado'].forEach(k => {
        const el = document.getElementById(`kpi-${k}`);
        if (el) el.textContent = resumen[k] || 0;
    });
}

/* ── LISTA ────────────────────────────────────────────────────────────── */
function renderLista(rows) {
    const tbody = document.getElementById('tareasTbody');

    if (!rows.length) {
        tbody.innerHTML = `
            <tr><td colspan="6" style="padding:64px;text-align:center;color:#94a3b8">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"
                     style="opacity:.3;margin:0 auto 10px;display:block">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Sin tareas. ¡Crea la primera!
            </td></tr>`;
        return;
    }

    const today = new Date(); today.setHours(0,0,0,0);

    tbody.innerHTML = rows.map(t => {
        const prioBg  = { alta:'#fee2e2', media:'#fef3c7', baja:'#dcfce7' };
        const prioClr = { alta:'#dc2626', media:'#d97706', baja:'#16a34a' };
        const prioDot = { alta:'#ef4444', media:'#f59e0b', baja:'#22c55e' };
        const prioLabel={ alta:'Alta',   media:'Media',   baja:'Baja'     };

        const estBg  = { pendiente:'#f1f5f9', en_progreso:'#dbeafe', revision:'#fef3c7', completado:'#dcfce7' };
        const estClr = { pendiente:'#475569', en_progreso:'#2563eb', revision:'#d97706', completado:'#16a34a' };
        const estLabel={ pendiente:'Pendiente', en_progreso:'En Proceso', revision:'Revisión', completado:'Completado' };

        let fechaHtml = '—';
        let vencido = false;
        if (t.fecha_limite) {
            const fl = new Date(t.fecha_limite + 'T00:00:00');
            vencido = fl < today && t.estado !== 'completado';
            fechaHtml = `<span style="color:${vencido ? '#ef4444' : '#475569'};font-weight:${vencido ? '700' : '400'}">${vencido ? '<span style="font-size:10px;margin-right:2px">!</span>' : ''}${fl.toLocaleDateString('es-CO',{day:'2-digit',month:'short'})}</span>`;
        }

        const vinculo = t.cliente_nombre
            ? `<div style="font-size:11px;color:#57544D;margin-top:3px">${t.cliente_nombre}</div>`
            : t.lead_nombre
            ? `<div style="font-size:11px;color:#57544D;margin-top:3px">${t.lead_nombre}</div>`
            : '';

        return `
        <tr style="border-bottom:1px solid #f1f5f9;transition:background .1s"
            onmouseenter="this.style.background='#fafafa'"
            onmouseleave="this.style.background='transparent'">

            <td style="padding:12px 16px">
                <span style="display:inline-flex;align-items:center;gap:5px;background:${prioBg[t.prioridad]};color:${prioClr[t.prioridad]};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700">
                    <span style="width:6px;height:6px;border-radius:50%;background:${prioDot[t.prioridad]};display:inline-block"></span>
                    ${prioLabel[t.prioridad]}
                </span>
            </td>

            <td style="padding:12px;max-width:260px">
                <div style="font-weight:700;font-size:13px;color:#0E0E0C;line-height:1.3">${escapeHtml(t.titulo)}</div>
                ${t.descripcion ? `<div style="font-size:11px;color:#94a3b8;margin-top:2px;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden">${escapeHtml(t.descripcion)}</div>` : ''}
                ${vinculo}
            </td>

            <td style="padding:12px;font-size:12px;color:#475569">
                ${t.responsable ? escapeHtml(t.responsable) : '<span style="color:#d1d5db">—</span>'}
            </td>

            <td style="padding:12px;text-align:center;font-size:12px">${fechaHtml}</td>

            <td style="padding:12px;text-align:center">
                <select onchange="cambiarEstadoTarea(this.value, ${t.id})" onclick="event.stopPropagation()"
                    style="background:${estBg[t.estado]};color:${estClr[t.estado]};border:none;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;outline:none;appearance:none;-webkit-appearance:none;background-image:none;padding-right:4px">
                    <option value="pendiente" style="background:#f1f5f9;color:#475569" ${t.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                    <option value="en_progreso" style="background:#dbeafe;color:#2563eb" ${t.estado === 'en_progreso' ? 'selected' : ''}>En Proceso</option>
                    <option value="revision" style="background:#fef3c7;color:#d97706" ${t.estado === 'revision' ? 'selected' : ''}>Revisión</option>
                    <option value="completado" style="background:#dcfce7;color:#16a34a" ${t.estado === 'completado' ? 'selected' : ''}>Completado</option>
                </select>
            </td>

            <td style="padding:12px;text-align:center">
                <div style="display:flex;align-items:center;justify-content:center;gap:4px">
                    <button onclick="verDetallesTarea(${t.id}); event.stopPropagation();" title="Ver detalles"
                        style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;color:#94a3b8"
                        onmouseenter="this.style.background='#FAFAF7';this.style.color='#0E0E0C'"
                        onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <button onclick="editarTarea(${t.id}); event.stopPropagation();" title="Editar"
                        style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;color:#94a3b8"
                        onmouseenter="this.style.background='#f1f5f9';this.style.color='#0f172a'"
                        onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>
                    <button onclick="confirmarEliminarTarea(${t.id},'${escapeHtml(t.titulo).replace(/'/g,"&#39;")}'); event.stopPropagation();" title="Cancelar tarea"
                        style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;color:#94a3b8"
                        onmouseenter="this.style.background='#fee2e2';this.style.color='#ef4444'"
                        onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

/* ── KANBAN ───────────────────────────────────────────────────────────── */
function renderKanban(rows, resumen) {
    const cols = { pendiente:[], en_progreso:[], revision:[], completado:[] };
    rows.forEach(t => { if (cols[t.estado]) cols[t.estado].push(t); });

    const prioDot  = { alta:'#ef4444', media:'#f59e0b', baja:'#22c55e' };
    const prioLabel= { alta:'Alta', media:'Media', baja:'Baja' };
    const today    = new Date(); today.setHours(0,0,0,0);

    Object.entries(cols).forEach(([estado, tareas]) => {
        const col = document.getElementById(`kCol-${estado}`);
        const cnt = document.getElementById(`kCount-${estado}`);
        if (cnt) cnt.textContent = resumen[estado] || 0;

        if (!tareas.length) {
            col.innerHTML = `<div style="text-align:center;padding:24px 12px;color:#cbd5e1;font-size:11px;border:1.5px dashed #e2e8f0;border-radius:8px">Sin tareas</div>`;
            return;
        }

        col.innerHTML = tareas.map(t => {
            let vencido = false;
            let fechaHtml = '';
            if (t.fecha_limite) {
                const fl = new Date(t.fecha_limite + 'T00:00:00');
                vencido = fl < today && t.estado !== 'completado';
                fechaHtml = `<div style="font-size:10px;color:${vencido ? '#ef4444' : '#94a3b8'};margin-top:8px;font-weight:${vencido ? '700' : '400'}">
                    ${vencido ? '! Vencida · ' : ''}${fl.toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'})}
                </div>`;
            }

            return `
            <div style="background:#fff;border:1.5px solid ${vencido ? '#fecaca' : '#e2e8f0'};border-radius:10px;padding:12px;cursor:pointer;transition:all .12s"
                 onmouseenter="this.style.borderColor='#cbd5e1';this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'"
                 onmouseleave="this.style.borderColor='${vencido ? '#fecaca' : '#e2e8f0'}';this.style.boxShadow='none'"
                 onclick="editarTarea(${t.id})">
                <!-- Cabecera: prioridad dot + titulo -->
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px">
                    <div style="font-size:13px;font-weight:700;color:#0E0E0C;line-height:1.35;flex:1">${escapeHtml(t.titulo)}</div>
                    <span style="width:8px;height:8px;border-radius:50%;background:${prioDot[t.prioridad]};flex-shrink:0;margin-top:4px" title="${prioLabel[t.prioridad]}"></span>
                </div>
                <!-- Descripción -->
                ${t.descripcion ? `<div style="font-size:11px;color:#94a3b8;margin-top:4px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">${escapeHtml(t.descripcion)}</div>` : ''}
                <!-- Responsable -->
                ${t.responsable ? `<div style="display:flex;align-items:center;gap:5px;margin-top:8px">
                    <div style="width:20px;height:20px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;color:#475569">${escapeHtml(t.responsable).slice(0,2).toUpperCase()}</div>
                    <span style="font-size:11px;color:#64748b">${escapeHtml(t.responsable)}</span>
                </div>` : ''}
                <!-- Fecha -->
                ${fechaHtml}
            </div>`;
        }).join('');
    });
}

/* ═══════════════════════════════════════════════════════════ FILTROS */
function debounceTareas() {
    clearTimeout(trState.debTimer);
    trState.debTimer = setTimeout(cargarTareas, 280);
}

function limpiarFiltrosTareas() {
    document.getElementById('filtroEstado').value    = '';
    document.getElementById('filtroPrioridad').value = '';
    document.getElementById('filtroBuscar').value    = '';
    document.getElementById('filtroFechaDesde').value = '';
    document.getElementById('filtroFechaHasta').value = '';
    cargarTareas();
}

/* ═══════════════════════════════════════════════════════════ MODAL */
function abrirModalTarea(id = null) {
    trState.editId = id;
    document.getElementById('modalTareaTitulo').textContent = id ? 'Editar tarea' : 'Nueva tarea';

    // Reset
    document.getElementById('trId').value              = '';
    document.getElementById('trTitulo').value          = '';
    document.getElementById('trDesc').value            = '';
    document.getElementById('trPrioridad').value       = 'media';
    document.getElementById('trEstado').value          = 'pendiente';
    document.getElementById('trResponsable').value     = '';
    document.getElementById('trFechaLimite').value     = '';
    document.getElementById('trNotas').value           = '';

    if (id) {
        fetch(`api/tareas.php?id=${id}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.error);
                const t = res.data;
                document.getElementById('trId').value          = t.id;
                document.getElementById('trTitulo').value      = t.titulo        || '';
                document.getElementById('trDesc').value        = t.descripcion   || '';
                document.getElementById('trPrioridad').value   = t.prioridad     || 'media';
                document.getElementById('trEstado').value      = t.estado        || 'pendiente';
                document.getElementById('trResponsable').value = t.responsable   || '';
                document.getElementById('trFechaLimite').value = t.fecha_limite  || '';
                document.getElementById('trNotas').value       = t.notas         || '';
            })
            .catch(err => {
                if (typeof showToast === 'function') showToast('Error al cargar tarea', 'error');
            });
    }

    document.getElementById('modalTarea').style.display = 'flex';
    setTimeout(() => document.getElementById('trTitulo').focus(), 80);
}

function cerrarModalTarea() {
    document.getElementById('modalTarea').style.display = 'none';
    trState.editId = null;
}

async function guardarTarea() {
    const titulo = document.getElementById('trTitulo').value.trim();
    if (!titulo) {
        if (typeof showToast === 'function') showToast('El título es requerido', 'error');
        document.getElementById('trTitulo').style.borderColor = '#ef4444';
        setTimeout(() => document.getElementById('trTitulo').style.borderColor = '#e2e8f0', 2000);
        return;
    }

    const btn = document.getElementById('trBtnGuardar');
    btn.disabled = true;
    btn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" class="spin"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Guardando...`;

    const id = document.getElementById('trId').value;
    const payload = {
        titulo,
        descripcion:  document.getElementById('trDesc').value.trim()        || null,
        prioridad:    document.getElementById('trPrioridad').value,
        estado:       document.getElementById('trEstado').value,
        responsable:  document.getElementById('trResponsable').value.trim() || null,
        fecha_limite: document.getElementById('trFechaLimite').value         || null,
        notas:        document.getElementById('trNotas').value.trim()        || null,
    };
    if (id) payload.id = id;

    try {
        const res = await fetch('api/tareas.php', {
            method:  id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        }).then(r => r.json());

        if (!res.success) throw new Error(res.error || 'Error al guardar');
        if (typeof showToast === 'function') showToast(id ? 'Tarea actualizada ✓' : 'Tarea creada ✓', 'success');
        cerrarModalTarea();
        cargarTareas();
    } catch (err) {
        if (typeof showToast === 'function') showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar`;
    }
}

function editarTarea(id) {
    abrirModalTarea(id);
}

function confirmarEliminarTarea(id, titulo) {
    document.getElementById('modalElimTareaNombre').textContent =
        `La tarea "${titulo}" se marcará como cancelada.`;
    document.getElementById('modalEliminarTarea').style.display = 'flex';

    const btn = document.getElementById('btnConfirmarElimTarea');
    const handler = async () => {
        btn.removeEventListener('click', handler);
        btn.disabled = true;
        try {
            const res = await fetch(`api/tareas.php?id=${id}`, { method: 'DELETE' }).then(r => r.json());
            if (!res.success) throw new Error(res.error);
            document.getElementById('modalEliminarTarea').style.display = 'none';
            if (typeof showToast === 'function') showToast('Tarea cancelada', 'success');
            cargarTareas();
        } catch (err) {
            if (typeof showToast === 'function') showToast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    };
    btn.addEventListener('click', handler);
}

/* ═══════════════════════════════════════════════════════════ MODAL DETALLES */
function verDetallesTarea(id) {
    fetch(`api/tareas.php?id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data) throw new Error('Tarea no encontrada');
            const t = res.data;

            // Llenar modal
            document.getElementById('detalleTareaTitulo').textContent = escapeHtml(t.titulo);

            // Prioridad
            const prioBg  = { alta:'#fee2e2', media:'#fef3c7', baja:'#dcfce7' };
            const prioClr = { alta:'#dc2626', media:'#d97706', baja:'#16a34a' };
            const prioLabel = { alta:'Alta', media:'Media', baja:'Baja' };
            document.getElementById('detallePrioridad').innerHTML =
                `<span style="display:inline-flex;align-items:center;gap:5px;background:${prioBg[t.prioridad]};color:${prioClr[t.prioridad]};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700">
                    <span style="width:6px;height:6px;border-radius:50%;background:${prioClr[t.prioridad]};display:inline-block"></span>
                    ${prioLabel[t.prioridad]}
                </span>`;

            // Estado - Badge de solo lectura
            const estBg  = { pendiente:'#f1f5f9', en_progreso:'#dbeafe', revision:'#fef3c7', completado:'#dcfce7' };
            const estClr = { pendiente:'#475569', en_progreso:'#2563eb', revision:'#d97706', completado:'#16a34a' };
            const estLabel = { pendiente:'Pendiente', en_progreso:'En Proceso', revision:'Revisión', completado:'Completado' };
            document.getElementById('detalleEstado').innerHTML =
                `<span style="display:inline-flex;align-items:center;gap:5px;background:${estBg[t.estado]};color:${estClr[t.estado]};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700">
                    ${estLabel[t.estado]}
                </span>`;
            // Guardar ID para cambio de estado
            document.getElementById('detalleEstado').dataset.tareaId = t.id;

            // Descripción
            document.getElementById('detalleDescripcion').textContent = t.descripcion || '—';

            // Responsable
            document.getElementById('detalleResponsable').textContent = t.responsable || '—';

            // Fecha límite
            if (t.fecha_limite) {
                const fl = new Date(t.fecha_limite + 'T00:00:00');
                const today = new Date(); today.setHours(0,0,0,0);
                const vencido = fl < today && t.estado !== 'completado';
                const fechaTxt = fl.toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'});
                document.getElementById('detalleFecha').innerHTML =
                    `<span style="color:${vencido ? '#ef4444' : '#475569'};font-weight:${vencido ? '700' : '400'}">
                        ${vencido ? '<span style="font-size:10px;margin-right:2px">!</span>' : ''}${fechaTxt}
                    </span>`;
            } else {
                document.getElementById('detalleFecha').textContent = '—';
            }

            // Vinculo cliente/lead
            let vinculo = '—';
            if (t.cliente_nombre) {
                vinculo = `<span style="color:#57544D;font-weight:600">📌 Cliente: ${escapeHtml(t.cliente_nombre)}</span>`;
            } else if (t.lead_nombre) {
                vinculo = `<span style="color:#57544D;font-weight:600">📌 Lead: ${escapeHtml(t.lead_nombre)}</span>`;
            }
            document.getElementById('detalleVinculo').innerHTML = vinculo;

            // Notas
            document.getElementById('detalleNotas').textContent = t.notas || '—';

            // Fechas de sistema
            if (t.created_at) {
                const cd = new Date(t.created_at).toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
                document.getElementById('detalleCreated').textContent = cd;
            }
            if (t.updated_at) {
                const ud = new Date(t.updated_at).toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
                document.getElementById('detalleUpdated').textContent = ud;
            }

            // Guardar ID actual para edición
            document.getElementById('btnEditarTarea').dataset.tareaId = t.id;

            // Mostrar modal
            document.getElementById('detallesTareaModal').classList.add('show');
        })
        .catch(err => {
            if (typeof showToast === 'function') showToast(err.message, 'error');
        });
}

function closeDetallesTareaModal() {
    document.getElementById('detallesTareaModal').classList.remove('show');
}

function editarTareaDesdeModal() {
    const id = document.getElementById('btnEditarTarea').dataset.tareaId;
    closeDetallesTareaModal();
    editarTarea(parseInt(id));
}

function cambiarEstadoTarea(nuevoEstado, tareaId) {
    // Si se llama desde el modal (sin tareaId), obtenerlo del select
    if (!tareaId) {
        const selectEl = document.getElementById('detalleEstado');
        tareaId = selectEl.dataset.tareaId;
    }

    if (!tareaId || !nuevoEstado) return;

    fetch('api/tareas.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(tareaId), estado: nuevoEstado })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (typeof showToast === 'function') showToast('Estado actualizado', 'success');
            cargarTareas();
        } else {
            throw new Error(res.error || 'Error al actualizar estado');
        }
    })
    .catch(err => {
        if (typeof showToast === 'function') showToast(err.message, 'error');
        // Revertir cambio
        cargarTareas();
    });
}

/* ═══════════════════════════════════════════════════════════ UTILS */
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ═══════════════════════════════════════════════════════════ INIT */
document.addEventListener('DOMContentLoaded', () => {
    cargarTareas();

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        cerrarModalTarea();
        document.getElementById('modalEliminarTarea').style.display = 'none';
    });

    document.getElementById('trTitulo').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); guardarTarea(); }
    });
});
