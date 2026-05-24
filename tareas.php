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
<div class="page-header">
    <div class="page-header-left">
        <div class="view-toggle" id="viewToggle">
            <button id="btnVista-lista" class="view-toggle-btn active" onclick="switchVista('lista',this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Lista
            </button>
            <button id="btnVista-kanban" class="view-toggle-btn" onclick="switchVista('kanban',this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                Kanban
            </button>
        </div>
    </div>
    <div class="page-header-right">
        <button class="btn btn-accent" onclick="abrirModalTarea()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Nueva tarea
        </button>
    </div>
</div>

<!-- ── RESUMEN KPIs ─────────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px" id="tareasKpis">
    <div style="background:#fff;border:1px solid #E8E5DD;border-radius:4px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s" onmouseenter="this.style.filter='brightness(.97)'" onmouseleave="this.style.filter='brightness(1)'">
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Pendientes</div>
            <div style="font-size:22px;font-weight:700;color:#0E0E0C;line-height:1" id="kpi-pendiente">—</div>
        </div>
        <span style="font-size:10px;font-weight:700;background:#EFECE5;color:#57544D;padding:3px 9px;border-radius:3px;white-space:nowrap">Espera</span>
    </div>
    <div style="background:#fff;border:1px solid #E8E5DD;border-radius:4px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s" onmouseenter="this.style.filter='brightness(.97)'" onmouseleave="this.style.filter='brightness(1)'">
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">En Proceso</div>
            <div style="font-size:22px;font-weight:700;color:#0E0E0C;line-height:1" id="kpi-en_progreso">—</div>
        </div>
        <span style="font-size:10px;font-weight:700;background:#E1E7F2;color:#3F5E9E;padding:3px 9px;border-radius:3px;white-space:nowrap">Activo</span>
    </div>
    <div style="background:#fff;border:1px solid #E8E5DD;border-radius:4px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s" onmouseenter="this.style.filter='brightness(.97)'" onmouseleave="this.style.filter='brightness(1)'">
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Revisión</div>
            <div style="font-size:22px;font-weight:700;color:#0E0E0C;line-height:1" id="kpi-revision">—</div>
        </div>
        <span style="font-size:10px;font-weight:700;background:#EFECE5;color:#57544D;padding:3px 9px;border-radius:3px;white-space:nowrap">Review</span>
    </div>
    <div style="background:#fff;border:1px solid #E8E5DD;border-radius:4px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:filter .15s" onmouseenter="this.style.filter='brightness(.97)'" onmouseleave="this.style.filter='brightness(1)'">
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Completadas</div>
            <div style="font-size:22px;font-weight:700;color:#2D8F5A;line-height:1" id="kpi-completado">—</div>
        </div>
        <span style="font-size:10px;font-weight:700;background:#E3F1E8;color:#2D8F5A;padding:3px 9px;border-radius:3px;white-space:nowrap">Listo</span>
    </div>
</div>

<!-- ── FILTROS ──────────────────────────────────────────────────────────── -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <select id="filtroEstado" onchange="cargarTareas()" style="padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="en_progreso">En Proceso</option>
        <option value="revision">Revisión</option>
        <option value="completado">Completado</option>
    </select>
    <select id="filtroPrioridad" onchange="cargarTareas()" style="padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
        <option value="">Todas las prioridades</option>
        <option value="alta">Alta</option>
        <option value="media">Media</option>
        <option value="baja">Baja</option>
    </select>

    <!-- Búsqueda -->
    <div style="display:flex;align-items:center;gap:6px;border-left:1.5px solid #E8E5DD;padding-left:8px">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color:#8A867C"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="text" id="filtroBuscar" placeholder="Buscar tarea..." oninput="debounceTareas()"
            style="padding:8px 10px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;width:180px;background:#fff;transition:border-color .15s" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
    </div>

    <!-- Filtros de Fechas -->
    <div style="display:flex;align-items:center;gap:6px;border-left:1.5px solid #E8E5DD;padding-left:8px">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color:#8A867C"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <input type="date" id="filtroFechaDesde" onchange="cargarTareas()" style="padding:6px 10px;border:1.5px solid #E8E5DD;border-radius:3px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s;background:#fff" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
        <span style="color:#8A867C;font-size:12px">–</span>
        <input type="date" id="filtroFechaHasta" onchange="cargarTareas()" style="padding:6px 10px;border:1.5px solid #E8E5DD;border-radius:3px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s;background:#fff" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
    </div>

    <button onclick="limpiarFiltrosTareas()" style="padding:8px 14px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#57544D">Limpiar</button>
