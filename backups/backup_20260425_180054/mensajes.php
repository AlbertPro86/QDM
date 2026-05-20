<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$currentPage  = 'mensajes';
$pageTitle    = 'Mensajes';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Mensajes</span>';
include __DIR__ . '/includes/header.php';
?>

<!-- ── Tabla de plantillas ───────────────────────────────────────── -->
<div style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);overflow:hidden">
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:var(--color-surface);border-bottom:1.5px solid var(--color-border)">
                    <th style="width:36px;padding:12px 16px;text-align:center"><input type="checkbox" id="chkTodas" style="cursor:pointer;accent-color:#4f46e5;width:16px;height:16px" onchange="toggleTodas()"></th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Nombre</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Descripción</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Tipo</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Previsualización</th>
                    <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em">Acciones</th>
                </tr>
            </thead>
            <tbody id="plantillasContainer">
                <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--color-text-light)">Cargando plantillas...</td></tr>
            </tbody>
        </table>
    </div>
    <!-- Nueva plantilla button -->
    <div style="padding:16px;border-top:1.5px solid var(--color-border);background:var(--color-surface)">
        <button onclick="abrirModalCrear()"
            style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;background:var(--color-primary);color:#c9f31d;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:background .15s;font-family:inherit"
            onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='var(--color-primary)'">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12"/></svg>
            Nueva plantilla
        </button>
    </div>
</div>


