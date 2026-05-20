<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$currentPage  = 'cotizaciones';
$pageTitle    = 'Cotizaciones';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Cotizaciones</span>';
include __DIR__ . '/includes/header.php';

$pdo = db();
$filtro_estado = $_GET['estado'] ?? 'pendientes';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
?>


<!-- ── Acciones + Filtros ───────────────────────────────────── -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;align-items:center;justify-content:space-between">
    <?php
    $param_fechas = ($fecha_desde || $fecha_hasta) ? '&fecha_desde=' . urlencode($fecha_desde) . '&fecha_hasta=' . urlencode($fecha_hasta) : '';
    ?>
    <a href="?estado=pendientes<?php echo $param_fechas; ?>" style="padding:8px 14px;border-radius:var(--radius-sm);font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;transition:all .15s;background:<?php echo $filtro_estado === 'pendientes' ? '#c9f31d' : 'var(--color-border)'; ?>;color:<?php echo $filtro_estado === 'pendientes' ? '#000' : 'var(--color-text-muted)'; ?>;display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Pendientes por aprobar
    </a>
    <a href="?estado=aceptada<?php echo $param_fechas; ?>" style="padding:8px 14px;border-radius:var(--radius-sm);font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;transition:all .15s;background:<?php echo $filtro_estado === 'aceptada' ? '#10b981' : 'var(--color-border)'; ?>;color:<?php echo $filtro_estado === 'aceptada' ? '#fff' : 'var(--color-text-muted)'; ?>;display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Aceptada
    </a>
    <a href="?estado=todas<?php echo $param_fechas; ?>" style="padding:8px 14px;border-radius:var(--radius-sm);font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;transition:all .15s;background:<?php echo $filtro_estado === 'todas' ? 'var(--color-text)' : 'var(--color-border)'; ?>;color:<?php echo $filtro_estado === 'todas' ? '#fff' : 'var(--color-text-muted)'; ?>;display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        Todas
    </a>

    <!-- Búsqueda -->
    <div style="display:flex;align-items:center;gap:8px;border-left:1.5px solid var(--color-border);padding-left:12px;margin-left:8px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color:var(--color-text-muted);flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="inputBusqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar cliente, número..." style="padding:6px 10px;border:1.5px solid var(--color-border);border-radius:6px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s;min-width:160px" onkeyup="aplicarBusqueda(event)" onfocus="this.style.borderColor='var(--color-secondary)'" onblur="this.style.borderColor='var(--color-border)'">
        <?php if ($busqueda): ?>
        <button onclick="limpiarBusqueda()" style="padding:6px 10px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;transition:background .15s;flex-shrink:0" onmouseenter="this.style.background='#fecaca'" onmouseleave="this.style.background='#fee2e2'">✕</button>
        <?php endif; ?>
    </div>

    <!-- Filtros de Fechas -->
    <div style="display:flex;gap:8px;align-items:center;border-left:1.5px solid var(--color-border);padding-left:12px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color:var(--color-text-muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <input type="date" id="fechaDesde" value="<?php echo $fecha_desde; ?>" style="padding:6px 10px;border:1.5px solid var(--color-border);border-radius:6px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s" placeholder="Desde" onchange="aplicarFiltroFechas()" onfocus="this.style.borderColor='var(--color-secondary)'" onblur="this.style.borderColor='var(--color-border)'">
        <span style="color:var(--color-text-muted);font-size:12px">–</span>
        <input type="date" id="fechaHasta" value="<?php echo $fecha_hasta; ?>" style="padding:6px 10px;border:1.5px solid var(--color-border);border-radius:6px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s" placeholder="Hasta" onchange="aplicarFiltroFechas()" onfocus="this.style.borderColor='var(--color-secondary)'" onblur="this.style.borderColor='var(--color-border)'">
        <?php if ($fecha_desde || $fecha_hasta): ?>
        <button onclick="limpiarFiltroFechas()" style="padding:6px 10px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;transition:background .15s" onmouseenter="this.style.background='#fecaca'" onmouseleave="this.style.background='#fee2e2'">Limpiar</button>
        <?php endif; ?>
    </div>

    <div style="display:flex;gap:8px;margin-left:auto;align-items:center">
        <button id="btnEliminarSeleccionadas" onclick="eliminarSeleccionadas()" style="padding:8px 14px;background:#ef4444;color:#fff;border:none;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;display:none;align-items:center;gap:6px" onmouseenter="this.style.background='#dc2626'" onmouseleave="this.style.background='#ef4444'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Eliminar seleccionadas
        </button>
        <a href="cotizador.php" style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;background:var(--color-primary);color:#c9f31d;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;text-decoration:none;transition:background .15s" onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='var(--color-primary)'">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12"/></svg>
            Nueva cotización
        </a>
    </div>
</div>

<?php
// Construir condiciones de filtro
$condicion_fechas = '';
$condicion_busqueda = '';
$params = [];

if ($fecha_desde) {
    $condicion_fechas .= " AND DATE(created_at) >= ?";
    $params[] = $fecha_desde;
}
if ($fecha_hasta) {
    $condicion_fechas .= " AND DATE(created_at) <= ?";
    $params[] = $fecha_hasta;
}
if ($busqueda) {
    $condicion_busqueda = " AND (numero LIKE ? OR nombre_cliente LIKE ? OR email LIKE ?)";
    $busqueda_param = '%' . $busqueda . '%';
    array_push($params, $busqueda_param, $busqueda_param, $busqueda_param);
}

// Obtener cotizaciones según filtro
if ($filtro_estado === 'pendientes') {
    // Mostrar todas las NO aprobadas (estado != aceptada)
    $query = "
        SELECT id, numero, cliente_tipo, nombre_cliente, email, total, estado, moneda, created_at, vigencia_dias
        FROM cotizaciones
        WHERE estado != 'aceptada'" . $condicion_fechas . $condicion_busqueda . "
        ORDER BY created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
} elseif ($filtro_estado === 'todas') {
    $query = "
        SELECT id, numero, cliente_tipo, nombre_cliente, email, total, estado, moneda, created_at, vigencia_dias
        FROM cotizaciones
        WHERE 1=1" . $condicion_fechas . $condicion_busqueda . "
        ORDER BY created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
} else {
    $query = "
        SELECT id, numero, cliente_tipo, nombre_cliente, email, total, estado, moneda, created_at, vigencia_dias
        FROM cotizaciones
        WHERE estado = ?" . $condicion_fechas . $condicion_busqueda . "
        ORDER BY created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$filtro_estado], $params));
}
$cotizaciones = $stmt->fetchAll();

