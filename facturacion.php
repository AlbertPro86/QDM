<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$pdo = db();
$pageTitle    = 'Facturación';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Facturación</span>';

include __DIR__ . '/includes/header.php';
?>

<!-- ── TABS PRINCIPAL ─────────────────────────────────────────────────────── -->
<div style="display:flex;gap:4px;border-bottom:2px solid #E8E5DD;margin-bottom:24px">
    <button class="facTab active" data-tab="facturas"
        style="padding:10px 20px;border:none;background:none;font-size:13px;font-weight:700;cursor:pointer;border-bottom:2.5px solid #0E0E0C;color:#0E0E0C;margin-bottom:-2px;border-radius:4px 4px 0 0;transition:all .15s"
        onclick="switchFacTab('facturas',this)">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:inline;vertical-align:-2px;margin-right:5px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Facturas
    </button>
    <button class="facTab" data-tab="proveedores"
        style="padding:10px 20px;border:none;background:none;font-size:13px;font-weight:700;cursor:pointer;border-bottom:2.5px solid transparent;color:#8A867C;margin-bottom:-2px;border-radius:4px 4px 0 0;transition:all .15s"
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
        <!-- Ingresos -->
        <div style="background:#E3F1E8;border:1.5px solid #B8DEC5;border-radius:3px;padding:16px 20px;transition:box-shadow .15s"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:#1B5A39;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Ingresos</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1" id="kpiIngresos">$ 0</div>
            <div style="font-size:10px;color:#8A867C;margin-top:5px">COP</div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D" id="kpiIngresosCount">0 facturas</span>
                <span style="font-size:10px;font-weight:700;background:#C8EAD3;color:#1B5A39;padding:2px 8px;border-radius:100px">Total</span>
            </div>
        </div>
        <!-- Egresos -->
        <div style="background:#F4DEDB;border:1.5px solid #E8BCB8;border-radius:3px;padding:16px 20px;transition:box-shadow .15s"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:#6E211B;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Egresos</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1" id="kpiEgresos">$ 0</div>
            <div style="font-size:10px;color:#8A867C;margin-top:5px">COP</div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D" id="kpiEgresosCount">0 egresos</span>
                <span style="font-size:10px;font-weight:700;background:#F4DEDB;color:#6E211B;padding:2px 8px;border-radius:100px">Gastos</span>
            </div>
        </div>
        <!-- Balance -->
        <div id="kpiBalanceCard" style="background:#C6F24E;border:1.5px solid #A8D87A;border-radius:3px;padding:16px 20px;transition:box-shadow .15s"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:rgba(0,0,0,.55);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Balance</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1" id="kpiBalance">$ 0</div>
            <div style="font-size:10px;color:rgba(0,0,0,0.4);margin-top:5px">COP</div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D" id="kpiPendientes">0 pendientes</span>
                <span style="font-size:10px;font-weight:700;background:rgba(0,0,0,0.12);color:#0E0E0C;padding:2px 8px;border-radius:100px" id="kpiBalancePill">Positivo</span>
            </div>
        </div>
        <!-- Transacciones -->
        <div style="background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:3px;padding:16px 20px;transition:box-shadow .15s"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Transacciones</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1" id="kpiTotal">0</div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D" id="kpiPagadas">0 registradas</span>
                <span style="font-size:10px;font-weight:700;background:#E8E5DD;color:#0E0E0C;padding:2px 8px;border-radius:100px">Total</span>
            </div>
        </div>
    </div>

    <!-- Filtros + Acciones -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <!-- Tipo -->
            <select id="facFiltroTipo" onchange="cargarFacturas()" style="padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#FFFFFF">
                <option value="">Todos</option>
                <option value="ingreso">Solo Ingresos</option>
                <option value="egreso">Solo Egresos</option>
            </select>
            <!-- Estado -->
            <select id="facFiltroEstado" onchange="cargarFacturas()" style="padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#FFFFFF">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="pagado">Pagado</option>
                <option value="vencido">Vencido</option>
            </select>
            <!-- Mes -->
            <input type="month" id="facFiltroMes" onchange="cargarFacturas()" style="padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;background:#FFFFFF">
            <!-- Búsqueda -->
            <div style="position:relative">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#8A867C;pointer-events:none" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="facBuscar" placeholder="Buscar concepto o proveedor..." oninput="debounceFac()" style="padding:8px 12px 8px 30px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;width:220px;background:#FFFFFF">
            </div>
            <button class="btn btn-ghost btn-sm" onclick="limpiarFiltrosFac()">Limpiar</button>
        </div>
        <button class="btn btn-accent" onclick="abrirModalTransaccion()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Nueva transacción
        </button>
    </div>

    <!-- Barra de acciones masivas -->
    <div id="facBulkBar" style="display:none;align-items:center;gap:12px;padding:10px 16px;background:#F4DEDB;border-bottom:1.5px solid #D6D2C7;margin-bottom:16px;border-radius:4px">
        <span id="facBulkCount" style="font-size:13px;font-weight:700;color:#B0382F"></span>
        <button class="btn btn-secondary btn-sm" onclick="editSelectedFac()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Editar
        </button>
        <button class="btn btn-danger btn-sm" onclick="deleteSelectedFac()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Eliminar
        </button>
        <button class="btn btn-ghost btn-sm" onclick="facClearSelection()">Cancelar</button>
    </div>

    <!-- Tabla facturas -->
    <div style="background:#FFFFFF;border:1.5px solid #E8E5DD;border-radius:6px;overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px" id="facTabla">
                <thead>
                    <tr style="background:#FAFAF7;border-bottom:1.5px solid #E8E5DD">
                        <th style="width:36px;padding-left:16px">
                            <input type="checkbox" id="facCheckAll" onchange="facToggleAll(this)" style="width:15px;height:15px;cursor:pointer;accent-color:#dc2626">
                        </th>
                        <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.06em">Tipo</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.06em">Título / Concepto</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.06em">Destinatario</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.06em">Frecuencia</th>
                        <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.06em">Monto</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.06em">Estado</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.06em">Vencimiento</th>
                        <th style="padding:10px 12px;text-align:right;font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.06em">Acciones</th>
                    </tr>
                </thead>
                <tbody id="facTbody">
                    <tr><td colspan="9" style="padding:48px;text-align:center;color:#8A867C;font-size:13px">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Paginación -->
        <div id="facPaginacion" style="padding:12px 16px;border-top:1px solid #E8E5DD;display:flex;align-items:center;justify-content:space-between;font-size:12px;color:#57544D"></div>
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
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#8A867C;pointer-events:none" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="provBuscar" placeholder="Buscar proveedor..." oninput="debounceProvBuscar()" style="padding:8px 12px 8px 30px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;width:220px;background:#FFFFFF">
            </div>
            <select id="provFiltroCategoria" onchange="cargarProveedores()" style="padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;background:#FFFFFF">
                <option value="">Todas las categorías</option>
            </select>
        </div>
        <button onclick="abrirModalProveedor()" style="display:flex;align-items:center;gap:6px;padding:9px 18px;background:#0E0E0C;color:#C6F24E;border:none;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px;white-space:nowrap">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Nuevo proveedor
        </button>
    </div>

    <!-- Grid de proveedores -->
    <div id="provGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#8A867C;font-size:13px">Cargando proveedores...</div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL: NUEVO / EDITAR PROVEEDOR
     ══════════════════════════════════════════════════════════ -->
