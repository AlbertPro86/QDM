<?php
/**
 * CRM QUANTUN Digital - API de Servicios
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();
try { $pdo->exec("ALTER TABLE servicios ADD COLUMN orden INT NOT NULL DEFAULT 0"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE sub_servicios ADD COLUMN orden INT NOT NULL DEFAULT 0"); } catch(PDOException $e){}

switch ($method) {
    case 'GET':
        $activo = $_GET['activo'] ?? null;
        $where = $activo !== null ? "WHERE activo = " . ($activo ? '1' : '0') : '';
        $stmt = $pdo->query("SELECT * FROM servicios {$where} ORDER BY orden ASC, nombre ASC");
        $svcs = $stmt->fetchAll();

        if ($svcs) {
            $ids = array_column($svcs, 'id');
            $ph  = implode(',', array_fill(0, count($ids), '?'));

            $subStmt = $pdo->prepare("SELECT * FROM sub_servicios WHERE servicio_id IN ($ph) AND activo = 1 ORDER BY id ASC");
            $subStmt->execute($ids);
            $allSubs = $subStmt->fetchAll();

            // Features de servicios
            $featStmt = $pdo->prepare("SELECT * FROM servicio_features WHERE servicio_id IN ($ph) AND sub_servicio_id IS NULL ORDER BY orden ASC, id ASC");
            $featStmt->execute($ids);
            $featMap = [];
            foreach ($featStmt->fetchAll() as $f) { $featMap[$f['servicio_id']][] = $f; }

            // Features de sub-servicios
            $subIds = array_column($allSubs, 'id');
            $subFeatMap = [];
            if ($subIds) {
                $ph2 = implode(',', array_fill(0, count($subIds), '?'));
                $sfStmt = $pdo->prepare("SELECT * FROM servicio_features WHERE sub_servicio_id IN ($ph2) ORDER BY orden ASC, id ASC");
                $sfStmt->execute($subIds);
                foreach ($sfStmt->fetchAll() as $f) { $subFeatMap[$f['sub_servicio_id']][] = $f; }
            }

            $subMap = [];
            foreach ($allSubs as $sub) {
                $sub['features'] = $subFeatMap[$sub['id']] ?? [];
                $subMap[$sub['servicio_id']][] = $sub;
            }
            foreach ($svcs as &$svc) {
                $svc['sub_servicios'] = $subMap[$svc['id']] ?? [];
                $svc['features']      = $featMap[$svc['id']] ?? [];
            }
        }

        jsonResponse(['success' => true, 'data' => $svcs]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['nombre'])) {
            jsonResponse(['error' => 'Nombre del servicio es requerido.'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO servicios (nombre, descripcion, precio_base, costo, frecuencia, categoria, enlace_pago) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['nombre'],
            $input['descripcion'] ?? null,
            $input['precio_base'] ?? 0,
            $input['costo'] ?? 0,
            $input['frecuencia'] ?? 'mes',
            $input['categoria'] ?? null,
            $input['enlace_pago'] ?? null
        ]);

        $id = $pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'crear', 'servicios', $id, "Nuevo servicio: {$input['nombre']}");

        // Guardar features
        if (!empty($input['features']) && is_array($input['features'])) {
            $fStmt = $pdo->prepare("INSERT INTO servicio_features (servicio_id, sub_servicio_id, texto, orden) VALUES (?, NULL, ?, ?)");
            foreach (array_values($input['features']) as $i => $texto) {
                if (trim($texto)) $fStmt->execute([$id, trim($texto), $i]);
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetch(), 'message' => 'Servicio creado'], 201);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? ($_GET['id'] ?? null);
        if (!$id) jsonResponse(['error' => 'ID es requerido.'], 400);

        $fields = [];
        $values = [];
        foreach (['nombre', 'descripcion', 'precio_base', 'costo', 'frecuencia', 'categoria', 'activo', 'enlace_pago'] as $f) {
            if (array_key_exists($f, $input)) {
                $fields[] = "{$f} = ?";
                $values[] = $input[$f];
            }
        }
        if (empty($fields)) jsonResponse(['error' => 'No hay campos para actualizar.'], 400);

        $values[] = $id;
        // Obtener nombre anterior para sincronización
        $oldSvc = $pdo->prepare("SELECT nombre FROM servicios WHERE id = ?");
        $oldSvc->execute([$id]);
        $oldNombre = $oldSvc->fetchColumn();

        $pdo->prepare("UPDATE servicios SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
        logActivity($_SESSION['user_id'], 'actualizar', 'servicios', $id, "Servicio actualizado");

        // ── Sincronización cruzada ──
        // Si cambió el nombre, propagar a leads y cliente_servicios
        if (isset($input['nombre']) && $input['nombre'] !== $oldNombre) {
            $newNombre = $input['nombre'];
            // Actualizar leads que tengan el nombre anterior en servicio_interes
            $pdo->prepare("UPDATE leads SET servicio_interes = ? WHERE servicio_interes = ?")
                ->execute([$newNombre, $oldNombre]);
            // Actualizar cliente_servicios donde nombre_display coincide con el anterior
            $pdo->prepare("UPDATE cliente_servicios SET nombre_display = ? WHERE servicio_id = ? AND (nombre_display = ? OR nombre_display IS NULL)")
                ->execute([$newNombre, $id, $oldNombre]);
        }

        // Reemplazar features
        if (array_key_exists('features', $input)) {
            $pdo->prepare("DELETE FROM servicio_features WHERE servicio_id = ? AND sub_servicio_id IS NULL")->execute([$id]);
            if (!empty($input['features']) && is_array($input['features'])) {
                $fStmt = $pdo->prepare("INSERT INTO servicio_features (servicio_id, sub_servicio_id, texto, orden) VALUES (?, NULL, ?, ?)");
                foreach (array_values($input['features']) as $i => $texto) {
                    if (trim($texto)) $fStmt->execute([$id, trim($texto), $i]);
                }
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetch(), 'message' => 'Servicio actualizado']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID es requerido.'], 400);

        // Obtener nombre para log
        $svc = $pdo->prepare("SELECT nombre FROM servicios WHERE id = ?");
        $svc->execute([$id]);
        $svcNombre = $svc->fetchColumn();

        // Limpiar datos relacionados
        $pdo->prepare("DELETE FROM servicio_features WHERE servicio_id = ?")->execute([$id]);
        // Sub-servicios y sus features
        $subIds = $pdo->prepare("SELECT id FROM sub_servicios WHERE servicio_id = ?");
        $subIds->execute([$id]);
        $subIdList = $subIds->fetchAll(PDO::FETCH_COLUMN);
        if ($subIdList) {
            $ph = implode(',', array_fill(0, count($subIdList), '?'));
            $pdo->prepare("DELETE FROM servicio_features WHERE sub_servicio_id IN ($ph)")->execute($subIdList);
            $pdo->prepare("DELETE FROM paquete_items WHERE sub_servicio_id IN ($ph)")->execute($subIdList);
            $pdo->prepare("DELETE FROM sub_servicios WHERE servicio_id = ?")->execute([$id]);
        }
        // Eliminar servicio
        $pdo->prepare("DELETE FROM servicios WHERE id = ?")->execute([$id]);
        logActivity($_SESSION['user_id'], 'eliminar', 'servicios', $id, "Servicio eliminado: $svcNombre");
        jsonResponse(['success' => true, 'message' => 'Servicio eliminado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