</div>

<!-- ══════════════════════════════════════════════════════════
     VISTA LISTA
══════════════════════════════════════════════════════════ -->
<div id="vistaLista">
    <div style="background:#fff;border:1.5px solid #E8E5DD;border-radius:4px;overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:#FAFAF7;border:1.5px solid #E8E5DD;border-bottom:1.5px solid #E8E5DD">
                        <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em;width:90px">Prioridad</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em">Título</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em;width:130px">Responsable</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em;width:110px">Vencimiento</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em;width:110px">Estado</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em;width:80px">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tareasTbody">
                    <tr><td colspan="6" style="padding:48px;text-align:center;color:#8A867C;font-size:13px">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── MODAL Detalles de Tarea ────────────────────────────────────────── -->
<div class="modal-overlay" id="detallesTareaModal">
    <div class="modal">
        <div class="modal-header" style="background:#fff;border-bottom:1.5px solid #E8E5DD;padding:20px 24px">
            <div>
                <h3 class="modal-title" style="color:#0E0E0C">Detalles de la Tarea</h3>
                <p style="font-size:12px;color:#57544D;margin:3px 0 0" id="detalleTareaTitulo">—</p>
            </div>
            <button class="modal-close" onclick="closeDetallesTareaModal()" style="color:#57544D">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;gap:12px">
            <!-- Prioridad | Estado (dos columnas) -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;background:#FAFAF7;border-radius:4px;padding:12px;margin:-4px -4px 0">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0E0E0C">Prioridad</label>
                    <div id="detallePrioridad" style="padding:10px 12px;background:#ffffff;border:1.5px solid #D6D2C7;border-radius:3px;font-size:12px;font-weight:600;color:#0E0E0C">—</div>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0E0E0C">Estado</label>
                    <div id="detalleEstado" style="padding:10px 12px;background:#ffffff;border:1.5px solid #D6D2C7;border-radius:3px;font-size:12px;font-weight:600;color:#0E0E0C">—</div>
                </div>
            </div>

            <!-- Descripción -->
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0E0E0C">Descripción</label>
                <div id="detalleDescripcion" style="padding:10px 12px;background:#FAFAF7;border:1.5px solid #D6D2C7;border-radius:3px;font-size:12px;color:#0E0E0C;line-height:1.5;min-height:50px;white-space:pre-wrap;word-break:break-word">—</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <!-- Responsable -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0E0E0C">Responsable</label>
                    <div id="detalleResponsable" style="padding:10px 12px;background:#FAFAF7;border:1.5px solid #D6D2C7;border-radius:3px;font-size:12px">—</div>
                </div>

                <!-- Fecha Límite -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0E0E0C">Fecha Límite</label>
                    <div id="detalleFecha" style="padding:10px 12px;background:#FAFAF7;border:1.5px solid #D6D2C7;border-radius:3px;font-size:12px">—</div>
                </div>
            </div>

            <!-- Cliente/Lead -->
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0E0E0C">Vinculado a</label>
                <div id="detalleVinculo" style="padding:10px 12px;background:#FAFAF7;border:1.5px solid #D6D2C7;border-radius:3px;font-size:12px">—</div>
            </div>

            <!-- Notas -->
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0E0E0C">Notas</label>
                <div id="detalleNotas" style="padding:10px 12px;background:#FAFAF7;border:1.5px solid #D6D2C7;border-radius:3px;font-size:12px;color:#0E0E0C;line-height:1.5;min-height:50px;white-space:pre-wrap;word-break:break-word">—</div>
            </div>

            <!-- Fechas de creación -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.05em">Creada el</label>
                    <div id="detalleCreated" style="font-size:11px;color:#57544D">—</div>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.05em">Última actualización</label>
                    <div id="detalleUpdated" style="font-size:11px;color:#57544D">—</div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div style="background:#ffffff;border:1.5px solid #D6D2C7;border-radius:4px;padding:12px;margin-top:8px">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0E0E0C;margin-bottom:8px;display:block">Cambiar estado rápido</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                    <button onclick="cambiarEstadoTarea('pendiente')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #E8E5DD;border-radius:3px;font-size:11px;cursor:pointer;background:#fff;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='#fff'">Pendiente</button>
                    <button onclick="cambiarEstadoTarea('en_progreso')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #3F5E9E;border-radius:3px;font-size:11px;cursor:pointer;background:#E1E7F2;color:#243A66;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#cdd6ec'" onmouseleave="this.style.background='#E1E7F2'">En Proceso</button>
                    <button onclick="cambiarEstadoTarea('revision')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #B47A1E;border-radius:3px;font-size:11px;cursor:pointer;background:#F5EBD3;color:#6E4A12;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#eadbbf'" onmouseleave="this.style.background='#F5EBD3'">Revisión</button>
                    <button onclick="cambiarEstadoTarea('completado')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #2D8F5A;border-radius:3px;font-size:11px;cursor:pointer;background:#E3F1E8;color:#1B5A39;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#cce5d5'" onmouseleave="this.style.background='#E3F1E8'">Completado</button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeDetallesTareaModal()">Cerrar</button>
            <button id="btnEditarTarea" class="btn btn-secondary" style="background:#0E0E0C;color:#fff" onclick="editarTareaDesdeModal()">
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
        <div style="background:#FAFAF7;border:1.5px solid #D6D2C7;border-radius:4px;padding:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#8A867C;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:700;color:#0E0E0C;text-transform:uppercase;letter-spacing:.04em">Pendiente</span>
                </div>
                <span id="kCount-pendiente" style="background:#EFECE5;color:#57544D;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">0</span>
            </div>
            <div id="kCol-pendiente" style="min-height:120px;display:flex;flex-direction:column;gap:8px"></div>
        </div>

        <!-- En Proceso -->
        <div style="background:#E1E7F2;border:1.5px solid #b5c3df;border-radius:4px;padding:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#3F5E9E;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:700;color:#243A66;text-transform:uppercase;letter-spacing:.04em">En Proceso</span>
                </div>
                <span id="kCount-en_progreso" style="background:#cdd6ec;color:#3F5E9E;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">0</span>
            </div>
            <div id="kCol-en_progreso" style="min-height:120px;display:flex;flex-direction:column;gap:8px"></div>
        </div>

        <!-- Revisión -->
        <div style="background:#F5EBD3;border:1.5px solid #e0c99a;border-radius:4px;padding:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#B47A1E;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:700;color:#6E4A12;text-transform:uppercase;letter-spacing:.04em">Revisión</span>
                </div>
                <span id="kCount-revision" style="background:#eadbbf;color:#B47A1E;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">0</span>
            </div>
            <div id="kCol-revision" style="min-height:120px;display:flex;flex-direction:column;gap:8px"></div>
        </div>

        <!-- Completado -->
        <div style="background:#E3F1E8;border:1.5px solid #aad4bb;border-radius:4px;padding:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#2D8F5A;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:700;color:#1B5A39;text-transform:uppercase;letter-spacing:.04em">Completado</span>
                </div>
                <span id="kCount-completado" style="background:#cce5d5;color:#2D8F5A;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">0</span>
            </div>
            <div id="kCol-completado" style="min-height:120px;display:flex;flex-direction:column;gap:8px"></div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL: NUEVA / EDITAR TAREA
