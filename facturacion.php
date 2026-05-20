<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$pdo = db();
$pageTitle    = 'Facturación';
$pageSubtitle = 'Facturas, transacciones y proveedores';

include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav style="margin-bottom:20px;font-size:13px;color:var(--color-text-muted);display:flex;align-items:center;gap:8px">
    <a href="dashboard.php" style="color:inherit;text-decoration:none">Dashboard</a>
    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M9 5l7 7-7 7"/></svg>
    <span style="color:var(--color-primary);font-weight:700">Facturación</span>
</nav>

<!-- ── TABS PRINCIPAL ─────────────────────────────────────────────────────── -->
<div style="display:flex;gap:4px;border-bottom:2px solid #e2e8f0;margin-bottom:24px">
    <button class="facTab active" data-tab="facturas"
        style="padding:10px 20px;border:none;background:none;font-size:13px;font-weight:700;cursor:pointer;border-bottom:2.5px solid #0f172a;color:#0f172a;margin-bottom:-2px;border-radius:8px 8px 0 0;transition:all .15s"
        onclick="switchFacTab('facturas',this)">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:inline;vertical-align:-2px;margin-right:5px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Facturas
    </button>
    <button class="facTab" data-tab="proveedores"
        style="padding:10px 20px;border:none;background:none;font-size:13px;font-weight:700;cursor:pointer;border-bottom:2.5px solid transparent;color:#94a3b8;margin-bottom:-2px;border-radius:8px 8px 0 0;transition:all .15s"
        onclick="switchFacTab('proveedores',this)">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:inline;vertical-align:-2px;margin-right:5px"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Proveedores
    </button>
</div>

<!-- ══════════════════════════════════════════════════════════
     TAB 1: FACTURAS / TRANSACCIONES
     ══════════════════════════════════════════════════════════ -->
