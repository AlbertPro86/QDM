<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

// ── Modo edición ─────────────────────────────────────────────────────────────
$editId   = intval($_GET['edit'] ?? 0);
$editData = null;
if ($editId) {
    $pdo = db();
    $st  = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
    $st->execute([$editId]);
    $editData = $st->fetch(PDO::FETCH_ASSOC);
    if (!$editData) $editId = 0;
}

$pageTitle    = $editId ? 'Editar Cotización' : 'Cotizador';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Cotizador</span>';
include __DIR__ . '/includes/header.php';
?>


<!-- ── Edit data injected ────────────────────────────────────── -->
<?php if($editData): ?>
<script>window._editData = <?= json_encode($editData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<?php endif; ?>


<!-- ── Recipient Strip ──────────────────────────────────────── -->
<div id="destinatarioStrip" style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:auto 1fr auto;gap:16px;align-items:center">

        <!-- Tipo de destinatario -->
        <div>
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);margin-bottom:10px">Tipo</div>
            <div style="display:flex;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);overflow:hidden">
                <button onclick="setTipo('cliente')" id="tipoBtnCliente" class="tipo-btn" style="padding:8px 14px;border:none;background:#000;color:#c9f31d;font-size:11px;font-weight:700;cursor:pointer;transition:all .15s;white-space:nowrap">Cliente</button>
                <button onclick="setTipo('lead')" id="tipoBtnLead" class="tipo-btn" style="padding:8px 14px;border:none;background:#fff;color:var(--color-text-muted);font-size:11px;font-weight:700;cursor:pointer;transition:all .15s;border-left:1.5px solid var(--color-border);white-space:nowrap">Lead</button>
            </div>
        </div>

        <!-- Búsqueda / datos -->
        <div>
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);margin-bottom:10px">Destinatario</div>
            <div id="destinatarioBuscador" style="position:relative">
                <div style="position:relative">
                    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--color-text-light)" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" id="destinatarioInput" placeholder="Buscar por nombre, email o teléfono..."
                        style="width:100%;padding:9px 14px 9px 36px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;font-family:inherit;outline:none;transition:border-color .15s"
                        onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'"
                        oninput="buscarDestinatario()">
                    <div id="destinatarioDropdown" style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid var(--color-border);border-top:none;border-radius:0 0 var(--radius-sm) var(--radius-sm);max-height:220px;overflow-y:auto;display:none;z-index:20;box-shadow:var(--shadow-lg)"></div>
                </div>
            </div>
            <div id="destinatarioManual" style="display:none;margin-top:12px;display:none">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                    <div>
                        <input type="text" id="manualNombre" placeholder="Nombre completo *" style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;transition:border-color .15s" onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                    <div>
                        <input type="email" id="manualEmail" placeholder="Email" style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;transition:border-color .15s" onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                    <div>
                        <input type="text" id="manualWhatsapp" placeholder="WhatsApp (con código país)" style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;transition:border-color .15s" onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                </div>
            </div>
        </div>

        <!-- Info del seleccionado -->
        <div id="destInfoBox" style="display:none;min-width:200px">
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);margin-bottom:10px">Seleccionado</div>
            <div style="padding:10px 14px;background:var(--color-surface);border-radius:var(--radius-sm);border-left:3px solid var(--color-primary)">
                <div style="font-weight:800;font-size:13px;color:var(--color-text);margin-bottom:3px" id="destNombreBox"></div>
                <div style="font-size:11px;color:var(--color-text-muted)" id="destEmailBox"></div>
                <div style="font-size:11px;color:var(--color-text-muted)" id="destWABox"></div>
                <button onclick="limpiarDestinatario()" style="margin-top:6px;font-size:10px;font-weight:700;color:var(--color-text-muted);background:none;border:none;cursor:pointer;padding:0;text-decoration:underline">Cambiar</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Main Grid: Catalog + Quote Builder ───────────────────── -->
