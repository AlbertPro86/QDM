'use strict';
/**
 * CRM QUANTUN Digital — Plantillas de Facturas
 * Editor en tiempo real con 4 layouts profesionales
 */

const pfState = {
    editId:     null,
    layoutTipo: 'clasica',
};

// Datos bancarios cargados desde configuraciones
let _bancoData = null;

async function cargarBancoData() {
    try {
        const r = await fetch('api/configuraciones.php', { credentials: 'include' });
        const d = await r.json();
        if (d.success && d.data) {
            _bancoData = {
                nombre:   d.data.banco_nombre   || '',
                numero:   d.data.banco_numero   || '',
                tipo:     d.data.banco_tipo     || '',
                titular:  d.data.banco_titular  || '',
            };
            // Si no hay datos bancarios configurados, avisamos
            const hayDatos = _bancoData.nombre || _bancoData.numero || _bancoData.titular;
            const toggle = document.getElementById('pfMostrarBanco');
            if (toggle) toggle.title = hayDatos ? '' : 'Primero configura los datos bancarios en Configuraciones';
        }
    } catch(e) { /* silencioso */ }
}

// Datos de muestra para la vista previa
const SAMPLE = {
    numero:   'COB-2024-001',
    fecha:    new Date().toLocaleDateString('es-CO', { day:'2-digit', month:'long', year:'numeric' }),
    vence:    new Date(Date.now() + 30*86400000).toLocaleDateString('es-CO', { day:'2-digit', month:'long', year:'numeric' }),
    cliente:  'Empresa Cliente S.A.S',
    clienteNit: '900.123.456-7',
    clienteDir: 'Calle 45 #23-12, Medellín',
    items: [
        { desc: 'Diseño Web Corporativo',       qty: 1, price: 3500000 },
        { desc: 'Consultoría SEO & Marketing',  qty: 2, price: 850000  },
        { desc: 'Mantenimiento Mensual',         qty: 3, price: 450000  },
    ],
    descuento: 200000,
};

/* ═══════════════════════════════════════════════════════ UTILS */
function fmtCOP(n) {
    return new Intl.NumberFormat('es-CO', { style:'currency', currency:'COP', minimumFractionDigits:0 }).format(n);
}

const CATEGORIA_LABELS = {
    cotizacion:   'Cotización',
    cuenta_cobro: 'Cuenta de cobro',
    orden_compra: 'Orden de compra',
    propuesta:    'Propuesta',
    recibo:       'Recibo',
    otro:         'Documento',
};

function getCfg() {
    const cat = document.getElementById('pfCategoria')?.value || 'cotizacion';
    return {
        colorPrimario:   document.getElementById('pfColorPrimario').value   || '#0f172a',
        colorSecundario: document.getElementById('pfColorSecundario').value  || '#c9f31d',
        fuente:          document.getElementById('pfFuente').value           || 'Poppins',
        empresaNombre:   document.getElementById('pfEmpresaNombre').value    || 'QUANTUN Digital S.A.S',
        empresaNit:      document.getElementById('pfEmpresaNit').value       || '900.000.000-0',
        empresaEmail:    document.getElementById('pfEmpresaEmail').value     || 'contacto@quantundigital.com',
        empresaTel:      document.getElementById('pfEmpresaTel').value       || '+57 333 274 7801',
        empresaDir:      document.getElementById('pfEmpresaDir').value       || 'Bogotá, Colombia',
        logoUrl:         document.getElementById('pfLogoUrl').value          || '',
        notasPie:        document.getElementById('pfNotasPie').value         || 'Gracias por su preferencia. El pago debe realizarse en los próximos 30 días.',
        mostrarBanco:    document.getElementById('pfMostrarBanco')?.checked  || false,
        categoria:       cat,
        categoriaLabel:  CATEGORIA_LABELS[cat] || 'Cotización',
        layout:          pfState.layoutTipo,
    };
}

/* ═══════════════════════════════════════════════════════ LOGO PREVIEW */
function previewLogo(url) {
    const box = document.getElementById('logoPreviewBox');
    const img = document.getElementById('logoPreviewImg');
    const status = document.getElementById('logoPreviewStatus');
    if (!box || !img) return;

    const trimmed = url ? url.trim() : '';
    if (!trimmed) {
        box.style.display = 'none';
        img.src = '';
        return;
    }
    box.style.display = 'block';
    status.textContent = 'Cargando...';
    status.style.color = '#64748b';
    img.src = trimmed;
}

