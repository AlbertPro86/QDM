<?php
/**
 * BACKUP AUTOMÁTICO — CRM QUANTUN Digital
 * Genera un SQL dump completo de crm_quantun y lo guarda en /backups/
 *
 * Acceso: http://localhost/CRM-QUANTUN-Digital/backup_db.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Carpeta de backups
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$pdo    = db();
$fecha  = date('Y-m-d_H-i-s');
$dbName = 'crm_quantun';
$file   = "{$backupDir}/backup_{$dbName}_{$fecha}.sql";

$sql  = "-- ============================================================\n";
$sql .= "-- CRM QUANTUN Digital — Backup de base de datos\n";
$sql .= "-- Generado: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Base de datos: {$dbName}\n";
$sql .= "-- ============================================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sql .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
$sql .= "SET NAMES utf8mb4;\n\n";

// Obtener todas las tablas
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $sql .= "-- ------------------------------------------------------------\n";
    $sql .= "-- Tabla: `{$table}`\n";
    $sql .= "-- ------------------------------------------------------------\n";

    // Estructura
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $sql .= $create[1] . ";\n\n";

    // Datos
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
        $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
        $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
        $values = [];
        foreach ($rows as $row) {
            $escaped = array_map(function($v) use ($pdo) {
                if ($v === null) return 'NULL';
                return $pdo->quote($v);
            }, array_values($row));
            $values[] = '(' . implode(', ', $escaped) . ')';
        }
        $sql .= implode(",\n", $values) . ";\n\n";
    } else {
        $sql .= "-- (sin datos)\n\n";
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
$sql .= "-- FIN DEL BACKUP\n";

file_put_contents($file, $sql);
$sizeKb = round(filesize($file) / 1024, 1);

// Respuesta visual
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Backup — CRM QUANTUN</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 40px; max-width: 520px; width: 100%; text-align: center; }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { color: #c9f31d; font-size: 22px; margin-bottom: 8px; }
        p  { color: #94a3b8; font-size: 14px; margin-bottom: 24px; line-height: 1.6; }
        .meta { background: #0f172a; border-radius: 10px; padding: 16px 20px; text-align: left; margin-bottom: 24px; }
        .meta-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
        .meta-row .label { color: #64748b; }
        .meta-row .val   { color: #e2e8f0; font-weight: 600; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 14px; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #c9f31d; color: #000; margin-right: 8px; }
        .btn-back { background: #334155; color: #e2e8f0; }
        .btn:hover { opacity: .85; }
        .filename { word-break: break-all; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">✅</div>
    <h1>Backup generado</h1>
    <p>El backup de la base de datos fue creado correctamente en la carpeta <strong>/backups/</strong> dentro del proyecto.</p>

    <div class="meta">
        <div class="meta-row">
            <span class="label">Archivo</span>
            <span class="val filename">backup_<?= $dbName ?>_<?= $fecha ?>.sql</span>
        </div>
        <div class="meta-row">
            <span class="label">Tablas respaldadas</span>
            <span class="val"><?= count($tables) ?> tablas</span>
        </div>
        <div class="meta-row">
            <span class="label">Tamaño</span>
            <span class="val"><?= $sizeKb ?> KB</span>
        </div>
        <div class="meta-row">
            <span class="label">Fecha</span>
            <span class="val"><?= date('d/m/Y H:i:s') ?></span>
        </div>
    </div>

    <a class="btn btn-primary" href="backups/backup_<?= $dbName ?>_<?= $fecha ?>.sql" download>⬇ Descargar SQL</a>
    <a class="btn btn-back" href="dashboard.php">← Dashboard</a>
</div>
</body>
</html>
