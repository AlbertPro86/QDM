<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

$pdo   = db();
$input = json_decode(file_get_contents('php://input'), true);
$tabla = $input['tabla'] ?? '';
$items = $input['items'] ?? []; // array de {id, orden}

$allowed = ['servicios', 'sub_servicios', 'paquetes'];
if (!in_array($tabla, $allowed)) jsonResponse(['error' => 'Tabla no permitida'], 400);
if (empty($items)) jsonResponse(['error' => 'Items requeridos'], 400);

// Migración: agregar columna orden si no existe
try { $pdo->exec("ALTER TABLE `{$tabla}` ADD COLUMN orden INT NOT NULL DEFAULT 0"); } catch(PDOException $e){}

$stmt = $pdo->prepare("UPDATE `{$tabla}` SET orden = ? WHERE id = ?");
foreach ($items as $item) {
    $stmt->execute([(int)$item['orden'], (int)$item['id']]);
}

jsonResponse(['success' => true]);