/* ═══════════════════════════════════════════════════════ LAYOUT TOGGLE */
function setLayout(tipo) {
    pfState.layoutTipo = 'clasica';
    actualizarPreview();
}

/* ═══════════════════════════════════════════════════════ COLOR SYNC */
function sincColorPrimario() {
    const hex = document.getElementById('pfColorPrimarioHex').value;
    if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
        document.getElementById('pfColorPrimario').value = hex;
        actualizarPreview();
    }
}
function sincColorSecundario() {
    const hex = document.getElementById('pfColorSecundarioHex').value;
    if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
        document.getElementById('pfColorSecundario').value = hex;
        actualizarPreview();
    }
}

/* ═══════════════════════════════════════════════════════ PREVIEW */
function actualizarPreview() {
    // Sync hex inputs
    document.getElementById('pfColorPrimarioHex').value   = document.getElementById('pfColorPrimario').value;
    document.getElementById('pfColorSecundarioHex').value = document.getElementById('pfColorSecundario').value;

    const cfg  = getCfg();
    const html = generarFacturaHTML(cfg);
    document.getElementById('facturaPreview').innerHTML = html;
}

function generarFacturaHTML(cfg) {
    return layoutClasica(cfg);
}

/**
 * Genera HTML de factura con datos reales.
 * Sustituye SAMPLE temporalmente para reutilizar todos los layouts existentes.
 */
function generarFacturaConDatos(cfg, data) {
    // Asegurar que categoriaLabel esté presente
    if (!cfg.categoriaLabel && cfg.categoria) {
        cfg.categoriaLabel = CATEGORIA_LABELS[cfg.categoria] || 'Cotización';
    }
    if (!cfg.categoriaLabel) cfg.categoriaLabel = 'Cotización';
    const bak = {
        items:      SAMPLE.items,
        descuento:  SAMPLE.descuento,
        numero:     SAMPLE.numero,
        fecha:      SAMPLE.fecha,
        vence:      SAMPLE.vence,
        cliente:    SAMPLE.cliente,
        clienteNit: SAMPLE.clienteNit,
        clienteDir: SAMPLE.clienteDir,
    };
    SAMPLE.items      = data.items      || bak.items;
    SAMPLE.descuento  = data.descuento  || 0;
    SAMPLE.numero     = data.numero     || bak.numero;
    SAMPLE.fecha      = data.fecha      || bak.fecha;
    SAMPLE.vence      = data.vence      || bak.vence;
    SAMPLE.cliente    = data.cliente    || bak.cliente;
    SAMPLE.clienteNit = data.clienteNit || '';
    SAMPLE.clienteDir = data.clienteDir || '';

    const html = generarFacturaHTML(cfg);
    Object.assign(SAMPLE, bak);
    return html;
}

/* ── TOTALES ────────────────────────────────────────────────────────── */
function calcTotales() {
    const sub = SAMPLE.items.reduce((s, i) => s + i.qty * i.price, 0);
    const desc = SAMPLE.descuento;
    const total = sub - desc;
    const iva = Math.round(total * 0.19);
    return { sub, desc, total: total + iva, iva };
}

/* ── TABLA ITEMS ────────────────────────────────────────────────────── */
function itemsRows(bg, clrText, borderColor, boldColor) {
    return SAMPLE.items.map((it, i) => `
        <tr style="background:${i % 2 === 0 ? bg : 'transparent'}">
            <td style="padding:9px 12px;font-size:12px;color:${clrText}">${it.desc}</td>
            <td style="padding:9px 12px;font-size:12px;color:${clrText};text-align:center">${it.qty}</td>
            <td style="padding:9px 12px;font-size:12px;color:${clrText};text-align:right">${fmtCOP(it.price)}</td>
            <td style="padding:9px 12px;font-size:12px;color:${boldColor};text-align:right;font-weight:700">${fmtCOP(it.qty * it.price)}</td>
        </tr>`).join('');
}

/* ═══════════════════════════════════════════════════════ LAYOUTS */