<!-- ── Modal crear/editar ───────────────────────────────────────── -->
<div id="modalPlantilla" class="modal-overlay">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <div>
                <h3 id="modalTitle" class="modal-title">Nueva plantilla</h3>
                <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Usa variables como {{cliente_nombre}}, {{numero_cotizacion}}</p>
            </div>
            <button class="modal-close" onclick="cerrarModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Nombre de la plantilla *</label>
                <input type="text" id="inputNombre" class="form-input" placeholder="Ej: Confirmación de Cotización">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Categoría</label>
                    <select id="inputCategoria" class="form-select">
                        <option value="general">General</option>
                        <option value="cotizaciones">Cotizaciones</option>
                        <option value="confirmaciones">Confirmaciones</option>
                        <option value="recordatorios">Recordatorios</option>
                        <option value="seguimiento">Seguimiento</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Descripción</label>
                    <input type="text" id="inputDescripcion" class="form-input" placeholder="¿Cuándo usar esta plantilla?">
                </div>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Contenido del mensaje *</label>
                <textarea id="inputContenido" class="form-textarea" rows="5"
                    style="font-family:'Monaco','Courier New',monospace;font-size:12px;resize:vertical"
                    placeholder="Hola {{cliente_nombre}}, adjuntamos la cotización #{{numero_cotizacion}}..."></textarea>
            </div>
            <!-- Imagen adjunta -->
            <div class="form-group" style="margin:0">
                <label class="form-label">Imagen adjunta <span style="font-weight:400;color:#94a3b8">(opcional · JPG, PNG, WEBP · máx. 5MB)</span></label>
                <!-- Zona de drop / clic -->
                <div id="imgZona"
                    onclick="document.getElementById('inputImagen').click()"
                    ondragover="event.preventDefault();this.style.borderColor='var(--color-primary)'"
                    ondragleave="this.style.borderColor='#e2e8f0'"
                    ondrop="soltar(event)"
                    style="border:2px dashed #e2e8f0;border-radius:10px;padding:18px 14px;text-align:center;cursor:pointer;transition:border-color .15s;background:#fafbfc;position:relative">
                    <!-- estado: sin imagen -->
                    <div id="imgPlaceholder">
                        <svg width="28" height="28" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 6px;display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                        <div style="font-size:12px;color:#94a3b8">Arrastra una imagen o <span style="color:#4f46e5;font-weight:700">haz clic para seleccionar</span></div>
                    </div>
                    <!-- estado: con imagen -->
                    <div id="imgPreviewBox" style="display:none;flex-direction:column;align-items:center;gap:8px">
                        <img id="imgPreview" src="" alt="preview"
                            style="max-height:110px;max-width:100%;border-radius:8px;object-fit:contain;box-shadow:0 2px 8px rgba(0,0,0,.10)">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span id="imgNombre" style="font-size:11px;color:#475569;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
                            <button type="button" onclick="quitarImagen(event)"
                                style="border:none;background:#fee2e2;color:#dc2626;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:700;cursor:pointer">✕ Quitar</button>
                        </div>
                    </div>
                    <!-- imagen existente al editar -->
                    <div id="imgExistenteBox" style="display:none;flex-direction:column;align-items:center;gap:8px">
                        <img id="imgExistente" src="" alt="actual"
                            style="max-height:110px;max-width:100%;border-radius:8px;object-fit:contain;box-shadow:0 2px 8px rgba(0,0,0,.10)">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:11px;color:#64748b">Imagen actual</span>
                            <button type="button" onclick="quitarImagenExistente(event)"
                                style="border:none;background:#fee2e2;color:#dc2626;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:700;cursor:pointer">✕ Quitar</button>
                            <button type="button" onclick="document.getElementById('inputImagen').click();event.stopPropagation()"
                                style="border:none;background:#eef2ff;color:#4f46e5;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:700;cursor:pointer">↺ Cambiar</button>
                        </div>
                    </div>
                </div>
                <input type="file" id="inputImagen" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="previewImagen(this)">
                <input type="hidden" id="inputQuitarImagen" value="">
            </div>

            <!-- Logo URL -->
            <div class="form-group" style="margin:0">
                <label class="form-label">URL del Logo <span style="font-weight:400;color:#94a3b8">(opcional · URL de imagen externa)</span></label>
                <input type="text" id="inputLogoUrl" class="form-input"
                    placeholder="https://tudominio.com/logo.png"
                    oninput="previewLogoMensaje(this.value)"
                    style="font-size:12px">
                <!-- Preview del logo -->
                <div id="logoMensajePreviewBox" style="display:none;margin-top:8px;padding:10px 14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;display:none;align-items:center;gap:12px">
                    <img id="logoMensajePreviewImg" src="" alt="Logo preview"
                        style="max-height:44px;max-width:140px;object-fit:contain;border-radius:4px"
                        onload="document.getElementById('logoMensajeStatus').textContent='✓ Logo cargado';document.getElementById('logoMensajeStatus').style.color='#16a34a'"
                        onerror="document.getElementById('logoMensajeStatus').textContent='✗ No se pudo cargar';document.getElementById('logoMensajeStatus').style.color='#dc2626'">
                    <span id="logoMensajeStatus" style="font-size:11px;color:#64748b"></span>
                </div>
            </div>

            <div style="padding:10px 12px;background:#f8fafc;border-radius:8px;border:1.5px solid #e2e8f0">
                <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Variables disponibles</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    <?php foreach(['cliente_nombre','numero_cotizacion','total','moneda','fecha','vigencia'] as $v): ?>
                    <code onclick="insertarVariable('{{<?=$v?>}}')" style="padding:3px 8px;background:#eef2ff;color:#4f46e5;border-radius:5px;font-size:11px;cursor:pointer;font-weight:600" title="Clic para insertar">{{<?=$v?>}}</code>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
            <button class="btn btn-secondary" id="btnGuardar" onclick="guardarPlantilla()">Guardar plantilla</button>
        </div>
    </div>
</div>

