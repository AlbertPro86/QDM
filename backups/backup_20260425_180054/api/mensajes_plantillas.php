<?php
/**
 * CRM QUANTUN Digital - API de Plantillas de Mensajes
 * Gestiona la creación y uso de plantillas de mensajes para WhatsApp
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) jsonResponse(['error' => 'No autorizado'], 401);

$pdo = db();

// PHP no parsea $_POST/$_FILES en PUT multipart → usar _method override
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && !empty($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

// ── Helper: mover imagen subida ──────────────────────────────────────────────
function subirImagenPlantilla(): array {
    // Sin archivo enviado
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }
    $err = $_FILES['imagen']['error'];
    if ($err !== UPLOAD_ERR_OK) {
        $msgs = [
            UPLOAD_ERR_INI_SIZE   => 'Archivo supera upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE  => 'Archivo supera MAX_FILE_SIZE del formulario',
            UPLOAD_ERR_PARTIAL    => 'Archivo subido parcialmente',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir en disco',
            UPLOAD_ERR_EXTENSION  => 'Extensión PHP bloqueó la subida',
        ];
        return ['path' => null, 'error' => $msgs[$err] ?? "Error de subida código $err"];
    }
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        return ['path' => null, 'error' => "Tipo de archivo no permitido: .$ext"];
    }
    $dir = __DIR__ . '/../uploads/plantillas/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return ['path' => null, 'error' => 'No se pudo crear el directorio de subida'];
    }
    $filename = 'plt_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $dest = $dir . $filename;
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
        return ['path' => 'uploads/plantillas/' . $filename, 'error' => null];
    }
    return ['path' => null, 'error' => 'move_uploaded_file falló — destino: ' . $dest];
}
function borrarImagenPlantilla(?string $ruta): void {
    if (!$ruta) return;
    $abs = __DIR__ . '/../' . ltrim($ruta, '/');
    if (file_exists($abs)) @unlink($abs);
}

// Auto-migración: crear tabla si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mensajes_plantillas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT DEFAULT NULL,
            contenido LONGTEXT NOT NULL,
            categoria VARCHAR(50) DEFAULT 'general',
            es_predefinida TINYINT(1) DEFAULT 0,
            activa TINYINT(1) DEFAULT 1,
            creado_por INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_activa (activa),
            INDEX idx_categoria (categoria),
            INDEX idx_creado (creado_por)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) { /* tabla ya existe */ }

// Auto-migración: agregar columna imagen si no existe
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM mensajes_plantillas LIKE 'imagen'");
    if ($colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE mensajes_plantillas ADD COLUMN imagen VARCHAR(255) DEFAULT NULL");
    }
} catch(PDOException $e) { /* tabla no existe aún */ }

// Auto-migración: agregar columna logo_url si no existe
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM mensajes_plantillas LIKE 'logo_url'");
    if ($colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE mensajes_plantillas ADD COLUMN logo_url VARCHAR(500) DEFAULT NULL");
    }
} catch(PDOException $e) { /* tabla no existe aún */ }

// Insertar plantillas predefinidas si no existen
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM mensajes_plantillas WHERE es_predefinida = 1");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $plantillas = [
            [
                'nombre' => 'Confirmación de Cotización',
                'descripcion' => 'Se envía cuando se confirma una cotización',
                'contenido' => 'Hola {{cliente_nombre}}, te adjuntamos la cotización #{{numero_cotizacion}} con los detalles de nuestros servicios. Total: {{total}} {{moneda}}. ¿Tienes alguna pregunta?',
                'categoria' => 'cotizaciones'
            ],
            [
                'nombre' => 'Recordatorio de Cotización',
                'descripcion' => 'Recordatorio de una cotización pendiente',
                'contenido' => 'Hola {{cliente_nombre}}, te recordamos que tienes pendiente la revisión de la cotización #{{numero_cotizacion}}. Si tienes dudas, estaremos disponibles. ¿Cuándo podemos agendar una llamada?',
                'categoria' => 'cotizaciones'
            ],
            [
                'nombre' => 'Cotización Aceptada',
                'descripcion' => 'Notificación cuando una cotización es aceptada',
                'contenido' => '¡Excelente! {{cliente_nombre}}, hemos recibido tu aceptación de la cotización #{{numero_cotizacion}}. Procederemos con la ejecución del proyecto. Nos pondremos en contacto pronto con los próximos pasos.',
                'categoria' => 'cotizaciones'
            ],
            [
                'nombre' => 'Cotización Rechazada',
                'descripcion' => 'Notificación de cotización rechazada',
                'contenido' => 'Hola {{cliente_nombre}}, vemos que has rechazado la cotización #{{numero_cotizacion}}. Si tienes dudas sobre el alcance o presupuesto, con gusto podemos hacer ajustes. ¿Hablamos?',
                'categoria' => 'cotizaciones'
            ],
            [
                'nombre' => 'Saludo General',
                'descripcion' => 'Mensaje de presentación inicial',
                'contenido' => 'Hola {{cliente_nombre}}, somos QUANTUN Digital. Nos especializamos en soluciones web y de marca para empresas. ¿Te gustaría conocer cómo podemos ayudarte a crecer? 🚀',
                'categoria' => 'general'
            ],
            [
                'nombre' => 'Respuesta a Consulta',
                'descripcion' => 'Respuesta estándar a consultas',
                'contenido' => 'Hola {{cliente_nombre}}, gracias por tu consulta. Revisamos tu solicitud y pronto te enviaremos una propuesta personalizada. En el tiempo, ¿cuál es tu disponibilidad para una llamada?',
                'categoria' => 'general'
            ]
        ];

        foreach ($plantillas as $p) {
            $stmt = $pdo->prepare("
                INSERT INTO mensajes_plantillas (nombre, descripcion, contenido, categoria, es_predefinida, activa, creado_por)
                VALUES (?, ?, ?, ?, 1, 1, NULL)
            ");
            $stmt->execute([$p['nombre'], $p['descripcion'], $p['contenido'], $p['categoria']]);
        }
    }
} catch (PDOException $e) { /* error al insertar */ }

