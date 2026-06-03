<?php
/**
 * CRM QUANTUN Digital — API de datos del Portal Cliente
 * Requiere sesión de portal activa.
 *
 * GET ?action=perfil        → datos del cliente (sin finanzas internas)
 * GET ?action=servicios     → servicios activos + valor de suscripción
 * GET ?action=historial     → historial de pagos (transacciones ingreso)
 * GET ?action=documentos    → archivos del cliente
 * GET ?action=renovaciones  → próximas renovaciones + estado
 * GET ?action=stats         → métricas resumen para el dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function pdJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ─── Guard ────────────────────────────────────────────────────────────────────
if (empty($_SESSION['portal_logged_in']) || empty($_SESSION['portal_cliente_id'])) {
    pdJson(['error' => 'No autorizado'], 401);
}

$cid    = (int)$_SESSION['portal_cliente_id'];
$pdo    = db();
$action = $_GET['action'] ?? '';

// ─── PERFIL ───────────────────────────────────────────────────────────────────
if ($action === 'perfil') {
    $stmt = $pdo->prepare("
        SELECT nombre_comercial, nit_cedula, email_contacto, email_facturacion,
               telefono, direccion, persona_contacto, created_at,
               (portal_password IS NOT NULL AND portal_password != '') AS has_custom_password
        FROM clientes
        WHERE id = ?
    ");
    $stmt->execute([$cid]);
    pdJson(['success' => true, 'data' => $stmt->fetch()]);
}

// ─── SERVICIOS ────────────────────────────────────────────────────────────────
if ($action === 'servicios') {
    $stmt = $pdo->prepare("
        SELECT
            cs.id,
            cs.estado,
            cs.frecuencia,
            cs.fecha_inicio,
            cs.fecha_vencimiento,
            (cs.monto_renovacion - COALESCE(cs.descuento, 0)) AS valor_suscripcion,
            CASE
                WHEN cs.paquete_id IS NOT NULL AND p.nombre IS NOT NULL THEN p.nombre
                WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
                ELSE s.nombre
            END AS nombre_servicio,
            s.descripcion AS descripcion_servicio,
            DATEDIFF(cs.fecha_vencimiento, CURDATE()) AS dias_para_vencimiento
        FROM cliente_servicios cs
        JOIN servicios s ON cs.servicio_id = s.id
        LEFT JOIN paquetes p ON cs.paquete_id = p.id
        WHERE cs.cliente_id = ?
        ORDER BY
            FIELD(cs.estado, 'activo', 'suspendido', 'cancelado'),
            cs.fecha_vencimiento ASC
    ");
    $stmt->execute([$cid]);
    pdJson(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── HISTORIAL ────────────────────────────────────────────────────────────────
if ($action === 'historial') {
    $limite = min((int)($_GET['limite'] ?? 50), 200);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    $stmt = $pdo->prepare("
        SELECT
            t.id,
            COALESCE(t.titulo, t.concepto) AS titulo,
            t.concepto,
            t.monto,
            t.estado,
            t.frecuencia,
            t.fecha_vencimiento,
            t.fecha_pago,
            t.created_at,
            COALESCE(
                (
                    SELECT CASE
                               WHEN cs.paquete_id IS NOT NULL AND pkg.nombre IS NOT NULL THEN pkg.nombre
                               WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
                               ELSE s2.nombre
                           END
                    FROM cliente_servicios cs
                    LEFT JOIN paquetes pkg ON cs.paquete_id = pkg.id
                    JOIN servicios s2 ON cs.servicio_id = s2.id
                    WHERE cs.cliente_id = t.cliente_id
                      AND cs.servicio_id = t.servicio_id
                    ORDER BY cs.id DESC
                    LIMIT 1
                ),
                s.nombre
            ) AS servicio_nombre
        FROM transacciones t
        LEFT JOIN servicios s ON t.servicio_id = s.id
        WHERE t.cliente_id = ?
          AND t.tipo = 'ingreso'
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$cid, $limite, $offset]);
    $rows = $stmt->fetchAll();

    // Total registros
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM transacciones WHERE cliente_id = ? AND tipo = 'ingreso'");
    $cntStmt->execute([$cid]);
    $total = (int)$cntStmt->fetchColumn();

    pdJson(['success' => true, 'data' => $rows, 'total' => $total]);
}

// ─── DOCUMENTOS ───────────────────────────────────────────────────────────────
if ($action === 'documentos') {
    // Archivos directos del cliente
    $stmt = $pdo->prepare("
        SELECT id, nombre_archivo, tipo_archivo, archivo_url,
               descripcion, created_at, 'archivo' AS origen
        FROM clientes_archivos
        WHERE cliente_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$cid]);
    $archivos = $stmt->fetchAll();

    // Adjuntos de transacciones (facturas, comprobantes)
    $stmt2 = $pdo->prepare("
        SELECT
            t.id,
            COALESCE(t.titulo, t.concepto) AS nombre_archivo,
            'documento' AS tipo_archivo,
            t.factura_path   AS factura_path,
            t.documento_path AS documento_path,
            t.imagen_path    AS imagen_path,
            t.fecha_pago,
            t.created_at,
            'transaccion' AS origen
        FROM transacciones t
        WHERE t.cliente_id = ?
          AND t.tipo = 'ingreso'
          AND (t.factura_path IS NOT NULL OR t.documento_path IS NOT NULL OR t.imagen_path IS NOT NULL)
        ORDER BY t.created_at DESC
    ");
    $stmt2->execute([$cid]);
    $txDocs = $stmt2->fetchAll();

    pdJson([
        'success'  => true,
        'archivos' => $archivos,
        'tx_docs'  => $txDocs,
    ]);
}

// ─── RENOVACIONES ─────────────────────────────────────────────────────────────
if ($action === 'renovaciones') {
    $stmt = $pdo->prepare("
        SELECT
            cs.id,
            cs.estado,
            cs.frecuencia,
            cs.fecha_vencimiento,
            (cs.monto_renovacion - COALESCE(cs.descuento, 0)) AS valor_suscripcion,
            CASE
                WHEN cs.paquete_id IS NOT NULL AND p.nombre IS NOT NULL THEN p.nombre
                WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
                ELSE s.nombre
            END AS nombre_servicio,
            DATEDIFF(cs.fecha_vencimiento, CURDATE()) AS dias_restantes
        FROM cliente_servicios cs
        JOIN servicios s ON cs.servicio_id = s.id
        LEFT JOIN paquetes p ON cs.paquete_id = p.id
        WHERE cs.cliente_id = ?
          AND cs.estado = 'activo'
          AND cs.frecuencia NOT IN ('unico', 'Único')
        ORDER BY cs.fecha_vencimiento ASC
    ");
    $stmt->execute([$cid]);
    pdJson(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── STATS ────────────────────────────────────────────────────────────────────
if ($action === 'stats') {
    // Servicios activos
    $activos = $pdo->prepare("SELECT COUNT(*) FROM cliente_servicios WHERE cliente_id = ? AND estado = 'activo'");
    $activos->execute([$cid]);

    // Total pagado histórico
    $pagado = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE cliente_id = ? AND tipo='ingreso' AND estado='pagado'");
    $pagado->execute([$cid]);

    // Pendientes
    $pendiente = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE cliente_id = ? AND tipo='ingreso' AND estado IN ('pendiente','vencido')");
    $pendiente->execute([$cid]);

    // Próxima renovación
    $proxRen = $pdo->prepare("
        SELECT MIN(fecha_vencimiento), DATEDIFF(MIN(fecha_vencimiento), CURDATE()) AS dias
        FROM cliente_servicios
        WHERE cliente_id = ? AND estado='activo' AND frecuencia NOT IN ('unico','Único')
    ");
    $proxRen->execute([$cid]);
    $renRow = $proxRen->fetch();

    pdJson([
        'success'            => true,
        'servicios_activos'  => (int)$activos->fetchColumn(),
        'total_pagado'       => (float)$pagado->fetchColumn(),
        'total_pendiente'    => (float)$pendiente->fetchColumn(),
        'proxima_renovacion' => $renRow['MIN(fecha_vencimiento)'] ?? null,
        'dias_renovacion'    => isset($renRow['dias']) ? (int)$renRow['dias'] : null,
    ]);
}

pdJson(['error' => 'Acción no válida'], 400);
