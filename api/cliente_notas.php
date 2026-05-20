<?php
/**
 * CRM QUANTUN Digital - API de Notas de Clientes
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
        $cliente_id = $_GET['cliente_id'] ?? null;
        if(!$cliente_id) jsonResponse(['error' => 'Cliente ID es requerido'], 400);
        
        $stmt = $pdo->prepare("SELECT cn.*, u.nombre as usuario_nombre FROM clientes_notas cn JOIN usuarios u ON cn.usuario_id = u.id WHERE cn.cliente_id = ? ORDER BY cn.created_at DESC");
        $stmt->execute([$cliente_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if(empty($input['cliente_id']) || empty($input['nota'])) jsonResponse(['error' => 'Nota es requerida'], 400);

        $stmt = $pdo->prepare("INSERT INTO clientes_notas (cliente_id, usuario_id, nota) VALUES (?, ?, ?)");
        $stmt->execute([
            $input['cliente_id'],
            $_SESSION['user_id'],
            $input['nota']
        ]);
        
        jsonResponse(['success' => true, 'message' => 'Nota agregada']);
        break;
}
