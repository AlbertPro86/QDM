<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = db();

// Migración: agregar columna frecuencia si no existe
try {
    $pdo->exec("ALTER TABLE sub_servicios ADD COLUMN frecuencia VARCHAR(20) NOT NULL DEFAULT 'mes'");
} catch (PDOException $e) { /* columna ya existe */ }

switch ($method) {
    case 'GET':
        $svcId = $_GET['servicio_id'] ?? null;
        if (!$svcId) jsonResponse(['error' => 'servicio_id requerido'], 400);
        $stmt = $pdo->prepare("SELECT * FROM sub_servicios WHERE servicio_id = ? AND activo = 1 ORDER BY orden ASC, id ASC");
        $stmt->execute([$svcId]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['servicio_id']) || empty($input['nombre']))
            jsonResponse(['error' => 'servicio_id y nombre son requeridos'], 400);

        $stmt = $pdo->prepare("INSERT INTO sub_servicios (servicio_id, nombre, descripcion, precio, costo, enlace_pago, frecuencia) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['servicio_id'],
            $input['nombre'],
            $input['descripcion'] ?? null,
            $input['precio'] ?? 0,
            $input['costo']  ?? 0,
            $input['enlace_pago'] ?? null,
            $input['frecuencia'] ?? 'mes'
        ]);
        $id = $pdo->lastInsertId();

        if (!empty($input['features']) && is_array($input['features'])) {
            $fStmt = $pdo->prepare("INSERT INTO servicio_features (servicio_id, sub_servicio_id, texto, orden) VALUES (?, ?, ?, ?)");
            foreach (array_values($input['features']) as $i => $texto) {
                if (trim($texto)) $fStmt->execute([$input['servicio_id'], $id, trim($texto), $i]);
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM sub_servicios WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetch(), 'message' => 'Sub-servicio creado'], 201);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        // Obtener datos anteriores para sincronización
        $oldSub = $pdo->prepare("SELECT nombre, precio, costo, servicio_id FROM sub_servicios WHERE id = ?");
        $oldSub->execute([$id]);
        $old = $oldSub->fetch();

        $pdo->prepare("UPDATE sub_servicios SET nombre = ?, descripcion = ?, precio = ?, costo = ?, enlace_pago = ?, frecuencia = ? WHERE id = ?")
            ->execute([$input['nombre'], $input['descripcion'] ?? null, $input['precio'] ?? 0, $input['costo'] ?? 0, $input['enlace_pago'] ?? null, $input['frecuencia'] ?? 'mes', $id]);

        // ── Sincronización cruzada ──
        $newNombre = $input['nombre'];
        $newPrecio = floatval($input['precio'] ?? 0);
        $newCosto  = floatval($input['costo'] ?? 0);

        // Si cambió el nombre, propagar a leads y cliente_servicios
        if ($old && $newNombre !== $old['nombre']) {
            $pdo->prepare("UPDATE leads SET servicio_interes = ? WHERE servicio_interes = ?")
                ->execute([$newNombre, $old['nombre']]);
            $pdo->prepare("UPDATE cliente_servicios SET nombre_display = ? WHERE servicio_id = ? AND nombre_display = ?")
                ->execute([$newNombre, $old['servicio_id'], $old['nombre']]);
        }

        // Si cambió precio o costo, recalcular paquetes que incluyan este sub-servicio
        if ($old && ($newPrecio != floatval($old['precio']) || $newCosto != floatval($old['costo']))) {
            $pkgStmt = $pdo->prepare("SELECT DISTINCT paquete_id FROM paquete_items WHERE sub_servicio_id = ?");
            $pkgStmt->execute([$id]);
            $pkgIds = $pkgStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($pkgIds as $pid) {
                $totals = $pdo->prepare("SELECT SUM(ss.precio) as total_precio, SUM(ss.costo) as total_costo FROM paquete_items pi JOIN sub_servicios ss ON pi.sub_servicio_id = ss.id WHERE pi.paquete_id = ?");
                $totals->execute([$pid]);
                $t = $totals->fetch();
                $pdo->prepare("UPDATE paquetes SET costo_total = ? WHERE id = ?")
                    ->execute([$t['total_costo'] ?? 0, $pid]);
                // Solo actualizar precio_venta si coincide con la suma anterior (no es precio personalizado)
                $pkg = $pdo->prepare("SELECT precio_venta FROM paquetes WHERE id = ?");
                $pkg->execute([$pid]);
                $currentPrecio = floatval($pkg->fetchColumn());
                $oldSum = $currentPrecio; // Aproximación: si el precio actual es "automático"
                // Recalcular el precio anterior sumando todos los sub-servicios pero con el precio viejo de este
                $oldTotalPrecio = floatval($t['total_precio']) - $newPrecio + floatval($old['precio']);
                if (abs($currentPrecio - $oldTotalPrecio) < 0.01) {
                    // El precio del paquete coincidía con la suma → es automático, actualizar
                    $pdo->prepare("UPDATE paquetes SET precio_venta = ? WHERE id = ?")
                        ->execute([$t['total_precio'] ?? 0, $pid]);
                }
            }
        }

        // Reemplazar features
        if (array_key_exists('features', $input)) {
            $pdo->prepare("DELETE FROM servicio_features WHERE sub_servicio_id = ?")->execute([$id]);
            if (!empty($input['features']) && is_array($input['features'])) {
                $svcId = (int)($input['servicio_id'] ?? 0);
                $fStmt = $pdo->prepare("INSERT INTO servicio_features (servicio_id, sub_servicio_id, texto, orden) VALUES (?, ?, ?, ?)");
                foreach (array_values($input['features']) as $i => $texto) {
                    if (trim($texto)) $fStmt->execute([$svcId, $id, trim($texto), $i]);
                }
            }
        }
        jsonResponse(['success' => true, 'message' => 'Sub-servicio actualizado']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        // Limpiar features y referencias en paquetes
        $pdo->prepare("DELETE FROM servicio_features WHERE sub_servicio_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM paquete_items WHERE sub_servicio_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM sub_servicios WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Sub-servicio eliminado']);
        break;
}
