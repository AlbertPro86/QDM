/**
 * CRM QUANTUN Digital - Finanzas JS
 */

function escapeHtml(s){ if(!s)return''; const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

/* ── MODAL NOTIFICAR PAGO ÚNICO ─────────────────────────────────────────────── */

let _fnMsgCanal      = 'wa';
let _fnMsgCliente    = null;   // { id, nombre, telefono, email }
let _fnMsgPlantilla  = null;
let _fnMsgPlantillas = [];

async function fnAbrirMsgModal(clienteId) {
    const nombreCliente = fnUnicosClienteMap[clienteId] || '—';

    // Obtener datos del cliente (teléfono, email)
    try {
        const r = await fetch(`api/clientes.php?id=${clienteId}`);
        const d = await r.json();
        const c = d.success && d.data ? d.data : null;
        _fnMsgCliente = {
            id:               clienteId,
            nombre_comercial: nombreCliente,
            telefono:         c?.telefono || '',
            email_facturacion:c?.email_facturacion || ''
        };
    } catch(e) {
        _fnMsgCliente = { id: clienteId, nombre_comercial: nombreCliente, telefono: '', email_facturacion: '' };
    }

    document.getElementById('fnMsgNombre').textContent = nombreCliente;
    fnMsgSwitchTab('wa');
    fnMsgMostrarStep(1);

    if (!_fnMsgPlantillas.length) {
        const r = await fetch('api/mensajes_plantillas.php?filtro=todas');
        const d = await r.json();
        _fnMsgPlantillas = d.success ? d.data : [];
    }
    fnMsgRenderPlantillas();
    document.getElementById('fnMsgModal').classList.add('show');
}

function fnCerrarMsgModal() {
    document.getElementById('fnMsgModal').classList.remove('show');
    _fnMsgCliente = null; _fnMsgPlantilla = null;
}

function fnMsgSwitchTab(canal) {
    _fnMsgCanal = canal;
    document.getElementById('fnMsgTabWA').style.borderBottomColor    = canal === 'wa'    ? '#25D366' : 'transparent';
    document.getElementById('fnMsgTabWA').style.color                = canal === 'wa'    ? '#0f172a' : '#94a3b8';
    document.getElementById('fnMsgTabEmail').style.borderBottomColor = canal === 'email' ? '#4f46e5' : 'transparent';
    document.getElementById('fnMsgTabEmail').style.color             = canal === 'email' ? '#0f172a' : '#94a3b8';
    fnMsgMostrarStep(1);
}

function fnMsgMostrarStep(n) {
    document.getElementById('fnMsgStep1').style.display       = n === 1 ? '' : 'none';
    document.getElementById('fnMsgStep2WA').style.display     = (n === 2 && _fnMsgCanal === 'wa')    ? 'flex' : 'none';
    document.getElementById('fnMsgStep2Email').style.display  = (n === 2 && _fnMsgCanal === 'email') ? 'flex' : 'none';
    document.getElementById('fnMsgBtnWA').style.display       = (n === 2 && _fnMsgCanal === 'wa')    ? 'inline-flex' : 'none';
    document.getElementById('fnMsgBtnEmail').style.display    = (n === 2 && _fnMsgCanal === 'email') ? 'inline-flex' : 'none';
}

function fnMsgVolverStep1() { _fnMsgPlantilla = null; fnMsgMostrarStep(1); }

function fnMsgReemplazarVars(texto) {
    if (!_fnMsgCliente) return texto;
    return texto
        .replace(/\{\{cliente_nombre\}\}/g, _fnMsgCliente.nombre_comercial || '')
        .replace(/\{\{numero_cotizacion\}\}/g, '')
        .replace(/\{\{total\}\}/g, '')
        .replace(/\{\{moneda\}\}/g, 'COP')
        .replace(/\{\{fecha\}\}/g, new Date().toLocaleDateString('es-CO'))
        .replace(/\{\{vigencia\}\}/g, '');
}

function fnMsgRenderPlantillas() {
    const grid = document.getElementById('fnMsgPlantillasGrid');
    if (!_fnMsgPlantillas.length) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#94a3b8;font-size:13px">Sin plantillas disponibles</div>';
        return;
    }
    grid.innerHTML = '';
    _fnMsgPlantillas.forEach(p => {
        const card = document.createElement('div');
        card.style.cssText = 'border:1.5px solid #e2e8f0;border-radius:10px;padding:12px 14px;cursor:pointer;transition:all .15s;background:#fff';
        card.onmouseenter = () => { card.style.borderColor='#4f46e5'; card.style.background='#fafaff'; };
        card.onmouseleave = () => { card.style.borderColor='#E8E5DD'; card.style.background='#fff'; };
        const preview = p.contenido.length > 80 ? p.contenido.substring(0,80) + '…' : p.contenido;
        const badge   = p.es_predefinida
            ? '<span style="background:#0E0E0C;color:#C6F24E;padding:2px 7px;border-radius:20px;font-size:9px;font-weight:700">Predefinida</span>'
            : '<span style="background:var(--color-info-bg);color:var(--color-info);padding:2px 7px;border-radius:20px;font-size:9px;font-weight:700">Personal</span>';
        card.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px">
                <div style="font-size:12px;font-weight:800;color:#0E0E0C;line-height:1.3">${escapeHtml(p.nombre)}</div>
                ${badge}
            </div>
            <div style="font-size:11px;color:#57544D;font-style:italic;line-height:1.5">"${escapeHtml(preview)}"</div>`;
        card.onclick = () => fnMsgSeleccionarPlantilla(p);
        grid.appendChild(card);
    });
}

function fnMsgSeleccionarPlantilla(p) {
    _fnMsgPlantilla = p;
    const texto = fnMsgReemplazarVars(p.contenido);
    if (_fnMsgCanal === 'wa') {
        const tel = (_fnMsgCliente.telefono || '').replace(/\D/g,'');
        document.getElementById('fnMsgWADest').textContent    = _fnMsgCliente.nombre_comercial + (tel ? ' · ' + tel : ' (sin teléfono)');
        document.getElementById('fnMsgWAPreview').textContent = texto;
        fnMsgMostrarStep(2);
    } else {
        const email = _fnMsgCliente.email_facturacion || '';
        document.getElementById('fnMsgEmailDest').textContent   = _fnMsgCliente.nombre_comercial + (email ? ' · ' + email : ' (sin correo)');
        document.getElementById('fnMsgEmailAsunto').value       = 'Mensaje de QUANTUN Digital para ' + _fnMsgCliente.nombre_comercial;
        document.getElementById('fnMsgEmailPreview').textContent = texto;
        fnMsgMostrarStep(2);
    }
}

function fnMsgConfirmarWA() {
    if (!_fnMsgPlantilla || !_fnMsgCliente) return;
    const tel = (_fnMsgCliente.telefono || '').replace(/\D/g,'');
    if (!tel) { showToast('Este cliente no tiene teléfono registrado', 'error'); return; }
    const texto = fnMsgReemplazarVars(_fnMsgPlantilla.contenido);
    window.open('https://wa.me/57' + tel + '?text=' + encodeURIComponent(texto), '_blank');
    fnCerrarMsgModal();
}

async function fnMsgConfirmarEmail() {
    if (!_fnMsgPlantilla || !_fnMsgCliente) return;
    const email = _fnMsgCliente.email_facturacion || '';
    if (!email) { showToast('Este cliente no tiene correo registrado', 'error'); return; }
    const asunto = document.getElementById('fnMsgEmailAsunto').value.trim();
    if (!asunto) { showToast('Escribe el asunto del correo', 'warning'); return; }

    const btn = document.getElementById('fnMsgBtnEmail');
    btn.disabled = true; btn.textContent = 'Enviando...';
    const texto = fnMsgReemplazarVars(_fnMsgPlantilla.contenido);
    try {
        const r = await fetch('api/enviar_correo_cliente.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ cliente_id: _fnMsgCliente.id, asunto, mensaje_texto: texto, imagen_path: _fnMsgPlantilla.imagen || '' })
        });
        const d = await r.json();
        if (d.success) { showToast('Correo enviado a ' + email, 'success'); fnCerrarMsgModal(); }
        else showToast(d.error || 'Error al enviar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
    btn.disabled = false;
    btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Enviar correo';
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('fnMsgModal')?.addEventListener('click', function(e) {
        if (e.target === this) fnCerrarMsgModal();
    });
});

/* ─── ESTADO PERÍODO ──────────────────────────────────────────────────────── */

let fnPeriodo = { tipo: 'mes', desde: '', hasta: '' };
// Datos completos de transacciones (para filtrado local sin recargar KPIs)
let fnTxDataFull = [];

/* ─── INIT ─────────────────────────────────────────────────────────────────── */

let stt;
// Vencidas de suscripciones (cliente_servicios) para el KPI "Por Cobrar"
let _fnVencidasSubsMonto = 0;
let _fnVencidasSubsCount = 0;
let _fnLastResumen       = null; // último resumen de transacciones para re-renderizar

document.addEventListener('DOMContentLoaded', function() {
    // Filtros tabla clientes
    const filterClienteNombreEl = document.getElementById('filterClienteNombre');
    const filterServicioEl = document.getElementById('filterServicio');
    const filterEstadoRenovacionEl = document.getElementById('filterEstadoRenovacion');
    if (filterClienteNombreEl) filterClienteNombreEl.addEventListener('input', function(){ clearTimeout(stt); stt = setTimeout(aplicarFiltrosClientes, 300); });
    if (filterServicioEl) filterServicioEl.addEventListener('change', aplicarFiltrosClientes);
    if (filterEstadoRenovacionEl) filterEstadoRenovacionEl.addEventListener('change', aplicarFiltrosClientes);

    // Período por defecto: mes actual
    setPeriodo('mes');

    // Si viene con hash #pagosUnicos, activar ese tab
    if (window.location.hash === '#pagosUnicos') {
        switchTab('pagosUnicos');
    }
});

/* ─── PERÍODO ──────────────────────────────────────────────────────────────── */

function setPeriodo(tipo) {
    const hoy = new Date();
    const pad = n => String(n).padStart(2, '0');
    const fmt = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

    let desde, hasta;

    if (tipo === 'hoy') {
        desde = hasta = fmt(hoy);
    } else if (tipo === 'semana') {
        const lunes = new Date(hoy);
        lunes.setDate(hoy.getDate() - ((hoy.getDay() + 6) % 7));
        desde = fmt(lunes);
        hasta = fmt(hoy);
    } else if (tipo === 'mes') {
        desde = `${hoy.getFullYear()}-${pad(hoy.getMonth()+1)}-01`;
        hasta = fmt(hoy);
    } else if (tipo === 'año') {
        desde = `${hoy.getFullYear()}-01-01`;
        hasta = fmt(hoy);
    } else if (tipo === 'custom') {
        desde = document.getElementById('fnDesde')?.value || '';
        hasta  = document.getElementById('fnHasta')?.value || '';
        if (!desde || !hasta) return;
    }

    fnPeriodo = { tipo, desde, hasta };

    // Actualizar inputs de fecha
    const inDesde = document.getElementById('fnDesde');
    const inHasta = document.getElementById('fnHasta');
    if (inDesde) inDesde.value = desde;
    if (inHasta) inHasta.value = hasta;

    // Resaltar botón activo
    document.querySelectorAll('#fnPeriodoBtns button').forEach(btn => {
        const isActive = btn.getAttribute('data-periodo') === tipo;
        btn.style.background = isActive ? '#0E0E0C' : '#fff';
        btn.style.color = isActive ? '#C6F24E' : '#57544D';
        btn.style.borderColor = isActive ? '#0E0E0C' : '#E8E5DD';
    });

    loadAll();
}

/* ─── LOAD ALL ─────────────────────────────────────────────────────────────── */

async function loadAll() {
    await Promise.all([
        loadTransacciones(),
        loadClientesFinanzas()
    ]);
}

/* ─── TRANSACCIONES ────────────────────────────────────────────────────────── */

async function loadTransacciones() {
    const { desde, hasta } = fnPeriodo;
    const params = new URLSearchParams({ desde, hasta, limite: 500 });

    try {
        const r = await fetch(`api/transacciones.php?${params}`);
        const d = await r.json();
        if (d.success) {
            fnTxDataFull  = d.data || [];
            _fnLastResumen = d.resumen;
            renderKpis(d.resumen);
            filterTxTable(); // aplica filtros actuales sobre los datos
        }
    } catch(e) { console.error('Error cargando transacciones:', e); }
}

/* Carga TODOS los ingresos pendiente+vencido sin restricción de fecha */
async function mostrarPorCobrar() {
    document.getElementById('fnTipo').value   = 'ingreso';
    document.getElementById('fnEstado').value = 'por_cobrar';

    // Banner informativo en la tabla
    const wrap = document.getElementById('fnTxTable');
    if (wrap) wrap.innerHTML = '<div style="padding:24px;text-align:center;color:#92400E;font-size:13px">Cargando por cobrar…</div>';

    try {
        const r = await fetch('api/transacciones.php?estado=por_cobrar&limite=500');
        const d = await r.json();
        if (d.success) {
            fnTxDataFull = d.data || [];
            // Mostrar banner sobre la tabla
            _fnPorCobrarMode = true;
            filterTxTable();
        }
    } catch(e) { console.error('Error cargando por cobrar:', e); }
}
let _fnPorCobrarMode = false;

function filterTxTable() {
    const buscar = (document.getElementById('fnBuscar')?.value || '').toLowerCase();
    const tipo   = document.getElementById('fnTipo')?.value || 'todos';
    const estado = document.getElementById('fnEstado')?.value || 'todos';

    let data = fnTxDataFull;
    if (tipo !== 'todos') data = data.filter(t => t.tipo === tipo);
    if (estado === 'por_cobrar') data = data.filter(t => t.estado === 'pendiente' || t.estado === 'vencido');
    else if (estado !== 'todos') data = data.filter(t => t.estado === estado);
    if (buscar) {
        data = data.filter(t =>
            (t.concepto || '').toLowerCase().includes(buscar) ||
            (t.titulo || '').toLowerCase().includes(buscar) ||
            (t.cliente_nombre || '').toLowerCase().includes(buscar) ||
            (t.lead_nombre || '').toLowerCase().includes(buscar) ||
            (t.proveedor || '').toLowerCase().includes(buscar)
        );
    }
    renderTxTable(data);
}

function renderKpis(r) {
    const cobrado    = parseFloat(r.total_ingresos) || 0;
    const egresos    = parseFloat(r.total_egresos)  || 0;
    const balance    = cobrado - egresos;
    const balPos     = balance >= 0;

    // Por cobrar: transacciones pendientes/vencidas + suscripciones vencidas de cliente_servicios
    const txPorCobrar    = parseFloat(r.total_por_cobrar) || 0;
    const pendienteMonto = txPorCobrar + _fnVencidasSubsMonto;
    const pendienteCount = (r.count_por_cobrar || 0) + _fnVencidasSubsCount;
    const vencidosCount  = (r.count_vencidos_pc || 0) + _fnVencidasSubsCount;
    const pendienteLabel = vencidosCount > 0
        ? `${pendienteCount} por cobrar (${vencidosCount} vencido${vencidosCount !== 1 ? 's' : ''})`
        : `${pendienteCount} por cobrar`;

    const balBg       = balPos ? '#C6F24E' : '#F4DEDB';
    const balTextMain = '#0E0E0C';
    const balTextSub  = balPos ? 'rgba(0,0,0,0.45)' : 'rgba(0,0,0,0.4)';
    const balPillBg   = balPos ? 'rgba(0,0,0,0.12)'  : '#E8BCB8';
    const balPillCol  = '#0E0E0C';
    const balPill     = balPos ? 'Positivo' : 'Negativo';

    const kpi = (label, value, sub, bg, borderCol, accent, pillBg, pillCol, pill, action='') =>
        `<a href="#" onclick="${action}return false;" style="background:${bg};border:1.5px solid ${borderCol};border-radius:3px;padding:16px 20px;position:relative;overflow:hidden;transition:box-shadow .15s;text-decoration:none;display:block;cursor:pointer"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:${accent};text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">${label}</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1;font-family:var(--font-secondary)">${value}</div>
            <div style="font-size:10px;color:#8A867C;margin-top:5px;font-family:var(--font-secondary)">COP</div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D">${sub}</span>
                <span style="font-size:10px;font-weight:700;background:${pillBg};color:${pillCol};padding:2px 8px;border-radius:100px;white-space:nowrap">${pill}</span>
            </div>
        </a>`;

    document.getElementById('fnKpis').innerHTML =
        kpi('Cobrado',    moneyNum(cobrado),       `${r.count_ingresos||0} ingresos pagados`,
            '#E3F1E8', '#B8DEC5', '#1B5A39', '#C8EAD3', '#1B5A39', 'Cobrado',
            "document.getElementById('fnTipo').value='ingreso';document.getElementById('fnEstado').value='pagado';filterTxTable();")
      + kpi('Egresos',    moneyNum(egresos),        `${r.count_egresos||0} gastos registrados`,
            '#F4DEDB', '#E8BCB8', '#6E211B', '#F4DEDB', '#6E211B', 'Gastos',
            "document.getElementById('fnTipo').value='egreso';document.getElementById('fnEstado').value='pagado';filterTxTable();")
      + kpi('Por Cobrar', moneyNum(pendienteMonto), pendienteLabel,
            '#FEF3C7', '#FDE68A', '#92400E', '#FEF3C7', '#92400E', 'Pendiente',
            'mostrarPorCobrar()')
      + kpi('Balance',    moneyNum(balance),        'Cobrado − Egresos',
            balBg, balPos ? '#A8D87A' : '#E8BCB8', balPos ? '#1B5A39' : '#6E211B',
            balPos ? 'rgba(0,0,0,0.12)' : 'rgba(255,255,255,0.2)',
            balPos ? '#0E0E0C' : '#ffffff', balPill,
            "document.getElementById('fnTipo').value='todos';document.getElementById('fnEstado').value='todos';filterTxTable();");
}

function renderTxTable(data) {
    const wrap = document.getElementById('fnTxTable');
    if (!wrap) return;

    // Banner modo "por cobrar" (sin filtro de fecha)
    const banner = document.getElementById('fnPorCobrarBanner');
    if (banner) banner.style.display = _fnPorCobrarMode ? 'flex' : 'none';

    if (!data || data.length === 0) {
        wrap.innerHTML = `<div style="padding:60px 20px;text-align:center;color:#8A867C;font-size:13px;font-style:italic">
            Sin transacciones para el período y filtros seleccionados.
        </div>`;
        return;
    }

    // Totales del bloque visible
    const totalIng = data.filter(t => t.tipo==='ingreso').reduce((s,t) => s+parseFloat(t.monto||0), 0);
    const totalEgr = data.filter(t => t.tipo==='egreso').reduce((s,t)  => s+parseFloat(t.monto||0), 0);
    const totalBal = totalIng - totalEgr;

    const rows = data.map(tx => {
        const fechaRef = tx.fecha_pago || tx.fecha_vencimiento || (tx.created_at ? tx.created_at.split(' ')[0] : null);
        const fecha = fechaRef
            ? new Date(fechaRef + 'T12:00:00').toLocaleDateString('es-CO', {day:'2-digit', month:'short', year:'numeric'})
            : '—';

        const titulo   = escapeHtml(tx.titulo || tx.concepto || '—');
        const _ct = _fixConcepto(tx);
        const concepto = tx.titulo && _ct && _ct !== tx.titulo ? `<div style="font-size:11px;color:#94a3b8;margin-top:1px">${escapeHtml(_ct)}</div>` : '';

        // Para egresos mostrar proveedor; para ingresos mostrar cliente/lead
        const dest = tx.tipo === 'egreso'
            ? escapeHtml(tx.proveedor || '—')
            : escapeHtml(tx.cliente_nombre || tx.lead_nombre || '—');
        const destLabel = tx.tipo === 'egreso' ? 'Proveedor' : 'Cliente';

        const tipoBg    = tx.tipo === 'ingreso' ? '#E3F1E8' : '#F4DEDB';
        const tipoColor = tx.tipo === 'ingreso' ? '#1B5A39' : '#6E211B';
        const tipoLabel = tx.tipo === 'ingreso' ? '↑ Ingreso' : '↓ Egreso';

        let estadoBg, estadoColor, estadoLabel;
        if (tx.estado === 'pagado')        { estadoBg = '#E3F1E8'; estadoColor = '#1B5A39'; estadoLabel = '✓ Pagado'; }
        else if (tx.estado === 'vencido')  { estadoBg = '#F4DEDB'; estadoColor = '#6E211B'; estadoLabel = '⚠ Vencido'; }
        else                               { estadoBg = '#FEF3C7'; estadoColor = '#92400E'; estadoLabel = '◷ Pendiente'; }

        const montoColor = tx.tipo === 'ingreso' ? '#1B5A39' : '#6E211B';
        const montoSign  = tx.tipo === 'egreso' ? '−' : '+';
        const rowBg      = tx.tipo === 'egreso' ? 'background:linear-gradient(90deg,#FDF5F4 0%,#FAFAF7 80%)' : '';

        return `<tr style="border-bottom:1px solid #E8E5DD;transition:background .12s;${rowBg}"
            onmouseenter="this.style.background='#F5F3EE'" onmouseleave="this.style.background=''">
            <td style="padding:11px 14px;font-size:12px;color:#8A867C;white-space:nowrap;font-family:var(--font-secondary)">${fecha}</td>
            <td style="padding:11px 14px">
                <div style="font-size:13px;font-weight:700;color:#0E0E0C">${titulo}</div>
                ${concepto}
            </td>
            <td style="padding:11px 14px">
                <div style="font-size:12px;color:#57544D;font-weight:600">${dest}</div>
                <div style="font-size:10px;color:#8A867C">${destLabel}</div>
            </td>
            <td style="padding:11px 14px">
                <span style="font-size:11px;font-weight:700;background:${tipoBg};color:${tipoColor};padding:3px 10px;border-radius:3px;white-space:nowrap">${tipoLabel}</span>
            </td>
            <td style="padding:11px 14px;text-align:right;font-size:14px;font-weight:900;color:${montoColor};white-space:nowrap;letter-spacing:-.3px;font-family:var(--font-secondary)">${montoSign} ${formatMoney(tx.monto)}</td>
            <td style="padding:11px 14px">
                <span style="font-size:11px;font-weight:700;background:${estadoBg};color:${estadoColor};padding:3px 10px;border-radius:3px;white-space:nowrap">${estadoLabel}</span>
            </td>
            <td style="padding:11px 8px;text-align:center;white-space:nowrap">
                <button onclick="verComprobante(${tx.id})" title="Ver comprobante"
                    style="background:none;border:none;cursor:pointer;padding:5px;border-radius:3px;color:#8A867C;transition:all .12s"
                    onmouseenter="this.style.background='#EFECE5';this.style.color='#0E0E0C'"
                    onmouseleave="this.style.background='none';this.style.color='#8A867C'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                <button onclick="abrirModalTransaccion(${tx.id})" title="Editar"
                    style="background:none;border:none;cursor:pointer;padding:5px;border-radius:3px;color:#8A867C;transition:all .12s"
                    onmouseenter="this.style.background='#EFECE5';this.style.color='#0E0E0C'"
                    onmouseleave="this.style.background='none';this.style.color='#8A867C'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button onclick="eliminarTx(${tx.id})" title="Eliminar"
                    style="background:none;border:none;cursor:pointer;padding:5px;border-radius:3px;color:#D6C5C3;transition:all .12s"
                    onmouseenter="this.style.background='#F4DEDB';this.style.color='#6E211B'"
                    onmouseleave="this.style.background='none';this.style.color='#D6C5C3'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </td>
        </tr>`;
    }).join('');

    wrap.innerHTML = `
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:#F5F3EE;border-bottom:2px solid #E8E5DD">
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap">Fecha</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Descripción</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Cliente / Proveedor</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Tipo</th>
                        <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Monto</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Estado</th>
                        <th style="padding:10px 14px;width:70px"></th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
                <tfoot>
                    <tr style="border-top:2px solid #E8E5DD">
                        <td colspan="7" style="padding:0">
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;background:#FAFAF7">
                                <!-- Ingresos -->
                                <div style="padding:14px 20px;display:flex;align-items:center;gap:12px;border-right:1px solid #E8E5DD">
                                    <div style="width:34px;height:34px;background:#E3F1E8;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <svg width="15" height="15" fill="none" stroke="#1B5A39" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5M5 12l7-7 7 7"/></svg>
                                    </div>
                                    <div>
                                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#8A867C;margin-bottom:2px">Ingresos</div>
                                        <div style="font-size:15px;font-weight:900;color:#1B5A39;letter-spacing:-.4px;font-family:var(--font-secondary)">+ ${formatMoney(totalIng)}</div>
                                        <div style="font-size:10px;color:#8A867C;margin-top:1px">${data.filter(t=>t.tipo==='ingreso').length} movimiento${data.filter(t=>t.tipo==='ingreso').length!==1?'s':''}</div>
                                    </div>
                                </div>
                                <!-- Egresos -->
                                <div style="padding:14px 20px;display:flex;align-items:center;gap:12px;border-right:1px solid #E8E5DD">
                                    <div style="width:34px;height:34px;background:#F4DEDB;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <svg width="15" height="15" fill="none" stroke="#6E211B" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12l7 7 7-7"/></svg>
                                    </div>
                                    <div>
                                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#8A867C;margin-bottom:2px">Egresos</div>
                                        <div style="font-size:15px;font-weight:900;color:#6E211B;letter-spacing:-.4px;font-family:var(--font-secondary)">− ${formatMoney(totalEgr)}</div>
                                        <div style="font-size:10px;color:#8A867C;margin-top:1px">${data.filter(t=>t.tipo==='egreso').length} movimiento${data.filter(t=>t.tipo==='egreso').length!==1?'s':''}</div>
                                    </div>
                                </div>
                                <!-- Balance -->
                                <div style="padding:14px 20px;display:flex;align-items:center;gap:12px;background:${totalBal>=0?'#E3F1E8':'#F4DEDB'}">
                                    <div style="width:34px;height:34px;background:${totalBal>=0?'rgba(27,90,57,.15)':'rgba(110,33,27,.15)'};border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <svg width="15" height="15" fill="none" stroke="${totalBal>=0?'#1B5A39':'#6E211B'}" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                    </div>
                                    <div>
                                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:${totalBal>=0?'#1B5A39':'#6E211B'};opacity:.7;margin-bottom:2px">Balance</div>
                                        <div style="font-size:15px;font-weight:900;color:${totalBal>=0?'#1B5A39':'#6E211B'};letter-spacing:-.4px;font-family:var(--font-secondary)">${totalBal>=0?'+':''}${formatMoney(totalBal)}</div>
                                        <div style="font-size:10px;color:${totalBal>=0?'#1B5A39':'#6E211B'};opacity:.7;margin-top:1px">${data.length} movimiento${data.length!==1?'s':''} en total</div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>`;
}

async function eliminarTx(id) {
    const ok = await confirmAction('¿Eliminar esta transacción?', 'Esta acción no se puede deshacer.');
    if (!ok) return;
    try {
        const r = await fetch(`api/transacciones.php?id=${id}`, { method: 'DELETE' });
        const d = await r.json();
        if (d.success) { showToast('Transacción eliminada', 'success'); loadTransacciones(); }
        else showToast(d.error || 'Error al eliminar', 'error');
    } catch(e) { showToast('Error al eliminar', 'error'); }
}

function switchTab(tab) {
    document.querySelectorAll('#fnTabs button').forEach(btn => {
        const active = btn.getAttribute('data-tab') === tab;
        btn.style.color             = active ? '#0E0E0C' : '#8A867C';
        btn.style.fontWeight        = active ? '700' : '600';
        btn.style.borderBottomColor = active ? '#0E0E0C' : 'transparent';
    });
    const tabMov    = document.getElementById('tabMovimientos');
    const tabUnicos = document.getElementById('tabPagosUnicos');
    const tabCli    = document.getElementById('tabClientes');
    if (tabMov)    tabMov.style.display    = tab === 'movimientos'  ? '' : 'none';
    if (tabUnicos) tabUnicos.style.display = tab === 'pagosUnicos'  ? '' : 'none';
    if (tabCli)    tabCli.style.display    = tab === 'clientes'     ? '' : 'none';
    if (tab === 'pagosUnicos') loadPagosUnicos();
}

/* ── PAGOS ÚNICOS ───────────────────────────────────────────── */

let fnUnicosDataFull  = [];
let fnUnicosClienteMap = {}; // { clienteId: nombreCliente }

async function loadPagosUnicos() {
    try {
        // Carga TODOS los pagos únicos (sin filtro de período)
        const r = await fetch('api/transacciones.php?frecuencia=unico&limite=500');
        const d = await r.json();
        if (d.success) {
            fnUnicosDataFull = d.data || [];
            filterUnicosTable();
        }
    } catch(e) { console.error('Error cargando pagos únicos:', e); }
}

function filterUnicosTable() {
    const buscar = (document.getElementById('fnUnicoBuscar')?.value || '').toLowerCase();
    const estado = document.getElementById('fnUnicoEstado')?.value || 'todos';

    // Solo transacciones con cliente asociado
    let data = fnUnicosDataFull.filter(t => t.cliente_id);

    // Filtro búsqueda por nombre de cliente/lead
    if (buscar) data = data.filter(t =>
        (t.cliente_nombre || '').toLowerCase().includes(buscar) ||
        (t.lead_nombre || '').toLowerCase().includes(buscar)
    );

    // Filtro estado: mantener transacciones del cliente si tiene al menos una en ese estado
    if (estado !== 'todos') {
        // Obtener keys de clientes que tienen alguna tx con ese estado
        const keysConEstado = new Set(
            fnUnicosDataFull
                .filter(t => t.estado === estado)
                .map(t => t.cliente_id ? `c_${t.cliente_id}` : `l_${t.lead_id || t.id}`)
        );
        data = data.filter(t => {
            const key = t.cliente_id ? `c_${t.cliente_id}` : `l_${t.lead_id || t.id}`;
            return keysConEstado.has(key);
        });
    }

    renderPagosUnicos(data);
}

function renderPagosUnicos(data) {
    const wrap = document.getElementById('fnUnicosTable');
    const resumenEl = document.getElementById('fnUnicoResumen');

    // Resetear mapa de clientes
    fnUnicosClienteMap = {};

    // Agrupar por cliente
    const porCliente = {};
    data.forEach(tx => {
        const key = tx.cliente_id ? `c_${tx.cliente_id}` : `l_${tx.lead_id || tx.id}`;
        const nombre = tx.cliente_nombre || tx.lead_nombre || '— Sin cliente —';
        if (!porCliente[key]) {
            porCliente[key] = { nombre, cliente_id: tx.cliente_id, txs: [], totalMonto: 0, pendiente: 0, vencido: 0 };
            if (tx.cliente_id) fnUnicosClienteMap[tx.cliente_id] = nombre;
        }
        porCliente[key].txs.push(tx);
        porCliente[key].totalMonto += parseFloat(tx.monto || 0);
        if (tx.estado === 'pendiente') porCliente[key].pendiente++;
        if (tx.estado === 'vencido')  porCliente[key].vencido++;
    });

    const clientes = Object.values(porCliente);

    // Resumen pendientes globales (solo con cliente asociado)
    const unicosConCliente = fnUnicosDataFull.filter(t => t.cliente_id);
    const totalPendMonto = unicosConCliente
        .filter(t => t.estado === 'pendiente' || t.estado === 'vencido')
        .reduce((s, t) => s + parseFloat(t.monto || 0), 0);
    const countPend = unicosConCliente.filter(t => t.estado === 'pendiente' || t.estado === 'vencido').length;

    resumenEl.innerHTML = countPend
        ? `<div style="background:#FEF3C7;border:1.5px solid #FDE68A;border-radius:3px;padding:10px 16px;display:inline-flex;align-items:center;gap:10px">
            <svg width="16" height="16" fill="none" stroke="#92400E" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <span style="font-size:10px;font-weight:700;color:#92400E;text-transform:uppercase;letter-spacing:.05em">Por cobrar</span>
            <span style="font-size:15px;font-weight:900;color:#92400E;font-family:var(--font-secondary)">${formatMoney(totalPendMonto)}</span>
            <span style="font-size:11px;color:#92400E;font-weight:600">${countPend} pendiente${countPend !== 1 ? 's' : ''}</span>
          </div>`
        : '';

    if (!clientes.length) {
        wrap.innerHTML = `<div style="padding:60px 20px;text-align:center;color:#94a3b8;font-size:13px">No hay clientes con pagos únicos registrados.</div>`;
        return;
    }

    const rows = clientes.map(cli => {
        // Badge de estado predominante
        let estadoBg, estadoColor, estadoLabel;
        if (cli.vencido > 0)        { estadoBg='#F4DEDB'; estadoColor='#6E211B'; estadoLabel=`⚠ ${cli.vencido} vencido${cli.vencido>1?'s':''}`; }
        else if (cli.pendiente > 0) { estadoBg='#FEF3C7'; estadoColor='#92400E'; estadoLabel=`◷ ${cli.pendiente} pendiente${cli.pendiente>1?'s':''}`; }
        else                        { estadoBg='#E3F1E8'; estadoColor='#1B5A39'; estadoLabel='✓ Al día'; }

        const tienePendiente = cli.pendiente > 0 || cli.vencido > 0;
        const notifBtn = (cli.cliente_id && tienePendiente)
            ? `<button onclick="fnAbrirMsgModal(${cli.cliente_id})" title="Notificar al cliente"
                style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:#FEF3C7;border:1.5px solid #FDE68A;border-radius:3px;font-size:11px;font-weight:700;color:#92400E;cursor:pointer;transition:all .12s"
                onmouseenter="this.style.background='#92400E';this.style.color='#fff';this.style.borderColor='#92400E'"
                onmouseleave="this.style.background='#FEF3C7';this.style.color='#92400E';this.style.borderColor='#FDE68A'">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                Notificar
              </button>`
            : '';
        const accionBtn = cli.cliente_id
            ? `<button onclick="window.location.href='cliente_detalle.php?id=${cli.cliente_id}'" title="Ver cliente"
                style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:3px;font-size:11px;font-weight:700;color:#57544D;cursor:pointer;transition:all .12s"
                onmouseenter="this.style.background='#0E0E0C';this.style.color='#C6F24E';this.style.borderColor='#0E0E0C'"
                onmouseleave="this.style.background='#FAFAF7';this.style.color='#57544D';this.style.borderColor='#E8E5DD'">
                Ver →
              </button>`
            : '';

        return `<tr style="border-bottom:1px solid #E8E5DD;transition:background .12s"
            onmouseenter="this.style.background='#F5F3EE'" onmouseleave="this.style.background=''">
            <td style="padding:12px 16px">
                <div style="font-size:13px;font-weight:700;color:#0E0E0C">${escapeHtml(cli.nombre)}</div>
                <div style="font-size:11px;color:#8A867C;margin-top:2px">${cli.txs.length} trabajo${cli.txs.length>1?'s':''} registrado${cli.txs.length>1?'s':''}</div>
            </td>
            <td style="padding:12px 16px;text-align:right;font-size:15px;font-weight:900;color:#1B5A39;white-space:nowrap;font-family:var(--font-secondary)">
                ${formatMoney(cli.totalMonto)}
            </td>
            <td style="padding:12px 16px">
                <span style="font-size:11px;font-weight:700;background:${estadoBg};color:${estadoColor};padding:3px 10px;border-radius:3px;white-space:nowrap">${estadoLabel}</span>
            </td>
            <td style="padding:12px 14px;text-align:right;white-space:nowrap;display:flex;gap:6px;justify-content:flex-end">${notifBtn}${accionBtn}</td>
        </tr>`;
    }).join('');

    const totalGeneral = clientes.reduce((s, c) => s + c.totalMonto, 0);

    wrap.innerHTML = `<div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:#F5F3EE;border-bottom:2px solid #E8E5DD">
                    <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Cliente</th>
                    <th style="padding:10px 16px;text-align:right;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Total</th>
                    <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:800;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Estado</th>
                    <th style="padding:10px 16px;width:160px"></th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
            <tfoot>
                <tr style="background:#F5F3EE;border-top:2px solid #E8E5DD">
                    <td style="padding:10px 16px;font-size:11px;font-weight:700;color:#8A867C">${clientes.length} cliente${clientes.length!==1?'s':''}</td>
                    <td style="padding:10px 16px;text-align:right;font-size:13px;font-weight:900;color:#1B5A39;font-family:var(--font-secondary)">${formatMoney(totalGeneral)}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>`;
}

/* ── FILTROS TABLA CLIENTES ─────────────────────────────────── */

function aplicarFiltrosClientes() {
    const nombre = document.getElementById('filterClienteNombre').value.toLowerCase();
    const svcId  = document.getElementById('filterServicio').value;
    const estado = document.getElementById('filterEstadoRenovacion').value;

    const filas = document.querySelectorAll('#clientesDetalle table tbody tr:not(.sin-resultados)');
    let visible = 0;

    const hoy = new Date();
    const mesActual = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0');

    filas.forEach(fila => {
        const textoFila = fila.textContent.toLowerCase();
        const proximaVencStr = fila.getAttribute('data-proxima-vencimiento') || '';

        // Filtro por nombre cliente
        if (nombre && !textoFila.includes(nombre)) {
            fila.style.display = 'none';
            return;
        }

        // Filtro por servicio (si está aplicado)
        if (svcId) {
            const svcsCliente = fila.getAttribute('data-servicios') || '';
            const svcsArray = svcsCliente.split(',').map(id => id.trim()).filter(id => id);
            const tieneSvc = svcsArray.includes(svcId);
            if (!tieneSvc) {
                fila.style.display = 'none';
                return;
            }
        }

        // Filtro por estado de renovación
        if (estado) {
            const venceMes = proximaVencStr.substring(0, 7) === mesActual;
            const vencido  = new Date(proximaVencStr + 'T12:00:00') < hoy;
            let filaEstado = 'activo';
            if (vencido) filaEstado = 'vencido';
            else if (venceMes) filaEstado = 'vence_mes';

            if (filaEstado !== estado) {
                fila.style.display = 'none';
                return;
            }
        }

        fila.style.display = '';
        visible++;
    });

    // Mostrar mensaje si no hay resultados
    const tbody = document.querySelector('#clientesDetalle table tbody');
    if (visible === 0 && filas.length > 0) {
        let msg = tbody.querySelector('tr.sin-resultados');
        if (!msg) {
            msg = document.createElement('tr');
            msg.className = 'sin-resultados';
            msg.innerHTML = '<td colspan="8" style="text-align:center;padding:40px;color:#8A867C;font-size:13px;font-style:italic">Sin clientes que coincidan con los filtros.</td>';
            tbody.appendChild(msg);
        }
    } else {
        const msg = tbody?.querySelector('tr.sin-resultados');
        if (msg) msg.remove();
    }
}

function limpiarFiltrosClientes() {
    document.getElementById('filterClienteNombre').value = '';
    document.getElementById('filterServicio').value = '';
    document.getElementById('filterEstadoRenovacion').value = '';
    aplicarFiltrosClientes();
}

function resetearPeriodo() {
    // Restablece período al mes actual y limpia todos los sub-filtros
    setPeriodo('mes');
    limpiarFiltrosMovimientos();
    limpiarFiltrosPagosUnicos();
    limpiarFiltrosClientes();
}

function limpiarFiltrosMovimientos() {
    _fnPorCobrarMode = false;
    document.getElementById('fnBuscar').value = '';
    document.getElementById('fnTipo').value = 'todos';
    document.getElementById('fnEstado').value = 'todos';
    loadAll(); // recarga transacciones + clientes (actualiza KPIs completos)
}

function limpiarFiltrosPagosUnicos() {
    document.getElementById('fnUnicoBuscar').value = '';
    document.getElementById('fnUnicoEstado').value = 'todos';
    filterUnicosTable();
}

/* ─── FINANCIERO REAL (CLIENTES) ──────────────────────────────────────────── */

async function loadClientesFinanzas() {
    try {
        const desde = fnPeriodo.desde || '';
        const hasta  = fnPeriodo.hasta  || '';

        let url = 'api/finanzas_clientes.php';
        if (desde && hasta) {
            url += `?desde=${desde}&hasta=${hasta}`;
        }

        const r = await fetch(url);
        const d = await r.json();
        if (d.success) {
            renderClientesKpis(d.resumen, d.periodo);
            renderClientesDetalle(d.por_cliente, d.proximas_renovaciones, d.por_servicio);
            // Actualizar globals de vencidas de suscripciones y re-renderizar KPI Por Cobrar
            _fnVencidasSubsMonto = parseFloat(d.resumen?.total_vencidas_subs) || 0;
            _fnVencidasSubsCount = parseInt(d.resumen?.count_vencidas_subs)   || 0;
            if (_fnLastResumen) renderKpis(_fnLastResumen); // re-renderiza con datos combinados
        } else {
            console.error('Error en API:', d);
        }
    } catch(e) { console.error('Error cargando datos de clientes:', e); }
}

const FREQ_LABELS_FIN = { mes:'Mensual', trimestre:'Trimestral', semestre:'Semestral', año:'Anual', unico:'Único' };

function margenColor(pct) {
    if (pct >= 50) return '#10b981';
    if (pct >= 20) return '#f59e0b';
    return '#ef4444';
}

function renderClientesKpis(r, periodo) {
    const balPositivo  = r.balance >= 0;
    const balCardClass = balPositivo ? 'cf-card--profit'  : 'cf-card--expense';
    const balPillClass = balPositivo ? 'cf-pill--profit'  : 'cf-pill--expense';
    const balPillLabel = balPositivo ? 'Positivo' : 'Negativo';
    const balCop       = balPositivo ? 'rgba(0,0,0,0.4)' : 'rgba(255,255,255,0.4)';

    let periodoLabel = periodo || 'Período filtrado';
    const mrrLabel     = 'Ingresos en el período';

    // renderClientesKpis actualiza el grid solo si el tab de clientes está activo
    // para no pisar los KPIs de transacciones; se llaman desde loadClientesFinanzas
    // que se ejecuta en paralelo con loadTransacciones — no actualizamos fnKpis aquí
    // para no crear conflicto de renderizado. Los KPIs de transacciones son la fuente de verdad.
    // Si se necesita MRR/Balance de clientes en el futuro, añadir un contenedor en el tab Clientes.
    void 0;
}

function renderClientesDetalle(porCliente, proximas, porServicio) {
    // Guardar en cache para exportación Excel
    fnClientesDataCache = { porCliente: porCliente || [], proximas: proximas || [], porServicio: porServicio || [] };

    const det = document.getElementById('clientesDetalle');

    // Tabla por cliente
    const hoyFin   = new Date();
    const mesFin   = hoyFin.getFullYear() + '-' + String(hoyFin.getMonth() + 1).padStart(2, '0');

    const clienteRows = porCliente.length ? porCliente.map(c => {
        const mColor = margenColor(c.margen_pct);

        // ¿La próxima renovación es este mes o ya venció?
        let fechaStr     = '—';
        let fechaRoja    = false;
        let fechaVencida = false;
        if (c.proxima_renovacion) {
            const vd = new Date(c.proxima_renovacion + 'T12:00:00');
            const vm = c.proxima_renovacion.substring(0, 7);
            fechaStr     = vd.toLocaleDateString('es-CO', {day:'2-digit', month:'short', year:'numeric'});
            fechaVencida = vd < hoyFin;
            fechaRoja    = vm === mesFin || fechaVencida;
        }

        const rowBg     = fechaRoja ? 'background:linear-gradient(90deg,#fff5f5,#fff 70%);border-left:3px solid #ef4444' : 'border-bottom:1px solid #f1f5f9';
        const rowCursor = fechaRoja ? 'cursor:pointer' : '';
        const rowClick  = fechaRoja ? `onclick="location.href='cliente_detalle.php?id=${c.id}'"` : '';

        const fechaCell = fechaRoja
            ? `<div style="display:flex;flex-direction:column;align-items:flex-end;gap:2px">
                 <span style="font-size:12px;font-weight:800;color:#ef4444">${fechaStr}</span>
                 <span style="font-size:10px;font-weight:700;color:#ef4444;background:#fef2f2;padding:1px 6px;border-radius:20px">${fechaVencida ? '! Vencida' : 'Este mes'}</span>
               </div>`
            : `<span style="font-size:12px;color:#94a3b8">${fechaStr}</span>`;

        return `<tr style="${rowBg};${rowCursor}" ${rowClick} data-servicios="${(c.servicios_ids||[]).join(',')}" data-proxima-vencimiento="${c.proxima_renovacion||''}">
            <td style="padding:10px 14px;font-weight:700;font-size:13px;color:#0f172a">
                <a href="cliente_detalle.php?id=${c.id}" style="color:inherit;text-decoration:none">${escapeHtml(c.nombre)}</a>
            </td>
            <td style="padding:10px 8px;text-align:center;font-size:12px;color:#64748b">${c.servicios}</td>
            <td style="padding:10px 8px;text-align:right;font-size:13px;font-weight:700;color:#10b981">${formatMoney(c.ingresos)}</td>
            <td style="padding:10px 8px;text-align:right;font-size:13px;color:#ef4444">${formatMoney(c.egresos)}</td>
            <td style="padding:10px 8px;text-align:right;font-size:13px;font-weight:800;color:${c.balance>=0?'#0f172a':'#ef4444'}">${formatMoney(c.balance)}</td>
            <td style="padding:10px 8px;text-align:center">
                <span style="font-size:11px;font-weight:800;color:${mColor};background:${mColor}18;padding:2px 8px;border-radius:20px">${c.margen_pct}%</span>
            </td>
            <td style="padding:10px 8px;text-align:right;font-size:12px;color:#64748b">${formatMoney(c.mrr)}/mes</td>
            <td style="padding:10px 14px;text-align:right">${fechaCell}</td>
        </tr>`;
    }).join('') : `<tr><td colspan="8" style="text-align:center;padding:40px;color:#8A867C;font-size:13px;font-style:italic">Sin clientes activos con servicios asignados.</td></tr>`;

    // Totales
    const sumIng  = porCliente.reduce((s, c) => s + c.ingresos, 0);
    const sumEgr  = porCliente.reduce((s, c) => s + c.egresos,  0);
    const sumBal  = sumIng - sumEgr;
    const sumMrr  = porCliente.reduce((s, c) => s + c.mrr, 0);
    const pctTot  = sumIng > 0 ? Math.round((sumBal / sumIng) * 100) : 0;

    const tablaClientes = `
        <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:13px;font-weight:800;color:#0f172a">Desglose por Cliente</span>
                <span style="font-size:11px;color:#64748b;font-weight:600">${porCliente.length} cliente${porCliente.length!==1?'s':''}</span>
            </div>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="background:#f8fafc">
                            <th style="padding:9px 14px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Cliente</th>
                            <th style="padding:9px 8px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Svcs</th>
                            <th style="padding:9px 8px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Ingreso</th>
                            <th style="padding:9px 8px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Costo</th>
                            <th style="padding:9px 8px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Balance</th>
                            <th style="padding:9px 8px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Margen</th>
                            <th style="padding:9px 8px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">MRR</th>
                            <th style="padding:9px 14px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Próx. Venc.</th>
                        </tr>
                    </thead>
                    <tbody>${clienteRows}</tbody>
                    <tfoot>
                        <tr style="background:#f8fafc;border-top:2px solid #e2e8f0">
                            <td style="padding:10px 14px;font-size:12px;font-weight:800;color:#0f172a">TOTAL</td>
                            <td style="padding:10px 8px;text-align:center;font-size:12px;color:#64748b">${porCliente.reduce((s,c)=>s+c.servicios,0)}</td>
                            <td style="padding:10px 8px;text-align:right;font-size:13px;font-weight:900;color:#10b981">${formatMoney(sumIng)}</td>
                            <td style="padding:10px 8px;text-align:right;font-size:13px;font-weight:700;color:#ef4444">${formatMoney(sumEgr)}</td>
                            <td style="padding:10px 8px;text-align:right;font-size:13px;font-weight:900;color:${sumBal>=0?'#0f172a':'#ef4444'}">${formatMoney(sumBal)}</td>
                            <td style="padding:10px 8px;text-align:center"><span style="font-size:11px;font-weight:800;color:${margenColor(pctTot)};background:${margenColor(pctTot)}18;padding:2px 8px;border-radius:20px">${pctTot}%</span></td>
                            <td style="padding:10px 8px;text-align:right;font-size:12px;font-weight:700;color:#0f172a">${formatMoney(sumMrr)}/mes</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>`;

    // Panel derecho: próximas renovaciones
    const renovRows = proximas.length ? proximas.map(p => {
        const vd      = new Date(p.fecha_vencimiento + 'T12:00:00');
        const vm      = p.fecha_vencimiento.substring(0, 7);
        const esMesP  = vm === mesFin;
        const urgColor = p.dias === 0 || esMesP ? '#ef4444' : p.dias <= 15 ? '#f59e0b' : '#64748b';
        const rowBg   = esMesP ? 'background:#fff5f5;border-left:3px solid #ef4444' : 'border-left:3px solid transparent';
        const fecha   = vd.toLocaleDateString('es-CO', {day:'2-digit', month:'short'});
        const diasTag = p.dias === 0 ? 'Hoy' : esMesP ? `${p.dias}d · Este mes` : p.dias + 'd';

        const svcs = Array.isArray(p.servicios) ? p.servicios : [p.servicio || ''];
        const svcsLabel = svcs.length > 1
            ? svcs.map(s => escapeHtml(s)).join(' · ')
            : escapeHtml(svcs[0]);

        return `<a href="cliente_detalle.php?id=${p.cliente_id||''}" style="display:flex;flex-direction:column;gap:3px;padding:10px 16px;border-bottom:1px solid #f1f5f9;text-decoration:none;${rowBg};transition:background .12s" onmouseenter="this.style.filter='brightness(.97)'" onmouseleave="this.style.filter=''">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                <span style="font-size:13px;font-weight:700;color:${esMesP?'#ef4444':'#0f172a'};flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(p.cliente)}</span>
                <span style="font-size:10px;font-weight:800;color:${urgColor};background:${urgColor}15;padding:2px 7px;border-radius:20px;white-space:nowrap;flex-shrink:0">${diasTag}</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                <span style="font-size:11px;color:#64748b;overflow:hidden;text-overflow:ellipsis;flex:1">${svcsLabel}</span>
                <span style="font-size:12px;font-weight:700;color:#10b981;flex-shrink:0">${formatMoney(p.ingreso_neto)}</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px">
                <span style="font-size:10px;color:${esMesP?'#ef4444':'#94a3b8'};font-weight:${esMesP?'700':'400'}">${fecha}</span>
                <span style="font-size:10px;color:#94a3b8">·</span>
                <span style="font-size:10px;color:#94a3b8">${FREQ_LABELS_FIN[p.frecuencia] || p.frecuencia}</span>
                ${svcs.length > 1 ? `<span style="font-size:10px;color:#94a3b8">·</span><span style="font-size:10px;background:#f1f5f9;color:#475569;padding:1px 5px;border-radius:10px;font-weight:600">${svcs.length} servicios</span>` : ''}
                ${p.margen > 0 ? `<span style="font-size:10px;color:#94a3b8">·</span><span style="font-size:10px;color:#64748b">Margen: ${formatMoney(p.margen)}</span>` : ''}
            </div>
        </a>`;
    }).join('') : `<div style="padding:40px 20px;text-align:center;color:#8A867C;font-size:13px;font-style:italic">Sin renovaciones en los próximos 30 días</div>`;

    // Panel por servicio
    const svcRows = porServicio.slice(0, 5).map(s => `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9">
            <div style="flex:1;min-width:0">
                <p style="font-size:12px;font-weight:700;color:#0f172a;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(s.nombre)}</p>
                <p style="font-size:11px;color:#94a3b8;margin:0">${s.clientes} cliente${s.clientes!==1?'s':''}</p>
            </div>
            <div style="text-align:right;flex-shrink:0;margin-left:12px">
                <p style="font-size:12px;font-weight:800;color:#10b981;margin:0">${formatMoney(s.ingresos)}</p>
                <p style="font-size:10px;color:#94a3b8;margin:0">costo: ${formatMoney(s.egresos)}</p>
            </div>
        </div>`).join('');

    det.innerHTML = `
        ${tablaClientes}
        <div style="display:flex;flex-direction:column;gap:16px">
            <!-- Próximas renovaciones -->
            <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden">
                <div style="padding:14px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px">
                    <svg width="14" height="14" fill="none" stroke="#f59e0b" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span style="font-size:13px;font-weight:800;color:#0f172a">Próximas Renovaciones</span>
                    ${proximas.length ? `<span style="font-size:10px;font-weight:800;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:20px;margin-left:auto">${proximas.length} en 30 días</span>` : ''}
                </div>
                <div style="max-height:300px;overflow-y:auto">${renovRows}</div>
            </div>
            <!-- Top servicios -->
            <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden">
                <div style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                    <span style="font-size:13px;font-weight:800;color:#0f172a">Top Servicios</span>
                </div>
                <div style="padding:4px 16px 12px">${svcRows || '<p style="text-align:center;color:#94a3b8;padding:20px;font-size:13px;font-style:italic">Sin datos</p>'}</div>
            </div>
        </div>`;
}

/* ─── DESCARGA DE INFORME ──────────────────────────────────────────────────── */

/* ─── NUEVA TRANSACCIÓN — MODAL ──────────────────────────────────────────── */

// Estado del modal
const txState = {
    tipo: 'ingreso',
    destTipo: 'cliente',
    destId: null,
    destNombre: '',
    catTab: 'subservicios',
    items: [],
    catalogCache: { subservicios: null, paquetes: null },
    editId: null   // null = crear, número = editar
};

// Resultados de búsqueda de destinatario (para acceso seguro sin pasar strings en onclick)
let txDestResults = [];
let txSearchTimer = null;

// ── Abrir / Cerrar ────────────────────────────────────────────────

async function abrirModalTransaccion(id = null) {
    // Reset estado
    txState.tipo = 'ingreso';
    txState.destTipo = 'cliente';
    txState.destId = null;
    txState.destNombre = '';
    txState.catTab = 'subservicios';
    txState.items = [];
    txState.editId = null;
    txDestResults = [];

    // Tipo buttons
    setTxTipo('ingreso');

    // Dest tabs — resetear a "Cliente"
    document.querySelectorAll('.tx-dest-tab').forEach(b => {
        b.style.borderBottomColor = 'transparent';
        b.style.color = '#94a3b8';
    });
    const firstDest = document.querySelector('.tx-dest-tab[data-dest="cliente"]');
    if (firstDest) { firstDest.style.borderBottomColor = '#0f172a'; firstDest.style.color = '#0f172a'; }
    document.getElementById('txDestBuscador').style.display = '';
    document.getElementById('txDestNuevoForm').style.display = 'none';
    document.getElementById('txDestSeleccionado').style.display = 'none';
    document.getElementById('txDestInput').value = '';
    document.getElementById('txDestInput').placeholder = 'Buscar cliente por nombre o email...';
    document.getElementById('txDestDropdown').style.display = 'none';

    // Nuevo contacto fields
    document.getElementById('txNuevoNombre').value = '';
    document.getElementById('txNuevoEmail').value = '';
    document.getElementById('txNuevoWA').value = '';

    // Cat tabs — resetear a "Sub-servicios"
    document.querySelectorAll('.tx-cat-tab').forEach(b => {
        b.style.borderBottomColor = 'transparent';
        b.style.color = '#94a3b8';
    });
    const firstCat = document.querySelector('.tx-cat-tab');
    if (firstCat) { firstCat.style.borderBottomColor = '#0f172a'; firstCat.style.color = '#0f172a'; }
    txState.catTab = 'subservicios';
    document.getElementById('txCatSearch').value = '';

    // Campos detalle
    document.getElementById('txTitulo').value = '';
    document.getElementById('txConcepto').value = '';
    document.getElementById('txMonto').value = '';
    document.getElementById('txDescuento').value = '';
    document.getElementById('txFrecuencia').value = 'unico';
    document.getElementById('txEstado').value = 'pendiente';
    document.getElementById('txFechaVenc').value = '';
    document.getElementById('txNotas').value = '';
    resetTxProv();
    resetTxArchivos();
    document.getElementById('txTotalLabel').textContent = '$ 0';
    document.getElementById('txSubtotalRow').style.display = 'none';
    document.getElementById('txDescuentoRow').style.display = 'none';
    document.getElementById('txFrecuenciaLabel').style.display = 'none';
    document.getElementById('txItemsTbody').innerHTML = '';
    document.getElementById('txItemsContainer').style.display = 'none';
    // Mostrar secciones de ingreso (por defecto)
    document.getElementById('txDestSection').style.display = '';
    document.getElementById('txEgresoSection').style.display = 'none';
    document.getElementById('txCatalogoSection').style.display = '';
    document.getElementById('txTituloSection').style.display = '';
    document.getElementById('txArchivosSection').style.display = '';

    // Título y botón por defecto (crear)
    document.getElementById('txModalTitle').textContent = 'Nueva Transacción';
    document.getElementById('txBtnGuardar').innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar transacción';

    // Cargar catálogo
    cargarTxCatalogo('subservicios');

    // Mostrar modal
    const modal = document.getElementById('modalTx');
    modal.style.display = 'flex';

    // ── Modo edición: cargar datos existentes ──────────────────────
    if (id) {
        txState.editId = id;
        document.getElementById('txModalTitle').textContent = 'Editar Transacción';
        document.getElementById('txBtnGuardar').innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar cambios';
        try {
            const r = await fetch(`api/transacciones.php?id=${id}`);
            const d = await r.json();
            if (d.success && d.data) _populateModalFromTx(d.data);
            else showToast('No se pudo cargar la transacción', 'error');
        } catch(e) {
            showToast('Error al cargar la transacción', 'error');
        }
    }
}

function _populateModalFromTx(tx) {
    // 1. Tipo
    setTxTipo(tx.tipo);

    // 2. Destinatario (solo ingreso)
    if (tx.tipo === 'ingreso') {
        if (tx.cliente_id && tx.cliente_nombre) {
            txState.destTipo  = 'cliente';
            txState.destId    = parseInt(tx.cliente_id);
            txState.destNombre = tx.cliente_nombre;
            document.getElementById('txDestNombre').textContent = tx.cliente_nombre;
            document.getElementById('txDestInfo').textContent   = 'Cliente';
            document.getElementById('txDestSeleccionado').style.display = 'block';
            document.getElementById('txDestBuscador').querySelector('input').style.display = 'none';
        } else if (tx.lead_id && tx.lead_nombre) {
            const leadBtn = document.querySelector('.tx-dest-tab[data-dest="lead"]');
            if (leadBtn) setTxDest('lead', leadBtn);
            txState.destTipo  = 'lead';
            txState.destId    = parseInt(tx.lead_id);
            txState.destNombre = tx.lead_nombre;
            document.getElementById('txDestNombre').textContent = tx.lead_nombre;
            document.getElementById('txDestInfo').textContent   = 'Lead';
            document.getElementById('txDestSeleccionado').style.display = 'block';
            document.getElementById('txDestBuscador').querySelector('input').style.display = 'none';
        }
    }

    // 3. Proveedor (solo egreso)
    if (tx.tipo === 'egreso' && tx.proveedor) {
        document.getElementById('txProveedor').value           = tx.proveedor;
        document.getElementById('txProvNombre').textContent    = tx.proveedor;
        document.getElementById('txProvInfo').textContent      = 'Proveedor registrado';
        document.getElementById('txProvSeleccionado').style.display = '';
        document.getElementById('txProvInputWrap').style.display    = 'none';
        document.getElementById('txProvCrearBtn').style.display     = 'none';
    }

    // 4. Campos básicos
    const set = (id, val) => { const el = document.getElementById(id); if (el && val != null) el.value = val; };
    set('txTitulo',    tx.titulo);
    set('txConcepto',  tx.concepto);
    set('txMonto',     tx.monto);
    set('txDescuento', tx.descuento || 0);
    set('txFrecuencia', tx.frecuencia || 'unico');
    set('txEstado',    tx.estado || 'pendiente');
    set('txFechaVenc', tx.fecha_vencimiento || '');
    set('txNotas',     tx.descripcion || '');

    // 5. Items del catálogo (si tiene)
    if (tx.items && tx.items.length) {
        txState.items = tx.items.map((i, idx) => ({
            uid:      `edit_${idx}`,
            id:       i.servicio_id || null,
            nombre:   i.nombre || 'Item',
            desc:     '',
            precio:   parseFloat(i.precio_unitario || 0),
            cantidad: parseInt(i.cantidad || 1)
        }));
        renderTxItems();
    }

    recalcularTxTotal();
}

function cerrarModalTx() {
    document.getElementById('modalTx').style.display = 'none';
}

/* ─── PROVEEDOR BUSCADOR ──────────────────────────────────────────────────── */

let txProvResults = [];
let txProvSelectedId = null;

function resetTxProv() {
    txProvResults = [];
    txProvSelectedId = null;
    const inp = document.getElementById('txProvInput');
    if (inp) inp.value = '';
    const dd = document.getElementById('txProvDropdown');
    if (dd) dd.style.display = 'none';
    const sel = document.getElementById('txProvSeleccionado');
    if (sel) sel.style.display = 'none';
    const wrap = document.getElementById('txProvInputWrap');
    if (wrap) wrap.style.display = '';
    const nf = document.getElementById('txProvNuevoForm');
    if (nf) nf.style.display = 'none';
    const cb = document.getElementById('txProvCrearBtn');
    if (cb) cb.style.display = '';
    const hidden = document.getElementById('txProveedor');
    if (hidden) hidden.value = '';
}

async function buscarTxProv() {
    const q = document.getElementById('txProvInput').value.trim();
    const dd = document.getElementById('txProvDropdown');
    if (!q) { dd.style.display = 'none'; return; }

    try {
        const r = await fetch('api/proveedores.php?q=' + encodeURIComponent(q));
        const d = await r.json();
        txProvResults = d.success ? d.data : [];
    } catch(e) { txProvResults = []; }

    if (!txProvResults.length) {
        dd.innerHTML = `<div style="padding:10px 14px;font-size:12px;color:#94a3b8;text-align:center">
            Sin resultados — <button onclick="mostrarNuevoProv()" style="font-size:12px;font-weight:700;color:#0f172a;background:none;border:none;cursor:pointer;text-decoration:underline">crear "${escapeHtml(document.getElementById('txProvInput').value)}"</button>
        </div>`;
    } else {
        dd.innerHTML = txProvResults.map((p, i) => `
            <div onclick="seleccionarProv(${i})" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .1s"
                onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background=''">
                <div style="font-size:13px;font-weight:700;color:#0f172a">${escapeHtml(p.nombre)}</div>
                ${p.nit ? `<div style="font-size:11px;color:#94a3b8">NIT ${escapeHtml(p.nit)}</div>` : ''}
            </div>`).join('');
    }
    dd.style.display = '';
}

function seleccionarProv(idx) {
    const p = txProvResults[idx];
    if (!p) return;
    txProvSelectedId = p.id;
    document.getElementById('txProveedor').value = p.nombre;
    document.getElementById('txProvNombre').textContent = p.nombre;
    document.getElementById('txProvInfo').textContent = [p.nit, p.ciudad, p.telefono].filter(Boolean).join(' · ') || 'Proveedor registrado';
    document.getElementById('txProvSeleccionado').style.display = '';
    document.getElementById('txProvInputWrap').style.display = 'none';
    document.getElementById('txProvDropdown').style.display = 'none';
    document.getElementById('txProvCrearBtn').style.display = 'none';
}

function limpiarTxProv() {
    txProvSelectedId = null;
    document.getElementById('txProveedor').value = '';
    document.getElementById('txProvSeleccionado').style.display = 'none';
    document.getElementById('txProvInputWrap').style.display = '';
    document.getElementById('txProvInput').value = '';
    document.getElementById('txProvCrearBtn').style.display = '';
}

function mostrarNuevoProv() {
    const q = document.getElementById('txProvInput')?.value || '';
    document.getElementById('txProvNuevoNombre').value   = q;
    document.getElementById('txProvNuevoNit').value      = '';
    document.getElementById('txProvNuevoTel').value      = '';
    document.getElementById('txProvNuevoCiudad').value   = '';
    document.getElementById('txProvNuevoDireccion').value = '';
    document.getElementById('txProvNuevoForm').style.display = '';
    document.getElementById('txProvCrearBtn').style.display = 'none';
    document.getElementById('txProvDropdown').style.display = 'none';
}

function cancelarNuevoProv() {
    document.getElementById('txProvNuevoForm').style.display = 'none';
    document.getElementById('txProvCrearBtn').style.display = '';
}

async function guardarNuevoProv() {
    const nombre = document.getElementById('txProvNuevoNombre').value.trim();
    if (!nombre) { showToast('Ingresa el nombre del proveedor', 'error'); return; }
    try {
        const r = await fetch('api/proveedores.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre,
                nit:       document.getElementById('txProvNuevoNit').value.trim()      || null,
                telefono:  document.getElementById('txProvNuevoTel').value.trim()      || null,
                ciudad:    document.getElementById('txProvNuevoCiudad').value.trim()   || null,
                direccion: document.getElementById('txProvNuevoDireccion').value.trim() || null
            })
        });
        const d = await r.json();
        if (d.success || d.id) {
            txProvSelectedId = d.id;
            document.getElementById('txProveedor').value = nombre;
            document.getElementById('txProvNombre').textContent = nombre;
            document.getElementById('txProvInfo').textContent = 'Proveedor recién creado';
            document.getElementById('txProvSeleccionado').style.display = '';
            document.getElementById('txProvNuevoForm').style.display = 'none';
            document.getElementById('txProvInputWrap').style.display = 'none';
            document.getElementById('txProvCrearBtn').style.display = 'none';
            showToast('Proveedor creado', 'success');
        } else {
            showToast(d.error || 'Error al crear proveedor', 'error');
        }
    } catch(e) { showToast('Error al guardar proveedor', 'error'); }
}

/* ─── ZONA ARCHIVOS ───────────────────────────────────────────────────────── */

let txArchivosSeleccionados = [];

function resetTxArchivos() {
    txArchivosSeleccionados = [];
    const inp = document.getElementById('txArchivos');
    if (inp) inp.value = '';
    renderTxArchivos();
}

function onTxFileChange(input) {
    const files = Array.from(input.files);
    files.forEach(f => {
        if (!txArchivosSeleccionados.find(x => x.name === f.name && x.size === f.size)) {
            txArchivosSeleccionados.push(f);
        }
    });
    input.value = ''; // permite volver a seleccionar el mismo archivo
    renderTxArchivos();
}

function onTxFileDrop(e) {
    e.preventDefault();
    const zone = document.getElementById('txDropZone');
    if (zone) { zone.style.borderColor = '#cbd5e1'; zone.style.background = '#f8fafc'; }
    const files = Array.from(e.dataTransfer.files);
    files.forEach(f => {
        if (!txArchivosSeleccionados.find(x => x.name === f.name && x.size === f.size)) {
            txArchivosSeleccionados.push(f);
        }
    });
    renderTxArchivos();
}

function renderTxArchivos() {
    const lista = document.getElementById('txArchivosLista');
    if (!lista) return;
    if (!txArchivosSeleccionados.length) { lista.style.display = 'none'; lista.innerHTML = ''; return; }
    lista.style.display = 'flex';
    lista.innerHTML = txArchivosSeleccionados.map((f, i) => {
        const ext  = f.name.split('.').pop().toLowerCase();
        const icon = ['pdf'].includes(ext) ? '📄' : ['jpg','jpeg','png','gif','webp'].includes(ext) ? '🖼️' : '📎';
        const size = f.size > 1024*1024 ? (f.size/1024/1024).toFixed(1)+'MB' : Math.round(f.size/1024)+'KB';
        return `<div style="display:flex;align-items:center;gap:8px;padding:7px 10px;background:#fff;border:1.5px solid #e2e8f0;border-radius:7px">
            <span style="font-size:14px">${icon}</span>
            <div style="flex:1;min-width:0">
                <div style="font-size:12px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(f.name)}</div>
                <div style="font-size:10px;color:#94a3b8">${size}</div>
            </div>
            <button onclick="quitarTxArchivo(${i})" style="background:none;border:none;cursor:pointer;color:#cbd5e1;padding:2px;border-radius:4px;transition:color .1s;flex-shrink:0" onmouseenter="this.style.color='#ef4444'" onmouseleave="this.style.color='#cbd5e1'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>`;
    }).join('');
}