if (empty($cotizaciones)) {
    echo '<div style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:40px;text-align:center">
        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 16px;color:var(--color-text-muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <div style="font-size:15px;font-weight:700;color:var(--color-text);margin-bottom:4px">Sin cotizaciones</div>
        <div style="font-size:13px;color:var(--color-text-muted)">No hay cotizaciones con este estado aún.</div>
    </div>';
} else {
    ?>

<!-- ── Lista de cotizaciones ──────────────────────────────────── -->
<div style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);overflow:hidden">
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:var(--color-surface);border-bottom:1.5px solid var(--color-border)">
                    <th style="padding:12px 16px;text-align:center;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px;width:40px">
                        <input type="checkbox" id="chkTodas" style="cursor:pointer" onchange="toggleSeleccionarTodas()">
                    </th>
                    <th style="padding:12px 16px;text-align:left;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px">Nº Cotización</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px">Cliente</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px">Tipo</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px">Total</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px">Estado</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px">Vencimiento</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px">Acciones</th>
                </tr>
            </thead>
            <tbody>
    <?php
    foreach ($cotizaciones as $cot) {
        $fecha_creacion = new DateTime($cot['created_at']);
        $fecha_vencimiento = $fecha_creacion->add(new DateInterval('P' . $cot['vigencia_dias'] . 'D'));
        $hoy = new DateTime();
        $vencida = $fecha_vencimiento < $hoy;

        $badge_estado = '';
        $badge_svg = '';
        $color_estado = '';
        switch ($cot['estado']) {
            case 'pendiente':
                $badge_estado = 'Pendiente';
                $badge_svg = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:inline;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                $color_estado = '#fbbf24';
                break;
            case 'enviada':
                $badge_estado = 'Enviada';
                $badge_svg = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:inline;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
                $color_estado = '#3b82f6';
                break;
            case 'aceptada':
                $badge_estado = 'Aceptada';
                $badge_svg = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:inline;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
                $color_estado = '#10b981';
                break;
            case 'rechazada':
                $badge_estado = 'Rechazada';
                $badge_svg = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:inline;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
                $color_estado = '#ef4444';
                break;
        }

        $tipo_label = $cot['cliente_tipo'] === 'cliente' ? 'Cliente' : 'Lead';
        $total_formateado = '$ ' . number_format($cot['total'], 0, ',', '.') . ' ' . $cot['moneda'];
        $vencimiento_label = $vencida ? '<span style="color:#ef4444;font-weight:700">Vencida</span>' : $fecha_vencimiento->format('d/m/Y');
    ?>
                <tr style="border-bottom:1px solid var(--color-border);transition:background .15s" onmouseenter="this.style.background='var(--color-surface)'" onmouseleave="this.style.background=''">
                    <td style="padding:12px 16px;text-align:center">
                        <input type="checkbox" class="chkCotizacion" value="<?php echo $cot['id']; ?>" style="cursor:pointer" onchange="actualizarBtnEliminar()">
                    </td>
                    <td style="padding:12px 16px;font-weight:700;color:var(--color-primary)">
                        <a href="cotizacion_vista.php?id=<?php echo $cot['id']; ?>" style="text-decoration:none;color:inherit;cursor:pointer">
                            <?php echo htmlspecialchars($cot['numero']); ?>
                        </a>
                    </td>
                    <td style="padding:12px 16px;color:var(--color-text)">
                        <?php echo htmlspecialchars($cot['nombre_cliente']); ?>
                        <?php if ($cot['email']) echo '<br><span style="font-size:11px;color:var(--color-text-muted)">' . htmlspecialchars($cot['email']) . '</span>'; ?>
                    </td>
                    <td style="padding:12px 16px;color:var(--color-text);font-size:12px">
                        <?php echo $tipo_label; ?>
                    </td>
                    <td style="padding:12px 16px;text-align:right;font-weight:700;color:var(--color-primary)">
                        <?php echo $total_formateado; ?>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="display:inline-flex;align-items:center;padding:4px 10px;background:<?php echo $color_estado; ?>;color:#fff;border-radius:var(--radius-sm);font-size:11px;font-weight:700">
                            <?php echo $badge_svg . $badge_estado; ?>
                        </span>
                    </td>
                    <td style="padding:12px 16px;text-align:center;font-size:12px;color:var(--color-text-muted)">
                        <?php echo $vencimiento_label; ?>
                    </td>
                    <td style="padding:10px 16px">
                        <div style="display:flex;align-items:center;gap:4px;flex-wrap:nowrap">
                            <!-- Ver: abre modal con iframe template -->
                            <button onclick="verCotizacion(<?php echo $cot['id']; ?>)" title="Ver cotización"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#eef2ff;color:#4f46e5;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap"
                                onmouseenter="this.style.background='#e0e7ff'" onmouseleave="this.style.background='#eef2ff'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Ver
                            </button>
                            <!-- Editar: abre modal formulario -->
                            <button onclick="editarCotizacion(<?php echo $cot['id']; ?>)" title="Editar"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#f1f5f9;color:#334155;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap"
                                onmouseenter="this.style.background='#e2e8f0'" onmouseleave="this.style.background='#f1f5f9'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Editar
                            </button>
                            <!-- Notificar -->
                            <button onclick="enviarWhatsApp(<?php echo $cot['id']; ?>)" title="Notificar por WhatsApp"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#dcfce7;color:#16a34a;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap"
                                onmouseenter="this.style.background='#bbf7d0'" onmouseleave="this.style.background='#dcfce7'">
                                <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622a11.01 11.01 0 001.203 1.487c.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                                Notificar
                            </button>
                            <!-- Aprobar -->
                            <?php if($cot['estado'] !== 'aceptada'): ?>
                            <button onclick="aprobarCotizacion(<?php echo $cot['id']; ?>, '<?php echo htmlspecialchars($cot['numero']); ?>')" title="Aprobar"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#0f172a;color:#c9f31d;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap"
                                onmouseenter="this.style.background='#1e293b'" onmouseleave="this.style.background='#0f172a'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Aprobar
                            </button>
                            <?php endif; ?>
                            <!-- Clonar -->
                            <button onclick="clonarCotizacion(<?php echo $cot['id']; ?>, '<?php echo htmlspecialchars($cot['numero']); ?>')" title="Clonar cotización"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#f0fdf4;color:#16a34a;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap"
                                onmouseenter="this.style.background='#dcfce7'" onmouseleave="this.style.background='#f0fdf4'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Clonar
                            </button>
                            <!-- Eliminar -->
                            <button onclick="eliminarCotizacion(<?php echo $cot['id']; ?>, '<?php echo htmlspecialchars($cot['numero']); ?>')" title="Eliminar"
                                style="display:inline-flex;align-items:center;padding:6px 8px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s"
                                onmouseenter="this.style.background='#fecaca'" onmouseleave="this.style.background='#fee2e2'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
    <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php } ?>

<!-- ── Modal: Ver Cotización (iframe template) ──────────────────── -->
<div id="modalVer" class="modal-overlay" style="z-index:1050">
    <div class="modal" style="max-width:960px;width:95vw;height:88vh;display:flex;flex-direction:column">
        <div class="modal-header" style="flex-shrink:0">
            <div>
                <h3 class="modal-title">Cotización</h3>
                <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Vista previa del documento</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <a id="verBtnPdf" href="#" target="_blank" class="btn btn-secondary btn-sm" style="font-size:12px">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Abrir en ventana
                </a>
                <button class="modal-close" onclick="cerrarModalVer()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div style="flex:1;overflow:hidden;border-radius:0 0 16px 16px">
            <iframe id="verIframe" src="" style="width:100%;height:100%;border:none;display:block"></iframe>
        </div>
    </div>
</div>

<!-- ── Modal: Editar Cotización ─────────────────────────────────── -->
<div id="modalEditar" class="modal-overlay" style="z-index:1050">
    <div class="modal" style="max-width:730px">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="editModalTitle">Editar Cotización</h3>
                <p style="font-size:12px;color:#94a3b8;margin:3px 0 0" id="editModalNumero"></p>
            </div>
            <button class="modal-close" onclick="cerrarModalEditar()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" id="editModalBody" style="max-height:65vh;overflow-y:auto">
            <div style="text-align:center;padding:40px;color:#94a3b8">Cargando...</div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="cerrarModalEditar()">Cancelar</button>
            <button class="btn btn-secondary" id="editSaveBtn" onclick="guardarEdicion()">Guardar cambios</button>
        </div>
    </div>
</div>

<!-- ── Modal: Notificar (WhatsApp / Email) ───────────────────────── -->
<div id="modalPlantilla" class="modal-overlay" style="z-index:1050">
    <div class="modal" style="max-width:730px">

        <!-- PASO 1: elegir canal + plantilla -->
        <div id="waStep1">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Notificar al cliente</h3>
                    <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Elige el canal y la plantilla</p>
                </div>
                <button class="modal-close" onclick="cerrarModalPlantilla()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Tabs canal -->
            <div style="display:flex;gap:0;border-bottom:2px solid #f1f5f9;padding:0 16px">
                <button id="tabWA" onclick="switchTab('wa')"
                    style="display:inline-flex;align-items:center;gap:6px;padding:12px 16px;background:none;border:none;border-bottom:2px solid #25D366;margin-bottom:-2px;font-size:13px;font-weight:700;color:#0f172a;cursor:pointer">
                    <svg width="14" height="14" fill="#25D366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                    WhatsApp
                </button>
                <button id="tabEmail" onclick="switchTab('email')"
                    style="display:inline-flex;align-items:center;gap:6px;padding:12px 16px;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;font-size:13px;font-weight:700;color:#94a3b8;cursor:pointer">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Correo
                </button>
            </div>

            <div id="listadoPlantillas" style="padding:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;max-height:55vh;overflow-y:auto"></div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="cerrarModalPlantilla()">Cancelar</button>
            </div>
        </div>

        <!-- PASO 2: preview + enviar -->
        <div id="waStep2" style="display:none">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" style="display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Revisar y enviar
                    </h3>
                    <p style="font-size:12px;color:#94a3b8;margin:3px 0 0">Así quedará el mensaje en WhatsApp</p>
                </div>
                <button class="modal-close" onclick="cerrarModalPlantilla()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body" style="display:flex;flex-direction:column;gap:16px">

                <!-- Imagen -->
                <div id="waStep2ImgBox" style="display:none">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Imagen adjunta</div>
                    <div style="display:flex;align-items:center;gap:14px;padding:12px;background:#f8fafc;border-radius:10px;border:1.5px solid #e2e8f0">
                        <img id="waStep2Img" src="" alt="imagen" style="width:72px;height:72px;border-radius:8px;object-fit:cover;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.10)">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:4px">Imagen adjunta</div>
                            <div style="display:flex;flex-direction:column;gap:4px">
                                <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:#16a34a;font-weight:600">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                    Móvil: se adjunta automáticamente
                                </div>
                                <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:#4f46e5;font-weight:600">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path stroke-linecap="round" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                    PC: se copia al portapapeles · pega con <kbd style="background:#e2e8f0;border-radius:4px;padding:1px 5px;font-size:10px">Ctrl+V</kbd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Burbuja de mensaje -->
                <div>
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Mensaje</div>
                    <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px 12px 12px 0;padding:14px 16px">
                        <div id="waStep2Texto" style="font-size:13px;color:#0f172a;line-height:1.65;white-space:pre-wrap;word-break:break-word"></div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:5px;text-align:right" id="waStep2Para"></div>
                </div>

            </div>
            <div class="modal-footer" style="gap:8px">
                <button class="btn btn-outline" onclick="volverStep1()">← Cambiar plantilla</button>
                <button onclick="confirmarEnvioWA()"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#25D366;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.filter='brightness(.9)'" onmouseleave="this.style.filter=''">
                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-1.567.934-2.582 2.325-2.582 3.972 0 2.487 1.998 4.614 4.644 5.048h.004c.987.135 2.025.027 2.906-.784l.384.622c.542.922.927 1.359 1.203 1.487.278.151.645.15.93-.002.393-.229.79-.767 1.144-1.649.19-.497.502-1.311.737-1.88-2.296-1.24-3.923-3.529-3.923-6.121 0-1.273.337-2.471.922-3.519m9.574-3.051c2.289 2.287 3.706 5.646 3.706 9.269 0 7.278-5.601 13.16-12.508 13.16-2.103 0-4.126-.494-5.911-1.369l-.67.11c-.5.083-.902.077-1.202-.022-.463-.15-.758-.544-.882-1.022-.149-.552-.05-1.215.38-1.95l.671-1.167c-.331-1.664-.5-3.406-.5-5.183 0-7.275 5.6-13.159 12.506-13.159 2.6 0 5.068.681 7.19 1.873-.37 1.564-.582 3.204-.582 4.919z"/></svg>
                    Abrir WhatsApp
                </button>
            </div>
        </div>

        <!-- PASO 2 EMAIL: preview + enviar -->
        <div id="emailStep2" style="display:none">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" style="display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Enviar correo
                    </h3>
                    <p style="font-size:12px;color:#94a3b8;margin:3px 0 0" id="emailStep2Para"></p>
                </div>
                <button class="modal-close" onclick="cerrarModalPlantilla()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">

                <!-- Asunto editable -->
                <div class="form-group" style="margin:0">
                    <label class="form-label">Asunto</label>
                    <input id="emailAsunto" class="form-input" placeholder="Asunto del correo">
                </div>

                <!-- Imagen adjunta -->
                <div id="emailImgBox" style="display:none">
                    <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f0fdf4;border-radius:8px;border:1.5px solid #bbf7d0">
                        <img id="emailImgThumb" src="" style="width:44px;height:44px;border-radius:6px;object-fit:cover">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:12px;font-weight:700;color:#166534">✓ Imagen incluida en el correo</div>
                            <div style="font-size:11px;color:#16a34a">Se mostrará dentro del diseño del email</div>
                        </div>
                    </div>
                </div>

                <!-- Preview HTML -->
                <div>
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Vista previa</div>
                    <div id="emailPreview" style="border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden;max-height:260px;overflow-y:auto"></div>
                </div>

            </div>
            <div class="modal-footer" style="gap:8px;display:flex;justify-content:space-between">
                <button class="btn btn-outline" onclick="volverStep1Email()">← Cambiar plantilla</button>
                <div style="display:flex;gap:8px">
                    <button onclick="abrirWhatsAppEmail()"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#25D366;color:#fff;border:none;border-radius:24px;font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.filter='brightness(.9)'" onmouseleave="this.style.filter=''" id="btnWhatsAppEmail">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
                        <span id="btnWhatsAppTel">WhatsApp</span>
                    </button>
                    <button id="btnEnviarCorreo" onclick="confirmarEnvioEmail()"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#4f46e5;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s" onmouseenter="this.style.filter='brightness(.9)'" onmouseleave="this.style.filter=''">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Enviar correo
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>


<script>
// ── helpers ───────────────────────────────────────────────────────────────────
function hesc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

async function apiPut(payload) {
    const r = await fetch('api/cotizaciones.php', {
        method: 'PUT', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    return r.json();
}

// ── Modal: VER (iframe template) ─────────────────────────────────────────────
function verCotizacion(id) {
    const url = 'cotizacion_vista.php?id=' + id;
    document.getElementById('verIframe').src = url;
    document.getElementById('verBtnPdf').href = url;
    document.getElementById('modalVer').classList.add('show');
}
function cerrarModalVer() {
    document.getElementById('modalVer').classList.remove('show');
    document.getElementById('verIframe').src = '';
}

// ── Modal: EDITAR ─────────────────────────────────────────────────────────────
let _editId = null;
async function editarCotizacion(id) {
    _editId = id;
    document.getElementById('editModalBody').innerHTML = '<div style="text-align:center;padding:40px;color:#94a3b8">Cargando...</div>';
    document.getElementById('modalEditar').classList.add('show');
    try {
        const r = await fetch('api/cotizaciones.php?id=' + id);
        const d = await r.json();
        if (!d.success || !d.data) { showToast('No se pudo cargar la cotización', 'error'); cerrarModalEditar(); return; }
        const c = d.data;
        document.getElementById('editModalNumero').textContent = c.numero;
        document.getElementById('editModalBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
            <div class="form-group">
                <label class="form-label">Nombre cliente *</label>
                <input class="form-input" id="eNombre" value="${hesc(c.nombre_cliente)}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" id="eEmail" value="${hesc(c.email||'')}">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
            <div class="form-group">
                <label class="form-label">WhatsApp</label>
                <input class="form-input" id="eWa" value="${hesc(c.whatsapp||'')}">
            </div>
            <div class="form-group">
                <label class="form-label">Estado</label>
                <select class="form-select" id="eEstado">
                    <option value="pendiente" ${c.estado==='pendiente'?'selected':''}>Pendiente</option>
                    <option value="enviada"   ${c.estado==='enviada'?'selected':''}>Enviada</option>
                    <option value="aceptada"  ${c.estado==='aceptada'?'selected':''}>Aceptada</option>
                    <option value="rechazada" ${c.estado==='rechazada'?'selected':''}>Rechazada</option>
                </select>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
            <div class="form-group">
                <label class="form-label">Vigencia (días)</label>
                <select class="form-select" id="eVigencia">
                    ${[7,15,30,60,90].map(v=>`<option value="${v}" ${parseInt(c.vigencia_dias)==v?'selected':''}>${v} días</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Total (${c.moneda||'COP'})</label>
                <input type="number" class="form-input" id="eTotal" value="${parseFloat(c.total||0).toFixed(0)}" min="0">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notas</label>
            <textarea class="form-textarea" id="eNotas" rows="3">${hesc(c.notas||'')}</textarea>
        </div>
        `;
        // store needed for PUT
        window._editData = c;
    } catch(e) { showToast('Error de conexión', 'error'); cerrarModalEditar(); }
}
function cerrarModalEditar() {
    document.getElementById('modalEditar').classList.remove('show');
    _editId = null;
    window._editData = null;
}
async function guardarEdicion() {
    if (!_editId || !window._editData) return;
    const nombre = document.getElementById('eNombre')?.value.trim();
    if (!nombre) { showToast('El nombre es obligatorio', 'warning'); return; }
    const btn = document.getElementById('editSaveBtn');
    btn.disabled = true; btn.textContent = 'Guardando...';
    const c = window._editData;
    try {
        const d = await apiPut({
            id: _editId,
            cliente_tipo: c.cliente_tipo,
            cliente_id:   c.cliente_id,
            nombre_cliente: nombre,
            email:    document.getElementById('eEmail').value.trim(),
            whatsapp: document.getElementById('eWa').value.trim(),
            estado:   document.getElementById('eEstado').value,
            vigencia_dias: parseInt(document.getElementById('eVigencia').value),
            subtotal: parseFloat(c.subtotal||0),
            descuento:parseFloat(c.descuento||0),
            total:    parseFloat(document.getElementById('eTotal').value)||0,
            notas:    document.getElementById('eNotas').value,
            moneda:   c.moneda||'COP',
            items:    c.items
        });
        if (d.success) {
            showToast('Cotización actualizada', 'success');
            cerrarModalEditar();
            location.reload();
        } else { showToast(d.error || 'Error al guardar', 'error'); }
    } catch(e) { showToast('Error de conexión', 'error'); }
    btn.disabled = false; btn.textContent = 'Guardar cambios';
}

// ── APROBAR ───────────────────────────────────────────────────────────────────
async function aprobarCotizacion(id, numero) {
    const ok = await confirmAction(
        `La cotización ${numero || '#'+id} será marcada como ACEPTADA.`,
        { title: '¿Aprobar cotización?', okText: 'Sí, aprobar', okColor: '#16a34a', okHover: '#15803d' }
    );
    if (!ok) return;
    try {
        const d = await apiPut({ id, estado: 'aceptada' });
        if (d.success) { showToast('¡Cotización aprobada!', 'success'); location.reload(); }
        else showToast(d.error || 'Error al aprobar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
async function eliminarCotizacion(id, numero) {
    const ok = await confirmAction(
        `La cotización ${numero || '#'+id} será eliminada permanentemente.`,
        { title: '¿Eliminar cotización?' }
    );
    if (!ok) return;
    try {
        const r = await fetch('api/cotizaciones.php', {
            method: 'DELETE', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const d = await r.json();
        if (d.success) { showToast('Cotización eliminada', 'success'); location.reload(); }
        else showToast(d.error || 'Error al eliminar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

// ── SELECCIÓN MASIVA ──────────────────────────────────────────────────────────
function toggleSeleccionarTodas() {
    const chk = document.getElementById('chkTodas');
    document.querySelectorAll('.chkCotizacion').forEach(c => c.checked = chk?.checked);
    actualizarBtnEliminar();
}
function actualizarBtnEliminar() {
    const n = document.querySelectorAll('.chkCotizacion:checked').length;
    const btn = document.getElementById('btnEliminarSeleccionadas');
    if (btn) btn.style.display = n > 0 ? 'inline-flex' : 'none';
}
async function eliminarSeleccionadas() {
    const ids = Array.from(document.querySelectorAll('.chkCotizacion:checked')).map(c => parseInt(c.value));
    if (!ids.length) { showToast('Selecciona al menos una cotización', 'warning'); return; }
    const ok = await confirmAction(`Se eliminarán ${ids.length} cotización(es). Esta acción no se puede deshacer.`, { title: '¿Eliminar seleccionadas?' });
    if (!ok) return;
    try {
        const r = await fetch('api/cotizaciones.php', {
            method: 'DELETE', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        });
        const d = await r.json();
        if (d.success) { showToast(d.message || 'Eliminadas', 'success'); location.reload(); }
        else showToast(d.error || 'Error', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

// ── NOTIFICAR (WhatsApp) ──────────────────────────────────────────────────────
let cotizacionActual = null;
let plantillaActual  = null;

async function enviarWhatsApp(id) {
    try {
        const r = await fetch('api/cotizaciones.php?id=' + id);
        const d = await r.json();
        if (d.success && d.data) {
            cotizacionActual = d.data;
            plantillaActual  = null;
            await cargarPlantillas();
            mostrarStep1();
            document.getElementById('modalPlantilla').classList.add('show');
        } else showToast('No se pudo obtener la cotización', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

function mostrarStep1() {
    document.getElementById('waStep1').style.display    = '';
    document.getElementById('waStep2').style.display    = 'none';
    document.getElementById('emailStep2').style.display = 'none';
}
function volverStep1() {
    plantillaActual = null;
    mostrarStep1();
}

async function cargarPlantillas() {
    const container = document.getElementById('listadoPlantillas');
    container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#94a3b8">Cargando plantillas...</div>';
    try {
        const r = await fetch('api/mensajes_plantillas.php?filtro=todas');
        const d = await r.json();
        container.innerHTML = '';
        if (d.success && d.data?.length) {
            d.data.forEach(p => {
                const card = document.createElement('div');
                card.style.cssText = 'border-radius:12px;cursor:pointer;transition:all .15s;background:#f8fafc;border:1.5px solid #e2e8f0;overflow:hidden;display:flex;flex-direction:column';
                card.onmouseenter = () => { card.style.borderColor='#a5b4fc'; card.style.background='#eef2ff'; card.style.transform='translateY(-2px)'; };
                card.onmouseleave = () => { card.style.borderColor='#e2e8f0'; card.style.background='#f8fafc'; card.style.transform=''; };

                const imgHtml = p.imagen
                    ? `<div style="width:100%;height:80px;overflow:hidden;flex-shrink:0"><img src="${hesc(p.imagen)}" style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.style.display='none'"></div>`
                    : '';

                const preview = (p.contenido||'').length > 70 ? p.contenido.substring(0,70)+'…' : p.contenido;
                const badge = p.es_predefinida
                    ? `<span style="padding:2px 7px;background:#0f172a;color:#c9f31d;border-radius:20px;font-size:9px;font-weight:700">Predefinida</span>`
                    : `<span style="padding:2px 7px;background:#eef2ff;color:#4f46e5;border-radius:20px;font-size:9px;font-weight:700">Personal</span>`;

                card.innerHTML = `${imgHtml}
                    <div style="padding:12px;flex:1;display:flex;flex-direction:column;gap:6px;position:relative">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px">
                            <div style="font-weight:800;color:#0f172a;font-size:12px;line-height:1.3">${hesc(p.nombre)}</div>
                            <div style="display:flex;gap:4px;align-items:center">
                                ${badge}
                                <button onclick="event.stopPropagation(); clonarPlantilla(${p.id}, '${hesc(p.nombre)}')"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border:none;background:#f0fdf4;color:#16a34a;border-radius:6px;cursor:pointer;transition:all .15s;font-size:10px;font-weight:700;flex-shrink:0"
                                    title="Clonar plantilla"
                                    onmouseenter="this.style.background='#dcfce7'; this.style.transform='scale(1.1)'"
                                    onmouseleave="this.style.background='#f0fdf4'; this.style.transform=''"
                                >
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div style="font-size:10.5px;color:#94a3b8;font-style:italic;line-height:1.4">"${hesc(preview)}"</div>
                        ${p.imagen ? `<div style="display:flex;align-items:center;gap:4px;font-size:10px;color:#10b981;font-weight:600"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg> Con imagen adjunta</div>` : ''}
                    </div>`;
                card.onclick = () => seleccionarPlantillaCanal(p);
                container.appendChild(card);
            });
        } else {
            container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#94a3b8;font-size:13px">No hay plantillas disponibles.<br><a href="mensajes.php" style="color:#4f46e5;font-weight:700">Crear plantilla</a></div>';
        }
    } catch(e) { container.innerHTML = '<div style="grid-column:1/-1;color:#ef4444;padding:16px">Error al cargar plantillas</div>'; }
}

function seleccionarPlantilla(p) {
    if (!cotizacionActual) return;
    plantillaActual = p;

    // Reemplazar variables con datos de la cotización
    const fecha = new Date().toLocaleDateString('es-CO', { day:'2-digit', month:'2-digit', year:'numeric' });
    const totalFmt = '$ ' + Number(cotizacionActual.total).toLocaleString('es-CO');
    let msg = (p.contenido || '')
        .replace(/{{cliente_nombre}}/g,    cotizacionActual.nombre_cliente || '')
        .replace(/{{numero_cotizacion}}/g,  cotizacionActual.numero || '')
        .replace(/{{total}}/g,              totalFmt)
        .replace(/{{moneda}}/g,             cotizacionActual.moneda || 'COP')
        .replace(/{{fecha}}/g,              fecha)
        .replace(/{{vigencia}}/g,           cotizacionActual.vigencia_dias || '15');

    // Imagen
    const imgBox = document.getElementById('waStep2ImgBox');
    if (p.imagen) {
        imgBox.style.display = '';
        document.getElementById('waStep2Img').src = p.imagen;
    } else {
        imgBox.style.display = 'none';
    }

    // Texto preview
    document.getElementById('waStep2Texto').textContent = msg;
    const wa = (cotizacionActual.whatsapp||'').replace(/\D/g,'');
    document.getElementById('waStep2Para').textContent = wa ? `Enviando a: +${wa}` : '⚠ Sin número WhatsApp registrado';

    // Mostrar paso 2
    document.getElementById('waStep1').style.display = 'none';
    document.getElementById('waStep2').style.display = '';
}

function confirmarEnvioWA() {
    if (!cotizacionActual || !plantillaActual) return;
    const texto  = document.getElementById('waStep2Texto').textContent;
    const wa     = (cotizacionActual.whatsapp||'').replace(/\D/g,'');
    const imagen = plantillaActual.imagen || null;

    if (!wa) { showToast('No hay número de WhatsApp registrado', 'warning'); return; }

    // 1. Abrir WhatsApp SIEMPRE — esto nunca falla
    cerrarModalPlantilla();
    window.open(`https://wa.me/${wa}?text=${encodeURIComponent(texto)}`, '_blank');
    apiPut({ id: cotizacionActual.id, estado: 'enviada' }).catch(()=>{});

    // 2. Si hay imagen, intentar copiar al portapapeles en segundo plano
    if (imagen) {
        _copiarImagenAlPortapapeles(imagen);
    }
}

async function clonarCotizacion(id, numero) {
    try {
        const r = await fetch('api/cotizaciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clonar', id })
        });
        const d = await r.json();
        if (d.success) {
            showToast(`✓ Cotización ${numero} clonada correctamente`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(d.error || 'Error al clonar', 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    }
}

function abrirWhatsAppEmail() {
    if (!cotizacionActual) return;
    const wa = (cotizacionActual.whatsapp||'').replace(/\D/g,'');
    if (!wa) { showToast('No hay número de WhatsApp registrado', 'warning'); return; }
    window.open(`https://wa.me/${wa}`, '_blank');
}

async function clonarPlantilla(plantillaId, nombreOriginal) {
    const nombreNuevo = prompt(`Nombre para la plantilla clonada:\n(Original: "${nombreOriginal}")`, `${nombreOriginal} - Copia`);
    if (!nombreNuevo || !nombreNuevo.trim()) return;

    try {
        const r = await fetch('api/mensajes_plantillas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clonar', id: plantillaId, nombre: nombreNuevo.trim() })
        });
        const d = await r.json();
        if (d.success) {
            showToast(`✓ Plantilla "${nombreNuevo}" creada correctamente`, 'success');
            await cargarPlantillas();
            await cargarGestionPlantillas();
        } else {
            showToast(d.error || 'Error al clonar plantilla', 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    }
}

// ── Modal Gestión de Plantillas ───────────────────────────────────────────────
async function abrirModalPlantillas() {
    document.getElementById('modalGestionPlantillas').classList.add('show');
    await cargarGestionPlantillas();
}

function cerrarModalPlantillasGestion() {
    document.getElementById('modalGestionPlantillas').classList.remove('show');
}

async function cargarGestionPlantillas() {
    const container = document.getElementById('listaGestionPlantillas');
    container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#94a3b8">Cargando plantillas...</div>';
    try {
        const r = await fetch('api/mensajes_plantillas.php?filtro=todas');
        const d = await r.json();
        container.innerHTML = '';
        if (!d.success || !d.data?.length) {
            container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#94a3b8;font-size:13px">No hay plantillas.<br><a href="mensajes.php" style="color:#4f46e5;font-weight:700">Crear plantilla</a></div>';
            return;
        }
        d.data.forEach(p => {
            const badge = p.es_predefinida
                ? `<span style="padding:2px 7px;background:#0f172a;color:#c9f31d;border-radius:20px;font-size:9px;font-weight:700">Predefinida</span>`
                : `<span style="padding:2px 7px;background:#eef2ff;color:#4f46e5;border-radius:20px;font-size:9px;font-weight:700">Personal</span>`;
            const preview = (p.contenido||'').length > 60 ? p.contenido.substring(0,60)+'…' : p.contenido;

            const card = document.createElement('div');
            card.style.cssText = 'border-radius:10px;background:#f8fafc;border:1.5px solid #e2e8f0;padding:12px;display:flex;flex-direction:column;gap:8px';
            card.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px">
                    <div style="font-weight:800;color:#0f172a;font-size:12px;line-height:1.3">${hesc(p.nombre)}</div>
                    ${badge}
                </div>
                <div style="font-size:11px;color:#94a3b8;font-style:italic;line-height:1.4">"${hesc(preview)}"</div>
                <div style="display:flex;gap:6px;margin-top:auto">
                    <button onclick="clonarPlantilla(${p.id}, '${hesc(p.nombre)}')"
                        style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:6px 10px;background:#f0fdf4;color:#16a34a;border:1.5px solid #bbf7d0;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;transition:all .15s"
                        onmouseenter="this.style.background='#dcfce7'" onmouseleave="this.style.background='#f0fdf4'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Clonar
                    </button>
                    <a href="mensajes.php" target="_blank"
                        style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:6px 10px;background:#eef2ff;color:#4f46e5;border:1.5px solid #c7d2fe;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;transition:all .15s"
                        onmouseenter="this.style.background='#e0e7ff'" onmouseleave="this.style.background='#eef2ff'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar
                    </a>
                </div>`;
            container.appendChild(card);
        });
    } catch(e) {
        container.innerHTML = '<div style="grid-column:1/-1;color:#ef4444;padding:16px;text-align:center">Error al cargar plantillas</div>';
    }
}

async function _copiarImagenAlPortapapeles(imagen) {
    // Móvil con Web Share: no hace falta portapapeles
    if (navigator.share && navigator.canShare) return;

    // Necesita contexto seguro (HTTPS o localhost)
    if (!navigator.clipboard || !window.ClipboardItem) {
        setTimeout(() => showToast('Imagen lista · Adjúntala con el clip 📎 en WhatsApp', 'info'), 800);
        return;
    }
    try {
        const resp = await fetch(imagen);
        const blob = await resp.blob();
        let pngBlob = blob;
        if (blob.type !== 'image/png') {
            pngBlob = await new Promise((res, rej) => {
                const img = new Image();
                const url = URL.createObjectURL(blob);
                img.onload = () => {
                    const c = document.createElement('canvas');
                    c.width = img.width; c.height = img.height;
                    c.getContext('2d').drawImage(img, 0, 0);
                    URL.revokeObjectURL(url);
                    c.toBlob(b => b ? res(b) : rej(), 'image/png');
                };
                img.onerror = () => { URL.revokeObjectURL(url); rej(); };
                img.src = url;
            });
        }
        await navigator.clipboard.write([new ClipboardItem({ 'image/png': pngBlob })]);
        setTimeout(() => showToast('📋 Imagen copiada · En WhatsApp haz clic en el chat y presiona Ctrl+V', 'success'), 800);
    } catch(e) {
        setTimeout(() => showToast('Imagen lista · Adjúntala con el clip 📎 en WhatsApp', 'info'), 800);
    }
}

function cerrarModalPlantilla() {
    document.getElementById('modalPlantilla').classList.remove('show');
    cotizacionActual = null;
    plantillaActual  = null;
    mostrarStep1();
}

// ── TABS canal ────────────────────────────────────────────────────────────────
let canalActual = 'wa'; // 'wa' | 'email'

function switchTab(canal) {
    canalActual = canal;
    const tabWA    = document.getElementById('tabWA');
    const tabEmail = document.getElementById('tabEmail');
    tabWA.style.borderBottomColor    = canal === 'wa'    ? '#25D366' : 'transparent';
    tabWA.style.color                = canal === 'wa'    ? '#0f172a' : '#94a3b8';
    tabEmail.style.borderBottomColor = canal === 'email' ? '#4f46e5' : 'transparent';
    tabEmail.style.color             = canal === 'email' ? '#0f172a' : '#94a3b8';
    cargarPlantillas(); // recargar con mismo listado, pero onclick cambia destino
}

// Wrapper: al seleccionar una plantilla va al flujo correcto
function seleccionarPlantillaCanal(p) {
    if (canalActual === 'email') {
        seleccionarPlantillaEmail(p);
    } else {
        seleccionarPlantilla(p);
    }
}

// ── EMAIL STEP 2 ──────────────────────────────────────────────────────────────
function seleccionarPlantillaEmail(p) {
    if (!cotizacionActual) return;
    plantillaActual = p;

    const fecha    = new Date().toLocaleDateString('es-CO', { day:'2-digit', month:'2-digit', year:'numeric' });
    const totalFmt = '$ ' + Number(cotizacionActual.total).toLocaleString('es-CO');
    const texto    = (p.contenido || '')
        .replace(/{{cliente_nombre}}/g,   cotizacionActual.nombre_cliente || '')
        .replace(/{{numero_cotizacion}}/g, cotizacionActual.numero || '')
        .replace(/{{total}}/g,             totalFmt)
        .replace(/{{moneda}}/g,            cotizacionActual.moneda || 'COP')
        .replace(/{{fecha}}/g,             fecha)
        .replace(/{{vigencia}}/g,          cotizacionActual.vigencia_dias || '15');

    const email = cotizacionActual.email || '';
    document.getElementById('emailStep2Para').textContent = email ? `Para: ${email}` : '⚠ Sin email registrado';
    document.getElementById('emailAsunto').value = `Cotización ${cotizacionActual.numero || ''} — QUANTUN Digital`;

    // Imagen
    const imgBox = document.getElementById('emailImgBox');
    if (p.imagen) {
        imgBox.style.display = '';
        document.getElementById('emailImgThumb').src = p.imagen;
    } else {
        imgBox.style.display = 'none';
    }

    // Preview HTML del correo (simplificado — el real lo genera PHP)
    document.getElementById('emailPreview').innerHTML = buildEmailPreview(
        cotizacionActual.nombre_cliente || '', texto, p.imagen || null, cotizacionActual, p.logo_url || null
    );

    // Actualizar botón de WhatsApp con el número
    const whatsappBtn = document.getElementById('btnWhatsAppTel');
    const wa = (cotizacionActual.whatsapp || '').replace(/\D/g, '');
    if (wa) {
        whatsappBtn.textContent = wa.replace(/^57/, '').replace(/^1/, '').slice(-10);
        if (wa.startsWith('57')) {
            whatsappBtn.textContent = wa;
        }
    } else {
        whatsappBtn.textContent = 'WhatsApp';
    }

    // Mostrar
    document.getElementById('waStep1').style.display   = 'none';
    document.getElementById('waStep2').style.display   = 'none';
    document.getElementById('emailStep2').style.display = '';
}

function volverStep1Email() {
    plantillaActual = null;
    document.getElementById('emailStep2').style.display = 'none';
    mostrarStep1();
}

function buildEmailPreview(nombre, texto, imagenUrl, cot, logoUrl) {
    // Preview simplificado en el modal (el HTML real lo genera PHP server-side)
    const imgBlock = imagenUrl
        ? `<div style="margin-bottom:16px"><img src="${imagenUrl}" style="max-width:100%;border-radius:8px;display:block"></div>`
        : '';
    const parrafos = (texto||'').split('\n').filter(l=>l.trim()).map(l =>
        `<p style="margin:0 0 8px;color:#475569;font-size:13px;line-height:1.6">${hesc(l)}</p>`).join('');
    const fmt = n => (cot?.moneda||'COP') + ' ' + Number(n).toLocaleString('es-CO');
    const logoBlock = logoUrl
        ? `<img src="${logoUrl}" alt="Logo" style="height:36px;max-width:180px;object-fit:contain;display:block">`
        : `<div style="font-size:16px;font-weight:800;color:#c9f31d">⚡ QUANTUN Digital</div>`;
    return `
    <div style="font-family:'Segoe UI',Arial,sans-serif;background:#f1f5f9;padding:16px">
      <div style="max-width:100%;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0">
        <div style="background:#0f172a;padding:14px 20px">
          ${logoBlock}
        </div>
        <div style="padding:16px 20px">
          ${imgBlock}
          ${parrafos}
        </div>
        <div style="border-top:2px solid #f1f5f9;padding:12px 20px">
          <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px">COTIZACIÓN</div>
          <div style="font-size:16px;font-weight:900;color:#0f172a">${hesc(cot?.numero||'')}</div>
          <div style="font-size:12px;color:#64748b;margin-top:2px">Para: <strong>${hesc(cot?.nombre_cliente||nombre)}</strong></div>
        </div>
        <div style="padding:10px 20px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11px;color:#0f172a;display:flex;justify-content:space-between">
          <span>Total</span>
          <strong>${fmt(cot?.total||0)}</strong>
        </div>
        <div style="padding:10px 20px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;font-size:10px;color:#94a3b8">
          El correo incluirá el detalle completo de la cotización
        </div>
      </div>
    </div>`;
}

async function confirmarEnvioEmail() {
    if (!cotizacionActual || !plantillaActual) return;
    const email = cotizacionActual.email || '';
    if (!email) { showToast('Esta cotización no tiene email registrado', 'warning'); return; }

    const asunto = document.getElementById('emailAsunto').value.trim();
    if (!asunto) { showToast('El asunto es obligatorio', 'warning'); return; }

    // Reconstruir texto con variables reemplazadas
    const fecha    = new Date().toLocaleDateString('es-CO', { day:'2-digit', month:'2-digit', year:'numeric' });
    const totalFmt = (cotizacionActual.moneda||'COP') + ' ' + Number(cotizacionActual.total).toLocaleString('es-CO');
    const textoFinal = (plantillaActual.contenido || '')
        .replace(/{{cliente_nombre}}/g,    cotizacionActual.nombre_cliente || '')
        .replace(/{{numero_cotizacion}}/g,  cotizacionActual.numero || '')
        .replace(/{{total}}/g,              totalFmt)
        .replace(/{{moneda}}/g,             cotizacionActual.moneda || 'COP')
        .replace(/{{fecha}}/g,              fecha)
        .replace(/{{vigencia}}/g,           cotizacionActual.vigencia_dias || '15');

    const btn = document.getElementById('btnEnviarCorreo');
    btn.disabled = true; btn.textContent = 'Enviando...';

    try {
        const r = await fetch('api/enviar_correo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cotizacion_id: cotizacionActual.id,
                asunto,
                mensaje_texto: textoFinal,
                imagen_path:   plantillaActual.imagen   || '',
                logo_url:      plantillaActual.logo_url || '',
            })
        });
        const d = await r.json();
        if (d.success) {
            cerrarModalPlantilla();
            showToast('✅ ' + d.message, 'success');
        } else {
            showToast(d.error || 'Error al enviar', 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    }
    btn.disabled = false; btn.textContent = 'Enviar correo';
}

// ── Búsqueda ──────────────────────────────────────────────────────────────────
function aplicarBusqueda(event) {
    if (event.key === 'Enter' || event.type === 'change') {
        const busqueda = document.getElementById('inputBusqueda').value.trim();
        let url = new URL(window.location);

        if (busqueda) {
            url.searchParams.set('busqueda', busqueda);
        } else {
            url.searchParams.delete('busqueda');
        }

        window.location.href = url.toString();
    }
}

function limpiarBusqueda() {
    let url = new URL(window.location);
    url.searchParams.delete('busqueda');
    window.location.href = url.toString();
}

// ── Filtros de fechas ────────────────────────────────────────────────────────
function aplicarFiltroFechas() {
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    let url = new URL(window.location);

    if (desde) url.searchParams.set('fecha_desde', desde);
    else url.searchParams.delete('fecha_desde');

    if (hasta) url.searchParams.set('fecha_hasta', hasta);
    else url.searchParams.delete('fecha_hasta');

    window.location.href = url.toString();
}

function limpiarFiltroFechas() {
    let url = new URL(window.location);
    url.searchParams.delete('fecha_desde');
    url.searchParams.delete('fecha_hasta');
    window.location.href = url.toString();
}

// ── Cierre de modales ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    ['modalVer','modalEditar','modalPlantilla'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function(e) {
            if (e.target === this) {
                if (id === 'modalVer')      cerrarModalVer();
                if (id === 'modalEditar')   cerrarModalEditar();
                if (id === 'modalPlantilla')cerrarModalPlantilla();
            }
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (document.getElementById('modalVer')?.classList.contains('show'))      cerrarModalVer();
        if (document.getElementById('modalEditar')?.classList.contains('show'))   cerrarModalEditar();
        if (document.getElementById('modalPlantilla')?.classList.contains('show'))cerrarModalPlantilla();
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
