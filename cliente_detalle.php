<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: clientes.php'); exit; }

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) { header('Location: clientes.php'); exit; }


$personaDisplay = $cliente['persona_contacto'] ?: $cliente['nombre_comercial'];
$pageTitle = sanitize($personaDisplay);
$pageSubtitle = '';

// Switcher: otras marcas del mismo contacto
$pageTitleSuffix = '';
if (!empty($cliente['persona_contacto'])) {
    $stmtMarcas = $pdo->prepare("SELECT id, nombre_comercial FROM clientes WHERE persona_contacto = ? AND id != ? ORDER BY nombre_comercial");
    $stmtMarcas->execute([$cliente['persona_contacto'], $cliente['id']]);
    $otrasMarcas = $stmtMarcas->fetchAll();
    if ($otrasMarcas) {
        $items = '<div style="padding:6px 0">';
        $items .= '<a href="cliente_detalle.php?id=' . $cliente['id'] . '" style="display:flex;align-items:center;gap:8px;padding:8px 14px;text-decoration:none;background:#E3F1E8">'
            . '<span style="width:7px;height:7px;border-radius:50%;background:#2D8F5A;flex-shrink:0"></span>'
            . '<span style="font-size:13px;font-weight:700;color:#0E0E0C">' . sanitize($cliente['nombre_comercial']) . '</span></a>';
        foreach ($otrasMarcas as $m) {
            $items .= '<a href="cliente_detalle.php?id=' . $m['id'] . '" style="display:flex;align-items:center;gap:8px;padding:8px 14px;text-decoration:none;background:#fff;transition:background .1s" onmouseenter="this.style.background=\'#FAFAF7\'" onmouseleave="this.style.background=\'#fff\'">'
                . '<span style="width:7px;height:7px;border-radius:50%;background:#D6D2C7;flex-shrink:0"></span>'
                . '<span style="font-size:13px;font-weight:600;color:#2A2926">' . sanitize($m['nombre_comercial']) . '</span></a>';
        }
        $items .= '</div>';
        $pageTitleSuffix = '<div style="position:relative;display:inline-block">'
            . '<button onclick="(function(e){e.stopPropagation();var d=document.getElementById(\'nhDrop\');d.style.display=d.style.display===\'none\'?\'block\':\'none\';})(event)"'
            . ' style="display:flex;align-items:center;gap:5px;padding:3px 10px 3px 8px;background:#EFECE5;border:1.5px solid #E8E5DD;border-radius:3px;font-size:12px;font-weight:700;color:#57544D;cursor:pointer;font-family:inherit;transition:all .15s"'
            . ' onmouseenter="this.style.background=\'#E8E5DD\';this.style.borderColor=\'#D6D2C7\';this.style.color=\'#0E0E0C\'"'
            . ' onmouseleave="this.style.background=\'#EFECE5\';this.style.borderColor=\'#E8E5DD\';this.style.color=\'#57544D\'">'
            . '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>'
            . sanitize($cliente['nombre_comercial'])
            . '</button>'
            . '<div id="nhDrop" style="display:none;position:absolute;top:calc(100% + 6px);left:0;z-index:400;background:#fff;border:1.5px solid #E8E5DD;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.03);min-width:210px;overflow:hidden">'
            . $items . '</div>'
            . '</div>'
            . '<script>document.addEventListener("click",function(){var d=document.getElementById("nhDrop");if(d)d.style.display="none";},true);</script>';
    }
}
$pageBreadcrumb = '<a href="clientes.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Clientes</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">' . sanitize($personaDisplay) . '</span>';
include __DIR__ . '/includes/header.php';
?>

<!-- ── Perfil sutil horizontal ───────────────────────────────────────────────── -->
<div class="card animate-fade-up" style="margin-bottom:20px;padding:14px 20px">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <!-- Avatar -->
        <div style="width:46px;height:46px;border-radius:var(--radius-md);background:var(--color-border-light);color:var(--color-text);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px;flex-shrink:0">
            <?= strtoupper(substr($cliente['nombre_comercial'], 0, 1)) ?>
        </div>
        <!-- Nombre del negocio + estado + tabs inline -->
        <div style="flex:0 0 auto;min-width:0">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <div id="heroClienteName" style="font-size:18px;font-weight:800;color:var(--color-text);line-height:1.2"><?= sanitize($cliente['nombre_comercial']) ?></div>
                <span style="width:10px;height:10px;border-radius:50%;background:<?= $cliente['estado']==='activo'?'#22c55e':'#ef4444' ?>;flex-shrink:0;display:inline-block" title="<?= ucfirst($cliente['estado']) ?>"></span>
                <!-- Selector de negocios compacto -->
                <select id="negocioTopbarSelector" onchange="selectNegocioFromDropdown(this.value)" style="display:none;padding:5px 10px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);background:var(--color-surface);color:var(--color-text);font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .2s" onmouseover="this.style.borderColor='var(--color-text-muted)'" onmouseout="this.style.borderColor='var(--color-border)'">
                  <option value="">Negocio ▼</option>
                </select>
                <!-- Indicador Tareas Pendientes -->
                <div id="tareasIndicador" style="display:none;animation:pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite">
                    <button class="btn" style="background:#ef4444;color:#fff;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:700;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;transition:filter .15s" onmouseenter="this.style.filter='brightness(.9)'" onmouseleave="this.style.filter=''">
                        <span id="countTareasIndicador" style="width:20px;height:20px;background:#ffffff;color:#ef4444;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800">0</span>
                        <span>Tarea pendiente</span>
                    </button>
                </div>
                <!-- Separador vertical -->
                <span id="negocioTabsSep" style="display:none;width:1px;height:18px;background:#e2e8f0;flex-shrink:0"></span>
                <!-- Pills de negocios inline (llenados por JS) -->
                <div id="negocioTabsList" style="display:none;align-items:center;gap:5px;flex-wrap:wrap"></div>
                <button id="negocioNuevoBtn" style="display:none"></button>
            </div>
        </div>
        <!-- Separador -->
        <div style="width:1px;height:36px;background:#e2e8f0;flex-shrink:0;display:none" class="sep-lg"></div>
        <!-- Datos inline -->
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;flex:1;min-width:0">
            <?php if($cliente['telefono']): ?>
            <a href="https://wa.me/<?= preg_replace('/\D/','',$cliente['telefono']) ?>" target="_blank" style="display:flex;align-items:center;gap:6px;text-decoration:none">
                <svg width="13" height="13" fill="#25D366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
                <span style="font-size:12px;color:#25D366;font-weight:700"><?= sanitize($cliente['telefono']) ?></span>
            </a>
            <?php endif; ?>
            <?php if($cliente['email_facturacion']): ?>
            <div style="display:flex;align-items:center;gap:6px;min-width:0">
                <svg width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span style="font-size:12px;color:var(--color-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px"><?= sanitize($cliente['email_facturacion']) ?></span>
            </div>
            <?php endif; ?>
            <?php if($cliente['nit_cedula']): ?>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                <svg width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                <span style="font-size:12px;color:#94a3b8"><?= sanitize($cliente['nit_cedula']) ?></span>
            </div>
            <?php endif; ?>
            <?php if($cliente['direccion']): ?>
            <div style="display:flex;align-items:center;gap:6px;min-width:0">
                <svg width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span style="font-size:12px;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px" title="<?= sanitize($cliente['direccion']) ?>"><?= sanitize($cliente['direccion']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <!-- Valor mensual destacado -->
        <span id="totalIngreso" style="font-size:28px;font-weight:800;color:#0E0E0C;font-family:var(--font-secondary);line-height:1;letter-spacing:-.02em;white-space:nowrap;flex-shrink:0">$ 0</span>
        <!-- IDs ocultos que el JS necesita -->
        <span id="totalEgreso"   style="display:none">0</span>
        <span id="totalGanancia" style="display:none">0</span>

        <!-- Separador -->
        <div style="width:1px;height:44px;background:#E8E5DD;flex-shrink:0"></div>

        <!-- Botones acción -->
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
            <!-- Botón RUT -->
            <button id="btnRut" class="btn btn-outline sm" onclick="openRutModal()" style="flex-shrink:0" onmouseenter="if(this.dataset.purl)showMediaPreview(event,this)" onmouseleave="hideMediaPreview()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span id="btnRutLabel">RUT</span>
                <span id="btnRutDot" style="display:none;width:7px;height:7px;background:#22c55e;border-radius:50%;flex-shrink:0"></span>
            </button>
            <button class="btn btn-outline sm" style="flex-shrink:0" onclick="openEditModal()">Editar datos</button>
        </div>
    </div>

</div>

<!-- ── Panel: Negocio Seleccionado (llenado por JS) ──────────────────────────── -->
<div id="negocioPanelWrap" style="display:none;margin-bottom:4px"></div>

<!-- ── Contenido principal ───────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:24px;align-items:start">

    <!-- Columna principal -->
    <div style="display:grid;gap:10px;min-width:0;overflow:hidden">
        <!-- Servicios Activos -->
        <div class="card animate-fade-up stagger-1">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h3 class="card-title">Servicios Activos y Renovaciones</h3>
                <div style="display:flex;gap:8px;align-items:center">
                    <button class="btn btn-outline sm" onclick="generateSelectedOrder()" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;font-size:12px;font-weight:700;border-radius:6px;border:1.5px solid #e2e8f0;color:#0f172a;background:#fff;cursor:pointer;transition:all .15s" onmouseenter="this.style.background='#f1f5f9';this.style.borderColor='#cbd5e1'" onmouseleave="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Facturar Seleccionados
                    </button>
                    <button class="btn btn-outline sm" onclick="openRenovarModal()" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;font-size:12px;font-weight:700;border-radius:6px;border:1.5px solid #e2e8f0;color:#3F5E9E;background:#fff;cursor:pointer;transition:all .15s" onmouseenter="this.style.background='#E1E7F2';this.style.borderColor='#3F5E9E'" onmouseleave="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Renovar
                    </button>
                    <button class="btn btn-secondary sm" onclick="openAddSvcModal()" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;font-size:12px;font-weight:700;border-radius:6px">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Nuevo Servicio
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th style="width:40px"><input type="checkbox" onclick="toggleAllSvcs(this)"></th><th>Servicio</th><th>Monto (Ingreso)</th><th>Descuento</th><th>Neto</th><th>Vencimiento</th><th>Estado</th><th style="width:80px"></th></tr></thead>
                    <tbody id="clientSvcsTable">
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--color-text-light)">Cargando servicios...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Trabajos Adicionales (pagos únicos) ─────────────────────────── -->
        <div class="card animate-fade-up stagger-2" id="trabajosAdicionalesCard" style="overflow:hidden">
            <div onclick="togglePanel('panelTrabajos','arrowTrabajos')"
                 style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;cursor:pointer;background:#FAFAF7;border-bottom:1px solid var(--color-border);user-select:none">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:26px;height:26px;border-radius:4px;background:rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="13" height="13" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M5 11h.01"/></svg>
                    </div>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#0E0E0C;line-height:1.2">Trabajos Adicionales <span id="trabajosBadge" style="font-size:10px;font-weight:600;background:#E8E5DD;color:#57544D;padding:1px 7px;border-radius:100px;display:none;margin-left:4px"></span></div>
                        <div style="font-size:9px;color:#8A867C">Trabajos puntuales · no recurrentes</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <button onclick="event.stopPropagation();abrirModalNuevoTrabajo()"
                        style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#ffffff;color:#0E0E0C;border:1px solid #E8E5DD;border-radius:3px;font-size:11px;font-weight:700;cursor:pointer;transition:background .15s;font-family:inherit"
                        onmouseenter="this.style.background='#F0EFEB'" onmouseleave="this.style.background='#ffffff'">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Nuevo trabajo
                    </button>
                    <svg id="arrowTrabajos" width="15" height="15" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2.5" style="transition:transform .25s;transform:rotate(-90deg)"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <div id="panelTrabajos" style="display:none">
                <div class="table-responsive">
                    <table class="data-table" style="font-size:12px;table-layout:fixed;width:100%">
                        <colgroup>
                            <col style="width:auto">
                            <col style="width:160px">
                            <col style="width:110px">
                            <col style="width:160px">
                            <col style="width:72px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th style="text-align:right">Monto</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="trabajosAdicionalesTable">
                            <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--color-text-light)">Sin trabajos adicionales.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="trabajosTotal" style="display:none;padding:10px 18px;text-align:right;border-top:1px solid var(--color-border)">
                    <span style="font-size:11px;color:var(--color-text-muted)">Total trabajos adicionales:&nbsp;</span>
                    <span id="trabajosTotalMonto" style="font-size:12px;font-weight:700;color:var(--color-text);font-family:var(--font-secondary)"></span>
                </div>
            </div>
        </div>

        <!-- ── Editor de Detalles ──────────────────────────────────────────── -->
        <div class="card animate-fade-up" style="overflow:hidden">
            <div onclick="togglePanel('panelEditor','arrowEditor')"
                 style="display:flex;align-items:center;justify-content:space-between;padding:5px 10px;cursor:pointer;background:#0E0E0C;user-select:none">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:26px;height:26px;border-radius:4px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="13" height="13" fill="none" stroke="#ffffff" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#ffffff;line-height:1.2">Detalles del cliente</div>
                        <div style="font-size:9px;color:rgba(255,255,255,0.55)">Notas internas · editor de texto</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <span id="editorSavedAt" style="font-size:9px;color:rgba(255,255,255,0.5)"></span>
                    <svg id="arrowEditor" width="16" height="16" fill="none" stroke="#ffffff" viewBox="0 0 24 24" stroke-width="2.5" style="transition:transform .25s;transform:rotate(-90deg)"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <div id="panelEditor" style="display:none">
                <!-- Toolbar -->
                <div style="display:flex;align-items:center;gap:2px;padding:5px 10px;border-bottom:1px solid #EFECE5;background:#FAFAF7;flex-wrap:wrap">
                    <?php
                    $editorBtns = [
                        ['cmd'=>'bold',        'icon'=>'<b style="font-size:13px">B</b>',  'title'=>'Negrita (Ctrl+B)'],
                        ['cmd'=>'italic',      'icon'=>'<i style="font-size:13px">I</i>',  'title'=>'Cursiva (Ctrl+I)'],
                        ['cmd'=>'underline',   'icon'=>'<u style="font-size:13px">U</u>',  'title'=>'Subrayado (Ctrl+U)'],
                        ['cmd'=>'sep'],
                        ['cmd'=>'insertUnorderedList','icon'=>'<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>','title'=>'Lista'],
                        ['cmd'=>'insertOrderedList',  'icon'=>'<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/><text x="0" y="7" font-size="6">1.</text></svg>','title'=>'Lista numerada'],
                        ['cmd'=>'sep'],
                        ['cmd'=>'formatBlock_h3','icon'=>'<span style="font-size:11px;font-weight:800">H3</span>','title'=>'Título'],
                        ['cmd'=>'formatBlock_p', 'icon'=>'<span style="font-size:11px">¶</span>',                  'title'=>'Párrafo'],
                        ['cmd'=>'sep'],
                        ['cmd'=>'justifyLeft',   'icon'=>'<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" d="M4 6h16M4 10h10M4 14h16M4 18h10"/></svg>','title'=>'Alinear izquierda'],
                        ['cmd'=>'justifyCenter', 'icon'=>'<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" d="M4 6h16M7 10h10M4 14h16M7 18h10"/></svg>','title'=>'Centrar'],
                        ['cmd'=>'sep'],
                        ['cmd'=>'removeFormat',  'icon'=>'<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>','title'=>'Limpiar formato'],
                    ];
                    foreach($editorBtns as $b):
                        if($b['cmd']==='sep'): ?>
                        <div style="width:1px;height:22px;background:#e2e8f0;margin:0 4px"></div>
                        <?php else: ?>
                        <button type="button" title="<?= $b['title'] ?>"
                            onclick="editorCmd('<?= $b['cmd'] ?>')"
                            style="width:30px;height:30px;border:1px solid transparent;background:transparent;border-radius:3px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#0E0E0C;transition:all .15s"
                            onmouseenter="this.style.background='#EFECE5';this.style.borderColor='#D6D2C7'"
                            onmouseleave="this.style.background='transparent';this.style.borderColor='transparent'">
                            <?= $b['icon'] ?>
                        </button>
                        <?php endif;
                    endforeach; ?>
                    <div style="margin-left:auto;display:flex;align-items:center;gap:6px">
                        <button onclick="saveEditor()" id="btnSaveEditor"
                            style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#0E0E0C;color:#ffffff;border:none;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;transition:filter .15s"
                            onmouseenter="this.style.filter='brightness(1.2)'" onmouseleave="this.style.filter=''">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Guardar
                        </button>
                    </div>
                </div>
                <!-- Área editable -->
                <div id="clienteEditor" contenteditable="true"
                    style="min-height:160px;max-height:360px;overflow-y:auto;padding:10px 14px;font-size:13px;line-height:1.6;color:#0E0E0C;outline:none;font-family:inherit"
                    oninput="markEditorDirty()"
                    onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='s'){event.preventDefault();saveEditor();}">
                </div>
            </div>
        </div>

        <!-- ── Inventario de Accesos / Credenciales ────────────────────────── -->
        <div class="card animate-fade-up" style="overflow:hidden">
            <div onclick="togglePanel('panelCredenciales','arrowCred')"
                 style="display:flex;align-items:center;justify-content:space-between;padding:5px 10px;cursor:pointer;background:#FAFAF7;user-select:none">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:26px;height:26px;border-radius:4px;background:rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="13" height="13" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#0E0E0C;line-height:1.2">Inventario de accesos</div>
                        <div style="font-size:9px;color:#8A867C">Correos, contraseñas y credenciales del cliente</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <button onclick="event.stopPropagation();openCredModal()"
                        style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#ffffff;color:#0E0E0C;border:1px solid #E8E5DD;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;transition:background .15s"
                        onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='#ffffff'">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Agregar
                    </button>
                    <svg id="arrowCred" width="16" height="16" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2.5" style="transition:transform .25s;transform:rotate(-90deg)"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <div id="panelCredenciales" style="display:none">
                <div class="table-responsive">
                    <table class="data-table" style="font-size:12px">
                        <thead>
                            <tr>
                                <th>Nombre / Descripción</th>
                                <th>Correo</th>
                                <th>Clave</th>
                                <th>Fecha creación</th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody id="credencialesTable">
                            <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--color-text-light)">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Sección Tareas del Cliente ─────────────────────────────────── -->
        <div class="card animate-fade-up" style="overflow:hidden" id="tareasClienteCard">
            <div onclick="togglePanel('panelTareasCliente','arrowTareasCliente')"
                 style="display:flex;align-items:center;justify-content:space-between;padding:5px 10px;cursor:pointer;background:#FAFAF7;user-select:none">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:26px;height:26px;border-radius:4px;background:rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="13" height="13" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <div>
                        <div style="display:flex;align-items:center;gap:7px">
                            <div style="font-size:12px;font-weight:700;color:#0E0E0C;line-height:1.2">Tareas del Cliente</div>
                            <span id="tareasClienteBadgePend" style="display:none;background:#fee2e2;color:#dc2626;font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px"></span>
                            <span id="tareasClienteBadgeTotal" style="display:none;background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px"></span>
                        </div>
                        <div style="font-size:9px;color:#8A867C">Seguimiento de actividades y pendientes</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <button onclick="event.stopPropagation();openTareasModal()"
                        style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#ffffff;color:#0E0E0C;border:1px solid #E8E5DD;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;transition:background .15s"
                        onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background='#ffffff'">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Agregar
                    </button>
                    <svg id="arrowTareasCliente" width="16" height="16" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2.5" style="transition:transform .25s"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <div id="panelTareasCliente">
                <!-- Filtros de estado -->
                <div style="display:flex;align-items:center;gap:6px;padding:10px 12px 4px;flex-wrap:wrap">
                    <button class="tarea-filtro-btn active" data-filtro="todos" onclick="filtrarTareasCliente('todos',this)" style="padding:4px 11px;border-radius:20px;border:1px solid #e2e8f0;background:#0f172a;color:#fff;font-size:11px;font-weight:700;cursor:pointer;transition:all .15s">Todas</button>
                    <button class="tarea-filtro-btn" data-filtro="pendiente" onclick="filtrarTareasCliente('pendiente',this)" style="padding:4px 11px;border-radius:20px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s">Pendiente</button>
                    <button class="tarea-filtro-btn" data-filtro="en_progreso" onclick="filtrarTareasCliente('en_progreso',this)" style="padding:4px 11px;border-radius:20px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s">En progreso</button>
                    <button class="tarea-filtro-btn" data-filtro="completado" onclick="filtrarTareasCliente('completado',this)" style="padding:4px 11px;border-radius:20px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s">Completadas</button>
                    <button class="tarea-filtro-btn" data-filtro="cancelado" onclick="filtrarTareasCliente('cancelado',this)" style="padding:4px 11px;border-radius:20px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s">Canceladas</button>
                </div>
                <div id="tareasClienteLista" style="padding:4px 0 8px">
                    <div style="text-align:center;padding:28px;color:var(--color-text-light);font-size:12px">Cargando...</div>
                </div>
            </div>
        </div>

    </div><!-- /columna principal -->

    <!-- Sidebar derecho -->
    <div style="display:flex;flex-direction:column;gap:16px;align-self:start;position:sticky;top:20px;min-width:0;width:100%;overflow:hidden">

        <!-- Disparadores -->
        <div class="card animate-fade-up stagger-2">
            <div class="card-body" style="padding:10px 14px;display:grid;gap:7px">
                <button style="display:inline-flex;align-items:center;gap:10px;padding:11px 18px;background:#25D366;color:#ffffff;border:none;border-radius:24px;font-size:12px;font-weight:700;cursor:pointer;width:100%;justify-content:flex-start;transition:filter .15s;box-shadow:0 2px 4px rgba(37,211,102,.2)" onclick="abrirChatCliente()" onmouseenter="this.style.filter='brightness(.9)'" onmouseleave="this.style.filter=''">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.438 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    Chatear con el cliente
                </button>
                <button class="btn btn-outline sm" style="justify-content:flex-start;gap:8px;width:100%;font-size:12px" onclick="openMensajesModal()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Notificar por Correo
                </button>
            </div>
        </div>

        <!-- Adjuntos -->
        <div class="card animate-fade-up stagger-4">
            <div class="card-header" style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center">
                <h3 class="card-title" style="font-size:13px">Adjuntos <span style="font-size:10px;font-weight:400;color:var(--color-text-muted)">(Facturas, Docs)</span></h3>
                <label class="btn btn-primary btn-sm" style="cursor:pointer;gap:4px">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 4v16m8-8H4"/></svg> Subir
                    <input type="file" id="mediaUpload" style="display:none" onchange="uploadMedia(this)">
                </label>
            </div>
            <div class="card-body" style="padding:6px 0">
                <div id="mediaGrid">
                    <div style="text-align:center;padding:20px;color:var(--color-text-light);font-size:12px">Sin archivos cargados.</div>
                </div>
            </div>
        </div>

        <!-- Notificaciones de renovación -->
        <button type="button" onclick="openNotifModal()"
            style="display:flex;align-items:center;gap:10px;width:100%;padding:11px 16px;background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:var(--radius-md);cursor:pointer;transition:all .15s;text-align:left"
            onmouseenter="this.style.borderColor='#8A867C';this.style.background='#FAFAF7'" onmouseleave="this.style.borderColor='#E8E5DD';this.style.background='#ffffff'">
            <div style="width:32px;height:32px;background:#E3F1E8;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="15" height="15" fill="none" stroke="#2D8F5A" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:12px;font-weight:700;color:#0E0E0C">Notificaciones de renovación</div>
                <div id="notifStatusLabel" style="font-size:10px;color:#8A867C">Cargando configuración...</div>
            </div>
            <svg width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        <!-- Agregar Tarea -->
        <button type="button" class="btn btn-outline" onclick="openTareasModal()" style="width:100%;justify-content:center;gap:8px;font-size:12px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M5 11h.01"/></svg>
            Agregar Tarea
        </button>

        <!-- Pagos únicos pendientes -->
        <div id="pagosUnicosBanner" style="display:none"></div>

        <!-- Ver Historial -->
        <button type="button" class="btn btn-outline" onclick="openHistorialModal()" style="width:100%;justify-content:center;gap:8px;font-size:12px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Ver Historial / Novedades
        </button>

    </div><!-- /sidebar -->

</div><!-- /grid principal -->

<!-- ── Modal RUT ──────────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="rutModal" style="padding:24px">
    <div class="modal" style="max-width:920px;max-height:calc(100vh - 48px)">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;background:#eff6ff;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="16" height="16" fill="none" stroke="#3b82f6" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h3 class="modal-title">RUT del Cliente</h3>
                    <p style="font-size:11px;color:#94a3b8;margin:2px 0 0">Registro Único Tributario</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeRutModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" id="rutModalBody" style="min-height:120px;max-height:calc(100vh - 170px);padding:16px 20px">
            <!-- contenido dinámico -->
        </div>
        <div class="modal-footer" id="rutModalFooter" style="flex-wrap:wrap;gap:8px">
            <!-- botones dinámicos -->
        </div>
    </div>
</div>

<!-- Modal Subir Adjunto -->
<div class="modal-overlay" id="adjuntoUploadModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <h3 class="modal-title">Subir adjunto</h3>
            <button class="modal-close" onclick="document.getElementById('adjuntoUploadModal').classList.remove('show')">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;gap:14px">
            <!-- Nombre del archivo -->
            <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#F0EFEB;border-radius:var(--radius-sm)">
                <svg width="16" height="16" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span id="adjuntoFileName" style="font-size:12px;font-weight:600;color:#0E0E0C;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0"></span>
            </div>
            <!-- Fecha del documento + Leyenda en fila -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Fecha del documento</label>
                    <input type="date" id="adjuntoFecha" class="form-input" style="font-size:13px">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Leyenda <span style="font-weight:400;color:var(--color-text-muted)">(opcional)</span></label>
                    <input type="text" id="adjuntoDesc" class="form-input" placeholder="Ej: Contrato, Comprobante…"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();confirmarSubirAdjunto();}">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('adjuntoUploadModal').classList.remove('show');_pendingMediaFile=null">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmarSubirAdjunto()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Subir
            </button>
        </div>
    </div>
</div>