function quitarTxArchivo(idx) {
    txArchivosSeleccionados.splice(idx, 1);
    renderTxArchivos();
}

// ── Tipo ──────────────────────────────────────────────────────────

function setTxTipo(tipo) {
    txState.tipo = tipo;
    const ing = document.getElementById('txTipoIngreso');
    const egr = document.getElementById('txTipoEgreso');
    if (!ing || !egr) return;

    if (tipo === 'ingreso') {
        ing.style.background = '#0E0E0C'; ing.style.color = '#C6F24E';
        egr.style.background = '#fff';    egr.style.color = '#8A867C';
        // Mostrar secciones de ingreso
        document.getElementById('txDestSection').style.display = '';
        document.getElementById('txEgresoSection').style.display = 'none';
        document.getElementById('txCatalogoSection').style.display = '';
        document.getElementById('txTituloSection').style.display = '';
        document.getElementById('txArchivosSection').style.display = '';
    } else {
        egr.style.background = '#0E0E0C'; egr.style.color = '#C6F24E';
        ing.style.background = '#fff';    ing.style.color = '#8A867C';
        // Ocultar destinatario, catálogo, título y archivos — egresos son gastos simples
        document.getElementById('txDestSection').style.display = 'none';
        document.getElementById('txEgresoSection').style.display = '';
        document.getElementById('txCatalogoSection').style.display = 'none';
        document.getElementById('txTituloSection').style.display = 'none';
        document.getElementById('txArchivosSection').style.display = '';
        // Limpiar estado de destinatario
        txState.destId = null;
        txState.destNombre = '';
        txState.items = [];
        renderTxItems();
    }
}

