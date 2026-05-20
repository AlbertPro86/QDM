<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$pageTitle    = 'Tareas';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;opacity:.65;text-decoration:none;transition:opacity .12s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a> › <span style="color:var(--color-primary);font-weight:700">Tareas</span>';
include __DIR__ . '/includes/header.php';
?>

<!-- ── CONTROLES ────────────────────────────────────────────────────────── -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
    <!-- Toggle vistas -->
    <div style="display:flex;background:#f1f5f9;border-radius:8px;padding:3px;gap:2px">
        <button id="btnVista-lista" onclick="switchVista('lista',this)"
            style="display:flex;align-items:center;gap:5px;padding:7px 13px;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;background:#0f172a;color:#c9f31d;transition:all .12s">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            Lista
        </button>
        <button id="btnVista-kanban" onclick="switchVista('kanban',this)"
            style="display:flex;align-items:center;gap:5px;padding:7px 13px;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;background:transparent;color:#64748b;transition:all .12s">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
            Kanban
        </button>
    </div>
    <!-- Nueva tarea -->
    <button onclick="abrirModalTarea()"
        style="display:flex;align-items:center;gap:6px;padding:9px 18px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;white-space:nowrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        Nueva tarea
    </button>
</div>

<!-- ── RESUMEN KPIs ─────────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px" id="tareasKpis">
    <div style="background:#0f172a;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s" onmouseenter="this.style.filter='brightness(1.08)'" onmouseleave="this.style.filter='brightness(1)'">
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Pendientes</div>
            <div style="font-size:22px;font-weight:900;color:#ffffff;line-height:1" id="kpi-pendiente">—</div>
        </div>
        <span style="font-size:10px;font-weight:700;background:rgba(255,255,255,0.15);color:#ffffff;padding:3px 9px;border-radius:20px;white-space:nowrap">Espera</span>
    </div>
    <div style="background:#4f46e5;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s" onmouseenter="this.style.filter='brightness(1.08)'" onmouseleave="this.style.filter='brightness(1)'">
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">En Proceso</div>
            <div style="font-size:22px;font-weight:900;color:#ffffff;line-height:1" id="kpi-en_progreso">—</div>
        </div>
        <span style="font-size:10px;font-weight:700;background:rgba(255,255,255,0.2);color:#ffffff;padding:3px 9px;border-radius:20px;white-space:nowrap">Activo</span>
    </div>
    <div style="background:#334155;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s" onmouseenter="this.style.filter='brightness(1.08)'" onmouseleave="this.style.filter='brightness(1)'">
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Revisión</div>
            <div style="font-size:22px;font-weight:900;color:#ffffff;line-height:1" id="kpi-revision">—</div>
        </div>
        <span style="font-size:10px;font-weight:700;background:rgba(255,255,255,0.15);color:#ffffff;padding:3px 9px;border-radius:20px;white-space:nowrap">Review</span>
    </div>
    <div style="background:#c9f31d;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s" onmouseenter="this.style.filter='brightness(1.08)'" onmouseleave="this.style.filter='brightness(1)'">
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;font-weight:700;color:rgba(0,0,0,0.45);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Completadas</div>
            <div style="font-size:22px;font-weight:900;color:#0f172a;line-height:1" id="kpi-completado">—</div>
        </div>
        <span style="font-size:10px;font-weight:700;background:rgba(0,0,0,0.15);color:#0f172a;padding:3px 9px;border-radius:20px;white-space:nowrap">Listo</span>
    </div>
</div>

<!-- ── FILTROS ──────────────────────────────────────────────────────────── -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <select id="filtroEstado" onchange="cargarTareas()" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="en_progreso">En Proceso</option>
        <option value="revision">Revisión</option>
        <option value="completado">Completado</option>
    </select>
    <select id="filtroPrioridad" onchange="cargarTareas()" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
        <option value="">Todas las prioridades</option>
        <option value="alta">Alta</option>
        <option value="media">Media</option>
        <option value="baja">Baja</option>
    </select>

    <!-- Búsqueda -->
    <div style="display:flex;align-items:center;gap:6px;border-left:1.5px solid #e2e8f0;padding-left:8px">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color:#94a3b8"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="text" id="filtroBuscar" placeholder="Buscar tarea..." oninput="debounceTareas()"
            style="padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:180px;background:#fff;transition:border-color .15s" onfocus="this.style.borderColor='#c9f31d'" onblur="this.style.borderColor='#e2e8f0'">
    </div>

    <!-- Filtros de Fechas -->
    <div style="display:flex;align-items:center;gap:6px;border-left:1.5px solid #e2e8f0;padding-left:8px">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color:#94a3b8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <input type="date" id="filtroFechaDesde" onchange="cargarTareas()" style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s;background:#fff" onfocus="this.style.borderColor='#c9f31d'" onblur="this.style.borderColor='#e2e8f0'">
        <span style="color:#94a3b8;font-size:12px">–</span>
        <input type="date" id="filtroFechaHasta" onchange="cargarTareas()" style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s;background:#fff" onfocus="this.style.borderColor='#c9f31d'" onblur="this.style.borderColor='#e2e8f0'">
    </div>

    <button onclick="limpiarFiltrosTareas()" style="padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#64748b">Limpiar</button>
