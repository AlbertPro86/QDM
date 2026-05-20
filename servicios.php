<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$pageTitle    = 'Servicios';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Catálogo de Servicios</span>';
include __DIR__ . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px" id="svcKpis"></div>

<div class="page-header">
    <div class="page-header-left"><h2 style="font-size:16px;color:var(--color-text-muted);font-weight:500">Administra los servicios y sus variantes</h2></div>
    <div class="page-header-right">
        <button class="btn btn-outline" onclick="openPkgModal()" style="border-color:#6366f1;color:#6366f1" onmouseenter="this.style.background='#6366f1';this.style.color='#fff'" onmouseleave="this.style.background='';this.style.color='#6366f1'">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg>
            Crear Paquete
        </button>
        <button class="btn btn-secondary" onclick="openSvcModal()">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nuevo Servicio
        </button>
    </div>
</div>

<div id="svcsGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
    <div style="text-align:center;padding:60px;color:var(--color-text-light);grid-column:1/-1">Cargando...</div>
</div>

<!-- ── Sección Paquetes ──────────────────────────────────────── -->
<div style="display:flex;align-items:center;gap:12px;margin:36px 0 20px">
    <div style="flex:1;height:1px;background:var(--color-border)"></div>
    <span style="font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.08em;white-space:nowrap">Paquetes</span>
    <div style="flex:1;height:1px;background:var(--color-border)"></div>
</div>
<p style="font-size:13px;color:var(--color-text-muted);margin:0 0 20px">Combina sub-servicios en un paquete con precio calculado automáticamente</p>
<div id="pkgsGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px">
    <div style="text-align:center;padding:40px;color:var(--color-text-light);grid-column:1/-1">Cargando...</div>
</div>

<!-- ── Modal Servicio ─────────────────────────────────────── -->
<div class="modal-overlay" id="svcModal">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <h3 class="modal-title" id="svcModalTitle">Nuevo Servicio</h3>
            <button class="modal-close" onclick="closeSvcModal()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body modal-body-lg">
            <form id="svcForm" onsubmit="saveSvc(event)">
                <input type="hidden" id="svcId">
                <div class="form-group"><label class="form-label">Nombre *</label><input type="text" class="form-input" id="svcNombre" required placeholder="Ej: Dominios, Hosting, Diseño Web..."></div>
                <div class="form-group"><label class="form-label">Descripción</label><textarea class="form-textarea" id="svcDesc" rows="2" placeholder="Descripción breve del servicio..."></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Precio de venta ($)</label>
                        <input type="number" class="form-input" id="svcPrecio" step="0.01" min="0" placeholder="0.00" oninput="calcSvcGanancia()">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Costo (lo que pagas) ($)</label>
                        <input type="number" class="form-input" id="svcCosto" step="0.01" min="0" placeholder="0.00" oninput="calcSvcGanancia()">
                    </div>
                </div>
                <div id="svcGananciaBox" style="display:none;margin-bottom:16px;padding:10px 14px;border-radius:10px;display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:12px;font-weight:700" id="svcGananciaLabel">Ganancia por ciclo</span>
                    <div style="display:flex;align-items:center;gap:12px">
                        <span id="svcGananciaValor" style="font-size:15px;font-weight:900">$ 0</span>
                        <span id="svcGananciaPct" style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px">0%</span>
                    </div>
                </div>
                <div class="form-group"><label class="form-label">Facturación</label>
                    <select class="form-select" id="svcFrecuencia">
                        <option value="mes">Mensual</option>
                        <option value="trimestre">Trimestral</option>
                        <option value="semestre">Semestral</option>
                        <option value="año">Anual</option>
                        <option value="unico">Pago Único</option>
                        <option value="ninguna">Ninguna</option>
                    </select>
                </div>
                <div class="form-group">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--color-text)">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            Enlace de Pago General
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none">
                            <span style="font-size:12px;color:var(--color-text-muted)" id="svcEnlaceToggleLabel">No</span>
                            <span style="position:relative;display:inline-block;width:36px;height:20px">
                                <input type="checkbox" id="svcEnlaceToggle" onchange="toggleSvcEnlace()" style="opacity:0;width:0;height:0;position:absolute">
                                <span id="svcEnlaceTrack" style="position:absolute;inset:0;border-radius:20px;background:#cbd5e1;transition:background .2s;cursor:pointer"></span>
                                <span id="svcEnlaceThumb" style="position:absolute;left:2px;top:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .2s;pointer-events:none;box-shadow:0 1px 3px rgba(0,0,0,0.2)"></span>
                            </span>
                        </label>
                    </div>
                    <div id="svcEnlaceField" style="display:none">
                        <input type="url" class="form-input" id="svcEnlace" placeholder="https://checkout.pagos.com/link">
                    </div>
                </div>
                <!-- Features / Características -->
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="margin-bottom:8px">Características incluidas</label>
                    <div id="svcFeaturesList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px"></div>
                    <div style="display:flex;gap:8px">
                        <input type="text" class="form-input" id="svcFeatureInput" placeholder="Ej: Soporte 24/7, SSL incluido..." style="flex:1" onkeydown="if(event.key==='Enter'){event.preventDefault();addSvcFeature();}">
                        <button type="button" class="btn btn-outline" onclick="addSvcFeature()" style="white-space:nowrap;padding:0 14px">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeSvcModal()">Cancelar</button>
            <button class="btn btn-secondary" onclick="document.getElementById('svcForm').requestSubmit()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal Sub-Servicio ─────────────────────────────────── -->
<div class="modal-overlay" id="subModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header">
            <h3 class="modal-title" id="subModalTitle">Nuevo Sub-Servicio</h3>
            <button class="modal-close" onclick="closeSubModal()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body modal-body-lg">
            <form id="subForm" onsubmit="saveSubSvc(event)">
                <input type="hidden" id="subId">
                <input type="hidden" id="subSvcId">
                <p id="subSvcLabel" style="font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px"></p>
                <div class="form-group"><label class="form-label">Nombre del sub-servicio *</label><input type="text" class="form-input" id="subNombre" required placeholder="Ej: .com, .co, Plan Básico, Plan Pro..."></div>
                <div class="form-group"><label class="form-label">Descripción</label><textarea class="form-textarea" id="subDesc" rows="2" placeholder="Descripción breve del sub-servicio..."></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Precio de venta ($)</label>
                        <input type="number" class="form-input" id="subPrecio" step="0.01" min="0" placeholder="0.00" oninput="calcSubGanancia()">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Costo (lo que pagas) ($)</label>
                        <input type="number" class="form-input" id="subCosto" step="0.01" min="0" placeholder="0.00" oninput="calcSubGanancia()">
                    </div>
                </div>
                <div id="subGananciaBox" style="display:none;margin-top:8px;padding:10px 14px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:12px;font-weight:700;color:#16a34a">Ganancia por ciclo</span>
                    <div style="display:flex;align-items:center;gap:12px">
                        <span id="subGananciaValor" style="font-size:15px;font-weight:900;color:#15803d">$ 0</span>
                        <span id="subGananciaPct" style="font-size:11px;font-weight:700;background:#dcfce7;color:#16a34a;padding:2px 8px;border-radius:20px">0%</span>
                    </div>
                </div>
                <div class="form-group"><label class="form-label">Facturación</label>
                    <select class="form-select" id="subFrecuencia">
                        <option value="mes">Mensual</option>
                        <option value="trimestre">Trimestral</option>
                        <option value="semestre">Semestral</option>
                        <option value="año">Anual</option>
                        <option value="unico">Pago Único</option>
                        <option value="ninguna">Ninguna</option>
                    </select>
                </div>
                <div class="form-group">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                        <span style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--color-text)">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            Enlace de Pago
                        </span>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none">
                            <span style="font-size:12px;color:var(--color-text-muted)" id="subEnlaceToggleLabel">No</span>
                            <span style="position:relative;display:inline-block;width:36px;height:20px">
                                <input type="checkbox" id="subEnlaceToggle" onchange="toggleSubEnlace()" style="opacity:0;width:0;height:0;position:absolute">
                                <span id="subEnlaceTrack" style="position:absolute;inset:0;border-radius:20px;background:#cbd5e1;transition:background .2s;cursor:pointer"></span>
                                <span id="subEnlaceThumb" style="position:absolute;left:2px;top:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .2s;pointer-events:none;box-shadow:0 1px 3px rgba(0,0,0,0.2)"></span>
                            </span>
                        </label>
                    </div>
                    <div id="subEnlaceField" style="display:none">
                        <input type="url" class="form-input" id="subEnlace" placeholder="https://checkout.pagos.com/link">
                    </div>
                </div>
                <!-- Features / Características -->
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="margin-bottom:8px">Características incluidas</label>
                    <div id="subFeaturesList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px"></div>
                    <div style="display:flex;gap:8px">
                        <input type="text" class="form-input" id="subFeatureInput" placeholder="Ej: Renovación automática, WHOIS..." style="flex:1" onkeydown="if(event.key==='Enter'){event.preventDefault();addSubFeature();}">
                        <button type="button" class="btn btn-outline" onclick="addSubFeature()" style="white-space:nowrap;padding:0 14px">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeSubModal()">Cancelar</button>
            <button class="btn btn-secondary" onclick="document.getElementById('subForm').requestSubmit()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal Paquete ──────────────────────────────────────── -->
