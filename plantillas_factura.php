<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$pageTitle    = 'Plantillas de Cobro';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;opacity:.65;text-decoration:none;transition:opacity .12s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a> › <a href="facturacion.php" style="color:inherit;opacity:.65;text-decoration:none;transition:opacity .12s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Facturación</a> › <span style="font-weight:700;color:var(--color-text)">Plantillas de Cobro</span>';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<div class="page-header" style="margin-bottom:20px">
    <div class="page-header-left" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1">
        <div style="position:relative">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--color-text-light);pointer-events:none">
                <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" id="pfFiltroNombre" placeholder="Buscar plantilla…" oninput="filtrarGaleria()"
                style="width:200px;padding:8px 12px 8px 33px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;background:#fff;color:var(--color-text);outline:none"
                onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
        </div>
        <select id="pfFiltroCategoria" onchange="filtrarGaleria()"
            style="padding:8px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;background:#fff;color:var(--color-text);outline:none;cursor:pointer">
            <option value="">Todas las categorías</option>
            <option value="cotizacion">Cotización</option>
            <option value="cuenta_cobro">Cuenta de cobro</option>
            <option value="orden_compra">Orden de compra</option>
            <option value="orden_renovacion">Orden de Renovación</option>
            <option value="propuesta">Propuesta</option>
            <option value="recibo">Recibo</option>
            <option value="otro">Otro</option>
        </select>
        <span id="pfFiltroConteo" style="font-size:12px;color:var(--color-text-muted);white-space:nowrap"></span>
    </div>
    <div class="page-header-right" style="display:flex;align-items:center;gap:10px">
        <span style="font-size:11px;font-weight:700;color:var(--color-text-muted);background:var(--color-surface);border:1.5px solid var(--color-border);padding:6px 12px;border-radius:var(--radius-sm);white-space:nowrap">
            Datos de muestra
        </span>
        <button class="btn btn-accent" onclick="abrirFormNueva()">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Nueva plantilla
        </button>
    </div>
</div>

<!-- ── VISTA GALERÍA (default) ────────────────────────────────────────── -->
<div id="vistaGaleria">
    <div id="plantillasGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--color-text-light);font-size:13px">Cargando plantillas...</div>
    </div>
</div>

