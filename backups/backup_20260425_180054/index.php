<?php
/**
 * CRM QUANTUN Digital - Login
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } elseif (login($email, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Credenciales incorrectas. Intenta de nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acceso al CRM Operativo de QUANTUN Digital">
    <title>Iniciar Sesión | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="Assets/logo_quantun_digital_negro.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=<?= APP_VERSION ?>">
</head>
<body>
    <div class="login-page">
        <!-- Animated background dots -->
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-dot"></div>

        <!-- Background gradient pattern -->
        <div class="login-bg-pattern"></div>

        <div class="login-container">
            <div class="login-card">
                <!-- Logo -->
                <div class="login-logo">
                    <img src="Assets/logo_quantun_digital_blanco.png" alt="QUANTUN Digital">
                </div>

                <h1 class="login-title">Bienvenido</h1>
                <p class="login-subtitle">Accede a tu panel de control operativo</p>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="login-error" id="loginError">
                        <?= sanitize($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" class="login-form" id="loginForm" autocomplete="on">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="tu@email.com"
                            value="<?= isset($email) ? sanitize($email) : '' ?>"
                            required 
                            autofocus
                            autocomplete="email"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Contraseña</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <button type="submit" class="login-btn" id="loginBtn">
                        Ingresar al CRM
                    </button>
                </form>

                <div class="login-footer">
                    © <?= date('Y') ?> QUANTUN Digital · Soluciones Digitales
                </div>
            </div>
        </div>
    </div>

    <script>
        // Deshabilitar botón mientras se envía el formulario
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.textContent = 'Verificando...';
        });
    </script>
</body>
</html>