<div class="modal-overlay" id="pkgModal" onclick="if(event.target===this)closePkgModal()">
    <div class="modal" style="max-width:960px">
        <div class="modal-header">
            <h3 class="modal-title" id="pkgModalTitle">Nuevo Paquete</h3>
            <button class="modal-close" onclick="closePkgModal()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body modal-body-lg">
            <form id="pkgForm" onsubmit="savePkg(event)">
                <input type="hidden" id="pkgId">
                <div style="display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start">
                    <!-- Columna izquierda: formulario -->
                    <div>
                        <div class="form-group"><label class="form-label">Nombre del paquete *</label><input type="text" class="form-input" id="pkgNombre" required placeholder="Ej: Landing Page Completa, Plan Empresarial..."></div>
                        <div class="form-group"><label class="form-label">Descripción</label><textarea class="form-textarea" id="pkgDesc" rows="2" placeholder="Descripción del paquete..."></textarea></div>

                        <!-- Selector de sub-servicios -->
                        <div class="form-group">
                            <label class="form-label" style="margin-bottom:10px">Sub-servicios incluidos</label>
                            <div id="pkgSubsList" style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow-y:auto;border:1.5px solid var(--color-border);border-radius:10px;padding:10px"></div>
                        </div>

                        <!-- Resumen de costos -->
                        <div id="pkgResumen" style="display:none;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:14px 16px">
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px">
                                <div style="text-align:center">
                                    <p style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 4px;letter-spacing:.06em">Costo total</p>
                                    <p id="pkgCostoVal" style="font-size:16px;font-weight:900;color:#ef4444;margin:0">$ 0</p>
                                </div>
                                <div style="text-align:center">
                                    <p style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 4px;letter-spacing:.06em">Precio sugerido</p>
                                    <p id="pkgPrecioSugVal" style="font-size:16px;font-weight:900;color:#10b981;margin:0">$ 0</p>
                                </div>
                                <div style="text-align:center">
                                    <p style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 4px;letter-spacing:.06em">Ganancia</p>
                                    <p id="pkgGananciaVal" style="font-size:16px;font-weight:900;color:#0f172a;margin:0">$ 0</p>
                                </div>
                            </div>
                            <div>
                                <label class="form-label" style="margin-bottom:6px">Precio de venta personalizado ($) <span style="font-weight:400;color:#94a3b8">— deja en 0 para usar precio sugerido</span></label>
                                <input type="number" class="form-input" id="pkgPrecioVenta" step="0.01" min="0" placeholder="0.00" oninput="calcPkgGanancia()">
                            </div>
                        </div>

                        <!-- Facturación -->
                        <div class="form-group">
                            <label class="form-label">Facturación</label>
                            <select class="form-select" id="pkgFrecuencia">
                                <option value="mes">Mensual</option>
                                <option value="trimestre">Trimestral</option>
                                <option value="semestre">Semestral</option>
                                <option value="año">Anual</option>
                                <option value="unico">Pago Único</option>
                                <option value="ninguna">Ninguna</option>
                            </select>
                        </div>

                        <!-- Características -->
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="margin-bottom:8px">Características incluidas</label>
                            <div id="pkgFeaturesList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px"></div>
                            <div style="display:flex;gap:8px">
                                <input type="text" class="form-input" id="pkgFeatureInput" placeholder="Ej: Soporte 24/7, Dominio gratis..." style="flex:1" onkeydown="if(event.key==='Enter'){event.preventDefault();addPkgFeature();}">
                                <button type="button" class="btn btn-outline" onclick="addPkgFeature()" style="white-space:nowrap;padding:0 14px">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: imagen + enlace -->
                    <div style="display:flex;flex-direction:column;gap:16px">
                        <div>
                            <label class="form-label" style="margin-bottom:8px">Imagen del paquete</label>
                            <!-- Zona de imagen -->
                            <div id="pkgImgZone" style="width:100%;aspect-ratio:4/3;border-radius:14px;border:2px dashed #e2e8f0;background:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;cursor:pointer;overflow:hidden;position:relative;transition:border-color .2s"
                                onclick="document.getElementById('pkgImgInput').click()"
                                onmouseenter="this.style.borderColor='#6366f1'" onmouseleave="this.style.borderColor='#e2e8f0'">
                                <img id="pkgImgPreview" src="" alt="" style="display:none;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:12px">
                                <div id="pkgImgPlaceholder">
                                    <svg width="36" height="36" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><path stroke-linecap="round" d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="8.5" cy="8.5" r="1.5" fill="#cbd5e1" stroke="none"/></svg>
                                    <p style="font-size:12px;color:#94a3b8;margin:0;text-align:center">Clic para subir imagen<br><span style="font-size:10px">JPG, PNG, WEBP · máx 5MB</span></p>
                                </div>
                            </div>
                            <input type="file" id="pkgImgInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none" onchange="onPkgImgChange(this)">
                            <div id="pkgImgActions" style="display:none;margin-top:8px;display:none;gap:6px">
                                <button type="button" class="btn btn-outline" style="flex:1;font-size:12px;padding:6px 10px" onclick="document.getElementById('pkgImgInput').click()">Cambiar</button>
                                <button type="button" class="btn btn-outline" style="font-size:12px;padding:6px 10px;color:#ef4444;border-color:#ef4444" onclick="removePkgImg()">Quitar</button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label" style="margin-bottom:8px">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:middle;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                Enlace del paquete
                            </label>
                            <input type="url" class="form-input" id="pkgEnlace" placeholder="https://..." style="font-size:13px">
                            <p style="font-size:11px;color:#94a3b8;margin:6px 0 0">Se mostrará como botón en la vista previa</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closePkgModal()">Cancelar</button>
            <button class="btn btn-secondary" onclick="document.getElementById('pkgForm').requestSubmit()">Guardar Paquete</button>
        </div>
    </div>
</div>

<!-- ── Modal Vista Previa Paquete ────────────────────────── -->
<div class="modal-overlay" id="previewPkgModal" onclick="if(event.target===this)closePreviewPkg()">
    <div class="modal" style="max-width:980px">
        <div class="modal-header">
            <h3 class="modal-title" id="previewPkgTitle">Detalle del Paquete</h3>
            <button class="modal-close" onclick="closePreviewPkg()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body modal-body-lg" id="previewPkgBody"></div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closePreviewPkg()">Cerrar</button>
        </div>
    </div>
</div>

<!-- ── Modal Vista Previa Sub-servicio ────────────────────── -->
<div class="modal-overlay" id="previewSubModal" onclick="if(event.target===this)closePreviewSub()">
    <div class="modal" style="max-width:980px">
        <div class="modal-header">
            <h3 class="modal-title" id="previewSubTitle">Detalle del Sub-servicio</h3>
            <button class="modal-close" onclick="closePreviewSub()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body modal-body-lg" id="previewSubBody"></div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closePreviewSub()">Cerrar</button>
        </div>
    </div>
</div>

<script>
let allSvcs = [];
function escapeHtml(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}

/* ── CARGA ─────────────────────────────────────────────────── */
async function loadSvcs() {
    try {
        const r = await fetch('api/servicios.php');
        const d = await r.json();
        if(d.success){ allSvcs = d.data; renderSvcKPIs(d.data); renderSvcs(d.data); if(typeof allPkgs!=='undefined') renderPkgs(allPkgs); }
    } catch(e){ showToast('Error al cargar','error'); }
}

