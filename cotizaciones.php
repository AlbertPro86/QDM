<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$currentPage  = 'cotizaciones';

$filtro_tipo = $_GET['tipo'] ?? 'cotizacion';
$tipo_labels = [
    'cotizacion'        => 'Cotizaciones',
    'cuenta_cobro'      => 'Cuentas de cobro',
    'orden_compra'      => 'Órdenes de compra',
    'orden_renovacion'  => 'Órdenes de renovación',
];
$tipo_label_singular = [
    'cotizacion'        => 'Cotización',
    'cuenta_cobro'      => 'Cuenta de cobro',
    'orden_compra'      => 'Orden de compra',
    'orden_renovacion'  => 'Orden de renovación',
];
$tipo_prefijos = [
    'cotizacion'        => 'COT',
    'cuenta_cobro'      => 'CDC',
    'orden_compra'      => 'OC',
    'orden_renovacion'  => 'OR',
];
$titulo_pagina   = $tipo_labels[$filtro_tipo]         ?? 'Cotizaciones';
$titulo_singular = $tipo_label_singular[$filtro_tipo] ?? 'Cotización';

$pageTitle    = $titulo_pagina;
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">' . htmlspecialchars($titulo_pagina) . '</span>';
include __DIR__ . '/includes/header.php';

$pdo = db();