══════════════════════════════════════════════════════════ -->
<div id="modalTarea" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1100;display:none;align-items:center;justify-content:center;padding:50px 24px">
  <div style="background:#fff;border-radius:6px;width:100%;max-width:730px;max-height:calc(100vh - 100px);overflow-y:auto;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;flex-direction:column">

    <!-- Header -->
    <div style="padding:20px 24px 16px;border-bottom:1.5px solid #EFECE5;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:6px 6px 0 0">
      <div>
        <h2 style="font-size:16px;font-weight:700;color:#0E0E0C;margin:0" id="modalTareaTitulo">Nueva tarea</h2>
        <p style="font-size:12px;color:#8A867C;margin:4px 0 0">Asigna, prioriza y da seguimiento</p>
      </div>
      <button onclick="cerrarModalTarea()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:4px;color:#8A867C" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='none'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px">
      <input type="hidden" id="trId">

      <!-- Título -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Título *</label>
        <input type="text" id="trTitulo" placeholder="¿Qué hay que hacer?" style="width:100%;padding:10px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
      </div>

      <!-- Descripción -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Descripción</label>
        <textarea id="trDesc" rows="2" placeholder="Detalla el alcance de la tarea..." style="width:100%;padding:10px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'"></textarea>
      </div>

      <!-- Grid 2 columnas -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <!-- Prioridad -->
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Prioridad</label>
          <select id="trPrioridad" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;background:#fff" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
            <option value="alta">Alta</option>
            <option value="media" selected>Media</option>
            <option value="baja">Baja</option>
          </select>
        </div>
        <!-- Estado -->
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Estado</label>
          <select id="trEstado" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;background:#fff" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
            <option value="pendiente" selected>Pendiente</option>
            <option value="en_progreso">En Proceso</option>
            <option value="revision">Revisión</option>
            <option value="completado">Completado</option>
          </select>
        </div>
        <!-- Responsable -->
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Responsable</label>
          <input type="text" id="trResponsable" placeholder="Nombre del responsable" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
        </div>
        <!-- Fecha límite -->
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Fecha límite</label>
          <input type="date" id="trFechaLimite" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;background:#fff" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
        </div>
      </div>

      <!-- Notas -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Notas adicionales</label>
        <textarea id="trNotas" rows="2" placeholder="Observaciones, contexto o links relevantes..." style="width:100%;padding:10px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'"></textarea>
      </div>
    </div>

    <!-- Footer -->
    <div style="padding:16px 24px;border-top:1.5px solid #EFECE5;display:flex;gap:10px;justify-content:flex-end;background:#fff;border-radius:0 0 6px 6px;position:sticky;bottom:0">
      <button onclick="cerrarModalTarea()" style="padding:10px 20px;background:transparent;color:#57544D;border:1.5px solid #E8E5DD;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='transparent'">Cancelar</button>
      <button id="trBtnGuardar" onclick="guardarTarea()" style="display:flex;align-items:center;gap:6px;padding:10px 24px;background:#0E0E0C;color:#fff;border:none;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px" onmouseenter="this.style.background='#2A2926'" onmouseleave="this.style.background='#0E0E0C'">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Guardar
      </button>
    </div>
  </div>
