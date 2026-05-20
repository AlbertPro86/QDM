<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$pageTitle    = 'Plantillas de Facturas';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;opacity:.65;text-decoration:none;transition:opacity .12s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a> › <a href="facturacion.php" style="color:inherit;opacity:.65;text-decoration:none;transition:opacity .12s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Facturación</a> › <span style="color:var(--color-primary);font-weight:700">Plantillas</span>';
include __DIR__ . '/includes/header.php';
?>

<!-- ── LAYOUT PRINCIPAL: Config | Preview ─────────────────────────────── -->
<div style="display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start">

    <!-- ══════════════════════════════════════
         PANEL IZQUIERDO — CONFIG + GALERÍA
    ═══════════════════════════════════════ -->
    <div>
        <!-- Header panel -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <h2 style="font-size:15px;font-weight:800;color:#0f172a;margin:0">Mis Plantillas</h2>
            <button onclick="abrirFormNueva()"
                style="display:flex;align-items:center;gap:5px;padding:7px 14px;background:#c9f31d;color:#0f172a;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                Nueva
            </button>
        </div>

        <!-- Galería de plantillas -->
        <div id="plantillasGrid" style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px">
            <div style="text-align:center;padding:32px;color:#94a3b8;font-size:13px;background:#f8fafc;border-radius:10px">Cargando plantillas...</div>
        </div>

        <!-- ── FORMULARIO CONFIG ───────────────── -->
        <div id="configForm" style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden">

            <!-- Form header -->
            <div style="padding:14px 18px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:#f8fafc">
                <div>
                    <div style="font-size:14px;font-weight:800;color:#0f172a" id="formTitulo">Nueva plantilla</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:1px">Personaliza y previsualiza en tiempo real</div>
                </div>
                <input type="hidden" id="pfId" value="">
            </div>

            <div style="padding:16px 18px;display:flex;flex-direction:column;gap:12px;max-height:calc(100vh - 280px);overflow-y:auto">

                <!-- Nombre -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Nombre *</label>
                    <input type="text" id="pfNombre" placeholder="Ej: Factura Corporativa" oninput="actualizarPreview()"
                        style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <!-- Layout tipo -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:8px">Diseño</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <button type="button" id="layout-clasica" onclick="setLayout('clasica',this)"
                            style="padding:10px 6px;border:2px solid #0f172a;border-radius:8px;background:#0f172a;color:#fff;cursor:pointer;font-size:11px;font-weight:700;transition:all .12s">
                            Clásica
                        </button>
                        <button type="button" id="layout-moderna" onclick="setLayout('moderna',this)"
                            style="padding:10px 6px;border:2px solid #e2e8f0;border-radius:8px;background:#fff;color:#64748b;cursor:pointer;font-size:11px;font-weight:700;transition:all .12s">
                            Moderna
                        </button>
                        <button type="button" id="layout-minimalista" onclick="setLayout('minimalista',this)"
                            style="padding:10px 6px;border:2px solid #e2e8f0;border-radius:8px;background:#fff;color:#64748b;cursor:pointer;font-size:11px;font-weight:700;transition:all .12s">
                            ✦ Minimalista
                        </button>
                        <button type="button" id="layout-ejecutiva" onclick="setLayout('ejecutiva',this)"
                            style="padding:10px 6px;border:2px solid #e2e8f0;border-radius:8px;background:#fff;color:#64748b;cursor:pointer;font-size:11px;font-weight:700;transition:all .12s">
                            ▣ Ejecutiva
                        </button>
                    </div>
                </div>

                <!-- Colores -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Color primario</label>
                        <div style="display:flex;align-items:center;gap:6px">
                            <input type="color" id="pfColorPrimario" value="#0f172a" onchange="actualizarPreview()"
                                style="width:36px;height:36px;border:1.5px solid #e2e8f0;border-radius:6px;cursor:pointer;padding:2px">
                            <input type="text" id="pfColorPrimarioHex" value="#0f172a" oninput="sincColorPrimario()"
                                style="flex:1;padding:7px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:11px;font-family:monospace;outline:none" maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Color acento</label>
                        <div style="display:flex;align-items:center;gap:6px">
                            <input type="color" id="pfColorSecundario" value="#c9f31d" onchange="actualizarPreview()"
                                style="width:36px;height:36px;border:1.5px solid #e2e8f0;border-radius:6px;cursor:pointer;padding:2px">
                            <input type="text" id="pfColorSecundarioHex" value="#c9f31d" oninput="sincColorSecundario()"
                                style="flex:1;padding:7px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:11px;font-family:monospace;outline:none" maxlength="7">
                        </div>
                    </div>
                </div>

                <!-- Fuente -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Tipografía</label>
                    <select id="pfFuente" onchange="actualizarPreview()" style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
                        <option value="Poppins">Poppins</option>
                        <option value="Montserrat">Montserrat</option>
                        <option value="Inter">Inter</option>
                        <option value="Georgia">Georgia (Serif)</option>
                        <option value="Arial">Arial</option>
                    </select>
                </div>

                <!-- Separador -->
                <div style="border-top:1.5px solid #f1f5f9;padding-top:12px">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:10px">Datos de tu empresa</div>
                </div>

                <!-- Logo URL -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">URL del Logo</label>
                    <input type="text" id="pfLogoUrl" placeholder="https://tudominio.com/logo.png" oninput="actualizarPreview(); previewLogo(this.value)"
                        style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
                    <!-- Preview del logo -->
                    <div id="logoPreviewBox" style="display:none;margin-top:8px;padding:10px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;text-align:center">
                        <img id="logoPreviewImg" src="" alt="Preview logo" style="max-height:48px;max-width:160px;object-fit:contain"
                            onload="document.getElementById('logoPreviewStatus').textContent='✓ Logo cargado correctamente';document.getElementById('logoPreviewStatus').style.color='#16a34a'"
                            onerror="document.getElementById('logoPreviewStatus').textContent='✗ No se pudo cargar la imagen';document.getElementById('logoPreviewStatus').style.color='#dc2626'">
                        <div id="logoPreviewStatus" style="font-size:10px;margin-top:5px;color:#64748b"></div>
                    </div>
                </div>

                <!-- Grid empresa -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div style="grid-column:1/-1">
                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Nombre empresa</label>
                        <input type="text" id="pfEmpresaNombre" placeholder="QUANTUN Digital S.A.S" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">NIT</label>
                        <input type="text" id="pfEmpresaNit" placeholder="900.000.000-0" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Email</label>
                        <input type="email" id="pfEmpresaEmail" placeholder="hola@empresa.com" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Teléfono</label>
                        <input type="text" id="pfEmpresaTel" placeholder="+57 300 000 0000" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div style="grid-column:1/-1">
                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Dirección</label>
                        <input type="text" id="pfEmpresaDir" placeholder="Calle 123 #45-67, Bogotá" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <!-- Notas al pie -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Notas al pie de factura</label>
                    <textarea id="pfNotasPie" rows="2" placeholder="Ej: Gracias por su preferencia. Pago en 30 días." oninput="actualizarPreview()"
                        style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>

                <!-- Default check -->
                <label style="display:flex;align-items:center;gap:9px;cursor:pointer;padding:10px 12px;background:#f8fafc;border-radius:8px;border:1.5px solid #e2e8f0">
                    <input type="checkbox" id="pfEsDefault" style="width:16px;height:16px;cursor:pointer;accent-color:#0f172a">
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#0f172a">Plantilla predeterminada</div>
                        <div style="font-size:11px;color:#94a3b8">Se usará automáticamente en nuevas facturas</div>
                    </div>
                </label>
            </div>

            <!-- Footer botones -->
            <div style="padding:14px 18px;border-top:1.5px solid #f1f5f9;display:flex;gap:8px;justify-content:flex-end;background:#f8fafc">
                <button onclick="abrirFormNueva()" style="padding:9px 16px;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px" onmouseenter="this.style.background='#fff'" onmouseleave="this.style.background='transparent'">Limpiar</button>
                <button id="pfBtnGuardar" onclick="guardarPlantilla()"
                    style="display:flex;align-items:center;gap:6px;padding:9px 20px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px" onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='#0f172a'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Guardar plantilla
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         PANEL DERECHO — VISTA PREVIA
    ═══════════════════════════════════════ -->
    <div style="position:sticky;top:24px">
        <!-- Header preview -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:8px;height:8px;border-radius:50%;background:#22c55e;animation:pulse 2s infinite"></div>
                <span style="font-size:13px;font-weight:700;color:#0f172a">Vista Previa · En tiempo real</span>
            </div>
            <span style="font-size:11px;color:#94a3b8;background:#f1f5f9;padding:3px 10px;border-radius:20px">Datos de muestra</span>
        </div>

        <!-- Preview container -->
        <div style="background:#e2e8f0;border-radius:14px;padding:20px;min-height:600px">
            <div style="background:#fff;border-radius:8px;box-shadow:0 8px 32px rgba(0,0,0,.12);overflow:hidden">
                <div id="facturaPreview" style="width:100%;font-family:Poppins,sans-serif">
                    <!-- JS renders here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmar eliminar -->
<div id="modalEliminarPlantilla" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:14px;width:100%;max-width:400px;padding:28px;box-shadow:0 24px 40px rgba(0,0,0,.2);text-align:center">
    <div style="width:52px;height:52px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <svg width="24" height="24" fill="none" stroke="#ef4444" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </div>
    <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 8px">¿Eliminar plantilla?</h3>
    <p id="modalElimPfNombre" style="font-size:13px;color:#64748b;margin:0 0 24px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('modalEliminarPlantilla').style.display='none'" style="padding:10px 20px;background:transparent;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;color:#64748b">Cancelar</button>
      <button id="btnConfirmarElimPf" style="padding:10px 20px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">Eliminar</button>
    </div>
  </div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>

<script>
document.getElementById('modalEliminarPlantilla').addEventListener('click', e => {
    if (e.target === document.getElementById('modalEliminarPlantilla'))
        document.getElementById('modalEliminarPlantilla').style.display = 'none';
});
</script>
<script src="js/plantillas_factura.js?v=<?= APP_VERSION ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