<div style="display:grid;grid-template-columns:340px 1fr;gap:16px;align-items:start">

    <!-- ══ PANEL IZQUIERDO: Catálogo ══════════════════════════ -->
    <div style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);overflow:hidden;position:sticky;top:20px">

        <!-- Header catálogo -->
        <div style="padding:16px 20px;border-bottom:1.5px solid var(--color-border);background:var(--color-primary)">
            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#c9f31d;margin-bottom:2px">Catálogo</div>
            <div style="font-size:13px;color:#fff;font-weight:600">Selecciona los servicios</div>
        </div>

        <!-- Search catálogo -->
        <div style="padding:12px 16px;border-bottom:1px solid var(--color-border)">
            <div style="position:relative">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--color-text-light)" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="catalogSearch" placeholder="Filtrar servicios..." oninput="filtrarCatalogo()"
                    style="width:100%;padding:7px 12px 7px 30px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;transition:border-color .15s"
                    onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'">
            </div>
        </div>

        <!-- Tabs catálogo -->
        <div style="display:flex;border-bottom:1.5px solid var(--color-border)">
            <button class="cat-tab active-tab" onclick="cambiarTab('subservicios',this)" data-tab="subservicios"
                style="flex:1;padding:10px 8px;border:none;background:none;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;cursor:pointer;color:var(--color-primary);border-bottom:2px solid var(--color-primary);transition:all .15s;font-family:inherit">Sub-servicios</button>
            <button class="cat-tab" onclick="cambiarTab('paquetes',this)" data-tab="paquetes"
                style="flex:1;padding:10px 8px;border:none;background:none;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;cursor:pointer;color:var(--color-text-muted);border-bottom:2px solid transparent;transition:all .15s;font-family:inherit">Paquetes</button>
        </div>

        <!-- Contenido catálogo -->
        <div style="max-height:480px;overflow-y:auto;padding:12px">
            <div id="catSubservicios" class="cat-panel" style="display:flex;flex-direction:column;gap:8px">
                <div style="text-align:center;padding:40px 0;color:var(--color-text-light);font-size:12px">Cargando catálogo...</div>
            </div>
            <div id="catPaquetes" class="cat-panel" style="display:none;flex-direction:column;gap:8px"></div>
        </div>
    </div>

    <!-- ══ PANEL DERECHO: Constructor de cotización ═══════════ -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Items de la cotización -->
        <div style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);overflow:hidden">

            <!-- Header items -->
            <div style="padding:16px 24px;border-bottom:1.5px solid var(--color-border);display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-size:15px;font-weight:800;color:var(--color-text)">Items de la cotización</div>
                    <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px"><span id="countItems">0</span> servicios añadidos</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <button onclick="addItemManual()" style="display:flex;align-items:center;gap:6px;padding:8px 14px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);background:#fff;font-size:11px;font-weight:700;cursor:pointer;color:var(--color-text-muted);transition:all .15s;font-family:inherit" onmouseenter="this.style.borderColor='var(--color-primary)';this.style.color='var(--color-primary)'" onmouseleave="this.style.borderColor='var(--color-border)';this.style.color='var(--color-text-muted)'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12"/></svg>
                        Línea personalizada
                    </button>
                    <button onclick="limpiarItems()" style="display:flex;align-items:center;gap-6px;padding:8px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);background:#fff;font-size:11px;font-weight:700;cursor:pointer;color:var(--color-text-muted);transition:all .15s;font-family:inherit" onmouseenter="this.style.borderColor='var(--color-danger)';this.style.color='var(--color-danger)'" onmouseleave="this.style.borderColor='var(--color-border)';this.style.color='var(--color-text-muted)'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Tabla de items -->
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;min-width:700px" id="itemsTable">
                    <thead>
                        <tr style="background:var(--color-surface)">
                            <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);font-family:inherit;width:38%">Descripción</th>
                            <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);font-family:inherit;width:10%">Ciclo</th>
                            <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);font-family:inherit;width:8%">Cant.</th>
                            <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);font-family:inherit;width:14%">Precio unit.</th>
                            <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);font-family:inherit;width:10%">Desc. %</th>
                            <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);font-family:inherit;width:13%">Total</th>
                            <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);font-family:inherit;width:7%"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody">
                        <tr id="emptyRow">
                            <td colspan="7" style="padding:56px 0;text-align:center">
                                <div style="display:flex;flex-direction:column;align-items:center;gap:10px">
                                    <div style="width:48px;height:48px;border-radius:50%;background:var(--color-surface);border:1.5px dashed var(--color-border);display:flex;align-items:center;justify-content:center">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="color:var(--color-text-light)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div style="font-size:13px;font-weight:600;color:var(--color-text-muted)">Sin items aún</div>
                                    <div style="font-size:12px;color:var(--color-text-light)">Selecciona servicios del catálogo o agrega una línea personalizada</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Notas internas del item / área de notas -->
            <div style="padding:16px 24px;border-top:1.5px solid var(--color-border);background:var(--color-surface)">
                <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);display:block;margin-bottom:8px">Notas y condiciones de la cotización</label>
                <textarea id="notas" placeholder="Ej: Aplica 50% anticipo. Entrega en 30 días hábiles. Precio no incluye IVA..."
                    style="width:100%;padding:10px 14px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;resize:vertical;min-height:72px;outline:none;transition:border-color .15s;background:#fff"
                    onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'"></textarea>
            </div>
        </div>

        <!-- Totales + Acciones -->
        <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">

            <!-- Config adicional -->
            <div style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:20px">
                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);margin-bottom:14px">Configuración</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:6px">Vigencia</label>
                        <select id="vigencia" style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;transition:border-color .15s;cursor:pointer" onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'">
                            <option value="7">7 días</option>
                            <option value="15" selected>15 días</option>
                            <option value="30">30 días</option>
                            <option value="60">60 días</option>
                            <option value="90">90 días</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:6px">Moneda</label>
                        <div style="padding:9px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;background:var(--color-surface);color:var(--color-text);font-weight:600">COP — Precio Colombia</div>
                        <input type="hidden" id="moneda" value="COP">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:6px">Descuento global</label>
                        <input type="number" id="descuentoGlobal" value="0" min="0" step="1" onchange="recalcular()" placeholder="0"
                            style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;transition:border-color .15s"
                            onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:6px">Estado</label>
                        <div style="padding:9px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;background:var(--color-surface);color:var(--color-text);font-weight:600">Pendiente por aprobar</div>
                        <input type="hidden" id="estadoCot" value="pendiente">
                    </div>
                </div>
            </div>

            <!-- Totales + Botones -->
            <div style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);overflow:hidden">
                <div style="padding:16px 20px;border-bottom:1px solid var(--color-border)">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span style="font-size:12px;color:var(--color-text-muted)">Subtotal</span>
                        <span style="font-size:13px;font-weight:700;color:var(--color-text)" id="totSubtotal">$ 0.00</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span style="font-size:12px;color:var(--color-text-muted)">Descuentos</span>
                        <span style="font-size:13px;font-weight:700;color:var(--color-danger)" id="totDescuento">- $ 0.00</span>
                    </div>
                    <div style="height:1px;background:var(--color-border);margin:12px 0"></div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:14px;font-weight:800;color:var(--color-text)">TOTAL</span>
                        <span style="font-size:20px;font-weight:900;color:var(--color-primary)" id="totTotal">$ 0.00</span>
                    </div>
                    <div style="font-size:10px;color:var(--color-text-light);text-align:right;margin-top:4px" id="totMonedaLabel">en USD</div>
                </div>

                <!-- Número de cotización -->
                <div style="padding:12px 20px;background:var(--color-surface);border-bottom:1px solid var(--color-border)">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)"># Cotización</span>
                        <span style="font-size:12px;font-weight:800;color:var(--color-primary)" id="previewNumero">COT-XXXX</span>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:8px">
                    <button id="btnGuardar" onclick="guardarCotizacion()" style="width:100%;padding:12px;background:var(--color-primary);color:#c9f31d;border:none;border-radius:var(--radius-sm);font-weight:800;font-size:13px;cursor:pointer;transition:all .15s;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px" onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='var(--color-primary)'">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                        Guardar cotización
                    </button>

                    <!-- Acciones post-guardado -->
                    <div id="accionesPostGuardado" style="display:none;flex-direction:column;gap:6px">
                        <button onclick="abrirVista()" style="width:100%;padding:10px;border:1.5px solid var(--color-primary);background:#fff;color:var(--color-primary);border-radius:var(--radius-sm);font-weight:700;font-size:12px;cursor:pointer;transition:all .15s;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:7px" onmouseenter="this.style.background='var(--color-primary)';this.style.color='#c9f31d'" onmouseleave="this.style.background='#fff';this.style.color='var(--color-primary)'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Ver e imprimir PDF
                        </button>
                        <button onclick="enviarWA()" style="width:100%;padding:10px;border:1.5px solid #c9f31d;background:#c9f31d;color:#000;border-radius:var(--radius-sm);font-weight:700;font-size:12px;cursor:pointer;transition:all .15s;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:7px" onmouseenter="this.style.background='#b8dc1f';this.style.borderColor='#b8dc1f'" onmouseleave="this.style.background='#c9f31d';this.style.borderColor='#c9f31d'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.553 4.112 1.522 5.842L.057 23.926a.5.5 0 00.617.617l6.084-1.465A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.898 0-3.67-.523-5.178-1.432l-.37-.223-3.837.924.94-3.837-.241-.384A9.944 9.944 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                            Enviar por WhatsApp
                        </button>
                        <button onclick="enviarEmail()" style="width:100%;padding:10px;border:1.5px solid var(--color-border);background:#fff;color:var(--color-text-muted);border-radius:var(--radius-sm);font-weight:700;font-size:12px;cursor:pointer;transition:all .15s;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:7px" onmouseenter="this.style.borderColor='var(--color-primary)';this.style.color='var(--color-primary)'" onmouseleave="this.style.borderColor='var(--color-border)';this.style.color='var(--color-text-muted)'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Enviar por email
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /grid totales -->
    </div><!-- /panel derecho -->
