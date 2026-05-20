<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$pdo = db();
$pageTitle = 'Núcleo Financiero';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Núcleo Financiero</span>';

// ─── FILTRO DE FECHA ───
$fechaDesde = $_GET['desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['hasta'] ?? date('Y-m-d');

// Obtener servicios que realmente tienen clientes asignados
$serviciosFiltro = $pdo->query("
    SELECT DISTINCT s.id, s.nombre
    FROM servicios s
    INNER JOIN cliente_servicios cs ON cs.servicio_id = s.id
    WHERE cs.estado = 'activo'
    ORDER BY s.nombre
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Period Selector bar -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px 14px">
    <div style="display:flex;gap:4px;flex-shrink:0" id="fnPeriodoBtns">
        <button onclick="setPeriodo('hoy')" data-periodo="hoy"
            style="padding:6px 14px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-weight:600;background:#fff;color:#64748b;cursor:pointer;transition:all .15s">Hoy</button>
        <button onclick="setPeriodo('semana')" data-periodo="semana"
            style="padding:6px 14px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-weight:600;background:#fff;color:#64748b;cursor:pointer;transition:all .15s">Semana</button>
        <button onclick="setPeriodo('mes')" data-periodo="mes"
            style="padding:6px 14px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-weight:600;background:#0f172a;color:#c9f31d;cursor:pointer;transition:all .15s">Mes</button>
        <button onclick="setPeriodo('año')" data-periodo="año"
            style="padding:6px 14px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-weight:600;background:#fff;color:#64748b;cursor:pointer;transition:all .15s">Año</button>
    </div>
    <div style="width:1px;height:24px;background:#e2e8f0;flex-shrink:0;margin:0 4px"></div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <input type="date" id="fnDesde" style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-family:inherit;outline:none">
        <span style="font-size:12px;color:#94a3b8">—</span>
        <input type="date" id="fnHasta" style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-family:inherit;outline:none">
        <button onclick="setPeriodo('custom')"
            style="padding:6px 14px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-weight:700;background:#f8fafc;color:#475569;cursor:pointer;transition:all .15s"
            onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='#f8fafc'">Filtrar</button>
    </div>
    <button onclick="abrirModalTransaccion()"
        style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:700;color:#000;background:#c9f31d;border:none;border-radius:9px;cursor:pointer;transition:all .15s;box-shadow:0 2px 4px rgba(201,243,29,.3);white-space:nowrap"
        onmouseenter="this.style.background='#b8dc1f';this.style.boxShadow='0 4px 8px rgba(201,243,29,.4)'"
        onmouseleave="this.style.background='#c9f31d';this.style.boxShadow='0 2px 4px rgba(201,243,29,.3)'">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        Nueva Transacción
    </button>
</div>

<!-- 4 KPI Cards -->
<div id="fnKpis" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px">
    <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:18px 20px"><span style="font-size:12px;font-weight:700;color:#15803d">Cargando...</span></div>
    <div style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:12px;padding:18px 20px"><span style="font-size:12px;font-weight:700;color:#dc2626">Cargando...</span></div>
    <div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;padding:18px 20px"><span style="font-size:12px;font-weight:700;color:#d97706">Cargando...</span></div>
    <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:18px 20px"><span style="font-size:12px;font-weight:700;color:#475569">Cargando...</span></div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:20px" id="fnTabs">
    <button onclick="switchTab('movimientos')" data-tab="movimientos"
        style="padding:10px 20px;border:none;background:none;font-size:13px;font-weight:700;color:#0f172a;cursor:pointer;border-bottom:2px solid #0f172a;margin-bottom:-2px;transition:all .15s">
        Movimientos
    </button>
    <button onclick="switchTab('pagosUnicos')" data-tab="pagosUnicos"
        style="padding:10px 20px;border:none;background:none;font-size:13px;font-weight:600;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s">
        Pagos únicos
    </button>
    <button onclick="switchTab('clientes')" data-tab="clientes"
        style="padding:10px 20px;border:none;background:none;font-size:13px;font-weight:600;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s">
        Clientes / Servicios
    </button>
</div>

<!-- Tab Movimientos -->
<div id="tabMovimientos">
    <!-- Filter bar -->
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap">
        <div style="position:relative;flex:1;min-width:200px;max-width:320px">
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="fnBuscar" placeholder="Buscar transacción..."
                style="width:100%;padding:8px 12px 8px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box;outline:none"
                oninput="filterTxTable()">
        </div>
        <select id="fnTipo" onchange="filterTxTable()"
            style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;background:#fff;outline:none">
            <option value="todos">Todos los tipos</option>
            <option value="ingreso">Ingreso</option>
            <option value="egreso">Egreso</option>
        </select>
        <select id="fnEstado" onchange="filterTxTable()"
            style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;background:#fff;outline:none">
            <option value="todos">Todos los estados</option>
            <option value="pagado">Pagado</option>
            <option value="pendiente">Pendiente</option>
            <option value="vencido">Vencido</option>
        </select>
    </div>
    <!-- Transactions table -->
    <div id="fnTxTable" style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden">
        <div style="padding:40px;text-align:center;color:#94a3b8;font-size:13px">Cargando movimientos...</div>
    </div>
</div>

<!-- Tab Pagos únicos (oculto por defecto) -->
<div id="tabPagosUnicos" style="display:none">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap">
        <div style="position:relative;flex:1;min-width:200px;max-width:320px">
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="fnUnicoBuscar" placeholder="Buscar cliente..."
                style="width:100%;padding:8px 12px 8px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box;outline:none"
                oninput="filterUnicosTable()">
        </div>
        <select id="fnUnicoEstado" onchange="filterUnicosTable()"
            style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;background:#fff;outline:none">
            <option value="todos">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="pagado">Pagado</option>
            <option value="vencido">Vencido</option>
        </select>
    </div>
    <!-- Resumen pendientes -->
    <div id="fnUnicoResumen" style="margin-bottom:14px"></div>
    <!-- Tabla -->
    <div id="fnUnicosTable" style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden">
        <div style="padding:40px;text-align:center;color:#94a3b8;font-size:13px">Cargando pagos únicos...</div>
    </div>
</div>

<!-- Tab Clientes / Servicios (oculto por defecto) -->
<div id="tabClientes" style="display:none">
    <!-- Filtros clientes -->
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <div style="position:relative">
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="filterClienteNombre" placeholder="Buscar cliente..."
                style="padding:8px 12px 8px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;width:200px;outline:none"
                oninput="aplicarFiltrosClientes()">
        </div>
        <select id="filterServicio" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;background:#fff;outline:none"
            onchange="aplicarFiltrosClientes()">
            <option value="">Todos los servicios</option>
            <?php foreach($serviciosFiltro as $s):?>
                <option value="<?=$s['id']?>"><?=sanitize($s['nombre'])?></option>
            <?php endforeach;?>
        </select>
        <select id="filterEstadoRenovacion" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;background:#fff;outline:none"
            onchange="aplicarFiltrosClientes()">
            <option value="">Todos los estados</option>
            <option value="activo">Activo</option>
            <option value="vence_mes">Vence este mes</option>
            <option value="vencido">Vencido</option>
        </select>
        <button onclick="limpiarFiltrosClientes()"
            style="padding:8px 12px;background:none;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s"
            onmouseenter="this.style.borderColor='#94a3b8';this.style.color='#475569'"
            onmouseleave="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
            × Limpiar
        </button>
    </div>
    <!-- Grid clientes -->
    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start" id="clientesDetalle">
        <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:40px;text-align:center;color:#94a3b8;font-size:13px">Cargando datos...</div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     MODAL NUEVA TRANSACCIÓN
     ══════════════════════════════════════════════════════════ -->
<div id="modalTx" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:16px;width:100%;max-width:680px;max-height:92vh;overflow-y:auto;box-shadow:0 24px 40px -8px rgba(0,0,0,.2);display:flex;flex-direction:column">

    <!-- Header -->
    <div style="padding:20px 24px 16px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0">
      <div>
        <h2 style="font-size:16px;font-weight:800;color:#0f172a;margin:0" id="txModalTitle">Nueva Transacción</h2>
        <p style="font-size:12px;color:#94a3b8;margin:4px 0 0">Registra un ingreso o egreso en el sistema</p>
      </div>
      <button onclick="cerrarModalTx()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:#94a3b8;transition:all .15s" onmouseenter="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:20px">

      <!-- 1. Tipo -->
      <div>
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:8px">Tipo de transacción</div>
        <div style="display:flex;border:1.5px solid #e2e8f0;border-radius:8px;overflow:hidden">
          <button id="txTipoIngreso" onclick="setTxTipo('ingreso')" style="flex:1;padding:10px;border:none;background:#0f172a;color:#c9f31d;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:6px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5M5 12l7-7 7 7"/></svg>
            Ingreso
          </button>
          <button id="txTipoEgreso" onclick="setTxTipo('egreso')" style="flex:1;padding:10px;border:none;background:#fff;color:#94a3b8;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;border-left:1.5px solid #e2e8f0;display:flex;align-items:center;justify-content:center;gap:6px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12l7 7 7-7"/></svg>
            Egreso
          </button>
        </div>
      </div>

      <!-- 2. Destinatario — solo visible para Ingreso -->
      <div id="txDestSection">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:8px">Destinatario</div>
        <div style="display:flex;border-bottom:1.5px solid #e2e8f0;margin-bottom:14px">
          <button class="tx-dest-tab active-dest" data-dest="cliente" onclick="setTxDest('cliente',this)" style="padding:8px 16px;border:none;background:none;font-size:12px;font-weight:700;cursor:pointer;border-bottom:2px solid #0f172a;color:#0f172a;transition:all .15s">Cliente</button>
          <button class="tx-dest-tab" data-dest="lead" onclick="setTxDest('lead',this)" style="padding:8px 16px;border:none;background:none;font-size:12px;font-weight:700;cursor:pointer;border-bottom:2px solid transparent;color:#94a3b8;transition:all .15s">Lead</button>
          <button class="tx-dest-tab" data-dest="nuevo" onclick="setTxDest('nuevo',this)" style="padding:8px 16px;border:none;background:none;font-size:12px;font-weight:700;cursor:pointer;border-bottom:2px solid transparent;color:#94a3b8;transition:all .15s">Nuevo contacto</button>
        </div>
        <!-- Buscador cliente/lead -->
        <div id="txDestBuscador">
          <div style="position:relative">
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="txDestInput" placeholder="Buscar cliente por nombre o email..." oninput="buscarTxDest()"
              style="width:100%;padding:9px 12px 9px 34px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s"
              onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            <div id="txDestDropdown" style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;max-height:200px;overflow-y:auto;display:none;z-index:10;box-shadow:0 8px 24px -4px rgba(0,0,0,.12)"></div>
          </div>
          <div id="txDestSeleccionado" style="display:none;margin-top:10px;padding:10px 14px;background:#f8fafc;border-radius:8px;border-left:3px solid #c9f31d">
            <div style="display:flex;align-items:center;justify-content:space-between">
              <div>
                <div style="font-weight:800;font-size:13px;color:#0f172a" id="txDestNombre"></div>
                <div style="font-size:11px;color:#64748b;margin-top:2px" id="txDestInfo"></div>
              </div>
              <button onclick="limpiarTxDest()" style="font-size:11px;font-weight:700;color:#94a3b8;background:none;border:none;cursor:pointer;text-decoration:underline">Cambiar</button>
            </div>
          </div>
        </div>
        <!-- Formulario nuevo contacto -->
        <div id="txDestNuevoForm" style="display:none">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div style="grid-column:1/-1">
              <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Nombre completo *</label>
              <input type="text" id="txNuevoNombre" placeholder="Nombre del contacto" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Email</label>
              <input type="email" id="txNuevoEmail" placeholder="email@ejemplo.com" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">WhatsApp</label>
              <input type="text" id="txNuevoWA" placeholder="+57 300 000 0000" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
          </div>
          <div style="margin-top:8px;padding:8px 10px;background:#fef9c3;border-radius:6px;font-size:11px;color:#92400e;display:flex;align-items:center;gap:6px">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Se creará automáticamente como Lead en el sistema
          </div>
        </div>
      </div>

      <!-- Egreso: Buscador de Proveedor con opción crear -->
      <div id="txEgresoSection" style="display:none">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:8px">Proveedor / Beneficiario <span style="font-weight:500;text-transform:none;letter-spacing:0">(opcional)</span></div>

        <!-- Buscador -->
        <div id="txProvBuscador">
          <div id="txProvInputWrap" style="position:relative">
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="txProvInput" placeholder="Buscar proveedor por nombre..."
              oninput="buscarTxProv()"
              style="width:100%;padding:9px 12px 9px 34px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s"
              onfocus="this.style.borderColor='#0f172a'" onblur="setTimeout(()=>document.getElementById('txProvDropdown').style.display='none',200)">
            <div id="txProvDropdown" style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;max-height:200px;overflow-y:auto;display:none;z-index:10;box-shadow:0 8px 24px -4px rgba(0,0,0,.12)"></div>
          </div>
          <!-- Proveedor seleccionado -->
          <div id="txProvSeleccionado" style="display:none;margin-top:10px;padding:10px 14px;background:#fafafa;border:1.5px solid #e2e8f0;border-radius:8px">
            <div style="display:flex;align-items:center;justify-content:space-between">
              <div>
                <div style="font-weight:800;font-size:13px;color:#0f172a" id="txProvNombre"></div>
                <div style="font-size:11px;color:#64748b;margin-top:2px" id="txProvInfo"></div>
              </div>
              <button onclick="limpiarTxProv()" style="font-size:11px;font-weight:700;color:#94a3b8;background:none;border:none;cursor:pointer;text-decoration:underline">Cambiar</button>
            </div>
          </div>
        </div>

        <!-- Formulario nuevo proveedor (oculto por defecto) -->
        <div id="txProvNuevoForm" style="display:none;margin-top:10px;padding:12px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px">
          <div style="font-size:11px;font-weight:800;color:#0f172a;margin-bottom:10px;display:flex;align-items:center;gap:6px">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nuevo proveedor
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div style="grid-column:1/-1">
              <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Nombre *</label>
              <input type="text" id="txProvNuevoNombre" placeholder="Ej: Google Workspace, Arriendo..." style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Ciudad</label>
              <input type="text" id="txProvNuevoCiudad" placeholder="Bogotá, Medellín..." style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Dirección</label>
              <input type="text" id="txProvNuevoDireccion" placeholder="Cra 7 # 32-45" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">NIT / Cédula</label>
              <input type="text" id="txProvNuevoNit" placeholder="900.000.000-0" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px">Teléfono</label>
              <input type="text" id="txProvNuevoTel" placeholder="300 000 0000" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
          </div>
          <div style="display:flex;gap:8px;margin-top:10px">
            <button onclick="cancelarNuevoProv()" style="flex:1;padding:8px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-weight:600;color:#64748b;background:#fff;cursor:pointer">Cancelar</button>
            <button onclick="guardarNuevoProv()" style="flex:1;padding:8px;border:none;border-radius:7px;font-size:12px;font-weight:700;color:#fff;background:#0f172a;cursor:pointer">Guardar proveedor</button>
          </div>
        </div>

        <!-- Botón crear nuevo (visible cuando no hay seleccionado y no está el form) -->
        <div id="txProvCrearBtn" style="margin-top:8px">
          <button onclick="mostrarNuevoProv()" style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border:1.5px dashed #cbd5e1;border-radius:7px;font-size:11px;font-weight:700;color:#64748b;background:none;cursor:pointer;transition:all .15s" onmouseenter="this.style.borderColor='#94a3b8';this.style.color='#475569'" onmouseleave="this.style.borderColor='#cbd5e1';this.style.color='#64748b'">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Crear nuevo proveedor
          </button>
        </div>

        <!-- Campo oculto que guarda el nombre final para el payload -->
        <input type="hidden" id="txProveedor">
      </div>

      <!-- 3. Catálogo de Servicios -->
      <div id="txCatalogoSection">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:8px">Servicios / Paquetes <span style="font-weight:500;text-transform:none;letter-spacing:0">(opcional)</span></div>
        <!-- Tabs -->
        <div style="display:flex;border-bottom:1.5px solid #e2e8f0;margin-bottom:12px">
          <button class="tx-cat-tab active-cat" onclick="setTxCatTab('subservicios',this)" style="padding:7px 14px;border:none;background:none;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;border-bottom:2px solid #0f172a;color:#0f172a;transition:all .15s">Sub-servicios</button>
          <button class="tx-cat-tab" onclick="setTxCatTab('paquetes',this)" style="padding:7px 14px;border:none;background:none;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;border-bottom:2px solid transparent;color:#94a3b8;transition:all .15s">Paquetes</button>
        </div>
        <!-- Buscador catálogo -->
        <div style="position:relative;margin-bottom:10px">
          <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          <input type="text" id="txCatSearch" placeholder="Filtrar servicios..." oninput="filtrarTxCatalogo()"
            style="width:100%;padding:8px 12px 8px 30px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s"
            onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <!-- Lista catálogo -->
        <div id="txCatPanel" style="max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;padding-right:2px">
          <div style="text-align:center;padding:20px;color:#94a3b8;font-size:12px">Cargando catálogo...</div>
        </div>
        <!-- Items seleccionados -->
        <div id="txItemsContainer" style="display:none;margin-top:12px">
          <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:8px">Items seleccionados</div>
          <div style="border:1.5px solid #e2e8f0;border-radius:8px;overflow:hidden">
            <table style="width:100%;border-collapse:collapse;font-size:12px">
              <thead>
                <tr style="background:#f8fafc">
                  <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase">Descripción</th>
                  <th style="padding:8px 8px;text-align:center;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;width:60px">Cant.</th>
                  <th style="padding:8px 8px;text-align:right;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;width:110px">Precio unit.</th>
                  <th style="padding:8px 8px;text-align:right;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;width:110px">Total</th>
                  <th style="width:32px"></th>
                </tr>
              </thead>
              <tbody id="txItemsTbody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 4. Detalles de la transacción -->
      <div style="border-top:1.5px solid #f1f5f9;padding-top:16px">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:12px">Detalles</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <!-- Título del ingreso — solo para Ingreso -->
          <div id="txTituloSection" style="grid-column:1/-1">
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Título del ingreso *</label>
            <input type="text" id="txTitulo" placeholder="Ej: Venta proyecto X, Consultoría..." style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
          </div>
          <div style="grid-column:1/-1">
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Concepto *</label>
            <input type="text" id="txConcepto" placeholder="Descripción de la transacción" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
          </div>
          <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Monto base (COP) *</label>
            <input type="number" id="txMonto" placeholder="0" min="0" step="1" oninput="recalcularTxTotal()" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
          </div>
          <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Descuento (COP)</label>
            <input type="number" id="txDescuento" placeholder="0" min="0" step="1" oninput="recalcularTxTotal()" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
          </div>
          <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Frecuencia</label>
            <select id="txFrecuencia" onchange="recalcularTxTotal()" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;background:#fff;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
              <option value="unico">Único (pago único)</option>
              <option value="mensual">Mensual</option>
              <option value="trimestral">Trimestral</option>
              <option value="semestral">Semestral</option>
              <option value="anual">Anual</option>
            </select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Estado</label>
            <select id="txEstado" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;background:#fff;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
              <option value="pendiente">Pendiente</option>
              <option value="pagado">Pagado</option>
            </select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Fecha de vencimiento</label>
            <input type="date" id="txFechaVenc" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'">
          </div>
          <div style="grid-column:1/-1">
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px">Notas adicionales</label>
            <textarea id="txNotas" rows="2" placeholder="Detalles adicionales opcionales..." style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical;transition:border-color .15s" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
          </div>
        </div>
      </div>

      <!-- Archivos adjuntos — zona única multi-archivo -->
      <div id="txArchivosSection" style="display:none">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:8px">Archivos adjuntos <span style="font-weight:500;text-transform:none;letter-spacing:0">(opcional)</span></div>
        <label for="txArchivos" id="txDropZone"
          style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:24px 16px;border:2px dashed #cbd5e1;border-radius:10px;background:#f8fafc;cursor:pointer;transition:all .15s"
          ondragover="event.preventDefault();this.style.borderColor='#0f172a';this.style.background='#f1f5f9'"
          ondragleave="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'"
          ondrop="onTxFileDrop(event)">
          <svg width="24" height="24" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
          <div style="text-align:center">
            <p style="font-size:13px;font-weight:600;color:#475569;margin:0">Arrastra archivos aquí o <span style="color:#0f172a;text-decoration:underline">haz clic para seleccionar</span></p>
            <p style="font-size:11px;color:#94a3b8;margin:4px 0 0">PDF, imágenes, Word, Excel — puedes subir varios a la vez</p>
          </div>
          <input type="file" id="txArchivos" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,image/*"
            style="display:none" onchange="onTxFileChange(this)">
        </label>
        <!-- Lista de archivos seleccionados -->
        <div id="txArchivosLista" style="display:none;margin-top:8px;display:flex;flex-direction:column;gap:4px"></div>
        <!-- Inputs ocultos para compatibilidad con lógica de guardado existente -->
        <input type="hidden" id="txFactura">
        <input type="hidden" id="txImagen">
        <input type="hidden" id="txDocumento">
      </div>

      <!-- Total preview — desglosado -->
      <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden">
        <div id="txSubtotalRow" style="display:none;padding:10px 18px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:11px;color:#94a3b8">Subtotal</span>
          <span id="txSubtotalLabel" style="font-size:13px;font-weight:700;color:#64748b">$ 0</span>
        </div>
        <div id="txDescuentoRow" style="display:none;padding:10px 18px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:11px;color:#f59e0b;font-weight:700">Descuento</span>
          <span id="txDescuentoLabel" style="font-size:13px;font-weight:700;color:#f59e0b">-$ 0</span>
        </div>
        <div style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between">
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Total</span>
            <span id="txFrecuenciaLabel" style="font-size:10px;font-weight:700;color:#0f172a;background:#e2e8f0;padding:2px 7px;border-radius:20px;display:none"></span>
          </div>
          <span id="txTotalLabel" style="font-size:20px;font-weight:900;color:#0f172a">$ 0</span>
        </div>
      </div>

    </div>

    <!-- Footer -->
    <div style="padding:16px 24px;border-top:1.5px solid #f1f5f9;display:flex;gap:10px;justify-content:flex-end;background:#fff;border-radius:0 0 16px 16px;position:sticky;bottom:0">
      <button onclick="cerrarModalTx()" style="padding:10px 20px;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;transition:all .15s" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">Cancelar</button>
      <button id="txBtnGuardar" onclick="guardarTransaccion()" style="display:flex;align-items:center;gap:6px;padding:10px 24px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;transition:all .15s" onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='#0f172a'">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Guardar transacción
      </button>
    </div>

  </div>
</div>

<script>
// Cerrar modal al click fuera
document.getElementById('modalTx').addEventListener('click', function(e) {
  if (e.target === this) cerrarModalTx();
});
// Cerrar al buscar destino – click fuera del dropdown
document.addEventListener('click', function(e) {
  if (!e.target.closest('#txDestBuscador')) {
    const dd = document.getElementById('txDestDropdown');
    if (dd) dd.style.display = 'none';
  }
});
</script>

<!-- ══════════════════════════════════════════════════════════
     MODAL COMPROBANTE (ojito)
     ══════════════════════════════════════════════════════════ -->
<div id="modalComprobante" style="position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1300;display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:16px;width:100%;max-width:820px;max-height:94vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 48px -8px rgba(0,0,0,.3)">
    <!-- Header -->
    <div style="padding:16px 20px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;gap:12px;flex-shrink:0">
      <div style="flex:1;min-width:0">
        <h2 style="font-size:15px;font-weight:800;color:#0f172a;margin:0" id="cmpTitle">Comprobante</h2>
        <p style="font-size:11px;color:#94a3b8;margin:3px 0 0" id="cmpSubtitle"></p>
      </div>
      <!-- Selector plantilla -->
      <select id="cmpPlantilla" onchange="renderComprobante()"
        style="padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:inherit;background:#fff;outline:none;color:#0f172a">
        <option value="clasica">Clásica</option>
        <option value="moderna">Moderna</option>
        <option value="minimalista">Minimalista</option>
        <option value="ejecutiva">Ejecutiva</option>
      </select>
      <button onclick="editarDesdeComprobante()" title="Editar"
        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#475569;background:#fff;cursor:pointer;transition:all .15s"
        onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='#fff'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Editar
      </button>
      <button onclick="descargarComprobante()" title="Descargar"
        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:none;border-radius:8px;font-size:12px;font-weight:700;color:#000;background:#c9f31d;cursor:pointer;transition:all .15s"
        onmouseenter="this.style.background='#b8dc1f'" onmouseleave="this.style.background='#c9f31d'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Descargar
      </button>
      <button onclick="cerrarComprobante()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:#94a3b8;transition:all .15s;flex-shrink:0" onmouseenter="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseleave="this.style.background='none';this.style.color='#94a3b8'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <!-- Preview del comprobante -->
    <div id="cmpPreview" style="flex:1;overflow-y:auto;padding:20px;background:#f1f5f9">
      <div style="text-align:center;padding:60px;color:#94a3b8">Cargando comprobante...</div>
    </div>
  </div>
</div>

<!-- ── Modal Notificar Pago Único ──────────────────────────────────────────── -->
<div class="modal-overlay" id="fnMsgModal">
    <div class="modal" style="max-width:730px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column">
        <div class="modal-header" style="flex-shrink:0">
            <div>
                <h3 class="modal-title" style="display:flex;align-items:center;gap:8px">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Notificar a <span id="fnMsgNombre" style="color:var(--color-primary)"></span>
                </h3>
                <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Pago único pendiente — elige plantilla y canal</p>
            </div>
            <button class="modal-close" onclick="fnCerrarMsgModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Tabs WA / Email -->
        <div style="display:flex;gap:0;border-bottom:2px solid #f1f5f9;padding:0 20px;flex-shrink:0">
            <button id="fnMsgTabWA" onclick="fnMsgSwitchTab('wa')"
                style="display:inline-flex;align-items:center;gap:6px;padding:11px 16px;background:none;border:none;border-bottom:3px solid #25D366;margin-bottom:-2px;font-size:13px;font-weight:700;color:#0f172a;cursor:pointer">
                <svg width="14" height="14" fill="#25D366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                WhatsApp
            </button>
            <button id="fnMsgTabEmail" onclick="fnMsgSwitchTab('email')"
                style="display:inline-flex;align-items:center;gap:6px;padding:11px 16px;background:none;border:none;border-bottom:3px solid transparent;margin-bottom:-2px;font-size:13px;font-weight:700;color:#94a3b8;cursor:pointer">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Correo
            </button>
        </div>

        <div style="flex:1;overflow-y:auto;padding:20px">
            <!-- Step 1: plantillas -->
            <div id="fnMsgStep1">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Elige una plantilla</div>
                <div id="fnMsgPlantillasGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div style="grid-column:1/-1;text-align:center;padding:30px;color:#94a3b8;font-size:13px">Cargando plantillas...</div>
                </div>
            </div>
            <!-- Step 2 WA -->
            <div id="fnMsgStep2WA" style="display:none;flex-direction:column;gap:16px">
                <button onclick="fnMsgVolverStep1()" style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;color:#64748b;font-size:12px;font-weight:700;cursor:pointer;padding:0;margin-bottom:4px">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Volver a plantillas
                </button>
                <div style="background:#f8fafc;border-radius:10px;padding:12px 16px;font-size:12px;color:#475569">Enviando a: <strong id="fnMsgWADest"></strong></div>
                <div>
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Vista previa</div>
                    <div id="fnMsgWAPreview" style="padding:14px 16px;background:#dcfce7;border-radius:12px 12px 12px 0;font-size:13px;color:#0f172a;line-height:1.6;white-space:pre-wrap;word-break:break-word;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #bbf7d0;min-height:60px"></div>
                </div>
            </div>
            <!-- Step 2 Email -->
            <div id="fnMsgStep2Email" style="display:none;flex-direction:column;gap:14px">
                <button onclick="fnMsgVolverStep1()" style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;color:#64748b;font-size:12px;font-weight:700;cursor:pointer;padding:0;margin-bottom:4px">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Volver a plantillas
                </button>
                <div style="background:#f8fafc;border-radius:10px;padding:12px 16px;font-size:12px;color:#475569">Enviando a: <strong id="fnMsgEmailDest"></strong></div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Asunto *</label>
                    <input type="text" id="fnMsgEmailAsunto" class="form-input" placeholder="Asunto del correo">
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Mensaje</div>
                    <div id="fnMsgEmailPreview" style="padding:12px 14px;background:#f8fafc;border-radius:8px;font-size:12px;color:#475569;line-height:1.6;white-space:pre-wrap;border:1.5px solid #e2e8f0;max-height:160px;overflow-y:auto"></div>
                </div>
            </div>
        </div>

        <div class="modal-footer" style="flex-shrink:0">
            <button class="btn btn-outline" onclick="fnCerrarMsgModal()">Cancelar</button>
            <button id="fnMsgBtnWA" style="display:none;align-items:center;gap:7px;padding:10px 20px;background:#25D366;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer" onclick="fnMsgConfirmarWA()">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                Abrir WhatsApp
            </button>
            <button id="fnMsgBtnEmail" style="display:none;align-items:center;gap:7px;padding:10px 20px;background:#4f46e5;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer" onclick="fnMsgConfirmarEmail()">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Enviar correo
            </button>
        </div>
    </div>
</div>

<script src="js/plantillas_factura.js?v=<?= APP_VERSION ?>"></script>
<script src="js/finanzas.js?v=<?= APP_VERSION ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
