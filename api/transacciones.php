<?php
/**
 * CRM QUANTUN Digital - API de Transacciones
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

// Auto-migración: nuevas columnas
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN frecuencia VARCHAR(20) NOT NULL DEFAULT 'unico'"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN descuento DECIMAL(10,2) NOT NULL DEFAULT 0"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN proveedor VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN titulo VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN factura_path VARCHAR(500) DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN documento_path VARCHAR(500) DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN imagen_path VARCHAR(500) DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN cliente_id INT DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD CONSTRAINT fk_tx_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN negocio_id INT DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE transacciones ADD COLUMN fecha_pago DATE DEFAULT NULL"); } catch(PDOException $e){}

switch ($method) {
    case 'GET':
        // Si viene un ID específico, retornar solo esa transacción
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("
                SELECT t.*, l.nombre AS lead_nombre, c.nombre_comercial AS cliente_nombre,
                       f.archivo_url AS factura_url
                FROM transacciones t
                LEFT JOIN leads l ON t.lead_id = l.id
                LEFT JOIN clientes c ON t.cliente_id = c.id
                LEFT JOIN facturas f ON t.factura_id = f.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if ($data) {
                $iStmt = $pdo->prepare("SELECT * FROM transaccion_items WHERE transaccion_id = ? ORDER BY id ASC");
                $iStmt->execute([$id]);
                $data['items'] = $iStmt->fetchAll();
            }
            jsonResponse(['success' => !!$data, 'data' => $data ?: null]);
        }

        $tipo = $_GET['tipo'] ?? null;
        $estado = $_GET['estado'] ?? null;
        $mes = $_GET['mes'] ?? null;
        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;
        $buscar = $_GET['buscar'] ?? null;
        $frecuenciaFiltro = $_GET['frecuencia'] ?? null;
        $clienteIdFiltro  = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        $negocioIdFiltro  = isset($_GET['negocio_id']) ? intval($_GET['negocio_id']) : null;
        $limite = min((int)($_GET['limite'] ?? 50), 500);
        $offset = max((int)($_GET['offset'] ?? 0), 0);

        $where = [];
        $params = [];

        // Modo especial: estado=por_cobrar → ingresos pendiente+vencido SIN filtro de fecha
        $esPorCobrar = ($estado === 'por_cobrar');

        if ($esPorCobrar) {
            // Forzar tipo ingreso y ambos estados sin restricción de fecha
            $where[] = "t.tipo = 'ingreso'";
            $where[] = "t.estado IN ('pendiente', 'vencido')";
        } else {
            if ($tipo && $tipo !== 'todos') {
                $where[] = "t.tipo = ?";
                $params[] = $tipo;
            }
            if ($estado && $estado !== 'todos') {
                $where[] = "t.estado = ?";
                $params[] = $estado;
            }
            if ($mes) {
                $where[] = "DATE_FORMAT(COALESCE(t.fecha_pago, t.fecha_vencimiento, t.created_at), '%Y-%m') = ?";
                $params[] = $mes;
            }
            if ($desde && !$mes) {
                $where[] = "DATE(COALESCE(t.fecha_pago, t.fecha_vencimiento, t.created_at)) >= ?";
                $params[] = $desde;
            }
            if ($hasta && !$mes) {
                $where[] = "DATE(COALESCE(t.fecha_pago, t.fecha_vencimiento, t.created_at)) <= ?";
                $params[] = $hasta;
            }
        }
        if ($frecuenciaFiltro) {
            $where[] = "t.frecuencia = ?";
            $params[] = $frecuenciaFiltro;
        }
        if ($clienteIdFiltro) {
            $where[] = "t.cliente_id = ?";
            $params[] = $clienteIdFiltro;
        }
        if ($negocioIdFiltro) {
            $where[] = "t.negocio_id = ?";
            $params[] = $negocioIdFiltro;
        }
        if ($buscar) {
            $where[] = "(t.concepto LIKE ? OR t.titulo LIKE ? OR l.nombre LIKE ? OR c.nombre_comercial LIKE ? OR t.proveedor LIKE ?)";
            $searchTerm = "%{$buscar}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Totales — con los mismos filtros de la tabla (excepto tipo para mostrar balance completo)
        $resumenWhere = [];
        $resumenParams = [];
        if ($mes) {
            $resumenWhere[] = "DATE_FORMAT(COALESCE(fecha_pago, fecha_vencimiento, created_at), '%Y-%m') = ?";
            $resumenParams[] = $mes;
        }
        if ($desde && !$mes) {
            $resumenWhere[] = "DATE(COALESCE(fecha_pago, fecha_vencimiento, created_at)) >= ?";
            $resumenParams[] = $desde;
        }
        if ($hasta && !$mes) {
            $resumenWhere[] = "DATE(COALESCE(fecha_pago, fecha_vencimiento, created_at)) <= ?";
            $resumenParams[] = $hasta;
        }
        $resumenClause = $resumenWhere ? 'WHERE ' . implode(' AND ', $resumenWhere) : '';
        $resumenStmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' AND estado = 'pagado' THEN monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0) as total_egresos,
                COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as total_pendientes,
                COUNT(CASE WHEN estado = 'vencido' THEN 1 END) as total_vencidos,
                COUNT(CASE WHEN tipo = 'ingreso' AND estado = 'pagado' THEN 1 END) as count_ingresos,
                COUNT(CASE WHEN tipo = 'egreso' THEN 1 END) as count_egresos
            FROM transacciones
            {$resumenClause}
        ");
        $resumenStmt->execute($resumenParams);
        $resumen = $resumenStmt->fetch();

        // Por cobrar: TODOS los ingresos pendientes/vencidos sin filtro de fecha
        // (los vencidos tienen fecha_vencimiento pasada y quedan fuera del rango del período)
        $stmtPorCobrar = $pdo->query("
            SELECT
                COALESCE(SUM(monto), 0)  AS total_por_cobrar,
                COUNT(*)                  AS count_por_cobrar,
                COUNT(CASE WHEN estado='vencido'  THEN 1 END) AS count_vencidos,
                COUNT(CASE WHEN estado='pendiente' THEN 1 END) AS count_pendientes
            FROM transacciones
            WHERE tipo = 'ingreso' AND estado IN ('pendiente', 'vencido')
        ");
        $porCobrar = $stmtPorCobrar->fetch();

        // Obtener meses disponibles
        $mesesStmt = $pdo->query("
            SELECT DISTINCT DATE_FORMAT(fecha_vencimiento, '%Y-%m') as mes
            FROM transacciones
            WHERE fecha_vencimiento IS NOT NULL
            ORDER BY mes DESC
            LIMIT 24
        ");
        $meses = array_column($mesesStmt->fetchAll(), 'mes');

        // Count
        $countSql = "SELECT COUNT(*) FROM transacciones t LEFT JOIN leads l ON t.lead_id = l.id LEFT JOIN clientes c ON t.cliente_id = c.id {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Fetch
        $sql = "SELECT t.*,
                       l.nombre AS lead_nombre,
                       c.nombre_comercial AS cliente_nombre,
                       c.telefono AS cliente_telefono,
                       c.email_facturacion AS cliente_email,
                       COALESCE(l.nombre, c.nombre_comercial) AS destinatario_nombre,
                       COALESCE(
                           (SELECT CASE
                               WHEN cs.paquete_id IS NOT NULL AND pkg.nombre IS NOT NULL THEN pkg.nombre
                               WHEN cs.nombre_display LIKE '% — %' THEN cs.nombre_display
                               ELSE s.nombre
                            END
                            FROM cliente_servicios cs
                            LEFT JOIN paquetes pkg ON cs.paquete_id = pkg.id
                            WHERE cs.cliente_id = t.cliente_id
                              AND cs.servicio_id = t.servicio_id
                            ORDER BY cs.id DESC
                            LIMIT 1),
                           s.nombre
                       ) AS servicio_nombre,
                       f.nombre_archivo AS factura_nombre,
                       f.archivo_url AS factura_url,
                       f.tipo AS factura_tipo
                FROM transacciones t
                LEFT JOIN leads l ON t.lead_id = l.id
                LEFT JOIN clientes c ON t.cliente_id = c.id
                LEFT JOIN servicios s ON t.servicio_id = s.id
                LEFT JOIN facturas f ON t.factura_id = f.id
                {$whereClause}
                ORDER BY t.created_at DESC
                LIMIT {$limite} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $txData = $stmt->fetchAll();

        // Fetch items for each transaction
        if ($txData) {
            $txIds = array_column($txData, 'id');
            $placeholders = implode(',', array_fill(0, count($txIds), '?'));
            $iStmt = $pdo->prepare("SELECT * FROM transaccion_items WHERE transaccion_id IN ($placeholders) ORDER BY id ASC");
            $iStmt->execute($txIds);
            $allItems = $iStmt->fetchAll();
            $itemMap = [];
            foreach ($allItems as $item) { $itemMap[$item['transaccion_id']][] = $item; }
            foreach ($txData as &$tx) { $tx['items'] = $itemMap[$tx['id']] ?? []; }
        }

        // Enriquecer resumen con datos de "por cobrar" sin restricción de fecha
        $resumen['total_por_cobrar']    = round((float)$porCobrar['total_por_cobrar'], 2);
        $resumen['count_por_cobrar']    = (int)$porCobrar['count_por_cobrar'];
        $resumen['count_vencidos_pc']   = (int)$porCobrar['count_vencidos'];
        $resumen['count_pendientes_pc'] = (int)$porCobrar['count_pendientes'];

        jsonResponse([
            'success' => true,
            'data' => $txData,
            'total' => (int)$total,
            'resumen' => $resumen,
            'meses' => $meses
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['tipo']) || empty($input['monto']) || empty($input['concepto'])) {
            jsonResponse(['error' => 'Tipo, monto y concepto son requeridos.'], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO transacciones (tipo, monto, concepto, descripcion, fecha_vencimiento, fecha_pago, estado, lead_id, cliente_id, servicio_id, factura_id, frecuencia, descuento, proveedor, titulo, factura_path, documento_path, imagen_path, registrado_por, negocio_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $frecuenciasValidas = ['unico','mensual','trimestral','semestral','anual'];
        $frecuencia = in_array($input['frecuencia'] ?? '', $frecuenciasValidas) ? $input['frecuencia'] : 'unico';
        $descuento  = max(0, (float)($input['descuento'] ?? 0));

        $stmt->execute([
            $input['tipo'],
            $input['monto'],
            $input['concepto'],
            $input['descripcion'] ?? null,
            $input['fecha_vencimiento'] ?? null,
            !empty($input['fecha_pago']) ? $input['fecha_pago'] : null,
            $input['estado'] ?? 'pendiente',
            $input['lead_id'] ?: null,
            $input['cliente_id'] ?: null,
            $input['servicio_id'] ?: null,
            $input['factura_id'] ?? null,
            $frecuencia,
            $descuento,
            $input['proveedor'] ?? null,
            $input['titulo'] ?? null,
            $input['factura_path'] ?? null,
            $input['documento_path'] ?? null,
            $input['imagen_path'] ?? null,
            $_SESSION['user_id'],
            !empty($input['negocio_id']) ? intval($input['negocio_id']) : null,
        ]);

        $id = $pdo->lastInsertId();

        // Auto-vincular: si tiene lead_id pero no cliente_id, buscar cliente por teléfono del lead
        if (!empty($input['lead_id']) && empty($input['cliente_id'])) {
            $pdo->prepare("
                UPDATE transacciones t
                JOIN leads l ON t.lead_id = l.id
                JOIN clientes c ON c.telefono = l.whatsapp
                SET t.cliente_id = c.id
                WHERE t.id = ? AND t.cliente_id IS NULL AND l.whatsapp != ''
            ")->execute([$id]);
        }

        logActivity($_SESSION['user_id'], 'crear', 'transacciones', $id, "Nueva transacción: {$input['concepto']}");

        // Insert line items
        if (!empty($input['items']) && is_array($input['items'])) {
            $iStmt = $pdo->prepare("INSERT INTO transaccion_items (transaccion_id, servicio_id, nombre, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($input['items'] as $item) {
                $qty   = max(1, (int)($item['cantidad'] ?? 1));
                $precio = max(0, (float)($item['precio_unitario'] ?? 0));
                $iStmt->execute([$id, $item['servicio_id'] ?: null, $item['nombre'], $precio, $qty, round($precio * $qty, 2)]);
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM transacciones WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'data' => $stmt->fetch(), 'message' => 'Transacción registrada'], 201);
        break;

    case 'PUT':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? ($_GET['id'] ?? null);

            if (!$id) {
                jsonResponse(['error' => 'ID es requerido.'], 400);
            }

            $fields = [];
            $values = [];

            $updatable = ['tipo', 'monto', 'concepto', 'titulo', 'descripcion', 'fecha_vencimiento', 'fecha_pago', 'estado', 'lead_id', 'cliente_id', 'servicio_id', 'factura_id', 'frecuencia', 'descuento', 'proveedor', 'factura_path', 'documento_path', 'imagen_path', 'negocio_id'];

            foreach ($updatable as $field) {
                if (isset($input[$field])) {
                    $fields[] = "{$field} = ?";
                    $values[] = $input[$field] ?: null;
                }
            }

            if (empty($fields)) {
                jsonResponse(['error' => 'No hay campos para actualizar.'], 400);
            }

            $values[] = $id;
            $pdo->prepare("UPDATE transacciones SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
            logActivity($_SESSION['user_id'], 'actualizar', 'transacciones', $id, "Transacción actualizada");

            // Update line items
            if (isset($input['items']) && is_array($input['items'])) {
                $pdo->prepare("DELETE FROM transaccion_items WHERE transaccion_id = ?")->execute([$id]);
                if ($input['items']) {
                    $iStmt = $pdo->prepare("INSERT INTO transaccion_items (transaccion_id, servicio_id, nombre, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($input['items'] as $item) {
                        $qty    = max(1, (int)($item['cantidad'] ?? 1));
                        $precio = max(0, (float)($item['precio_unitario'] ?? 0));
                        $iStmt->execute([$id, $item['servicio_id'] ?: null, $item['nombre'], $precio, $qty, round($precio * $qty, 2)]);
                    }
                }
            }

            $stmt = $pdo->prepare("SELECT * FROM transacciones WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['success' => true, 'data' => $stmt->fetch(), 'message' => 'Transacción actualizada']);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID es requerido.'], 400);

        $pdo->prepare("DELETE FROM transacciones WHERE id = ?")->execute([$id]);
        logActivity($_SESSION['user_id'], 'eliminar', 'transacciones', $id, "Transacción eliminada");
        jsonResponse(['success' => true, 'message' => 'Transacción eliminada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
