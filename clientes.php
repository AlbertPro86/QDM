<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle = 'Dirección de Clientes';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Área de Clientes</span>';

include __DIR__ . '/includes/header.php';

$pdo = db();
$servicios = $pdo->query("SELECT id, nombre FROM servicios WHERE activo = 1 ORDER BY nombre")->fetchAll();

$subServicios = [];
try {
    $result = $pdo->query("SELECT ss.*, s.nombre as servicio_nombre FROM sub_servicios ss JOIN servicios s ON ss.servicio_id = s.id WHERE s.activo = 1 ORDER BY s.nombre, ss.nombre");
    if ($result) $subServicios = $result->fetchAll();
} catch (Exception $e) {
    $subServicios = [];
}

$paquetes = [];
try {
    $result = $pdo->query("SELECT * FROM paquetes WHERE activo = 1 ORDER BY nombre");
    if ($result) $paquetes = $result->fetchAll();
} catch (Exception $e) {
    $paquetes = [];
}

// Obtener leads ganados no convertidos
$stmt = $pdo->query("SELECT * FROM leads WHERE estado = 'ganado' AND id NOT IN (SELECT lead_id FROM clientes WHERE lead_id IS NOT NULL)");
$leadsToConvert = $stmt->fetchAll();
?>

<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:4px">
    <div class="page-header">
        <div class="page-header-left">
            <div class="tabs-nav" id="clientTabs">
                <button class="tab-btn active" data-tab="activeClients">Mis Clientes</button>
                <button class="tab-btn" data-tab="uniqueClients">Pagos únicos</button>
                <button class="tab-btn" data-tab="renewals">Próximas Renovaciones</button>
                <button class="tab-btn" data-tab="pipeline">Conversión (<?= count($leadsToConvert) ?>)</button>
            </div>
        </div>
        <div class="page-header-right">
            <button class="btn btn-accent" onclick="openClientModal()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nuevo Cliente
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="width: 100%; padding: 16px; display: flex; gap: 16px; align-items: center; background: white; flex-wrap: wrap;">
        <div style="flex:1;min-width:260px;position:relative;display:flex;align-items:center;background:#fff;border:2px solid #0E0E0C;border-radius:6px;padding:0 12px;height:42px;transition:box-shadow .15s;box-shadow:none" onfocus-within="this.style.boxShadow='0 0 0 3px rgba(14,14,12,.08)'">
            <svg width="17" height="17" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2.5" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="clientSearch" placeholder="Buscar cliente, contacto, NIT, servicio…" oninput="onClientSearch()"
                style="border:none;outline:none;width:100%;font-size:13px;font-weight:500;color:#0E0E0C;background:transparent;padding:0 8px;font-family:inherit"
                onfocus="this.parentElement.style.boxShadow='0 0 0 3px rgba(14,14,12,.1)'"
                onblur="this.parentElement.style.boxShadow='none'">
            <button id="clientSearchClear" onclick="limpiarBusqueda()" title="Limpiar"
                style="display:none;border:none;background:none;cursor:pointer;color:#8A867C;font-size:18px;line-height:1;padding:0 2px;flex-shrink:0"
                onmouseenter="this.style.color='#0E0E0C'" onmouseleave="this.style.color='#8A867C'">&times;</button>
        </div>
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase;">Servicio / Sub Servicio / Suscripción</label>
                <select class="form-select" id="filterSvcType" style="width: 320px;">
                    <option value="todos">Todos</option>
                    <optgroup label="Servicios">
                        <?php foreach($servicios as $svc): ?>
                        <option value="<?= sanitize($svc['nombre']) ?>"><?= sanitize($svc['nombre']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Sub Servicios">
                        <?php foreach($subServicios as $ss): ?>
                        <option value="<?= sanitize($ss['nombre']) ?>"><?= sanitize($ss['nombre']) ?> (<?= sanitize($ss['servicio_nombre']) ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Suscripciones">
                        <?php foreach($paquetes as $paq): ?>
                        <option value="<?= sanitize($paq['nombre']) ?>"><?= sanitize($paq['nombre']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase;">Frecuencia</label>
                <select class="form-select" id="filterFrecuencia" style="width: 160px;">
                    <option value="todos">Todas</option>
                    <option value="unico">Pago único</option>
                    <option value="mes">Mensual</option>
                    <option value="trimestre">Trimestral</option>
                    <option value="semestre">Semestral</option>
                    <option value="año">Anual</option>
                </select>
            </div>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase;">Estado</label>
                <select class="form-select" id="filterStatus" style="width: 150px;">
                    <option value="todos">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
                <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase">Renovación desde</label>
                <input type="date" id="filterFechaDesde" class="form-select" style="width:150px;padding:8px 10px;background-image:none" onchange="renderClients(allClients)">
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
                <label style="font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase">Hasta</label>
                <input type="date" id="filterFechaHasta" class="form-select" style="width:150px;padding:8px 10px;background-image:none" onchange="renderClients(allClients)">
            </div>
            <button class="btn btn-ghost btn-sm" onclick="limpiarTodosLosFiltros()" style="align-self:flex-end">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Limpiar filtros
            </button>
        </div>
    </div>
</div>

<!-- Tab: Mis Clientes (Vista Tabla) -->
<div class="tab-content" id="activeClients">
    <div class="card">
        <div class="table-responsive">
            <!-- Barra de acciones masivas -->
            <div id="bulkBar" style="display:none;align-items:center;gap:12px;padding:10px 16px;background:#F4DEDB;border-bottom:1.5px solid #E8BCB8">
                <span id="bulkCount" style="font-size:13px;font-weight:700;color:#dc2626"></span>
                <button onclick="deleteSelected()" class="btn btn-danger btn-sm">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Eliminar seleccionados
                </button>
                <button onclick="clearSelection()" class="btn btn-ghost btn-sm">
                    Cancelar
                </button>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:36px;padding-left:16px">
                            <input type="checkbox" id="checkAll" onchange="toggleAll(this)"
                                style="width:15px;height:15px;cursor:pointer;accent-color:#dc2626">
                        </th>
                        <th onclick="sortClients('nombre')" style="cursor:pointer;user-select:none;white-space:nowrap">Nombre Marca / Empresa <span id="sort_nombre" style="color:#8A867C;font-size:10px"></span></th>
                        <th onclick="sortClients('contacto')" style="cursor:pointer;user-select:none;white-space:nowrap">Contacto <span id="sort_contacto" style="color:#8A867C;font-size:10px"></span></th>
                        <th>Servicios Activos</th>
                        <th onclick="sortClients('renovacion')" style="cursor:pointer;user-select:none;white-space:nowrap">Próx. Renovación <span id="sort_renovacion" style="color:#8A867C;font-size:10px"></span></th>
                        <th onclick="sortClients('ingresos')" style="cursor:pointer;user-select:none;white-space:nowrap">Ingresos <span id="sort_ingresos" style="color:#8A867C;font-size:10px"></span></th>
                        <th>Estado</th>
                        <th>Frecuencia</th>
                        <th style="width:50px;"></th>
                    </tr>
                </thead>
                <tbody id="clientsTableBody">
                    <tr><td colspan="9" style="text-align:center;padding:60px;color:var(--color-text-light)">Cargando clientes...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Pagos únicos -->
<div class="tab-content hidden" id="uniqueClients">
    <!-- Filtros -->
    <div style="padding:14px 20px;border-bottom:1px solid var(--color-border);display:flex;gap:12px;align-items:center;flex-wrap:wrap;background:var(--color-surface)">
        <input type="text" id="unicoBuscar" placeholder="Buscar cliente o concepto..."
               oninput="renderUnicoClients()"
               style="flex:1;min-width:180px;max-width:280px;padding:8px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;font-family:inherit;color:var(--color-text);outline:none;transition:border .15s"
               onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
        <select id="unicoEstado" onchange="renderUnicoClients()"
                style="padding:8px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;font-family:inherit;color:var(--color-text);background:var(--color-surface);cursor:pointer">
            <option value="todos">Todos los estados</option>
            <option value="pendiente">Pendientes</option>
            <option value="vencido">Vencidos</option>
            <option value="pagado">Pagados</option>
        </select>
        <div id="unicosResumen" style="margin-left:auto;font-size:12px;color:var(--color-text-muted);font-weight:600"></div>
    </div>
    <div class="card" style="margin-top:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Concepto / Trabajo</th>
                        <th style="text-align:right">Monto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>WhatsApp</th>
                        <th style="width:50px"></th>
                    </tr>
                </thead>
                <tbody id="unicosTableBody">
                    <tr><td colspan="7" style="text-align:center;padding:60px;color:var(--color-text-light)">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Renovaciones -->
<div class="tab-content hidden" id="renewals">
    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Cliente</th><th>Servicio</th><th>Vencimiento</th><th>Monto</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody id="renewalsTableBody">
                    <tr><td colspan="6" style="text-align:center;padding:60px;color:var(--color-text-light)">Cargando renovaciones...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Pipeline (Conversión) -->
<div class="tab-content hidden" id="pipeline">
    <div class="grid-layout" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px">
        <?php if(empty($leadsToConvert)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--color-text-light)">No hay nuevos leads ganados por convertir.</div>
        <?php else: ?>
            <?php foreach($leadsToConvert as $lead): ?>
            <div class="card animate-fade-up">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                        <div>
                            <h3 style="font-size:16px;font-weight:700"><?= sanitize($lead['nombre']) ?></h3>
                            <span class="badge badge-success">Lead Ganado</span>
                        </div>
                        <button class="btn btn-accent btn-sm" onclick="convertLead(<?= $lead['id'] ?>, <?= htmlspecialchars(json_encode($lead['nombre']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lead['whatsapp']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lead['email']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lead['servicio_interes']), ENT_QUOTES, 'UTF-8') ?>)">Convertir</button>
                    </div>
                    <p style="font-size:13px;color:var(--color-text-muted)">Servicio: <?= sanitize($lead['servicio_interes']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<!-- Modal Cliente -->
<div class="modal-overlay" id="clientModal">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="clientModalTitle">Nuevo Cliente</h3>
                <p id="clientModalSubtitle" style="font-size:12px;color:#8A867C;margin:3px 0 0;display:none"></p>
            </div>
            <button class="modal-close" onclick="closeClientModal()">&times;</button>
        </div>
        <div class="modal-body">

            <!-- ── Banner: contacto vinculado ── -->
            <div id="clientContactoSelCard" style="display:none;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;padding:10px 14px;margin-bottom:14px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                    <div style="display:flex;align-items:center;gap:9px;min-width:0">
                        <div style="width:30px;height:30px;border-radius:8px;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0" id="clientContactoAvatar">R</div>
                        <div style="min-width:0">
                            <div style="font-size:12px;font-weight:800;color:#15803d" id="clientContactoCardNombre">—</div>
                            <div style="font-size:11px;color:#166534" id="clientContactoCardNegocios">—</div>
                        </div>
                    </div>
                    <button onclick="limpiarContactoSeleccionado()" title="Quitar"
                        style="flex-shrink:0;background:none;border:none;color:#8A867C;cursor:pointer;font-size:19px;line-height:1;padding:2px 5px">×</button>
                </div>
                <div style="font-size:11px;color:#166534;margin-top:5px">✓ Nombre del contacto copiado — completa los datos propios del nuevo negocio.</div>
            </div>

            <!-- ── Formulario nuevo cliente ── -->
            <form id="clientForm" onsubmit="saveClient(event)">
                <input type="hidden" id="clientId">
                <input type="hidden" id="clientLeadId">
                <div class="form-group"><label class="form-label">Nombre Comercial / Empresa *</label><input type="text" class="form-input" id="clientNombre" required placeholder="Nombre del nuevo negocio o empresa"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group"><label class="form-label">NIT / Cédula</label><input type="text" class="form-input" id="clientNit"></div>
                    <!-- Persona de Contacto con autocompletado de contactos existentes -->
                    <div class="form-group" style="position:relative">
                        <label class="form-label">Persona de Contacto</label>
                        <input type="text" class="form-input" id="clientContacto"
                               autocomplete="off"
                               oninput="buscarContactoExistente(this.value)"
                               onfocus="if(this.value.length>=2) buscarContactoExistente(this.value)"
                               placeholder="Nombre del contacto…">
                        <div id="clientBuscarResults"
                             style="display:none;position:absolute;top:100%;left:0;right:0;z-index:400;background:#fff;border:1.5px solid #E8E5DD;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.03);overflow:hidden;max-height:220px;overflow-y:auto;margin-top:3px"></div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group"><label class="form-label">WhatsApp</label><input type="text" class="form-input" id="clientTel" placeholder="Número del nuevo negocio"></div>
                    <div class="form-group"><label class="form-label">Email Facturación</label><input type="email" class="form-input" id="clientEmail"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group"><label class="form-label">Responsable</label><input type="text" class="form-input" id="clientResp"></div>
                    <div class="form-group"><label class="form-label">Ubicación (Ciudad)</label><input type="text" class="form-input" id="clientUbi"></div>
                </div>
                <div class="form-group"><label class="form-label">Dirección</label><input type="text" class="form-input" id="clientDir"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeClientModal()">Cancelar</button>
            <button class="btn btn-primary" id="clientModalSaveBtn"
                onclick="document.getElementById('clientForm').requestSubmit()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal Mensajes Cliente ────────────────────────────────────────────────── -->
<div class="modal-overlay" id="modalMsgCliente">
    <div class="modal" style="max-width:730px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column">

        <!-- Header -->
        <div class="modal-header" style="flex-shrink:0">
            <div>
                <h3 class="modal-title" style="display:flex;align-items:center;gap:8px">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Enviar mensaje a <span id="msgClienteNombre" style="color:var(--color-primary)"></span>
                </h3>
                <p style="font-size:12px;color:#8A867C;margin:3px 0 0">Selecciona plantilla y canal de envío</p>
            </div>
            <button class="modal-close" onclick="cerrarModalMsgCliente()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Tabs WA / Email -->
        <div style="display:flex;gap:0;border-bottom:2px solid var(--color-border-light);padding:0 20px;flex-shrink:0">
            <button id="msgTabWA" onclick="msgSwitchTab('wa')"
                style="display:inline-flex;align-items:center;gap:6px;padding:11px 16px;background:none;border:none;border-bottom:3px solid #25D366;margin-bottom:-2px;font-size:13px;font-weight:700;color:var(--color-text);cursor:pointer">
                <svg width="14" height="14" fill="#25D366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                WhatsApp
            </button>
            <button id="msgTabEmail" onclick="msgSwitchTab('email')"
                style="display:inline-flex;align-items:center;gap:6px;padding:11px 16px;background:none;border:none;border-bottom:3px solid transparent;margin-bottom:-2px;font-size:13px;font-weight:700;color:var(--color-text-light);cursor:pointer">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Correo
            </button>
        </div>

        <!-- Cuerpo scrollable -->
        <div style="flex:1;overflow-y:auto;padding:20px">

            <!-- STEP 1: Grid de plantillas -->
            <div id="msgStep1">
                <div style="font-size:11px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Elige una plantilla</div>
                <div id="msgPlantillasGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div style="grid-column:1/-1;text-align:center;padding:30px;color:#8A867C;font-size:13px">Cargando plantillas...</div>
                </div>
            </div>

            <!-- STEP 2 WhatsApp -->
            <div id="msgStep2WA" style="display:none;flex-direction:column;gap:16px">
                <button onclick="msgVolverStep1()" style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;color:#57544D;font-size:12px;font-weight:700;cursor:pointer;padding:0;margin-bottom:4px">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Volver a plantillas
                </button>
                <div style="background:#FAFAF7;border-radius:10px;padding:12px 16px;font-size:12px;color:#57544D">
                    Enviando a: <strong id="msgWADest"></strong>
                </div>
                <!-- Preview burbuja -->
                <div>
                    <div style="font-size:11px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Vista previa</div>
                    <div id="msgWAPreview" style="padding:14px 16px;background:#dcfce7;border-radius:12px 12px 12px 0;font-size:13px;color:#0E0E0C;line-height:1.6;white-space:pre-wrap;word-break:break-word;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #bbf7d0;min-height:60px"></div>
                </div>
                <!-- Imagen si hay -->
                <div id="msgWAImagenBox" style="display:none;background:#FAFAF7;border-radius:10px;padding:12px;display:none;align-items:center;gap:12px;border:1.5px solid #E8E5DD">
                    <img id="msgWAImagenThumb" src="" style="width:56px;height:56px;border-radius:8px;object-fit:cover;flex-shrink:0">
                    <div style="flex:1;min-width:0;font-size:12px;color:#57544D">Imagen adjunta a la plantilla.<br>Se copiará al portapapeles al abrir WhatsApp.</div>
                </div>
            </div>

            <!-- STEP 2 Email -->
            <div id="msgStep2Email" style="display:none;flex-direction:column;gap:14px">
                <button onclick="msgVolverStep1()" style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;color:#57544D;font-size:12px;font-weight:700;cursor:pointer;padding:0;margin-bottom:4px">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Volver a plantillas
                </button>
                <div style="background:#FAFAF7;border-radius:10px;padding:12px 16px;font-size:12px;color:#57544D">
                    Enviando a: <strong id="msgEmailDest"></strong>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Asunto *</label>
                    <input type="text" id="msgEmailAsunto" class="form-input" placeholder="Asunto del correo">
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Mensaje</div>
                    <div id="msgEmailPreview" style="padding:12px 14px;background:#FAFAF7;border-radius:8px;font-size:12px;color:#57544D;line-height:1.6;white-space:pre-wrap;border:1.5px solid #E8E5DD;max-height:160px;overflow-y:auto"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer" style="flex-shrink:0">
            <button class="btn btn-outline" onclick="cerrarModalMsgCliente()">Cancelar</button>
            <!-- Botón WA step 2 -->
            <button id="msgBtnWA" style="display:none;align-items:center;gap:7px;padding:10px 20px;background:#25D366;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer"
                onclick="msgConfirmarWA()">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                Abrir WhatsApp
            </button>
            <!-- Botón Email step 2 -->
            <button id="msgBtnEmail" style="display:none;align-items:center;gap:7px;padding:10px 20px;background:#4f46e5;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer"
                onclick="msgConfirmarEmail()">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Enviar correo
            </button>
        </div>
    </div>
</div>

<!-- ── Modal: Marcas del Contacto ─────────────────────────────────────────── -->
<div class="modal-overlay" id="modalMarcas">
    <div class="modal" style="max-width:480px;width:100%">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="modalMarcasTitle"></h3>
                <p id="modalMarcasSubtitle" style="font-size:12px;color:#8A867C;margin-top:2px"></p>
            </div>
            <button class="modal-close" onclick="cerrarModalMarcas()">&times;</button>
        </div>
        <div class="modal-body" style="padding:16px 20px 20px">
            <div id="modalMarcasGrid" style="display:grid;gap:8px"></div>
        </div>
    </div>
</div>

<script src="js/clientes.js?v=<?= APP_VERSION ?>"></script>
<style>
.tabs-nav { display:flex;gap:4px;background:rgba(0,0,0,0.04);padding:4px;border-radius:6px; }
.tab-btn { padding:7px 14px;border-radius:4px;border:none;background:transparent;font-size:13px;font-weight:600;color:var(--color-text-muted);cursor:pointer;transition:all 0.15s; }
.tab-btn.active { background:#ffffff;color:var(--color-text);font-weight:700;box-shadow:0 1px 4px rgba(14,14,12,.08); }
.hidden { display:none !important; }
.client-card { transition:transform 0.15s; }
.client-card:hover { transform:translateY(-2px); }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