/* ── KPIs ──────────────────────────────────────────────────── */
function renderSvcKPIs(svcs) {
    const activos = svcs.filter(s => s.activo == 1);
    const totalSubs = activos.reduce((acc, s) => acc + (s.sub_servicios ? s.sub_servicios.length : 0), 0);
    const conSubs   = activos.filter(s => s.sub_servicios && s.sub_servicios.length > 0).length;
    const allVariantes = activos.flatMap(s =>
        s.sub_servicios && s.sub_servicios.length
            ? s.sub_servicios.map(ss => parseFloat(ss.precio || 0) - parseFloat(ss.costo || 0))
            : [parseFloat(s.precio_base || 0) - parseFloat(s.costo || 0)]
    );
    const conMargen   = allVariantes.filter(g => g > 0).length;
    const totalVariantes = allVariantes.length;

    const kpiCard = (label, value, footer, bg, textMain, textSub, pillBg, pillColor, pill) => `
        <div style="background:${bg};border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s"
            onmouseenter="this.style.filter='brightness(1.08)'" onmouseleave="this.style.filter='brightness(1)'">
            <div style="flex:1;min-width:0">
                <div style="font-size:10px;font-weight:700;color:${textSub};text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">${label}</div>
                <div style="font-size:22px;font-weight:900;color:${textMain};line-height:1">${value}</div>
                <div style="font-size:11px;color:${textSub};margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${footer}</div>
            </div>
            <span style="font-size:10px;font-weight:700;background:${pillBg};color:${pillColor};padding:3px 9px;border-radius:20px;white-space:nowrap;flex-shrink:0">${pill}</span>
        </div>`;

    document.getElementById('svcKpis').innerHTML =
        kpiCard('Total Servicios',    activos.length,                          'En catálogo',
                '#0f172a', '#ffffff', 'rgba(255,255,255,0.5)', 'rgba(255,255,255,0.15)', '#ffffff', 'Total')
      + kpiCard('Sub-servicios',      totalSubs,                               `${conSubs} con variantes`,
                '#4f46e5', '#ffffff', 'rgba(255,255,255,0.6)', 'rgba(255,255,255,0.2)',  '#ffffff',  'Variantes')
      + kpiCard('Con enlace de pago', activos.filter(s=>s.enlace_pago).length, 'Links activos',
                '#334155', '#ffffff', 'rgba(255,255,255,0.5)', 'rgba(255,255,255,0.15)', '#ffffff', 'Links')
      + kpiCard('Margen Positivo',    conMargen,                               `de ${totalVariantes} variantes`,
                '#c9f31d', '#0f172a', 'rgba(0,0,0,0.45)',      'rgba(0,0,0,0.18)',       '#0f172a', 'Rentables');
}

/* ── TARJETAS ───────────────────────────────────────────────── */
const CARD_ACCENTS = ['#c9f31d','#3b82f6','#10b981','#f59e0b','#8b5cf6','#06b6d4','#f97316','#ec4899','#ef4444'];
const FREQ_LABELS  = { mes:'Mensual', trimestre:'Trimestral', semestre:'Semestral', año:'Anual', unico:'Pago Único', ninguna:'Ninguna' };
const FREQ_ICONS   = {
    mes:      '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    trimestre:'<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    semestre: '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    año:      '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    unico:    '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
    ninguna:  ''
};

