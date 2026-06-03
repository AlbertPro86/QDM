<?php
/**
 * CRM QUANTUN Digital — Módulo Archivo
 * Agrega archivos de todas las fuentes: clientes_archivos, transacciones, RUTs
 *
 * GET  ?action=list   [&cliente_id=X] [&type=all|image|pdf|doc] [&year=YYYY] [&month=MM] [&q=texto]
 * GET  ?action=stats
 * GET  ?action=clientes
 * DELETE ?id=X&source=clientes_archivos|transacciones_factura|transacciones_doc|transacciones_img|rut
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

// ── Helpers ──────────────────────────────────────────────────────────────────
function formatBytes(int $bytes): string {
    if ($bytes <= 0) return '—';
    if ($bytes < 1024)        return $bytes . ' B';
    if ($bytes < 1048576)     return round($bytes / 1024, 1)      . ' KB';
    if ($bytes < 1073741824)  return round($bytes / 1048576, 2)   . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}

function mimeToCategory(string $mime): string {
    if (str_starts_with($mime, 'image/'))          return 'image';
    if ($mime === 'application/pdf')               return 'pdf';
    if (str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') || $mime === 'text/csv') return 'excel';
    if (str_contains($mime, 'word') || str_contains($mime, 'document')) return 'word';
    return 'doc';
}

function extToCategory(string $ext): string {
    $ext = strtolower($ext);
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) return 'image';
    if ($ext === 'pdf')                                           return 'pdf';
    if (in_array($ext, ['xls','xlsx','csv']))                    return 'excel';
    if (in_array($ext, ['doc','docx']))                         return 'word';
    return 'doc';
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id     = intval($_GET['id'] ?? 0);
    $source = $_GET['source'] ?? '';

    if (!$id || !$source) jsonResponse(['error' => 'Parámetros incompletos'], 400);

    try {
        if ($source === 'clientes_archivos') {
            $s = $pdo->prepare("SELECT archivo_url FROM clientes_archivos WHERE id = ?");
            $s->execute([$id]);
            $row = $s->fetch();
            if (!$row) jsonResponse(['error' => 'Archivo no encontrado'], 404);
            $path = __DIR__ . '/../' . $row['archivo_url'];
            if (file_exists($path)) @unlink($path);
            $pdo->prepare("DELETE FROM clientes_archivos WHERE id = ?")->execute([$id]);

        } elseif (in_array($source, ['transacciones_factura','transacciones_doc','transacciones_img'])) {
            $col = ['transacciones_factura'=>'factura_path','transacciones_doc'=>'documento_path','transacciones_img'=>'imagen_path'][$source];
            $s = $pdo->prepare("SELECT {$col} FROM transacciones WHERE id = ?");
            $s->execute([$id]);
            $row = $s->fetch();
            if (!$row) jsonResponse(['error' => 'Transacción no encontrada'], 404);
            $path = __DIR__ . '/../' . $row[$col];
            if ($path && file_exists($path)) @unlink($path);
            $pdo->prepare("UPDATE transacciones SET {$col} = NULL WHERE id = ?")->execute([$id]);

        } elseif ($source === 'rut') {
            $s = $pdo->prepare("SELECT rut_url FROM clientes WHERE id = ?");
            $s->execute([$id]);
            $row = $s->fetch();
            if (!$row) jsonResponse(['error' => 'Cliente no encontrado'], 404);
            $path = __DIR__ . '/../' . ($row['rut_url'] ?? '');
            if ($path && file_exists($path)) @unlink($path);
            $pdo->prepare("UPDATE clientes SET rut_url = NULL WHERE id = ?")->execute([$id]);
        } else {
            jsonResponse(['error' => 'Fuente no válida'], 400);
        }

        jsonResponse(['success' => true, 'message' => 'Archivo eliminado']);
    } catch (\Throwable $e) {
        jsonResponse(['error' => 'Error al eliminar: ' . $e->getMessage()], 500);
    }
}

// ── GET ───────────────────────────────────────────────────────────────────────

// Todos los clientes con conteo de archivos (incluyendo los que no tienen)
if ($action === 'all_clients') {
    try {
        $rows = $pdo->query("
            SELECT c.id, c.nombre_comercial,
                (SELECT COUNT(*) FROM clientes_archivos WHERE cliente_id = c.id) AS n_docs,
                (SELECT COALESCE(SUM(
                    (CASE WHEN factura_path   IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN documento_path IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN imagen_path    IS NOT NULL THEN 1 ELSE 0 END)
                ),0) FROM transacciones WHERE cliente_id = c.id) AS n_tx,
                (CASE WHEN c.rut_url IS NOT NULL AND c.rut_url != '' THEN 1 ELSE 0 END) AS n_rut,
                c.created_at
            FROM clientes c
            ORDER BY c.nombre_comercial ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['total_archivos'] = (int)$r['n_docs'] + (int)$r['n_tx'] + (int)$r['n_rut'];
            unset($r['n_docs'], $r['n_tx'], $r['n_rut']);
        }
        jsonResponse(['success' => true, 'data' => $rows]);
    } catch (\Throwable $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

// Papelera: archivos de clientes eliminados
if ($action === 'papelera') {
    $archivos = [];

    // clientes_archivos sin cliente activo
    try {
        $s = $pdo->query("
            SELECT ca.id, ca.cliente_id, ca.nombre_archivo, ca.archivo_url,
                   ca.tipo_archivo, ca.peso_archivo, ca.descripcion, ca.fecha_documento, ca.created_at,
                   CONCAT('[Eliminado #', ca.cliente_id, ']') AS cliente_nombre
            FROM clientes_archivos ca
            WHERE ca.cliente_id NOT IN (SELECT id FROM clientes)
            ORDER BY ca.created_at DESC
        ");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ext = strtolower(pathinfo($row['archivo_url'], PATHINFO_EXTENSION));
            $cat = $row['tipo_archivo'] ? mimeToCategory($row['tipo_archivo']) : extToCategory($ext);
            $archivos[] = [
                'id'             => (int)$row['id'],
                'source'         => 'clientes_archivos',
                'cliente_id'     => (int)$row['cliente_id'],
                'cliente_nombre' => $row['cliente_nombre'],
                'nombre'         => $row['nombre_archivo'],
                'url'            => $row['archivo_url'],
                'tipo'           => $row['tipo_archivo'] ?? 'application/octet-stream',
                'categoria'      => $cat,
                'peso'           => (int)$row['peso_archivo'],
                'peso_fmt'       => formatBytes((int)$row['peso_archivo']),
                'descripcion'    => $row['descripcion'] ?? '',
                'fecha'          => $row['fecha_documento'] ?: substr($row['created_at'], 0, 10),
                'fecha_full'     => $row['created_at'],
            ];
        }
    } catch (\Throwable $e) {}

    // transacciones sin cliente activo
    try {
        $s2 = $pdo->query("
            SELECT t.id, t.cliente_id, t.titulo, t.concepto, t.factura_path, t.documento_path, t.imagen_path, t.created_at
            FROM transacciones t
            WHERE t.cliente_id IS NOT NULL
              AND t.cliente_id NOT IN (SELECT id FROM clientes)
              AND (t.factura_path IS NOT NULL OR t.documento_path IS NOT NULL OR t.imagen_path IS NOT NULL)
            ORDER BY t.created_at DESC
        ");
        foreach ($s2->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $colMap = [
                'factura_path'   => ['transacciones_factura','Factura'],
                'documento_path' => ['transacciones_doc','Documento'],
                'imagen_path'    => ['transacciones_img','Imagen'],
            ];
            foreach ($colMap as $col => [$srcKey,$label]) {
                if (empty($row[$col])) continue;
                $path = $row[$col];
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $cat  = extToCategory($ext);
                $fullPath = __DIR__ . '/../' . $path;
                $peso = file_exists($fullPath) ? filesize($fullPath) : 0;
                $nombre = $label . ' — ' . ($row['titulo'] ?: $row['concepto'] ?: 'Sin concepto');
                $archivos[] = [
                    'id'             => (int)$row['id'],
                    'source'         => $srcKey,
                    'cliente_id'     => (int)$row['cliente_id'],
                    'cliente_nombre' => '[Eliminado #' . $row['cliente_id'] . ']',
                    'nombre'         => $nombre,
                    'url'            => $path,
                    'tipo'           => ($ext==='pdf'?'application/pdf':(in_array($ext,['jpg','jpeg','png','webp','gif'])?"image/$ext":'application/octet-stream')),
                    'categoria'      => $cat,
                    'peso'           => $peso,
                    'peso_fmt'       => formatBytes($peso),
                    'descripcion'    => $row['concepto'] ?? '',
                    'fecha'          => substr($row['created_at'],0,10),
                    'fecha_full'     => $row['created_at'],
                ];
            }
        }
    } catch (\Throwable $e) {}

    usort($archivos, fn($a,$b) => strcmp($b['fecha_full'],$a['fecha_full']));
    jsonResponse(['success'=>true,'data'=>$archivos,'total'=>count($archivos)]);
}

// Lista de clientes que tienen archivos (backward compat)
if ($action === 'clientes') {
    $rows = $pdo->query("
        SELECT DISTINCT c.id, c.nombre_comercial
        FROM clientes c
        WHERE c.id IN (
            SELECT DISTINCT cliente_id FROM clientes_archivos
            UNION
            SELECT DISTINCT cliente_id FROM transacciones
                WHERE cliente_id IS NOT NULL AND (factura_path IS NOT NULL OR documento_path IS NOT NULL OR imagen_path IS NOT NULL)
            UNION
            SELECT DISTINCT id FROM clientes WHERE rut_url IS NOT NULL AND rut_url != ''
        )
        ORDER BY c.nombre_comercial ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'data' => $rows]);
}

// Estadísticas globales
if ($action === 'stats') {
    try {
        $totalArchivos = (int)$pdo->query("SELECT COUNT(*) FROM clientes_archivos")->fetchColumn();
        $totalPeso     = (int)($pdo->query("SELECT COALESCE(SUM(peso_archivo),0) FROM clientes_archivos")->fetchColumn() ?? 0);
        $porTipo = $pdo->query("
            SELECT tipo_archivo, COUNT(*) AS cnt FROM clientes_archivos GROUP BY tipo_archivo
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Contar archivos de transacciones
        try {
            $txFiles = (int)$pdo->query("
                SELECT SUM(
                    (CASE WHEN factura_path IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN documento_path IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN imagen_path IS NOT NULL THEN 1 ELSE 0 END)
                ) FROM transacciones
            ")->fetchColumn();
        } catch (\Throwable $e) { $txFiles = 0; }

        // Contar RUTs
        try {
            $rutFiles = (int)$pdo->query("SELECT COUNT(*) FROM clientes WHERE rut_url IS NOT NULL AND rut_url != ''")->fetchColumn();
        } catch (\Throwable $e) { $rutFiles = 0; }

        jsonResponse([
            'success'        => true,
            'total_archivos' => $totalArchivos + $txFiles + $rutFiles,
            'total_peso'     => formatBytes($totalPeso),
            'por_tipo'       => $porTipo,
        ]);
    } catch (\Throwable $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

// Lista de archivos con filtros
$clienteId = intval($_GET['cliente_id'] ?? 0);
$typeFilter = $_GET['type'] ?? 'all';
$yearFilter = intval($_GET['year'] ?? 0);
$monthFilter = intval($_GET['month'] ?? 0);
$q = trim($_GET['q'] ?? '');
$archivos = [];

// ── Fuente 1: clientes_archivos ───────────────────────────────────────────────
try {
    $where = ['1=1'];
    $params = [];

    if ($clienteId) { $where[] = 'ca.cliente_id = ?'; $params[] = $clienteId; }
    if ($yearFilter)  { $where[] = 'YEAR(ca.created_at) = ?';  $params[] = $yearFilter; }
    if ($monthFilter) { $where[] = 'MONTH(ca.created_at) = ?'; $params[] = $monthFilter; }
    if ($q)           { $where[] = '(ca.nombre_archivo LIKE ? OR ca.descripcion LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

    $sql = "
        SELECT ca.id, ca.cliente_id, ca.nombre_archivo, ca.archivo_url,
               ca.tipo_archivo, ca.peso_archivo, ca.descripcion, ca.fecha_documento, ca.created_at,
               c.nombre_comercial AS cliente_nombre
        FROM clientes_archivos ca
        LEFT JOIN clientes c ON c.id = ca.cliente_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY ca.created_at DESC
    ";
    $s = $pdo->prepare($sql);
    $s->execute($params);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ext = strtolower(pathinfo($row['archivo_url'], PATHINFO_EXTENSION));
        $cat = $row['tipo_archivo'] ? mimeToCategory($row['tipo_archivo']) : extToCategory($ext);
        if ($typeFilter !== 'all' && $cat !== $typeFilter) continue;
        $archivos[] = [
            'id'             => (int)$row['id'],
            'source'         => 'clientes_archivos',
            'cliente_id'     => (int)$row['cliente_id'],
            'cliente_nombre' => $row['cliente_nombre'] ?? '—',
            'nombre'         => $row['nombre_archivo'],
            'url'            => $row['archivo_url'],
            'tipo'           => $row['tipo_archivo'] ?? 'application/octet-stream',
            'categoria'      => $cat,
            'peso'           => (int)$row['peso_archivo'],
            'peso_fmt'       => formatBytes((int)$row['peso_archivo']),
            'descripcion'    => $row['descripcion'] ?? '',
            'fecha'          => $row['fecha_documento'] ?: substr($row['created_at'], 0, 10),
            'fecha_full'     => $row['created_at'],
        ];
    }
} catch (\Throwable $e) {}

// ── Fuente 2: transacciones (factura_path, documento_path, imagen_path) ──────
try {
    $where2 = ["(t.factura_path IS NOT NULL OR t.documento_path IS NOT NULL OR t.imagen_path IS NOT NULL)"];
    $params2 = [];

    if ($clienteId) { $where2[] = 't.cliente_id = ?'; $params2[] = $clienteId; }
    if ($yearFilter)  { $where2[] = 'YEAR(t.created_at) = ?';  $params2[] = $yearFilter; }
    if ($monthFilter) { $where2[] = 'MONTH(t.created_at) = ?'; $params2[] = $monthFilter; }

    $sql2 = "
        SELECT t.id, t.cliente_id, t.titulo, t.concepto, t.factura_path, t.documento_path, t.imagen_path,
               t.created_at, c.nombre_comercial AS cliente_nombre
        FROM transacciones t
        LEFT JOIN clientes c ON c.id = t.cliente_id
        WHERE " . implode(' AND ', $where2) . "
        ORDER BY t.created_at DESC
    ";
    $s2 = $pdo->prepare($sql2);
    $s2->execute($params2);
    foreach ($s2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $colMap = [
            'factura_path'   => ['transacciones_factura', 'Factura'],
            'documento_path' => ['transacciones_doc',     'Documento'],
            'imagen_path'    => ['transacciones_img',     'Imagen'],
        ];
        foreach ($colMap as $col => [$srcKey, $label]) {
            if (empty($row[$col])) continue;
            $path = $row[$col];
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $cat  = extToCategory($ext);
            if ($typeFilter !== 'all' && $cat !== $typeFilter) continue;
            $nombre = $label . ' — ' . ($row['titulo'] ?: $row['concepto'] ?: 'Sin concepto');
            if ($q && stripos($nombre, $q) === false) continue;
            // peso del archivo físico
            $fullPath = __DIR__ . '/../' . $path;
            $peso = file_exists($fullPath) ? filesize($fullPath) : 0;
            $archivos[] = [
                'id'             => (int)$row['id'],
                'source'         => $srcKey,
                'cliente_id'     => (int)($row['cliente_id'] ?? 0),
                'cliente_nombre' => $row['cliente_nombre'] ?? 'Sin cliente',
                'nombre'         => $nombre,
                'url'            => $path,
                'tipo'           => ($ext === 'pdf' ? 'application/pdf' : (in_array($ext, ['jpg','jpeg','png','webp','gif']) ? "image/$ext" : 'application/octet-stream')),
                'categoria'      => $cat,
                'peso'           => $peso,
                'peso_fmt'       => formatBytes($peso),
                'descripcion'    => $row['concepto'] ?? '',
                'fecha'          => substr($row['created_at'], 0, 10),
                'fecha_full'     => $row['created_at'],
            ];
        }
    }
} catch (\Throwable $e) {}

// ── Fuente 3: RUTs de clientes ────────────────────────────────────────────────
try {
    $where3 = ["c.rut_url IS NOT NULL AND c.rut_url != ''"];
    $params3 = [];

    if ($clienteId) { $where3[] = 'c.id = ?'; $params3[] = $clienteId; }
    if ($q) { $where3[] = 'c.nombre_comercial LIKE ?'; $params3[] = "%$q%"; }

    $s3 = $pdo->prepare("
        SELECT c.id, c.nombre_comercial, c.rut_url, c.created_at
        FROM clientes c
        WHERE " . implode(' AND ', $where3));
    $s3->execute($params3);
    foreach ($s3->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($typeFilter !== 'all' && $typeFilter !== 'pdf') continue;
        $fullPath = __DIR__ . '/../' . $row['rut_url'];
        $peso = file_exists($fullPath) ? filesize($fullPath) : 0;
        // Extraer fecha del archivo por nombre o created_at
        $fecha = substr($row['created_at'], 0, 10);
        $yf  = intval(date('Y', strtotime($fecha)));
        $mf  = intval(date('m', strtotime($fecha)));
        if ($yearFilter && $yf !== $yearFilter) continue;
        if ($monthFilter && $mf !== $monthFilter) continue;
        $archivos[] = [
            'id'             => (int)$row['id'],
            'source'         => 'rut',
            'cliente_id'     => (int)$row['id'],
            'cliente_nombre' => $row['nombre_comercial'],
            'nombre'         => 'RUT — ' . $row['nombre_comercial'],
            'url'            => $row['rut_url'],
            'tipo'           => 'application/pdf',
            'categoria'      => 'pdf',
            'peso'           => $peso,
            'peso_fmt'       => formatBytes($peso),
            'descripcion'    => 'RUT del cliente',
            'fecha'          => $fecha,
            'fecha_full'     => $row['created_at'],
        ];
    }
} catch (\Throwable $e) {}

// Ordenar por fecha descendente
usort($archivos, fn($a, $b) => strcmp($b['fecha_full'], $a['fecha_full']));

jsonResponse(['success' => true, 'data' => $archivos, 'total' => count($archivos)]);
