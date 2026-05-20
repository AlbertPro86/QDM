<?php
/**
 * CRM QUANTUN Digital - API de Renovaciones Globales
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();
$stmt = $pdo->query("
    SELECT MIN(cs.fecha_vencimiento) as fecha_vencimiento, 
           c.nombre_comercial as cliente_nombre, 
           cs.cliente_id,
           GROUP_CONCAT(DISTINCT s.nombre SEPARATOR ', ') as servicio_nombre,
           SUM(cs.monto_renovacion) as monto_renovacion,
           c.estado
    FROM cliente_servicios cs 
    JOIN clientes c ON cs.cliente_id = c.id 
    JOIN servicios s ON cs.servicio_id = s.id 
    WHERE cs.estado IN ('activo', 'vencido')
      AND LOWER(COALESCE(cs.frecuencia, '')) != 'unico'
    GROUP BY cs.cliente_id
    ORDER BY fecha_vencimiento ASC
");

jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