<div id="modalProveedor" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#FFFFFF;border-radius:6px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 1px 2px rgba(14,14,12,.06);display:flex;flex-direction:column">

    <!-- Header -->
    <div style="padding:20px 24px 16px;border-bottom:1.5px solid #E8E5DD;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#FFFFFF;z-index:1;border-radius:6px 6px 0 0">
      <div>
        <h2 style="font-size:16px;font-weight:700;color:#0E0E0C;margin:0" id="modalProvTitulo">Nuevo proveedor</h2>
        <p style="font-size:12px;color:#8A867C;margin:4px 0 0">Registro de proveedor externo</p>
      </div>
      <button onclick="cerrarModalProveedor()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:4px;color:#8A867C" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='none'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px">
      <input type="hidden" id="provId">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div style="grid-column:1/-1">
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Nombre *</label>
          <input type="text" id="provNombre" placeholder="Nombre del proveedor" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">NIT / RUT</label>
          <input type="text" id="provNit" placeholder="900.123.456-7" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Categoría</label>
          <div style="display:flex;gap:6px;align-items:center">
            <select id="provCategoria" style="flex:1;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;background:#FFFFFF" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
              <option value="">Sin categoría</option>
            </select>
            <button type="button" onclick="toggleNuevaCatForm()" id="btnNuevaCat" title="Crear nueva categoría"
              style="padding:9px 11px;background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:4px;cursor:pointer;color:#57544D;font-size:14px;font-weight:700;line-height:1;white-space:nowrap;transition:all .12s"
              onmouseenter="this.style.background='#E8E5DD'" onmouseleave="this.style.background='#FAFAF7'">+ Nueva</button>
          </div>
          <!-- Inline: crear nueva categoría -->
          <div id="nuevaCatForm" style="display:none;margin-top:8px;display:flex;gap:6px;align-items:center">
            <input type="text" id="nuevaCatInput" placeholder="Nombre de categoría..."
              style="flex:1;padding:8px 11px;border:1.5px solid #C6F24E;border-radius:4px;font-size:12px;font-family:inherit;outline:none;background:#FFFFFF"
              onkeydown="if(event.key==='Enter'){event.preventDefault();agregarNuevaCategoria();}"
              oninput="var b=document.getElementById('btnGuardarCat');b.disabled=!this.value.trim();b.style.opacity=this.value.trim()?'1':'.5';b.style.cursor=this.value.trim()?'pointer':'default'">
            <button type="button" id="btnGuardarCat" onclick="agregarNuevaCategoria()" disabled
              style="padding:8px 13px;background:#0E0E0C;color:#C6F24E;border:none;border-radius:4px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;opacity:.5"
              onmouseenter="if(!this.disabled)this.style.opacity='1'" onmouseleave="if(!this.disabled)this.style.opacity=''">Agregar</button>
            <button type="button" onclick="cerrarNuevaCatForm()"
              style="padding:8px 10px;background:none;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;cursor:pointer;color:#8A867C">✕</button>
          </div>
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Email</label>
          <input type="email" id="provEmail" placeholder="contacto@proveedor.com" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Teléfono</label>
          <input type="text" id="provTelefono" placeholder="+57 300 000 0000" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Dirección</label>
          <input type="text" id="provDireccion" placeholder="Calle, ciudad" style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'">
        </div>
        <div style="grid-column:1/-1">
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px">Notas</label>
          <textarea id="provNotas" rows="2" placeholder="Condiciones de pago, contacto clave, etc." style="width:100%;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical" onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'"></textarea>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div style="padding:16px 24px;border-top:1.5px solid #E8E5DD;display:flex;gap:10px;justify-content:flex-end;background:#FFFFFF;border-radius:0 0 6px 6px;position:sticky;bottom:0">
      <button onclick="cerrarModalProveedor()" style="padding:10px 20px;background:transparent;color:#57544D;border:1.5px solid #E8E5DD;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='transparent'">Cancelar</button>
      <button id="provBtnGuardar" onclick="guardarProveedor()" style="display:flex;align-items:center;gap:6px;padding:10px 24px;background:#0E0E0C;color:#C6F24E;border:none;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px" onmouseenter="this.style.background='#0E0E0C'" onmouseleave="this.style.background='#0E0E0C'">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Guardar
      </button>
    </div>
  </div>