<div id="tab-facturas">

    <!-- KPIs -->
    <div id="facKpisGrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px">
        <div class="cf-card cf-card--income animate-fade-up stagger-1">
            <div class="cf-card__body">
                <div class="cf-card__header"><span class="cf-card__label">Ingresos</span><span class="cf-pill cf-pill--income">Total</span></div>
                <div><p class="cf-card__value" id="kpiIngresos">$ 0</p><span style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.4)">COP</span></div>
                <span class="cf-card__footer" id="kpiIngresosCount">0 facturas</span>
            </div>
        </div>
        <div class="cf-card cf-card--expense animate-fade-up stagger-2">
            <div class="cf-card__body">
                <div class="cf-card__header"><span class="cf-card__label">Egresos</span><span class="cf-pill cf-pill--expense">Total</span></div>
                <div><p class="cf-card__value" id="kpiEgresos">$ 0</p><span style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.4)">COP</span></div>
                <span class="cf-card__footer" id="kpiEgresosCount">0 facturas</span>
            </div>
        </div>
        <div class="cf-card cf-card--profit animate-fade-up stagger-3">
            <div class="cf-card__body">
                <div class="cf-card__header"><span class="cf-card__label">Balance</span><span class="cf-pill cf-pill--profit" id="kpiBalancePill">Positivo</span></div>
                <div><p class="cf-card__value" id="kpiBalance">$ 0</p><span style="font-size:10px;font-weight:700;color:rgba(0,0,0,0.4)">COP</span></div>
                <span class="cf-card__footer" id="kpiPendientes">0 pendientes</span>
            </div>
        </div>
        <div class="cf-card animate-fade-up stagger-4" style="background:#fff;border:1.5px solid #e2e8f0">
            <div class="cf-card__body">
                <div class="cf-card__header"><span class="cf-card__label" style="color:#0f172a">Transacciones</span></div>
                <div><p class="cf-card__value" id="kpiTotal" style="color:#0f172a">0</p></div>
                <span class="cf-card__footer" id="kpiPagadas">0 pagadas</span>
            </div>
        </div>
    </div>

    <!-- Filtros + Acciones -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <!-- Tipo -->
            <select id="facFiltroTipo" onchange="cargarFacturas()" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
                <option value="">Todos</option>
                <option value="ingreso">Solo Ingresos</option>
                <option value="egreso">Solo Egresos</option>
            </select>
            <!-- Estado -->
            <select id="facFiltroEstado" onchange="cargarFacturas()" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="pagado">Pagado</option>
                <option value="vencido">Vencido</option>
            </select>
            <!-- Mes -->
            <input type="month" id="facFiltroMes" onchange="cargarFacturas()" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;background:#fff">
            <!-- Búsqueda -->
            <div style="position:relative">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="facBuscar" placeholder="Buscar concepto o proveedor..." oninput="debounceFac()" style="padding:8px 12px 8px 30px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:220px;background:#fff">
            </div>
            <button onclick="limpiarFiltrosFac()" style="padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#64748b">Limpiar</button>
        </div>
        <button onclick="abrirModalTransaccion()" style="display:flex;align-items:center;gap:6px;padding:9px 18px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;white-space:nowrap">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Nueva transacción
        </button>
    </div>

    <!-- Barra de acciones masivas -->
    <div id="facBulkBar" style="display:none;align-items:center;gap:12px;padding:10px 16px;background:#fef2f2;border-bottom:1.5px solid #fecaca;margin-bottom:16px;border-radius:8px">
        <span id="facBulkCount" style="font-size:13px;font-weight:700;color:#dc2626"></span>
        <button onclick="editSelectedFac()" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;font-size:12px;font-weight:700;color:#0f172a;background:#fff;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Editar
        </button>
        <button onclick="deleteSelectedFac()" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;font-size:12px;font-weight:700;color:#fff;background:#dc2626;border:none;border-radius:8px;cursor:pointer">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Eliminar
        </button>
        <button onclick="facClearSelection()" style="padding:6px 12px;font-size:12px;font-weight:600;color:#64748b;background:none;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer">
            Cancelar
        </button>
    </div>

    <!-- Tabla facturas -->
    <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px" id="facTabla">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:1.5px solid #e2e8f0">
                        <th style="width:36px;padding-left:16px">
                            <input type="checkbox" id="facCheckAll" onchange="facToggleAll(this)" style="width:15px;height:15px;cursor:pointer;accent-color:#dc2626">
                        </th>
                        <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Tipo</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Título / Concepto</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Destinatario</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Frecuencia</th>
                        <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Monto</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Estado</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Vencimiento</th>
                        <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Acciones</th>
                    </tr>
                </thead>
                <tbody id="facTbody">
                    <tr><td colspan="9" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Paginación -->
        <div id="facPaginacion" style="padding:12px 16px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;font-size:12px;color:#64748b"></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     TAB 2: PROVEEDORES
     ══════════════════════════════════════════════════════════ -->
<div id="tab-proveedores" style="display:none">

    <!-- Barra acciones -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:8px">
            <div style="position:relative">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="provBuscar" placeholder="Buscar proveedor..." oninput="debounceProvBuscar()" style="padding:8px 12px 8px 30px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:220px;background:#fff">
            </div>
            <select id="provFiltroCategoria" onchange="cargarProveedores()" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#fff">
                <option value="">Todas las categorías</option>
            </select>
        </div>
        <button onclick="abrirModalProveedor()" style="display:flex;align-items:center;gap:6px;padding:9px 18px;background:#c9f31d;color:#0f172a;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;white-space:nowrap">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Nuevo proveedor
        </button>
    </div>

    <!-- Grid de proveedores -->
    <div id="provGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#94a3b8;font-size:13px">Cargando proveedores...</div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL: NUEVO / EDITAR PROVEEDOR
     ══════════════════════════════════════════════════════════ -->
