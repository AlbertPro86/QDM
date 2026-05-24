<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$user = getCurrentUser();
$pdo = db();

// Helper: botón ojo para campos password
function pwEye(string $inputId): string {
    return '<button type="button" class="pw-eye" onclick="togglePw(\'' . $inputId . '\', this)" tabindex="-1" aria-label="Mostrar contraseña">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
    </button>';
}

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        try {
            if (!$nombre) {
                setFlash('error', 'El nombre es requerido.');
            } elseif (!$email) {
                setFlash('error', 'El email es requerido.');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                setFlash('error', 'El email no es válido.');
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $user['id']]);
                $_SESSION['user_nombre'] = $nombre;
                $_SESSION['user_email'] = $email;
                setFlash('success', '✓ Perfil actualizado correctamente');
            }
        } catch (Exception $e) {
            setFlash('error', 'Error al actualizar: ' . $e->getMessage());
        }
        header('Location: perfil.php'); exit;
    }
    if ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = trim($_POST['new_password']     ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        try {
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$user['id']]);
            $dbUser = $stmt->fetch();

            if (!$dbUser) {
                setFlash('error', 'Usuario no encontrado.');
            } elseif (strlen($new) < 6) {
                setFlash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
            } elseif ($new !== $confirm) {
                setFlash('error', 'Las contraseñas nuevas no coinciden.');
            } else {
                $stored = $dbUser['password'];
                // Soportar bcrypt Y contraseñas legacy en texto plano (migración automática)
                $passOk = password_verify($current, $stored) || ($stored === $current);
                if (!$passOk) {
                    setFlash('error', 'La contraseña actual es incorrecta.');
                } else {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")
                        ->execute([$hash, $user['id']]);
                    setFlash('success', '¡Contraseña actualizada correctamente!');
                }
            }
        } catch (Exception $e) {
            setFlash('error', 'Error al actualizar: ' . $e->getMessage());
        }
        header('Location: perfil.php'); exit;
    }
}

// Obtener datos frescos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

// Últimas actividades
$stmt = $pdo->prepare("SELECT * FROM actividad_log WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$actividades = $stmt->fetchAll();

$pageTitle = 'Configuración';
$pageSubtitle = '';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:600;color:var(--color-text)">Perfil</span>';
include __DIR__ . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px">
    <div>
        <!-- Perfil -->
        <div class="card mb-6">
            <div class="card-header"><h2 class="card-title">Información del Perfil</h2></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div class="form-group"><label class="form-label">Nombre</label><input type="text" class="form-input" name="nombre" value="<?= sanitize($userData['nombre']) ?>" required></div>
                        <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-input" name="email" value="<?= sanitize($userData['email']) ?>" required></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div class="form-group"><label class="form-label">Rol</label><input type="text" class="form-input" value="<?= ucfirst($userData['rol']) ?>" disabled></div>
                        <div class="form-group"><label class="form-label">Último Login</label><input type="text" class="form-input" value="<?= $userData['ultimo_login'] ? formatDate($userData['ultimo_login'], 'd M Y H:i') : 'Nunca' ?>" disabled></div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:20px">
                        <button type="submit" class="btn btn-secondary" style="flex:1;padding:11px 16px;font-weight:700;font-size:13px">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:inline;margin-right:6px"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contraseña -->
        <div class="card">
            <div class="card-header" style="background:#fef3c7;border-bottom:1.5px solid #fcd34d">
                <h2 class="card-title" style="color:#92400e">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2" style="display:inline;margin-right:8px;vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Cambiar Contraseña
                </h2>
            </div>
            <div class="card-body">
                <p style="font-size:12px;color:#64748b;margin-bottom:16px">Solo ingresa tu contraseña actual cuando desees cambiarla por una nueva.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Contraseña Actual</label>
                        <div class="pw-wrap"><input type="password" class="form-input" name="current_password" id="pw_current" required placeholder="Ingresa tu contraseña actual"><?= pwEye('pw_current') ?></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div class="form-group">
                            <label class="form-label">Nueva Contraseña</label>
                            <div class="pw-wrap"><input type="password" class="form-input" name="new_password" id="pw_new" required minlength="6" placeholder="Mínimo 6 caracteres"><?= pwEye('pw_new') ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmar</label>
                            <div class="pw-wrap"><input type="password" class="form-input" name="confirm_password" id="pw_confirm" required placeholder="Repite la nueva contraseña"><?= pwEye('pw_confirm') ?></div>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:20px">
                        <button type="submit" class="btn btn-primary" style="flex:1;padding:11px 16px;font-weight:700;font-size:13px">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:inline;margin-right:6px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Actualizar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="card" style="height:fit-content">
        <div class="card-header"><h2 class="card-title">Actividad Reciente</h2></div>
        <div class="card-body" style="padding:12px 20px">
            <?php if (empty($actividades)): ?>
                <p style="text-align:center;color:var(--color-text-light);padding:30px 0;font-size:14px">Sin actividad registrada</p>
            <?php else: ?>
            <?php foreach ($actividades as $a): ?>
            <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--color-border-light)">
                <div style="width:8px;height:8px;border-radius:50%;background:var(--q-lima);margin-top:6px;flex-shrink:0"></div>
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--color-text)"><?= sanitize($a['accion']) ?> — <?= sanitize($a['entidad']) ?></div>
                    <div style="font-size:12px;color:var(--color-text-muted)"><?= $a['detalles'] ? sanitize($a['detalles']) : '' ?></div>
                    <div style="font-size:11px;color:var(--color-text-light);margin-top:2px"><?= timeAgo($a['created_at']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media(max-width:1024px){
    div[style*="grid-template-columns:1fr 380px"]{grid-template-columns:1fr !important;}
}
.pw-wrap {
    position: relative;
}
.pw-wrap .form-input {
    padding-right: 42px;
}
.pw-eye {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    color: #94a3b8;
    display: flex;
    align-items: center;
    transition: color .15s;
}
.pw-eye:hover { color: #4f46e5; }
</style>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    // Cambiar ícono
    btn.innerHTML = isText ? eyeClosedSvg() : eyeOpenSvg();
}
function eyeOpenSvg() {
    return '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
}
function eyeClosedSvg() {
    return '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
