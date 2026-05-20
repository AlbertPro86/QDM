<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle      = 'Configuraciones';
$pageSubtitle   = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Configuraciones</span>';
include __DIR__ . '/includes/header.php';
?>

<div style="display:grid;gap:12px">

    <!-- ── Notificaciones por correo ─────────────────────────────────────── -->
    <div class="card animate-fade-up">
        <div class="card-header" style="padding:8px 12px;display:flex;align-items:center;justify-content:space-between;gap:8px">
            <div style="display:flex;align-items:center;gap:6px;min-width:0">
                <div style="width:28px;height:28px;border-radius:6px;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="14" height="14" fill="none" stroke="#4f46e5" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div style="min-width:0">
                    <h3 class="card-title" style="margin:0;font-size:11px;font-weight:700">Notificaciones por correo</h3>
                    <p style="margin:0;font-size:9px;color:#94a3b8;line-height:1.2">Resumen diario</p>
                </div>
            </div>
            <!-- Toggle activa -->
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;user-select:none;white-space:nowrap">
                <span style="font-size:11px;color:#64748b;font-weight:600" id="lblActiva">Activa</span>
                <div style="position:relative;width:40px;height:22px" onclick="toggleActiva()">
                    <div id="trackActiva" style="width:40px;height:22px;border-radius:11px;background:#c9f31d;transition:background .2s"></div>
                    <div id="thumbActiva" style="position:absolute;top:2px;left:2px;width:18px;height:18px;border-radius:50%;background:#0f172a;transition:transform .2s;transform:translateX(18px)"></div>
                </div>
            </label>
        </div>

        <div class="card-body" style="padding:14px;display:grid;grid-template-columns:1fr 1fr;gap:14px" id="panelNotif">

            <!-- Columna Izquierda -->
            <div style="display:grid;gap:12px">
                <!-- Email destinatario -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Correo destinatario</label>
                    <input id="notif_email" class="form-input" type="email" placeholder="correo@ejemplo.com" style="padding:6px 10px;font-size:12px">
                    <p style="margin:3px 0 0;font-size:10px;color:#94a3b8">El resumen llegará a este correo</p>
                </div>

                <!-- Número WhatsApp -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Número WhatsApp</label>
                    <input id="notif_whatsapp" class="form-input" type="tel" placeholder="+57 3145979983" style="padding:6px 10px;font-size:12px">
                    <p style="margin:3px 0 0;font-size:10px;color:#94a3b8">Número para notificaciones por WhatsApp (con código país)</p>
                </div>

                <!-- Hora de envío -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Hora de envío</label>
                    <input id="notif_hora" class="form-input" type="time" style="max-width:140px;padding:6px 10px;font-size:12px">
                </div>

                <!-- Días de la semana -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Días de envío</label>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px">
                        <?php
                        $dias = [
                            ['val' => 0, 'label' => 'Dom'],
                            ['val' => 1, 'label' => 'Lun'],
                            ['val' => 2, 'label' => 'Mar'],
                            ['val' => 3, 'label' => 'Mié'],
                            ['val' => 4, 'label' => 'Jue'],
                            ['val' => 5, 'label' => 'Vie'],
                            ['val' => 6, 'label' => 'Sáb'],
                        ];
                        foreach ($dias as $d): ?>
                        <button type="button" data-dia="<?= $d['val'] ?>"
                            onclick="toggleDia(this)"
                            style="width:42px;height:42px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:11px;font-weight:700;cursor:pointer;transition:all .15s">
                            <?= $d['label'] ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <p style="margin:4px 0 0;font-size:10px;color:#94a3b8">Selecciona los días</p>
                </div>
            </div>

            <!-- Columna Derecha -->
            <div style="display:grid;gap:12px">
                <!-- Logo URL -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Logo del correo (URL)</label>
                    <input id="notif_logo_url" class="form-input" type="text" placeholder="https://tudominio.com/logo.png" oninput="previewLogoNotif()" style="padding:6px 10px;font-size:12px">
                    <div id="notifLogoPreview" style="display:none;margin-top:6px;padding:8px 10px;background:#0f172a;border-radius:6px;display:inline-flex;align-items:center;gap:8px">
                        <img id="notifLogoImg" src="" alt="Logo" style="height:32px;max-width:150px;object-fit:contain"
                            onload="document.getElementById('notifLogoStatus').textContent='✓ Logo cargado';document.getElementById('notifLogoStatus').style.color='#4ade80'"
                            onerror="document.getElementById('notifLogoStatus').textContent='✗ No se pudo cargar';document.getElementById('notifLogoStatus').style.color='#f87171'">
                        <span id="notifLogoStatus" style="font-size:10px;color:#94a3b8"></span>
                    </div>
                    <p style="margin:3px 0 0;font-size:10px;color:#94a3b8">Si está vacío se usa el logo local</p>
                </div>

                <!-- Qué incluir -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Incluir en el resumen</label>
                    <div style="display:grid;gap:8px;margin-top:4px">
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:8px;border:1.5px solid #e2e8f0;cursor:pointer;transition:border-color .15s" onmouseenter="this.style.borderColor='#a5b4fc'" onmouseleave="this.style.borderColor='#e2e8f0'">
                            <input type="checkbox" id="notif_incluir_tareas" style="width:16px;height:16px;accent-color:#4f46e5;cursor:pointer;flex-shrink:0">
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#0f172a">✅ Tareas pendientes</div>
                                <div style="font-size:10px;color:#94a3b8;line-height:1.3">Pendiente o en progreso</div>
                            </div>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:8px;border:1.5px solid #e2e8f0;cursor:pointer;transition:border-color .15s" onmouseenter="this.style.borderColor='#a5b4fc'" onmouseleave="this.style.borderColor='#e2e8f0'">
                            <input type="checkbox" id="notif_incluir_renovaciones" style="width:16px;height:16px;accent-color:#4f46e5;cursor:pointer;flex-shrink:0">
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#0f172a">🔄 Renovaciones</div>
                                <div style="font-size:10px;color:#94a3b8;line-height:1.3">Próximos 2 meses</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

        </div>

        <div style="padding:12px 14px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <button onclick="enviarPrueba()" id="btnPrueba"
                style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;transition:all .15s"
                onmouseenter="this.style.borderColor='#94a3b8'" onmouseleave="this.style.borderColor='#e2e8f0'">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Enviar prueba ahora
            </button>
            <button onclick="guardarConfig()" id="btnGuardar"
                style="display:inline-flex;align-items:center;gap:6px;padding:7px 18px;background:#0f172a;color:#c9f31d;border:none;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;transition:filter .15s"
                onmouseenter="this.style.filter='brightness(1.3)'" onmouseleave="this.style.filter=''">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Guardar configuración
            </button>
        </div>
    </div>

    <!-- ── Info tarea programada ──────────────────────────────────────────── -->
    <div class="card animate-fade-up" style="background:#0f172a">
        <div class="card-body" style="padding:14px 16px;display:flex;align-items:flex-start;gap:12px">
            <div style="width:36px;height:36px;border-radius:8px;background:rgba(201,243,29,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="18" height="18" fill="none" stroke="#c9f31d" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="min-width:0">
                <div style="font-size:12px;font-weight:800;color:#ffffff;margin-bottom:4px">Tarea programada automática</div>
                <div id="infoTarea" style="font-size:11px;color:#94a3b8;line-height:1.6">Cargando configuración...</div>
                <div style="margin-top:8px;font-size:10px;color:#64748b">
                    El resumen se envía ejecutando:<br>
                    <code style="background:#1e293b;color:#c9f31d;padding:3px 6px;border-radius:4px;font-size:9px;display:inline-block;margin-top:3px;overflow:hidden;text-overflow:ellipsis;word-break:break-all">
                        C:\xampp\php\php.exe C:\xampp\htdocs\CRM-QUANTUN-Digital\api\notificacion_resumen.php
                    </code>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const DIAS_LABEL = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