</div>

<!-- Modal Factura: Ver + Editar -->
<div id="modalFactura" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#FFFFFF;border-radius:6px;width:100%;max-width:680px;max-height:92vh;overflow-y:auto;box-shadow:0 1px 2px rgba(14,14,12,.06);display:flex;flex-direction:column">

    <!-- Header compartido -->
    <div style="padding:18px 24px 14px;border-bottom:1.5px solid #E8E5DD;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#FFFFFF;z-index:1;border-radius:6px 6px 0 0">
      <h2 style="font-size:15px;font-weight:700;color:#0E0E0C;margin:0" id="modalFacturaTitle">Ver Factura</h2>
      <button onclick="cerrarModalFactura()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:4px;color:#8A867C" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='none'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <input type="hidden" id="facId">

    <!-- ── PANEL VER (Template Preview) ────────────────────────────── -->
    <div id="facViewPanel">
      <!-- Selector de plantilla -->
      <div style="padding:10px 20px;background:#FAFAF7;border-bottom:1.5px solid #E8E5DD;display:flex;align-items:center;gap:10px">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="color:#57544D;flex-shrink:0"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M9 21V9"/></svg>
        <span style="font-size:11px;font-weight:700;color:#57544D;white-space:nowrap">Plantilla:</span>
        <select id="facTemplateSel" onchange="cambiarPlantillaVista()"
          style="flex:1;padding:5px 10px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:12px;font-family:inherit;outline:none;background:#FFFFFF;cursor:pointer">
          <option value="">Sin plantillas guardadas</option>
        </select>
      </div>
      <!-- Preview -->
      <div style="padding:16px;background:#E8E5DD;overflow-y:auto;min-height:320px">
        <div id="facTemplateContent" style="background:#FFFFFF;border-radius:4px;overflow:hidden;box-shadow:0 1px 2px rgba(14,14,12,.06);max-width:640px;margin:0 auto">
          <div style="padding:48px 32px;text-align:center;color:#8A867C;font-size:13px">
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
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px;text-transform:uppercase">Tipo</label>
          <select id="facTipo" style="width:100%;padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;outline:none;background:#FFFFFF">
            <option value="ingreso">Ingreso</option>
            <option value="egreso">Egreso</option>
          </select>
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px;text-transform:uppercase">Estado</label>
          <select id="facEstado" style="width:100%;padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;outline:none;background:#FFFFFF">
            <option value="pendiente">Pendiente</option>
            <option value="pagado">Pagado</option>
            <option value="vencido">Vencido</option>
          </select>
        </div>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px;text-transform:uppercase">Título / Concepto *</label>
        <input type="text" id="facTitulo" placeholder="Descripción de la factura" style="width:100%;padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;outline:none;box-sizing:border-box">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px;text-transform:uppercase">Monto *</label>
          <input type="number" id="facMonto" placeholder="0" step="0.01" style="width:100%;padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;outline:none">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px;text-transform:uppercase">Vencimiento</label>
          <input type="date" id="facVencimiento" style="width:100%;padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;outline:none">
        </div>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#57544D;display:block;margin-bottom:5px;text-transform:uppercase">Descripción</label>
        <textarea id="facDescripcion" rows="3" placeholder="Detalles adicionales" style="width:100%;padding:8px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical"></textarea>
      </div>
    </div>

    <!-- Footer: VER -->
    <div id="facViewFooter" style="padding:14px 24px;border-top:1.5px solid #E8E5DD;display:flex;align-items:center;justify-content:space-between;background:#FFFFFF;border-radius:0 0 6px 6px;position:sticky;bottom:0">
      <button onclick="deleteFactura(document.getElementById('facId').value)"
        style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:transparent;color:#B0382F;border:1.5px solid #F4DEDB;border-radius:4px;font-weight:700;cursor:pointer;font-size:12px"
        onmouseenter="this.style.background='#F4DEDB'" onmouseleave="this.style.background='transparent'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Eliminar
      </button>
      <div style="display:flex;gap:8px">
        <button onclick="imprimirFactura()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:transparent;color:#0369a1;border:1.5px solid #bae6fd;border-radius:4px;font-weight:700;cursor:pointer;font-size:12px"
          onmouseenter="this.style.background='#f0f9ff'" onmouseleave="this.style.background='transparent'">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Imprimir
        </button>
        <button onclick="cerrarModalFactura()" style="padding:8px 16px;background:transparent;color:#57544D;border:1.5px solid #E8E5DD;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px">Cerrar</button>
        <button onclick="switchFacMode('edit')" style="display:flex;align-items:center;gap:6px;padding:8px 18px;background:#0E0E0C;color:#C6F24E;border:none;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Editar
        </button>
      </div>
    </div>

    <!-- Footer: EDITAR -->
    <div id="facEditFooter" style="padding:14px 24px;border-top:1.5px solid #E8E5DD;display:none;align-items:center;justify-content:flex-end;gap:10px;background:#FFFFFF;border-radius:0 0 6px 6px;position:sticky;bottom:0">
      <button onclick="switchFacMode('view')" style="padding:8px 18px;background:transparent;color:#57544D;border:1.5px solid #E8E5DD;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px">Cancelar</button>
      <button id="facBtnGuardar" onclick="guardarFactura()" style="display:flex;align-items:center;gap:6px;padding:8px 22px;background:#0E0E0C;color:#C6F24E;border:none;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Guardar
      </button>
    </div>
  </div>