function renderSvcs(svcs) {
    const grid    = document.getElementById('svcsGrid');
    const activos = svcs.filter(s => s.activo == 1);
    if(!activos.length){
        grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><p class="empty-state-title">Sin servicios</p><p class="empty-state-text">Agrega tu primer servicio al catálogo.</p></div>';
        return;
    }

    grid.innerHTML = activos.map((s, idx) => {
        const accent    = CARD_ACCENTS[idx % CARD_ACCENTS.length];
        const isDark    = ['#c9f31d','#f59e0b'].includes(accent);
        const subs      = s.sub_servicios || [];
        const freqKey   = s.frecuencia || 'mes';
        const freqLabel = FREQ_LABELS[freqKey] || freqKey;

        const subItems = subs.map(ss => `
            <div data-id="${ss.id}" data-svc="${s.id}" style="display:flex;justify-content:space-between;align-items:center;
                        padding:6px 10px;border-radius:8px;background:#fff;
                        border:1px solid #e5e7eb;margin-bottom:5px">
                <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1">
                    <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:${accent};flex-shrink:0"></span>
                    <span style="font-size:12px;font-weight:700;color:#111;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(ss.nombre)}</span>
                </div>
                <div style="display:flex;align-items:center;gap:3px;flex-shrink:0;margin-left:6px">
                    <span style="font-size:12px;font-weight:800;color:#0f172a;font-family:var(--font-primary)">${formatMoney(ss.precio)}</span>
                    <button class="btn btn-ghost btn-icon sm" onclick='previewSub(${JSON.stringify(ss).replace(/'/g,"&#39;")})' title="Ver" style="color:#3b82f6;padding:2px">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button class="btn btn-ghost btn-icon sm" onclick='dupSub(${s.id},${JSON.stringify(ss).replace(/'/g,"&#39;")})' title="Duplicar" style="color:#f59e0b;padding:2px">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                    </button>
                    <button class="btn btn-ghost btn-icon sm" onclick='openSubModal(${s.id},${JSON.stringify(ss).replace(/'/g,"&#39;")})' title="Editar" style="color:#64748b;padding:2px">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="btn btn-ghost btn-icon sm" onclick="deleteSubSvc(${ss.id})" title="Eliminar" style="color:var(--color-danger);padding:2px">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>`).join('');

        return `
        <div class="animate-fade-up" data-id="${s.id}" style="background:#fff;border-radius:14px;border:1.5px solid #e5e7eb;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;flex-direction:column;overflow:hidden">

            <div style="padding:12px 14px 10px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px">
                    <div style="min-width:0;flex:1">
                        <div style="min-width:0;flex:1">
                            <h3 style="font-size:14px;font-weight:800;font-family:var(--font-primary);margin:0 0 2px;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(s.nombre)}</h3>
                            ${s.descripcion ? `<p style="font-size:11px;color:#64748b;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(s.descripcion)}</p>` : ""}
                        </div>
                    </div>
                    <div style="display:flex;gap:2px;flex-shrink:0">
                        <button class="btn btn-ghost btn-icon sm" onclick="dupSvc(${s.id})" title="Duplicar" style="color:#f59e0b;padding:3px">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                        <button class="btn btn-ghost btn-icon sm" onclick="editSvc(${s.id})" title="Editar" style="color:#64748b;padding:3px">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button class="btn btn-ghost btn-icon sm" onclick="deleteSvc(${s.id})" title="Desactivar" style="color:#ef4444;padding:3px">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:5px;margin-top:8px">
                    ${freqKey !== 'ninguna' ? `<span style="display:inline-flex;align-items:center;font-size:10px;font-weight:700;color:${isDark ? "#000" : "#fff"};background:${accent};padding:2px 8px;border-radius:20px">${freqLabel}</span>` : ''}
                    ${parseFloat(s.precio_base) > 0 ? `<span style="font-size:12px;font-weight:800;color:#0f172a;font-family:var(--font-primary)">${formatMoney(s.precio_base)}</span>` : ""}
                    ${parseFloat(s.costo) > 0 ? `<span style="font-size:10px;color:#94a3b8">costo ${formatMoney(s.costo)}</span>` : ""}
                    ${s.enlace_pago ? `<a href="${escapeHtml(s.enlace_pago)}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;color:#fff;background:#0f172a;padding:2px 8px;border-radius:20px;text-decoration:none"><svg width="8" height="8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>Link</a>` : ""}
                </div>
            </div>

            <div style="padding:10px 12px 12px;flex:1;background:#f8fafc;border-top:1px solid #e5e7eb">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px">
                    <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;display:flex;align-items:center;gap:4px">
                        Variantes
                        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:17px;height:17px;border-radius:20px;background:${accent};color:${isDark ? "#000" : "#fff"};font-size:9px;font-weight:900">${subs.length}</span>
                    </span>
                    <button onclick="openSubModal(${s.id})"
                        style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;cursor:pointer;color:#fff;background:#6366f1;border:none;padding:3px 8px;border-radius:20px"
                        onmouseenter="this.style.background='#4f46e5'" onmouseleave="this.style.background='#6366f1'">
                        <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 4v16m8-8H4"/></svg> Agregar
                    </button>
                </div>
                <div data-sub-list>${subs.length ? subItems : `<div style="text-align:center;padding:10px 0;font-size:11px;color:#94a3b8;font-style:italic">Sin variantes</div>`}</div>
            </div>
        </div>`;
    }).join('');

    // Drag & drop servicios
    initDrag('svcsGrid', 'servicios');
    // Drag & drop sub-servicios dentro de cada card
    grid.querySelectorAll('[data-sub-list]').forEach(subList => {
        initDragEl(subList, 'sub_servicios');
    });
}

/* ── TOGGLE ENLACE ──────────────────────────────────────────── */
function toggleSvcEnlace() {
    const on    = document.getElementById('svcEnlaceToggle').checked;
    const field = document.getElementById('svcEnlaceField');
    const track = document.getElementById('svcEnlaceTrack');
    const thumb = document.getElementById('svcEnlaceThumb');
    const label = document.getElementById('svcEnlaceToggleLabel');
    field.style.display = on ? 'block' : 'none';
    track.style.background = on ? 'var(--color-secondary, #c9f31d)' : '#cbd5e1';
    thumb.style.transform  = on ? 'translateX(16px)' : 'translateX(0)';
    label.textContent      = on ? 'Sí' : 'No';
    if(!on) document.getElementById('svcEnlace').value = '';
}

function setSvcEnlaceToggle(active) {
    document.getElementById('svcEnlaceToggle').checked = active;
    toggleSvcEnlace();
}

/* ── MODAL SERVICIO ─────────────────────────────────────────── */
function openSvcModal(svc = null) {
    document.getElementById('svcForm').reset();
    document.getElementById('svcId').value = '';
    if(svc) {
        document.getElementById('svcModalTitle').textContent  = 'Editar Servicio';
        document.getElementById('svcId').value         = svc.id;
        document.getElementById('svcNombre').value     = svc.nombre;
        document.getElementById('svcDesc').value       = svc.descripcion || '';
        document.getElementById('svcPrecio').value     = svc.precio_base || '';
        document.getElementById('svcCosto').value      = svc.costo || '';
        document.getElementById('svcFrecuencia').value = svc.frecuencia || 'mes';
        setSvcEnlaceToggle(!!svc.enlace_pago);
        document.getElementById('svcEnlace').value     = svc.enlace_pago || '';
        svcFeatures = Array.isArray(svc.features) ? svc.features.map(f => f.texto) : [];
    } else {
        document.getElementById('svcModalTitle').textContent = 'Nuevo Servicio';
        document.getElementById('svcPrecio').value = '';
        document.getElementById('svcCosto').value  = '';
        setSvcEnlaceToggle(false);
        svcFeatures = [];
    }
    document.getElementById('svcFeatureInput').value = '';
    renderFeatures(svcFeatures, 'svcFeaturesList', 'removeSvcFeature');
    calcSvcGanancia();
    document.getElementById('svcModal').classList.add('show');
}
function closeSvcModal(){ document.getElementById('svcModal').classList.remove('show'); }
function editSvc(id){ const s = allSvcs.find(x => x.id == id); if(s) openSvcModal(s); }

async function saveSvc(e) {
    e.preventDefault();
    const id = document.getElementById('svcId').value;
    const payload = {
        nombre:      document.getElementById('svcNombre').value,
        descripcion: document.getElementById('svcDesc').value,
        precio_base: parseFloat(document.getElementById('svcPrecio').value) || 0,
        costo:       parseFloat(document.getElementById('svcCosto').value)  || 0,
        frecuencia:  document.getElementById('svcFrecuencia').value,
        enlace_pago: document.getElementById('svcEnlaceToggle').checked ? (document.getElementById('svcEnlace').value || null) : null,
        features:    svcFeatures,
    };
    if(id) payload.id = parseInt(id);
    try {
        const r = await fetch('api/servicios.php', { method: id ? 'PUT' : 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const d = await r.json();
        if(d.success){ showToast(d.message,'success'); closeSvcModal(); await Promise.all([loadSvcs(), loadPkgs()]); }
        else showToast(d.error,'error');
    } catch(e){ showToast('Error','error'); }
}

async function deleteSvc(id) {
    const ok = await confirmAction('El servicio y sus sub-servicios serán desactivados.', { title: '¿Desactivar servicio?', okText: 'Desactivar', okColor: '#f59e0b', okHover: '#d97706' });
    if (!ok) return;
    try {
        const r = await fetch(`api/servicios.php?id=${id}`, { method:'DELETE' });
        const d = await r.json();
        if(d.success){ showToast('Servicio desactivado','success'); await Promise.all([loadSvcs(), loadPkgs()]); }
    } catch(e){ showToast('Error','error'); }
}

/* ── TOGGLE SUB ENLACE ──────────────────────────────────────── */
function toggleSubEnlace() {
    const on    = document.getElementById('subEnlaceToggle').checked;
    const field = document.getElementById('subEnlaceField');
    const track = document.getElementById('subEnlaceTrack');
    const thumb = document.getElementById('subEnlaceThumb');
    const label = document.getElementById('subEnlaceToggleLabel');
    field.style.display = on ? 'block' : 'none';
    track.style.background = on ? 'var(--color-secondary, #c9f31d)' : '#cbd5e1';
    thumb.style.transform  = on ? 'translateX(16px)' : 'translateX(0)';
    label.textContent      = on ? 'Sí' : 'No';
    if(!on) document.getElementById('subEnlace').value = '';
}

function setSubEnlaceToggle(active) {
    document.getElementById('subEnlaceToggle').checked = active;
    toggleSubEnlace();
}

/* ── MODAL SUB-SERVICIO ─────────────────────────────────────── */
function openSubModal(svcId, sub = null) {
    document.getElementById('subForm').reset();
    document.getElementById('subId').value    = '';
    document.getElementById('subSvcId').value = svcId;
    const svc = allSvcs.find(s => s.id == svcId);
    document.getElementById('subSvcLabel').textContent = svc ? '↳ ' + svc.nombre : '';

    if(sub) {
        document.getElementById('subModalTitle').textContent = 'Editar Sub-servicio';
        document.getElementById('subId').value         = sub.id;
        document.getElementById('subNombre').value     = sub.nombre;
        document.getElementById('subDesc').value       = sub.descripcion || '';
        document.getElementById('subPrecio').value     = sub.precio;
        document.getElementById('subCosto').value      = sub.costo || '';
        document.getElementById('subFrecuencia').value = sub.frecuencia || 'mes';
        setSubEnlaceToggle(!!sub.enlace_pago);
        document.getElementById('subEnlace').value = sub.enlace_pago || '';
        subFeatures = Array.isArray(sub.features) ? sub.features.map(f => f.texto) : [];
    } else {
        document.getElementById('subModalTitle').textContent = 'Nuevo Sub-servicio';
        document.getElementById('subFrecuencia').value = 'mes';
        document.getElementById('subCosto').value = '';
        setSubEnlaceToggle(false);
        subFeatures = [];
    }
    document.getElementById('subFeatureInput').value = '';
    renderFeatures(subFeatures, 'subFeaturesList', 'removeSubFeature');
    calcSubGanancia();
    document.getElementById('subModal').classList.add('show');
}
function closeSubModal(){ document.getElementById('subModal').classList.remove('show'); }

async function saveSubSvc(e) {
    e.preventDefault();
    const id = document.getElementById('subId').value;
    const payload = {
        servicio_id: parseInt(document.getElementById('subSvcId').value),
        nombre:      document.getElementById('subNombre').value,
        descripcion: document.getElementById('subDesc').value || null,
        precio:      parseFloat(document.getElementById('subPrecio').value) || 0,
        costo:       parseFloat(document.getElementById('subCosto').value) || 0,
        frecuencia:  document.getElementById('subFrecuencia').value,
        enlace_pago: document.getElementById('subEnlaceToggle').checked ? (document.getElementById('subEnlace').value || null) : null,
        features:    subFeatures,
    };
    if(id) payload.id = parseInt(id);
    try {
        const r = await fetch('api/sub_servicios.php', { method: id ? 'PUT' : 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const d = await r.json();
        if(d.success){ showToast(d.message,'success'); closeSubModal(); await Promise.all([loadSvcs(), loadPkgs()]); }
        else showToast(d.error,'error');
    } catch(e){ showToast('Error','error'); }
}

async function deleteSubSvc(id) {
    const ok = await confirmAction('El sub-servicio será eliminado permanentemente.', { title: '¿Eliminar sub-servicio?' });
    if (!ok) return;
    try {
        const r = await fetch(`api/sub_servicios.php?id=${id}`, { method:'DELETE' });
        const d = await r.json();
        if(d.success){ showToast('Eliminado','success'); await Promise.all([loadSvcs(), loadPkgs()]); }
    } catch(e){ showToast('Error','error'); }
}

/* ── FEATURES (CARACTERÍSTICAS) ────────────────────────────── */
let svcFeatures = [];
let subFeatures = [];

function renderFeatures(list, containerId, removeFn) {
    const el = document.getElementById(containerId);
    if (!list.length) { el.innerHTML = ''; return; }
    el.innerHTML = list.map((f, i) => `
        <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:8px">
            <svg width="13" height="13" fill="none" stroke="#10b981" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span style="flex:1;font-size:13px;color:var(--color-text)">${escapeHtml(f)}</span>
            <button type="button" onclick="${removeFn}(${i})" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;line-height:1" title="Eliminar">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>`).join('');
}

function addSvcFeature() {
    const inp = document.getElementById('svcFeatureInput');
    const val = inp.value.trim();
    if (!val) return;
    svcFeatures.push(val);
    inp.value = '';
    renderFeatures(svcFeatures, 'svcFeaturesList', 'removeSvcFeature');
}
function removeSvcFeature(i) {
    svcFeatures.splice(i, 1);
    renderFeatures(svcFeatures, 'svcFeaturesList', 'removeSvcFeature');
}

function addSubFeature() {
    const inp = document.getElementById('subFeatureInput');
    const val = inp.value.trim();
    if (!val) return;
    subFeatures.push(val);
    inp.value = '';
    renderFeatures(subFeatures, 'subFeaturesList', 'removeSubFeature');
}
function removeSubFeature(i) {
    subFeatures.splice(i, 1);
    renderFeatures(subFeatures, 'subFeaturesList', 'removeSubFeature');
}

/* ── CÁLCULO GANANCIA SERVICIO ──────────────────────────────── */
function calcSvcGanancia() {
    const precio  = parseFloat(document.getElementById('svcPrecio').value) || 0;
    const costo   = parseFloat(document.getElementById('svcCosto').value)  || 0;
    const box     = document.getElementById('svcGananciaBox');
    if (precio <= 0 && costo <= 0) { box.style.display = 'none'; return; }
    const ganancia = precio - costo;
    const pct      = precio > 0 ? Math.round((ganancia / precio) * 100) : 0;
    const color    = ganancia >= 0
        ? { bg:'#f0fdf4', border:'#86efac', txt:'#15803d', badge:'#dcfce7', badgeTxt:'#16a34a' }
        : { bg:'#fef2f2', border:'#fca5a5', txt:'#dc2626', badge:'#fee2e2', badgeTxt:'#dc2626' };
    box.style.cssText = `display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding:10px 14px;background:${color.bg};border:1.5px solid ${color.border};border-radius:10px`;
    document.getElementById('svcGananciaLabel').style.color  = color.txt;
    document.getElementById('svcGananciaValor').style.color  = color.txt;
    document.getElementById('svcGananciaPct').style.cssText  = `font-size:11px;font-weight:700;background:${color.badge};color:${color.badgeTxt};padding:2px 8px;border-radius:20px`;
    document.getElementById('svcGananciaValor').textContent  = (ganancia >= 0 ? '+ ' : '- ') + formatMoney(Math.abs(ganancia));
    document.getElementById('svcGananciaPct').textContent    = pct + '%';
}

/* ── CÁLCULO GANANCIA SUB-SERVICIO ──────────────────────────── */
function calcSubGanancia() {
    const precio  = parseFloat(document.getElementById('subPrecio').value) || 0;
    const costo   = parseFloat(document.getElementById('subCosto').value)  || 0;
    const box     = document.getElementById('subGananciaBox');
    if (precio <= 0 && costo <= 0) { box.style.display = 'none'; return; }
    const ganancia = precio - costo;
    const pct      = precio > 0 ? Math.round((ganancia / precio) * 100) : 0;
    const color    = ganancia >= 0 ? { bg:'#f0fdf4', border:'#86efac', txt:'#15803d', badge:'#dcfce7', badgeTxt:'#16a34a' }
                                   : { bg:'#fef2f2', border:'#fca5a5', txt:'#dc2626', badge:'#fee2e2', badgeTxt:'#dc2626' };
    box.style.cssText = `display:flex;align-items:center;justify-content:space-between;margin-top:8px;padding:10px 14px;background:${color.bg};border:1.5px solid ${color.border};border-radius:10px`;
    document.getElementById('subGananciaValor').style.color = color.txt;
    document.getElementById('subGananciaPct').style.cssText = `font-size:11px;font-weight:700;background:${color.badge};color:${color.badgeTxt};padding:2px 8px;border-radius:20px`;
    document.getElementById('subGananciaValor').textContent = (ganancia >= 0 ? '+ ' : '- ') + formatMoney(Math.abs(ganancia));
    document.getElementById('subGananciaPct').textContent   = pct + '%';
}

/* ── VISTA PREVIA SUB-SERVICIO ──────────────────────────────── */
function previewSub(ss) {
    const freq   = { mes:'Mensual', trimestre:'Trimestral', semestre:'Semestral', año:'Anual', unico:'Pago Único', ninguna:'Ninguna' };
    const ganancia = parseFloat(ss.precio) - parseFloat(ss.costo || 0);
    const pct      = parseFloat(ss.precio) > 0 ? Math.round((ganancia / parseFloat(ss.precio)) * 100) : 0;
    const gColor   = ganancia >= 0 ? '#15803d' : '#dc2626';
    const gBg      = ganancia >= 0 ? '#f0fdf4' : '#fef2f2';
    const gBorder  = ganancia >= 0 ? '#86efac' : '#fca5a5';

    const featuresHtml = Array.isArray(ss.features) && ss.features.length
        ? `<div style="margin-top:20px">
            <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin:0 0 10px">Características incluidas</p>
            <div style="display:flex;flex-direction:column;gap:6px">
              ${ss.features.map(f => `
                <div style="display:flex;align-items:center;gap:10px;padding:11px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px">
                  <svg width="15" height="15" fill="none" stroke="#10b981" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                  <span style="font-size:14px;color:#0f172a">${escapeHtml(f.texto)}</span>
                </div>`).join('')}
            </div>
          </div>` : '';

    document.getElementById('previewSubTitle').textContent = ss.nombre;
    document.getElementById('previewSubBody').innerHTML = `
        <!-- Fila superior: 2 cards precio/costo a ancho completo -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div style="padding:24px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:16px;text-align:center">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 10px;letter-spacing:.07em">Precio de venta</p>
                <p style="font-size:24px;font-weight:900;color:#10b981;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${formatMoney(ss.precio)}</p>
            </div>
            <div style="padding:24px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:16px;text-align:center">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 10px;letter-spacing:.07em">Costo</p>
                <p style="font-size:24px;font-weight:900;color:#ef4444;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${formatMoney(ss.costo || 0)}</p>
            </div>
        </div>
        <!-- Fila inferior: descripción+meta | features -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px">
            <div>
                ${ss.descripcion ? `
                <div style="margin-bottom:24px">
                    <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin:0 0 10px">Descripción</p>
                    <p style="font-size:15px;color:#334155;line-height:1.75;margin:0;white-space:pre-line">${escapeHtml(ss.descripcion)}</p>
                </div>` : ''}
                ${ss.frecuencia !== 'ninguna' ? `<div style="display:flex;align-items:center;gap:10px;padding:16px 20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:14px">
                    <svg width="15" height="15" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span style="font-size:14px;color:#475569;font-weight:600">Facturación: <strong style="color:#0f172a">${freq[ss.frecuencia] || ss.frecuencia}</strong></span>
                </div>` : ''}
                <div style="padding:20px 24px;background:#c9f31d;border:none;border-radius:16px;display:flex;align-items:center;justify-content:space-between;gap:16px">
                    <div>
                        <p style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#000;margin:0 0 6px">Ganancia por ciclo</p>
                        <p style="font-size:24px;font-weight:900;color:#000;margin:0;white-space:nowrap">${ganancia>=0?'+ ':'-'}${formatMoney(Math.abs(ganancia))}</p>
                    </div>
                    <span style="font-size:18px;font-weight:900;background:rgba(0,0,0,0.12);color:#000;padding:8px 14px;border-radius:14px;white-space:nowrap;flex-shrink:0">${pct}%</span>
                </div>
                ${ss.enlace_pago ? `
                <div style="margin-top:14px">
                    <a href="${escapeHtml(ss.enlace_pago)}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#fff;background:#111;padding:11px 18px;border-radius:10px;text-decoration:none">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        Ver enlace de pago
                    </a>
                </div>` : ''}
            </div>
            <div>${featuresHtml || '<div style="padding:50px 30px;text-align:center;color:#94a3b8;font-size:14px;font-style:italic;border:1.5px dashed #e2e8f0;border-radius:14px">Sin características agregadas</div>'}</div>
        </div>`;

    document.getElementById('previewSubModal').classList.add('show');
}
function closePreviewSub() { document.getElementById('previewSubModal').classList.remove('show'); }

/* ── VISTA PREVIA PAQUETE ───────────────────────────────────── */
function previewPkg(p) {
    const FREQ = { mes:'Mensual', trimestre:'Trimestral', semestre:'Semestral', año:'Anual', unico:'Pago Único', ninguna:'Ninguna' };
    const costoReal   = p.items.reduce((s, it) => s + parseFloat(it.costo  || 0), 0);
    const precioSug   = p.items.reduce((s, it) => s + parseFloat(it.precio || 0), 0);
    const precioVenta = parseFloat(p.precio_venta) > 0 ? parseFloat(p.precio_venta) : precioSug;
    const ganancia    = precioVenta - costoReal;
    const pct         = precioVenta > 0 ? Math.round((ganancia / precioVenta) * 100) : 0;
    const gColor      = ganancia >= 0 ? '#15803d' : '#dc2626';
    const gBg         = ganancia >= 0 ? '#dcfce7' : '#fee2e2';

    document.getElementById('previewPkgTitle').textContent = p.nombre;
    document.getElementById('previewPkgBody').innerHTML = `
        <!-- Cards de precios: ancho completo -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 16px">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;color:#15803d;letter-spacing:.06em;margin:0 0 4px">Precio de venta</p>
                <p style="font-size:22px;font-weight:900;color:#15803d;margin:0;line-height:1.1;font-family:var(--font-primary)">${moneyNum(precioVenta)}</p>
                <p style="font-size:10px;font-weight:700;color:#15803d;margin:2px 0 4px;opacity:.7">COP</p>
                ${p.frecuencia !== 'ninguna' ? `<p style="font-size:10px;color:#86efac;margin:0">${FREQ[p.frecuencia] || p.frecuencia}</p>` : ''}
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.06em;margin:0 0 4px">Costo total</p>
                <p style="font-size:22px;font-weight:900;color:#0f172a;margin:0;line-height:1.1;font-family:var(--font-primary)">${moneyNum(costoReal)}</p>
                <p style="font-size:10px;font-weight:700;color:#64748b;margin:2px 0 4px;opacity:.7">COP</p>
                <p style="font-size:10px;color:#94a3b8;margin:0">${p.items.length} sub-servicio${p.items.length !== 1 ? 's' : ''}</p>
            </div>
            <div style="background:${gBg};border:1px solid ${ganancia>=0?'#bbf7d0':'#fecaca'};border-radius:12px;padding:14px 16px">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;color:${gColor};letter-spacing:.06em;margin:0 0 4px">Ganancia</p>
                <div style="display:flex;align-items:baseline;gap:6px">
                    <p style="font-size:22px;font-weight:900;color:${gColor};margin:0;line-height:1.1;font-family:var(--font-primary)">${moneyNum(ganancia)}</p>
                    <span style="font-size:12px;font-weight:800;color:${gColor}">${pct}%</span>
                </div>
                <p style="font-size:10px;font-weight:700;color:${gColor};margin:2px 0 0;opacity:.7">COP</p>
            </div>
        </div>

        <!-- Contenido inferior: 65% izquierda / 35% derecha -->
        <div style="display:grid;grid-template-columns:65fr 35fr;gap:24px;align-items:start">

            <!-- Columna izquierda: sub-servicios + características -->
            <div>
                ${p.descripcion ? `<p style="font-size:13px;color:#475569;line-height:1.6;margin:0 0 16px;white-space:pre-line">${escapeHtml(p.descripcion)}</p>` : ''}
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.06em;margin:0 0 10px">Sub-servicios incluidos</p>
                <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:${p.features&&p.features.length?'20px':'0'}">
                    ${p.items.map(it => `
                    <div style="padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start">
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px">${escapeHtml(it.ss_nombre)}</p>
                                <p style="font-size:11px;color:#94a3b8;margin:0">${escapeHtml(it.svc_nombre)}</p>
                            </div>
                            <div style="text-align:right;flex-shrink:0;margin-left:12px">
                                <p style="font-size:13px;font-weight:800;color:#0f172a;margin:0 0 2px">${formatMoney(it.precio)}</p>
                                <p style="font-size:11px;color:#94a3b8;margin:0">costo ${formatMoney(it.costo)}</p>
                            </div>
                        </div>
                    </div>`).join('') || '<p style="font-size:12px;color:#94a3b8;font-style:italic">Sin items</p>'}
                </div>
                ${p.features && p.features.length ? `
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.06em;margin:0 0 10px">Características</p>
                <div style="display:flex;flex-direction:column;gap:6px">
                    ${p.features.map(f => `
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px">
                        <svg width="13" height="13" fill="none" stroke="#10b981" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span style="font-size:13px;color:#0f172a">${escapeHtml(f.texto)}</span>
                    </div>`).join('')}
                </div>` : ''}
            </div>

            <!-- Columna derecha: imagen + enlace -->
            <div style="display:flex;flex-direction:column;gap:12px">
                ${p.imagen ? `
                <div style="border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;aspect-ratio:4/3">
                    <img src="${escapeHtml(p.imagen)}" alt="${escapeHtml(p.nombre)}" style="width:100%;height:100%;object-fit:cover;display:block">
                </div>` : `
                <div style="border-radius:14px;border:2px dashed #e2e8f0;background:#f8fafc;aspect-ratio:4/3;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px">
                    <svg width="36" height="36" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><path stroke-linecap="round" d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="8.5" cy="8.5" r="1.5" fill="#cbd5e1" stroke="none"/></svg>
                    <p style="font-size:12px;color:#94a3b8;margin:0">Sin imagen</p>
                </div>`}
                ${p.enlace ? `
                <a href="${escapeHtml(p.enlace)}" target="_blank" rel="noopener noreferrer"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;padding:12px 16px;
                          background:#0f172a;color:#fff;border-radius:12px;text-decoration:none;
                          font-size:13px;font-weight:700"
                   onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='#0f172a'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    Ver paquete
                </a>` : ''}
            </div>
        </div>`;

    document.getElementById('previewPkgModal').classList.add('show');
}
function closePreviewPkg() { document.getElementById('previewPkgModal').classList.remove('show'); }

/* ── PAQUETES ───────────────────────────────────────────────── */
let allPkgs = [];

async function loadPkgs() {
    try {
        const r = await fetch('api/paquetes.php');
        const d = await r.json();
        if (d.success) { allPkgs = d.data; renderPkgs(d.data); }
    } catch(e) { showToast('Error al cargar paquetes','error'); }
}

function renderPkgs(pkgs) {
    const grid = document.getElementById('pkgsGrid');
    if (!pkgs.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><p class="empty-state-title">Sin paquetes</p><p class="empty-state-text">Crea tu primer paquete combinando sub-servicios.</p></div>`;
        return;
    }
    const FREQ = { mes:'Mensual', trimestre:'Trimestral', semestre:'Semestral', año:'Anual', unico:'Pago Único', ninguna:'Ninguna' };
    grid.innerHTML = pkgs.map(p => {
        // Recalcular desde items actuales (precios en tiempo real de sub_servicios)
        const costoReal  = p.items.reduce((s, it) => s + parseFloat(it.costo  || 0), 0);
        const precioSug  = p.items.reduce((s, it) => s + parseFloat(it.precio || 0), 0);
        const precioVenta = parseFloat(p.precio_venta) > 0 ? parseFloat(p.precio_venta) : precioSug;
        const ganancia = precioVenta - costoReal;
        const pct      = precioVenta > 0 ? Math.round((ganancia / precioVenta) * 100) : 0;
        const gColor   = ganancia >= 0 ? '#15803d' : '#dc2626';
        const itemsHtml = p.items.map(it => `
            <div style="display:flex;align-items:center;gap:8px;padding:7px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px">
                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#c9f31d;flex-shrink:0"></span>
                <span style="flex:1;font-size:12px;font-weight:600;color:#0f172a">${escapeHtml(it.svc_nombre)} — ${escapeHtml(it.ss_nombre)}</span>
                <span style="font-size:11px;color:#94a3b8;font-weight:600">${formatMoney(it.precio)}</span>
            </div>`).join('');
        return `
        <div data-id="${p.id}" style="background:#fff;border-radius:18px;border:1.5px solid #e5e7eb;box-shadow:0 4px 24px rgba(0,0,0,0.07);display:flex;flex-direction:column;overflow:hidden;transition:transform .2s,box-shadow .2s"
             onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 36px rgba(0,0,0,0.12)'"
             onmouseleave="this.style.transform='';this.style.boxShadow='0 4px 24px rgba(0,0,0,0.07)'">
            <div style="padding:18px 20px 0">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                    <div>
                        <div>
                            <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8">Paquete</span>
                            <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:2px 0 0">${escapeHtml(p.nombre)}</h3>
                        </div>
                    </div>
                    <div style="display:flex;gap:4px">
                        <button class="btn btn-ghost btn-icon sm" onclick='previewPkg(${JSON.stringify(p).replace(/'/g,"&#39;")})' title="Ver detalle" style="color:#3b82f6">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <button class="btn btn-ghost btn-icon sm" onclick="dupPkg(${p.id})" title="Duplicar" style="color:#f59e0b">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                        <button class="btn btn-ghost btn-icon sm" onclick="editPkg(${p.id})" title="Editar" style="color:#64748b">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button class="btn btn-ghost btn-icon sm" onclick="deletePkg(${p.id})" title="Eliminar" style="color:#ef4444">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                ${p.descripcion ? `<p style="font-size:13px;color:#475569;line-height:1.5;margin:0 0 12px">${escapeHtml(p.descripcion.split(/\n\n|\n/)[0])}</p>` : ''}
                <!-- Items -->
                <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:${p.features&&p.features.length?'10px':'14px'}">${itemsHtml || '<p style="font-size:12px;color:#94a3b8;font-style:italic;margin:0">Sin items</p>'}</div>
                ${p.features && p.features.length ? `
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:14px">
                    ${p.features.map(f=>`<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#0f172a;background:#f1f5f9;border:1px solid #e2e8f0;padding:3px 9px;border-radius:20px">
                        <svg width="10" height="10" fill="none" stroke="#10b981" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>${escapeHtml(f.texto)}</span>`).join('')}
                </div>` : ''}
            </div>
            <!-- Footer con precios -->
            <div style="margin-top:auto;padding:14px 20px;background:#f8fafc;border-top:1px solid #e2e8f0">
                ${p.frecuencia !== 'ninguna' ? `<div style="display:flex;align-items:center;gap:6px;margin-bottom:10px">
                    <svg width="12" height="12" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span style="font-size:11px;color:#64748b;font-weight:600">${FREQ[p.frecuencia] || p.frecuencia}</span>
                </div>` : ''}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <p style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 3px;letter-spacing:.05em">Precio de venta</p>
                        <p style="font-size:16px;font-weight:900;color:#10b981;margin:0">${formatMoney(precioVenta)}</p>
                    </div>
                    <div style="text-align:right">
                        <p style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 3px;letter-spacing:.05em">Ganancia</p>
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                            <p style="font-size:16px;font-weight:900;color:${gColor};margin:0">${formatMoney(ganancia)}</p>
                            <span style="font-size:10px;font-weight:800;background:${ganancia>=0?'#dcfce7':'#fee2e2'};color:${gColor};padding:2px 7px;border-radius:20px">${pct}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');

    initDrag('pkgsGrid', 'paquetes');
}