<div id="modalProveedor" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 40px -8px rgba(0,0,0,.2);display:flex;flex-direction:column">

    <!-- Header -->
    <div style="padding:20px 24px 16px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0">
      <div>
        <h2 style="font-size:16px;font-weight:800;color:#0f172a;margin:0" id="modalProvTitulo">Nuevo proveedor</h2>
        <p style="font-size:12px;color:#94a3b8;margin:4px 0 0">Registro de proveedor externo</p>
      </div>
      <button onclick="cerrarModalProveedor()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:#94a3b8" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='none'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px">
      <input type="hidden" id="provId">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div style="grid-column:1/-1">
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Nombre *</label>
          <input type="text" id="provNombre" placeholder="Nombre del proveedor" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">NIT / RUT</label>
          <input type="text" id="provNit" placeholder="900.123.456-7" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Categoría</label>
          <div style="display:flex;gap:6px;align-items:center">
            <select id="provCategoria" style="flex:1;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;background:#fff" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
              <option value="">Sin categoría</option>
            </select>
            <button type="button" onclick="toggleNuevaCatForm()" id="btnNuevaCat" title="Crear nueva categoría"
              style="padding:9px 11px;background:#f1f5f9;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#475569;font-size:14px;font-weight:700;line-height:1;white-space:nowrap;transition:all .12s"
              onmouseenter="this.style.background='#e2e8f0'" onmouseleave="this.style.background='#f1f5f9'">+ Nueva</button>
          </div>
          <!-- Inline: crear nueva categoría -->
          <div id="nuevaCatForm" style="display:none;margin-top:8px;display:flex;gap:6px;align-items:center">
            <input type="text" id="nuevaCatInput" placeholder="Nombre de categoría..."
              style="flex:1;padding:8px 11px;border:1.5px solid #c9f31d;border-radius:8px;font-size:12px;font-family:inherit;outline:none;background:#fff"
              onkeydown="if(event.key==='Enter'){event.preventDefault();agregarNuevaCategoria();}"
              oninput="var b=document.getElementById('btnGuardarCat');b.disabled=!this.value.trim();b.style.opacity=this.value.trim()?'1':'.5';b.style.cursor=this.value.trim()?'pointer':'default'">
            <button type="button" id="btnGuardarCat" onclick="agregarNuevaCategoria()" disabled
              style="padding:8px 13px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;opacity:.5"
              onmouseenter="if(!this.disabled)this.style.opacity='1'" onmouseleave="if(!this.disabled)this.style.opacity=''">Agregar</button>
            <button type="button" onclick="cerrarNuevaCatForm()"
              style="padding:8px 10px;background:none;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;cursor:pointer;color:#94a3b8">✕</button>
          </div>
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Email</label>
          <input type="email" id="provEmail" placeholder="contacto@proveedor.com" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Teléfono</label>
          <input type="text" id="provTelefono" placeholder="+57 300 000 0000" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Dirección</label>
          <input type="text" id="provDireccion" placeholder="Calle, ciudad" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div style="grid-column:1/-1">
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Notas</label>
          <textarea id="provNotas" rows="2" placeholder="Condiciones de pago, contacto clave, etc." style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div style="padding:16px 24px;border-top:1.5px solid #f1f5f9;display:flex;gap:10px;justify-content:flex-end;background:#fff;border-radius:0 0 16px 16px;position:sticky;bottom:0">
      <button onclick="cerrarModalProveedor()" style="padding:10px 20px;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">Cancelar</button>
      <button id="provBtnGuardar" onclick="guardarProveedor()" style="display:flex;align-items:center;gap:6px;padding:10px 24px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px" onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='#0f172a'">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Guardar
      </button>
    </div>
  </div>
</div>