</div><!-- /main grid -->

<!-- ── Estilos locales ───────────────────────────────────────── -->
<style>
.cat-tab { font-family: var(--font-secondary) !important; }
.cat-tab.active-tab {
    color: var(--color-primary) !important;
    border-bottom-color: var(--color-primary) !important;
    background: var(--color-surface) !important;
}
.cat-panel { display: flex; flex-direction: column; }
/* Item card del catálogo */
.catalog-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.15s;
    background: #fff;
}
.catalog-item:hover { border-color: var(--color-primary); background: var(--color-surface); }
.catalog-item:hover .cat-add-btn { background: var(--color-primary); color: #c9f31d; }
.catalog-item.added { border-color: var(--color-primary); background: var(--color-surface); }
.cat-add-btn {
    width: 28px; height: 28px; min-width: 28px;
    border: 1.5px solid var(--color-border);
    border-radius: 6px;
    background: #fff;
    color: var(--color-text);
    font-weight: 800;
    font-size: 16px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
    flex-shrink: 0;
}
/* Fila de item en tabla */
.item-row { border-bottom: 1px solid var(--color-border-light); transition: background .1s; }
.item-row:last-child { border-bottom: none; }
.item-row:hover { background: var(--color-surface); }
.item-row td { padding: 12px 12px; vertical-align: middle; }
.item-row td:first-child { padding-left: 20px; }
.item-row td:last-child { padding-right: 16px; }
.item-input {
    width: 100%;
    padding: 6px 8px;
    border: 1.5px solid transparent;
    border-radius: 6px;
    font-size: 12px;
    font-family: inherit;
    text-align: center;
    background: transparent;
    transition: all .15s;
    outline: none;
}
.item-input:hover { border-color: var(--color-border); background: #fff; }
.item-input:focus { border-color: var(--color-primary); background: #fff; }
.item-input.left { text-align: left; }
.item-remove {
    width: 26px; height: 26px;
    border: none;
    background: transparent;
    color: var(--color-text-light);
    border-radius: 6px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
    margin: 0 auto;
}
.item-remove:hover { background: var(--color-danger-bg); color: var(--color-danger); }
/* Badges de frecuencia */
.freq-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    background: var(--color-surface);
    color: var(--color-text-muted);
    border: 1px solid var(--color-border);
    white-space: nowrap;
}
/* Grupo por categoría en catálogo */
.cat-group-header {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--color-text-muted);
    padding: 8px 4px 4px;
    margin-top: 6px;
}
.cat-group-header:first-child { margin-top: 0; }
</style>

<!-- ── JavaScript ────────────────────────────────────────────── -->
<script>
/* ═══════════════════════════════════════════════
   Estado central
═══════════════════════════════════════════════ */
const cot = {
    destinatario: { tipo: 'cliente', id: null, nombre: '', email: '', whatsapp: '' },
    items: [],
    savedId: null,
    savedNumero: null
};

let _catalogoServicios    = [];
let _catalogoSubservicios = [];
let _catalogoPaquetes     = [];
let _currentTab           = 'servicios';

/* ═══════════════════════════════════════════════
   Init
═══════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    setTipo('cliente');
    cargarCatalogo();
    document.getElementById('descuentoGlobal').addEventListener('input', recalcular);
    document.getElementById('moneda').addEventListener('change', recalcular);
});

/* ═══════════════════════════════════════════════
   Tipo de destinatario
═══════════════════════════════════════════════ */
function setTipo(tipo) {
    cot.destinatario.tipo = tipo;
    cot.destinatario.id = null;

    // Estilos de botones
    ['cliente','lead'].forEach(t => {
        const btn = document.getElementById(`tipoBtn${t.charAt(0).toUpperCase()+t.slice(1)}`);
        if (t === tipo) {
            btn.style.background = '#000';
            btn.style.color = '#c9f31d';
        } else {
            btn.style.background = '#fff';
            btn.style.color = 'var(--color-text-muted)';
        }
    });

    // Mostrar buscador
    const buscador = document.getElementById('destinatarioBuscador');
    buscador.style.display = 'block';
    document.getElementById('destinatarioInput').value = '';
    document.getElementById('destinatarioInput').placeholder =
        tipo === 'cliente' ? 'Buscar cliente activo...' : 'Buscar lead por nombre o teléfono...';
    document.getElementById('destInfoBox').style.display = 'none';
}

function limpiarDestinatario() {
    cot.destinatario.id = null; cot.destinatario.nombre = ''; cot.destinatario.email = ''; cot.destinatario.whatsapp = '';
    document.getElementById('destInfoBox').style.display = 'none';
    document.getElementById('destinatarioBuscador').style.display = 'block';
    document.getElementById('destinatarioInput').value = '';
}

/* ═══════════════════════════════════════════════
   Búsqueda de destinatario
═══════════════════════════════════════════════ */
let _searchTimer;
function buscarDestinatario() {
    clearTimeout(_searchTimer);
    const q = document.getElementById('destinatarioInput').value.trim();
    if (q.length < 2) {
        document.getElementById('destinatarioDropdown').style.display = 'none';
        return;
    }
    _searchTimer = setTimeout(async () => {
        try {
            const tipo = cot.destinatario.tipo === 'lead' ? '&solo=lead' : '&solo=cliente';
            const r = await fetch(`api/cotizaciones.php?q=${encodeURIComponent(q)}${tipo}`);
            const d = await r.json();
            const dd = document.getElementById('destinatarioDropdown');
            if (d.success && d.data.length > 0) {
                dd.innerHTML = d.data.map(it => `
                    <div style="padding:10px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .1s"
                        onmouseenter="this.style.background='var(--color-surface)'" onmouseleave="this.style.background=''"
                        onclick="seleccionarDestinatario('${it.tipo}',${it.id},'${esc(it.nombre)}','${esc(it.email||'')}','${esc(it.whatsapp||'')}')">
                        <div style="font-weight:700;font-size:12px;color:var(--color-text)">${hesc(it.nombre)}</div>
                        <div style="font-size:10px;margin-top:2px;color:var(--color-text-muted)">${it.tipo === 'cliente' ? 'Cliente' : 'Lead'}${it.email ? ' · ' + hesc(it.email) : ''}</div>
                    </div>`).join('');
                dd.style.display = 'block';
            } else {
                dd.innerHTML = '<div style="padding:12px 14px;font-size:12px;color:var(--color-text-muted)">Sin resultados</div>';
                dd.style.display = 'block';
            }
        } catch(e) {}
    }, 280);
}

function seleccionarDestinatario(tipo, id, nombre, email, whatsapp) {
    cot.destinatario = { tipo, id, nombre, email, whatsapp };
    document.getElementById('destinatarioBuscador').style.display = 'none';
    document.getElementById('destNombreBox').textContent = nombre;
    document.getElementById('destEmailBox').textContent = email || 'Sin email';
    document.getElementById('destWABox').textContent = whatsapp || 'Sin WhatsApp';
    document.getElementById('destInfoBox').style.display = 'block';
}

// Cerrar dropdown al clic fuera
document.addEventListener('click', e => {
    if (!e.target.closest('#destinatarioBuscador')) {
        document.getElementById('destinatarioDropdown').style.display = 'none';
    }
});

/* ═══════════════════════════════════════════════
   Catálogo: carga desde API de servicios
═══════════════════════════════════════════════ */
async function cargarCatalogo() {
    try {
        const [rSvc, rPkg] = await Promise.all([
            fetch('api/servicios.php?activo=1'),
            fetch('api/paquetes.php?activo=1')
        ]);
        const [dSvc, dPkg] = await Promise.all([rSvc.json(), rPkg.json()]);

        console.log('🔍 SERVICIOS CARGADOS:', dSvc.data);
        console.log('📦 PAQUETES CARGADOS:', dPkg.data);

        if (dSvc.success) {
            _catalogoServicios = dSvc.data || [];
            // Extraer sub-servicios de todos los servicios
            _catalogoSubservicios = [];
            _catalogoServicios.forEach(svc => {
                if (svc.sub_servicios && svc.sub_servicios.length > 0) {
                    svc.sub_servicios.forEach(sub => {
                        _catalogoSubservicios.push({ ...sub, _parent: svc.nombre });
                    });
                }
            });
        }
        if (dPkg.success) _catalogoPaquetes = dPkg.data || [];

        renderCatalogo();
    } catch(e) {
        document.getElementById('catSubservicios').innerHTML = '<div style="text-align:center;padding:30px;color:var(--color-text-light);font-size:12px">Error cargando catálogo</div>';
        console.error('Error catálogo:', e);
    }
}

function renderCatalogo(filtro) {
    const f = (filtro || '').toLowerCase();

    // Sub-servicios
    const subFiltrados = _catalogoSubservicios.filter(s => !f || s.nombre.toLowerCase().includes(f) || (s._parent||'').toLowerCase().includes(f));
    const subHtml = subFiltrados.length === 0
        ? '<div style="text-align:center;padding:40px 0;color:var(--color-text-light);font-size:12px">Sin sub-servicios</div>'
        : subFiltrados.map(s => catalogItemHtml('subservicio', s.id, s.nombre, `${s._parent}`, s.frecuencia||'mes', s.precio||0)).join('');
    document.getElementById('catSubservicios').innerHTML = subHtml;

    // Paquetes
    const pkgFiltrados = _catalogoPaquetes.filter(p => !f || p.nombre.toLowerCase().includes(f));
    const pkgHtml = pkgFiltrados.length === 0
        ? '<div style="text-align:center;padding:40px 0;color:var(--color-text-light);font-size:12px">Sin paquetes</div>'
        : pkgFiltrados.map(p => catalogItemHtml('paquete', p.id, p.nombre, p.descripcion, p.frecuencia||'mes', p.precio_venta||p.precio||0)).join('');
    document.getElementById('catPaquetes').innerHTML = pkgHtml;
}

function catalogItemHtml(tipo, id, nombre, desc, frecuencia, precio) {
    return `<div class="catalog-item" onclick="addItemCatalogo('${tipo}',${id},'${esc(nombre)}','${esc(desc||'')}','${esc(frecuencia)}',${precio})">
        <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:12px;color:var(--color-text);margin-bottom:2px">${hesc(nombre)}</div>
            ${desc ? `<div style="font-size:10px;color:var(--color-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${hesc(desc)}</div>` : ''}
            <div style="margin-top:4px;display:flex;align-items:center;gap:6px">
                <span style="font-size:12px;font-weight:800;color:var(--color-primary)">${formatM(precio, 'COP')}</span>
                <span class="freq-badge">${labelFrecuencia(frecuencia)}</span>
            </div>
        </div>
        <button class="cat-add-btn" onclick="event.stopPropagation();addItemCatalogo('${tipo}',${id},'${esc(nombre)}','${esc(desc||'')}','${esc(frecuencia)}',${precio})">+</button>
    </div>`;
}

function filtrarCatalogo() {
    renderCatalogo(document.getElementById('catalogSearch').value);
}

/* ═══════════════════════════════════════════════
   Tabs del catálogo
═══════════════════════════════════════════════ */
function cambiarTab(tab, btnEl) {
    _currentTab = tab;
    document.querySelectorAll('.cat-tab').forEach(b => {
        b.classList.remove('active-tab');
        b.style.color = 'var(--color-text-muted)';
        b.style.borderBottomColor = 'transparent';
        b.style.background = '#fff';
    });
    btnEl.classList.add('active-tab');
    btnEl.style.color = 'var(--color-primary)';
    btnEl.style.borderBottomColor = 'var(--color-primary)';
    btnEl.style.background = 'var(--color-surface)';
    document.querySelectorAll('.cat-panel').forEach(p => p.style.display = 'none');
    const map = { servicios: 'catServicios', subservicios: 'catSubservicios', paquetes: 'catPaquetes' };
    const el = document.getElementById(map[tab]);
    el.style.display = 'flex';
    el.style.flexDirection = 'column';
}

/* ═══════════════════════════════════════════════
   Items
═══════════════════════════════════════════════ */
let _itemCounter = 0;

function addItemCatalogo(tipo, refId, nombre, descripcion, frecuencia, precio) {
    _itemCounter++;
    cot.items.push({
        _uid: _itemCounter,
        tipo, ref_id: refId, nombre, descripcion,
        frecuencia, precio_unit: parseFloat(precio)||0,
        cantidad: 1, descuento_pct: 0
    });
    renderItems();
    recalcular();
    showToast(`${nombre} añadido a la cotización`, 'success', 2000);
}

function addItemManual() {
    _itemCounter++;
    cot.items.push({
        _uid: _itemCounter,
        tipo: 'manual', ref_id: null,
        nombre: 'Servicio personalizado',
        descripcion: '', frecuencia: 'unico',
        precio_unit: 0, cantidad: 1, descuento_pct: 0
    });
    renderItems();
    recalcular();
}

function removeItem(uid) {
    cot.items = cot.items.filter(i => i._uid !== uid);
    renderItems();
    recalcular();
}

function limpiarItems() {
    if (cot.items.length === 0) return;
    // Confirmar con doble click (click en botón → aviso, segundo click → limpia)
    const btn = event.currentTarget;
    if (!btn._confirmando) {
        btn._confirmando = true;
        const textoOrig = btn.innerHTML;
        btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.054 0 1.93-.816 1.997-1.85l.659-11.1A2 2 0 0019.575 5H4.425A2 2 0 002.428 7.05L3.087 18.15C3.154 19.184 4.03 20 5.083 20z"/></svg>';
        btn.style.borderColor = 'var(--color-danger)';
        btn.style.color = 'var(--color-danger)';
        showToast('Haz clic de nuevo para eliminar todos los items', 'warning', 2500);
        setTimeout(() => {
            btn._confirmando = false;
            btn.innerHTML = textoOrig;
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2500);
        return;
    }
    btn._confirmando = false;
    cot.items = [];
    renderItems();
    recalcular();
}

function updateField(uid, field, val) {
    const item = cot.items.find(i => i._uid === uid);
    if (!item) return;
    if (field === 'precio_unit' || field === 'descuento_pct') val = parseFloat(val)||0;
    if (field === 'cantidad') val = Math.max(1, parseInt(val)||1);
    item[field] = val;
    recalcular();
    // Actualizar solo el total de esa fila
    const total = calcItemTotal(item);
    const el = document.getElementById(`totRow_${uid}`);
    if (el) el.textContent = formatM(total);
}

function calcItemTotal(item) {
    const bruto = item.precio_unit * item.cantidad;
    return bruto - (bruto * (item.descuento_pct / 100));
}

function renderItems() {
    const tbody = document.getElementById('itemsTbody');
    const emptyRow = document.getElementById('emptyRow');
    document.getElementById('countItems').textContent = cot.items.length;

    if (cot.items.length === 0) {
        tbody.innerHTML = `<tr id="emptyRow">
            <td colspan="7" style="padding:56px 0;text-align:center">
                <div style="display:flex;flex-direction:column;align-items:center;gap:10px">
                    <div style="width:48px;height:48px;border-radius:50%;background:var(--color-surface);border:1.5px dashed var(--color-border);display:flex;align-items:center;justify-content:center">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="color:var(--color-text-light)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div style="font-size:13px;font-weight:600;color:var(--color-text-muted)">Sin items aún</div>
                    <div style="font-size:12px;color:var(--color-text-light)">Selecciona servicios del catálogo o agrega una línea personalizada</div>
                </div>
            </td>
        </tr>`;
        return;
    }

    tbody.innerHTML = cot.items.map(item => {
        const total = calcItemTotal(item);
        return `<tr class="item-row">
            <td>
                <input class="item-input left" style="width:100%;font-weight:600;font-size:12px" value="${hesc(item.nombre)}"
                    onchange="updateField(${item._uid},'nombre',this.value)"
                    placeholder="Descripción del servicio">
                <input class="item-input left" style="width:100%;font-size:11px;color:var(--color-text-muted);margin-top:2px" value="${hesc(item.descripcion||'')}"
                    onchange="updateField(${item._uid},'descripcion',this.value)"
                    placeholder="Detalle adicional (opcional)">
            </td>
            <td style="text-align:center">
                <select class="item-input" onchange="updateField(${item._uid},'frecuencia',this.value)" style="font-size:10px;padding:4px 6px">
                    ${['mes','trimestre','semestre','año','unico','ninguna'].map(f =>
                        `<option value="${f}" ${item.frecuencia===f?'selected':''}>${labelFrecuencia(f)}</option>`
                    ).join('')}
                </select>
            </td>
            <td style="text-align:center">
                <input type="number" class="item-input" value="${item.cantidad}" min="1" style="width:56px"
                    onchange="updateField(${item._uid},'cantidad',this.value)">
            </td>
            <td style="text-align:right">
                <input type="number" class="item-input" value="${item.precio_unit.toFixed(2)}" min="0" step="0.01" style="width:90px;text-align:right"
                    onchange="updateField(${item._uid},'precio_unit',this.value)">
            </td>
            <td style="text-align:right">
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:2px">
                    <input type="number" class="item-input" value="${item.descuento_pct}" min="0" max="100" step="1" style="width:48px;text-align:right"
                        onchange="updateField(${item._uid},'descuento_pct',this.value)">
                    <span style="font-size:11px;color:var(--color-text-muted)">%</span>
                </div>
            </td>
            <td style="text-align:right">
                <span id="totRow_${item._uid}" style="font-size:13px;font-weight:800;color:var(--color-primary)">${formatM(total)}</span>
            </td>
            <td style="text-align:center">
                <button class="item-remove" onclick="removeItem(${item._uid})" title="Eliminar item">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </td>
        </tr>`;
    }).join('');
}

/* ═══════════════════════════════════════════════
   Cálculos
═══════════════════════════════════════════════ */
function recalcular() {
    const subtotal = cot.items.reduce((sum, item) => sum + calcItemTotal(item), 0);
    const descGlobal = parseFloat(document.getElementById('descuentoGlobal').value)||0;
    const total = Math.max(0, subtotal - descGlobal);
    const moneda = document.getElementById('moneda').value;
    const prefix = monedaPrefix(moneda);

    document.getElementById('totSubtotal').textContent = formatM(subtotal, moneda);
    document.getElementById('totDescuento').textContent = '- ' + formatM(descGlobal, moneda);
    document.getElementById('totTotal').textContent = formatM(total, moneda);
    document.getElementById('totMonedaLabel').textContent = moneda === 'COP' ? 'en COP' : `en ${moneda}`;

    // Actualizar totales de filas
    cot.items.forEach(item => {
        const el = document.getElementById(`totRow_${item._uid}`);
        if (el) el.textContent = formatM(calcItemTotal(item), moneda);
    });
}

/* ═══════════════════════════════════════════════
   Guardar
═══════════════════════════════════════════════ */
async function guardarCotizacion() {
    // Validar destinatario
    let destOk = false;
    if (cot.destinatario.tipo === 'nuevo') {
        const n = document.getElementById('manualNombre').value.trim();
        if (!n) { showToast('Ingresa el nombre del contacto', 'warning', 3000); return; }
        cot.destinatario.nombre = n;
        cot.destinatario.email = document.getElementById('manualEmail').value.trim();
        cot.destinatario.whatsapp = document.getElementById('manualWhatsapp').value.trim();
        destOk = true;
    } else {
        destOk = !!cot.destinatario.id;
    }
    if (!destOk) { showToast('Selecciona un cliente o lead', 'warning', 3000); return; }
    if (cot.items.length === 0) { showToast('Agrega al menos un servicio', 'warning', 3000); return; }

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = `<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Guardando...`;

    const subtotal = cot.items.reduce((s, i) => s + calcItemTotal(i), 0);
    const descGlobal = parseFloat(document.getElementById('descuentoGlobal').value)||0;

    const isEdit = !!cot.savedId;
    const payload = {
        cliente_tipo: cot.destinatario.tipo === 'nuevo' ? 'lead' : cot.destinatario.tipo,
        cliente_id: cot.destinatario.id,
        nombre_cliente: cot.destinatario.nombre,
        email: cot.destinatario.email,
        whatsapp: cot.destinatario.whatsapp,
        items: cot.items,
        subtotal, descuento: descGlobal,
        total: Math.max(0, subtotal - descGlobal),
        notas: document.getElementById('notas').value,
        vigencia_dias: parseInt(document.getElementById('vigencia').value),
        moneda: document.getElementById('moneda').value,
        estado: document.getElementById('estadoCot').value
    };
    if (isEdit) payload.id = cot.savedId;

    try {
        const r = await fetch('api/cotizaciones.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.success) {
            cot.savedId     = d.id || cot.savedId;
            cot.savedNumero = d.numero || cot.savedNumero;
            document.getElementById('previewNumero').textContent = cot.savedNumero || 'COT-guardada';
            btn.style.display = 'none';
            document.getElementById('accionesPostGuardado').style.display = 'flex';
            showToast(isEdit ? 'Cotización actualizada' : 'Cotización guardada correctamente', 'success', 3000);
        } else {
            showToast('Error: ' + (d.error || 'No se pudo guardar'), 'error', 4000);
            btn.disabled = false;
            btn.innerHTML = `<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg> ${isEdit ? 'Actualizar cotización' : 'Guardar cotización'}`;
        }
    } catch(e) {
        showToast('Error de conexión', 'error', 4000);
        btn.disabled = false;
        btn.innerHTML = `<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg> ${isEdit ? 'Actualizar cotización' : 'Guardar cotización'}`;
    }
}

/* ═══════════════════════════════════════════════
   Acciones post-guardado
═══════════════════════════════════════════════ */
function abrirVista() {
    if (!cot.savedId) { showToast('Guarda la cotización primero', 'warning'); return; }
    window.open(`cotizacion_vista.php?id=${cot.savedId}`, '_blank');
}

function enviarWA() {
    if (!cot.savedId) { showToast('Guarda la cotización primero', 'warning'); return; }
    const wa = (cot.destinatario.whatsapp||'').replace(/\D/g,'');
    if (!wa) { showToast('No hay número de WhatsApp para este destinatario', 'warning', 3000); return; }
    const url = `${window.location.origin}${window.location.pathname.replace('cotizador.php','')}cotizacion_vista.php?id=${cot.savedId}`;
    const msg = `Hola *${cot.destinatario.nombre}*, te compartimos la cotización *${cot.savedNumero}*:\n${url}\n\nQuedamos atentos. — QUANTUN Digital`;
    window.open(`https://wa.me/${wa}?text=${encodeURIComponent(msg)}`, '_blank');
}

function enviarEmail() {
    if (!cot.savedId) { showToast('Guarda la cotización primero', 'warning'); return; }
    if (!cot.destinatario.email) { showToast('No hay email para este destinatario', 'warning', 3000); return; }
    const url = `${window.location.origin}${window.location.pathname.replace('cotizador.php','')}cotizacion_vista.php?id=${cot.savedId}`;
    const subj = `Cotización ${cot.savedNumero} — QUANTUN Digital`;
    const body = `Hola ${cot.destinatario.nombre},\n\nTe compartimos la cotización solicitada:\n${url}\n\nQuedamos atentos a tus comentarios.\n\nSaludos,\nQUANTUN Digital`;
    window.location.href = `mailto:${cot.destinatario.email}?subject=${encodeURIComponent(subj)}&body=${encodeURIComponent(body)}`;
}

/* ═══════════════════════════════════════════════
   Helpers
═══════════════════════════════════════════════ */
function labelFrecuencia(f) {
    const map = { mes:'Mensual', trimestre:'Trimestral', semestre:'Semestral', año:'Anual', unico:'Único', ninguna:'—' };
    return map[f] || f;
}
function monedaPrefix(m) {
    const map = { USD:'$ ', COP:'$ ', EUR:'€ ', MXN:'MXN ' };
    return map[m] || '$ ';
}
function formatNum(n, moneda) {
    const num = parseFloat(n) || 0;
    // Usar formato español: 1.000,00 para COP (0 decimales), 1,234.56 para otros
    if (moneda === 'COP' || moneda === undefined) {
        // COP sin decimales: $ 1.000
        return num.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    } else {
        // Otros con 2 decimales
        return num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}
function formatM(n, moneda) {
    const m = moneda || (document.getElementById('moneda')?.value || 'COP');
    const prefix = monedaPrefix(m);
    const num = formatNum(n, m);
    return `${prefix}${num}`;
}
function hesc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
function esc(s) { return (s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'\\"'); }

// Animación spinner
const styleEl = document.createElement('style');
styleEl.textContent = `@keyframes spin { to { transform: rotate(360deg); } }`;
document.head.appendChild(styleEl);

// ── Modo edición: pre-cargar datos ────────────────────────────────────────────
if (window._editData) {
    const ed = window._editData;
    // Marcar que es edición
    cot.savedId     = ed.id;
    cot.savedNumero = ed.numero;

    // Esperar a que el catálogo cargue para pre-llenar
    const _origCargar = cargarCatalogo;
    cargarCatalogo = async function() {
        await _origCargar();
        // Destinatario
        cot.destinatario.tipo     = ed.cliente_tipo || 'cliente';
        cot.destinatario.id       = ed.cliente_id   || null;
        cot.destinatario.nombre   = ed.nombre_cliente || '';
        cot.destinatario.email    = ed.email   || '';
        cot.destinatario.whatsapp = ed.whatsapp || '';
        setTipo(cot.destinatario.tipo);
        if (cot.destinatario.nombre) seleccionarDestinatario(cot.destinatario.tipo, cot.destinatario.id, cot.destinatario.nombre, cot.destinatario.email, cot.destinatario.whatsapp);

        // Items
        try {
            const rawItems = typeof ed.items === 'string' ? JSON.parse(ed.items) : (ed.items || []);
            cot.items = rawItems.map(item => ({
                ...item,
                _uid: '_' + Math.random().toString(36).substr(2, 9)
            }));
            renderItems();
            recalcular();
        } catch(e) { console.warn('Error parsing items edit:', e); }

        // Config
        const vigEl = document.getElementById('vigencia');
        if (vigEl && ed.vigencia_dias) vigEl.value = ed.vigencia_dias;
        const descEl = document.getElementById('descuentoGlobal');
        if (descEl && ed.descuento > 0) {
            const pct = ed.subtotal > 0 ? Math.round(ed.descuento / ed.subtotal * 100) : 0;
            descEl.value = pct;
        }
        const notasEl = document.getElementById('notas');
        if (notasEl && ed.notas) notasEl.value = ed.notas;
        recalcular();

        // Mostrar acciones post-guardado si ya tiene ID
        const ag = document.getElementById('accionesPostGuardado');
        if (ag) ag.style.display = 'flex';
        const pg = document.getElementById('previewNumero');
        if (pg) pg.textContent = ed.numero;
    };
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
