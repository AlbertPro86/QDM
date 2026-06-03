<?php
/**
 * CRM QUANTUN Digital - API de Archivos de Clientes
 * Guarda archivos en uploads/clientes/{año}/{mes}/
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

// Auto-migración
try { $pdo->exec("ALTER TABLE clientes_archivos ADD COLUMN descripcion VARCHAR(500) DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE clientes_archivos ADD COLUMN fecha_documento DATE DEFAULT NULL"); } catch(PDOException $e){}
try { $pdo->exec("ALTER TABLE clientes_archivos ADD COLUMN visible_cliente TINYINT(1) NOT NULL DEFAULT 1"); } catch(PDOException $e){}

switch ($method) {

    case 'GET':
        $cliente_id = $_GET['cliente_id'] ?? null;
        if (!$cliente_id) jsonResponse(['error' => 'ID de cliente requerido'], 400);
        $stmt = $pdo->prepare("SELECT * FROM clientes_archivos WHERE cliente_id = ? ORDER BY created_at DESC");
        $stmt->execute([$cliente_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $cliente_id  = $_POST['cliente_id'] ?? null;
        if (!$cliente_id || empty($_FILES['archivo'])) jsonResponse(['error' => 'Datos incompletos'], 400);

        $file = $_FILES['archivo'];

        // Validar tamaño (20 MB máx)
        if ($file['size'] > 20 * 1024 * 1024) {
            jsonResponse(['error' => 'El archivo supera los 20 MB'], 400);
        }

        // Carpeta por año/mes
        $year     = date('Y');
        $month    = date('m');
        $dir      = __DIR__ . '/../uploads/clientes/' . $year . '/' . $month . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin');
        $newName = 'cnt_' . uniqid() . '.' . $ext;
        $target  = $dir . $newName;
        $relPath = 'uploads/clientes/' . $year . '/' . $month . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            jsonResponse(['error' => 'Error al guardar el archivo'], 500);
        }

        $descripcion      = isset($_POST['descripcion'])      ? trim($_POST['descripcion'])    : null;
        $fechaDocumento   = !empty($_POST['fecha_documento'])  ? $_POST['fecha_documento']      : date('Y-m-d');
        $visibleCliente   = isset($_POST['visible_cliente'])   ? (int)$_POST['visible_cliente'] : 1;

        $stmt = $pdo->prepare(
            "INSERT INTO clientes_archivos
                (cliente_id, nombre_archivo, archivo_url, tipo_archivo, peso_archivo, descripcion, fecha_documento, visible_cliente)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $cliente_id,
            $file['name'],
            $relPath,
            $file['type'],
            $file['size'],
            $descripcion ?: null,
            $fechaDocumento,
            $visibleCliente,
        ]);

        jsonResponse(['success' => true, 'message' => 'Archivo subido correctamente']);
        break;

    case 'PATCH':
        // Actualizar visible_cliente
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $id             = intval($input['id'] ?? 0);
        $visibleCliente = intval($input['visible_cliente'] ?? 1);
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $pdo->prepare("UPDATE clientes_archivos SET visible_cliente = ? WHERE id = ?")->execute([$visibleCliente, $id]);
        jsonResponse(['success' => true, 'visible_cliente' => $visibleCliente]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

        $stmt = $pdo->prepare("SELECT archivo_url FROM clientes_archivos WHERE id = ?");
        $stmt->execute([$id]);
        $archivo = $stmt->fetch();

        if ($archivo) {
            $filePath = __DIR__ . '/../' . $archivo['archivo_url'];
            if (file_exists($filePath)) unlink($filePath);
            $pdo->prepare("DELETE FROM clientes_archivos WHERE id = ?")->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'Archivo eliminado']);
        } else {
            jsonResponse(['error' => 'Archivo no encontrado'], 404);
        }
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