function buildPkgSubsList(selectedIds = []) {
    const container = document.getElementById('pkgSubsList');
    const allSubs = allSvcs.flatMap(s => (s.sub_servicios || []).map(ss => ({...ss, svc_nombre: s.nombre})));
    if (!allSubs.length) { container.innerHTML = '<p style="color:#94a3b8;font-size:13px;padding:10px">No hay sub-servicios disponibles.</p>'; return; }

    // Agrupar por servicio
    const byService = {};
    allSubs.forEach(ss => {
        if (!byService[ss.svc_nombre]) byService[ss.svc_nombre] = [];
        byService[ss.svc_nombre].push(ss);
    });

    container.innerHTML = Object.entries(byService).map(([svcNombre, subs]) => `
        <div style="margin-bottom:8px">
            <p style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin:0 0 6px;padding:0 4px">${escapeHtml(svcNombre)}</p>
            ${subs.map(ss => `
            <label style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;cursor:pointer;transition:background .1s;margin-bottom:4px"
                   onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background=''">
                <input type="checkbox" data-id="${ss.id}" data-precio="${ss.precio}" data-costo="${ss.costo}"
                       ${selectedIds.includes(parseInt(ss.id)) ? 'checked' : ''}
                       onchange="calcPkgResumen()" style="width:16px;height:16px;accent-color:#000;cursor:pointer;flex-shrink:0">
                <span style="flex:1;font-size:13px;font-weight:600;color:#0f172a">${escapeHtml(ss.nombre)}</span>
                <span style="font-size:12px;color:#10b981;font-weight:700">${formatMoney(ss.precio)}</span>
                <span style="font-size:11px;color:#94a3b8">costo: ${formatMoney(ss.costo)}</span>
            </label>`).join('')}
        </div>`).join('');

    calcPkgResumen();
}