// Migración: asegurar columnas necesarias
try { $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN tipo VARCHAR(30) NOT NULL DEFAULT 'cotizacion'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN link_pago VARCHAR(500) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN datos_bancarios LONGTEXT DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN plantilla_id INT DEFAULT NULL"); } catch (PDOException $e) {}

$filtro_estado = $_GET['estado'] ?? 'pendientes';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
?>


<!-- ── Acciones + Filtros ───────────────────────────────────── -->
<div class="page-header" style="flex-wrap:wrap;gap:8px;margin-bottom:20px;align-items:center">
    <?php
    $param_tipo   = '&tipo=' . urlencode($filtro_tipo);
    $param_fechas = ($fecha_desde || $fecha_hasta) ? '&fecha_desde=' . urlencode($fecha_desde) . '&fecha_hasta=' . urlencode($fecha_hasta) : '';
    ?>
    <a href="?estado=pendientes<?php echo $param_tipo . $param_fechas; ?>" style="padding:8px 14px;border-radius:var(--radius-sm);font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;transition:all .15s;background:<?php echo $filtro_estado === 'pendientes' ? '#C6F24E' : 'var(--color-border)'; ?>;color:<?php echo $filtro_estado === 'pendientes' ? '#0E0E0C' : 'var(--color-text-muted)'; ?>;display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Pendientes por aprobar
    </a>
    <a href="?estado=aceptada<?php echo $param_tipo . $param_fechas; ?>" style="padding:8px 14px;border-radius:var(--radius-sm);font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;transition:all .15s;background:<?php echo $filtro_estado === 'aceptada' ? 'var(--color-success)' : 'var(--color-border)'; ?>;color:<?php echo $filtro_estado === 'aceptada' ? '#fff' : 'var(--color-text-muted)'; ?>;display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Aceptada
    </a>
    <a href="?estado=todas<?php echo $param_tipo . $param_fechas; ?>" style="padding:8px 14px;border-radius:var(--radius-sm);font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;transition:all .15s;background:<?php echo $filtro_estado === 'todas' ? '#0E0E0C' : 'var(--color-border)'; ?>;color:<?php echo $filtro_estado === 'todas' ? '#fff' : 'var(--color-text-muted)'; ?>;display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        Todas
    </a>

    <!-- Búsqueda -->
    <div style="display:flex;align-items:center;gap:8px;border-left:1.5px solid var(--color-border);padding-left:12px;margin-left:8px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color:var(--color-text-muted);flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="inputBusqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar cliente, número..." style="padding:6px 10px;border:1.5px solid var(--color-border);border-radius:6px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s;min-width:160px" onkeyup="aplicarBusqueda(event)" onfocus="this.style.borderColor='var(--color-secondary)'" onblur="this.style.borderColor='var(--color-border)'">
        <?php if ($busqueda): ?>
        <button onclick="limpiarBusqueda()" class="btn btn-ghost btn-sm" style="color:var(--color-danger)">✕</button>
        <?php endif; ?>
    </div>

    <!-- Filtros de Fechas -->
    <div style="display:flex;gap:8px;align-items:center;border-left:1.5px solid var(--color-border);padding-left:12px">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color:var(--color-text-muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <input type="date" id="fechaDesde" value="<?php echo $fecha_desde; ?>" style="padding:6px 10px;border:1.5px solid var(--color-border);border-radius:6px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s" placeholder="Desde" onchange="aplicarFiltroFechas()" onfocus="this.style.borderColor='var(--color-secondary)'" onblur="this.style.borderColor='var(--color-border)'">
        <span style="color:var(--color-text-muted);font-size:12px">–</span>
        <input type="date" id="fechaHasta" value="<?php echo $fecha_hasta; ?>" style="padding:6px 10px;border:1.5px solid var(--color-border);border-radius:6px;font-size:12px;font-family:inherit;outline:none;transition:border-color .15s" placeholder="Hasta" onchange="aplicarFiltroFechas()" onfocus="this.style.borderColor='var(--color-secondary)'" onblur="this.style.borderColor='var(--color-border)'">
        <?php if ($fecha_desde || $fecha_hasta): ?>
        <button onclick="limpiarFiltroFechas()" class="btn btn-ghost btn-sm" style="color:var(--color-danger)">Limpiar</button>
        <?php endif; ?>
    </div>

    <div style="display:flex;gap:8px;margin-left:auto;align-items:center">
        <button id="btnEliminarSeleccionadas" onclick="eliminarSeleccionadas()" class="btn btn-danger" style="display:none;align-items:center;gap:6px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Eliminar seleccionadas
        </button>
        <a href="cotizador.php?tipo=<?= urlencode($filtro_tipo) ?>" class="btn btn-accent">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12"/></svg>
            Nueva <?= htmlspecialchars($titulo_singular) ?>
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
        WHERE estado != 'aceptada' AND tipo = ?" . $condicion_fechas . $condicion_busqueda . "
        ORDER BY created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$filtro_tipo], $params));
} elseif ($filtro_estado === 'todas') {
    $query = "
        SELECT id, numero, cliente_tipo, nombre_cliente, email, total, estado, moneda, created_at, vigencia_dias
        FROM cotizaciones
        WHERE tipo = ?" . $condicion_fechas . $condicion_busqueda . "
        ORDER BY created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$filtro_tipo], $params));
} else {
    $query = "
        SELECT id, numero, cliente_tipo, nombre_cliente, email, total, estado, moneda, created_at, vigencia_dias
        FROM cotizaciones
        WHERE estado = ? AND tipo = ?" . $condicion_fechas . $condicion_busqueda . "
        ORDER BY created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$filtro_estado, $filtro_tipo], $params));
}
$cotizaciones = $stmt->fetchAll();

