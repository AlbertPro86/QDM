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
                <div style="width:28px;height:28px;border-radius:4px;background:#E1E7F2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="14" height="14" fill="none" stroke="#3F5E9E" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div style="min-width:0">
                    <h3 class="card-title" style="margin:0;font-size:11px;font-weight:700">Notificaciones por correo</h3>
                    <p style="margin:0;font-size:9px;color:#8A867C;line-height:1.2">Resumen diario</p>
                </div>
            </div>
            <!-- Toggle activa -->
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;user-select:none;white-space:nowrap">
                <span style="font-size:11px;color:#57544D;font-weight:600" id="lblActiva">Activa</span>
                <div style="position:relative;width:40px;height:22px" onclick="toggleActiva()">
                    <div id="trackActiva" style="width:40px;height:22px;border-radius:11px;background:#C6F24E;transition:background .2s"></div>
                    <div id="thumbActiva" style="position:absolute;top:2px;left:2px;width:18px;height:18px;border-radius:50%;background:#0E0E0C;transition:transform .2s;transform:translateX(18px)"></div>
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
                    <p style="margin:3px 0 0;font-size:10px;color:#8A867C">El resumen llegará a este correo</p>
                </div>

                <!-- Número WhatsApp -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Número WhatsApp</label>
                    <input id="notif_whatsapp" class="form-input" type="tel" placeholder="+57 3145979983" style="padding:6px 10px;font-size:12px">
                    <p style="margin:3px 0 0;font-size:10px;color:#8A867C">Número para notificaciones por WhatsApp (con código país)</p>
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
                            style="width:42px;height:42px;border-radius:4px;border:1.5px solid #E8E5DD;background:#FAFAF7;color:#57544D;font-size:11px;font-weight:700;cursor:pointer;transition:all .15s">
                            <?= $d['label'] ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <p style="margin:4px 0 0;font-size:10px;color:#8A867C">Selecciona los días</p>
                </div>
            </div>

            <!-- Columna Derecha -->
            <div style="display:grid;gap:12px">
                <!-- Logo URL -->
                <?php $defaultLogoUrl = getSiteLogoUrl(); ?>
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Logo del correo</label>
                    <!-- Vista previa activa -->
                    <div style="margin-bottom:8px;padding:10px 12px;background:#EFECE5;border:1px solid #E8E5DD;border-radius:6px;display:flex;align-items:center;gap:10px">
                        <img id="notifLogoImg" src="<?= htmlspecialchars($defaultLogoUrl) ?>" alt="Logo QUANTUN"
                            style="height:34px;max-width:160px;object-fit:contain"
                            onload="document.getElementById('notifLogoStatus').textContent='✓ Logo cargado';document.getElementById('notifLogoStatus').style.color='#4ade80'"
                            onerror="logoFallback(this)">
                        <span id="notifLogoStatus" style="font-size:10px;color:#8A867C"></span>
                    </div>
                    <!-- Campo URL + botón reset -->
                    <div style="display:flex;gap:6px;align-items:center">
                        <input id="notif_logo_url" class="form-input" type="text"
                            placeholder="<?= htmlspecialchars($defaultLogoUrl) ?>"
                            oninput="previewLogoNotif()"
                            style="padding:6px 10px;font-size:12px;flex:1">
                        <button type="button" title="Usar logo del sitio web"
                            onclick="usarLogoSitio()"
                            style="padding:6px 10px;border-radius:6px;border:1px solid #E8E5DD;background:#FAFAF7;font-size:11px;font-weight:600;color:#57544D;cursor:pointer;white-space:nowrap;transition:all .15s"
                            onmouseenter="this.style.background='#0E0E0C';this.style.color='#fff'"
                            onmouseleave="this.style.background='#FAFAF7';this.style.color='#57544D'">
                            Usar logo del sitio
                        </button>
                    </div>
                    <p style="margin:3px 0 0;font-size:10px;color:#8A867C">Si está vacío se usa el logo del sitio web (por defecto)</p>
                </div>

                <!-- Qué incluir -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Incluir en el resumen</label>
                    <div style="display:grid;gap:8px;margin-top:4px">
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#FAFAF7;border-radius:3px;border:1.5px solid #E8E5DD;cursor:pointer;transition:border-color .15s" onmouseenter="this.style.borderColor='#D6D2C7'" onmouseleave="this.style.borderColor='#E8E5DD'">
                            <input type="checkbox" id="notif_incluir_tareas" style="width:16px;height:16px;accent-color:#0E0E0C;cursor:pointer;flex-shrink:0">
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#0E0E0C">✅ Tareas pendientes</div>
                                <div style="font-size:10px;color:#8A867C;line-height:1.3">Pendiente o en progreso</div>
                            </div>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#FAFAF7;border-radius:3px;border:1.5px solid #E8E5DD;cursor:pointer;transition:border-color .15s" onmouseenter="this.style.borderColor='#D6D2C7'" onmouseleave="this.style.borderColor='#E8E5DD'">
                            <input type="checkbox" id="notif_incluir_renovaciones" style="width:16px;height:16px;accent-color:#0E0E0C;cursor:pointer;flex-shrink:0">
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#0E0E0C">🔄 Renovaciones</div>
                                <div style="font-size:10px;color:#8A867C;line-height:1.3">Próximos 2 meses</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

        </div>

        <div style="padding:12px 14px;border-top:1px solid #EFECE5;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <button onclick="enviarPrueba()" id="btnPrueba"
                style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#FAFAF7;color:#57544D;border:1.5px solid #E8E5DD;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;transition:all .15s"
                onmouseenter="this.style.borderColor='#8A867C'" onmouseleave="this.style.borderColor='#E8E5DD'">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Enviar prueba ahora
            </button>
            <button onclick="guardarConfig()" id="btnGuardar"
                class="btn btn-primary btn-sm">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Guardar configuración
            </button>
        </div>
    </div>

    <!-- ── Datos de la Empresa ───────────────────────────────────────────── -->
    <div class="card animate-fade-up">
        <div class="card-header" style="padding:8px 12px;display:flex;align-items:center;gap:6px">
            <div style="width:28px;height:28px;border-radius:4px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="14" height="14" fill="none" stroke="#7C3AED" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <h3 class="card-title" style="margin:0;font-size:11px;font-weight:700">Datos de la empresa</h3>
                <p style="margin:0;font-size:9px;color:#8A867C;line-height:1.2">Se usan en órdenes, facturas y documentos</p>
            </div>
        </div>
        <div class="card-body" style="padding:14px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Nombre / Razón social</label>
                <input id="empresa_nombre" class="form-input" type="text" placeholder="Ej: QUANTUN Digital S.A.S" style="padding:6px 10px;font-size:12px">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">NIT / Cédula</label>
                <input id="empresa_nit" class="form-input" type="text" placeholder="Ej: 900.567.123-4" style="padding:6px 10px;font-size:12px">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Correo electrónico</label>
                <input id="empresa_email" class="form-input" type="email" placeholder="Ej: contacto@empresa.com" style="padding:6px 10px;font-size:12px">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Teléfono / WhatsApp</label>
                <input id="empresa_tel" class="form-input" type="text" placeholder="Ej: +57 314 597 9983" style="padding:6px 10px;font-size:12px">
            </div>
            <div class="form-group" style="margin:0;grid-column:1/-1">
                <label class="form-label" style="font-size:11px">Dirección</label>
                <input id="empresa_dir" class="form-input" type="text" placeholder="Ej: Cra 42 # 43A-12, Montería, Córdoba" style="padding:6px 10px;font-size:12px">
            </div>
        </div>
        <div style="padding:12px 14px;border-top:1px solid #EFECE5;display:flex;justify-content:flex-end">
            <button onclick="guardarEmpresa()" id="btnGuardarEmpresa" class="btn btn-primary btn-sm">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Guardar datos de empresa
            </button>
        </div>
    </div>

    <!-- ── Datos bancarios ───────────────────────────────────────────────── -->
    <div class="card animate-fade-up">
        <div class="card-header" style="padding:8px 12px;display:flex;align-items:center;gap:6px">
            <div style="width:28px;height:28px;border-radius:4px;background:#E3F1E8;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="14" height="14" fill="none" stroke="#2D8F5A" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h3 class="card-title" style="margin:0;font-size:11px;font-weight:700">Datos bancarios</h3>
                <p style="margin:0;font-size:9px;color:#8A867C;line-height:1.2">Se incluyen en órdenes, cotizaciones y cuentas de cobro</p>
            </div>
        </div>
        <div class="card-body" style="padding:14px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Titular de la cuenta</label>
                <input id="banco_titular" class="form-input" type="text" placeholder="Ej: QUANTUN Digital S.A.S" style="padding:6px 10px;font-size:12px">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Cédula / NIT del titular</label>
                <input id="banco_cedula" class="form-input" type="text" placeholder="Ej: 900.567.123-4" style="padding:6px 10px;font-size:12px">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Nombre del banco</label>
                <input id="banco_nombre" class="form-input" type="text" placeholder="Ej: Bancolombia" style="padding:6px 10px;font-size:12px">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Número de cuenta</label>
                <input id="banco_numero" class="form-input" type="text" placeholder="Ej: 123-456789-00" style="padding:6px 10px;font-size:12px">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Tipo de cuenta</label>
                <select id="banco_tipo" class="form-input" style="padding:6px 10px;font-size:12px;cursor:pointer">
                    <option value="Ahorros">Ahorros</option>
                    <option value="Corriente">Corriente</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Llave</label>
                <input id="banco_llave" class="form-input" type="text" placeholder="Ej: +57 300 000 0000" style="padding:6px 10px;font-size:12px">
            </div>
        </div>
        <div style="padding:12px 14px;border-top:1px solid #EFECE5;display:flex;justify-content:flex-end">
            <button onclick="guardarBanco()" id="btnGuardarBanco" class="btn btn-primary btn-sm">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Guardar datos bancarios
            </button>
        </div>
    </div>

    <!-- ── Info tarea programada ──────────────────────────────────────────── -->
    <div class="card animate-fade-up" style="background:#fff;border:1.5px solid var(--color-border)">
        <div class="card-body" style="padding:14px 16px;display:flex;align-items:flex-start;gap:12px">
            <div style="width:36px;height:36px;border-radius:4px;background:#E1E7F2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="18" height="18" fill="none" stroke="#3F5E9E" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="min-width:0">
                <div style="font-size:12px;font-weight:700;color:#0E0E0C;margin-bottom:4px">Tarea programada automática</div>
                <div id="infoTarea" style="font-size:11px;color:#57544D;line-height:1.6">Cargando configuración...</div>
                <div style="margin-top:8px;font-size:10px;color:#8A867C">
                    El resumen se envía ejecutando:<br>
                    <code style="background:#EFECE5;color:#0E0E0C;padding:3px 6px;border-radius:3px;font-size:9px;display:inline-block;margin-top:3px;overflow:hidden;text-overflow:ellipsis;word-break:break-all">
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

        // Datos de la empresa
        document.getElementById('empresa_nombre').value = cfg.empresa_nombre || '';
        document.getElementById('empresa_nit').value    = cfg.empresa_nit    || '';
        document.getElementById('empresa_email').value  = cfg.empresa_email  || '';
        document.getElementById('empresa_tel').value    = cfg.empresa_tel    || '';
        document.getElementById('empresa_dir').value    = cfg.empresa_dir    || '';

        // Datos bancarios
        document.getElementById('banco_titular').value  = cfg.banco_titular  || '';
        document.getElementById('banco_cedula').value   = cfg.banco_cedula   || '';
        document.getElementById('banco_nombre').value   = cfg.banco_nombre   || '';
        document.getElementById('banco_numero').value   = cfg.banco_numero   || '';
        document.getElementById('banco_tipo').value     = cfg.banco_tipo     || 'Ahorros';
        document.getElementById('banco_llave').value    = cfg.banco_llave    || '';

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
    track.style.background     = notifActiva ? '#C6F24E' : '#E8E5DD';
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
        btn.style.background   = sel ? '#C6F24E' : '#FAFAF7';
        btn.style.color        = sel ? '#0E0E0C' : '#57544D';
        btn.style.borderColor  = sel ? '#C6F24E' : '#E8E5DD';
        btn.style.fontWeight   = '700';
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
        `📧 Destinatario: <strong style="color:#0E0E0C">${email}</strong><br>` +
        `🕐 Hora: <strong style="color:#0E0E0C">${hora}</strong><br>` +
        `📅 Días: <strong style="color:#0E0E0C">${diasTxt}</strong>`;
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
const DEFAULT_LOGO_URL = <?= json_encode($defaultLogoUrl) ?>;