function calcPkgResumen() {
    const checks = document.querySelectorAll('#pkgSubsList input[type=checkbox]:checked');
    const resumen = document.getElementById('pkgResumen');
    let costo = 0, precio = 0;
    checks.forEach(c => { costo += parseFloat(c.dataset.costo || 0); precio += parseFloat(c.dataset.precio || 0); });
    if (!checks.length) { resumen.style.display = 'none'; return; }
    resumen.style.display = 'block';
    document.getElementById('pkgCostoVal').textContent     = formatMoney(costo);
    document.getElementById('pkgPrecioSugVal').textContent = formatMoney(precio);
    calcPkgGanancia();
}

function calcPkgGanancia() {
    const checks   = document.querySelectorAll('#pkgSubsList input[type=checkbox]:checked');
    let costo = 0, precioSug = 0;
    checks.forEach(c => { costo += parseFloat(c.dataset.costo || 0); precioSug += parseFloat(c.dataset.precio || 0); });
    const custom   = parseFloat(document.getElementById('pkgPrecioVenta').value) || 0;
    const precioFinal = custom > 0 ? custom : precioSug;
    const ganancia = precioFinal - costo;
    const pct      = precioFinal > 0 ? Math.round((ganancia / precioFinal) * 100) : 0;
    const color    = ganancia >= 0 ? '#15803d' : '#dc2626';
    const el       = document.getElementById('pkgGananciaVal');
    el.textContent = formatMoney(ganancia);
    el.style.color = color;
    el.nextElementSibling && (el.nextElementSibling.textContent = pct + '%');
}

