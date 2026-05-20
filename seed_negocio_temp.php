<?php
/**
 * Seed temporal: agrega negocio "ozzomarket" al cliente NATURVIDACOL / Ramón Anaya P.
 * Borrar este archivo después de ejecutarlo.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

// Buscar el cliente
$stmt = $pdo->prepare("SELECT id, nombre_comercial FROM clientes WHERE nombre_comercial LIKE ? OR persona_contacto LIKE ? LIMIT 5");
$stmt->execute(['%NATURVIDACOL%', '%Ramón%']);
$clientes = $stmt->fetchAll();

if (!$clientes) {
    echo "<p style='color:red'>No se encontró el cliente NATURVIDACOL / Ramón Anaya P.</p>";
    echo "<p>Clientes existentes:</p><pre>";
    $all = $pdo->query("SELECT id, nombre_comercial, persona_contacto FROM clientes LIMIT 20")->fetchAll();
    print_r($all);
    echo "</pre>";
    exit;
}

echo "<p>Clientes encontrados:</p><ul>";
foreach ($clientes as $c) {
    echo "<li>ID {$c['id']}: {$c['nombre_comercial']}</li>";
}
echo "</ul>";

$clienteId = $clientes[0]['id'];

// Verificar si ya existe el negocio
$check = $pdo->prepare("SELECT id FROM cliente_negocios WHERE cliente_id = ? AND nombre LIKE ?");
$check->execute([$clienteId, '%ozzomarket%']);
if ($check->fetch()) {
    echo "<p style='color:orange'>⚠️ El negocio 'ozzomarket' ya existe para este cliente.</p>";
} else {
    // Crear negocio "Naturvidacol" si no existe (como referencia del negocio original)
    $checkNatura = $pdo->prepare("SELECT id FROM cliente_negocios WHERE cliente_id = ? AND nombre LIKE ?");
    $checkNatura->execute([$clienteId, '%aturvidacol%']);
    if (!$checkNatura->fetch()) {
        $pdo->prepare("INSERT INTO cliente_negocios (cliente_id, nombre, tipo, estado, descripcion) VALUES (?, ?, ?, ?, ?)")
            ->execute([$clienteId, 'Naturvidacol', 'E-commerce', 'activo', 'Negocio principal del cliente']);
        echo "<p style='color:green'>✓ Negocio 'Naturvidacol' creado.</p>";
    }

    // Crear negocio "ozzomarket"
    $pdo->prepare("INSERT INTO cliente_negocios (cliente_id, nombre, tipo, estado, descripcion) VALUES (?, ?, ?, ?, ?)")
        ->execute([$clienteId, 'ozzomarket', 'E-commerce', 'activo', 'Segundo negocio del cliente Ramón Anaya P.']);
    $newId = $pdo->lastInsertId();
    echo "<p style='color:green'>✓ Negocio 'ozzomarket' creado con ID {$newId} para cliente ID {$clienteId}.</p>";
}

echo "<br><a href='cliente_detalle.php?id={$clienteId}' style='background:#0f172a;color:#c9f31d;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700'>→ Ir al cliente NATURVIDACOL</a>";
echo "<br><br><a href='clientes.php' style='color:#64748b;font-size:12px'>← Volver a clientes</a>";
echo "<br><br><small style='color:#94a3b8'>Elimina este archivo (seed_negocio_temp.php) después de ejecutarlo.</small>";