// ── Destinatario ──────────────────────────────────────────────────

function setTxDest(tipo, btn) {
    txState.destTipo = tipo;
    txState.destId   = null;
    txState.destNombre = '';

    document.querySelectorAll('.tx-dest-tab').forEach(b => {
        b.style.borderBottomColor = 'transparent';
        b.style.color = '#94a3b8';
    });
    btn.style.borderBottomColor = '#0f172a';
    btn.style.color = '#0f172a';

    const buscador   = document.getElementById('txDestBuscador');
    const nuevoForm  = document.getElementById('txDestNuevoForm');
    const sel        = document.getElementById('txDestSeleccionado');

    if (tipo === 'nuevo') {
        buscador.style.display  = 'none';
        nuevoForm.style.display = '';
        sel.style.cssText += ';display:none!important';
    } else {
        buscador.style.display  = '';
        nuevoForm.style.display = 'none';
        sel.style.cssText += ';display:none!important';
        const inp = document.getElementById('txDestInput');
        inp.value = '';
        inp.placeholder = tipo === 'cliente'
            ? 'Buscar cliente por nombre o email...'
            : 'Buscar lead por nombre, email o WhatsApp...';
        document.getElementById('txDestDropdown').style.display = 'none';
    }
}

function buscarTxDest() {
    clearTimeout(txSearchTimer);
    txSearchTimer = setTimeout(async () => {
        const q  = document.getElementById('txDestInput').value.trim();
        const dd = document.getElementById('txDestDropdown');
        if (!q || q.length < 2) { dd.style.display = 'none'; return; }

        try {
            const r = await fetch('api/cotizaciones.php?q=' + encodeURIComponent(q) + '&solo=' + txState.destTipo);
            const d = await r.json();
            txDestResults = (d.success && d.data) ? d.data : [];

            if (!txDestResults.length) {
                dd.innerHTML = '<div style="padding:12px 16px;font-size:12px;color:#94a3b8;text-align:center">Sin resultados</div>';
                dd.style.display = '';
                return;
            }

            dd.innerHTML = txDestResults.map((item, idx) =>
                '<div data-idx="' + idx + '" class="tx-dest-option" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .1s" onmouseenter="this.style.background=\'#f8fafc\'" onmouseleave="this.style.background=\'\'">'
                + '<div style="font-size:13px;font-weight:700;color:#0f172a">' + escapeHtml(item.nombre) + '</div>'
                + '<div style="font-size:11px;color:#94a3b8;margin-top:2px">' + escapeHtml(item.email || item.whatsapp || '—') + '</div>'
                + '</div>'
            ).join('');

            // Delegación de eventos
            dd.querySelectorAll('.tx-dest-option').forEach(el => {
                el.addEventListener('click', function() {
                    const idx = parseInt(this.getAttribute('data-idx'));
                    const item = txDestResults[idx];
                    if (item) seleccionarTxDest(item);
                });
            });

            dd.style.display = '';
        } catch(e) { dd.style.display = 'none'; }
    }, 280);
}