if (empty($cotizaciones)) {
    echo '<div style="background:#fff;border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:40px;text-align:center">
        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 16px;color:var(--color-text-muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <div style="font-size:15px;font-weight:700;color:var(--color-text);margin-bottom:4px">Sin ' . htmlspecialchars($titulo_pagina) . '</div>
        <div style="font-size:13px;color:var(--color-text-muted)">No hay ' . htmlspecialchars(strtolower($titulo_pagina)) . ' con este estado aún.</div>
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
                    <th style="padding:12px 16px;text-align:left;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;font-size:11px">Nº <?= htmlspecialchars($titulo_singular) ?></th>
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
                        <a href="cotizacion_vista.php?id=<?php echo $cot['id']; ?>&tipo=<?php echo urlencode($filtro_tipo); ?>" style="text-decoration:none;color:inherit;cursor:pointer">
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
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#EFECE5;color:#2A2926;border:none;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap"
                                onmouseenter="this.style.background='#E8E5DD'" onmouseleave="this.style.background='#EFECE5'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Ver
                            </button>
                            <!-- Editar: redirige al cotizador completo -->
                            <a href="cotizador.php?edit=<?php echo $cot['id']; ?>" title="Editar cotización completa"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#EFECE5;color:#57544D;border:none;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap;text-decoration:none"
                                onmouseenter="this.style.background='#E8E5DD'" onmouseleave="this.style.background='#EFECE5'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Editar
                            </a>
                            <!-- Enviar por correo con plantilla -->
                            <button onclick="abrirModalEnviar(<?php echo $cot['id']; ?>)" title="Enviar por correo"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#E3F1E8;color:#2D8F5A;border:none;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap"
                                onmouseenter="this.style.background='#c8e6d4'" onmouseleave="this.style.background='#E3F1E8'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Enviar
                            </button>
                            <!-- Aprobar -->
                            <?php if($cot['estado'] !== 'aceptada'): ?>
                            <button onclick="aprobarCotizacion(<?php echo $cot['id']; ?>, '<?php echo htmlspecialchars($cot['numero']); ?>')" title="Aprobar"
                                class="btn btn-accent btn-sm">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Aprobar
                            </button>
                            <?php endif; ?>
                            <!-- Clonar -->
                            <button onclick="clonarCotizacion(<?php echo $cot['id']; ?>, '<?php echo htmlspecialchars($cot['numero']); ?>')" title="Clonar cotización"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#E3F1E8;color:#2D8F5A;border:none;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s;white-space:nowrap"
                                onmouseenter="this.style.background='#c8e6d4'" onmouseleave="this.style.background='#E3F1E8'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Clonar
                            </button>
                            <!-- Eliminar -->
                            <button onclick="eliminarCotizacion(<?php echo $cot['id']; ?>, '<?php echo htmlspecialchars($cot['numero']); ?>')" title="Eliminar"
                                style="display:inline-flex;align-items:center;padding:6px 8px;background:#F4DEDB;color:#B0382F;border:none;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;transition:background .12s"
                                onmouseenter="this.style.background='#e8c9c6'" onmouseleave="this.style.background='#F4DEDB'">
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
        <div style="flex:1;overflow:hidden;border-radius:0 0 6px 6px">
            <iframe id="verIframe" src="" style="width:100%;height:100%;border:none;display:block"></iframe>
        </div>
    </div>
</div>


<!-- ── Modal: Enviar cotización por email ─────────────────────────── -->
<div id="modalEnviar" class="modal-overlay" style="z-index:1050">
    <div class="modal" style="max-width:620px">

        <!-- PASO 1: Galería de plantillas de diseño -->
        <div id="enviarStep1">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Enviar cotización</h3>
                    <p style="font-size:12px;color:#94a3b8;margin:3px 0 0" id="enviarStep1Sub">Elige el diseño de la plantilla</p>
                </div>
                <button class="modal-close" onclick="cerrarModalEnviar()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="galeriaDiseños" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;max-height:52vh;overflow-y:auto">
                    <div style="grid-column:1/-1;text-align:center;padding:32px;color:#94a3b8;font-size:13px">Cargando plantillas...</div>
                </div>
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--color-border)">
                    <button onclick="seleccionarDiseno(null)"
                        style="width:100%;padding:8px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);background:#fff;font-size:12px;font-weight:700;color:var(--color-text-muted);cursor:pointer;font-family:inherit;transition:all .15s"
                        onmouseenter="this.style.borderColor='var(--color-primary)';this.style.color='var(--color-primary)'"
                        onmouseleave="this.style.borderColor='var(--color-border)';this.style.color='var(--color-text-muted)'">
                        Sin plantilla — diseño básico
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="cerrarModalEnviar()">Cancelar</button>
            </div>
        </div>

        <!-- PASO 2: Redactar y enviar -->
        <div id="enviarStep2" style="display:none">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" style="display:flex;align-items:center;gap:8px">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Enviar por email
                    </h3>
                    <p style="font-size:12px;color:#94a3b8;margin:3px 0 0" id="enviarStep2Para"></p>
                </div>
                <button class="modal-close" onclick="cerrarModalEnviar()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">
                <!-- Destinatario -->
                <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-sm)">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="color:var(--color-text-muted);flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span style="font-size:12px;font-weight:700;color:var(--color-text)" id="enviarEmailDest">—</span>
                    <span id="enviarDiseñoPill" style="margin-left:auto;font-size:10px;font-weight:700;padding:2px 8px;border-radius:100px;background:var(--color-border);color:var(--color-text-muted)"></span>
                </div>
                <!-- Asunto -->
                <div class="form-group" style="margin:0">
                    <label class="form-label">Asunto</label>
                    <input id="enviarAsunto" class="form-input" placeholder="Asunto del correo">
                </div>
                <!-- Mensaje -->
                <div class="form-group" style="margin:0">
                    <label class="form-label">Mensaje personal <span style="font-weight:400;color:var(--color-text-light)">(opcional)</span></label>
                    <textarea id="enviarMensaje" class="form-textarea" rows="4" placeholder="Agrega un mensaje personalizado para el cliente..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="justify-content:space-between">
                <button class="btn btn-outline" onclick="volverStep1Enviar()">← Cambiar diseño</button>
                <button id="btnConfirmarEnvio" onclick="confirmarEnvio()"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#0E0E0C;color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;transition:filter .15s;font-family:inherit"
                    onmouseenter="this.style.filter='brightness(1.15)'" onmouseleave="this.style.filter=''">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Enviar cotización
                </button>
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

