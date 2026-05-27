<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

// Migración: columnas imagen, enlace y orden
try { $pdo->exec("ALTER TABLE paquetes ADD COLUMN imagen VARCHAR(500) NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE paquetes ADD COLUMN enlace VARCHAR(500) NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE paquetes ADD COLUMN orden INT NOT NULL DEFAULT 0"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE paquetes ADD COLUMN icono VARCHAR(50) NULL"); } catch(PDOException $e){}
// Migración: hacer servicio_id nullable y eliminar FK para permitir features de paquetes
try { $pdo->exec("ALTER TABLE servicio_features DROP FOREIGN KEY servicio_features_ibfk_1"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE servicio_features MODIFY COLUMN servicio_id INT NULL"); } catch(PDOException $e){}

function getPaquetes(PDO $pdo): array {
    $paquetes = $pdo->query("SELECT * FROM paquetes WHERE activo = 1 ORDER BY orden ASC, nombre ASC")->fetchAll();
    if (!$paquetes) return [];
    $ids = array_column($paquetes, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT pi.paquete_id, pi.sub_servicio_id,
               ss.nombre AS ss_nombre, ss.precio, ss.costo, ss.frecuencia,
               s.nombre  AS svc_nombre, s.id AS servicio_id
        FROM paquete_items pi
        JOIN sub_servicios ss ON pi.sub_servicio_id = ss.id
        JOIN servicios s ON ss.servicio_id = s.id
        WHERE pi.paquete_id IN ($ph)
    ");
    $stmt->execute($ids);
    $itemMap = [];
    foreach ($stmt->fetchAll() as $row) {
        $itemMap[$row['paquete_id']][] = $row;
    }
    // Features de paquetes
    $fStmt = $pdo->prepare("SELECT * FROM servicio_features WHERE paquete_id IN ($ph) ORDER BY orden ASC, id ASC");
    $fStmt->execute($ids);
    $featMap = [];
    foreach ($fStmt->fetchAll() as $f) { $featMap[$f['paquete_id']][] = $f; }

    foreach ($paquetes as &$p) {
        $p['items']    = $itemMap[$p['id']] ?? [];
        $p['features'] = $featMap[$p['id']] ?? [];
    }
    return $paquetes;
}

switch ($method) {
    case 'GET':
        jsonResponse(['success' => true, 'data' => getPaquetes($pdo)]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['nombre'])) jsonResponse(['error' => 'Nombre requerido'], 400);
        $ids = array_filter(array_map('intval', $input['items'] ?? []));

        // Calcular totales desde sub_servicios
        $costo = $precio = 0;
        if ($ids) {
            $ph   = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT precio, costo FROM sub_servicios WHERE id IN ($ph)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                $costo  += floatval($row['costo']);
                $precio += floatval($row['precio']);
            }
        }
        // Precio de venta puede venir del input (personalizado) o usar suma
        $precioVenta = isset($input['precio_venta']) && $input['precio_venta'] > 0
            ? floatval($input['precio_venta'])
            : $precio;

        $pdo->prepare("INSERT INTO paquetes (nombre, descripcion, precio_venta, costo_total, frecuencia, imagen, enlace, icono) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$input['nombre'], $input['descripcion'] ?? null, $precioVenta, $costo, $input['frecuencia'] ?? 'mes', $input['imagen'] ?? null, $input['enlace'] ?? null, $input['icono'] ?? null]);
        $pid = $pdo->lastInsertId();

        if ($ids) {
            $iStmt = $pdo->prepare("INSERT INTO paquete_items (paquete_id, sub_servicio_id) VALUES (?, ?)");
            foreach ($ids as $sid) $iStmt->execute([$pid, $sid]);
        }
        if (!empty($input['features']) && is_array($input['features'])) {
            $fStmt = $pdo->prepare("INSERT INTO servicio_features (servicio_id, paquete_id, texto, orden) VALUES (NULL, ?, ?, ?)");
            foreach (array_values($input['features']) as $i => $texto) {
                if (trim($texto)) $fStmt->execute([$pid, trim($texto), $i]);
            }
        }

        logActivity($_SESSION['user_id'], 'crear', 'paquetes', $pid, "Nuevo paquete: {$input['nombre']}");
        jsonResponse(['success' => true, 'message' => 'Paquete creado', 'id' => $pid], 201);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id    = $input['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $ids   = array_filter(array_map('intval', $input['items'] ?? []));

        // Capturar nombre anterior para sincronización
        $oldPkgStmt = $pdo->prepare("SELECT nombre FROM paquetes WHERE id = ?");
        $oldPkgStmt->execute([$id]);
        $oldPkgNombre = $oldPkgStmt->fetchColumn();

        $costo = $precio = 0;
        if ($ids) {
            $ph   = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT precio, costo FROM sub_servicios WHERE id IN ($ph)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                $costo  += floatval($row['costo']);
                $precio += floatval($row['precio']);
            }
        }
        $precioVenta = isset($input['precio_venta']) && $input['precio_venta'] > 0
            ? floatval($input['precio_venta'])
            : $precio;

        $pdo->prepare("UPDATE paquetes SET nombre=?, descripcion=?, precio_venta=?, costo_total=?, frecuencia=?, enlace=?, icono=? WHERE id=?")
            ->execute([$input['nombre'], $input['descripcion'] ?? null, $precioVenta, $costo, $input['frecuencia'] ?? 'mes', $input['enlace'] ?? null, $input['icono'] ?? null, $id]);
        // imagen solo se actualiza si viene en el input (se maneja por upload separado)

        $pdo->prepare("DELETE FROM paquete_items WHERE paquete_id = ?")->execute([$id]);
        if ($ids) {
            $iStmt = $pdo->prepare("INSERT INTO paquete_items (paquete_id, sub_servicio_id) VALUES (?, ?)");
            foreach ($ids as $sid) $iStmt->execute([$id, $sid]);
        }
        $pdo->prepare("DELETE FROM servicio_features WHERE paquete_id = ?")->execute([$id]);
        if (!empty($input['features']) && is_array($input['features'])) {
            $fStmt = $pdo->prepare("INSERT INTO servicio_features (servicio_id, paquete_id, texto, orden) VALUES (NULL, ?, ?, ?)");
            foreach (array_values($input['features']) as $i => $texto) {
                if (trim($texto)) $fStmt->execute([$id, trim($texto), $i]);
            }
        }

        // ── Sincronización: propagar nombre a cliente_servicios ──
        if (isset($input['nombre'])) {
            $newPkgNombre = $input['nombre'];
            try {
                // Caso 1: registros con paquete_id FK (asignaciones nuevas) → siempre actualizar
                $pdo->prepare("UPDATE cliente_servicios SET nombre_display = ? WHERE paquete_id = ?")
                    ->execute([$newPkgNombre, $id]);

                // Caso 2: registros legados sin paquete_id → buscar por nombre anterior
                // filtrado por servicio_id de los items del paquete para evitar colisiones
                if ($newPkgNombre !== $oldPkgNombre && $oldPkgNombre) {
                    $pdo->prepare("
                        UPDATE cliente_servicios SET nombre_display = ?
                        WHERE paquete_id IS NULL
                        AND nombre_display = ?
                        AND servicio_id IN (
                            SELECT DISTINCT ss.servicio_id
                            FROM paquete_items pi
                            JOIN sub_servicios ss ON pi.sub_servicio_id = ss.id
                            WHERE pi.paquete_id = ?
                        )
                    ")->execute([$newPkgNombre, $oldPkgNombre, $id]);
                }
            } catch (PDOException $e) {
                error_log('[paquetes] Error al sincronizar nombre: ' . $e->getMessage());
            }
        }

        logActivity($_SESSION['user_id'], 'actualizar', 'paquetes', $id, "Paquete actualizado");
        jsonResponse(['success' => true, 'message' => 'Paquete actualizado']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        // Limpiar items, features y eliminar paquete
        $pdo->prepare("DELETE FROM paquete_items WHERE paquete_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM servicio_features WHERE paquete_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM paquetes WHERE id = ?")->execute([$id]);
        logActivity($_SESSION['user_id'], 'eliminar', 'paquetes', $id, "Paquete eliminado");
        jsonResponse(['success' => true, 'message' => 'Paquete eliminado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