function seleccionarTxDest(item) {
    txState.destId    = item.id;
    txState.destNombre = item.nombre;

    document.getElementById('txDestInput').value = '';
    document.getElementById('txDestDropdown').style.display = 'none';
    document.getElementById('txDestNombre').textContent = item.nombre;
    document.getElementById('txDestInfo').textContent   = item.email || item.whatsapp || '';

    const sel = document.getElementById('txDestSeleccionado');
    sel.style.display = 'block';

    if (!document.getElementById('txConcepto').value) {
        document.getElementById('txConcepto').value = 'Servicio a ' + item.nombre;
    }
}

function limpiarTxDest() {
    txState.destId     = null;
    txState.destNombre = '';
    const sel = document.getElementById('txDestSeleccionado');
    sel.style.cssText += ';display:none!important';
    const inp = document.getElementById('txDestInput');
    inp.value = '';
    inp.focus();
}

// ── Catálogo ──────────────────────────────────────────────────────

function setTxCatTab(tab, btn) {
    txState.catTab = tab;
    document.querySelectorAll('.tx-cat-tab').forEach(b => {
        b.style.borderBottomColor = 'transparent';
        b.style.color = '#94a3b8';
    });
    btn.style.borderBottomColor = '#0f172a';
    btn.style.color = '#0f172a';
    document.getElementById('txCatSearch').value = '';
    cargarTxCatalogo(tab);
}