<!-- ── VISTA EDITOR (hidden) ──────────────────────────────────────────── -->
<div id="vistaEditor" style="display:none">
<div style="display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start">

    <!-- ══════════════════════════════════════
         PANEL IZQUIERDO — FORMULARIO CONFIG
    ═══════════════════════════════════════ -->
    <div>
        <!-- ── FORMULARIO CONFIG ───────────────── -->
        <div id="configForm" style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);overflow:hidden">

            <!-- Form header -->
            <div style="padding:12px 16px;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;background:var(--color-surface)">
                <div style="display:flex;align-items:center;gap:10px">
                    <button onclick="mostrarGaleria()" style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:none;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:11px;font-weight:700;color:var(--color-text-muted);cursor:pointer" onmouseenter="this.style.borderColor='var(--color-text)'" onmouseleave="this.style.borderColor='var(--color-border)'">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Galería
                    </button>
                    <div style="font-size:13px;font-weight:700;color:var(--color-text)" id="formTitulo">Nueva plantilla</div>
                </div>
                <input type="hidden" id="pfId" value="">
            </div>

            <div style="padding:16px 18px;display:flex;flex-direction:column;gap:12px;max-height:calc(100vh - 280px);overflow-y:auto">

                <!-- Nombre -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Nombre *</label>
                    <input type="text" id="pfNombre" placeholder="Ej: Cobro Corporativo" oninput="actualizarPreview()"
                        style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
                </div>

                <!-- Categoría -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Categoría del documento</label>
                    <select id="pfCategoria" onchange="actualizarPreview()" style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
                        <option value="cotizacion">Cotización</option>
                        <option value="cuenta_cobro">Cuenta de cobro</option>
                        <option value="orden_compra">Orden de compra</option>
                        <option value="propuesta">Propuesta</option>
                        <option value="recibo">Recibo</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <!-- Layout tipo (oculto — solo Clásica) -->
                <input type="hidden" id="layout-clasica" value="clasica">

                <!-- Colores -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Color primario</label>
                        <div style="display:flex;align-items:center;gap:6px">
                            <input type="color" id="pfColorPrimario" value="#0f172a" onchange="actualizarPreview()"
                                style="width:36px;height:36px;border:1.5px solid var(--color-border);border-radius:6px;cursor:pointer;padding:2px">
                            <input type="text" id="pfColorPrimarioHex" value="#0f172a" oninput="sincColorPrimario()"
                                style="flex:1;padding:7px 8px;border:1.5px solid var(--color-border);border-radius:6px;font-size:11px;font-family:monospace;outline:none" maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Color acento</label>
                        <div style="display:flex;align-items:center;gap:6px">
                            <input type="color" id="pfColorSecundario" value="#c9f31d" onchange="actualizarPreview()"
                                style="width:36px;height:36px;border:1.5px solid var(--color-border);border-radius:6px;cursor:pointer;padding:2px">
                            <input type="text" id="pfColorSecundarioHex" value="#c9f31d" oninput="sincColorSecundario()"
                                style="flex:1;padding:7px 8px;border:1.5px solid var(--color-border);border-radius:6px;font-size:11px;font-family:monospace;outline:none" maxlength="7">
                        </div>
                    </div>
                </div>

                <!-- Fuente -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Tipografía</label>
                    <select id="pfFuente" onchange="actualizarPreview()" style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
                        <option value="Poppins">Poppins</option>
                        <option value="Montserrat">Montserrat</option>
                        <option value="Inter">Inter</option>
                        <option value="Georgia">Georgia (Serif)</option>
                        <option value="Arial">Arial</option>
                    </select>
                </div>

                <!-- Separador -->
                <div style="border-top:1px solid var(--color-border-light);padding-top:12px">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-muted);margin-bottom:10px">Datos de tu empresa</div>
                </div>

                <!-- Logo URL -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">URL del Logo</label>
                    <input type="text" id="pfLogoUrl" placeholder="https://tudominio.com/logo.png" oninput="actualizarPreview(); previewLogo(this.value)"
                        style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
                    <!-- Preview del logo -->
                    <div id="logoPreviewBox" style="display:none;margin-top:8px;padding:10px;background:var(--color-surface);border:1.5px solid var(--color-border);border-radius:var(--radius-sm);text-align:center">
                        <img id="logoPreviewImg" src="" alt="Preview logo" style="max-height:48px;max-width:160px;object-fit:contain"
                            onload="document.getElementById('logoPreviewStatus').textContent='✓ Logo cargado correctamente';document.getElementById('logoPreviewStatus').style.color='#16a34a'"
                            onerror="document.getElementById('logoPreviewStatus').textContent='✗ No se pudo cargar la imagen';document.getElementById('logoPreviewStatus').style.color='#dc2626'">
                        <div id="logoPreviewStatus" style="font-size:10px;margin-top:5px;color:var(--color-text-muted)"></div>
                    </div>
                </div>

                <!-- Grid empresa -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div style="grid-column:1/-1">
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Nombre empresa</label>
                        <input type="text" id="pfEmpresaNombre" placeholder="QUANTUN Digital S.A.S" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">NIT</label>
                        <input type="text" id="pfEmpresaNit" placeholder="900.000.000-0" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Email</label>
                        <input type="email" id="pfEmpresaEmail" placeholder="hola@empresa.com" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Teléfono</label>
                        <input type="text" id="pfEmpresaTel" placeholder="+57 300 000 0000" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                    <div style="grid-column:1/-1">
                        <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Dirección</label>
                        <input type="text" id="pfEmpresaDir" placeholder="Calle 123 #45-67, Bogotá" oninput="actualizarPreview()"
                            style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
                    </div>
                </div>

                <!-- Notas al pie -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);display:block;margin-bottom:4px">Notas al pie</label>
                    <textarea id="pfNotasPie" rows="2" placeholder="Ej: Gracias por su preferencia. Pago en 30 días." oninput="actualizarPreview()"
                        style="width:100%;padding:8px 11px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:12px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical" onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'"></textarea>
                </div>

                <!-- Toggle datos bancarios -->
                <label style="display:flex;align-items:center;gap:9px;cursor:pointer;padding:10px 12px;background:var(--color-surface);border-radius:var(--radius-sm);border:1.5px solid var(--color-border)">
                    <input type="checkbox" id="pfMostrarBanco" onchange="actualizarPreview()" style="width:16px;height:16px;cursor:pointer;accent-color:var(--color-text)">
                    <div>
                        <div style="font-size:12px;font-weight:700;color:var(--color-text)">Incluir datos bancarios</div>
                        <div style="font-size:11px;color:var(--color-text-light)">Muestra cuenta bancaria al pie del documento</div>
                    </div>
                </label>

                <!-- Default check -->
                <label style="display:flex;align-items:center;gap:9px;cursor:pointer;padding:10px 12px;background:var(--color-surface);border-radius:var(--radius-sm);border:1.5px solid var(--color-border)">
                    <input type="checkbox" id="pfEsDefault" style="width:16px;height:16px;cursor:pointer;accent-color:var(--color-text)">
                    <div>
                        <div style="font-size:12px;font-weight:700;color:var(--color-text)">Plantilla predeterminada</div>
                        <div style="font-size:11px;color:var(--color-text-light)">Se usará automáticamente en nuevos cobros</div>
                    </div>
                </label>
            </div>

            <!-- Footer botones -->
            <div style="padding:14px 18px;border-top:1px solid var(--color-border-light);display:flex;gap:8px;justify-content:flex-end;background:var(--color-surface)">
                <button class="btn btn-ghost btn-sm" onclick="abrirFormNueva()">Limpiar</button>
                <button id="pfBtnGuardar" class="btn btn-primary btn-sm" onclick="guardarPlantilla()">
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
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
            <div style="width:7px;height:7px;border-radius:50%;background:#22c55e;animation:pulse 2s infinite"></div>
            <span style="font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Vista Previa · En tiempo real</span>
        </div>

        <!-- Preview container -->
        <div style="background:#E8E5DD;border-radius:var(--radius-sm);padding:20px;min-height:600px">
            <div style="background:#fff;border-radius:var(--radius-sm);box-shadow:0 4px 20px rgba(0,0,0,.10);overflow:hidden">
                <div id="facturaPreview" style="width:100%;font-family:Poppins,sans-serif">
                    <!-- JS renders here -->
                </div>
            </div>
        </div>
    </div>
</div><!-- end grid -->
</div><!-- end vistaEditor -->

<!-- Modal confirmar eliminar -->
<div id="modalEliminarPlantilla" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:var(--radius-lg);width:100%;max-width:400px;padding:28px;box-shadow:0 24px 40px rgba(0,0,0,.2);text-align:center">
    <div style="width:52px;height:52px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <svg width="24" height="24" fill="none" stroke="#ef4444" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </div>
    <h3 style="font-size:16px;font-weight:800;color:var(--color-text);margin:0 0 8px">¿Eliminar plantilla de cobro?</h3>
    <p id="modalElimPfNombre" style="font-size:13px;color:var(--color-text-muted);margin:0 0 24px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('modalEliminarPlantilla').style.display='none'" style="padding:10px 20px;background:transparent;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-weight:700;cursor:pointer;font-size:13px;color:var(--color-text-muted)">Cancelar</button>
      <button id="btnConfirmarElimPf" style="padding:10px 20px;background:#ef4444;color:#fff;border:none;border-radius:var(--radius-sm);font-weight:700;cursor:pointer;font-size:13px">Eliminar</button>
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