function logoFallback(img) {
    var status = document.getElementById('notifLogoStatus');
    if (img.src !== DEFAULT_LOGO_URL) {
        // La URL personalizada falló → intentar con el logo del sitio
        status.textContent = 'URL inválida, usando logo del sitio';
        status.style.color = '#f59e0b';
        img.src = DEFAULT_LOGO_URL;
    } else {
        status.textContent = '✗ Logo no disponible';
        status.style.color = '#f87171';
    }
}

function previewLogoNotif() {
    const url    = document.getElementById('notif_logo_url').value.trim();
    const img    = document.getElementById('notifLogoImg');
    const status = document.getElementById('notifLogoStatus');
    status.textContent = 'Cargando…';
    status.style.color = '#8A867C';
    img.src = url || DEFAULT_LOGO_URL;
}

function usarLogoSitio() {
    document.getElementById('notif_logo_url').value = DEFAULT_LOGO_URL;
    previewLogoNotif();
}

// ── Guardar datos de empresa ──────────────────────────────────────────────────
async function guardarEmpresa() {
    const btn = document.getElementById('btnGuardarEmpresa');
    btn.disabled = true; btn.textContent = 'Guardando...';
    const payload = {
        empresa_nombre: document.getElementById('empresa_nombre').value.trim(),
        empresa_nit:    document.getElementById('empresa_nit').value.trim(),
        empresa_email:  document.getElementById('empresa_email').value.trim(),
        empresa_tel:    document.getElementById('empresa_tel').value.trim(),
        empresa_dir:    document.getElementById('empresa_dir').value.trim(),
    };
    try {
        const r = await fetch('api/configuraciones.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body:JSON.stringify(payload) });
        const d = await r.json();
        if (d.success) showToast('✓ Datos de empresa guardados', 'success');
        else showToast(d.error || 'Error al guardar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar datos de empresa';
    }
}

// ── Guardar datos bancarios ───────────────────────────────────────────────────
async function guardarBanco() {
    const btn = document.getElementById('btnGuardarBanco');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const payload = {
        banco_titular:  document.getElementById('banco_titular').value.trim(),
        banco_cedula:   document.getElementById('banco_cedula').value.trim(),
        banco_nombre:   document.getElementById('banco_nombre').value.trim(),
        banco_numero:   document.getElementById('banco_numero').value.trim(),
        banco_tipo:     document.getElementById('banco_tipo').value,
        banco_llave:    document.getElementById('banco_llave').value.trim(),
    };

    try {
        const r = await fetch('api/configuraciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.success) showToast('✓ Datos bancarios guardados', 'success');
        else showToast(d.error || 'Error al guardar', 'error');
    } catch(e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar datos bancarios';
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