// ── ENVIAR POR EMAIL CON PLANTILLA DE DISEÑO ─────────────────────────────────
let cotizacionActual = null;
let disenoSeleccionado = null; // plantilla_factura elegida (null = básico)
let _disenosCache = null;
const _filtroTipoActual = <?= json_encode($filtro_tipo) ?>;

async function abrirModalEnviar(id) {
    try {
        const r = await fetch('api/cotizaciones.php?id=' + id);
        const d = await r.json();
        if (!d.success || !d.data) { showToast('No se pudo cargar la cotización', 'error'); return; }
        cotizacionActual  = d.data;
        disenoSeleccionado = null;
        document.getElementById('enviarStep1').style.display = '';
        document.getElementById('enviarStep2').style.display = 'none';
        document.getElementById('enviarStep1Sub').textContent = `Para: ${cotizacionActual.nombre_cliente || ''}`;
        document.getElementById('modalEnviar').classList.add('show');
        await cargarGaleriaDiseños();
    } catch(e) { showToast('Error de conexión', 'error'); }
}

function cerrarModalEnviar() {
    document.getElementById('modalEnviar').classList.remove('show');
    cotizacionActual   = null;
    disenoSeleccionado = null;
}

async function cargarGaleriaDiseños() {
    const grid = document.getElementById('galeriaDiseños');
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:32px;color:#94a3b8;font-size:13px">Cargando diseños...</div>';
    try {
        if (!_disenosCache) {
            const r = await fetch('api/plantillas_factura.php?categoria=' + encodeURIComponent(_filtroTipoActual));
            const d = await r.json();
            _disenosCache = (d.success && d.data) ? d.data : [];
        }
        if (!_disenosCache.length) {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:32px;color:#94a3b8;font-size:13px">No hay diseños de plantilla aún.<br><a href="plantillas_factura.php" style="color:#0E0E0C;font-weight:700">Crear plantilla</a></div>';
            return;
        }
        grid.innerHTML = _disenosCache.map(p => {
            const c1 = p.color_primario   || '#0f172a';
            const c2 = p.color_secundario || '#C6F24E';
            const defBadge = p.es_default ? `<span style="position:absolute;top:8px;right:8px;font-size:9px;font-weight:700;background:#C6F24E;color:#0E0E0C;padding:2px 7px;border-radius:100px">Default</span>` : '';
            return `<div onclick="seleccionarDiseno(${p.id})"
                style="position:relative;padding:14px;border:1.5px solid #E8E5DD;border-radius:var(--radius-sm);cursor:pointer;background:#FAFAF7;transition:all .15s"
                onmouseenter="this.style.borderColor='#0E0E0C';this.style.background='#fff';this.style.transform='translateY(-1px)'"
                onmouseleave="this.style.borderColor='#E8E5DD';this.style.background='#FAFAF7';this.style.transform=''">
                <div style="height:5px;border-radius:2px;background:linear-gradient(90deg,${c1} 50%,${c2} 100%);margin-bottom:10px"></div>
                <div style="font-weight:800;font-size:12px;color:#0E0E0C;margin-bottom:2px">${hesc(p.nombre)}</div>
                ${p.descripcion ? `<div style="font-size:10px;color:#94a3b8">${hesc(p.descripcion)}</div>` : ''}
                ${defBadge}
            </div>`;
        }).join('');
    } catch(e) {
        grid.innerHTML = '<div style="grid-column:1/-1;color:#ef4444;padding:16px;text-align:center">Error al cargar diseños</div>';
    }
}