<!-- ── Modal envío WhatsApp ───────────────────────────────────────────────── -->
<div id="modalEnvioWA" class="modal-overlay">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" style="display:flex;align-items:center;gap:8px">
                    <svg width="17" height="17" fill="#25D366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                    Preparar mensaje de WhatsApp
                </h3>
                <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Completa las variables y revisa el mensaje antes de enviarlo</p>
            </div>
            <button class="modal-close" onclick="cerrarModalWA()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:16px">

            <!-- Variables dinámicas -->
            <div id="waVariablesBox" style="display:none">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">Reemplazar variables</div>
                <div id="waVariablesGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px"></div>
            </div>

            <!-- Preview del mensaje -->
            <div>
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Vista previa del mensaje</div>
                <div id="waPreviewTexto"
                    style="padding:14px 16px;background:#dcfce7;border-radius:12px 12px 12px 0;
                           font-size:13px;color:#0f172a;line-height:1.6;white-space:pre-wrap;word-break:break-word;
                           box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #bbf7d0;min-height:60px"></div>
            </div>

            <!-- Imagen adjunta (si existe) -->
            <div id="waImagenBox" style="display:none">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Imagen adjunta</div>
                <div style="display:flex;align-items:center;gap:14px;padding:12px;background:#f8fafc;border-radius:10px;border:1.5px solid #e2e8f0">
                    <img id="waImagenThumb" src="" alt="imagen"
                        style="width:64px;height:64px;border-radius:8px;object-fit:cover;flex-shrink:0">
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:4px">Imagen adjunta a esta plantilla</div>
                        <div style="font-size:11px;color:#64748b;margin-bottom:8px">Descárgala y adjúntala manualmente en WhatsApp junto con el mensaje.</div>
                        <a id="waDescargarBtn" href="#" download
                            style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:#0f172a;color:#fff;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Descargar imagen
                        </a>
                    </div>
                </div>
            </div>

        </div>
        <div class="modal-footer" style="gap:8px">
            <button class="btn btn-outline" onclick="cerrarModalWA()">Cancelar</button>
            <button onclick="abrirWhatsApp()"
                style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#25D366;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                Abrir WhatsApp
            </button>
        </div>
    </div>
</div>

