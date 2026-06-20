<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$r = $_GET['r'] ?? 'all';

// ── Auto-migración ──────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS agenda_columnas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL DEFAULT 'Nueva lista',
    posicion   INT NOT NULL DEFAULT 0,
    color      VARCHAR(20)  DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS agenda_tarjetas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    columna_id   INT NOT NULL,
    titulo       VARCHAR(500) NOT NULL,
    descripcion  TEXT DEFAULT NULL,
    etiquetas    JSON DEFAULT NULL,
    prioridad    VARCHAR(10) DEFAULT 'media',
    fecha_venc   DATE DEFAULT NULL,
    posicion     INT NOT NULL DEFAULT 0,
    archivada    TINYINT(1) DEFAULT 0,
    completada   TINYINT(1) DEFAULT 0,
    completada_at DATETIME DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS agenda_checklist (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tarjeta_id INT NOT NULL,
    texto      VARCHAR(500) NOT NULL,
    hecho      TINYINT(1) DEFAULT 0,
    posicion   INT NOT NULL DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS agenda_adjuntos (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tarjeta_id INT NOT NULL,
    tipo       VARCHAR(10) NOT NULL DEFAULT 'archivo',
    nombre     VARCHAR(255) NOT NULL,
    url        VARCHAR(1000) NOT NULL,
    mime       VARCHAR(100) DEFAULT NULL,
    peso       INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS agenda_comentarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tarjeta_id INT NOT NULL,
    autor      VARCHAR(100) NOT NULL DEFAULT 'Usuario',
    texto      TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Seed columnas iniciales si la tabla está vacía
$count = $pdo->query("SELECT COUNT(*) FROM agenda_columnas")->fetchColumn();
if ((int)$count === 0) {
    $pdo->exec("INSERT INTO agenda_columnas (nombre, posicion) VALUES
        ('Ideas', 0), ('Por hacer', 1), ('En progreso', 2), ('Hecho', 3)");
}

// ── GET all ─────────────────────────────────────────────────────────────
if ($method === 'GET' && $r === 'all') {
    $columnas = $pdo->query("SELECT * FROM agenda_columnas ORDER BY posicion")->fetchAll();

    $tarjetas = $pdo->query("
        SELECT t.*,
            (SELECT COUNT(*) FROM agenda_checklist WHERE tarjeta_id = t.id) AS chk_total,
            (SELECT COUNT(*) FROM agenda_checklist WHERE tarjeta_id = t.id AND hecho = 1) AS chk_done,
            (SELECT COUNT(*) FROM agenda_adjuntos WHERE tarjeta_id = t.id) AS adj_count
        FROM agenda_tarjetas t
        WHERE t.archivada = 0
        ORDER BY t.posicion
    ")->fetchAll();

    foreach ($tarjetas as &$t) {
        $t['etiquetas'] = $t['etiquetas'] ? json_decode($t['etiquetas'], true) : [];
    }

    jsonResponse(['success' => true, 'columnas' => $columnas, 'tarjetas' => $tarjetas]);
}

// ── GET detail ──────────────────────────────────────────────────────────
if ($method === 'GET' && $r === 'detail') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

    $card = $pdo->prepare("SELECT * FROM agenda_tarjetas WHERE id = ?");
    $card->execute([$id]);
    $card = $card->fetch();
    if (!$card) jsonResponse(['error' => 'No encontrada'], 404);
    $card['etiquetas'] = $card['etiquetas'] ? json_decode($card['etiquetas'], true) : [];

    $chk = $pdo->prepare("SELECT * FROM agenda_checklist WHERE tarjeta_id = ? ORDER BY posicion");
    $chk->execute([$id]);

    $adj = $pdo->prepare("SELECT * FROM agenda_adjuntos WHERE tarjeta_id = ? ORDER BY created_at DESC");
    $adj->execute([$id]);

    $com = $pdo->prepare("SELECT * FROM agenda_comentarios WHERE tarjeta_id = ? ORDER BY created_at ASC");
    $com->execute([$id]);

    jsonResponse(['success' => true, 'card' => $card, 'checklist' => $chk->fetchAll(), 'adjuntos' => $adj->fetchAll(), 'comentarios' => $com->fetchAll()]);
}

// ── COLUMNAS ─────────────────────────────────────────────────────────────
if ($r === 'col') {
    $input = in_array($method, ['POST','PUT']) ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

    if ($method === 'POST') {
        $maxPos = $pdo->query("SELECT COALESCE(MAX(posicion),0)+1 FROM agenda_columnas")->fetchColumn();
        $color = $input['color'] ?? null;
        if ($color !== null && !preg_match('/^#[0-9a-fA-F]{3,8}$|^[a-zA-Z]{3,30}$/', $color)) {
            $color = null;
        }
        $stmt = $pdo->prepare("INSERT INTO agenda_columnas (nombre, posicion, color) VALUES (?,?,?)");
        $stmt->execute([
            substr(trim($input['nombre'] ?? 'Nueva lista'), 0, 100),
            (int)($input['posicion'] ?? $maxPos),
            $color,
        ]);
        $newId = $pdo->lastInsertId();
        $col = $pdo->prepare("SELECT * FROM agenda_columnas WHERE id = ?");
        $col->execute([$newId]);
        jsonResponse(['success' => true, 'id' => $newId, 'col' => $col->fetch()]);
    }

    if ($method === 'PUT') {
        $id = intval($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $fields = []; $vals = [];
        foreach (['nombre','posicion','color'] as $f) {
            if (array_key_exists($f, $input)) {
                $val = $input[$f];
                if ($f === 'color' && $val !== null && !preg_match('/^#[0-9a-fA-F]{3,8}$|^[a-zA-Z]{3,30}$/', $val)) {
                    $val = null;
                }
                $fields[] = "$f=?"; $vals[] = $val;
            }
        }
        if ($fields) { $vals[] = $id; $pdo->prepare("UPDATE agenda_columnas SET ".implode(',',$fields)." WHERE id=?")->execute($vals); }
        jsonResponse(['success' => true]);
    }

    if ($method === 'DELETE') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $pdo->prepare("DELETE FROM agenda_checklist    WHERE tarjeta_id IN (SELECT id FROM agenda_tarjetas WHERE columna_id=?)")->execute([$id]);
        $pdo->prepare("DELETE FROM agenda_adjuntos    WHERE tarjeta_id IN (SELECT id FROM agenda_tarjetas WHERE columna_id=?)")->execute([$id]);
        $pdo->prepare("DELETE FROM agenda_comentarios WHERE tarjeta_id IN (SELECT id FROM agenda_tarjetas WHERE columna_id=?)")->execute([$id]);
        $pdo->prepare("DELETE FROM agenda_tarjetas    WHERE columna_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM agenda_columnas   WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
    }
}

// ── TARJETAS ─────────────────────────────────────────────────────────────
if ($r === 'card') {
    $input = in_array($method, ['POST','PUT']) ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

    if ($method === 'POST') {
        $colId = intval($input['columna_id'] ?? 0);
        if (!$colId) jsonResponse(['error' => 'columna_id requerido'], 400);
        $colExists = $pdo->prepare("SELECT 1 FROM agenda_columnas WHERE id = ?");
        $colExists->execute([$colId]);
        if (!$colExists->fetchColumn()) jsonResponse(['error' => 'Columna no existe'], 404);
        $maxPos = $pdo->prepare("SELECT COALESCE(MAX(posicion),0)+1 FROM agenda_tarjetas WHERE columna_id=?");
        $maxPos->execute([$colId]);
        $pos = $maxPos->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO agenda_tarjetas (columna_id, titulo, posicion) VALUES (?,?,?)");
        $stmt->execute([$colId, substr(trim($input['titulo'] ?? 'Sin título'), 0, 500), (int)$pos]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $id = intval($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $allowed = ['columna_id','titulo','descripcion','etiquetas','prioridad','fecha_venc','posicion','archivada','completada'];
        $fields = []; $vals = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $input)) {
                $fields[] = "$f=?";
                $vals[] = ($f === 'etiquetas') ? json_encode($input[$f]) : $input[$f];
            }
        }
        if (isset($input['completada'])) {
            if ($input['completada']) { $fields[] = "completada_at=?"; $vals[] = date('Y-m-d H:i:s'); }
            else { $fields[] = "completada_at=NULL"; }
        }
        if ($fields) { $vals[] = $id; $pdo->prepare("UPDATE agenda_tarjetas SET ".implode(',',$fields)." WHERE id=?")->execute($vals); }
        jsonResponse(['success' => true]);
    }

    if ($method === 'DELETE') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $adjs = $pdo->prepare("SELECT url,tipo FROM agenda_adjuntos WHERE tarjeta_id=?");
        $adjs->execute([$id]);
        foreach ($adjs->fetchAll() as $a) {
            if ($a['tipo'] === 'archivo') {
                $realBase = realpath(__DIR__ . '/../uploads');
                $realPath = realpath(__DIR__ . '/../' . $a['url']);
                if ($realBase && $realPath && str_starts_with($realPath, $realBase)) {
                    unlink($realPath);
                }
            }
        }
        $pdo->prepare("DELETE FROM agenda_checklist    WHERE tarjeta_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM agenda_adjuntos    WHERE tarjeta_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM agenda_comentarios WHERE tarjeta_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM agenda_tarjetas    WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
    }
}

// ── CHECKLIST ─────────────────────────────────────────────────────────────
if ($r === 'chk') {
    $input = in_array($method, ['POST','PUT']) ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

    if ($method === 'POST') {
        $tid = intval($input['tarjeta_id'] ?? 0);
        if (!$tid) jsonResponse(['error' => 'tarjeta_id requerido'], 400);
        $maxPos = $pdo->prepare("SELECT COALESCE(MAX(posicion),0)+1 FROM agenda_checklist WHERE tarjeta_id=?");
        $maxPos->execute([$tid]);
        $stmt = $pdo->prepare("INSERT INTO agenda_checklist (tarjeta_id, texto, posicion) VALUES (?,?,?)");
        $stmt->execute([$tid, substr(trim($input['texto'] ?? ''), 0, 500), (int)$maxPos->fetchColumn()]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $id = intval($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $fields = []; $vals = [];
        foreach (['texto','hecho','posicion'] as $f) {
            if (array_key_exists($f, $input)) { $fields[] = "$f=?"; $vals[] = $input[$f]; }
        }
        if ($fields) { $vals[] = $id; $pdo->prepare("UPDATE agenda_checklist SET ".implode(',',$fields)." WHERE id=?")->execute($vals); }
        jsonResponse(['success' => true]);
    }

    if ($method === 'DELETE') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $pdo->prepare("DELETE FROM agenda_checklist WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
    }
}

// ── COMENTARIOS ───────────────────────────────────────────────────────────
if ($r === 'comment') {
    $input = in_array($method, ['POST']) ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

    if ($method === 'POST') {
        $tid   = intval($input['tarjeta_id'] ?? 0);
        $texto = trim($input['texto'] ?? '');
        $autor = trim($input['autor'] ?? 'Usuario');
        if (!$tid || !$texto) jsonResponse(['error' => 'tarjeta_id y texto requeridos'], 400);
        $stmt = $pdo->prepare("INSERT INTO agenda_comentarios (tarjeta_id, autor, texto) VALUES (?,?,?)");
        $stmt->execute([$tid, substr($autor, 0, 100), substr($texto, 0, 2000)]);
        $newId = $pdo->lastInsertId();
        $row = $pdo->prepare("SELECT * FROM agenda_comentarios WHERE id=?");
        $row->execute([$newId]);
        jsonResponse(['success' => true, 'comentario' => $row->fetch()]);
    }

    if ($method === 'DELETE') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $pdo->prepare("DELETE FROM agenda_comentarios WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
    }
}

jsonResponse(['error' => 'Ruta no encontrada'], 404);