/* ── CLÁSICA ────────────────────────────────────────────────────────── */
function layoutClasica(cfg) {
    const { sub, desc, total, iva } = calcTotales();
    const logoUrl = cfg.logoUrl && cfg.logoUrl.trim() ? cfg.logoUrl : 'Assets/logo_quantun_digital_negro.png';
    const logoTag = `<img src="${logoUrl}" alt="Logo" style="max-height:44px;max-width:140px;object-fit:contain;filter:brightness(0)">`;

    return `
    <div style="font-family:'${cfg.fuente}',sans-serif;background:#fff;color:#0E0E0C">
        <div style="padding:28px 32px 20px;display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1.5px solid #E8E5DD">
            <div>
                ${logoTag}
                <div style="margin-top:10px;font-size:10px;color:#8A867C;line-height:1.8">
                    <strong style="color:#0E0E0C;font-size:11px">${cfg.empresaNombre}</strong><br>
                    NIT: ${cfg.empresaNit} &nbsp;·&nbsp; ${cfg.empresaEmail}<br>
                    ${cfg.empresaTel} &nbsp;·&nbsp; ${cfg.empresaDir}
                </div>
            </div>
            <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:10px">
                <!-- Badge tipo de documento -->
                <div style="display:inline-block;background:${cfg.colorPrimario};color:${cfg.colorSecundario};padding:5px 14px;border-radius:3px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;white-space:nowrap">${cfg.categoriaLabel}</div>
                <!-- Número -->
                <div style="font-size:22px;font-weight:900;color:#0E0E0C;letter-spacing:-1px">${SAMPLE.numero}</div>
                <!-- Fechas -->
                <div style="border:1.5px solid #E8E5DD;border-radius:3px;padding:10px 14px;text-align:left;min-width:160px">
                    <div style="display:flex;justify-content:space-between;gap:16px;font-size:10px;margin-bottom:5px">
                        <span style="color:#8A867C;font-weight:700;text-transform:uppercase">Emisión</span>
                        <span style="color:#0E0E0C;font-weight:700">${SAMPLE.fecha}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:16px;font-size:10px">
                        <span style="color:#8A867C;font-weight:700;text-transform:uppercase">Vence</span>
                        <span style="color:${cfg.colorPrimario};font-weight:700">${SAMPLE.vence}</span>
                    </div>
                </div>
            </div>
        </div>
        <div style="padding:18px 32px;border-bottom:1.5px solid #E8E5DD">
            <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:${cfg.colorPrimario};margin-bottom:5px">Cobrado a</div>
            <div style="font-size:13px;font-weight:700;color:#0E0E0C">${SAMPLE.cliente}</div>
            <div style="font-size:10px;color:#8A867C;margin-top:3px;line-height:1.7">NIT: ${SAMPLE.clienteNit} &nbsp;·&nbsp; ${SAMPLE.clienteDir}</div>
        </div>
        <div style="padding:0 32px">
            <table style="width:100%;border-collapse:collapse;margin:18px 0;font-size:12px">
                <thead>
                    <tr style="border-bottom:2px solid ${cfg.colorPrimario}">
                        <th style="padding:8px 0;text-align:left;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:${cfg.colorPrimario}">Descripción</th>
                        <th style="padding:8px 0;text-align:center;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:${cfg.colorPrimario}">Cant.</th>
                        <th style="padding:8px 0;text-align:right;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:${cfg.colorPrimario}">Precio unit.</th>
                        <th style="padding:8px 0;text-align:right;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:${cfg.colorPrimario}">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${SAMPLE.items.map((it,i) => `
                    <tr style="border-bottom:1px solid #F0EFEB;background:${i%2===0?'#fff':'#FAFAF7'}">
                        <td style="padding:10px 0;font-size:12px;color:#57544D">${it.desc}</td>
                        <td style="padding:10px 0;font-size:12px;color:#8A867C;text-align:center">${it.qty}</td>
                        <td style="padding:10px 0;font-size:12px;color:#8A867C;text-align:right">${fmtCOP(it.price)}</td>
                        <td style="padding:10px 0;font-size:12px;font-weight:700;color:#0E0E0C;text-align:right">${fmtCOP(it.qty*it.price)}</td>
                    </tr>`).join('')}
                </tbody>
            </table>
        </div>
        <div style="padding:0 32px 28px;display:flex;justify-content:flex-end">
            <div style="min-width:230px">
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;color:#8A867C;border-bottom:1px solid #F0EFEB"><span>Subtotal</span><span>${fmtCOP(sub)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;color:#8A867C;border-bottom:1px solid #F0EFEB"><span>Descuento</span><span>– ${fmtCOP(desc)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;color:#8A867C;border-bottom:1px solid #F0EFEB"><span>IVA (19%)</span><span>${fmtCOP(iva)}</span></div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:${cfg.colorPrimario};border-radius:3px;margin-top:10px">
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Total</span>
                    <span style="font-size:18px;font-weight:900;color:${cfg.colorSecundario}">${fmtCOP(total)}</span>
                </div>
            </div>
        </div>
        <div style="padding:14px 32px;border-top:1.5px solid #E8E5DD;font-size:10px;color:#8A867C;line-height:1.7">${cfg.notasPie}</div>
        ${cfg.mostrarBanco && _bancoData && (_bancoData.nombre || _bancoData.numero) ? `
        <div style="padding:0 32px 28px">
            <div style="background:${cfg.colorPrimario};border-radius:4px;padding:16px 20px">
                <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:${cfg.colorSecundario};margin-bottom:12px;display:flex;align-items:center;gap:6px">
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="opacity:.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Datos para transferencia bancaria
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    ${_bancoData.nombre ? `
                    <div>
                        <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:${cfg.colorSecundario};opacity:.65;margin-bottom:3px">Banco</div>
                        <div style="font-size:12px;font-weight:700;color:#fff">${_bancoData.nombre}</div>
                    </div>` : ''}
                    ${_bancoData.tipo ? `
                    <div>
                        <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:${cfg.colorSecundario};opacity:.65;margin-bottom:3px">Tipo de cuenta</div>
                        <div style="font-size:12px;font-weight:700;color:#fff">${_bancoData.tipo}</div>
                    </div>` : ''}
                    ${_bancoData.numero ? `
                    <div>
                        <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:${cfg.colorSecundario};opacity:.65;margin-bottom:3px">Número de cuenta</div>
                        <div style="font-size:13px;font-weight:800;color:#fff;letter-spacing:.03em">${_bancoData.numero}</div>
                    </div>` : ''}
                    ${_bancoData.titular ? `
                    <div>
                        <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:${cfg.colorSecundario};opacity:.65;margin-bottom:3px">A nombre de</div>
                        <div style="font-size:12px;font-weight:700;color:#fff">${_bancoData.titular}</div>
                    </div>` : ''}
                </div>
            </div>
        </div>` : ''}
    </div>`;
}


