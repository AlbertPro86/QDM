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

        $newNombre = $input['nombre'];
        $newPrecio = floatval($input['precio'] ?? 0);
        $newCosto  = floatval($input['costo']  ?? 0);

        if ($old) {
            $oldNombre = $old['nombre'];
            $oldPrecio = floatval($old['precio']);
            $oldCosto  = floatval($old['costo']);

            // Nombre del servicio padre (necesario para display compuesto y para encontrar filas)
            $parentStmt = $pdo->prepare("SELECT nombre FROM servicios WHERE id = ?");
            $parentStmt->execute([$old['servicio_id']]);
            $parentNombre    = $parentStmt->fetchColumn() ?: '';
            $compositeOld    = $parentNombre ? $parentNombre . ' — ' . $oldNombre : $oldNombre;
            $compositeNew    = $parentNombre ? $parentNombre . ' — ' . $newNombre : $newNombre;

            // ── 1. Sync monto_renovacion → cliente_servicios (ANTES del rename para poder hacer match) ──
            if ($newPrecio !== $oldPrecio) {
                $pdo->prepare("
                    UPDATE cliente_servicios
                    SET monto_renovacion = ?
                    WHERE servicio_id = ?
                      AND paquete_id IS NULL
                      AND estado = 'activo'
                      AND ROUND(monto_renovacion, 2) = ROUND(?, 2)
                      AND nombre_display IN (?, ?)
                ")->execute([$newPrecio, $old['servicio_id'], $oldPrecio, $compositeOld, $oldNombre]);
            }

            // ── 2. Sync costo_servicio → cliente_servicios ──
            if ($newCosto !== $oldCosto) {
                $pdo->prepare("
                    UPDATE cliente_servicios
                    SET costo_servicio = ?
                    WHERE servicio_id = ?
                      AND paquete_id IS NULL
                      AND estado = 'activo'
                      AND nombre_display IN (?, ?)
                ")->execute([$newCosto, $old['servicio_id'], $compositeOld, $oldNombre]);
            }

            // ── 3. Sync nombre → leads y cliente_servicios ──
            if ($newNombre !== $oldNombre) {
                $pdo->prepare("UPDATE leads SET servicio_interes = ? WHERE servicio_interes = ?")
                    ->execute([$newNombre, $oldNombre]);

                // Display compuesto "Padre — OldSub" → "Padre — NewSub"
                $pdo->prepare("UPDATE cliente_servicios SET nombre_display = ? WHERE servicio_id = ? AND nombre_display = ?")
                    ->execute([$compositeNew, $old['servicio_id'], $compositeOld]);

                // Display simple (solo nombre del sub sin prefijo)
                $pdo->prepare("UPDATE cliente_servicios SET nombre_display = ? WHERE servicio_id = ? AND nombre_display = ?")
                    ->execute([$newNombre, $old['servicio_id'], $oldNombre]);
            }

            // ── 4. Recalcular paquetes que incluyan este sub-servicio ──
            if ($newPrecio !== $oldPrecio || $newCosto !== $oldCosto) {
                $pkgStmt = $pdo->prepare("SELECT DISTINCT paquete_id FROM paquete_items WHERE sub_servicio_id = ?");
                $pkgStmt->execute([$id]);
                $pkgIds = $pkgStmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($pkgIds as $pid) {
                    $totals = $pdo->prepare("SELECT SUM(ss.precio) as total_precio, SUM(ss.costo) as total_costo FROM paquete_items pi JOIN sub_servicios ss ON pi.sub_servicio_id = ss.id WHERE pi.paquete_id = ?");
                    $totals->execute([$pid]);
                    $t = $totals->fetch();
                    $pdo->prepare("UPDATE paquetes SET costo_total = ? WHERE id = ?")->execute([$t['total_costo'] ?? 0, $pid]);
                    // Solo actualizar precio_venta si era automático (coincidía con la suma anterior)
                    $pkg = $pdo->prepare("SELECT precio_venta FROM paquetes WHERE id = ?");
                    $pkg->execute([$pid]);
                    $currentPrecio  = floatval($pkg->fetchColumn());
                    $oldTotalPrecio = floatval($t['total_precio']) - $newPrecio + $oldPrecio;
                    if (abs($currentPrecio - $oldTotalPrecio) < 0.01) {
                        $pdo->prepare("UPDATE paquetes SET precio_venta = ? WHERE id = ?")->execute([$t['total_precio'] ?? 0, $pid]);
                    }
                    // Propagar costo actualizado del paquete a cliente_servicios activos
                    $pdo->prepare("UPDATE cliente_servicios SET costo_servicio = ? WHERE paquete_id = ? AND estado = 'activo'")
                        ->execute([$t['total_costo'] ?? 0, $pid]);
                }
            }
        }

        // ── 5. Reemplazar features ──
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
