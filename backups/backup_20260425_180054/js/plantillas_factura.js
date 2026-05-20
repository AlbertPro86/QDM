'use strict';
/**
 * CRM QUANTUN Digital — Plantillas de Facturas
 * Editor en tiempo real con 4 layouts profesionales
 */

const pfState = {
    editId:     null,
    layoutTipo: 'clasica',
};

// Datos de muestra para la vista previa
const SAMPLE = {
    numero:   'FAC-2024-001',
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

function getCfg() {
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
function setLayout(tipo, btn) {
    pfState.layoutTipo = tipo;
    document.querySelectorAll('[id^="layout-"]').forEach(b => {
        b.style.border      = '2px solid #e2e8f0';
        b.style.background  = '#fff';
        b.style.color       = '#64748b';
    });
    btn.style.border     = '2px solid #0f172a';
    btn.style.background = '#0f172a';
    btn.style.color      = '#fff';

    // Auto-completar nombre si está vacío
    const nombreInput = document.getElementById('pfNombre');
    if (nombreInput && !nombreInput.value.trim()) {
        const nombres = { clasica: 'Clásica', moderna: 'Moderna', minimalista: 'Minimalista', ejecutiva: 'Ejecutiva' };
        nombreInput.value = nombres[tipo] || tipo.charAt(0).toUpperCase() + tipo.slice(1);
        nombreInput.style.borderColor = '#e2e8f0'; // quitar borde rojo si había
    }

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
    const layouts = { clasica: layoutClasica, moderna: layoutModerna, minimalista: layoutMinimalista, ejecutiva: layoutEjecutiva };
    return (layouts[cfg.layout] || layoutClasica)(cfg);
}

/**
 * Genera HTML de factura con datos reales.
 * Sustituye SAMPLE temporalmente para reutilizar todos los layouts existentes.
 */
function generarFacturaConDatos(cfg, data) {
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
    const logoUrl = cfg.logoUrl && cfg.logoUrl.trim() ? cfg.logoUrl : 'assets/logo_quantun_digital_negro.png';
    const logoTag = `<img src="${logoUrl}" alt="Logo" style="max-height:50px;max-width:150px;object-fit:contain">`;

    return `
    <div style="font-family:'${cfg.fuente}',sans-serif;background:#fff;color:#1e293b;padding:40px">

        <!-- Encabezado -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:24px;border-bottom:3px solid ${cfg.colorPrimario}">
            <div>
                ${logoTag}
                <div style="margin-top:10px;font-size:11px;color:#64748b;line-height:1.7">
                    ${cfg.empresaNombre}<br>
                    NIT: ${cfg.empresaNit}<br>
                    ${cfg.empresaDir}<br>
                    ${cfg.empresaEmail} · ${cfg.empresaTel}
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-size:28px;font-weight:900;color:${cfg.colorPrimario};letter-spacing:-1px">FACTURA</div>
                <div style="font-size:13px;font-weight:700;color:#94a3b8;margin-top:4px">${SAMPLE.numero}</div>
                <div style="margin-top:14px;background:${cfg.colorSecundario};padding:10px 16px;border-radius:8px;text-align:left">
                    <div style="font-size:10px;font-weight:700;color:${cfg.colorPrimario};text-transform:uppercase">Fecha emisión</div>
                    <div style="font-size:12px;font-weight:700;color:${cfg.colorPrimario};margin-top:2px">${SAMPLE.fecha}</div>
                    <div style="font-size:10px;font-weight:700;color:${cfg.colorPrimario};text-transform:uppercase;margin-top:6px">Vencimiento</div>
                    <div style="font-size:12px;font-weight:700;color:${cfg.colorPrimario};margin-top:2px">${SAMPLE.vence}</div>
                </div>
            </div>
        </div>

        <!-- Facturado a -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px">
            <div>
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:${cfg.colorPrimario};margin-bottom:8px">Facturado a</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a">${SAMPLE.cliente}</div>
                <div style="font-size:11px;color:#64748b;margin-top:4px;line-height:1.6">NIT: ${SAMPLE.clienteNit}<br>${SAMPLE.clienteDir}</div>
            </div>
        </div>

        <!-- Tabla items -->
        <table style="width:100%;border-collapse:collapse;margin-bottom:24px;font-size:12px">
            <thead>
                <tr style="background:${cfg.colorPrimario}">
                    <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Descripción</th>
                    <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Cant.</th>
                    <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Precio</th>
                    <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Total</th>
                </tr>
            </thead>
            <tbody>
                ${itemsRows('#f8fafc','#475569','#e2e8f0','#0f172a')}
            </tbody>
        </table>

        <!-- Totales -->
        <div style="display:flex;justify-content:flex-end">
            <div style="min-width:220px">
                <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:12px;color:#64748b"><span>Subtotal</span><span>${fmtCOP(sub)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:12px;color:#64748b"><span>Descuento</span><span>- ${fmtCOP(desc)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:12px;color:#64748b"><span>IVA (19%)</span><span>${fmtCOP(iva)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:12px 16px;background:${cfg.colorPrimario};border-radius:8px;margin-top:8px">
                    <span style="font-size:14px;font-weight:800;color:${cfg.colorSecundario}">TOTAL</span>
                    <span style="font-size:16px;font-weight:900;color:${cfg.colorSecundario}">${fmtCOP(total)}</span>
                </div>
            </div>
        </div>

        <!-- Pie -->
        <div style="margin-top:32px;padding-top:16px;border-top:1.5px solid #e2e8f0;font-size:11px;color:#94a3b8;text-align:center;line-height:1.6">
            ${cfg.notasPie}
        </div>
    </div>`;
}

/* ── MODERNA ────────────────────────────────────────────────────────── */
function layoutModerna(cfg) {
    const { sub, desc, total, iva } = calcTotales();
    const logoUrl = cfg.logoUrl && cfg.logoUrl.trim() ? cfg.logoUrl : 'assets/logo_quantun_digital_negro.png';
    const logoTag = `<img src="${logoUrl}" alt="Logo" style="max-height:44px;object-fit:contain">`;

    return `
    <div style="font-family:'${cfg.fuente}',sans-serif;background:#fff;color:#1e293b">

        <!-- Header colorido full-width -->
        <div style="background:${cfg.colorPrimario};padding:28px 36px;display:flex;justify-content:space-between;align-items:center">
            <div style="color:${cfg.colorSecundario}">${logoTag}</div>
            <div style="text-align:right">
                <div style="font-size:32px;font-weight:900;color:${cfg.colorSecundario};letter-spacing:-1.5px">FACTURA</div>
                <div style="font-size:13px;color:rgba(255,255,255,0.6);margin-top:2px">${SAMPLE.numero}</div>
            </div>
        </div>

        <!-- Barra acento -->
        <div style="background:${cfg.colorSecundario};height:5px"></div>

        <!-- Info grid -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;border-bottom:1.5px solid #f1f5f9">
            <div style="padding:20px 24px;border-right:1.5px solid #f1f5f9">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:8px">Empresa</div>
                <div style="font-size:12px;font-weight:700;color:#0f172a">${cfg.empresaNombre}</div>
                <div style="font-size:11px;color:#64748b;margin-top:4px;line-height:1.6">NIT: ${cfg.empresaNit}<br>${cfg.empresaEmail}</div>
            </div>
            <div style="padding:20px 24px;border-right:1.5px solid #f1f5f9">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:8px">Cliente</div>
                <div style="font-size:12px;font-weight:700;color:#0f172a">${SAMPLE.cliente}</div>
                <div style="font-size:11px;color:#64748b;margin-top:4px;line-height:1.6">NIT: ${SAMPLE.clienteNit}<br>${SAMPLE.clienteDir}</div>
            </div>
            <div style="padding:20px 24px">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:8px">Fechas</div>
                <div style="font-size:11px;color:#64748b;line-height:2">
                    <span style="font-weight:700;color:#0f172a">Emisión:</span> ${SAMPLE.fecha}<br>
                    <span style="font-weight:700;color:#0f172a">Vence:</span> ${SAMPLE.vence}
                </div>
            </div>
        </div>

        <!-- Items -->
        <div style="padding:24px 36px">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="padding:8px 0;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;color:${cfg.colorPrimario};border-bottom:2px solid ${cfg.colorPrimario}">Descripción</th>
                        <th style="padding:8px 0;text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;color:${cfg.colorPrimario};border-bottom:2px solid ${cfg.colorPrimario}">Cant.</th>
                        <th style="padding:8px 0;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;color:${cfg.colorPrimario};border-bottom:2px solid ${cfg.colorPrimario}">Precio unit.</th>
                        <th style="padding:8px 0;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;color:${cfg.colorPrimario};border-bottom:2px solid ${cfg.colorPrimario}">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${SAMPLE.items.map((it,i) => `
                    <tr style="border-bottom:1px solid #f1f5f9">
                        <td style="padding:11px 0;font-size:12px;color:#475569">${it.desc}</td>
                        <td style="padding:11px 0;font-size:12px;color:#475569;text-align:center">${it.qty}</td>
                        <td style="padding:11px 0;font-size:12px;color:#475569;text-align:right">${fmtCOP(it.price)}</td>
                        <td style="padding:11px 0;font-size:12px;font-weight:700;color:#0f172a;text-align:right">${fmtCOP(it.qty*it.price)}</td>
                    </tr>`).join('')}
                </tbody>
            </table>

            <!-- Totales moderno -->
            <div style="display:flex;justify-content:flex-end;margin-top:20px">
                <div style="min-width:240px">
                    <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:12px;color:#64748b"><span>Subtotal</span><span>${fmtCOP(sub)}</span></div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:12px;color:#64748b"><span>Descuento</span><span>- ${fmtCOP(desc)}</span></div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:12px;color:#64748b"><span>IVA 19%</span><span>${fmtCOP(iva)}</span></div>
                    <div style="display:flex;justify-content:space-between;padding:14px 0;border-top:2px solid ${cfg.colorPrimario};margin-top:8px">
                        <span style="font-size:15px;font-weight:900;color:${cfg.colorPrimario}">TOTAL</span>
                        <span style="font-size:17px;font-weight:900;color:${cfg.colorPrimario}">${fmtCOP(total)}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie -->
        <div style="background:${cfg.colorPrimario};padding:16px 36px;font-size:11px;color:rgba(255,255,255,.6);text-align:center">
            ${cfg.notasPie}
        </div>
    </div>`;
}

/* ── MINIMALISTA ────────────────────────────────────────────────────── */
function layoutMinimalista(cfg) {
    const { sub, desc, total, iva } = calcTotales();
    const logoUrl = cfg.logoUrl && cfg.logoUrl.trim() ? cfg.logoUrl : 'assets/logo_quantun_digital_negro.png';
    const logoTag = `<img src="${logoUrl}" alt="Logo" style="max-height:36px;object-fit:contain">`;

    return `
    <div style="font-family:'${cfg.fuente}',sans-serif;background:#fff;color:#1e293b;padding:48px 52px">

        <!-- Top -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:48px">
            ${logoTag}
            <div style="text-align:right">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:${cfg.colorPrimario};opacity:.5">Factura</div>
                <div style="font-size:22px;font-weight:900;color:${cfg.colorPrimario};letter-spacing:-1px;margin-top:2px">${SAMPLE.numero}</div>
            </div>
        </div>

        <!-- Info en línea -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:40px">
            <div>
                <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:10px">De</div>
                <div style="font-size:13px;font-weight:700;color:${cfg.colorPrimario}">${cfg.empresaNombre}</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:6px;line-height:1.8">
                    ${cfg.empresaNit ? 'NIT: ' + cfg.empresaNit + '<br>' : ''}
                    ${cfg.empresaEmail}<br>${cfg.empresaTel}
                </div>
            </div>
            <div>
                <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:10px">Para</div>
                <div style="font-size:13px;font-weight:700;color:${cfg.colorPrimario}">${SAMPLE.cliente}</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:6px;line-height:1.8">${SAMPLE.clienteDir}</div>
                <div style="margin-top:12px">
                    <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:6px">Fecha · Vencimiento</div>
                    <div style="font-size:11px;color:#64748b">${SAMPLE.fecha} · ${SAMPLE.vence}</div>
                </div>
            </div>
        </div>

        <!-- Línea acento -->
        <div style="height:2px;background:${cfg.colorSecundario};margin-bottom:24px;border-radius:2px"></div>

        <!-- Items minimalista -->
        <table style="width:100%;border-collapse:collapse;margin-bottom:32px">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0">
                    <th style="padding:8px 0;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8">Servicio / Producto</th>
                    <th style="padding:8px 0;text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8">Qty</th>
                    <th style="padding:8px 0;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8">Unitario</th>
                    <th style="padding:8px 0;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8">Valor</th>
                </tr>
            </thead>
            <tbody>
                ${SAMPLE.items.map(it => `
                <tr style="border-bottom:1px solid #f8fafc">
                    <td style="padding:12px 0;font-size:12px;color:#475569">${it.desc}</td>
                    <td style="padding:12px 0;font-size:12px;color:#94a3b8;text-align:center">${it.qty}</td>
                    <td style="padding:12px 0;font-size:12px;color:#94a3b8;text-align:right">${fmtCOP(it.price)}</td>
                    <td style="padding:12px 0;font-size:12px;font-weight:700;color:${cfg.colorPrimario};text-align:right">${fmtCOP(it.qty*it.price)}</td>
                </tr>`).join('')}
            </tbody>
        </table>

        <!-- Totales minimalista -->
        <div style="display:flex;justify-content:flex-end">
            <div style="min-width:200px">
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:11px;color:#94a3b8"><span>Subtotal</span><span>${fmtCOP(sub)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:11px;color:#94a3b8"><span>Descuento</span><span>- ${fmtCOP(desc)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:11px;color:#94a3b8"><span>IVA 19%</span><span>${fmtCOP(iva)}</span></div>
                <div style="height:1px;background:${cfg.colorPrimario};margin:10px 0"></div>
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:13px;font-weight:900;color:${cfg.colorPrimario}">Total</span>
                    <span style="font-size:16px;font-weight:900;color:${cfg.colorPrimario}">${fmtCOP(total)}</span>
                </div>
            </div>
        </div>

        <!-- Pie -->
        <div style="margin-top:48px;font-size:10px;color:#94a3b8;text-align:center;font-style:italic">
            ${cfg.notasPie}
        </div>
    </div>`;
}

/* ── EJECUTIVA ──────────────────────────────────────────────────────── */
function layoutEjecutiva(cfg) {
    const { sub, desc, total, iva } = calcTotales();
    const logoUrl = cfg.logoUrl && cfg.logoUrl.trim() ? cfg.logoUrl : 'assets/logo_quantun_digital_negro.png';
    const logoTag = `<img src="${logoUrl}" alt="Logo" style="max-height:48px;object-fit:contain;filter:brightness(0) invert(1)">`;

    return `
    <div style="font-family:'${cfg.fuente}',sans-serif;background:#fff;color:#1e293b">

        <!-- Header oscuro -->
        <div style="background:${cfg.colorPrimario};padding:0">
            <!-- Top stripe acento -->
            <div style="background:${cfg.colorSecundario};height:6px"></div>
            <div style="padding:28px 36px;display:flex;justify-content:space-between;align-items:flex-end">
                <div>
                    ${logoTag}
                    <div style="margin-top:14px;font-size:11px;color:rgba(255,255,255,0.55);line-height:1.8">
                        ${cfg.empresaNit ? 'NIT: ' + cfg.empresaNit + ' &nbsp;·&nbsp; ' : ''}${cfg.empresaEmail}<br>
                        ${cfg.empresaTel} &nbsp;·&nbsp; ${cfg.empresaDir}
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:${cfg.colorSecundario}">Factura de Venta</div>
                    <div style="font-size:26px;font-weight:900;color:#fff;letter-spacing:-1px;margin-top:4px">${SAMPLE.numero}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,0.5);margin-top:4px">${SAMPLE.fecha}</div>
                </div>
            </div>
        </div>

        <!-- Cliente + Fechas -->
        <div style="background:#f8fafc;padding:20px 36px;display:flex;justify-content:space-between;align-items:center;border-bottom:1.5px solid #e2e8f0">
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px">Facturado a</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a">${SAMPLE.cliente}</div>
                <div style="font-size:11px;color:#64748b;margin-top:3px">${SAMPLE.clienteDir}</div>
            </div>
            <div style="display:flex;gap:28px">
                <div style="text-align:center">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">Emisión</div>
                    <div style="font-size:12px;font-weight:700;color:#0f172a">${SAMPLE.fecha}</div>
                </div>
                <div style="text-align:center">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">Vencimiento</div>
                    <div style="font-size:12px;font-weight:700;color:#0f172a">${SAMPLE.vence}</div>
                </div>
            </div>
        </div>

        <!-- Items ejecutiva -->
        <div style="padding:28px 36px">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:${cfg.colorPrimario}">
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Descripción</th>
                        <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Qty</th>
                        <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Precio</th>
                        <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${SAMPLE.items.map((it,i) => `
                    <tr style="background:${i%2===0?'#fff':'#f8fafc'};border-bottom:1px solid #f1f5f9">
                        <td style="padding:11px 14px;font-size:12px;color:#475569">${it.desc}</td>
                        <td style="padding:11px 14px;font-size:12px;color:#475569;text-align:center">${it.qty}</td>
                        <td style="padding:11px 14px;font-size:12px;color:#475569;text-align:right">${fmtCOP(it.price)}</td>
                        <td style="padding:11px 14px;font-size:12px;font-weight:700;color:#0f172a;text-align:right">${fmtCOP(it.qty*it.price)}</td>
                    </tr>`).join('')}
                </tbody>
            </table>

            <!-- Totales ejecutiva -->
            <div style="display:flex;justify-content:flex-end;margin-top:20px">
                <div style="min-width:250px">
                    <div style="display:flex;justify-content:space-between;padding:6px 12px;font-size:12px;color:#64748b"><span>Subtotal</span><span>${fmtCOP(sub)}</span></div>
                    <div style="display:flex;justify-content:space-between;padding:6px 12px;font-size:12px;color:#64748b"><span>Descuento</span><span>- ${fmtCOP(desc)}</span></div>
                    <div style="display:flex;justify-content:space-between;padding:6px 12px;font-size:12px;color:#64748b"><span>IVA (19%)</span><span>${fmtCOP(iva)}</span></div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 12px;background:${cfg.colorPrimario};border-radius:8px;margin-top:10px">
                        <span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:${cfg.colorSecundario}">Total a pagar</span>
                        <span style="font-size:18px;font-weight:900;color:#fff">${fmtCOP(total)}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie ejecutiva -->
        <div style="background:${cfg.colorPrimario};padding:14px 36px;display:flex;justify-content:space-between;align-items:center">
            <div style="font-size:11px;color:rgba(255,255,255,.5)">${cfg.notasPie}</div>
            <div style="width:40px;height:4px;background:${cfg.colorSecundario};border-radius:2px"></div>
        </div>
    </div>`;
}

/* ═══════════════════════════════════════════════════════ CRUD */
function cargarPlantillas() {
    fetch('api/plantillas_factura.php')
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.error);
            renderPlantillas(res.data);
        })
        .catch(err => {
            document.getElementById('plantillasGrid').innerHTML =
                `<div style="text-align:center;padding:24px;color:#ef4444;font-size:12px">${err.message}</div>`;
        });
}