</div>

<!-- Modal confirmar eliminar proveedor -->
<div id="modalEliminarProv" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1300;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#FFFFFF;border-radius:6px;width:100%;max-width:400px;padding:28px;box-shadow:0 1px 2px rgba(14,14,12,.06);text-align:center">
    <div style="width:52px;height:52px;background:#F4DEDB;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <svg width="24" height="24" fill="none" stroke="#B0382F" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </div>
    <h3 style="font-size:16px;font-weight:700;color:#0E0E0C;margin:0 0 8px">¿Eliminar proveedor?</h3>
    <p id="modalElimProvNombre" style="font-size:13px;color:#57544D;margin:0 0 24px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('modalEliminarProv').style.display='none'" style="padding:10px 20px;background:transparent;border:1.5px solid #E8E5DD;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px;color:#57544D">Cancelar</button>
      <button id="btnConfirmarElimProv" style="padding:10px 20px;background:#B0382F;color:#fff;border:none;border-radius:4px;font-weight:700;cursor:pointer;font-size:13px">Eliminar</button>
    </div>
  </div>
</div>

<script>
document.getElementById('modalProveedor').addEventListener('click', e => { if (e.target === document.getElementById('modalProveedor')) cerrarModalProveedor(); });
</script>
<script src="js/plantillas_factura.js?v=<?= APP_VERSION ?>"></script>
<script src="js/facturacion.js?v=<?= APP_VERSION ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
