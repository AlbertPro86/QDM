<?php
/**
 * CRM QUANTUN Digital - API de Clientes (Avanzado)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        if($id) {
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?"); $stmt->execute([$id]);
            $cliente = $stmt->fetch();
            $stmt = $pdo->prepare("SELECT cs.*, s.nombre as servicio_nombre FROM cliente_servicios cs JOIN servicios s ON cs.servicio_id = s.id WHERE cs.cliente_id = ?"); 
            $stmt->execute([$id]);
            $cliente['servicios_activos'] = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $cliente]);
        }
        $stmt = $pdo->query("
            SELECT c.*,
                   GROUP_CONCAT(DISTINCT COALESCE(cs.nombre_display, s.nombre) SEPARATOR ', ') as servicios_nombres,
                   GROUP_CONCAT(DISTINCT CASE WHEN cs.estado='activo' THEN cs.frecuencia END SEPARATOR ', ') as frecuencias_activas,
                   MIN(CASE WHEN cs.estado='activo' AND LOWER(COALESCE(cs.frecuencia,'')) != 'unico' THEN cs.fecha_vencimiento END) as proxima_renovacion,
                   COUNT(DISTINCT CASE WHEN cs.estado='activo' THEN cs.id END) as num_servicios_activos,
                   COALESCE((
                       SELECT SUM(cs2.monto_renovacion - COALESCE(cs2.descuento,0))
                       FROM cliente_servicios cs2
                       WHERE cs2.cliente_id = c.id
                         AND cs2.estado = 'activo'
                         AND LOWER(COALESCE(cs2.frecuencia,'')) != 'unico'
                   ), 0) as mrr,
                   COALESCE(tx_cobrado.total, 0)  as total_cobrado,
                   COALESCE(tx_pendiente.total, 0) as total_por_cobrar,
                   COALESCE(cn_data.num_negocios, 0)     as num_negocios,
                   COALESCE(cn_data.negocios_nombres, '') as negocios_nombres,
                   COALESCE(cn_data.negocios_ids, '')     as negocios_ids
            FROM clientes c
            LEFT JOIN cliente_servicios cs ON c.id = cs.cliente_id
            LEFT JOIN servicios s ON cs.servicio_id = s.id
            LEFT JOIN (
                SELECT cliente_id, SUM(monto) as total
                FROM transacciones
                WHERE tipo='ingreso' AND estado='pagado' AND cliente_id IS NOT NULL
                GROUP BY cliente_id
            ) tx_cobrado ON c.id = tx_cobrado.cliente_id
            LEFT JOIN (
                SELECT cliente_id, SUM(monto) as total
                FROM transacciones
                WHERE tipo='ingreso' AND estado='pendiente' AND cliente_id IS NOT NULL
                GROUP BY cliente_id
            ) tx_pendiente ON c.id = tx_pendiente.cliente_id
            LEFT JOIN (
                SELECT cliente_id,
                       COUNT(*) as num_negocios,
                       GROUP_CONCAT(nombre ORDER BY id SEPARATOR '|||') as negocios_nombres,
                       GROUP_CONCAT(id     ORDER BY id SEPARATOR '|||') as negocios_ids
                FROM cliente_negocios
                GROUP BY cliente_id
            ) cn_data ON c.id = cn_data.cliente_id
            GROUP BY c.id
            ORDER BY c.nombre_comercial ASC
        ");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST': // Create new client OR convert lead
        $input = json_decode(file_get_contents('php://input'), true);
        if(empty($input['nombre_comercial'])) jsonResponse(['error' => 'Nombre comercial es requerido'], 400);

        $stmt = $pdo->prepare("INSERT INTO clientes (lead_id, nombre_comercial, nit_cedula, persona_contacto, telefono, email_facturacion, direccion, responsable, ubicacion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['lead_id'] ?: null,
            $input['nombre_comercial'],
            $input['nit_cedula'] ?? '',
            $input['persona_contacto'] ?? '',
            $input['telefono'] ?? '',
            $input['email_facturacion'] ?? '',
            $input['direccion'] ?? '',
            $input['responsable'] ?? '',
            $input['ubicacion'] ?? '',
            $input['estado'] ?? 'activo'
        ]);
        
        $clientId = $pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'crear', 'clientes', $clientId, "Nuevo cliente: {$input['nombre_comercial']}");
        
        jsonResponse(['success' => true, 'id' => $clientId, 'message' => 'Cliente creado con éxito']);
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if(!$id) jsonResponse(['error' => 'ID es requerido'], 400);
        
        $fields = ['nombre_comercial', 'nit_cedula', 'persona_contacto', 'telefono', 'email_facturacion', 'email_contacto', 'direccion', 'responsable', 'ubicacion', 'estado'];
        $up = []; $vals = [];
        foreach($fields as $f) { if(isset($input[$f])) { $up[] = "$f = ?"; $vals[] = $input[$f]; } }
        $vals[] = $id;
        
        $pdo->prepare("UPDATE clientes SET ".implode(',', $up)." WHERE id = ?")->execute($vals);
        jsonResponse(['success' => true, 'message' => 'Cliente actualizado']);
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if(!$id) jsonResponse(['error' => 'ID es requerido'], 400);
        // Limpiar datos relacionados del cliente
        $pdo->prepare("DELETE FROM cliente_servicios WHERE cliente_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM clientes_notas WHERE cliente_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM clientes_archivos WHERE cliente_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Cliente eliminado']);
        break;
}