function seleccionarDiseno(plantillaId) {
    if (!cotizacionActual) return;
    // Encontrar el diseño si hay id, si no null = básico
    disenoSeleccionado = plantillaId ? (_disenosCache||[]).find(p => p.id == plantillaId) || null : null;
    const nombreDiseno = disenoSeleccionado ? disenoSeleccionado.nombre : 'Diseño básico';

    const email = cotizacionActual.email || '';
    document.getElementById('enviarStep2Para').textContent = email ? `Para: ${email}` : '⚠ Sin email registrado';
    document.getElementById('enviarEmailDest').textContent = email || 'Sin email';
    document.getElementById('enviarDiseñoPill').textContent = nombreDiseno;
    document.getElementById('enviarAsunto').value = `Cotización ${cotizacionActual.numero || ''} — QUANTUN Digital`;
    document.getElementById('enviarMensaje').value = `Hola ${cotizacionActual.nombre_cliente || ''},\n\nTe compartimos la cotización que solicitaste.\n\nQuedamos atentos a tus comentarios.`;
    document.getElementById('enviarStep1').style.display = 'none';
    document.getElementById('enviarStep2').style.display = '';
}

function volverStep1Enviar() {
    disenoSeleccionado = null;
    document.getElementById('enviarStep2').style.display = 'none';
    document.getElementById('enviarStep1').style.display = '';
}

async function confirmarEnvio() {
    if (!cotizacionActual) return;
    const email  = cotizacionActual.email || '';
    if (!email)  { showToast('Esta cotización no tiene email registrado', 'warning'); return; }
    const asunto  = document.getElementById('enviarAsunto').value.trim();
    if (!asunto) { showToast('El asunto es obligatorio', 'warning'); return; }
    const mensaje = document.getElementById('enviarMensaje').value.trim();

    const btn = document.getElementById('btnConfirmarEnvio');
    btn.disabled = true; btn.textContent = 'Enviando...';

    try {
        const r = await fetch('api/enviar_correo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cotizacion_id:  cotizacionActual.id,
                asunto,
                mensaje_texto:  mensaje,
                plantilla_id:   disenoSeleccionado ? disenoSeleccionado.id : null
            })
        });
        const d = await r.json();
        if (d.success) {
            cerrarModalEnviar();
            showToast('✅ Correo enviado a ' + email, 'success');
        } else {
            showToast(d.error || 'Error al enviar', 'error');
        }
    } catch(e) { showToast('Error de conexión', 'error'); }

    btn.disabled = false;
    btn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Enviar cotización`;
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
    [
        ['modalVer',    cerrarModalVer],
        ['modalEnviar', cerrarModalEnviar]
    ].forEach(([id, fn]) => {
        document.getElementById(id)?.addEventListener('click', function(e) {
            if (e.target === this) fn();
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (document.getElementById('modalVer')?.classList.contains('show'))    cerrarModalVer();
        if (document.getElementById('modalEnviar')?.classList.contains('show')) cerrarModalEnviar();
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