</div>

<!-- ══════════════════════════════════════════════════════════
     VISTA LISTA
══════════════════════════════════════════════════════════ -->
<div id="vistaLista">
    <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:#ffffff;border:1.5px solid #e2e8f0;border-bottom:1.5px solid #e2e8f0">
                        <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;width:90px">Prioridad</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Título</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;width:130px">Responsable</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;width:110px">Vencimiento</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;width:110px">Estado</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;width:80px">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tareasTbody">
                    <tr><td colspan="6" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── MODAL Detalles de Tarea ────────────────────────────────────────── -->
<div class="modal-overlay" id="detallesTareaModal">
    <div class="modal">
        <div class="modal-header" style="background:#0f172a;border-bottom:1.5px solid #e2e8f0;padding:20px 24px">
            <div>
                <h3 class="modal-title" style="color:#ffffff">Detalles de la Tarea</h3>
                <p style="font-size:12px;color:#c9f31d;margin:3px 0 0" id="detalleTareaTitulo">—</p>
            </div>
            <button class="modal-close" onclick="closeDetallesTareaModal()" style="color:#c9f31d">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;gap:12px">
            <!-- Prioridad | Estado (dos columnas) -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;background:#f8fafc;border-radius:8px;padding:12px;margin:-4px -4px 0">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Prioridad</label>
                    <div id="detallePrioridad" style="padding:10px 12px;background:#ffffff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;font-weight:600;color:#0f172a">—</div>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Estado</label>
                    <div id="detalleEstado" style="padding:10px 12px;background:#ffffff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;font-weight:600;color:#0f172a">—</div>
                </div>
            </div>

            <!-- Descripción -->
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Descripción</label>
                <div id="detalleDescripcion" style="padding:10px 12px;background:#f1f5f9;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;color:#0f172a;line-height:1.5;min-height:50px;white-space:pre-wrap;word-break:break-word">—</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <!-- Responsable -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Responsable</label>
                    <div id="detalleResponsable" style="padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px">—</div>
                </div>

                <!-- Fecha Límite -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Fecha Límite</label>
                    <div id="detalleFecha" style="padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px">—</div>
                </div>
            </div>

            <!-- Cliente/Lead -->
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Vinculado a</label>
                <div id="detalleVinculo" style="padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px">—</div>
            </div>

            <!-- Notas -->
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Notas</label>
                <div id="detalleNotas" style="padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;color:#0f172a;line-height:1.5;min-height:50px;white-space:pre-wrap;word-break:break-word">—</div>
            </div>

            <!-- Fechas de creación -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Creada el</label>
                    <div id="detalleCreated" style="font-size:11px;color:#64748b">—</div>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Última actualización</label>
                    <div id="detalleUpdated" style="font-size:11px;color:#64748b">—</div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div style="background:#ffffff;border:1.5px solid #cbd5e1;border-radius:8px;padding:12px;margin-top:8px">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a;margin-bottom:8px;display:block">Cambiar estado rápido</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                    <button onclick="cambiarEstadoTarea('pendiente')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:11px;cursor:pointer;background:#fff;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='#fff'">Pendiente</button>
                    <button onclick="cambiarEstadoTarea('en_progreso')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #3b82f6;border-radius:6px;font-size:11px;cursor:pointer;background:#eff6ff;color:#1d4ed8;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#dbeafe'" onmouseleave="this.style.background='#eff6ff'">En Proceso</button>
                    <button onclick="cambiarEstadoTarea('revision')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #f59e0b;border-radius:6px;font-size:11px;cursor:pointer;background:#fffbeb;color:#92400e;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#fef3c7'" onmouseleave="this.style.background='#fffbeb'">Revisión</button>
                    <button onclick="cambiarEstadoTarea('completado')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #22c55e;border-radius:6px;font-size:11px;cursor:pointer;background:#f0fdf4;color:#166534;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#dcfce7'" onmouseleave="this.style.background='#f0fdf4'">Completado</button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeDetallesTareaModal()">Cerrar</button>
            <button id="btnEditarTarea" class="btn btn-secondary" style="background:#4f46e5;color:#fff" onclick="editarTareaDesdeModal()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2V7a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                Editar
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     VISTA KANBAN
══════════════════════════════════════════════════════════ -->
<div id="vistaKanban" style="display:none">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;min-width:0">

        <!-- Pendiente -->
        <div style="background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:14px;padding:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#94a3b8;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:800;color:#0f172a;text-transform:uppercase;letter-spacing:.04em">Pendiente</span>
                </div>
                <span id="kCount-pendiente" style="background:#e2e8f0;color:#64748b;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">0</span>
            </div>
            <div id="kCol-pendiente" style="min-height:120px;display:flex;flex-direction:column;gap:8px"></div>
        </div>

        <!-- En Proceso -->
        <div style="background:#eff6ff;border-radius:14px;padding:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:800;color:#1d4ed8;text-transform:uppercase;letter-spacing:.04em">En Proceso</span>
                </div>
                <span id="kCount-en_progreso" style="background:#dbeafe;color:#2563eb;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">0</span>
            </div>
            <div id="kCol-en_progreso" style="min-height:120px;display:flex;flex-direction:column;gap:8px"></div>
        </div>

        <!-- Revisión -->
        <div style="background:#fffbeb;border-radius:14px;padding:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:.04em">Revisión</span>
                </div>
                <span id="kCount-revision" style="background:#fef3c7;color:#d97706;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">0</span>
            </div>
            <div id="kCol-revision" style="min-height:120px;display:flex;flex-direction:column;gap:8px"></div>
        </div>

        <!-- Completado -->
        <div style="background:#f0fdf4;border-radius:14px;padding:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#22c55e;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:800;color:#166534;text-transform:uppercase;letter-spacing:.04em">Completado</span>
                </div>
                <span id="kCount-completado" style="background:#dcfce7;color:#16a34a;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">0</span>
            </div>
            <div id="kCol-completado" style="min-height:120px;display:flex;flex-direction:column;gap:8px"></div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL: NUEVA / EDITAR TAREA