function renderPlantillas(arr) {
    const grid = document.getElementById('plantillasGrid');
    if (!arr.length) {
        grid.innerHTML = `<div style="text-align:center;padding:24px;color:#94a3b8;font-size:12px;background:#f8fafc;border-radius:10px;border:1.5px dashed #e2e8f0">Sin plantillas. Crea tu primera diseño.</div>`;
        return;
    }

    const layoutLabels = { clasica:'Clásica', moderna:'Moderna', minimalista:'Minimalista', ejecutiva:'Ejecutiva' };

    grid.innerHTML = arr.map(p => `
    <div style="background:#fff;border:1.5px solid ${p.es_default ? p.color_primario : '#e2e8f0'};border-radius:12px;overflow:hidden;transition:all .12s"
         onmouseenter="this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'" onmouseleave="this.style.boxShadow='none'">
        <!-- Franja color top -->
        <div style="height:5px;background:linear-gradient(90deg,${p.color_primario},${p.color_secundario})"></div>
        <div style="padding:12px 14px">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                <div style="display:flex;align-items:center;gap:8px;min-width:0">
                    <div style="width:28px;height:28px;border-radius:6px;background:${p.color_primario};flex-shrink:0"></div>
                    <div style="min-width:0">
                        <div style="font-size:13px;font-weight:800;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${p.nombre}</div>
                        <div style="font-size:10px;color:#94a3b8">${layoutLabels[p.layout_tipo] || p.layout_tipo} · ${p.fuente}</div>
                    </div>
                </div>
                ${p.es_default ? `<span style="background:#c9f31d;color:#0f172a;padding:2px 8px;border-radius:20px;font-size:9px;font-weight:800;white-space:nowrap;flex-shrink:0">★ Default</span>` : ''}
            </div>
            <div style="display:flex;gap:4px;margin-top:10px">
                <button onclick="cargarEnForm(${p.id})"
                    style="flex:1;padding:6px;background:#f1f5f9;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;color:#475569" onmouseenter="this.style.background='#e2e8f0'" onmouseleave="this.style.background='#f1f5f9'">Editar</button>
                ${!p.es_default ? `<button onclick="setComoDefault(${p.id})" title="Establecer como predeterminada"
                    style="padding:6px 8px;background:#f0fdf4;border:none;border-radius:6px;font-size:11px;cursor:pointer;color:#16a34a" onmouseenter="this.style.background='#dcfce7'" onmouseleave="this.style.background='#f0fdf4'">★</button>` : ''}
                ${!p.es_default ? `<button onclick="confirmarEliminarPlantilla(${p.id},'${p.nombre.replace(/'/g,"&#39;")}')"
                    style="padding:6px 8px;background:#fff;border:1.5px solid #fecaca;border-radius:6px;font-size:11px;cursor:pointer;color:#ef4444" onmouseenter="this.style.background='#fee2e2'" onmouseleave="this.style.background='#fff'">✕</button>` : ''}
            </div>
        </div>
    </div>`).join('');
}