let cfg = {};
let diasSeleccionados = [];
let notifActiva = true;

// ── Cargar configuración ──────────────────────────────────────────────────────
async function cargarConfig() {
    try {
        const r = await fetch('api/configuraciones.php', { credentials: 'include' });
        const d = await r.json();
        if (!d.success) return;
        cfg = d.data;

        document.getElementById('notif_email').value             = cfg.notif_email || '';
        document.getElementById('notif_whatsapp').value           = cfg.notif_whatsapp || '';
        document.getElementById('notif_hora').value              = cfg.notif_hora  || '08:00';
        document.getElementById('notif_incluir_tareas').checked       = cfg.notif_incluir_tareas === '1';
        document.getElementById('notif_incluir_renovaciones').checked = cfg.notif_incluir_renovaciones === '1';
        document.getElementById('notif_logo_url').value               = cfg.notif_logo_url || '';
        previewLogoNotif();

        notifActiva = cfg.notif_activa === '1';
        diasSeleccionados = Array.isArray(cfg.notif_dias) ? cfg.notif_dias.map(Number) : [1,2,3,4,5];

        actualizarToggle();
        actualizarDias();
        actualizarInfoTarea();
    } catch(e) { showToast('Error al cargar configuración', 'error'); }
}

// ── Toggle activa/inactiva ────────────────────────────────────────────────────
function toggleActiva() {
    notifActiva = !notifActiva;
    actualizarToggle();
}
function actualizarToggle() {
    const track = document.getElementById('trackActiva');
    const thumb = document.getElementById('thumbActiva');
    const lbl   = document.getElementById('lblActiva');
    const panel = document.getElementById('panelNotif');
    track.style.background     = notifActiva ? '#c9f31d' : '#e2e8f0';
    thumb.style.transform      = notifActiva ? 'translateX(18px)' : 'translateX(0)';
    lbl.textContent            = notifActiva ? 'Activa' : 'Inactiva';
    lbl.style.color            = notifActiva ? '#16a34a' : '#94a3b8';
    panel.style.opacity        = notifActiva ? '1' : '.45';
    panel.style.pointerEvents  = notifActiva ? '' : 'none';
}

