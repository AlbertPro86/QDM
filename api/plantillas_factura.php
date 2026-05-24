<?php
/**
 * CRM QUANTUN Digital — API Plantillas de Factura
 * CRUD completo con auto-migración
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

// ── Auto-migración ────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS plantillas_factura (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(120) NOT NULL,
    descripcion      VARCHAR(255) DEFAULT NULL,
    layout_tipo      VARCHAR(30)  NOT NULL DEFAULT 'clasica',
    categoria        VARCHAR(50)  NOT NULL DEFAULT 'cotizacion',
    color_primario   VARCHAR(7)   NOT NULL DEFAULT '#0f172a',
    color_secundario VARCHAR(7)   NOT NULL DEFAULT '#c9f31d',
    fuente           VARCHAR(50)  NOT NULL DEFAULT 'Poppins',
    empresa_nombre   VARCHAR(120) DEFAULT NULL,
    empresa_nit      VARCHAR(50)  DEFAULT NULL,
    empresa_email    VARCHAR(120) DEFAULT NULL,
    empresa_tel      VARCHAR(50)  DEFAULT NULL,
    empresa_dir      VARCHAR(255) DEFAULT NULL,
    logo_url         VARCHAR(500) DEFAULT NULL,
    notas_pie        TEXT         DEFAULT NULL,
    mostrar_banco    TINYINT(1)   NOT NULL DEFAULT 0,
    es_default       TINYINT(1)   NOT NULL DEFAULT 0,
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Migración incremental — añade columnas a tablas existentes
try { $pdo->exec("ALTER TABLE plantillas_factura ADD COLUMN mostrar_banco TINYINT(1) NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE plantillas_factura ADD COLUMN categoria VARCHAR(50) NOT NULL DEFAULT 'cotizacion'"); } catch (PDOException $e) {}

// Seed: crear plantilla default de Orden de Renovación si no existe
try {
    $chkOR = $pdo->query("SELECT COUNT(*) FROM plantillas_factura WHERE categoria = 'orden_renovacion'")->fetchColumn();
    if ($chkOR == 0) {
        $pdo->exec("INSERT INTO plantillas_factura (nombre, descripcion, layout_tipo, categoria, color_primario, color_secundario, fuente, empresa_nombre, empresa_nit, empresa_email, empresa_tel, empresa_dir, notas_pie, mostrar_banco, es_default, activo)
            VALUES ('Orden de Renovación - Default', 'Plantilla predeterminada para órdenes de renovación de servicios', 'clasica', 'orden_renovacion', '#0E0E0C', '#C6F24E', 'Poppins', 'QUANTUN Digital', '', '', '', '', 'Gracias por su preferencia. El pago debe realizarse en los próximos 30 días.', 1, 1, 1)");
    }
} catch (PDOException $e) {}

switch ($method) {

    /* ── GET ─────────────────────────────────────────────────────────── */
    case 'GET':
        // Establecer como default
        if (isset($_GET['set_default'])) {
            $id = (int)$_GET['set_default'];
            $pdo->exec("UPDATE plantillas_factura SET es_default = 0");
            $pdo->prepare("UPDATE plantillas_factura SET es_default = 1 WHERE id = ?")->execute([$id]);
            logActivity($_SESSION['user_id'], 'editar', 'plantillas_factura', $id, "Plantilla establecida como default: ID $id");
            jsonResponse(['success' => true, 'message' => 'Plantilla establecida como predeterminada']);
        }

        $id = $_GET['id'] ?? null;

        if ($id) {
            $s = $pdo->prepare("SELECT * FROM plantillas_factura WHERE id = ? AND activo = 1");
            $s->execute([$id]);
            $row = $s->fetch();
            if (!$row) jsonResponse(['error' => 'Plantilla no encontrada'], 404);
            jsonResponse(['success' => true, 'data' => $row]);
        }

        // Filtro por categoría (opcional)
        $catFilter = $_GET['categoria'] ?? null;
        if ($catFilter) {
            $stmtCat = $pdo->prepare("SELECT * FROM plantillas_factura WHERE activo = 1 AND categoria = ? ORDER BY es_default DESC, nombre ASC");
            $stmtCat->execute([$catFilter]);
            $rows = $stmtCat->fetchAll();
        } else {
            $rows = $pdo->query(
                "SELECT * FROM plantillas_factura WHERE activo = 1 ORDER BY es_default DESC, nombre ASC"
            )->fetchAll();
        }

        jsonResponse(['success' => true, 'data' => $rows, 'total' => count($rows)]);

    /* ── POST ────────────────────────────────────────────────────────── */
    case 'POST':
        $in = json_decode(file_get_contents('php://input'), true);
        if (empty($in['nombre'])) jsonResponse(['error' => 'El nombre es requerido'], 400);

        // Si es default, limpiar los demás
        if (!empty($in['es_default'])) $pdo->exec("UPDATE plantillas_factura SET es_default = 0");

        $s = $pdo->prepare(
            "INSERT INTO plantillas_factura
             (nombre,descripcion,layout_tipo,categoria,color_primario,color_secundario,fuente,
              empresa_nombre,empresa_nit,empresa_email,empresa_tel,empresa_dir,logo_url,notas_pie,mostrar_banco,es_default)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $s->execute([
            $in['nombre'],
            $in['descripcion']      ?? null,
            $in['layout_tipo']      ?? 'clasica',
            $in['categoria']        ?? 'cotizacion',
            $in['color_primario']   ?? '#0f172a',
            $in['color_secundario'] ?? '#c9f31d',
            $in['fuente']           ?? 'Poppins',
            $in['empresa_nombre']   ?? null,
            $in['empresa_nit']      ?? null,
            $in['empresa_email']    ?? null,
            $in['empresa_tel']      ?? null,
            $in['empresa_dir']      ?? null,
            $in['logo_url']         ?? null,
            $in['notas_pie']        ?? null,
            !empty($in['mostrar_banco']) ? 1 : 0,
            !empty($in['es_default']) ? 1 : 0,
        ]);
        $newId = $pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'crear', 'plantillas_factura', $newId, "Nueva plantilla: {$in['nombre']}");

        $s = $pdo->prepare("SELECT * FROM plantillas_factura WHERE id = ?");
        $s->execute([$newId]);
        jsonResponse(['success' => true, 'data' => $s->fetch(), 'message' => 'Plantilla creada'], 201);

    /* ── PUT ─────────────────────────────────────────────────────────── */
    case 'PUT':
        $in = json_decode(file_get_contents('php://input'), true);
        $id = $in['id'] ?? ($_GET['id'] ?? null);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        if (!empty($in['es_default'])) $pdo->exec("UPDATE plantillas_factura SET es_default = 0");

        $allowed = ['nombre','descripcion','layout_tipo','categoria','color_primario','color_secundario','fuente',
                    'empresa_nombre','empresa_nit','empresa_email','empresa_tel','empresa_dir','logo_url','notas_pie','mostrar_banco','es_default'];
        $fields = []; $values = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = ?";
                $values[] = $in[$f];
            }
        }
        if (!$fields) jsonResponse(['error' => 'Sin campos para actualizar'], 400);

        $values[] = $id;
        $pdo->prepare("UPDATE plantillas_factura SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
        logActivity($_SESSION['user_id'], 'editar', 'plantillas_factura', $id, "Plantilla editada: ID $id");

        $s = $pdo->prepare("SELECT * FROM plantillas_factura WHERE id = ?");
        $s->execute([$id]);
        jsonResponse(['success' => true, 'data' => $s->fetch(), 'message' => 'Plantilla actualizada']);

    /* ── DELETE ──────────────────────────────────────────────────────── */
    case 'DELETE':
        $id = $_GET['id'] ?? (json_decode(file_get_contents('php://input'), true)['id'] ?? null);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        // No eliminar si es default
        $row = $pdo->prepare("SELECT es_default FROM plantillas_factura WHERE id = ?");
        $row->execute([$id]);
        $p = $row->fetch();
        if ($p && $p['es_default']) jsonResponse(['error' => 'No puedes eliminar la plantilla predeterminada. Asigna otra primero.'], 400);

        $pdo->prepare("UPDATE plantillas_factura SET activo = 0 WHERE id = ?")->execute([$id]);
        logActivity($_SESSION['user_id'], 'eliminar', 'plantillas_factura', $id, "Plantilla eliminada: ID $id");
        jsonResponse(['success' => true, 'message' => 'Plantilla eliminada']);

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