<!-- Modal Enviar Adjunto por Correo -->
<div class="modal-overlay" id="enviarAdjuntoModal">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <h3 class="modal-title" id="enviarAdjModalTitle">Enviar por correo</h3>
            <button class="modal-close" onclick="document.getElementById('enviarAdjuntoModal').classList.remove('show')">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- ── PANEL 1: Redactar ──────────────────────────────────────── -->
        <div id="adjPanelCompose">
            <div class="modal-body" style="display:grid;gap:14px">
                <!-- Archivo -->
                <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#F0EFEB;border-radius:var(--radius-sm)">
                    <svg width="16" height="16" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span id="enviarAdjNombre" style="font-size:12px;font-weight:600;color:#0E0E0C;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0"></span>
                </div>
                <!-- Para + botón plantillas -->
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted);min-width:0;flex:1;overflow:hidden">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Para: <strong id="enviarAdjEmail" style="color:var(--color-text)"></strong></span>
                    </div>
                    <button onclick="adjShowPlantillas()" style="flex-shrink:0;display:inline-flex;align-items:center;gap:5px;background:#F0EFEB;border:none;border-radius:6px;padding:5px 10px;font-size:11px;font-weight:600;color:#57544D;cursor:pointer;transition:background .12s" onmouseenter="this.style.background='#E4E2DD'" onmouseleave="this.style.background='#F0EFEB'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Plantillas
                    </button>
                </div>
                <!-- Indicador plantilla seleccionada -->
                <div id="adjPlantillaSelTag" style="display:none;align-items:center;gap:7px;padding:6px 10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:11px;color:#15803d">
                    <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <span id="adjPlantillaSelNombre" style="font-weight:600"></span>
                    <button onclick="adjQuitarPlantilla()" style="margin-left:auto;background:none;border:none;color:#15803d;cursor:pointer;padding:0 2px;font-size:13px;line-height:1" title="Quitar plantilla">×</button>
                </div>
                <!-- Asunto -->
                <div class="form-group" style="margin:0">
                    <label class="form-label">Asunto</label>
                    <input type="text" id="enviarAdjAsunto" class="form-input" placeholder="Ej: Documento adjunto" style="font-size:13px">
                </div>
                <!-- Mensaje -->
                <div class="form-group" style="margin:0">
                    <label class="form-label">Mensaje <span style="font-weight:400;color:var(--color-text-muted)">(opcional)</span></label>
                    <textarea id="enviarAdjMensaje" class="form-input" rows="4" placeholder="Escribe un mensaje personalizado para el cliente…" style="font-size:13px;resize:vertical;min-height:90px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="document.getElementById('enviarAdjuntoModal').classList.remove('show')">Cancelar</button>
                <button class="btn btn-primary" id="enviarAdjBtn" onclick="enviarAdjunto()">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Enviar
                </button>
            </div>
        </div>

        <!-- ── PANEL 2: Galería de plantillas ─────────────────────────── -->
        <div id="adjPanelPlantillas" style="display:none">
            <div style="padding:14px 20px 10px;border-bottom:1px solid var(--color-border)">
                <input type="search" id="adjPlantillaBuscar" class="form-input" placeholder="Buscar plantilla…"
                    style="font-size:12px;height:34px"
                    oninput="adjFiltrarPlantillas(this.value)">
            </div>
            <div id="adjPlantillasList" style="overflow-y:auto;max-height:340px;padding:10px 12px;display:grid;gap:6px">
                <div style="text-align:center;padding:24px;color:var(--color-text-light);font-size:12px">Cargando…</div>
            </div>
            <div style="padding:12px 20px;border-top:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;gap:8px">
                <button onclick="adjShowCompose()" class="btn btn-ghost" style="font-size:12px">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Volver
                </button>
                <button onclick="adjUsarSinPlantilla()" style="background:none;border:none;font-size:11px;color:var(--color-text-muted);cursor:pointer;text-decoration:underline">Continuar sin plantilla</button>
            </div>
        </div>
    </div>
</div>

<!-- Menú flotante de adjunto (kebab) -->
<div id="mediaDropdown" style="display:none;position:fixed;z-index:9000;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.15),0 2px 6px rgba(0,0,0,.08);min-width:170px;overflow:hidden;padding:4px 0">
    <button onclick="mediaAccion('correo')"    class="media-menu-item">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Enviar a correo
    </button>
    <button onclick="mediaAccion('descargar')" class="media-menu-item">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Descargar
    </button>
    <button onclick="mediaAccion('ver')"       class="media-menu-item">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        Ver
    </button>
    <div style="height:1px;background:var(--color-border);margin:4px 0"></div>
    <button onclick="mediaAccion('eliminar')"  class="media-menu-item" style="color:#dc2626">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Eliminar
    </button>
</div>

<!-- Preview flotante de adjunto (hover) -->
<div id="mediaPreviewFloat" style="display:none;position:fixed;z-index:8998;width:270px;height:350px;border-radius:10px;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,.28),0 2px 8px rgba(0,0,0,.14),0 0 0 1.5px rgba(0,0,0,.08);border:1.5px solid #b6c2cc;background:#f8fafc;pointer-events:none;transition:opacity .15s">
    <div id="mediaPreviewInner" style="width:100%;height:100%"></div>
</div>

<!-- Modal Nuevo / Editar Trabajo Adicional -->
<div class="modal-overlay" id="trabajoAdModal">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <h3 id="trabajoModalTitle" class="modal-title">Nuevo Trabajo Adicional</h3>
            <button class="modal-close" onclick="document.getElementById('trabajoAdModal').classList.remove('show')">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Concepto <span style="color:var(--color-danger)">*</span></label>
                <input type="text" id="trabajoTxConcepto" class="form-input" placeholder="Ej: Diseño de logo, Mantenimiento…">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Título corto</label>
                <input type="text" id="trabajoTxTitulo" class="form-input" placeholder="Nombre breve para mostrar en tabla">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Descripción</label>
                <textarea id="trabajoTxDesc" class="form-input" rows="2" placeholder="Detalles del trabajo realizado…" style="resize:vertical"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Monto (COP) <span style="color:var(--color-danger)">*</span></label>
                    <input type="number" id="trabajoTxMonto" class="form-input" placeholder="0" min="0" step="1000">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Estado</label>
                    <select id="trabajoTxEstado" class="form-input">
                        <option value="pendiente">Pendiente</option>
                        <option value="pagado">Pagado</option>
                        <option value="vencido">Vencido</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Fecha</label>
                <input type="date" id="trabajoTxFecha" class="form-input">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('trabajoAdModal').classList.remove('show')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarTrabajoAdicional()">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal Detalle Trabajo Adicional -->
<div class="modal-overlay" id="detalleTxModal">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3 class="modal-title">Detalle del Trabajo</h3>
            <button class="modal-close" onclick="document.getElementById('detalleTxModal').classList.remove('show')">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="detalleTxBody" class="modal-body"></div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('detalleTxModal').classList.remove('show')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Renovar Servicios -->
<div class="modal-overlay" id="renovarModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header">
            <h3 class="modal-title" style="display:flex;align-items:center;gap:8px">
                <svg width="18" height="18" fill="none" stroke="#3F5E9E" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Renovar Servicios
            </h3>
            <button class="modal-close" onclick="closeRenovarModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size:13px;color:#64748b;margin-bottom:16px" id="renovarSubtitle">
                Calculando nuevas fechas...
            </p>
            <div style="border:1.5px solid #E8E5DD;border-radius:4px;overflow:hidden">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="background:#FAFAF7;border:1.5px solid #D6D2C7">
                            <th style="padding:9px 14px;width:36px">
                                <input type="checkbox" id="renovarCheckAll" checked
                                    onchange="toggleAllRenov(this)"
                                    style="width:16px;height:16px;cursor:pointer;accent-color:#3F5E9E">
                            </th>
                            <th style="padding:9px 8px;text-align:left;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase">Servicio</th>
                            <th style="padding:9px 8px;text-align:center;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase">Ciclo</th>
                            <th style="padding:9px 8px;text-align:right;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase">Vence hoy</th>
                            <th style="padding:9px 14px;text-align:right;font-size:11px;font-weight:700;color:#2D8F5A;text-transform:uppercase">Nueva fecha</th>
                        </tr>
                    </thead>
                    <tbody id="renovarPreviewBody">
                        <tr><td colspan="4" style="padding:30px;text-align:center;color:#94a3b8">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="renovarWarning" style="display:none;margin-top:12px;padding:10px 14px;background:#F5EBD3;border:1px solid #e0c99a;border-radius:4px;font-size:12px;color:#6E4A12">
                ⚠ Los servicios de <strong>Pago Único</strong> no se renuevan y no aparecen en esta lista.
            </div>
        </div>
        <div class="modal-footer" style="justify-content:space-between">
            <div style="display:flex;gap:8px">
                <button class="btn btn-outline" onclick="closeRenovarModal()">Cancelar</button>
                <button class="btn" id="renovarRevertBtn" onclick="pedirConfirmarReversion()"
                    style="background:#fff;color:#ef4444;border:1.5px solid #ef4444;font-weight:700;display:flex;align-items:center;gap:6px;display:none">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Revertir
                </button>
            </div>
            <button class="btn" id="renovarConfirmBtn" onclick="confirmarRenovacion()"
                style="background:#3F5E9E;color:#fff;font-weight:700;display:flex;align-items:center;gap:8px">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Confirmar Renovación
            </button>
        </div>

        <!-- Mini modal confirmación reversión -->
        <div id="revertConfirmBox" style="display:none;position:absolute;inset:0;background:rgba(15,23,42,.45);border-radius:6px;z-index:10;align-items:center;justify-content:center">
            <div style="background:#fff;border-radius:6px;padding:28px 28px 24px;max-width:340px;width:100%;box-shadow:0 1px 3px rgba(0,0,0,.03);text-align:center">
                <div style="width:48px;height:48px;border-radius:50%;background:#F4DEDB;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                    <svg width="22" height="22" fill="none" stroke="#ef4444" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h4 style="font-size:15px;font-weight:800;color:#0f172a;margin:0 0 8px">¿Revertir renovación?</h4>
                <p style="font-size:13px;color:#64748b;margin:0 0 20px;line-height:1.5">
                    Las fechas de los servicios seleccionados volverán al periodo anterior. Esta acción se puede deshacer volviendo a renovar.
                </p>
                <div style="display:flex;gap:10px;justify-content:center">
                    <button onclick="cerrarConfirmReversion()" class="btn btn-outline" style="flex:1">Cancelar</button>
                    <button onclick="ejecutarReversion()" class="btn" style="flex:1;background:#ef4444;color:#fff;font-weight:700">Sí, revertir</button>
                </div>
            </div>
        </div>
    </div>
</div><!-- /renovarModal -->

<!-- Modal Asignar / Editar Servicio -->
<div class="modal-overlay" id="addSvcModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header">
            <div>
                <h3 id="addSvcModalTitle" class="modal-title">Asignar Servicio</h3>
                <p style="font-size:12px;color:#94a3b8;margin:4px 0 0">Selecciona del catálogo completo de servicios, sub-servicios y suscripciones</p>
            </div>
            <button class="modal-close" onclick="closeAddSvcModal()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <!-- Body -->
        <div class="modal-body">
            <form id="addSvcForm" onsubmit="saveSvc(event)">
                <input type="hidden" id="svcEditId">
                <input type="hidden" id="newSvcIdHidden">
                <input type="hidden" id="newSvcNombreDisplay">

                <!-- Selector del catálogo (modo crear) -->
                <div id="svcSelectorWrap" style="margin-bottom:20px">
                    <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Servicio / Sub-servicio / Suscripción *</label>
                    <select id="newSvcId" class="form-select" required onchange="onCatalogSelect(this)" style="font-size:14px">
                        <option value="">Seleccionar del catálogo...</option>
                    </select>
                </div>

                <!-- Preview suscripción seleccionada -->
                <div id="svcPreviewWrap" style="display:none;margin-bottom:20px">
                    <div style="background:#F8F7F4;border:1.5px solid #E8E5DD;border-radius:8px;overflow:hidden">
                        <div style="display:flex;align-items:center;gap:7px;padding:9px 14px;border-bottom:1px solid #E8E5DD;background:#EFECE5">
                            <svg width="13" height="13" fill="none" stroke="#57544D" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span style="font-size:11px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.05em">Composición de la suscripción</span>
                        </div>
                        <div id="svcPreviewContent" style="padding:12px 14px"></div>
                    </div>
                </div>

                <!-- Modo editar: nombre fijo -->
                <div id="newSvcReadonly" style="display:none;margin-bottom:20px">
                    <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Servicio</label>
                    <div style="padding:11px 16px;background:#f8fafc;border:1.5px solid #cbd5e1;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px">
                        <svg width="14" height="14" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <span id="newSvcReadonlyName"></span>
                    </div>
                </div>

                <!-- Financials: 2 columnas -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Monto Bruto ($) *</label>
                        <input type="number" class="form-input" id="newSvcMonto" oninput="calculateNet()" placeholder="Precio base" min="0" step="0.01" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Descuento ($)</label>
                        <input type="number" class="form-input" id="newSvcDesc" oninput="calculateNet()" value="0" min="0" step="0.01">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#2D8F5A;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Ingreso Neto</label>
                        <input type="text" class="form-input" id="newSvcNeto" readonly style="background:#E3F1E8;font-weight:700;color:#1B5A39;cursor:default">
                    </div>
                </div>

                <!-- Frecuencia + Fechas: 3 columnas -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Frecuencia</label>
                        <select class="form-select" id="newSvcFreq">
                            <option value="mes">Mensual</option>
                            <option value="trimestre">Trimestral</option>
                            <option value="semestre">Semestral</option>
                            <option value="año" selected>Anual</option>
                            <option value="unico">Único</option>
                            <option value="ninguna">Ninguna</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Fecha Inicio *</label>
                        <input type="date" class="form-input" id="newSvcStart" value="<?=date('Y-m-d')?>" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Próx. Vencimiento *</label>
                        <input type="date" class="form-input" id="newSvcEnd" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeAddSvcModal()">Cancelar</button>
            <button class="btn btn-secondary" onclick="document.getElementById('addSvcForm').requestSubmit()">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal Confirmar Eliminación -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:400px;text-align:center">
        <div class="modal-header" style="justify-content:center"><h3 class="modal-title" style="color:var(--color-danger)">¿Confirmar Eliminación?</h3></div>
        <div class="modal-body">
            <p style="margin-bottom:20px;color:var(--color-text-muted)">Esta acción eliminará el servicio de forma permanente del historial de este cliente y no podrá deshacerse.</p>
            <input type="hidden" id="deleteItemId">
        </div>
        <div class="modal-footer" style="justify-content:center;gap:12px">
            <button class="btn btn-outline" onclick="closeDeleteModal()">Cancelar</button>
            <button class="btn btn-danger" onclick="confirmDeleteSvc()">Sí, Eliminar</button>
        </div>
    </div>
</div>

<!-- Modal Editar Cliente -->
<div class="modal-overlay" id="editClientModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header">
            <div>
                <h3 id="editClientModalTitle" class="modal-title">Editar Datos del Cliente</h3>
                <p style="font-size:13px;color:#94a3b8;margin:4px 0 0">Actualiza la información comercial del cliente</p>
            </div>
            <button class="modal-close" onclick="closeEditClientModal()"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body">
            <form id="editClientForm" onsubmit="saveClientData(event)">
                <!-- Fila 1: Nombre + Estado -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Nombre Comercial *</label>
                        <input type="text" class="form-input" id="editNombreComercial" required placeholder="Nombre empresa">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Estado *</label>
                        <select class="form-select" id="editEstado" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <!-- Fila 2: Persona Contacto + NIT -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Persona de Contacto</label>
                        <input type="text" class="form-input" id="editPersonaContacto" placeholder="Nombre contacto">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">NIT / Cédula</label>
                        <input type="text" class="form-input" id="editNitCedula" placeholder="123456789">
                    </div>
                </div>
                <!-- Fila 3: Teléfono + Email -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">WhatsApp</label>
                        <input type="tel" class="form-input" id="editTelefono" placeholder="+57 300 123 4567">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Email Facturación</label>
                        <input type="email" class="form-input" id="editEmailFacturacion" placeholder="email@empresa.com">
                    </div>
                </div>
                <!-- Fila 4: Email encargado + Dirección -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Email Encargado / Persona</label>
                        <input type="email" class="form-input" id="editEmailContacto" placeholder="encargado@empresa.com">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px">Dirección</label>
                        <input type="text" class="form-input" id="editDireccion" placeholder="Calle, número, ciudad">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeEditClientModal()">Cancelar</button>
            <button class="btn btn-secondary" onclick="document.getElementById('editClientForm').requestSubmit()">Guardar Cambios</button>
        </div>
    </div>
</div>

<script>
const clienteId = <?= (int)$id ?>;
const clienteEmails = {
    facturacion: <?= json_encode($cliente['email_facturacion'] ?? '') ?>,
    contacto:    <?= json_encode($cliente['email_contacto']    ?? '') ?>,
};

async function loadPagosUnicosBanner() {
    try {
        const r = await fetch(`api/transacciones.php?frecuencia=unico&cliente_id=${clienteId}&limite=50`);
        const d = await r.json();
        if (!d.success) return;

        const pendientes = (d.data || []).filter(t => t.estado === 'pendiente' || t.estado === 'vencido');
        const banner = document.getElementById('pagosUnicosBanner');
        if (!banner) return;

        if (!pendientes.length) { banner.style.display = 'none'; return; }

        const totalPend = pendientes.reduce((s, t) => s + parseFloat(t.monto || 0), 0);
        const hayVencido = pendientes.some(t => t.estado === 'vencido');
        const bg     = hayVencido ? '#fef2f2' : '#fffbeb';
        const border = hayVencido ? '#fecaca' : '#fde68a';
        const color  = hayVencido ? '#dc2626' : '#d97706';
        const icon   = hayVencido
            ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        const label  = hayVencido ? 'Pago vencido' : 'Pago pendiente';
        const fmt = n => '$ ' + parseFloat(n).toLocaleString('es-CO', {minimumFractionDigits:0, maximumFractionDigits:0});

        banner.style.display = 'block';
        banner.innerHTML = `
            <div style="background:${bg};border:1.5px solid ${border};border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px">
                <svg width="15" height="15" fill="none" stroke="${color}" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0">${icon}</svg>
                <div style="flex:1;min-width:0">
                    <div style="font-size:11px;font-weight:700;color:${color}">${label}${pendientes.length > 1 ? 's (' + pendientes.length + ')' : ''} — trabajo aparte</div>
                    <div style="font-size:12px;font-weight:800;color:${color}">${fmt(totalPend)} COP</div>
                </div>
                <a href="finanzas.php#pagosUnicos" style="font-size:10px;font-weight:700;color:${color};text-decoration:none;white-space:nowrap;opacity:.8" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.8">Ver →</a>
            </div>`;
    } catch(e) { console.error('Error cargando pagos únicos:', e); }
}

async function loadServices() {
    try {
        const r = await fetch(`api/cliente_servicios.php?cliente_id=${clienteId}`);
        const d = await r.json();
        if(d.success) renderServices(d.data);
    } catch(e) { showToast('Error al cargar servicios','error'); }
}