<!-- Modal Factura: Ver + Editar -->
<div id="modalFactura" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:16px;width:100%;max-width:680px;max-height:92vh;overflow-y:auto;box-shadow:0 24px 40px -8px rgba(0,0,0,.2);display:flex;flex-direction:column">

    <!-- Header compartido -->
    <div style="padding:18px 24px 14px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0">
      <h2 style="font-size:15px;font-weight:800;color:#0f172a;margin:0" id="modalFacturaTitle">Ver Factura</h2>
      <button onclick="cerrarModalFactura()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:#94a3b8" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='none'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <input type="hidden" id="facId">

    <!-- ── PANEL VER (Template Preview) ────────────────────────────── -->
    <div id="facViewPanel">
      <!-- Selector de plantilla -->
      <div style="padding:10px 20px;background:#f8fafc;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;gap:10px">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="color:#64748b;flex-shrink:0"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M9 21V9"/></svg>
        <span style="font-size:11px;font-weight:700;color:#64748b;white-space:nowrap">Plantilla:</span>
        <select id="facTemplateSel" onchange="cambiarPlantillaVista()"
          style="flex:1;padding:5px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-family:inherit;outline:none;background:#fff;cursor:pointer">
          <option value="">Sin plantillas guardadas</option>
        </select>
      </div>
      <!-- Preview -->
      <div style="padding:16px;background:#dde2e9;overflow-y:auto;min-height:320px">
        <div id="facTemplateContent" style="background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.12);max-width:640px;margin:0 auto">
          <div style="padding:48px 32px;text-align:center;color:#94a3b8;font-size:13px">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2" style="margin:0 auto 10px;display:block;opacity:.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Cargando...
          </div>
        </div>
      </div>
    </div>

    <!-- ── PANEL EDITAR ──────────────────────────────────────────────── -->
    <div id="facEditPanel" style="padding:20px 24px;display:none;flex-direction:column;gap:14px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;text-transform:uppercase">Tipo</label>
          <select id="facTipo" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#fff">
            <option value="ingreso">Ingreso</option>
            <option value="egreso">Egreso</option>
          </select>
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;text-transform:uppercase">Estado</label>
          <select id="facEstado" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#fff">
            <option value="pendiente">Pendiente</option>
            <option value="pagado">Pagado</option>
            <option value="vencido">Vencido</option>
          </select>
        </div>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;text-transform:uppercase">Título / Concepto *</label>
        <input type="text" id="facTitulo" placeholder="Descripción de la factura" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;text-transform:uppercase">Monto *</label>
          <input type="number" id="facMonto" placeholder="0" step="0.01" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;text-transform:uppercase">Vencimiento</label>
          <input type="date" id="facVencimiento" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none">
        </div>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;text-transform:uppercase">Descripción</label>
        <textarea id="facDescripcion" rows="3" placeholder="Detalles adicionales" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical"></textarea>
      </div>
    </div>

    <!-- Footer: VER -->
    <div id="facViewFooter" style="padding:14px 24px;border-top:1.5px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:#fff;border-radius:0 0 16px 16px;position:sticky;bottom:0">
      <button onclick="deleteFactura(document.getElementById('facId').value)"
        style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:transparent;color:#ef4444;border:1.5px solid #fecaca;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px"
        onmouseenter="this.style.background='#fef2f2'" onmouseleave="this.style.background='transparent'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Eliminar
      </button>
      <div style="display:flex;gap:8px">
        <button onclick="imprimirFactura()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:transparent;color:#0369a1;border:1.5px solid #bae6fd;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px"
          onmouseenter="this.style.background='#f0f9ff'" onmouseleave="this.style.background='transparent'">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Imprimir
        </button>
        <button onclick="cerrarModalFactura()" style="padding:8px 16px;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">Cerrar</button>
        <button onclick="switchFacMode('edit')" style="display:flex;align-items:center;gap:6px;padding:8px 18px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Editar
        </button>
      </div>
    </div>

    <!-- Footer: EDITAR -->
    <div id="facEditFooter" style="padding:14px 24px;border-top:1.5px solid #f1f5f9;display:none;align-items:center;justify-content:flex-end;gap:10px;background:#fff;border-radius:0 0 16px 16px;position:sticky;bottom:0">
      <button onclick="switchFacMode('view')" style="padding:8px 18px;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">Cancelar</button>
      <button id="facBtnGuardar" onclick="guardarFactura()" style="display:flex;align-items:center;gap:6px;padding:8px 22px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Guardar
      </button>
    </div>
  </div>
</div>

<!-- Modal confirmar eliminar proveedor -->
<div id="modalEliminarProv" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1300;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:14px;width:100%;max-width:400px;padding:28px;box-shadow:0 24px 40px -8px rgba(0,0,0,.2);text-align:center">
    <div style="width:52px;height:52px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <svg width="24" height="24" fill="none" stroke="#ef4444" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </div>
    <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 8px">¿Eliminar proveedor?</h3>
    <p id="modalElimProvNombre" style="font-size:13px;color:#64748b;margin:0 0 24px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('modalEliminarProv').style.display='none'" style="padding:10px 20px;background:transparent;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;color:#64748b">Cancelar</button>
      <button id="btnConfirmarElimProv" style="padding:10px 20px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">Eliminar</button>
    </div>
  </div>
</div>

<script>
document.getElementById('modalProveedor').addEventListener('click', e => { if (e.target === document.getElementById('modalProveedor')) cerrarModalProveedor(); });
</script>
<script src="js/plantillas_factura.js?v=<?= APP_VERSION ?>"></script>
<script src="js/facturacion.js?v=<?= APP_VERSION ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