async function cargarTxCatalogo(tab) {
    const panel = document.getElementById('txCatPanel');

    if (txState.catalogCache[tab]) {
        renderTxCatalogo(txState.catalogCache[tab], tab);
        return;
    }

    panel.innerHTML = '<div style="text-align:center;padding:24px;color:#94a3b8;font-size:12px">Cargando catálogo...</div>';

    try {
        let items = [];
        if (tab === 'subservicios') {
            const r = await fetch('api/servicios.php?activo=1');
            const d = await r.json();
            if (d.success && d.data) {
                d.data.forEach(svc => {
                    const subs = svc.sub_servicios || [];
                    if (subs.length) {
                        subs.forEach(ss => items.push({
                            uid: 'ss_' + ss.id,
                            id: ss.id,
                            nombre: ss.nombre,
                            desc: svc.nombre,
                            precio: parseFloat(ss.precio) || 0
                        }));
                    } else {
                        items.push({
                            uid: 'svc_' + svc.id,
                            id: svc.id,
                            nombre: svc.nombre,
                            desc: 'Servicio',
                            precio: parseFloat(svc.precio_base) || 0
                        });
                    }
                });
            }
        } else {
            const r = await fetch('api/paquetes.php');
            const d = await r.json();
            if (d.success && d.data) {
                d.data.forEach(p => items.push({
                    uid: 'pkg_' + p.id,
                    id: p.id,
                    nombre: p.nombre,
                    desc: p.descripcion || '',
                    precio: parseFloat(p.precio_venta || p.precio || 0)
                }));
            }
        }
        txState.catalogCache[tab] = items;
        renderTxCatalogo(items, tab);
    } catch(e) {
        panel.innerHTML = '<div style="text-align:center;padding:24px;color:#ef4444;font-size:12px">Error al cargar catálogo</div>';
    }
}