function renderServices(svcs) {
    const tbody = document.getElementById('clientSvcsTable');
    if(!svcs.length) { 
        tbody.innerHTML='<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--color-text-light)">No hay servicios activos asignados.</td></tr>'; 
        return; 
    }
    
    let totalIn = 0, totalOut = 0;

    tbody.innerHTML = svcs.map(s => {
        const vence = new Date(s.fecha_vencimiento + 'T12:00:00');
        const hoy   = new Date();
        const diff  = (vence - hoy) / (1000 * 60 * 60 * 24);

        // ¿Vence este mes?
        const esMes = vence.getFullYear() === hoy.getFullYear() && vence.getMonth() === hoy.getMonth();
        const vencido = diff < 0;

        let badgeClass = 'badge-success';
        if (vencido) badgeClass = 'badge-danger';
        else if (diff < 30) badgeClass = 'badge-warning';

        // Estilo de fila: rojo si vence este mes o ya venció
        const rowStyle = (esMes || vencido)
            ? 'background:linear-gradient(90deg,#fff5f5 0%,#fff 60%)'
            : '';

        const monto    = parseFloat(s.monto_renovacion) || 0;
        const descuento = parseFloat(s.descuento) || 0;
        const subtotal = monto - descuento;

        totalIn  += monto;
        totalOut += descuento;

        const descLabel = descuento > 0
            ? `<div style="font-size:10px;color:var(--color-danger)">- ${formatMoney(descuento)} DESC.</div>`
            : '';

        // Celda de fecha con alerta roja
        const diasAbs = Math.abs(Math.round(diff));
        const fechaLabel = vencido
            ? `<div style="font-size:10px;font-weight:800;color:#ef4444">⚠ Vencido hace ${diasAbs}d</div>`
            : esMes
                ? `<div style="font-size:10px;font-weight:800;color:#ef4444"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#ef4444;margin-right:4px;vertical-align:middle"></span>Vence este mes${diff <= 7 ? ' (en ' + Math.round(diff) + 'd)' : ''}</div>`
                : diff <= 30
                    ? `<div style="font-size:10px;color:#f59e0b;font-weight:700">en ${Math.round(diff)} días</div>`
                    : '';

        // Composición del paquete (si aplica)
        const pkg = getPkgByName(s.servicio_nombre);
        const pkgItemsHtml = pkg && (pkg.items||[]).length
            ? `<div style="margin-top:6px;border-top:1px solid #EAE8E2;padding-top:5px">
                ${(pkg.items||[]).map(it=>`
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;padding:2px 0">
                        <span style="font-size:10px;color:#8A867C"><span style="font-weight:600;color:#57544D">${escapeHtml(it.svc_nombre)}</span> · ${escapeHtml(it.ss_nombre)}</span>
                        <span style="font-size:10px;color:#8A867C;white-space:nowrap;flex-shrink:0">$${Number(it.precio).toLocaleString('es-CO')}/${escapeHtml(it.frecuencia||'mes')}</span>
                    </div>`).join('')}
                ${(pkg.features||[]).length ? `<div style="margin-top:4px;padding-top:4px;border-top:1px dashed #EAE8E2">
                    ${(pkg.features||[]).map(f=>`<span style="font-size:10px;color:#8A867C">✓ ${escapeHtml(f.texto)} &nbsp;</span>`).join('')}
                </div>` : ''}
               </div>`
            : '';

        return `<tr style="${rowStyle}">
            <td><input type="checkbox" class="svc-check" value="${s.id}"></td>
            <td style="font-weight:700">
                ${escapeHtml(s.servicio_nombre)}
                <div style="font-size:10px;color:var(--color-text-muted)">${{mes:'Mensual',trimestre:'Trimestral',semestre:'Semestral',año:'Anual',unico:'Pago Único',ninguna:'Ninguna'}[s.frecuencia] || s.frecuencia || 'Anual'}</div>
                ${pkgItemsHtml}
            </td>
            <td style="font-weight:700;color:var(--color-success)">
                ${formatMoney(subtotal)}
                ${descLabel}
            </td>
            <td style="color:var(--color-danger)">${descuento > 0 ? formatMoney(descuento) : '—'}</td>
            <td style="font-weight:800;color:var(--color-primary)">${formatMoney(subtotal)}</td>
            <td>
                <span style="font-size:13px;${(esMes||vencido)?'color:#ef4444;font-weight:700':''}">${vence.toLocaleDateString('es-CO')}</span>
                ${fechaLabel}
            </td>
            <td><span class="badge ${badgeClass}">${s.estado.toUpperCase()}</span></td>
            <td>
                <div style="display:flex;gap:4px">
                    <button class="btn btn-ghost btn-icon sm" onclick="openOrdenModal(${s.id})" title="Ver Orden de Compra">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </button>
                    ${s.enlace_pago
                        ? `<button class="btn btn-ghost btn-icon sm" onclick="sendPaymentLink('${escapeJs(s.enlace_pago)}','${escapeJs(s.servicio_nombre)}')" title="Enviar Enlace de Pago" style="color:var(--q-lima);background:var(--color-primary)">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                           </button>`
                        : `<button class="btn btn-ghost btn-icon sm" title="Sin enlace de pago configurado" disabled style="opacity:0.3;cursor:default">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                           </button>`
                    }
                    <button class="btn btn-ghost btn-icon sm" onclick="editSvcLink(${JSON.stringify(s).replace(/"/g, '&quot;')})" title="Editar">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="btn btn-ghost btn-icon sm" onclick="openDeleteModal(${s.id}, '${escapeJs(s.servicio_nombre)}')" title="Eliminar" style="color:var(--color-danger)">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    document.getElementById('totalIngreso').textContent = moneyNum(totalIn);
    document.getElementById('totalEgreso').textContent = moneyNum(totalOut);
    document.getElementById('totalGanancia').textContent = moneyNum(totalIn - totalOut); // Neto = bruto - descuentos

    window._ordenSvcMap = {};
    svcs.forEach(s => { window._ordenSvcMap[s.id] = s; });
}

function openDeleteModal(id, name) {
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteModal').classList.add('show');
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('show'); }

async function confirmDeleteSvc() {
    const id = document.getElementById('deleteItemId').value;
    try {
        // Fetch service name for logging
        const rS = await fetch(`api/cliente_servicios.php?id=${id}`); // Reusing API if it supports GET by ID
        // (Wait, GET usually returns all by client_id. I'll just rely on the stored name in UI or a separate fetch)
        // I'll log a generic deletion message with ID if needed.
        
        const response = await fetch(`api/cliente_servicios.php?id=${id}`, { method: 'DELETE' });
        const data = await response.json();
        
        if(data.success) {
            // Log to history
            await fetch('api/cliente_notas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    cliente_id: clienteId, 
                    nota: `[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.`
                })
            });
            showToast(data.message, 'success');
            closeDeleteModal();
            loadServices();
            loadNotes();
        }
    } catch(e) { showToast('Error al eliminar servicio', 'error'); }
}

async function deleteSvc(id) {
    // Legacy function, redirects to new modal logic
    openDeleteModal(id, '');
}

async function sendPaymentLink(enlace, servicioNombre) {
    const tel = "<?= preg_replace('/\D/','',$cliente['telefono']) ?>";
    const msg = `Hola, te compartimos el enlace para realizar el pago del servicio *${servicioNombre}*:\n${enlace}`;
    window.open(`https://wa.me/${tel}?text=${encodeURIComponent(msg)}`, '_blank');

    try {
        await fetch('api/cliente_notas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId, nota: `Enlace de pago enviado por WhatsApp: ${servicioNombre}` })
        });
        loadNotes();
    } catch(e) {}
}

function toggleAllSvcs(master) {
    const checks = document.querySelectorAll('.svc-check');
    checks.forEach(c => c.checked = master.checked);
}

// ── Órdenes de Renovación — helpers ─────────────────────────────────────────
window._ordenModalTipo = 'orden_compra';
window._ordenPlantillasCache = [];

function _toggleOrdenRenovacionFields(show) {
    document.getElementById('ordenPlantillaWrap').style.display  = show ? '' : 'none';
    document.getElementById('ordenLinkPagoWrap').style.display   = show ? '' : 'none';
    const btnCfg  = document.getElementById('btnOrdenCargarConfig');
    const btnSave = document.getElementById('btnGuardarEnConfig');
    if (btnCfg)  btnCfg.style.display  = show ? '' : 'none';
    if (btnSave) btnSave.style.display  = show ? 'inline-flex' : 'none';
    if (!show) {
        // Modo Orden de Compra: toggle bancarios OFF por defecto
        _setBancariosToggle(false);
        const lp = document.getElementById('ordenLinkPago'); if (lp) lp.value = '';
    }
    // En modo renovación: el toggle se activa en _fillOrdenFromConfig según si hay datos
}

async function guardarBancariosEnConfig() {
    const btn = document.getElementById('btnGuardarEnConfig');
    const bancarios = _getOrdenBancarios();
    // Mapeo de campos del modal → claves de configuraciones
    const payload = {
        banco_titular: bancarios.titular,
        banco_cedula:  bancarios.cedula,
        banco_nombre:  bancarios.banco,
        banco_numero:  bancarios.cuenta,
        banco_tipo:    bancarios.tipo === 'Cuenta Corriente' ? 'Corriente' : 'Ahorros',
        banco_llave:   bancarios.llave,
    };
    const hasDatos = Object.values(payload).some(v => v && v.trim());
    if (!hasDatos) { showToast('No hay datos bancarios que guardar', 'warning'); return; }

    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Guardando...';
    try {
        const r = await fetch('api/configuraciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.success) {
            window._ordenCfgCache = { ...(window._ordenCfgCache || {}), ...payload };
            showToast('✓ Datos bancarios guardados en Configuraciones', 'success');
        } else {
            showToast(d.error || 'Error al guardar', 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

async function _loadOrdenPlantillas() {
    const sel = document.getElementById('ordenPlantillaSelect');
    sel.innerHTML = '<option value="">Cargando...</option>';
    try {
        const [rP, rC] = await Promise.all([
            fetch('api/plantillas_factura.php?categoria=orden_renovacion'),
            fetch('api/configuraciones.php')
        ]);
        const [dP, dC] = await Promise.all([rP.json(), rC.json()]);

        // Plantillas
        if (dP.success && dP.data.length) {
            window._ordenPlantillasCache = dP.data;
            sel.innerHTML = dP.data.map(p =>
                `<option value="${p.id}" ${p.es_default == 1 ? 'selected' : ''}>${p.nombre}</option>`
            ).join('');
        } else {
            sel.innerHTML = '<option value="">Sin plantillas — crea una en Plantillas</option>';
        }

        // Auto-fill configuraciones → campos del modal
        if (dC.success && dC.data) {
            _fillOrdenFromConfig(dC.data);
            window._ordenCfgCache = dC.data;
        }

        scheduleOrdenPreview();
    } catch(e) {
        sel.innerHTML = '<option value="">Error al cargar</option>';
    }
}

function _fillOrdenFromConfig(cfg) {
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
    set('bancTitular', cfg.banco_titular);
    set('bancCedula',  cfg.banco_cedula);
    set('bancBanco',   cfg.banco_nombre);
    set('bancCuenta',  cfg.banco_numero);
    set('bancLlave',   cfg.banco_llave);
    const tipSel = document.getElementById('bancTipo');
    if (tipSel && cfg.banco_tipo) {
        tipSel.value = cfg.banco_tipo === 'Corriente' ? 'Cuenta Corriente' : 'Cuenta de Ahorros';
    }
    // Activar toggle si hay al menos un dato (convertimos a String para evitar errores si viene como número)
    const hayDatos = [cfg.banco_titular, cfg.banco_nombre, cfg.banco_numero, cfg.banco_llave]
        .some(v => v !== null && v !== undefined && String(v).trim() !== '');
    _setBancariosToggle(hayDatos);
}

let _bancIncluir = true;

function _setBancariosToggle(on) {
    _bancIncluir = on;
    const track  = document.getElementById('trackBancarios');
    const thumb  = document.getElementById('thumbBancarios');
    const campos = document.getElementById('bancariosCampos');
    const lbl    = document.getElementById('lblBancarios');
    if (!track) return;
    track.style.background  = on ? '#22c55e' : '#cbd5e1';
    thumb.style.transform   = on ? 'translateX(15px)' : 'translateX(0)';
    if (campos) campos.style.display = on ? '' : 'none';
    if (lbl)    lbl.style.color = on ? '#15803d' : '#94a3b8';
    // NO scheduleOrdenPreview() aquí — solo se llama desde toggleBancariosIncluir (interacción usuario)
}

function toggleBancariosIncluir() {
    _setBancariosToggle(!_bancIncluir);
    scheduleOrdenPreview();
}

function onOrdenCargarMisDatos() {
    const cfg = window._ordenCfgCache;
    if (!cfg) { showToast('Configuraciones no cargadas aún', 'warning'); return; }
    _fillOrdenFromConfig(cfg);
    _setBancariosToggle(true);
    scheduleOrdenPreview();
    showToast('Datos cargados desde Configuraciones', 'success');
}

function onOrdenPlantillaChange() {
    scheduleOrdenPreview();
}

function generateSelectedOrder() {
    let ids = Array.from(document.querySelectorAll('.svc-check:checked')).map(c => c.value);
    if (!ids.length) {
        showToast('Selecciona al menos un servicio para facturar.', 'warning');
        return;
    }
    window._ordenModalTipo = 'orden_renovacion';
    document.getElementById('ordenModalTitle').textContent = 'Orden de Renovación';
    _toggleOrdenRenovacionFields(true);
    _loadOrdenPlantillas();
    openOrdenModalMultiple(ids.join(','));
}

function openOrdenModalMultiple(csIds) {
    document.getElementById('currentCsId').value  = '';
    document.getElementById('currentCsIds').value = csIds;
    var map = window._ordenSvcMap || {};
    var svcs = csIds.split(',').map(function(id) { return map[id]; }).filter(Boolean);
    if (svcs.length > 0) {
        _loadOrdenEditor(svcs);
    } else {
        _loadOrdenEditor([{ servicio_nombre: '', monto_renovacion: 0, descuento: 0 }]);
    }
    document.getElementById('ordenMetodoPago').value  = 'Transferencia Bancaria / PSE / QR';
    document.getElementById('ordenNotas').value        = '';
    document.getElementById('ordenFechaUltPago').value = '';
    document.getElementById('ordenModal').classList.add('show');
    try { _loadOrdenDraftIfExists(); } catch(e) { console.warn('Draft load skipped:', e); }
    try { refreshOrdenPreview(); } catch(e) { console.error('Preview error:', e); }
}

function calculateNet() {
    const monto = parseFloat(document.getElementById('newSvcMonto').value) || 0;
    const desc  = parseFloat(document.getElementById('newSvcDesc').value)  || 0;
    const neto  = monto - desc;
    document.getElementById('newSvcNeto').value = neto.toLocaleString('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 });
}

function setSvcModeEdit(show) {
    document.getElementById('svcSelectorWrap').style.display = show ? 'none' : '';
    document.getElementById('newSvcId').required             = !show;
    document.getElementById('newSvcReadonly').style.display  = show ? 'block' : 'none';
}

// Cuando el usuario elige del catálogo, auto-rellena precio y costo
function onCatalogSelect(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) { renderSvcPreview(null); return; }
    const precio = parseFloat(opt.dataset.precio) || 0;
    const freq   = opt.dataset.freq || 'año';
    if (precio) document.getElementById('newSvcMonto').value = precio;
    if (freq)   document.getElementById('newSvcFreq').value  = freq;
    calculateNet();
    // Mostrar preview solo si es suscripción (paquete)
    renderSvcPreview(opt.dataset.pkgId || null);
}

function renderSvcPreview(pkgId) {
    const wrap = document.getElementById('svcPreviewWrap');
    if (!wrap) return;
    if (!pkgId) { wrap.style.display = 'none'; return; }

    const catalog = window._pkgCatalog || [];
    const pkg = catalog.find(p => String(p.id) === String(pkgId));
    if (!pkg) { wrap.style.display = 'none'; return; }

    const fmt = v => Number(v || 0).toLocaleString('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 });
    const esc = s => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    const items    = Array.isArray(pkg.items)    ? pkg.items    : [];
    const features = Array.isArray(pkg.features) ? pkg.features : [];

    // Servicios que componen la suscripción
    const itemsHtml = items.map(item => `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid #EAE8E2">
            <div style="min-width:0">
                <div style="font-size:12px;font-weight:700;color:#0E0E0C">${esc(item.svc_nombre)}</div>
                <div style="font-size:11px;color:#8A867C;margin-top:1px">${esc(item.ss_nombre)}</div>
            </div>
            <div style="text-align:right;flex-shrink:0;margin-left:12px">
                <span style="font-size:12px;font-weight:700;color:#0E0E0C">${fmt(item.precio)}</span>
                <span style="font-size:10px;color:#8A867C">/${esc(item.frecuencia||'mes')}</span>
            </div>
        </div>`).join('');

    // Características del paquete
    const featHtml = features.map(f => `
        <div style="display:flex;align-items:flex-start;gap:6px;padding:3px 0">
            <svg width="12" height="12" fill="none" stroke="#2D8F5A" viewBox="0 0 24 24" stroke-width="2.5" style="flex-shrink:0;margin-top:1px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span style="font-size:11px;color:#57544D;line-height:1.4">${esc(f.texto)}</span>
        </div>`).join('');

    // Siempre mostrar, incluso si está vacío
    document.getElementById('svcPreviewContent').innerHTML = `
        ${pkg.descripcion ? `<p style="font-size:12px;color:#57544D;margin:0 0 10px;font-style:italic">${esc(pkg.descripcion)}</p>` : ''}
        ${items.length
            ? `<div>${itemsHtml}
               <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;margin-top:2px">
                   <span style="font-size:11px;color:#8A867C;font-weight:600">Total</span>
                   <span style="font-size:14px;font-weight:800;color:#0E0E0C">${fmt(pkg.precio_venta)}<span style="font-size:10px;font-weight:500;color:#8A867C"> / ${esc(pkg.frecuencia||'mes')}</span></span>
               </div></div>`
            : `<div style="font-size:12px;color:#8A867C;padding:4px 0">Este paquete no tiene servicios configurados aún.</div>`}
        ${features.length
            ? `<div style="border-top:1px solid #EAE8E2;padding-top:10px;margin-top:8px">
                   <div style="font-size:10px;font-weight:700;color:#8A867C;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Características incluidas</div>
                   ${featHtml}
               </div>`
            : ''}`;

    wrap.style.display = 'block';
}

// Carga el catálogo completo (servicios + sub-servicios + paquetes) en el select
async function loadCatalogo() {
    const sel = document.getElementById('newSvcId');
    sel.innerHTML = '<option value="">Cargando catálogo...</option>';
    try {
        const [rSvc, rPkg] = await Promise.all([
            fetch('api/servicios.php?activo=1').then(r => r.json()),
            fetch('api/paquetes.php').then(r => r.json())
        ]);

        sel.innerHTML = '<option value="">Seleccionar del catálogo...</option>';

        // Servicios y sus sub-servicios
        if (rSvc.success && rSvc.data?.length) {
            rSvc.data.forEach(svc => {
                const subs = svc.sub_servicios || [];
                if (subs.length) {
                    const grp = document.createElement('optgroup');
                    grp.label = svc.nombre;
                    // Servicio padre
                    const optPadre = document.createElement('option');
                    optPadre.value           = svc.id;
                    optPadre.textContent     = svc.nombre + ' (general)';
                    optPadre.dataset.precio  = svc.precio_base || 0;
                    optPadre.dataset.costo   = svc.costo || 0;
                    optPadre.dataset.freq    = svc.frecuencia || 'año';
                    optPadre.dataset.display = svc.nombre;
                    grp.appendChild(optPadre);
                    // Sub-servicios
                    subs.forEach(sub => {
                        const opt = document.createElement('option');
                        opt.value           = svc.id; // FK al servicio padre
                        opt.textContent     = '↳ ' + sub.nombre;
                        opt.dataset.precio  = sub.precio || 0;
                        opt.dataset.costo   = sub.costo  || 0;
                        opt.dataset.freq    = sub.frecuencia || svc.frecuencia || 'año';
                        opt.dataset.display = svc.nombre + ' — ' + sub.nombre;
                        grp.appendChild(opt);
                    });
                    sel.appendChild(grp);
                } else {
                    const opt = document.createElement('option');
                    opt.value           = svc.id;
                    opt.textContent     = svc.nombre;
                    opt.dataset.precio  = svc.precio_base || 0;
                    opt.dataset.costo   = svc.costo || 0;
                    opt.dataset.freq    = svc.frecuencia || 'año';
                    opt.dataset.display = svc.nombre;
                    sel.appendChild(opt);
                }
            });
        }

        // Guardar catálogo completo para preview
        window._pkgCatalog = (rPkg.success && rPkg.data) ? rPkg.data : [];
        window._svcCatalog = (rSvc.success && rSvc.data) ? rSvc.data : [];

        // Paquetes - obtener primer servicio válido como fallback
        if (rPkg.success && rPkg.data?.length) {
            const grpPkg = document.createElement('optgroup');
            grpPkg.label = '— Suscripciones —';

            // Obtener primer servicio válido como fallback para paquetes
            let fallbackSvcId = null;
            if (rSvc.success && rSvc.data?.length) {
                fallbackSvcId = rSvc.data[0].id;
            }

            rPkg.data.forEach(pkg => {
                const opt = document.createElement('option');
                const primerSvcId = pkg.items?.[0]?.servicio_id || fallbackSvcId;
                if (!primerSvcId) return; // sin servicio padre no podemos guardar FK
                opt.value           = primerSvcId;
                opt.textContent     = pkg.nombre;
                opt.dataset.precio  = pkg.precio_venta || 0;
                opt.dataset.costo   = pkg.costo_total  || 0;
                opt.dataset.freq    = pkg.frecuencia   || 'año';
                opt.dataset.display = pkg.nombre;
                opt.dataset.pkgId   = pkg.id;           // ← identifica como suscripción
                grpPkg.appendChild(opt);
            });
            if (grpPkg.children.length) sel.appendChild(grpPkg);
        }
    } catch(e) {
        sel.innerHTML = '<option value="">Error al cargar catálogo</option>';
    }
}

function editSvcLink(s) {
    document.getElementById('addSvcModalTitle').textContent   = 'Editar Servicio';
    document.getElementById('svcEditId').value                = s.id;
    document.getElementById('newSvcIdHidden').value           = s.servicio_id;
    document.getElementById('newSvcNombreDisplay').value      = s.servicio_nombre;
    document.getElementById('newSvcReadonlyName').textContent = s.servicio_nombre;
    document.getElementById('newSvcMonto').value              = s.monto_renovacion;
    document.getElementById('newSvcDesc').value               = s.descuento || 0;
    document.getElementById('newSvcFreq').value               = s.frecuencia || 'año';
    document.getElementById('newSvcStart').value              = s.fecha_inicio;
    document.getElementById('newSvcEnd').value                = s.fecha_vencimiento;
    setSvcModeEdit(true);
    calculateNet();
    document.getElementById('addSvcModal').classList.add('show');
}

function openAddSvcModal() {
    document.getElementById('addSvcForm').reset();
    document.getElementById('svcEditId').value         = '';
    document.getElementById('newSvcIdHidden').value    = '';
    document.getElementById('newSvcNombreDisplay').value = '';
    document.getElementById('newSvcDesc').value        = 0;
    renderSvcPreview(null); // ocultar preview al abrir

    // Pre-llenar fechas
    const hoy = new Date();
    const proxAno = new Date(hoy.getFullYear() + 1, hoy.getMonth(), hoy.getDate());
    document.getElementById('newSvcStart').value       = '<?=date('Y-m-d')?>';
    document.getElementById('newSvcEnd').value         = proxAno.toISOString().split('T')[0];

    document.getElementById('addSvcModalTitle').textContent = 'Asignar Servicio';
    setSvcModeEdit(false);
    calculateNet();
    loadCatalogo();
    document.getElementById('addSvcModal').classList.add('show');
}

function closeAddSvcModal() {
    document.getElementById('addSvcModal').classList.remove('show');
    setSvcModeEdit(false);
    renderSvcPreview(null); // ocultar preview al cerrar
}

let savingSvc = false;
async function saveSvc(e) {
    e.preventDefault();

    // Evitar envíos duplicados
    if (savingSvc) return;
    savingSvc = true;

    const id     = document.getElementById('svcEditId').value;
    const isEdit = !!id;

    let servicioId, nombreDisplay;
    if (isEdit) {
        servicioId   = document.getElementById('newSvcIdHidden').value;
        nombreDisplay = document.getElementById('newSvcNombreDisplay').value;
    } else {
        const sel = document.getElementById('newSvcId');
        const opt = sel.options[sel.selectedIndex];
        servicioId    = sel.value;
        nombreDisplay = opt?.dataset.display || opt?.textContent || '';
    }

    // Validar que servicio_id no esté vacío
    if (!servicioId) {
        showToast('Por favor selecciona un servicio', 'error');
        savingSvc = false;
        return;
    }

    const payload = {
        cliente_id:       clienteId,
        servicio_id:      servicioId,
        nombre_display:   nombreDisplay,
        monto_renovacion: parseFloat(document.getElementById('newSvcMonto').value) || 0,
        descuento:        parseFloat(document.getElementById('newSvcDesc').value)  || 0,
        costo_servicio:   0,
        frecuencia:       document.getElementById('newSvcFreq').value || 'año',
        fecha_inicio:     document.getElementById('newSvcStart').value,
        fecha_vencimiento: document.getElementById('newSvcEnd').value
    };
    if (id) payload.id = id;

    try {
        const r = await fetch('api/cliente_servicios.php', { method: id ? 'PUT' : 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const d = await r.json();
        if (d.success) {
            showToast(d.message, 'success');
            closeAddSvcModal();
            loadServices();
        }
        else showToast(d.error || 'Error al guardar', 'error');
    } catch(e) {
        showToast('Error al guardar: ' + e.message, 'error');
    } finally {
        savingSvc = false;
    }
}

function abrirChatCliente() {
    const tel = "<?= preg_replace('/\D/','',$cliente['telefono']) ?>";
    if (!tel) { showToast('El cliente no tiene número registrado', 'warning'); return; }
    window.open(`https://wa.me/${tel}`, '_blank');
}

async function sendPrompt(type) {
    let msg = "";
    let actionLabel = "";
    if(type === 'reminder') { msg = `Hola, te recordamos que tu servicio con QUANTUN Digital se aproxima a renovarse.`; actionLabel = "Recordatorio de Pago enviado"; }
    else if(type === 'update') { msg = `Te compartimos una novedad sobre tu servicio...`; actionLabel = "Novedad enviada"; }
    else if(type === 'invoice') { msg = `Adjuntamos el comprobante de tu último pago. ¡Gracias por confiar en nosotros!`; actionLabel = "Factura Pagada enviada"; }

    const tel = "<?= preg_replace('/\D/','',$cliente['telefono']) ?>";
    window.open(`https://wa.me/${tel}?text=${encodeURIComponent(msg)}`, '_blank');

    try {
        await fetch('api/cliente_notas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId, nota: actionLabel })
        });
        loadNotes();
    } catch(e) {}
}

function escapeHtml(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function escapeJs(s){if(!s)return'';return s.replace(/'/g, "\\'").replace(/"/g, '&quot;');}

async function loadNotes() {
    try {
        const r = await fetch(`api/cliente_notas.php?cliente_id=${clienteId}`);
        const d = await r.json();
        if(d.success) renderNotes(d.data);
    } catch(e) { console.error('Error al cargar notas'); }
}

function renderNotes(notes) {
    const html = !notes.length
        ? '<p style="text-align:center;color:var(--color-text-light);font-size:13px;padding:20px">Sin novedades registradas.</p>'
        : notes.map(n => `
        <div style="background:var(--color-surface);padding:12px 16px;border-radius:12px">
            <p style="font-size:14px;margin-bottom:6px">${escapeHtml(n.nota)}</p>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--color-text-muted);font-weight:700;text-transform:uppercase">
                <span>${n.usuario_nombre}</span>
                <span>${new Date(n.created_at).toLocaleDateString()}</span>
            </div>
        </div>
    `).join('');

    // Renderizar en el modal si existe
    const modalCont = document.getElementById('notesTimelineModal');
    if(modalCont) modalCont.innerHTML = html;
}

function openHistorialModal() {
    document.getElementById('historialModal').classList.add('show');
    loadNotes();
}

function closeHistorialModal() {
    document.getElementById('historialModal').classList.remove('show');
}

// ── Funciones Modal Tareas ────────────────────────────────────────────────────
function openTareasModal() {
    document.getElementById('tareasModal').classList.add('show');
    document.getElementById('tarea_titulo').focus();
}

function closeTareasModal() {
    document.getElementById('tareasModal').classList.remove('show');
    document.getElementById('tarea_titulo').value = '';
    document.getElementById('tarea_descripcion').value = '';
    document.getElementById('tarea_prioridad').value = 'media';
    document.getElementById('tarea_fecha').value = '';
    document.getElementById('tarea_notas').value = '';
}

function crearTarea() {
    const titulo = document.getElementById('tarea_titulo').value.trim();
    const descripcion = document.getElementById('tarea_descripcion').value.trim();
    const prioridad = document.getElementById('tarea_prioridad').value;
    const fecha = document.getElementById('tarea_fecha').value;
    const notas = document.getElementById('tarea_notas').value.trim();

    if (!titulo) {
        showToast('El título de la tarea es requerido', 'error');
        return;
    }

    // Mostrar loading
    const btn = event.target;
    const btnText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="1"></circle><path d="M12 1v6m0 6v6"/></svg> Creando...';

    fetch('api/tareas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            titulo: titulo,
            descripcion: descripcion || null,
            prioridad: prioridad,
            cliente_id: clienteId,
            fecha_limite: fecha || null,
            notas: notas || null,
            estado: 'pendiente'
        })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) throw new Error(res.error || 'Error al crear la tarea');
        showToast('✓ Tarea creada correctamente', 'success');
        closeTareasModal();
        loadTareasCliente();
        loadTareasIndicador();
    })
    .catch(err => {
        showToast(err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = btnText;
    });
}

let _ordenPreviewTimer = null;
function scheduleOrdenPreview() {
    clearTimeout(_ordenPreviewTimer);
    _ordenPreviewTimer = setTimeout(refreshOrdenPreview, 700);
}

function _ordenItemRow(desc, qty, precio, descuento) {
    const div = document.createElement('div');
    div.className = 'orden-item-row';
    div.style.cssText = 'background:#fff;border:1.5px solid #e2e8f0;border-radius:8px;padding:10px;display:flex;flex-direction:column;gap:6px';
    div.innerHTML = `
        <div style="display:flex;gap:6px;align-items:center">
            <input type="text" placeholder="Descripción del servicio" value="${desc.replace(/"/g,'&quot;')}"
                style="flex:1;padding:6px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none"
                onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'"
                oninput="recalcOrdenTotals();scheduleOrdenPreview()" data-field="desc">
            <button onclick="removeOrdenItem(this)" style="flex-shrink:0;width:26px;height:26px;background:transparent;border:none;cursor:pointer;color:#8A867C;display:flex;align-items:center;justify-content:center;border-radius:3px" onmouseenter="this.style.color='#B0382F'" onmouseleave="this.style.color='#8A867C'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="display:grid;grid-template-columns:50px 1fr 1fr;gap:6px">
            <div>
                <div style="font-size:10px;font-weight:700;color:#8A867C;margin-bottom:3px">QTY</div>
                <input type="number" min="1" value="${qty}" data-field="qty"
                    style="width:100%;box-sizing:border-box;padding:5px 6px;border:1.5px solid #E8E5DD;border-radius:3px;font-size:12px;outline:none"
                    onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'"
                    oninput="recalcOrdenTotals();scheduleOrdenPreview()">
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;color:#8A867C;margin-bottom:3px">PRECIO UNIT.</div>
                <input type="number" min="0" value="${precio}" data-field="precio"
                    style="width:100%;box-sizing:border-box;padding:5px 6px;border:1.5px solid #E8E5DD;border-radius:3px;font-size:12px;outline:none"
                    onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'"
                    oninput="recalcOrdenTotals();scheduleOrdenPreview()">
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;color:#8A867C;margin-bottom:3px">DESCUENTO</div>
                <input type="number" min="0" value="${descuento}" data-field="desc_val"
                    style="width:100%;box-sizing:border-box;padding:5px 6px;border:1.5px solid #E8E5DD;border-radius:3px;font-size:12px;outline:none"
                    onfocus="this.style.borderColor='#0E0E0C'" onblur="this.style.borderColor='#E8E5DD'"
                    oninput="recalcOrdenTotals();scheduleOrdenPreview()">
            </div>
        </div>`;
    return div;
}

function _loadOrdenEditor(svcs) {
    const container = document.getElementById('ordenItemsContainer');
    container.innerHTML = '';
    svcs.forEach(s => {
        container.appendChild(_ordenItemRow(
            s.servicio_nombre || s.descripcion || '',
            s._qty || 1,
            s._precio_unit || s.monto_renovacion || 0,
            s.descuento || 0
        ));
    });
    recalcOrdenTotals();
}

function addOrdenItem() {
    document.getElementById('ordenItemsContainer').appendChild(_ordenItemRow('', 1, 0, 0));
    recalcOrdenTotals();
    scheduleOrdenPreview();
}

function removeOrdenItem(btn) {
    btn.closest('.orden-item-row').remove();
    recalcOrdenTotals();
    scheduleOrdenPreview();
}

function recalcOrdenTotals() {
    let subtotal = 0, total = 0;
    document.querySelectorAll('.orden-item-row').forEach(row => {
        const qty    = parseFloat(row.querySelector('[data-field="qty"]').value) || 1;
        const precio = parseFloat(row.querySelector('[data-field="precio"]').value) || 0;
        const desc   = parseFloat(row.querySelector('[data-field="desc_val"]').value) || 0;
        subtotal += precio * qty;
        total    += (precio * qty) - desc;
    });
    const fmt = n => '$ ' + n.toLocaleString('es-CO');
    document.getElementById('ordenResumenSubtotal').textContent = fmt(subtotal);
    document.getElementById('ordenResumenTotal').textContent    = fmt(total);
}

function _getOrdenItems() {
    return Array.from(document.querySelectorAll('.orden-item-row')).map(row => ({
        descripcion: row.querySelector('[data-field="desc"]').value,
        qty:         parseInt(row.querySelector('[data-field="qty"]').value) || 1,
        precio:      parseFloat(row.querySelector('[data-field="precio"]').value) || 0,
        descuento:   parseFloat(row.querySelector('[data-field="desc_val"]').value) || 0,
    }));
}

function _getOrdenBancarios() {
    if (!_bancIncluir) return {}; // toggle OFF → no incluir
    return {
        titular:  (document.getElementById('bancTitular')?.value  || '').trim(),
        cedula:   (document.getElementById('bancCedula')?.value   || '').trim(),
        banco:    (document.getElementById('bancBanco')?.value    || '').trim(),
        cuenta:   (document.getElementById('bancCuenta')?.value   || '').trim(),
        tipo:     (document.getElementById('bancTipo')?.value     || '').trim(),
        llave:    (document.getElementById('bancLlave')?.value    || '').trim(),
    };
}

function refreshOrdenPreview() {
    var items = _getOrdenItems();
    var banc  = _getOrdenBancarios();
    var csId  = document.getElementById('currentCsId').value;
    var csIds = document.getElementById('currentCsIds').value;

    // Mostrar loader
    var loader = document.getElementById('ordenIframeLoader');
    if (loader) loader.style.display = 'flex';

    // Rellenar el form oculto y hacer POST directo al iframe (enfoque confiable)
    document.getElementById('ordenFormCsId').value        = csId;
    document.getElementById('ordenFormCsIds').value       = csIds;
    document.getElementById('ordenFormItemsJson').value   = JSON.stringify(items);
    document.getElementById('ordenFormMetodoPago').value  = document.getElementById('ordenMetodoPago').value;
    document.getElementById('ordenFormNotas').value       = document.getElementById('ordenNotas').value;
    document.getElementById('ordenFormBancarios').value   = JSON.stringify(banc);
    document.getElementById('ordenFormFechaUltPago').value= document.getElementById('ordenFechaUltPago').value;
    document.getElementById('ordenFormLinkPago').value    = (document.getElementById('ordenLinkPago') || {value:''}).value;
    document.getElementById('ordenFormPlantillaId').value = (document.getElementById('ordenPlantillaSelect') || {value:''}).value;
    document.getElementById('ordenFormDocTipo').value     = window._ordenModalTipo || 'orden_renovacion';

    document.getElementById('ordenPreviewForm').submit();
}

function openOrdenModal(csId) {
    window._ordenModalTipo = 'orden_compra';
    document.getElementById('ordenModalTitle').textContent = 'Orden de Compra';
    _toggleOrdenRenovacionFields(false);
    document.getElementById('currentCsId').value  = csId;
    document.getElementById('currentCsIds').value = '';
    var svc = (window._ordenSvcMap || {})[csId];
    if (svc) {
        _loadOrdenEditor([svc]);
    } else {
        _loadOrdenEditor([{ servicio_nombre: '', monto_renovacion: 0, descuento: 0 }]);
    }
    document.getElementById('ordenMetodoPago').value  = 'Transferencia Bancaria / PSE / QR';
    document.getElementById('ordenNotas').value        = '';
    document.getElementById('ordenFechaUltPago').value = '';
    document.getElementById('ordenModal').classList.add('show');
    try { _loadOrdenDraftIfExists(); } catch(e) { console.warn('Draft load skipped:', e); }
    try { refreshOrdenPreview(); } catch(e) { console.error('Preview error:', e); }
}

function closeOrdenModal() {
    document.getElementById('ordenModal').classList.remove('show');
    document.getElementById('ordenIframe').src = 'about:blank';
    var loader = document.getElementById('ordenIframeLoader');
    if (loader) loader.style.display = 'flex';
}

function downloadOrdenPDF() {
    var iframe = document.getElementById('ordenIframe');
    function handler() {
        iframe.removeEventListener('load', handler);
        setTimeout(function() { if (iframe.contentWindow) iframe.contentWindow.print(); }, 400);
    }
    iframe.addEventListener('load', handler);
    refreshOrdenPreview();
    // Fallback por si onload no se dispara
    setTimeout(function() { iframe.removeEventListener('load', handler); if (iframe.contentWindow) iframe.contentWindow.print(); }, 4000);
}

// ── Selector de destinatario ──────────────────────────────────────────────────
function sendOrdenByEmail() {
    const csId  = document.getElementById('currentCsId').value;
    const csIds = document.getElementById('currentCsIds').value;
    if (!csId && !csIds) { showToast('No hay orden seleccionada', 'error'); return; }

    // Construir opciones de correo disponibles
    const opciones = [];
    if (clienteEmails.facturacion) opciones.push({ label: 'Facturación', email: clienteEmails.facturacion });
    if (clienteEmails.contacto)    opciones.push({ label: 'Encargado / Persona', email: clienteEmails.contacto });

    const opcionesHTML = opciones.map((o, i) => `
        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;margin-bottom:8px;transition:border-color .15s"
               onmouseenter="this.style.borderColor='#0E0E0C'" onmouseleave="this.style.borderColor=document.getElementById('emailOpt${i}').checked?'#0E0E0C':'#E8E5DD'">
            <input type="radio" id="emailOpt${i}" name="emailDestinatario" value="${o.email}" ${i===0?'checked':''} style="accent-color:#0E0E0C">
            <span>
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;display:block">${o.label}</span>
                <span style="font-size:13px;color:#0f172a">${o.email}</span>
            </span>
        </label>`).join('');

    // Crear y mostrar el mini-modal
    const overlay = document.createElement('div');
    overlay.id = 'emailSelectorOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:2000;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:6px;width:420px;max-width:94vw;box-shadow:0 1px 3px rgba(0,0,0,.03)">
            <div style="padding:20px 24px;border-bottom:1.5px solid #E8E5DD;display:flex;align-items:center;justify-content:space-between">
                <div>
                    <h3 style="margin:0;font-size:15px;font-weight:700;color:#0E0E0C">Enviar por Correo</h3>
                    <p style="margin:3px 0 0;font-size:12px;color:#8A867C">Selecciona el destinatario</p>
                </div>
                <button onclick="document.getElementById('emailSelectorOverlay').remove()" style="background:none;border:none;cursor:pointer;color:#8A867C;font-size:20px;line-height:1">×</button>
            </div>
            <div style="padding:20px 24px">
                ${opcionesHTML}
                <label style="display:block;margin-top:4px">
                    <span style="font-size:11px;font-weight:700;color:#57544D;text-transform:uppercase;display:block;margin-bottom:6px">Otro correo</span>
                    <input id="emailPersonalizado" type="email" placeholder="otro@correo.com"
                        style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #E8E5DD;border-radius:4px;font-size:13px;outline:none"
                        onfocus="this.style.borderColor='#0E0E0C';document.querySelectorAll('[name=emailDestinatario]').forEach(r=>r.checked=false)"
                        onblur="this.style.borderColor='#E8E5DD'">
                </label>
            </div>
            <div style="padding:16px 24px;border-top:1.5px solid #E8E5DD;display:flex;gap:10px;justify-content:flex-end">
                <button onclick="document.getElementById('emailSelectorOverlay').remove()"
                    style="padding:9px 18px;background:#FAFAF7;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer">Cancelar</button>
                <button onclick="_confirmarEnvioOrden()"
                    style="padding:9px 20px;background:#0E0E0C;color:#fff;border:none;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Enviar
                </button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
}

async function _confirmarEnvioOrden() {
    const personalizado = document.getElementById('emailPersonalizado')?.value.trim();
    const seleccionado  = document.querySelector('[name=emailDestinatario]:checked')?.value;
    const emailDest     = personalizado || seleccionado;

    if (!emailDest) { showToast('Selecciona o escribe un correo', 'error'); return; }

    document.getElementById('emailSelectorOverlay')?.remove();

    const csId  = document.getElementById('currentCsId').value;
    const csIds = document.getElementById('currentCsIds').value;
    showToast('📧 Enviando correo, por favor espera...', 'info');

    try {
        const payload = { cliente_id: clienteId, email_destino: emailDest };
        if (csId)  payload.cs_id  = csId;
        if (csIds) payload.cs_ids = csIds;
        payload.items_override  = _getOrdenItems();
        payload.metodo_pago     = document.getElementById('ordenMetodoPago').value;
        payload.notas_pie       = document.getElementById('ordenNotas').value || null;
        payload.bancarios       = _getOrdenBancarios();
        payload.fecha_ult_pago  = document.getElementById('ordenFechaUltPago').value || null;
        payload.doc_tipo        = window._ordenModalTipo || 'orden_compra';
        payload.link_pago       = document.getElementById('ordenLinkPago')?.value || null;
        payload.plantilla_id    = document.getElementById('ordenPlantillaSelect')?.value || null;

        const r = await fetch('api/enviar_orden.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.success) showToast('✅ ' + d.message, 'success');
        else showToast('❌ ' + (d.error || 'Error al enviar'), 'error');
    } catch(e) {
        showToast('❌ Error al enviar por correo', 'error');
    }
}

// ── Borrador ──────────────────────────────────────────────────────────────────
function _ordenDraftKey() {
    const csId  = document.getElementById('currentCsId').value;
    const csIds = document.getElementById('currentCsIds').value;
    return 'orden_draft_' + (csId || csIds || 'multi');
}

function saveOrdenDraft() {
    const draft = {
        items:      _getOrdenItems(),
        bancarios:  _getOrdenBancarios(),
        metodo:     document.getElementById('ordenMetodoPago').value,
        notas:      document.getElementById('ordenNotas').value,
        fechaUlt:   document.getElementById('ordenFechaUltPago').value,
        savedAt:    new Date().toLocaleString('es-CO'),
    };
    localStorage.setItem(_ordenDraftKey(), JSON.stringify(draft));
    showToast('Borrador guardado correctamente', 'success');
    _updateDraftBadge(draft.savedAt);
}

function _loadOrdenDraftIfExists() {
    try {
        const raw = localStorage.getItem(_ordenDraftKey());
        if (!raw) return;
        const draft = JSON.parse(raw);
        // Cargar items
        const container = document.getElementById('ordenItemsContainer');
        container.innerHTML = '';
        (draft.items || []).forEach(it => container.appendChild(_ordenItemRow(it.descripcion||'', it.qty||1, it.precio||0, it.descuento||0)));
        recalcOrdenTotals();
        // Otros campos
        if (draft.metodo)   document.getElementById('ordenMetodoPago').value   = draft.metodo;
        if (draft.notas)    document.getElementById('ordenNotas').value         = draft.notas;
        if (draft.fechaUlt) document.getElementById('ordenFechaUltPago').value  = draft.fechaUlt;
        // Bancarios
        const b = draft.bancarios || {};
        ['Titular','Cedula','Banco','Cuenta','Llave'].forEach(f => {
            const el = document.getElementById('banc'+f);
            if (el && b[f.toLowerCase()]) el.value = b[f.toLowerCase()];
        });
        const tipo = document.getElementById('bancTipo');
        if (tipo && b.tipo) tipo.value = b.tipo;
        _updateDraftBadge(draft.savedAt);
    } catch(e) {}
}

function _updateDraftBadge(savedAt) {
    const badge = document.getElementById('draftBadge');
    if (badge) { badge.style.display = 'inline'; badge.title = 'Guardado: ' + savedAt; }
}

function clearOrdenDraft() {
    localStorage.removeItem(_ordenDraftKey());
    const badge = document.getElementById('draftBadge');
    if (badge) badge.style.display = 'none';
    showToast('Borrador eliminado', 'info');
}

async function saveNote(e) {
    e.preventDefault();
    const text = document.getElementById('noteText').value;
    try {
        const r = await fetch('api/cliente_notas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId, nota: text })
        });
        const d = await r.json();
        if(d.success) {
            document.getElementById('noteText').value = '';
            loadNotes();
        }
    } catch(e) { showToast('Error al guardar nota', 'error'); }
}

async function saveNoteFromModal(e) {
    e.preventDefault();
    const text = document.getElementById('noteTextModal').value;
    try {
        const r = await fetch('api/cliente_notas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId, nota: text })
        });
        const d = await r.json();
        if(d.success) {
            document.getElementById('noteTextModal').value = '';
            loadNotes();
            showToast('✅ Nota guardada', 'success');
        }
    } catch(e) { showToast('Error al guardar nota', 'error'); }
}

async function loadMedia() {
    try {
        const r = await fetch(`api/cliente_archivos.php?cliente_id=${clienteId}`);
        const d = await r.json();
        if(d.success) renderMedia(d.data);
    } catch(e) { console.error('Error al cargar multimedia'); }
}

function renderMedia(media) {
    const cont = document.getElementById('mediaGrid');
    if (!media.length) {
        cont.innerHTML = '<div style="text-align:center;padding:20px;color:var(--color-text-light);font-size:12px">Sin archivos cargados.</div>';
        return;
    }

    const extIcon = (mime, ext) => {
        if (mime.startsWith('image/')) return { bg:'#E3EEFF', color:'#2D5FBE', label: ext };
        if (mime === 'application/pdf') return { bg:'#FDECEA', color:'#B0382F', label:'PDF' };
        if (mime.includes('word') || ext === 'doc' || ext === 'docx') return { bg:'#E3EEFF', color:'#1A56A4', label: ext.toUpperCase() };
        if (mime.includes('excel') || mime.includes('spreadsheet') || ext === 'xlsx' || ext === 'xls') return { bg:'#E3F1E8', color:'#1B5A39', label: ext.toUpperCase() };
        return { bg:'#F0EFEB', color:'#57544D', label: ext.toUpperCase() };
    };

    cont.innerHTML = media.map(m => {
        const ext  = m.nombre_archivo.split('.').pop().toLowerCase();
        const isImg = m.tipo_archivo && m.tipo_archivo.startsWith('image/');
        const ic   = extIcon(m.tipo_archivo || '', ext);

        const purl  = escapeHtml(m.archivo_url);
        const pmime = escapeHtml(m.tipo_archivo || '');
        const avatar = isImg
            ? `<a href="${m.archivo_url}" target="_blank" data-purl="${purl}" data-pmime="${pmime}" style="display:block;width:36px;height:36px;border-radius:var(--radius-sm);overflow:hidden;flex-shrink:0;border:1px solid var(--color-border)" onmouseenter="showMediaPreview(event,this)" onmouseleave="hideMediaPreview()">
                   <img src="${m.archivo_url}" style="width:100%;height:100%;object-fit:cover">
               </a>`
            : `<a href="${m.archivo_url}" target="_blank" data-purl="${purl}" data-pmime="${pmime}" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:var(--radius-sm);background:${ic.bg};flex-shrink:0;text-decoration:none" onmouseenter="showMediaPreview(event,this)" onmouseleave="hideMediaPreview()">
                   <span style="font-size:9px;font-weight:800;color:${ic.color};font-family:var(--font-secondary)">${ic.label}</span>
               </a>`;

        // Fecha: preferir fecha_documento, si no created_at
        const fechaRaw = m.fecha_documento || m.created_at || '';
        const fechaStr = fechaRaw
            ? new Date(fechaRaw.includes('T') ? fechaRaw : fechaRaw + 'T12:00:00')
                .toLocaleDateString('es-CO', {day:'2-digit', month:'short', year:'numeric'})
            : '';

        const infoLine = [
            fechaStr ? `<span>${fechaStr}</span>` : '',
            m.descripcion ? `<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(m.descripcion)}</span>` : ''
        ].filter(Boolean).join('<span style="margin:0 3px;opacity:.4">·</span>');

        const descLine = infoLine
            ? `<div style="display:flex;align-items:center;gap:0;font-size:10px;color:var(--color-text-muted);margin-top:2px;overflow:hidden;max-width:100%">${infoLine}</div>`
            : '';

        return `<div style="display:flex;align-items:center;gap:10px;padding:8px 14px;border-bottom:1px solid var(--color-border);min-width:0;max-width:100%;overflow:hidden;cursor:default" onmouseenter="this.style.background='#FAFAF7'" onmouseleave="this.style.background=''">
            ${avatar}
            <div style="flex:1;min-width:0">
                <a href="${m.archivo_url}" target="_blank" style="display:block;font-size:12px;font-weight:600;color:var(--color-text);text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${m.nombre_archivo}">${m.nombre_archivo}</a>
                ${descLine}
            </div>
            <button onclick="openMediaMenu(event,this)" data-mid="${m.id}" data-mnombre="${escapeHtml(m.nombre_archivo)}" data-murl="${escapeHtml(m.archivo_url)}" title="Opciones" style="flex-shrink:0;background:transparent;border:none;width:28px;height:28px;display:flex;align-items:center;justify-content:center;color:var(--color-text-light);cursor:pointer;border-radius:5px;transition:all .12s" onmouseenter="this.style.color='var(--color-text)';this.style.background='var(--color-surface)'" onmouseleave="this.style.color='var(--color-text-light)';this.style.background='transparent'">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
            </button>
        </div>`;
    }).join('');
    // Remove last border
    const items = cont.querySelectorAll('[style*="border-bottom"]');
    if (items.length) items[items.length - 1].style.borderBottom = 'none';
}

let _pendingMediaFile = null;

function uploadMedia(input) {
    if (!input.files.length) return;
    _pendingMediaFile = input.files[0];
    input.value = '';

    document.getElementById('adjuntoFileName').textContent = _pendingMediaFile.name;
    document.getElementById('adjuntoDesc').value = '';
    // Fecha por defecto: hoy
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth()+1).padStart(2,'0');
    const dd = String(hoy.getDate()).padStart(2,'0');
    document.getElementById('adjuntoFecha').value = `${yyyy}-${mm}-${dd}`;
    document.getElementById('adjuntoUploadModal').classList.add('show');
    setTimeout(() => document.getElementById('adjuntoFecha').focus(), 120);
}

async function confirmarSubirAdjunto() {
    if (!_pendingMediaFile) return;
    const desc  = document.getElementById('adjuntoDesc').value.trim();
    const fecha = document.getElementById('adjuntoFecha').value;
    const formData = new FormData();
    formData.append('archivo', _pendingMediaFile);
    formData.append('cliente_id', clienteId);
    if (desc)  formData.append('descripcion', desc);
    if (fecha) formData.append('fecha_documento', fecha);

    document.getElementById('adjuntoUploadModal').classList.remove('show');
    showToast('Subiendo archivo...', 'info');
    try {
        const r = await fetch('api/cliente_archivos.php', { method: 'POST', body: formData });
        const d = await r.json();
        if (d.success) { showToast(d.message, 'success'); loadMedia(); }
        else showToast(d.error || 'Error al subir', 'error');
    } catch(e) { showToast('Error al subir', 'error'); }
    _pendingMediaFile = null;
}

/* ── ENVIAR ADJUNTO POR CORREO ─────────────────────────────────────────── */
/* ── PREVIEW FLOTANTE EN HOVER ─────────────────────────────────────────── */
let _previewTimer  = null;
let _previewActive = false;
let _pdfJsReady    = false;
const _PDFJS_CDN   = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
const _PDFJS_WORKER= 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

function _ensurePdfJs() {
    if (_pdfJsReady) return Promise.resolve();
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = _PDFJS_CDN;
        s.onload = () => {
            pdfjsLib.GlobalWorkerOptions.workerSrc = _PDFJS_WORKER;
            _pdfJsReady = true;
            resolve();
        };
        s.onerror = reject;
        document.head.appendChild(s);
    });
}

function _positionFloat(float, anchorEl) {
    const FW = 270, FH = 350;
    const rect = anchorEl.getBoundingClientRect();
    let left = rect.left - FW - 12;
    let top  = Math.round(rect.top + rect.height / 2 - FH / 2);
    if (left < 8)                          left = rect.right + 12;
    if (top < 8)                           top  = 8;
    if (top + FH > window.innerHeight - 8) top  = window.innerHeight - FH - 8;
    float.style.left = left + 'px';
    float.style.top  = top  + 'px';
}

function _showFloat(float) {
    float.style.opacity = '0';
    float.style.display = 'block';
    requestAnimationFrame(() => { float.style.opacity = '1'; });
}

function showMediaPreview(e, row) {
    const url  = row.dataset.purl;
    const mime = row.dataset.pmime || '';
    if (!url) return;

    clearTimeout(_previewTimer);
    _previewTimer = setTimeout(async () => {
        if (!_previewActive) return; // el cursor salió durante el delay
        const float = document.getElementById('mediaPreviewFloat');
        const inner = document.getElementById('mediaPreviewInner');

        _positionFloat(float, row);

        if (mime.startsWith('image/')) {
            inner.innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:contain;background:#f1f5f9;display:block">`;
            _showFloat(float);

        } else if (mime === 'application/pdf') {
            // Spinner mientras carga
            inner.innerHTML = `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8fafc">
                <svg width="28" height="28" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" stroke-width="2" style="animation:spin .8s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            </div>`;
            _showFloat(float);

            try {
                await _ensurePdfJs();
                if (!_previewActive) return; // salió mientras cargaba pdf.js

                const pdf     = await pdfjsLib.getDocument(url).promise;
                if (!_previewActive) return;

                const page    = await pdf.getPage(1);
                if (!_previewActive) return;

                const vp0     = page.getViewport({ scale: 1 });
                const scale   = Math.min(270 / vp0.width, 350 / vp0.height);
                const vp      = page.getViewport({ scale });

                const canvas  = document.createElement('canvas');
                canvas.width  = vp.width;
                canvas.height = vp.height;
                canvas.style.cssText = 'width:100%;height:100%;object-fit:contain;display:block;background:#fff';

                await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
                if (!_previewActive) return;

                inner.innerHTML = '';
                inner.appendChild(canvas);
            } catch(err) {
                if (!_previewActive) return;
                const ext = url.split('.').pop().toUpperCase().substring(0,4);
                inner.innerHTML = _noPreviewHtml(ext);
            }

        } else {
            const ext = (url.split('.').pop() || '').toUpperCase().substring(0,5);
            inner.innerHTML = _noPreviewHtml(ext);
            _showFloat(float);
        }
    }, 380);
    _previewActive = true;
}

function _noPreviewHtml(ext) {
    return `<div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;background:#f8fafc">
        <svg width="44" height="44" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" stroke-width="1.5"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        <span style="font-size:16px;font-weight:800;color:#94a3b8;font-family:monospace">${ext}</span>
        <span style="font-size:11px;color:#cbd5e1">Sin vista previa</span>
    </div>`;
}

function hideMediaPreview() {
    clearTimeout(_previewTimer);
    _previewActive = false;
    const float = document.getElementById('mediaPreviewFloat');
    float.style.display = 'none';
    float.style.opacity = '0';
    setTimeout(() => {
        if (!_previewActive) document.getElementById('mediaPreviewInner').innerHTML = '';
    }, 50);
}

/* ── KEBAB MENÚ ADJUNTOS ────────────────────────────────────────────────── */
let _mediaMenu = { id: null, nombre: null, url: null };

function openMediaMenu(e, btn) {
    e.stopPropagation();
    hideMediaPreview();
    _mediaMenu = {
        id:     btn.dataset.mid,
        nombre: btn.dataset.mnombre,
        url:    btn.dataset.murl,
    };
    const dd = document.getElementById('mediaDropdown');
    dd.style.display = 'block';
    // Posicionar cerca del botón
    const rect = e.currentTarget.getBoundingClientRect();
    const ddW = 174, ddH = 160;
    let top  = rect.bottom + 4;
    let left = rect.right  - ddW;
    if (top + ddH > window.innerHeight)  top  = rect.top - ddH - 4;
    if (left < 8)                         left = 8;
    dd.style.top  = top  + 'px';
    dd.style.left = left + 'px';
}

function closeMediaMenu() {
    document.getElementById('mediaDropdown').style.display = 'none';
}

document.addEventListener('click', function(e) {
    const dd = document.getElementById('mediaDropdown');
    if (dd && !dd.contains(e.target)) closeMediaMenu();
});

function mediaAccion(accion) {
    closeMediaMenu();
    const { id, nombre, url } = _mediaMenu;
    if (accion === 'correo')    { openEnviarAdjuntoModal(id, nombre); }
    else if (accion === 'ver')  { window.open(url, '_blank'); }
    else if (accion === 'descargar') {
        const a = document.createElement('a');
        a.href = url; a.download = nombre; a.target = '_blank';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    }
    else if (accion === 'eliminar') { deleteMedia(id); }
}

/* ── ENVIAR ADJUNTO POR CORREO ─────────────────────────────────────────── */
let _enviarAdjArchivoId = null;
let _adjPlantillas      = [];   // caché de plantillas cargadas
let _adjPlantillaActiva = null; // plantilla seleccionada

function openEnviarAdjuntoModal(archivoId, nombreArchivo) {
    _enviarAdjArchivoId  = archivoId;
    _adjPlantillaActiva  = null;

    document.getElementById('enviarAdjNombre').textContent = nombreArchivo;
    document.getElementById('enviarAdjEmail').textContent  = clienteEmails.facturacion || '(sin correo registrado)';
    document.getElementById('enviarAdjAsunto').value       = nombreArchivo;
    document.getElementById('enviarAdjMensaje').value      = '';
    document.getElementById('adjPlantillaSelTag').style.display = 'none';

    const btn = document.getElementById('enviarAdjBtn');
    btn.disabled = false;
    btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Enviar`;

    adjShowCompose();
    document.getElementById('enviarAdjuntoModal').classList.add('show');
    setTimeout(() => document.getElementById('enviarAdjAsunto').focus(), 120);
}

function adjShowCompose() {
    document.getElementById('adjPanelCompose').style.display = '';
    document.getElementById('adjPanelPlantillas').style.display = 'none';
    document.getElementById('enviarAdjModalTitle').textContent = 'Enviar por correo';
}

async function adjShowPlantillas() {
    document.getElementById('adjPanelCompose').style.display = 'none';
    document.getElementById('adjPanelPlantillas').style.display = '';
    document.getElementById('enviarAdjModalTitle').textContent = 'Elegir plantilla';
    document.getElementById('adjPlantillaBuscar').value = '';
    await adjCargarPlantillas();
}

async function adjCargarPlantillas() {
    if (_adjPlantillas.length) { adjRenderPlantillas(_adjPlantillas); return; }
    document.getElementById('adjPlantillasList').innerHTML =
        '<div style="text-align:center;padding:24px;color:var(--color-text-light);font-size:12px">Cargando…</div>';
    try {
        const r = await fetch('api/mensajes_plantillas.php?filtro=todas');
        const d = await r.json();
        _adjPlantillas = (d.data || []).filter(p => p.activa != 0);
        adjRenderPlantillas(_adjPlantillas);
    } catch(e) {
        document.getElementById('adjPlantillasList').innerHTML =
            '<div style="text-align:center;padding:24px;color:var(--color-danger);font-size:12px">Error al cargar</div>';
    }
}

function adjRenderPlantillas(lista) {
    const cont = document.getElementById('adjPlantillasList');
    if (!lista.length) {
        cont.innerHTML = '<div style="text-align:center;padding:24px;color:var(--color-text-light);font-size:12px">Sin plantillas disponibles</div>';
        return;
    }
    cont.innerHTML = lista.map(p => {
        const badge = p.es_predefinida == 1
            ? `<span style="font-size:10px;background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:10px;font-weight:600;flex-shrink:0">Base</span>`
            : '';
        const preview = (p.contenido || '').substring(0, 80) + (p.contenido?.length > 80 ? '…' : '');
        return `<div onclick="adjUsarPlantilla(${p.id})"
            style="padding:10px 12px;border:1.5px solid var(--color-border);border-radius:8px;cursor:pointer;transition:all .12s"
            onmouseenter="this.style.borderColor='#94a3b8';this.style.background='#f8fafc'"
            onmouseleave="this.style.borderColor='var(--color-border)';this.style.background=''">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px">
                <span style="font-size:12px;font-weight:700;color:var(--color-text);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(p.nombre)}</span>
                ${badge}
            </div>
            <div style="font-size:11px;color:var(--color-text-muted);line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">${escapeHtml(preview)}</div>
        </div>`;
    }).join('');
}

function adjFiltrarPlantillas(q) {
    const lower = q.toLowerCase().trim();
    const filtradas = lower ? _adjPlantillas.filter(p =>
        (p.nombre || '').toLowerCase().includes(lower) ||
        (p.contenido || '').toLowerCase().includes(lower) ||
        (p.categoria || '').toLowerCase().includes(lower)
    ) : _adjPlantillas;
    adjRenderPlantillas(filtradas);
}

function adjUsarPlantilla(id) {
    const p = _adjPlantillas.find(x => x.id == id);
    if (!p) return;
    _adjPlantillaActiva = p;
    const nombreCliente = <?= json_encode(sanitize($cliente['nombre_comercial'] ?? '')) ?>;
    const texto = (p.contenido || '')
        .replace(/\{\{cliente_nombre\}\}/g, nombreCliente)
        .replace(/\{\{empresa\}\}/g, nombreCliente);

    document.getElementById('enviarAdjAsunto').value    = p.nombre + ' — QUANTUN Digital';
    document.getElementById('enviarAdjMensaje').value   = texto;
    document.getElementById('adjPlantillaSelNombre').textContent = p.nombre;
    document.getElementById('adjPlantillaSelTag').style.display  = 'flex';
    adjShowCompose();
    setTimeout(() => document.getElementById('enviarAdjMensaje').focus(), 80);
}

function adjUsarSinPlantilla() {
    _adjPlantillaActiva = null;
    document.getElementById('adjPlantillaSelTag').style.display = 'none';
    adjShowCompose();
}

function adjQuitarPlantilla() {
    _adjPlantillaActiva = null;
    document.getElementById('adjPlantillaSelTag').style.display = 'none';
    document.getElementById('enviarAdjMensaje').value = '';
}

async function enviarAdjunto() {
    if (!_enviarAdjArchivoId) return;
    const asunto  = document.getElementById('enviarAdjAsunto').value.trim();
    const mensaje = document.getElementById('enviarAdjMensaje').value.trim();
    if (!asunto) { showToast('El asunto es requerido.', 'warning'); document.getElementById('enviarAdjAsunto').focus(); return; }

    const btn = document.getElementById('enviarAdjBtn');
    btn.disabled = true;
    btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="animation:spin .7s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Enviando…`;

    try {
        const r = await fetch('api/enviar_adjunto.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ archivo_id: _enviarAdjArchivoId, asunto, mensaje_texto: mensaje })
        });
        const d = await r.json();
        if (d.success) {
            document.getElementById('enviarAdjuntoModal').classList.remove('show');
            showToast(d.message, 'success');
        } else {
            showToast(d.error || 'Error al enviar', 'error');
            btn.disabled = false;
            btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Enviar`;
        }
    } catch(e) {
        showToast('Error de conexión al enviar', 'error');
        btn.disabled = false;
        btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Enviar`;
    }
}

/* ── TRABAJOS ADICIONALES (pagos únicos del cliente) ───────────────────── */
async function loadTrabajosAdicionales() {
    try {
        const r = await fetch(`api/transacciones.php?frecuencia=unico&cliente_id=${clienteId}&limite=100`);
        const d = await r.json();
        if (!d.success) return;
        renderTrabajosAdicionales(d.data || []);
    } catch(e) { console.error('Error cargando trabajos adicionales:', e); }
}

function renderTrabajosAdicionales(txs) {
    const card       = document.getElementById('trabajosAdicionalesCard');
    const tbody      = document.getElementById('trabajosAdicionalesTable');
    const badge      = document.getElementById('trabajosBadge');
    const totalWrap  = document.getElementById('trabajosTotal');
    const totalMonto = document.getElementById('trabajosTotalMonto');

    card.style.display = '';

    if (!txs.length) {
        badge.style.display = 'none';
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:28px;color:var(--color-text-light);font-size:12px">Sin trabajos adicionales registrados.</td></tr>`;
        totalWrap.style.display = 'none';
        return;
    }

    badge.textContent   = txs.length + ' trabajo' + (txs.length !== 1 ? 's' : '');
    badge.style.display = 'inline';

    const estadoMap = {
        pagado:   { label:'Pagado',    cls:'badge-success'   },
        pendiente:{ label:'Pendiente', cls:'badge-warning'   },
        vencido:  { label:'Vencido',   cls:'badge-danger'    },
        cancelado:{ label:'Cancelado', cls:'badge-secondary' },
    };

    let totalGeneral = 0;

    tbody.innerHTML = txs.map(tx => {
        const monto = parseFloat(tx.monto) || 0;
        totalGeneral += monto;

        const fechaRef = tx.fecha_pago || tx.fecha_vencimiento || (tx.created_at ? tx.created_at.split(' ')[0] : null);
        const fecha = fechaRef
            ? new Date(fechaRef + 'T12:00:00').toLocaleDateString('es-CO', {day:'2-digit', month:'short', year:'numeric'})
            : '—';

        const est     = estadoMap[tx.estado] || { label: tx.estado, cls:'badge-secondary' };
        const concepto = escapeHtml(tx.titulo || tx.concepto || '—');
        const desc     = tx.descripcion
            ? `<div style="font-size:10px;color:var(--color-text-muted)">${escapeHtml(tx.descripcion)}</div>`
            : '';

        return `<tr>
            <td style="padding-top:12px;padding-bottom:12px">
                <div style="font-weight:600">${concepto}</div>
                ${desc}
            </td>
            <td style="color:var(--color-text-muted);white-space:nowrap;padding-top:12px;padding-bottom:12px">${fecha}</td>
            <td style="padding-top:12px;padding-bottom:12px"><span class="badge ${est.cls}">${est.label}</span></td>
            <td style="text-align:right;font-weight:700;font-family:var(--font-secondary);padding-top:12px;padding-bottom:12px;white-space:nowrap">${formatMoney(monto)}</td>
            <td style="padding-top:8px;padding-bottom:8px">
                <div style="display:flex;gap:3px;justify-content:flex-end">
                    <button class="btn btn-ghost btn-icon sm" onclick="abrirDetalleTrabajoAdicional(${tx.id})" title="Ver detalle">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </button>
                    <button class="btn btn-ghost btn-icon sm" onclick="abrirEditarTrabajoAdicional(${JSON.stringify(tx).replace(/"/g,'&quot;')})" title="Editar">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    totalMonto.textContent = formatMoney(totalGeneral);
    totalWrap.style.display = 'block';
}

/* ── MODAL NUEVO / EDITAR TRABAJO ADICIONAL ──────────────────────────────── */
let _trabajoEditId = null;

function abrirModalNuevoTrabajo() {
    _trabajoEditId = null;
    document.getElementById('trabajoModalTitle').textContent = 'Nuevo Trabajo Adicional';
    document.getElementById('trabajoTxConcepto').value  = '';
    document.getElementById('trabajoTxTitulo').value    = '';
    document.getElementById('trabajoTxDesc').value      = '';
    document.getElementById('trabajoTxMonto').value     = '';
    document.getElementById('trabajoTxFecha').value     = new Date().toISOString().split('T')[0];
    document.getElementById('trabajoTxEstado').value    = 'pendiente';
    document.getElementById('trabajoAdModal').classList.add('show');
}

function abrirEditarTrabajoAdicional(tx) {
    _trabajoEditId = tx.id;
    document.getElementById('trabajoModalTitle').textContent = 'Editar Trabajo Adicional';
    document.getElementById('trabajoTxConcepto').value  = tx.concepto || '';
    document.getElementById('trabajoTxTitulo').value    = tx.titulo   || '';
    document.getElementById('trabajoTxDesc').value      = tx.descripcion || '';
    document.getElementById('trabajoTxMonto').value     = tx.monto    || '';
    document.getElementById('trabajoTxFecha').value     = tx.fecha_pago || tx.fecha_vencimiento || '';
    document.getElementById('trabajoTxEstado').value    = tx.estado   || 'pendiente';
    document.getElementById('trabajoAdModal').classList.add('show');
}

async function abrirDetalleTrabajoAdicional(id) {
    try {
        const r = await fetch(`api/transacciones.php?id=${id}`);
        const d = await r.json();
        if (!d.success || !d.data) { showToast('No se pudo cargar el detalle', 'error'); return; }
        const tx = d.data;
        const fechaRef = tx.fecha_pago || tx.fecha_vencimiento || (tx.created_at ? tx.created_at.split(' ')[0] : null);
        const fecha = fechaRef ? new Date(fechaRef+'T12:00:00').toLocaleDateString('es-CO',{day:'2-digit',month:'long',year:'numeric'}) : '—';
        const estadoMap = { pagado:'Pagado', pendiente:'Pendiente', vencido:'Vencido', cancelado:'Cancelado' };
        document.getElementById('detalleTxBody').innerHTML = `
            <div style="display:grid;gap:12px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div style="background:#FAFAF7;border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:12px">
                        <div style="font-size:10px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Concepto</div>
                        <div style="font-size:13px;font-weight:600">${escapeHtml(tx.titulo || tx.concepto || '—')}</div>
                    </div>
                    <div style="background:#FAFAF7;border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:12px">
                        <div style="font-size:10px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Monto</div>
                        <div style="font-size:18px;font-weight:700;font-family:var(--font-secondary)">${formatMoney(tx.monto)}</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div style="background:#FAFAF7;border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:12px">
                        <div style="font-size:10px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Fecha</div>
                        <div style="font-size:13px">${fecha}</div>
                    </div>
                    <div style="background:#FAFAF7;border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:12px">
                        <div style="font-size:10px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Estado</div>
                        <div style="font-size:13px;font-weight:700">${estadoMap[tx.estado] || tx.estado}</div>
                    </div>
                </div>
                ${tx.descripcion ? `<div style="background:#FAFAF7;border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:12px">
                    <div style="font-size:10px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Descripción</div>
                    <div style="font-size:13px;line-height:1.6;color:var(--color-text)">${escapeHtml(tx.descripcion)}</div>
                </div>` : ''}
                ${tx.items && tx.items.length ? `<div>
                    <div style="font-size:10px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Ítems</div>
                    ${tx.items.map(i=>`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--color-border);font-size:12px">
                        <span>${escapeHtml(i.nombre)} × ${i.cantidad}</span>
                        <span style="font-weight:700">${formatMoney(i.subtotal)}</span>
                    </div>`).join('')}
                </div>` : ''}
            </div>`;
        document.getElementById('detalleTxModal').classList.add('show');
    } catch(e) { showToast('Error al cargar detalle', 'error'); }
}

async function guardarTrabajoAdicional() {
    const concepto = document.getElementById('trabajoTxConcepto').value.trim();
    const monto    = parseFloat(document.getElementById('trabajoTxMonto').value);
    if (!concepto || isNaN(monto) || monto <= 0) {
        showToast('Concepto y monto son requeridos', 'warning'); return;
    }
    const payload = {
        tipo:              'ingreso',
        concepto,
        titulo:            document.getElementById('trabajoTxTitulo').value.trim() || concepto,
        descripcion:       document.getElementById('trabajoTxDesc').value.trim() || null,
        monto,
        fecha_vencimiento: document.getElementById('trabajoTxFecha').value || null,
        estado:            document.getElementById('trabajoTxEstado').value,
        frecuencia:        'unico',
        cliente_id:        clienteId,
    };
    if (_trabajoEditId) payload.id = _trabajoEditId;
    const method = _trabajoEditId ? 'PUT' : 'POST';
    try {
        const r = await fetch('api/transacciones.php', { method, headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const d = await r.json();
        if (d.error) throw new Error(d.error);
        showToast(_trabajoEditId ? 'Trabajo actualizado ✓' : 'Trabajo registrado ✓', 'success');
        document.getElementById('trabajoAdModal').classList.remove('show');
        loadTrabajosAdicionales();
        loadPagosUnicosBanner();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

async function deleteMedia(id) {
    const ok = await confirmAction('El archivo será eliminado permanentemente.', { title: '¿Eliminar archivo?' });
    if (!ok) return;
    try {
        const r = await fetch(`api/cliente_archivos.php?id=${id}`, { method: 'DELETE' });
        const d = await r.json();
        if(d.success) { showToast(d.message,'success'); loadMedia(); }
    } catch(e) { showToast('Error al eliminar', 'error'); }
}

/* ── NEGOCIOS / TRABAJOS ─────────────────────────────────────────────────── */

const TX_ESTADOS = {
    pagado:   { bg:'#f0fdf4', color:'#16a34a', label:'✓ Pagado' },
    pendiente:{ bg:'#fffbeb', color:'#d97706', label:'◷ Pendiente' },
    vencido:  { bg:'#fef2f2', color:'#dc2626', label:'⚠ Vencido' },
};

/* ═══════════════════════════════════════════════════════════════
   NEGOCIOS DEL CLIENTE — selector desplegable en encabezado
═══════════════════════════════════════════════════════════════ */
let _negociosData          = [];
let _negocioSelected        = null;   // id seleccionado (null = general)
const _clientNombreOriginal = <?= json_encode($personaDisplay) ?>;
const _negocioNombreOriginal = <?= json_encode($cliente['nombre_comercial']) ?>;

const NEGOCIO_EC = {
    activo:      { dot:'#22c55e', bg:'#dcfce7', color:'#15803d', label:'Activo' },
    en_progreso: { dot:'#3b82f6', bg:'#dbeafe', color:'#1d4ed8', label:'En progreso' },
    pausado:     { dot:'#f59e0b', bg:'#fef3c7', color:'#b45309', label:'Pausado' },
    completado:  { dot:'#8b5cf6', bg:'#ede9fe', color:'#6d28d9', label:'Completado' },
    cancelado:   { dot:'#ef4444', bg:'#fee2e2', color:'#b91c1c', label:'Cancelado' },
};

async function loadNegocios() {
    try {
        const r = await fetch(`api/cliente_negocios.php?cliente_id=${clienteId}`);
        const d = await r.json();
        _negociosData = d.success ? d.data : [];
        renderNegocioSelector();
        renderNegocioPanel();
    } catch(e) {}
}

function renderNegocioSelector() {
    const list   = document.getElementById('negocioTabsList');
    const sep    = document.getElementById('negocioTabsSep');
    const addBtn = document.getElementById('negocioNuevoBtn');
    const dropdown = document.getElementById('negocioSelectorBreadcrumb');
    if (!list) return;

    if (!_negociosData.length) {
        list.style.display = 'none';
        if (sep)    sep.style.display    = 'none';
        if (addBtn) addBtn.style.display = 'none';
        const topbarSel = document.getElementById('negocioTopbarSelector');
        if (topbarSel) topbarSel.style.display = 'none';
        const hs = document.getElementById('negocioHeroSwitcher');
        if (hs) hs.style.display = 'none';
        return;
    }

    // Pills y select del hero card: siempre ocultos (el switcher está solo en el topbar)
    if (addBtn) addBtn.style.display = 'inline-flex';

    // Switcher en topbar (junto al nombre del cliente)
    const heroSwitcher = document.getElementById('negocioHeroSwitcher');
    const heroDropdown = document.getElementById('negocioHeroDropdown');
    const heroBtnLabel = document.getElementById('negocioHeroBtnLabel');
    if (heroSwitcher) heroSwitcher.style.display = 'block';
    if (heroDropdown) {
        heroDropdown.innerHTML = _negociosData.map(n => {
            const isSel = _negocioSelected === n.id;
            return `<button onclick="selectNegocio(${n.id});closeNegocioHeroDropdown()"
                style="display:block;width:100%;text-align:left;padding:9px 14px;border:none;background:${isSel ? '#f0fdf4' : '#fff'};color:#0f172a;font-size:13px;font-weight:${isSel ? '800' : '600'};cursor:pointer;font-family:inherit;transition:background .1s"
                onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='${isSel ? '#f0fdf4' : '#fff'}'">
                <span style="color:${isSel ? '#22c55e' : 'transparent'};margin-right:6px">●</span>${escapeHtml(n.nombre)}
            </button>`;
        }).join('');
    }
    if (heroBtnLabel) {
        const sel = _negocioSelected ? _negociosData.find(x => x.id === _negocioSelected) : null;
        heroBtnLabel.textContent = sel ? sel.nombre : 'Negocios';
    }

    const genActive = !_negocioSelected;
    list.innerHTML = `
      <button onclick="selectNegocio(null)"
        style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;
               ${genActive
                 ? 'background:#0E0E0C;color:#ffffff;border:1.5px solid #0E0E0C'
                 : 'background:#f1f5f9;color:#64748b;border:1.5px solid #e2e8f0'}"
        ${genActive ? '' : 'onmouseenter="this.style.background=\'#e9edf2\'" onmouseleave="this.style.background=\'#f1f5f9\'"'}>
        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
        General
      </button>
      ${_negociosData.map(n => {
        const ec    = NEGOCIO_EC[n.estado] || NEGOCIO_EC.activo;
        const isSel = _negocioSelected === n.id;
        return `
        <button onclick="selectNegocio(${n.id})"
          style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;
                 ${isSel
                   ? `background:${ec.bg};color:${ec.color};border:1.5px solid ${ec.dot}`
                   : 'background:#f1f5f9;color:#64748b;border:1.5px solid #e2e8f0'}"
          ${isSel ? '' : 'onmouseenter="this.style.background=\'#e9edf2\'" onmouseleave="this.style.background=\'#f1f5f9\'"'}>
          <span style="width:6px;height:6px;border-radius:50%;background:${ec.dot};flex-shrink:0;display:inline-block"></span>
          ${escapeHtml(n.nombre)}
        </button>`;
      }).join('')}`;
}


function toggleNegocioHeroDropdown(e) {
    e.stopPropagation();
    const d = document.getElementById('negocioHeroDropdown');
    if (d) d.style.display = d.style.display === 'none' ? 'block' : 'none';
}
function closeNegocioHeroDropdown() {
    const d = document.getElementById('negocioHeroDropdown');
    if (d) d.style.display = 'none';
}
document.addEventListener('click', closeNegocioHeroDropdown);

function selectNegocioFromDropdown(id) {
    if (id === '') return; // "— Cambiar negocio —"
    selectNegocio(parseInt(id));
}

function selectNegocio(id) {
    _negocioSelected = id;
    renderNegocioSelector();
    renderNegocioPanel();

    // ── Actualizar miga de pan según negocio seleccionado ──────────────────────────
    const n       = id ? _negociosData.find(x => x.id === id) : null;
    const crumbEl = document.getElementById('breadcrumbLastItem');
    const chevron = `<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>`;

    if (n) {
        // Negocio seleccionado: mostrar cliente › negocio
        if (crumbEl) crumbEl.innerHTML =
            `<a href="cliente_detalle.php?id=${clienteId}" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s"
                onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">${escapeHtml(_clientNombreOriginal)}</a>` +
            chevron +
            `<span style="font-weight:700;color:var(--color-text)">${escapeHtml(n.nombre)}</span>`;
    } else {
        // General: mostrar nombre del negocio principal (nombre_comercial)
        if (crumbEl) crumbEl.textContent = _negocioNombreOriginal;
    }
}

async function renderNegocioPanel() {
    const wrap = document.getElementById('negocioPanelWrap');
    if (!wrap) return;

    if (!_negocioSelected) {
        wrap.style.display = 'none';
        wrap.innerHTML = '';
        return;
    }

    const n = _negociosData.find(x => x.id === _negocioSelected);
    if (!n) { wrap.style.display = 'none'; return; }

    const ec       = NEGOCIO_EC[n.estado] || NEGOCIO_EC.activo;
    const fmtMoney = v => v ? '$ ' + parseFloat(v).toLocaleString('es-CO', {minimumFractionDigits:0}) : null;
    const fmtDate  = s => s ? new Date(s+'T00:00:00').toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'}) : null;
    const safeName = escapeHtml(n.nombre).replace(/'/g,"\\'");

    wrap.style.display = 'block';
    wrap.innerHTML = `
      <div class="card animate-fade-up" style="overflow:hidden;margin-bottom:20px">
        <!-- Header del negocio -->
        <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:#f8fafc;border-bottom:1px solid #e3e8ef;flex-wrap:wrap">
          <div style="width:10px;height:10px;border-radius:50%;background:${ec.dot};flex-shrink:0;box-shadow:0 0 0 3px rgba(0,0,0,.06)"></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:15px;font-weight:800;color:#0f172a;line-height:1.2">${escapeHtml(n.nombre)}</div>
            ${n.tipo ? `<div style="font-size:11px;color:#94a3b8;margin-top:1px">${escapeHtml(n.tipo)}</div>` : ''}
          </div>
          <span style="font-size:10px;font-weight:700;background:${ec.bg};color:${ec.color};padding:3px 10px;border-radius:20px;flex-shrink:0">${ec.label}</span>
          ${n.monto ? `<span style="font-size:14px;font-weight:900;color:#0f172a;flex-shrink:0">${fmtMoney(n.monto)}</span>` : ''}
          <div style="display:flex;gap:6px;flex-shrink:0">
            <button onclick="abrirModalNegocio(${n.id})" title="Editar negocio"
              style="width:30px;height:30px;border:1.5px solid #e2e8f0;border-radius:7px;background:#ffffff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all .12s"
              onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='#ffffff'">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button onclick="eliminarNegocio(${n.id})" title="Eliminar negocio"
              style="width:30px;height:30px;border:1.5px solid rgba(239,68,68,.35);border-radius:7px;background:rgba(239,68,68,.1);cursor:pointer;display:flex;align-items:center;justify-content:center;color:#f87171;transition:all .12s"
              onmouseenter="this.style.background='rgba(239,68,68,.25)'" onmouseleave="this.style.background='rgba(239,68,68,.1)'">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </div>
        <!-- Meta del negocio -->
        ${(n.descripcion || n.fecha_inicio || n.fecha_entrega || n.notas) ? `
        <div style="display:flex;flex-wrap:wrap;gap:20px;padding:14px 18px;border-bottom:1px solid #f1f5f9;background:#fafbff">
          ${n.descripcion ? `<div style="flex:2;min-width:180px"><div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px">Descripción</div><div style="font-size:13px;color:#475569;line-height:1.5">${escapeHtml(n.descripcion)}</div></div>` : ''}
          <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start">
            ${n.fecha_inicio  ? `<div><div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:2px">Inicio</div><div style="font-size:12px;font-weight:700;color:#0f172a">${fmtDate(n.fecha_inicio)}</div></div>` : ''}
            ${n.fecha_entrega ? `<div><div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:2px">Entrega</div><div style="font-size:12px;font-weight:700;color:#0f172a">${fmtDate(n.fecha_entrega)}</div></div>` : ''}
          </div>
          ${n.notas ? `<div style="flex:1;min-width:160px;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;font-size:12px;color:#78350f;line-height:1.5"><strong>Nota:</strong> ${escapeHtml(n.notas)}</div>` : ''}
        </div>` : ''}
        <!-- Trabajos / Pagos -->
        <div style="padding:14px 18px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Trabajos / Pagos</div>
            <button onclick="abrirModalTrabajo(${n.id}, '${safeName}')"
              style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#0E0E0C;color:#ffffff;border:none;border-radius:var(--radius-sm);font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:filter .12s"
              onmouseenter="this.style.filter='brightness(1.2)'" onmouseleave="this.style.filter=''">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
              Agregar trabajo
            </button>
          </div>
          <div id="negocioPanelTxs" style="background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden">
            <div style="text-align:center;padding:24px;color:#94a3b8;font-size:12px">Cargando…</div>
          </div>
        </div>
      </div>`;

    loadNegocioTxs(_negocioSelected);
}

async function loadNegocioTxs(negocioId) {
    const wrap = document.getElementById('negocioPanelTxs');
    if (!wrap) return;
    try {
        const r = await fetch(`api/transacciones.php?negocio_id=${negocioId}&limite=50`);
        const d = await r.json();
        const txs = d.success ? d.data : [];
        if (!txs.length) {
            wrap.innerHTML = '<div style="text-align:center;padding:28px;color:#94a3b8;font-size:12px">Sin trabajos registrados. Usa "Agregar trabajo" para el primer pago.</div>';
            return;
        }
        const totalMonto  = txs.reduce((s,t) => s + parseFloat(t.monto||0), 0);
        const pagadoMonto = txs.filter(t=>t.estado==='pagado').reduce((s,t)=>s+parseFloat(t.monto||0),0);
        const pendMonto   = totalMonto - pagadoMonto;
        wrap.innerHTML = `
          <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="background:#f8fafc;border-bottom:1.5px solid #e2e8f0">
              <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase">Concepto</th>
              <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase">Monto</th>
              <th style="padding:8px 12px;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase">Estado</th>
              <th style="padding:8px 12px;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase">Fecha</th>
            </tr></thead>
            <tbody>
              ${txs.map(t => {
                const ec = TX_ESTADOS[t.estado] || {bg:'#f1f5f9',color:'#64748b',label:t.estado};
                const fecha = t.fecha_vencimiento
                    ? new Date(t.fecha_vencimiento+'T00:00:00').toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'})
                    : '—';
                return `<tr style="border-bottom:1px solid #f1f5f9">
                  <td style="padding:10px 12px;color:#0f172a;font-weight:600">${escapeHtml(t.concepto||'—')}</td>
                  <td style="padding:10px 12px;text-align:right;font-weight:800;color:#16a34a">$ ${parseFloat(t.monto||0).toLocaleString('es-CO',{minimumFractionDigits:0})}</td>
                  <td style="padding:10px 12px"><span style="background:${ec.bg};color:${ec.color};font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;white-space:nowrap">${ec.label}</span></td>
                  <td style="padding:10px 12px;color:#64748b">${fecha}</td>
                </tr>`;
              }).join('')}
            </tbody>
            <tfoot><tr style="background:#f8fafc;border-top:1.5px solid #e2e8f0">
              <td style="padding:8px 12px;font-size:11px;font-weight:700;color:#64748b">${txs.length} trabajo${txs.length!==1?'s':''}</td>
              <td style="padding:8px 12px;text-align:right;font-size:13px;font-weight:900;color:#16a34a">$ ${totalMonto.toLocaleString('es-CO',{minimumFractionDigits:0})}</td>
              <td colspan="2" style="padding:8px 12px;font-size:11px;color:#d97706;font-weight:700">${pendMonto>0?'Por cobrar: $ '+pendMonto.toLocaleString('es-CO',{minimumFractionDigits:0}):''}</td>
            </tr></tfoot>
          </table>`;
    } catch(e) {
        wrap.innerHTML = '<div style="text-align:center;padding:20px;color:#ef4444;font-size:12px">Error al cargar</div>';
    }
}

/* ── Modal crear / editar negocio ─────────────────────────────────── */
function abrirModalNegocio(idOrObj) {
    const n      = typeof idOrObj === 'number' ? _negociosData.find(x => x.id === idOrObj) : null;
    const isEdit = !!n;
    document.getElementById('negocioModalTitle').textContent  = isEdit ? 'Editar negocio' : 'Nuevo negocio';
    document.getElementById('negocioId').value                = isEdit ? n.id             : '';
    document.getElementById('negocioNombre').value            = isEdit ? (n.nombre        ||'') : '';
    document.getElementById('negocioTipo').value              = isEdit ? (n.tipo          ||'') : '';
    document.getElementById('negocioEstado').value            = isEdit ? (n.estado        ||'activo') : 'activo';
    document.getElementById('negocioDescripcion').value       = isEdit ? (n.descripcion   ||'') : '';
    document.getElementById('negocioMonto').value             = isEdit ? (n.monto         ||'') : '';
    document.getElementById('negocioFechaInicio').value       = isEdit ? (n.fecha_inicio  ||'') : '';
    document.getElementById('negocioFechaEntrega').value      = isEdit ? (n.fecha_entrega ||'') : '';
    document.getElementById('negocioNotas').value             = isEdit ? (n.notas         ||'') : '';
    document.getElementById('negocioModal').classList.add('show');
    setTimeout(() => document.getElementById('negocioNombre').focus(), 100);
}

function cerrarModalNegocio() { document.getElementById('negocioModal').classList.remove('show'); }

async function guardarNegocio() {
    const nombre = document.getElementById('negocioNombre').value.trim();
    if (!nombre) { showToast('El nombre es requerido', 'warning'); return; }
    const id  = document.getElementById('negocioId').value;
    const btn = document.getElementById('negocioGuardarBtn');
    btn.disabled = true;
    const payload = {
        cliente_id:    clienteId, nombre,
        tipo:          document.getElementById('negocioTipo').value          || null,
        estado:        document.getElementById('negocioEstado').value,
        descripcion:   document.getElementById('negocioDescripcion').value.trim() || null,
        monto:         document.getElementById('negocioMonto').value         || null,
        fecha_inicio:  document.getElementById('negocioFechaInicio').value   || null,
        fecha_entrega: document.getElementById('negocioFechaEntrega').value  || null,
        notas:         document.getElementById('negocioNotas').value.trim()  || null,
    };
    if (id) payload.id = parseInt(id);
    try {
        const r = await fetch('api/cliente_negocios.php', {
            method: id ? 'PUT' : 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.success) {
            showToast(d.message, 'success');
            cerrarModalNegocio();
            if (!id) _negocioSelected = d.data.id; // auto-seleccionar el nuevo
            await loadNegocios();
        } else showToast(d.error||'Error al guardar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
    btn.disabled = false;
}

async function eliminarNegocio(id) {
    const ok = await confirmAction('¿Eliminar este negocio?', 'Esta acción no se puede deshacer.', 'Eliminar', 'danger');
    if (!ok) return;
    try {
        const r = await fetch(`api/cliente_negocios.php?id=${id}`, {method:'DELETE'});
        const d = await r.json();
        if (d.success) {
            if (_negocioSelected === id) _negocioSelected = null;
            showToast('Negocio eliminado', 'success');
            await loadNegocios();
        } else showToast(d.error||'Error', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

/* ── Modal agregar trabajo ────────────────────────────────────────── */
function abrirModalTrabajo(negocioId, negocioNombre) {
    document.getElementById('trabajoNegocioId').value           = negocioId;
    document.getElementById('trabajoModalSubtitle').textContent = negocioNombre;
    document.getElementById('trabajoConcepto').value            = '';
    document.getElementById('trabajoMonto').value               = '';
    document.getElementById('trabajoEstado').value              = 'pendiente';
    document.getElementById('trabajoFecha').value               = '';
    document.getElementById('trabajoModal').classList.add('show');
    setTimeout(() => document.getElementById('trabajoConcepto').focus(), 100);
}

function cerrarModalTrabajo() { document.getElementById('trabajoModal').classList.remove('show'); }

async function guardarTrabajo() {
    const concepto  = document.getElementById('trabajoConcepto').value.trim();
    const monto     = document.getElementById('trabajoMonto').value;
    const negocioId = parseInt(document.getElementById('trabajoNegocioId').value);
    if (!concepto) { showToast('El concepto es requerido', 'warning'); return; }
    if (!monto || parseFloat(monto) <= 0) { showToast('El monto debe ser mayor a 0', 'warning'); return; }
    const btn = document.getElementById('trabajoGuardarBtn');
    btn.disabled = true;
    try {
        const r = await fetch('api/transacciones.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                tipo:              'ingreso',
                monto:             parseFloat(monto),
                concepto,
                estado:            document.getElementById('trabajoEstado').value,
                fecha_vencimiento: document.getElementById('trabajoFecha').value || null,
                cliente_id:        clienteId,
                negocio_id:        negocioId,
                frecuencia:        'unico',
            })
        });
        const d = await r.json();
        if (d.success) {
            showToast('Trabajo registrado correctamente', 'success');
            cerrarModalTrabajo();
            loadNegocioTxs(negocioId);
        } else showToast(d.error||'Error al guardar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
    btn.disabled = false;
}


document.getElementById('negocioModal')?.addEventListener('click', e => { if(e.target===e.currentTarget) cerrarModalNegocio(); });
document.getElementById('trabajoModal')?.addEventListener('click', e => { if(e.target===e.currentTarget) cerrarModalTrabajo(); });

loadNegocios();
loadServices();
loadNotes();
loadMedia();
loadPagosUnicosBanner();
loadTrabajosAdicionales();

/* ── RENOVAR SERVICIOS ──────────────────────────────────────── */
const FREQ_LABELS_REN = { mes:'Mensual', trimestre:'Trimestral', semestre:'Semestral', año:'Anual', unico:'Pago Único' };

function getSelectedSvcIds() {
    return Array.from(document.querySelectorAll('.svc-check:checked')).map(c => c.value);
}

async function openRenovarModal() {
    document.getElementById('renovarPreviewBody').innerHTML =
        '<tr><td colspan="5" style="padding:30px;text-align:center;color:#94a3b8">Calculando...</td></tr>';
    document.getElementById('renovarSubtitle').textContent = 'Calculando nuevas fechas...';
    document.getElementById('renovarConfirmBtn').disabled = true;
    document.getElementById('renovarModal').classList.add('show');

    try {
        const resp    = await fetch(`api/renovar_servicios.php?cliente_id=${clienteId}`);
        const json    = await resp.json();
        if (!json.success) { showToast(json.error || 'Error', 'error'); return; }

        const preview = json.preview || [];
        document.getElementById('renovarWarning').style.display = 'block';

        if (!preview.length) {
            document.getElementById('renovarSubtitle').textContent = 'No hay servicios recurrentes activos para renovar.';
            document.getElementById('renovarPreviewBody').innerHTML =
                '<tr><td colspan="5" style="padding:30px;text-align:center;color:#94a3b8;font-style:italic">Sin servicios recurrentes para renovar.</td></tr>';
            return;
        }

        const fmtDate = fecha => new Date(fecha + 'T12:00:00').toLocaleDateString('es-CO', {day:'2-digit', month:'short', year:'numeric'});

        document.getElementById('renovarPreviewBody').innerHTML = preview.map(p => `
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:10px 14px;text-align:center;width:36px">
                    <input type="checkbox" class="renov-check" value="${p.id}" checked
                        onchange="updateRenovarBtn()"
                        style="width:16px;height:16px;cursor:pointer;accent-color:#3b82f6">
                </td>
                <td style="padding:10px 8px;font-weight:700;color:#0f172a">${escapeHtml(p.servicio_nombre)}</td>
                <td style="padding:10px 8px;text-align:center">
                    <span style="font-size:11px;background:#eff6ff;color:#3b82f6;padding:2px 8px;border-radius:20px;font-weight:700">${FREQ_LABELS_REN[p.frecuencia] || p.frecuencia}</span>
                </td>
                <td style="padding:10px 8px;text-align:right;font-size:12px;color:#64748b">${fmtDate(p.fecha_actual)}</td>
                <td style="padding:10px 14px;text-align:right;font-size:13px;font-weight:800;color:#10b981">${fmtDate(p.nueva_fecha_fin)}</td>
            </tr>`).join('');

        updateRenovarBtn();
        // Mostrar botón revertir solo cuando hay servicios
        document.getElementById('renovarRevertBtn').style.display = preview.length ? 'flex' : 'none';
    } catch(e) {
        console.error(e);
        showToast('Error al cargar preview de renovación', 'error');
    }
}

function toggleAllRenov(master) {
    document.querySelectorAll('.renov-check').forEach(c => c.checked = master.checked);
    updateRenovarBtn();
}

function updateRenovarBtn() {
    const checked = document.querySelectorAll('.renov-check:checked');
    const total   = document.querySelectorAll('.renov-check');
    const btn     = document.getElementById('renovarConfirmBtn');
    const all     = document.getElementById('renovarCheckAll');
    btn.disabled  = checked.length === 0;
    if (all) {
        all.indeterminate = checked.length > 0 && checked.length < total.length;
        all.checked = checked.length === total.length;
    }
    // Actualizar subtítulo
    const sub = document.getElementById('renovarSubtitle');
    if (sub) sub.textContent = checked.length > 0
        ? `Se renovarán ${checked.length} de ${total.length} servicio(s). Las fechas se extenderán según su ciclo.`
        : 'Selecciona al menos un servicio para renovar.';
}

function closeRenovarModal() {
    document.getElementById('renovarModal').classList.remove('show');
}

async function ejecutarAccionRenovar(action) {
    const btnId  = action === 'revertir' ? 'renovarRevertBtn' : 'renovarConfirmBtn';
    const btn    = document.getElementById(btnId);
    const label  = action === 'revertir' ? 'Revirtiendo...' : 'Renovando...';
    const orig   = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> ${label}`;

    try {
        const ids = Array.from(document.querySelectorAll('.renov-check:checked')).map(c => Number(c.value));
        const r   = await fetch('api/renovar_servicios.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ cliente_id: clienteId, ids, action })
        });
        const d = await r.json();
        if (d.success) {
            showToast(d.message, 'success');
            closeRenovarModal();
            loadServices();
            loadNotes();
        } else {
            showToast(d.error || 'Error', 'error');
        }
    } catch(e) { showToast('Error', 'error'); }

    btn.disabled = false;
    btn.innerHTML = orig;
}

async function confirmarRenovacion() {
    await ejecutarAccionRenovar('renovar');
}

function pedirConfirmarReversion() {
    const box = document.getElementById('revertConfirmBox');
    box.style.display = 'flex';
}
function cerrarConfirmReversion() {
    document.getElementById('revertConfirmBox').style.display = 'none';
}
async function ejecutarReversion() {
    cerrarConfirmReversion();
    await ejecutarAccionRenovar('revertir');
}

document.getElementById('renovarModal').addEventListener('click', function(e) {
    if (e.target === this) closeRenovarModal();
});

// Editar Datos del Cliente
function openEditModal() {
    // json_encode garantiza escape correcto de comillas, backslashes, saltos de línea, etc.
    const cliente = <?= json_encode([
        'nombre_comercial'  => $cliente['nombre_comercial']       ?? '',
        'estado'            => $cliente['estado']                 ?? 'activo',
        'persona_contacto'  => $cliente['persona_contacto']       ?? '',
        'nit_cedula'        => $cliente['nit_cedula']             ?? '',
        'telefono'          => $cliente['telefono']               ?? '',
        'email_facturacion' => $cliente['email_facturacion']      ?? '',
        'email_contacto'    => $cliente['email_contacto']         ?? '',
        'direccion'         => $cliente['direccion']              ?? '',
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

    document.getElementById('editNombreComercial').value = cliente.nombre_comercial;
    document.getElementById('editEstado').value = cliente.estado;
    document.getElementById('editPersonaContacto').value = cliente.persona_contacto;
    document.getElementById('editNitCedula').value = cliente.nit_cedula;
    document.getElementById('editTelefono').value = cliente.telefono;
    document.getElementById('editEmailFacturacion').value = cliente.email_facturacion;
    document.getElementById('editEmailContacto').value = cliente.email_contacto;
    document.getElementById('editDireccion').value = cliente.direccion;

    document.getElementById('editClientModal').classList.add('show');
}

function closeEditClientModal() {
    document.getElementById('editClientModal').classList.remove('show');
}

async function saveClientData(event) {
    event.preventDefault();
    const data = {
        id: clienteId,
        nombre_comercial: document.getElementById('editNombreComercial').value,
        estado: document.getElementById('editEstado').value,
        persona_contacto: document.getElementById('editPersonaContacto').value || null,
        nit_cedula: document.getElementById('editNitCedula').value || null,
        telefono: document.getElementById('editTelefono').value || null,
        email_facturacion: document.getElementById('editEmailFacturacion').value || null,
        email_contacto: document.getElementById('editEmailContacto').value || null,
        direccion: document.getElementById('editDireccion').value || null
    };

    try {
        const response = await fetch('api/clientes.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            showToast('Cliente actualizado correctamente', 'success');
            closeEditClientModal();
            // Recargar la página para mostrar cambios
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(result.error || 'Error al actualizar', 'error');
        }
    } catch(e) {
        showToast('Error al guardar cambios', 'error');
        console.error(e);
    }
}

// ── Modal Mensajes ─────────────────────────────────────────────────────────────
let _msgPlantilla = null;
let _msgCanal = 'correo';

function _hesc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function openMensajesModal() {
    document.getElementById('mensajesModal').classList.add('show');
    // Hide WA tab — only email notifications
    const tabWA = document.getElementById('msgTabWA');
    if (tabWA) tabWA.style.display = 'none';
    mostrarMsgStep1();
    switchMensajeTab('correo');
}

function closeMensajesModal() {
    document.getElementById('mensajesModal').classList.remove('show');
    _msgPlantilla = null;
}

function mostrarMsgStep1() {
    document.getElementById('msgStep1').style.display = '';
    document.getElementById('msgEmailStep2').style.display = 'none';
}

function switchMensajeTab(canal) {
    _msgCanal = canal;
    const tabWA    = document.getElementById('msgTabWA');
    const tabEmail = document.getElementById('msgTabEmail');
    tabWA.style.borderBottomColor    = canal === 'whatsapp' ? '#25D366'  : 'transparent';
    tabWA.style.color                = canal === 'whatsapp' ? '#0f172a'  : '#94a3b8';
    tabEmail.style.borderBottomColor = canal === 'correo'   ? '#4f46e5'  : 'transparent';
    tabEmail.style.color             = canal === 'correo'   ? '#0f172a'  : '#94a3b8';
    cargarMsgPlantillas();
}

async function cargarMsgPlantillas() {
    const cont = document.getElementById('msgListado');
    cont.innerHTML = '<div style="padding:24px;text-align:center;color:#94a3b8;font-size:13px">Cargando...</div>';
    try {
        const r = await fetch('api/mensajes_plantillas.php');
        const d = await r.json();
        if (d.success && d.data && d.data.length) {
            cont.innerHTML = '';
            d.data.forEach(m => {
                const div = document.createElement('div');
                div.style.cssText = 'padding:14px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .15s;display:flex;align-items:flex-start;gap:10px';
                const badge = m.es_predefinida == 1
                    ? `<span style="font-size:10px;font-weight:600;background:#f1f5f9;color:#64748b;padding:2px 7px;border-radius:20px;margin-left:6px">Predefinida</span>`
                    : '';
                div.innerHTML = `
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;flex-wrap:wrap;gap:4px">${_hesc(m.nombre)}${badge}</div>
                        <div style="font-size:12px;color:#64748b;line-height:1.4">${m.contenido ? _hesc(m.contenido.substring(0,90)) + (m.contenido.length > 90 ? '…' : '') : 'Sin contenido'}</div>
                    </div>
                    <span style="font-size:10px;font-weight:700;background:#1e293b;color:#ffffff;padding:4px 8px;border-radius:14px;white-space:nowrap;flex-shrink:0">Usar</span>
                `;
                div.addEventListener('mouseenter', () => { div.style.borderColor='#94a3b8'; div.style.background='#f8fafc'; });
                div.addEventListener('mouseleave', () => { div.style.borderColor='#e2e8f0'; div.style.background=''; });
                div.addEventListener('click', () => seleccionarMsgPlantilla(m));
                cont.appendChild(div);
            });
        } else {
            cont.innerHTML = '<div style="padding:24px;text-align:center;color:#94a3b8;font-size:13px">Sin plantillas disponibles</div>';
        }
    } catch(e) {
        cont.innerHTML = '<div style="padding:24px;text-align:center;color:#ef4444;font-size:13px">Error al cargar plantillas</div>';
    }
}

function seleccionarMsgPlantilla(p) {
    _msgPlantilla = p;
    const nombreCliente = "<?= addslashes(sanitize($cliente['nombre_comercial'])) ?>";
    const texto = (p.contenido || '').replace(/\{\{cliente_nombre\}\}/g, nombreCliente).replace(/\{\{empresa\}\}/g, nombreCliente);

    if (_msgCanal === 'whatsapp') {
        const tel = "<?= preg_replace('/\D/', '', $cliente['telefono'] ?? '') ?>";
        if (!tel) { showToast('El cliente no tiene número de WhatsApp registrado', 'warning'); return; }
        closeMensajesModal();
        window.open('https://wa.me/' + tel + '?text=' + encodeURIComponent(texto || p.nombre), '_blank');
        fetch('api/cliente_notas.php', { method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ cliente_id: clienteId, nota: '[📱] WhatsApp: ' + p.nombre })
        }).then(() => loadNotes());
        showToast('WhatsApp abierto', 'success');
    } else {
        const email = "<?= addslashes(sanitize($cliente['email_facturacion'] ?? '')) ?>";
        document.getElementById('msgEmailPara').textContent = email ? 'Para: ' + email : '⚠ Sin correo registrado';
        document.getElementById('msgEmailAsunto').value = p.nombre + ' — QUANTUN Digital';
        const imgBox = document.getElementById('msgEmailImgBox');
        if (p.imagen) {
            imgBox.style.display = '';
            document.getElementById('msgEmailImgThumb').src = p.imagen;
        } else {
            imgBox.style.display = 'none';
        }
        document.getElementById('msgEmailPreview').innerHTML = _buildMsgEmailPreview(nombreCliente, texto, p.imagen || null, p.logo_url || null);
        document.getElementById('msgStep1').style.display = 'none';
        document.getElementById('msgEmailStep2').style.display = '';
    }
}

function _buildMsgEmailPreview(nombre, texto, imagenUrl, logoUrl) {
    const imgBlock = imagenUrl
        ? '<div style="margin-bottom:16px"><img src="' + imagenUrl + '" style="max-width:100%;border-radius:8px;display:block"></div>'
        : '';
    const parrafos = (texto || '').split('\n').filter(l => l.trim()).map(l =>
        '<p style="margin:0 0 8px;color:#0f172a;font-size:13px;line-height:1.6">' + _hesc(l) + '</p>').join('');
    const logoBlock = logoUrl
        ? '<img src="' + logoUrl + '" alt="Logo" style="height:36px;max-width:180px;object-fit:contain;display:block">'
        : '<span style="font-size:15px;font-weight:800;color:#c9f31d">⚡ QUANTUN Digital</span>';
    return '<div style="font-family:\'Segoe UI\',Arial,sans-serif;background:#f1f5f9;padding:16px">'
        + '<div style="background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0">'
        + '<div style="background:#0f172a;padding:14px 20px">' + logoBlock + '</div>'
        + '<div style="padding:16px 20px">' + imgBlock + parrafos + '</div>'
        + '<div style="padding:10px 20px;background:#f8fafc;border:1.5px solid #cbd5e1;border-top:1px solid #e2e8f0;text-align:center;font-size:11px;color:#94a3b8">QUANTUN Digital · Soluciones Digitales</div>'
        + '</div></div>';
}

async function confirmarEnvioMsgEmail() {
    if (!_msgPlantilla) return;
    const email = "<?= addslashes(sanitize($cliente['email_facturacion'] ?? '')) ?>";
    if (!email) { showToast('El cliente no tiene correo registrado', 'warning'); return; }

    const asunto = document.getElementById('msgEmailAsunto').value.trim();
    if (!asunto) { showToast('El asunto es obligatorio', 'warning'); return; }

    const nombreCliente = "<?= addslashes(sanitize($cliente['nombre_comercial'])) ?>";
    const textoFinal = (_msgPlantilla.contenido || '').replace(/\{\{cliente_nombre\}\}/g, nombreCliente).replace(/\{\{empresa\}\}/g, nombreCliente);

    // Logo URL: será proporcionado por la plantilla o buscado en servidor
    const logoUrl = _msgPlantilla.logo_url || '';

    const btn = document.getElementById('btnEnviarMsgCorreo');
    btn.disabled = true; btn.textContent = 'Enviando...';

    try {
        const r = await fetch('api/enviar_correo_cliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cliente_id:    clienteId,
                asunto,
                mensaje_texto: textoFinal,
                imagen_path:   _msgPlantilla.imagen || '',
                logo_url:      logoUrl
            })
        });
        const d = await r.json();
        if (d.success) {
            fetch('api/cliente_notas.php', { method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ cliente_id: clienteId, nota: '[📧] Correo enviado: ' + _msgPlantilla.nombre })
            }).then(() => loadNotes());
            closeMensajesModal();
            showToast('✅ ' + d.message, 'success');
        } else {
            showToast(d.error || 'Error al enviar', 'error');
            btn.disabled = false; btn.textContent = 'Enviar correo';
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
        btn.disabled = false; btn.textContent = 'Enviar correo';
    }
}
</script>

<!-- Modal Mensajes -->
<div class="modal-overlay" id="mensajesModal">
    <div class="modal" style="max-width:730px">

        <!-- PASO 1: canal + listado de plantillas -->
        <div id="msgStep1">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Notificar al cliente</h3>
                    <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Elige el canal y la plantilla</p>
                </div>
                <button class="modal-close" onclick="closeMensajesModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div style="display:flex;gap:0;border-bottom:2px solid #f1f5f9;padding:0 16px">
                <button id="msgTabWA" onclick="switchMensajeTab('whatsapp')"
                    style="display:inline-flex;align-items:center;gap:6px;padding:12px 16px;background:none;border:none;border-bottom:2px solid #25D366;margin-bottom:-2px;font-size:13px;font-weight:700;color:#0f172a;cursor:pointer">
                    <svg width="14" height="14" fill="#25D366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519"/></svg>
                    WhatsApp
                </button>
                <button id="msgTabEmail" onclick="switchMensajeTab('correo')"
                    style="display:inline-flex;align-items:center;gap:6px;padding:12px 16px;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;font-size:13px;font-weight:700;color:#94a3b8;cursor:pointer">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Correo
                </button>
            </div>
            <div id="msgListado" style="padding:16px;display:grid;gap:10px;max-height:55vh;overflow-y:auto"></div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeMensajesModal()">Cancelar</button>
            </div>
        </div>

        <!-- PASO 2: preview correo + enviar -->
        <div id="msgEmailStep2" style="display:none">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" style="display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Enviar correo
                    </h3>
                    <p id="msgEmailPara" style="font-size:12px;color:#94a3b8;margin:3px 0 0"></p>
                </div>
                <button class="modal-close" onclick="closeMensajesModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">
                <div id="msgEmailImgBox" style="display:none">
                    <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f0fdf4;border-radius:8px;border:1.5px solid #bbf7d0">
                        <img id="msgEmailImgThumb" src="" style="width:44px;height:44px;border-radius:6px;object-fit:cover">
                        <div style="font-size:12px;font-weight:700;color:#166534">✓ Imagen incluida en el correo</div>
                    </div>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Asunto</label>
                    <input id="msgEmailAsunto" class="form-input" placeholder="Asunto del correo">
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Vista previa</div>
                    <div id="msgEmailPreview" style="border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden;max-height:260px;overflow-y:auto"></div>
                </div>
            </div>
            <div class="modal-footer" style="gap:8px">
                <button class="btn btn-outline" onclick="mostrarMsgStep1()">← Cambiar plantilla</button>
                <button id="btnEnviarMsgCorreo" onclick="confirmarEnvioMsgEmail()"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#4f46e5;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s"
                    onmouseenter="this.style.filter='brightness(.9)'" onmouseleave="this.style.filter=''">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Enviar correo
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Modal Orden de Compra -->
<div class="modal-overlay" id="ordenModal">
    <div class="modal" style="max-width:1100px;width:96vw;height:90vh;display:flex;flex-direction:column">
        <div class="modal-header">
            <h3 class="modal-title" id="ordenModalTitle">Orden de Compra</h3>
            <button class="modal-close" onclick="closeOrdenModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="flex:1;overflow:hidden;display:flex;border-top:1.5px solid #e2e8f0">
            <!-- Panel editor -->
            <div style="width:340px;min-width:280px;overflow-y:auto;background:#f8fafc;border-right:1.5px solid #e2e8f0;display:flex;flex-direction:column;gap:0">
                <!-- Selector de plantilla (solo renovación) -->
                <div id="ordenPlantillaWrap" style="display:none;padding:12px 16px;border-bottom:1.5px solid #e2e8f0">
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:6px">Plantilla</label>
                    <select id="ordenPlantillaSelect" style="width:100%;box-sizing:border-box;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;outline:none;cursor:pointer" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" onchange="onOrdenPlantillaChange()">
                        <option value="">Cargando plantillas...</option>
                    </select>
                </div>
                <div style="padding:16px 16px 0">
                    <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin:0 0 10px">Items de la orden</p>
                    <div id="ordenItemsContainer" style="display:flex;flex-direction:column;gap:8px"></div>
                    <button onclick="addOrdenItem()" style="width:100%;margin-top:10px;padding:8px;background:transparent;border:1.5px dashed #cbd5e1;border-radius:8px;font-size:12px;font-weight:600;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px" onmouseenter="this.style.borderColor='#94a3b8'" onmouseleave="this.style.borderColor='#cbd5e1'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Agregar item
                    </button>
                </div>
                <div style="margin:16px 16px 0;height:1px;background:#e2e8f0"></div>
                <div style="padding:12px 16px;display:flex;flex-direction:column;gap:10px">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div>
                            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:5px">Método de Pago</label>
                            <input id="ordenMetodoPago" type="text" value="Transferencia Bancaria / PSE / QR" style="width:100%;box-sizing:border-box;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;outline:none" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" oninput="scheduleOrdenPreview()">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:5px">Fecha último pago</label>
                            <input id="ordenFechaUltPago" type="date" style="width:100%;box-sizing:border-box;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;outline:none" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" onchange="scheduleOrdenPreview()">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:5px">Notas / Pie de página</label>
                        <textarea id="ordenNotas" rows="2" style="width:100%;box-sizing:border-box;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;outline:none;resize:vertical" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" oninput="scheduleOrdenPreview()"></textarea>
                    </div>
                    <!-- Enlace de pago (solo renovación) -->
                    <div id="ordenLinkPagoWrap" style="display:none">
                        <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:5px">Enlace de Pago (opcional)</label>
                        <input id="ordenLinkPago" type="url" placeholder="https://pago.ejemplo.com/..." style="width:100%;box-sizing:border-box;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;outline:none" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" oninput="scheduleOrdenPreview()">
                    </div>
                </div>
                <div style="margin:4px 16px 0;height:1px;background:#e2e8f0"></div>
                <!-- Datos bancarios -->
                <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px">
                    <!-- Header: toggle + label + botón cargar -->
                    <div style="display:flex;align-items:center;gap:8px">
                        <label style="display:flex;align-items:center;gap:7px;cursor:pointer;flex:1;min-width:0">
                            <div style="position:relative;width:34px;height:19px;flex-shrink:0" onclick="toggleBancariosIncluir()">
                                <div id="trackBancarios" style="width:34px;height:19px;border-radius:10px;background:#cbd5e1;transition:background .2s"></div>
                                <div id="thumbBancarios" style="position:absolute;top:2px;left:2px;width:15px;height:15px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:transform .2s"></div>
                            </div>
                            <span id="lblBancarios" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b">Datos Bancarios</span>
                        </label>
                        <button id="btnOrdenCargarConfig" onclick="onOrdenCargarMisDatos()" style="display:none;padding:4px 9px;background:#f0fdf4;color:#16a34a;border:1.5px solid #bbf7d0;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;transition:filter .15s" onmouseenter="this.style.filter='brightness(.95)'" onmouseleave="this.style.filter=''">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="vertical-align:middle;margin-right:3px"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Cargar
                        </button>
                    </div>
                    <!-- Campos bancarios (se ocultan con el toggle) -->
                    <div id="bancariosCampos">
                    <?php
                    $inputBancStyle = 'width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;outline:none';
                    $labelBancStyle = 'font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px';
                    ?>
                    <div>
                        <label style="<?= $labelBancStyle ?>">Titular de la Cuenta</label>
                        <input id="bancTitular" type="text" placeholder="Nombre completo o razón social" style="<?= $inputBancStyle ?>" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" oninput="scheduleOrdenPreview()">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div>
                            <label style="<?= $labelBancStyle ?>">Cédula / NIT</label>
                            <input id="bancCedula" type="text" placeholder="000.000.000-0" style="<?= $inputBancStyle ?>" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" oninput="scheduleOrdenPreview()">
                        </div>
                        <div>
                            <label style="<?= $labelBancStyle ?>">Banco</label>
                            <input id="bancBanco" type="text" placeholder="Ej: Bancolombia" style="<?= $inputBancStyle ?>" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" oninput="scheduleOrdenPreview()">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div>
                            <label style="<?= $labelBancStyle ?>">N° de Cuenta</label>
                            <input id="bancCuenta" type="text" placeholder="000-000000-00" style="<?= $inputBancStyle ?>" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" oninput="scheduleOrdenPreview()">
                        </div>
                        <div>
                            <label style="<?= $labelBancStyle ?>">Tipo de Cuenta</label>
                            <select id="bancTipo" style="<?= $inputBancStyle ?>;cursor:pointer" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" onchange="scheduleOrdenPreview()">
                                <option value="">Seleccionar</option>
                                <option value="Cuenta de Ahorros">Cuenta de Ahorros</option>
                                <option value="Cuenta Corriente">Cuenta Corriente</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label style="<?= $labelBancStyle ?>">Llave (Nequi / Daviplata / QR)</label>
                        <input id="bancLlave" type="text" placeholder="Ej: +57 300 000 0000" style="<?= $inputBancStyle ?>" onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e2e8f0'" oninput="scheduleOrdenPreview()">
                    </div>
                </div>
                </div>
                <div style="padding:0 16px 16px">
                    <div style="padding:10px 12px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:8px">
                        <div style="display:flex;justify-content:space-between;font-size:11px;color:#64748b;margin-bottom:4px"><span>Subtotal</span><span id="ordenResumenSubtotal">$ 0</span></div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:800;color:#0f172a"><span>Total</span><span id="ordenResumenTotal">$ 0</span></div>
                    </div>
                </div>
            </div>
            <!-- Preview -->
            <div style="flex:1;overflow:hidden;position:relative">
                <div id="ordenIframeLoader" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#f8fafc;z-index:2;gap:10px">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c9f31d" stroke-width="2.5" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span style="font-size:12px;color:#64748b;font-weight:600">Generando vista previa...</span>
                </div>
                <iframe id="ordenIframe" name="ordenIframe" style="width:100%;height:100%;border:none" src="about:blank" onload="(function(fr){var url=fr.contentDocument&&fr.contentDocument.URL||'';if(url&&url!=='about:blank'){document.getElementById('ordenIframeLoader').style.display='none';}})(this)"></iframe>
            </div>
        </div>
        <!-- Form oculto para POST al iframe — fuera del footer para no afectar layout -->
        <input type="hidden" id="currentCsId">
        <input type="hidden" id="currentCsIds">
        <form id="ordenPreviewForm" method="POST" action="orden_compra.php" target="ordenIframe" style="display:none">
            <input type="hidden" name="cs_id" id="ordenFormCsId">
            <input type="hidden" name="cs_ids" id="ordenFormCsIds">
            <input type="hidden" name="items_json" id="ordenFormItemsJson">
            <input type="hidden" name="metodo_pago" id="ordenFormMetodoPago">
            <input type="hidden" name="notas_pie_override" id="ordenFormNotas">
            <input type="hidden" name="bancarios_json" id="ordenFormBancarios">
            <input type="hidden" name="fecha_ult_pago" id="ordenFormFechaUltPago">
            <input type="hidden" name="link_pago" id="ordenFormLinkPago">
            <input type="hidden" name="plantilla_id" id="ordenFormPlantillaId">
            <input type="hidden" name="doc_tipo" id="ordenFormDocTipo">
        </form>
        <div class="modal-footer" style="gap:8px;border-top:1.5px solid #e2e8f0;flex-wrap:wrap;align-items:center">
            <button class="btn btn-outline" onclick="closeOrdenModal()">Cerrar</button>
            <!-- Guardar datos bancarios en config (solo renovación) -->
            <button id="btnGuardarEnConfig" onclick="guardarBancariosEnConfig()" style="display:none;align-items:center;gap:7px;padding:10px 18px;background:#f0fdf4;color:#16a34a;border:1.5px solid #bbf7d0;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.filter='brightness(.95)'" onmouseleave="this.style.filter=''">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Guardar datos
            </button>
            <button onclick="saveOrdenDraft()" style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='#f8fafc'" title="Guardar borrador">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Guardar Borrador
                <span id="draftBadge" style="display:none;width:8px;height:8px;background:#f59e0b;border-radius:50%;flex-shrink:0" title=""></span>
            </button>
            <button onclick="refreshOrdenPreview()" style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#f1f5f9;color:#0f172a;border:1.5px solid #e2e8f0;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.background='#e2e8f0'" onmouseleave="this.style.background='#f1f5f9'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Vista Previa
            </button>
            <button onclick="downloadOrdenPDF()" style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#1e293b;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.filter='brightness(.9)'" onmouseleave="this.style.filter=''">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Descargar PDF
            </button>
            <button onclick="sendOrdenByEmail()" style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#4f46e5;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.filter='brightness(.9)'" onmouseleave="this.style.filter=''">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Enviar por Correo
            </button>
        </div>
    </div>
</div>

<!-- Modal Detalles de Tarea del Cliente -->
<div class="modal-overlay" id="detallesTareaClienteModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header" style="background:#ffffff;border-bottom:1.5px solid #e2e8f0;padding:20px 24px">
            <div>
                <h3 class="modal-title" style="color:#0f172a">Detalles de la Tarea</h3>
                <p style="font-size:12px;color:#64748b;margin:3px 0 0" id="detalleTareaClienteTitulo">—</p>
            </div>
            <button class="modal-close" onclick="closeDetallesTareaClienteModal()" style="color:#94a3b8">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;gap:12px">
            <!-- Prioridad | Estado (dos columnas) -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Prioridad</label>
                    <div id="detallePrioridadCliente" style="padding:10px 12px;background:#ffffff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;font-weight:600;color:#0f172a">—</div>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Estado</label>
                    <div id="detalleEstadoCliente" style="padding:10px 12px;background:#ffffff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;font-weight:600;color:#0f172a">—</div>
                </div>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Descripción</label>
                <div id="detalleDescripcionCliente" style="padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;color:#0f172a;line-height:1.5;min-height:50px;white-space:pre-wrap;word-break:break-word">—</div>
            </div>

            <!-- Responsable | Fecha Límite (dos columnas) -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Responsable</label>
                    <div id="detalleResponsableCliente" style="padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;color:#0f172a">—</div>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Fecha Límite</label>
                    <div id="detalleFechaCliente" style="padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;color:#0f172a">—</div>
                </div>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Notas</label>
                <div id="detalleNotasCliente" style="padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;color:#0f172a;line-height:1.5;min-height:50px;white-space:pre-wrap;word-break:break-word">—</div>
            </div>

            <!-- Acciones rápidas -->
            <div style="background:#ffffff;border:1.5px solid #cbd5e1;border-radius:8px;padding:12px;margin-top:8px">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a;margin-bottom:8px;display:block">Cambiar estado rápido</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                    <button onclick="cambiarEstadoTareaCliente('pendiente')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:11px;cursor:pointer;background:#fff;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='#fff'">Pendiente</button>
                    <button onclick="cambiarEstadoTareaCliente('en_progreso')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #3b82f6;border-radius:6px;font-size:11px;cursor:pointer;background:#eff6ff;color:#1d4ed8;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#dbeafe'" onmouseleave="this.style.background='#eff6ff'">En Proceso</button>
                    <button onclick="cambiarEstadoTareaCliente('revision')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #f59e0b;border-radius:6px;font-size:11px;cursor:pointer;background:#fffbeb;color:#92400e;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#fef3c7'" onmouseleave="this.style.background='#fffbeb'">Revisión</button>
                    <button onclick="cambiarEstadoTareaCliente('completado')" class="estado-btn" style="padding:6px 10px;border:1.5px solid #22c55e;border-radius:6px;font-size:11px;cursor:pointer;background:#f0fdf4;color:#166534;font-weight:600;transition:all .12s" onmouseenter="this.style.background='#dcfce7'" onmouseleave="this.style.background='#f0fdf4'">Completado</button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="btnEliminarTareaModal" class="btn btn-outline" style="color:#B0382F;border-color:#FECACA;margin-right:auto"
                onclick="eliminarTareaClienteDesdeModal()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Eliminar
            </button>
            <button class="btn btn-outline" onclick="closeDetallesTareaClienteModal()">Cerrar</button>
            <button id="btnIrATasksPage" class="btn btn-secondary" style="background:#4f46e5;color:#fff" onclick="goToTaskPage()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Ver en Tareas
            </button>
        </div>
    </div>
</div>

<!-- Modal Agregar Tarea -->
<div class="modal-overlay" id="tareasModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:36px;height:36px;background:#eef2ff;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="18" height="18" fill="none" stroke="#4f46e5" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M5 11h.01"/></svg>
                </div>
                <div>
                    <h3 class="modal-title" style="font-size:15px">Agregar Tarea</h3>
                    <p style="font-size:11px;color:#94a3b8;margin:2px 0 0">Nueva tarea para este cliente</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeTareasModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;gap:14px;padding:20px">
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Título de la tarea *</label>
                <input id="tarea_titulo" type="text" class="form-input" placeholder="Ej: Llamar cliente" style="padding:8px 12px;font-size:13px" required>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Descripción</label>
                <textarea id="tarea_descripcion" class="form-input" placeholder="Detalles de la tarea..." style="padding:8px 12px;font-size:13px;min-height:70px;resize:vertical"></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Prioridad</label>
                    <select id="tarea_prioridad" class="form-input" style="padding:8px 12px;font-size:13px">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                    </select>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Fecha Límite</label>
                    <input id="tarea_fecha" type="date" class="form-input" style="padding:8px 12px;font-size:13px">
                </div>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px;font-weight:700;color:#0f172a">Notas adicionales</label>
                <textarea id="tarea_notas" class="form-input" placeholder="Notas o comentarios..." style="padding:8px 12px;font-size:13px;min-height:60px;resize:vertical"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeTareasModal()">Cancelar</button>
            <button class="btn btn-secondary" onclick="crearTarea()" style="background:#4f46e5;color:#fff">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                Crear Tarea
            </button>
        </div>
    </div>
</div>

<!-- ── Modal Negocio ──────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="negocioModal">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="negocioModalTitle">Nuevo negocio</h3>
                <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Registra un trabajo o proyecto del cliente</p>
            </div>
            <button class="modal-close" onclick="cerrarModalNegocio()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="padding:24px">
            <input type="hidden" id="negocioId">
            <div class="form-group">
                <label class="form-label">Nombre del negocio / trabajo *</label>
                <input type="text" id="negocioNombre" class="form-input" placeholder="Ej: Sitio web tienda online" maxlength="150">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label class="form-label">Tipo de trabajo</label>
                    <select id="negocioTipo" class="form-select">
                        <option value="">— Selecciona —</option>
                        <option value="Diseño web">Diseño web</option>
                        <option value="Desarrollo web">Desarrollo web</option>
                        <option value="E-commerce">E-commerce</option>
                        <option value="Diseño gráfico">Diseño gráfico</option>
                        <option value="Marketing digital">Marketing digital</option>
                        <option value="SEO / Posicionamiento">SEO / Posicionamiento</option>
                        <option value="Redes sociales">Redes sociales</option>
                        <option value="Branding">Branding</option>
                        <option value="Fotografía / Video">Fotografía / Video</option>
                        <option value="Consultoría">Consultoría</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="negocioEstado" class="form-select">
                        <option value="activo">Activo</option>
                        <option value="en_progreso">En progreso</option>
                        <option value="pausado">Pausado</option>
                        <option value="completado">Completado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea id="negocioDescripcion" class="form-input" rows="2" placeholder="Breve descripción del trabajo acordado…" style="resize:vertical"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Monto acordado</label>
                    <input type="number" id="negocioMonto" class="form-input" placeholder="0" min="0" step="1000">
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" id="negocioFechaInicio" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha entrega</label>
                    <input type="date" id="negocioFechaEntrega" class="form-input">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notas internas</label>
                <textarea id="negocioNotas" class="form-input" rows="2" placeholder="Notas privadas sobre este negocio…" style="resize:vertical"></textarea>
            </div>
        </div>
        <div class="modal-footer" style="padding:16px 24px;border-top:1px solid #f1f5f9;display:flex;gap:10px;justify-content:flex-end">
            <button class="btn btn-ghost" onclick="cerrarModalNegocio()">Cancelar</button>
            <button class="btn btn-primary" id="negocioGuardarBtn" onclick="guardarNegocio()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Guardar negocio
            </button>
        </div>
    </div>
</div>

<!-- ── Modal Agregar Trabajo al Negocio ───────────────────────────────────── -->
<div class="modal-overlay" id="trabajoModal">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Agregar trabajo / pago</h3>
                <p style="font-size:12px;color:#94a3b8;margin:3px 0 0" id="trabajoModalSubtitle">Negocio</p>
            </div>
            <button class="modal-close" onclick="cerrarModalTrabajo()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="padding:20px 24px">
            <input type="hidden" id="trabajoNegocioId">
            <div class="form-group">
                <label class="form-label">Concepto / descripción del trabajo *</label>
                <input type="text" id="trabajoConcepto" class="form-input" placeholder="Ej: Diseño de logo, Anticipo desarrollo…">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Monto (COP) *</label>
                    <input type="number" id="trabajoMonto" class="form-input" placeholder="0" min="0" step="1000">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="trabajoEstado" class="form-select">
                        <option value="pendiente">Pendiente</option>
                        <option value="pagado">Pagado</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Fecha de vencimiento / entrega</label>
                <input type="date" id="trabajoFecha" class="form-input">
            </div>
        </div>
        <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f1f5f9;display:flex;gap:10px;justify-content:flex-end">
            <button class="btn btn-ghost" onclick="cerrarModalTrabajo()">Cancelar</button>
            <button class="btn btn-primary" id="trabajoGuardarBtn" onclick="guardarTrabajo()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Guardar trabajo
            </button>
        </div>
    </div>
</div>

<!-- Modal Historial / Novedades -->
<div class="modal-overlay" id="historialModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Historial / Novedades</h3>
                <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Registro de actividades del cliente</p>
            </div>
            <button class="modal-close" onclick="closeHistorialModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="noteFormModal" onsubmit="saveNoteFromModal(event)" style="margin-bottom:16px;display:flex;gap:8px">
                <input type="text" id="noteTextModal" class="form-input" placeholder="Nueva nota..." style="font-size:13px;padding:9px" required>
                <button class="btn btn-secondary btn-icon" type="submit" style="width:36px;height:36px;flex-shrink:0">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                </button>
            </form>
            <div id="notesTimelineModal" style="display:grid;gap:10px;max-height:400px;overflow-y:auto;padding-right:4px">
                <p style="text-align:center;color:var(--color-text-light);font-size:12px">Cargando...</p>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Configuración de Notificaciones ─────────────────────────────────── -->
<div class="modal-overlay" id="notifModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:36px;height:36px;background:#f0fdf4;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="18" height="18" fill="none" stroke="#16a34a" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div>
                    <h3 class="modal-title" style="font-size:15px">Notificaciones de renovación</h3>
                    <p style="font-size:11px;color:#94a3b8;margin:2px 0 0">Correo automático al cliente antes del vencimiento</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeNotifModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;gap:16px">

            <!-- Toggle activa -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:10px;border:1.5px solid #e2e8f0">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#0f172a">Activar notificaciones</div>
                    <div style="font-size:11px;color:#94a3b8">Enviar recordatorio automático al correo del cliente</div>
                </div>
                <div style="position:relative;width:44px;height:24px;cursor:pointer;flex-shrink:0" onclick="toggleNotifActiva()">
                    <div id="notifTrack" style="width:44px;height:24px;border-radius:12px;background:#e2e8f0;transition:background .2s"></div>
                    <div id="notifThumb" style="position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:transform .2s"></div>
                </div>
            </div>

            <div id="notifFormBody" style="display:grid;gap:14px">
                <!-- Días antes -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:12px">Días de anticipación</label>
                    <div style="display:flex;align-items:center;gap:10px">
                        <input id="notifDias" type="number" min="1" max="90" value="15" class="form-input" style="width:90px;text-align:center;font-size:15px;font-weight:700;padding:8px">
                        <span style="font-size:12px;color:#64748b">días antes del vencimiento</span>
                    </div>
                </div>

                <!-- Hora -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:12px">Hora de envío</label>
                    <input id="notifHora" type="time" value="08:00" class="form-input" style="max-width:140px">
                </div>

                <!-- Asunto personalizado -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:12px">Asunto del correo <span style="font-weight:400;color:#94a3b8">(opcional)</span></label>
                    <input id="notifAsunto" type="text" class="form-input" placeholder="Ej: ¡Tu servicio está por vencer!" style="font-size:13px">
                    <p style="margin:3px 0 0;font-size:10px;color:#94a3b8">Si está vacío se usa el asunto predeterminado</p>
                </div>

                <!-- Plantilla de mensaje -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:12px">Usar plantilla de mensaje <span style="font-weight:400;color:#94a3b8">(opcional)</span></label>
                    <div style="position:relative">
                        <select id="notifPlantillaSelect" onchange="aplicarPlantillaNotif()"
                            style="width:100%;padding:8px 36px 8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;cursor:pointer;appearance:none;-webkit-appearance:none;outline:none;transition:border-color .15s"
                            onfocus="this.style.borderColor='#c9f31d'" onblur="this.style.borderColor='#e2e8f0'">
                            <option value="">— Seleccionar plantilla —</option>
                        </select>
                        <svg width="14" height="14" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="2.5" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div id="notifPlantillaPreview" style="display:none;margin-top:8px;padding:10px 12px;background:#f8fafc;border:1.5px solid #cbd5e1;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;line-height:1.5;max-height:80px;overflow-y:auto"></div>
                </div>

                <!-- Mensaje adicional -->
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:12px">Mensaje personalizado <span style="font-weight:400;color:#94a3b8">(editable)</span></label>
                    <textarea id="notifMensaje" class="form-input" rows="3" placeholder="Selecciona una plantilla arriba o escribe un mensaje personalizado que aparecerá en el correo..." style="font-size:13px;resize:vertical;min-height:70px"></textarea>
                </div>

                <!-- Info destino -->
                <div style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;background:#eff6ff;border-radius:8px;border:1px solid #dbeafe">
                    <svg width="14" height="14" fill="none" stroke="#3b82f6" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;margin-top:1px"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div style="font-size:11px;color:#1d4ed8;line-height:1.5">
                        El correo se enviará a <strong><?= htmlspecialchars($cliente['email_facturacion'] ?: '(sin email)') ?></strong><br>
                        Incluirá todos los servicios activos que venzan en el período configurado.
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="justify-content:space-between">
            <button onclick="enviarNotifPrueba()" id="btnNotifPrueba"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f8fafc;border:1.5px solid #cbd5e1;color:#0f172a;border:1.5px solid #e2e8f0;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;transition:all .15s"
                onmouseenter="this.style.borderColor='#94a3b8'" onmouseleave="this.style.borderColor='#e2e8f0'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Enviar prueba
            </button>
            <div style="display:flex;gap:8px">
                <button class="btn btn-outline" onclick="closeNotifModal()">Cancelar</button>
                <button onclick="guardarNotifConfig()" id="btnGuardarNotif"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#1e293b;color:#ffffff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s"
                    onmouseenter="this.style.filter='brightness(1.3)'" onmouseleave="this.style.filter=''">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Credencial ──────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="credModal">
    <div class="modal" style="max-width:730px">
        <div class="modal-header" style="background:linear-gradient(135deg,#0f766e,#0d9488);border-radius:var(--radius-lg) var(--radius-lg) 0 0">
            <div>
                <h3 class="modal-title" style="color:#fff" id="credModalTitle">Nueva credencial</h3>
                <p style="font-size:12px;color:rgba(255,255,255,0.7);margin:2px 0 0">Inventario de accesos del cliente</p>
            </div>
            <button class="modal-close" onclick="closeCredModal()" style="color:#fff;opacity:.8">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;gap:14px">
            <input type="hidden" id="credId">
            <div class="form-group" style="margin:0">
                <label class="form-label">Nombre / Descripción <span style="color:#ef4444">*</span></label>
                <input id="credNombre" class="form-input" type="text" placeholder="Ej: Correo principal, cPanel, WordPress...">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Correo</label>
                <input id="credCorreo" class="form-input" type="email" placeholder="usuario@dominio.com">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Clave / Contraseña</label>
                <div style="position:relative">
                    <input id="credClave" class="form-input" type="password" placeholder="Contraseña" style="padding-right:40px">
                    <button type="button" onclick="toggleCredPass()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;display:flex" title="Mostrar/ocultar">
                        <svg id="eyeIcon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeCredModal()">Cancelar</button>
            <button class="btn btn-secondary" onclick="saveCred()" id="btnSaveCred"
                style="background:#0d9488;color:#fff;border:none"
                onmouseenter="this.style.filter='brightness(1.1)'" onmouseleave="this.style.filter=''">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Guardar credencial
            </button>
        </div>
    </div>
</div>

<style>
#clienteEditor:focus { outline: none; }
#clienteEditor h3 { font-size:15px; font-weight:700; color:#0f172a; margin:8px 0 4px; }
#clienteEditor ul, #clienteEditor ol { padding-left:20px; margin:4px 0; }
#clienteEditor li { margin:2px 0; }
#clienteEditor p { margin:4px 0; }
</style>

<script>
// ── Collapse panels ────────────────────────────────────────────────────────────
function togglePanel(panelId, arrowId) {
    const panel = document.getElementById(panelId);
    const arrow = document.getElementById(arrowId);
    const open  = panel.style.display !== 'none';
    panel.style.display = open ? 'none' : 'block';
    arrow.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
}

// ── Editor de detalles ─────────────────────────────────────────────────────────
let editorDirty = false;

function editorCmd(cmd) {
    const editor = document.getElementById('clienteEditor');
    editor.focus();
    if (cmd === 'formatBlock_h3') {
        document.execCommand('formatBlock', false, 'h3');
    } else if (cmd === 'formatBlock_p') {
        document.execCommand('formatBlock', false, 'p');
    } else {
        document.execCommand(cmd, false, null);
    }
    markEditorDirty();
}

function markEditorDirty() {
    editorDirty = true;
    document.getElementById('editorSavedAt').textContent = '● Sin guardar';
}

async function loadEditor() {
    try {
        const r = await fetch(`api/cliente_editor.php?cliente_id=${clienteId}`);
        const d = await r.json();
        const el = document.getElementById('clienteEditor');
        if (d.contenido) {
            el.innerHTML = d.contenido;
            if (d.updated_at) {
                const dt = new Date(d.updated_at);
                document.getElementById('editorSavedAt').textContent =
                    'Guardado ' + dt.toLocaleDateString('es-CO', {day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
            }
        } else {
            el.innerHTML = '<p style="color:#94a3b8">Escribe aquí los detalles del cliente...</p>';
        }
        editorDirty = false;
    } catch(e) {}
}

async function saveEditor() {
    const btn = document.getElementById('btnSaveEditor');
    const contenido = document.getElementById('clienteEditor').innerHTML;
    btn.textContent = 'Guardando...';
    btn.disabled = true;
    try {
        const r = await fetch('api/cliente_editor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId, contenido })
        });
        const d = await r.json();
        if (d.success) {
            editorDirty = false;
            const now = new Date();
            document.getElementById('editorSavedAt').textContent =
                'Guardado ' + now.toLocaleDateString('es-CO', {day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
            showToast('Detalles guardados', 'success');
        } else {
            showToast(d.error || 'Error al guardar', 'error');
        }
    } catch(e) { showToast('Error de conexión', 'error'); }
    finally {
        btn.innerHTML = '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar';
        btn.disabled = false;
    }
}

// Aviso si sale sin guardar
window.addEventListener('beforeunload', e => {
    if (editorDirty) { e.preventDefault(); e.returnValue = ''; }
});

// ── Inventario de credenciales ─────────────────────────────────────────────────
let credenciales = [];

async function loadCredenciales() {
    try {
        const r = await fetch(`api/cliente_credenciales.php?cliente_id=${clienteId}`);
        const d = await r.json();
        if (d.success) renderCredenciales(d.data);
    } catch(e) {}
}

function renderCredenciales(list) {
    credenciales = list;
    const tbody = document.getElementById('credencialesTable');
    if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--color-text-light)">Sin credenciales registradas. Haz clic en <strong>Agregar</strong> para añadir.</td></tr>';
        return;
    }
    tbody.innerHTML = list.map(c => {
        const fecha = new Date(c.created_at).toLocaleDateString('es-CO', {day:'2-digit',month:'short',year:'numeric'});
        return `
        <tr>
            <td><span style="font-weight:700;color:#0f172a;font-size:12px">${escapeHtml(c.nombre)}</span></td>
            <td style="font-size:12px;color:#0f172a">${escapeHtml(c.correo) || '<span style="color:#cbd5e1">—</span>'}</td>
            <td>
                <div style="display:flex;align-items:center;gap:6px">
                    <span class="cred-pass" id="pass-${c.id}" style="font-family:monospace;font-size:12px;letter-spacing:2px;color:#0f172a">${c.clave ? '••••••••' : '<span style=\'color:#cbd5e1\'>—</span>'}</span>
                    ${c.clave ? `<button onclick="togglePassView(${c.id},'${escapeJs(c.clave)}')" style="background:none;border:none;cursor:pointer;padding:2px;color:#94a3b8;display:flex" title="Mostrar"><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                    <button onclick="copiarClave('${escapeJs(c.clave)}')" style="background:none;border:none;cursor:pointer;padding:2px;color:#94a3b8;display:flex" title="Copiar"><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>` : ''}
                </div>
            </td>
            <td style="font-size:11px;color:#94a3b8">${fecha}</td>
            <td>
                <div style="display:flex;gap:4px;justify-content:flex-end">
                    <button onclick="editCred(${c.id})" style="width:28px;height:28px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#3b82f6;transition:all .15s" title="Editar" onmouseenter="this.style.background='#eff6ff'" onmouseleave="this.style.background='#fff'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="deleteCred(${c.id})" style="width:28px;height:28px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#ef4444;transition:all .15s" title="Eliminar" onmouseenter="this.style.background='#fef2f2'" onmouseleave="this.style.background='#fff'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function togglePassView(id, pass) {
    const el = document.getElementById('pass-' + id);
    if (el.dataset.visible === '1') {
        el.textContent = '••••••••';
        el.dataset.visible = '0';
    } else {
        el.textContent = pass;
        el.dataset.visible = '1';
    }
}

function copiarClave(clave) {
    navigator.clipboard.writeText(clave).then(() => showToast('Contraseña copiada', 'success'));
}

function openCredModal(id = null) {
    document.getElementById('credId').value    = '';
    document.getElementById('credNombre').value = '';
    document.getElementById('credCorreo').value = '';
    document.getElementById('credClave').value  = '';
    document.getElementById('credModalTitle').textContent = 'Nueva credencial';
    if (id) {
        const c = credenciales.find(x => x.id == id);
        if (c) {
            document.getElementById('credId').value     = c.id;
            document.getElementById('credNombre').value  = c.nombre;
            document.getElementById('credCorreo').value  = c.correo;
            document.getElementById('credClave').value   = c.clave;
            document.getElementById('credModalTitle').textContent = 'Editar credencial';
        }
    }
    document.getElementById('credModal').classList.add('show');
}

function editCred(id) { openCredModal(id); }

function closeCredModal() {
    document.getElementById('credModal').classList.remove('show');
}

function toggleCredPass() {
    const inp = document.getElementById('credClave');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

async function saveCred() {
    const btn  = document.getElementById('btnSaveCred');
    const id   = document.getElementById('credId').value;
    const nombre = document.getElementById('credNombre').value.trim();
    if (!nombre) { showToast('El nombre es obligatorio', 'warning'); return; }

    const payload = {
        id:          id ? parseInt(id) : undefined,
        cliente_id:  clienteId,
        nombre:      nombre,
        correo:      document.getElementById('credCorreo').value.trim(),
        clave:       document.getElementById('credClave').value.trim(),
    };

    btn.disabled = true;
    btn.textContent = 'Guardando...';
    try {
        const method = id ? 'PUT' : 'POST';
        const r = await fetch('api/cliente_credenciales.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.success) {
            showToast(id ? 'Credencial actualizada' : 'Credencial guardada', 'success');
            closeCredModal();
            loadCredenciales();
        } else {
            showToast(d.error || 'Error al guardar', 'error');
        }
    } catch(e) { showToast('Error de conexión', 'error'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar credencial';
    }
}

async function deleteCred(id) {
    const ok = await confirmAction('¿Eliminar credencial?', 'Esta acción no se puede deshacer.');
    if (!ok) return;
    const r = await fetch(`api/cliente_credenciales.php?id=${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.success) { showToast('Credencial eliminada', 'success'); loadCredenciales(); }
    else showToast(d.error || 'Error', 'error');
}

// ── Configuración de Notificaciones ──────────────────────────────────────────
let notifActiva = true;

// ── Plantillas en modal de notificaciones ─────────────────────────────────────
let notifPlantillas = [];

async function loadPlantillasNotif() {
    if (notifPlantillas.length) return; // ya cargadas
    try {
        const r = await fetch('api/mensajes_plantillas.php?filtro=todas');
        const d = await r.json();
        const lista = Array.isArray(d) ? d : (d.data || []);
        notifPlantillas = lista.filter(p => p.activa != 0);

        const sel = document.getElementById('notifPlantillaSelect');
        // Agrupar por categoría
        const grupos = {};
        notifPlantillas.forEach(p => {
            const cat = p.categoria || 'General';
            if (!grupos[cat]) grupos[cat] = [];
            grupos[cat].push(p);
        });
        sel.innerHTML = '<option value="">— Seleccionar plantilla —</option>';
        Object.entries(grupos).forEach(([cat, items]) => {
            const grp = document.createElement('optgroup');
            grp.label = cat;
            items.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.nombre;
                grp.appendChild(opt);
            });
            sel.appendChild(grp);
        });
    } catch(e) {}
}

function aplicarPlantillaNotif() {
    const sel  = document.getElementById('notifPlantillaSelect');
    const id   = sel.value;
    const prev = document.getElementById('notifPlantillaPreview');
    const txt  = document.getElementById('notifMensaje');

    if (!id) { prev.style.display = 'none'; return; }

    const p = notifPlantillas.find(x => x.id == id);
    if (!p) return;

    // Mostrar preview
    prev.style.display = 'block';
    prev.textContent   = p.contenido;

    // Copiar contenido al textarea (limpio de variables {{...}})
    txt.value = p.contenido;
}

async function loadNotifConfig() {
    try {
        const r = await fetch(`api/cliente_notif_config.php?cliente_id=${clienteId}`);
        const d = await r.json();
        if (!d.success) return;
        const cfg = d.data;

        notifActiva = cfg.activa == 1;
        document.getElementById('notifDias').value    = cfg.dias_antes || 15;
        document.getElementById('notifHora').value    = (cfg.hora_envio || '08:00').substring(0,5);
        document.getElementById('notifAsunto').value  = cfg.asunto_personalizado || '';
        document.getElementById('notifMensaje').value = cfg.mensaje_personalizado || '';
        actualizarToggleNotif();

        const label = document.getElementById('notifStatusLabel');
        if (label) label.textContent = notifActiva
            ? `Activa · ${cfg.dias_antes || 15} días antes`
            : 'Inactiva';
    } catch(e) {}
}

function toggleNotifActiva() {
    notifActiva = !notifActiva;
    actualizarToggleNotif();
}
function actualizarToggleNotif() {
    const track = document.getElementById('notifTrack');
    const thumb = document.getElementById('notifThumb');
    const form  = document.getElementById('notifFormBody');
    track.style.background   = notifActiva ? '#16a34a' : '#e2e8f0';
    thumb.style.transform    = notifActiva ? 'translateX(20px)' : 'translateX(0)';
    form.style.opacity       = notifActiva ? '1' : '.45';
    form.style.pointerEvents = notifActiva ? '' : 'none';
}

function openNotifModal() {
    loadNotifConfig();
    loadPlantillasNotif();
    document.getElementById('notifPlantillaSelect').value = '';
    document.getElementById('notifPlantillaPreview').style.display = 'none';
    document.getElementById('notifModal').classList.add('show');
}
function closeNotifModal() {
    document.getElementById('notifModal').classList.remove('show');
}

async function guardarNotifConfig() {
    const btn = document.getElementById('btnGuardarNotif');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const r = await fetch('api/cliente_notif_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cliente_id:             clienteId,
                activa:                 notifActiva,
                dias_antes:             parseInt(document.getElementById('notifDias').value) || 15,
                hora_envio:             document.getElementById('notifHora').value,
                asunto_personalizado:   document.getElementById('notifAsunto').value.trim(),
                mensaje_personalizado:  document.getElementById('notifMensaje').value.trim(),
            })
        });
        const d = await r.json();
        if (d.success) {
            showToast('✓ Configuración de notificaciones guardada', 'success');
            closeNotifModal();
            loadNotifConfig();
        } else showToast(d.error || 'Error al guardar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Guardar';
    }
}

async function enviarNotifPrueba() {
    const btn = document.getElementById('btnNotifPrueba');
    btn.disabled = true;
    btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Enviando...';
    try {
        const r = await fetch(`api/enviar_notif_renovacion.php?cliente_id=${clienteId}&test=1`, {
            credentials: 'include'
        });
        let d;
        try { d = await r.json(); }
        catch { showToast('Respuesta inesperada del servidor (status ' + r.status + ')', 'error'); return; }
        if (d.success) showToast('✓ Correo de prueba enviado al cliente', 'success');
        else showToast(d.error || 'Error al enviar', 'error');
    } catch(e) {
        showToast('Error de red: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Enviar prueba';
    }
}

// ── Cargar contador de tareas pendientes ──────────────────────────────────────
let tareasClientePendientes = [];

function loadTareasIndicador() {
    fetch(`api/tareas.php?estado=pendiente&cliente_id=${clienteId}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data) return;
            const tareas = Array.isArray(res.data) ? res.data : [];
            tareasClientePendientes = tareas;
            const count = tareas.length;

            const indicador = document.getElementById('tareasIndicador');
            const countEl = document.getElementById('countTareasIndicador');

            if (count > 0) {
                countEl.textContent = count > 9 ? '9+' : count;
                indicador.style.display = 'block';
                // Hacer clickeable el botón
                const btn = indicador.querySelector('button');
                if (btn) {
                    btn.onclick = () => verDetallesTareaCliente(tareas[0].id);
                }
            } else {
                indicador.style.display = 'none';
            }
        })
        .catch(err => console.log('Error al cargar tareas:', err));
}

// ── Detalles de tarea en modal ────────────────────────────────────────────────
function verDetallesTareaCliente(id) {
    fetch(`api/tareas.php?id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data) throw new Error('Tarea no encontrada');
            const t = res.data;

            // Llenar modal
            document.getElementById('detalleTareaClienteTitulo').textContent = escapeHtmlClient(t.titulo);

            // Prioridad
            const _prioCfg = {
                alta:  { bg:'#fee2e2', clr:'#dc2626', label:'Alta',  op:['1','1','1']        },
                media: { bg:'#fef3c7', clr:'#d97706', label:'Media', op:['1','1','.3']       },
                baja:  { bg:'#dcfce7', clr:'#16a34a', label:'Baja',  op:['1','.3','.3']      },
            };
            const _pc = _prioCfg[t.prioridad] || _prioCfg.media;
            const _bars = (op) => `<svg width="12" height="10" viewBox="0 0 12 10" fill="currentColor" style="flex-shrink:0">
                <rect x="0" y="5" width="2.5" height="5" rx="1" opacity="${op[0]}"/>
                <rect x="4.5" y="2.5" width="2.5" height="7.5" rx="1" opacity="${op[1]}"/>
                <rect x="9" y="0" width="2.5" height="10" rx="1" opacity="${op[2]}"/>
            </svg>`;
            document.getElementById('detallePrioridadCliente').innerHTML =
                `<span style="display:inline-flex;align-items:center;gap:5px;background:${_pc.bg};color:${_pc.clr};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700">${_bars(_pc.op)}${_pc.label}</span>`;

            // Estado - Badge de solo lectura
            const estBg  = { pendiente:'#f1f5f9', en_progreso:'#dbeafe', revision:'#fef3c7', completado:'#dcfce7' };
            const estClr = { pendiente:'#475569', en_progreso:'#2563eb', revision:'#d97706', completado:'#16a34a' };
            const estLabel = { pendiente:'Pendiente', en_progreso:'En Proceso', revision:'Revisión', completado:'Completado' };
            document.getElementById('detalleEstadoCliente').innerHTML =
                `<span style="display:inline-flex;align-items:center;gap:5px;background:${estBg[t.estado]};color:${estClr[t.estado]};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700">
                    ${estLabel[t.estado]}
                </span>`;
            // Guardar ID para cambio de estado
            document.getElementById('detalleEstadoCliente').dataset.tareaId = t.id;

            // Descripción
            document.getElementById('detalleDescripcionCliente').textContent = t.descripcion || '—';

            // Responsable
            document.getElementById('detalleResponsableCliente').textContent = t.responsable || '—';

            // Fecha límite
            if (t.fecha_limite) {
                const fl = new Date(t.fecha_limite + 'T00:00:00');
                const today = new Date(); today.setHours(0,0,0,0);
                const vencido = fl < today && t.estado !== 'completado';
                const fechaTxt = fl.toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'});
                document.getElementById('detalleFechaCliente').innerHTML =
                    `<span style="color:${vencido ? '#ef4444' : '#475569'};font-weight:${vencido ? '700' : '400'}">
                        ${vencido ? '<span style="font-size:10px;margin-right:2px">!</span>' : ''}${fechaTxt}
                    </span>`;
            } else {
                document.getElementById('detalleFechaCliente').textContent = '—';
            }

            // Notas
            document.getElementById('detalleNotasCliente').textContent = t.notas || '—';

            // Guardar ID actual
            document.getElementById('btnIrATasksPage').dataset.tareaId = t.id;

            // Mostrar modal
            document.getElementById('detallesTareaClienteModal').classList.add('show');
        })
        .catch(err => {
            if (typeof showToast === 'function') showToast(err.message || 'Error', 'error');
        });
}

function closeDetallesTareaClienteModal() {
    document.getElementById('detallesTareaClienteModal').classList.remove('show');
}

async function eliminarTareaCliente(id, titulo) {
    const ok = await confirmAction(
        `La tarea "${titulo}" se moverá al historial de eliminadas.`,
        { title: '¿Eliminar tarea?' }
    );
    if (!ok) return;
    try {
        const r = await fetch(`api/tareas.php?id=${id}`, { method: 'DELETE' });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'Error');
        showToast('Tarea eliminada', 'success');
        loadTareasCliente();
        loadTareasIndicador();
    } catch(e) { showToast(e.message || 'Error al eliminar', 'error'); }
}

async function eliminarTareaClienteDesdeModal() {
    const btn = document.getElementById('btnIrATasksPage');
    const id  = btn?.dataset.tareaId;
    const titulo = document.getElementById('detalleTareaClienteTitulo')?.textContent || '';
    if (!id) return;
    closeDetallesTareaClienteModal();
    await eliminarTareaCliente(id, titulo);
}

function goToTaskPage() {
    const id = document.getElementById('btnIrATasksPage').dataset.tareaId;
    closeDetallesTareaClienteModal();
    if (id) {
        window.location.href = `tareas.php`;
    }
}

function cambiarEstadoTareaCliente(nuevoEstado) {
    const selectEl = document.getElementById('detalleEstadoCliente');
    const tareaId = selectEl.dataset.tareaId;

    if (!tareaId || !nuevoEstado) return;

    fetch('api/tareas.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(tareaId), estado: nuevoEstado })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (typeof showToast === 'function') showToast('Estado actualizado', 'success');
            loadTareasIndicador();
        } else {
            throw new Error(res.error || 'Error al actualizar estado');
        }
    })
    .catch(err => {
        if (typeof showToast === 'function') showToast(err.message, 'error');
        // Revertir select al valor anterior
        verDetallesTareaCliente(tareaId);
    });
}

function escapeHtmlClient(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Paquetes globales (para mostrar composición en tabla de servicios) ──────
window._pkgCatalogGlobal = [];
async function loadPkgCatalogGlobal() {
    try {
        const r = await fetch('api/paquetes.php');
        const d = await r.json();
        if (d.success) window._pkgCatalogGlobal = d.data || [];
    } catch(e) {}
}
function getPkgByName(name) {
    return (window._pkgCatalogGlobal || []).find(p => p.nombre === name) || null;
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', () => {
    loadPkgCatalogGlobal();
    loadEditor();
    loadCredenciales();
    loadNotifConfig();
    loadTareasIndicador();
    loadNegocios();
    loadTareasCliente();
    loadRutStatus();
});

// Actualizar indicador después de crear tarea
const originalCrearTarea = window.crearTarea;
window.crearTarea = function() {
    originalCrearTarea.call(this);
    // Recargar el indicador y la sección de tareas
    setTimeout(() => { loadTareasIndicador(); loadTareasCliente(); }, 1000);
};

// ══════════════════════════════════════════════════════════════════════════════
// SECCIÓN TAREAS DEL CLIENTE
// ══════════════════════════════════════════════════════════════════════════════

let _tareasClienteData = [];
let _tareasClienteFiltro = 'todos';

async function loadTareasCliente() {
    try {
        const r = await fetch(`api/tareas.php?cliente_id=${clienteId}&limite=100`);
        const d = await r.json();
        if (!d.success) return;
        _tareasClienteData = Array.isArray(d.data) ? d.data : [];
        renderTareasCliente();
        updateTareasBadges();
    } catch(e) { console.error('Error cargando tareas:', e); }
}

function updateTareasBadges() {
    const pend = _tareasClienteData.filter(t => t.estado === 'pendiente' || t.estado === 'en_progreso').length;
    const total = _tareasClienteData.length;
    const bPend = document.getElementById('tareasClienteBadgePend');
    const bTot  = document.getElementById('tareasClienteBadgeTotal');
    if (pend > 0) {
        bPend.textContent = pend + ' pendiente' + (pend !== 1 ? 's' : '');
        bPend.style.display = 'inline';
    } else {
        bPend.style.display = 'none';
    }
    if (total > 0) {
        bTot.textContent = total + ' tarea' + (total !== 1 ? 's' : '');
        bTot.style.display = 'inline';
    } else {
        bTot.style.display = 'none';
    }
}

function filtrarTareasCliente(filtro, btn) {
    _tareasClienteFiltro = filtro;
    document.querySelectorAll('.tarea-filtro-btn').forEach(b => {
        const active = b === btn;
        b.style.background = active ? '#0f172a' : '#fff';
        b.style.color = active ? '#fff' : '#475569';
        b.style.fontWeight = active ? '700' : '600';
    });
    renderTareasCliente();
}

function renderTareasCliente() {
    const lista = document.getElementById('tareasClienteLista');
    const filtro = _tareasClienteFiltro;
    let tareas = _tareasClienteData;
    if (filtro === 'todos') {
        // "Todas" excluye canceladas — para verlas usar filtro explícito
        tareas = tareas.filter(t => t.estado !== 'cancelado');
    } else {
        tareas = tareas.filter(t => t.estado === filtro);
    }

    if (!tareas.length) {
        lista.innerHTML = `<div style="text-align:center;padding:32px 20px">
            <svg width="36" height="36" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 10px;display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p style="font-size:12px;color:#94a3b8;margin:0">${filtro === 'todos' ? 'Sin tareas registradas para este cliente.' : 'Sin tareas con este estado.'}</p>
            ${filtro === 'todos' ? `<button onclick="openTareasModal()" style="margin-top:12px;padding:7px 16px;background:#4f46e5;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer">+ Agregar tarea</button>` : ''}
        </div>`;
        return;
    }

    const _mkPrioBars = (clr, op) =>
        `<svg width="10" height="9" viewBox="0 0 12 10" fill="${clr}">` +
        `<rect x="0" y="5" width="2.5" height="5" rx="1" opacity="${op[0]}"/>` +
        `<rect x="4.5" y="2.5" width="2.5" height="7.5" rx="1" opacity="${op[1]}"/>` +
        `<rect x="9" y="0" width="2.5" height="10" rx="1" opacity="${op[2]}"/>` +
        `</svg>`;
    const prioData = {
        alta:  { bg:'#fee2e2', clr:'#dc2626', label:'Alta',  icon: _mkPrioBars('#dc2626',['1','1','1'])       },
        media: { bg:'#fef3c7', clr:'#d97706', label:'Media', icon: _mkPrioBars('#d97706',['1','1','.3'])      },
        baja:  { bg:'#dcfce7', clr:'#16a34a', label:'Baja',  icon: _mkPrioBars('#16a34a',['1','.3','.3'])     },
    };
    const estadoData = {
        pendiente:   { bg:'#fff7ed', clr:'#ea580c', label:'Pendiente', icon:'⏳' },
        en_progreso: { bg:'#eff6ff', clr:'#2563eb', label:'En progreso', icon:'🔄' },
        revision:    { bg:'#f5f3ff', clr:'#7c3aed', label:'Revisión', icon:'🔍' },
        completado:  { bg:'#f0fdf4', clr:'#16a34a', label:'Completado', icon:'✅' },
        cancelado:   { bg:'#f8fafc', clr:'#94a3b8', label:'Cancelado', icon:'✗' },
    };

    lista.innerHTML = tareas.map(t => {
        const prio = prioData[t.prioridad] || prioData.media;
        const est  = estadoData[t.estado]  || estadoData.pendiente;
        const fechaStr = t.fecha_limite
            ? (() => {
                const d = new Date(t.fecha_limite + 'T12:00:00');
                const hoy = new Date(); hoy.setHours(0,0,0,0);
                const diff = Math.round((d - hoy) / 86400000);
                const label = d.toLocaleDateString('es-CO', {day:'2-digit', month:'short'});
                const color = diff < 0 ? '#dc2626' : diff === 0 ? '#ea580c' : diff <= 3 ? '#d97706' : '#64748b';
                const prefix = diff < 0 ? 'Venció ' : diff === 0 ? 'Hoy' : '';
                return `<span style="color:${color};font-weight:${diff <= 0 ? '700' : '500'}">${prefix}${label}</span>`;
            })()
            : '<span style="color:#cbd5e1">Sin fecha</span>';
        const completado = t.estado === 'completado' || t.estado === 'cancelado';
        return `<div style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-bottom:1px solid #f1f5f9;transition:background .12s" onmouseenter="this.style.background='#fafafa'" onmouseleave="this.style.background=''">
            <!-- Checkbox circular -->
            <button onclick="toggleTareaEstado(${t.id}, '${t.estado}')" title="${completado ? 'Marcar como pendiente' : 'Marcar como completada'}"
                style="width:20px;height:20px;border-radius:50%;border:2px solid ${completado ? '#22c55e' : '#cbd5e1'};background:${completado ? '#22c55e' : 'transparent'};flex-shrink:0;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;padding:0"
                onmouseenter="this.style.borderColor='#22c55e'" onmouseleave="this.style.borderColor='${completado ? '#22c55e' : '#cbd5e1'}'">
                ${completado ? '<svg width="10" height="10" fill="none" stroke="#fff" viewBox="0 0 24 24" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' : ''}
            </button>
            <!-- Contenido -->
            <div style="flex:1;min-width:0;cursor:pointer" onclick="verDetallesTareaCliente(${t.id})">
                <div style="font-size:12px;font-weight:${completado ? '500' : '700'};color:${completado ? '#94a3b8' : '#0f172a'};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;${completado ? 'text-decoration:line-through' : ''}">${escapeHtmlClient(t.titulo)}</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:3px;flex-wrap:wrap">
                    <span style="font-size:10px;background:${est.bg};color:${est.clr};padding:2px 7px;border-radius:20px;font-weight:700">${est.icon} ${est.label}</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;background:${prio.bg};color:${prio.clr};padding:2px 7px;border-radius:20px;font-size:10px;font-weight:700">${prio.icon}${prio.label}</span>
                    <span style="font-size:10px;color:#94a3b8">${fechaStr}</span>
                </div>
            </div>
            <!-- Eliminar -->
            <button onclick="event.stopPropagation();eliminarTareaCliente(${t.id},'${escapeHtmlClient(t.titulo).replace(/'/g,'&#39;')}')" title="Eliminar tarea"
                style="width:26px;height:26px;border:none;background:transparent;cursor:pointer;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#C6C2BB;flex-shrink:0;transition:all .12s"
                onmouseenter="this.style.background='#FEE2E2';this.style.color='#B0382F'"
                onmouseleave="this.style.background='transparent';this.style.color='#C6C2BB'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>`;
    }).join('');
}

async function toggleTareaEstado(id, estadoActual) {
    const nuevoEstado = (estadoActual === 'completado') ? 'pendiente' : 'completado';
    try {
        const r = await fetch('api/tareas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, estado: nuevoEstado })
        });
        const d = await r.json();
        if (d.success) {
            // Actualizar localmente sin recargar todo
            const tarea = _tareasClienteData.find(t => t.id == id);
            if (tarea) tarea.estado = nuevoEstado;
            renderTareasCliente();
            updateTareasBadges();
            loadTareasIndicador();
        } else {
            showToast(d.error || 'Error al actualizar', 'error');
        }
    } catch(e) { showToast('Error al actualizar tarea', 'error'); }
}

// ══════════════════════════════════════════════════════════════════════════════
// BOTÓN Y MODAL RUT
// ══════════════════════════════════════════════════════════════════════════════

let _rutUrl = null;

async function loadRutStatus() {
    try {
        const r = await fetch(`api/cliente_rut.php?cliente_id=${clienteId}`);
        const d = await r.json();
        _rutUrl = d.rut_url || null;
        const dot   = document.getElementById('btnRutDot');
        const label = document.getElementById('btnRutLabel');
        const btn   = document.getElementById('btnRut');
        if (_rutUrl) {
            dot.style.display   = 'inline-block';
            label.textContent   = 'RUT ✓';
            const rutMime = _rutUrl.match(/\.(pdf)$/i) ? 'application/pdf'
                          : _rutUrl.match(/\.(png|jpg|jpeg|webp|gif)$/i) ? 'image/' + _rutUrl.split('.').pop().toLowerCase()
                          : '';
            btn.dataset.purl  = _rutUrl;
            btn.dataset.pmime = rutMime;
        } else {
            dot.style.display   = 'none';
            label.textContent   = 'RUT';
            delete btn.dataset.purl;
            delete btn.dataset.pmime;
        }
    } catch(e) {}
}

function openRutModal() {
    const body = document.getElementById('rutModalBody');
    const footer = document.getElementById('rutModalFooter');

    if (_rutUrl) {
        const isPdf = _rutUrl.toLowerCase().endsWith('.pdf');
        const previewH = 'calc(100vh - 230px)';
        body.innerHTML = `
            <div>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin-bottom:12px">
                    <svg width="14" height="14" fill="none" stroke="#16a34a" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span style="font-size:12px;color:#15803d;font-weight:600">RUT cargado</span>
                </div>
                ${isPdf
                    ? `<iframe src="${_rutUrl}" style="width:100%;height:${previewH};min-height:420px;border:1.5px solid #e2e8f0;border-radius:8px;display:block" title="Vista previa RUT"></iframe>`
                    : `<img src="${_rutUrl}" alt="RUT" style="width:100%;height:${previewH};min-height:420px;object-fit:contain;border:1.5px solid #e2e8f0;border-radius:8px;display:block;background:#f8fafc">`
                }
            </div>`;
        footer.innerHTML = `
            <button class="btn btn-ghost" onclick="closeRutModal()">Cerrar</button>
            <label class="btn btn-outline" style="cursor:pointer;gap:6px">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Cambiar RUT
                <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" style="display:none" onchange="subirRut(this)">
            </label>
            <a href="${_rutUrl}" download class="btn btn-outline" style="gap:6px;text-decoration:none">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4"/></svg>
                Descargar
            </a>
            <button onclick="eliminarRut()" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;background:#fee2e2;color:#dc2626;border:none;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.filter='brightness(.95)'" onmouseleave="this.style.filter=''">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Eliminar
            </button>`;
    } else {
        body.innerHTML = `
            <div style="text-align:center;padding:28px 20px">
                <div style="width:52px;height:52px;background:#f1f5f9;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                    <svg width="24" height="24" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p style="font-size:13px;font-weight:600;color:#0f172a;margin:0 0 4px">Sin RUT cargado</p>
                <p style="font-size:12px;color:#94a3b8;margin:0 0 16px">Adjunta el RUT del cliente para tenerlo siempre a mano.</p>
                <label class="btn btn-primary" style="cursor:pointer;gap:8px;display:inline-flex;align-items:center">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 4v16m8-8H4"/></svg>
                    Subir RUT (PDF o imagen)
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" style="display:none" onchange="subirRut(this)">
                </label>
                <p style="font-size:10px;color:#cbd5e1;margin:10px 0 0">Formatos: PDF, JPG, PNG · Máx. 10 MB</p>
            </div>`;
        footer.innerHTML = `<button class="btn btn-ghost" onclick="closeRutModal()">Cerrar</button>`;
    }
    document.getElementById('rutModal').classList.add('show');
}

function closeRutModal() {
    document.getElementById('rutModal').classList.remove('show');
}

async function subirRut(input) {
    if (!input.files.length) return;
    const file = input.files[0];
    input.value = '';
    closeRutModal();
    showToast('Subiendo RUT...', 'info');
    const fd = new FormData();
    fd.append('rut', file);
    fd.append('cliente_id', clienteId);
    try {
        const r = await fetch('api/cliente_rut.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) {
            _rutUrl = d.rut_url;
            await loadRutStatus();
            showToast('RUT guardado correctamente', 'success');
        } else {
            showToast(d.error || 'Error al subir RUT', 'error');
        }
    } catch(e) { showToast('Error al subir RUT', 'error'); }
}

async function eliminarRut() {
    const ok = await confirmAction({
        title: 'Eliminar RUT',
        message: '¿Seguro que quieres eliminar el RUT del cliente? Esta acción no se puede deshacer.',
        confirmText: 'Eliminar',
        cancelText: 'Cancelar',
        type: 'danger'
    });
    if (!ok) return;
    closeRutModal();
    try {
        const r = await fetch(`api/cliente_rut.php?cliente_id=${clienteId}`, { method: 'DELETE' });
        const d = await r.json();
        if (d.success) {
            _rutUrl = null;
            await loadRutStatus();
            showToast('RUT eliminado', 'success');
        } else {
            showToast(d.error || 'Error al eliminar', 'error');
        }
    } catch(e) { showToast('Error al eliminar RUT', 'error'); }
}
</script>

<style>
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