// ── Días ──────────────────────────────────────────────────────────────────────
function toggleDia(btn) {
    const val = parseInt(btn.dataset.dia);
    const idx = diasSeleccionados.indexOf(val);
    if (idx === -1) diasSeleccionados.push(val);
    else diasSeleccionados.splice(idx, 1);
    actualizarDias();
    actualizarInfoTarea();
}
function actualizarDias() {
    document.querySelectorAll('[data-dia]').forEach(btn => {
        const val = parseInt(btn.dataset.dia);
        const sel = diasSeleccionados.includes(val);
        btn.style.background   = sel ? '#0f172a' : '#f8fafc';
        btn.style.color        = sel ? '#c9f31d' : '#64748b';
        btn.style.borderColor  = sel ? '#0f172a' : '#e2e8f0';
        btn.style.transform    = sel ? 'scale(1.05)' : '';
    });
}

// ── Info tarea programada ─────────────────────────────────────────────────────
function actualizarInfoTarea() {
    const hora  = document.getElementById('notif_hora')?.value || '08:00';
    const email = document.getElementById('notif_email')?.value || '—';
    const diasTxt = diasSeleccionados.length === 0
        ? '<span style="color:#ef4444">Ningún día seleccionado</span>'
        : diasSeleccionados.sort().map(d => DIAS_LABEL[d]).join(', ');

    document.getElementById('infoTarea').innerHTML =
        `📧 Destinatario: <strong style="color:#fff">${email}</strong><br>` +
        `🕐 Hora: <strong style="color:#fff">${hora}</strong><br>` +
        `📅 Días: <strong style="color:#c9f31d">${diasTxt}</strong>`;
}

// Actualizar info al cambiar campos
document.addEventListener('DOMContentLoaded', () => {
    cargarConfig();
    document.getElementById('notif_hora').addEventListener('input', actualizarInfoTarea);
    document.getElementById('notif_email').addEventListener('input', actualizarInfoTarea);
});

// ── Guardar ───────────────────────────────────────────────────────────────────
async function guardarConfig() {
    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const payload = {
        notif_activa:               notifActiva ? '1' : '0',
        notif_email:                document.getElementById('notif_email').value.trim(),
        notif_whatsapp:             document.getElementById('notif_whatsapp').value.trim(),
        notif_hora:                 document.getElementById('notif_hora').value,
        notif_dias:                 diasSeleccionados,
        notif_incluir_tareas:       document.getElementById('notif_incluir_tareas').checked ? '1' : '0',
        notif_incluir_renovaciones: document.getElementById('notif_incluir_renovaciones').checked ? '1' : '0',
        notif_logo_url:             document.getElementById('notif_logo_url').value.trim(),
    };

    try {
        const r = await fetch('api/configuraciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.success) showToast('✓ Configuración guardada correctamente', 'success');
        else showToast(d.error || 'Error al guardar', 'error');
    } catch(e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar configuración';
    }
}

// ── Preview logo ─────────────────────────────────────────────────────────────
function previewLogoNotif() {
    const url     = document.getElementById('notif_logo_url').value.trim();
    const preview = document.getElementById('notifLogoPreview');
    const img     = document.getElementById('notifLogoImg');
    const status  = document.getElementById('notifLogoStatus');
    if (url) {
        preview.style.display = 'inline-flex';
        status.textContent    = 'Cargando...';
        status.style.color    = '#94a3b8';
        img.src               = url;
    } else {
        preview.style.display = 'none';
        img.src               = '';
    }
}

// ── Prueba ────────────────────────────────────────────────────────────────────
async function enviarPrueba() {
    const btn = document.getElementById('btnPrueba');
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Enviando...';
    try {
        const r = await fetch('api/notificacion_resumen.php', { credentials: 'include' });
        const d = await r.json();
        if (d.success) showToast(`✓ Prueba enviada · ${d.tareas} tareas · ${(d.renovaciones_mes_actual||0)+(d.renovaciones_prox_mes||0)} renovaciones`, 'success');
        else showToast(d.error || 'Error al enviar', 'error');
    } catch(e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Enviar prueba ahora';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
