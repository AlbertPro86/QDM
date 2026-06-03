<?php
/**
 * CRM QUANTUN Digital — API de datos del Portal Cliente
 * Requiere sesión de portal activa.
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

// ─── Guard de sesión ──────────────────────────────────────────────────────────
if (empty($_SESSION['portal_logged_in']) || empty($_SESSION['portal_cliente_id'])) {
    pdJson(['error' => 'No autorizado'], 401);
}

$cid = (int)$_SESSION['portal_cliente_id'];
$pdo = db(); // ← debe ir ANTES de cualquier uso de $pdo

// Auto-migraciones (por si el cliente llegó sin pasar por portal_auth)
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_password VARCHAR(255) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_activo TINYINT(1) NOT NULL DEFAULT 1"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE clientes ADD COLUMN portal_ultimo_acceso DATETIME DEFAULT NULL"); } catch (PDOException $e) {}

// Verificación en vivo: si el admin bloqueó al cliente, la sesión queda inválida
try {
    $_chkStmt = $pdo->prepare("SELECT portal_activo FROM clientes WHERE id = ?");
    $_chkStmt->execute([$cid]);
    $_chkRow = $_chkStmt->fetch();
    if ($_chkRow && !(int)$_chkRow['portal_activo']) {
        unset($_SESSION['portal_logged_in'], $_SESSION['portal_cliente_id'],
              $_SESSION['portal_nombre'], $_SESSION['portal_email']);
        pdJson(['error' => 'Acceso revocado. Comunícate con QUANTUN Digital.', 'revocado' => true], 403);
    }
} catch (PDOException $e) { /* columna aún no existe — ignorar */ }

$action = $_GET['action'] ?? '';

// ─── PERFIL ───────────────────────────────────────────────────────────────────
if ($action === 'perfil') {
    $stmt = $pdo->prepare("
        SELECT nombre_comercial, nit_cedula, email_contacto, email_facturacion,
               telefono, direccion, persona_contacto, created_at
        FROM clientes WHERE id = ?
    ");
    $stmt->execute([$cid]);
    $row = $stmt->fetch();
    if ($row) {
        // Detectar si tiene contraseña personalizada (columna puede no existir)
        try {
            $pwdStmt = $pdo->prepare("SELECT portal_password FROM clientes WHERE id = ?");
            $pwdStmt->execute([$cid]);
            $pwdRow = $pwdStmt->fetch();
            $row['has_custom_password'] = !empty($pwdRow['portal_password']) ? 1 : 0;
        } catch (PDOException $e) {
            $row['has_custom_password'] = 0;
        }
    }
    pdJson(['success' => true, 'data' => $row]);
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
        ORDER BY FIELD(cs.estado,'activo','suspendido','cancelado'), cs.fecha_vencimiento ASC
    ");
    $stmt->execute([$cid]);
    pdJson(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── HISTORIAL ────────────────────────────────────────────────────────────────
if ($action === 'historial') {
    $limite = min((int)($_GET['limite'] ?? 30), 200);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    $stmt = $pdo->prepare("
        SELECT t.id,
               COALESCE(t.titulo, t.concepto) AS titulo,
               t.concepto, t.monto, t.estado, t.frecuencia,
               t.fecha_vencimiento, t.fecha_pago, t.created_at,
               COALESCE(
                   (SELECT CASE
                               WHEN cs.paquete_id IS NOT NULL AND pkg.nombre IS NOT NULL THEN pkg.nombre
                               WHEN cs.nombre_display IS NOT NULL AND cs.nombre_display != '' THEN cs.nombre_display
                               ELSE s2.nombre
                           END
                    FROM cliente_servicios cs
                    LEFT JOIN paquetes pkg ON cs.paquete_id = pkg.id
                    JOIN servicios s2 ON cs.servicio_id = s2.id
                    WHERE cs.cliente_id = t.cliente_id AND cs.servicio_id = t.servicio_id
                    ORDER BY cs.id DESC LIMIT 1),
                   s.nombre
               ) AS servicio_nombre
        FROM transacciones t
        LEFT JOIN servicios s ON t.servicio_id = s.id
        WHERE t.cliente_id = ? AND t.tipo = 'ingreso'
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$cid, $limite, $offset]);
    $rows = $stmt->fetchAll();

    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM transacciones WHERE cliente_id = ? AND tipo='ingreso'");
    $cntStmt->execute([$cid]);
    pdJson(['success' => true, 'data' => $rows, 'total' => (int)$cntStmt->fetchColumn()]);
}

// ─── DOCUMENTOS ───────────────────────────────────────────────────────────────
if ($action === 'documentos') {
    $stmt = $pdo->prepare("
        SELECT id, nombre_archivo, tipo_archivo, archivo_url, descripcion, created_at
        FROM clientes_archivos WHERE cliente_id = ? ORDER BY created_at DESC
    ");
    $stmt->execute([$cid]);
    $archivos = $stmt->fetchAll();

    $stmt2 = $pdo->prepare("
        SELECT t.id, COALESCE(t.titulo, t.concepto) AS nombre_archivo,
               t.factura_path, t.documento_path, t.imagen_path, t.fecha_pago, t.created_at
        FROM transacciones t
        WHERE t.cliente_id = ? AND t.tipo='ingreso'
          AND (t.factura_path IS NOT NULL OR t.documento_path IS NOT NULL OR t.imagen_path IS NOT NULL)
        ORDER BY t.created_at DESC
    ");
    $stmt2->execute([$cid]);
    pdJson(['success' => true, 'archivos' => $archivos, 'tx_docs' => $stmt2->fetchAll()]);
}

// ─── STATS ────────────────────────────────────────────────────────────────────
if ($action === 'stats') {
    $s1 = $pdo->prepare("SELECT COUNT(*) FROM cliente_servicios WHERE cliente_id=? AND estado='activo'");
    $s1->execute([$cid]);
    $nActivos = (int)$s1->fetchColumn();

    $s2 = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE cliente_id=? AND tipo='ingreso' AND estado='pagado'");
    $s2->execute([$cid]);
    $totalPagado = (float)$s2->fetchColumn();

    $s3 = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE cliente_id=? AND tipo='ingreso' AND estado IN('pendiente','vencido')");
    $s3->execute([$cid]);
    $totalPend = (float)$s3->fetchColumn();

    $s4 = $pdo->prepare("
        SELECT MIN(fecha_vencimiento) AS prox, DATEDIFF(MIN(fecha_vencimiento), CURDATE()) AS dias
        FROM cliente_servicios
        WHERE cliente_id=? AND estado='activo' AND LOWER(COALESCE(frecuencia,'')) NOT IN('unico','único')
          AND fecha_vencimiento IS NOT NULL
    ");
    $s4->execute([$cid]);
    $renRow = $s4->fetch();

    pdJson([
        'success'            => true,
        'servicios_activos'  => $nActivos,
        'total_pagado'       => $totalPagado,
        'total_pendiente'    => $totalPend,
        'proxima_renovacion' => $renRow['prox'] ?? null,
        'dias_renovacion'    => isset($renRow['dias']) && $renRow['prox'] ? (int)$renRow['dias'] : null,
    ]);
}

pdJson(['error' => 'Acción no válida'], 400);
