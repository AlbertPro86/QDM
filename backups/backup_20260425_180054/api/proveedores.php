<?php
/**
 * CRM QUANTUN Digital - API de Proveedores
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

// Crear tabla si no existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS proveedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        nit VARCHAR(50),
        email VARCHAR(150),
        telefono VARCHAR(20),
        ciudad VARCHAR(100),
        direccion TEXT,
        categoria VARCHAR(50),
        notas TEXT,
        activo TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {}
// Auto-migración: ciudad
try { $pdo->exec("ALTER TABLE proveedores ADD COLUMN ciudad VARCHAR(100) DEFAULT NULL"); } catch(PDOException $e) {}

// GET: Listar todos los proveedores (con filtros opcionales)
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // GET ?id=X → obtener un proveedor
        $id = (int) $_GET['id'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ? AND activo = 1");
            $stmt->execute([$id]);
            $proveedor = $stmt->fetch();
            if (!$proveedor) {
                http_response_code(404);
                echo json_encode(['error' => 'Proveedor no encontrado']);
            } else {
                echo json_encode(['success' => true, 'data' => $proveedor]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        // GET sin parámetros → listar todos (con filtros q y cat)
        try {
            $q = $_GET['q'] ?? '';
            $cat = $_GET['cat'] ?? '';
            $activo = isset($_GET['activo']) ? (int) $_GET['activo'] : 1;

            $sql = "SELECT * FROM proveedores WHERE activo = ?";
            $params = [$activo];

            if ($q) {
                $sql .= " AND (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)";
                $search = "%$q%";
                $params = array_merge([$activo], [$search, $search, $search]);
            }

            if ($cat) {
                $sql .= " AND categoria = ?";
                $params[] = $cat;
            }

            $sql .= " ORDER BY nombre ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $proveedores = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $proveedores]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    exit;
}

// POST: Crear nuevo proveedor
if ($method === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre del proveedor es requerido']);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO proveedores
            (nombre, nit, email, telefono, ciudad, direccion, categoria, notas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $input['nombre'],
            $input['nit'] ?? null,
            $input['email'] ?? null,
            $input['telefono'] ?? null,
            $input['ciudad'] ?? null,
            $input['direccion'] ?? null,
            $input['categoria'] ?? null,
            $input['notas'] ?? null
        ]);

        $id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// PUT: Actualizar proveedor
if ($method === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID requerido']);
            exit;
        }

        if (empty($input['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre del proveedor es requerido']);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE proveedores
            SET nombre = ?, nit = ?, email = ?, telefono = ?, ciudad = ?, direccion = ?, categoria = ?, notas = ?
            WHERE id = ?"
        );

        $stmt->execute([
            $input['nombre'],
            $input['nit'] ?? null,
            $input['email'] ?? null,
            $input['telefono'] ?? null,
            $input['ciudad'] ?? null,
            $input['direccion'] ?? null,
            $input['categoria'] ?? null,
            $input['notas'] ?? null,
            $id
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// DELETE: Eliminar proveedor (soft delete)
if ($method === 'DELETE') {
    try {
        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID requerido']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE proveedores SET activo = 0 WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
?>