</div>

<!-- Modal confirmar eliminar tarea -->
<div id="modalEliminarTarea" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:6px;width:100%;max-width:400px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-align:center">
    <div style="width:52px;height:52px;background:#F4DEDB;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <svg width="24" height="24" fill="none" stroke="#B0382F" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </div>
    <h3 style="font-size:16px;font-weight:700;color:#0E0E0C;margin:0 0 8px">¿Cancelar tarea?</h3>
    <p id="modalElimTareaNombre" style="font-size:13px;color:#57544D;margin:0 0 24px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('modalEliminarTarea').style.display='none'" style="padding:10px 20px;background:transparent;border:1.5px solid #E8E5DD;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px;color:#57544D">Conservar</button>
      <button id="btnConfirmarElimTarea" style="padding:10px 20px;background:#B0382F;color:#fff;border:none;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px">Sí, cancelar</button>
    </div>
  </div>
</div>

<script>
document.getElementById('modalTarea').addEventListener('click', e => { if (e.target === document.getElementById('modalTarea')) cerrarModalTarea(); });
document.getElementById('modalEliminarTarea').addEventListener('click', e => { if (e.target === document.getElementById('modalEliminarTarea')) document.getElementById('modalEliminarTarea').style.display='none'; });
</script>

<script src="js/tareas.js?v=<?= APP_VERSION ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