// Cache del catálogo renderizado para filtrado sin re-fetch
function filtrarTxCatalogo() {
    const cache = txState.catalogCache[txState.catTab];
    if (cache) renderTxCatalogo(cache, txState.catTab);
}

function renderTxCatalogo(items, tab) {
    const q = (document.getElementById('txCatSearch').value || '').toLowerCase().trim();
    const filtered = q
        ? items.filter(i => i.nombre.toLowerCase().includes(q) || (i.desc && i.desc.toLowerCase().includes(q)))
        : items;

    const panel = document.getElementById('txCatPanel');

    if (!filtered.length) {
        panel.innerHTML = '<div style="text-align:center;padding:24px;color:#94a3b8;font-size:12px;font-style:italic">Sin resultados en el catálogo</div>';
        return;
    }

    panel.innerHTML = filtered.map((item, idx) =>
        '<div class="tx-cat-item" data-uid="' + item.uid + '" data-tab="' + tab + '" style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;transition:all .15s;background:#fff" onmouseenter="this.style.borderColor=\'#0f172a\';this.style.background=\'#f8fafc\'" onmouseleave="this.style.borderColor=\'#e2e8f0\';this.style.background=\'#fff\'">'
        + '<div style="flex:1;min-width:0">'
        +   '<div style="font-size:12px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escapeHtml(item.nombre) + '</div>'
        +   (item.desc ? '<div style="font-size:11px;color:#94a3b8;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escapeHtml(item.desc) + '</div>' : '')
        + '</div>'
        + '<div style="text-align:right;margin-left:12px;flex-shrink:0;display:flex;align-items:center;gap:8px">'
        +   '<span style="font-size:12px;font-weight:800;color:#10b981">' + formatMoney(item.precio) + '</span>'
        +   '<button class="tx-add-btn" style="padding:3px 8px;background:#0E0E0C;color:#C6F24E;border:none;border-radius:3px;font-size:10px;font-weight:700;cursor:pointer">+ Añadir</button>'
        + '</div>'
        + '</div>'
    ).join('');

    // Delegación de eventos para añadir items
    panel.querySelectorAll('.tx-cat-item').forEach(el => {
        el.addEventListener('click', function() {
            const uid  = this.getAttribute('data-uid');
            const currTab = this.getAttribute('data-tab');
            const cache = txState.catalogCache[currTab];
            if (cache) {
                const item = cache.find(i => i.uid === uid);
                if (item) txAddItem(item);
            }
        });
    });
}

// ── Items seleccionados ───────────────────────────────────────────

function txAddItem(item) {
    // Evitar duplicados del mismo item
    const exists = txState.items.find(i => i.uid === item.uid);
    if (exists) {
        exists.cantidad++;
    } else {
        txState.items.push({ uid: item.uid, id: item.id, nombre: item.nombre, desc: item.desc, precio: item.precio, cantidad: 1 });
    }
    renderTxItems();
    _syncTxMontoFromItems();
    if (!document.getElementById('txConcepto').value) {
        document.getElementById('txConcepto').value = item.nombre;
    }
}

function txRemoveItem(uid) {
    txState.items = txState.items.filter(i => i.uid !== uid);
    renderTxItems();
    _syncTxMontoFromItems();
}

function txUpdateCantidad(uid, val) {
    const item = txState.items.find(i => i.uid === uid);
    if (item) {
        item.cantidad = Math.max(1, parseInt(val) || 1);
        renderTxItems();
        _syncTxMontoFromItems();
    }
}

function _syncTxMontoFromItems() {
    const total = txState.items.reduce((s, i) => s + i.precio * i.cantidad, 0);
    if (txState.items.length) document.getElementById('txMonto').value = Math.round(total);
    else document.getElementById('txMonto').value = '';
    recalcularTxTotal();
}

function renderTxItems() {
    const container = document.getElementById('txItemsContainer');
    const tbody = document.getElementById('txItemsTbody');

    if (!txState.items.length) {
        container.style.display = 'none';
        return;
    }
    container.style.display = '';

    tbody.innerHTML = txState.items.map(item =>
        '<tr style="border-bottom:1px solid #f1f5f9" data-uid="' + item.uid + '">'
        + '<td style="padding:8px 12px">'
        +   '<div style="font-size:12px;font-weight:700;color:#0f172a">' + escapeHtml(item.nombre) + '</div>'
        +   (item.desc ? '<div style="font-size:11px;color:#94a3b8">' + escapeHtml(item.desc) + '</div>' : '')
        + '</td>'
        + '<td style="padding:8px;text-align:center">'
        +   '<input type="number" value="' + item.cantidad + '" min="1" class="tx-qty-input" style="width:48px;padding:4px 6px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;text-align:center;font-family:inherit;outline:none">'
        + '</td>'
        + '<td style="padding:8px;text-align:right;font-size:12px;color:#64748b">' + formatMoney(item.precio) + '</td>'
        + '<td style="padding:8px;text-align:right;font-size:12px;font-weight:800;color:#0f172a">' + formatMoney(item.precio * item.cantidad) + '</td>'
        + '<td style="padding:8px;text-align:center"><button class="tx-rm-btn" style="background:none;border:none;cursor:pointer;padding:2px 6px;border-radius:4px;color:#ef4444;font-size:16px;line-height:1;font-weight:700" onmouseenter="this.style.background=\'#fef2f2\'" onmouseleave="this.style.background=\'none\'">×</button></td>'
        + '</tr>'
    ).join('');

    // Eventos cantidad y eliminar (delegación)
    tbody.querySelectorAll('.tx-qty-input').forEach(inp => {
        inp.addEventListener('change', function() {
            const uid = this.closest('tr').getAttribute('data-uid');
            txUpdateCantidad(uid, this.value);
        });
    });
    tbody.querySelectorAll('.tx-rm-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const uid = this.closest('tr').getAttribute('data-uid');
            txRemoveItem(uid);
        });
    });
}

const TX_FREQ_LABELS = { unico: 'Pago único', mensual: 'Mensual', trimestral: 'Trimestral', semestral: 'Semestral', anual: 'Anual' };

function recalcularTxTotal() {
    const itemsTotal  = txState.items.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const manualMonto = parseFloat(document.getElementById('txMonto').value) || 0;
    const subtotal    = txState.items.length ? itemsTotal : manualMonto;
    const descMonto   = Math.max(0, parseFloat(document.getElementById('txDescuento').value) || 0);
    const total       = subtotal - descMonto;
    const frecuencia  = document.getElementById('txFrecuencia').value;

    // Subtotal row — mostrar solo si hay items del catálogo
    const subtotalRow = document.getElementById('txSubtotalRow');
    if (txState.items.length) {
        subtotalRow.style.display = 'flex';
        document.getElementById('txSubtotalLabel').textContent = formatMoney(subtotal);
    } else {
        subtotalRow.style.display = 'none';
    }

    // Descuento row
    const descRow = document.getElementById('txDescuentoRow');
    if (descMonto > 0) {
        descRow.style.display = 'flex';
        document.getElementById('txDescuentoLabel').textContent = '-' + formatMoney(descMonto);
    } else {
        descRow.style.display = 'none';
    }

    // Frecuencia badge
    const freqLabel = document.getElementById('txFrecuenciaLabel');
    if (frecuencia && frecuencia !== 'unico') {
        freqLabel.textContent = TX_FREQ_LABELS[frecuencia] || frecuencia;
        freqLabel.style.display = '';
    } else {
        freqLabel.style.display = 'none';
    }

    document.getElementById('txTotalLabel').textContent = formatMoney(total);
}

