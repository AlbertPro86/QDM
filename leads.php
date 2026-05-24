<?php
/**
 * CRM QUANTUN Digital - Gestión de Leads
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pdo = db();
$servicios = $pdo->query("SELECT id, nombre FROM servicios WHERE activo = 1 ORDER BY nombre")->fetchAll();

$pageTitle    = 'Gestión de Leads';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Gestión de Leads</span>';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="view-toggle" id="viewToggle">
            <button class="view-toggle-btn active" data-view="table" id="viewTableBtn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Tabla
            </button>
            <button class="view-toggle-btn" data-view="kanban" id="viewKanbanBtn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                Kanban
            </button>
        </div>
    </div>
    <div class="page-header-right">
        <div class="search-bar" style="width: 260px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchInput" placeholder="Buscar lead...">
        </div>
        <select class="form-select" id="filterEstado" style="width: 180px; padding: 10px 40px 10px 14px;">
            <option value="todos">Todos los estados</option>
            <option value="nuevo">Nuevos</option>
            <option value="contactado">Contactados</option>
            <option value="en_negociacion">En Negociación</option>
            <option value="ganado">Ganados</option>
            <option value="perdido">Perdidos</option>
        </select>
        <button class="btn btn-accent" onclick="openLeadModal()" id="btnNewLead">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Nuevo Lead
        </button>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px" id="leadsKpiBar"></div>

<!-- Vista Tabla -->
<div class="card" id="tableView">
    <!-- Barra de acciones masivas -->
    <div id="leadsBulkBar" style="display:none;align-items:center;gap:12px;padding:10px 16px;background:#F4DEDB;border-bottom:1.5px solid #D6D2C7">
        <span id="leadsBulkCount" style="font-size:13px;font-weight:700;color:#B0382F"></span>
        <button onclick="deleteSelectedLeads()"
            style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;font-size:12px;font-weight:700;color:#fff;background:#B0382F;border:none;border-radius:4px;cursor:pointer">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Eliminar seleccionados
        </button>
        <button onclick="clearLeadsSelection()"
            style="padding:6px 12px;font-size:12px;font-weight:600;color:#57544D;background:none;border:1.5px solid #E8E5DD;border-radius:4px;cursor:pointer">
            Cancelar
        </button>
    </div>
    <div style="overflow-x: auto;">
        <table class="data-table" id="leadsTable">
            <thead><tr>
                <th style="width:36px;padding-left:16px">
                    <input type="checkbox" id="leadsCheckAll" onchange="leadsToggleAll(this)"
                        style="width:15px;height:15px;cursor:pointer;accent-color:#B0382F">
                </th>
                <th>Lead</th><th>WhatsApp</th><th>Servicio</th><th>Presupuesto</th><th>Estado</th><th>Fecha</th><th style="width:90px">Acciones</th>
            </tr></thead>
            <tbody id="leadsTableBody">
                <tr><td colspan="8" style="text-align:center;padding:60px;color:var(--color-text-light);">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Vista Kanban -->
<div class="kanban-board hidden" id="kanbanView">
    <?php
    $columns = [
        'nuevo' => ['Nuevos', 'var(--color-info)'],
        'contactado' => ['Contactados', 'var(--color-text-muted)'],
        'en_negociacion' => ['En Negociación', 'var(--color-warning)'],
        'ganado' => ['Ganados', 'var(--color-success)'],
        'perdido' => ['Perdidos', 'var(--color-danger)']
    ];
    foreach ($columns as $key => $col): ?>
    <div class="kanban-column" data-status="<?=$key?>">
        <div class="kanban-column-header">
            <span class="kanban-column-title"><span style="width:10px;height:10px;border-radius:50%;background:<?=$col[1]?>;display:inline-block;"></span> <?=$col[0]?></span>
            <span class="kanban-column-count" id="count-<?=$key?>">0</span>
        </div>
        <div class="kanban-column-body" id="kanban-<?=$key?>" ondragover="handleDragOver(event)" ondrop="handleDrop(event,'<?=$key?>')" ondragleave="handleDragLeave(event)"></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Lead -->
<div class="modal-overlay" id="leadModal">
    <div class="modal" style="max-width:860px">
        <div class="modal-header">
            <div>
                <h3 id="leadModalTitle" class="modal-title">Nuevo Lead</h3>
                <p style="font-size:13px;color:#8A867C;margin:4px 0 0">Completa la información del prospecto</p>
            </div>
            <button class="modal-close" onclick="closeLeadModal()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body">
            <form id="leadForm" onsubmit="saveLead(event)">
                <input type="hidden" id="leadId" value="">
                <!-- Fila 1: Nombre + WhatsApp -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Nombre *</label>
                        <input type="text" class="form-input" id="leadNombre" required placeholder="Nombre del prospecto">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">WhatsApp *</label>
                        <input type="text" class="form-input" id="leadWhatsapp" required placeholder="+57 300 123 4567">
                    </div>
                </div>
                <!-- Fila 2: Email + Servicio -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Email</label>
                        <input type="email" class="form-input" id="leadEmail" placeholder="email@ejemplo.com">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Servicio de Interés *</label>
                        <select class="form-select" id="leadServicio" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach($servicios as $s): ?>
                            <option value="<?=sanitize($s['nombre'])?>"><?=sanitize($s['nombre'])?></option>
                            <?php endforeach; ?>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                <!-- Fila 3: Presupuesto + Fuente -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Presupuesto ($)</label>
                        <input type="number" class="form-input" id="leadPresupuesto" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Fuente</label>
                        <select class="form-select" id="leadFuente">
                            <option value="manual">Manual</option>
                            <option value="wordpress">WordPress</option>
                            <option value="landing">Landing</option>
                            <option value="referido">Referido</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
                <!-- Fila 4: URL + Estado (estado oculto por defecto) -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">URL Actual</label>
                        <input type="url" class="form-input" id="leadUrl" placeholder="https://...">
                    </div>
                    <div id="leadEstadoGroup" style="display:none">
                        <label style="display:block;font-size:12px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Estado</label>
                        <select class="form-select" id="leadEstado">
                            <option value="nuevo">Nuevo</option>
                            <option value="contactado">Contactado</option>
                            <option value="en_negociacion">En Negociación</option>
                            <option value="ganado">Ganado</option>
                            <option value="perdido">Perdido</option>
                        </select>
                    </div>
                </div>
                <!-- Notas: ancho completo -->
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Notas</label>
                    <textarea class="form-textarea" id="leadNotas" rows="3" placeholder="Observaciones adicionales..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeLeadModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="document.getElementById('leadForm').requestSubmit()" id="leadSaveBtn">Guardar Lead</button>
        </div>
    </div>
</div>

<script src="js/leads.js?v=<?= APP_VERSION ?>"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