switch ($method) {
    case 'GET':
        try {
            // Listar plantillas
            if (!isset($_GET['id'])) {
                $filtro = $_GET['filtro'] ?? 'todas';

                if ($filtro === 'predefinidas') {
                    $stmt = $pdo->query("
                        SELECT id, nombre, descripcion, contenido, categoria, es_predefinida, activa, imagen, logo_url, created_at
                        FROM mensajes_plantillas
                        WHERE es_predefinida = 1
                        ORDER BY categoria, nombre
                    ");
                } elseif ($filtro === 'personalizadas') {
                    $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0;
                    $stmt = $pdo->prepare("
                        SELECT id, nombre, descripcion, contenido, categoria, es_predefinida, activa, imagen, logo_url, created_at
                        FROM mensajes_plantillas
                        WHERE es_predefinida = 0 AND creado_por = ?
                        ORDER BY categoria, nombre
                    ");
                    $stmt->execute([$usuario_id]);
                } else {
                    $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0;
                    $stmt = $pdo->prepare("
                        SELECT id, nombre, descripcion, contenido, categoria, es_predefinida, activa, imagen, logo_url, created_at
                        FROM mensajes_plantillas
                        WHERE es_predefinida = 1 OR creado_por = ?
                        ORDER BY es_predefinida DESC, categoria, nombre
                    ");
                    $stmt->execute([$usuario_id]);
                }

                $plantillas = $stmt->fetchAll();
                jsonResponse(['success' => true, 'data' => $plantillas]);
            }
            // Obtener una plantilla por ID
            else {
                $id = intval($_GET['id']);
                $stmt = $pdo->prepare("
                    SELECT * FROM mensajes_plantillas WHERE id = ?
                ");
                $stmt->execute([$id]);
                $plantilla = $stmt->fetch();

                if (!$plantilla) {
                    jsonResponse(['error' => 'Plantilla no encontrada'], 404);
                }
                jsonResponse(['success' => true, 'data' => $plantilla]);
            }
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Error al obtener plantillas'], 500);
        }
        break;

    case 'POST':
        try {
            // Soporta multipart/form-data (con imagen) y application/json
            $isMultipart = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart') !== false;
            $input = $isMultipart ? $_POST : json_decode(file_get_contents('php://input'), true);

            // ── Acción: Clonar plantilla ──────────────────────────────────────
            if ($input['action'] === 'clonar') {
                $plantillaId = intval($input['id'] ?? 0);
                $nombreNuevo = trim($input['nombre'] ?? '');

                if (!$plantillaId || !$nombreNuevo) {
                    jsonResponse(['error' => 'ID de plantilla y nombre nuevo son requeridos'], 400);
                }

                // Obtener plantilla original
                $stmt = $pdo->prepare("SELECT * FROM mensajes_plantillas WHERE id = ?");
                $stmt->execute([$plantillaId]);
                $plantillaOriginal = $stmt->fetch();

                if (!$plantillaOriginal) {
                    jsonResponse(['error' => 'Plantilla no encontrada'], 404);
                }

                $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;

                // Si la plantilla original no tiene logo_url, usar el de configuraciones
                $logoUrlClon = $plantillaOriginal['logo_url'] ?: getCfg($pdo, 'notif_logo_url', null);

                // Crear copia
                $stmt = $pdo->prepare("
                    INSERT INTO mensajes_plantillas
                    (nombre, descripcion, contenido, categoria, es_predefinida, activa, imagen, logo_url, creado_por)
                    VALUES (?, ?, ?, ?, 0, 1, ?, ?, ?)
                ");

                $stmt->execute([
                    $nombreNuevo,
                    $plantillaOriginal['descripcion'] ?? null,
                    $plantillaOriginal['contenido'],
                    $plantillaOriginal['categoria'] ?? 'general',
                    $plantillaOriginal['imagen'],
                    $logoUrlClon,
                    $usuario_id
                ]);

                $idNueva = $pdo->lastInsertId();
                jsonResponse(['success' => true, 'id' => $idNueva, 'message' => 'Plantilla clonada exitosamente']);
                break;
            }

            // ── Acción: Crear nueva plantilla ──────────────────────────────────
            if (empty($input['nombre']) || empty($input['contenido'])) {
                jsonResponse(['error' => 'Nombre y contenido son requeridos'], 400);
            }

            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
            $upload = subirImagenPlantilla();
            if ($upload['error']) jsonResponse(['error' => 'Error al subir imagen: ' . $upload['error']], 400);
            $imagenPath = $upload['path'];

            $stmt = $pdo->prepare("
                INSERT INTO mensajes_plantillas
                (nombre, descripcion, contenido, categoria, es_predefinida, activa, imagen, logo_url, creado_por)
                VALUES (?, ?, ?, ?, 0, 1, ?, ?, ?)
            ");

            $stmt->execute([
                $input['nombre'],
                $input['descripcion'] ?? null,
                $input['contenido'],
                $input['categoria'] ?? 'general',
                $imagenPath,
                !empty($input['logo_url']) ? trim($input['logo_url']) : null,
                $usuario_id
            ]);

            $id = $pdo->lastInsertId();
            jsonResponse(['success' => true, 'id' => $id, 'imagen' => $imagenPath, 'message' => 'Plantilla creada exitosamente']);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Error al crear plantilla: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        try {
            $isMultipart = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart') !== false;
            $input = $isMultipart ? $_POST : json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;

            if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;

            // Obtener plantilla actual
            $stmt = $pdo->prepare("SELECT * FROM mensajes_plantillas WHERE id = ?");
            $stmt->execute([$id]);
            $plantilla = $stmt->fetch();

            if (!$plantilla) jsonResponse(['error' => 'Plantilla no encontrada'], 404);

            // Solo bloquear si no es predefinida y el dueño no coincide
            if (!$plantilla['es_predefinida'] && $plantilla['creado_por'] != $usuario_id) {
                jsonResponse(['error' => 'No tienes permisos para editar esta plantilla'], 403);
            }

            // Manejar imagen: nueva subida, eliminar, o conservar
            $imagenPath = $plantilla['imagen'] ?? null; // conservar por defecto
            $upload = subirImagenPlantilla();
            if ($upload['error']) jsonResponse(['error' => 'Error al subir imagen: ' . $upload['error']], 400);
            if ($upload['path']) {
                borrarImagenPlantilla($plantilla['imagen'] ?? null);
                $imagenPath = $upload['path'];
            } elseif (!empty($input['quitar_imagen'])) {
                borrarImagenPlantilla($plantilla['imagen'] ?? null);
                $imagenPath = null;
            }

            $logoUrl = !empty($input['logo_url']) ? trim($input['logo_url']) : ($plantilla['logo_url'] ?? null);
            // Si viene vacío explícitamente, limpiar
            if (isset($input['logo_url']) && trim($input['logo_url']) === '') $logoUrl = null;

            $stmt = $pdo->prepare("
                UPDATE mensajes_plantillas
                SET nombre=?, descripcion=?, contenido=?, categoria=?, activa=?, imagen=?, logo_url=?, updated_at=NOW()
                WHERE id=?
            ");

            $stmt->execute([
                $input['nombre']      ?? $plantilla['nombre'],
                $input['descripcion'] ?? null,
                $input['contenido']   ?? $plantilla['contenido'],
                $input['categoria']   ?? 'general',
                isset($input['activa']) ? intval($input['activa']) : 1,
                $imagenPath,
                $logoUrl,
                $id
            ]);

            jsonResponse(['success' => true, 'imagen' => $imagenPath, 'message' => 'Plantilla actualizada exitosamente']);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Error al actualizar plantilla: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = intval($input['id'] ?? 0);

            if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

            $stmt = $pdo->prepare("SELECT imagen FROM mensajes_plantillas WHERE id = ?");
            $stmt->execute([$id]);
            $plantilla = $stmt->fetch();

            if (!$plantilla) jsonResponse(['error' => 'Plantilla no encontrada'], 404);

            borrarImagenPlantilla($plantilla['imagen'] ?? null);

            $pdo->prepare("DELETE FROM mensajes_plantillas WHERE id = ?")->execute([$id]);

            jsonResponse(['success' => true, 'message' => 'Plantilla eliminada']);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Error al eliminar plantilla'], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