/* ═══════════════════════════════════════════════════════ CRUD */
function cargarPlantillas() {
    fetch('api/plantillas_factura.php')
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.error);
            _pfTodas = res.data || [];
            filtrarGaleria();
        })
        .catch(err => {
            document.getElementById('plantillasGrid').innerHTML =
                `<div style="grid-column:1/-1;text-align:center;padding:24px;color:#B0382F;font-size:12px">${err.message}</div>`;
        });
}

/* ── Vista galería / editor ──────────────────────────────────────────── */
let _pfTodas = [];

function mostrarGaleria() {
    document.getElementById('vistaGaleria').style.display = '';
    document.getElementById('vistaEditor').style.display  = 'none';
}

function mostrarEditor() {
    document.getElementById('vistaGaleria').style.display = 'none';
    document.getElementById('vistaEditor').style.display  = '';
}

function filtrarGaleria() {
    const q        = (document.getElementById('pfFiltroNombre')?.value    || '').toLowerCase().trim();
    const categoria = document.getElementById('pfFiltroCategoria')?.value || '';
    const filtradas = _pfTodas.filter(p => {
        const matchQ = !q        || (p.nombre    || '').toLowerCase().includes(q);
        const matchC = !categoria || (p.categoria || 'cotizacion') === categoria;
        return matchQ && matchC;
    });
    const conteo = document.getElementById('pfFiltroConteo');
    if (conteo) conteo.textContent = filtradas.length + ' de ' + _pfTodas.length + ' plantillas';
    renderPlantillas(filtradas);
}