function syncTxMonto() {
    recalcularTxTotal();
}

// ── Guardar ───────────────────────────────────────────────────────

async function guardarTransaccion() {
    const concepto = document.getElementById('txConcepto').value.trim();
    const monto    = parseFloat(document.getElementById('txMonto').value);
    const titulo   = txState.tipo === 'ingreso' ? document.getElementById('txTitulo').value.trim() : null;

    // Validaciones
    if (txState.tipo === 'ingreso' && !titulo) {
        _txFieldError('txTitulo');
        showToast('El título del ingreso es requerido', 'error');
        return;
    }
    if (!concepto) {
        _txFieldError('txConcepto');
        showToast('El concepto es requerido', 'error');
        return;
    }
    if (!monto || monto <= 0) {
        _txFieldError('txMonto');
        showToast('El monto debe ser mayor a cero', 'error');
        return;
    }

    const btn = document.getElementById('txBtnGuardar');
    const _btnReset = () => {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar transacción';
    };
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg> Guardando...';

    try {
        // 0. Upload de archivos (zona multi-archivo)
        let filePaths = {};
        if (txArchivosSeleccionados.length) {
            try {
                const formData = new FormData();
                txArchivosSeleccionados.forEach((f, i) => formData.append(`archivo_${i}`, f));
                const rUpload = await fetch('api/upload_transaccion.php', { method: 'POST', body: formData });
                const dUpload = await rUpload.json();
                if (dUpload.success && dUpload.data) filePaths = dUpload.data;
            } catch(e) { /* upload falla silenciosamente */ }
        }
        let leadId     = null;
        let clienteId  = null;

        // 1. Nuevo contacto → crear lead
        if (txState.destTipo === 'nuevo') {
            const nombre = document.getElementById('txNuevoNombre').value.trim();
            if (!nombre) {
                _txFieldError('txNuevoNombre');
                showToast('El nombre del contacto es requerido', 'error');
                _btnReset();
                return;
            }
            const rLead = await fetch('api/leads.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nombre:           nombre,
                    email:            document.getElementById('txNuevoEmail').value.trim() || null,
                    whatsapp:         document.getElementById('txNuevoWA').value.trim()    || null,
                    servicio_interes: 'Sin especificar',
                    fuente:           'transaccion',
                    estado:           'nuevo'
                })
            });
            const dLead = await rLead.json();
            if (!dLead.success) throw new Error(dLead.error || 'Error al crear el lead');
            leadId = dLead.data?.id || dLead.id || null;

        } else if (txState.destTipo === 'lead' && txState.destId) {
            leadId = txState.destId;
        } else if (txState.destTipo === 'cliente' && txState.destId) {
            clienteId = txState.destId;
        }

        // 2. Armar items para la API
        const items = txState.items.map(i => ({
            servicio_id:     i.uid.startsWith('ss_') ? i.id : null,
            nombre:          i.nombre,
            precio_unitario: i.precio,
            cantidad:        i.cantidad
        }));

        // 2. Calcular monto final con descuento (en pesos)
        const descMonto  = Math.max(0, parseFloat(document.getElementById('txDescuento').value) || 0);
        const montoFinal = monto - descMonto;

        // 3. Armar payload
        const payload = {
            tipo:              txState.tipo,
            monto:             montoFinal,
            concepto:          concepto,
            descripcion:       document.getElementById('txNotas').value.trim() || null,
            fecha_vencimiento: document.getElementById('txFechaVenc').value    || null,
            estado:            document.getElementById('txEstado').value,
            lead_id:           leadId,
            cliente_id:        clienteId,
            servicio_id:       (items.length && items[0].servicio_id) ? items[0].servicio_id : null,
            frecuencia:        document.getElementById('txFrecuencia').value,
            descuento:         descMonto,
            proveedor:         txState.tipo === 'egreso' ? (document.getElementById('txProveedor').value.trim() || null) : null,
            titulo:            titulo,
            factura_path:      filePaths.factura_path || null,
            documento_path:    filePaths.documento_path || null,
            imagen_path:       filePaths.imagen_path || null,
            items:             items
        };

        // 4. POST (crear) o PUT (editar)
        let r, d;
        if (txState.editId) {
            payload.id = txState.editId;
            r = await fetch('api/transacciones.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            d = await r.json();
            if (!d.success) throw new Error(d.error || 'Error al actualizar la transacción');
            showToast('✓ Transacción actualizada', 'success');
        } else {
            r = await fetch('api/transacciones.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            d = await r.json();
            if (!d.success) throw new Error(d.error || 'Error al registrar la transacción');
            showToast('✓ Transacción registrada', 'success');
        }

        cerrarModalTx();
        loadAll(); // Refrescar KPIs + tabla

    } catch(e) {
        showToast(e.message || 'Error al guardar la transacción', 'error');
    } finally {
        _btnReset();
    }
}

function _txFieldError(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.borderColor = '#ef4444';
    el.focus();
    setTimeout(() => { el.style.borderColor = '#e2e8f0'; }, 2500);
}

/* ─────────────────────────────────────────────────────────────────────────── */