let pkgFeatures = [];

function addPkgFeature() {
    const inp = document.getElementById('pkgFeatureInput');
    const val = inp.value.trim();
    if (!val) return;
    pkgFeatures.push(val);
    inp.value = '';
    renderFeatures(pkgFeatures, 'pkgFeaturesList', 'removePkgFeature');
}
function removePkgFeature(i) {
    pkgFeatures.splice(i, 1);
    renderFeatures(pkgFeatures, 'pkgFeaturesList', 'removePkgFeature');
}

let pkgImgPendiente = null; // archivo pendiente para subir tras guardar

function setPkgImgUI(url) {
    const preview     = document.getElementById('pkgImgPreview');
    const placeholder = document.getElementById('pkgImgPlaceholder');
    const actions     = document.getElementById('pkgImgActions');
    if (url) {
        preview.src           = url;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
        actions.style.display = 'flex';
    } else {
        preview.src           = '';
        preview.style.display = 'none';
        placeholder.style.display = 'flex';
        placeholder.style.flexDirection = 'column';
        placeholder.style.alignItems = 'center';
        placeholder.style.gap = '8px';
        actions.style.display = 'none';
    }
}

function onPkgImgChange(input) {
    const file = input.files[0];
    if (!file) return;
    pkgImgPendiente = file;
    setPkgImgUI(URL.createObjectURL(file));
}