function abrirFormNueva() {
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
    document.getElementById('pfEsDefault').checked     = false;
    previewLogo(''); // ocultar preview del logo
    setLayout('clasica', document.getElementById('layout-clasica'));
}

async function cargarEnForm(id) {
    try {
        const res = await fetch(`api/plantillas_factura.php?id=${id}`).then(r => r.json());
        if (!res.success) throw new Error(res.error);
        const p = res.data;
        pfState.editId = p.id;
        pfState.layoutTipo = p.layout_tipo || 'clasica';

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
        document.getElementById('pfEsDefault').checked      = !!p.es_default;

        // Actualizar botones de layout
        document.querySelectorAll('[id^="layout-"]').forEach(b => {
            b.style.border = '2px solid #e2e8f0'; b.style.background = '#fff'; b.style.color = '#64748b';
        });
        const btnActivo = document.getElementById(`layout-${pfState.layoutTipo}`);
        if (btnActivo) { btnActivo.style.border='2px solid #0f172a'; btnActivo.style.background='#0f172a'; btnActivo.style.color='#fff'; }

        actualizarPreview();
    } catch (err) {
        if (typeof showToast === 'function') showToast('Error al cargar plantilla', 'error');
    }
}

async function guardarPlantilla() {
    const nombreInput = document.getElementById('pfNombre');
    let nombre = nombreInput.value.trim();

    // Si el nombre está vacío, auto-completar con el diseño seleccionado
    if (!nombre) {
        const nombresLayout = { clasica: 'Clásica', moderna: 'Moderna', minimalista: 'Minimalista', ejecutiva: 'Ejecutiva' };
        nombre = nombresLayout[pfState.layoutTipo] || 'Mi Plantilla';
        nombreInput.value = nombre;
    }

    const btn = document.getElementById('pfBtnGuardar');
    btn.disabled = true;
    btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" class="spin"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Guardando...`;

    const id = parseInt(document.getElementById('pfId').value, 10) || null;
    const payload = {
        nombre,
        layout_tipo:      pfState.layoutTipo,
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
        cargarPlantillas();
        if (!id) abrirFormNueva();
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