<style>
.msg-card {
    background:#fff;
    border-radius:14px;
    padding:20px;
    display:flex;
    flex-direction:column;
    gap:14px;
    box-shadow:0 1px 3px rgba(0,0,0,.06),0 2px 8px rgba(0,0,0,.04);
    transition:box-shadow .15s,transform .15s;
    border:1.5px solid #f1f5f9;
}
.msg-card:hover {
    box-shadow:0 4px 16px rgba(0,0,0,.10);
    transform:translateY(-2px);
}
.msg-badge-pre  { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:#0f172a;color:#c9f31d;border-radius:20px;font-size:10px;font-weight:700 }
.msg-badge-pers { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:#eef2ff;color:#4f46e5;border-radius:20px;font-size:10px;font-weight:700 }
.msg-preview {
    padding:10px 12px;
    background:#f8fafc;
    border-radius:8px;
    font-size:11.5px;
    color:#475569;
    line-height:1.55;
    max-height:68px;
    overflow:hidden;
    border:1px solid #e2e8f0;
    font-style:italic;
}
.msg-btn {
    flex:1;min-width:72px;
    display:inline-flex;align-items:center;justify-content:center;gap:5px;
    padding:8px 10px;
    border:none;border-radius:8px;
    font-size:11px;font-weight:700;
    cursor:pointer;transition:filter .12s;
    white-space:nowrap;
}
.msg-btn:hover { filter:brightness(.92); }
.msg-btn-copy { background:#0f172a;color:#fff; }
.msg-btn-wa   { background:#c9f31d;color:#0f172a; }
.msg-btn-edit { background:#eef2ff;color:#4f46e5; }
.msg-btn-del  { background:#fee2e2;color:#dc2626; }
.msg-img-thumb {
    width:100%;height:100px;
    object-fit:cover;
    border-radius:10px;
    display:block;
}
.msg-img-wrap {
    position:relative;overflow:hidden;border-radius:10px;
    border:1.5px solid #f1f5f9;
}
.msg-img-wrap::after {
    content:'';position:absolute;inset:0;
    background:linear-gradient(to bottom, transparent 40%, rgba(0,0,0,.18));
    border-radius:10px;pointer-events:none;
}
#imgZona:hover { border-color:var(--color-primary); }

/* Checkbox selección */
.card-chk {
    width:17px; height:17px;
    accent-color:#4f46e5;
    cursor:pointer;
    flex-shrink:0;
}
.msg-card.seleccionada {
    border-color:#4f46e5 !important;
    box-shadow:0 0 0 3px rgba(79,70,229,.15) !important;
    background:#f5f5ff;
}
</style>

<script>
// ── store: indexed by id ──────────────────────────────────────────────────────
const _store = {};
let plantillaEditando = null;

// ── Cargar ────────────────────────────────────────────────────────────────────
async function cargarPlantillas() {
    const filtro = new URLSearchParams(location.search).get('filtro') || 'todas';
    const box = document.getElementById('plantillasContainer');
    try {
        const r = await fetch('api/mensajes_plantillas.php?filtro=' + filtro);
        const d = await r.json();
        box.innerHTML = '';
        if (!d.success || !d.data?.length) {
            box.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8">No hay plantillas</td></tr>`;
            return;
        }
        d.data.forEach(p => { _store[p.id] = p; renderCard(box, p); });
    } catch(e) { box.innerHTML = '<tr><td colspan="6" style="padding:20px;text-align:center;color:#ef4444">Error al cargar plantillas</td></tr>'; }
}

function renderCard(container, p) {
    const row = document.createElement('tr');
    row.id = 'row-' + p.id;
    row.style.cssText = 'border-bottom:1px solid var(--color-border);transition:background .15s';
    row.onmouseenter = () => { row.style.background = 'var(--color-surface)'; };
    row.onmouseleave = () => { row.style.background = ''; };

    const badge = p.es_predefinida
        ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:#0f172a;color:#c9f31d;border-radius:20px;font-size:10px;font-weight:700"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Predefinida</span>'
        : '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:#eef2ff;color:#4f46e5;border-radius:20px;font-size:10px;font-weight:700"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Personal</span>';

    const preview = p.contenido.length > 70 ? p.contenido.substring(0, 70) + '…' : p.contenido;

    row.innerHTML = `
        <td style="padding:12px 16px;text-align:center;width:36px"><input type="checkbox" class="card-chk" id="chk-${p.id}" style="width:16px;height:16px;accent-color:#4f46e5;cursor:pointer" onchange="onCheck(${p.id},this.checked)"></td>
        <td style="padding:12px 16px;font-weight:700;color:#0f172a">${hesc(p.nombre)}</td>
        <td style="padding:12px 16px;font-size:12px;color:var(--color-text-muted)">${hesc(p.descripcion || '')}</td>
        <td style="padding:12px 16px">${badge}</td>
        <td style="padding:12px 16px;font-size:12px;color:#475569;max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">"${hesc(preview)}"</td>
        <td style="padding:10px 16px;text-align:center">
            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                <button onclick="abrirModalEditar(${p.id})" style="padding:6px 10px;background:#eef2ff;color:#4f46e5;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;transition:filter .12s" onmouseenter="this.style.filter='brightness(.95)'" onmouseleave="this.style.filter=''">
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:middle;display:inline-block;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar
                </button>
                <button onclick="clonarPlantilla(${p.id})" style="padding:6px 10px;background:#f0fdf4;color:#15803d;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;transition:filter .12s" onmouseenter="this.style.filter='brightness(.95)'" onmouseleave="this.style.filter=''">
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:middle;display:inline-block;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Clonar
                </button>
            </div>
        </td>
    `;
    container.appendChild(row);
}

// ── Acciones de tarjeta ───────────────────────────────────────────────────────
async function clonarPlantilla(id) {
    const p = _store[id]; if (!p) return;
    const nombreNuevo = 'Copia de ' + p.nombre;
    try {
        const r = await fetch('api/mensajes_plantillas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clonar', id, nombre: nombreNuevo })
        });
        const d = await r.json();
        if (d.success) {
            showToast('✓ Plantilla clonada como "' + nombreNuevo + '"', 'success');
            setTimeout(() => cargarPlantillas(), 800);
        } else {
            showToast(d.error || 'Error al clonar', 'error');
        }
    } catch(e) { showToast('Error de red', 'error'); }
}

// Variables disponibles en plantillas
const VARS_PLANTILLA = ['cliente_nombre','numero_cotizacion','total','moneda','fecha','vigencia'];

// Detecta qué variables usa el contenido
function detectarVariables(contenido) {
    const found = [];
    VARS_PLANTILLA.forEach(v => {
        if (contenido.includes('{{' + v + '}}')) found.push(v);
    });
    return found;
}

// Reemplaza variables con los valores del formulario WA
function reemplazarVariables(texto) {
    VARS_PLANTILLA.forEach(v => {
        const input = document.getElementById('waVar_' + v);
        if (input) {
            const valor = input.value.trim() || ('{{' + v + '}}');
            texto = texto.split('{{' + v + '}}').join(valor);
        }
    });
    return texto;
}

// Labels bonitos para cada variable
const VAR_LABELS = {
    cliente_nombre:      'Nombre del cliente',
    numero_cotizacion:   'Número de cotización',
    total:               'Total',
    moneda:              'Moneda',
    fecha:               'Fecha',
    vigencia:            'Vigencia (días)',
};

let _waPlantillaId = null;

function enviarWA(id) {
    const p = _store[id]; if (!p) return;
    _waPlantillaId = id;

    // Construir campos de variables
    const vars = detectarVariables(p.contenido);
    const grid = document.getElementById('waVariablesGrid');
    const box  = document.getElementById('waVariablesBox');
    grid.innerHTML = '';

    if (vars.length > 0) {
        box.style.display = '';
        vars.forEach(v => {
            const label = VAR_LABELS[v] || v;
            grid.innerHTML += `
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">${label}</label>
                    <input id="waVar_${v}" class="form-input" style="font-size:12px"
                        placeholder="{{${v}}}"
                        oninput="actualizarPreviewWA()">
                </div>`;
        });
    } else {
        box.style.display = 'none';
    }

    // Imagen
    const imgBox  = document.getElementById('waImagenBox');
    if (p.imagen) {
        imgBox.style.display = '';
        document.getElementById('waImagenThumb').src = p.imagen;
        const btnDl = document.getElementById('waDescargarBtn');
        btnDl.href     = p.imagen;
        btnDl.download = p.imagen.split('/').pop();
    } else {
        imgBox.style.display = 'none';
    }

    actualizarPreviewWA(p.contenido);
    document.getElementById('modalEnvioWA').classList.add('show');
}

function actualizarPreviewWA(base) {
    const p = _store[_waPlantillaId]; if (!p) return;
    const texto = reemplazarVariables(base || p.contenido);
    // Resaltar variables sin reemplazar
    const html = hesc(texto).replace(/\{\{[^}]+\}\}/g,
        m => `<mark style="background:#fef9c3;color:#92400e;border-radius:3px;padding:0 2px">${m}</mark>`);
    document.getElementById('waPreviewTexto').innerHTML = html;
}

function abrirWhatsApp() {
    const p = _store[_waPlantillaId]; if (!p) return;
    const texto = reemplazarVariables(p.contenido);
    window.open('https://wa.me/?text=' + encodeURIComponent(texto), '_blank');
    cerrarModalWA();
}

function cerrarModalWA() {
    document.getElementById('modalEnvioWA').classList.remove('show');
    _waPlantillaId = null;
}

function toggleTodas() {
    const chkTodas = document.getElementById('chkTodas');
    document.querySelectorAll('.card-chk').forEach(chk => { chk.checked = chkTodas.checked; });
    actualizarBtnEliminar();
}

function actualizarBtnEliminar() {
    const seleccionadas = document.querySelectorAll('.card-chk:checked').length;
    const btn = document.getElementById('btnEliminarSel');
    if (btn) {
        btn.style.display = seleccionadas > 0 ? 'inline-flex' : 'none';
        const cnt = document.getElementById('cntSel');
        if (cnt) cnt.textContent = seleccionadas;
    }
}

async function eliminarPlantilla(id) {
    const p = _store[id];
    const ok = await confirmAction(`"${p?.nombre || 'esta plantilla'}" será eliminada permanentemente.`, { title: '¿Eliminar plantilla?' });
    if (!ok) return;
    try {
        const r = await fetch('api/mensajes_plantillas.php', {
            method: 'DELETE', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const d = await r.json();
        if (d.success) {
            delete _store[id];
            document.getElementById('row-' + id)?.remove();
            showToast('Plantilla eliminada', 'success');
        } else showToast(d.error || 'Error al eliminar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

// ── Helpers zona imagen ───────────────────────────────────────────────────────
function limpiarZonaImagen() {
    document.getElementById('inputImagen').value = '';
    document.getElementById('inputQuitarImagen').value = '';
    document.getElementById('imgPlaceholder').style.display = '';
    document.getElementById('imgPreviewBox').style.display  = 'none';
    document.getElementById('imgExistenteBox').style.display= 'none';
}
function previewImagen(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) { showToast('La imagen no debe superar 5 MB', 'warning'); input.value=''; return; }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('imgExistenteBox').style.display = 'none';
        document.getElementById('imgPlaceholder').style.display  = 'none';
        document.getElementById('imgPreviewBox').style.display   = 'flex';
        document.getElementById('imgPreview').src    = e.target.result;
        document.getElementById('imgNombre').textContent = file.name;
        document.getElementById('inputQuitarImagen').value = '';
    };
    reader.readAsDataURL(file);
}
function quitarImagen(e) {
    e.stopPropagation();
    document.getElementById('inputImagen').value = '';
    document.getElementById('imgPreviewBox').style.display = 'none';
    document.getElementById('imgPlaceholder').style.display = '';
}
function quitarImagenExistente(e) {
    e.stopPropagation();
    document.getElementById('imgExistenteBox').style.display = 'none';
    document.getElementById('imgPlaceholder').style.display  = '';
    document.getElementById('inputQuitarImagen').value = '1';
}
function soltar(e) {
    e.preventDefault();
    document.getElementById('imgZona').style.borderColor = '#e2e8f0';
    const file = e.dataTransfer?.files?.[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer();
        dt.items.add(file);
        const inp = document.getElementById('inputImagen');
        inp.files = dt.files;
        previewImagen(inp);
    }
}

// ── Preview logo mensajes ─────────────────────────────────────────────────────
function previewLogoMensaje(url) {
    const box    = document.getElementById('logoMensajePreviewBox');
    const img    = document.getElementById('logoMensajePreviewImg');
    const status = document.getElementById('logoMensajeStatus');
    if (!box || !img) return;
    const trimmed = (url || '').trim();
    if (!trimmed) { box.style.display = 'none'; img.src = ''; return; }
    box.style.display = 'flex';
    status.textContent = 'Cargando...';
    status.style.color = '#64748b';
    img.src = trimmed;
}

// ── Modal crear ───────────────────────────────────────────────────────────────
function abrirModalCrear() {
    plantillaEditando = null;
    document.getElementById('modalTitle').textContent = 'Nueva plantilla';
    document.getElementById('btnGuardar').textContent = 'Guardar plantilla';
    document.getElementById('inputNombre').value = '';
    document.getElementById('inputCategoria').value = 'general';
    document.getElementById('inputDescripcion').value = '';
    document.getElementById('inputContenido').value = '';
    document.getElementById('inputLogoUrl').value = '';
    previewLogoMensaje('');
    limpiarZonaImagen();
    document.getElementById('modalPlantilla').classList.add('show');
}

// ── Modal editar ──────────────────────────────────────────────────────────────
async function abrirModalEditar(id) {
    let p = _store[id];
    if (!p) {
        try {
            const r = await fetch('api/mensajes_plantillas.php?id=' + id);
            const d = await r.json();
            if (d.success && d.data) { p = d.data; _store[id] = p; }
            else { showToast('No se pudo cargar la plantilla', 'error'); return; }
        } catch(e) { showToast('Error de conexión', 'error'); return; }
    }
    plantillaEditando = p;
    document.getElementById('modalTitle').textContent = 'Editar plantilla';
    document.getElementById('btnGuardar').textContent = 'Guardar cambios';
    document.getElementById('inputNombre').value       = p.nombre;
    document.getElementById('inputCategoria').value    = p.categoria || 'general';
    document.getElementById('inputDescripcion').value  = p.descripcion || '';
    document.getElementById('inputContenido').value    = p.contenido;
    document.getElementById('inputLogoUrl').value      = p.logo_url || '';
    previewLogoMensaje(p.logo_url || '');
    limpiarZonaImagen();
    // Si ya tiene imagen, mostrarla
    if (p.imagen) {
        document.getElementById('imgPlaceholder').style.display  = 'none';
        document.getElementById('imgExistenteBox').style.display = 'flex';
        document.getElementById('imgExistente').src = p.imagen;
    }
    document.getElementById('modalPlantilla').classList.add('show');
}

function cerrarModal() {
    document.getElementById('modalPlantilla').classList.remove('show');
    plantillaEditando = null;
    limpiarZonaImagen();
}

// ── Guardar ───────────────────────────────────────────────────────────────────
async function guardarPlantilla() {
    const nombre    = document.getElementById('inputNombre').value.trim();
    const contenido = document.getElementById('inputContenido').value.trim();
    if (!nombre || !contenido) { showToast('El nombre y el contenido son obligatorios', 'warning'); return; }

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true; btn.textContent = 'Guardando...';

    const imagenFile = document.getElementById('inputImagen').files[0];
    const quitarImg  = document.getElementById('inputQuitarImagen').value;

    // Siempre POST (PHP no parsea $_FILES/$_POST en PUT multipart)
    // Para edición se incluye _method=PUT + id
    const fd = new FormData();
    fd.append('nombre',      nombre);
    fd.append('descripcion', document.getElementById('inputDescripcion').value.trim());
    fd.append('contenido',   contenido);
    fd.append('categoria',   document.getElementById('inputCategoria').value);
    fd.append('logo_url',    document.getElementById('inputLogoUrl').value.trim());
    fd.append('activa',      '1');
    if (plantillaEditando) {
        fd.append('_method', 'PUT');
        fd.append('id',      plantillaEditando.id);
    }
    if (imagenFile) fd.append('imagen',        imagenFile);
    if (quitarImg)  fd.append('quitar_imagen', '1');

    try {
        const r = await fetch('api/mensajes_plantillas.php', { method: 'POST', body: fd });
        const d = await r.json();
        console.log('[Plantilla] Respuesta API:', d);
        if (d.success) {
            cerrarModal();
            const msg = plantillaEditando ? 'Plantilla actualizada' : 'Plantilla creada';
            // Advertir si se subió imagen pero no se guardó
            if (imagenFile && !d.imagen) {
                showToast(msg + ' (advertencia: imagen no se guardó)', 'warning');
            } else {
                showToast(msg, 'success');
            }
            cargarPlantillas();
        } else showToast(d.error || 'Error al guardar', 'error');
    } catch(e) { console.error('[Plantilla] Error:', e); showToast('Error de conexión', 'error'); }
    btn.disabled = false;
    btn.textContent = plantillaEditando ? 'Guardar cambios' : 'Guardar plantilla';
}

// ── Insertar variable en textarea ─────────────────────────────────────────────
function insertarVariable(variable) {
    const ta = document.getElementById('inputContenido');
    const s = ta.selectionStart, e = ta.selectionEnd;
    ta.value = ta.value.substring(0, s) + variable + ta.value.substring(e);
    ta.selectionStart = ta.selectionEnd = s + variable.length;
    ta.focus();
}

// ── Selección múltiple ────────────────────────────────────────────────────────
window._sel = new Set();

function onCheck(id, checked) {
    if (checked) window._sel.add(id); else window._sel.delete(id);
    document.getElementById('card-' + id)?.classList.toggle('seleccionada', checked);
    const n   = window._sel.size;
    const btn = document.getElementById('btnEliminarSel');
    const cnt = document.getElementById('cntSel');
    cnt.textContent   = n;
    btn.style.display = n > 0 ? 'inline-flex' : 'none';
}

async function eliminarSeleccionadas() {
    const n = window._sel.size;
    if (!n) return;
    const ok = await confirmAction(
        `Se eliminarán permanentemente ${n} plantilla${n > 1 ? 's' : ''}.`,
        { title: `¿Eliminar ${n} plantilla${n > 1 ? 's' : ''}?` }
    );
    if (!ok) return;
    let done = 0;
    for (const id of [...window._sel]) {
        try {
            const r = await fetch('api/mensajes_plantillas.php', {
                method: 'DELETE', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            if ((await r.json()).success) {
                delete _store[id];
                document.getElementById('card-' + id)?.remove();
                window._sel.delete(id);
                done++;
            }
        } catch(e) {}
    }
    document.getElementById('btnEliminarSel').style.display = 'none';
    document.getElementById('cntSel').textContent = '0';
    showToast(`${done} plantilla${done !== 1 ? 's' : ''} eliminada${done !== 1 ? 's' : ''}`, 'success');
}

// ── HTML escape ───────────────────────────────────────────────────────────────
function hesc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    cargarPlantillas();
    document.getElementById('modalPlantilla')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });
    document.getElementById('modalEnvioWA')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalWA();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (document.getElementById('modalPlantilla')?.classList.contains('show'))  cerrarModal();
            if (document.getElementById('modalEnvioWA')?.classList.contains('show'))    cerrarModalWA();
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