function removePkgImg() {
    pkgImgPendiente = null;
    document.getElementById('pkgImgInput').value = '';
    // marcar para borrar si hay imagen guardada
    const id = document.getElementById('pkgId').value;
    if (id) {
        fetch(`api/paquete_imagen.php?id=${id}`, { method: 'DELETE' });
    }
    setPkgImgUI(null);
}

function openPkgModal(pkg = null) {
    document.getElementById('pkgForm').reset();
    document.getElementById('pkgId').value = '';
    document.getElementById('pkgResumen').style.display = 'none';
    pkgImgPendiente = null;
    document.getElementById('pkgImgInput').value = '';
    setPkgImgUI(null);

    const selectedIds = pkg ? (pkg.items || []).map(i => parseInt(i.sub_servicio_id)) : [];
    if (pkg) {
        document.getElementById('pkgModalTitle').textContent   = 'Editar Paquete';
        document.getElementById('pkgId').value                 = pkg.id;
        document.getElementById('pkgNombre').value             = pkg.nombre;
        document.getElementById('pkgDesc').value               = pkg.descripcion || '';
        document.getElementById('pkgPrecioVenta').value        = pkg.precio_venta || '';
        document.getElementById('pkgFrecuencia').value         = pkg.frecuencia || 'mes';
        document.getElementById('pkgEnlace').value             = pkg.enlace || '';
        pkgFeatures = Array.isArray(pkg.features) ? pkg.features.map(f => f.texto) : [];
        if (pkg.imagen) setPkgImgUI(pkg.imagen);
    } else {
        document.getElementById('pkgModalTitle').textContent = 'Nuevo Paquete';
        document.getElementById('pkgEnlace').value = '';
        pkgFeatures = [];
    }
    document.getElementById('pkgFeatureInput').value = '';
    renderFeatures(pkgFeatures, 'pkgFeaturesList', 'removePkgFeature');
    buildPkgSubsList(selectedIds);
    document.getElementById('pkgModal').classList.add('show');
}
function closePkgModal() { document.getElementById('pkgModal').classList.remove('show'); }
function editPkg(id) { const p = allPkgs.find(x => x.id == id); if (p) openPkgModal(p); }

async function savePkg(e) {
    e.preventDefault();
    const id      = document.getElementById('pkgId').value;
    const checks  = [...document.querySelectorAll('#pkgSubsList input[type=checkbox]:checked')];
    const items   = checks.map(c => parseInt(c.dataset.id));
    if (!items.length) { showToast('Selecciona al menos un sub-servicio','error'); return; }
    const payload = {
        nombre:       document.getElementById('pkgNombre').value,
        descripcion:  document.getElementById('pkgDesc').value || null,
        precio_venta: parseFloat(document.getElementById('pkgPrecioVenta').value) || 0,
        frecuencia:   document.getElementById('pkgFrecuencia').value,
        enlace:       document.getElementById('pkgEnlace').value || null,
        features:     pkgFeatures,
        items,
    };
    if (id) payload.id = parseInt(id);
    try {
        const r = await fetch('api/paquetes.php', { method: id ? 'PUT' : 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const d = await r.json();
        if (!d.success) { showToast(d.error,'error'); return; }

        // Subir imagen si hay pendiente
        const pkgId = id || d.id;
        if (pkgImgPendiente && pkgId) {
            const fd = new FormData();
            fd.append('id', pkgId);
            fd.append('imagen', pkgImgPendiente);
            await fetch('api/paquete_imagen.php', { method:'POST', body: fd });
            pkgImgPendiente = null;
        }

        showToast(d.message, 'success');
        closePkgModal();
        await Promise.all([loadSvcs(), loadPkgs()]);
    } catch(e) { showToast('Error','error'); }
}

async function deletePkg(id) {
    const ok = await confirmAction('El paquete será eliminado permanentemente.', { title: '¿Eliminar paquete?' });
    if (!ok) return;
    try {
        const r = await fetch(`api/paquetes.php?id=${id}`, { method:'DELETE' });
        const d = await r.json();
        if (d.success) { showToast('Paquete eliminado','success'); await Promise.all([loadSvcs(), loadPkgs()]); }
    } catch(e) { showToast('Error','error'); }
}

/* ── DUPLICAR ───────────────────────────────────────────────── */
function dupSvc(id) {
    const s = allSvcs.find(x => x.id == id);
    if (!s) return;
    const copia = {...s, id: null};
    copia.nombre = s.nombre + ' (copia)';
    openSvcModal(copia);
}

function dupSub(svcId, ss) {
    const copia = {...ss, id: null};
    copia.nombre = ss.nombre + ' (copia)';
    openSubModal(svcId, copia);
}

function dupPkg(id) {
    const p = allPkgs.find(x => x.id == id);
    if (!p) return;
    const copia = {...p, id: null};
    copia.nombre = p.nombre + ' (copia)';
    openPkgModal(copia);
}

/* ── EVENTOS ─────────────────────────────────────────────────── */
document.getElementById('svcModal').addEventListener('click', function(e){ if(e.target===this) closeSvcModal(); });
document.getElementById('subModal').addEventListener('click', function(e){ if(e.target===this) closeSubModal(); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeSvcModal(); closeSubModal(); closePreviewSub(); closePkgModal(); closePreviewPkg(); } });

/* ── DRAG & DROP ORDEN ──────────────────────────────────────── */
function initDragEl(container, tabla) {
    if (!container) return;
    const getItems = () => [...container.querySelectorAll(':scope > [data-id]')];
    let _dragSrc = null;
    let _dragOver = null;

    getItems().forEach(el => {
        el.draggable = true;

        el.addEventListener('dragstart', e => {
            _dragSrc  = el;
            _dragOver = null;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', el.dataset.id);
            setTimeout(() => { el.style.opacity = '.35'; el.style.transform = 'scale(.98)'; }, 0);
        });

        el.addEventListener('dragend', () => {
            el.style.opacity = '';
            el.style.transform = '';
            getItems().forEach(c => c.classList.remove('drag-over'));
            _dragSrc = _dragOver = null;
        });

        el.addEventListener('dragenter', e => {
            e.preventDefault();
            if (el === _dragSrc) return;
            getItems().forEach(c => c.classList.remove('drag-over'));
            el.classList.add('drag-over');
            _dragOver = el;
        });

        el.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        el.addEventListener('drop', async e => {
            e.preventDefault();
            e.stopPropagation();
            if (!_dragSrc || _dragSrc === el) return;

            // Insertar en DOM
            const items = getItems();
            const srcIdx = items.indexOf(_dragSrc);
            const tgtIdx = items.indexOf(el);
            if (srcIdx < tgtIdx) el.after(_dragSrc); else el.before(_dragSrc);

            el.classList.remove('drag-over');

            // Guardar orden
            const ordered = getItems().map((c, i) => ({ id: c.dataset.id, orden: i }));
            try {
                await fetch('api/orden.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ tabla, items: ordered }) });
            } catch(e) { showToast('Error al guardar orden','error'); }
        });
    });

    // Drop en el contenedor vacío (espacio libre al final)
    container.addEventListener('dragover', e => e.preventDefault());
    container.addEventListener('drop', async e => {
        e.preventDefault();
        if (!_dragSrc || _dragOver) return; // ya fue manejado por un item
        container.appendChild(_dragSrc);
        const ordered = getItems().map((c, i) => ({ id: c.dataset.id, orden: i }));
        await fetch('api/orden.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ tabla, items: ordered }) });
    });
}

function initDrag(containerId, tabla) {
    initDragEl(document.getElementById(containerId), tabla);
}

// Estilos drag
const _dragStyle = document.createElement('style');
_dragStyle.textContent = `
[data-id].drag-over { outline:2px dashed #6366f1; outline-offset:3px; border-radius:14px; background:#f5f3ff !important; }
[data-id] { transition: opacity .15s, transform .15s; }
.drag-handle { opacity: .25; cursor: grab; flex-shrink:0; }
[data-id]:hover .drag-handle { opacity: .5; }
`;
document.head.appendChild(_dragStyle);

loadSvcs();
loadPkgs();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