function renderPlantillas(arr) {
    const grid = document.getElementById('plantillasGrid');
    if (!arr.length) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:56px;color:#8A867C;font-size:13px;background:#FAFAF7;border-radius:3px;border:1.5px dashed #E8E5DD">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="display:block;margin:0 auto 12px;opacity:.35"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Sin plantillas. Crea tu primer diseño.
        </div>`;
        return;
    }

    grid.innerHTML = arr.map(p => {
        const catLabel = CATEGORIA_LABELS[p.categoria || 'cotizacion'] || 'Cotización';
        return `
    <div style="background:#fff;border:1.5px solid ${p.es_default ? p.color_primario : '#E8E5DD'};border-radius:3px;overflow:hidden;transition:box-shadow .15s;display:flex;flex-direction:column"
         onmouseenter="this.style.boxShadow='0 4px 16px rgba(0,0,0,.07)'" onmouseleave="this.style.boxShadow='none'">

        <!-- Preview thumbnail -->
        <div style="height:110px;background:#F5F3EE;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;cursor:pointer" onclick="cargarEnForm(${p.id})">
            <div style="width:140px;background:#fff;border-radius:2px;box-shadow:0 2px 10px rgba(0,0,0,.12);overflow:hidden;transform:scale(.95)">
                <div style="padding:8px 10px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:7px">
                        <div style="width:45%;height:7px;background:${p.color_primario};border-radius:1px;margin-top:1px;opacity:.9"></div>
                        <div style="background:${p.color_primario};color:${p.color_secundario};padding:2px 5px;border-radius:2px;font-size:5px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;max-width:60px;overflow:hidden;text-overflow:ellipsis">${catLabel}</div>
                    </div>
                    <div style="width:35%;height:4px;background:#E8E5DD;border-radius:1px;margin-bottom:8px"></div>
                    <div style="display:flex;flex-direction:column;gap:2px">
                        <div style="height:3px;background:#F0EFEB;border-radius:1px"></div>
                        <div style="height:3px;background:#F0EFEB;border-radius:1px;width:80%"></div>
                        <div style="height:3px;background:#F0EFEB;border-radius:1px;width:60%"></div>
                    </div>
                    <div style="margin-top:8px;display:flex;justify-content:flex-end">
                        <div style="background:${p.color_primario};padding:3px 8px;border-radius:1px">
                            <div style="height:4px;width:32px;background:${p.color_secundario};border-radius:1px"></div>
                        </div>
                    </div>
                </div>
            </div>
            ${p.es_default ? `<span style="position:absolute;top:8px;right:8px;background:#C6F24E;color:#0E0E0C;padding:2px 8px;border-radius:100px;font-size:9px;font-weight:800">★ Default</span>` : ''}
        </div>

        <!-- Info -->
        <div style="padding:12px 14px;flex:1;display:flex;flex-direction:column;gap:10px">
            <div>
                <div style="font-size:13px;font-weight:700;color:#0E0E0C;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.nombre}</div>
                <div style="display:flex;align-items:center;gap:6px;margin-top:5px;flex-wrap:wrap">
                    <span style="font-size:10px;font-weight:700;color:${p.color_primario};background:${p.color_primario}18;padding:2px 8px;border-radius:100px;white-space:nowrap">${catLabel}</span>
                    <span style="font-size:10px;color:#8A867C">${p.fuente}</span>
                    <div style="display:flex;gap:3px;margin-left:auto">
                        <div style="width:12px;height:12px;border-radius:50%;background:${p.color_primario};border:1px solid rgba(0,0,0,.08)" title="Color primario"></div>
                        <div style="width:12px;height:12px;border-radius:50%;background:${p.color_secundario};border:1px solid rgba(0,0,0,.08)" title="Color acento"></div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:4px">
                <button onclick="cargarEnForm(${p.id})"
                    style="flex:1;padding:7px;background:#0E0E0C;border:none;border-radius:3px;font-size:11px;font-weight:700;cursor:pointer;color:#fff;transition:opacity .12s" onmouseenter="this.style.opacity='.85'" onmouseleave="this.style.opacity='1'">
                    Editar
                </button>
                ${!p.es_default ? `<button onclick="setComoDefault(${p.id})" title="Usar como predeterminada"
                    style="padding:7px 10px;background:#E3F1E8;border:none;border-radius:3px;font-size:12px;cursor:pointer;color:#2D8F5A;transition:background .12s" onmouseenter="this.style.background='#C8E6D4'" onmouseleave="this.style.background='#E3F1E8'">★</button>` : ''}
                ${!p.es_default ? `<button onclick="confirmarEliminarPlantilla(${p.id},'${p.nombre.replace(/'/g,"&#39;")}')"
                    style="padding:7px 10px;background:#F4DEDB;border:none;border-radius:3px;font-size:12px;cursor:pointer;color:#B0382F;transition:background .12s" onmouseenter="this.style.background='#EFCFCC'" onmouseleave="this.style.background='#F4DEDB'">✕</button>` : ''}
            </div>
        </div>
    </div>`;
    }).join('');
}

function abrirFormNueva() {
    mostrarEditor();
    pfState.editId = null;
    document.getElementById('formTitulo').textContent  = 'Nueva plantilla';
    document.getElementById('pfId').value              = '';
    document.getElementById('pfNombre').value          = '';
    document.getElementById('pfColorPrimario').value   = '#0f172a';
    document.getElementById('pfColorPrimarioHex').value= '#0f172a';
    document.getElementById('pfColorSecundario').value = '#c9f31d';
    document.getElementById('pfColorSecundarioHex').value='#c9f31d';
    document.getElementById('pfFuente').value          = 'Poppins';
    document.getElementById('pfLogoUrl').value         = '';
    document.getElementById('pfEmpresaNombre').value   = '';
    document.getElementById('pfEmpresaNit').value      = '';
    document.getElementById('pfEmpresaEmail').value    = '';
    document.getElementById('pfEmpresaTel').value      = '';
    document.getElementById('pfEmpresaDir').value      = '';
    document.getElementById('pfNotasPie').value        = '';
    document.getElementById('pfMostrarBanco').checked  = false;
    document.getElementById('pfEsDefault').checked     = false;
    if (document.getElementById('pfCategoria')) document.getElementById('pfCategoria').value = 'cotizacion';
    previewLogo(''); // ocultar preview del logo
    setLayout('clasica');
}

async function cargarEnForm(id) {
    try {
        const res = await fetch(`api/plantillas_factura.php?id=${id}`).then(r => r.json());
        if (!res.success) throw new Error(res.error);
        const p = res.data;
        pfState.editId = p.id;
        pfState.layoutTipo = p.layout_tipo || 'clasica';

        mostrarEditor();
        document.getElementById('formTitulo').textContent   = 'Editar plantilla';
        document.getElementById('pfId').value               = p.id;
        document.getElementById('pfNombre').value           = p.nombre          || '';
        document.getElementById('pfColorPrimario').value    = p.color_primario  || '#0f172a';
        document.getElementById('pfColorPrimarioHex').value = p.color_primario  || '#0f172a';
        document.getElementById('pfColorSecundario').value  = p.color_secundario|| '#c9f31d';
        document.getElementById('pfColorSecundarioHex').value=p.color_secundario|| '#c9f31d';
        document.getElementById('pfFuente').value           = p.fuente          || 'Poppins';
        document.getElementById('pfLogoUrl').value          = p.logo_url        || '';
        previewLogo(p.logo_url || ''); // mostrar preview si tiene logo
        document.getElementById('pfEmpresaNombre').value    = p.empresa_nombre  || '';
        document.getElementById('pfEmpresaNit').value       = p.empresa_nit     || '';
        document.getElementById('pfEmpresaEmail').value     = p.empresa_email   || '';
        document.getElementById('pfEmpresaTel').value       = p.empresa_tel     || '';
        document.getElementById('pfEmpresaDir').value       = p.empresa_dir     || '';
        document.getElementById('pfNotasPie').value         = p.notas_pie       || '';
        document.getElementById('pfMostrarBanco').checked   = !!p.mostrar_banco;
        document.getElementById('pfEsDefault').checked      = !!p.es_default;
        if (document.getElementById('pfCategoria')) document.getElementById('pfCategoria').value = p.categoria || 'cotizacion';

        pfState.layoutTipo = 'clasica';
        actualizarPreview();
    } catch (err) {
        if (typeof showToast === 'function') showToast('Error al cargar plantilla', 'error');
    }
}

async function guardarPlantilla() {
    const nombreInput = document.getElementById('pfNombre');
    let nombre = nombreInput.value.trim();

    // Si el nombre está vacío, auto-completar
    if (!nombre) {
        nombre = 'Clásica';
        nombreInput.value = nombre;
    }

    const btn = document.getElementById('pfBtnGuardar');
    btn.disabled = true;
    btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" class="spin"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Guardando...`;

    const id = parseInt(document.getElementById('pfId').value, 10) || null;
    const payload = {
        nombre,
        layout_tipo:      pfState.layoutTipo,
        categoria:        document.getElementById('pfCategoria')?.value || 'cotizacion',
        color_primario:   document.getElementById('pfColorPrimario').value,
        color_secundario: document.getElementById('pfColorSecundario').value,
        fuente:           document.getElementById('pfFuente').value,
        logo_url:         document.getElementById('pfLogoUrl').value.trim()        || null,
        empresa_nombre:   document.getElementById('pfEmpresaNombre').value.trim()  || null,
        empresa_nit:      document.getElementById('pfEmpresaNit').value.trim()     || null,
        empresa_email:    document.getElementById('pfEmpresaEmail').value.trim()   || null,
        empresa_tel:      document.getElementById('pfEmpresaTel').value.trim()     || null,
        empresa_dir:      document.getElementById('pfEmpresaDir').value.trim()     || null,
        notas_pie:        document.getElementById('pfNotasPie').value.trim()       || null,
        mostrar_banco:    document.getElementById('pfMostrarBanco').checked ? 1 : 0,
        es_default:       document.getElementById('pfEsDefault').checked ? 1 : 0,
    };
    if (id) payload.id = id;

    try {
        const res = await fetch('api/plantillas_factura.php', {
            method:  id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        }).then(r => r.json());

        if (!res.success) throw new Error(res.error || 'Error al guardar');
        if (typeof showToast === 'function') showToast(id ? 'Plantilla actualizada ✓' : 'Plantilla creada ✓', 'success');
        await cargarPlantillas();
        mostrarGaleria();
    } catch (err) {
        if (typeof showToast === 'function') showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar plantilla`;
    }
}

async function setComoDefault(id) {
    try {
        const res = await fetch(`api/plantillas_factura.php?set_default=${id}`).then(r => r.json());
        if (!res.success) throw new Error(res.error);
        if (typeof showToast === 'function') showToast('Plantilla predeterminada actualizada ✓', 'success');
        cargarPlantillas();
    } catch (err) {
        if (typeof showToast === 'function') showToast(err.message, 'error');
    }
}

function confirmarEliminarPlantilla(id, nombre) {
    document.getElementById('modalElimPfNombre').textContent = `Se eliminará la plantilla "${nombre}". Esta acción no se puede deshacer.`;
    document.getElementById('modalEliminarPlantilla').style.display = 'flex';

    const btn = document.getElementById('btnConfirmarElimPf');
    const handler = async () => {
        btn.removeEventListener('click', handler);
        btn.disabled = true;
        try {
            const res = await fetch(`api/plantillas_factura.php?id=${id}`, { method: 'DELETE' }).then(r => r.json());
            if (!res.success) throw new Error(res.error);
            document.getElementById('modalEliminarPlantilla').style.display = 'none';
            if (typeof showToast === 'function') showToast('Plantilla eliminada', 'success');
            cargarPlantillas();
        } catch (err) {
            if (typeof showToast === 'function') showToast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    };
    btn.addEventListener('click', handler);
}

/* ═══════════════════════════════════════════════════════ INIT */
document.addEventListener('DOMContentLoaded', () => {
    cargarPlantillas();
    cargarBancoData();

    // Solo en la página de plantillas (pfColorPrimario existe ahí)
    const pfCP = document.getElementById('pfColorPrimario');
    const pfCS = document.getElementById('pfColorSecundario');
    if (pfCP && pfCS) {
        actualizarPreview();

        pfCP.addEventListener('change', () => {
            document.getElementById('pfColorPrimarioHex').value = pfCP.value;
            actualizarPreview();
        });
        pfCS.addEventListener('change', () => {
            document.getElementById('pfColorSecundarioHex').value = pfCS.value;
            actualizarPreview();
        });
    }

    const modalElim = document.getElementById('modalEliminarPlantilla');
    if (modalElim) {
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') modalElim.style.display = 'none';
        });
    }
});