function descargarInforme() {
    const desde = fnPeriodo.desde || document.getElementById('fnDesde')?.value || '';
    const hasta  = fnPeriodo.hasta  || document.getElementById('fnHasta')?.value  || '';
    let periodoLabel = 'Período Financiero';
    if (desde && hasta) {
        periodoLabel = 'Del ' + new Date(desde + 'T12:00:00').toLocaleDateString('es-CO') + ' al ' + new Date(hasta + 'T12:00:00').toLocaleDateString('es-CO');
    }

    // Extraer KPIs del nuevo HTML de fnKpis
    const cards = document.querySelectorAll('#fnKpis > div');
    const kpiRows = [...cards].map(c => {
        const divs = c.querySelectorAll('div, span');
        const label = c.querySelector('div:first-child')?.textContent?.trim() || '';
        const valueEl = c.querySelector('span[style*="font-size:26px"]') || c.querySelector('span');
        const value = valueEl?.textContent?.trim() || '';
        const footerEl = [...c.querySelectorAll('div')].find(el => el.style.fontSize === '11px');
        const footer = footerEl?.textContent?.trim() || '';
        return `<tr><td style="padding:8px 16px;font-weight:700;color:#0f172a">${label}</td><td style="padding:8px 16px;text-align:right;font-weight:900;font-size:15px;color:#0f172a">${value} COP</td><td style="padding:8px 16px;color:#64748b;font-size:12px">${footer}</td></tr>`;
    }).join('');

    // Extraer tabla de clientes
    const tablaClientes = document.querySelector('#clientesDetalle table');
    const clientesHtml = tablaClientes ? tablaClientes.outerHTML : '<p>Sin datos de clientes</p>';

    // Extraer top servicios
    const topSvcs = document.querySelector('#clientesDetalle > div:last-child > div:last-child > div:last-child');
    const topSvcsHtml = topSvcs ? topSvcs.innerHTML : '';

    const now = new Date().toLocaleDateString('es-CO',{day:'2-digit',month:'long',year:'numeric'});

    const html = `<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Informe Financiero — QUANTUN Digital</title>
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',Arial,sans-serif;color:#0f172a;background:#fff;padding:40px}
    h1{font-size:24px;font-weight:900;margin-bottom:4px}
    .sub{font-size:13px;color:#64748b;margin-bottom:32px}
    h2{font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin:28px 0 10px}
    table{width:100%;border-collapse:collapse;font-size:13px}
    th{padding:8px 16px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;background:#f8fafc;border-bottom:1px solid #e2e8f0}
    td{border-bottom:1px solid #f1f5f9}
    tfoot td{background:#f8fafc;font-weight:800;border-top:2px solid #e2e8f0}
    .kpi-table td:nth-child(2){font-size:18px;font-weight:900}
    .footer{margin-top:40px;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:16px}
    @media print{body{padding:20px}}
</style></head><body>
<h1>Informe Financiero — QUANTUN Digital</h1>
<p class="sub">${periodoLabel} &nbsp;·&nbsp; Generado el ${now}</p>

<h2>Resumen KPIs</h2>
<table class="kpi-table" style="margin-bottom:8px">
    <thead><tr><th>Indicador</th><th style="text-align:right">Valor</th><th>Detalle</th></tr></thead>
    <tbody>${kpiRows}</tbody>
</table>

<h2>Desglose por Cliente</h2>
${clientesHtml}

${topSvcsHtml ? `<h2>Top Servicios</h2><div style="font-size:13px">${topSvcsHtml}</div>` : ''}

<div class="footer">CRM QUANTUN Digital &nbsp;·&nbsp; Informe generado automáticamente</div>
</body></html>`;

    // Incluir tabla de movimientos del período actual
    const movRows = fnTxDataFull.map(tx => {
        const fechaRefM = tx.fecha_pago || tx.fecha_vencimiento || (tx.created_at ? tx.created_at.split(' ')[0] : null);
        const fecha = fechaRefM
            ? new Date(fechaRefM + 'T12:00:00').toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'})
            : '—';
        const sign  = tx.tipo === 'egreso' ? '−' : '+';
        const color = tx.tipo === 'egreso' ? '#dc2626' : '#16a34a';
        const dest  = tx.cliente_nombre || tx.lead_nombre || tx.proveedor || '—';
        return `<tr>
            <td style="padding:7px 12px;color:#64748b;white-space:nowrap">${fecha}</td>
            <td style="padding:7px 12px;font-weight:600">${tx.titulo || tx.concepto || '—'}</td>
            <td style="padding:7px 12px;color:#64748b">${dest}</td>
            <td style="padding:7px 12px;text-align:center">
                <span style="font-size:10px;font-weight:700;background:${tx.tipo==='ingreso'?'#dcfce7':'#fee2e2'};color:${color};padding:2px 8px;border-radius:20px">${tx.tipo==='ingreso'?'Ingreso':'Egreso'}</span>
            </td>
            <td style="padding:7px 12px;text-align:right;font-weight:800;color:${color}">${sign} ${new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',minimumFractionDigits:0}).format(tx.monto)}</td>
            <td style="padding:7px 12px;color:#64748b;font-size:11px">${tx.estado}</td>
        </tr>`;
    }).join('');

    const movTotal = fnTxDataFull.filter(t=>t.tipo==='ingreso').reduce((s,t)=>s+parseFloat(t.monto||0),0)
                   - fnTxDataFull.filter(t=>t.tipo==='egreso').reduce((s,t)=>s+parseFloat(t.monto||0),0);

    const movHtml = fnTxDataFull.length ? `
        <table style="margin-top:8px">
            <thead><tr>
                <th>Fecha</th><th>Descripción</th><th>Cliente / Proveedor</th><th style="text-align:center">Tipo</th>
                <th style="text-align:right">Monto</th><th>Estado</th>
            </tr></thead>
            <tbody>${movRows}</tbody>
            <tfoot><tr>
                <td colspan="4" style="padding:8px 12px;font-weight:800">BALANCE PERÍODO</td>
                <td style="padding:8px 12px;text-align:right;font-size:16px;font-weight:900;color:${movTotal>=0?'#15803d':'#dc2626'}">${movTotal>=0?'+':''}${new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',minimumFractionDigits:0}).format(movTotal)}</td>
                <td></td>
            </tr></tfoot>
        </table>` : '<p style="color:#94a3b8;padding:16px 0">Sin movimientos en este período</p>';

    const fullHtml = `<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Informe Financiero — QUANTUN Digital</title>
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',Arial,sans-serif;color:#0f172a;background:#fff;padding:40px}
    h1{font-size:24px;font-weight:900;margin-bottom:4px}
    .sub{font-size:13px;color:#64748b;margin-bottom:32px}
    h2{font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin:28px 0 10px;padding-top:20px;border-top:1px solid #f1f5f9}
    h2:first-of-type{border-top:none;padding-top:0}
    table{width:100%;border-collapse:collapse;font-size:13px}
    th{padding:8px 12px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;background:#f8fafc;border-bottom:1px solid #e2e8f0}
    td{border-bottom:1px solid #f1f5f9}
    tfoot td{background:#f8fafc;font-weight:800;border-top:2px solid #e2e8f0}
    .footer{margin-top:40px;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:16px;text-align:center}
    @media print{body{padding:20px}button{display:none}}
</style></head><body>
<h1>Informe Financiero — QUANTUN Digital</h1>
<p class="sub">${periodoLabel} &nbsp;·&nbsp; Generado el ${now}</p>

<h2>Resumen del Período</h2>
<table style="margin-bottom:8px">
    <thead><tr><th>Indicador</th><th style="text-align:right">Valor (COP)</th><th>Detalle</th></tr></thead>
    <tbody>${kpiRows}</tbody>
</table>

<h2>Movimientos del Período</h2>
${movHtml}

<h2>Desglose por Cliente</h2>
${clientesHtml}

${topSvcsHtml ? `<h2>Top Servicios</h2><div style="font-size:13px">${topSvcsHtml}</div>` : ''}

<div class="footer">CRM QUANTUN Digital &nbsp;·&nbsp; Informe generado automáticamente</div>
</body></html>`;

    const blob = new Blob([fullHtml], {type:'text/html;charset=utf-8'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    const slug = (desde && hasta) ? `${desde}_${hasta}` : new Date().toISOString().substring(0,10);
    a.download = `informe-financiero-${slug}.html`;
    a.click();
    URL.revokeObjectURL(a.href);
    showToast('Informe descargado', 'success');
}

/* ─── DETALLE MOVIMIENTO (ojito) ──────────────────────────────────────────── */

let _cmpTxActual = null;

async function verComprobante(id) {
    document.getElementById('cmpPreview').innerHTML = '<div style="text-align:center;padding:40px;color:#8A867C;font-size:13px">Cargando...</div>';
    document.getElementById('cmpFooter').innerHTML  = '<button onclick="cerrarComprobante()" style="padding:9px 18px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-weight:600;color:#57544D;background:#FFFFFF;cursor:pointer" onmouseenter="this.style.background=\'#FAFAF7\'" onmouseleave="this.style.background=\'#FFFFFF\'">Cerrar</button>';
    document.getElementById('modalComprobante').style.display = 'flex';
    try {
        const r = await fetch(`api/transacciones.php?id=${id}`);
        const d = await r.json();
        if (!d.success || !d.data) throw new Error('No encontrado');
        _cmpTxActual = d.data;
        renderComprobante();
    } catch(e) {
        document.getElementById('cmpPreview').innerHTML = '<div style="text-align:center;padding:40px;color:#ef4444;font-size:13px">Error al cargar el movimiento</div>';
    }
}

/* Reemplaza el nombre viejo de servicio en el concepto por el actual del catálogo */
function _fixConcepto(tx) {
    let c = tx.concepto || '';
    if (tx.servicio_nombre && tx.servicio_id && c) {
        const i = c.search(/\s*[–—\-]\s/);
        if (i > 0) c = c.substring(0, i) + ' – ' + tx.servicio_nombre;
    }
    return c;
}

function renderComprobante() {
    if (!_cmpTxActual) return;
    const tx = _cmpTxActual;

    const fmt = s => {
        if (!s) return null;
        // Normalizar: "2026-05-22 14:30:00" → "2026-05-22T14:30:00", "2026-05-22" → "2026-05-22T12:00:00"
        const iso = s.includes('T') ? s : s.includes(' ') ? s.replace(' ', 'T') : s + 'T12:00:00';
        const d = new Date(iso);
        if (isNaN(d)) return s; // fallback: mostrar string original
        return d.toLocaleDateString('es-CO', { day:'2-digit', month:'long', year:'numeric' });
    };
    const fmtMoney = v => '$ ' + Number(v || 0).toLocaleString('es-CO');

    const esIngreso  = tx.tipo === 'ingreso';
    const tipoBg     = esIngreso ? '#E3F1E8' : '#F4DEDB';
    const tipoColor  = esIngreso ? '#1B5A39' : '#6E211B';
    const tipoLabel  = esIngreso ? '↑ Ingreso' : '↓ Egreso';
    const estadoMap  = { pagado:['#E3F1E8','#1B5A39','✓ Pagado'], pendiente:['#F5EBD3','#6E4A12','◷ Pendiente'], vencido:['#F4DEDB','#6E211B','⚠ Vencido'] };
    const [eBg, eColor, eLabel] = estadoMap[tx.estado] || ['#F3F2EE','#57544D', tx.estado];

    const nombre = tx.cliente_nombre || tx.lead_nombre || tx.proveedor || null;
    const clienteId = tx.cliente_id || null;

    // Header del modal
    document.getElementById('cmpTitle').textContent   = tx.titulo || tx.concepto || 'Movimiento';
    document.getElementById('cmpSubtitle').textContent = `TRX-${String(tx.id).padStart(4,'0')} · ${nombre || 'Sin asignar'}`;

    const campo = (iconPath, label, value, extra='') => !value ? '' : `
        <div style="display:flex;align-items:flex-start;gap:11px;padding:11px 0;border-bottom:1px solid #F3F2EE">
            <div style="flex-shrink:0;width:32px;height:32px;background:#F3F2EE;border-radius:8px;display:flex;align-items:center;justify-content:center">
                <svg width="15" height="15" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="${iconPath}"/></svg>
            </div>
            <div>
                <div style="font-size:10px;color:#8A867C;margin-bottom:2px;font-weight:600;text-transform:uppercase;letter-spacing:.05em">${label}</div>
                <div style="font-size:13px;font-weight:600;color:#0E0E0C;word-break:break-word">${value}</div>
                ${extra ? `<div style="font-size:11px;color:#8A867C;margin-top:2px">${extra}</div>` : ''}
            </div>
        </div>`;

    const html = `
        <!-- Monto grande -->
        <div style="background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:6px;padding:20px 22px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#8A867C;margin-bottom:4px">Monto</div>
                <div style="font-size:28px;font-weight:900;color:#0E0E0C;letter-spacing:-1px;line-height:1">${fmtMoney(tx.monto)}</div>
                <div style="font-size:10px;color:#8A867C;margin-top:4px">COP</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
                <span style="font-size:11px;font-weight:700;background:${tipoBg};color:${tipoColor};padding:4px 12px;border-radius:4px">${tipoLabel}</span>
                <span style="font-size:11px;font-weight:700;background:${eBg};color:${eColor};padding:4px 12px;border-radius:4px">${eLabel}</span>
            </div>
        </div>
        <!-- Campos -->
        <div>
            ${campo('9 5l7 7-7 7', 'Concepto', _fixConcepto(tx))}
            ${tx.descripcion ? campo('M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Descripción', tx.descripcion) : ''}
            ${nombre ? campo('M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', esIngreso ? 'Cliente' : 'Proveedor', nombre) : ''}
            ${campo('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'Fecha de registro', fmt(tx.created_at))}
            ${tx.fecha_pago ? campo('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'Fecha de pago', fmt(tx.fecha_pago)) : ''}
            ${tx.fecha_vencimiento ? campo('M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'Fecha de vencimiento', fmt(tx.fecha_vencimiento)) : ''}
            ${tx.frecuencia && tx.frecuencia !== 'unico' ? campo('M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'Frecuencia', tx.frecuencia.charAt(0).toUpperCase()+tx.frecuencia.slice(1)) : ''}
        </div>`;

    document.getElementById('cmpPreview').innerHTML = html;

    // Footer con botón ir al cliente
    const footer = document.getElementById('cmpFooter');
    footer.innerHTML = `
        <button onclick="cerrarComprobante()" style="padding:9px 18px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-weight:600;color:#57544D;background:#FFFFFF;cursor:pointer;transition:all .15s" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='#FFFFFF'">Cerrar</button>
        ${clienteId ? `<a href="cliente_detalle.php?id=${clienteId}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#0E0E0C;color:#C6F24E;border:none;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:filter .15s" onmouseenter="this.style.filter='brightness(1.2)'" onmouseleave="this.style.filter=''">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Ir al cliente
        </a>` : ''}`;
}

function cerrarComprobante() {
    document.getElementById('modalComprobante').style.display = 'none';
    _cmpTxActual = null;
}

function editarDesdeComprobante() {
    if (!_cmpTxActual) return;
    const id = _cmpTxActual.id;
    cerrarComprobante();
    abrirModalTransaccion(id);
}

function descargarComprobante() {
    // Función legacy — ya no se usa desde el modal info
}

/* ═══════════════════════════════════════════════════════════════════════════
   EXPORTAR A EXCEL
   ══════════════════════════════════════════════════════════════════════════ */

// Cache para datos de clientes/servicios (se llena en renderClientesDetalle)
let fnClientesDataCache = { porCliente: [], porServicio: [], proximas: [] };

/* ── Helper: genera y descarga el .xlsx ──────────────────────────────────── */
function exportXLSX(rows, sheetName, filename) {
    if (!rows.length) {
        if (typeof showToast === 'function') showToast('Sin datos para exportar', 'warning');
        return;
    }
    if (typeof XLSX === 'undefined') {
        if (typeof showToast === 'function') showToast('Librería Excel no cargada, reintenta', 'error');
        return;
    }
    try {
        const ws = XLSX.utils.json_to_sheet(rows);
        // Ajuste automático de ancho de columnas
        const headers = Object.keys(rows[0]);
        ws['!cols'] = headers.map(h => ({
            wch: Math.max(h.length + 2, ...rows.map(r => String(r[h] ?? '').length)) + 2
        }));
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        XLSX.writeFile(wb, filename);
        if (typeof showToast === 'function') showToast('Excel descargado ✓', 'success');
    } catch (e) {
        console.error('exportXLSX error:', e);
        if (typeof showToast === 'function') showToast('Error al generar Excel', 'error');
    }
}

/* ── Tab Movimientos ─────────────────────────────────────────────────────── */
function exportarMovimientosExcel() {
    const buscar = (document.getElementById('fnBuscar')?.value || '').toLowerCase();
    const tipo   = document.getElementById('fnTipo')?.value   || 'todos';
    const estado = document.getElementById('fnEstado')?.value || 'todos';

    let data = fnTxDataFull;
    if (tipo   !== 'todos') data = data.filter(t => t.tipo   === tipo);
    if (estado === 'por_cobrar') data = data.filter(t => t.estado === 'pendiente' || t.estado === 'vencido');
    else if (estado !== 'todos') data = data.filter(t => t.estado === estado);
    if (buscar) data = data.filter(t =>
        (t.concepto      || '').toLowerCase().includes(buscar) ||
        (t.titulo        || '').toLowerCase().includes(buscar) ||
        (t.cliente_nombre|| '').toLowerCase().includes(buscar) ||
        (t.lead_nombre   || '').toLowerCase().includes(buscar) ||
        (t.proveedor     || '').toLowerCase().includes(buscar)
    );

    const rows = data.map(tx => {
        const fechaRef = tx.fecha_pago || tx.fecha_vencimiento || (tx.created_at ? tx.created_at.split(' ')[0] : '');
        const dest = tx.tipo === 'egreso'
            ? (tx.proveedor     || '')
            : (tx.cliente_nombre || tx.lead_nombre || '');
        return {
            'Fecha':              fechaRef,
            'Título':             tx.titulo    || '',
            'Concepto':           _fixConcepto(tx),
            'Cliente / Proveedor':dest,
            'Tipo':               tx.tipo === 'ingreso' ? 'Ingreso' : 'Egreso',
            'Monto (COP)':        parseFloat(tx.monto || 0),
            'Estado':             tx.estado     || '',
            'Frecuencia':         tx.frecuencia || '',
            'Notas':              tx.notas      || '',
        };
    });

    const p = fnPeriodo;
    exportXLSX(rows, 'Movimientos', `finanzas_movimientos_${p.desde}_${p.hasta}.xlsx`);
}

/* ── Tab Pagos únicos ────────────────────────────────────────────────────── */
function exportarPagosUnicosExcel() {
    const buscar = (document.getElementById('fnUnicoBuscar')?.value  || '').toLowerCase();
    const estado  = document.getElementById('fnUnicoEstado')?.value  || 'todos';

    let data = fnUnicosDataFull.filter(t => t.cliente_id);
    if (buscar) data = data.filter(t =>
        (t.cliente_nombre || '').toLowerCase().includes(buscar) ||
        (t.lead_nombre    || '').toLowerCase().includes(buscar)
    );
    if (estado !== 'todos') {
        const keysConEstado = new Set(
            fnUnicosDataFull
                .filter(t => t.estado === estado)
                .map(t => t.cliente_id ? `c_${t.cliente_id}` : `l_${t.lead_id || t.id}`)
        );
        data = data.filter(t => {
            const key = t.cliente_id ? `c_${t.cliente_id}` : `l_${t.lead_id || t.id}`;
            return keysConEstado.has(key);
        });
    }

    const rows = data.map(tx => ({
        'Cliente':            tx.cliente_nombre || tx.lead_nombre || '',
        'Título':             tx.titulo         || '',
        'Concepto':           _fixConcepto(tx),
        'Monto (COP)':        parseFloat(tx.monto || 0),
        'Estado':             tx.estado         || '',
        'Fecha Vencimiento':  tx.fecha_vencimiento || '',
        'Fecha Creación':     tx.created_at ? tx.created_at.split(' ')[0] : '',
        'Notas':              tx.notas          || '',
    }));

    exportXLSX(rows, 'Pagos Únicos', 'finanzas_pagos_unicos.xlsx');
}

/* ── Tab Clientes / Servicios ────────────────────────────────────────────── */
function exportarClientesExcel() {
    const porCliente = fnClientesDataCache.porCliente || [];
    if (!porCliente.length) {
        if (typeof showToast === 'function') showToast('Abre el tab Clientes/Servicios primero', 'warning');
        return;
    }

    // Aplicar filtros actuales del tab Clientes
    const filtroNombre   = (document.getElementById('filterClienteNombre')?.value   || '').toLowerCase();
    const filtroServicio = document.getElementById('filterServicio')?.value           || '';
    const filtroEstado   = document.getElementById('filterEstadoRenovacion')?.value  || '';
    const hoy = new Date();

    let data = porCliente;
    if (filtroNombre)   data = data.filter(c => (c.nombre || '').toLowerCase().includes(filtroNombre));
    if (filtroServicio) data = data.filter(c =>
        (c.servicios || []).some(s => String(s.servicio_id) === filtroServicio)
    );
    if (filtroEstado) {
        data = data.filter(c => {
            if (!c.proxima_renovacion) return filtroEstado === 'activo';
            const vd = new Date(c.proxima_renovacion + 'T12:00:00');
            const vm = c.proxima_renovacion.substring(0, 7);
            const mes = hoy.getFullYear() + '-' + String(hoy.getMonth()+1).padStart(2,'0');
            if (filtroEstado === 'vencido')    return vd < hoy;
            if (filtroEstado === 'vence_mes')  return vm === mes && vd >= hoy;
            if (filtroEstado === 'activo')     return vd >= hoy;
            return true;
        });
    }

    const rows = data.map(c => ({
        'Cliente':              c.nombre              || '',
        'Servicios Activos':    c.servicios_activos   || 0,
        'Ingresos Período (COP)': parseFloat(c.ingresos || 0),
        'Costos Período (COP)': parseFloat(c.costos   || 0),
        'Margen (%)':           parseFloat(c.margen_pct || 0).toFixed(1),
        'Próxima Renovación':   c.proxima_renovacion  || '—',
        'Email':                c.email               || '',
        'Teléfono':             c.telefono            || '',
    }));

    const p = fnPeriodo;
    exportXLSX(rows, 'Clientes', `finanzas_clientes_${p.desde}_${p.hasta}.xlsx`);
}