══════════════════════════════════════════════════════════ -->
<div id="modalTarea" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1100;display:none;align-items:center;justify-content:center;padding:50px 24px">
  <div style="background:#fff;border-radius:16px;width:100%;max-width:730px;max-height:calc(100vh - 100px);overflow-y:auto;box-shadow:0 24px 40px -8px rgba(0,0,0,.2);display:flex;flex-direction:column">

    <!-- Header -->
    <div style="padding:20px 24px 16px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0">
      <div>
        <h2 style="font-size:16px;font-weight:800;color:#0f172a;margin:0" id="modalTareaTitulo">Nueva tarea</h2>
        <p style="font-size:12px;color:#94a3b8;margin:4px 0 0">Asigna, prioriza y da seguimiento</p>
      </div>
      <button onclick="cerrarModalTarea()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:#94a3b8" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='none'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px">
      <input type="hidden" id="trId">

      <!-- Título -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Título *</label>
        <input type="text" id="trTitulo" placeholder="¿Qué hay que hacer?" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
      </div>

      <!-- Descripción -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Descripción</label>
        <textarea id="trDesc" rows="2" placeholder="Detalla el alcance de la tarea..." style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
      </div>

      <!-- Grid 2 columnas -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <!-- Prioridad -->
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Prioridad</label>
          <select id="trPrioridad" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;background:#fff" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            <option value="alta">Alta</option>
            <option value="media" selected>Media</option>
            <option value="baja">Baja</option>
          </select>
        </div>
        <!-- Estado -->
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Estado</label>
          <select id="trEstado" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;background:#fff" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            <option value="pendiente" selected>Pendiente</option>
            <option value="en_progreso">En Proceso</option>
            <option value="revision">Revisión</option>
            <option value="completado">Completado</option>
          </select>
        </div>
        <!-- Responsable -->
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Responsable</label>
          <input type="text" id="trResponsable" placeholder="Nombre del responsable" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <!-- Fecha límite -->
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Fecha límite</label>
          <input type="date" id="trFechaLimite" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;background:#fff" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
      </div>

      <!-- Notas -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Notas adicionales</label>
        <textarea id="trNotas" rows="2" placeholder="Observaciones, contexto o links relevantes..." style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
      </div>
    </div>

    <!-- Footer -->
    <div style="padding:16px 24px;border-top:1.5px solid #f1f5f9;display:flex;gap:10px;justify-content:flex-end;background:#fff;border-radius:0 0 16px 16px;position:sticky;bottom:0">
      <button onclick="cerrarModalTarea()" style="padding:10px 20px;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">Cancelar</button>
      <button id="trBtnGuardar" onclick="guardarTarea()" style="display:flex;align-items:center;gap:6px;padding:10px 24px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px" onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='#0f172a'">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Guardar
      </button>
    </div>
  </div>
</div>

<!-- Modal confirmar eliminar tarea -->
<div id="modalEliminarTarea" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:14px;width:100%;max-width:400px;padding:28px;box-shadow:0 24px 40px -8px rgba(0,0,0,.2);text-align:center">
    <div style="width:52px;height:52px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <svg width="24" height="24" fill="none" stroke="#ef4444" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </div>
    <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 8px">¿Cancelar tarea?</h3>
    <p id="modalElimTareaNombre" style="font-size:13px;color:#64748b;margin:0 0 24px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('modalEliminarTarea').style.display='none'" style="padding:10px 20px;background:transparent;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;color:#64748b">Conservar</button>
      <button id="btnConfirmarElimTarea" style="padding:10px 20px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">Sí, cancelar</button>
    </div>
  </div>
</div>

<script>
document.getElementById('modalTarea').addEventListener('click', e => { if (e.target === document.getElementById('modalTarea')) cerrarModalTarea(); });
document.getElementById('modalEliminarTarea').addEventListener('click', e => { if (e.target === document.getElementById('modalEliminarTarea')) document.getElementById('modalEliminarTarea').style.display='none'; });
</script>

<script src="js/tareas.js?v=<?= APP_VERSION ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
